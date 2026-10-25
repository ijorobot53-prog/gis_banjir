<?php
/**
 * Footer Admin Panel
 * File: admin/includes/footer.php
 *
 * Menutup tag <main>, memuat script JS, dan menutup <body> dan <html>.
 */
?>
</main><!-- /.admin-main -->

<!-- ====== JAVASCRIPT LIBRARIES ====== -->

<!-- Bootstrap 5.3 Bundle (termasuk Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Leaflet JS (untuk map picker) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js (untuk dashboard) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<!-- SweetAlert2 JS (untuk konfirmasi hapus) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- ====== GLOBAL ADMIN SCRIPT ====== -->
<script>
/**
 * Script global admin: konfirmasi hapus dengan SweetAlert2,
 * helper tampilkan flash message, inisialisasi tooltips Bootstrap.
 */

// Inisialisasi semua tooltip Bootstrap
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
});

/**
 * Konfirmasi hapus data menggunakan SweetAlert2.
 * Dipanggil dari tombol hapus di tabel, mengirim form POST hapus.
 *
 * @param {string} formId - ID form yang akan di-submit
 * @param {string} namaData - Nama data yang akan dihapus (untuk pesan konfirmasi)
 */
function konfirmasiHapus(formId, namaData) {
    Swal.fire({
        title: 'Hapus Data?',
        html: 'Data <strong>' + namaData + '</strong> akan dihapus permanen.<br>Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i>Ya, Hapus',
        cancelButtonText: 'Batal',
        focusCancel: true
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
}

/**
 * Tampilkan toast notifikasi.
 *
 * @param {string} tipe - 'success' | 'error' | 'warning' | 'info'
 * @param {string} pesan - Isi pesan
 */
function tampilkanToast(tipe, pesan) {
    const ikonMap = {
        success: 'fa-circle-check',
        error:   'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info:    'fa-circle-info'
    };
    const warnaMap = {
        success: '#10b981',
        error:   '#ef4444',
        warning: '#f59e0b',
        info:    '#3b82f6'
    };

    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipe,
        title: pesan,
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        customClass: { popup: 'admin-toast' }
    });
}
</script>

</body>
</html>
