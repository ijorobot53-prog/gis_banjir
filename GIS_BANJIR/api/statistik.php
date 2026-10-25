<?php
/**
 * API Endpoint: Data Statistik untuk Chart.js
 * File: api/statistik.php
 *
 * Mengembalikan data statistik banjir dalam format JSON sesuai tipe yang diminta.
 *
 * GET Parameter:
 *   type  - Tipe statistik yang diinginkan:
 *           per_tahun     : Jumlah kejadian per tahun (2019-2024)
 *           per_kecamatan : Jumlah kejadian per kecamatan
 *           tingkat       : Jumlah lokasi rawan per tingkat bahaya
 *           tren          : Rata-rata ketinggian air per bulan (12 bulan terakhir)
 *           ringkasan     : Ringkasan statistik keseluruhan
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

// Validasi parameter type
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$allowedTypes = ['per_tahun', 'per_kecamatan', 'tingkat', 'tren', 'ringkasan'];

if (empty($type) || !in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Parameter type tidak valid.',
        'allowed' => $allowedTypes
    ]);
    exit;
}

try {
    $pdo = getDB();

    // ─────────────────────────────────────────────────────────────
    // TYPE: per_tahun
    // Menghitung jumlah kejadian banjir per tahun dari 2019 hingga 2024
    // ─────────────────────────────────────────────────────────────
    if ($type === 'per_tahun') {
        $sql = "SELECT
                    YEAR(tanggal)    AS tahun,
                    COUNT(*)         AS jumlah
                FROM kejadian_banjir
                WHERE YEAR(tanggal) BETWEEN 2019 AND 2024
                GROUP BY YEAR(tanggal)
                ORDER BY tahun ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Pastikan semua tahun 2019–2024 terwakili meskipun tidak ada data
        $tahunRange = range(2019, 2024);
        $dataMap = [];
        foreach ($rows as $row) {
            $dataMap[(int)$row['tahun']] = (int)$row['jumlah'];
        }

        $labels = [];
        $data   = [];
        foreach ($tahunRange as $tahun) {
            $labels[] = (string)$tahun;
            $data[]   = $dataMap[$tahun] ?? 0;
        }

        echo json_encode(
            ['labels' => $labels, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );

    // ─────────────────────────────────────────────────────────────
    // TYPE: per_kecamatan
    // Menghitung jumlah kejadian banjir per kecamatan
    // ─────────────────────────────────────────────────────────────
    } elseif ($type === 'per_kecamatan') {
        $sql = "SELECT
                    k.nama     AS nama_kecamatan,
                    COUNT(kb.id) AS jumlah
                FROM kejadian_banjir kb
                JOIN lokasi_rawan lr ON lr.id = kb.lokasi_id
                JOIN kecamatan    k  ON k.id  = lr.kecamatan_id
                GROUP BY k.id, k.nama
                ORDER BY jumlah DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $labels = [];
        $data   = [];
        foreach ($rows as $row) {
            $labels[] = $row['nama_kecamatan'];
            $data[]   = (int)$row['jumlah'];
        }

        echo json_encode(
            ['labels' => $labels, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );

    // ─────────────────────────────────────────────────────────────
    // TYPE: tingkat
    // Menghitung jumlah lokasi rawan aktif per tingkat bahaya
    // Juga mengembalikan warna untuk Chart.js
    // ─────────────────────────────────────────────────────────────
    } elseif ($type === 'tingkat') {
        $sql = "SELECT
                    tingkat,
                    COUNT(*) AS jumlah
                FROM lokasi_rawan
                WHERE status = 'aktif'
                GROUP BY tingkat
                ORDER BY FIELD(tingkat, 'rendah', 'sedang', 'tinggi')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Warna sesuai tingkat bahaya untuk digunakan langsung di Chart.js
        $colorMap = [
            'rendah' => '#28a745', // Hijau – bahaya rendah
            'sedang' => '#ffc107', // Kuning – bahaya sedang
            'tinggi' => '#dc3545', // Merah  – bahaya tinggi
        ];

        $labels = [];
        $data   = [];
        $colors = [];
        foreach ($rows as $row) {
            $tingkat  = $row['tingkat'];
            $labels[] = ucfirst($tingkat);
            $data[]   = (int)$row['jumlah'];
            $colors[] = $colorMap[$tingkat] ?? '#6c757d';
        }

        echo json_encode(
            ['labels' => $labels, 'data' => $data, 'colors' => $colors],
            JSON_UNESCAPED_UNICODE
        );

    // ─────────────────────────────────────────────────────────────
    // TYPE: tren
    // Rata-rata ketinggian air per bulan selama 12 bulan terakhir
    // ─────────────────────────────────────────────────────────────
    } elseif ($type === 'tren') {
        $sql = "SELECT
                    DATE_FORMAT(tanggal, '%Y-%m') AS bulan_key,
                    DATE_FORMAT(tanggal, '%b %Y') AS bulan_label,
                    ROUND(AVG(ketinggian_cm), 1)  AS rata_rata_cm
                FROM kejadian_banjir
                WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY bulan_key, bulan_label
                ORDER BY bulan_key ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $labels = [];
        $data   = [];
        foreach ($rows as $row) {
            $labels[] = $row['bulan_label'];
            $data[]   = (float)$row['rata_rata_cm'];
        }

        echo json_encode(
            ['labels' => $labels, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );

    // ─────────────────────────────────────────────────────────────
    // TYPE: ringkasan
    // Statistik ringkasan keseluruhan sistem
    // ─────────────────────────────────────────────────────────────
    } elseif ($type === 'ringkasan') {

        // Jumlah kecamatan yang memiliki lokasi rawan aktif
        $stmtKec = $pdo->query(
            "SELECT COUNT(DISTINCT kecamatan_id) AS total
             FROM lokasi_rawan
             WHERE status = 'aktif' AND kecamatan_id IS NOT NULL"
        );
        $totalKecamatanRawan = (int)$stmtKec->fetchColumn();

        // Jumlah total lokasi rawan aktif
        $stmtLokasi = $pdo->query(
            "SELECT COUNT(*) FROM lokasi_rawan WHERE status = 'aktif'"
        );
        $totalLokasiBanjir = (int)$stmtLokasi->fetchColumn();

        // Jumlah total posko/titik evakuasi aktif
        $stmtPosko = $pdo->query(
            "SELECT COUNT(*) FROM titik_evakuasi WHERE status = 'aktif'"
        );
        $totalPosko = (int)$stmtPosko->fetchColumn();

        // Total korban terdampak dari semua kejadian tercatat
        $stmtKorban = $pdo->query(
            "SELECT COALESCE(SUM(korban_terdampak), 0) FROM kejadian_banjir"
        );
        $totalKorban = (int)$stmtKorban->fetchColumn();

        // Tanggal kejadian banjir terakhir
        $stmtTerakhir = $pdo->query(
            "SELECT MAX(tanggal) FROM kejadian_banjir"
        );
        $kejadianTerakhir = $stmtTerakhir->fetchColumn();

        echo json_encode([
            'total_kecamatan_rawan' => $totalKecamatanRawan,
            'total_lokasi_banjir'   => $totalLokasiBanjir,
            'total_posko'           => $totalPosko,
            'total_korban'          => $totalKorban,
            'kejadian_terakhir'     => $kejadianTerakhir,
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    // Error query database
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data statistik.', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    // Error umum lainnya
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server.', 'message' => $e->getMessage()]);
}
