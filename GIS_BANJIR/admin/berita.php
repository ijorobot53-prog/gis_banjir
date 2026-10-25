<?php
/**
 * Halaman CRUD Berita / Pengumuman / Tips
 * File: admin/berita.php
 *
 * Fitur:
 * - List berita dengan pagination dan search
 * - Form tambah/edit (full page toggle, bukan modal, agar textarea konten lega)
 * - Auto-generate slug dari judul via JavaScript
 * - Upload gambar sampul dengan validasi
 * - Badge kategori dan status
 * - Hapus dengan konfirmasi SweetAlert2
 * - CSRF semua form
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

    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        setFlashMessage('error', 'Token keamanan tidak valid.');
        header('Location: /GIS_BANJIR/admin/berita.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'tambah' || $action === 'edit') {
        $judul    = trim($_POST['judul'] ?? '');
        $slug     = generateSlug(trim($_POST['slug'] ?? $judul));
        $konten   = trim($_POST['konten'] ?? '');
        $kategori = $_POST['kategori'] ?? 'berita';
        $status   = $_POST['status'] ?? 'published';

        // Validasi wajib
        if (empty($judul) || empty($konten)) {
            setFlashMessage('error', 'Judul dan konten wajib diisi.');
            header('Location: /GIS_BANJIR/admin/berita.php');
            exit;
        }

        // Validasi enum
        if (!in_array($kategori, ['berita','pengumuman','tips'])) $kategori = 'berita';
        if (!in_array($status, ['draft','published'])) $status = 'published';

        // Upload gambar jika ada
        $gambar = null;
        if (!empty($_FILES['gambar']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/berita/';
            $namaFile  = uploadImage($_FILES['gambar'], $uploadDir);
            if ($namaFile === false) {
                setFlashMessage('error', 'Gagal upload gambar. Pastikan format JPG/PNG/WebP dan ukuran maksimal 5MB.');
                header('Location: /GIS_BANJIR/admin/berita.php');
                exit;
            }
            $gambar = $namaFile;
        }

        if ($action === 'tambah') {
            // Pastikan slug unik dengan suffix jika perlu
            $slugBase = $slug;
            $counter  = 1;
            while (true) {
                $cek = $db->prepare("SELECT COUNT(*) FROM berita WHERE slug = ?");
                $cek->execute([$slug]);
                if ((int)$cek->fetchColumn() === 0) break;
                $slug = $slugBase . '-' . $counter++;
            }

            $stmt = $db->prepare("
                INSERT INTO berita (judul, slug, konten, gambar, kategori, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$judul, $slug, $konten, $gambar, $kategori, $status]);
            setFlashMessage('success', "Berita <strong>" . htmlspecialchars($judul) . "</strong> berhasil ditambahkan.");
        } else {
            $id = (int) ($_POST['id'] ?? 0);

            // Ambil data lama untuk mempertahankan gambar jika tidak diupload baru
            $dataLama = $db->prepare("SELECT gambar, slug FROM berita WHERE id = ?");
            $dataLama->execute([$id]);
            $lama = $dataLama->fetch();

            if ($gambar === null) {
                $gambar = $lama['gambar']; // Pertahankan gambar lama
            } else {
                // Hapus gambar lama jika ada dan berbeda
                if (!empty($lama['gambar'])) {
                    $pathLama = __DIR__ . '/../uploads/berita/' . $lama['gambar'];
                    if (file_exists($pathLama)) {
                        @unlink($pathLama);
                    }
                }
            }

            // Pastikan slug unik (kecuali slug milik berita ini sendiri)
            $slugBase = $slug;
            $counter  = 1;
            while (true) {
                $cek = $db->prepare("SELECT COUNT(*) FROM berita WHERE slug = ? AND id != ?");
                $cek->execute([$slug, $id]);
                if ((int)$cek->fetchColumn() === 0) break;
                $slug = $slugBase . '-' . $counter++;
            }

            $stmt = $db->prepare("
                UPDATE berita
                SET judul=?, slug=?, konten=?, gambar=?, kategori=?, status=?
                WHERE id=?
            ");
            $stmt->execute([$judul, $slug, $konten, $gambar, $kategori, $status, $id]);
            setFlashMessage('success', "Berita <strong>" . htmlspecialchars($judul) . "</strong> berhasil diperbarui.");
        }

    } elseif ($action === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            // Hapus file gambar sebelum menghapus record
            $dataHapus = $db->prepare("SELECT gambar FROM berita WHERE id = ?");
            $dataHapus->execute([$id]);
            $brt = $dataHapus->fetch();
            if ($brt && !empty($brt['gambar'])) {
                $pathGambar = __DIR__ . '/../uploads/berita/' . $brt['gambar'];
                if (file_exists($pathGambar)) {
                    @unlink($pathGambar);
                }
            }
            $stmt = $db->prepare("DELETE FROM berita WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Berita berhasil dihapus.');
        }
    }

    header('Location: /GIS_BANJIR/admin/berita.php');
    exit;
}

// ============================================================
// Mode form: tampilkan form atau list
// ============================================================
$mode      = $_GET['mode'] ?? 'list';    // 'list' | 'tambah' | 'edit'
$editId    = (int) ($_GET['id'] ?? 0);
$editData  = null;

if ($mode === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
    if (!$editData) {
        $mode = 'list'; // Data tidak ditemukan, kembali ke list
    }
}

// ============================================================
// Ambil Data List
// ============================================================
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$searchParam = "%{$search}%";

$totalStmt = $db->prepare("SELECT COUNT(*) FROM berita WHERE judul LIKE ? OR kategori LIKE ?");
$totalStmt->execute([$searchParam, $searchParam]);
$total = (int) $totalStmt->fetchColumn();

$paging = pagination($total, $perPage, $page,
    '/GIS_BANJIR/admin/berita.php' . ($search ? '?search=' . urlencode($search) : ''));
$offset = $paging['offset'];

$dataStmt = $db->prepare("
    SELECT * FROM berita
    WHERE judul LIKE ? OR kategori LIKE ?
    ORDER BY created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$dataStmt->execute([$searchParam, $searchParam]);
$beritaList = $dataStmt->fetchAll();

$flashMsg  = getFlashMessage();
$pageTitle = ($mode === 'list') ? 'Berita & Pengumuman' : (($mode === 'tambah') ? 'Tambah Berita' : 'Edit Berita');
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
        <h1><i class="fa-solid fa-newspaper me-2 text-info"></i><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/index.php">Dashboard</a></li>
                <?php if ($mode !== 'list'): ?>
                <li class="breadcrumb-item"><a href="/GIS_BANJIR/admin/berita.php">Berita</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>
    </div>
    <?php if ($mode === 'list'): ?>
    <a href="/GIS_BANJIR/admin/berita.php?mode=tambah" class="btn btn-info text-white d-flex align-items-center gap-2"
       style="border-radius:9px; font-size:13px">
        <i class="fa-solid fa-plus"></i> Tambah Berita
    </a>
    <?php else: ?>
    <a href="/GIS_BANJIR/admin/berita.php" class="btn btn-outline-secondary d-flex align-items-center gap-2"
       style="border-radius:9px; font-size:13px">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke List
    </a>
    <?php endif; ?>
</div>

<?php if ($mode === 'list'): ?>
<!-- ============================================================
     TAMPILAN LIST BERITA
     ============================================================ -->

<!-- Filter & Search -->
<div class="data-card mb-4">
    <div class="data-card-header">
        <form method="GET" class="d-flex gap-2 flex-wrap w-100 align-items-center">
            <div class="input-group" style="max-width:320px">
                <span class="input-group-text bg-white" style="border-color:#e2e8f0">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control" placeholder="Cari judul berita..."
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="border-left:none">
            </div>
            <button type="submit" class="btn btn-primary" style="border-radius:8px; font-size:13px">Cari</button>
            <?php if ($search): ?>
            <a href="/GIS_BANJIR/admin/berita.php" class="btn btn-outline-secondary" style="border-radius:8px; font-size:13px">Reset</a>
            <?php endif; ?>
            <span class="ms-auto text-muted" style="font-size:12px">Total: <?= $total ?> berita</span>
        </form>
    </div>
</div>

<!-- Tabel Berita -->
<div class="data-card">
    <div class="data-card-body">
        <?php if (count($beritaList) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-newspaper fa-3x mb-3 d-block opacity-50"></i>
                <?= $search ? 'Tidak ada berita yang cocok.' : 'Belum ada data berita.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table w-100" aria-label="Tabel Berita">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th style="width:110px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($beritaList as $i => $brt): ?>
                    <tr>
                        <td class="text-muted"><?= $paging['offset'] + $i + 1 ?></td>
                        <td>
                            <div style="font-weight:600; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"
                                 title="<?= htmlspecialchars($brt['judul'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($brt['judul'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="font-size:11px; color:#94a3b8">
                                /<?= htmlspecialchars($brt['slug'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>
                        <td>
                            <span class="admin-badge badge-<?= $brt['kategori'] ?>">
                                <?= ucfirst($brt['kategori']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge badge-<?= $brt['status'] ?>">
                                <?= $brt['status'] === 'published' ? 'Terbit' : 'Draft' ?>
                            </span>
                        </td>
                        <td style="font-size:12px"><?= formatDate($brt['created_at']) ?></td>
                        <td>
                            <a href="/GIS_BANJIR/admin/berita.php?mode=edit&id=<?= $brt['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" style="border-radius:6px; padding:4px 9px"
                               title="Edit" aria-label="Edit <?= htmlspecialchars($brt['judul'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-pencil"></i>
                            </a>
                            <form id="hapus-<?= $brt['id'] ?>" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= $brt['id'] ?>">
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:6px; padding:4px 9px"
                                        onclick="konfirmasiHapus('hapus-<?= $brt['id'] ?>', '<?= htmlspecialchars(addslashes($brt['judul']), ENT_QUOTES, 'UTF-8') ?>')"
                                        title="Hapus" aria-label="Hapus <?= htmlspecialchars($brt['judul'], ENT_QUOTES, 'UTF-8') ?>">
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
            <nav aria-label="Pagination Berita">
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

<?php else: ?>
<!-- ============================================================
     TAMPILAN FORM TAMBAH / EDIT BERITA
     ============================================================ -->
<div class="data-card">
    <form method="POST" enctype="multipart/form-data" id="formBerita">
        <div class="modal-body p-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRF(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'edit' : 'tambah' ?>">
            <?php if ($mode === 'edit'): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <!-- Kolom Kiri: Judul, Slug, Konten -->
                <div class="col-lg-8">
                    <!-- Judul -->
                    <div class="mb-3">
                        <label for="judulInput" class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" id="judulInput" name="judul" class="form-control"
                               placeholder="Masukkan judul berita..." required maxlength="255"
                               value="<?= htmlspecialchars($editData['judul'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               oninput="autoSlug()">
                    </div>

                    <!-- Slug -->
                    <div class="mb-3">
                        <label for="slugInput" class="form-label">
                            Slug URL
                            <small class="text-muted">(otomatis dari judul, bisa diubah manual)</small>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text text-muted" style="font-size:12px">/berita/</span>
                            <input type="text" id="slugInput" name="slug" class="form-control"
                                   placeholder="contoh-slug-berita" maxlength="255"
                                   value="<?= htmlspecialchars($editData['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <!-- Konten -->
                    <div class="mb-3">
                        <label for="kontenInput" class="form-label">Konten <span class="text-danger">*</span></label>
                        <textarea id="kontenInput" name="konten" class="form-control" rows="14"
                                  placeholder="Tulis isi berita / pengumuman di sini..." required
                                  style="font-size:14px; line-height:1.7"><?= htmlspecialchars($editData['konten'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <small class="text-muted">Mendukung HTML dasar untuk format teks.</small>
                    </div>
                </div>

                <!-- Kolom Kanan: Meta, Gambar, Publish -->
                <div class="col-lg-4">
                    <!-- Kategori -->
                    <div class="mb-3">
                        <label for="kategoriInput" class="form-label">Kategori</label>
                        <select id="kategoriInput" name="kategori" class="form-select">
                            <option value="berita"      <?= ($editData['kategori'] ?? '') === 'berita'      ? 'selected' : '' ?>>📰 Berita</option>
                            <option value="pengumuman"  <?= ($editData['kategori'] ?? '') === 'pengumuman'  ? 'selected' : '' ?>>📢 Pengumuman</option>
                            <option value="tips"        <?= ($editData['kategori'] ?? '') === 'tips'        ? 'selected' : '' ?>>💡 Tips</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="statusInput" class="form-label">Status Publikasi</label>
                        <select id="statusInput" name="status" class="form-select">
                            <option value="published" <?= ($editData['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>✅ Terbit</option>
                            <option value="draft"     <?= ($editData['status'] ?? '') === 'draft'              ? 'selected' : '' ?>>📝 Draft</option>
                        </select>
                    </div>

                    <!-- Upload Gambar -->
                    <div class="mb-3">
                        <label for="gambarInput" class="form-label">Gambar Sampul</label>
                        <?php if (!empty($editData['gambar'])): ?>
                        <div class="mb-2">
                            <img src="/GIS_BANJIR/uploads/berita/<?= htmlspecialchars($editData['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Gambar saat ini"
                                 style="width:100%; height:120px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0">
                            <small class="text-muted d-block mt-1">Upload baru untuk mengganti.</small>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="gambarInput" name="gambar" class="form-control"
                               accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">JPG/PNG/WebP, maks 5MB.</small>
                        <!-- Preview gambar sebelum upload -->
                        <div id="previewGambarWrap" class="mt-2 d-none">
                            <img id="previewGambar" src="" alt="Preview"
                                 style="width:100%; height:120px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0">
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-info text-white" style="border-radius:9px">
                            <i class="fa-solid fa-save me-2"></i>
                            <?= $mode === 'edit' ? 'Perbarui Berita' : 'Simpan Berita' ?>
                        </button>
                        <a href="/GIS_BANJIR/admin/berita.php" class="btn btn-outline-secondary" style="border-radius:9px">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<script>
/**
 * Generate slug URL dari judul berita.
 * Konversi: lowercase, spasi → tanda hubung, hapus karakter non-alphanumeric.
 */
function autoSlug() {
    const judul    = document.getElementById('judulInput').value;
    const slugEl   = document.getElementById('slugInput');
    if (!slugEl) return;
    const slug = judul
        .toLowerCase()
        .replace(/[àáâãäå]/g, 'a').replace(/[èéêë]/g, 'e')
        .replace(/[ìíîï]/g, 'i').replace(/[òóôõö]/g, 'o')
        .replace(/[ùúûü]/g, 'u')
        .replace(/[^a-z0-9\s-]/g, '')   // Hapus karakter spesial
        .replace(/\s+/g, '-')            // Spasi → tanda hubung
        .replace(/-+/g, '-')             // Hapus tanda hubung ganda
        .replace(/^-+|-+$/g, '');        // Trim tanda hubung di tepi
    slugEl.value = slug;
}

// Preview gambar sebelum upload
const gambarInput = document.getElementById('gambarInput');
if (gambarInput) {
    gambarInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('previewGambar').src = e.target.result;
            document.getElementById('previewGambarWrap').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
