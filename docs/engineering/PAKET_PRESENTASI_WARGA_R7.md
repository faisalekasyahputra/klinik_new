# Paket Presentasi Warga R7

**Status:** panduan lokal, 28 Juli 2026. Bukan panduan rilis dan tidak boleh
dipakai pada production aktif.

## Tujuan presentasi

Paparkan satu cerita yang dapat dibuktikan: data sintetis masuk melalui batas
SIMPERUM, warga memeriksa serta melengkapinya, ruleset server memberi
rekomendasi beralasan, admin wilayah meminta perbaikan, warga mengirim versi
baru, lalu admin menyetujui. Jangan menyebut simulasi sebagai keputusan bantuan
resmi atau API nyata.

## Data demo minimum

| Skenario | NIK sintetis | Tanggal lahir | Wilayah | Yang dibuktikan |
|---|---|---|---|---|
| SIM-01 | `0000000000000001` | `1980-01-01` | Kota Semarang (`3374`) | Data relatif lengkap, rumah eksisting, rekomendasi RTLH, perjalanan revisi penuh |
| SIM-02 | `0000000000000002` | `1990-12-31` | Kabupaten Demak (`3321`) | Data sumber parsial harus dilengkapi warga, bukan diisi default |
| SIM-03 | `0000000000000003` | `1988-03-03` | Kabupaten Kendal (`3324`) | Cabang calon lahan dan bukti yang berbeda dari rumah eksisting |

Gunakan `warga@example.com` dan `adminkabkota@example.com` dengan password demo
yang memang ditampilkan halaman login hanya untuk walkthrough manual SIM-01.
Skenario lintas wilayah harus memakai akun sementara yang dibuat skrip uji R7,
bukan mengubah scope akun demo atau memakai akun production.

Siapkan satu JPG/PNG sintetis tanpa data pribadi sebagai bukti. Jangan memakai
foto KTP, KK, rumah, atau lokasi warga sungguhan dalam rekaman presentasi.

## Urutan presentasi utama

1. Masuk sebagai warga dan buka `/warga/pendataan`.
   Tunjukkan label persisten
   **“Mode Simulasi — API SIMPERUM belum terhubung”** sebelum lookup.
2. Lookup SIM-01. Tunjukkan nilai hasil sumber dan penanda koreksi warga.
   Jelaskan bahwa desil berasal dari sumber dan tidak dihitung ulang dari
   penghasilan.
3. Lengkapi cabang rumah eksisting, unggah bukti sintetis, lalu buka
   **Review & Rekomendasi**. Tunjukkan versi `SIM-2026-01`, status, alasan, dan
   pilihan yang memang berasal dari server.
4. Kirim satu kali, lalu buka `/akun`. Catat kode tiket dan tunjukkan status
   **Dalam Peninjauan** beserta label simulasi.
5. Masuk sebagai Admin Kab/Kota Semarang. Pada antrean, tunjukkan label
   simulasi; buka detail dan tunjukkan:
   - sumber mentah dibanding koreksi warga;
   - seluruh rekomendasi dan penanda **Dipilih warga**;
   - versi ruleset, alasan, dan bukti lewat endpoint privat;
   - tidak ada NIK plaintext pada tabel antrean.
6. Pilih **Minta perbaikan**. Buktikan catatan kosong ditolak, lalu isi catatan
   spesifik, misalnya “Perbarui kondisi atap dan unggah bukti terbaru”.
7. Masuk kembali sebagai warga. `/akun` harus menampilkan
   **Perlu Perbaikan**, catatan admin, dan aksi **Mulai Perbaikan**.
8. Mulai perbaikan. Tunjukkan versi assessment bertambah, ubah satu data,
   rescore, pilih rekomendasi server, dan kirim ulang. Tiket tetap sama.
9. Admin membuka antrean `pending` yang sama dan menyetujui. `/akun` warga harus
   menampilkan **Disetujui** tanpa aksi revisi.

Target durasi walkthrough utama: 8–10 menit. SIM-02 dan SIM-03 cukup sebagai
walkthrough singkat terpisah; jangan mengulang seluruh siklus admin.

## Shot list acceptance desktop dan mobile

| Permukaan | Bukti yang wajib terlihat |
|---|---|
| Wizard awal | label simulasi persisten, input NIK/tanggal lahir |
| Data warga | provenance sumber versus koreksi |
| Review | ruleset, alasan manusia, status rekomendasi, disclaimer |
| Riwayat akun | tiket, status, label simulasi, catatan admin, aksi revisi |
| Antrean admin | scope wilayah, status, label simulasi, link detail |
| Detail admin | perbandingan data, semua rekomendasi, yang dipilih, bukti privat |
| Mobile 390×844 | konten memakai lebar viewport; sidebar tertutup awal, menjadi overlay lewat tombol, dapat ditutup dengan backdrop/Escape |

Pada mobile, cek minimal `/akun`, `/warga/pendataan`, antrean admin, dan detail
admin. Pada desktop, cek sidebar dapat dibuka dan diciutkan tanpa menghilangkan
main content. Catat error console; warning CDN eksternal yang sudah dikenal
harus dibedakan dari error aplikasi baru.

## Pembuktian runnable sebelum presentasi

Jalankan pada Apache XAMPP dan MariaDB lokal:

```powershell
C:\xampp\php\php.exe docs\engineering\uji_warga_fresh_r7.php
C:\xampp\php\php.exe docs\engineering\uji_keamanan_warga_r7.php
```

`uji_warga_fresh_r7.php` membuat DB sementara dari baseline, memigrasikan sampai
versi terbaru, menjalankan rangkaian R1–R6 termasuk perjalanan revisi, kemudian
memulihkan `.env` dan menghapus DB sementara. Pastikan ringkasan akhirnya
`HIJAU` dan kedua cleanup `OK`.

`uji_keamanan_warga_r7.php` membuktikan rate limit, CSRF, dan cleanup data uji.
Hasil browser melengkapi skrip; screenshot bukan pengganti pemeriksaan
anti-IDOR, immutable snapshot, submit idempoten, dan CAS keputusan yang sudah
ada di `uji_pendataan_warga_r6.php`.

## Checklist pembicara

- Ucapkan “simulasi” setiap kali menyebut SIMPERUM atau kelayakan.
- Jangan mengatakan status sudah tersinkron ke SIMPERUM.
- Jangan menampilkan `.env`, query DB berkredensial, path private upload, atau
  NIK non-sintetis.
- Gunakan satu tiket yang sama sepanjang revisi agar jejak lifecycle mudah
  diikuti.
- Bila satu langkah gagal, hentikan cerita sukses; rekam kondisi dan ulang dari
  DB uji bersih.
- Setelah selesai, pastikan akun, queue, file, rate-limit key, DB sementara, dan
  perubahan `.env` telah dibersihkan oleh skrip.

