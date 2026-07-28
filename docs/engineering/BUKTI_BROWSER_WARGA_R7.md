# Bukti Browser Warga R7

**Tanggal:** 28 Juli 2026  
**Lingkungan:** Apache XAMPP lokal, database skema `20260701000020`  
**Status:** lulus lokal; belum di-push atau dirilis

## Permukaan yang diverifikasi

| Permukaan | Desktop | Mobile 390×844 | Hasil |
|---|---:|---:|---|
| Wizard `/warga/pendataan` | Ya | Ya | Tahap, disclaimer simulasi, rekomendasi, pilihan program, dan CTA memakai lebar layar |
| Riwayat `/akun` | Ya | Ya | Tiket, program, label simulasi, tanggal, dan status terbaca |
| Detail Admin Kab/Kota | Ya | Ya | Scope, sumber vs koreksi, ruleset, rekomendasi, dan keputusan terbaca |
| Shell admin | Ya | Ya | Sidebar tidak lagi menyempitkan konten mobile |

## Temuan dan perbaikan

1. Sidebar admin awalnya tetap menjadi flex item selebar 256 px pada mobile
   karena utility Tailwind runtime menimpa posisi lokal. Shell diperbaiki pada
   satu layout bersama agar sidebar mobile keluar dari flow dan konten memakai
   lebar viewport.
2. Kartu pengajuan `/akun` awalnya mempertahankan kolom desktop sehingga nama
   program dan label simulasi terjepit. Kartu kini stack pada mobile dan kembali
   horizontal pada breakpoint desktop.
3. Label simulasi dibuat persisten pada wizard, riwayat warga, antrean, dan
   detail admin; detail hanya menampilkan label itu bila sumber memang
   `simulation`.
4. Review awalnya menyembunyikan seluruh tindakan saat tidak ada rekomendasi
   `eligible/potential`. Kini state dapat diajukan menampilkan pilihan radio +
   CTA **Ajukan program yang dipilih**; state `needs_data`/`not_eligible`
   menjelaskan alasan dan memberi tindakan periksa data atau lihat layanan lain.
   Diverifikasi desktop dan viewport mobile 390×844; status demo yang diubah
   sementara untuk visual check dikembalikan dan tidak ada pengajuan dibuat.

5. Tombol **Unggah** tanpa berkas semula membuka halaman 404. Setelah guard
   unggah dipisahkan, browser tetap berada di `/warga/pendataan` dan toast
   “Pilih berkas JPG/PNG terlebih dahulu.” tampil pada langkah yang sama.
   Check R4 membuktikan tidak ada ledger/file baru; unggah valid dan penolakan
   ownership palsu tetap bekerja.
6. Pusat notifikasi global diverifikasi pada dua shell. Portal Warga
   menampilkan error unggah sebagai `role="alert"` dengan tombol tutup dan
   tanpa dialog; dashboard admin menampilkan error validasi “Nama perusahaan
   wajib diisi.” lewat komponen yang sama. Form admin hanya diisi spasi sehingga
   berhenti pada validasi server sebelum ada perubahan data. Lonceng admin palsu
   tidak lagi ada.

## Console

Tidak ditemukan error JavaScript baru dari aplikasi Warga/Admin. Yang terlihat:

- warning `cdn.tailwindcss.com should not be used in production`;
- error eksternal reCAPTCHA `Missing required parameters: sitekey` pada login
  lokal karena kunci kosong.

Keduanya sudah merupakan kondisi lama/keputusan sadar yang tercatat di
`AGENTS.md`; bukan regresi R7. Tidak ada klaim bahwa API SIMPERUM nyata telah
terhubung.

## Catatan alat

Kontrol klik Alpine pada browser uji tidak mengubah state tombol menu maupun
tema, walau layout, state awal, atribut aksesibilitas, CSS, dan seluruh layar
lain dapat diperiksa. Karena perilaku yang sama terjadi pada tombol tema lama,
ini dicatat sebagai keterbatasan surface pengujian, bukan dilaporkan sebagai
bukti sukses karangan. Toggle tetap harus disentuh sekali dalam walkthrough
manual sebelum presentasi.
