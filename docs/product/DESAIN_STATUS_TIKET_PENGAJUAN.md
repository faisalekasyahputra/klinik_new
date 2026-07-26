# Konsep Status Tiket Pengajuan

**Status:** Tahap 1 terverifikasi terhadap database staging; hardening NIK dan pengujian keamanan lanjutan belum selesai
**Tanggal:** 23 Juli 2026
**Ruang lingkup:** Pengajuan program perumahan dari alur `solusi_pembiayaan` dan `Program/diagnosa/{kode_program}`

## Keputusan Produk

Klinik PKP menggunakan pendekatan **hybrid**:

1. Warga dapat memantau pengajuan **tanpa login** memakai nomor tiket.
2. Login bersifat **opsional**, tetapi memberi akses ke riwayat pengajuan, profil, dan notifikasi yang lebih lengkap.
3. Warga tidak boleh dipaksa membuat akun hanya untuk mengirim atau mengecek satu pengajuan.

Keputusan ini menjaga akses publik tetap mudah, sekaligus menyediakan pengalaman akun untuk warga yang sering memakai layanan.

## Alur Pengguna

```text
Diagnosa → Pilih program → Ajukan
        → Sistem membuat nomor tiket unik
        → Halaman sukses: nomor tiket + Cek Status + Masuk Akun

Cek Status → Nomor tiket + verifikasi tambahan → Status pengajuan
Login      → Dashboard akun → Riwayat dan detail pengajuan
```

### Halaman sukses

Halaman sukses mengikuti tema cerah dan komponen portal terbaru:

- ikon sukses sederhana dengan warna brand;
- nomor tiket ditampilkan jelas dan bisa disalin;
- tombol utama **Cek Status dengan Tiket**;
- tombol sekunder **Masuk ke Akun**;
- informasi estimasi verifikasi dan kanal notifikasi WhatsApp;
- tanpa confetti otomatis yang mengganggu aksesibilitas.

## Desain Tiket dan Keamanan

- Tiket memakai kode acak yang tidak berasal langsung dari `sf_housing_queue.id`, contoh `PKP-7F4K9X`.
- Lookup tiket meminta nomor tiket dan satu verifikasi tambahan, misalnya nomor WhatsApp atau empat digit terakhir NIK.
- Jangan menampilkan NIK, alamat, atau dokumen pribadi pada URL maupun halaman publik.
- Endpoint lookup membatasi lima percobaan gagal per hash IP dalam satu menit, lalu mengembalikan HTTP 429 dan pesan generik.
- Status publik: `Menunggu verifikasi`, `Disetujui`, atau `Ditolak`.
- Catatan internal admin (`catatan_admin`) **sudah ada** sejak Fase 10 selesai (26 Jul 2026) dan tetap TIDAK ditampilkan oleh lookup publik — hanya terlihat oleh admin berwenang dan oleh pemiliknya sendiri di `/akun`.

## Status Login

| Akses | Kemampuan |
|---|---|
| Tanpa login | Cek satu pengajuan menggunakan tiket + verifikasi tambahan |
| Login warga | Lihat tiket, status, tanggal, dan riwayat pengajuan milik sendiri |
| Admin | ✅ Sudah ada (Fase 10 selesai): superadmin melihat seluruh antrean lintas wilayah lewat `Admin`, admin kabupaten/kota hanya wilayahnya lewat `Admin_Kabkota` — pembatasan ditegakkan di query (`WHERE id AND kabupaten_id`), bukan hanya di UI |

Pengajuan guest tetap boleh memiliki `user_id = NULL`. Jika warga kemudian membuat akun, pengaitan pengajuan dilakukan melalui proses terverifikasi, bukan dari input `user_id` bebas.

## Rencana Implementasi

1. Tambah penyimpanan `ticket_code` unik pada `sf_housing_queue`.
2. Buat generator tiket server-side dan simpan kodenya bersama row pengajuan.
3. Simpan kanal verifikasi secara aman; jangan masukkan data sensitif ke URL.
4. Buat route dan halaman cek status tiket tanpa login.
5. Ubah halaman sukses ke tema cerah dan tampilkan tiket yang baru dibuat.
6. Tambahkan daftar pengajuan milik user di dashboard akun.
7. Tambahkan rate limit pada lookup publik.
8. Lakukan audit log seperlunya dan pengujian keamanan lanjutan sebelum deploy produksi.

## Status Implementasi Tahap 1

- Terverifikasi di database staging: generator kode tiket, migration kolom unik, dan penyimpanan tiket dari kedua jalur pengajuan.
- Terverifikasi di database staging: lookup publik khusus tiket `PKP-*` + empat digit terakhir NIK tanpa data pribadi pada respons.
- Terverifikasi di database staging: halaman sukses menampilkan tiket yang sama dengan row pengajuan.
- Terverifikasi di database staging: riwayat `/akun` mengambil dan menampilkan pengajuan milik user sesi.
- Terverifikasi di database staging: lima kegagalan lookup diizinkan dan percobaan keenam diblokir dengan HTTP 429.
- Belum selesai: enkripsi NIK antrean, pengujian keamanan lanjutan, notifikasi WhatsApp, dan verifikasi deployment web staging/production.

## Batasan Versi Pertama

- Notifikasi WhatsApp otomatis belum menjadi bagian inti; versi pertama cukup menampilkan kanal dan estimasi proses.
- Pengajuan tetap masuk ke `sf_housing_queue` dan menunggu validasi admin.
- Generator PDF atau kartu digital tidak diperlukan untuk fitur cek tiket awal.
