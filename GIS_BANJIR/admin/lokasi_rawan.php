<?php
/**
 * Halaman CRUD Lokasi Rawan Banjir
 * File: admin/lokasi_rawan.php
 *
 * Fitur:
 * - List lokasi dengan pagination dan search
 * - Tambah/Edit data via modal Bootstrap + map picker Leaflet
 * - AJAX load kelurahan berdasarkan kecamatan
 * - Hapus data dengan konfirmasi SweetAlert2
 * - CSRF protection semua form
 */

// ============================================================
// Cek Session — HARUS di paling atas
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
        header('Location: /GIS_BANJIR/admin/lokasi_rawan.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        // Ambil dan sanitasi data form
        $nama            = trim($_POST['nama'] ?? '');
        $kecamatan_id    = (int) ($_POST['kecamatan_id'] ?? 0);
        $kelurahan_id    = ($_POST['kelurahan_id'] !== '' && $_POST['kelurahan_id'] !== null) ? (int) $_POST['kelurahan_id'] : null;
        $latitude        = (float) ($_POST['latitude'] ?? 0);
        $longitude       = (float) ($_POST['longitude'] ?? 0);
        $tingkat         = $_POST['tingkat'] ?? 'rendah';
        $ketinggian      = (int) ($_POST['ketinggian_air_cm'] ?? 0);
        $penyebab        = trim($_POST['penyebab'] ?? '');
        $keterangan      = trim($_POST['keterangan'] ?? '');
        $status          = $_POST['status'] ?? 'aktif';

        // Validasi field wajib
        if (empty($nama) || $kecamatan_id <= 0 || $latitude == 0 || $longitude == 0) {
            setFlashMessage('error', 'Nama, kecamatan, dan koordinat wajib diisi.');
            header('Location: /GIS_BANJIR/admin/lokasi_rawan.php');
            exit;
        }

        // Pastikan nilai enum valid
        if (!in_array($tingkat, ['rendah','sedang','tinggi'])) $tingkat = 'rendah';
        if (!in_array($status, ['aktif','nonaktif'])) $status = 'aktif';

        if ($action === 'tambah') {
            $stmt = $db->prepare("
                INSERT INTO lokasi_rawan
                    (nama, kecamatan_id, kelurahan_id, latitude, longitude, tingkat, ketinggian_air_cm, penyebab, keterangan, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama, $kecamatan_id, $kelurahan_id, $latitude, $longitude, $tingkat, $ketinggian, $penyebab, $keterangan, $status]);
            setFlashMessage('success', "Lokasi rawan <strong>{$nama}</strong> berhasil ditambahkan.");
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE lokasi_rawan
                SET nama=?, kecamatan_id=?, kelurahan_id=?, latitude=?, longitude=?,
                    tingkat=?, ketinggian_air_cm=?, penyebab=?, keterangan=?, status=?
                WHERE id=?
            ");
            $stmt->execute([$nama, $kecamatan_id, $kelurahan_id, $latitude, $longitude, $tingkat, $ketinggian, $penyebab, $keterangan, $status, $id]);
            setFlashMessage('success', "Lokasi rawan <strong>{$nama}</strong> berhasil diperbarui.");
        }

    } elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM lokasi_rawan WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Data lokasi rawan berhasil dihapus.');
        }
    }

    header('Location: /GIS_BANJIR/admin/lokasi_rawan.php');
    exit;
}

// ============================================================
// Ambil Data untuk Ditampilkan
// ============================================================

// Pencarian
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

// Query dengan filter search
$searchParam = "%{$search}%";
$totalStmt   = $db->prepare("
    SELECT COUNT(*)
    FROM lokasi_rawan lr
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    WHERE lr.nama LIKE ? OR k.nama LIKE ?
");
$totalStmt->execute([$searchParam, $searchParam]);
$total = (int) $totalStmt->fetchColumn();

$paging  = pagination($total, $perPage, $page, '/GIS_BANJIR/admin/lokasi_rawan.php' . ($search ? '?search=' . urlencode($search) : ''));
$offset  = $paging['offset'];

$dataStmt = $db->prepare("
    SELECT lr.*, k.nama AS nama_kecamatan, kel.nama AS nama_kelurahan
    FROM lokasi_rawan lr
    LEFT JOIN kecamatan k ON lr.kecamatan_id = k.id
    LEFT JOIN kelurahan kel ON lr.kelurahan_id = kel.id
    WHERE lr.nama LIKE ? OR k.nama LIKE ?
    ORDER BY lr.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$dataStmt->execute([$searchParam, $searchParam]);
$lokasiList = $dataStmt->fetchAll();

// Data untuk dropdown form
$kecamatanList = $db->query("SELECT id, nama FROM kecamatan ORDER BY nama")->fetchAll();

// Flash message
$flashMsg = getFlashMessage();

$pageTitle = 'Lokasi Rawan Banjir';
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
        <h1><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Lokasi Rawan Banjir</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Lokasi Rawan</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-primary d-flex align-items-center gap-2" style="border-radius:9px; font-size:13px"
            data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fa-solid fa-plus"></i> Tambah Lokasi
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
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau kecamatan..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="border-left:none">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px; font-size:13px">Cari</button>
            <?php if ($search): ?>
            <a href="/GIS_BANJIR/admin/lokasi_rawan.php" class="btn btn-outline-secondary" style="border-radius:8px; font-size:13px">Reset</a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:12px">Total: <?= $total ?> lokasi</span>
        </form>
    </div>
</div>

<!-- Tabel Data -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($lokasiList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-map-location-dot fa-3x mb-3 d-block opacity-50"></i>
                <?= $search ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data lokasi rawan.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Lokasi Rawan Banjir">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Nama Lokasi</th>
                        <th>Kecamatan</th>
                        <th>Kelurahan</th>
                        <th>Tingkat</th>
                        <th>Ketinggian</th>
                        <th>Status</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lokasiList as $i => $lok): ?>
                    <tr>
                        <td class="text-muted"><?= $paging['offset'] + $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"
                                 title="<?= htmlspecialchars($lok['nama'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($lok['nama'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="font-size:11px; color:#94a3b8">
                                <?= number_format($lok['latitude'], 6) ?>, <?= number_format($lok['longitude'], 6) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($lok['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($lok['nama_kelurahan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="admin-badge badge-<?= $lok['tingkat'] ?>">
                                <?= ucfirst($lok['tingkat']) ?>
                            </span>
                        </td>
                        <td><?= (int)$lok['ketinggian_air_cm'] ?> cm</td>
                        <td>
                            <span class="admin-badge badge-<?= $lok['status'] ?>">
                                <?= ucfirst($lok['status']) ?>
                            </span>
                        </td>
                        <td>
                            <!-- Tombol Edit -->
                            <button class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px; padding:4px 9px"
                                    onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($lok), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)"
                                    title="Edit Data" aria-label="Edit <?= htmlspecialchars($lok['nama'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <!-- Form Hapus -->
                            <form id="hapus-<?= $lok['id'] ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= $lok['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; padding:4px 9px"
                                        onclick="konfirmasiHapus('hapus-<?= $lok['id'] ?>', '<?= htmlspecialchars(addslashes($lok['nama']), ENT_QUOTES, 'UTF-8') ?>')"
                                        title="Hapus Data" aria-label="Hapus <?= htmlspecialchars($lok['nama'], ENT_QUOTES, 'UTF-8') ?>">
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
            <nav aria-label="Pagination Lokasi Rawan">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= !$paging['prev'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $paging['prev'] ?? '#' ?>" aria-label="Sebelumnya">&laquo;</a>
                    </li>
                    <?php foreach ($paging['links'] as $link): ?>
                    <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= $link['page'] ?></a>
                    </li>
                    <?php endforeach; ?>
                    <li class="page-item <?= !$paging['next'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $paging['next'] ?? '#' ?>" aria-label="Berikutnya">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Render form field lokasi rawan.
 * Dipanggil dua kali — sekali untuk modal tambah, sekali untuk modal edit.
 * Prefix memastikan setiap field ID unik di halaman.
 *
 * @param string $prefix      'tambah' atau 'edit'
 * @param array  $kecList     Daftar kecamatan dari DB
 */
function renderFormLokasi(string $prefix, array $kecList): void
{
    $p = $prefix;
    ?>
    <div class="row g-3">
        <!-- Nama Lokasi -->
        <div class="col-12">
            <label for="<?= $p ?>Nama" class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
            <input type="text" id="<?= $p ?>Nama" name="nama" class="form-control"
                   placeholder="Contoh: Jl. Gatot Subroto Km 5" required maxlength="200">
        </div>

        <!-- Kecamatan & Kelurahan -->
        <div class="col-md-6">
            <label for="<?= $p ?>KecamatanId" class="form-label">Kecamatan <span class="text-danger">*</span></label>
            <select id="<?= $p ?>KecamatanId" name="kecamatan_id" class="form-select" required
                    onchange="loadKelurahan(this.value, '<?= $p ?>KelurahanId')">
                <option value="">-- Pilih Kecamatan --</option>
                <?php foreach ($kecList as $kec): ?>
                <option value="<?= $kec['id'] ?>">
                    <?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="<?= $p ?>KelurahanId" class="form-label">Kelurahan <small class="text-muted">(opsional)</small></label>
            <select id="<?= $p ?>KelurahanId" name="kelurahan_id" class="form-select" disabled>
                <option value="">-- Pilih Kecamatan Dulu --</option>
            </select>
        </div>

        <!-- Tingkat & Ketinggian Air -->
        <div class="col-md-6">
            <label for="<?= $p ?>Tingkat" class="form-label">Tingkat Risiko <span class="text-danger">*</span></label>
            <select id="<?= $p ?>Tingkat" name="tingkat" class="form-select" required>
                <option value="rendah">🟢 Rendah</option>
                <option value="sedang">🟡 Sedang</option>
                <option value="tinggi">🔴 Tinggi</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="<?= $p ?>Ketinggian" class="form-label">Ketinggian Air (cm)</label>
            <div class="input-group">
                <input type="number" id="<?= $p ?>Ketinggian" name="ketinggian_air_cm"
                       class="form-control" min="0" max="9999" value="0">
                <span class="input-group-text">cm</span>
            </div>
        </div>

        <!-- Koordinat -->
        <div class="col-md-6">
            <label for="<?= $p ?>Lat" class="form-label">Latitude <span class="text-danger">*</span></label>
            <input type="number" id="<?= $p ?>Lat" name="latitude" class="form-control"
                   step="any" placeholder="Contoh: 3.595200" required
                   oninput="syncMarker<?= ucfirst($p) ?>()">
        </div>

        <div class="col-md-6">
            <label for="<?= $p ?>Lng" class="form-label">Longitude <span class="text-danger">*</span></label>
            <input type="number" id="<?= $p ?>Lng" name="longitude" class="form-control"
                   step="any" placeholder="Contoh: 98.672200" required
                   oninput="syncMarker<?= ucfirst($p) ?>()">
        </div>

        <!-- Peta Picker -->
        <div class="col-12">
            <label class="form-label">
                <i class="fa-solid fa-map-pin me-1 text-primary"></i>
                Klik peta untuk memilih koordinat
            </label>
            <!-- Kotak pencarian alamat -->
            <div class="input-group mb-2">
                <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" id="<?= $p ?>CariAlamat"
                       class="form-control"
                       placeholder="Cari alamat, nama jalan, kelurahan, kecamatan..."
                       autocomplete="off"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();cariAlamat('<?= $p ?>');}">
                <button type="button" class="btn btn-outline-primary"
                        onclick="cariAlamat('<?= $p ?>')">
                    <i class="fa-solid fa-location-dot me-1"></i> Cari
                </button>
            </div>
            <!-- Hasil pencarian dropdown -->
            <div id="<?= $p ?>HasilCari"
                 class="list-group mb-2"
                 style="display:none;max-height:180px;overflow-y:auto;position:relative;z-index:9999;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12)">
            </div>
            <div id="<?= $p ?>MapPicker"
                 style="height:240px; border:1px solid #e2e8f0; border-radius:10px; z-index:1"></div>
            <small class="text-muted mt-1 d-block">
                <i class="fa-solid fa-circle-info text-primary me-1"></i>
                Cari alamat di atas, atau klik langsung pada peta untuk menentukan koordinat.
            </small>
        </div>

        <!-- Penyebab -->
        <div class="col-12">
            <label for="<?= $p ?>Penyebab" class="form-label">Penyebab Banjir</label>
            <input type="text" id="<?= $p ?>Penyebab" name="penyebab" class="form-control"
                   placeholder="Contoh: Luapan sungai, drainase buruk" maxlength="255">
        </div>

        <!-- Keterangan -->
        <div class="col-12">
            <label for="<?= $p ?>Keterangan" class="form-label">Keterangan Tambahan</label>
            <textarea id="<?= $p ?>Keterangan" name="keterangan" class="form-control" rows="3"
                      placeholder="Deskripsi detail kondisi lokasi..."></textarea>
        </div>

        <!-- Status -->
        <div class="col-md-6">
            <label for="<?= $p ?>Status" class="form-label">Status</label>
            <select id="<?= $p ?>Status" name="status" class="form-select">
                <option value="aktif">✅ Aktif</option>
                <option value="nonaktif">⭕ Nonaktif</option>
            </select>
        </div>
    </div>
    <?php
}
?>

<!-- ============================================================
     MODAL: Tambah Lokasi Rawan
     ============================================================ -->
<div class="modal fade modal-admin-form" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalTambahLabel">
                    <i class="fa-solid fa-plus me-2"></i>Tambah Lokasi Rawan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" id="formTambah">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="tambah">
                    <?php renderFormLokasi('tambah', $kecamatanList); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit Lokasi Rawan
     ============================================================ -->
<div class="modal fade modal-admin-form" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="modalEditLabel">
                    <i class="fa-solid fa-pencil me-2"></i>Edit Lokasi Rawan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" id="formEdit">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <?php renderFormLokasi('edit', $kecamatanList); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning text-dark">
                        <i class="fa-solid fa-save me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     DATA: List Kecamatan untuk JS
     ============================================================ -->
<script>
// Data kecamatan dari PHP
const kecamatanData = <?= json_encode($kecamatanList, JSON_UNESCAPED_UNICODE) ?>;

// ============================================================
// Inisialisasi Leaflet Map Picker (Tambah)
// ============================================================
let mapTambah = null;
let markerTambah = null;

document.getElementById('modalTambah').addEventListener('shown.bs.modal', function () {
    if (!mapTambah) {
        mapTambah = L.map('tambahMapPicker').setView([3.5952, 98.6722], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(mapTambah);

        mapTambah.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('tambahLat').value = lat;
            document.getElementById('tambahLng').value = lng;
            if (markerTambah) markerTambah.remove();
            markerTambah = L.marker(e.latlng).addTo(mapTambah)
                .bindPopup('Lokasi dipilih').openPopup();
        });
    }
    setTimeout(() => mapTambah.invalidateSize(), 200);
});

function syncMarkerTambah() {
    const lat = parseFloat(document.getElementById('tambahLat').value);
    const lng = parseFloat(document.getElementById('tambahLng').value);
    if (!isNaN(lat) && !isNaN(lng) && mapTambah) {
        if (markerTambah) markerTambah.remove();
        markerTambah = L.marker([lat, lng]).addTo(mapTambah);
        mapTambah.setView([lat, lng], 14);
    }
}

// ============================================================
// Inisialisasi Leaflet Map Picker (Edit)
// ============================================================
let mapEdit = null;
let markerEdit = null;

document.getElementById('modalEdit').addEventListener('shown.bs.modal', function () {
    if (!mapEdit) {
        mapEdit = L.map('editMapPicker').setView([3.5952, 98.6722], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(mapEdit);

        mapEdit.on('click', function (e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('editLat').value = lat;
            document.getElementById('editLng').value = lng;
            if (markerEdit) markerEdit.remove();
            markerEdit = L.marker(e.latlng).addTo(mapEdit).bindPopup('Lokasi dipilih').openPopup();
        });
    }
    const lat = parseFloat(document.getElementById('editLat').value);
    const lng = parseFloat(document.getElementById('editLng').value);
    if (!isNaN(lat) && !isNaN(lng)) {
        if (markerEdit) markerEdit.remove();
        markerEdit = L.marker([lat, lng]).addTo(mapEdit);
        mapEdit.setView([lat, lng], 14);
    }
    setTimeout(() => mapEdit.invalidateSize(), 200);
});

function syncMarkerEdit() {
    const lat = parseFloat(document.getElementById('editLat').value);
    const lng = parseFloat(document.getElementById('editLng').value);
    if (!isNaN(lat) && !isNaN(lng) && mapEdit) {
        if (markerEdit) markerEdit.remove();
        markerEdit = L.marker([lat, lng]).addTo(mapEdit);
        mapEdit.setView([lat, lng], 14);
    }
}

// ============================================================
// FUNGSI PENCARIAN ALAMAT (Nominatim OpenStreetMap)
// ============================================================
function cariAlamat(prefix) {
    const query = document.getElementById(prefix + 'CariAlamat').value.trim();
    const hasilEl = document.getElementById(prefix + 'HasilCari');

    if (!query) return;

    hasilEl.innerHTML = '<div class="list-group-item text-muted small"><i class="fa-solid fa-spinner fa-spin me-2"></i>Mencari...</div>';
    hasilEl.style.display = 'block';

    // Nominatim API — batasi ke Indonesia, prioritaskan Medan
    const url = 'https://nominatim.openstreetmap.org/search?format=json&q='
        + encodeURIComponent(query + ', Medan, Sumatera Utara, Indonesia')
        + '&limit=6&addressdetails=1&countrycodes=id';

    fetch(url, { headers: { 'Accept-Language': 'id' } })
        .then(r => r.json())
        .then(function(results) {
            if (!results.length) {
                hasilEl.innerHTML = '<div class="list-group-item text-muted small">Alamat tidak ditemukan. Coba kata kunci lain.</div>';
                return;
            }
            hasilEl.innerHTML = '';
            results.forEach(function(item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action py-2';
                btn.style.fontSize = '13px';
                btn.innerHTML = '<i class="fa-solid fa-location-dot text-primary me-2"></i>' + item.display_name;
                btn.addEventListener('click', function() {
                    const lat = parseFloat(item.lat).toFixed(6);
                    const lng = parseFloat(item.lon).toFixed(6);

                    // Isi input koordinat
                    document.getElementById(prefix + 'Lat').value = lat;
                    document.getElementById(prefix + 'Lng').value = lng;

                    // Gerakkan peta dan pasang marker
                    const mapObj = prefix === 'tambah' ? mapTambah : mapEdit;
                    if (mapObj) {
                        mapObj.setView([lat, lng], 16);
                        if (prefix === 'tambah') {
                            if (markerTambah) markerTambah.remove();
                            markerTambah = L.marker([lat, lng]).addTo(mapTambah)
                                .bindPopup('<strong>' + item.display_name.split(',')[0] + '</strong>').openPopup();
                        } else {
                            if (markerEdit) markerEdit.remove();
                            markerEdit = L.marker([lat, lng]).addTo(mapEdit)
                                .bindPopup('<strong>' + item.display_name.split(',')[0] + '</strong>').openPopup();
                        }
                    }

                    // Isi input alamat/nama jika tersedia
                    const namaInput = document.getElementById(prefix + 'Nama');
                    if (namaInput && !namaInput.value) {
                        namaInput.value = item.display_name.split(',')[0];
                    }

                    // Tutup dropdown hasil
                    hasilEl.style.display = 'none';
                    document.getElementById(prefix + 'CariAlamat').value = item.display_name.split(',').slice(0,2).join(',');
                });
                hasilEl.appendChild(btn);
            });
        })
        .catch(function() {
            hasilEl.innerHTML = '<div class="list-group-item text-danger small"><i class="fa-solid fa-circle-exclamation me-2"></i>Gagal menghubungi layanan pencarian alamat.</div>';
        });
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', function(e) {
    ['tambah','edit'].forEach(function(p) {
        const input  = document.getElementById(p + 'CariAlamat');
        const hasil  = document.getElementById(p + 'HasilCari');
        if (hasil && input && !input.contains(e.target) && !hasil.contains(e.target)) {
            hasil.style.display = 'none';
        }
    });
});

// ============================================================
// AJAX Load Kelurahan berdasarkan Kecamatan
// ============================================================
function loadKelurahan(kecamatanId, targetSelectId, selectedId = null) {
    const select = document.getElementById(targetSelectId);
    select.innerHTML = '<option value="">-- Memuat... --</option>';
    select.disabled = true;

    if (!kecamatanId) {
        select.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';
        return;
    }

    fetch('/GIS_BANJIR/api/kelurahan.php?kecamatan_id=' + kecamatanId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">-- Pilih Kelurahan (Opsional) --</option>';
            data.forEach(function (kel) {
                const opt = document.createElement('option');
                opt.value = kel.id;
                opt.textContent = kel.nama;
                if (selectedId && parseInt(selectedId) === parseInt(kel.id)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
            select.disabled = false;
        })
        .catch(() => {
            select.innerHTML = '<option value="">-- Gagal memuat kelurahan --</option>';
            select.disabled = false;
        });
}

// ============================================================
// Fungsi Buka Modal Edit dengan data dari baris tabel
// ============================================================
function bukaModalEdit(data) {
    document.getElementById('editId').value = data.id;
    document.getElementById('editNama').value = data.nama;
    document.getElementById('editKecamatanId').value = data.kecamatan_id;
    document.getElementById('editTingkat').value = data.tingkat;
    document.getElementById('editKetinggian').value = data.ketinggian_air_cm;
    document.getElementById('editPenyebab').value = data.penyebab || '';
    document.getElementById('editKeterangan').value = data.keterangan || '';
    document.getElementById('editStatus').value = data.status;
    document.getElementById('editLat').value = data.latitude;
    document.getElementById('editLng').value = data.longitude;

    // Load kelurahan lalu set selected
    loadKelurahan(data.kecamatan_id, 'editKelurahanId', data.kelurahan_id);

    const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
