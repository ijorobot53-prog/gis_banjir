<?php
/**
 * Halaman CRUD Kejadian Banjir
 * File: admin/kejadian.php
 *
 * Fitur:
 * - List kejadian banjir dengan pagination dan search
 * - Tambah/Edit via modal Bootstrap
 * - Hapus dengan konfirmasi SweetAlert2
 * - CSRF protection semua form
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
// Proses Form POST (Tambah / Edit / Hapus)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifikasi CSRF
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid.');
        header('Location: /GIS_BANJIR/admin/kejadian.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $lokasi_id         = ($_POST['lokasi_id'] !== '' ? (int) $_POST['lokasi_id'] : null);
        $tanggal           = $_POST['tanggal'] ?? '';
        $ketinggian_cm     = (int) ($_POST['ketinggian_cm'] ?? 0);
        $durasi_jam        = (float) ($_POST['durasi_jam'] ?? 0);
        $korban_terdampak  = (int) ($_POST['korban_terdampak'] ?? 0);
        $keterangan        = trim($_POST['keterangan'] ?? '');

        // Validasi tanggal wajib diisi
        if (empty($tanggal)) {
            setFlashMessage('error', 'Tanggal kejadian wajib diisi.');
            header('Location: /GIS_BANJIR/admin/kejadian.php');
            exit;
        }

        // Validasi format tanggal
        $tglObj = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$tglObj) {
            setFlashMessage('error', 'Format tanggal tidak valid.');
            header('Location: /GIS_BANJIR/admin/kejadian.php');
            exit;
        }

        if ($action === 'tambah') {
            $stmt = $db->prepare("
                INSERT INTO kejadian_banjir
                    (lokasi_id, tanggal, ketinggian_cm, durasi_jam, korban_terdampak, keterangan)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$lokasi_id, $tanggal, $ketinggian_cm, $durasi_jam, $korban_terdampak, $keterangan]);
            setFlashMessage('success', 'Data kejadian banjir berhasil ditambahkan.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE kejadian_banjir
                SET lokasi_id=?, tanggal=?, ketinggian_cm=?, durasi_jam=?,
                    korban_terdampak=?, keterangan=?
                WHERE id=?
            ");
            $stmt->execute([$lokasi_id, $tanggal, $ketinggian_cm, $durasi_jam, $korban_terdampak, $keterangan, $id]);
            setFlashMessage('success', 'Data kejadian banjir berhasil diperbarui.');
        }

    } elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM kejadian_banjir WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Data kejadian banjir berhasil dihapus.');
        }
    }

    header('Location: /GIS_BANJIR/admin/kejadian.php');
    exit;
}

// ============================================================
// Ambil Data untuk Ditampilkan
// ============================================================
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$searchParam = "%{$search}%";

$totalStmt = $db->prepare("
    SELECT COUNT(*)
    FROM kejadian_banjir kb
    LEFT JOIN lokasi_rawan lr ON kb.lokasi_id = lr.id
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    WHERE lr.nama LIKE ? OR k.nama LIKE ? OR kb.keterangan LIKE ?
");
$totalStmt->execute([$searchParam, $searchParam, $searchParam]);
$total = (int) $totalStmt->fetchColumn();

$paging = pagination($total, $perPage, $page,
    '/GIS_BANJIR/admin/kejadian.php' . ($search ? '?search=' . urlencode($search) : ''));
$offset = $paging['offset'];

$dataStmt = $db->prepare("
    SELECT kb.*, lr.nama AS nama_lokasi, k.nama AS nama_kecamatan
    FROM kejadian_banjir kb
    LEFT JOIN lokasi_rawan lr ON kb.lokasi_id = lr.id
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    WHERE lr.nama LIKE ? OR k.nama LIKE ? OR kb.keterangan LIKE ?
    ORDER BY kb.tanggal DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$dataStmt->execute([$searchParam, $searchParam, $searchParam]);
$kejadianList = $dataStmt->fetchAll();

// Daftar lokasi rawan untuk dropdown di modal
$lokasiRawanList = $db->query("
    SELECT lr.id, lr.nama, k.nama AS nama_kecamatan
    FROM lokasi_rawan lr
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    ORDER BY lr.nama
")->fetchAll();

$flashMsg  = getFlashMessage();
$pageTitle = 'Kejadian Banjir';
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
        <h1><i class="fa-solid fa-house-flood-water me-2 text-danger"></i>Kejadian Banjir</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Kejadian Banjir</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-danger d-flex align-items-center gap-2" style="border-radius:9px; font-size:13px"
            data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fa-solid fa-plus"></i> Tambah Kejadian
    </button>
</div>

<!-- Filter & Search -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <form method="GET" class="d-flex gap-2 flex-wrap w-100 align-items-center">
            <div class="input-group" style="max-width:320px">
                <span class="input-group-text bg-white" style="border-color:#e2e8f0">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control" placeholder="Cari lokasi, kecamatan..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="border-left:none">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px; font-size:13px">Cari</button>
            <?php if ($search): ?>
            <a href="/GIS_BANJIR/admin/kejadian.php" class="btn btn-outline-secondary" style="border-radius:8px; font-size:13px">Reset</a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:12px">Total: <?= $total ?> kejadian</span>
        </form>
    </div>
</div>

<!-- Tabel Data -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($kejadianList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-house-flood-water fa-3x mb-3 d-block opacity-50"></i>
                <?= $search ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data kejadian banjir.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Kejadian Banjir">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Kecamatan</th>
                        <th>Ketinggian</th>
                        <th>Durasi</th>
                        <th>Korban</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kejadianList as $i => $kej): ?>
                    <tr>
                        <td class="text-muted"><?= $paging['offset'] + $i + 1 ?></td>
                        <td style="white-space:nowrap; font-weight:500"><?= formatDate($kej['tanggal']) ?></td>
                        <td>
                            <div style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"
                                 title="<?= htmlspecialchars($kej['nama_lokasi'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($kej['nama_lokasi'] ?? '<em>Tidak diketahui</em>', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($kej['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="fw-semibold text-danger"><?= (int)$kej['ketinggian_cm'] ?></span>
                            <span style="font-size:11px; color:#64748b"> cm</span>
                        </td>
                        <td><?= number_format((float)$kej['durasi_jam'], 1) ?> jam</td>
                        <td>
                            <span class="fw-semibold"><?= number_format((int)$kej['korban_terdampak']) ?></span>
                            <span style="font-size:11px; color:#64748b"> KK</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px; padding:4px 9px"
                                    onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($kej), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)"
                                    title="Edit" aria-label="Edit kejadian <?= formatDate($kej['tanggal']) ?>">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <form id="hapus-<?= $kej['id'] ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= $kej['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; padding:4px 9px"
                                        onclick="konfirmasiHapus('hapus-<?= $kej['id'] ?>', 'kejadian <?= formatDate($kej['tanggal']) ?>')"
                                        title="Hapus" aria-label="Hapus kejadian <?= formatDate($kej['tanggal']) ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
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
            <nav aria-label="Pagination Kejadian">
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
     MODAL: Tambah Kejadian
     ============================================================ -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="lblModalTambah" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalTambah">
                    <i class="fa-solid fa-plus me-2"></i>Tambah Kejadian Banjir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="tambah">
                    <?php renderFormKejadian('tambah', $lokasiRawanList); ?>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" style="border-radius:8px">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit Kejadian
     ============================================================ -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="lblModalEdit" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalEdit">
                    <i class="fa-solid fa-pencil me-2"></i>Edit Kejadian Banjir
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <?php renderFormKejadian('edit', $lokasiRawanList); ?>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark" style="border-radius:8px">
                        <i class="fa-solid fa-save me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Render field form kejadian banjir.
 *
 * @param string $prefix      'tambah' atau 'edit'
 * @param array  $lokasiList  Daftar lokasi rawan dari DB
 */
function renderFormKejadian(string $prefix, array $lokasiList): void
{
    $p = $prefix;
    ?>
    <div class="row g-3">
        <!-- Lokasi -->
        <div class="col-12">
            <label for="<?= $p ?>LokasiId" class="form-label">Lokasi Rawan</label>
            <select id="<?= $p ?>LokasiId" name="lokasi_id" class="form-select">
                <option value="">-- Pilih Lokasi (Opsional) --</option>
                <?php foreach ($lokasiList as $lok): ?>
                <option value="<?= $lok['id'] ?>">
                    <?= htmlspecialchars($lok['nama'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($lok['nama_kecamatan']): ?>
                        (<?= htmlspecialchars($lok['nama_kecamatan'], ENT_QUOTES, 'UTF-8') ?>)
                    <?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Tanggal -->
        <div class="col-12">
            <label for="<?= $p ?>Tanggal" class="form-label">Tanggal Kejadian <span class="text-danger">*</span></label>
            <input type="date" id="<?= $p ?>Tanggal" name="tanggal" class="form-control"
                   max="<?= date('Y-m-d') ?>" required>
        </div>
        <!-- Ketinggian & Durasi -->
        <div class="col-md-6">
            <label for="<?= $p ?>Ketinggian" class="form-label">Ketinggian Air (cm)</label>
            <div class="input-group">
                <input type="number" id="<?= $p ?>Ketinggian" name="ketinggian_cm"
                       class="form-control" min="0" max="9999" value="0">
                <span class="input-group-text">cm</span>
            </div>
        </div>
        <div class="col-md-6">
            <label for="<?= $p ?>Durasi" class="form-label">Durasi (jam)</label>
            <div class="input-group">
                <input type="number" id="<?= $p ?>Durasi" name="durasi_jam"
                       class="form-control" min="0" step="0.1" value="0">
                <span class="input-group-text">jam</span>
            </div>
        </div>
        <!-- Korban -->
        <div class="col-12">
            <label for="<?= $p ?>Korban" class="form-label">Korban Terdampak (KK)</label>
            <div class="input-group">
                <input type="number" id="<?= $p ?>Korban" name="korban_terdampak"
                       class="form-control" min="0" value="0">
                <span class="input-group-text">KK</span>
            </div>
        </div>
        <!-- Keterangan -->
        <div class="col-12">
            <label for="<?= $p ?>Keterangan" class="form-label">Keterangan</label>
            <textarea id="<?= $p ?>Keterangan" name="keterangan" class="form-control" rows="3"
                      placeholder="Deskripsi singkat kejadian banjir..."></textarea>
        </div>
    </div>
    <?php
}
?>

<script>
// Buka modal edit dan isi field dengan data
function bukaModalEdit(data) {
    document.getElementById('editId').value             = data.id;
    document.getElementById('editLokasiId').value       = data.lokasi_id || '';
    document.getElementById('editTanggal').value        = data.tanggal;
    document.getElementById('editKetinggian').value     = data.ketinggian_cm;
    document.getElementById('editDurasi').value         = data.durasi_jam;
    document.getElementById('editKorban').value         = data.korban_terdampak;
    document.getElementById('editKeterangan').value     = data.keterangan || '';

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
