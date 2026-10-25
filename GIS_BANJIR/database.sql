-- ===================================
-- Database Schema untuk SIG Rawan Banjir & Jalur Evakuasi Kota Medan
-- ===================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS gis_banjir DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gis_banjir;

-- Hapus tabel jika sudah ada (untuk re-import)
DROP TABLE IF EXISTS laporan_warga;
DROP TABLE IF EXISTS berita;
DROP TABLE IF EXISTS kejadian_banjir;
DROP TABLE IF EXISTS titik_evakuasi;
DROP TABLE IF EXISTS lokasi_rawan;
DROP TABLE IF EXISTS kelurahan;
DROP TABLE IF EXISTS kecamatan;
DROP TABLE IF EXISTS users;

-- ===================================
-- TABEL: users
-- ===================================
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  role ENUM('admin','operator') DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: kecamatan
-- ===================================
CREATE TABLE kecamatan (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nama VARCHAR(100) NOT NULL,
  kode VARCHAR(20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: kelurahan
-- ===================================
CREATE TABLE kelurahan (
  id INT PRIMARY KEY AUTO_INCREMENT,
  kecamatan_id INT NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kode VARCHAR(20),
  FOREIGN KEY (kecamatan_id) REFERENCES kecamatan(id) ON DELETE CASCADE,
  INDEX idx_kecamatan (kecamatan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: lokasi_rawan
-- ===================================
CREATE TABLE lokasi_rawan (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nama VARCHAR(200) NOT NULL,
  kecamatan_id INT,
  kelurahan_id INT,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  geojson TEXT,
  tingkat ENUM('rendah','sedang','tinggi') NOT NULL DEFAULT 'rendah',
  ketinggian_air_cm INT DEFAULT 0,
  penyebab VARCHAR(255),
  keterangan TEXT,
  status ENUM('aktif','nonaktif') DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kecamatan_id) REFERENCES kecamatan(id) ON DELETE SET NULL,
  FOREIGN KEY (kelurahan_id) REFERENCES kelurahan(id) ON DELETE SET NULL,
  INDEX idx_kecamatan (kecamatan_id),
  INDEX idx_tingkat (tingkat),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: titik_evakuasi
-- ===================================
CREATE TABLE titik_evakuasi (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nama VARCHAR(200) NOT NULL,
  jenis ENUM('sekolah','masjid','gedung','lapangan','rumah_sakit','lainnya') NOT NULL,
  alamat TEXT,
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  kapasitas INT DEFAULT 0,
  fasilitas TEXT,
  kontak VARCHAR(50),
  kecamatan_id INT,
  status ENUM('aktif','nonaktif') DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kecamatan_id) REFERENCES kecamatan(id) ON DELETE SET NULL,
  INDEX idx_kecamatan (kecamatan_id),
  INDEX idx_jenis (jenis),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: kejadian_banjir
-- ===================================
CREATE TABLE kejadian_banjir (
  id INT PRIMARY KEY AUTO_INCREMENT,
  lokasi_id INT,
  tanggal DATE NOT NULL,
  ketinggian_cm INT DEFAULT 0,
  durasi_jam DECIMAL(5,1) DEFAULT 0,
  korban_terdampak INT DEFAULT 0,
  keterangan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lokasi_id) REFERENCES lokasi_rawan(id) ON DELETE SET NULL,
  INDEX idx_lokasi (lokasi_id),
  INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: laporan_warga
-- ===================================
CREATE TABLE laporan_warga (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nama_pelapor VARCHAR(100) NOT NULL,
  kontak VARCHAR(20),
  latitude DECIMAL(10,8) NOT NULL,
  longitude DECIMAL(11,8) NOT NULL,
  ketinggian_cm INT DEFAULT 0,
  foto VARCHAR(255),
  deskripsi TEXT,
  status ENUM('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  kecamatan_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (kecamatan_id) REFERENCES kecamatan(id) ON DELETE SET NULL,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABEL: berita
-- ===================================
CREATE TABLE berita (
  id INT PRIMARY KEY AUTO_INCREMENT,
  judul VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  konten TEXT,
  gambar VARCHAR(255),
  kategori ENUM('berita','pengumuman','tips') DEFAULT 'berita',
  status ENUM('draft','published') DEFAULT 'published',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- DATA SEED
-- ===================================

-- Data users (password: Admin123!)
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES
('admin', '$2y$10$UhR7TZKMKZ5wDaEhSMlFwu582Ynamk5moTXmFLngQdSjVRiugvkzO', 'Administrator', 'admin@bpbdmedan.go.id', 'admin');

-- Data kecamatan (10 kecamatan di Kota Medan)
INSERT INTO kecamatan (nama, kode) VALUES
('Belawan', 'KEC-01'),
('Labuhan', 'KEC-02'),
('Deli', 'KEC-03'),
('Marelan', 'KEC-04'),
('Sunggal', 'KEC-05'),
('Helvetia', 'KEC-06'),
('Johor', 'KEC-07'),
('Polonia', 'KEC-08'),
('Tuntungan', 'KEC-09'),
('Selayang', 'KEC-10');

-- Data kelurahan (25 kelurahan)
INSERT INTO kelurahan (kecamatan_id, nama, kode) VALUES
-- Belawan
(1, 'Belawan I', 'KEL-0101'),
(1, 'Belawan II', 'KEL-0102'),
(1, 'Belawan Bahari', 'KEL-0103'),
-- Labuhan
(2, 'Tangkahan', 'KEL-0201'),
(2, 'Pekan Labuhan', 'KEL-0202'),
-- Deli
(3, 'Kota Bangun', 'KEL-0301'),
(3, 'Hamdan', 'KEL-0302'),
(3, 'Sukaraja', 'KEL-0303'),
-- Marelan
(4, 'Rengas Pulau', 'KEL-0401'),
(4, 'Tanah Enam Ratus', 'KEL-0402'),
(4, 'Paya Pasir', 'KEL-0403'),
-- Sunggal
(5, 'Sunggal', 'KEL-0501'),
(5, 'Lalang', 'KEL-0502'),
-- Helvetia
(6, 'Helvetia', 'KEL-0601'),
(6, 'Helvetia Timur', 'KEL-0602'),
(6, 'Helvetia Tengah', 'KEL-0603'),
-- Johor
(7, 'Kwala Bekala', 'KEL-0701'),
(7, 'Gedung Johor', 'KEL-0702'),
-- Polonia
(8, 'Polonia', 'KEL-0801'),
(8, 'Sari Rejo', 'KEL-0802'),
-- Tuntungan
(9, 'Tuntungan I', 'KEL-0901'),
(9, 'Tuntungan II', 'KEL-0902'),
(9, 'Tanjung Selamat', 'KEL-0903'),
-- Selayang
(10, 'Asam Kumbang', 'KEL-1001'),
(10, 'Beringin', 'KEL-1002');

-- Data lokasi_rawan (15 lokasi dengan koordinat realistis Medan)
INSERT INTO lokasi_rawan (nama, kecamatan_id, kelurahan_id, latitude, longitude, tingkat, ketinggian_air_cm, penyebab, keterangan, status) VALUES
-- Tingkat Rendah (5 lokasi)
('Jl. Sei Deli Raya', 3, 6, 3.583950, 98.663120, 'rendah', 25, 'Drainase kurang memadai', 'Banjir hanya saat hujan deras, cepat surut', 'aktif'),
('Jl. Gajah Mada Depan SMP 8', 8, 19, 3.575820, 98.671430, 'rendah', 20, 'Saluran tersumbat sampah', 'Genangan 1-2 jam setelah hujan', 'aktif'),
('Perumahan Helvetia Raya Blok D', 6, 14, 3.598760, 98.625340, 'rendah', 30, 'Tanah rendah', 'Genangan ringan area tertentu', 'aktif'),
('Jl. Karya Wisata Km 8', 9, 21, 3.618950, 98.712450, 'rendah', 15, 'Luapan parit', 'Jarang terjadi, hanya musim ekstrem', 'aktif'),
('Komp. Johor Indah Permai', 7, 17, 3.548620, 98.694870, 'rendah', 20, 'Resapan kurang', 'Genangan minor di jalan depan', 'aktif'),

-- Tingkat Sedang (6 lokasi)
('Jl. Gatot Subroto Km 5', 4, 9, 3.628450, 98.661230, 'sedang', 50, 'Dekat sungai Deli', 'Banjir 3-4 kali per tahun, ketinggian 50cm', 'aktif'),
('Pasar Belawan', 1, 1, 3.783420, 98.683210, 'sedang', 60, 'Rob dan hujan ekstrem', 'Area pasar sering tergenang', 'aktif'),
('Jl. STM Perumnas Mandala', 4, 10, 3.647120, 98.673450, 'sedang', 45, 'Sistem drainase buruk', 'Menggenangi permukiman penduduk', 'aktif'),
('Jl. Timor Sekip', 8, 20, 3.571230, 98.679120, 'sedang', 55, 'Dataran rendah', 'Terjadi setiap musim hujan', 'aktif'),
('Jl. Sei Kapuas Ujung', 2, 4, 3.673580, 98.709340, 'sedang', 40, 'Dekat muara sungai', 'Kombinasi rob dan hujan', 'aktif'),
('Kelurahan Sunggal Barat', 5, 12, 3.611240, 98.643780, 'sedang', 48, 'Tanah rendah dan drainase buruk', 'Meluas ke permukiman warga', 'aktif'),

-- Tingkat Tinggi (4 lokasi)
('Jl. Sei Brantas Kel. Tangkahan', 2, 4, 3.672450, 98.718930, 'tinggi', 100, 'Luapan sungai Deli', 'Banjir besar setiap tahun, ratusan KK terdampak', 'aktif'),
('Jl. Sei Musi - Kampung Aur', 2, 5, 3.664870, 98.723410, 'tinggi', 90, 'Luapan sungai Babura', 'Akses jalan terputus saat banjir', 'aktif'),
('Kel. Rengas Pulau', 4, 9, 3.635620, 98.658320, 'tinggi', 85, 'Tanah rendah + drainase minim', 'Banjir tahunan, lama surut 2-3 hari', 'aktif'),
('Jl. Setia Budi Pasar 2 Tembung', 5, 13, 3.612780, 98.652140, 'tinggi', 95, 'Dekat bantaran sungai', 'Langganan banjir, ratusan rumah terendam', 'aktif');

-- Data titik_evakuasi (10 titik)
INSERT INTO titik_evakuasi (nama, jenis, alamat, latitude, longitude, kapasitas, fasilitas, kontak, kecamatan_id, status) VALUES
('SDN 060934 Belawan', 'sekolah', 'Jl. Veteran Belawan I', 3.781290, 98.682340, 200, 'Ruang kelas, toilet, air bersih, dapur umum', '061-6941234', 1, 'aktif'),
('Masjid Raya Al-Mashun', 'masjid', 'Jl. Sisingamangaraja No. 1', 3.575680, 98.682190, 500, 'Aula besar, toilet, air bersih, musholla, parkir luas', '061-4567890', 8, 'aktif'),
('GOR Padang Golf', 'gedung', 'Jl. Jenderal Besar A.H. Nasution', 3.569340, 98.695430, 300, 'Ruang serbaguna, toilet, kantin, parkir', '061-7654321', 7, 'aktif'),
('Lapangan Merdeka', 'lapangan', 'Jl. Balai Kota No. 1', 3.576520, 98.677810, 1000, 'Lapangan terbuka, tenda darurat, MCK darurat', '061-4155555', 8, 'aktif'),
('RSUD Dr. Pirngadi', 'rumah_sakit', 'Jl. Prof. H.M. Yamin SH No. 47', 3.585670, 98.675430, 150, 'Ruang IGD, ruang tunggu, toilet, air bersih', '061-4566368', 3, 'aktif'),
('SMA Negeri 1 Medan', 'sekolah', 'Jl. Guru Patimpus No. 5', 3.576230, 98.669820, 400, 'Ruang kelas, aula, laboratorium, toilet, kantin', '061-4530343', 8, 'aktif'),
('Masjid Agung Sumatera Utara', 'masjid', 'Jl. Sisingamangaraja KM 5', 3.551340, 98.701560, 800, 'Aula utama, ruang wudhu, toilet, dapur', '061-7871234', 7, 'aktif'),
('Gedung Serba Guna Helvetia', 'gedung', 'Jl. Veteran Pasar IV Helvetia', 3.597830, 98.626540, 250, 'Aula, panggung, toilet, tempat parkir', '061-8451111', 6, 'aktif'),
('Kantor Camat Marelan', 'gedung', 'Jl. Marelan Raya No. 88', 3.639870, 98.664320, 100, 'Ruang kantor, aula kecil, toilet', '061-6847777', 4, 'aktif'),
('Masjid Al-Ikhlas Tuntungan', 'masjid', 'Jl. Tuntungan Raya No. 45', 3.615680, 98.707890, 300, 'Ruang utama, toilet, tempat wudhu, parkir', '061-7941234', 9, 'aktif');

-- Data kejadian_banjir (20 kejadian 2020-2024)
INSERT INTO kejadian_banjir (lokasi_id, tanggal, ketinggian_cm, durasi_jam, korban_terdampak, keterangan) VALUES
(12, '2020-01-15', 110, 36.0, 450, 'Banjir besar akibat hujan deras 3 hari, evakuasi massal ke GOR'),
(13, '2020-02-08', 95, 28.0, 320, 'Luapan sungai Babura, akses jalan terputus 2 hari'),
(12, '2020-11-20', 105, 40.0, 480, 'Banjir tahunan terbesar, ratusan rumah terendam'),
(14, '2021-01-10', 88, 30.0, 290, 'Kombinasi hujan dan pasang air, permukiman terendam'),
(15, '2021-02-14', 98, 32.0, 350, 'Banjir melanda pasar dan permukiman, kerugian ekonomi tinggi'),
(6, '2021-03-05', 52, 12.0, 80, 'Genangan di jalan utama, lalu lintas terganggu'),
(7, '2021-10-11', 65, 18.0, 120, 'Rob tinggi bersamaan hujan, area pasar tergenang'),
(12, '2021-12-18', 115, 42.0, 520, 'Banjir terparah dalam 5 tahun, bantuan dari pemerintah pusat'),
(13, '2022-01-22', 92, 26.0, 300, 'Luapan sungai kembali terjadi, warga sudah antisipasi'),
(8, '2022-02-16', 48, 10.0, 75, 'Genangan ringan di kompleks perumahan'),
(14, '2022-03-08', 85, 28.0, 270, 'Banjir rutin tahunan, warga sudah terbiasa evakuasi mandiri'),
(15, '2022-11-25', 100, 35.0, 380, 'Hujan ekstrem, bantuan logistik dari BPBD dan TNI'),
(6, '2022-12-30', 55, 14.0, 95, 'Genangan akibat drainase tersumbat sampah'),
(9, '2023-01-19', 50, 11.0, 60, 'Banjir ringan di area pasar, pedagang dirugikan'),
(12, '2023-02-10', 108, 38.0, 460, 'Banjir tahunan, pola sama setiap tahun'),
(13, '2023-12-05', 90, 24.0, 310, 'Hujan deras semalaman, sungai meluap pagi hari'),
(10, '2024-01-15', 42, 8.0, 50, 'Genangan singkat, cepat surut setelah hujan berhenti'),
(14, '2024-02-18', 82, 26.0, 250, 'Banjir sedang, warga evakuasi ke masjid terdekat'),
(15, '2024-03-22', 96, 30.0, 340, 'Bantuan cepat dari relawan dan BPBD'),
(7, '2024-10-10', 68, 16.0, 140, 'Rob tinggi ditambah hujan, area pelabuhan terdampak');

-- Data berita (3 berita/pengumuman)
INSERT INTO berita (judul, slug, konten, gambar, kategori, status) VALUES
('Waspada Musim Hujan 2024: BPBD Medan Siapkan 15 Posko Siaga Banjir', 'waspada-musim-hujan-2024-bpbd-medan-siapkan-15-posko-siaga-banjir', 'Badan Penanggulangan Bencana Daerah (BPBD) Kota Medan telah menyiapkan 15 posko siaga banjir yang tersebar di berbagai kecamatan rawan banjir. Posko ini dilengkapi dengan perahu karet, peralatan evakuasi, dan logistik darurat. Kepala BPBD Medan menyatakan bahwa seluruh personel siap siaga 24 jam untuk merespon cepat kejadian banjir. Masyarakat diminta untuk selalu waspada dan mengikuti arahan dari petugas lapangan.', NULL, 'berita', 'published'),
('Pengumuman: Simulasi Evakuasi Banjir Dilaksanakan 15 November 2024', 'pengumuman-simulasi-evakuasi-banjir-dilaksanakan-15-november-2024', 'BPBD Kota Medan akan mengadakan simulasi evakuasi banjir pada tanggal 15 November 2024 pukul 08.00 WIB di Kelurahan Tangkahan, Kecamatan Labuhan. Simulasi ini melibatkan warga, TNI, Polri, PMI, dan relawan. Tujuan kegiatan adalah meningkatkan kesiapsiagaan masyarakat dalam menghadapi bencana banjir. Warga diharapkan dapat berpartisipasi aktif dalam kegiatan ini.', NULL, 'pengumuman', 'published'),
('Tips Menghadapi Banjir: Persiapan dan Tindakan Darurat', 'tips-menghadapi-banjir-persiapan-dan-tindakan-darurat', 'Menghadapi musim hujan, penting bagi masyarakat untuk mempersiapkan diri. Beberapa tips yang dapat dilakukan: 1) Siapkan tas siaga bencana berisi dokumen penting, obat-obatan, makanan instan, dan pakaian; 2) Kenali rute evakuasi terdekat dan lokasi titik kumpul; 3) Simpan nomor kontak darurat BPBD 117, PMI, dan Basarnas; 4) Matikan listrik jika air mulai masuk rumah; 5) Jangan melintasi genangan air yang tinggi; 6) Ikuti arahan petugas dan jangan panik. Kesiapsiagaan adalah kunci keselamatan.', NULL, 'tips', 'published');

-- Data laporan_warga (5 laporan dengan berbagai status)
INSERT INTO laporan_warga (nama_pelapor, kontak, latitude, longitude, ketinggian_cm, foto, deskripsi, status, kecamatan_id) VALUES
('Ahmad Fauzi', '081234567890', 3.674120, 98.720340, 45, NULL, 'Jalan depan rumah saya di Jl. Sei Musi sudah mulai tergenang sejak pagi tadi. Air terus naik. Mohon bantuan', 'menunggu', 2),
('Siti Rahmah', '085298765432', 3.636780, 98.660120, 55, NULL, 'Banjir di Rengas Pulau sudah setinggi lutut orang dewasa. Beberapa warga sudah mulai evakuasi mandiri', 'menunggu', 4),
('Budi Santoso', '081298567123', 3.583210, 98.671450, 30, NULL, 'Genangan di Jl. Gatot Subroto depan pasar. Lalu lintas mulai terganggu. Butuh pompa air', 'disetujui', 4),
('Dewi Lestari', '085234876543', 3.598230, 98.627890, 25, NULL, 'Laporan banjir di Helvetia Raya sudah ditangani BPBD. Air mulai surut. Terima kasih', 'disetujui', 6),
('Andi Wijaya', '081256789012', 3.612340, 98.655670, 40, NULL, 'Laporan tidak valid, lokasi salah dan tidak ada bukti foto. Mohon laporan lebih akurat', 'ditolak', 5);

-- Aktifkan kembali foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- ===================================
-- Selesai
-- ===================================
