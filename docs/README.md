# 📚 Dokumentasi Klinik PKP
**Terakhir Diperbarui:** 1 Juli 2026 (v3.0 Refactor)

Folder ini berisi seluruh dokumentasi teknis proyek Klinik PKP yang telah distruktur ulang.

---

## 🎯 Product (`product/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [PRODUCT_REQUIREMENTS_DOCUMENT.md](./product/PRODUCT_REQUIREMENTS_DOCUMENT.md) | Spesifikasi kebutuhan fungsional & non-fungsional (PRD) |
| [IMPLEMENTATION_ROADMAP.md](./product/IMPLEMENTATION_ROADMAP.md) | Peta jalan pengembangan (Fase 1–10) |
| [ANALISIS_DAN_RENCANA_PERBAIKAN.md](./product/ANALISIS_DAN_RENCANA_PERBAIKAN.md) | Audit keamanan awal & status remediasi |

---

## 🏛️ Architecture (`architecture/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [TECHNICAL_DESIGN_DOCUMENT.md](./architecture/TECHNICAL_DESIGN_DOCUMENT.md) | Arsitektur backend, base controller, API, struktur direktori |
| [DATABASE_DESIGN_DOCUMENT.md](./architecture/DATABASE_DESIGN_DOCUMENT.md) | Kamus data, ERD, dan skema enkripsi |
| [SECURITY_DESIGN_DOCUMENT.md](./architecture/SECURITY_DESIGN_DOCUMENT.md) | Model ancaman STRIDE, enkripsi, OAuth hardening |
| [SYSTEM_DESIGN_DIAGRAMS.md](./architecture/SYSTEM_DESIGN_DIAGRAMS.md) | ERD dan Flowchart sistem |
| [flowchart.mmd](./architecture/flowchart.mmd) | Kode sumber diagram Mermaid (Flowchart) |

---

## ⚙️ Engineering (`engineering/`)
| Dokumen | Deskripsi |
|---------|-----------|
| [AKUN_LOGIN.md](./engineering/AKUN_LOGIN.md) | Panduan alur autentikasi, onboarding, profil, hapus akun |
| [SETUP_DATABASE.md](./engineering/SETUP_DATABASE.md) | Panduan setup database untuk developer baru |
| [schema_klinikpkp.sql](./engineering/schema_klinikpkp.sql) | Schema SQL lengkap untuk migrasi (tanpa data dummy) |

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
