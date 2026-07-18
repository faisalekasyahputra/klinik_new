# PKP Provinsi JATENG

May 13, 2026

## Pak Ketua Disperakim

- Materi ingin dijabarkan sejak awal.
- Saat pelaporan, jawaban ingin langsung diberikan (diharapkan memanfaatkan AI).
- Membutuhkan sistem tanya jawab interaktif (FAQ) agar pengguna tidak perlu menunggu jawaban dari Mas Galung di Disko.
- Menginginkan PRD (Product Requirements Document) yang lengkap.
- Menginginkan jadwal dan timeline yang jelas.
- Klinik ini diharapkan menjadi semacam *super app* untuk pelayanan Disperakim.

## Bu MC

- Program disesuaikan dengan lokasi (perlu dikoordinasikan ulang).

## Dinas Perumahan

- Data yang ditampilkan bersumber dari SIMPERUM.
- Perlu kejelasan tanggung jawab atas keamanan data pengguna (dari sisi Klinik PKP).
- Akses API diberikan berdasarkan permintaan (*by request*).
- Perlu mitigasi karena data SIMPERUM banyak memuat data pribadi; keamanan data dari SIMPERUM harus terjaga.
- Inti perhatian dari pihak SIMPERUM adalah keamanan data yang diambil melalui API.

## BIMA (Sekretariat)

- Saat menyampaikan pengaduan, apakah pengguna dapat menerima notifikasi terkait perkembangannya?
- Perlu sistem tiket untuk pengaduan; pertanyaan yang termasuk FAQ dapat dijawab otomatis oleh sistem, sedangkan pertanyaan di luar FAQ diteruskan ke petugas.
- Perlu penyaringan (filtering) berdasarkan golongan pertanyaan; form pertanyaan dibuat bertahap agar lebih spesifik dan tepat sasaran.
- Pengaduan dipisahkan/segmentasi menjadi kategori "Lapor" dan "Tanya".
- Perlu menampilkan program unggulan.
- Dilakukan audit per fitur.

## Catatan Umum (Campur)

- Di halaman depan terdapat *slide show* dan *navbar* berisi menu program.
- Rekomendasi teknis (Rekomtek) dapat diproses secara paralel.
- Tersedia fitur simulasi perumahan (*housing*).

### Komentar UI

- Gunakan gambar latar (background) depan yang relevan dengan tema perumahan.
- Tambahkan widget simulasi KPR dari bank.
- Sertakan tautan ke Tapera.
- Daftar publikasi diisi oleh admin.
- Tambahkan menu desain dari KRSJawa3.
- Tambahkan menu kumpulan link.

# Data simperum

KKN

psn

rusun

ada filter yang mana daftar tunggu mengkluster

smart filter

BNBA itu dikasih data ditarik dari API dari database yang ada , klaster baru masuk ke pkp untuk bnba simperum

jadi relasi data dari NIK dari API simperum
kuran form

oemah lestari

ighp

profil ngopeni omah nglekoni sesarengan

integrasi seemless
lebih menyampaikan pesan

yang kita butuhkan ,
bank data ini fungsinya integrasi data

kurang mengetahui problem
rapat dulu senin

ngobrol lebih dalam perumahan

harapan
tujuan
Masalah

PKP pagi ini

bayangan dashboard  , masayarakat tau program

— menu dashboard

intinya

pembangunan baru hanya dari APBD Provinsi dan APBD kabupaten

program pembiayaan perumahan

data diri , daftar housing karir ,

onboarding konsep ,

untuk halaman pengembang hanya view saja , di list akan menampilkan SP2(sertifikat pengembang)

Ngopeni omah nglakoni sesarengan

menu navbar prumahan ,kawasan ,pertanahan

buat card data program untukprogram pemerintah di ihero
profil dihidden

Summary


22 juni 2026

### Tindak Lanjut

- [ ] Fokus pengembangan pada konsep SIMPERUM dan alur onboarding hingga hari Minggu
- [ ] Pelajari dan dokumentasikan detail setiap program perumahan (RTLH, PPP, KPR-KTP, dll.) secara terpisah
- [ ] Sesuaikan tampilan dashboard berdasarkan konsep alur pengguna yang telah didiskusikan
- [ ] Siapkan modul berdasarkan konsep yang telah disepakati untuk didalami lebih lanjut
- [ ] Kirim pertanyaan jika ada setelah sesi ini

---

### Struktur Program Perumahan

- Terdapat tiga kategori utama program yang harus ditampilkan di dashboard:
  - **Pembangunan Baru** — bersumber dari APBN dan APBD Provinsi, mencakup program seperti HPBD
  - **Peningkatan Kualitas** — mencakup RTLH dan PKN, bersumber dari APBN, APBD Provinsi, dan APBD Kabupaten
  - **Pembiayaan Perumahan** — mencakup KPR-KTP, KUR-KPP (dari APBN), serta Omah Sekeng dan Omah Lestari (dari APBD Provinsi)
- Program pembangunan baru hanya tersedia di provinsi-provinsi tertentu (top provinces)
- Setiap program memiliki persyaratan dan kriteria penerima bantuan yang berbeda-beda

### Desain Dashboard & Alur Pengguna

- Dashboard dirancang dengan pendekatan **onboarding journey**: pengguna memilih program yang sesuai, lalu sistem memfilter kelayakan berdasarkan data yang dimasukkan
- Setiap program di dashboard harus menampilkan: definisi operasional, persyaratan bantuan, kriteria penerima, dan dokumentasi hasil (foto before/after)
- Pengguna diberi pilihan mandiri (misalnya: ingin renovasi atau bangun baru), namun sistem akan memvalidasi apakah data mereka sesuai dengan program yang dipilih
- Jika tidak memenuhi syarat suatu program, sistem akan menawarkan program lain yang sesuai
- Konsep navigasi dianalogikan seperti pengguna yang masuk ke kantor dan memilih layanan perumahan
- Disepakati bahwa alur dimulai dari pengenalan program terlebih dahulu, bukan langsung ke formulir/registrasi

### Integrasi Data & SIMPERUM

- Data yang sudah ada di SIMPERUM (realisasi intervensi dan database) akan digunakan sebagai basis
- Fokus saat ini adalah pada integrasi dengan SIMPERUM
- Terdapat empat sumber data yang digunakan (disebutkan terkait API dan elemen PKP)
- Data NIC (NIK) pengguna akan ditarik dari SIMPERUM jika sudah terdaftar; jika belum, akan ditambahkan sebagai data baru

### Proses Registrasi & Validasi Data

- Pengguna yang berminat memasukkan data sendiri secara lengkap; setelah selesai, data dikirim ke antrian (hosting carrier) sesuai program yang dipilih
- Data yang masuk akan disimpan di database sistem dan dikirim ke SIMPERUM
- Proses validasi di SIMPERUM (misalnya ketersediaan kuota) masih perlu dikonfirmasi mekanismenya
- Beberapa program (APBD, WB, PK) memerlukan pengecekan data terpusat; program lain seperti KPAR dan PPP tidak
- Untuk keperluan legal/audit, disepakati agar proses tidak otomatis penuh — data tetap masuk ke sistem namun perlu dikonfirmasi manual, bukan diproses secara otomatis
- Data yang didaftarkan namun tidak lolos seleksi akan disimpan sebagai **antrian perumahan** untuk periode berikutnya

### Catatan Tambahan

- Konsep alur onboarding sudah dianggap matang; data dan form masih bisa disesuaikan, yang terpenting adalah alurnya
- Beberapa bagian akhir transkrip mengalami degradasi kualitas (pengulangan frasa) dan tidak dapat dirangkum secara akurat
