<?php
/**
 * File Helper Functions
 * File: includes/functions.php
 *
 * Berisi fungsi-fungsi helper untuk keamanan, validasi, format, dan utilitas umum.
 */

// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate token CSRF dan simpan di session.
 *
 * @return string Token CSRF yang di-generate
 */
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi token CSRF dari input POST.
 *
 * @param string $token Token yang dikirim via POST
 * @return bool True jika valid, false jika tidak
 */
function verifyCSRF(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input string untuk mencegah XSS.
 *
 * @param string $input String yang akan di-sanitize
 * @return string String yang sudah bersih
 */
function sanitize(string $input): string {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Format tanggal ke Bahasa Indonesia.
 *
 * @param string $date Tanggal dalam format Y-m-d atau timestamp
 * @param string $format Format output (default: 'd F Y')
 * @return string Tanggal dalam Bahasa Indonesia
 */
function formatDate(string $date, string $format = 'd F Y'): string {
    // Array nama bulan Indonesia
    $bulanIndo = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    // Array nama hari Indonesia
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    // Convert string ke timestamp jika perlu
    $timestamp = is_numeric($date) ? $date : strtotime($date);

    // Format tanggal
    $tanggal = date('j', $timestamp);
    $bulan = $bulanIndo[(int)date('n', $timestamp)];
    $tahun = date('Y', $timestamp);
    $hari = $hariIndo[date('l', $timestamp)];

    // Sesuaikan format output
    if ($format === 'd F Y') {
        return $tanggal . ' ' . $bulan . ' ' . $tahun;
    } elseif ($format === 'l, d F Y') {
        return $hari . ', ' . $tanggal . ' ' . $bulan . ' ' . $tahun;
    } elseif ($format === 'd/m/Y') {
        return date('d/m/Y', $timestamp);
    } else {
        // Format custom lainnya
        return date($format, $timestamp);
    }
}

/**
 * Upload gambar dengan validasi.
 *
 * @param array $file Data file dari $_FILES
 * @param string $dir Direktori tujuan upload (tanpa trailing slash)
 * @return string|false Nama file yang berhasil diupload, atau false jika gagal
 */
function uploadImage(array $file, string $dir): string|false {
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validasi ukuran file (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB dalam bytes
    if ($file['size'] > $maxSize) {
        return false;
    }

    // Validasi MIME type (hanya gambar)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }

    // Ekstrak ekstensi file
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp'])) {
        return false;
    }

    // Generate nama file unik
    $filename = uniqid('img_', true) . '.' . strtolower($extension);
    $destination = $dir . '/' . $filename;

    // Pastikan direktori ada
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }

    return false;
}

/**
 * Generate data pagination.
 *
 * @param int $total Total record
 * @param int $perPage Jumlah record per halaman
 * @param int $currentPage Halaman saat ini
 * @param string $baseUrl URL dasar untuk link pagination
 * @return array Array berisi info pagination
 */
function pagination(int $total, int $perPage, int $currentPage, string $baseUrl): array {
    $totalPages = ceil($total / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    // URL untuk prev/next
    $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
    $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
    $nextPage = $currentPage < $totalPages ? $currentPage + 1 : null;

    // Generate array links halaman
    $links = [];
    $rangeStart = max(1, $currentPage - 2);
    $rangeEnd = min($totalPages, $currentPage + 2);

    for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
        $links[] = [
            'page' => $i,
            'url' => $baseUrl . $separator . 'page=' . $i,
            'active' => ($i === $currentPage)
        ];
    }

    return [
        'total' => $total,
        'perPage' => $perPage,
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'prev' => $prevPage ? $baseUrl . $separator . 'page=' . $prevPage : null,
        'next' => $nextPage ? $baseUrl . $separator . 'page=' . $nextPage : null,
        'links' => $links,
        'hasMore' => $currentPage < $totalPages
    ];
}

/**
 * Redirect ke URL tertentu.
 *
 * @param string $url URL tujuan
 * @return void
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Cek apakah user sudah login sebagai admin.
 *
 * @return bool True jika sudah login, false jika belum
 */
function isAdmin(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require user harus login sebagai admin.
 * Redirect ke halaman login jika belum login.
 *
 * @return void
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(getBaseUrl() . '/admin/login.php');
    }
}

/**
 * Dapatkan base URL aplikasi.
 *
 * @return string Base URL tanpa trailing slash
 */
function getBaseUrl(): string {
    // Deteksi protokol (HTTP atau HTTPS)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Dapatkan host
    $host = $_SERVER['HTTP_HOST'];

    // Dapatkan path folder aplikasi
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);

    // Bangun base URL
    $baseUrl = $protocol . '://' . $host . $scriptPath;

    // Hapus trailing slash jika ada
    return rtrim($baseUrl, '/');
}

/**
 * Generate slug dari string (untuk URL-friendly).
 *
 * @param string $text String yang akan dijadikan slug
 * @return string Slug hasil konversi
 */
function generateSlug(string $text): string {
    // Convert ke lowercase
    $text = strtolower($text);

    // Replace karakter non-alphanumeric dengan dash
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);

    // Hapus dash di awal dan akhir
    $text = trim($text, '-');

    return $text;
}

/**
 * Escape output untuk JSON (mencegah XSS di JavaScript).
 *
 * @param mixed $data Data yang akan di-escape
 * @return string JSON string yang sudah di-escape
 */
function jsonEncode($data): string {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

/**
 * Ambil pesan flash dari session dan hapus.
 *
 * @param string $key Key dari flash message (default: 'flash_message')
 * @return array|null Array berisi 'type' dan 'message', atau null jika tidak ada
 */
function getFlashMessage(string $key = 'flash_message'): ?array {
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }
    return null;
}

/**
 * Set pesan flash untuk ditampilkan di halaman berikutnya.
 *
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Isi pesan
 * @param string $key Key untuk menyimpan pesan (default: 'flash_message')
 * @return void
 */
function setFlashMessage(string $type, string $message, string $key = 'flash_message'): void {
    $_SESSION[$key] = [
        'type' => $type,
        'message' => $message
    ];
}
