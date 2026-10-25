<?php
/**
 * API Endpoint: Data Lokasi Rawan Banjir
 * File: api/lokasi.php
 *
 * Mengembalikan JSON array data lokasi rawan banjir yang aktif
 * beserta laporan warga yang telah disetujui.
 *
 * GET Parameters (semua opsional):
 *   kecamatan_id  - Filter berdasarkan ID kecamatan (INT)
 *   tingkat       - Filter tingkat bahaya: rendah | sedang | tinggi
 *   tahun         - Filter berdasarkan tahun kejadian (YYYY)
 *   search        - Pencarian nama lokasi / kecamatan / kelurahan
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
    // BAGIAN 1: Ambil data dari tabel lokasi_rawan
    // ─────────────────────────────────────────────

    // Base query JOIN kecamatan dan kelurahan
    $sql = "SELECT
                lr.id,
                lr.nama,
                lr.kecamatan_id,
                k.nama          AS nama_kecamatan,
                lr.kelurahan_id,
                kel.nama        AS nama_kelurahan,
                lr.latitude,
                lr.longitude,
                lr.tingkat,
                lr.ketinggian_air_cm,
                lr.penyebab,
                lr.keterangan,
                -- Ambil tanggal kejadian terakhir dari tabel kejadian_banjir
                (
                    SELECT MAX(kb.tanggal)
                    FROM   kejadian_banjir kb
                    WHERE  kb.lokasi_id = lr.id
                ) AS tanggal_kejadian_terakhir,
                'lokasi' AS type
            FROM  lokasi_rawan lr
            LEFT  JOIN kecamatan k   ON k.id   = lr.kecamatan_id
            LEFT  JOIN kelurahan kel ON kel.id  = lr.kelurahan_id
            WHERE lr.status = 'aktif'";

    // Array parameter untuk prepared statement
    $params = [];

    // ── Filter kecamatan_id ──────────────────────
    if (!empty($_GET['kecamatan_id']) && ctype_digit((string)$_GET['kecamatan_id'])) {
        $sql .= " AND lr.kecamatan_id = :kecamatan_id";
        $params[':kecamatan_id'] = (int)$_GET['kecamatan_id'];
    }

    // ── Filter tingkat bahaya ────────────────────
    $allowedTingkat = ['rendah', 'sedang', 'tinggi'];
    if (!empty($_GET['tingkat']) && in_array($_GET['tingkat'], $allowedTingkat, true)) {
        $sql .= " AND lr.tingkat = :tingkat";
        $params[':tingkat'] = $_GET['tingkat'];
    }

    // ── Filter tahun (berdasarkan tanggal kejadian) ──
    if (!empty($_GET['tahun']) && preg_match('/^\d{4}$/', $_GET['tahun'])) {
        $sql .= " AND EXISTS (
                    SELECT 1 FROM kejadian_banjir kb2
                    WHERE  kb2.lokasi_id = lr.id
                    AND    YEAR(kb2.tanggal) = :tahun
                 )";
        $params[':tahun'] = (int)$_GET['tahun'];
    }

    // ── Filter pencarian (nama, kecamatan, kelurahan) ──
    if (!empty($_GET['search'])) {
        $keyword = '%' . trim($_GET['search']) . '%';
        $sql .= " AND (
                    lr.nama        LIKE :search1
                    OR k.nama      LIKE :search2
                    OR kel.nama    LIKE :search3
                 )";
        $params[':search1'] = $keyword;
        $params[':search2'] = $keyword;
        $params[':search3'] = $keyword;
    }

    $sql .= " ORDER BY lr.tingkat DESC, lr.nama ASC";

    // Eksekusi query lokasi_rawan
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dataLokasi = $stmt->fetchAll();

    // ─────────────────────────────────────────────
    // BAGIAN 2: Ambil laporan warga yang disetujui
    // ─────────────────────────────────────────────

    $sqlLaporan = "SELECT
                       lw.id,
                       lw.nama_pelapor                   AS nama,
                       lw.kecamatan_id,
                       k.nama                           AS nama_kecamatan,
                       NULL                             AS kelurahan_id,
                       NULL                             AS nama_kelurahan,
                       lw.latitude,
                       lw.longitude,
                       NULL                             AS tingkat,
                       lw.ketinggian_cm                 AS ketinggian_air_cm,
                       NULL                             AS penyebab,
                       lw.deskripsi                     AS keterangan,
                       DATE(lw.created_at)              AS tanggal_kejadian_terakhir,
                       'laporan'                        AS type
                   FROM  laporan_warga lw
                   LEFT  JOIN kecamatan k ON k.id = lw.kecamatan_id
                   WHERE lw.status = 'disetujui'";

    $paramsLaporan = [];

    // Filter kecamatan_id untuk laporan juga
    if (!empty($params[':kecamatan_id'])) {
        $sqlLaporan .= " AND lw.kecamatan_id = :kecamatan_id";
        $paramsLaporan[':kecamatan_id'] = $params[':kecamatan_id'];
    }

    // Filter pencarian pada laporan
    if (!empty($_GET['search'])) {
        $keyword = '%' . trim($_GET['search']) . '%';
        $sqlLaporan .= " AND (lw.nama_pelapor LIKE :search1 OR lw.deskripsi LIKE :search2 OR k.nama LIKE :search3)";
        $paramsLaporan[':search1'] = $keyword;
        $paramsLaporan[':search2'] = $keyword;
        $paramsLaporan[':search3'] = $keyword;
    }

    $sqlLaporan .= " ORDER BY lw.created_at DESC";

    $stmtLaporan = $pdo->prepare($sqlLaporan);
    $stmtLaporan->execute($paramsLaporan);
    $dataLaporan = $stmtLaporan->fetchAll();

    // ─────────────────────────────────────────────
    // BAGIAN 3: Gabungkan hasil dan kembalikan JSON
    // ─────────────────────────────────────────────

    $result = array_merge($dataLokasi, $dataLaporan);

    // Konversi tipe data numerik agar JSON lebih bersih
    foreach ($result as &$row) {
        $row['id']                = (int)$row['id'];
        $row['kecamatan_id']      = $row['kecamatan_id']  !== null ? (int)$row['kecamatan_id']  : null;
        $row['kelurahan_id']      = $row['kelurahan_id']  !== null ? (int)$row['kelurahan_id']  : null;
        $row['latitude']          = (float)$row['latitude'];
        $row['longitude']         = (float)$row['longitude'];
        $row['ketinggian_air_cm'] = $row['ketinggian_air_cm'] !== null ? (int)$row['ketinggian_air_cm'] : null;
    }
    unset($row); // Putus referensi setelah loop

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    // Error query database
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data lokasi.', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    // Error umum lainnya
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan server.', 'message' => $e->getMessage()]);
}
