# Manajemen sertifikat TLS Klinik PKP

## Konfigurasi produksi

Portal publik hanya dilayani melalui HTTPS pada `floralwhite-lion-710022.hostingersite.com`. Sertifikat TLS dikelola di hPanel oleh penyedia hosting. Cookie sesi memakai atribut `Secure`; HSTS aktif selama satu tahun.

## Verifikasi berkala

Jalankan dari lingkungan yang memiliki akses internet:

```sh
php tests/tls_certificate_test.php
```

Uji membuat koneksi TLS dengan pemeriksaan rantai sertifikat dan nama host, lalu memastikan sertifikat belum kedaluwarsa. Jalankan setidaknya setiap tiga bulan dan setelah perubahan domain, CDN, atau sertifikat.

## Pembaruan dan insiden

1. Perbarui/aktifkan sertifikat pada hPanel sebelum tanggal kedaluwarsa.
2. Pastikan HTTPS, HSTS, dan cookie `Secure` tetap aktif.
3. Jalankan uji di atas dari lokal dan server produksi.
4. Bila uji gagal, hentikan perubahan rilis dan eskalasi kepada pengelola domain/hosting.

Dokumen ini hanya membahas sertifikat TLS kanal komunikasi. Sertifikat TLS bukan sertifikat penandatanganan kode atau aplikasi.
