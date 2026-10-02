# Kebersihan fitur, dokumentasi, sampel, dan konfigurasi - temuan, perbaikan, dan penjaga

Menjawab satu butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 13 "Konfigurasi"):

- **13.3** Menghapus fitur, dokumentasi, sampel, dan konfigurasi yang tidak diperlukan.

Dokumen ini tidak memuat alamat IP, nama akun hosting, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Temuan (21 Sep 2026)

| # | Temuan | Risiko |
|---|---|---|
| 1 | Sebuah dokumen di repo publik mencantumkan **akun demo lengkap dengan kata sandi bawaan `password`**, dan halaman login (layar publik) memajang panel "Kredensial Demo" yang mengisi otomatis kata sandi itu. Diperiksa terhadap database live: **11 akun masih memakai kata sandi tersebut**, termasuk akun admin dinas. | Pengambilalihan akun berwenang oleh siapa pun yang membaca repo atau layar login |
| 2 | Dokumen operasional (runbook rilis, catatan staging, catatan agen) memuat alamat IP server dan nama akun hosting; repo publik | Peta infrastruktur untuk penyerang |
| 3 | Direktori `docs/archive/` dan 12 view/aset "arsip" ikut ter-deploy, plus 9 view dan 2 aset tanpa satu pun pemanggil (`welcome_message`, `registrasi`, halaman kemitraan lama, dst.) | Permukaan serangan dan dokumentasi usang; beberapa arsip memuat detail infrastruktur |
| 4 | Migrasi 055 membuat data demo sertifikat KKN **di semua lingkungan**, termasuk production; di live sudah ada 2 pendaftaran demo yang dapat dicari publik lewat NIM sebagai sertifikat "sah" | Data fiktif tersaji sebagai hasil resmi |
| 5 | Mode simulasi SIMPERUM (data fiktif) ditentukan `.env`; env yang hilang/salah ketik jatuh ke `simulation` | Data fiktif tersaji sebagai hasil pencarian resmi |
| 6 | Salinan konfigurasi `.env.bak-*` di DocumentRoot server (bukan dotfile murni; cadangan berisi rahasia). `.htaccess` hanya menolak pola yang dikenal | Bocor bila polanya luput |
| 7 | DocumentRoot menyajikan **apa saja yang ada** (mis. `license.txt`, `composer.*`, README) kecuali pola yang dilarang satu per satu | Kelas kebocoran yang sama berulang (log, dump, cadangan) |

## 2. Yang diperbaiki

| Temuan | Perbaikan |
|---|---|
| 1 | Kredensial dihapus dari dokumen akun login. Panel "Kredensial Demo" sempat dicabut dari halaman login, modal login, dan halaman syarat pengembang (commit 15ed468), lalu **dikembalikan sementara pada 21 Sep 2026 atas keputusan pemilik produk karena sistem masih uji coba** (AGENTS.md §20); tes membatasinya pada tiga layar itu. Alat `docs/engineering/bersihkan_data_sampel.php` menemukan akun ber-kata-sandi-bawaan dari daftar kata sandi umum (dry-run bawaan; `--ya` mengubah: admin diputar acak dan wajib ganti, akun lain dinonaktifkan, tidak dihapus). **Alat itu belum dijalankan di live**, lihat bagian 4. |
| 2 | Alamat IP, akun hosting, dan pengenal akun disamarkan di seluruh dokumen. Riwayat git **tidak** ditulis ulang (lihat bagian 4). |
| 3 | `docs/archive/`, seluruh `archive/` di views dan aset, 9 view yatim, `style.css` dan `script.js` lama dihapus. Tes memastikan tiap view punya pemanggil. |
| 4 | Migrasi 055 tidak berbuat apa pun di production. Alat pembersih menghapus data demo yang sudah terlanjur ada (`DEMO-SERTIFIKAT-KKN-%`). |
| 5 | `config/simperum.php` memaksa `api` di production, apa pun isi `.env`. Alat pembersih menghapus antrean/snapshot simulasi yang sudah terlanjur ada. |
| 6-7 | `.htaccess` kini memakai **daftar izin**: berkas atau direktori yang benar-benar ada di luar `index.php`, `push-sw.js`, `manifest.webmanifest`, `assets/`, `.well-known/` dijawab 403. Alamat virtual aplikasi (bukan berkas nyata) tidak terkena. Daftar tolak per pola tetap ada sebagai lapis kedua. |

## 3. Penjaga

`tests/repo_hygiene_test.php` (offline) menggagalkan bila: berkas mati kembali atau ada `archive/`; ada view tanpa pemanggil; aturan daftar izin `.htaccess` dilonggarkan atau dipindah setelah front-controller; akar proyek memuat cadangan/dump/salinan `.env`; repo memuat alamat IP (selain rentang dokumentasi RFC 5737 dan contoh bawaan CodeIgniter) atau nama akun hosting; dokumen akun memuat kata sandi bawaan; panel demo muncul di view di luar tiga layar yang diizinkan; mode simulasi aktif di production; migrasi 055 kehilangan guard production.
**Uji mutasi:** pelonggaran daftar izin, mode simulasi di production, panel demo muncul di view baru, kata sandi bawaan di dokumen, IP di dokumen, view tanpa pemanggil, dan salinan `.env` di akar masing-masing menggagalkan tes.

## 4. Yang belum dan batas yang diakui

- **Panel Kredensial Demo aktif kembali (sementara)**: kata sandi bawaan tertulis di layar login publik selama uji coba. Syaratnya (AGENTS.md §17 poin 12): dicabut begitu sistem memuat data warga sungguhan atau ada akun di panel yang memegang wewenang nyata; akun admin dinas di live sudah berkata sandi itu, jadi **cabut sebelum dipakai sungguhan**.
- **Akun ber-kata-sandi-bawaan di database live belum dinonaktifkan** dan data demo/simulasi di live belum dihapus: itu keputusan pemilik produk (panel demo dulu sengaja dipasang atas permintaan dinas untuk uji coba, AGENTS.md §20). Yang sudah dilakukan hanya menghapus kredensial dari dokumen; panel di layar login dikembalikan untuk uji coba. **Selama akun-akun itu aktif, kata sandinya tetap dianggap bocor** (pernah tertulis di dokumen publik dan di layar login). Jalankan alat pembersih (dry-run dulu) setelah ada keputusan.
- **Riwayat git tidak ditulis ulang**: alamat IP dan kredensial yang pernah dikomit tetap terbaca di riwayat repo publik. Menghapusnya menuntut penulisan ulang riwayat (force-push) dan berdampak ke semua kloning; tidak dikerjakan tanpa perintah eksplisit. Alamat yang bocor sebaiknya diperlakukan sudah diketahui.
- **Deploy tidak menghapus berkas yang dihapus dari repo** (catatan rilis 7.3/4.6): berkas yang dibuang di sini harus dibuang manual dari server; `.env.bak-*` di server dipindah keluar DocumentRoot manual.
- Tes memeriksa repo, bukan server: berkas tak dikenal yang dibuat runtime di server hanya tertahan oleh daftar izin `.htaccess`.
- Pemeriksa "view tanpa pemanggil" berbasis pencocokan nama; view yang dimuat lewat nama yang dirakit dari variabel akan dianggap yatim (belum ada satu pun; pola itu juga dilarang `tests/dynamic_code_test.php`).
