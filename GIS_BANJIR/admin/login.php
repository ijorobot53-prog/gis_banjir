<?php
/**
 * Halaman Login Admin
 * File: admin/login.php
 *
 * Menangani autentikasi admin dengan:
 * - Proteksi CSRF
 * - Rate limiting (max 5 percobaan per 15 menit)
 * - password_verify() untuk cek password hash
 * - session_regenerate_id() setelah login berhasil
 */

// Mulai session sebelum apapun
session_start();

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: /GIS_BANJIR/admin/index.php');
    exit;
}

// Include konfigurasi database dan fungsi helper
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================================================
// Konfigurasi Rate Limiting
// ============================================================
define('LOGIN_MAX_ATTEMPTS', 5);    // Maksimum percobaan
define('LOGIN_LOCKOUT_TIME', 900);  // Waktu lockout: 15 menit (detik)

// Variabel pesan error
$errorMsg = '';
$isLocked = false;

// ============================================================
// Cek apakah sedang dalam kondisi terkunci (rate limit)
// ============================================================
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
    $elapsed = time() - ($_SESSION['login_last_attempt'] ?? 0);
    if ($elapsed < LOGIN_LOCKOUT_TIME) {
        // Masih terkunci
        $sisaDetik = LOGIN_LOCKOUT_TIME - $elapsed;
        $sisaMenit = ceil($sisaDetik / 60);
        $isLocked  = true;
        $errorMsg  = "Terlalu banyak percobaan login. Coba lagi dalam {$sisaMenit} menit.";
    } else {
        // Waktu lockout sudah habis, reset counter
        unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);
    }
}

// ============================================================
// Generate token CSRF untuk form
// ============================================================
$csrfToken = generateCSRF();

// ============================================================
// Proses form POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {

    // 1. Verifikasi CSRF token
    $tokenKirim = $_POST['csrf_token'] ?? '';
    if (!verifyCSRF($tokenKirim)) {
        $errorMsg = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        // 2. Ambil dan sanitasi input
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // 3. Validasi input tidak kosong
        if (empty($username) || empty($password)) {
            $errorMsg = 'Username dan password harus diisi.';
        } else {
            // 4. Cari user di database berdasarkan username
            try {
                $db   = getDB();
                $stmt = $db->prepare("SELECT id, username, password, nama_lengkap, role FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // 5. Verifikasi password
                if ($user && password_verify($password, $user['password'])) {
                    // ---- LOGIN BERHASIL ----

                    // Reset counter percobaan gagal
                    unset($_SESSION['login_attempts'], $_SESSION['login_last_attempt']);

                    // Regenerate session ID untuk mencegah session fixation
                    session_regenerate_id(true);

                    // Simpan data user ke session
                    $_SESSION['admin_id']    = $user['id'];
                    $_SESSION['admin_nama']  = $user['nama_lengkap'];
                    $_SESSION['admin_role']  = $user['role'];

                    // Redirect ke dashboard
                    header('Location: /GIS_BANJIR/admin/index.php');
                    exit;

                } else {
                    // ---- LOGIN GAGAL ----

                    // Increment counter percobaan
                    $_SESSION['login_attempts']      = ($_SESSION['login_attempts'] ?? 0) + 1;
                    $_SESSION['login_last_attempt']  = time();

                    $sisaCobaan = LOGIN_MAX_ATTEMPTS - $_SESSION['login_attempts'];

                    if ($sisaCobaan > 0) {
                        $errorMsg = "Username atau password salah. Sisa percobaan: {$sisaCobaan}.";
                    } else {
                        $isLocked = true;
                        $errorMsg = 'Terlalu banyak percobaan. Akun dikunci selama 15 menit.';
                    }
                }
            } catch (PDOException $e) {
                // Jangan tampilkan detail error ke user
                $errorMsg = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin &mdash; SIG Banjir</title>

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #1a56db 50%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Kartu login */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,.25);
        }

        /* Header kartu */
        .login-header {
            background: linear-gradient(135deg, #1e3a5f, #1a56db);
            padding: 36px 32px 28px;
            text-align: center;
        }

        .login-icon {
            width: 68px; height: 68px;
            background: rgba(255,255,255,.15);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
            margin-bottom: 16px;
            border: 2px solid rgba(255,255,255,.25);
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px;
        }

        .login-header p {
            font-size: 13px;
            color: rgba(255,255,255,.75);
            margin: 0;
        }

        /* Body form */
        .login-body {
            padding: 32px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-control {
            padding: 10px 14px;
            font-size: 14px;
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,.12);
            outline: none;
        }

        .input-group .form-control { border-radius: 10px 0 0 10px !important; }
        .input-group .btn           { border-radius: 0 10px 10px 0 !important; border-color: #e5e7eb; }

        .btn-login {
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            border: none;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            transition: opacity .2s, transform .1s;
            cursor: pointer;
        }

        .btn-login:hover    { opacity: .92; }
        .btn-login:active   { transform: scale(.98); }
        .btn-login:disabled { opacity: .65; cursor: not-allowed; }

        /* Pesan error */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b91c1c;
            font-size: 13px;
            padding: 12px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }

        .alert-error i { flex-shrink: 0; margin-top: 1px; }

        /* Indikator percobaan tersisa */
        .attempt-indicator {
            display: flex;
            gap: 5px;
            justify-content: center;
            margin-top: 10px;
        }

        .attempt-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #e5e7eb;
        }

        .attempt-dot.used { background: #ef4444; }

        /* Footer kartu */
        .login-footer {
            text-align: center;
            padding: 0 32px 28px;
        }

        .login-footer a {
            color: #1a56db;
            font-size: 13px;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover { text-decoration: underline; }

        /* Copyright */
        .login-copyright {
            text-align: center;
            color: rgba(255,255,255,.6);
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <!-- Kartu Login -->
    <div class="login-card">

        <!-- Header -->
        <div class="login-header">
            <div class="login-icon">
                <i class="fa-solid fa-water"></i>
            </div>
            <h1>Login Admin</h1>
            <p>Sistem Informasi Geografis Rawan Banjir</p>
        </div>

        <!-- Body Form -->
        <div class="login-body">

            <?php if (!empty($errorMsg)): ?>
            <!-- Pesan Error -->
            <div class="alert-error" role="alert" aria-live="polite">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
            <!-- Indikator percobaan tersisa -->
            <div class="attempt-indicator" aria-label="Indikator percobaan login">
                <?php for ($i = 0; $i < LOGIN_MAX_ATTEMPTS; $i++): ?>
                    <div class="attempt-dot <?= ($i < ($_SESSION['login_attempts'] ?? 0)) ? 'used' : '' ?>"></div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="/GIS_BANJIR/admin/login.php" novalidate
                  id="loginForm" autocomplete="off">

                <!-- Token CSRF tersembunyi -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fa-solid fa-user me-1 text-primary"></i> Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username"
                        required
                        <?= $isLocked ? 'disabled' : '' ?>
                        aria-required="true"
                    >
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fa-solid fa-lock me-1 text-primary"></i> Password
                    </label>
                    <div class="input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                            <?= $isLocked ? 'disabled' : '' ?>
                            aria-required="true"
                        >
                        <!-- Tombol tampilkan/sembunyikan password -->
                        <button type="button" class="btn btn-outline-secondary"
                                id="togglePassword" aria-label="Tampilkan/Sembunyikan Password"
                                <?= $isLocked ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="btn-login" <?= $isLocked ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-right-to-bracket me-2"></i>
                    <?= $isLocked ? 'Akun Terkunci Sementara' : 'Masuk ke Panel Admin' ?>
                </button>

            </form>
        </div>

        <!-- Footer Kartu -->
        <div class="login-footer">
            <a href="/GIS_BANJIR/index.php">
                <i class="fa-solid fa-arrow-left me-1"></i>
                Kembali ke Website Utama
            </a>
        </div>
    </div>

    <!-- Copyright -->
    <p class="login-copyright">
        &copy; <?= date('Y') ?> SIG Rawan Banjir &mdash; BPBD Kota Medan
    </p>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle tampilkan/sembunyikan password
document.getElementById('togglePassword').addEventListener('click', function () {
    const passInput = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');

    if (passInput.type === 'password') {
        passInput.type = 'text';
        eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passInput.type = 'password';
        eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});

// Nonaktifkan tombol submit saat form dikirim agar tidak double submit
document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = this.querySelector('.btn-login');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Memproses...';
});
</script>

</body>
</html>
