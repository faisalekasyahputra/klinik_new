<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian Properti - Sikumbang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-img-top { height: 200px; object-fit: cover; }
        .badge-subsidi { background-color: #ffc107; color: #000; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Sikumbang API Explorer</span>
    </div>
</nav>

<div class="container">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= base_url('sikumbang/index') ?>" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cari Perumahan</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Masukkan nama perumahan..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Urutkan</label>
                    <select name="sort" class="form-select">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Termurah (Subsidi)</option>
                        <option value="terdekat">Terdekat</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Cari Lokasi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="https://sikumbang.tapera.go.id/public/upload/2026/05/11/file-lokasi-e6483e30-c296-41f0-9c88-1269a4d8dd4a.jpg" class="card-img-top" alt="Foto Perumahan">
                        <div class="card-body">
                            <h5 class="card-title text-truncate"><?= $row['namaPerumahan'] ?></h5>
                            <p class="card-text text-muted small">
                                <i class="bi bi-geo-alt"></i> <?= $row['wilayah']['kecamatan'] . ', ' . $row['wilayah']['kabupaten'] ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-subsidi">Subsidi</span>
                                <span class="text-primary fw-bold">Rp <?= number_convert($row['harga_min'] ?? 0) ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="https://sikumbang.tapera.go.id/detail-lokasi/<?= $row['idLokasi'] ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    Silakan masukkan kata kunci untuk mencari data perumahan.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Helper sederhana untuk format mata uang di dalam view
function number_convert($number) {
    return number_format($number, 0, ',', '.');
}
?>
