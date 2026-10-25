<?php
/**
 * Halaman Tentang & Kontak
 * File: tentang.php
 *
 * Berisi deskripsi sistem, SDG, tim pengembang, disclaimer,
 * formulir kontak, dan peta lokasi kantor BPBD Medan.
 */

// Muat helper
require_once __DIR__ . '/includes/functions.php';

// ── Judul halaman (harus sebelum include header) ─────────────────────────────
$pageTitle = 'Tentang Sistem';

// ── Include header ────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/header.php';
?>

<!-- Leaflet CSS (untuk peta kecil BPBD) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">

<!-- ── Hero Section ────────────────────────────────────────────────────────── -->
<section class="page-hero py-5 bg-info text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-right">
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 small opacity-75">
                        <li class="breadcrumb-item">
                            <a href="index.php" class="text-white text-decoration-none">Beranda</a>
                        </li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Tentang</li>
                    </ol>
                </nav>
                <h1 class="display-6 fw-bold mb-2">
                    <i class="fa-solid fa-circle-info me-3"></i>Tentang Sistem
                </h1>
                <p class="lead mb-0 opacity-90">
                    Informasi lengkap mengenai SIG Rawan Banjir Kota Medan: tujuan, teknologi, tim pengembang, dan cara menghubungi kami.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
                <i class="fa-solid fa-droplet fa-4x opacity-20"></i>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 1 — Deskripsi Sistem
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="py-5" id="deskripsi">
    <div class="container">

        <div class="row g-5 align-items-center">

            <!-- Teks deskripsi -->
            <div class="col-lg-6" data-aos="fade-right">
                <span class="badge bg-primary-subtle text-primary fw-semibold mb-3 px-3 py-2">
                    <i class="fa-solid fa-globe me-1"></i> Tentang Sistem
                </span>
                <h2 class="fw-bold mb-3">SIG Rawan Banjir &amp; Jalur Evakuasi Kota Medan</h2>
                <p class="text-muted mb-3">
                    Sistem Informasi Geografis (SIG) Rawan Banjir dan Jalur Evakuasi Kota Medan adalah platform
                    berbasis web yang dikembangkan untuk membantu masyarakat dan pemangku kepentingan dalam
                    memahami distribusi kawasan rawan banjir, menemukan jalur evakuasi terdekat, dan mengakses
                    data historis kejadian banjir di Kota Medan.
                </p>
                <p class="text-muted mb-4">
                    Sistem ini dikelola oleh Badan Penanggulangan Bencana Daerah (BPBD) Kota Medan dan
                    bertujuan menjadi sumber informasi tunggal yang dapat diakses oleh seluruh lapisan masyarakat
                    kapan saja dan di mana saja melalui perangkat apa pun.
                </p>

                <!-- Tujuan & Manfaat -->
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex gap-3 p-3 rounded-3 bg-primary bg-opacity-5 border border-primary border-opacity-10 h-100">
                            <i class="fa-solid fa-bullseye text-primary fs-4 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Tujuan</h6>
                                <p class="small text-muted mb-0">Menyediakan informasi spasial banjir yang akurat, terkini, dan mudah diakses oleh masyarakat Kota Medan.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex gap-3 p-3 rounded-3 bg-success bg-opacity-5 border border-success border-opacity-10 h-100">
                            <i class="fa-solid fa-star text-success fs-4 mt-1 flex-shrink-0"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Manfaat</h6>
                                <p class="small text-muted mb-0">Mempercepat respons darurat, meningkatkan kesadaran masyarakat, dan mendukung pengambilan keputusan berbasis data.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teknologi yang digunakan -->
            <div class="col-lg-6" data-aos="fade-left">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-microchip text-primary me-2"></i>Teknologi yang Digunakan
                </h5>
                <div class="row g-3">
                    <?php
                    // Daftar teknologi yang digunakan dalam sistem
                    $teknologi = [
                        ['icon' => 'fa-brands fa-php',      'color' => 'text-primary',  'nama' => 'PHP 8',          'desc' => 'Backend server-side'],
                        ['icon' => 'fa-solid fa-database',   'color' => 'text-info',     'nama' => 'MySQL / MariaDB', 'desc' => 'Manajemen database'],
                        ['icon' => 'fa-brands fa-bootstrap', 'color' => 'text-purple',   'nama' => 'Bootstrap 5',    'desc' => 'UI responsif'],
                        ['icon' => 'fa-solid fa-map',        'color' => 'text-success',  'nama' => 'Leaflet.js',     'desc' => 'Peta interaktif'],
                        ['icon' => 'fa-solid fa-chart-bar',  'color' => 'text-warning',  'nama' => 'Chart.js',       'desc' => 'Visualisasi data'],
                        ['icon' => 'fa-brands fa-js',        'color' => 'text-warning',  'nama' => 'JavaScript ES6+','desc' => 'Interaktivitas frontend'],
                        ['icon' => 'fa-solid fa-bell',       'color' => 'text-danger',   'nama' => 'SweetAlert2',    'desc' => 'Dialog notifikasi'],
                        ['icon' => 'fa-solid fa-wind',       'color' => 'text-info',     'nama' => 'AOS Library',    'desc' => 'Animasi scroll'],
                    ];
                    foreach ($teknologi as $t):
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="text-center p-3 rounded-3 border h-100">
                            <i class="<?= htmlspecialchars($t['icon'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($t['color'], ENT_QUOTES, 'UTF-8') ?> fs-3 mb-2 d-block"></i>
                            <div class="fw-semibold small"><?= htmlspecialchars($t['nama'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($t['desc'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.row -->
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 2 — SDG (Sustainable Development Goals)
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="py-5 bg-body-secondary" id="sdg">
    <div class="container">

        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-success-subtle text-success fw-semibold mb-2 px-3 py-2">
                <i class="fa-solid fa-leaf me-1"></i> Pembangunan Berkelanjutan
            </span>
            <h2 class="fw-bold">Kontribusi terhadap SDG PBB</h2>
            <p class="text-muted col-lg-7 mx-auto">
                Sistem ini berkontribusi langsung pada pencapaian dua tujuan pembangunan berkelanjutan PBB.
            </p>
        </div>

        <div class="row g-4 justify-content-center">

            <!-- SDG 11: Kota & Permukiman Berkelanjutan -->
            <div class="col-md-5" data-aos="fade-up" data-aos-delay="0">
                <div class="card shadow-sm border-0 h-100 overflow-hidden">
                    <div class="card-header text-white fw-bold py-3"
                         style="background: linear-gradient(135deg, #f99d26, #e5831a);">
                        <div class="d-flex align-items-center gap-3">
                            <span class="display-5 fw-black">11</span>
                            <div>
                                <div class="fw-bold">SDG 11</div>
                                <div class="small opacity-90">Kota &amp; Permukiman Berkelanjutan</div>
                            </div>
                            <i class="fa-solid fa-city ms-auto fs-3 opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <em>"Menjadikan kota dan permukiman manusia inklusif, aman, tangguh, dan berkelanjutan."</em>
                        </p>
                        <ul class="list-unstyled">
                            <li class="d-flex gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-warning mt-1 flex-shrink-0"></i>
                                <span class="small">Memetakan kawasan rawan banjir untuk perencanaan tata ruang kota yang lebih aman.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-warning mt-1 flex-shrink-0"></i>
                                <span class="small">Menyediakan jalur evakuasi yang terstruktur untuk mengurangi risiko korban jiwa.</span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="fa-solid fa-check-circle text-warning mt-1 flex-shrink-0"></i>
                                <span class="small">Mendukung ketahanan kota Medan terhadap ancaman bencana hidrometeorologi.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- SDG 13: Penanganan Perubahan Iklim -->
            <div class="col-md-5" data-aos="fade-up" data-aos-delay="100">
                <div class="card shadow-sm border-0 h-100 overflow-hidden">
                    <div class="card-header text-white fw-bold py-3"
                         style="background: linear-gradient(135deg, #3f9c35, #2d7327);">
                        <div class="d-flex align-items-center gap-3">
                            <span class="display-5 fw-black">13</span>
                            <div>
                                <div class="fw-bold">SDG 13</div>
                                <div class="small opacity-90">Penanganan Perubahan Iklim</div>
                            </div>
                            <i class="fa-solid fa-temperature-half ms-auto fs-3 opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            <em>"Mengambil tindakan cepat untuk mengatasi perubahan iklim dan dampaknya."</em>
                        </p>
                        <ul class="list-unstyled">
                            <li class="d-flex gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                                <span class="small">Menyediakan data tren banjir historis untuk mendukung analisis dampak perubahan iklim lokal.</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                                <span class="small">Meningkatkan kapasitas adaptasi masyarakat melalui edukasi dan peringatan dini berbasis data.</span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="fa-solid fa-check-circle text-success mt-1 flex-shrink-0"></i>
                                <span class="small">Mendukung pelaporan partisipatif warga sebagai data observasi perubahan pola banjir.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div><!-- /.row sdg -->
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 3 — Tim Pengembang
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="py-5" id="tim">
    <div class="container">

        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-primary-subtle text-primary fw-semibold mb-2 px-3 py-2">
                <i class="fa-solid fa-users me-1"></i> Tim
            </span>
            <h2 class="fw-bold">Tim Pengembang</h2>
            <p class="text-muted col-lg-7 mx-auto">
                Sistem ini dikembangkan oleh tim multidisiplin yang terdiri dari tenaga ahli teknologi informasi
                dan praktisi manajemen bencana dari BPBD Kota Medan.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php
            // Data tim pengembang (placeholder)
            $timPengembang = [
                [
                    'nama'   => 'Budi Santoso, S.Kom.',
                    'jabatan'=> 'Lead Developer & System Architect',
                    'unit'   => 'Bidang Teknologi Informasi BPBD',
                    'icon'   => 'fa-solid fa-laptop-code',
                    'warna'  => 'primary'
                ],
                [
                    'nama'   => 'Sari Dewi Nasution, S.T.',
                    'jabatan'=> 'GIS Analyst & Database Engineer',
                    'unit'   => 'Bidang Pemetaan & Data Spasial',
                    'icon'   => 'fa-solid fa-map',
                    'warna'  => 'success'
                ],
                [
                    'nama'   => 'Ahmad Fauzi, S.Sos.',
                    'jabatan'=> 'Project Manager & Stakeholder Liaison',
                    'unit'   => 'Sekretariat BPBD Kota Medan',
                    'icon'   => 'fa-solid fa-person-chalkboard',
                    'warna'  => 'warning'
                ],
                [
                    'nama'   => 'Putri Handayani, S.Kom.',
                    'jabatan'=> 'UI/UX Designer & Frontend Developer',
                    'unit'   => 'Bidang Teknologi Informasi BPBD',
                    'icon'   => 'fa-solid fa-palette',
                    'warna'  => 'info'
                ],
            ];
            foreach ($timPengembang as $idx => $anggota):
            ?>
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $idx * 100 ?>">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center
                                    bg-<?= htmlspecialchars($anggota['warna'], ENT_QUOTES, 'UTF-8') ?> bg-opacity-10"
                             style="width:80px; height:80px;">
                            <i class="<?= htmlspecialchars($anggota['icon'], ENT_QUOTES, 'UTF-8') ?>
                                       text-<?= htmlspecialchars($anggota['warna'], ENT_QUOTES, 'UTF-8') ?> fs-2"></i>
                        </div>
                        <h6 class="fw-bold mb-1">
                            <?= htmlspecialchars($anggota['nama'], ENT_QUOTES, 'UTF-8') ?>
                        </h6>
                        <p class="text-muted small mb-1">
                            <?= htmlspecialchars($anggota['jabatan'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <span class="badge bg-<?= htmlspecialchars($anggota['warna'], ENT_QUOTES, 'UTF-8') ?> bg-opacity-10
                                             text-<?= htmlspecialchars($anggota['warna'], ENT_QUOTES, 'UTF-8') ?> small">
                            <?= htmlspecialchars($anggota['unit'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Catatan kolaborasi -->
        <div class="text-center mt-4" data-aos="fade-up">
            <p class="text-muted small">
                <i class="fa-solid fa-handshake text-primary me-2"></i>
                Sistem ini dikembangkan atas dukungan dan kolaborasi Dinas Komunikasi dan Informatika Kota Medan,
                BMKG Stasiun Klimatologi Sumatera Utara, dan Universitas Sumatera Utara.
            </p>
        </div>

    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 4 — Disclaimer Data
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="py-4 bg-warning bg-opacity-10 border-top border-bottom border-warning border-opacity-25" id="disclaimer">
    <div class="container" data-aos="fade-up">
        <div class="row align-items-center g-3">
            <div class="col-md-1 text-center">
                <i class="fa-solid fa-triangle-exclamation text-warning fa-3x"></i>
            </div>
            <div class="col-md-11">
                <h5 class="fw-bold text-warning mb-2">
                    Disclaimer Data
                </h5>
                <p class="text-muted mb-2">
                    Data yang ditampilkan pada website ini — termasuk lokasi kawasan rawan banjir, koordinat titik evakuasi,
                    data statistik kejadian banjir, dan informasi lainnya — <strong>bersifat ilustratif dan digunakan
                    untuk keperluan simulasi, edukasi, serta pengembangan sistem</strong>.
                </p>
                <p class="text-muted mb-2">
                    Data tersebut <strong>belum tentu mencerminkan kondisi lapangan yang sesungguhnya</strong>.
                    Untuk keperluan operasional, perencanaan tata ruang, atau pengambilan keputusan resmi,
                    <strong>gunakan data resmi yang diterbitkan oleh BPBD Kota Medan, BMKG, atau Badan Informasi
                    Geospasial (BIG)</strong>.
                </p>
                <p class="mb-0 small text-muted">
                    <strong>Kontak data resmi:</strong>
                    BPBD Kota Medan &mdash; (061) 450-12345 &mdash; bpbd@pemkomedan.go.id
                    &nbsp;|&nbsp;
                    BMKG Stasiun Meteorologi Kelas I Kualanamu &mdash; (061) 7954140
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 5 — Formulir Kontak
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="py-5" id="kontak">
    <div class="container">

        <div class="text-center mb-5" data-aos="fade-up">
            <span class="badge bg-info-subtle text-info fw-semibold mb-2 px-3 py-2">
                <i class="fa-solid fa-envelope me-1"></i> Kontak
            </span>
            <h2 class="fw-bold">Hubungi Kami</h2>
            <p class="text-muted col-lg-7 mx-auto">
                Punya pertanyaan, saran, atau kritik tentang sistem ini? Kirimkan pesan kepada kami.
            </p>
        </div>

        <div class="row g-4">

            <!-- Formulir kontak -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="fa-solid fa-paper-plane text-primary me-2"></i>Kirim Pesan
                        </h5>
                        <form id="formKontak" novalidate>
                            <div class="mb-3">
                                <label for="kontakNama" class="form-label fw-semibold">
                                    Nama <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="kontakNama"
                                       name="nama" required minlength="2" maxlength="100"
                                       placeholder="Nama lengkap Anda" autocomplete="name">
                                <div class="invalid-feedback">Nama wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label for="kontakEmail" class="form-label fw-semibold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control" id="kontakEmail"
                                       name="email" required maxlength="100"
                                       placeholder="email@contoh.com" autocomplete="email">
                                <div class="invalid-feedback">Masukkan alamat email yang valid.</div>
                            </div>
                            <div class="mb-4">
                                <label for="kontakPesan" class="form-label fw-semibold">
                                    Pesan <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="kontakPesan" name="pesan"
                                          rows="5" required minlength="10" maxlength="1000"
                                          placeholder="Tulis pertanyaan atau saran Anda di sini…"></textarea>
                                <div class="invalid-feedback">Pesan wajib diisi (min 10 karakter).</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="btnKirimKontak">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Kirim Pesan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info kontak BPBD -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="fa-solid fa-address-card text-info me-2"></i>Informasi Kontak
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-3 mb-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 flex-shrink-0"
                                     style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold small">Kantor</div>
                                    <div class="text-muted small">BPBD Kota Medan<br>
                                        Jl. Kapten Maulana Lubis No. 2, Medan Petisah,<br>
                                        Kota Medan, Sumatera Utara 20112</div>
                                </div>
                            </li>
                            <li class="d-flex gap-3 mb-3">
                                <div class="rounded-circle bg-success bg-opacity-10 p-2 flex-shrink-0"
                                     style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-phone text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold small">Telepon</div>
                                    <div class="text-muted small">
                                        <a href="tel:06145012345" class="text-decoration-none">(061) 450-12345</a> (Kantor)<br>
                                        <a href="tel:117" class="text-decoration-none text-danger fw-bold">117</a> (Hotline Darurat 24 jam)
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex gap-3 mb-3">
                                <div class="rounded-circle bg-warning bg-opacity-10 p-2 flex-shrink-0"
                                     style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-envelope text-warning"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold small">Email</div>
                                    <div class="text-muted small">
                                        <a href="mailto:bpbd@pemkomedan.go.id" class="text-decoration-none">bpbd@pemkomedan.go.id</a>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex gap-3">
                                <div class="rounded-circle bg-info bg-opacity-10 p-2 flex-shrink-0"
                                     style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                    <i class="fa-solid fa-clock text-info"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold small">Jam Operasional</div>
                                    <div class="text-muted small">
                                        Senin – Jumat: 08.00 – 16.00 WIB<br>
                                        <span class="text-danger">Hotline Darurat: 24 Jam / 7 Hari</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div><!-- /.row kontak -->
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════════════════════
     SECTION 6 — Peta Lokasi Kantor BPBD Medan
     ════════════════════════════════════════════════════════════════════════════ -->
<section class="pb-5" id="lokasi-kantor">
    <div class="container" data-aos="fade-up">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent fw-bold border-bottom">
                <i class="fa-solid fa-location-dot text-danger me-2"></i>
                Lokasi Kantor BPBD Kota Medan
            </div>
            <div class="card-body p-0">
                <div id="mapBPBD" style="height:350px; border-radius:0 0 .375rem .375rem;"
                     role="application" aria-label="Peta lokasi kantor BPBD Kota Medan"></div>
            </div>
        </div>
    </div>
</section>

<!-- ── Footer sticky kontak darurat BPBD ───────────────────────────────────── -->
<div class="bg-danger text-white py-2 sticky-bottom d-none d-md-block" id="stickyDarurat">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-semibold small">
                <i class="fa-solid fa-phone-volume me-2"></i>
                Darurat Banjir? Hubungi BPBD Kota Medan sekarang
            </span>
            <div class="d-flex gap-3 align-items-center">
                <a href="tel:117" class="text-white text-decoration-none fw-bold">
                    <i class="fa-solid fa-phone me-1"></i>117 (Hotline Bebas Pulsa)
                </a>
                <span class="opacity-50">|</span>
                <a href="tel:06145012345" class="text-white text-decoration-none">
                    (061) 450-12345
                </a>
                <button type="button" class="btn-close btn-close-white btn-sm ms-2"
                        id="btnTutupStickyDarurat"
                        aria-label="Tutup bar darurat"></button>
            </div>
        </div>
    </div>
</div>

<!-- ── Leaflet JS ───────────────────────────────────────────────────────────── -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WLEo=" crossorigin="anonymous"></script>

<script>
/**
 * Tentang Page Scripts
 * - Inisialisasi peta kecil lokasi kantor BPBD
 * - Formulir kontak (simpan ke localStorage sebagai simulasi)
 * - Tutup sticky bar darurat
 */
(function () {
    'use strict';

    // ── Peta kecil: lokasi kantor BPBD Kota Medan ─────────────────────────
    // Koordinat perkiraan BPBD Kota Medan
    var BPBD_LAT = 3.5870;
    var BPBD_LNG = 98.6740;

    var mapBPBD = L.map('mapBPBD', {
        center: [BPBD_LAT, BPBD_LNG],
        zoom: 15,
        scrollWheelZoom: false,   // Matikan scroll zoom agar tidak ganggu scroll halaman
        dragging: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(mapBPBD);

    // Marker BPBD dengan popup info
    var markerBPBD = L.marker([BPBD_LAT, BPBD_LNG]).addTo(mapBPBD);
    markerBPBD.bindPopup(
        '<strong><i class="fa-solid fa-shield-halved"></i> BPBD Kota Medan</strong><br>' +
        'Jl. Kapten Maulana Lubis No. 2<br>' +
        'Medan Petisah, Kota Medan<br>' +
        '<a href="tel:06145012345">(061) 450-12345</a>'
    ).openPopup();

    // ── Formulir kontak: simpan ke localStorage (simulasi) ────────────────
    document.getElementById('formKontak').addEventListener('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }
        this.classList.add('was-validated');

        // Kumpulkan data pesan
        var pesanData = {
            nama      : document.getElementById('kontakNama').value.trim(),
            email     : document.getElementById('kontakEmail').value.trim(),
            pesan     : document.getElementById('kontakPesan').value.trim(),
            timestamp : new Date().toISOString()
        };

        // Simpan ke localStorage (simulasi pengiriman)
        try {
            var existing = JSON.parse(localStorage.getItem('sig_kontak_pesan') || '[]');
            existing.push(pesanData);
            localStorage.setItem('sig_kontak_pesan', JSON.stringify(existing));
        } catch (err) {
            // Abaikan error localStorage (mode private browser, dll.)
        }

        // Tampilkan notifikasi sukses
        Swal.fire({
            icon: 'success',
            title: 'Pesan Terkirim!',
            html: '<p>Terima kasih, <strong>' + escapeHtml(pesanData.nama) + '</strong>!</p>' +
                  '<p class="text-muted small">Kami akan merespons ke ' +
                  escapeHtml(pesanData.email) + ' dalam 1–2 hari kerja.</p>',
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Oke'
        }).then(function () {
            // Reset form setelah dialog ditutup
            document.getElementById('formKontak').reset();
            document.getElementById('formKontak').classList.remove('was-validated');
        });
    });

    // ── Helper: escape HTML untuk output ke SweetAlert ───────────────────
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ── Tombol tutup sticky bar darurat ──────────────────────────────────
    var btnTutup = document.getElementById('btnTutupStickyDarurat');
    if (btnTutup) {
        btnTutup.addEventListener('click', function () {
            var bar = document.getElementById('stickyDarurat');
            if (bar) bar.style.display = 'none';
        });
    }

})();
</script>

<?php
// ── Include footer ────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/footer.php';
?>
