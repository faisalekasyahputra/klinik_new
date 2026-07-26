# AGENTS.md — Panduan & Pusat Sinkronisasi untuk AI Coding Agent

> Peta navigasi teknis DAN titik temu antar agent (Claude Code, Cursor, Copilot, dll) yang bekerja di repo ini.
> Isi ditulis dari pembacaan kode langsung, bukan asumsi dokumen lama. Tiap klaim penting mencantumkan tanggal verifikasi.
> Spesifikasi produk/bisnis: [`docs/README.md`](docs/README.md). ⚠️ [`README.md`](README.md) di root **usang** (v1.0, 9 Juni 2026) — jangan dijadikan acuan.

---

## 0. BACA INI DULU — Status Terkini & Protokol Antar-Agent

**Terakhir disinkronkan: 26 Juli 2026.** Kalau kamu agent yang baru masuk, baca bagian ini sampai habis sebelum menyentuh apa pun.

### 0a. Keadaan lingkungan saat ini

| | Branch | Kode | Skema DB |
|---|---|---|---|
| **Lokal** | `feature/homepage-portal-v2` | terbaru | `20260701000014` |
| **Staging** | ikut branch fitur (auto-deploy) | terbaru | `20260701000014` |
| **Production** | `main` — **DIBEKUKAN** | tertinggal | tertinggal (`20260701000010`) |

> 🚫 **`main` tidak boleh disentuh tanpa perintah eksplisit user.** Detail & urutan rilis yang benar ada di §1. Staging bebas — push branch fitur otomatis merilis ke sana.

### 0b. Yang baru saja mendarat (jangan dikerjakan ulang)

Semua sudah selesai + terverifikasi lewat HTTP nyata, bukan hanya dibaca kodenya:
- **Audit 5 role** → `docs/engineering/AUDIT_*.md` + [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) (baca ringkasannya dulu)
- **Normalisasi skema** (FK + kolom reviewer) → migrasi `20260701000011`–`14`, lihat §16
- **Dashboard terpadu** Fase 0–4 + B8 → [`ANCHOR_DASHBOARD_TERPADU.md`](docs/architecture/ANCHOR_DASHBOARD_TERPADU.md), lihat §17
- **PRD verifikasi admin SRP2** Fase 0–4 → [`PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md)
- **Semua unggahan pindah ke penyimpanan privat** → lihat peringatan di §17 checklist poin 4

### 0c. Yang masih terbuka

1. Pengujian manual staging oleh user (semua verifikasi sejauh ini lewat curl)
2. Dokumen produk usang: `IMPLEMENTATION_ROADMAP.md`, `PRODUCT_REQUIREMENTS_DOCUMENT.md`, `DESAIN_STATUS_TIKET_PENGAJUAN.md`, `SRP2_ACCOUNT_FLOW.md` — masih memuat klaim yang sudah tidak benar
3. Path `private_uploads` sebaiknya jadi variabel `.env`, bukan hasil `dirname(FCPATH)`
4. Migrasi DB production — **setelah** merge, bukan sebelum
5. Di luar kita: integrasi SIMPERUM (belum ada), generator sertifikat PDF

### 0d. Protokol yang mengikat semua agent

1. **Verifikasi mengalahkan dokumentasi — termasuk dokumen ini.** Kalau kode dan dokumen berbeda, kode yang benar; perbaiki dokumennya. Semua kerusakan terburuk di repo ini ditemukan dengan *mencoba*, bukan membaca.
2. **Jangan pernah menampilkan angka, status, atau pesan sukses karangan.** Sudah dua kali terjadi (§17). Kalau belum bisa dihitung, hilangkan elemennya.
3. **Selesai = terverifikasi.** Laporkan hasil apa adanya; kalau ada bagian yang dilewati, sebutkan.
4. **Perbarui bagian §0 ini setiap kali pekerjaan besar mendarat.** Dokumen sinkronisasi yang basi lebih berbahaya daripada tidak ada.
5. **Aksi tak-bisa-ditarik butuh izin:** merge/push `main`, migrasi production, hapus data. Bekerja di lokal & staging bebas.

### 0e. Jebakan yang sudah pernah memakan korban

| Jebakan | Akibatnya dulu |
|---|---|
| `private_uploads` dikira selalu di luar webroot | Dokumen SRP2 bisa diunduh tanpa login di lokal (§17 poin 4) |
| Ada **tiga** blok `DB_PASS` di `.env` | Mengambil "baris terakhir" lewat script = dapat password **production** (§1) |
| `count_all_results($tabel, FALSE)` lalu `get($tabel)` | `FROM x, x` → error 1066, 3 halaman admin mati (§17 poin 6) |
| Nama kolom sort dari input langsung ke `ORDER BY` | Query builder tidak meng-escape nama kolom seperti nilai (§17 poin 6) |
| FK ke `usr_users.id` pakai `UNSIGNED` | `usr_users.id` itu `int(11)` **signed** → errno 150 (§16) |
| Ikon `fa-*` di view admin | Font Awesome tidak di-load di shell admin → ikon blank (§17 poin 5) |
| Form tanpa token CSRF | Fitur tampak normal tapi setiap submit 403 — audit baca-kode tidak menangkapnya |

---

## 1. Identitas Proyek

- **Nama:** Klinik PKP (Klinik Perumahan dan Kawasan Permukiman)
- **Instansi:** Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah
- **Stack:** CodeIgniter 3.1.13 (PHP 8.x), MySQL/MariaDB, Tailwind CSS, Alpine.js, Leaflet.js
- **Bahasa dokumentasi & komentar kode:** Bahasa Indonesia
- **Lingkungan (JANGAN tertukar — diverifikasi 26 Jul 2026):**

  | Lingkungan | URL | Database | Deploy |
  |---|---|---|---|
  | **Production** | `https://palegreen-mink-703421.hostingersite.com/` | `u504551489_klinikpkp` | auto-deploy dari branch **`main`** |
  | **Staging** | `https://floralwhite-lion-710022.hostingersite.com/` | `u504551489_klinikstg` | auto-deploy dari **branch fitur** — terbukti: push ke `feature/homepage-portal-v2` langsung membuat controller baru hidup di staging |

  Kredensial ketiga lingkungan ada di `.env` sebagai blok terpisah (lokal aktif, staging & production dikomentari). **Perhatikan saat mengambil nilai dari `.env` lewat script:** ada tiga blok `DB_PASS`, mengambil yang "terakhir" akan mendapat password PRODUCTION. Ambil per nomor baris atau per blok.

  > 🚫 **JANGAN merge/push ke `main` tanpa perintah eksplisit user.** `main` = rilis ke production, otomatis, tanpa tahap konfirmasi. Aturan yang berlaku sejak 26 Jul 2026: `main` disentuh HANYA setelah seluruh pekerjaan yang sedang berjalan dinyatakan beres oleh user. Bekerja dan merilis ke staging (branch fitur) bebas; naik ke production tidak.

  **Urutan naik ke production (saat sudah diizinkan):** merge ke `main` → tunggu deploy → **baru** jalankan migrasi ke DB production. Jangan dibalik; kode baru dengan skema lama akan error, sedangkan skema baru dengan kode lama aman selama migrasinya bersifat menambah.

## 2. Setup Lokal

```bash
composer install
# copy .env.example -> .env, isi DB_*, KPKP_DATA_KEY/PEPPER, GOOGLE_*, RECAPTCHA_*, GEMINI_API_KEY
# import docs/engineering/schema_klinikpkp.sql ke database bernama sesuai DB_NAME
```

- Entry point: [`index.php`](index.php) (root, bukan `application/`)
- `.env` dibaca via `getenv()` di `application/config/*.php` (lihat `config.php` untuk `base_url`)
- **Jangan edit** folder `system/` (core CodeIgniter) atau `vendor/`

## 3. Struktur Direktori

```
application/
├── config/        # database.php, routes.php, autoload.php (autoload: email, session, database; helper: url, file, ternak)
├── controllers/    # 22 file, lihat tabel §4
├── core/           # MY_Controller.php — base class hierarchy, lihat §5
├── helpers/        # forum_helper.php, ternak_helper.php
├── hooks/          # kosong
├── libraries/      # Encryption_lib, Smart_filter, Sikaper_api, Ternak_api — lihat §6
├── models/         # 8 file, lihat §7
└── views/pages/    # 20 subfolder modular per fitur (admin, auth, umum, program, dll)
docs/               # Dokumentasi resmi lengkap — BACA docs/README.md dulu sebelum menggali di sini
assets/             # css/js/img
uploads/            # file upload user
```

**`dev-scripts/` dan `local-assets/` (gitignored, tidak ter-track git):** script debug/migrasi one-off (`finish_refactor.ps1`, dst) dan aset besar non-web (rekaman rapat, gambar referensi) hidup di sini secara lokal, bukan di repo — sebelumnya sempat ke-commit di root dan ikut ter-deploy ke production (salah satunya sempat jadi celah keamanan aktif), sudah dibersihkan. **Jangan pernah commit script/aset satu-kali-pakai ke root repo** — taruh di salah satu folder ini, keduanya sudah di `.gitignore`.

## 4. Controllers (`application/controllers/`)

| Controller | Fungsi Utama |
|---|---|
| `Auth.php` | Login, register, Google OAuth, onboarding multi-step, hapus akun |
| `Index.php` | Portal utama, AJAX load more, proxy foto Tapera, banyak clean-URL routes (lihat `routes.php`). Termasuk `golek_omah()`, `cari_rumah()`, `panduan_desain()` — halaman mandiri yang duplikat dari section di homepage `awal.php` (lihat §10) |
| `Umum.php` | Forum diskusi (thread, nested reply, likes), layanan masyarakat |
| `Pengembang.php` | Profil pengembang, pendaftaran & verifikasi SRP2 (Sertifikasi Registrasi Pengembang Perumahan) — lihat §14 |
| `Program.php` | Smart Filter — wizard diagnosa kelayakan perumahan (NIK → SIMPERUM mock) |
| `Chat.php` | Chat konsultasi |
| `Statistika.php` | Data statistik & chart |
| `Sikumbang.php`, `Sikaper.php`, `Sikunang.php`, `Siperum.php` | Integrasi API eksternal pemerintah |
| `Pengaturan.php` | Dashboard `/akun` — profil user & hapus akun, plus section per role (`pengembang`: data SRP2 §14; `mahasiswa`: status KKN/Magang; semua role: riwayat antrean + aduan) — lihat §16 |
| `Admin.php`, `Admin_Content.php`, `Admin_Dashboard.php`, `Admin_Settings.php`, `Admin_Users.php`, `Admin_Srp2.php`, `Admin_Kemitraan.php` | Panel superadmin (extends `Admin_Controller`) — lihat §16 |
| `Admin_Kabkota.php`, `Admin_Bidang.php` | Panel admin ter-scope (extends `Admin_Kabkota_Controller`/`Admin_Bidang_Controller`) — lihat §16 |
| `KemitraanPortal.php` | Info + pendaftaran KKN/Magang (`daftar($jenis)`/`simpan()`, login-gated role `mahasiswa`) |
| `Bank_desain.php`, `Berita.php`, `Kemitraan.php`, `Kabupaten.php`, `User_Profile.php` | Fitur pendukung, ukuran kecil |

Controller besar (`Auth.php` ~26KB, `Index.php` ~20KB, `Umum.php` ~17KB) — kandidat untuk dipecah kalau melakukan refactor besar.

## 5. Base Controller Hierarchy (`application/core/MY_Controller.php`)

```
CI_Controller
└── MY_Controller               # security headers (CSP-lite: X-Frame-Options, HSTS, Permissions-Policy dll) di SETIAP request
    ├── Public_Controller        # untuk route publik, tidak ada guard tambahan
    ├── Admin_Controller         # redirect ke Auth/login jika !is_logged || role !== 'admin'; punya render_admin()
    ├── Admin_Kabkota_Controller # role !== 'admin_kabkota' → redirect; expose $this->my_kabupaten_id dari session; punya render_scoped_admin()
    └── Admin_Bidang_Controller  # role !== 'admin_bidang' → redirect; expose $this->my_bidang_kode dari session; punya render_scoped_admin()
```

Helper session di `MY_Controller`: `is_logged_in()`, `get_user_id()`, `current_role()`, `has_role($role)`, `sanitize_redirect()` (cegah open-redirect — cek ini sebelum pakai `redirect($_GET['next'])` gaya apa pun).

> Catatan: TIDAK ada kelas `Auth_Controller`. Kalau menemukan referensi itu di dokumen lama/checkpoint, itu keliru — controller auth cukup pakai `Public_Controller` biasa + cek session manual.

## 6. Libraries (`application/libraries/`)

| Library | Fungsi |
|---|---|
| `Encryption_lib.php` | AES-256-GCM encrypt/decrypt + SHA-256 deterministic hash (untuk lookup NIK terenkripsi). Kunci dari `.env`: `KPKP_DATA_KEY`, `KPKP_DATA_PEPPER`. **JANGAN GANTI kunci ini setelah data terenkripsi** — data akan hilang permanen. |
| `Smart_filter.php` | Kalkulasi Desil UN HABITAT Matrix untuk wizard diagnosa kelayakan di `Program.php` |
| `Sikaper_api.php` | Integrasi API Sikaper |
| `Ternak_api.php` | Integrasi API Ternak Web (katalog bank desain) |

## 7. Models (`application/models/`)

`Auth_model`, `User_model`, `Forum_model`, `Program_model`, `Chat_model`, `Admin_model`, `Setting_model`, `Buka_peta` (GIS/spasial — pakai nama tabel dinamis via parameter, lihat §8 soal tabel legacy).

## 8. Database

Schema baseline (snapshot lama, TIDAK real-time): [`docs/engineering/schema_klinikpkp.sql`](docs/engineering/schema_klinikpkp.sql). Perubahan skema SEJAK migration library diaktifkan (lihat §16) ada di `application/migrations/*.php` — itu sumber kebenaran untuk tabel/kolom terbaru, bukan file `.sql` di `docs/engineering/`. Jalankan `php index.php migrate` untuk menyamakan skema DB manapun yang sedang ditunjuk `.env` ke migrasi terbaru.

**Konvensi prefix modular** (tabel baru ikuti pola ini):
- `usr_` — akun & data user (`usr_users`, `usr_documents`)
- `sf_` — Smart Filter (`sf_programs`, `sf_program_kategori`, `sf_housing_queue`)
- `forum_` — forum diskusi (`forum_diskusi`, `forum_komentar`, `forum_likes`)
- `sys_` — sistem (`sys_menu`, `sys_multi`, `sys_settings`, `sys_ticket_lookup_limits`)
- `chat_` — konsultasi (`chat_rooms`, `chat_messages`)
- `data_sosmed_perumahan` — sosmed pengembang
- `srp2_` — Sertifikasi Pengembang (`srp2_registrations`, `srp2_certified_developers`, `srp2_documents`) — lihat §14
- Tabel tanpa prefix modular tapi tetap resmi (dibuat lewat `application/migrations/`, bukan lagi `.sql` lepas): `aduan`, `kabupaten`, `bidang`, `kkn_magang_pendaftaran` — lihat §16

**Tabel legacy tanpa prefix** (dipakai lewat parameter dinamis di `Buka_peta.php`, tidak ada di `schema_klinikpkp.sql` — asumsikan sudah ada di DB dari baseline sebelum migrasi ini): `kondisi`, `bendung`, `irigasi`, `saluran_pembuang`.

Jangan hapus tabel existing saat menambah fitur baru — tambah tabel baru mengikuti konvensi prefix di atas.

## 9. Keamanan — JANGAN DIRUSAK

Fondasi ini sudah solid (OWASP + UU PDP compliant), perlakukan sebagai kontrak, bukan implementation detail yang bebas diubah:

1. CSRF protection aktif global (CodeIgniter native)
2. Google OAuth dengan state token kriptografis + anti-redirect (`sanitize_redirect()`)
3. Anti-IDOR — ID user selalu dari `session`/`get_user_id()`, **bukan** dari POST/GET body
4. `Admin_Controller` guard berbasis session role, backend-enforced (bukan hanya sembunyikan UI)
5. AES-256-GCM untuk NIK & Alamat (`Encryption_lib`), SHA-256 deterministic hash untuk lookup
6. Security headers global di `MY_Controller::set_security_headers()`
7. Kredensial hanya lewat `.env`, tidak pernah hardcode

## 10. Routes Penting (`application/config/routes.php`)

Default controller: `Index`. Banyak clean-URL alias ke `Index/*` (`umum`, `profil`, `pengembang`, `simulasi_kpr`, dll), plus alias auth (`login`, `register`, `onboarding`) dan AJAX endpoints (`ajax_articles`, `load_more`, `cari_wil`). Cek file ini sebelum menambah route baru — banyak alias sudah dipakai di frontend, jangan duplikasi path.

**Hub "Nggolek Omah"** (ditambahkan setelah §11 fase UI/UX) — hero card di homepage sekarang link ke `golek_omah`, hub kecil dengan 3 menu card:
- `golek_omah` → `Index::golek_omah()` → `pages/golek_omah/index.php` — halaman hub.
- `cari_rumah` → `Index::cari_rumah()` → `pages/perumahan/cari_rumah.php`. **DUPLIKAT** dari section `#cari-perumahan` di `awal.php` — section itu masih ada dan masih jalan di homepage, ini bukan pengganti. Reuse AJAX `cari_wil`/`load_more`/`ajax_perumahan` yang sama.
- `panduan_desain` → `Index::panduan_desain()` → `pages/bank_desain/panduan_desain.php`. **DUPLIKAT** dari section `#bank-desain` di `awal.php`, section aslinya juga masih ada. Reuse AJAX `ajax_house_designs`. Jangan disamakan dengan route `materia` (`Index::materia()`) — itu placeholder kosong tidak terkait.
- `solusi_pembiayaan` → `Program/solusi_pembiayaan` — wizard diagnosa pembiayaan dengan hasil rekomendasi.
- `solusi_pembiayaan/hasil` → `Program/hasil_diagnosa` — hasil program yang bisa dipilih warga untuk diajukan.
- `solusi_pembiayaan/ajukan` → `Program/ajukan_solusi` — POST-only, memvalidasi program dari session sebelum masuk `sf_housing_queue`.
- `solusi_pembiayaan/cek-tiket` → `Program/cek_tiket` — POST-only lookup publik memakai `ticket_code` + empat digit terakhir NIK; jangan tampilkan PII.
- `cek_status_pengajuan` → `Program/cek_status_pengajuan` — tab navbar dan halaman publik untuk cek status tanpa login.

Jadi kalau menemukan section pencarian rumah atau bank desain di dua tempat (homepage dan halaman mandiri), itu memang disengaja — bukan duplikasi yang perlu dibersihkan.

## 11. Status & Roadmap

Fase 9.5 status tiket sudah tahap 1; Fase 10 (Admin Dashboard untuk validasi manual antrean `sf_housing_queue`) **sudah selesai** — `Admin_Kabkota.php`/`Admin_Bidang.php` fungsional penuh sejak commit sistem role multi-peran (§16). Klaim "belum dimulai" di `docs/product/IMPLEMENTATION_ROADMAP.md`/`ANALISIS_DAN_RENCANA_PERBAIKAN.md`/`DESAIN_STATUS_TIKET_PENGAJUAN.md` sudah usang (diverifikasi ulang 26 Jul 2026, lihat [`docs/engineering/AUDIT_ROLE_WARGA.md`](docs/engineering/AUDIT_ROLE_WARGA.md) Temuan #1) — dokumen produk tersebut belum diperbarui, jangan jadikan acuan untuk klaim ini.

Untuk konteks bisnis (arahan rapat, program perumahan, matrix Smart Filter), baca `docs/meetings/` dan `docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md` — jangan diduplikasi di sini, cek langsung sumbernya karena detail bisnis berubah lebih cepat dari kode.

## 12. Cara Verifikasi Sebelum Percaya Dokumen Lama

Repo ini punya banyak dokumen historis (`docs/archive/`, termasuk `docs/archive/AI_ROADMAP.md`). Sebelum mengandalkan klaim struktur kode dari dokumen manapun (termasuk file ini kalau sudah lama tidak di-update), verifikasi cepat:
- Controller/model list: `ls application/controllers application/models`
- Base controller hierarchy: baca langsung `application/core/MY_Controller.php`
- Tabel DB: `grep "CREATE TABLE" docs/engineering/schema_klinikpkp.sql`

## 13. Frontend & Design System

Warna/tipografi/spacing didokumentasikan di [`docs/design/DESIGN_SYSTEM.md`](docs/design/DESIGN_SYSTEM.md) — **baca itu sebelum menyentuh CSS/warna apa pun**. Ringkasan penting: ada 3 sumber warna yang tidak sinkron (`docs/design/tokens.css` tidak ke-load sama sekali di aplikasi; `assets/css/design-system.css` yang beneran dipakai situs publik; inline `tailwind.config` di `application/views/admin/layouts/head.php` buat panel admin), plus ~1.300 hex literal tertulis manual di 70+ file view karena situs publik tidak punya `tailwind.config`. `docs/design/DESIGN_SYSTEM.md` punya tabel palet kanonis (sudah diverifikasi ke pemakaian nyata di kode) dan daftar warna drift yang masih butuh keputusan — jangan asumsikan `tokens.css` sudah merepresentasikan apa yang tampil di browser.

## 14. Konvensi Frontend Portal + Sertifikasi Pengembang (SRP2)

> Section ini memuat DUA topik yang tidak berhubungan — konvensi frontend portal publik (tabel & skeleton) lalu alur SRP2. Judulnya dulu cuma "Sertifikasi Pengembang (SRP2)" sehingga dua konvensi di bawah ini tersembunyi di tempat yang salah. Nomornya sengaja TIDAK diubah karena dirujuk 8 kali dari dokumen lain; yang diperbaiki judulnya.
>
> **Konvensi di bawah ini berlaku untuk portal PUBLIK.** Untuk tabel di dashboard admin, pakai pola di §17 (server-side + komponen bersama) — jangan campur keduanya.

### Konvensi Tabel Portal

Jika membuat tabel baru di halaman portal, gunakan komponen reusable `application/views/components/portal_data_table.php` sebagai dasar. Tabel wajib mendukung:

- pencarian melalui atribut `data-table-search`;
- sorting kolom melalui `data-table-sort` dan isi sel melalui `data-table-column`;
- pagination melalui `data-table-pagination`;
- ringkasan hasil melalui `data-table-summary`;
- jumlah awal 10 baris dengan `data-table-per-page="10"`.

Setiap baris memakai `data-table-row`, nomor urut memakai `data-table-index`, dan state kosong memakai `data-table-empty`. Pertahankan token warna portal (`--portal-*`, `--teal`, `--brand-primary`) serta pastikan tombol dan kontrol memiliki label yang dapat diakses.

### Konvensi Skeleton Loading

Setiap halaman portal yang memuat data wajib menyediakan skeleton dengan bentuk yang mengikuti layout konten sebenarnya, bukan hanya spinner. Gunakan pola berikut:

- initial load: letakkan skeleton di `#page-loading-skeleton`; layout utama akan menyembunyikannya setelah `window.load`;
- navigasi AJAX: gunakan `page-skeleton animate-pulse`, pertahankan tinggi panel, lalu ganti skeleton setelah fetch selesai;
- gunakan `var(--portal-skeleton)` untuk warna placeholder dan samakan jumlah/ukuran blok dengan judul, toolbar, kartu, atau baris tabel yang akan tampil;
- jangan menampilkan skeleton permanen jika request gagal; kembalikan opacity konten dan tampilkan keadaan error/kosong yang jelas.

Untuk halaman tabel, skeleton minimal berisi blok judul, toolbar, dan 5–10 baris tabel. Reuse pola di `application/views/layouts/main.php` dan `application/views/layouts/footer.php` sebelum menambah markup baru.

## 15. Status Tiket Pengajuan

- Tabel `sf_housing_queue` memiliki `ticket_code` unik; migration existing database ada di [`docs/engineering/migration_housing_queue_ticket.sql`](docs/engineering/migration_housing_queue_ticket.sql), sedangkan schema fresh setup sudah memuat kolomnya.
- Pengajuan dari `Program::ajukan_solusi()` membuat tiket server-side berformat `PKP-XXXXXX`; jangan menerima `ticket_code` atau `program_id` dari user sebagai sumber kebenaran tanpa validasi session.
- `Program::cek_tiket()` adalah endpoint POST publik. Lookup wajib memakai tiket dan empat digit terakhir NIK, lalu hanya boleh mengembalikan status serta timestamp—bukan NIK, alamat, nama, atau dokumen.
- `cek_status_pengajuan` adalah tab navbar dan view standalone yang memakai endpoint lookup yang sama; jangan membuat endpoint status kedua.
- Dashboard `akun` hanya mengambil riwayat antrean berdasarkan `user_id` session. Guest tetap boleh memiliki `user_id = NULL`.
- Halaman sukses memakai tema cerah portal dan menampilkan tiket melalui flashdata satu kali.
- Lookup dibatasi setelah lima percobaan gagal per hash IP dalam satu menit melalui `sys_ticket_lookup_limits`; migration existing database ada di [`docs/engineering/migration_ticket_lookup_rate_limit.sql`](docs/engineering/migration_ticket_lookup_rate_limit.sql).
- Enkripsi NIK penuh masih menjadi hardening lanjutan.
- Acuan produk: [`docs/product/DESAIN_STATUS_TIKET_PENGAJUAN.md`](docs/product/DESAIN_STATUS_TIKET_PENGAJUAN.md).
## 16. Sistem Role Multi-Peran

Daftar resmi role: [`application/config/roles.php`](application/config/roles.php) (`$config['available_roles']`) — **satu-satunya sumber kebenaran**, jangan hardcode string role baru di controller/view.

| Role | Scope tambahan | Dashboard |
|---|---|---|
| `admin` | — (superadmin, lihat semua) | `Admin_Dashboard`, `Admin`, `Admin_Content`, `Admin_Users`, `Admin_Srp2`, `Admin_Kemitraan`, `Admin_Settings` |
| `warga` | — | `/akun` — riwayat antrean perumahan + riwayat aduan |
| `pengembang` | — | `/akun` (§14) + alur SRP2 penuh |
| `mahasiswa` | — | `/akun` (status KKN/Magang) + `KemitraanPortal/daftar/{kkn,magang}` |
| `admin_kabkota` | `usr_users.kabupaten_id` (FK → `kabupaten.id`) | `Admin_Kabkota` — HANYA `sf_housing_queue` di kabupatennya |
| `admin_bidang` | `usr_users.bidang_kode` (FK → `bidang.kode`) | `Admin_Bidang` — HANYA `aduan` di bidangnya |

`vendor` masih ada di kode (onboarding + upload KTP/SIU di `Auth.php`) tapi **sengaja tidak masuk** `available_roles`/rencana ini — dormant, belum ada fitur/dashboard, jangan dianggap bagian dari sistem role baru ini.

**Provisioning role scoped (`admin_kabkota`/`admin_bidang`):** TIDAK bisa didaftar publik — `Auth::onboarding()` `$valid_roles` sengaja tidak memasukkan keduanya. Satu-satunya jalan: superadmin lewat `Admin_Users::create_staff()` (akun baru) atau `Admin_Users::update_role()` (ubah role user existing) — keduanya wajib isi `kabupaten_id`/`bidang_kode` sesuai role yang dipilih, divalidasi terhadap tabel `kabupaten`/`bidang`.

**Session carries scope:** `Auth::login()` dan callback Google OAuth menyimpan `kabupaten_id`/`bidang_kode` ke session saat login (dari kolom `usr_users`, `SELECT *` via `Auth_model::find_by_login()`). `Admin_Kabkota_Controller`/`Admin_Bidang_Controller` baca scope dari session (`$this->my_kabupaten_id`/`$this->my_bidang_kode`), BUKAN dari request — kalau scope kosong di session, controller redirect balik ke login (akun belum di-assign, minta superadmin).

**Anti-IDOR wajib untuk role scoped:** setiap `update_status()` di `Admin_Kabkota`/`Admin_Bidang` WHERE clause-nya harus dobel — `WHERE id = ? AND kabupaten_id = ?` / `WHERE id = ? AND bidang = ?` — supaya admin kabupaten/bidang lain tidak bisa mengubah data di luar scope-nya walau tahu ID barisnya. Cek `$this->db->affected_rows()` untuk membedakan "berhasil" vs "row ada tapi bukan milik scope ini".

**`/akun` sekarang 2 halaman, bukan 1:** `Pengaturan::index()` (route `akun`) = "Status Pengajuan" — satu list gabungan SEMUA jenis pengajuan user (antrean, aduan, SRP2, KKN/Magang) diurut tanggal, item utama sidebar. `Pengaturan::profil()` (route `akun/profil`, baru) = form edit profil + data perusahaan SRP2 + hapus akun (dulu semua nyampur jadi satu di `index()`). Redirect dari `update_profile()`/`update_pengembang_profile()`/`delete_account()` (gagal) sekarang ke `akun/profil`, bukan `akun`.

**Semua dashboard login (bukan cuma admin) satu tema:** `MY_Controller::render_user_dashboard($view, $data, $scoped_menu)` — dipakai `Pengaturan::index()` (dashboard `/akun` untuk role `warga`/`pengembang`/`mahasiswa`) supaya reuse shell admin yang sama (`admin/index.php`: sidebar+topbar) seperti `Admin_Kabkota`/`Admin_Bidang`, bukan lagi halaman portal terpisah bertema gelap sendiri. Bedanya dengan `render_admin()` (khusus `Admin_Controller`, inject `pending_count`): `render_user_dashboard()` bisa dipanggil controller manapun yang extend `MY_Controller` langsung, tanpa gate role tambahan (gate login tetap tanggung jawab controller pemanggil). View yang dipakai lewat method ini WAJIB pakai token `bg-white dark:bg-brand-card`/dst (lihat `application/views/admin/layouts/head.php` untuk daftar token `brand-*`) dan ikon Phosphor (`ph-*`) — bukan `fa-solid`, karena Font Awesome TIDAK di-load di shell admin (cuma dimuat di halaman publik `layouts/main.php`).

**Tabel baru** (migrasi `application/migrations/2026070100000{8,9,10}_*.php`):
- `kabupaten` (id = kode wilayah Kemendagri 4 digit, nama) — 35 kabupaten/kota Jawa Tengah, sama persis dengan array lama di `Index.php` (`kabupaten_kota_jateng`, sekarang jadi tabel nyata).
- `bidang` (kode, nama) — formalisasi 5 nilai yang sudah dipakai `aduan.bidang` (perumahan/kawasan/pertanahan/pengembang/umum). Kolom `aduan.bidang` **tetap varchar**, bukan FK — sengaja tidak diubah supaya tidak menyentuh `Umum::simpan_aduan()`.
- `kkn_magang_pendaftaran` — pendaftaran nyata untuk `KemitraanPortal` (sebelumnya halaman statis tanpa form/tabel sama sekali).
- `sf_housing_queue.kabupaten_id`, `usr_users.kabupaten_id`, `usr_users.bidang_kode`, `aduan.catatan_admin` — kolom baru.

**Sidebar admin ter-scope:** `application/views/admin/layouts/sidebar.php` reuse layout admin superadmin yang sama — kalau view dipanggil dengan variabel `$scoped_menu` (array `[label, icon, url, segment]`), sidebar render menu ringkas itu alih-alih menu superadmin penuh. Dipakai lewat `render_scoped_admin()` di `Admin_Kabkota_Controller`/`Admin_Bidang_Controller`.

**Diketahui belum lengkap (di luar scope sesi ini):**
- `Program::ajukan_solusi()` (alur "Solusi Pembiayaan", identitas dari SIMPERUM lookup) belum mengisi `kabupaten_id` — hanya `Program::submit_antrean()` (dipakai `diagnosa()`/`solusi_pembiayaan()` view) yang sudah kirim field ini. Data lama & jalur `ajukan_solusi()` akan punya `kabupaten_id = NULL`, tidak muncul di dashboard `Admin_Kabkota` manapun.
- Belum ada admin/dashboard untuk role `warga` di luar `/akun` — sesuai permintaan awal (warga tidak butuh panel admin, cuma dashboard aktivitas sendiri).

Fitur pendaftaran & verifikasi SRP2 (Sertifikasi Registrasi Pengembang Perumahan) dibangun ulang jadi interaktif penuh sesi ini (sebelumnya rencananya cuma halaman statis "view-only" — lihat catatan usang di bawah).

**Alur pendaftaran SRP2 sekarang satu wizard di satu halaman** (`Pengembang::syarat()`, route `Pengembang/syarat`) — bukan lagi rangkaian halaman terpisah (syarat → daftar → login → formulir → dokumen). View `pages/pengembang/syarat.php` pakai Alpine `x-data="srp2Wizard(...)"` + `x-show="step === N"`, pola yang sama dengan wizard `Program/solusi_pembiayaan` (`diagnosa.php`):
1. **Syarat** — info dokumen (konten lama, tidak berubah).
2. **Akun** — kalau sudah login sebagai pengembang: langsung tampil "Anda sudah terdaftar" + tombol lanjut. Kalau belum login: tab Masuk/Daftar Cepat, submit lewat `fetch()` ke `Auth/do_login`/`Auth/do_register` (lihat AJAX di bawah) — TIDAK pindah halaman.
3. **Unggah** — 14 dokumen (`dokumen_persyaratan()`), file dipilih dulu semua lalu dikirim **satu per satu** lewat `fetch()` berurutan ke `Pengembang/simpan_dokumen/{id}`, tiap baris punya status sendiri (siap/mengunggah/tersimpan/gagal+tombol ulangi) + toast progres. Boleh ditinggal belum lengkap — sisanya dilengkapi lewat dashboard (`/akun`).
4. **Selesai** — setelah `Pengembang/kirim_pengajuan/{id}` sukses, tombol "Cek Status Pengajuan" → `akun` (satu-satunya navigasi keluar wizard yang disengaja).

**Cabang AJAX (JSON) di controller yang sudah ada — perilaku non-AJAX/halaman biasa TIDAK berubah sama sekali:**
- `Auth::do_login()` / `Auth::do_register()` — kalau `is_ajax_request()`, balas JSON (`status`, `message`/`role`/`registration_id`) alih-alih flashdata+redirect. Untuk `do_register` dengan `srp2_pengembang=1`, draft `srp2_registrations` langsung dibuat di sini (bukan lewat detour "verifikasi email" simulasi lama) supaya wizard bisa lanjut ke step Unggah tanpa request tambahan.
- `Pengembang::akses_pengembang()` — kalau request AJAX dan belum login/salah role, balas **401/403 JSON**, BUKAN `redirect()`. Ini wajib: `fetch()` diam-diam mengikuti redirect dan akan menganggap HTML halaman lain sebagai "berhasil" kalau ini tidak ada.
- `Pengembang::simpan_dokumen()` / `kirim_pengajuan()` — balas JSON per aksi. Validasi keamanan upload (whitelist ekstensi, cek MIME asli via `finfo`, cap 1 MB, nama file acak, folder di luar webroot) **tidak disentuh**, cuma cara membalas responsnya yang bercabang.

**Form manual 12 field (nama_peserta/nik_ktp/dst) DIARSIPKAN** — `Pengembang::formulir()`/`simpan()` sekarang cuma redirect ke `Pengembang/syarat`, viewnya dipindah ke `archive/formulir_sertifikasi_12field.php`. Jalur resmi pendaftaran cuma satu: daftar cepat (nama perusahaan + email + password) di step 2 wizard. Halaman `Pengembang/daftar` (standalone lama) juga diarsipkan (`archive/daftar_standalone.php`), `daftar()` sekarang cuma redirect ke `syarat` supaya tautan/bookmark lama tidak jadi dead-end.

- `result($id)` — halaman resi pendaftaran, login-gated DAN dibatasi hanya bisa lihat resi milik sendiri (`WHERE id = ? AND user_id = <session>`). **Perbaikan keamanan lama**: sebelumnya rawan IDOR — `id` auto_increment sekuensial gampang ditebak.
- `profil($id)` — halaman publik read-only detail pengembang, hanya untuk `status_verifikasi = 'Diterima'`.

**Controller `Pengaturan.php`** (dashboard akun, route `akun`):
- `update_pengembang_profile()` (route `akun/update_pengembang`, BARU) — hanya jalan kalau `session->userdata('role') === 'pengembang'`, update data SRP2 milik sendiri (`WHERE user_id` selalu dari sesi, bukan dari input — anti-IDOR).
- `index()` sekarang juga fetch `srp2_registrations` by `user_id` kalau role user `pengembang`, dikirim ke view sebagai `$pengajuan_sp2`.
- View `pages/pengaturan/index.php` punya section kondisional (`isset($pengajuan_sp2)`): badge status pengajuan (Pending/Diterima/Ditolak), form edit data pengembang sendiri, dan tombol "Download Sertifikat" yang jujur menampilkan "belum tersedia" (bukan simulasi sukses palsu) karena generator sertifikat PDF asli belum dibangun — lihat follow-up di bawah.

**Tabel `srp2_registrations`** — skema lengkap di [`docs/engineering/migration_srp2_registrations.sql`](docs/engineering/migration_srp2_registrations.sql) (tidak masuk `schema_klinikpkp.sql` utama, cek file migrasi ini terpisah). Kolom `user_id`, `instagram`, `website`, `sosmed_lainnya` **baru** (bukan dari skema lama) — untuk fitur cek status pengajuan di dashboard akun dan halaman profil publik. Migrasi **sudah dijalankan ke staging** (`u504551489_klinikstg`) tapi **belum ke production** — jalankan manual ke production dulu sebelum fitur ini dianggap live di sana.

**Route baru** di `application/config/routes.php`: `$route['akun/update_pengembang'] = 'Pengaturan/update_pengembang_profile';`. Method `Pengembang.php` lainnya tetap pakai default CI routing (`Pengembang/method`), tidak ada clean-URL alias.

**Perbaikan bug terkait:** role pengembang saat registrasi akun dulu salah tersimpan sebagai string `'pages/pengembang/pengembang'` (bug copy-paste path view) — sudah diperbaiki jadi `'pengembang'` di `application/controllers/Auth.php`. Kalau menemukan dokumen/kode lama yang masih menyebut role ini dengan nilai salah tersebut, itu usang.

**`Pengembang::masuk()` (route `Pengembang/masuk`) dipertahankan sebagai fallback**, bukan lagi jalur utama — cuma dipakai kalau ada yang deep-link langsung ke halaman gated non-wizard (mis. bookmark lama ke `Pengembang/dokumen/{id}`) sambil belum login: `akses_pengembang()` masih redirect ke sini untuk request NON-AJAX. Untuk request AJAX (dari wizard `syarat.php`), `akses_pengembang()` balas JSON 401/403 (lihat di atas), tidak pernah redirect ke halaman ini. `Auth/login`/`Auth/register` utama TIDAK diubah, tetap berfungsi biasa untuk role lain — mekanisme `?next=`/`redirect_to` (dibaca sebelum cek status login, divalidasi `sanitize_redirect()`) tetap ada di `Auth::login()`/`do_login()` untuk jalur fallback ini.

**Detour "verifikasi email" simulasi (`Auth/verify_pending` → `Auth/do_verify_email` → `Auth/lanjutkan`) sudah dilewati untuk SRP2** — draft `srp2_registrations` dibuat langsung di `do_register()` saat itu juga. `Auth::lanjutkan()` sendiri TIDAK dihapus (masih dipakai alur verifikasi generik non-SRP2), cuma sudah idempotent kalau dipanggil untuk akun SRP2 (draft sudah ada, tidak dobel insert).

**Audit sistem/keamanan/konsistensi seluruh role (26 Jul 2026):** [`docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) — baca sebelum membangun role baru yang lebih kompleks. Audit per role: [`AUDIT_ROLE_PENGEMBANG.md`](docs/engineering/AUDIT_ROLE_PENGEMBANG.md), [`AUDIT_ROLE_WARGA.md`](docs/engineering/AUDIT_ROLE_WARGA.md), [`AUDIT_ROLE_MAHASISWA.md`](docs/engineering/AUDIT_ROLE_MAHASISWA.md), [`AUDIT_ROLE_ADMIN_SCOPED.md`](docs/engineering/AUDIT_ROLE_ADMIN_SCOPED.md) (`admin_kabkota`+`admin_bidang`). Semua temuan murni observasi, belum diperbaiki. Ringkasan pola lintas-role yang paling berdampak: (1) **tidak ada alur admin approve/reject `srp2_registrations`** — satu-satunya role yang belum punya sisi reviewer, role lain (`warga`→`Admin_Kabkota`/`Admin_Bidang`, `mahasiswa`→`Admin_Kemitraan`) sudah fungsional penuh; (2) **upload disimpan di direktori publik** berulang di 3 lokasi berbeda (`Auth::_handle_uploads()`, `Umum::simpan_aduan()`, `KemitraanPortal::simpan()`) — semua seharusnya pakai pola `private_uploads/` seperti SRP2; (3) **`User_model::delete_user_account()` tidak membersihkan tabel turunan role baru** (`aduan`, `kkn_magang_pendaftaran`, kemungkinan `srp2_registrations`) — cuma menangani tabel forum lama; (4) **`sf_housing_queue.kabupaten_id` dipercaya mentah dari request warga** — 🔴 satu-satunya temuan tingkat Tinggi, mempengaruhi integritas seluruh model scoping `admin_kabkota`. Roadmap perbaikan gap SRP2 ada di [`docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md), termasuk checklist "Prinsip Umum untuk Role Baru" yang wajib diisi sebelum role kompleks berikutnya dibangun.

**Dashboard terpadu:** [`docs/architecture/ANCHOR_DASHBOARD_TERPADU.md`](docs/architecture/ANCHOR_DASHBOARD_TERPADU.md) — dokumen ANCHOR untuk seluruh pekerjaan dashboard, baca sebelum menyentuh sidebar/render helper/controller admin mana pun. **Fase 0–4 SELESAI SEMUA** (26 Jul 2026), lihat §17 di bawah + tabel status di anchor.

## 17. Dashboard Terpadu — Registry Modul

Semua role login memakai SATU shell dashboard (`application/views/admin/index.php`: sidebar+topbar+content slot). Menu sidebar dibangun dari **satu registry**: [`application/config/dashboard_modules.php`](application/config/dashboard_modules.php), difilter per role+scope oleh `MY_Controller::dashboard_menu()`. Ketiga render helper (`render_admin()`, `render_scoped_admin()`, `render_user_dashboard()`) sekarang semuanya delegasi ke `render_user_dashboard()` — nama method dipertahankan karena banyak call site, tapi perilakunya sudah seragam. Registry ini juga merangkap peta role→tabel pengajuan→reviewer (menggantikan rencana `role_admin_map.php` yang disebut di `DESAIN_NORMALISASI_SKEMA_ROLE.md` — jangan buat file itu).

> ⚠️ **Registry = menu yang TAMPIL, BUKAN otorisasi.** Penegakan akses tetap di constructor base controller (`Admin_Controller`/`Admin_Kabkota_Controller`/`Admin_Bidang_Controller`) + WHERE ganda scope + `affected_rows()`. Modul terdaftar untuk role X tapi controller-nya menolak role X = menu tampil, akses ditolak — itu perilaku BENAR (fail-closed). Perbaikannya selalu di registry, JANGAN pernah melonggarkan guard controller.

**Cara menambah modul dashboard (checklist wajib, urutan tetap):**
1. Tentukan reviewer & scope pakai checklist "Prinsip Umum untuk Role Baru" di [`docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md) — SEBELUM menulis kode.
2. Buat/pakai controller yang extend base class sesuai role penegak. Guard di constructor, bukan di method.
3. Endpoint tulis: POST-only, whitelist field eksplisit, WHERE ganda scope bila ter-scope, cek `affected_rows()`, isi `reviewed_by`/`reviewed_at`.
4. Lampiran: WAJIB lewat `MY_Controller::store_private_upload()` (validasi ekstensi + MIME asli via `finfo` + batas ukuran + nama acak, simpan ke `private_uploads/{domain}/{pemilik}/`) dan disajikan lewat `serve_private_file()` di endpoint ber-guard. Tidak ada pengecualian, jangan bikin jalur unggah sendiri.
   > ⚠️ **"private_uploads di luar webroot" TIDAK selalu benar.** Diverifikasi 26 Jul 2026: di layout XAMPP lokal `dirname(FCPATH)` sama dengan DocumentRoot Apache, sehingga `http://localhost/private_uploads/srp2/7/xxx.pdf` menyajikan dokumen SRP2 tanpa login. Karena itu `ensure_private_uploads_protected()` menulis `.htaccess` penolak akses di akar `private_uploads/` — dibuat oleh kode, bukan manual, sebab direktori itu di luar repo git. Jangan hapus pemanggilannya. **Batas:** `.htaccess` hanya dipatuhi Apache/LiteSpeed; kalau pindah ke nginx, proteksi ini tidak berlaku dan wajib diganti aturan server atau direktorinya benar-benar dipindah keluar DocumentRoot. **Belum diverifikasi di production** — cek sendiri apakah `dirname(FCPATH)` di sana juga tersaji web.
5. View: render lewat helper yang sesuai; token `brand-*` + ikon Phosphor `ph-*` (**Font Awesome TIDAK di-load di shell admin** — memakai `fa-*` di view admin = ikon blank); skeleton §14.
6. Komponen bersama WAJIB dipakai, jangan bikin varian baru: `admin/components/status_badge.php` (petakan status domainmu ke `pending|process|ok|reject`), `review_form.php` (form keputusan), `table_toolbar.php` (kotak cari + slot filter), `pagination.php`. Pola tabel admin **selalu**: `table_state()` (whitelist kolom sort) → terapkan filter → `count_all_results('', FALSE)` → `paginate_state()` → ambil baris. Semua link tabel lewat `admin_table_url()` supaya filter/cari/urut saling terbawa. **Jangan** kirim semua baris ke browser lalu filter di klien — pola itu sudah dihapus dari sisi admin (B8).
   > Dua jebakan yang sudah memakan korban: (a) `count_all_results($tabel, FALSE)` menyetel FROM **dan** menyimpan state — diikuti `->get($tabel)` jadi `FROM x, x` (error 1066); pakai `->from($tabel)` di depan lalu `count_all_results('', FALSE)` lalu `->get()` tanpa argumen. (b) Nama kolom sort TIDAK PERNAH boleh dari input langsung ke ORDER BY — CI query builder tidak meng-escape nama kolom seperti nilai; whitelist lewat `table_state()`.
7. TERAKHIR: satu entri di `dashboard_modules.php`. Active-state dihitung otomatis — jangan tulis logika active sendiri di view. Kalau modulmu punya antrean, isi `table` + `pending_where` (deklarasi tunggal "apa arti belum diproses" untuk domain itu — dipakai badge sidebar sekaligus kartu overview), plus `ringkas` kalau mau muncul sebagai kartu di `Admin_Dashboard`.
8. FK baru ke `usr_users.id` = `INT` signed, bukan `UNSIGNED` (lihat catatan migrasi `20260701000011/12`).

> ⚠️ **Jangan pernah menampilkan angka, status, atau pesan sukses karangan.** Dua kali ditemukan di repo ini: overview dengan "Publikasi Aktif = 24" hardcode + chart dummy + feed nama fiktif (B2), dan `Admin::update_status()` yang mengklaim "telah disinkronisasi dengan API SIMPERUM" padahal tidak ada request apa pun yang dikirim (B11). Kalau sebuah metrik belum bisa dihitung atau integrasi belum ada, hilangkan elemennya atau tulis apa adanya — **jangan disimulasikan**. Integrasi SIMPERUM memang belum ada dan akan menyusul.

**Normalisasi skema (26 Jul 2026):** [`docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md`](docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) — Opsi A (konvensi konsisten, tabel domain tetap terpisah) dipilih dan migrasi intinya **sudah dijalankan**: `srp2_registrations`, `aduan`, `kkn_magang_pendaftaran`, `sf_housing_queue` sekarang semua punya `reviewed_by`/`reviewed_at` + FK `user_id` ke `usr_users.id` dengan `ON DELETE` yang sadar per domain (migrasi `20260701000011`-`20260701000013`). **Catatan penting untuk kolom baru mana pun yang menunjuk `usr_users.id`:** gunakan `INT` biasa, BUKAN `UNSIGNED` — `usr_users.id` adalah `int(11)` signed (peninggalan skema lama), FK ke kolom unsigned akan gagal (errno 150). `User_model::delete_user_account()` sudah diperbarui untuk `unlink()` file fisik SRP2/KKN-Magang sebelum baris DB ter-CASCADE. Kolom `reviewed_by`/`reviewed_at` **sudah diisi** keempat jalur reviewer sejak Fase 2. **Yang masih terbuka:** lokasi upload publik (Pola A di [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md)) belum dipindah ke `private_uploads/` — 3 lokasi (`Auth::_handle_uploads()`, `Umum::simpan_aduan()`, `KemitraanPortal::simpan()`), sebaiknya lewat satu helper terpusat.

**Follow-up yang sengaja di luar scope sesi ini** (jangan dikerjakan tanpa arahan baru, cukup dicatat):
- Generator sertifikat PDF asli belum ada — tombol "Download Sertifikat" di dashboard akun sengaja nonaktif dengan pesan jujur, bukan simulasi.
- ~~`Auth::save_onboarding()` menulis kolom `nama_perusahaan`/`alamat_kantor`/`telp_kantor` yang tidak ada di skema~~ — **sudah tidak berlaku**, ketiga kolom itu memang ada di `usr_users` (lihat §16). Catatan lama ini usang, jangan diikuti.
- [`docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md`](docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md) dan [`docs/product/IMPLEMENTATION_ROADMAP.md`](docs/product/IMPLEMENTATION_ROADMAP.md) masih menyebut menu Pengembang/SP2 sebagai halaman statis/"belum berupa sistem interaktif" ("view-only") — **usang (superseded)**, sudah dikonfirmasi user untuk dibuat interaktif penuh sesi ini.

