/**
 * peta.js — Logika Leaflet untuk halaman Peta Rawan Banjir
 * File: assets/js/peta.js
 *
 * Menangani inisialisasi peta, render marker, filter lokasi,
 * dan daftar lokasi di sidebar.
 *
 * Dependensi (harus dimuat sebelum file ini):
 *   - Leaflet.js  (https://unpkg.com/leaflet@1.9.4/dist/leaflet.js)
 *   - Bootstrap 5 (sudah dimuat via footer.php)
 */

(function () {
    'use strict';

    // ═══════════════════════════════════════════════════════════
    // VARIABEL GLOBAL MODULE
    // ═══════════════════════════════════════════════════════════
    var map = null;
    var markersLayer = null;
    var allData = [];

    // Warna marker per tingkat bahaya
    var COLOR_MAP = {
        rendah: '#28a745',
        sedang: '#ffc107',
        tinggi: '#dc3545'
    };

    // ═══════════════════════════════════════════════════════════
    // INISIALISASI PETA
    // ═══════════════════════════════════════════════════════════
    function initMap() {
        // Koordinat center: pusat Kota Medan
        map = L.map('map', {
            center: [3.595, 98.672],
            zoom: 12,
            zoomControl: true
        });

        // Layer OpenStreetMap (default)
        var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        });

        // Layer Esri Satellite
        var esriSatellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                attribution: '&copy; <a href="https://www.esri.com/">Esri</a>',
                maxZoom: 19
            }
        );

        osmLayer.addTo(map);

        // Layer switcher
        L.control.layers({
            'OpenStreetMap': osmLayer,
            'Satelit': esriSatellite
        }).addTo(map);

        // Layer group untuk semua marker
        markersLayer = L.layerGroup().addTo(map);

        addLegend();
        loadLokasiData();
    }

    // ═══════════════════════════════════════════════════════════
    // LEGEND DI POJOK KANAN BAWAH
    // ═══════════════════════════════════════════════════════════
    function addLegend() {
        var legend = L.control({ position: 'bottomright' });

        legend.onAdd = function () {
            var div = L.DomUtil.create('div', 'leaflet-legend');
            div.innerHTML =
                '<h6>Tingkat Bahaya</h6>' +
                '<div class="legend-item"><span class="legend-dot" style="background:#28a745;"></span><span>Rendah</span></div>' +
                '<div class="legend-item"><span class="legend-dot" style="background:#ffc107;"></span><span>Sedang</span></div>' +
                '<div class="legend-item"><span class="legend-dot" style="background:#dc3545;"></span><span>Tinggi</span></div>';
            return div;
        };

        legend.addTo(map);
    }

    // ═══════════════════════════════════════════════════════════
    // LOAD DATA DARI API
    // ═══════════════════════════════════════════════════════════
    function loadLokasiData(params) {
        params = params || {};
        var queryString = new URLSearchParams(params).toString();
        var url = 'api/lokasi.php' + (queryString ? '?' + queryString : '');

        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (Array.isArray(data)) {
                    allData = data;
                    renderMarkers(data);
                    renderLokasiList(data);
                } else {
                    console.error('Format data tidak valid:', data);
                    showError('Gagal memuat data lokasi.');
                }
            })
            .catch(function (err) {
                console.error('Error fetch lokasi:', err);
                showError('Tidak dapat memuat data dari server.');
            });
    }

    // ═══════════════════════════════════════════════════════════
    // RENDER MARKER KE PETA
    // ═══════════════════════════════════════════════════════════
    function renderMarkers(data) {
        markersLayer.clearLayers();

        data.forEach(function (item) {
            var lat = parseFloat(item.latitude);
            var lng = parseFloat(item.longitude);

            if (isNaN(lat) || isNaN(lng)) return;

            var color = COLOR_MAP[item.tingkat] || '#6c757d';
            var badgeClass = item.tingkat === 'rendah' ? 'success' :
                             item.tingkat === 'sedang' ? 'warning' : 'danger';

            var marker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            });

            var popupContent =
                '<div style="min-width:200px;">' +
                    '<h6 class="fw-bold mb-2">' + escapeHtml(item.nama) + '</h6>' +
                    '<p class="mb-1 small text-muted">' +
                        '<i class="fa-solid fa-map-pin me-1"></i>' +
                        escapeHtml(item.nama_kecamatan || '-') + ' / ' + escapeHtml(item.nama_kelurahan || '-') +
                    '</p>' +
                    '<span class="badge bg-' + badgeClass + ' mb-2">' +
                        (item.tingkat ? item.tingkat.toUpperCase() : 'N/A') +
                    '</span>' +
                    '<hr class="my-2">' +
                    '<p class="mb-1 small"><strong>Ketinggian Air:</strong> ' + (item.ketinggian_air_cm || 0) + ' cm</p>' +
                    '<p class="mb-1 small"><strong>Penyebab:</strong> ' + escapeHtml(item.penyebab || '-') + '</p>' +
                    (item.tanggal_kejadian_terakhir
                        ? '<p class="mb-0 small text-muted"><i class="fa-regular fa-calendar me-1"></i>Kejadian terakhir: ' + formatDateID(item.tanggal_kejadian_terakhir) + '</p>'
                        : '') +
                '</div>';

            marker.bindPopup(popupContent);
            marker.addTo(markersLayer);

            marker.on('click', function () {
                map.setView([lat, lng], 15);
            });
        });

        var badge = document.getElementById('badgeLokasiCount');
        if (badge) badge.textContent = data.length;
    }

    // ═══════════════════════════════════════════════════════════
    // RENDER DAFTAR LOKASI DI SIDEBAR
    // ═══════════════════════════════════════════════════════════
    function renderLokasiList(data) {
        var listEl = document.getElementById('lokasiList');
        if (!listEl) return;

        if (data.length === 0) {
            listEl.innerHTML =
                '<li class="text-muted small text-center py-3">' +
                    '<i class="fa-solid fa-circle-info me-1"></i>' +
                    'Tidak ada lokasi yang sesuai filter.' +
                '</li>';
            return;
        }

        listEl.innerHTML = '';

        data.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'lokasi-list-item';

            var badgeClass = item.tingkat === 'rendah' ? 'success' :
                             item.tingkat === 'sedang' ? 'warning' : 'danger';

            li.innerHTML =
                '<div class="lokasi-nama">' + escapeHtml(item.nama) + '</div>' +
                '<div class="lokasi-sub">' + escapeHtml(item.nama_kecamatan || '-') + '</div>' +
                '<span class="badge bg-' + badgeClass + ' mt-1" style="font-size:.7rem;">' +
                    (item.tingkat ? item.tingkat.toUpperCase() : 'N/A') +
                '</span>';

            li.addEventListener('click', function () {
                var lat = parseFloat(item.latitude);
                var lng = parseFloat(item.longitude);
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.setView([lat, lng], 16);
                    markersLayer.eachLayer(function (layer) {
                        var pos = layer.getLatLng();
                        if (pos && pos.lat === lat && pos.lng === lng) {
                            layer.openPopup();
                        }
                    });
                }
            });

            listEl.appendChild(li);
        });
    }

    // ═══════════════════════════════════════════════════════════
    // FUNGSI FILTER PETA
    // ═══════════════════════════════════════════════════════════
    function filterMap() {
        var kecamatanId = document.getElementById('filterKecamatan').value;
        var tingkat     = document.getElementById('filterTingkat').value;
        var tahun       = document.getElementById('filterTahun').value;
        var search      = document.getElementById('filterSearch').value.trim();

        var params = {};
        if (kecamatanId) params.kecamatan_id = kecamatanId;
        if (tingkat)     params.tingkat      = tingkat;
        if (tahun)       params.tahun        = tahun;
        if (search)      params.search       = search;

        loadLokasiData(params);
    }

    // ═══════════════════════════════════════════════════════════
    // FUNGSI RESET FILTER
    // ═══════════════════════════════════════════════════════════
    function resetFilter() {
        var form = document.getElementById('formFilterPeta');
        if (form) form.reset();
        loadLokasiData();
    }

    // ═══════════════════════════════════════════════════════════
    // HELPER: ESCAPE HTML
    // ═══════════════════════════════════════════════════════════
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // ═══════════════════════════════════════════════════════════
    // HELPER: FORMAT TANGGAL INDONESIA
    // ═══════════════════════════════════════════════════════════
    function formatDateID(dateStr) {
        if (!dateStr) return '-';
        var date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    // ═══════════════════════════════════════════════════════════
    // HELPER: TAMPILKAN ERROR DI SIDEBAR
    // ═══════════════════════════════════════════════════════════
    function showError(message) {
        var listEl = document.getElementById('lokasiList');
        if (!listEl) return;
        listEl.innerHTML =
            '<li class="text-danger small text-center py-3">' +
                '<i class="fa-solid fa-triangle-exclamation me-1"></i>' +
                escapeHtml(message) +
            '</li>';
    }

    // ═══════════════════════════════════════════════════════════
    // EVENT LISTENERS — dijalankan setelah DOM siap
    // ═══════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', function () {
        // Hanya jalankan jika elemen #map ada di halaman
        if (!document.getElementById('map')) return;

        initMap();

        var btnApply = document.getElementById('btnApplyFilter');
        if (btnApply) btnApply.addEventListener('click', filterMap);

        var btnReset = document.getElementById('btnResetFilter');
        if (btnReset) btnReset.addEventListener('click', resetFilter);

        var searchInput = document.getElementById('filterSearch');
        if (searchInput) {
            searchInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterMap();
                }
            });
        }
    });

})();
