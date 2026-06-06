<!doctype html>
<html lang="id">
  <head>
     <?php include "layout/head.php"?>"
  </head>
  <body data-page="kkn" style="display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div class="glass" style="max-width:550px;">
      <h1 class="page-title">Pendaftaran KKN Tematik</h1>
      <form>
        <label for="univ" class="form-label form-label--white">Nama Universitas</label>
        <input type="text" class="form-control" id="univ" placeholder="Masukkan nama universitas" required />
        <label for="nama" class="form-label form-label--white">Nama</label>
        <input type="text" class="form-control" id="nama" placeholder="Masukkan nama lengkap" required />
        <label for="nim" class="form-label form-label--white">NIM</label>
        <input type="text" class="form-control" id="nim" placeholder="Masukkan nomor induk mahasiswa" required />
        <label for="surat" class="form-label form-label--white">Upload Surat Permohonan</label>
        <input type="file" class="form-control" id="surat" required />
        <button type="submit" class="btn-gold" style="margin-top:10px;">Kirim</button>
      </form>
    </div>
    <script src="<?=base_url('assets/js/script.js')?>"></script>
  </body>
</html>
