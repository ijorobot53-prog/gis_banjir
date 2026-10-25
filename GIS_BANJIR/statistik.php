<?php
/**
 * Halaman Data & Statistik Banjir
 * File: statistik.php
 *
 * Menampilkan tabel kejadian banjir dengan pagination, 4 grafik Chart.js,
 * fitur pencarian, sortir kolom, dan ekspor CSV.
 */

// Muat konfigurasi database dan helper
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// ── Judul halaman (harus sebelum include header) ─────────────────────────────
$pageTitle = 'Data & Statistik Banjir';

// ── Parameter pencarian, sortir, dan pagination ───────────────────────────────
$search      = isset($_GET['q'])    ? trim($_GET['q'])         : '';
$sortCol     = isset($_GET['sort']) ? trim($_GET['sort'])       : 'tanggal';
$sortDir     = (isset($_GET['dir']) && strtolower($_GET['dir']) === 'asc') ? 'ASC' : 'DESC';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage     = 20;

// Kolom yang diizinkan untuk sortir (whitelist agar aman)
$allowedSortCols = ['tanggal', 'nama_lokasi', 'nama_kecamatan', 'tingkat',
                    'ketinggian_cm', 'durasi_jam', 'korban_terdampak'];
if (!in_array($sortCol, $allowedSortCols, true)) {
    $sortCol = 'tanggal';
}

// ── Query database ────────────────────────────────────────────────────────────
$kejadianList = [];
$totalRecords = 0;
$paginationData = [];
$dbError = '';

try {
    $pdo = getDB();

    // Kondisi pencarian
    $searchCondition = '';
    $searchParams    = [];
    if ($search !== '') {
        $searchCondition = "AND (lr.nama LIKE :search
                             OR k.nama LIKE :search2
                             OR kb.keterangan LIKE :search3)";
        $searchParams[':search']  = '%' . $search . '%';
        $searchParams[':search2'] = '%' . $search . '%';
        $searchParams[':search3'] = '%' . $search . '%';
    }

    // Hitung total untuk pagination
    $sqlCount = "SELECT COUNT(*)
                 FROM kejadian_banjir kb
                 LEFT JOIN lokasi_rawan lr ON lr.id = kb.lokasi_id
                 LEFT JOIN kecamatan    k  ON k.id  = lr.kecamatan_id
                 WHERE 1=1 {$searchCondition}";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($searchParams);
    $totalRecords = (int)$stmtCount->fetchColumn();

    // Hitung offset
    $offset = ($currentPage - 1) * $perPage;

    // Query data utama
    $sqlData = "SELECT
                    kb.id,
                    kb.tanggal,
                    COALESCE(lr.nama, '-')    AS nama_lokasi,
                    COALESCE(k.nama,  '-')    AS nama_kecamatan,
                    COALESCE(lr.tingkat, '-') AS tingkat,
                    kb.ketinggian_cm,
                    kb.durasi_jam,
                    kb.korban_terdampak,
                    kb.keterangan
                FROM kejadian_banjir kb
                LEFT JOIN lokasi_rawan lr ON lr.id = kb.lokasi_id
                LEFT JOIN kecamatan    k  ON k.id  = lr.kecamatan_id
                WHERE 1=1 {$searchCondition}
                ORDER BY {$sortCol} {$sortDir}
                LIMIT :limit OFFSET :offset";

    $stmtData = $pdo->prepare($sqlData);
    // Bind parameter pencarian jika ada
    foreach ($searchParams as $key => $val) {
        $stmtData->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmtData->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmtData->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmtData->execute();
    $kejadianList = $stmtData->fetchAll();

    // Generate data pagination menggunakan helper
    $baseUrl       = 'statistik.php?q=' . urlencode($search) . '&sort=' . urlencode($sortCol) . '&dir=' . strtolower($sortDir);
    $paginationData = pagination($totalRecords, $perPage, $currentPage, $baseUrl);

} catch (PDOException $e) {
    $dbError = 'Gagal memuat data: ' . $e->getMessage();
}

// ── Include header ────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero section statistik ─────────────────────────────────────────────── -->
<section class="page-hero py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-right">
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 small opacity-75">
                        <li class="breadcrumb-item">
                            <a href="index.php" class="text-white text-decoration-none">Beranda</a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Statistik</li>
                    </ol>
                </nav>
                <h1 class="display-6 fw-bold mb-2">
                    <i class="fa-solid fa-chart-bar me-3"></i>Data &amp; Statistik Banjir
                </h1>
                <p class="lead mb-0 opacity-90">
                    Visualisasi data historis kejadian banjir di Kota Medan beserta analisis tren dan statistik lengkap.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
                <span class="badge bg-white text-primary fs-6 px-3 py-2">
                    <i class="fa-solid fa-database me-2"></i>
                    <?= number_format($totalRecords) ?> Total Kejadian
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ── 4 Grafik Chart.js ────────────────────────────────────────────────────── -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
            <h2 class="fw-bold">Visualisasi Data</h2>
            <p class="text-muted">Grafik interaktif kejadian banjir berdasarkan berbagai parameter</p>
        </div>

        <!-- Grid 2×2 chart -->
        <div class="row g-4">

            <!-- Chart 1: Kejadian per Tahun (Bar) -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fa-solid fa-calendar-days text-primary me-2"></i>
                        Kejadian Banjir per Tahun
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position:relative; height:280px;">
                            <canvas id="chartPerTahun" aria-label="Grafik kejadian banjir per tahun" role="img"></canvas>
                        </div>
                        <div id="chartPerTahunLoading" class="text-center text-muted py-4">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat data…
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 2: Proporsi Tingkat Kerawanan (Doughnut) -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fa-solid fa-circle-half-stroke text-warning me-2"></i>
                        Proporsi Tingkat Kerawanan
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <div class="chart-container" style="position:relative; height:280px; width:100%;">
                            <canvas id="chartTingkat" aria-label="Grafik proporsi tingkat kerawanan" role="img"></canvas>
                        </div>
                        <div id="chartTingkatLoading" class="text-center text-muted py-4">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat data…
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 3: Kejadian per Kecamatan (Horizontal Bar) -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fa-solid fa-map-pin text-danger me-2"></i>
                        Kejadian per Kecamatan
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position:relative; height:280px;">
                            <canvas id="chartPerKecamatan" aria-label="Grafik kejadian banjir per kecamatan" role="img"></canvas>
                        </div>
                        <div id="chartPerKecamatanLoading" class="text-center text-muted py-4">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat data…
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart 4: Tren Ketinggian Air (Line) -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent fw-semibold">
                        <i class="fa-solid fa-water text-info me-2"></i>
                        Tren Ketinggian Air Rata-rata (12 Bulan)
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position:relative; height:280px;">
                            <canvas id="chartTren" aria-label="Grafik tren ketinggian air rata-rata per bulan" role="img"></canvas>
                        </div>
                        <div id="chartTrenLoading" class="text-center text-muted py-4">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>Memuat data…
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row charts -->
    </div>
</section>

<!-- ── Tabel Kejadian Banjir ────────────────────────────────────────────────── -->
<section class="py-5 bg-body-secondary">
    <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
            <h2 class="fw-bold">Tabel Kejadian Banjir</h2>
            <p class="text-muted">Data lengkap seluruh kejadian banjir yang tercatat</p>
        </div>

        <!-- Toolbar: Pencarian + Ekspor CSV -->
        <div class="card shadow-sm" data-aos="fade-up" data-aos-delay="50">
            <div class="card-body">

                <!-- Baris kontrol -->
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-6">
                        <form method="GET" action="statistik.php" id="formCari">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent">
                                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                </span>
                                <input type="text" class="form-control" id="inputCari" name="q"
                                       placeholder="Cari lokasi, kecamatan, keterangan…"
                                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                                       autocomplete="off">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortCol, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="dir"  value="<?= strtolower($sortDir) ?>">
                                <button class="btn btn-primary" type="submit">Cari</button>
                                <?php if ($search !== ''): ?>
                                    <a href="statistik.php" class="btn btn-outline-secondary" title="Hapus filter">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-muted small me-3">
                            <?= number_format($totalRecords) ?> data ditemukan
                        </span>
                        <button class="btn btn-success btn-sm" id="btnExportCSV">
                            <i class="fa-solid fa-file-csv me-1"></i>Ekspor CSV
                        </button>
                    </div>
                </div>

                <?php if ($dbError): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Tabel -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" id="tabelKejadian">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" class="text-center" style="width:50px;">No</th>
                                <?php
                                // Helper untuk render header kolom sortable
                                $cols = [
                                    'tanggal'          => 'Tanggal',
                                    'nama_lokasi'      => 'Lokasi',
                                    'nama_kecamatan'   => 'Kecamatan',
                                    'tingkat'          => 'Tingkat',
                                    'ketinggian_cm'    => 'Ketinggian (cm)',
                                    'durasi_jam'       => 'Durasi (jam)',
                                    'korban_terdampak' => 'Korban',
                                ];
                                foreach ($cols as $colKey => $colLabel):
                                    $isActive = ($sortCol === $colKey);
                                    $newDir   = ($isActive && $sortDir === 'ASC') ? 'desc' : 'asc';
                                    $icon     = $isActive
                                        ? ($sortDir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down')
                                        : 'fa-sort';
                                    $sortUrl  = 'statistik.php?q=' . urlencode($search)
                                              . '&sort=' . $colKey . '&dir=' . $newDir;
                                ?>
                                <th scope="col">
                                    <a href="<?= htmlspecialchars($sortUrl, ENT_QUOTES, 'UTF-8') ?>"
                                       class="text-white text-decoration-none d-flex align-items-center gap-1 <?= $isActive ? 'fw-bold' : '' ?>">
                                        <?= htmlspecialchars($colLabel, ENT_QUOTES, 'UTF-8') ?>
                                        <i class="fa-solid <?= $icon ?> small ms-1"></i>
                                    </a>
                                </th>
                                <?php endforeach; ?>
                                <th scope="col">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kejadianList)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                                        <?= $search !== ''
                                            ? 'Tidak ada hasil untuk pencarian "<strong>' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '</strong>"'
                                            : 'Belum ada data kejadian banjir.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kejadianList as $i => $banjir): ?>
                                    <?php
                                    // Tentukan warna baris berdasarkan tingkat
                                    $rowClass = match($banjir['tingkat']) {
                                        'tinggi' => 'table-danger',
                                        'sedang' => 'table-warning',
                                        default  => ''
                                    };
                                    // Badge tingkat
                                    $badgeClass = match($banjir['tingkat']) {
                                        'tinggi' => 'bg-danger',
                                        'sedang' => 'bg-warning text-dark',
                                        'rendah' => 'bg-success',
                                        default  => 'bg-secondary'
                                    };
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td class="text-center text-muted small">
                                            <?= $paginationData['offset'] + $i + 1 ?>
                                        </td>
                                        <td><?= htmlspecialchars(formatDate($banjir['tanggal']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($banjir['nama_lokasi'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($banjir['nama_kecamatan'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= htmlspecialchars(ucfirst($banjir['tingkat']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars(number_format($banjir['ketinggian_cm']), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(number_format((float)$banjir['durasi_jam'], 1), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(number_format($banjir['korban_terdampak']), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="text-muted small" style="max-width:200px;">
                                            <?php
                                            $ket = $banjir['keterangan'] ?? '-';
                                            if (mb_strlen($ket) > 80) {
                                                $ket = mb_substr($ket, 0, 80) . '…';
                                            }
                                            echo htmlspecialchars($ket, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div><!-- /.table-responsive -->

                <!-- Pagination -->
                <?php if (!empty($paginationData) && $paginationData['totalPages'] > 1): ?>
                <nav aria-label="Navigasi halaman" class="mt-4">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Tombol Prev -->
                        <li class="page-item <?= $paginationData['prev'] ? '' : 'disabled' ?>">
                            <a class="page-link"
                               href="<?= htmlspecialchars($paginationData['prev'] ?? '#', ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Halaman sebelumnya">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Tombol halaman pertama jika tidak muncul -->
                        <?php if (!empty($paginationData['links']) && $paginationData['links'][0]['page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="statistik.php?q=<?= urlencode($search) ?>&sort=<?= urlencode($sortCol) ?>&dir=<?= strtolower($sortDir) ?>&page=1">1</a>
                            </li>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>

                        <!-- Link halaman terdekat -->
                        <?php foreach ($paginationData['links'] as $link): ?>
                            <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $link['active'] ? 'aria-current="page"' : '' ?>>
                                    <?= $link['page'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>

                        <!-- Tombol halaman terakhir jika tidak muncul -->
                        <?php
                        $lastLink = end($paginationData['links']);
                        if ($lastLink && $lastLink['page'] < $paginationData['totalPages']):
                        ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                            <li class="page-item">
                                <a class="page-link"
                                   href="statistik.php?q=<?= urlencode($search) ?>&sort=<?= urlencode($sortCol) ?>&dir=<?= strtolower($sortDir) ?>&page=<?= $paginationData['totalPages'] ?>">
                                    <?= $paginationData['totalPages'] ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Tombol Next -->
                        <li class="page-item <?= $paginationData['next'] ? '' : 'disabled' ?>">
                            <a class="page-link"
                               href="<?= htmlspecialchars($paginationData['next'] ?? '#', ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="Halaman berikutnya">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <p class="text-center text-muted small mt-2">
                    Halaman <?= $paginationData['currentPage'] ?> dari <?= $paginationData['totalPages'] ?>
                    &mdash; <?= number_format($totalRecords) ?> data
                </p>
                <?php endif; ?>

            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div>
</section>

<!-- ── Chart.js & logika grafik ────────────────────────────────────────────── -->
<!-- Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
/**
 * Statistik Page Scripts
 * - Load 4 grafik dari endpoint API
 * - Ekspor CSV dari data tabel halaman saat ini
 */
(function () {
    'use strict';

    // ── Deteksi dark mode untuk warna teks grafik ─────────────────────────
    const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = () => isDark() ? '#adb5bd' : '#495057';
    const gridColor = () => isDark() ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.06)';

    // ── Helper: tampilkan/sembunyikan loading spinner ─────────────────────
    function hideLoading(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    // ── Helper: ambil data dari API ───────────────────────────────────────
    async function fetchChart(type) {
        const res = await fetch('api/statistik.php?type=' + type);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    }

    // ── Chart 1: Kejadian per Tahun (Bar Chart) ───────────────────────────
    async function loadChartPerTahun() {
        try {
            const data = await fetchChart('per_tahun');
            hideLoading('chartPerTahunLoading');

            new Chart(document.getElementById('chartPerTahun'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Kejadian',
                        data: data.data,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor:     'rgba(13, 110, 253, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.y + ' kejadian'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor(),
                                precision: 0
                            },
                            grid: { color: gridColor() }
                        },
                        x: {
                            ticks: { color: textColor() },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (e) {
            document.getElementById('chartPerTahunLoading').innerHTML =
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Gagal memuat data</span>';
        }
    }

    // ── Chart 2: Tingkat Kerawanan (Doughnut) ────────────────────────────
    async function loadChartTingkat() {
        try {
            const data = await fetchChart('tingkat');
            hideLoading('chartTingkatLoading');

            new Chart(document.getElementById('chartTingkat'), {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: data.colors,
                        borderWidth: 2,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor(),
                                padding: 16,
                                font: { size: 13 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' lokasi'
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        } catch (e) {
            document.getElementById('chartTingkatLoading').innerHTML =
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Gagal memuat data</span>';
        }
    }

    // ── Chart 3: Kejadian per Kecamatan (Horizontal Bar) ─────────────────
    async function loadChartPerKecamatan() {
        try {
            const data = await fetchChart('per_kecamatan');
            hideLoading('chartPerKecamatanLoading');

            new Chart(document.getElementById('chartPerKecamatan'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Jumlah Kejadian',
                        data: data.data,
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        borderColor:     'rgba(220, 53, 69, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    indexAxis: 'y',       // Horizontal bar
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.x + ' kejadian'
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor(),
                                precision: 0
                            },
                            grid: { color: gridColor() }
                        },
                        y: {
                            ticks: {
                                color: textColor(),
                                font: { size: 11 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (e) {
            document.getElementById('chartPerKecamatanLoading').innerHTML =
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Gagal memuat data</span>';
        }
    }

    // ── Chart 4: Tren Ketinggian Air (Line) ──────────────────────────────
    async function loadChartTren() {
        try {
            const data = await fetchChart('tren');
            hideLoading('chartTrenLoading');

            new Chart(document.getElementById('chartTren'), {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Rata-rata Ketinggian (cm)',
                        data: data.data,
                        borderColor:     'rgba(13, 202, 240, 1)',
                        backgroundColor: 'rgba(13, 202, 240, 0.15)',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.y + ' cm'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: textColor(),
                                callback: val => val + ' cm'
                            },
                            grid: { color: gridColor() }
                        },
                        x: {
                            ticks: {
                                color: textColor(),
                                maxRotation: 45,
                                font: { size: 11 }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        } catch (e) {
            document.getElementById('chartTrenLoading').innerHTML =
                '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i>Gagal memuat data</span>';
        }
    }

    // ── Muat semua grafik secara bersamaan ────────────────────────────────
    loadChartPerTahun();
    loadChartTingkat();
    loadChartPerKecamatan();
    loadChartTren();

    // ── Ekspor CSV dari data tabel yang tampil saat ini ───────────────────
    document.getElementById('btnExportCSV').addEventListener('click', function () {
        const table = document.getElementById('tabelKejadian');
        if (!table) return;

        const rows  = table.querySelectorAll('tr');
        const lines = [];

        rows.forEach(function (row) {
            const cells = row.querySelectorAll('th, td');
            // Ambil teks tiap sel, bersihkan, bungkus dengan kutip ganda
            const rowData = Array.from(cells).map(function (cell) {
                const text = cell.innerText.replace(/"/g, '""').trim();
                return '"' + text + '"';
            });
            lines.push(rowData.join(','));
        });

        // Tambahkan BOM agar Excel bisa membaca UTF-8
        const csvContent = '\uFEFF' + lines.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href     = url;
        link.download = 'statistik_banjir_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });

})();
</script>

<?php
// ── Include footer ────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/footer.php';
?>
