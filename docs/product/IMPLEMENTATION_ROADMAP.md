# 📅 IMPLEMENTATION ROADMAP (Peta Jalan Eksekusi)
## Pivot & Re-Arsitektur Klinik PKP (v3.0)
**Target Workspace:** `c:\xampp\htdocs\klinik_new`  
**Terakhir Diperbarui:** 23 Juli 2026 (v3.0)

---

## 0. RINGKASAN EKSEKUTIF (Executive Summary)

Peta jalan ini telah diperbarui secara radikal mengikuti **PRD v3.0**. Fokus pengembangan kini tidak lagi pada "Forum Komunitas", melainkan telah ber- *pivot* menjadi **Bank Data & Onboarding Journey** (Grand Program: *Ngopeni Omah Nglakoni Sesarengan*). 

Fondasi Keamanan & Auth dari eksekusi sebelumnya (Fase 1-6) tetap dipertahankan sebagai pilar solid.

**Status Terkini:**
- ✅ **Fase 1–6 (Security, Enkripsi NIK, Auth)** — Selesai
- ✅ **Fase 7 (Restrukturisasi UI & Navbar)** — Selesai
- ✅ **Fase 8 (Etalase Program & Hero Beranda)** — Selesai
- ✅ **Fase 9 (Integrasi NIK SIMPERUM & Housing Queue)** — Selesai
- 🔄 **Fase 9.5 (Status Tiket Pengajuan Hybrid)** — Tahap 1 terverifikasi terhadap database staging; hardening lanjutan belum selesai
- 🛠️ **Fase 10 (Validasi Manual ASN / Admin Dashboard)** — Belum mulai
- 🔄 **Fase 11 (Tokenisasi CSS & Theming)** — Sedang berjalan (Transisi dari Dark Theme hardcoded ke CSS Variables untuk Light/Dark Mode)

---

## 1. STRATEGI PELAKSANAAN (Phased Action Plan)

```
[FASE 1-6: KEAMANAN & AUTENTIKASI] ✅ DONE
(Token CSRF, Anti-IDOR, Enkripsi NIK/Alamat PDP, Login Google/SSO)
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 7: RESTRUKTURISASI NAVBAR & SITEMAP        ✅ DONE  │
│  • Hapus menu Profil statis                              │
│  • Bentuk 6 Entitas Navbar (2 Utilitas, 1 Pengembang)    │
│  • Dropdown 3 Pilar (Perumahan, Kawasan, Pertanahan)     │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 8: HERO SECTION & ETALASE PROGRAM          ✅ DONE  │
│  • Hero Slider 3 Pilar                                   │
│  • Section "Ice Breaker" (3 Fungsi Utama PKP)            │
│  • 5 Cards "Etalase" Program (KPR-FLPP, Oemah Lestari,   │
│    RTLH, Stimulan PB, Rumah Apung)                       │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 9: API SIMPERUM & HOUSING QUEUE            ✅ DONE  │
│  • Migrasi Database (Tabel sf_programs, sf_housing_queue)│
│  • Form Input NIK saat klik "Daftar" di Card Program     │
│  • Hit API SIMPERUM, auto-fill data diri                 │
│  • Smart Filter Kelayakan -> Insert ke Housing Queue     │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 9.5: STATUS TIKET PENGAJUAN          🔄 PARTIAL DONE │
│  • Nomor tiket unik setelah pengajuan                    │
│  • Cek status tanpa login + verifikasi tambahan           │
│  • Login opsional untuk riwayat dan dashboard warga       │
│  • Halaman sukses mengikuti tema cerah                    │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 10: ADMIN DASHBOARD (VALIDASI MANUAL)      🔒 TODO  │
│  • Tabel Antrean (Housing Queue) di sisi Admin           │
│  • Tombol Approve / Reject / Tunda                       │
│  • Endpoint Sinkronisasi balik ke server SIMPERUM        │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 11: TOKENISASI CSS & THEMING               🔄 TODO  │
│  • Tokenisasi CSS dari hardcoded Dark Theme              │
│  • Peralihan ke Light Theme berbasis CSS variables       │
│  • Dukungan untuk Dark Mode                              │
└──────────────────────────────────────────────────────────┘
```

---

## 2. DETAIL PER-FASE (Pasca-Pivot)

### ✅ FASE 1 - 6: Fondasi Keamanan & Auth (SELESAI)
*Fondasi seperti CSRF, Enkripsi NIK/Alamat (UU PDP), dan Login/Google SSO sudah selesai dan berjalan sempurna. Ini akan menjadi modal awal untuk sistem Onboarding di fase selanjutnya.*

---

### ✅ FASE 7 — Restrukturisasi Navbar & Sitemap (SELESAI)
*   **Target:** Mengubah kerangka navigasi lama (`nav.php`) menjadi 6 entitas baru sesuai arsitektur PRD v3.0.
*   **Action Items:**
    *   [x] Membersihkan link statis "Profil".
    *   [x] Membuat *Dropdown* "Perumahan" (Berisi: Bank Data, Program, Bank Desain).
    *   [x] Membuat *Dropdown* "Kawasan" (Berisi: Data Spasial).
    *   [x] Mengubah menu "Pengembang" menjadi *view-only* SP2 list.

---

### ✅ FASE 8 — Hero Section & Etalase Program (SELESAI)
*   **Target:** Merombak tampilan `awal.php` agar berfokus pada "Iklan" program perumahan.
*   **Action Items:**
    *   [x] Implementasi Slider (Perumahan, Kawasan, Pertanahan).
    *   [x] Membuat blok 3 Fungsi Utama PKP (Informasi, Bantuan Teknis, Pendampingan).
    *   [x] Membuat komponen 5 Card Etalase Utama (KPR-FLPP, Oemah Lestari, RTLH, Bantuan PB, Rumah Apung).

---

### ✅ FASE 9 — Integrasi SIMPERUM & Smart Filter (SELESAI)
*   **Target:** Logika *backend* krusial dan UI interaktif untuk *Onboarding Journey* (Diagnosa Pasien).
*   **Action Items:**
    *   [x] Membuat UI *Wizard* "Kalkulator Kelayakan/Klinik Diagnosa" interaktif (4 step alur baru).
    *   [x] Membuat *Smart Filter Engine* (Backend) untuk menghitung Desil secara reaktif (dinamis) dan mencocokkan program (UN HABITAT Matrix).
    *   [x] Update struktur Database (Refactoring awalan tabel `sf_programs`, `sf_housing_queue` dll).
    *   [x] Membuat fungsi mock API SIMPERUM berbasis NIK untuk auto-fill data (skenario dummy dan empty survey).
    *   [x] Menyimpan hasil pengajuan masyarakat ke tabel `sf_housing_queue`.

---

### 🔒 FASE 10 — Admin Dashboard / Validasi Manual (BELUM MULAI)
*   **Target:** Pemenuhan syarat audit/hukum di mana otomatisasi harus dihentikan untuk validasi manual ASN.
*   **Action Items:**
    *   [ ] Halaman *backend* admin untuk melihat antrean masuk.
    *   [ ] Fungsi persetujuan manual (Ubah status *pending* menjadi *approved* atau *rejected*).

### 🔄 FASE 9.5 — Status Tiket Pengajuan Hybrid (TAHAP 1 TERVERIFIKASI DI STAGING)
*   **Target:** Warga dapat memantau satu pengajuan tanpa wajib membuat akun, sementara user login mendapat riwayat lengkap.
*   **Dokumen acuan:** [`DESAIN_STATUS_TIKET_PENGAJUAN.md`](./DESAIN_STATUS_TIKET_PENGAJUAN.md).
*   **Action Items:**
    *   [x] Tambah `ticket_code` unik pada `sf_housing_queue` di database staging.
    *   [x] Tampilkan tiket pada halaman sukses dari jalur `solusi_pembiayaan` dan `Program/diagnosa/*`.
    *   [x] Buat lookup publik khusus tiket `PKP-*` dengan verifikasi empat digit terakhir NIK.
    *   [x] Sediakan dashboard riwayat yang mengambil data berdasarkan `user_id` sesi.
    *   [x] Batasi lookup setelah lima percobaan gagal per hash IP dalam satu menit.
    *   [ ] Terapkan hardening enkripsi NIK pada `sf_housing_queue`.
    *   [ ] Lakukan pengujian keamanan lanjutan untuk anti-IDOR, enumeration, dan kebocoran PII.

    Verifikasi 23 Juli 2026 dijalankan pada branch lokal dengan koneksi langsung ke database staging: kedua jalur pengajuan menampilkan tiket yang sama dengan row database, lookup valid memberi HTTP 200, percobaan keenam memberi HTTP 429, dan `/akun` terautentikasi memberi HTTP 200 serta menampilkan tiket milik user sesi. Deployment web staging/production belum diverifikasi.

---

### 🔄 FASE 11 — Tokenisasi CSS & Theming (SEDANG BERJALAN)
*   **Target:** Beralih dari desain Dark Theme yang di-*hardcode* menuju sistem *theming* berbasis CSS variables (Light Theme dengan dukungan Dark Mode).
*   **Action Items:**
    *   [ ] Mengidentifikasi dan mengekstrak warna *hardcoded* ke dalam CSS variables.
    *   [ ] Menyusun *default* tema menjadi Light Theme.
    *   [ ] Memastikan *fallback* dan integrasi Dark Mode berfungsi dengan baik pada seluruh komponen UI.

---

## 3. KRITERIA KEBERHASILAN (Success Criteria) FASE DEPAN

1. ⬜ **UI/UX Relevan:** Navigasi 3 pilar dan Etalase Program dapat diakses dan responsif di *mobile*.
2. ⬜ **API Hit Sukses:** Sistem bisa mengirim NIK, dan menerima JSON *response* dari server SIMPERUM.
3. ⬜ **Antrean Terekam:** Data warga berhasil masuk ke tabel `sf_housing_queue` dengan status default `pending`.
4. ⬜ **Validasi Terjaga:** Hanya *role* Admin yang bisa mengeksekusi perubahan status persetujuan.
