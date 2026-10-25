<?php
/**
 * Logout Admin
 * File: admin/logout.php
 *
 * Menghapus seluruh data session dan mengarahkan ke halaman login.
 */

// Mulai session
session_start();

// Hapus semua variabel session
session_unset();

// Hancurkan session sepenuhnya
session_destroy();

// Redirect ke halaman login
header('Location: /GIS_BANJIR/admin/login.php');
exit;
