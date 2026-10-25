<?php
/**
 * Konfigurasi Koneksi Database
 * File: config/database.php
 *
 * Ubah nilai konstanta berikut sesuai environment Anda.
 * Jangan commit file ini ke repository publik jika berisi credential produksi.
 */

// Nama host database (biasanya localhost untuk XAMPP)
define('DB_HOST', 'localhost');

// Nama database
define('DB_NAME', 'gis_banjir');

// Username database (default XAMPP adalah root)
define('DB_USER', 'root');

// Password database (default XAMPP adalah kosong)
define('DB_PASS', '');

// Charset database
define('DB_CHARSET', 'utf8mb4');

/**
 * Mendapatkan koneksi PDO ke database.
 * Menggunakan pola Singleton agar hanya satu koneksi yang aktif.
 *
 * @return PDO Instance koneksi PDO
 */
function getDB(): PDO {
    // Variabel statis hanya diinisialisasi sekali (Singleton pattern)
    static $pdo = null;

    if ($pdo === null) {
        // Bangun DSN (Data Source Name) untuk MySQL
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        // Opsi PDO untuk keamanan dan kemudahan penggunaan
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lempar exception saat error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Fetch hasil sebagai array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                     // Gunakan prepared statement native
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Hentikan eksekusi dan tampilkan pesan error dalam format JSON
            http_response_code(500);
            die(json_encode([
                'error'   => 'Koneksi database gagal.',
                'message' => $e->getMessage()
            ]));
        }
    }

    return $pdo;
}
