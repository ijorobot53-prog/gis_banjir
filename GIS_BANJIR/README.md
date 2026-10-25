# SIG Rawan Banjir & Jalur Evakuasi Kota Medan

## Deskripsi

Sistem Informasi Geografis (SIG) Rawan Banjir & Jalur Evakuasi Kota Medan adalah aplikasi berbasis web yang memetakan kawasan rawan banjir, titik-titik evakuasi, dan jalur aman bagi warga Kota Medan. Sistem ini dikelola oleh Badan Penanggulangan Bencana Daerah (BPBD) Kota Medan.

**Tujuan Sistem:**
- Menyediakan informasi peta rawan banjir yang mudah diakses oleh masyarakat umum
- Membantu warga menemukan jalur evakuasi dan posko darurat terdekat
- Menyediakan kanal pelaporan banjir secara real-time
- Mengelola data kejadian, statistik, dan edukasi kebencanaan

**Relevansi Tujuan Pembangunan Berkelanjutan (SDG):**
- **SDG 11** – *Kota dan Komunitas yang Berkelanjutan*: Meningkatkan ketangguhan kota terhadap bencana dan menyediakan infrastruktur informasi yang inklusif
- **SDG 13** – *Penanganan Perubahan Iklim*: Meningkatkan kapasitas adaptasi dan mitigasi terhadap bencana hidrometeorologi seperti banjir

---

## Persyaratan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Web Server | Apache 2.4+ (via XAMPP/Laragon) |
| Browser | Chrome 90+, Firefox 88+, Edge 90+ |
| Koneksi Internet | Diperlukan (CDN libraries: Bootstrap, Leaflet, Font Awesome) |

---

## Instalasi di XAMPP

### Langkah 1: Salin File Proyek

```bash
# Clone repository (atau salin manual)
git clone https://github.com/username/GIS_BANJIR.git C:\xampp\htdocs\GIS_BANJIR

# Atau salin folder secara manual ke:
# C:\xampp\htdocs\GIS_BANJIR
```

### Langkah 2: Jalankan XAMPP

1. Buka **XAMPP Control Panel**
2. Klik **Start** pada Apache dan MySQL

### Langkah 3: Buat Database

1. Buka browser, akses `http://localhost/phpmyadmin`
2. Klik **New** di panel kiri
3. Isi nama database: `gis_banjir`
4. Pilih collation: `utf8mb4_unicode_ci`
5. Klik **Create**

### Langkah 4: Import Skema & Data

1. Pilih database `gis_banjir` di panel kiri phpMyAdmin
2. Klik tab **Import**
3. Klik **Choose File** → pilih file `database.sql` dari folder proyek
4. Klik **Go** untuk menjalankan import

### Langkah 5: Buka Aplikasi

Buka browser dan akses: `http://localhost/GIS_BANJIR`

---

## Instalasi di Laragon

### Langkah 1: Salin File Proyek

Salin atau clone folder `GIS_BANJIR` ke direktori root Laragon:

```
C:\laragon\www\GIS_BANJIR
```

### Langkah 2: Jalankan Laragon

1. Buka Laragon
2. Klik **Start All** (memulai Apache dan MySQL)

### Langkah 3: Buat Database & Import

1. Buka **phpMyAdmin** melalui tombol di Laragon atau akses `http://localhost/phpmyadmin`
2. Buat database baru bernama `gis_banjir` dengan collation `utf8mb4_unicode_ci`
3. Import file `database.sql` melalui tab **Import**

### Langkah 4: Buka Aplikasi

Akses `http://localhost/GIS_BANJIR` di browser Anda.

> **Catatan Laragon:** Laragon mendukung virtual host otomatis. Anda juga bisa mengakses aplikasi via `http://gis-banjir.test` jika fitur Pretty URL diaktifkan.

---

## Konfigurasi Database

Edit file `config/database.php` untuk menyesuaikan koneksi database:

```php
// config/database.php

define('DB_HOST', 'localhost');   // Host database — biasanya 'localhost' untuk XAMPP/Laragon
define('DB_NAME', 'gis_banjir');  // Nama database yang sudah dibuat di phpMyAdmin
define('DB_USER', 'root');        // Username database (default XAMPP/Laragon: 'root')
define('DB_PASS', '');            // Password database (default XAMPP: kosong, Laragon: kosong atau 'root')
define('DB_CHARSET', 'utf8mb4'); // Charset — tetap utf8mb4 untuk mendukung karakter Unicode penuh
```

**Konfigurasi untuk produksi:**
- Ganti `DB_USER` dan `DB_PASS` dengan kredensial yang aman
- Jangan menyimpan file ini di repository publik jika berisi password
- Pertimbangkan menggunakan file `.env` untuk environment produksi

---

## Akun Admin Default

| Field | Nilai |
|---|---|
| URL Login | `http://localhost/GIS_BANJIR/admin/login.php` |
| Username | `admin` |
| Password | `Admin123!` |

> ⚠️ **PENTING:** Segera ganti password setelah instalasi pertama!
> Masuk ke panel admin → menu **Users** → edit akun admin → ubah password dengan kombinasi huruf besar, huruf kecil, angka, dan simbol.

---

## Cara Mengganti Data BPBD

Setelah instalasi, data koordinat yang tersedia bersifat ilustratif. Ikuti langkah ini untuk mengganti dengan data resmi BPBD:

### 1. Ganti Data Kecamatan & Kelurahan
- Masuk ke panel admin: `http://localhost/GIS_BANJIR/admin/`
- Data kecamatan dan kelurahan dikelola langsung melalui tabel `kecamatan` dan `kelurahan` di phpMyAdmin

### 2. Ganti Data Lokasi Rawan Banjir
- Masuk ke **Admin → Lokasi Rawan**
- Klik **Tambah Lokasi** untuk menambah titik baru
- Isi nama lokasi, pilih kecamatan/kelurahan, tentukan tingkat bahaya, dan tandai koordinat di peta picker
- Untuk data massal: ekspor template CSV dari halaman admin, isi data, lalu lakukan import via phpMyAdmin

### 3. Ganti Data Titik Evakuasi
- Masuk ke **Admin → Evakuasi**
- Tambahkan posko evakuasi dengan koordinat GPS yang valid
- Sertakan kapasitas, fasilitas, dan nomor kontak penanggung jawab

### 4. Input Kejadian Banjir
- Masuk ke **Admin → Kejadian Banjir**
- Input data kejadian secara berkala termasuk tanggal, ketinggian air, dan jumlah warga terdampak

### 5. Verifikasi Laporan Warga
- Laporan banjir dari masyarakat masuk ke **Admin → Laporan**
- Tinjau dan ubah status laporan dari *menunggu* menjadi *disetujui* atau *ditolak*

---

## Struktur Folder

```
GIS_BANJIR/
│
├── admin/                      # Panel administrasi
│   ├── includes/
│   │   ├── header.php          # Header & navbar admin
│   │   ├── sidebar.php         # Sidebar navigasi admin
│   │   ├── footer.php          # Footer admin
│   │   └── form_lokasi.php     # Komponen form lokasi rawan
│   ├── index.php               # Dashboard admin
│   ├── login.php               # Halaman login admin
│   ├── logout.php              # Proses logout
│   ├── lokasi_rawan.php        # CRUD lokasi rawan banjir
│   ├── evakuasi.php            # CRUD titik evakuasi
│   ├── kejadian.php            # CRUD kejadian banjir
│   ├── laporan.php             # Moderasi laporan warga
│   ├── berita.php              # CRUD berita & pengumuman
│   └── users.php               # Manajemen pengguna admin
│
├── api/                        # Endpoint API (output JSON)
│   ├── lokasi.php              # API data lokasi rawan banjir
│   ├── evakuasi.php            # API data titik evakuasi
│   ├── kelurahan.php           # API daftar kelurahan
│   ├── laporan.php             # API submit laporan banjir
│   └── statistik.php           # API data statistik
│
├── assets/                     # Aset statis
│   ├── css/
│   │   └── style.css           # Stylesheet utama
│   └── js/
│       └── main.js             # JavaScript utama
│
├── config/
│   └── database.php            # Konfigurasi koneksi database
│
├── includes/                   # Komponen yang digunakan ulang
│   ├── header.php              # Header HTML publik (navbar, CDN)
│   ├── footer.php              # Footer HTML publik (script, copyright)
│   └── functions.php           # Fungsi PHP helper global
│
├── uploads/                    # File yang diupload pengguna
│   ├── berita/                 # Gambar artikel berita
│   ├── laporan/                # Foto laporan banjir dari warga
│   └── .gitkeep                # Penanda direktori untuk Git
│
├── index.php                   # Halaman beranda (hero, stats, berita)
├── peta.php                    # Halaman peta rawan banjir
├── evakuasi.php                # Halaman jalur & titik evakuasi
├── statistik.php               # Halaman grafik statistik banjir
├── edukasi.php                 # Halaman berita & edukasi
├── lapor.php                   # Formulir laporan banjir warga
├── tentang.php                 # Halaman tentang aplikasi
├── database.sql                # Skema database & data awal
└── README.md                   # Dokumentasi ini
```

---

## Teknologi yang Digunakan

### Backend
| Teknologi | Keterangan |
|---|---|
| PHP 8.0+ | Bahasa pemrograman server-side |
| MySQL / MariaDB | Database relasional |
| PDO | Abstraksi koneksi database (prepared statements) |

### Frontend
| Library | Versi | Keterangan |
|---|---|---|
| Bootstrap | 5.3.3 | Framework CSS & komponen UI |
| Leaflet.js | 1.9.4 | Library peta interaktif |
| Font Awesome | 6.5.1 | Ikon vektor |
| Google Fonts (Poppins) | — | Tipografi |
| AOS (Animate on Scroll) | 2.3.4 | Animasi scroll |
| SweetAlert2 | 11 | Dialog konfirmasi & notifikasi |

### Tile Map
| Provider | Keterangan |
|---|---|
| OpenStreetMap | Peta dasar (gratis, open-source) |
| Esri World Imagery | Citra satelit |

---

## Disclaimer Data

> ⚠️ **Perhatian:** Koordinat lokasi rawan banjir, titik evakuasi, dan data kejadian yang tersedia secara default dalam aplikasi ini bersifat **ilustratif** dan hanya ditujukan untuk keperluan **simulasi, demonstrasi, dan edukasi**.
>
> Data tersebut **bukan merupakan data resmi BPBD Kota Medan**. Untuk informasi bencana yang akurat dan terkini, selalu rujuk ke sumber resmi:
> - Website resmi BPBD Kota Medan
> - Aplikasi InaRisk dari BNPB
> - Hotline BPBD Nasional: **117**

---

## Lisensi

```
MIT License

Copyright (c) 2024 BPBD Kota Medan

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```

---

*Dikembangkan untuk mendukung upaya mitigasi bencana banjir di Kota Medan.*
