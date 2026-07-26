# 📚 Dokumentasi Klinik PKP
**Terakhir Diperbarui:** 1 Juli 2026 (v3.0 Refactor)

Folder ini berisi seluruh dokumentasi teknis proyek Klinik PKP yang telah distruktur ulang.

---

## 🎯 Product (`product/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [PRODUCT_REQUIREMENTS_DOCUMENT.md](./product/PRODUCT_REQUIREMENTS_DOCUMENT.md) | Spesifikasi kebutuhan fungsional & non-fungsional (PRD) |
| [IMPLEMENTATION_ROADMAP.md](./product/IMPLEMENTATION_ROADMAP.md) | Peta jalan pengembangan (Fase 1–10) |
| [DESAIN_STATUS_TIKET_PENGAJUAN.md](./product/DESAIN_STATUS_TIKET_PENGAJUAN.md) | Konsep akses status pengajuan dengan tiket tanpa login atau akun |
| [ANALISIS_DAN_RENCANA_PERBAIKAN.md](./product/ANALISIS_DAN_RENCANA_PERBAIKAN.md) | Audit keamanan awal & status remediasi |
| [PRD_DASHBOARD_MULTI_ROLE.md](./product/PRD_DASHBOARD_MULTI_ROLE.md) | PRD dashboard warga, pengembang, mahasiswa, dan admin |
| [PRD_SRP2_AKUN_PENGEMBANG.md](./product/PRD_SRP2_AKUN_PENGEMBANG.md) | PRD alur SRP2 berbasis akun pengembang |
| [PRD_VERIFIKASI_ADMIN_SRP2.md](./product/PRD_VERIFIKASI_ADMIN_SRP2.md) | Roadmap menutup gap admin approval SRP2 + prinsip relasi role↔admin untuk role baru — belum dikerjakan |

---

## 🏛️ Architecture (`architecture/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [TECHNICAL_DESIGN_DOCUMENT.md](./architecture/TECHNICAL_DESIGN_DOCUMENT.md) | Arsitektur backend, base controller, API, struktur direktori |
| [DATABASE_DESIGN_DOCUMENT.md](./architecture/DATABASE_DESIGN_DOCUMENT.md) | Kamus data, ERD, dan skema enkripsi |
| [SECURITY_DESIGN_DOCUMENT.md](./architecture/SECURITY_DESIGN_DOCUMENT.md) | Model ancaman STRIDE, enkripsi, OAuth hardening |
| [SYSTEM_DESIGN_DIAGRAMS.md](./architecture/SYSTEM_DESIGN_DIAGRAMS.md) | ERD dan Flowchart sistem |
| [DESAIN_NORMALISASI_SKEMA_ROLE.md](./architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) | Normalisasi skema tabel pengajuan per-role — Opsi A dipilih & migrasi inti sudah dijalankan |
| [ANCHOR_DASHBOARD_TERPADU.md](./architecture/ANCHOR_DASHBOARD_TERPADU.md) | **ANCHOR** dashboard terpadu multi-role: keputusan arsitektur final + registry modul + rencana 5 fase — wajib dibaca sebelum menyentuh apa pun berbau dashboard |
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
| [AUDIT_SISTEM_ROLE_RINGKASAN.md](./engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) | Ringkasan pola lintas-role dari audit 5 role (26 Jul 2026) — baca ini duluan sebelum menambah role baru |
| [AUDIT_ROLE_PENGEMBANG.md](./engineering/AUDIT_ROLE_PENGEMBANG.md) | Audit sistem/keamanan/konsistensi role `pengembang` |
| [AUDIT_ROLE_WARGA.md](./engineering/AUDIT_ROLE_WARGA.md) | Audit sistem/keamanan/konsistensi role `warga` |
| [AUDIT_ROLE_MAHASISWA.md](./engineering/AUDIT_ROLE_MAHASISWA.md) | Audit sistem/keamanan/konsistensi role `mahasiswa` |
| [AUDIT_ROLE_ADMIN_SCOPED.md](./engineering/AUDIT_ROLE_ADMIN_SCOPED.md) | Audit role `admin_kabkota` & `admin_bidang` |

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
