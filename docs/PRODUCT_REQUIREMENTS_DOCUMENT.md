# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Klinik PKP — Portal Layanan Informasi Perumahan Terpadu Jawa Tengah

| Atribut | Detail |
|---|---|
| **Nama Aplikasi** | Klinik PKP (Klinik Perumahan dan Kawasan Permukiman) |
| **Versi Dokumen** | v3.0 |
| **Tanggal Terbit** | 22 Juni 2026 |
| **Instansi Penyelenggara** | Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah |
| **Status Dokumen** | Updated — Fase Pivot: Integrasi SIMPERUM & Onboarding Journey |

---

## 1. LATAR BELAKANG, VISI & CORE IDENTITY

### 1.1 Situasi & Transformasi Saat Ini
Sebelumnya, Klinik PKP difokuskan sebagai portal informasi dan forum komunitas. Namun, berdasarkan evaluasi kebutuhan dinas, Klinik PKP kini **bertransformasi penuh menjadi Bank Data & Layanan Terpadu (Onboarding Journey)**. Sistem ini akan menjadi garda terdepan (etalase) yang mengintegrasikan data BNBA secara riil dengan *backend* SIMPERUM.

### 1.2 Visi & Grand Program
> **"Ngopeni Omah Nglakoni Sesarengan"**
Ini bukan sekadar *tagline*, melainkan nama program payung besar yang membawahi seluruh pilar layanan perumahan di Jawa Tengah, guna memastikan pesan pemerintah tersampaikan secara mulus (*seamless*) ke masyarakat.

### 1.3 Core Identity (3 Fungsi Utama Klinik PKP)
Sebagai pusat layanan terpadu, Klinik PKP memberikan:
1. **Informasi**: Rekomendasi dan panduan program sesuai kebutuhan.
2. **Bantuan Teknis**: Pendampingan dari tahap perencanaan hingga pelaksanaan pembangunan.
3. **Pendampingan**: Layanan konsultasi terarah dan mudah diakses.

---

## 2. GOALS & SCOPE (Tujuan & Batasan)

1. **Integrasi NIK SIMPERUM:** Menjadikan NIK sebagai *primary key* untuk menarik, mencocokkan, dan memvalidasi data penerima bantuan langsung dari API SIMPERUM.
2. **Onboarding & Smart Filter:** Menyaring kelayakan pendaftar secara otomatis (berdasarkan kriteria spesifik program) dan mengarahkan mereka ke program yang paling tepat.
3. **Sistem Antrean (Housing Queue):** Menyimpan rekam jejak (*housing career*) dan antrean pendaftaran masyarakat yang menunggu validasi/realisasi.
4. **Validasi Manual (Audit):** Menjamin legalitas dengan menahan seluruh *approval* data agar diverifikasi secara manual oleh ASN/Admin (tidak otomatis).
5. **Autentikasi Hibrida Aman:** Mempertahankan akses masuk via registrasi tradisional & Google SSO dengan keamanan tingkat tinggi.

---

## 3. ARSITEKTUR UI & NAVIGASI (SITEMAP)

Navigasi utama dirampingkan untuk menonjolkan 3 pilar tupoksi utama dinas, dengan total 6 komponen Navbar:

1. **Menu Utilitas Kiri**: (Beranda / Logo Klinik PKP).
2. **Menu Pengembang**: Halaman *view-only* yang menampilkan daftar/tabel SP2 (Sertifikat Pengembang).
3. **Layanan Utama 1: PERUMAHAN** *(Fokus Prioritas)*
   - **Bank Data**: Tarikan data statistik (Sikumbang/API eksternal).
   - **Info KPR**: Informasi pembiayaan kredit.
   - **Bank Desain**: Unduhan prototipe denah & RAB.
   - **Onboarding Program**: Etalase "Ngopeni Omah Nglakoni Sesarengan".
4. **Layanan Utama 2: KAWASAN**
   - **Data Spasial**: Peta sebaran spasial (GIS Leaflet.js).
5. **Layanan Utama 3: PERTANAHAN** *(Disiapkan untuk fase selanjutnya)*.
6. **Menu Utilitas Kanan**: (Tombol Login / Akun).

---

## 4. PERSYARATAN FUNGSIONAL PORTAL (Functional Requirements)

### 🚀 4.1 Modul Beranda & Etalase Program (Hero Section)
*   **FR-1.1:** Menampilkan *Slider Hero* yang berotasi pada 3 pilar: Perumahan, Kawasan, Pertanahan.
*   **FR-1.2:** Menampilkan blok *Ice Breaker* "Apa Itu Klinik PKP?" yang berisi 3 Core Identity (Informasi, Bantuan Teknis, Pendampingan).
*   **FR-1.3:** Menampilkan *Cards* Iklan Program Pemerintah di sektor Perumahan yang terbagi menjadi 3 Klaster:
    1. **Pembangunan Baru (PB)**: HPBD, Rumah Relokasi, Rumah Bencana.
    2. **Peningkatan Kualitas**: RTLH, PKN.
    3. **Pembiayaan Perumahan**: KPR-KTP, KUR-KPP, Omah Sekeng, Omah Lestari.
*   **FR-1.4:** Setiap *card* jika diklik akan memuat *Definisi Operasional, Syarat & Kriteria, dan Foto Before-After*.

### 📝 4.2 Modul Onboarding & Smart Filter
*   **FR-2.1:** Pengguna yang tertarik pada sebuah program dapat mengklik tombol "Cek Kelayakan / Daftar".
*   **FR-2.2:** Sistem meminta input NIK dan melakukan *fetching* ke API SIMPERUM. Jika data ditemukan, formulir terisi otomatis sebagian.
*   **FR-2.3:** Sistem memproses data melalui *Smart Filter*. Jika tidak lolos syarat dasar program, sistem akan merekomendasikan program lain.
*   **FR-2.4:** Data yang lolos filter akan disimpan di *database* dengan status **"Housing Queue"** (Antrean Perumahan).

### 👥 4.3 Modul Manajemen Antrean (ASN/Admin)
*   **FR-3.1:** Halaman dashboard khusus Admin untuk melihat seluruh data *Housing Queue*.
*   **FR-3.2:** Tombol aksi validasi manual (Setujui / Tolak / Tunda Tahun Depan) untuk keperluan audit.
*   **FR-3.3:** Sinkronisasi status akhir kembali ke API SIMPERUM.

### 🗺️ 4.4 Modul Peta Spasial (Pilar Kawasan) & Bank Desain (Pilar Perumahan)
*   **FR-4.1:** Mempertahankan integrasi peta Leaflet.js untuk pemetaan kewilayahan/perumahan komersial.
*   **FR-4.2:** Mempertahankan katalog unduhan desain prototipe rumah yang ditarik dari API Ternak Web.

### 🔐 4.5 Modul Autentikasi & Akun
*   **FR-5.1:** Pendaftaran tradisional (email/password) dan Google OAuth 2.0.
*   **FR-5.2:** Halaman pengaturan akun untuk melengkapi NIK, nama lengkap, alamat, dll.

---

## 5. PERSYARATAN NON-FUNGSIONAL (Non-Functional Requirements)

### 🔒 5.1 Keamanan & Kepatuhan Hukum (PDP Compliance)
*   **NFR-1.1:** Enkripsi database (AES-256-GCM) untuk kolom krusial seperti **NIK** dan **Alamat** guna mematuhi undang-undang PDP No. 27/2022.
*   **NFR-1.2:** Keamanan SSO Google menggunakan `state` token anti-CSRF.
*   **NFR-1.3:** Proteksi rute admin ketat untuk proses persetujuan (approval) manual *Housing Queue*.

### ⚡ 5.2 Desain Database & Relasi (Berdasarkan ERD)
*   **NFR-2.1:** Tabel `USERS` harus memiliki `nik` berstatus *Unique Key* (UK) untuk integrasi tanpa duplikasi.
*   **NFR-2.2:** Harus ada tabel `PROGRAM_KATEGORI` dan `PROGRAMS` terpisah agar *card* iklan bersifat dinamis dan *scalable*.
*   **NFR-2.3:** Harus ada tabel `HOUSING_QUEUE` untuk menyimpan riwayat (*housing career*) beserta kolom `status_antrean`.

---
*Dokumen ini diperbarui secara komprehensif mengikuti arahan rapat dan transkripsi 22 Juni 2026.*
