<?php
/**
 * Footer HTML untuk semua halaman publik
 * File: includes/footer.php
 *
 * Footer ini HARUS menutup </body> dan </html>.
 */
?>

    <!-- ========================
         FOOTER
         ======================== -->
    <footer class="footer pt-5 pb-3 mt-auto">
        <div class="container">
            <div class="row g-4 pb-4 border-bottom">

                <!-- Kolom 1: Info BPBD Kota Medan -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="0">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-droplet text-primary fs-4"></i>
                        <h5 class="fw-bold mb-0">SIG Banjir Medan</h5>
                    </div>
                    <p class="text-muted small mb-3">
                        Sistem Informasi Geografis Kawasan Rawan Banjir dan Jalur Evakuasi Kota Medan
                        yang dikelola oleh BPBD Kota Medan.
                    </p>
                    <ul class="list-unstyled text-muted small">
                        <li class="d-flex gap-2 mb-2">
                            <i class="fa-solid fa-building mt-1 text-primary flex-shrink-0"></i>
                            <span><strong>BPBD Kota Medan</strong><br>Jl. Kapten Maulana Lubis No. 2, Medan</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="fa-solid fa-phone mt-1 text-primary flex-shrink-0"></i>
                            <span>(061) 4512345</span>
                        </li>
                        <li class="d-flex gap-2 mb-2">
                            <i class="fa-solid fa-envelope mt-1 text-primary flex-shrink-0"></i>
                            <span>bpbd@pemkomedan.go.id</span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="fa-solid fa-clock mt-1 text-primary flex-shrink-0"></i>
                            <span>Siaga 24 Jam / 7 Hari</span>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 2: Navigasi Cepat -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <h5 class="fw-bold mb-3">Navigasi Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="index.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Beranda
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="peta.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Peta Kawasan Rawan Banjir
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="evakuasi.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Jalur &amp; Titik Evakuasi
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="statistik.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Statistik Kejadian Banjir
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="edukasi.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Edukasi &amp; Informasi
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="lapor.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Laporkan Banjir
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="tentang.php" class="footer-link text-muted text-decoration-none">
                                <i class="fa-solid fa-chevron-right me-2 text-primary small"></i>Tentang Aplikasi
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Kolom 3: Kontak Darurat -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <h5 class="fw-bold mb-3">
                        <i class="fa-solid fa-phone-volume text-danger me-2"></i>Kontak Darurat
                    </h5>
                    <p class="text-muted small mb-3">
                        Hubungi nomor berikut segera jika terjadi keadaan darurat banjir:
                    </p>

                    <!-- BPBD -->
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-shield-halved text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">BPBD Nasional</div>
                            <a href="tel:117" class="text-danger text-decoration-none fw-bold fs-5">117</a>
                        </div>
                    </div>

                    <!-- PMI -->
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-kit-medical text-danger fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">PMI (Palang Merah Indonesia)</div>
                            <a href="tel:0217992325" class="text-danger text-decoration-none fw-bold fs-5">021-7992325</a>
                        </div>
                    </div>

                    <!-- Basarnas -->
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-helicopter text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Basarnas (SAR Nasional)</div>
                            <a href="tel:115" class="text-warning text-decoration-none fw-bold fs-5">115</a>
                        </div>
                    </div>
                </div>

            </div><!-- /.row -->

            <!-- Baris Copyright & Disclaimer -->
            <div class="row align-items-center pt-3">
                <div class="col-md-6 text-muted small mb-2 mb-md-0">
                    &copy; 2024 BPBD Kota Medan &mdash; SIG Rawan Banjir &amp; Jalur Evakuasi.
                    Hak cipta dilindungi undang-undang.
                </div>
                <div class="col-md-6 text-md-end text-muted small">
                    <i class="fa-solid fa-circle-info me-1 text-warning"></i>
                    <em>Data pada situs ini bersifat ilustratif untuk keperluan simulasi dan edukasi.</em>
                </div>
            </div>

        </div><!-- /.container -->
    </footer>
    <!-- ======================== END FOOTER ======================== -->

    <!-- Bootstrap 5.3 JS Bundle (termasuk Popper) — HARUS sebelum AOS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmZ6mVxNpSVLLLMxwnkqSXpGHI1" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- AOS (Animate On Scroll) JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" integrity="sha512-A7AYk1fGKX6S2SsHywmPkrnzTZHrgiVT7GcQkLGDe2ev0afc/LGktzI77c1DzMDSZZf5J5Mko/+5iAaA90G/A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Inisialisasi AOS — disable=true pada mobile agar tidak konflik dengan accordion -->
    <script>
        AOS.init({
            duration: 700,
            easing: 'ease-in-out',
            once: true,
            offset: 50,
            disable: function() {
                // Matikan AOS di layar kecil dan jika ada accordion di halaman
                return window.innerWidth < 768;
            }
        });

        // Paksa semua elemen accordion agar selalu visible (override AOS)
        document.querySelectorAll('.accordion').forEach(function(el) {
            // Hapus atribut AOS dari accordion dan semua child-nya
            el.removeAttribute('data-aos');
            el.removeAttribute('data-aos-delay');
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.closest('[data-aos]') && (function(parent) {
                parent.style.opacity = '1';
                parent.style.transform = 'none';
            })(el.closest('[data-aos]'));
        });

        // Override: pastikan semua elemen yang sudah dianimasikan tidak disembunyikan
        document.querySelectorAll('[data-aos]').forEach(function(el) {
            // Set opacity ke 1 setelah sedikit delay agar AOS selesai init
        });
    </script>

    <!-- Fix AOS konflik dengan Bootstrap accordion dan collapse -->
    <style>
        /* Pastikan accordion content tidak ter-hide oleh AOS */
        .accordion [data-aos],
        [data-aos] .accordion,
        [data-aos] .accordion-item,
        [data-aos] .accordion-collapse {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    </style>

</body>
</html>
