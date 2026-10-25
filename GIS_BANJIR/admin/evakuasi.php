<?php
/**
 * Halaman CRUD Titik Evakuasi
 * File: admin/evakuasi.php
 *
 * Fitur:
 * - List titik evakuasi dengan pagination dan search
 * - Tambah/Edit via modal Bootstrap + map picker Leaflet
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
        header('Location: /GIS_BANJIR/admin/evakuasi.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        // Ambil dan sanitasi data form
        $nama         = trim($_POST['nama'] ?? '');
        $jenis        = $_POST['jenis'] ?? 'lainnya';
        $alamat       = trim($_POST['alamat'] ?? '');
        $latitude     = (float) ($_POST['latitude'] ?? 0);
        $longitude    = (float) ($_POST['longitude'] ?? 0);
        $kapasitas    = (int) ($_POST['kapasitas'] ?? 0);
        $fasilitas    = trim($_POST['fasilitas'] ?? '');
        $kontak       = trim($_POST['kontak'] ?? '');
        $kecamatan_id = ($_POST['kecamatan_id'] !== '' ? (int) $_POST['kecamatan_id'] : null);
        $status       = $_POST['status'] ?? 'aktif';

        // Validasi field wajib
        if (empty($nama) || $latitude == 0 || $longitude == 0) {
            setFlashMessage('error', 'Nama dan koordinat wajib diisi.');
            header('Location: /GIS_BANJIR/admin/evakuasi.php');
            exit;
        }

        // Validasi enum
        $jenisValid = ['sekolah','masjid','gedung','lapangan','rumah_sakit','lainnya'];
        if (!in_array($jenis, $jenisValid)) $jenis = 'lainnya';
        if (!in_array($status, ['aktif','nonaktif'])) $status = 'aktif';

        if ($action === 'tambah') {
            $stmt = $db->prepare("
                INSERT INTO titik_evakuasi
                    (nama, jenis, alamat, latitude, longitude, kapasitas, fasilitas, kontak, kecamatan_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nama, $jenis, $alamat, $latitude, $longitude, $kapasitas, $fasilitas, $kontak, $kecamatan_id, $status]);
            setFlashMessage('success', "Titik evakuasi <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.");
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE titik_evakuasi
                SET nama=?, jenis=?, alamat=?, latitude=?, longitude=?,
                    kapasitas=?, fasilitas=?, kontak=?, kecamatan_id=?, status=?
                WHERE id=?
            ");
            $stmt->execute([$nama, $jenis, $alamat, $latitude, $longitude, $kapasitas, $fasilitas, $kontak, $kecamatan_id, $status, $id]);
            setFlashMessage('success', "Titik evakuasi <strong>" . htmlspecialchars($nama) . "</strong> berhasil diperbarui.");
        }

    } elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM titik_evakuasi WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Data titik evakuasi berhasil dihapus.');
        }
    }

    header('Location: /GIS_BANJIR/admin/evakuasi.php');
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
    FROM titik_evakuasi te
    LEFT JOIN kecamatan k ON te.kecamatan_id = k.id
    WHERE te.nama LIKE ? OR te.alamat LIKE ? OR k.nama LIKE ?
");
$totalStmt->execute([$searchParam, $searchParam, $searchParam]);
$total = (int) $totalStmt->fetchColumn();

$paging = pagination($total, $perPage, $page,
    '/GIS_BANJIR/admin/evakuasi.php' . ($search ? '?search=' . urlencode($search) : ''));
$offset = $paging['offset'];

$dataStmt = $db->prepare("
    SELECT te.*, k.nama AS nama_kecamatan
    FROM titik_evakuasi te
    LEFT JOIN kecamatan k ON te.kecamatan_id = k.id
    WHERE te.nama LIKE ? OR te.alamat LIKE ? OR k.nama LIKE ?
    ORDER BY te.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$dataStmt->execute([$searchParam, $searchParam, $searchParam]);
$evakuasiList = $dataStmt->fetchAll();

// Data kecamatan untuk dropdown
$kecamatanList = $db->query("SELECT id, nama FROM kecamatan ORDER BY nama")->fetchAll();

// Flash message
$flashMsg  = getFlashMessage();
$pageTitle = 'Titik Evakuasi';
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
        <h1><i class="fa-solid fa-location-dot me-2 text-success"></i>Titik Evakuasi</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Titik Evakuasi</li>
            </ol>
        </nav>
    </div>
    <button class="btn btn-success d-flex align-items-center gap-2" style="border-radius:9px; font-size:13px"
            data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fa-solid fa-plus"></i> Tambah Titik Evakuasi
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
                <input type="text" name="search" class="form-control" placeholder="Cari nama, alamat, kecamatan..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="border-left:none">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px; font-size:13px">Cari</button>
            <?php if ($search): ?>
            <a href="/GIS_BANJIR/admin/evakuasi.php" class="btn btn-outline-secondary" style="border-radius:8px; font-size:13px">Reset</a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:12px">Total: <?= $total ?> titik</span>
        </form>
    </div>
</div>

<!-- Tabel Data -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($evakuasiList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-location-dot fa-3x mb-3 d-block opacity-50"></i>
                <?= $search ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data titik evakuasi.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Titik Evakuasi">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Nama Posko</th>
                        <th>Jenis</th>
                        <th>Alamat</th>
                        <th>Kapasitas</th>
                        <th>Kecamatan</th>
                        <th>Status</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evakuasiList as $i => $evak): ?>
                    <tr>
                        <td class="text-muted"><?= $paging['offset'] + $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600"><?= htmlspecialchars($evak['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($evak['kontak']): ?>
                            <div style="font-size:11px; color:#64748b">
                                <i class="fa-solid fa-phone fa-xs"></i>
                                <?= htmlspecialchars($evak['kontak'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $jenisLabel = [
                                'sekolah'     => ['🏫','Sekolah'],
                                'masjid'      => ['🕌','Masjid'],
                                'gedung'      => ['🏢','Gedung'],
                                'lapangan'    => ['⛳','Lapangan'],
                                'rumah_sakit' => ['🏥','Rumah Sakit'],
                                'lainnya'     => ['📍','Lainnya'],
                            ];
                            $jLabel = $jenisLabel[$evak['jenis']] ?? ['📍','Lainnya'];
                            ?>
                            <span style="font-size:13px"><?= $jLabel[0] ?> <?= $jLabel[1] ?></span>
                        </td>
                        <td style="max-width:180px">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:13px"
                                 title="<?= htmlspecialchars($evak['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($evak['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td><?= number_format((int)$evak['kapasitas']) ?> orang</td>
                        <td><?= htmlspecialchars($evak['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="admin-badge badge-<?= $evak['status'] ?>">
                                <?= ucfirst($evak['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px; padding:4px 9px"
                                    onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($evak), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)"
                                    title="Edit" aria-label="Edit <?= htmlspecialchars($evak['nama'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-pencil"></i>
                            </button>
                            <form id="hapus-<?= $evak['id'] ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= $evak['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; padding:4px 9px"
                                        onclick="konfirmasiHapus('hapus-<?= $evak['id'] ?>', '<?= htmlspecialchars(addslashes($evak['nama']), ENT_QUOTES, 'UTF-8') ?>')"
                                        title="Hapus" aria-label="Hapus <?= htmlspecialchars($evak['nama'], ENT_QUOTES, 'UTF-8') ?>">
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
            <nav aria-label="Pagination Evakuasi">
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
     MODAL: Tambah Titik Evakuasi
     ============================================================ -->
<div class="modal fade modal-admin-form" id="modalTambah" tabindex="-1" aria-labelledby="lblModalTambah" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalTambah">
                    <i class="fa-solid fa-plus me-2"></i>Tambah Titik Evakuasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" id="formTambah">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="tambah">
                    <?php renderFormEvakuasi('tambah', $kecamatanList); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit Titik Evakuasi
     ============================================================ -->
<div class="modal fade modal-admin-form" id="modalEdit" tabindex="-1" aria-labelledby="lblModalEdit" aria-modal="true" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:16px; border:none">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title" id="lblModalEdit">
                    <i class="fa-solid fa-pencil me-2"></i>Edit Titik Evakuasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" id="formEdit">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <?php renderFormEvakuasi('edit', $kecamatanList); ?>
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

<?php
/**
 * Helper render form evakuasi (inline agar tidak perlu file terpisah).
 *
 * @param string $prefix    'tambah' atau 'edit'
 * @param array  $kecList   Daftar kecamatan dari DB
 */
function renderFormEvakuasi(string $prefix, array $kecList): void
{
    $p = $prefix;
    $jenisOptions = [
        'sekolah'     => '🏫 Sekolah',
        'masjid'      => '🕌 Masjid',
        'gedung'      => '🏢 Gedung',
        'lapangan'    => '⛳ Lapangan',
        'rumah_sakit' => '🏥 Rumah Sakit',
        'lainnya'     => '📍 Lainnya',
    ];
    ?>
    <div class="row g-3">
        <!-- Nama -->
        <div class="col-12">
            <label for="<?= $p ?>Nama" class="form-label">Nama Posko <span class="text-danger">*</span></label>
            <input type="text" id="<?= $p ?>Nama" name="nama" class="form-control"
                   placeholder="Contoh: SDN 060934 Belawan" required maxlength="200">
        </div>
        <!-- Jenis & Status -->
        <div class="col-md-6">
            <label for="<?= $p ?>Jenis" class="form-label">Jenis Fasilitas <span class="text-danger">*</span></label>
            <select id="<?= $p ?>Jenis" name="jenis" class="form-select" required>
                <?php foreach ($jenisOptions as $val => $lbl): ?>
                <option value="<?= $val ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="<?= $p ?>Status" class="form-label">Status</label>
            <select id="<?= $p ?>Status" name="status" class="form-select">
                <option value="aktif">✅ Aktif</option>
                <option value="nonaktif">⭕ Nonaktif</option>
            </select>
        </div>
        <!-- Alamat -->
        <div class="col-12">
            <label for="<?= $p ?>Alamat" class="form-label">Alamat Lengkap</label>
            <textarea id="<?= $p ?>Alamat" name="alamat" class="form-control" rows="2"
                      placeholder="Jl. Veteran No. 1, Belawan I..."></textarea>
        </div>
        <!-- Koordinat -->
        <div class="col-md-6">
            <label for="<?= $p ?>Lat" class="form-label">Latitude <span class="text-danger">*</span></label>
            <input type="number" id="<?= $p ?>Lat" name="latitude" class="form-control"
                   step="any" placeholder="3.595200" required
                   oninput="syncEvakuasiMarker('<?= $p ?>')">
        </div>
        <div class="col-md-6">
            <label for="<?= $p ?>Lng" class="form-label">Longitude <span class="text-danger">*</span></label>
            <input type="number" id="<?= $p ?>Lng" name="longitude" class="form-control"
                   step="any" placeholder="98.672200" required
                   oninput="syncEvakuasiMarker('<?= $p ?>')">
        </div>
        <!-- Map Picker -->
        <div class="col-12">
            <label class="form-label">
                <i class="fa-solid fa-map-pin me-1 text-success"></i>Klik peta untuk memilih koordinat
            </label>
            <!-- Kotak pencarian alamat -->
            <div class="input-group mb-2">
                <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" id="<?= $p ?>CariAlamat"
                       class="form-control"
                       placeholder="Cari alamat, nama sekolah, masjid, gedung..."
                       autocomplete="off"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();cariAlamatEvakuasi('<?= $p ?>');}">
                <button type="button" class="btn btn-outline-success"
                        onclick="cariAlamatEvakuasi('<?= $p ?>')">
                    <i class="fa-solid fa-location-dot me-1"></i> Cari
                </button>
            </div>
            <!-- Dropdown hasil pencarian -->
            <div id="<?= $p ?>HasilCari"
                 class="list-group mb-2"
                 style="display:none;max-height:180px;overflow-y:auto;position:relative;z-index:9999;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12)">
            </div>
            <div id="mapEvakuasi_<?= $p ?>" style="height:220px; border:1px solid #e2e8f0; border-radius:10px; z-index:1"></div>
            <small class="text-muted mt-1 d-block">
                <i class="fa-solid fa-circle-info text-success me-1"></i>
                Cari alamat di atas, atau klik langsung pada peta untuk menentukan koordinat.
            </small>
        </div>
        <!-- Kapasitas & Kontak -->
        <div class="col-md-6">
            <label for="<?= $p ?>Kapasitas" class="form-label">Kapasitas (orang)</label>
            <input type="number" id="<?= $p ?>Kapasitas" name="kapasitas" class="form-control"
                   min="0" value="0">
        </div>
        <div class="col-md-6">
            <label for="<?= $p ?>Kontak" class="form-label">Nomor Kontak</label>
            <input type="text" id="<?= $p ?>Kontak" name="kontak" class="form-control"
                   placeholder="061-XXXXXXX" maxlength="50">
        </div>
        <!-- Kecamatan -->
        <div class="col-md-6">
            <label for="<?= $p ?>KecamatanId" class="form-label">Kecamatan</label>
            <select id="<?= $p ?>KecamatanId" name="kecamatan_id" class="form-select">
                <option value="">-- Pilih Kecamatan --</option>
                <?php foreach ($kecList as $kec): ?>
                <option value="<?= $kec['id'] ?>"><?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Fasilitas -->
        <div class="col-12">
            <label for="<?= $p ?>Fasilitas" class="form-label">Fasilitas Tersedia</label>
            <textarea id="<?= $p ?>Fasilitas" name="fasilitas" class="form-control" rows="2"
                      placeholder="Toilet, air bersih, dapur umum, genset..."></textarea>
        </div>
    </div>
    <?php
}
?>

<script>
// ============================================================
// Peta Leaflet untuk Tambah & Edit Evakuasi
// ============================================================
const evakuasiMaps    = {};
const evakuasiMarkers = {};

/**
 * Inisialisasi atau invalidate peta berdasarkan prefix.
 * @param {string} prefix - 'tambah' atau 'edit'
 */
function initEvakuasiMap(prefix) {
    const containerId = 'mapEvakuasi_' + prefix;
    if (evakuasiMaps[prefix]) {
        setTimeout(() => evakuasiMaps[prefix].invalidateSize(), 150);
        return;
    }

    const map = L.map(containerId).setView([3.5952, 98.6722], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function (e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);
        document.getElementById(prefix + 'Lat').value = lat;
        document.getElementById(prefix + 'Lng').value = lng;
        if (evakuasiMarkers[prefix]) evakuasiMarkers[prefix].remove();
        evakuasiMarkers[prefix] = L.marker(e.latlng).addTo(map)
            .bindPopup('Lokasi dipilih').openPopup();
    });

    evakuasiMaps[prefix] = map;
    setTimeout(() => map.invalidateSize(), 200);
}

// Sinkronisasi input koordinat → perbarui marker peta
function syncEvakuasiMarker(prefix) {
    const lat = parseFloat(document.getElementById(prefix + 'Lat').value);
    const lng = parseFloat(document.getElementById(prefix + 'Lng').value);
    const map = evakuasiMaps[prefix];
    if (!map || isNaN(lat) || isNaN(lng)) return;
    if (evakuasiMarkers[prefix]) evakuasiMarkers[prefix].remove();
    evakuasiMarkers[prefix] = L.marker([lat, lng]).addTo(map);
    map.setView([lat, lng], 14);
}

// Init peta saat modal dibuka
document.getElementById('modalTambah').addEventListener('shown.bs.modal', function () {
    initEvakuasiMap('tambah');
});

document.getElementById('modalEdit').addEventListener('shown.bs.modal', function () {
    initEvakuasiMap('edit');
    // Pindahkan ke koordinat data yang sedang diedit
    const lat = parseFloat(document.getElementById('editLat').value);
    const lng = parseFloat(document.getElementById('editLng').value);
    if (!isNaN(lat) && !isNaN(lng) && evakuasiMaps['edit']) {
        if (evakuasiMarkers['edit']) evakuasiMarkers['edit'].remove();
        evakuasiMarkers['edit'] = L.marker([lat, lng]).addTo(evakuasiMaps['edit']);
        evakuasiMaps['edit'].setView([lat, lng], 14);
    }
});

// ============================================================
// Buka modal edit dan isi data
// ============================================================
function bukaModalEdit(data) {
    document.getElementById('editId').value            = data.id;
    document.getElementById('editNama').value          = data.nama;
    document.getElementById('editJenis').value         = data.jenis;
    document.getElementById('editAlamat').value        = data.alamat    || '';
    document.getElementById('editLat').value           = data.latitude;
    document.getElementById('editLng').value           = data.longitude;
    document.getElementById('editKapasitas').value     = data.kapasitas || 0;
    document.getElementById('editKontak').value        = data.kontak    || '';
    document.getElementById('editKecamatanId').value   = data.kecamatan_id || '';
    document.getElementById('editFasilitas').value     = data.fasilitas || '';
    document.getElementById('editStatus').value        = data.status;

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

// ============================================================
// FUNGSI PENCARIAN ALAMAT (Nominatim OpenStreetMap)
// ============================================================
function cariAlamatEvakuasi(prefix) {
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
                btn.innerHTML = '<i class="fa-solid fa-location-dot text-success me-2"></i>' + item.display_name;
                btn.addEventListener('click', function() {
                    const lat = parseFloat(item.lat).toFixed(6);
                    const lng = parseFloat(item.lon).toFixed(6);

                    // Isi input koordinat
                    document.getElementById(prefix + 'Lat').value = lat;
                    document.getElementById(prefix + 'Lng').value = lng;

                    // Gerakkan peta dan pasang marker
                    const mapObj = evakuasiMaps[prefix];
                    if (mapObj) {
                        mapObj.setView([lat, lng], 16);
                        if (evakuasiMarkers[prefix]) evakuasiMarkers[prefix].remove();
                        evakuasiMarkers[prefix] = L.marker([lat, lng]).addTo(mapObj)
                            .bindPopup('<strong>' + item.display_name.split(',')[0] + '</strong>').openPopup();
                    }

                    // Isi input nama atau alamat jika kosong
                    const namaInput = document.getElementById(prefix + 'Nama');
                    if (namaInput && !namaInput.value) {
                        namaInput.value = item.display_name.split(',')[0];
                    }

                    const alamatInput = document.getElementById(prefix + 'Alamat');
                    if (alamatInput && !alamatInput.value) {
                        alamatInput.value = item.display_name.split(',').slice(0,3).join(',');
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
