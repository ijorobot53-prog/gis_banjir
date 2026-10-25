<?php
/**
 * API Endpoint: Kelurahan berdasarkan Kecamatan
 * File: api/kelurahan.php
 *
 * Mengembalikan JSON daftar kelurahan berdasarkan kecamatan_id.
 * Digunakan oleh admin panel untuk AJAX dynamic select.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

$kecamatanId = (int) ($_GET['kecamatan_id'] ?? 0);

if ($kecamatanId <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, nama FROM kelurahan WHERE kecamatan_id = ? ORDER BY nama");
    $stmt->execute([$kecamatanId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal memuat data kelurahan.']);
}
