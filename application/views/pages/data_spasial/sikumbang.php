<!doctype html>
<html lang="id">
  <head>
    
    	<?php include "layout/head.php"?>"
  </head>
  <style>
   img-container {
    width: 100%;
    height: 200px; /* Sesuaikan tinggi yang diinginkan */
    overflow: hidden;
}

.img-container img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Ini penting agar gambar terpotong rapi, tidak gepeng */
}
  </style>
  <body data-page="umum" style="padding:30px 15px;">
    <div class="glass glass--wide">
      <h2 class="page-title" style="font-size:1.8rem;margin-bottom:10px;">Menu Umum</h2>
      <p class="sub-title" style="margin-bottom:25px;">Sikumbang</p>

      <div class="menu-grid">
        <?php if (!empty($results)): ?>
                <?php foreach ($results as $row): ?>
       <div class="property-card shadow">
                        <div class="img-container">
                            <img src="<?=trim($row['foto'][0])?>" crossorigin="anonymous" class="card-img-top">
                        </div>
                        <div class="p-3">
                            <h6 class="text-truncate fw-bold mb-1" title="<?= $row['namaPerumahan'] ?? '' ?>">
                                <?= $row['namaPerumahan'] ?? 'Nama Tidak Tersedia' ?>
                            </h6>
                            <p class="small text-muted mb-2">
                                <?= ($row['kecamatan'] ?? '') . ', ' . ($row['kabupaten'] ?? '') ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-subsidi">Subsidi</span>
                                <span class="price-tag text-primary">
                                    Rp <?= number_format($row['harga_min'] ?? 0, 0, ',', '.') ?>
                                </span>
                            </div>
                            <hr>
                            <a href="https://sikumbang.tapera.go.id/detail-lokasi/<?= $row['id_lokasi'] ?? '' ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary w-100 shadow-sm">
                                Detail Unit
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center w-100 py-5">
                    <p>Data tidak ditemukan atau sedang memuat...</p>
                </div>
            <?php endif; ?>
      </div>

      
    </div>
    <script src="<?=base_url('assets/js/script.js')?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
