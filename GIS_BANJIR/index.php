<?php
/**
 * Halaman Beranda / Landing Page
 * File: index.php
 *
 * Menampilkan hero section, statistik counter, kejadian banjir terbaru,
 * berita/pengumuman terbaru, dan CTA untuk lapor banjir.
 */

// Koneksi database dan fungsi helper
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Inisialisasi variabel untuk statistik
$statKecamatan = 0;
$statTitikBanjir = 0;
$statPoskoEvakuasi = 0;
$statKorbanTerdampak = 0;

// Query statistik kejadian banjir terbaru
$kejadianTerbaru = [];

// Query berita terbaru
$beritaTerbaru = [];

try {
    $pdo = getDB();

    // ══════════════════════════════════════════════════
    // 1. STATISTIK: Jumlah kecamatan rawan
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT kecamatan_id) AS total
        FROM lokasi_rawan
        WHERE status = 'aktif' AND kecamatan_id IS NOT NULL
    ");
    $row = $stmt->fetch();
    $statKecamatan = (int)($row['total'] ?? 0);

    // ══════════════════════════════════════════════════
    // 2. STATISTIK: Jumlah titik rawan banjir aktif
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM lokasi_rawan
        WHERE status = 'aktif'
    ");
    $row = $stmt->fetch();
    $statTitikBanjir = (int)($row['total'] ?? 0);

    // ══════════════════════════════════════════════════
    // 3. STATISTIK: Jumlah posko evakuasi aktif
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM titik_evakuasi
        WHERE status = 'aktif'
    ");
    $row = $stmt->fetch();
    $statPoskoEvakuasi = (int)($row['total'] ?? 0);

    // ══════════════════════════════════════════════════
    // 4. STATISTIK: Total korban terdampak (SUM)
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT SUM(korban_terdampak) AS total
        FROM kejadian_banjir
    ");
    $row = $stmt->fetch();
    $statKorbanTerdampak = (int)($row['total'] ?? 0);

    // ══════════════════════════════════════════════════
    // 5. KEJADIAN BANJIR TERBARU (3 terbaru)
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT
            kb.id,
            kb.tanggal,
            kb.ketinggian_cm,
            kb.durasi_jam,
            kb.korban_terdampak,
            kb.keterangan,
            lr.nama AS nama_lokasi,
            lr.tingkat,
            k.nama AS nama_kecamatan
        FROM kejadian_banjir kb
        LEFT JOIN lokasi_rawan lr ON lr.id = kb.lokasi_id
        LEFT JOIN kecamatan k ON k.id = lr.kecamatan_id
        ORDER BY kb.tanggal DESC, kb.created_at DESC
        LIMIT 3
    ");
    $kejadianTerbaru = $stmt->fetchAll();

    // ══════════════════════════════════════════════════
    // 6. BERITA / PENGUMUMAN TERBARU (3 terbaru)
    // ══════════════════════════════════════════════════
    $stmt = $pdo->query("
        SELECT
            id,
            judul,
            slug,
            SUBSTRING(konten, 1, 150) AS konten_preview,
            gambar,
            kategori,
            created_at
        FROM berita
        WHERE status = 'published'
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $beritaTerbaru = $stmt->fetchAll();

} catch (PDOException $e) {
    // Tangani error query
    error_log('Database error di index.php: ' . $e->getMessage());
}

// Set page title
$pageTitle = 'Beranda';

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- ================================================================
     HERO SECTION
     ================================================================ -->
<section class="hero-section">
    <div class="container position-relative" data-aos="fade-up">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-8 text-center text-md-start">

                <!-- Badge -->
                <div class="hero-badge" data-aos="fade-up" data-aos-delay="100">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    BPBD Kota Medan
                </div>

                <!-- Judul Utama -->
                <h1 class="mb-3" data-aos="fade-up" data-aos-delay="200">
                    SIG Rawan Banjir<br>
                    <span class="text-gradient-blue">Kota Medan</span>
                </h1>

                <!-- Subjudul -->
                <p class="lead mb-4" data-aos="fade-up" data-aos-delay="300">
                    Sistem Informasi Geografis untuk pemetaan kawasan rawan banjir,
                    jalur evakuasi, dan titik aman bagi warga Kota Medan.
                </p>

                <!-- Tombol CTA -->
                <div class="d-flex gap-2 flex-wrap justify-content-center justify-content-md-start"
                     data-aos="fade-up" data-aos-delay="400">
                    <a href="peta.php" class="btn btn-light btn-lg px-4">
                        <i class="fa-solid fa-map-location-dot me-2"></i>
                        Lihat Peta Rawan
                    </a>
                    <a href="evakuasi.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="fa-solid fa-route me-2"></i>
                        Jalur Evakuasi
                    </a>
                </div>

            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container -->

    <!-- Ikon Dekoratif Melayang -->
    <div class="hero-floating-icon">
        <i class="fa-solid fa-water"></i>
    </div>
</section>

<!-- ================================================================
     STATISTIK COUNTER SECTION
     ================================================================ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">

            <!-- Stat 1: Kecamatan Rawan -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="0">
                <div class="stat-card stat-kecamatan">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-map"></i>
                    </div>
                    <span class="stat-number counter-animate" data-target="<?= htmlspecialchars((string)$statKecamatan, ENT_QUOTES, 'UTF-8') ?>">
                        0
                    </span>
                    <div class="stat-label">Kecamatan Rawan</div>
                </div>
            </div>

            <!-- Stat 2: Titik Banjir -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card stat-titik">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <span class="stat-number counter-animate" data-target="<?= htmlspecialchars((string)$statTitikBanjir, ENT_QUOTES, 'UTF-8') ?>">
                        0
                    </span>
                    <div class="stat-label">Titik Banjir</div>
                </div>
            </div>

            <!-- Stat 3: Posko Evakuasi -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card stat-posko">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-house-flag"></i>
                    </div>
                    <span class="stat-number counter-animate" data-target="<?= htmlspecialchars((string)$statPoskoEvakuasi, ENT_QUOTES, 'UTF-8') ?>">
                        0
                    </span>
                    <div class="stat-label">Posko Evakuasi</div>
                </div>
            </div>

            <!-- Stat 4: Total Korban Terdampak -->
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card stat-korban">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <span class="stat-number counter-animate" data-target="<?= htmlspecialchars((string)$statKorbanTerdampak, ENT_QUOTES, 'UTF-8') ?>">
                        0
                    </span>
                    <div class="stat-label">Total Korban Terdampak</div>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</section>

<!-- ================================================================
     KEJADIAN BANJIR TERBARU
     ================================================================ -->
<section class="py-5 bg-body-secondary">
    <div class="container">
        
        <!-- Heading -->
        <div class="section-heading" data-aos="fade-up">
            <div class="section-label">Pemantauan</div>
            <h2>Kejadian Banjir Terbaru</h2>
            <p>Update terkini lokasi banjir yang terjadi di Kota Medan</p>
        </div>

        <!-- Cards -->
        <div class="row g-4">
            <?php if (!empty($kejadianTerbaru)): ?>
                <?php foreach ($kejadianTerbaru as $index => $kejadian): ?>
                    <?php
                        $tingkat = htmlspecialchars($kejadian['tingkat'] ?? 'rendah', ENT_QUOTES, 'UTF-8');
                        $badgeClass = 'badge-tingkat-' . $tingkat;
                        $delay = $index * 100;
                    ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="kejadian-card tingkat-<?= $tingkat ?>">
                            <div class="card-header-band"></div>
                            <div class="card-body">
                                
                                <!-- Badge Tingkat -->
                                <span class="badge <?= $badgeClass ?> mb-2">
                                    <?= ucfirst($tingkat) ?>
                                </span>

                                <!-- Nama Lokasi -->
                                <h5 class="card-title mb-2">
                                    <?= htmlspecialchars($kejadian['nama_lokasi'] ?? 'Lokasi Tidak Diketahui', ENT_QUOTES, 'UTF-8') ?>
                                </h5>

                                <!-- Kecamatan -->
                                <p class="text-muted small mb-2">
                                    <i class="fa-solid fa-map-pin me-1"></i>
                                    <?= htmlspecialchars($kejadian['nama_kecamatan'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </p>

                                <hr class="my-2">

                                <!-- Info Kejadian -->
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        <?= htmlspecialchars(formatDate($kejadian['tanggal'], 'd F Y'), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                </div>

                                <div class="d-flex gap-3">
                                    <div>
                                        <small class="text-muted d-block">Ketinggian Air</small>
                                        <strong class="text-danger">
                                            <?= htmlspecialchars((string)($kejadian['ketinggian_cm'] ?? 0), ENT_QUOTES, 'UTF-8') ?> cm
                                        </strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Terdampak</small>
                                        <strong class="text-warning">
                                            <?= htmlspecialchars(number_format($kejadian['korban_terdampak'] ?? 0, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?> orang
                                        </strong>
                                    </div>
                                </div>

                            </div><!-- /.card-body -->
                        </div><!-- /.kejadian-card -->
                    </div><!-- /.col -->
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Belum ada data kejadian banjir terbaru.
                    </div>
                </div>
            <?php endif; ?>
        </div><!-- /.row -->

        <!-- Link Lihat Semua -->
        <div class="text-center mt-4" data-aos="fade-up">
            <a href="statistik.php" class="btn btn-outline-primary">
                <i class="fa-solid fa-chart-bar me-2"></i>
                Lihat Statistik Lengkap
            </a>
        </div>

    </div><!-- /.container -->
</section>

<!-- ================================================================
     BERITA / PENGUMUMAN TERBARU
     ================================================================ -->
<section class="py-5">
    <div class="container">

        <!-- Heading -->
        <div class="section-heading" data-aos="fade-up">
            <div class="section-label">Informasi</div>
            <h2>Berita &amp; Pengumuman</h2>
            <p>Berita terkini dan informasi penting dari BPBD Kota Medan</p>
        </div>

        <!-- Cards -->
        <div class="row g-4">
            <?php if (!empty($beritaTerbaru)): ?>
                <?php foreach ($beritaTerbaru as $index => $berita): ?>
                    <?php
                        $kategori = htmlspecialchars($berita['kategori'] ?? 'berita', ENT_QUOTES, 'UTF-8');
                        $badgeKategori = 'badge-kategori-' . $kategori;
                        $delay = $index * 100;
                    ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                        <div class="berita-card">
                            
                            <!-- Gambar placeholder atau gambar real -->
                            <?php if (!empty($berita['gambar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($berita['gambar'], ENT_QUOTES, 'UTF-8') ?>"
                                     class="card-img-top"
                                     alt="<?= htmlspecialchars($berita['judul'], ENT_QUOTES, 'UTF-8') ?>"
                                     style="height: 180px; object-fit: cover;">
                            <?php else: ?>
                                <div class="berita-img-placeholder">
                                    <i class="fa-solid fa-newspaper"></i>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                
                                <!-- Badge Kategori -->
                                <span class="badge <?= $badgeKategori ?> mb-2">
                                    <?= ucfirst($kategori) ?>
                                </span>

                                <!-- Judul -->
                                <h5 class="card-title mb-2">
                                    <?= htmlspecialchars($berita['judul'], ENT_QUOTES, 'UTF-8') ?>
                                </h5>

                                <!-- Preview Konten -->
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($berita['konten_preview'], ENT_QUOTES, 'UTF-8') ?>...
                                </p>

                                <!-- Tanggal -->
                                <p class="text-muted small mb-0">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    <?= htmlspecialchars(formatDate($berita['created_at'], 'd F Y'), ENT_QUOTES, 'UTF-8') ?>
                                </p>

                            </div><!-- /.card-body -->
                        </div><!-- /.berita-card -->
                    </div><!-- /.col -->
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Belum ada berita atau pengumuman.
                    </div>
                </div>
            <?php endif; ?>
        </div><!-- /.row -->

        <!-- Link Lihat Semua -->
        <div class="text-center mt-4" data-aos="fade-up">
            <a href="edukasi.php" class="btn btn-outline-primary">
                <i class="fa-solid fa-book-open me-2"></i>
                Lihat Semua Berita &amp; Edukasi
            </a>
        </div>

    </div><!-- /.container -->
</section>

<!-- ================================================================
     CTA SECTION: LAPOR BANJIR
     ================================================================ -->
<section class="cta-section" data-aos="fade-up">
    <div class="container position-relative">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7">
                <h2 class="mb-3 fw-bold">
                    <i class="fa-solid fa-megaphone me-2"></i>
                    Lihat Banjir di Sekitar Anda?
                </h2>
                <p class="lead mb-4">
                    Laporkan segera kepada kami agar tim BPBD dapat segera memberikan bantuan.
                    Setiap laporan warga sangat membantu respon cepat kami.
                </p>
                <a href="laporan.php" class="btn btn-light btn-lg px-5">
                    <i class="fa-solid fa-file-circle-plus me-2"></i>
                    Lapor Sekarang
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     SCRIPT: COUNTER ANIMATION
     ================================================================ -->
<script>
/**
 * Animasi counter untuk stat number
 * Menganimasikan angka dari 0 ke target
 */
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-number.counter-animate');

    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'), 10) || 0;
        const duration = 1500; // durasi animasi dalam ms
        const increment = target / (duration / 16); // 60fps ~16ms per frame
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.ceil(current).toLocaleString('id-ID');
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString('id-ID');
            }
        };

        // Mulai animasi saat elemen terlihat di viewport
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        observer.observe(counter);
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
