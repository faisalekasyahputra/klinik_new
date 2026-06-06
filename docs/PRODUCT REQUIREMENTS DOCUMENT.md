# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Klinik PKP — Portal Layanan Informasi Perumahan Terpadu Jawa Tengah

| Atribut | Detail |
|---|---|
| **Nama Aplikasi** | Klinik PKP (Klinik Perumahan dan Kawasan Permukiman) |
| **Versi Dokumen** | v1.0 |
| **Tanggal Terbit** | 2 Juni 2026 |
| **Instansi Penyelenggara** | Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah |
| **Status Dokumen** | Final Baseline (Ready for Hardening) |

---

## 1. LATAR BELAKANG & VISI PRODUK

### 1.1 Situasi Saat Ini
Disperakim Provinsi Jawa Tengah bertugas memberikan pelayanan informasi, fasilitasi, pengaduan, dan data spasial perumahan bagi seluruh elemen masyarakat (warga MBR, pengembang perumahan, akademisi, dan pemerintah daerah). 
Namun, data perumahan sering kali terdistribusi secara terpisah-pisah, menyulitkan monitoring, dan kurang responsif.

### 1.2 Visi Produk
> *"Menghadirkan satu portal tunggal (Single Access Portal) Klinik PKP yang mengintegrasikan informasi perumahan komersial & subsidi secara real-time, peta sebaran spasial GIS, forum komunitas interaktif, fasilitas download bank desain prototipe, serta pendaftaran kemitraan secara aman, transparan, dan responsif."*

---

## 2. GOALS & SCOPE (Tujuan & Batasan)

### 2.1 Goals (Tujuan Utama)
1.  **Integrasi Real-time Sikumbang:** Menyajikan data perumahan di 35 Kabupaten/Kota Jawa Tengah secara dinamis dari API SiKumbang Tapera.
2.  **Katalog Bank Desain Terpadu:** Menyediakan unduhan prototipe denah bangunan dan Rencana Anggaran Biaya (RAB) dari API Ternak Web.
3.  **Peta Spasial (GIS Mapping):** Memetakan sebaran perumahan komersial/subsidi secara visual menggunakan Leaflet.js berbasis satelit.
4.  **Autentikasi Aman:** Menyediakan akses masuk cepat melalui **Google Single Sign-On (SSO) Popup Flow** yang meminimalisir input manual.
5.  **Perlindungan Privasi PDP:** Menjamin keamanan data kependudukan (NIK dan Alamat Domisili) warga yang mendaftar.

---

## 3. USER PERSONAS & ROLE PENGGUNA

Aplikasi Klinik PKP melayani 4 pilar pengguna utama:

1.  **Masyarakat Umum (Warga):**
    *   *Kebutuhan:* Mencari lokasi perumahan subsidi, mendownload desain rumah prototipe gratis, berdiskusi di forum komunitas, dan melacak status pendaftaran bantuan.
2.  **Pengembang Perumahan (Developer):**
    *   *Kebutuhan:* Mengakses registrasi sertifikasi pengembang dan mempromosikan proyek perumahan yang telah lolos verifikasi dinas.
3.  **Mahasiswa (Kemitraan Akademik):**
    *   *Kebutuhan:* Mendaftar program KKN tematik sengketa lahan atau mengajukan magang dinas secara online.
4.  **Pemerintah Daerah (Kabupaten/Kota):**
    *   *Kebutuhan:* Melakukan input data intervensi penanganan kawasan kumuh dan memonitoring sebaran wilayah secara spasial.

---

## 4. PERSYARATAN FUNGSIONAL PORTAL (Functional Requirements)

### 📊 4.1 Modul Beranda & Pencarian Perumahan
*   **FR-1.1:** Menampilkan carousel hero statistik unit perumahan, rumah subsidi, dan jumlah lokasi terdaftar di Jawa Tengah.
*   **FR-1.2:** Fitur pencarian real-time (AJAX) berdasarkan Kabupaten/Kota dan kata kunci Nama Perumahan.
*   **FR-1.3:** Filter pencarian ketat untuk menampilkan "Hanya Rumah Subsidi" dengan status validasi tipe rumah.
*   **FR-1.4:** Tombol pagination "Load More" berbasis AJAX untuk memuat data perumahan tambahan secara mulus tanpa *reload* halaman.

### 🗺️ 4.2 Modul Peta Sebaran Spasial (GIS Map)
*   **FR-2.1:** Integrasi Leaflet.js menggunakan tile satelit World Imagery dari Esri.
*   **FR-2.2:** Menampilkan marker interaktif: Kuning/Emas dengan efek denyut ping (`animate-ping`) untuk perumahan subsidi, dan Biru untuk perumahan komersial.
*   **FR-2.3:** Fitur pencarian instan di dalam peta dengan auto-complete yang dapat melakukan animasi *flyTo* (panning & zoom halus) ke lokasi marker perumahan.
*   **FR-2.4:** Detail popup marker yang menampilkan nama perumahan, status, dan link akses ke halaman detail perumahan eksternal.

### 🏠 4.3 Modul Bank Desain Prototipe
*   **FR-3.1:** Menampilkan grid katalog denah prototipe rumah yang ditarik secara dinamis dari API Ternak Web.
*   **FR-3.2:** Menyediakan deskripsi denah, detail spesifikasi tipe rumah, estimasi RAB, dan tautan unduhan dokumen PDF secara langsung.
*   **FR-3.3:** Integrasi media promosi berupa link video YouTube resmi dari pengembang/dinas jika tersedia.

### 💬 4.4 Modul Forum Komunikasi Komunitas
*   **FR-4.1:** Halaman forum publik untuk bertukar pikiran seputar RTLH, prasarana umum, sengketa lahan, dan perumahan subsidi.
*   **FR-4.2:** Form modal "Buat Diskusi Baru" bagi pengguna terdaftar untuk membuat utas (thread) diskusi.
*   **FR-4.3:** Halaman detail diskusi yang menampilkan kronologi utas dan daftar balasan tanggapan.
*   **FR-4.4:** Form pengiriman balasan/tanggapan resmi (dengan proteksi verifikasi peran admin/staf dinas secara mutlak di sisi backend).

---

## 5. PERSYARATAN NON-FUNGSIONAL (Non-Functional Requirements)

### 🔒 5.1 Keamanan & Kepatuhan Hukum (PDP Compliance)
*   **NFR-1.1:** Proteksi Cross-Site Request Forgery (CSRF) aktif secara global pada seluruh form input.
*   **NFR-1.2:** Token Google OAuth `state` harus di-generate secara kriptografis acak dinamis untuk memitigasi celah bypass login.
*   **NFR-1.3:** Validasi backend ketat terhadap perubahan profil pengguna (anti-IDOR) berbasis pencocokan ID Session.
*   **NFR-1.4:** Enkripsi database (AES-256-GCM) untuk kolom NIK dan Alamat guna mematuhi undang-undang PDP No. 27/2022.

### ⚡ 5.2 Performa & Skalabilitas
*   **NFR-2.1:** Waktu pemuatan halaman awal (LCP) di bawah 3 detik di jaringan 4G standar.
*   **NFR-2.2:** Mekanisme cache penyimpanan gambar perumahan (`buka_foto`) di folder lokal server (`assets/cache_foto`) untuk menghindari kegagalan *load* aset Tapera dan menghemat bandwidth.
