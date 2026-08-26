# Revisi Dinas Putaran 2 - Rekonstruksi & Status

> Disusun **24 Agt 2026** dengan membaca riwayat git, bukan dari daftar aslinya.
> Baca peringatan di bawah sebelum memakai dokumen ini untuk menjawab apa pun.

## 🔴 Batas dokumen ini - baca dulu

**Daftar 24 butir aslinya TIDAK PERNAH MASUK REPO INI.** Berbeda dengan revisi
5 Agt yang punya tabel 23 butirnya sendiri di
[`ROADMAP_REVISI_5AGT.md`](ROADMAP_REVISI_5AGT.md), putaran ini hanya meninggalkan
jejak di badan commit. Isi tabel di bawah direkonstruksi dari situ.

Konsekuensinya keras dan tidak bisa disiasati:

1. **Sepuluh nomor tidak pernah disebut commit mana pun: 1, 6, 9, 10, 11, 13, 15,
   17, 19a, 22.** Untuk nomor-nomor itu, "sudah selesai tanpa menyebut nomornya"
   dan "belum tersentuh sama sekali" **tidak bisa dibedakan** dari repo. Jangan
   melaporkan keduanya sebagai selesai, dan jangan pula mengerjakannya ulang
   sebelum memastikan.
2. **Isi tiap butir di bawah adalah tafsir dari pekerjaan yang mendarat**, bukan
   kalimat asli dinas. Kalau kalimat aslinya berbeda, yang benar kalimat dinas.
3. **Kalau user masih memegang daftarnya, salin ke sini.** Selama belum, satu-satunya
   jawaban jujur atas "sisa berapa" adalah "tidak diketahui" - dan itu jawaban yang
   buruk untuk dibawa ke rapat berikutnya.

> Penomorannya BERBEDA dari roadmap 5 Agt. "Butir 4" di sini bukan "butir 4" di sana.

## Yang mendarat (15 butir, 10-11 Agt 2026)

| Butir | Isi | Commit | Migrasi |
|---|---|---|---|
| **2** | Tombol "Semua" di penyaring cari rumah. Mesinnya sudah mengenal `semua` sejak toggle lama diganti; yang hilang cuma tombolnya | `1369719` | - |
| **3** | "Komersil" jadi "Non Subsidi" **di badge kartu** - kata itu dicetak apa adanya dari SIKUMBANG, bukan dari tombol pilihan (tombolnya sudah benar sejak 3 Agt). Diterjemahkan saat ditampilkan; istilah asing dibiarkan lewat supaya jenis baru tidak hilang senyap | `1369719` | - |
| **4** | Desain prototipe beserta RAB dari API KRS Jawa 3 | `197dbc2` | - |
| **5** | Tanggal lahir dilepas dari Cek Data Rumah | `4e02817` | - |
| **7** | SRP2 status bertingkat, empat tahap. DEFAULT `bersertifikat` (67 baris di tabel bernama `srp2_certified_developers` memang sudah bersertifikat; default lain justru berbohong tentang mereka) | `0d337af` | `040` |
| **8** | Satu NIK satu akun warga - NIK sebagai kunci identitas, ditegakkan UNIQUE pada sidik deterministiknya | `0d337af`, `07c4d0d` | `040`, `041` |
| **12** | SRP2: kabupaten, asosiasi, dan NPWP. NPWP diperlakukan **persis seperti NIK warga**: ciphertext + sidik ber-pepper + UNIQUE di sidiknya, dan hanya angkanya yang disimpan | `0d337af` | `040` |
| **14** | Tombol Dashboard untuk SEMUA peran yang login, bukan `admin` saja - sebelumnya lima peran lain tidak punya satu pun jalan ke dashboardnya dari situs publik. Sidebar dapat "Kembali ke beranda"; sebelumnya satu-satunya jalan keluar adalah KELUAR AKUN. **Susulan `750fee2` (20 Agt): tombolnya semula hanya ada di `main.php` (desktop)** - menu hamburger mobile sejak itu tidak punya jalan ke dashboard sama sekali. Pola yang berulang: satu tautan ditambahkan ke satu layout, layout keduanya terlewat karena tidak ada yang membukanya di layar kecil | `1369719`, `750fee2` | - |
| **16** | Detail aduan, read-only. Sebelumnya isi aduan terpotong di daftar sehingga admin men-triase dari judul saja lalu salah teruskan | `1369719` | - |
| **18** | KKN "tidak bisa mendaftar" - ternyata **bukan** fitur yang hilang melainkan gerbang login yang tak terduga. Dari sini lahir butir 24 | `1369719` | - |
| **19b** | Tombol "Daftar" di bidang yang masih menerima, plus keterangan "perlu masuk dulu" bila belum login | `1369719` | - |
| **20** | Cek status pengajuan dicabut dari situs publik, dialihkan ke dashboard (**tidak** di-404-kan: alamatnya sudah tersebar). Endpoint tiketnya sengaja tetap hidup untuk halaman sesudah pengajuan terkirim | `011d868`, `5bc27b8` | - |
| **21** | Isian NIK di Profil Saya beserta kuncinya | `07c4d0d` | `041` |
| **23** | Unduhan rekap setahun untuk perumahan dan kawasan | `8df9d4f` | - |
| **24** | Pilihan (a), keputusan user: warga/pengembang/mahasiswa **tidak lagi** didaratkan di dashboard sesudah login; tanpa halaman asal mereka ke beranda. Dasarnya terukur - dashboard ketiga peran itu cuma berisi dua menu, sementara yang mereka cari ada di situs publik. Peran staf tetap ke dashboard (admin punya 14 menu) | `1369719` | - |

## Tiga pelajaran yang lebih mahal daripada butirnya

**1. Temuan user lebih tajam daripada temuan kami: `gerbang_login()` melayani dua
keadaan berbeda dengan cara yang sama.** Belum login lalu dilempar ke halaman
masuk itu masuk akal. SUDAH login tapi salah peran lalu dilempar ke halaman masuk
**tidak** - yang dialami orangnya: sesinya sehat, tapi tiba-tiba diminta login
lagi, lalu terlempar entah ke mana. Pesan "Anda bukan Admin Kabupaten/Kota"
sebenarnya sudah bagus, tapi mendarat di halaman masuk, tempat orang tidak
mencarinya. Sekarang yang salah peran mendarat di `Auth/akses_ditolak`, ditulis
sebagai **halaman, bukan modal** - modal yang bisa ditutup meninggalkan orang di
halaman kosong, karena aksesnya memang ditolak.

**2. `SELECT *` mengubah "menambah kolom" jadi "menerbitkan kolom".**
`Pengembang::profil()` memakai `SELECT *`, jadi menambahkan kolom NPWP saja sudah
cukup untuk mengirimkannya ke view PUBLIK tanpa ada yang menyentuh baris itu -
kebalikan persis dari yang diminta. Kolomnya kini disebut satu per satu.
**Sebelum menambah kolom sensitif, grep `SELECT *` di jalur bacanya.**

**3. Empat cek kebocoran TETAP HIJAU saat mutasi mengembalikan `SELECT *`.**
View-nya memang hanya mencetak medan tertentu, jadi ciphertext berhenti di memori
PHP. Ceknya benar (tidak ada yang bocor ke halaman) tapi tidak menjaga daftar
SELECT-nya. Penjaga strukturallah yang dibutuhkan - dan versi pertamanya merah
oleh **komentar penjelasan kami sendiri**, jebakan pencocokan substring yang sudah
dicatat 5 Agt dan tetap terulang.

## Sisa dari roadmap 5 Agt yang masih menunggu dinas

Tiga butir, semuanya nol pekerjaan kode: A1 (5 foto program), butir 4 lama
(keterangan 11 formulir SRP2), butir 8b lama (buku panduan + leaflet KKN).
Rinciannya di [`ROADMAP_REVISI_5AGT.md`](ROADMAP_REVISI_5AGT.md).
