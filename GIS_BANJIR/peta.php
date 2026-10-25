<?php
/**
 * Halaman Peta Rawan Banjir
 * File: peta.php
 *
 * Menampilkan peta interaktif dengan marker lokasi rawan banjir.
 * Sidebar kiri berisi filter dan daftar lokasi.
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
    error_log('Database error di peta.php: ' . $e->getMessage());
}

// Set page title
$pageTitle = 'Peta Rawan Banjir';

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- ================================================================
     LEAFLET CSS
     ================================================================ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">

<style>
/* Override untuk z-index modal Leaflet agar tidak tertutup navbar */
.leaflet-popup-pane { z-index: 700; }
.leaflet-control-layers { z-index: 800; }
</style>

<!-- ================================================================
     LAYOUT: SIDEBAR + MAP CONTAINER
     ================================================================ -->
<div class="map-page-wrapper">

    <!-- ═══════════════════════════════════════════════════════════
         SIDEBAR KIRI: FILTER & DAFTAR LOKASI
         ═══════════════════════════════════════════════════════════ -->
    <aside class="map-sidebar" id="mapSidebar">

        <!-- Header Sidebar -->
        <div class="sidebar-header">
            <h5 class="mb-0 fw-bold">
                <i class="fa-solid fa-filter me-2 text-primary"></i>
                Filter Peta
            </h5>
        </div>

        <!-- Body Sidebar -->
        <div class="sidebar-body">

            <!-- Form Filter -->
            <form id="formFilterPeta" class="mb-3">

                <!-- Filter Kecamatan -->
                <div class="mb-3">
                    <label for="filterKecamatan" class="form-label small fw-semibold">
                        <i class="fa-solid fa-map me-1"></i> Kecamatan
                    </label>
                    <select class="form-select form-select-sm" id="filterKecamatan" name="kecamatan_id">
                        <option value="">Semua Kecamatan</option>
                        <?php foreach ($daftarKecamatan as $kec): ?>
                            <option value="<?= htmlspecialchars((string)$kec['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Tingkat -->
                <div class="mb-3">
                    <label for="filterTingkat" class="form-label small fw-semibold">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Tingkat Bahaya
                    </label>
                    <select class="form-select form-select-sm" id="filterTingkat" name="tingkat">
                        <option value="">Semua Tingkat</option>
                        <option value="rendah">Rendah</option>
                        <option value="sedang">Sedang</option>
                        <option value="tinggi">Tinggi</option>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="mb-3">
                    <label for="filterTahun" class="form-label small fw-semibold">
                        <i class="fa-regular fa-calendar me-1"></i> Tahun Kejadian
                    </label>
                    <select class="form-select form-select-sm" id="filterTahun" name="tahun">
                        <option value="">Semua Tahun</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                        <option value="2019">2019</option>
                    </select>
                </div>

                <!-- Filter Search -->
                <div class="mb-3">
                    <label for="filterSearch" class="form-label small fw-semibold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Pencarian
                    </label>
                    <input type="text" class="form-control form-control-sm" id="filterSearch"
                           name="search" placeholder="Cari nama lokasi...">
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="d-flex gap-2">
                    <button type="button" id="btnApplyFilter" class="btn btn-primary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i> Terapkan Filter
                    </button>
                    <button type="button" id="btnResetFilter" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                </div>

            </form>

            <hr>

            <!-- Judul Daftar Lokasi -->
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0 fw-semibold small">Daftar Lokasi</h6>
                <span class="badge bg-secondary" id="badgeLokasiCount">0</span>
            </div>

            <!-- Daftar Lokasi (diisi oleh JavaScript) -->
            <ul class="lokasi-list" id="lokasiList">
                <li class="text-muted small text-center py-3">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Memuat data...
                </li>
            </ul>

        </div><!-- /.sidebar-body -->

    </aside><!-- /.map-sidebar -->

    <!-- ═══════════════════════════════════════════════════════════
         MAP CONTAINER
         ═══════════════════════════════════════════════════════════ -->
    <div class="map-container">
        <div id="map"></div>
    </div>

</div><!-- /.map-page-wrapper -->

<!-- ================================================================
     LEAFLET JS
     ================================================================ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<!-- ================================================================
     SCRIPT PETA INTERAKTIF
     ================================================================ -->
<script>
// ═══════════════════════════════════════════════════════════════
// VARIABEL GLOBAL
// ═══════════════════════════════════════════════════════════════
let map = null;
let markersLayer = null;
let allData = [];

// ═══════════════════════════════════════════════════════════════
// INISIALISASI PETA
// ═══════════════════════════════════════════════════════════════
function initMap() {
    // Koordinat center: Medan (3.595, 98.672)
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

    // Layer group untuk marker
    markersLayer = L.layerGroup().addTo(map);

    // Tambahkan legend
    addLegend();

    // Load data pertama kali
    loadLokasiData();
}

// ═══════════════════════════════════════════════════════════════
// TAMBAHKAN LEGEND DI POJOK KANAN BAWAH
// ═══════════════════════════════════════════════════════════════
function addLegend() {
    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function() {
        const div = L.DomUtil.create('div', 'leaflet-legend');
        div.innerHTML = `
            <h6>Tingkat Bahaya</h6>
            <div class="legend-item">
                <span class="legend-dot" style="background:#28a745;"></span>
                <span>Rendah</span>
            </div>
            <div class="legend-item">
                <span class="legend-dot" style="background:#ffc107;"></span>
                <span>Sedang</span>
            </div>
            <div class="legend-item">
                <span class="legend-dot" style="background:#dc3545;"></span>
                <span>Tinggi</span>
            </div>
        `;
        return div;
    };

    legend.addTo(map);
}

// ═══════════════════════════════════════════════════════════════
// LOAD DATA LOKASI DARI API
// ═══════════════════════════════════════════════════════════════
function loadLokasiData(params = {}) {
    // Bangun query string
    const queryString = new URLSearchParams(params).toString();
    const url = `api/lokasi.php${queryString ? '?' + queryString : ''}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (Array.isArray(data)) {
                allData = data;
                renderMarkers(data);
                renderLokasiList(data);
            } else {
                console.error('Format data tidak valid:', data);
                showError('Gagal memuat data lokasi.');
            }
        })
        .catch(err => {
            console.error('Error fetch lokasi:', err);
            showError('Tidak dapat memuat data dari server.');
        });
}

// ═══════════════════════════════════════════════════════════════
// RENDER MARKER KE PETA
// ═══════════════════════════════════════════════════════════════
function renderMarkers(data) {
    // Hapus marker lama
    markersLayer.clearLayers();

    // Map warna berdasarkan tingkat
    const colorMap = {
        'rendah': '#28a745',
        'sedang': '#ffc107',
        'tinggi': '#dc3545'
    };

    data.forEach(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        const color = colorMap[item.tingkat] || '#6c757d';

        // Buat circle marker
        const marker = L.circleMarker([lat, lng], {
            radius: 8,
            fillColor: color,
            color: '#fff',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        });

        // Badge tingkat
        const badgeClass = item.tingkat === 'rendah' ? 'success' :
                           item.tingkat === 'sedang' ? 'warning' : 'danger';

        // Popup content
        const popupContent = `
            <div style="min-width:200px;">
                <h6 class="fw-bold mb-2">${escapeHtml(item.nama)}</h6>
                <p class="mb-1 small text-muted">
                    <i class="fa-solid fa-map-pin me-1"></i>
                    ${escapeHtml(item.nama_kecamatan || '-')} / ${escapeHtml(item.nama_kelurahan || '-')}
                </p>
                <span class="badge bg-${badgeClass} mb-2">
                    ${item.tingkat ? item.tingkat.toUpperCase() : 'N/A'}
                </span>
                <hr class="my-2">
                <p class="mb-1 small">
                    <strong>Ketinggian Air:</strong> ${item.ketinggian_air_cm || 0} cm
                </p>
                <p class="mb-1 small">
                    <strong>Penyebab:</strong> ${escapeHtml(item.penyebab || '-')}
                </p>
                ${item.tanggal_kejadian_terakhir ? `
                <p class="mb-0 small text-muted">
                    <i class="fa-regular fa-calendar me-1"></i>
                    Kejadian terakhir: ${formatDateID(item.tanggal_kejadian_terakhir)}
                </p>
                ` : ''}
            </div>
        `;

        marker.bindPopup(popupContent);
        marker.addTo(markersLayer);

        // Event klik marker -> zoom & buka popup
        marker.on('click', () => {
            map.setView([lat, lng], 15);
        });
    });

    // Update badge count
    document.getElementById('badgeLokasiCount').textContent = data.length;
}

// ═══════════════════════════════════════════════════════════════
// RENDER DAFTAR LOKASI DI SIDEBAR
// ═══════════════════════════════════════════════════════════════
function renderLokasiList(data) {
    const listEl = document.getElementById('lokasiList');

    if (data.length === 0) {
        listEl.innerHTML = `
            <li class="text-muted small text-center py-3">
                <i class="fa-solid fa-circle-info me-1"></i>
                Tidak ada lokasi yang sesuai filter.
            </li>
        `;
        return;
    }

    listEl.innerHTML = '';

    data.forEach(item => {
        const li = document.createElement('li');
        li.className = 'lokasi-list-item';

        const badgeClass = item.tingkat === 'rendah' ? 'success' :
                           item.tingkat === 'sedang' ? 'warning' : 'danger';

        li.innerHTML = `
            <div class="lokasi-nama">${escapeHtml(item.nama)}</div>
            <div class="lokasi-sub">
                ${escapeHtml(item.nama_kecamatan || '-')}
            </div>
            <span class="badge bg-${badgeClass} mt-1" style="font-size:.7rem;">
                ${item.tingkat ? item.tingkat.toUpperCase() : 'N/A'}
            </span>
        `;

        // Klik item -> zoom ke lokasi di peta
        li.addEventListener('click', () => {
            const lat = parseFloat(item.latitude);
            const lng = parseFloat(item.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], 16);
                // Buka popup marker
                markersLayer.eachLayer(layer => {
                    const markerLatLng = layer.getLatLng();
                    if (markerLatLng.lat === lat && markerLatLng.lng === lng) {
                        layer.openPopup();
                    }
                });
            }
        });

        listEl.appendChild(li);
    });
}

// ═══════════════════════════════════════════════════════════════
// FUNGSI FILTER PETA
// ═══════════════════════════════════════════════════════════════
function filterMap() {
    const kecamatanId = document.getElementById('filterKecamatan').value;
    const tingkat = document.getElementById('filterTingkat').value;
    const tahun = document.getElementById('filterTahun').value;
    const search = document.getElementById('filterSearch').value.trim();

    const params = {};
    if (kecamatanId) params.kecamatan_id = kecamatanId;
    if (tingkat) params.tingkat = tingkat;
    if (tahun) params.tahun = tahun;
    if (search) params.search = search;

    loadLokasiData(params);
}

// ═══════════════════════════════════════════════════════════════
// FUNGSI RESET FILTER
// ═══════════════════════════════════════════════════════════════
function resetFilter() {
    document.getElementById('formFilterPeta').reset();
    loadLokasiData();
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
// HELPER: FORMAT DATE INDONESIA
// ═══════════════════════════════════════════════════════════════
function formatDateID(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
}

// ═══════════════════════════════════════════════════════════════
// HELPER: TAMPILKAN ERROR
// ═══════════════════════════════════════════════════════════════
function showError(message) {
    const listEl = document.getElementById('lokasiList');
    listEl.innerHTML = `
        <li class="text-danger small text-center py-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            ${escapeHtml(message)}
        </li>
    `;
}

// ═══════════════════════════════════════════════════════════════
// EVENT LISTENERS
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi peta
    initMap();

    // Tombol Apply Filter
    document.getElementById('btnApplyFilter').addEventListener('click', filterMap);

    // Tombol Reset Filter
    document.getElementById('btnResetFilter').addEventListener('click', resetFilter);

    // Enter key pada search
    document.getElementById('filterSearch').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            filterMap();
        }
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
