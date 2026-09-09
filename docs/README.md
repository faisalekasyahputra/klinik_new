# 📚 Dokumentasi Klinik PKP
**Terakhir Diperbarui:** 1 Juli 2026 (v3.0 Refactor)

Folder ini berisi seluruh dokumentasi teknis proyek Klinik PKP yang telah distruktur ulang.

---

## 🎯 Product (`product/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [PRODUCT_REQUIREMENTS_DOCUMENT.md](./product/PRODUCT_REQUIREMENTS_DOCUMENT.md) | Spesifikasi kebutuhan fungsional & non-fungsional (PRD) |
| [IMPLEMENTATION_ROADMAP.md](./product/IMPLEMENTATION_ROADMAP.md) | Peta jalan pengembangan (Fase 1-10) |
| [DESAIN_STATUS_TIKET_PENGAJUAN.md](./product/DESAIN_STATUS_TIKET_PENGAJUAN.md) | Konsep akses status pengajuan dengan tiket tanpa login atau akun |
| [ANALISIS_DAN_RENCANA_PERBAIKAN.md](./product/ANALISIS_DAN_RENCANA_PERBAIKAN.md) | Audit keamanan awal & status remediasi |
| [PRD_DASHBOARD_MULTI_ROLE.md](./product/PRD_DASHBOARD_MULTI_ROLE.md) | PRD dashboard warga, pengembang, mahasiswa, dan admin |
| [PRD_SRP2_AKUN_PENGEMBANG.md](./product/PRD_SRP2_AKUN_PENGEMBANG.md) | PRD alur SRP2 berbasis akun pengembang |
| [PRD_VERIFIKASI_ADMIN_SRP2.md](./product/PRD_VERIFIKASI_ADMIN_SRP2.md) | Roadmap menutup gap admin approval SRP2 + prinsip relasi role↔admin untuk role baru - belum dikerjakan |
| [PRD_FORM_WARGA_SIMPERUM.md](./product/PRD_FORM_WARGA_SIMPERUM.md) | PRD pengganti diagnosa sederhana: wizard warga adaptif, cache SIMPERUM local-first, provenance, rekomendasi versioned |
| [ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md](./product/ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md) | Roadmap aktif R0-R9: implementasi penuh memakai data SIMPERUM sintetis, tracker fase, checks, dan protokol handoff antar-agent |
| [PRD_WARGA_ADMIN_KABKOTA.md](./product/PRD_WARGA_ADMIN_KABKOTA.md) | Kartu Domain alur warga ↔ admin kab/kota (antrean perumahan): kepemilikan, scope wilayah, transisi status |
| [PRD_PELUNASAN_UTANG_TEKNIS.md](./product/PRD_PELUNASAN_UTANG_TEKNIS.md) | PRD penutupan utang teknis dari inventaris terverifikasi 29 Jul 2026: tiga kelompok (kepercayaan publik/keamanan/fitur mati), prinsip urutan, definisi selesai, dan 8 keputusan user yang memblokir |
| [ROADMAP_PELUNASAN_UTANG_TEKNIS.md](./product/ROADMAP_PELUNASAN_UTANG_TEKNIS.md) | Roadmap U0-U6 pelunasan utang teknis: butir yang ditutup per tahap, berkas yang disentuh, cara membuktikan (termasuk uji balik), dan risiko regresi |
| [PRD_REKAM_DATA.md](./product/PRD_REKAM_DATA.md) | PRD Rekam Data (pelaporan capaian Kab/Kota) pengganti dua Google Form dinas: struktur kedua form apa adanya, 6 cacat form yang ditemukan, keputusan user 29 Jul 2026, skema 5 tabel |

---

## 🏛️ Architecture (`architecture/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [TECHNICAL_DESIGN_DOCUMENT.md](./architecture/TECHNICAL_DESIGN_DOCUMENT.md) | Arsitektur backend, base controller, API, struktur direktori |
| [DATABASE_DESIGN_DOCUMENT.md](./architecture/DATABASE_DESIGN_DOCUMENT.md) | Kamus data, ERD, dan skema enkripsi |
| [SECURITY_DESIGN_DOCUMENT.md](./architecture/SECURITY_DESIGN_DOCUMENT.md) | Model ancaman STRIDE, enkripsi, OAuth hardening |
| [SYSTEM_DESIGN_DIAGRAMS.md](./architecture/SYSTEM_DESIGN_DIAGRAMS.md) | ERD dan Flowchart sistem |
| [DESAIN_NORMALISASI_SKEMA_ROLE.md](./architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) | Normalisasi skema tabel pengajuan per-role - Opsi A dipilih & migrasi inti sudah dijalankan |
| [ANCHOR_DASHBOARD_TERPADU.md](./architecture/ANCHOR_DASHBOARD_TERPADU.md) | **ANCHOR** dashboard terpadu multi-role: keputusan arsitektur final + registry modul + rencana 5 fase - wajib dibaca sebelum menyentuh apa pun berbau dashboard |
| [SKEMA_DATA_FORM_WARGA_SIMPERUM.md](./architecture/SKEMA_DATA_FORM_WARGA_SIMPERUM.md) | Kamus field dari 50 gambar, dependensi cabang, katalog dropdown, dan rancangan tabel pendataan warga/SIMPERUM |
| [KONTRAK_INTEGRASI_SIMPERUM_API.md](./architecture/KONTRAK_INTEGRASI_SIMPERUM_API.md) | Kontrak GET SIMPERUM, auth MD5, mapping canonical, keamanan, blocker desil, dan checklist aktivasi |
| [SKEMA_DATA_REKAM_DATA.md](./architecture/SKEMA_DATA_REKAM_DATA.md) | ERD Rekam Data, analisis normalisasi 1NF→BCNF, DDL 6 tabel terpasang, pemetaan field form→kolom, pola query rekap, dan 10 bukti uji constraint |
| [flowchart.mmd](./architecture/flowchart.mmd) | Kode sumber diagram Mermaid (Flowchart) |

---

## ⚙️ Engineering (`engineering/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [AKUN_LOGIN.md](./engineering/AKUN_LOGIN.md) | Panduan alur autentikasi, onboarding, profil, hapus akun |
| [SETUP_DATABASE.md](./engineering/SETUP_DATABASE.md) | Panduan setup database untuk developer baru |
| [schema_klinikpkp.sql](./engineering/schema_klinikpkp.sql) | Schema SQL lengkap untuk migrasi (tanpa data dummy) |
| [ROLE_DATA_RELATION_MAP.md](./engineering/ROLE_DATA_RELATION_MAP.md) | Peta relasi data dan gap schema per dashboard |
| [SRP2_ACCOUNT_FLOW.md](./engineering/SRP2_ACCOUNT_FLOW.md) | Alur teknis, endpoint, dan keamanan SRP2 |
| [SRP2_CHANGELOG.md](./engineering/SRP2_CHANGELOG.md) | Log perbaikan SRP2 |
| [AUDIT_SISTEM_ROLE_RINGKASAN.md](./engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) | Ringkasan pola lintas-role dari audit 5 role (26 Jul 2026) - baca ini duluan sebelum menambah role baru |
| [AUDIT_ROLE_PENGEMBANG.md](./engineering/AUDIT_ROLE_PENGEMBANG.md) | Audit sistem/keamanan/konsistensi role `pengembang` |
| [AUDIT_ROLE_WARGA.md](./engineering/AUDIT_ROLE_WARGA.md) | Audit sistem/keamanan/konsistensi role `warga` |
| [AUDIT_ROLE_MAHASISWA.md](./engineering/AUDIT_ROLE_MAHASISWA.md) | Audit sistem/keamanan/konsistensi role `mahasiswa` |
| [AUDIT_ROLE_ADMIN_SCOPED.md](./engineering/AUDIT_ROLE_ADMIN_SCOPED.md) | Audit role `admin_kabkota` & `admin_bidang` |
| [AUDIT_AKURASI_FORM_WARGA_R3.md](./engineering/AUDIT_AKURASI_FORM_WARGA_R3.md) | Audit akurasi field form warga terhadap 50 gambar sumber - opsi yang belum terbaca dilarang ditebak |
| [AUDIT_KEAMANAN_WARGA_R7.md](./engineering/AUDIT_KEAMANAN_WARGA_R7.md) | Audit keamanan alur warga R7: CSRF, rate limit multi-dimensi, scope, bukti privat |
| [BUKTI_BROWSER_WARGA_R7.md](./engineering/BUKTI_BROWSER_WARGA_R7.md) | Bukti uji browser nyata (desktop + mobile) untuk wizard, riwayat, dan antrean admin |
| [UJI_FRESH_WARGA_R7.md](./engineering/UJI_FRESH_WARGA_R7.md) | Prosedur runner DB fresh: import baseline → migrasi 1→20 → seluruh check R1-R7 |
| [PAKET_PRESENTASI_WARGA_R7.md](./engineering/PAKET_PRESENTASI_WARGA_R7.md) | Paket presentasi alur warga untuk dinas (skenario demo + tiket contoh) |
| [RUNBOOK_RILIS_033_035.md](./engineering/RUNBOOK_RILIS_033_035.md) | **Rilis berjalan (4 Agt 2026)** - jejak audit, triase aduan, janji temu. Migrasi-DULU-baru-push; memuat temuan bahwa production menjalankan layar Jejak Audit tanpa tabelnya |
| [RUNBOOK_RILIS_WIZARD_024.md](./engineering/RUNBOOK_RILIS_WIZARD_024.md) | Runbook rilis wizard Rekam Data (`…024`, 30 Jul 2026) - kasus yang TERPAKSA push-dulu |
| [RUNBOOK_RILIS_30JUL2026.md](./engineering/RUNBOOK_RILIS_30JUL2026.md) | Runbook rilis 30 Jul 2026 - asal pola backup-terverifikasi & `set -o pipefail` |
| [STRUKTUR_FORM_SUMBER_REKAM_DATA.md](./engineering/STRUKTUR_FORM_SUMBER_REKAM_DATA.md) | **Bukti mentah** struktur dua Google Form dinas (Perumahan 170 item/21 hal, Kawasan 178 item/22 hal), metode ekstraksi, peta percabangan, daftar nilai verbatim, dan 8 cacat form berikut lokasinya - form Perumahan wajib login, jadi ini satu-satunya salinan yang bisa dibaca agent |

---

## 🎨 Design System (`design/`)
| File | Deskripsi |
|------|-----------|
| [design-tokens.json](./design/design-tokens.json) | Token desain (warna, tipografi, spacing) |
| [tokens.css](./design/tokens.css) | CSS variables dari design tokens |
| [color-scheme-preview.html](./design/color-scheme-preview.html) | Preview visual skema warna |

---

## 🤝 Meetings (`meetings/`)
Folder ini memuat log *meeting* dan catatan *progress update*, seperti:
- `22_juni_2026/` (Transkrip rapat dan transisi ke Fase Pivot)

---

## 📦 Arsip (`archive/`)
File lama dari fase riset awal (seperti *changelogs* dan dokumen `superpowers`) diarsipkan di sini untuk referensi historis.
