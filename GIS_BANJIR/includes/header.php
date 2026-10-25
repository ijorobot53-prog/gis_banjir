<?php
/**
 * Header HTML untuk semua halaman publik
 * File: includes/header.php
 *
 * Variabel yang diharapkan sudah terdefinisi sebelum include:
 * - $pageTitle (string): Judul halaman
 */

// Pastikan $pageTitle terdefinisi
if (!isset($pageTitle)) {
    $pageTitle = 'SIG Rawan Banjir & Jalur Evakuasi Kota Medan';
}

// Pastikan session sudah aktif untuk dark mode preference
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan halaman aktif berdasarkan URL
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Geografis Kawasan Rawan Banjir dan Jalur Evakuasi Kota Medan - BPBD Kota Medan">
    <meta name="keywords" content="banjir Medan, SIG banjir, evakuasi banjir, BPBD Medan, rawan banjir">
    <meta name="author" content="BPBD Kota Medan">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | SIG Banjir Medan</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💧</text></svg>">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- AOS (Animate On Scroll) CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" integrity="sha512-1cK78a1o+ht2JcaW6g8OXYwqpev9+6GqOkz9xmBN9iUUhIndKtxwILGWYOSibeskjh4Q5bnkQdFAqPdEA7m/A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/assets/css/style.css">

    <!-- Script dark mode dijalankan sebelum render untuk mencegah flash -->
    <script>
        (function () {
            // Baca preferensi dari localStorage
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');
            // Terapkan theme sebelum halaman di-render
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
</head>
<body>

    <!-- ========================
         NAVBAR
         ======================== -->
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm" id="mainNavbar">
        <div class="container">

            <!-- Logo & Brand -->
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <span class="navbar-icon text-primary fs-4">
                    <i class="fa-solid fa-droplet"></i>
                </span>
                <span class="brand-text">
                    SIG Banjir <span class="text-primary">Medan</span>
                </span>
            </a>

            <!-- Tombol Hamburger Mobile -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Buka navigasi">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu Navigasi -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto gap-1">

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>"
                           href="index.php">
                            <i class="fa-solid fa-house me-1"></i> Beranda
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'peta.php') ? 'active' : '' ?>"
                           href="peta.php">
                            <i class="fa-solid fa-map-location-dot me-1"></i> Peta Rawan
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'evakuasi.php') ? 'active' : '' ?>"
                           href="evakuasi.php">
                            <i class="fa-solid fa-route me-1"></i> Jalur Evakuasi
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'statistik.php') ? 'active' : '' ?>"
                           href="statistik.php">
                            <i class="fa-solid fa-chart-bar me-1"></i> Statistik
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'edukasi.php') ? 'active' : '' ?>"
                           href="edukasi.php">
                            <i class="fa-solid fa-book-open me-1"></i> Edukasi
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'lapor.php') ? 'active' : '' ?>"
                           href="lapor.php">
                            <i class="fa-solid fa-file-circle-plus me-1"></i> Lapor
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage === 'tentang.php') ? 'active' : '' ?>"
                           href="tentang.php">
                            <i class="fa-solid fa-circle-info me-1"></i> Tentang
                        </a>
                    </li>

                </ul>

                <!-- Tombol Dark Mode + Login Admin -->
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-circle" id="darkModeToggle"
                        title="Ganti tema terang/gelap" aria-label="Toggle dark mode">
                        <i class="fa-solid fa-moon" id="darkModeIcon"></i>
                    </button>
                    <a href="admin/login.php" class="btn btn-sm btn-outline-danger" title="Login Admin">
                        <i class="fa-solid fa-lock me-1"></i> Admin
                    </a>
                </div>

            </div><!-- /.collapse -->
        </div><!-- /.container -->
    </nav>
    <!-- ======================== END NAVBAR ======================== -->

    <!-- Script Dark Mode Toggle (berjalan setelah DOM siap) -->
    <script>
        (function () {
            const toggleBtn = document.getElementById('darkModeToggle');
            const icon = document.getElementById('darkModeIcon');
            const html = document.documentElement;

            // Update ikon sesuai tema saat ini
            function updateIcon() {
                const isDark = html.getAttribute('data-bs-theme') === 'dark';
                icon.className = isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            }

            // Terapkan saat pertama load
            updateIcon();

            // Tangani klik toggle
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    const currentTheme = html.getAttribute('data-bs-theme');
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-bs-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateIcon();
                });
            }
        })();
    </script>

    <!-- Konten halaman diletakkan di bawah ini -->
