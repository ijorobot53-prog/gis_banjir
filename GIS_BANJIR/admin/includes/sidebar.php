<?php
/**
 * Sidebar Admin Panel
 * File: admin/includes/sidebar.php
 *
 * Menampilkan navigasi sidebar dengan ikon Font Awesome.
 * Active state berdasarkan nama file halaman saat ini.
 */

// Dapatkan nama file halaman saat ini (tanpa path)
$currentPage = basename($_SERVER['PHP_SELF']);

// Hitung laporan yang menunggu verifikasi untuk badge
$badgeMenunggu = 0;
try {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) FROM laporan_warga WHERE status = 'menunggu'");
    $badgeMenunggu = (int) $stmt->fetchColumn();
} catch (Exception $e) {
    // Abaikan error, badge cukup kosong
    $badgeMenunggu = 0;
}

/**
 * Helper: tentukan apakah menu ini aktif.
 *
 * @param string|array $pages Nama file halaman yang dianggap aktif
 * @return string Class CSS 'active' atau kosong
 */
function sidebarActive(string|array $pages, string $currentPage): string {
    if (is_array($pages)) {
        return in_array($currentPage, $pages) ? 'active' : '';
    }
    return $currentPage === $pages ? 'active' : '';
}
?>

<style>
/* ====== SIDEBAR STYLES ====== */
.admin-sidebar {
    background: #1e293b;
}

.sidebar-nav {
    padding: 16px 12px;
    list-style: none;
    margin: 0;
}

.sidebar-section-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #475569;
    padding: 14px 12px 6px;
    display: block;
}

.sidebar-item {
    margin-bottom: 2px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    border-radius: 9px;
    text-decoration: none;
    color: #94a3b8;
    font-size: 13.5px;
    font-weight: 500;
    transition: background .18s, color .18s;
    position: relative;
}

.sidebar-link:hover {
    background: #334155;
    color: #e2e8f0;
}

.sidebar-link.active {
    background: #1a56db;
    color: #fff;
    box-shadow: 0 4px 12px rgba(26,86,219,.35);
}

.sidebar-link .sidebar-icon {
    width: 22px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
}

.sidebar-link .sidebar-badge {
    margin-left: auto;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
}

.sidebar-link.active .sidebar-badge {
    background: rgba(255,255,255,.3);
}

.sidebar-divider {
    border: none;
    border-top: 1px solid #334155;
    margin: 10px 12px;
}
</style>

<nav class="admin-sidebar" id="adminSidebar" aria-label="Navigasi Admin">
    <ul class="sidebar-nav" role="menubar">

        <!-- ---- Utama ---- -->
        <li>
            <span class="sidebar-section-label">Utama</span>
        </li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/index.php"
               class="sidebar-link <?= sidebarActive('index.php', $currentPage) ?>"
               role="menuitem" aria-label="Dashboard">
                <span class="sidebar-icon"><i class="fa-solid fa-gauge-high"></i></span>
                Dashboard
            </a>
        </li>

        <!-- ---- Data Spasial ---- -->
        <li><span class="sidebar-section-label">Data Spasial</span></li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/lokasi_rawan.php"
               class="sidebar-link <?= sidebarActive('lokasi_rawan.php', $currentPage) ?>"
               role="menuitem" aria-label="Lokasi Rawan Banjir">
                <span class="sidebar-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                Lokasi Rawan
            </a>
        </li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/evakuasi.php"
               class="sidebar-link <?= sidebarActive('evakuasi.php', $currentPage) ?>"
               role="menuitem" aria-label="Titik Evakuasi">
                <span class="sidebar-icon"><i class="fa-solid fa-location-dot"></i></span>
                Titik Evakuasi
            </a>
        </li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/kejadian.php"
               class="sidebar-link <?= sidebarActive('kejadian.php', $currentPage) ?>"
               role="menuitem" aria-label="Kejadian Banjir">
                <span class="sidebar-icon"><i class="fa-solid fa-house-flood-water"></i></span>
                Kejadian Banjir
            </a>
        </li>

        <!-- ---- Konten ---- -->
        <li><span class="sidebar-section-label">Konten</span></li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/laporan.php"
               class="sidebar-link <?= sidebarActive('laporan.php', $currentPage) ?>"
               role="menuitem" aria-label="Laporan Warga">
                <span class="sidebar-icon"><i class="fa-solid fa-flag"></i></span>
                Laporan Warga
                <?php if ($badgeMenunggu > 0): ?>
                    <span class="sidebar-badge" aria-label="<?= $badgeMenunggu ?> laporan menunggu">
                        <?= $badgeMenunggu ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/berita.php"
               class="sidebar-link <?= sidebarActive('berita.php', $currentPage) ?>"
               role="menuitem" aria-label="Berita dan Pengumuman">
                <span class="sidebar-icon"><i class="fa-solid fa-newspaper"></i></span>
                Berita
            </a>
        </li>

        <!-- ---- Sistem ---- -->
        <li><span class="sidebar-section-label">Sistem</span></li>

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/admin/users.php"
               class="sidebar-link <?= sidebarActive('users.php', $currentPage) ?>"
               role="menuitem" aria-label="Manajemen Pengguna Admin">
                <span class="sidebar-icon"><i class="fa-solid fa-users-gear"></i></span>
                Users
            </a>
        </li>

        <hr class="sidebar-divider">

        <li class="sidebar-item">
            <a href="/GIS_BANJIR/index.php"
               class="sidebar-link"
               role="menuitem" aria-label="Kembali ke Website Utama"
               target="_blank" rel="noopener noreferrer">
                <span class="sidebar-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                Kembali ke Website
            </a>
        </li>

    </ul>
</nav>
