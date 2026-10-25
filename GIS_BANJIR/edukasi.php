<?php
/**
 * Halaman Edukasi & Informasi Banjir
 * File: edukasi.php
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Edukasi & Informasi Banjir';

$beritaList = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, judul, konten, gambar, kategori, created_at FROM berita WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
    $stmt->execute();
    $beritaList = $stmt->fetchAll();
} catch (PDOException $e) {
    $beritaList = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="py-4 bg-success text-white">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0 small opacity-75">
                <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item active text-white">Edukasi</li>
            </ol>
        </nav>
        <h1 class="display-6 fw-bold mb-1"><i class="fa-solid fa-book-open me-2"></i>Edukasi &amp; Informasi</h1>
        <p class="lead mb-0 opacity-90">Panduan kesiapsiagaan banjir, nomor darurat, berita terkini, dan tanya jawab.</p>
    </div>
</section>

<!-- Nav Tab Section -->
<section class="py-2 bg-body-secondary border-bottom sticky-top" style="top:56px;z-index:100;">
    <div class="container">
        <ul class="nav nav-pills gap-2" id="edukasiTab">
            <li class="nav-item"><a class="nav-link active" href="#tips">Tips Kesiapsiagaan</a></li>
            <li class="nav-item"><a class="nav-link" href="#darurat">Nomor Darurat</a></li>
            <li class="nav-item"><a class="nav-link" href="#berita">Berita</a></li>
            <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        </ul>
    </div>
</section>

<!-- ================================================================
     SECTION 1: TIPS KESIAPSIAGAAN
     ================================================================ -->
<section class="py-5" id="tips">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Tips Menghadapi Banjir</h2>
            <p class="text-muted">Tindakan tepat sebelum, saat, dan sesudah banjir dapat menyelamatkan jiwa.</p>
        </div>
        <div class="row g-4">
            <!-- Sebelum Banjir -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 border-top border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fa-solid fa-calendar-check text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Sebelum Banjir</h5>
                                <small class="text-muted">Persiapan &amp; pencegahan</small>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Kenali tanda banjir: hujan lebat &gt;1 jam, air sungai naik cepat, peringatan BPBD.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Siapkan tas darurat: obat, dokumen penting, air minum, makanan untuk 3 hari.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Simpan barang berharga di tempat tinggi dan kedap air.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Catat nomor darurat: BPBD (117), Basarnas (115), PMI, keluarga.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Bersihkan saluran air di sekitar rumah secara rutin.</span></li>
                            <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-primary mt-1 flex-shrink-0"></i><span>Diskusikan rencana evakuasi keluarga dan titik kumpul.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Saat Banjir -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 border-top border-4 border-danger">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="fa-solid fa-person-running text-danger fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Saat Banjir</h5>
                                <small class="text-muted">Tindakan darurat</small>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Segera matikan listrik dari panel utama jika air mulai masuk.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Evakuasi lansia, anak-anak, dan penyandang disabilitas terlebih dahulu.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Jangan menerobos arus banjir — 15 cm sudah bisa menghanyutkan orang.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Gunakan air minum kemasan atau air rebus, jangan air banjir.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Ikuti arahan petugas BPBD dan jangan kembali sebelum dinyatakan aman.</span></li>
                            <li class="d-flex gap-2"><i class="fa-solid fa-circle-exclamation text-danger mt-1 flex-shrink-0"></i><span>Hubungi BPBD (117) atau Basarnas (115) jika ada warga terjebak.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Setelah Banjir -->
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fa-solid fa-house-flag text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Setelah Banjir</h5>
                                <small class="text-muted">Pemulihan &amp; kebersihan</small>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Periksa kerusakan struktural rumah sebelum masuk kembali.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Bersihkan dan disinfeksi lantai, dinding, perabotan dengan larutan kaporit.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Cuci tangan dengan sabun dan hindari kontak dengan air banjir.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Buang makanan yang terkena air banjir karena sudah terkontaminasi.</span></li>
                            <li class="d-flex gap-2 mb-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Waspada penyakit: diare, leptospirosis, DBD, infeksi kulit.</span></li>
                            <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-success mt-1 flex-shrink-0"></i><span>Laporkan kerusakan infrastruktur kepada kelurahan atau BPBD.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     SECTION 2: NOMOR DARURAT
     ================================================================ -->
<section class="py-5 bg-body-secondary" id="darurat">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Nomor &amp; Kontak Darurat</h2>
            <p class="text-muted">Simpan nomor berikut di ponsel Anda. Hubungi segera jika terjadi keadaan darurat.</p>
        </div>
        <div class="col-lg-8 mx-auto px-0">

            <!-- Dipakai div biasa bukan accordion, agar tidak bergantung JS untuk tampil -->
            <div class="row g-3">

                <!-- BPBD -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="badge bg-danger fs-5 px-3 py-2">117</span>
                                <div>
                                    <h5 class="mb-0 fw-bold">BPBD Kota Medan</h5>
                                    <small class="text-muted">Badan Penanggulangan Bencana Daerah</small>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-phone text-danger me-2"></i>Hotline:</strong>
                                        <a href="tel:117" class="text-danger fw-bold">117</a> (bebas pulsa) &bull;
                                        <a href="tel:06145012345">(061) 450-12345</a>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-location-dot text-danger me-2"></i>Alamat:</strong>
                                        Jl. Kapten Maulana Lubis No. 2, Medan Petisah
                                    </p>
                                </div>
                                <div class="col-12">
                                    <p class="mb-0 text-muted small">Koordinasi penanggulangan bencana, evakuasi korban, distribusi logistik, pendirian posko darurat, dan pemulihan pascabencana.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basarnas -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="badge bg-warning text-dark fs-5 px-3 py-2">115</span>
                                <div>
                                    <h5 class="mb-0 fw-bold">Basarnas (SAR Nasional)</h5>
                                    <small class="text-muted">Badan Nasional Pencarian dan Pertolongan</small>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-phone text-warning me-2"></i>Hotline:</strong>
                                        <a href="tel:115" class="text-warning fw-bold">115</a> (aktif 24 jam)
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-location-dot text-warning me-2"></i>Kantor SAR Medan:</strong>
                                        Jl. Medan Belawan KM 18, Medan Labuhan
                                    </p>
                                </div>
                                <div class="col-12">
                                    <p class="mb-0 text-muted small">Operasi pencarian dan pertolongan korban bencana di darat dan perairan, termasuk korban banjir yang terisolasi.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PMI -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="badge bg-danger fs-5 px-3 py-2">PMI</span>
                                <div>
                                    <h5 class="mb-0 fw-bold">PMI Kota Medan</h5>
                                    <small class="text-muted">Palang Merah Indonesia Cabang Medan</small>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-phone text-danger me-2"></i>Telepon:</strong>
                                        <a href="tel:0614512049">(061) 451-2049</a>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong><i class="fa-solid fa-location-dot text-danger me-2"></i>Alamat:</strong>
                                        Jl. Diponegoro No. 50, Medan
                                    </p>
                                </div>
                                <div class="col-12">
                                    <p class="mb-0 text-muted small">Bantuan medis darurat, distribusi logistik, kebutuhan pokok, dan dukungan psikososial bagi korban bencana.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Polisi, Damkar, RS dalam 3 kolom -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary px-2 py-1">110</span>
                                <h6 class="mb-0 fw-bold">Polisi</h6>
                            </div>
                            <p class="small text-muted mb-2">Polresta Medan</p>
                            <p class="mb-1"><a href="tel:110" class="text-primary fw-bold">110</a></p>
                            <p class="mb-0"><a href="tel:0614523333" class="small">(061) 452-3333</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger px-2 py-1">113</span>
                                <h6 class="mb-0 fw-bold">Damkar</h6>
                            </div>
                            <p class="small text-muted mb-2">Dinas Pemadam Kebakaran Medan</p>
                            <p class="mb-1"><a href="tel:113" class="text-danger fw-bold">113</a></p>
                            <p class="mb-0"><a href="tel:0614512345" class="small">(061) 451-2345</a></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-info text-dark px-2 py-1"><i class="fa-solid fa-hospital"></i></span>
                                <h6 class="mb-0 fw-bold">Rumah Sakit</h6>
                            </div>
                            <p class="small mb-1"><strong>RSUP Adam Malik:</strong> <a href="tel:0618360381">(061) 836-0381</a></p>
                            <p class="small mb-1"><strong>RS Pirngadi:</strong> <a href="tel:0614158701">(061) 415-8701</a></p>
                            <p class="small mb-0"><strong>RSU Haji:</strong> <a href="tel:0616850392">(061) 685-0392</a></p>
                        </div>
                    </div>
                </div>

            </div><!-- /.row kontak -->
        </div>
    </div>
</section>

<!-- ================================================================
     SECTION 3: BERITA & PENGUMUMAN
     ================================================================ -->
<section class="py-5" id="berita">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Berita &amp; Pengumuman</h2>
            <p class="text-muted">Update terbaru seputar kondisi banjir, pengumuman BPBD, dan informasi penting lainnya.</p>
        </div>

        <?php if (empty($beritaList)): ?>
            <div class="text-center py-4">
                <i class="fa-solid fa-newspaper fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">Belum ada berita yang dipublikasikan.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($beritaList as $i => $berita):
                    $katClass = match($berita['kategori']) {
                        'pengumuman' => 'bg-warning text-dark',
                        'tips'       => 'bg-success text-white',
                        default      => 'bg-primary text-white'
                    };
                    $katLabel = match($berita['kategori']) {
                        'pengumuman' => 'Pengumuman',
                        'tips'       => 'Tips',
                        default      => 'Berita'
                    };
                    $konten  = $berita['konten'] ?? '';
                    $preview = mb_strlen($konten) > 180
                        ? htmlspecialchars(mb_substr($konten, 0, 180), ENT_QUOTES, 'UTF-8') . '…'
                        : nl2br(htmlspecialchars($konten, ENT_QUOTES, 'UTF-8'));
                    $gambarUrl = !empty($berita['gambar'])
                        ? 'uploads/' . htmlspecialchars($berita['gambar'], ENT_QUOTES, 'UTF-8')
                        : '';
                    $kontenFull = htmlspecialchars($berita['konten'] ?? '', ENT_QUOTES, 'UTF-8');
                    $judulEsc   = htmlspecialchars($berita['judul'], ENT_QUOTES, 'UTF-8');
                    $tglEsc     = htmlspecialchars(formatDate($berita['created_at']), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if ($gambarUrl): ?>
                            <img src="<?= $gambarUrl ?>" class="card-img-top"
                                 style="height:180px;object-fit:cover;"
                                 alt="<?= $judulEsc ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:180px;">
                                <i class="fa-solid fa-newspaper fa-3x text-muted opacity-25"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <span class="badge <?= $katClass ?> mb-2"><?= $katLabel ?></span>
                            <h6 class="card-title fw-bold"><?= $judulEsc ?></h6>
                            <p class="card-text text-muted small"><?= $preview ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fa-regular fa-calendar me-1"></i><?= $tglEsc ?>
                            </small>
                            <!-- Tombol pakai onclick pure JS, tidak bergantung Bootstrap JS -->
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="bukaBerita(<?= $i ?>)">
                                Baca Selengkapnya <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Overlay modal custom (pure CSS + JS, tanpa Bootstrap modal) -->
            <div id="beritaOverlay" onclick="tutupBerita(event)"
                 style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;overflow-y:auto;padding:2rem 1rem;">
                <div id="beritaBox"
                     style="background:#fff;max-width:720px;margin:0 auto;border-radius:12px;overflow:hidden;position:relative;"
                     onclick="event.stopPropagation()">
                    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #dee2e6;display:flex;align-items:center;gap:.75rem;">
                        <span id="beritaBadge" class="badge"></span>
                        <h5 id="beritaJudul" style="margin:0;font-weight:700;flex:1;font-size:1rem;"></h5>
                        <button onclick="tutupBerita()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;line-height:1;">&times;</button>
                    </div>
                    <div style="padding:1.5rem;max-height:70vh;overflow-y:auto;">
                        <img id="beritaGambar" src="" alt="" style="display:none;width:100%;max-height:260px;object-fit:cover;border-radius:8px;margin-bottom:1rem;">
                        <p id="beritaTgl" class="text-muted small mb-3"></p>
                        <div id="beritaKonten" style="line-height:1.8;white-space:pre-wrap;"></div>
                    </div>
                    <div style="padding:1rem 1.5rem;border-top:1px solid #dee2e6;text-align:right;">
                        <button onclick="tutupBerita()" class="btn btn-secondary btn-sm">Tutup</button>
                    </div>
                </div>
            </div>

            <!-- Data berita disimpan sebagai JSON untuk dibaca JS -->
            <script>
            var beritaData = <?php
                $jsData = [];
                foreach ($beritaList as $b) {
                    $jsData[] = [
                        'judul'    => $b['judul'],
                        'kategori' => $b['kategori'],
                        'konten'   => $b['konten'] ?? '',
                        'gambar'   => !empty($b['gambar']) ? 'uploads/' . $b['gambar'] : '',
                        'tanggal'  => formatDate($b['created_at']),
                    ];
                }
                echo json_encode($jsData, JSON_UNESCAPED_UNICODE);
            ?>;

            var katMap = {
                'pengumuman': { label: 'Pengumuman', cls: 'bg-warning text-dark' },
                'tips':       { label: 'Tips',        cls: 'bg-success' },
                'berita':     { label: 'Berita',      cls: 'bg-primary' }
            };

            function bukaBerita(idx) {
                var b    = beritaData[idx];
                var kat  = katMap[b.kategori] || katMap['berita'];
                var overlay = document.getElementById('beritaOverlay');
                var box     = document.getElementById('beritaBox');

                document.getElementById('beritaBadge').textContent  = kat.label;
                document.getElementById('beritaBadge').className     = 'badge ' + kat.cls;
                document.getElementById('beritaJudul').textContent   = b.judul;
                document.getElementById('beritaTgl').innerHTML        = '<i class="fa-regular fa-calendar me-1"></i>' + b.tanggal;
                document.getElementById('beritaKonten').textContent  = b.konten;

                var img = document.getElementById('beritaGambar');
                if (b.gambar) {
                    img.src   = b.gambar;
                    img.alt   = b.judul;
                    img.style.display = 'block';
                } else {
                    img.style.display = 'none';
                }

                // Sesuaikan warna box dengan dark mode
                var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                box.style.background  = isDark ? '#2b3035' : '#fff';
                box.style.color       = isDark ? '#dee2e6' : '#212529';

                overlay.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            function tutupBerita(e) {
                if (e && e.target !== document.getElementById('beritaOverlay') && e.type === 'click') return;
                document.getElementById('beritaOverlay').style.display = 'none';
                document.body.style.overflow = '';
            }

            // Tutup dengan tombol Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') tutupBerita();
            });
            </script>

        <?php endif; ?>
    </div>
</section>

<!-- ================================================================
     SECTION 4: FAQ
     ================================================================ -->
<section class="py-5 bg-body-secondary" id="faq">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="fw-bold">Tanya Jawab Seputar Banjir</h2>
            <p class="text-muted">Pertanyaan umum masyarakat seputar banjir di Kota Medan.</p>
        </div>
        <div class="col-lg-9 mx-auto px-0">
            <?php
            $faqList = [
                ['q'=>'Kecamatan mana di Medan yang paling sering banjir?',
                 'a'=>'Berdasarkan data historis, kecamatan paling rawan banjir di Kota Medan antara lain Belawan, Labuhan, Deli, Marelan, dan Helvetia. Wilayah ini umumnya berada di dataran rendah dekat sungai besar (Sungai Deli, Babura, Belawan) yang sering meluap saat hujan intensitas tinggi.'],
                ['q'=>'Kapan musim paling rawan banjir di Medan?',
                 'a'=>'Banjir di Medan dapat terjadi sepanjang tahun, puncaknya pada Oktober–Desember dan Maret–Mei (musim hujan). Hujan >50 mm dalam 2 jam sudah cukup memicu genangan di kawasan rawan.'],
                ['q'=>'Bagaimana cara mengetahui apakah daerah saya rawan banjir?',
                 'a'=>'Buka menu "Peta Rawan" di website ini. Peta menampilkan lokasi rawan dengan kode warna: merah (tinggi), kuning (sedang), hijau (rendah). Bisa juga hubungi kantor kelurahan atau BPBD Kota Medan.'],
                ['q'=>'Apa yang dilakukan jika banjir datang malam hari?',
                 'a'=>'Bangunkan semua anggota keluarga. Matikan listrik dari panel utama, amankan dokumen penting ke tempat tinggi, evakuasi ke lantai atas atau tempat aman. Hubungi BPBD (117) atau Basarnas (115) jika perlu pertolongan. Jangan menerobos arus banjir dalam gelap.'],
                ['q'=>'Apakah ada sistem peringatan dini banjir di Medan?',
                 'a'=>'BPBD Kota Medan bekerja sama dengan BMKG mengoperasikan sistem peringatan dini via sirine, SMS broadcast, dan media sosial resmi. Ikuti akun BPBD Kota Medan dan aktifkan notifikasi aplikasi BMKG untuk peringatan cuaca ekstrem real-time.'],
                ['q'=>'Penyakit apa yang perlu diwaspadai setelah banjir?',
                 'a'=>'Waspadai: diare (air terkontaminasi), leptospirosis (kontak urin hewan pengerat), DBD (nyamuk di genangan), infeksi kulit (luka terkena air kotor), dan ISPA. Segera ke puskesmas atau rumah sakit jika mengalami gejala.'],
                ['q'=>'Bagaimana cara melaporkan titik banjir baru?',
                 'a'=>'Bisa melalui: (1) Menu "Lapor Banjir" di website ini, (2) Hotline BPBD (061) 450-12345, (3) Posko kelurahan setempat, atau (4) Langsung ke kantor BPBD. Setiap laporan diverifikasi petugas sebelum dipublikasikan.'],
                ['q'=>'Apakah ada bantuan pemerintah untuk korban banjir?',
                 'a'=>'Ya, BPBD Kota Medan menyediakan: tempat pengungsian, logistik (makanan, air, selimut), layanan medis darurat, dan bantuan rehabilitasi rumah bagi warga terdampak parah. Daftarkan diri melalui kelurahan untuk asesmen dan bantuan.'],
            ];
            foreach ($faqList as $idx => $faq):
            ?>
            <div class="card mb-3 border shadow-sm">
                <div class="card-header bg-transparent d-flex align-items-center gap-2 py-3"
                     style="cursor:pointer;"
                     onclick="toggleFaq('faq-ans-<?= $idx ?>', this)">
                    <i class="fa-solid fa-circle-question text-warning flex-shrink-0"></i>
                    <span class="fw-semibold flex-grow-1"><?= htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="fa-solid fa-chevron-down text-muted faq-chevron" id="faq-icon-<?= $idx ?>"></i>
                </div>
                <div class="card-body <?= $idx === 0 ? '' : 'd-none' ?> text-muted" id="faq-ans-<?= $idx ?>">
                    <?= htmlspecialchars($faq['a'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
/* Toggle FAQ tanpa Bootstrap accordion — pure vanilla JS */
function toggleFaq(answerId, headerEl) {
    var answerEl = document.getElementById(answerId);
    var iconEl   = headerEl.querySelector('.faq-chevron');
    if (!answerEl) return;

    var isHidden = answerEl.classList.contains('d-none');
    answerEl.classList.toggle('d-none', !isHidden);
    if (iconEl) {
        iconEl.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
        iconEl.style.transition = 'transform 0.2s';
    }
}
/* Smooth scroll untuk nav pills */
document.querySelectorAll('#edukasiTab a').forEach(function(a) {
    a.addEventListener('click', function(e) {
        e.preventDefault();
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        document.querySelectorAll('#edukasiTab a').forEach(function(x){ x.classList.remove('active'); });
        this.classList.add('active');
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
