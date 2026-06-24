# 📅 IMPLEMENTATION ROADMAP (Peta Jalan Eksekusi)
## Pivot & Re-Arsitektur Klinik PKP (v3.0)
**Target Workspace:** `c:\xampp\htdocs\klinik_new`  
**Terakhir Diperbarui:** 22 Juni 2026

---

## 0. RINGKASAN EKSEKUTIF (Executive Summary)

Peta jalan ini telah diperbarui secara radikal mengikuti **PRD v3.0**. Fokus pengembangan kini tidak lagi pada "Forum Komunitas", melainkan telah ber- *pivot* menjadi **Bank Data & Onboarding Journey** (Grand Program: *Ngopeni Omah Nglakoni Sesarengan*). 

Fondasi Keamanan & Auth dari eksekusi sebelumnya (Fase 1-6) tetap dipertahankan sebagai pilar solid.

**Status Terkini:**
- ✅ **Fase 1–6 (Security, Enkripsi NIK, Auth)** — Selesai
- 🚀 **Fase 7 (Restrukturisasi UI & Navbar)** — Siap dikerjakan
- 🛠️ **Fase 8 (Etalase Program & Hero Beranda)** — Belum mulai
- 🛠️ **Fase 9 (Integrasi NIK SIMPERUM & Housing Queue)** — Belum mulai
- 🔒 **Fase 10 (Validasi Manual ASN / Admin Dashboard)** — Belum mulai

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
│ FASE 8: HERO SECTION & ETALASE PROGRAM          🚀 NEXT  │
│  • Hero Slider 3 Pilar                                   │
│  • Section "Ice Breaker" (3 Fungsi Utama PKP)            │
│  • 5 Cards "Etalase" Program (KPR-FLPP, Oemah Lestari,   │
│    RTLH, Stimulan PB, Rumah Apung)                       │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 9: API SIMPERUM & HOUSING QUEUE            🛠️ TODO  │
│  • Migrasi Database (Tabel PROGRAMS, HOUSING_QUEUE)      │
│  • Form Input NIK saat klik "Daftar" di Card Program     │
│  • Hit API SIMPERUM, auto-fill data diri                 │
│  • Smart Filter Kelayakan -> Insert ke Housing Queue     │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 10: ADMIN DASHBOARD (VALIDASI MANUAL)      🔒 TODO  │
│  • Tabel Antrean (Housing Queue) di sisi Admin           │
│  • Tombol Approve / Reject / Tunda                       │
│  • Endpoint Sinkronisasi balik ke server SIMPERUM        │
└──────────────────────────────────────────────────────────┘
```

---

## 2. DETAIL PER-FASE (Pasca-Pivot)

### ✅ FASE 1 - 6: Fondasi Keamanan & Auth (SELESAI)
*Fondasi seperti CSRF, Enkripsi NIK/Alamat (UU PDP), dan Login/Google SSO sudah selesai dan berjalan sempurna. Ini akan menjadi modal awal untuk sistem Onboarding di fase selanjutnya.*

---

### 🚀 FASE 7 — Restrukturisasi Navbar & Sitemap (SEGERA DIMULAI)
*   **Target:** Mengubah kerangka navigasi lama (`nav.php`) menjadi 6 entitas baru sesuai arsitektur PRD v3.0.
*   **Action Items:**
    *   [ ] Membersihkan link statis "Profil".
    *   [ ] Membuat *Dropdown* "Perumahan" (Berisi: Bank Data, Program, Bank Desain).
    *   [ ] Membuat *Dropdown* "Kawasan" (Berisi: Data Spasial).
    *   [ ] Mengubah menu "Pengembang" menjadi *view-only* SP2 list.

---

### 🛠️ FASE 8 — Hero Section & Etalase Program (BELUM MULAI)
*   **Target:** Merombak tampilan `awal.php` agar berfokus pada "Iklan" program perumahan.
*   **Action Items:**
    *   [ ] Implementasi Slider (Perumahan, Kawasan, Pertanahan).
    *   [ ] Membuat blok 3 Fungsi Utama PKP (Informasi, Bantuan Teknis, Pendampingan).
    *   [ ] Membuat komponen 5 Card Etalase Utama (KPR-FLPP, Oemah Lestari, RTLH, Bantuan PB, Rumah Apung).

---

### 🛠️ FASE 9 — Integrasi SIMPERUM & Smart Filter (BELUM MULAI)
*   **Target:** Logika *backend* krusial dan UI interaktif untuk *Onboarding Journey* (Diagnosa Pasien).
*   **Action Items:**
    *   [ ] Membuat UI *Wizard* "Kalkulator Kelayakan/Klinik Diagnosa" interaktif (3-4 step pertanyaan).
    *   [ ] Membuat *Smart Filter Engine* (Backend) untuk menghitung Desil dan mencocokkan program.
    *   [ ] Update struktur Database (ERD baru: `PROGRAMS`, `HOUSING_QUEUE`).
    *   [ ] Membuat fungsi `curl`/guzzle untuk memanggil API SIMPERUM berbasis NIK (Auto-fill data).
    *   [ ] Menyimpan hasil pengajuan masyarakat ke tabel `HOUSING_QUEUE`.

---

### 🔒 FASE 10 — Admin Dashboard / Validasi Manual (BELUM MULAI)
*   **Target:** Pemenuhan syarat audit/hukum di mana otomatisasi harus dihentikan untuk validasi manual ASN.
*   **Action Items:**
    *   [ ] Halaman *backend* admin untuk melihat antrean masuk.
    *   [ ] Fungsi persetujuan manual (Ubah status *pending* menjadi *approved* atau *rejected*).

---

## 3. KRITERIA KEBERHASILAN (Success Criteria) FASE DEPAN

1. ⬜ **UI/UX Relevan:** Navigasi 3 pilar dan Etalase Program dapat diakses dan responsif di *mobile*.
2. ⬜ **API Hit Sukses:** Sistem bisa mengirim NIK, dan menerima JSON *response* dari server SIMPERUM.
3. ⬜ **Antrean Terekam:** Data warga berhasil masuk ke tabel `HOUSING_QUEUE` dengan status default `pending`.
4. ⬜ **Validasi Terjaga:** Hanya *role* Admin yang bisa mengeksekusi perubahan status persetujuan.
