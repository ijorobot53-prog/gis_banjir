<?php
/**
 * API Endpoint: Data Titik Evakuasi
 * File: api/evakuasi.php
 *
 * Mengembalikan JSON array data titik evakuasi yang aktif.
 *
 * GET Parameters (semua opsional):
 *   kecamatan_id  - Filter berdasarkan ID kecamatan (INT)
 *   jenis         - Filter jenis tempat: sekolah | masjid | gedung | lapangan | rumah_sakit | lainnya
 */

// Hanya izinkan method GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method tidak diizinkan. Gunakan GET.']);
    exit;
}

// Set header JSON
header('Content-Type: application/json; charset=utf-8');

// Muat konfigurasi database menggunakan __DIR__ agar path selalu benar
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();

    // ─────────────────────────────────────────────
    // Base query JOIN kecamatan
    // ─────────────────────────────────────────────
    $sql = "SELECT
                te.id,
                te.nama,
                te.jenis,
                te.alamat,
                te.latitude,
                te.longitude,
                te.kapasitas,
                te.fasilitas,
                te.kontak,
                k.nama AS nama_kecamatan
            FROM  titik_evakuasi te
            LEFT  JOIN kecamatan k ON k.id = te.kecamatan_id
            WHERE te.status = 'aktif'";

    // Array parameter untuk prepared statement
    $params = [];

    // ── Filter kecamatan_id ──────────────────────
    if (!empty($_GET['kecamatan_id']) && ctype_digit((string)$_GET['kecamatan_id'])) {
        $sql .= " AND te.kecamatan_id = :kecamatan_id";
        $params[':kecamatan_id'] = (int)$_GET['kecamatan_id'];
    }

    // ── Filter jenis tempat evakuasi ─────────────
    $allowedJenis = ['sekolah', 'masjid', 'gedung', 'lapangan', 'rumah_sakit', 'lainnya'];
    if (!empty($_GET['jenis']) && in_array($_GET['jenis'], $allowedJenis, true)) {
        $sql .= " AND te.jenis = :jenis";
        $params[':jenis'] = $_GET['jenis'];
    }

    $sql .= " ORDER BY k.nama ASC, te.nama ASC";

    // Eksekusi query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Konversi tipe data numerik agar JSON lebih bersih
    foreach ($data as &$row) {
        $row['id']        = (int)$row['id'];
        $row['latitude']  = (float)$row['latitude'];
        $row['longitude'] = (float)$row['longitude'];
        $row['kapasitas'] = (int)$row['kapasitas'];
    }
    unset($row); // Putus referensi setelah loop

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    // Error query database
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data evakuasi.', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    // Error umum lainnya
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server.', 'message' => $e->getMessage()]);
}
