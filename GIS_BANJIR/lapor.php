<?php
/**
 * Halaman Laporan Warga
 * File: lapor.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Lapor Banjir';
$csrfToken  = generateCSRF();

$kecamatanList = [];
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, nama FROM kecamatan ORDER BY nama ASC");
    $stmt->execute();
    $kecamatanList = $stmt->fetchAll();
} catch (PDOException $e) {
    $kecamatanList = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

<style>
/* Pastikan peta selalu tampil dan tidak ter-clip oleh AOS */
#mapLaporan {
    height: 380px;
    width: 100%;
    border-radius: 8px;
    z-index: 1;
    position: relative;
    background: #e8e8e8;
}
/* Hapus transform AOS dari kolom peta agar Leaflet bisa hitung ukuran */
.no-aos-map { transform: none !important; opacity: 1 !important; }
</style>

<!-- ── Hero ── -->
<section class="py-4 bg-warning text-dark">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0 small opacity-75">
                <li class="breadcrumb-item"><a href="index.php" class="text-dark text-decoration-none">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page">Lapor Banjir</li>
            </ol>
        </nav>
        <h1 class="display-6 fw-bold mb-1">
            <i class="fa-solid fa-file-circle-plus me-2"></i>Lapor Banjir
        </h1>
        <p class="lead mb-0 opacity-90">Laporkan kejadian banjir untuk membantu BPBD memetakan wilayah terdampak.</p>
    </div>
</section>

<!-- ── Form + Peta ── -->
<section class="py-5">
    <div class="container">

        <div class="alert alert-info d-flex gap-2 small mb-4" role="alert">
            <i class="fa-solid fa-circle-info flex-shrink-0 mt-1"></i>
            <span><strong>Perhatian:</strong> Data laporan akan diverifikasi admin BPBD (maks 1×24 jam) sebelum ditampilkan di peta publik.</span>
        </div>

        <div class="row g-4">

            <!-- ── Kolom Form ── -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-bold">
                        <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Form Laporan Banjir
                    </div>
                    <div class="card-body p-4">
                        <form id="formLaporan" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="latitude"  id="inputLatitude">
                            <input type="hidden" name="longitude" id="inputLongitude">

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="namaPelapor" class="form-label fw-semibold">Nama Pelapor <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="namaPelapor" name="nama_pelapor"
                                           placeholder="Nama lengkap" required minlength="3" maxlength="100">
                                    <div class="invalid-feedback">Nama wajib diisi (min 3 karakter).</div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="kontak" class="form-label fw-semibold">Nomor Kontak <small class="text-muted">(opsional)</small></label>
                                    <input type="tel" class="form-control" id="kontak" name="kontak"
                                           placeholder="0812-xxxx-xxxx" maxlength="20">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="kecamatanId" class="form-label fw-semibold">Kecamatan</label>
                                    <select class="form-select" id="kecamatanId" name="kecamatan_id">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($kecamatanList as $kec): ?>
                                        <option value="<?= htmlspecialchars($kec['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="ketinggianCm" class="form-label fw-semibold">Ketinggian Air (cm) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="ketinggianCm" name="ketinggian_cm"
                                               placeholder="50" required min="0" max="9999">
                                        <span class="input-group-text">cm</span>
                                    </div>
                                    <div class="invalid-feedback">Ketinggian wajib diisi (0–9999).</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="deskripsi" class="form-label fw-semibold">Deskripsi Kejadian <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi"
                                          rows="4" required minlength="20" maxlength="2000"
                                          placeholder="Ceritakan kondisi banjir: lokasi persis, penyebab, dampak... (min 20 karakter)"></textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <div class="invalid-feedback d-block" id="errDeskripsi" style="display:none!important"></div>
                                    <small class="text-muted ms-auto" id="charCount">0 / 2000</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="foto" class="form-label fw-semibold">Foto Kondisi Banjir <small class="text-muted">(opsional, maks 5 MB)</small></label>
                                <input type="file" class="form-control" id="foto" name="foto"
                                       accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Format: JPEG, PNG, WebP.</div>
                                <div id="previewContainer" class="mt-2 d-none">
                                    <div class="position-relative d-inline-block">
                                        <img id="previewFoto" src="#" alt="Preview" class="img-thumbnail" style="max-height:160px;">
                                        <button type="button" id="btnHapusFoto"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Koordinat yang dipilih -->
                            <div id="lokasiStatus" class="mb-3 p-2 rounded small bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                                Belum ada lokasi dipilih — klik peta di sebelah kanan atau gunakan tombol GPS.
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning btn-lg fw-bold" id="btnKirim">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Kirim Laporan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Kolom Peta (TANPA data-aos agar Leaflet tidak error) ── -->
            <div class="col-lg-6 no-aos-map">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent fw-bold">
                        <i class="fa-solid fa-map-pin text-danger me-2"></i>Tandai Lokasi Banjir
                    </div>
                    <div class="card-body p-3 d-flex flex-column">

                        <!-- Tombol lokasi -->
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" id="btnGPS" class="btn btn-sm btn-primary flex-fill">
                                <i class="fa-solid fa-location-crosshairs me-1"></i> GPS
                            </button>
                            <button type="button" id="btnIPLokasi" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="fa-solid fa-wifi me-1"></i> Lokasi via IP
                            </button>
                        </div>

                        <!-- Peta Leaflet -->
                        <div id="mapLaporan"></div>

                        <p class="small text-muted mt-2 mb-0">
                            <i class="fa-solid fa-circle-info text-primary me-1"></i>
                            Klik peta untuk pasang pin &bull; Geser pin untuk koreksi posisi
                        </p>
                    </div>
                </div>
            </div>

        </div><!-- /.row -->
    </div>
</section>

<!-- ── Scripts ── -->
<!-- SweetAlert2 dimuat SEBELUM Leaflet agar Swal tersedia saat dipakai -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<script>
(function () {
    'use strict';

    /* ── 1. Inisialisasi Leaflet ──────────────────────────────────────── */
    var map = L.map('mapLaporan', {
        center: [3.5952, 98.6722],
        zoom: 12
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Paksa Leaflet hitung ulang ukuran container (atasi AOS / hidden div)
    setTimeout(function () { map.invalidateSize(); }, 300);

    var marker = null;

    /* ── 2. Helper set koordinat ──────────────────────────────────────── */
    function setKoordinat(lat, lng) {
        document.getElementById('inputLatitude').value  = lat.toFixed(8);
        document.getElementById('inputLongitude').value = lng.toFixed(8);
        document.getElementById('lokasiStatus').innerHTML =
            '<i class="fa-solid fa-circle-check text-success me-1"></i>' +
            '<strong>Lokasi dipilih:</strong> ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
        document.getElementById('lokasiStatus').className =
            'mb-3 p-2 rounded small bg-success bg-opacity-10 border border-success border-opacity-25';
    }

    function letakkanMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function (e) {
                var p = e.target.getLatLng();
                setKoordinat(p.lat, p.lng);
            });
        }
        setKoordinat(lat, lng);
    }

    /* ── 3. Klik peta ─────────────────────────────────────────────────── */
    map.on('click', function (e) {
        letakkanMarker(e.latlng.lat, e.latlng.lng);
    });

    /* ── 4. Tombol GPS ────────────────────────────────────────────────── */
    document.getElementById('btnGPS').addEventListener('click', function () {
        var btn = this;

        if (!navigator.geolocation) {
            Swal.fire('GPS Tidak Didukung', 'Browser ini tidak mendukung Geolocation. Coba "Lokasi via IP" atau klik peta.', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mencari...';

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                letakkanMarker(pos.coords.latitude, pos.coords.longitude);
                map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> GPS OK';
                setTimeout(function () {
                    btn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> GPS';
                }, 3000);
            },
            function (err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> GPS';
                var msg = {
                    1: 'Izin lokasi ditolak browser.<br><small>Di Chrome: klik ikon 🔒 di address bar → Izinkan Lokasi.<br>Di localhost HTTP, Chrome memblokir GPS — gunakan Firefox atau tombol "Lokasi via IP".</small>',
                    2: 'Informasi lokasi tidak tersedia. Aktifkan GPS perangkat Anda.',
                    3: 'Waktu deteksi habis. Coba lagi atau gunakan "Lokasi via IP".'
                }[err.code] || ('Error: ' + err.message);
                Swal.fire({ icon: 'warning', title: 'GPS Gagal', html: msg, confirmButtonColor: '#ffc107' });
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    });

    /* ── 5. Tombol Lokasi via IP ─────────────────────────────────────── */
    document.getElementById('btnIPLokasi').addEventListener('click', function () {
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mencari...';

        fetch('https://ipapi.co/json/')
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var lat = parseFloat(d.latitude  || 0);
                var lng = parseFloat(d.longitude || 0);
                if (!lat && !lng) throw new Error('Koordinat kosong');
                letakkanMarker(lat, lng);
                map.setView([lat, lng], 13);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-wifi me-1"></i> Lokasi via IP';
                Swal.fire({
                    icon: 'info',
                    title: 'Lokasi via IP Terdeteksi',
                    html: (d.city || '') + (d.region ? ', ' + d.region : '') +
                          '<br><small class="text-muted">Akurasi ±5–10 km. Klik peta untuk posisi lebih tepat.</small>',
                    confirmButtonColor: '#20c997'
                });
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-wifi me-1"></i> Lokasi via IP';
                Swal.fire('Gagal', 'Tidak dapat mendeteksi lokasi via IP. Klik langsung pada peta.', 'error');
            });
    });

    /* ── 6. Preview foto ─────────────────────────────────────────────── */
    document.getElementById('foto').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('File Terlalu Besar', 'Ukuran foto maksimal 5 MB.', 'error');
            this.value = '';
            return;
        }
        var rd = new FileReader();
        rd.onload = function (e) {
            document.getElementById('previewFoto').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
        };
        rd.readAsDataURL(file);
    });

    document.getElementById('btnHapusFoto').addEventListener('click', function () {
        document.getElementById('foto').value = '';
        document.getElementById('previewFoto').src = '#';
        document.getElementById('previewContainer').classList.add('d-none');
    });

    /* ── 7. Counter karakter ─────────────────────────────────────────── */
    document.getElementById('deskripsi').addEventListener('input', function () {
        document.getElementById('charCount').textContent = this.value.length + ' / 2000';
    });

    /* ── 8. Submit AJAX ──────────────────────────────────────────────── */
    document.getElementById('formLaporan').addEventListener('submit', function (e) {
        e.preventDefault();

        // Cek koordinat
        if (!document.getElementById('inputLatitude').value) {
            Swal.fire({
                icon: 'warning',
                title: 'Lokasi Belum Dipilih',
                text: 'Klik pada peta, gunakan GPS, atau Lokasi via IP untuk menentukan lokasi banjir.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return;
        }
        this.classList.add('was-validated');

        var btn = document.getElementById('btnKirim');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Mengirim...';

        var fd = new FormData(this);

        fetch('api/laporan.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                btn.innerHTML = orig;
                if (d.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Laporan Terkirim!',
                        html: (d.message || 'Terima kasih atas laporan Anda.') +
                              (d.id_laporan ? '<br><small class="text-muted">ID: #' + d.id_laporan + '</small>' : ''),
                        confirmButtonColor: '#198754'
                    }).then(function () {
                        document.getElementById('formLaporan').reset();
                        document.getElementById('formLaporan').classList.remove('was-validated');
                        document.getElementById('previewContainer').classList.add('d-none');
                        document.getElementById('charCount').textContent = '0 / 2000';
                        if (marker) { map.removeLayer(marker); marker = null; }
                        document.getElementById('inputLatitude').value  = '';
                        document.getElementById('inputLongitude').value = '';
                        document.getElementById('lokasiStatus').innerHTML =
                            '<i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>' +
                            'Belum ada lokasi dipilih — klik peta atau gunakan GPS.';
                        document.getElementById('lokasiStatus').className =
                            'mb-3 p-2 rounded small bg-warning bg-opacity-10 border border-warning border-opacity-25';
                    });
                } else {
                    Swal.fire('Gagal', d.error || 'Terjadi kesalahan.', 'error');
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = orig;
                Swal.fire('Kesalahan Jaringan', 'Tidak dapat menghubungi server.', 'error');
            });
    });

})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
