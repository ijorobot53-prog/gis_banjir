<?php
/**
 * Halaman Verifikasi Laporan Warga
 * File: admin/laporan.php
 *
 * Fitur:
 * - List laporan dengan filter status (menunggu/disetujui/ditolak)
 * - Preview foto di modal
 * - Tombol Setujui & Tolak dengan CSRF
 * - Modal detail laporan + peta Leaflet menampilkan pin lokasi pelapor
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
// Proses Aksi POST (Setujui / Tolak)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid.');
        header('Location: /GIS_BANJIR/admin/laporan.php');
        exit;
    }

    $action     = $_POST['action'] ?? '';
    $laporanId  = (int) ($_POST['laporan_id'] ?? 0);

    if ($laporanId > 0 && in_array($action, ['setujui', 'tolak'])) {
        $statusBaru = ($action === 'setujui') ? 'disetujui' : 'ditolak';
        $stmt = $db->prepare("UPDATE laporan_warga SET status = ? WHERE id = ?");
        $stmt->execute([$statusBaru, $laporanId]);

        $label = ($statusBaru === 'disetujui') ? 'disetujui' : 'ditolak';
        setFlashMessage('success', "Laporan berhasil <strong>{$label}</strong>.");
    }

    // Pertahankan filter yang aktif saat redirect
    $filterParam = isset($_POST['filter_status']) ? '?status=' . urlencode($_POST['filter_status']) : '';
    header('Location: /GIS_BANJIR/admin/laporan.php' . $filterParam);
    exit;
}

// ============================================================
// Filter & Pagination
// ============================================================
$filterStatus = $_GET['status'] ?? 'semua';
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 15;

// Bangun kondisi WHERE berdasarkan filter
$whereClause = '';
$bindParams  = [];

if (in_array($filterStatus, ['menunggu', 'disetujui', 'ditolak'])) {
    $whereClause = "WHERE lw.status = ?";
    $bindParams  = [$filterStatus];
}

$totalStmt = $db->prepare("
    SELECT COUNT(*)
    FROM laporan_warga lw
    {$whereClause}
");
$totalStmt->execute($bindParams);
$total = (int) $totalStmt->fetchColumn();

$baseUrl = '/GIS_BANJIR/admin/laporan.php' . ($filterStatus !== 'semua' ? '?status=' . urlencode($filterStatus) : '');
$paging  = pagination($total, $perPage, $page, $baseUrl);
$offset  = $paging['offset'];

$dataStmt = $db->prepare("
    SELECT lw.*, k.nama AS nama_kecamatan
    FROM laporan_warga lw
    LEFT JOIN kecamatan k ON lw.kecamatan_id = k.id
    {$whereClause}
    ORDER BY lw.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$dataStmt->execute($bindParams);
$laporanList = $dataStmt->fetchAll();

// Hitung jumlah tiap status untuk badge filter
$countStmt = $db->query("
    SELECT status, COUNT(*) AS jumlah FROM laporan_warga GROUP BY status
");
$statusCounts = [];
foreach ($countStmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = $row['jumlah'];
}

$flashMsg  = getFlashMessage();
$pageTitle = 'Verifikasi Laporan Warga';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Flash Notification -->
<?php if ($flashMsg): ?>
<div class="alert alert-<?= $flashMsg['type'] === 'error' ? 'danger' : $flashMsg['type'] ?> flash-alert alert-dismissible fade show" role="alert">
    <i class="fa-solid <?= $flashMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> me-2"></i>
    <?= $flashMsg['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fa-solid fa-flag me-2 text-warning"></i>Laporan Warga</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan Warga</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Tab Filter Status -->
<div class="data-card mb-4">
    <div class="data-card-header" style="flex-wrap:wrap; gap:8px">
        <?php
        $tabs = [
            'semua'    => ['label' => 'Semua', 'count' => array_sum($statusCounts), 'class' => 'btn-secondary'],
            'menunggu' => ['label' => 'Menunggu', 'count' => $statusCounts['menunggu'] ?? 0, 'class' => 'btn-warning'],
            'disetujui'=> ['label' => 'Disetujui', 'count' => $statusCounts['disetujui'] ?? 0, 'class' => 'btn-success'],
            'ditolak'  => ['label' => 'Ditolak', 'count' => $statusCounts['ditolak'] ?? 0, 'class' => 'btn-danger'],
        ];
        foreach ($tabs as $key => $tab):
            $isActive = ($filterStatus === $key);
        ?>
        <a href="/GIS_BANJIR/admin/laporan.php?status=<?= $key ?>"
           class="btn btn-sm <?= $isActive ? $tab['class'] : 'btn-outline-' . str_replace('btn-','', $tab['class']) ?>"
           style="border-radius:8px; font-size:12px">
            <?= $tab['label'] ?>
            <span class="badge <?= $isActive ? 'bg-white text-dark' : 'bg-secondary' ?> ms-1">
                <?= $tab['count'] ?>
            </span>
        </a>
        <?php endforeach; ?>
        <span class="ms-auto text-muted" style="font-size:12px">Menampilkan <?= $total ?> laporan</span>
    </div>
</div>

<!-- Tabel Laporan -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($laporanList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-flag fa-3x mb-3 d-block opacity-50"></i>
                Tidak ada laporan dengan status ini.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Laporan Warga">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Pelapor</th>
                        <th>Kecamatan</th>
                        <th>Ketinggian</th>
                        <th>Deskripsi</th>
                        <th>Foto</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporanList as $i => $lap): ?>
                    <tr>
                        <td class="text-muted"><?= $paging['offset'] + $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($lap['nama_pelapor'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size:11px; color:#64748b"><?= htmlspecialchars($lap['kontak'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars($lap['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="fw-semibold text-danger"><?= (int)$lap['ketinggian_cm'] ?></span> cm</td>
                        <td style="max-width:200px">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#334155"
                                 title="<?= htmlspecialchars($lap['deskripsi'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(mb_strimwidth($lap['deskripsi'] ?? '-', 0, 80, '...'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($lap['foto'])): ?>
                                <?php $fotoUrl = '/GIS_BANJIR/uploads/laporan/' . htmlspecialchars($lap['foto'], ENT_QUOTES, 'UTF-8'); ?>
                                <img src="<?= $fotoUrl ?>" alt="Foto Laporan"
                                     style="width:48px; height:48px; object-fit:cover; border-radius:8px; cursor:pointer; border:2px solid #e2e8f0"
                                     onclick="previewFoto('<?= $fotoUrl ?>')"
                                     title="Klik untuk preview">
                            <?php else: ?>
                                <span class="text-muted" style="font-size:11px">Tidak ada foto</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge badge-<?= $lap['status'] ?>">
                                <?= ucfirst($lap['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px; white-space:nowrap"><?= formatDate($lap['created_at']) ?></td>
                        <td>
                            <!-- Tombol Detail -->
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-1"
                                    style="border-radius:6px; padding:4px 9px; font-size:11px"
                                    onclick="bukaDetail(<?= htmlspecialchars(json_encode($lap), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)"
                                    title="Lihat Detail">
                                <i class="fa-solid fa-eye"></i> Detail
                            </button>
                            <br>
                            <!-- Tombol Setujui/Tolak hanya jika masih menunggu -->
                            <?php if ($lap['status'] === 'menunggu'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="laporan_id" value="<?= $lap['id'] ?>">
                                <input type="hidden" name="action" value="setujui">
                                <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-success me-1"
                                        style="border-radius:6px; padding:4px 9px; font-size:11px"
                                        title="Setujui Laporan">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="laporan_id" value="<?= $lap['id'] ?>">
                                <input type="hidden" name="action" value="tolak">
                                <input type="hidden" name="filter_status" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        style="border-radius:6px; padding:4px 9px; font-size:11px"
                                        title="Tolak Laporan">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($paging['totalPages'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top" style="border-color:#f1f5f9 !important">
            <span class="text-muted" style="font-size:12px">
                Halaman <?= $paging['currentPage'] ?> dari <?= $paging['totalPages'] ?>
            </span>
            <nav aria-label="Pagination Laporan">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= !$paging['prev'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $paging['prev'] ?? '#' ?>">&laquo;</a>
                    </li>
                    <?php foreach ($paging['links'] as $link): ?>
                    <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= $link['page'] ?></a>
                    </li>
                    <?php endforeach; ?>
                    <li class="page-item <?= !$paging['next'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $paging['next'] ?? '#' ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     MODAL: Preview Foto
     ============================================================ -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="lblModalFoto" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none; background:transparent">
            <div class="modal-body p-2 text-center">
                <img id="previewFotoImg" src="" alt="Preview Foto Laporan"
                     style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 8px 32px rgba(0,0,0,.5)">
            </div>
            <div class="modal-footer border-0 justify-content-center pb-3">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Detail Laporan + Peta Lokasi
     ============================================================ -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="lblModalDetail" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalDetail">
                    <i class="fa-solid fa-flag me-2"></i>Detail Laporan Warga
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <!-- Info Pelapor -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background:#f8fafc; border:1px solid #e2e8f0">
                            <h6 class="fw-semibold mb-3" style="font-size:13px; color:#1e293b">
                                <i class="fa-solid fa-user me-2 text-primary"></i>Informasi Pelapor
                            </h6>
                            <table style="font-size:13px; width:100%">
                                <tr>
                                    <td class="text-muted" style="width:40%; padding-bottom:6px">Nama</td>
                                    <td id="detailNama" class="fw-semibold"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="padding-bottom:6px">Kontak</td>
                                    <td id="detailKontak"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="padding-bottom:6px">Kecamatan</td>
                                    <td id="detailKecamatan"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="padding-bottom:6px">Ketinggian</td>
                                    <td id="detailKetinggian" class="fw-semibold text-danger"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted" style="padding-bottom:6px">Status</td>
                                    <td id="detailStatus"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Dilaporkan</td>
                                    <td id="detailTanggal"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <!-- Deskripsi & Foto -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 h-100" style="background:#f8fafc; border:1px solid #e2e8f0">
                            <h6 class="fw-semibold mb-2" style="font-size:13px; color:#1e293b">
                                <i class="fa-solid fa-align-left me-2 text-primary"></i>Deskripsi
                            </h6>
                            <p id="detailDeskripsi" style="font-size:13px; color:#334155; line-height:1.6"></p>
                            <div id="detailFotoWrap" class="mt-2 d-none">
                                <h6 class="fw-semibold mb-2" style="font-size:13px; color:#1e293b">
                                    <i class="fa-solid fa-image me-2 text-primary"></i>Foto
                                </h6>
                                <img id="detailFoto" src="" alt="Foto Laporan"
                                     style="width:100%; max-height:150px; object-fit:cover; border-radius:8px; cursor:pointer"
                                     onclick="previewFoto(this.src)">
                            </div>
                        </div>
                    </div>
                    <!-- Peta lokasi laporan -->
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px">
                            <i class="fa-solid fa-map-location-dot me-2 text-primary"></i>Lokasi Laporan
                        </label>
                        <div id="mapDetail" style="height:220px; border:1px solid #e2e8f0; border-radius:10px; z-index:1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================
// Preview Foto di Modal
// ============================================================
function previewFoto(url) {
    document.getElementById('previewFotoImg').src = url;
    new bootstrap.Modal(document.getElementById('modalFoto')).show();
}

// ============================================================
// Inisialisasi peta detail laporan
// ============================================================
let mapDetailObj    = null;
let mapDetailMarker = null;

document.getElementById('modalDetail').addEventListener('shown.bs.modal', function () {
    if (!mapDetailObj) {
        mapDetailObj = L.map('mapDetail').setView([3.5952, 98.6722], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(mapDetailObj);
    }
    setTimeout(() => mapDetailObj.invalidateSize(), 200);
});

// ============================================================
// Buka modal detail laporan
// ============================================================
function bukaDetail(data) {
    document.getElementById('detailNama').textContent      = data.nama_pelapor || '-';
    document.getElementById('detailKontak').textContent    = data.kontak       || '-';
    document.getElementById('detailKecamatan').textContent = data.nama_kecamatan || '-';
    document.getElementById('detailKetinggian').textContent = data.ketinggian_cm + ' cm';
    document.getElementById('detailDeskripsi').textContent  = data.deskripsi   || '-';
    document.getElementById('detailTanggal').textContent    = data.created_at  || '-';

    // Badge status
    const statusEl  = document.getElementById('detailStatus');
    const badgeClass = {
        menunggu: 'badge-menunggu',
        disetujui: 'badge-disetujui',
        ditolak:   'badge-ditolak'
    };
    statusEl.innerHTML = '<span class="admin-badge ' + (badgeClass[data.status] || '') + '">'
        + data.status.charAt(0).toUpperCase() + data.status.slice(1) + '</span>';

    // Foto
    if (data.foto) {
        const fotoUrl = '/GIS_BANJIR/uploads/laporan/' + data.foto;
        document.getElementById('detailFoto').src = fotoUrl;
        document.getElementById('detailFotoWrap').classList.remove('d-none');
    } else {
        document.getElementById('detailFotoWrap').classList.add('d-none');
    }

    // Tampilkan modal dulu, baru update peta
    new bootstrap.Modal(document.getElementById('modalDetail')).show();

    // Update marker peta setelah modal muncul
    document.getElementById('modalDetail').addEventListener('shown.bs.modal', function setMarker() {
        const lat = parseFloat(data.latitude);
        const lng = parseFloat(data.longitude);
        if (!isNaN(lat) && !isNaN(lng) && mapDetailObj) {
            if (mapDetailMarker) mapDetailMarker.remove();
            mapDetailMarker = L.marker([lat, lng])
                .addTo(mapDetailObj)
                .bindPopup('<strong>' + (data.nama_pelapor || 'Pelapor') + '</strong><br>Ketinggian: ' + data.ketinggian_cm + ' cm')
                .openPopup();
            mapDetailObj.setView([lat, lng], 15);
        }
        // Hapus listener agar tidak bertumpuk
        document.getElementById('modalDetail').removeEventListener('shown.bs.modal', setMarker);
    }, { once: true });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
