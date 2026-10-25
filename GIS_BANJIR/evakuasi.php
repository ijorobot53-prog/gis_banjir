<?php
/**
 * Halaman Jalur Evakuasi
 * File: evakuasi.php
 *
 * Menampilkan peta titik evakuasi dengan fitur navigasi rute.
 * Pengguna dapat menggunakan lokasi GPS dan mendapatkan rute
 * ke posko evakuasi terdekat via Leaflet Routing Machine (OSRM).
 */

// Koneksi database dan fungsi helper
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ambil daftar kecamatan untuk dropdown filter
$daftarKecamatan = [];

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, nama FROM kecamatan ORDER BY nama ASC");
    $daftarKecamatan = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Database error di evakuasi.php: ' . $e->getMessage());
}

// Set page title
$pageTitle = 'Jalur Evakuasi';

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- ================================================================
     LEAFLET CSS
     ================================================================ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">

<!-- Leaflet Routing Machine CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">

<style>
/* Sembunyikan panel instruksi bawaan Leaflet Routing Machine */
.leaflet-routing-container {
    display: none !important;
}
</style>

<!-- ================================================================
     LAYOUT: SIDEBAR + MAP CONTAINER
     ================================================================ -->
<div class="map-page-wrapper">

    <!-- ═══════════════════════════════════════════════════════════
         SIDEBAR KIRI: FILTER & DAFTAR POSKO
         ═══════════════════════════════════════════════════════════ -->
    <aside class="map-sidebar" id="mapSidebarEvakuasi">

        <!-- Header Sidebar -->
        <div class="sidebar-header">
            <h5 class="mb-1 fw-bold">
                <i class="fa-solid fa-house-flag me-2 text-success"></i>
                Posko Evakuasi
            </h5>
            <p class="text-muted small mb-0">Temukan posko terdekat dari lokasi Anda</p>
        </div>

        <!-- Body Sidebar -->
        <div class="sidebar-body">

            <!-- Filter Kecamatan -->
            <div class="mb-3">
                <label for="filterKecamatanEvak" class="form-label small fw-semibold">
                    <i class="fa-solid fa-map me-1"></i> Filter Kecamatan
                </label>
                <select class="form-select form-select-sm" id="filterKecamatanEvak">
                    <option value="">Semua Kecamatan</option>
                    <?php foreach ($daftarKecamatan as $kec): ?>
                        <option value="<?= htmlspecialchars((string)$kec['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="my-2">

            <!-- Keterangan Jumlah Posko -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0 fw-semibold small">Daftar Posko</h6>
                <span class="badge bg-success" id="badgePoskoCount">0</span>
            </div>

            <!-- Daftar Posko (diisi oleh JavaScript) -->
            <div id="poskoList">
                <div class="text-muted small text-center py-3">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Memuat data posko...
                </div>
            </div>

        </div><!-- /.sidebar-body -->

    </aside><!-- /.map-sidebar -->

    <!-- ═══════════════════════════════════════════════════════════
         MAP CONTAINER
         ═══════════════════════════════════════════════════════════ -->
    <div class="map-container">
        <div id="map"></div>

        <!-- Tombol Gunakan Lokasi Saya -->
        <button id="btnLokasiSaya" class="btn btn-primary">
            <i class="fa-solid fa-location-crosshairs me-1"></i>
            Gunakan Lokasi Saya
        </button>

        <!-- Panel Info Rute -->
        <div id="routeInfoPanel">
            <div class="route-info-item">
                <span class="route-info-value" id="routeJarak">-</span>
                <span class="route-info-label">Jarak (km)</span>
            </div>
            <div class="route-info-item">
                <span class="route-info-value" id="routeWaktu">-</span>
                <span class="route-info-label">Est. Waktu</span>
            </div>
            <div class="flex-grow-1 text-center">
                <div class="small fw-semibold" id="routeNamaPosko">Memuat...</div>
                <div class="small text-muted">Tujuan Evakuasi</div>
            </div>
            <button class="btn btn-sm btn-outline-secondary" id="btnTutupRute" title="Tutup panel rute">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

    </div><!-- /.map-container -->

</div><!-- /.map-page-wrapper -->

<!-- ================================================================
     LEAFLET JS
     ================================================================ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<!-- Leaflet Routing Machine -->
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<!-- ================================================================
     SCRIPT PETA EVAKUASI
     ================================================================ -->
<script>
// ═══════════════════════════════════════════════════════════════
// VARIABEL GLOBAL
// ═══════════════════════════════════════════════════════════════
let map = null;                 // Instance Leaflet map
let poskoMarkersLayer = null;  // Layer group untuk marker posko
let userMarker = null;          // Marker lokasi user
let routingControl = null;      // Instance Leaflet Routing Machine
let userLatLng = null;          // Koordinat lokasi user
let allPosko = [];              // Cache data semua posko

// ═══════════════════════════════════════════════════════════════
// KONFIGURASI IKON MARKER BERDASARKAN JENIS POSKO
// ═══════════════════════════════════════════════════════════════
const poskoIconConfig = {
    sekolah:    { color: '#0d6efd', icon: 'fa-school',        label: 'Sekolah'     },
    masjid:     { color: '#198754', icon: 'fa-mosque',        label: 'Masjid'      },
    gedung:     { color: '#6f42c1', icon: 'fa-building',      label: 'Gedung'      },
    lapangan:   { color: '#0dcaf0', icon: 'fa-leaf',          label: 'Lapangan'    },
    rumah_sakit:{ color: '#dc3545', icon: 'fa-hospital',      label: 'Rumah Sakit' },
    lainnya:    { color: '#6c757d', icon: 'fa-house-circle-check', label: 'Lainnya' }
};

// ═══════════════════════════════════════════════════════════════
// BUAT IKON LEAFLET KUSTOM DENGAN WARNA PER JENIS
// ═══════════════════════════════════════════════════════════════
function createPoskoIcon(jenis) {
    const cfg = poskoIconConfig[jenis] || poskoIconConfig.lainnya;
    const svgIcon = `
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
            <path d="M16 0 C7.16 0 0 7.16 0 16 C0 28 16 42 16 42 C16 42 32 28 32 16 C32 7.16 24.84 0 16 0 Z"
                  fill="${cfg.color}" stroke="white" stroke-width="2"/>
        </svg>
    `;
    return L.divIcon({
        className: '',
        html: `
            <div style="position:relative; text-align:center; width:32px; height:42px;">
                ${svgIcon}
                <i class="fa-solid ${cfg.icon}" style="
                    position:absolute; top:6px; left:50%; transform:translateX(-50%);
                    color:white; font-size:13px;
                "></i>
            </div>
        `,
        iconSize: [32, 42],
        iconAnchor: [16, 42],
        popupAnchor: [0, -42]
    });
}

// ═══════════════════════════════════════════════════════════════
// BUAT IKON MARKER USER
// ═══════════════════════════════════════════════════════════════
function createUserIcon() {
    return L.divIcon({
        className: '',
        html: `
            <div style="
                width:36px; height:36px;
                background:#0d6efd; border:3px solid white;
                border-radius:50%; display:flex; align-items:center; justify-content:center;
                box-shadow: 0 2px 8px rgba(0,0,0,.3);
            ">
                <i class="fa-solid fa-person" style="color:white; font-size:16px;"></i>
            </div>
        `,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -36]
    });
}

// ═══════════════════════════════════════════════════════════════
// INISIALISASI PETA
// ═══════════════════════════════════════════════════════════════
function initMap() {
    // Koordinat center: Medan
    map = L.map('map', {
        center: [3.595, 98.672],
        zoom: 12,
        zoomControl: true
    });

    // Layer OSM (default)
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    });

    // Layer Esri Satellite
    const esriSatellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; <a href="https://www.esri.com/">Esri</a>',
        maxZoom: 19
    });

    // Tambahkan layer default
    osmLayer.addTo(map);

    // Control layer switcher
    const baseLayers = {
        'OpenStreetMap': osmLayer,
        'Satelit': esriSatellite
    };
    L.control.layers(baseLayers).addTo(map);

    // Layer group untuk marker posko
    poskoMarkersLayer = L.layerGroup().addTo(map);

    // Tambahkan legend
    addLegend();

    // Load data posko pertama kali
    loadPoskoData();
}

// ═══════════════════════════════════════════════════════════════
// TAMBAHKAN LEGEND DI POJOK KANAN BAWAH
// ═══════════════════════════════════════════════════════════════
function addLegend() {
    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-legend');
        let html = '<h6>Jenis Posko</h6>';
        for (const [jenis, cfg] of Object.entries(poskoIconConfig)) {
            html += `
                <div class="legend-item">
                    <span class="legend-dot" style="background:${cfg.color};"></span>
                    <span>${cfg.label}</span>
                </div>
            `;
        }
        div.innerHTML = html;
        return div;
    };

    legend.addTo(map);
}

// ═══════════════════════════════════════════════════════════════
// LOAD DATA POSKO DARI API
// ═══════════════════════════════════════════════════════════════
function loadPoskoData(kecamatanId = '') {
    const params = kecamatanId ? `?kecamatan_id=${encodeURIComponent(kecamatanId)}` : '';
    const url = `api/evakuasi.php${params}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data)) {
                allPosko = data;
                renderPoskoMarkers(data);
                renderPoskoList(data);
            } else {
                console.error('Format data tidak valid:', data);
            }
        })
        .catch(err => {
            console.error('Error fetch evakuasi:', err);
            document.getElementById('poskoList').innerHTML = `
                <div class="text-danger small text-center py-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                    Gagal memuat data posko.
                </div>
            `;
        });
}

// ═══════════════════════════════════════════════════════════════
// RENDER MARKER POSKO KE PETA
// ═══════════════════════════════════════════════════════════════
function renderPoskoMarkers(data) {
    poskoMarkersLayer.clearLayers();

    data.forEach(posko => {
        const lat = parseFloat(posko.latitude);
        const lng = parseFloat(posko.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        const icon = createPoskoIcon(posko.jenis);
        const marker = L.marker([lat, lng], { icon });

        // Popup content
        const popupContent = buildPoskoPopup(posko);
        marker.bindPopup(popupContent);

        // Simpan id posko di marker untuk referensi
        marker.poskoId = posko.id;

        marker.addTo(poskoMarkersLayer);
    });

    // Update badge
    document.getElementById('badgePoskoCount').textContent = data.length;
}

// ═══════════════════════════════════════════════════════════════
// BUILD POPUP HTML UNTUK POSKO
// ═══════════════════════════════════════════════════════════════
function buildPoskoPopup(posko) {
    const cfg = poskoIconConfig[posko.jenis] || poskoIconConfig.lainnya;
    return `
        <div style="min-width:220px;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="
                    width:30px; height:30px; border-radius:50%;
                    background:${cfg.color}; display:flex; align-items:center;
                    justify-content:center; flex-shrink:0;
                ">
                    <i class="fa-solid ${cfg.icon}" style="color:white; font-size:13px;"></i>
                </span>
                <strong style="font-size:.9rem;">${escapeHtml(posko.nama)}</strong>
            </div>
            <p class="mb-1 small text-muted">
                <i class="fa-solid fa-location-dot me-1"></i>
                ${escapeHtml(posko.alamat || '-')}
            </p>
            <p class="mb-1 small">
                <i class="fa-solid fa-users me-1"></i>
                <strong>Kapasitas:</strong> ${parseInt(posko.kapasitas || 0).toLocaleString('id-ID')} orang
            </p>
            ${posko.fasilitas ? `
            <p class="mb-1 small">
                <i class="fa-solid fa-list-check me-1"></i>
                ${escapeHtml(posko.fasilitas)}
            </p>
            ` : ''}
            ${posko.kontak ? `
            <p class="mb-2 small">
                <i class="fa-solid fa-phone me-1"></i>
                ${escapeHtml(posko.kontak)}
            </p>
            ` : ''}
            ${userLatLng ? `
            <button onclick="tampilkanRute(${posko.id})"
                    class="btn btn-sm btn-success w-100">
                <i class="fa-solid fa-route me-1"></i>
                Tampilkan Rute ke Sini
            </button>
            ` : `
            <p class="text-muted small mb-0">
                <i class="fa-solid fa-info-circle me-1"></i>
                Aktifkan lokasi Anda untuk melihat rute.
            </p>
            `}
        </div>
    `;
}

// ═══════════════════════════════════════════════════════════════
// RENDER DAFTAR POSKO DI SIDEBAR
// ═══════════════════════════════════════════════════════════════
function renderPoskoList(data) {
    const listEl = document.getElementById('poskoList');

    if (data.length === 0) {
        listEl.innerHTML = `
            <div class="text-muted small text-center py-3">
                <i class="fa-solid fa-circle-info me-1"></i>
                Tidak ada posko di kecamatan ini.
            </div>
        `;
        return;
    }

    listEl.innerHTML = '';

    data.forEach(posko => {
        const cfg = poskoIconConfig[posko.jenis] || poskoIconConfig.lainnya;
        const card = document.createElement('div');
        card.className = 'posko-card';
        card.id = `posko-card-${posko.id}`;

        // Hitung jarak jika lokasi user sudah ada
        const jarakInfo = userLatLng
            ? `<div class="posko-jarak"><i class="fa-solid fa-route me-1"></i>${hitungJarak(userLatLng.lat, userLatLng.lng, posko.latitude, posko.longitude).toFixed(2)} km</div>`
            : '';

        card.innerHTML = `
            <div class="d-flex align-items-start gap-2">
                <span style="
                    width:28px; height:28px; border-radius:50%;
                    background:${cfg.color}; display:flex; align-items:center;
                    justify-content:center; flex-shrink:0; margin-top:2px;
                ">
                    <i class="fa-solid ${cfg.icon}" style="color:white; font-size:12px;"></i>
                </span>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="posko-nama">${escapeHtml(posko.nama)}</div>
                    <div class="posko-meta">${escapeHtml(posko.nama_kecamatan || '-')}</div>
                    <div class="posko-meta">
                        <i class="fa-solid fa-users me-1"></i>
                        Kapasitas: ${parseInt(posko.kapasitas || 0).toLocaleString('id-ID')} orang
                    </div>
                    ${jarakInfo}
                </div>
            </div>
            <button class="btn btn-sm btn-outline-success w-100 mt-2"
                    onclick="tampilkanRute(${posko.id})" id="btn-rute-${posko.id}"
                    ${userLatLng ? '' : 'disabled title="Aktifkan lokasi terlebih dahulu"'}>
                <i class="fa-solid fa-route me-1"></i>
                Tampilkan Rute
            </button>
        `;

        // Klik card -> zoom ke marker
        card.addEventListener('click', (e) => {
            // Cegah trigger saat klik tombol rute
            if (e.target.closest('button')) return;

            const lat = parseFloat(posko.latitude);
            const lng = parseFloat(posko.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], 16);
                // Buka popup marker yang sesuai
                poskoMarkersLayer.eachLayer(layer => {
                    if (layer.poskoId === posko.id) {
                        layer.openPopup();
                    }
                });
            }
        });

        listEl.appendChild(card);
    });
}

// ═══════════════════════════════════════════════════════════════
// AMBIL LOKASI USER (GEOLOCATION)
// ═══════════════════════════════════════════════════════════════
function gunakanLokasiSaya() {
    if (!navigator.geolocation) {
        Swal.fire({
            icon: 'error',
            title: 'Tidak Didukung',
            text: 'Browser Anda tidak mendukung Geolocation.',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Tampilkan loading
    const btn = document.getElementById('btnLokasiSaya');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mencari lokasi...';

    navigator.geolocation.getCurrentPosition(
        // Sukses mendapatkan lokasi
        (position) => {
            userLatLng = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            // Hapus marker user sebelumnya
            if (userMarker) {
                map.removeLayer(userMarker);
            }

            // Tambahkan marker user ke peta
            userMarker = L.marker([userLatLng.lat, userLatLng.lng], {
                icon: createUserIcon()
            });
            userMarker.addTo(map);
            userMarker.bindPopup('<strong>Lokasi Anda</strong>').openPopup();

            // Zoom ke lokasi user
            map.setView([userLatLng.lat, userLatLng.lng], 14);

            // Hitung jarak dan urutkan posko
            const poskoSorted = sortPoskoByJarak(allPosko, userLatLng.lat, userLatLng.lng);

            // Re-render daftar dengan jarak
            renderPoskoList(poskoSorted);
            // Aktifkan tombol rute di popup
            renderPoskoMarkers(allPosko);

            // Tampilkan rute ke posko terdekat
            if (poskoSorted.length > 0) {
                tampilkanRute(poskoSorted[0].id);
            }

            // Update tombol
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Perbarui Lokasi';

            Swal.fire({
                icon: 'success',
                title: 'Lokasi Ditemukan',
                text: 'Menampilkan rute ke posko terdekat.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        },
        // Gagal mendapatkan lokasi
        (error) => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-location-crosshairs me-1"></i> Gunakan Lokasi Saya';

            let pesan = 'Tidak dapat mengambil lokasi Anda.';

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    pesan = 'Izin lokasi ditolak. Mohon aktifkan akses lokasi di pengaturan browser Anda.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    pesan = 'Informasi lokasi tidak tersedia saat ini. Coba lagi beberapa saat.';
                    break;
                case error.TIMEOUT:
                    pesan = 'Permintaan lokasi habis waktu. Periksa koneksi dan coba lagi.';
                    break;
            }

            Swal.fire({
                icon: 'error',
                title: 'Gagal Mendapatkan Lokasi',
                text: pesan,
                confirmButtonText: 'Mengerti'
            });
        },
        // Opsi geolocation
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
}

// ═══════════════════════════════════════════════════════════════
// TAMPILKAN RUTE DARI LOKASI USER KE POSKO
// ═══════════════════════════════════════════════════════════════
function tampilkanRute(poskoId) {
    if (!userLatLng) {
        Swal.fire({
            icon: 'warning',
            title: 'Lokasi Belum Aktif',
            text: 'Klik tombol "Gunakan Lokasi Saya" terlebih dahulu.',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Cari data posko
    const posko = allPosko.find(p => p.id === poskoId);
    if (!posko) return;

    const destLat = parseFloat(posko.latitude);
    const destLng = parseFloat(posko.longitude);

    if (isNaN(destLat) || isNaN(destLng)) return;

    // Hapus rute sebelumnya
    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }

    // Buat rute baru dengan OSRM
    routingControl = L.Routing.control({
        waypoints: [
            L.latLng(userLatLng.lat, userLatLng.lng),
            L.latLng(destLat, destLng)
        ],
        router: L.Routing.osrmv1({
            serviceUrl: 'https://router.project-osrm.org/route/v1'
        }),
        lineOptions: {
            styles: [{ color: '#0d6efd', weight: 4, opacity: 0.8 }],
            addWaypoints: false
        },
        show: false,              // Sembunyikan panel instruksi
        addWaypoints: false,
        draggableWaypoints: false,
        fitSelectedRoutes: true,  // Zoom agar rute muat
        showAlternatives: false
    });

    routingControl.addTo(map);

    // Update panel info rute saat route dihitung
    routingControl.on('routesfound', (e) => {
        const routes = e.routes;
        if (routes && routes.length > 0) {
            const route = routes[0];
            const jarakKm = (route.summary.totalDistance / 1000).toFixed(1);
            const waktuMenit = Math.round(route.summary.totalTime / 60);
            const waktuLabel = waktuMenit >= 60
                ? `${Math.floor(waktuMenit / 60)}j ${waktuMenit % 60}m`
                : `${waktuMenit} mnt`;

            document.getElementById('routeJarak').textContent = jarakKm;
            document.getElementById('routeWaktu').textContent = waktuLabel;
            document.getElementById('routeNamaPosko').textContent = posko.nama;
            document.getElementById('routeInfoPanel').classList.add('show');
        }
    });

    routingControl.on('routingerror', () => {
        Swal.fire({
            icon: 'warning',
            title: 'Rute Tidak Tersedia',
            text: 'Tidak dapat menghitung rute ke posko ini. Pastikan koneksi internet aktif.',
            confirmButtonText: 'OK'
        });
    });

    // Tandai posko aktif di sidebar
    document.querySelectorAll('.posko-card').forEach(el => el.classList.remove('posko-active'));
    const activeCard = document.getElementById(`posko-card-${poskoId}`);
    if (activeCard) {
        activeCard.classList.add('posko-active');
        activeCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// ═══════════════════════════════════════════════════════════════
// HITUNG JARAK HAVERSINE (km)
// ═══════════════════════════════════════════════════════════════
function hitungJarak(lat1, lng1, lat2, lng2) {
    const R = 6371; // Radius bumi dalam km
    const toRad = deg => deg * (Math.PI / 180);

    const dLat = toRad(parseFloat(lat2) - parseFloat(lat1));
    const dLng = toRad(parseFloat(lng2) - parseFloat(lng1));

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(parseFloat(lat1))) *
              Math.cos(toRad(parseFloat(lat2))) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

// ═══════════════════════════════════════════════════════════════
// URUTKAN POSKO BERDASARKAN JARAK (TERDEKAT DULU)
// ═══════════════════════════════════════════════════════════════
function sortPoskoByJarak(poskoList, userLat, userLng) {
    return [...poskoList]
        .map(p => ({
            ...p,
            jarak: hitungJarak(userLat, userLng, p.latitude, p.longitude)
        }))
        .sort((a, b) => a.jarak - b.jarak);
}

// ═══════════════════════════════════════════════════════════════
// HELPER: ESCAPE HTML
// ═══════════════════════════════════════════════════════════════
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// ═══════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi peta
    initMap();

    // Tombol Gunakan Lokasi Saya
    document.getElementById('btnLokasiSaya').addEventListener('click', gunakanLokasiSaya);

    // Tombol tutup panel rute
    document.getElementById('btnTutupRute').addEventListener('click', () => {
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
        document.getElementById('routeInfoPanel').classList.remove('show');
        document.querySelectorAll('.posko-card').forEach(el => el.classList.remove('posko-active'));
    });

    // Filter kecamatan
    document.getElementById('filterKecamatanEvak').addEventListener('change', function() {
        loadPoskoData(this.value);
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
