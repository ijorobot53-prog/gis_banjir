<?php
/**
 * Halaman Manajemen User Admin
 * File: admin/users.php
 *
 * Fitur:
 * - List user admin dengan role badge
 * - Tambah/Edit user via modal Bootstrap
 * - Password wajib diisi saat tambah, opsional saat edit (kosong = tidak ubah)
 * - Tidak bisa hapus user sendiri yang sedang login
 * - CSRF protection semua form
 */

// ============================================================
// Cek Session — HARUS di paling atas sebelum include apapun
// ============================================================
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /GIS_BANJIR/admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// ============================================================
// Proses Form POST (Tambah / Edit / Hapus)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid.');
        header('Location: /GIS_BANJIR/admin/users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $username      = trim($_POST['username'] ?? '');
        $nama_lengkap  = trim($_POST['nama_lengkap'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $role          = $_POST['role'] ?? 'operator';
        $password      = $_POST['password'] ?? '';

        // Validasi field wajib
        if (empty($username) || empty($nama_lengkap)) {
            setFlashMessage('error', 'Username dan nama lengkap wajib diisi.');
            header('Location: /GIS_BANJIR/admin/users.php');
            exit;
        }

        // Validasi role
        if (!in_array($role, ['admin','operator'])) $role = 'operator';

        if ($action === 'tambah') {
            // Password wajib saat tambah
            if (empty($password)) {
                setFlashMessage('error', 'Password wajib diisi saat menambah user baru.');
                header('Location: /GIS_BANJIR/admin/users.php');
                exit;
            }

            // Cek apakah username sudah ada
            $cekStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $cekStmt->execute([$username]);
            if ((int)$cekStmt->fetchColumn() > 0) {
                setFlashMessage('error', "Username <strong>{$username}</strong> sudah digunakan.");
                header('Location: /GIS_BANJIR/admin/users.php');
                exit;
            }

            // Hash password
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                INSERT INTO users (username, password, nama_lengkap, email, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hashedPass, $nama_lengkap, $email, $role]);
            setFlashMessage('success', "User <strong>" . htmlspecialchars($username) . "</strong> berhasil ditambahkan.");

        } else {
            $id = (int) ($_POST['id'] ?? 0);

            // Ambil data user lama untuk cek username unik
            $stmtLama = $db->prepare("SELECT username FROM users WHERE id = ?");
            $stmtLama->execute([$id]);
            $dataLama = $stmtLama->fetch();

            // Jika username diubah, pastikan tidak bentrok dengan user lain
            if ($username !== $dataLama['username']) {
                $cekStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                $cekStmt->execute([$username, $id]);
                if ((int)$cekStmt->fetchColumn() > 0) {
                    setFlashMessage('error', "Username <strong>{$username}</strong> sudah digunakan oleh user lain.");
                    header('Location: /GIS_BANJIR/admin/users.php');
                    exit;
                }
            }

            // Jika password diisi, update password; jika kosong, tidak update
            if (!empty($password)) {
                $hashedPass = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("
                    UPDATE users
                    SET username=?, password=?, nama_lengkap=?, email=?, role=?
                    WHERE id=?
                ");
                $stmt->execute([$username, $hashedPass, $nama_lengkap, $email, $role, $id]);
            } else {
                $stmt = $db->prepare("
                    UPDATE users
                    SET username=?, nama_lengkap=?, email=?, role=?
                    WHERE id=?
                ");
                $stmt->execute([$username, $nama_lengkap, $email, $role, $id]);
            }

            setFlashMessage('success', "User <strong>" . htmlspecialchars($username) . "</strong> berhasil diperbarui.");
        }

    } elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);

        // Tidak boleh hapus user sendiri
        if ($id === (int) $_SESSION['admin_id']) {
            setFlashMessage('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            header('Location: /GIS_BANJIR/admin/users.php');
            exit;
        }

        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'User berhasil dihapus.');
        }
    }

    header('Location: /GIS_BANJIR/admin/users.php');
    exit;
}

// ============================================================
// Ambil Data User untuk Ditampilkan
// ============================================================
$userList = $db->query("
    SELECT id, username, nama_lengkap, email, role, created_at
    FROM users
    ORDER BY created_at DESC
")->fetchAll();

$flashMsg  = getFlashMessage();
$pageTitle = 'Manajemen User';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Flash Notification -->
<?php if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] === 'error' ? 'danger' : $flashMsg['type'] ?> flash-alert alert-dismissible fade show" role="alert">
    <i class="fa-solid <?= $flashMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
    <?= $flashMsg['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-users-gear me-2 text-primary"></i>Manajemen User</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">User</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:9px; font-size:13px"
            data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fa-solid fa-user-plus"></i> Tambah User
    </button>
</div>

<!-- Tabel User -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($userList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-users fa-3x mb-3 d-block opacity-50"></i>
                Belum ada data user.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Manajemen User">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dibuat</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userList as $i => $usr): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($usr['username'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($usr['id'] === (int) $_SESSION['admin_id']): ?>
                                <span class="admin-badge" style="background:#dbeafe; color:#1d4ed8; font-size:10px; padding:2px 6px">
                                    <i class="fa-solid fa-user fa-xs"></i> Anda
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($usr['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-size:13px"><?= htmlspecialchars($usr['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="admin-badge badge-<?= $usr['role'] ?>">
                                <?= ucfirst($usr['role']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px"><?= formatDate($usr['created_at']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px; padding:4px 9px"
                                    onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($usr), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)"
                                    title="Edit User" aria-label="Edit <?= htmlspecialchars($usr['username'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <?php if ($usr['id'] !== (int) $_SESSION['admin_id']): ?>
                            <form id="hapus-<?= $usr['id'] ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= $usr['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; padding:4px 9px"
                                        onclick="konfirmasiHapus('hapus-<?= $usr['id'] ?>', '<?= htmlspecialchars(addslashes($usr['username']), ENT_QUOTES, 'UTF-8') ?>')"
                                        title="Hapus User" aria-label="Hapus <?= htmlspecialchars($usr['username'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:6px; padding:4px 9px; cursor:not-allowed" disabled title="Tidak dapat hapus akun sendiri">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MODAL: Tambah User
     ============================================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="lblModalTambah" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalTambah">
                    <i class="fa-solid fa-user-plus me-2"></i>Tambah User Admin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="tambah">
                    <?php renderFormUser('tambah'); ?>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:8px">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit User
     ============================================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="lblModalEdit" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalEdit">
                    <i class="fa-solid fa-user-pen me-2"></i>Edit User Admin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <?php renderFormUser('edit'); ?>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark" style="border-radius:8px">
                        <i class="fa-solid fa-save me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Render form user (inline).
 *
 * @param string $prefix 'tambah' atau 'edit'
 */
function renderFormUser(string $prefix): void
{
    $p = $prefix;
    ?>
    <div class="row g-3">
        <!-- Username -->
        <div class="col-12">
            <label for="<?= $p ?>Username" class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" id="<?= $p ?>Username" name="username" class="form-control"
                   placeholder="Contoh: admin_medan" required maxlength="50" autocomplete="off">
        </div>
        <!-- Nama Lengkap -->
        <div class="col-12">
            <label for="<?= $p ?>NamaLengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" id="<?= $p ?>NamaLengkap" name="nama_lengkap" class="form-control"
                   placeholder="Contoh: Ahmad Fauzi" required maxlength="100" autocomplete="off">
        </div>
        <!-- Email -->
        <div class="col-12">
            <label for="<?= $p ?>Email" class="form-label">Email</label>
            <input type="email" id="<?= $p ?>Email" name="email" class="form-control"
                   placeholder="admin@bpbdmedan.go.id" maxlength="100" autocomplete="off">
        </div>
        <!-- Role -->
        <div class="col-12">
            <label for="<?= $p ?>Role" class="form-label">Role</label>
            <select id="<?= $p ?>Role" name="role" class="form-select">
                <option value="admin">🔑 Admin (Akses Penuh)</option>
                <option value="operator">👤 Operator (Terbatas)</option>
            </select>
        </div>
        <!-- Password -->
        <div class="col-12">
            <label for="<?= $p ?>Password" class="form-label">
                Password
                <?php if ($prefix === 'tambah'): ?>
                    <span class="text-danger">*</span>
                <?php else: ?>
                    <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small>
                <?php endif; ?>
            </label>
            <div class="input-group">
                <input type="password" id="<?= $p ?>Password" name="password" class="form-control"
                       placeholder="Min. 8 karakter" minlength="8"
                       <?= $prefix === 'tambah' ? 'required' : '' ?> autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="togglePasswordVisibility('<?= $p ?>Password', '<?= $p ?>EyeIcon')"
                        aria-label="Tampilkan/Sembunyikan Password">
                    <i class="fa-solid fa-eye" id="<?= $p ?>EyeIcon"></i>
                </button>
            </div>
            <?php if ($prefix === 'edit'): ?>
            <small class="text-muted">Kosongkan jika tidak ingin mengganti password.</small>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<script>
/**
 * Toggle tampilkan/sembunyikan password.
 *
 * @param {string} inputId   ID input password
 * @param {string} iconId    ID ikon mata
 */
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/**
 * Buka modal edit dan isi field dengan data user.
 *
 * @param {object} data - Data user dari JSON
 */
function bukaModalEdit(data) {
    document.getElementById('editId').value             = data.id;
    document.getElementById('editUsername').value       = data.username;
    document.getElementById('editNamaLengkap').value    = data.nama_lengkap;
    document.getElementById('editEmail').value          = data.email || '';
    document.getElementById('editRole').value           = data.role;
    document.getElementById('editPassword').value       = ''; // Kosongkan password saat edit

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
