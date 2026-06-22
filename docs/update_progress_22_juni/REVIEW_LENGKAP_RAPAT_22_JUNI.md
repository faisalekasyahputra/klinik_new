# Review Lengkap: Hasil Rapat & Arahan Transformasi Klinik PKP (22 Juni 2026)

Dokumen ini merupakan hasil peleburan (_merging_) dari seluruh catatan kasar, transkripsi audio rapat 22 Juni, identifikasi UI/UX, pendefinisian core identity, serta divalidasi penuh oleh catatan teknis (Mas Gema - ASN). Dokumen ini berfungsi sebagai **Jembatan/Review Pra-PRD** sebelum kita memperbarui `PRODUCT_REQUIREMENTS_DOCUMENT.md` dan `IMPLEMENTATION_ROADMAP.md` yang sudah ada di _project_ ini.

---

## 1. Visi, Fungsi Utama, & Perubahan Paradigma (The "Why")

Klinik PKP bertransformasi dari profil statis menjadi **Bank Data & Layanan Terpadu (Onboarding Journey)**.

- **Nama Program Besar (Grand Program)**: _"Ngopeni Omah Nglakoni Sesarengan"_ (Bukan sekadar _tagline_, ini adalah nama payung besar yang membawahi seluruh pilar).
- **Fokus Sistem**: Mengintegrasikan data secara mulus (_seamless_), menyajikan "iklan" program-program pemerintah secara interaktif.

### Core Identity: Fungsi Utama Klinik PKP

Sebagai pusat layanan terpadu, Klinik PKP hadir dengan **3 fungsi pelayanan utama** bagi masyarakat:

1. **Informasi**: Rekomendasi dan panduan sesuai kebutuhan.
2. **Bantuan Teknis**: Pendampingan mulai dari tahap perencanaan hingga pelaksanaan.
3. **Pendampingan**: Konsultasi yang terarah dan mudah diakses.

Secara desain antarmuka, penjabaran 3 fungsi utama ini diletakkan sebagai "Ice Breaker" tepat di bawah Hero Slider Beranda, bertugas menyambut dan meyakinkan masyarakat.

---

## 2. Pemetaan Arsitektur UI & Navigasi (Sitemap)

Berdasarkan struktur terbaru, _navbar_ utama dan pembagian fitur dirancang sangat spesifik menjadi komponen-komponen berikut:

### A. Struktur Navbar

Total akan ada 6 komponen utama di Navbar:

1. **Menu Utilitas (1)**: (Beranda / Logo)
2. **Menu Pengembang**: Diarahkan ke halaman _view-only_ yang berisi daftar SP2.
3. **Layanan Utama 1: Perumahan** (Dilengkapi _Sub-Navbar_ / _Dropdown_)
4. **Layanan Utama 2: Kawasan** (Dilengkapi _Sub-Navbar_ / _Dropdown_)
5. **Layanan Utama 3: Pertanahan**
6. **Menu Utilitas (2)**: (Tombol Login/Akun)

### B. Pemetaan Sub-Menu & Fitur ke dalam 3 Layanan Utama

Fitur-fitur lama dan baru kini dipetakan dengan rapi ke bawah 3 pilar layanan utama:

#### Pilar 1: Perumahan (Fokus Utama Terintegrasi SIMPERUM)

- **Bank Data**: Berfungsi sebagai pusat statistika yang menarik data (BNBA dll) dari berbagai API.
- **Info KPR**: Masuk ke dalam pilar ini.
- **Bank Desain**: Masuk ke dalam pilar Perumahan.
- **Onboarding Program (Ngopeni Omah Nglakoni Sesarengan)**: Terdiri dari 3 Klaster Utama:
  1. **Pembangunan Baru (PB)**: Termasuk di dalamnya program Rumah Relokasi, Rumah Bencana, dan HPBD.
  2. **Peningkatan Kualitas**: Termasuk RTLH dan PKN.
  3. **Pembiayaan Perumahan Murah**: Termasuk KPR-KTP, KUR-KPP, Omah Sekeng, dan Omah Lestari.

#### Pilar 2: Kawasan (Permukiman)

- **Data Spasial**: Semua bentuk pemetaan spasial dan kewilayahan.

#### Pilar 3: Pertanahan

- _(Belum didetailkan di fase ini, disiapkan ruangnya untuk pengembangan ke depan)._

### C. Hero Section (Slider Utama & Etalase Program)

- Menggunakan _slider/carousel_ yang memuat 3 slide utama (Perumahan, Kawasan, Pertanahan).
- Khusus di bawah sektor Perumahan, Dashboard berfungsi murni sebagai **"Etalase/Iklan Program"** untuk RTLH, PB, dll.

---

## 3. Alur Pengguna & Logika Backend (Validasi Onboarding Journey)

Berdasarkan konfirmasi alur teknis, arsitektur logikanya berjalan persis seperti ini:

1. **Etalase / Iklan Program**: Pengguna melihat program perumahan. Di dalamnya muncul detail kegiatan dan _Syarat Penerima_.
2. **Call to Action**: Jika tertarik dan ingin mengajukan bantuan yang mana, pengguna melakukan _klik_.
3. **Formulir Data Diri**: Setelah diklik, sistem baru memunculkan form input data diri (Terintegrasi API NIK/SIMPERUM).
4. **Housing Queue**: Ketika data selesai diketik dan masuk, data tersebut langsung berstatus sebagai **Housing Queue (Antrean Perumahan)**.
5. **Smart Filter & Validasi**: Sistem menyaring kelayakan secara manual/audit sebelum program benar-benar direalisasikan.

---

## 4. Langkah Selanjutnya (Next Steps)

1. **Update Dokumentasi Inti**:
   - Menuangkan struktur lengkap ini ke dalam `PRODUCT_REQUIREMENTS_DOCUMENT.md`.
2. **Mulai Pengerjaan Kode (Coding)**:
   - Membuat/mengedit `nav.php` untuk memuat Navbar baru.
   - Mengedit `awal.php` untuk merombak UI Hero dan "Etalase Iklan" Program.
