<?php
/**
 * Partial: Form Field Lokasi Rawan
 * File: admin/includes/form_lokasi.php
 *
 * Di-include oleh lokasi_rawan.php untuk modal Tambah maupun Edit.
 * Prefix ID field: "tambah" bila modal tambah, "edit" bila modal edit.
 * Ditentukan dari konteks include (form parent).
 *
 * Karena di-include dua kali dalam satu halaman, semua ID harus unik.
 * Solusi: gunakan prefix berdasarkan nama form parent ($formPrefix).
 */

// Tentukan prefix berdasarkan form yang sedang aktif
// Cara: cek apakah kita berada di dalam modal tambah atau edit
// melalui variabel $formPrefix yang di-set sebelum include
// Jika tidak di-set, default ke 'tambah'
if (!isset($formPrefix)) {
    // Deteksi otomatis dari context: jika ada $editData maka 'edit', selain itu 'tambah'
    $formPrefix = 'tambah';
}

$p = $formPrefix; // shorthand
?>

<div class="row g-3">
    <!-- Nama Lokasi -->
    <div class="col-12">
        <label for="<?= $p ?>Nama" class="form-label">Nama Lokasi <span class="text-danger">*</span></label>
        <input type="text" id="<?= $p ?>Nama" name="nama" class="form-control"
               placeholder="Contoh: Jl. Gatot Subroto Km 5" required maxlength="200">
    </div>

    <!-- Kecamatan & Kelurahan -->
    <div class="col-md-6">
        <label for="<?= $p ?>KecamatanId" class="form-label">Kecamatan <span class="text-danger">*</span></label>
        <select id="<?= $p ?>KecamatanId" name="kecamatan_id" class="form-select" required
                onchange="loadKelurahan(this.value, '<?= $p ?>KelurahanId')">
            <option value="">-- Pilih Kecamatan --</option>
            <?php foreach ($kecamatanList as $kec): ?>
            <option value="<?= $kec['id'] ?>">
                <?= htmlspecialchars($kec['nama'], ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label for="<?= $p ?>KelurahanId" class="form-label">Kelurahan <small class="text-muted">(opsional)</small></label>
        <select id="<?= $p ?>KelurahanId" name="kelurahan_id" class="form-select" disabled>
            <option value="">-- Pilih Kecamatan Dulu --</option>
        </select>
    </div>

    <!-- Tingkat & Ketinggian Air -->
    <div class="col-md-6">
        <label for="<?= $p ?>Tingkat" class="form-label">Tingkat Risiko <span class="text-danger">*</span></label>
        <select id="<?= $p ?>Tingkat" name="tingkat" class="form-select" required>
            <option value="rendah">🟢 Rendah</option>
            <option value="sedang">🟡 Sedang</option>
            <option value="tinggi">🔴 Tinggi</option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="<?= $p ?>Ketinggian" class="form-label">Ketinggian Air (cm)</label>
        <div class="input-group">
            <input type="number" id="<?= $p ?>Ketinggian" name="ketinggian_air_cm" class="form-control"
                   min="0" max="9999" value="0">
            <span class="input-group-text">cm</span>
        </div>
    </div>

    <!-- Koordinat -->
    <div class="col-md-6">
        <label for="<?= $p ?>Lat" class="form-label">Latitude <span class="text-danger">*</span></label>
        <input type="number" id="<?= $p ?>Lat" name="latitude" class="form-control"
               step="any" placeholder="Contoh: 3.595200" required
               oninput="syncMarker<?= ucfirst($p) ?>()">
    </div>

    <div class="col-md-6">
        <label for="<?= $p ?>Lng" class="form-label">Longitude <span class="text-danger">*</span></label>
        <input type="number" id="<?= $p ?>Lng" name="longitude" class="form-control"
               step="any" placeholder="Contoh: 98.672200" required
               oninput="syncMarker<?= ucfirst($p) ?>()">
    </div>

    <!-- Peta Picker -->
    <div class="col-12">
        <label class="form-label">
            <i class="fa-solid fa-map-pin me-1 text-primary"></i>
            Klik peta untuk memilih koordinat
        </label>
        <div id="mapPicker<?= ucfirst($p) ?>" style="height:240px; border:1px solid #e2e8f0; border-radius:10px; z-index:1"></div>
        <small class="text-muted mt-1 d-block">Klik pada peta untuk otomatis mengisi latitude dan longitude.</small>
    </div>

    <!-- Penyebab -->
    <div class="col-12">
        <label for="<?= $p ?>Penyebab" class="form-label">Penyebab Banjir</label>
        <input type="text" id="<?= $p ?>Penyebab" name="penyebab" class="form-control"
               placeholder="Contoh: Luapan sungai, drainase buruk" maxlength="255">
    </div>

    <!-- Keterangan -->
    <div class="col-12">
        <label for="<?= $p ?>Keterangan" class="form-label">Keterangan Tambahan</label>
        <textarea id="<?= $p ?>Keterangan" name="keterangan" class="form-control" rows="3"
                  placeholder="Deskripsi detail kondisi lokasi..."></textarea>
    </div>

    <!-- Status -->
    <div class="col-md-6">
        <label for="<?= $p ?>Status" class="form-label">Status</label>
        <select id="<?= $p ?>Status" name="status" class="form-select">
            <option value="aktif">✅ Aktif</option>
            <option value="nonaktif">⭕ Nonaktif</option>
        </select>
    </div>
</div>
