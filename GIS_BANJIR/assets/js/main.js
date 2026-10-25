/**
 * JavaScript Utama - SIG Rawan Banjir Kota Medan
 * File: assets/js/main.js
 *
 * Berisi:
 * - Dark Mode toggle & persistence
 * - Navbar scroll effect
 * - Counter animation
 * - Toast notification
 * - Skeleton loading
 * - CSRF helper
 * - Fetch wrapper
 * - Form helpers
 * - Image preview
 * - Slug generator
 * - Export CSV
 */

'use strict';

/* ============================================================
   DARK MODE
   Menggunakan data-theme attribute pada <html>
   State disimpan di localStorage
   ============================================================ */

/**
 * Terapkan tema ke dokumen dan sinkronkan ikon tombol.
 * @param {string} theme - 'light' atau 'dark'
 */
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem('theme', theme);

    const icon = document.getElementById('darkModeIcon');
    if (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
}

/**
 * Baca preferensi dari localStorage atau media query dan terapkan saat load.
 * Dipanggil sebelum DOMContentLoaded agar tidak ada flash (inline di header).
 * Fungsi ini tetap tersedia untuk modul lain yang membutuhkan inisialisasi ulang.
 */
function initDarkMode() {
    const saved = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);
}

/**
 * Inisialisasi tombol toggle dark mode.
 * Harus dipanggil setelah DOM siap.
 */
function initDarkModeToggle() {
    const btn = document.getElementById('darkModeToggle');
    if (!btn) return;

    btn.addEventListener('click', function () {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });
}

/* ============================================================
   NAVBAR SCROLL EFFECT
   Menambahkan class 'scrolled' saat scroll melebihi 50px
   ============================================================ */

function initNavbarScroll() {
    const navbar = document.getElementById('mainNavbar');
    if (!navbar) return;

    function handleScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    // Cek posisi awal (misal jika halaman di-refresh di tengah)
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
}

/* ============================================================
   COUNTER ANIMATION
   Menggunakan IntersectionObserver untuk trigger saat elemen
   masuk viewport. Animasi angka dari 0 ke data-target.
   ============================================================ */

/**
 * Easing: ease-out cubic
 * @param {number} t - progress 0..1
 * @returns {number} - eased value
 */
function easeOutCubic(t) {
    return 1 - Math.pow(1 - t, 3);
}

/**
 * Animasikan satu elemen counter.
 * @param {HTMLElement} el - Elemen yang berisi data-target
 * @param {number} duration - Durasi animasi (ms)
 */
function animateCounter(el, duration = 2000) {
    const target = parseInt(el.getAttribute('data-target'), 10) || 0;
    const start = performance.now();

    function step(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = easeOutCubic(progress);
        const current = Math.round(eased * target);

        el.textContent = current.toLocaleString('id-ID');

        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            el.textContent = target.toLocaleString('id-ID');
        }
    }

    requestAnimationFrame(step);
}

/**
 * Inisialisasi counter animation dengan IntersectionObserver.
 * Target elemen: .stat-counter atau elemen dengan data-target.
 */
function initCounterAnimation() {
    const counters = document.querySelectorAll('[data-target]');
    if (!counters.length) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(function (counter) {
        observer.observe(counter);
    });
}

/* ============================================================
   TOAST NOTIFICATION
   showToast(message, type='success')
   type: success | error | warning | info
   Auto-dismiss setelah 3 detik
   ============================================================ */

/**
 * Pastikan container toast ada di DOM.
 * @returns {HTMLElement}
 */
function getToastContainer() {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Tampilkan notifikasi toast.
 * @param {string} message - Pesan yang ditampilkan
 * @param {string} [type='success'] - Tipe: success | error | warning | info
 * @param {number} [duration=3000] - Durasi auto-dismiss (ms)
 */
function showToast(message, type = 'success', duration = 3000) {
    const icons = {
        success: 'fa-solid fa-circle-check',
        error:   'fa-solid fa-circle-xmark',
        warning: 'fa-solid fa-triangle-exclamation',
        info:    'fa-solid fa-circle-info',
    };

    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = 'toast-item toast-' + type;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');

    toast.innerHTML =
        '<span class="toast-icon"><i class="' + (icons[type] || icons.info) + '"></i></span>' +
        '<span class="toast-message">' + escapeHtml(message) + '</span>';

    container.appendChild(toast);

    // Auto-dismiss
    setTimeout(function () {
        toast.style.animation = 'slideIn 0.3s ease-out reverse';
        toast.addEventListener('animationend', function () {
            toast.remove();
        }, { once: true });
    }, duration);
}

/* ============================================================
   SKELETON LOADING
   showSkeleton(containerId, count)
   hideSkeleton(containerId)
   ============================================================ */

/**
 * Tampilkan skeleton placeholder dalam container.
 * @param {string} containerId - ID elemen container
 * @param {number} [count=3] - Jumlah skeleton item
 */
function showSkeleton(containerId, count = 3) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Simpan konten asli sebagai backup
    container.dataset.originalContent = container.innerHTML;

    let html = '';
    for (let i = 0; i < count; i++) {
        html +=
            '<div class="skeleton-card">' +
                '<div class="skeleton skeleton-text wide mb-2"></div>' +
                '<div class="skeleton skeleton-text medium mb-2"></div>' +
                '<div class="skeleton skeleton-text short"></div>' +
            '</div>';
    }

    container.innerHTML = html;
}

/**
 * Hapus skeleton dan kembalikan konten asli (jika ada).
 * @param {string} containerId - ID elemen container
 */
function hideSkeleton(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (container.dataset.originalContent !== undefined) {
        container.innerHTML = container.dataset.originalContent;
        delete container.dataset.originalContent;
    }
}

/* ============================================================
   CSRF HELPER
   Ambil token dari meta tag atau hidden input
   ============================================================ */

/**
 * Ambil CSRF token dari halaman.
 * Urutan pencarian:
 * 1. Meta tag: <meta name="csrf-token" content="...">
 * 2. Hidden input: <input name="csrf_token" value="...">
 * @returns {string|null}
 */
function getCsrfToken() {
    // Dari meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) return metaTag.getAttribute('content');

    // Dari hidden input
    const hiddenInput = document.querySelector('input[name="csrf_token"]');
    if (hiddenInput) return hiddenInput.value;

    return null;
}

/* ============================================================
   FETCH WRAPPER
   apiFetch(url, options) - async function
   Default: Content-Type JSON, error handling lengkap
   ============================================================ */

/**
 * Wrapper fetch dengan header default dan error handling.
 * @param {string} url - URL endpoint
 * @param {Object} [options={}] - opsi fetch (method, body, dll)
 * @returns {Promise<any>} - data JSON yang di-parse
 * @throws {Error} - network error atau HTTP error
 */
async function apiFetch(url, options = {}) {
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const csrfToken = getCsrfToken();
    if (csrfToken) {
        defaultHeaders['X-CSRF-Token'] = csrfToken;
    }

    const config = Object.assign({}, options, {
        headers: Object.assign({}, defaultHeaders, options.headers || {}),
    });

    let response;
    try {
        response = await fetch(url, config);
    } catch (networkError) {
        throw new Error('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
    }

    if (!response.ok) {
        let errorMessage = 'Permintaan gagal: HTTP ' + response.status;
        try {
            const errorData = await response.json();
            if (errorData && errorData.message) {
                errorMessage = errorData.message;
            }
        } catch (_) {
            // Abaikan jika response bukan JSON
        }
        throw new Error(errorMessage);
    }

    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        return response.json();
    }

    return response.text();
}

/* ============================================================
   FORM HELPERS
   ============================================================ */

/**
 * Validasi semua input required dalam form.
 * @param {HTMLFormElement} formEl - Elemen form
 * @returns {boolean} - true jika valid, false jika ada yang kosong
 */
function validateRequired(formEl) {
    if (!formEl) return false;

    const inputs = formEl.querySelectorAll('[required]');
    let valid = true;

    inputs.forEach(function (input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return valid;
}

/**
 * Reset semua input dalam form.
 * @param {HTMLFormElement} formEl - Elemen form
 */
function resetForm(formEl) {
    if (!formEl) return;
    formEl.reset();

    // Hapus class validasi Bootstrap
    formEl.querySelectorAll('.is-invalid, .is-valid').forEach(function (el) {
        el.classList.remove('is-invalid', 'is-valid');
    });
}

/* ============================================================
   IMAGE PREVIEW
   previewImage(inputEl, previewEl)
   Menggunakan FileReader untuk preview sebelum upload
   ============================================================ */

/**
 * Tampilkan preview gambar dari file input.
 * @param {HTMLInputElement} inputEl - Elemen <input type="file">
 * @param {HTMLImageElement} previewEl - Elemen <img> untuk preview
 */
function previewImage(inputEl, previewEl) {
    if (!inputEl || !previewEl) return;

    inputEl.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showToast('File yang dipilih bukan gambar.', 'error');
            return;
        }

        const maxSize = 5 * 1024 * 1024; // 5 MB
        if (file.size > maxSize) {
            showToast('Ukuran gambar maksimal 5 MB.', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            previewEl.src = e.target.result;
            previewEl.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}

/* ============================================================
   SLUG GENERATOR
   generateSlug(text) - untuk URL berita admin
   ============================================================ */

/**
 * Konversi teks judul menjadi slug URL-friendly.
 * @param {string} text - Judul artikel
 * @returns {string} - slug
 */
function generateSlug(text) {
    if (!text) return '';

    // Peta karakter Indonesia ke ASCII
    const charMap = {
        'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a', 'å': 'a',
        'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e',
        'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i',
        'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o',
        'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'u',
        'ñ': 'n', 'ç': 'c',
    };

    return text
        .toLowerCase()
        .replace(/./g, function (c) { return charMap[c] || c; })
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

/* ============================================================
   EXPORT CSV
   exportTableToCSV(tableId, filename)
   ============================================================ */

/**
 * Ekspor tabel HTML ke file CSV.
 * @param {string} tableId - ID elemen <table>
 * @param {string} [filename='export.csv'] - Nama file yang diunduh
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) {
        showToast('Tabel tidak ditemukan.', 'error');
        return;
    }

    const rows = table.querySelectorAll('tr');
    const csvRows = [];

    rows.forEach(function (row) {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(function (cell) {
            // Bersihkan teks: hapus leading/trailing space, escape kutip ganda
            const text = cell.innerText.trim().replace(/"/g, '""');
            return '"' + text + '"';
        });
        csvRows.push(rowData.join(','));
    });

    const csvString = '\uFEFF' + csvRows.join('\n'); // BOM untuk UTF-8 di Excel
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showToast('Data berhasil diekspor ke ' + filename, 'success');
}

/* ============================================================
   HELPER: ESCAPE HTML
   ============================================================ */

/**
 * Escape karakter HTML untuk mencegah XSS.
 * @param {string} text
 * @returns {string}
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text || '');
    return div.innerHTML;
}

/* ============================================================
   INISIALISASI SAAT DOM SIAP
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
    initDarkMode();
    initDarkModeToggle();
    initNavbarScroll();
    initCounterAnimation();

    // Image preview: auto-bind jika elemen ada
    const imgInput = document.getElementById('inputGambar');
    const imgPreview = document.getElementById('previewGambar');
    if (imgInput && imgPreview) {
        previewImage(imgInput, imgPreview);
    }

    // Slug generator: auto-bind untuk field judul berita admin
    const judulInput = document.getElementById('inputJudul');
    const slugInput = document.getElementById('inputSlug');
    if (judulInput && slugInput) {
        judulInput.addEventListener('input', function () {
            slugInput.value = generateSlug(this.value);
        });
    }

    // Export CSV: auto-bind tombol dengan data-export-table
    document.querySelectorAll('[data-export-table]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tableId = btn.getAttribute('data-export-table');
            const filename = btn.getAttribute('data-filename') || 'data-export.csv';
            exportTableToCSV(tableId, filename);
        });
    });
});

/* ============================================================
   EXPORT GLOBAL (agar bisa dipanggil dari HTML inline)
   ============================================================ */
window.SIG = {
    applyTheme,
    showToast,
    showSkeleton,
    hideSkeleton,
    getCsrfToken,
    apiFetch,
    validateRequired,
    resetForm,
    previewImage,
    generateSlug,
    exportTableToCSV,
    escapeHtml,
};
