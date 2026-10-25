<?php
/**
 * API Endpoint: Submit Laporan Warga
 * File: api/laporan.php
 *
 * Menerima laporan banjir dari warga via form POST.
 * Melakukan validasi input, upload foto (opsional), dan menyimpan ke database.
 *
 * Method: POST
 *
 * POST Fields:
 *   csrf_token    (string, required)  - Token CSRF dari session
 *   nama_pelapor  (string, required)  - Nama lengkap pelapor
 *   kontak        (string, optional)  - Nomor telepon / kontak
 *   latitude      (float,  required)  - Koordinat latitude lokasi banjir
 *   longitude     (float,  required)  - Koordinat longitude lokasi banjir
 *   ketinggian_cm (int,    required)  - Estimasi ketinggian air dalam cm
 *   deskripsi     (string, required)  - Deskripsi kejadian banjir
 *
 * File Upload (opsional):
 *   foto          - Foto kondisi banjir (JPEG/PNG/WebP, maks 5MB)
 *
 * Response JSON:
 *   Sukses  : { "success": true,  "message": "..." }
 *   Gagal   : { "success": false, "error": "..." }
 */

// ── Muat helper dan database ────────────────────────────────────────────────
// Gunakan __DIR__ agar path selalu benar terlepas dari CWD
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// ── Set header JSON ──────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

// ── Periksa method HTTP ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan. Gunakan POST.']);
    exit;
}

// ── Verifikasi token CSRF ────────────────────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRF($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token keamanan tidak valid. Muat ulang halaman dan coba lagi.']);
    exit;
}

// ── Kumpulkan dan validasi input ─────────────────────────────────────────────
$errors = [];

// Nama pelapor (wajib, 3–100 karakter)
$namaPelapor = isset($_POST['nama_pelapor']) ? sanitize($_POST['nama_pelapor']) : '';
if (empty($namaPelapor)) {
    $errors[] = 'Nama pelapor wajib diisi.';
} elseif (strlen($namaPelapor) < 3 || strlen($namaPelapor) > 100) {
    $errors[] = 'Nama pelapor harus antara 3 hingga 100 karakter.';
}

// Kontak (opsional, maks 20 karakter jika diisi)
$kontak = isset($_POST['kontak']) ? sanitize($_POST['kontak']) : '';
if ($kontak !== '' && strlen($kontak) > 20) {
    $errors[] = 'Kontak maksimal 20 karakter.';
}
$kontak = $kontak !== '' ? $kontak : null;

// Latitude (wajib, numerik, rentang valid -90 sampai 90)
$latitudeRaw = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
if ($latitudeRaw === '') {
    $errors[] = 'Latitude wajib diisi.';
} elseif (!is_numeric($latitudeRaw)) {
    $errors[] = 'Latitude harus berupa angka.';
} elseif ((float)$latitudeRaw < -90 || (float)$latitudeRaw > 90) {
    $errors[] = 'Nilai latitude tidak valid (harus antara -90 dan 90).';
}
$latitude = is_numeric($latitudeRaw) ? (float)$latitudeRaw : null;

// Longitude (wajib, numerik, rentang valid -180 sampai 180)
$longitudeRaw = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';
if ($longitudeRaw === '') {
    $errors[] = 'Longitude wajib diisi.';
} elseif (!is_numeric($longitudeRaw)) {
    $errors[] = 'Longitude harus berupa angka.';
} elseif ((float)$longitudeRaw < -180 || (float)$longitudeRaw > 180) {
    $errors[] = 'Nilai longitude tidak valid (harus antara -180 dan 180).';
}
$longitude = is_numeric($longitudeRaw) ? (float)$longitudeRaw : null;

// Ketinggian air dalam cm (wajib, bilangan bulat non-negatif)
$ketinggianRaw = isset($_POST['ketinggian_cm']) ? trim($_POST['ketinggian_cm']) : '';
if ($ketinggianRaw === '') {
    $errors[] = 'Estimasi ketinggian air wajib diisi.';
} elseif (!ctype_digit($ketinggianRaw)) {
    $errors[] = 'Ketinggian air harus berupa bilangan bulat non-negatif.';
} elseif ((int)$ketinggianRaw > 9999) {
    $errors[] = 'Nilai ketinggian air terlalu besar (maks 9999 cm).';
}
$ketinggianCm = ctype_digit($ketinggianRaw) ? (int)$ketinggianRaw : null;

// Deskripsi (wajib, 10–2000 karakter)
$deskripsi = isset($_POST['deskripsi']) ? sanitize($_POST['deskripsi']) : '';
if (empty($deskripsi)) {
    $errors[] = 'Deskripsi kejadian wajib diisi.';
} elseif (strlen($deskripsi) < 10) {
    $errors[] = 'Deskripsi terlalu singkat (minimal 10 karakter).';
} elseif (strlen($deskripsi) > 2000) {
    $errors[] = 'Deskripsi terlalu panjang (maksimal 2000 karakter).';
}

// ── Kembalikan semua error validasi sekaligus ────────────────────────────────
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// ── Proses upload foto (opsional) ────────────────────────────────────────────
$namaFoto = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Direktori upload relatif terhadap root proyek
    $uploadDir = __DIR__ . '/../uploads';

    // Panggil helper uploadImage dari functions.php
    $namaFoto = uploadImage($_FILES['foto'], $uploadDir);

    if ($namaFoto === false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Gagal mengunggah foto. Pastikan format file adalah JPEG/PNG/WebP dan ukurannya tidak melebihi 5MB.'
        ]);
        exit;
    }
}

// ── Simpan laporan ke database ───────────────────────────────────────────────
try {
    $pdo = getDB();

    $sql = "INSERT INTO laporan_warga
                (nama_pelapor, kontak, latitude, longitude, ketinggian_cm, foto, deskripsi, status, created_at)
            VALUES
                (:nama_pelapor, :kontak, :latitude, :longitude, :ketinggian_cm, :foto, :deskripsi, 'menunggu', NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama_pelapor'  => $namaPelapor,
        ':kontak'        => $kontak,
        ':latitude'      => $latitude,
        ':longitude'     => $longitude,
        ':ketinggian_cm' => $ketinggianCm,
        ':foto'          => $namaFoto,
        ':deskripsi'     => $deskripsi,
    ]);

    // Ambil ID laporan yang baru dibuat
    $idLaporan = (int)$pdo->lastInsertId();

    http_response_code(200);
    echo json_encode([
        'success'    => true,
        'message'    => 'Laporan berhasil dikirim. Terima kasih atas laporan Anda. Tim kami akan memverifikasi dalam 1x24 jam.',
        'id_laporan' => $idLaporan,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Jika foto sudah terlanjur diunggah, hapus agar tidak ada file orphan
    if ($namaFoto !== null) {
        $filePath = __DIR__ . '/../uploads/' . $namaFoto;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Gagal menyimpan laporan ke database. Silakan coba lagi.',
        'message' => $e->getMessage(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Terjadi kesalahan server. Silakan coba lagi.',
        'message' => $e->getMessage(),
    ]);
}
