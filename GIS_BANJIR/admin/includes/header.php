<?php
/**
 * Header Admin Panel
 * File: admin/includes/header.php
 *
 * Menampilkan HTML head, navbar atas, dan include sidebar.
 * Diasumsikan sudah ada $pageTitle dari halaman yang memanggil file ini.
 */

// Pastikan variabel judul halaman tersedia
if (!isset($pageTitle)) {
    $pageTitle = 'Admin Panel';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> &mdash; SIG Banjir Admin</title>

    <!-- Bootstrap 5.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">

    <!-- Leaflet CSS (untuk map picker di modal) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* ==========================================
           Custom Admin CSS - SIG Banjir Admin Panel
           ========================================== */

        :root {
            --admin-primary:    #1a56db;
            --admin-secondary:  #1e3a5f;
            --admin-sidebar-bg: #1e293b;
            --admin-sidebar-hover: #334155;
            --admin-sidebar-active: #1a56db;
            --admin-sidebar-width: 260px;
            --admin-topbar-height: 64px;
            --admin-text-light: #94a3b8;
        }

        /* Reset & Base */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            font-size: 14px;
        }

        /* ====== TOPBAR ====== */
        .admin-topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--admin-topbar-height);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 20px 0 0;
            z-index: 1040;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }

        .admin-topbar .topbar-brand {
            width: var(--admin-sidebar-width);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            flex-shrink: 0;
            text-decoration: none;
        }

        .admin-topbar .topbar-brand .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 18px;
            flex-shrink: 0;
        }

        .admin-topbar .topbar-brand .brand-text {
            display: flex; flex-direction: column; line-height: 1.2;
        }

        .admin-topbar .topbar-brand .brand-title {
            font-size: 13px; font-weight: 700;
            color: var(--admin-secondary);
        }

        .admin-topbar .topbar-brand .brand-subtitle {
            font-size: 10px; color: #64748b;
        }

        .topbar-right {
            display: flex; align-items: center; gap: 12px;
            margin-left: auto;
        }

        .admin-user-info {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 14px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: default;
        }

        .admin-user-info .user-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .admin-user-info .user-name {
            font-size: 13px; font-weight: 600; color: #1e293b;
            white-space: nowrap;
        }

        .admin-user-info .user-role {
            font-size: 11px; color: #64748b;
        }

        .btn-logout {
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            display: flex; align-items: center; gap: 6px;
        }

        /* ====== SIDEBAR ====== */
        .admin-sidebar {
            position: fixed;
            top: var(--admin-topbar-height);
            left: 0;
            width: var(--admin-sidebar-width);
            height: calc(100vh - var(--admin-topbar-height));
            background: var(--admin-sidebar-bg);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 1030;
            padding-bottom: 20px;
            transition: transform .3s ease;
        }

        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-track { background: transparent; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: #475569; border-radius: 2px; }

        /* ====== MAIN CONTENT ====== */
        .admin-main {
            margin-left: var(--admin-sidebar-width);
            margin-top: var(--admin-topbar-height);
            min-height: calc(100vh - var(--admin-topbar-height));
            padding: 24px;
        }

        /* ====== PAGE HEADER ====== */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-header-left h1 {
            font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;
        }

        .page-header-left .breadcrumb {
            font-size: 12px; margin: 4px 0 0;
        }

        /* ====== STAT CARDS ====== */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px;
            border: 1px solid #e2e8f0;
            transition: box-shadow .2s, transform .2s;
            height: 100%;
        }

        .stat-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .stat-card .stat-value {
            font-size: 28px; font-weight: 700; color: #1e293b; line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 13px; color: #64748b; margin-top: 4px;
        }

        /* ====== DATA CARD ====== */
        .data-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .data-card .data-card-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
        }

        .data-card .data-card-header h5 {
            font-size: 15px; font-weight: 600; color: #1e293b; margin: 0;
        }

        .data-card .data-card-body {
            padding: 0;
        }

        /* ====== TABLE ====== */
        .admin-table {
            width: 100%; border-collapse: collapse;
        }

        .admin-table th {
            background: #f8fafc;
            padding: 12px 16px;
            font-size: 12px; font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .admin-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #334155;
            vertical-align: middle;
        }

        .admin-table tbody tr:last-child td { border-bottom: none; }

        .admin-table tbody tr:hover td { background: #f8fafc; }

        /* ====== BADGE TINGKAT ====== */
        .badge-rendah  { background: #dcfce7; color: #15803d; }
        .badge-sedang  { background: #fef9c3; color: #a16207; }
        .badge-tinggi  { background: #fee2e2; color: #b91c1c; }
        .badge-aktif   { background: #d1fae5; color: #065f46; }
        .badge-nonaktif{ background: #f1f5f9; color: #64748b; }
        .badge-menunggu   { background: #fef3c7; color: #b45309; }
        .badge-disetujui  { background: #d1fae5; color: #065f46; }
        .badge-ditolak    { background: #fee2e2; color: #b91c1c; }
        .badge-published  { background: #d1fae5; color: #065f46; }
        .badge-draft      { background: #f1f5f9; color: #64748b; }
        .badge-berita     { background: #dbeafe; color: #1d4ed8; }
        .badge-pengumuman { background: #fde8d8; color: #c2410c; }
        .badge-tips       { background: #e0e7ff; color: #4338ca; }
        .badge-admin      { background: #f3e8ff; color: #7e22ce; }
        .badge-operator   { background: #e0f2fe; color: #0369a1; }

        .admin-badge {
            display: inline-flex; align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px; font-weight: 600;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        /* ====== FORM MODAL ====== */
        .modal-header-custom {
            background: linear-gradient(135deg, #1a56db, #3b82f6);
            color: #fff;
            padding: 18px 22px;
        }

        .modal-header-custom .modal-title { font-size: 16px; font-weight: 600; }
        .modal-header-custom .btn-close { filter: invert(1); }

        .form-label { font-size: 13px; font-weight: 500; margin-bottom: 5px; }
        .form-control, .form-select {
            font-size: 13px;
            border-radius: 8px;
            border-color: #e2e8f0;
            padding: 8px 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(26,86,219,.1);
        }

        /* ====== MODAL UKURAN & TOMBOL SELALU TERLIHAT ====== */

        /* Modal XL — lebih lebar agar form tidak sempit */
        .modal-admin-form .modal-dialog {
            max-width: 860px;
            width: 95%;
            margin: 1rem auto;
        }

        /* Pastikan modal-content tidak overflow layar */
        .modal-admin-form .modal-content {
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }

        /* Header tetap di atas */
        .modal-admin-form .modal-header {
            flex-shrink: 0;
        }

        /* Body bisa scroll, tapi footer TIDAK ikut scroll */
        .modal-admin-form .modal-body {
            overflow-y: auto;
            flex: 1 1 auto;
            /* Batasi tinggi body agar footer tombol selalu kelihatan */
            max-height: calc(100vh - 200px);
        }

        /* Footer tombol selalu menempel di bawah */
        .modal-admin-form .modal-footer {
            flex-shrink: 0;
            border-top: 1px solid #e2e8f0 !important;
            background: #fff;
            padding: 14px 22px !important;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Tombol di footer cukup besar dan jelas */
        .modal-admin-form .modal-footer .btn {
            min-width: 110px;
            padding: 9px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 9px;
        }

        /* Responsif mobile */
        @media (max-width: 767.98px) {
            .modal-admin-form .modal-dialog {
                max-width: 100%;
                width: 100%;
                margin: 0;
            }
            .modal-admin-form .modal-content {
                border-radius: 0;
                max-height: 100vh;
            }
            .modal-admin-form .modal-body {
                max-height: calc(100vh - 160px);
            }
        }

        /* ====== MAP PICKER ====== */
        #mapPicker, #mapPickerEvakuasi, #mapDetail { border-radius: 10px; }

        /* ====== ALERT FLASH ====== */
        .flash-alert {
            border-radius: 10px; font-size: 13px;
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 16px;
        }

        /* ====== PAGINATION ====== */
        .pagination .page-link {
            font-size: 13px; border-radius: 6px !important;
            margin: 0 2px; border-color: #e2e8f0;
            color: var(--admin-primary);
        }

        .pagination .page-item.active .page-link {
            background: var(--admin-primary); border-color: var(--admin-primary);
        }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .topbar-brand {
                width: auto !important;
            }
        }
    </style>
</head>
<body>

<!-- ====== TOPBAR ====== -->
<header class="admin-topbar">
    <!-- Brand / Logo -->
    <a class="topbar-brand" href="/GIS_BANJIR/admin/index.php">
        <div class="brand-icon">
            <i class="fa-solid fa-water"></i>
        </div>
        <div class="brand-text">
            <span class="brand-title">SIG Banjir</span>
            <span class="brand-subtitle">Panel Admin</span>
        </div>
    </a>

    <!-- Tombol toggle sidebar (mobile) -->
    <button class="btn btn-sm btn-outline-secondary d-lg-none ms-3"
            id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Info Admin & Logout -->
    <div class="topbar-right">
        <div class="admin-user-info">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="user-role"><?= ucfirst($_SESSION['admin_role'] ?? 'admin') ?></div>
            </div>
        </div>
        <a href="/GIS_BANJIR/admin/logout.php" class="btn btn-sm btn-danger btn-logout"
           onclick="return confirm('Yakin ingin logout?')">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="d-none d-md-inline">Logout</span>
        </a>
    </div>
</header>

<!-- ====== SIDEBAR ====== -->
<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- ====== MAIN CONTENT WRAPPER ====== -->
<main class="admin-main">

<!-- Script toggle sidebar mobile -->
<script>
document.getElementById('sidebarToggle').addEventListener('click', function () {
    document.querySelector('.admin-sidebar').classList.toggle('show');
});
</script>
