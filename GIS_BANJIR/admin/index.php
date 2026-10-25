<?php
/**
 * Dashboard Admin
 * File: admin/index.php
 *
 * Menampilkan ringkasan statistik, grafik kejadian banjir 6 bulan terakhir,
 * laporan warga terbaru, dan kejadian banjir terbaru.
 */

// ============================================================
// Cek Session — HARUS di paling atas sebelum include apapun
// ============================================================
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /GIS_BANJIR/admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// ============================================================
// Ambil Data Statistik untuk Stat Cards
// ============================================================

// Total lokasi rawan aktif
$totalLokasiAktif = (int) $db->query("SELECT COUNT(*) FROM lokasi_rawan WHERE status = 'aktif'")->fetchColumn();

// Total titik evakuasi aktif
$totalPoskoAktif = (int) $db->query("SELECT COUNT(*) FROM titik_evakuasi WHERE status = 'aktif'")->fetchColumn();

// Laporan warga menunggu verifikasi
$totalLaporanMenunggu = (int) $db->query("SELECT COUNT(*) FROM laporan_warga WHERE status = 'menunggu'")->fetchColumn();

// Total kejadian banjir
$totalKejadian = (int) $db->query("SELECT COUNT(*) FROM kejadian_banjir")->fetchColumn();

// ============================================================
// Data Chart: Kejadian Banjir 6 Bulan Terakhir
// ============================================================
$chartData = [];
$chartLabels = [];

// Generate label 6 bulan terakhir (urut dari terlama ke terbaru)
$bulanIndo = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
for ($i = 5; $i >= 0; $i--) {
    $ts      = strtotime("-{$i} months");
    $bulan   = (int) date('n', $ts);
    $tahun   = (int) date('Y', $ts);
    $chartLabels[] = $bulanIndo[$bulan - 1] . ' ' . $tahun;

    // Hitung jumlah kejadian bulan tersebut
    $stmt = $db->prepare("SELECT COUNT(*) FROM kejadian_banjir WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmt->execute([$bulan, $tahun]);
    $chartData[] = (int) $stmt->fetchColumn();
}

// ============================================================
// 5 Laporan Warga Terbaru
// ============================================================
$stmtLaporan = $db->query("
    SELECT lw.id, lw.nama_pelapor, lw.kontak, lw.ketinggian_cm, lw.deskripsi,
           lw.status, lw.created_at, lw.foto,
           k.nama AS nama_kecamatan
    FROM laporan_warga lw
    LEFT JOIN kecamatan k ON lw.kecamatan_id = k.id
    ORDER BY lw.created_at DESC
    LIMIT 5
");
$laporanTerbaru = $stmtLaporan->fetchAll();

// ============================================================
// 5 Kejadian Banjir Terbaru
// ============================================================
$stmtKejadian = $db->query("
    SELECT kb.id, kb.tanggal, kb.ketinggian_cm, kb.durasi_jam, kb.korban_terdampak,
           lr.nama AS nama_lokasi, k.nama AS nama_kecamatan
    FROM kejadian_banjir kb
    LEFT JOIN lokasi_rawan lr ON kb.lokasi_id = lr.id
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    ORDER BY kb.tanggal DESC
    LIMIT 5
");
$kejadianTerbaru = $stmtKejadian->fetchAll();

// ============================================================
// Proses Aksi Cepat (Setujui/Tolak Laporan dari Dashboard)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_laporan'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid.');
    } else {
        $laporanId = (int) ($_POST['laporan_id'] ?? 0);
        $aksi      = $_POST['aksi_laporan'];

        if ($laporanId > 0 && in_array($aksi, ['setujui', 'tolak'])) {
            $statusBaru = ($aksi === 'setujui') ? 'disetujui' : 'ditolak';
            $stmt = $db->prepare("UPDATE laporan_warga SET status = ? WHERE id = ?");
            $stmt->execute([$statusBaru, $laporanId]);
            setFlashMessage('success', 'Status laporan berhasil diperbarui.');
        }
    }
    header('Location: /GIS_BANJIR/admin/index.php');
    exit;
}

// Ambil flash message jika ada
$flashMsg = getFlashMessage();

// Judul halaman untuk header
$pageTitle = 'Dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     KONTEN DASHBOARD
     ============================================================ -->

<!-- Flash message notifikasi -->
<?php if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] === 'error' ? 'danger' : $flashMsg['type'] ?> flash-alert alert-dismissible fade show" role="alert">
    <i class="fa-solid <?= $flashMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
    <?= htmlspecialchars($flashMsg['message'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-gauge-high me-2 text-primary"></i>Dashboard</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active" aria-current="page">Ringkasan Sistem</li>
            </ol>
        </nav>
    </div>
    <div class="text-muted" style="font-size:13px">
        <i class="fa-regular fa-calendar me-1"></i>
        <?= formatDate(date('Y-m-d'), 'l, d F Y') ?>
    </div>
</div>

<!-- ====== STAT CARDS ====== -->
<div class="row g-3 mb-4">

    <!-- Lokasi Rawan Aktif -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="stat-value"><?= $totalLokasiAktif ?></div>
            <div class="stat-label">Lokasi Rawan Aktif</div>
        </div>
    </div>

    <!-- Posko Evakuasi Aktif -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5; color:#065f46">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div class="stat-value"><?= $totalPoskoAktif ?></div>
            <div class="stat-label">Titik Evakuasi Aktif</div>
        </div>
    </div>

    <!-- Laporan Menunggu -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7; color:#b45309">
                <i class="fa-solid fa-flag"></i>
            </div>
            <div class="stat-value"><?= $totalLaporanMenunggu ?></div>
            <div class="stat-label">Laporan Menunggu Verifikasi</div>
        </div>
    </div>

    <!-- Total Kejadian Banjir -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2; color:#b91c1c">
                <i class="fa-solid fa-house-flood-water"></i>
            </div>
            <div class="stat-value"><?= $totalKejadian ?></div>
            <div class="stat-label">Total Kejadian Banjir</div>
        </div>
    </div>
</div>

<!-- ====== BARIS 2: Chart + Laporan Terbaru ====== -->
<div class="row g-3 mb-4">

    <!-- Chart Kejadian 6 Bulan Terakhir -->
    <div class="col-lg-6">
        <div class="data-card h-100">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-chart-bar me-2 text-primary"></i>Kejadian Banjir 6 Bulan Terakhir</h5>
            </div>
            <div class="data-card-body p-3">
                <canvas id="chartKejadian" height="180" aria-label="Grafik Kejadian Banjir 6 Bulan Terakhir" role="img"></canvas>
            </div>
        </div>
    </div>

    <!-- Info Cepat -->
    <div class="col-lg-6">
        <div class="data-card h-100">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-circle-info me-2 text-primary"></i>Aksi Cepat</h5>
            </div>
            <div class="data-card-body p-3">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="/GIS_BANJIR/admin/lokasi_rawan.php" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3 gap-2" style="border-radius:12px; font-size:12px;">
                            <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                            <span>Kelola Lokasi Rawan</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/GIS_BANJIR/admin/evakuasi.php" class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3 gap-2" style="border-radius:12px; font-size:12px;">
                            <i class="fa-solid fa-location-dot fa-2x"></i>
                            <span>Kelola Evakuasi</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/GIS_BANJIR/admin/laporan.php" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center py-3 gap-2 position-relative" style="border-radius:12px; font-size:12px;">
                            <i class="fa-solid fa-flag fa-2x"></i>
                            <span>Verifikasi Laporan</span>
                            <?php if ($totalLaporanMenunggu > 0): ?>
                            <span class="position-absolute top-0 end-0 mt-1 me-1 badge bg-danger rounded-pill" style="font-size:10px"><?= $totalLaporanMenunggu ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/GIS_BANJIR/admin/berita.php" class="btn btn-outline-info w-100 d-flex flex-column align-items-center py-3 gap-2" style="border-radius:12px; font-size:12px;">
                            <i class="fa-solid fa-newspaper fa-2x"></i>
                            <span>Kelola Berita</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ====== BARIS 3: Tabel Laporan + Kejadian Terbaru ====== -->
<div class="row g-3">

    <!-- Tabel 5 Laporan Warga Terbaru -->
    <div class="col-12">
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-flag me-2 text-warning"></i>Laporan Warga Terbaru</h5>
                <a href="/GIS_BANJIR/admin/laporan.php" class="btn btn-sm btn-outline-primary" style="font-size:12px; border-radius:7px;">
                    Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="data-card-body">
                <?php if (count($laporanTerbaru) === 0): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                        Belum ada laporan warga.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table w-100">
                        <thead>
                            <tr>
                                <th>Pelapor</th>
                                <th>Kecamatan</th>
                                <th>Ketinggian</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporanTerbaru as $lap): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($lap['nama_pelapor'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size:12px; color:#64748b"><?= htmlspecialchars($lap['kontak'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= htmlspecialchars($lap['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$lap['ketinggian_cm'] ?> cm</td>
                                <td>
                                    <span class="admin-badge badge-<?= htmlspecialchars($lap['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= ucfirst($lap['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:12px"><?= formatDate($lap['created_at']) ?></td>
                                <td>
                                    <?php if ($lap['status'] === 'menunggu'): ?>
                                    <!-- Form setujui -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="laporan_id" value="<?= $lap['id'] ?>">
                                        <input type="hidden" name="aksi_laporan" value="setujui">
                                        <button type="submit" class="btn btn-sm btn-success" style="font-size:11px; border-radius:6px; padding:4px 10px"
                                                title="Setujui Laporan">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <!-- Form tolak -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="laporan_id" value="<?= $lap['id'] ?>">
                                        <input type="hidden" name="aksi_laporan" value="tolak">
                                        <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px; border-radius:6px; padding:4px 10px"
                                                title="Tolak Laporan">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <a href="/GIS_BANJIR/admin/laporan.php" class="btn btn-sm btn-outline-secondary" style="font-size:11px; border-radius:6px; padding:4px 10px">Detail</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabel 5 Kejadian Banjir Terbaru -->
    <div class="col-12">
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-house-flood-water me-2 text-danger"></i>Kejadian Banjir Terbaru</h5>
                <a href="/GIS_BANJIR/admin/kejadian.php" class="btn btn-sm btn-outline-danger" style="font-size:12px; border-radius:7px;">
                    Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="data-card-body">
                <?php if (count($kejadianTerbaru) === 0): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                        Belum ada data kejadian banjir.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table w-100">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Lokasi</th>
                                <th>Kecamatan</th>
                                <th>Ketinggian</th>
                                <th>Durasi</th>
                                <th>Korban</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kejadianTerbaru as $kej): ?>
                            <tr>
                                <td style="white-space:nowrap"><?= formatDate($kej['tanggal']) ?></td>
                                <td><?= htmlspecialchars($kej['nama_lokasi'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($kej['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$kej['ketinggian_cm'] ?> cm</td>
                                <td><?= number_format((float)$kej['durasi_jam'], 1) ?> jam</td>
                                <td>
                                    <span class="fw-semibold text-danger"><?= number_format((int)$kej['korban_terdampak']) ?></span>
                                    <span style="font-size:11px; color:#64748b"> KK</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ====== CHART.JS SCRIPT ====== -->
<script>
// Data dari PHP dimasukkan ke variabel JavaScript
const chartLabels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
const chartData   = <?= json_encode($chartData) ?>;

// Render chart batang kejadian banjir
const ctx = document.getElementById('chartKejadian').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Jumlah Kejadian',
            data: chartData,
            backgroundColor: 'rgba(26, 86, 219, 0.75)',
            borderColor: '#1a56db',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function (ctx) {
                        return ' ' + ctx.parsed.y + ' kejadian';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font: { size: 11, family: 'Poppins' }
                },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { size: 11, family: 'Poppins' } },
                grid: { display: false }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
