<!doctype html>
<html lang="id">
  <head>
    <?php include "layout/head.php"?>"
  </head>
  <body data-page="inputpublikasi" style="padding:40px 20px;">
    <div class="glass glass--wide">
      <h1 class="page-title page-title--white" style="font-size:2rem;margin-bottom:5px;">Publikasi Media</h1>
      <p class="sub-title sub-title--gold" style="font-size:0.9rem;">Promosikan Proyek Perumahan Anda Secara Digital</p>
      <div class="platform-icons">
        <i class="bi bi-instagram"></i><i class="bi bi-facebook"></i><i class="bi bi-twitter-x"></i><i class="bi bi-youtube"></i>
      </div>
      <form>
        <label class="form-label">Nama Proyek Perumahan</label>
        <input type="text" class="form-control" placeholder="Masukkan nama perumahan" />
        <label class="form-label">Deskripsi / Caption Iklan</label>
        <textarea class="form-control" rows="3" placeholder="Tuliskan deskripsi menarik untuk konten sosial media..."></textarea>
        <label class="form-label">Unggah Foto/Video Proyek</label>
        <div class="upload-zone">
          <i class="bi bi-cloud-arrow-up-fill" style="font-size:3rem;color:#ffc107;"></i>
          <p class="mt-2 mb-0">Klik atau seret file gambar/video ke sini</p>
          <small class="text-white-50">Maksimal 10MB (JPG, PNG, MP4)</small>
        </div>
        <button type="submit" class="btn-gold">Ajukan Publikasi</button>
      </form>
    </div>
    <script src="<?=base_url('assets/js/script.js')?>"></script>
  </body>
</html>
