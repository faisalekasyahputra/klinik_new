# Konsep Status Tiket Pengajuan

**Status:** Tahap 1 implementasi selesai; hardening lanjutan direncanakan
**Tanggal:** 21 Juli 2026
**Ruang lingkup:** Pengajuan program perumahan dari alur `solusi_pembiayaan`

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
- Tambahkan rate limit pada endpoint lookup dan pesan error generik agar tiket tidak mudah dienumerasi.
- Status publik minimum: `Menunggu verifikasi`, `Sedang diverifikasi`, `Disetujui`, `Ditolak`.
- Detail catatan internal admin hanya tersedia untuk pemilik yang login dan admin.

## Status Login

| Akses | Kemampuan |
|---|---|
| Tanpa login | Cek satu pengajuan menggunakan tiket + verifikasi tambahan |
| Login warga | Lihat pengajuan milik sendiri, status, catatan publik, dan riwayat |
| Admin | Melihat seluruh antrean dan mengubah status sesuai kewenangan |

Pengajuan guest tetap boleh memiliki `user_id = NULL`. Jika warga kemudian membuat akun, pengaitan pengajuan dilakukan melalui proses terverifikasi, bukan dari input `user_id` bebas.

## Rencana Implementasi

1. Tambah penyimpanan `ticket_code` unik pada `sf_housing_queue`.
2. Buat generator tiket server-side setelah insert berhasil.
3. Simpan kanal verifikasi secara aman; jangan masukkan data sensitif ke URL.
4. Buat route dan halaman cek status tiket tanpa login.
5. Ubah halaman sukses ke tema cerah dan tampilkan tiket yang baru dibuat.
6. Tambahkan daftar pengajuan milik user di dashboard akun.
7. Tambahkan rate limit, audit log seperlunya, dan uji IDOR sebelum deploy.

## Status Implementasi Tahap 1

- Selesai: generator kode tiket, migration kolom unik, dan penyimpanan tiket saat pengajuan.
- Selesai: lookup publik dengan tiket + empat digit terakhir NIK.
- Selesai: halaman sukses tema cerah dengan salin tiket dan cek status.
- Selesai: riwayat pengajuan milik user di dashboard akun.
- Berikutnya: rate limit endpoint lookup, enkripsi NIK sesuai fondasi repo, dan notifikasi WhatsApp.

## Batasan Versi Pertama

- Notifikasi WhatsApp otomatis belum menjadi bagian inti; versi pertama cukup menampilkan kanal dan estimasi proses.
- Pengajuan tetap masuk ke `sf_housing_queue` dan menunggu validasi admin.
- Generator PDF atau kartu digital tidak diperlukan untuk fitur cek tiket awal.
