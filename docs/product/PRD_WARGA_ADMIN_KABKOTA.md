# PRD Alur Warga ↔ Admin Kabupaten/Kota

**Tanggal:** 27 Juli 2026  
**Domain:** pengajuan program perumahan (`sf_housing_queue`)

> **Batas dokumen:** ini mencatat pengamanan alur prototipe diagnosa yang sudah
> selesai di lokal. Form intake sederhana akan diganti oleh wizard komprehensif
> dalam [`PRD_FORM_WARGA_SIMPERUM.md`](./PRD_FORM_WARGA_SIMPERUM.md). Aturan
> scope, transisi, dan kejujuran hasil di dokumen ini tetap berlaku. Urutan
> implementasi aktif ada di
> [`ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md`](./ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md).

## Kartu Domain

| Pertanyaan | Keputusan |
|---|---|
| Tabel induk | `sf_housing_queue` |
| Pemilik | `user_id`; boleh `NULL` karena pengajuan tanpa akun tetap didukung |
| Kardinalitas | Banyak pengajuan per warga; setiap baris adalah riwayat mandiri |
| Status | `status_antrean`: `pending`, `approved`, `rejected` |
| Scope | `kabupaten_id`; wajib terisi untuk pengajuan baru. Profil warga login menang atas pilihan form; pilihan tamu wajib cocok dengan tabel `kabupaten` |
| PII | NIK, nama lengkap, data SIMPERUM, data survei sosial-ekonomi |

Reviewer resmi adalah `admin_kabkota` untuk wilayahnya. Superadmin (`admin`)
boleh meninjau lintas wilayah. Pengajuan lama tanpa wilayah hanya terlihat
superadmin; pengajuan baru tanpa wilayah ditolak sebelum baris dibuat.

## Tabel Transisi

| Status asal | Aksi | Status tujuan | Aktor | Efek turunan |
|---|---|---|---|---|
| `pending` | setujui | `approved` | admin wilayah/superadmin | catatan penolakan dikosongkan |
| `pending` | tolak | `rejected` | admin wilayah/superadmin | catatan wajib disimpan |
| `approved` | koreksi keputusan | `rejected` | admin wilayah/superadmin | catatan wajib disimpan |
| `rejected` | koreksi keputusan | `approved` | admin wilayah/superadmin | catatan lama dikosongkan |

Pengiriman ulang status yang sama ditolak. Setiap perubahan menulis
`reviewed_by` dan `reviewed_at`; update menyertakan status asal agar dua tab
admin tidak saling menimpa.

## Inventaris Permukaan

| Permukaan | Yang boleh ditampilkan/dilakukan |
|---|---|
| `Program/diagnosa/*` dan `solusi_pembiayaan` | Membuat pengajuan `pending` dari identitas dan hasil kelayakan yang tersimpan di sesi server |
| `Program/cek_tiket` | Semua status, setelah tiket + 4 digit NIK cocok; tanpa PII atau catatan admin |
| `/akun` | Semua pengajuan milik `user_id` sesi, termasuk catatan keputusan |
| `Admin_Kabkota` | Hanya baris dengan `kabupaten_id` sama dengan scope sesi |
| `Admin` | Semua wilayah, termasuk data lama yang belum terpetakan |

Tidak ada direktori publik untuk domain ini. Tidak ada endpoint edit pemohon
setelah pengajuan dibuat; koreksi data membutuhkan alur produk baru.

## Gerbang Pengajuan

Satu fungsi di `Program_model` wajib memastikan:

1. identitas dan hasil kalkulasi berasal dari sesi server dan belum kedaluwarsa;
2. NIK, nama, data survei, dan kabupaten valid;
3. program yang dipilih termasuk hasil kelayakan dan masih aktif di database;
4. baris hanya dibuat dengan status awal `pending`.

## Definisi Selesai

Satu skrip PHP CLI + curl membuktikan perjalanan:

`diagnosa → pilih program → kirim → terlihat admin wilayah → disetujui → status tiket berubah`

Uji negatif wajib mencakup program hasil manipulasi, wilayah kosong/salah,
admin wilayah lain, dan transisi status ilegal.

## Batas Fase

Fase ini hanya menuntaskan antrean perumahan Warga ↔ Admin Kab/Kota. Domain
aduan Warga ↔ Admin Bidang dikerjakan sebagai alur berikutnya. NIK pada tabel
queue masih mengikuti penyimpanan plaintext legacy; perubahan enkripsi wajib
punya migrasi data tersendiri karena menyentuh data lama dan kunci produksi.
