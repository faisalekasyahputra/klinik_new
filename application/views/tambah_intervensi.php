<!doctype html>
<html lang="id">
  <head>
    <?php include "layout/head.php"?>"
  </head>
  <body data-page="kabupaten" style="display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div class="glass glass--wide">
      <h1 class="page-title">Input Data Intervensi</h1>
      <form>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Kabupaten / Kota</label>
            <select class="form-select" required><option selected disabled>Pilih Wilayah...</option><option>Kota Semarang</option><option>Kab. Magelang</option><option>Kab. Boyolali</option></select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Kecamatan</label>
            <input type="text" class="form-control" placeholder="Nama Kecamatan" required />
          </div>
        </div>
        <label class="form-label">Nama Program Intervensi</label>
        <input type="text" class="form-control" placeholder="Contoh: RTLH, PSU, atau Pembangunan Baru" required />
        <div class="row">
          <div class="col-md-6 mb-3"><label class="form-label">Jumlah Unit</label><input type="number" class="form-control" placeholder="0" required /></div>
          <div class="col-md-6 mb-3"><label class="form-label">Tahun Anggaran</label><input type="number" class="form-control" value="2026" required /></div>
        </div>
        <label class="form-label">Total Pagu Anggaran (Rp)</label>
        <input type="number" class="form-control" placeholder="Masukkan nominal tanpa titik" required />
        <label class="form-label">Titik Koordinat (GeoJSON/UTM)</label>
        <input type="text" class="form-control" placeholder="Masukkan data koordinat lokasi" />
        <button type="submit" class="btn-gold">Simpan Data Intervensi</button>
      </form>
    </div>
   <script src="<?=base_url('assets/js/script.js')?>"></script>
  </body>
</html>
