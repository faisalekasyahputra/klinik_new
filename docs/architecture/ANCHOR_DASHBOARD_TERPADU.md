# ANCHOR — Dashboard Terpadu Multi-Role Klinik PKP

**Status:** Rencana disetujui konsepnya, eksekusi per fase menunggu perintah. Dokumen ini adalah **anchor**: satu sumber kebenaran untuk seluruh pekerjaan dashboard ke depan — semua agent (AI maupun manusia) wajib baca ini sebelum menyentuh apa pun yang berbau dashboard.
**Tanggal:** 26 Juli 2026
**Dasar:** analisis paralel 2 subagent (inventaris permukaan existing + proposal arsitektur), dikonsolidasikan dan dinilai ulang. Fondasi data: [`DESAIN_NORMALISASI_SKEMA_ROLE.md`](DESAIN_NORMALISASI_SKEMA_ROLE.md) (Opsi A sudah dieksekusi — `reviewed_by`/`reviewed_at` + FK di 4 tabel pengajuan). Fondasi audit: [`../engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md`](../engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md). Gap produk: [`../product/PRD_VERIFIKASI_ADMIN_SRP2.md`](../product/PRD_VERIFIKASI_ADMIN_SRP2.md).

---

## 1. Keputusan Arsitektur Inti (final, jangan dilitigasi ulang tanpa alasan baru)

### 1a. "Satu dashboard untuk semua role" TIDAK berarti satu controller

Temuan kunci inventaris: **shell dashboard terpadu sudah ada** — `application/views/admin/index.php` (+ `layouts/head/sidebar/topbar/footer`) sudah dipakai SEMUA role hari ini lewat tiga render helper (`render_admin`, `render_scoped_admin`, `render_user_dashboard`), semuanya bermuara ke shell yang sama. Yang belum terpadu hanya dua:

1. **Menu** — sidebar superadmin hardcode HTML 7 link; role lain kirim `$scoped_menu` hardcode di 3 tempat berbeda. Tidak ada satu sumber kebenaran "role X melihat modul apa".
2. **Komponen konten** — form update status + tabel + badge status ditulis ulang 3-5x dengan gaya berbeda-beda.

Maka target arsitekturnya: **satu shell (sudah ada) + satu registry modul (baru) + komponen view bersama (baru) + controller domain TETAP TERPISAH sebagai security boundary.**

Satu `Dashboard.php` generik ditolak dengan alasan yang sama seperti Opsi B skema ditolak di `DESAIN_NORMALISASI_SKEMA_ROLE.md`: guard role akan pindah dari constructor base controller (fail-closed, tidak bisa lupa per-method) ke lookup runtime (fail-open kalau registry salah tulis), dan business logic per domain (14 dokumen SRP2, ticket_code, scoping bidang) tetap harus ditulis per domain — abstraksi bertambah, kode domain tidak berkurang.

### 1b. "Aktif/nonaktifkan fitur per role" = registry, bukan tabel setting

Mekanisme on/off per role cukup lewat field `roles` di `application/config/dashboard_modules.php` (file BARU, draft lengkap di §3). Tidak ada override per-user, tidak ada toggle `sys_settings` runtime — keduanya menambah dimensi debug tanpa kebutuhan produk, dan toggle DB menciptakan ilusi "fitur mati" padahal controller-nya tetap hidup di URL. Kalau nanti butuh mematikan modul sementara: field `'enabled' => false` di registry, deploy via git.

### 1c. Dua lapis yang tidak boleh dicampur

- **Lapis tampilan (registry):** menentukan menu apa yang TAMPIL untuk role apa. **BUKAN keamanan.**
- **Lapis penegakan (controller):** constructor base class (`Admin_Controller`/`Admin_Kabkota_Controller`/`Admin_Bidang_Controller`) + scope dari session + WHERE ganda + `affected_rows()` + `reviewed_by`/`reviewed_at`. **Tidak berubah sedikit pun dari pola yang sudah terbukti.**

Konsekuensi yang disengaja: modul yang terdaftar di registry untuk role X tapi controller-nya menolak role X = menu tampil, akses ditolak. Itu perilaku BENAR (fail-closed). Perbaikannya selalu di registry, JANGAN pernah melonggarkan guard controller.

### 1d. Satu URL per domain dipertahankan

Tidak ada route `/dashboard` universal. `akun`, `Admin_Kabkota`, `Admin_Srp2/pending` tetap URL masing-masing — guard per class, log server jelas per peran, deep-link predictable. Yang "satu" adalah shell visual dan bahasa desainnya.

### 1e. Yang TIDAK ikut digabung

- `Auth`, `Pengembang` (wizard SRP2 sisi pemohon), `KemitraanPortal`, semua halaman portal publik — itu alur publik, bukan dashboard.
- `Admin_Settings`, `Admin_Content`, `Admin_Users` — CRUD superadmin satu-role; cukup jadi entri registry (menu), kodenya tidak disentuh.

---

## 2. Kondisi Nyata Saat Ini (temuan inventaris — hutang yang ikut diselesaikan)

Bug/hutang konkret yang ditemukan saat inventaris, diurutkan berbahaya → kosmetik. Nomor ini dirujuk di rencana fase (§4):

| # | Temuan | Lokasi |
|---|---|---|
| B1 | `pages/admin/dashboard.php` (443 baris, halaman Validasi Antrean superadmin) adalah fork lama dari versi kabkota: dark-only hex hardcode + ikon `fa-solid` padahal shell admin TIDAK me-load Font Awesome → **ikon blank/rusak di produksi sekarang**. ±90% duplikat dari `admin_kabkota/dashboard.php` yang lebih baru dan benar. | `pages/admin/dashboard.php` |
| B2 | `Admin_Dashboard::index()` overview sebagian datanya **palsu**: "Publikasi Aktif" hardcode 24, chart Chart.js data dummy statis, "Aktivitas Terkini" HTML statis. | `admin/dashboard.php:75,158-194,215-236` |
| B3 | Topbar hardcode link "Profil Saya" → `User_Profile` yang digate `Admin_Controller` — role selain superadmin diusir ke login dengan pesan menyesatkan. | `admin/layouts/topbar.php:50` |
| B4 | `render_admin()` selalu query `count_pending_queue()` global tiap page load; kalau mekanisme badge sama dipakai role scoped, angkanya bohong (bukan wilayahnya). | `MY_Controller.php:172` |
| B5 | Flashdata dirender dobel di `/akun` (shell `admin/index.php` + `pages/pengaturan/index.php` sama-sama render blok flash). | dua file tsb |
| B6 | `render_scoped_admin()` didefinisikan 2x copy-paste identik (kabkota + bidang). | `MY_Controller.php:205,240` |
| B7 | Tanpa paginasi server-side di mana-mana: `Admin`/`Admin_Kabkota` kirim s.d. 1000 baris JSON ke Alpine; `Admin_Bidang`/`Admin_Kemitraan`/`Admin_Users` render semua baris tanpa LIMIT. | model/controller terkait |
| B8 | Tiga paradigma tabel hidup berdampingan (Alpine smart-table, tabel server-rendered polos, `portal_data_table.php` resmi §14) + badge status ditulis ulang 5x dengan vocabulary status beda-beda per domain. | banyak view |
| B9 | `User_Profile.php` vs `Pengaturan::profil()` — dua halaman "Profil Saya" dengan logika update hampir identik, dipisah cuma karena gate role. | dua controller tsb |

Kosakata status per domain (`pending/approved/rejected` vs `Baru/Diproses/Selesai` vs `Diajukan/Diterima/Ditolak` vs `Draft/Pending/Diterima/Ditolak`) **sengaja TIDAK diseragamkan di DB** — menyentuh alur publik yang sudah jalan. Penyeragaman terjadi di lapisan view lewat komponen `status_badge` yang menerima peta per-domain.

---

## 3. Registry Modul — kontrak `application/config/dashboard_modules.php`

File ini juga **menggantikan** rencana `role_admin_map.php` (kontrak #5 Opsi A) — jangan buat dua file untuk peta yang sama.

Field per modul: `label`, `icon` (Phosphor `ph-*`, BUKAN Font Awesome), `url` (controller/method CI, juga dipakai deteksi active-state), `roles` (array — display only), `scope` (`null`/`'kabupaten_id'`/`'bidang_kode'` — menu hanya dirender kalau session punya scope terisi), `group` (section sidebar), `order`, opsional `badge` (`['model'=>..,'method'=>..]` — counter di sidebar, menggantikan `pending_count` hardcode), opsional `table`+`review_by` (dokumentasi hidup peta pengajuan→reviewer).

> **Tabel di bawah adalah rencana awal Fase 0 (12 modul).** Kondisi akhir setelah Fase 1–4: **13 modul** — bertambah `aduan_semua` (Fase 4). Mekanisme badge berubah dari `['model'=>..,'method'=>..]` menjadi flag `TRUE` + `pending_where` di registry. `status_pengajuan` TIDAK memuat role `admin` (superadmin bukan pemohon), sedangkan `profil` memuatnya sejak `User_Profile` dilebur. Sumber kebenaran sekarang adalah file registry itu sendiri, bukan tabel ini.

Entri rencana awal Fase 0 (12 modul):

| Key | Roles | URL | Group | Catatan |
|---|---|---|---|---|
| `status_pengajuan` | semua role login | `akun` | Akun | agregator lintas-domain existing |
| `profil` | semua role login | `akun/profil` | Akun | |
| `antrean_kabkota` | `admin_kabkota` | `Admin_Kabkota` | Layanan | scope `kabupaten_id`; table `sf_housing_queue` |
| `aduan_bidang` | `admin_bidang` | `Admin_Bidang` | Layanan | scope `bidang_kode`; table `aduan` |
| `overview` | `admin` | `Admin_Dashboard` | Utama | data palsu dibersihkan di Fase 4 (B2) |
| `validasi_antrean` | `admin` | `Admin` | Utama | badge `count_pending_queue` |
| `srp2_verifikasi` | `admin` | `Admin_Srp2/pending` | Layanan | **modul BARU Fase 1**; badge `count_pending_srp2` |
| `srp2_direktori` | `admin` | `Admin_Srp2` | Layanan | |
| `kemitraan` | `admin` | `Admin_Kemitraan` | Layanan | table `kkn_magang_pendaftaran` |
| `users` | `admin` | `Admin_Users` | Manajemen | |
| `content` | `admin` | `Admin_Content` | Manajemen | |
| `settings` | `admin` | `Admin_Settings` | Manajemen | |

`pengembang` sengaja tidak punya modul "SRP2 saya" terpisah — sudah punya rumah di `status_pengajuan` + `profil`. Jangan tambah menu untuk hal yang sudah punya rumah.

Header file registry WAJIB memuat peringatan (verbatim): *"'roles' di sini HANYA mengatur menu yang TAMPIL. Otorisasi sesungguhnya ada di constructor base controller tujuan + WHERE ganda scope di query. Menambah role di sini TANPA controller yang menegakkan role itu = menu tampil tapi diusir saat diklik — dan itu perilaku yang BENAR."*

---

## 4. Rencana Eksekusi Per Fase

Aturan main: **tiap fase aplikasi tetap jalan penuh** (tidak ada big-bang), tiap fase punya kriteria selesai yang bisa diverifikasi, dan fase tidak boleh dikerjakan melompat tanpa alasan tertulis di dokumen ini.

### Fase 0 — Registry + sidebar registry-driven (fondasi, nol perubahan perilaku akses)
- Buat `application/config/dashboard_modules.php` (§3).
- Tambah `MY_Controller::dashboard_menu()`: load registry → filter role dari `current_role()` → filter scope terisi → group + order → array menu.
- Tulis ulang `admin/layouts/sidebar.php`: satu loop atas `$dashboard_menu`, menggantikan blok binary `isset($scoped_menu)` + HTML hardcode superadmin.
- Seragamkan 3 render helper: ketiganya panggil `dashboard_menu()`; nama method TIDAK di-rename (banyak call site). Hapus `$scoped_menu` hardcode di `Admin_Kabkota_Controller`, `Admin_Bidang_Controller`, `Pengaturan::scoped_menu()` — **harus tuntas dalam satu commit**; sisa satu hardcode = dua sumber kebenaran, lebih buruk dari sebelumnya.
- Badge generik: `render` helper loop entri registry ber-`badge` yang role-nya cocok (menutup B4 — scoped role tidak dapat badge global yang bohong).
- Sekalian (menyentuh file yang sama): B3 (link profil topbar → arahkan sesuai role: `admin` → `User_Profile`, lainnya → `akun/profil`), B5 (hapus blok flash dobel di `pages/pengaturan/index.php`), B6 (lipat `render_scoped_admin()` dobel jadi satu).
- **Kriteria selesai:** login 6 role → menu identik fungsinya dengan sebelumnya, badge benar per role, tidak ada regresi akses (verifikasi curl per role seperti biasa).

### Fase 1 — Modul Verifikasi SRP2 (fitur baru; komponen bersama lahir di sini)
- Eksekusi `PRD_VERIFIKASI_ADMIN_SRP2.md` Fase 0+1: keputusan `certified_developer_id` (opsi b PRD — kolom link di `srp2_registrations`, migrasi baru; ingat aturan **INT signed** untuk FK apa pun — cek dulu tipe PK tabel tujuan), lalu `Admin_Srp2::pending()/detail()/terima()/tolak()/lihat_dokumen()` extends `Admin_Controller`.
- `terima()`/`tolak()` langsung mengisi `reviewed_by`/`reviewed_at` (kolom sudah ada sejak migrasi `20260701000011`), `tolak()` wajib `catatan_admin` (validasi server), `lihat_dokumen()` = `readfile()` dari `private_uploads/srp2/{id}/` dengan `Content-Type` dari kolom `mime_type` — tidak ada URL publik.
- Komponen bersama dibuat **bersama konsumen pertamanya** (bukan spekulatif): `admin/components/status_badge.php` (terima peta label→kelas per domain) dan `admin/components/review_form.php` (action_url, allowed_statuses, row_id, flag catatan_wajib). Tabel: reuse `views/components/portal_data_table.php` (§14) — jangan lahirkan paradigma tabel keempat.
- Daftarkan `srp2_verifikasi` + `count_pending_srp2` di registry.
- **Kriteria selesai:** kriteria penerimaan PRD Fase 1 (4 poin) lolos lewat pengujian curl + browser nyata; pengembang berstatus `Ditolak` bisa unggah ulang (status-lock existing sudah mengizinkan, verifikasi tidak regresi).

### Fase 2 — Retrofit 3 reviewer existing ke pola baru
- `Admin_Kabkota::update_status()`, `Admin_Bidang::update_status()`, `Admin_Kemitraan::proses()`: tambah `reviewed_by`/`reviewed_at` di `update()` yang sama (menutup sisa terbuka `DESAIN_NORMALISASI` + `AUDIT_ROLE_ADMIN_SCOPED` #2). Query & guard TIDAK disentuh.
- Ganti markup badge + form status ketiganya ke komponen Fase 1 (menutup sebagian B8).
- B1: hapus `pages/admin/dashboard.php`, `Admin::index()` render versi turunan `admin_kabkota/dashboard.php` (tanpa WHERE scope) — memperbaiki ikon rusak + light mode sekali jalan.
- Perbaiki false-negative `affected_rows()` (AUDIT_ROLE_ADMIN_SCOPED #6): SELECT cek keberadaan di scope dulu, bedakan pesan "tidak ada perubahan" vs "bukan wilayah/bidang Anda" — di kedua controller scoped.
- **Kriteria selesai:** approve/reject di 4 modul reviewer mengisi `reviewed_by` (cek DB langsung); UI ketiganya memakai komponen yang sama; halaman Validasi Antrean superadmin tampil benar di light+dark dengan ikon Phosphor.

### Fase 3 — Integritas data hulu (prasyarat kepercayaan data dashboard)
- 🔴 `AUDIT_ROLE_ADMIN_SCOPED` #1: `Program::submit_antrean()` berhenti menerima `kabupaten_id` mentah — validasi minimal terhadap tabel `kabupaten`, idealnya diturunkan dari profil user login / data program.
- `Program::ajukan_solusi()` diisi `kabupaten_id`-nya (gap lama §16) — pertimbangkan konsolidasi kedua jalur insert ke satu method model yang mewajibkan `kabupaten_id`.
- Baris lama `kabupaten_id IS NULL`: badge "Belum Terpetakan Wilayah" di dashboard superadmin (bukan backfill buta).
- **Kriteria selesai:** tidak ada jalur baru yang bisa membuat baris antrean dengan kabupaten_id sembarang/kosong; baris NULL lama terlihat eksplisit oleh superadmin.

### Fase 4 — Kualitas & skala (boleh dicicil, tidak memblokir apa pun)
- B2: `Admin_Dashboard` overview diisi data nyata dari registry (kartu ringkas per modul ber-`table`: count pending per domain) — hapus angka hardcode, chart dummy, feed statis. Jujur lebih baik daripada indah-palsu.
- B7: paginasi server-side untuk `Admin_Bidang`/`Admin_Kemitraan`/`Admin_Users` (yang tanpa LIMIT) — pola LIMIT+OFFSET sederhana, bukan infinite scroll.
- B9: lebur `User_Profile` ke `Pengaturan::profil()` (satu halaman profil untuk semua role, section admin-only kondisional) — hapus controller `User_Profile`.
- Visibilitas superadmin atas `aduan` (AUDIT_ROLE_ADMIN_SCOPED #5): view read-only lintas bidang sebagai modul registry baru.
- `catatan_admin` aduan tampil ke pelapor (AUDIT #7) — tambah kolom di select `Pengaturan::index()` + tampilkan di view.

**Di luar scope dashboard (jangan diselundupkan ke fase mana pun, punya jalur sendiri):** Pola A audit (pindah `.assets/uploads/` + `FCPATH/uploads/` ke `private_uploads/` — 3 lokasi, satu helper terpusat) dan sisa `PRD_VERIFIKASI_ADMIN_SRP2.md` Fase 2-3 (satukan pembuatan draft SRP2, upload onboarding). Keduanya tetap tercatat di dokumen masing-masing.

---

## 5. Kontrak untuk Agent Berikutnya — cara menambah modul dashboard

> **Versi resmi checklist ini sekarang ada di `AGENTS.md` §17** (8 langkah + peringatan "jangan tampilkan data karangan"), sudah diperbarui mengikuti hasil Fase 1–4. Daftar di bawah adalah versi rencana awal (7 langkah) — dipertahankan sebagai jejak, tapi **kalau berbeda, ikuti AGENTS.md §17**.

Checklist versi rencana awal:

1. Tentukan reviewer & scope pakai checklist "Prinsip Umum untuk Role Baru" di `PRD_VERIFIKASI_ADMIN_SRP2.md` — SEBELUM menulis kode.
2. Buat/pakai controller yang extend base class sesuai role penegak. Guard di constructor, bukan di method.
3. Endpoint tulis: POST-only, whitelist field eksplisit, WHERE ganda scope bila ter-scope, cek `affected_rows()`, isi `reviewed_by`/`reviewed_at`.
4. Lampiran: `private_uploads/{fitur}/{id}/` + endpoint baca ber-guard. Tidak ada pengecualian.
5. View: shell existing lewat render helper; komponen `admin/components/status_badge.php` + `review_form.php` + `components/portal_data_table.php`; token `brand-*` + ikon `ph-*` (Font Awesome TIDAK ada di shell admin); skeleton §14.
6. TERAKHIR: satu entri di `dashboard_modules.php`. Menu tampil tapi akses ditolak = registry salah, perbaiki registry, JANGAN longgarkan guard.
7. FK baru ke `usr_users.id` = `INT` signed, bukan `UNSIGNED` (lihat catatan migrasi `20260701000011/12`).

---

## 6. Status Eksekusi

| Fase | Status |
|---|---|
| 0 — Registry + sidebar | ✅ **Selesai 26 Jul 2026** — registry `dashboard_modules.php` + `MY_Controller::dashboard_menu()`, sidebar registry-driven, 3 render helper dilebur (B6), badge per-role dari registry (B4), link profil topbar per-role (B3), flash dobel dihapus (B5). Diverifikasi 6 role via curl + browser. |
| 1 — Modul Verifikasi SRP2 | ✅ **Selesai 26 Jul 2026** — migrasi `20260701000014` (link `certified_developer_id`), `Admin_Srp2::pending()/detail()/proses()/lihat_dokumen()`, komponen bersama `status_badge`/`review_form` lahir di sini, helper `srp2_helper.php` (daftar 14 dokumen jadi satu sumber), modul terdaftar di registry + badge `count_pending_srp2`. Bonus: perbaikan CSRF hilang di form Direktori SRP2 (lihat catatan di bawah). 4 kriteria PRD diverifikasi via curl end-to-end. |
| 2 — Retrofit reviewer | ✅ **Selesai 26 Jul 2026** — `reviewed_by`/`reviewed_at` kini diisi 4 jalur reviewer (`Admin_Kabkota`, `Admin_Bidang`, `Admin_Kemitraan`, `Admin` superadmin); false-negative `affected_rows()` diperbaiki di 2 controller scoped (#6); B1 tuntas — `pages/admin/dashboard.php` (fork rusak) & `admin_kabkota/dashboard.php` diarsipkan, diganti view bersama `admin/antrean/dashboard.php`; `admin_bidang` + `kemitraan` pindah ke komponen `status_badge`/`review_form`. Bonus: pesan sukses palsu SIMPERUM dijujurkan (lihat catatan). Diverifikasi via curl: reviewed_by terisi per role, resubmit-sama tidak lagi salah pesan, anti-IDOR lintas scope tetap menolak. |
| 3 — Integritas hulu | ✅ **Selesai 26 Jul 2026** — 🔴 `kabupaten_id` tidak lagi dipercaya mentah dari `$_POST`: `Program_model::resolve_kabupaten_id()` (profil user login menang → kalau tidak ada, validasi pilihan form ke tabel `kabupaten` → kalau tidak valid, NULL). `Program::ajukan_solusi()` kini mengisi `kabupaten_id` (dulu selalu NULL). `insert_housing_queue()` mencatat log error kalau pemanggil lupa key-nya. Baris NULL lama ditandai eksplisit "Belum Terpetakan Wilayah" (kartu + badge per baris) di dashboard superadmin. Diverifikasi: nilai ngawur/0 → NULL, valid → tersimpan, profil menang atas form. |
| 4 — Kualitas & skala | ✅ **Selesai 26 Jul 2026** — B2: overview dibersihkan dari angka hardcode/chart dummy/feed fiktif, diganti kartu per domain dari registry + tren 7 hari nyata + pengajuan terbaru lintas domain. B7: paginasi server-side (`paginate_state()` + komponen `pagination.php`) di `Admin_Users`/`Admin_Kemitraan`/`Admin_Bidang`/`Admin_Aduan`. B9: `User_Profile` dilebur ke `akun/profil` (redirect stub, view diarsipkan), ganti password ikut pindah dengan kebijakan password `Auth`. Modul BARU `Admin_Aduan` (read-only lintas bidang, menutup AUDIT #5) + peringatan bidang tanpa admin. `catatan_admin` aduan kini tampil ke pelapor (AUDIT #7). Mekanisme badge disatukan ke `pending_where` di registry. |

| **B8 — satukan paradigma tabel admin** | ✅ **Selesai 26 Jul 2026** (di luar Fase 0–4, dikerjakan atas permintaan setelah verifikasi anchor) — semua tabel admin kini SATU pola: cari + filter + urut + paginasi **server-side**. Tabel antrean tidak lagi mengirim s.d. 1000 baris JSON ke browser; Alpine tersisa hanya untuk modal keputusan. Fondasi baru: `MY_Controller::table_state()` (whitelist kolom sort), `antrean_table_data()`, helper `admin_table_helper.php`, komponen `table_toolbar.php`. Kode mati dihapus: `Admin_model::get_all_housing_queue()`. |

Update tabel ini setiap fase selesai (dengan tanggal + ringkasan satu baris), supaya dokumen anchor tetap mencerminkan kenyataan — dokumen anchor yang basi lebih berbahaya daripada tidak ada.

### Keputusan & temuan B8

- **Keputusan: server-side untuk semuanya.** Trade-off yang diterima sadar: pencarian jadi submit (reload), bukan instan per-ketikan. Alasan: hanya pola ini yang skalanya tidak terbatas, cocok dengan CI3 tanpa build step, dan sejalan dengan paginasi server-side. Kalau suatu saat pencarian instan diinginkan lagi, jalurnya AJAX ke endpoint yang sama — **bukan** kembali mengirim semua baris ke browser.
- **Bug nyata yang ditemukan & diperbaiki:** komponen `pagination.php` versi Fase 4 membangun `?page=N` polos, sehingga **filter bidang di `Admin_Aduan` hilang begitu pindah halaman**. Semua link tabel sekarang lewat `admin_table_url()` yang mempertahankan parameter lain; mengganti filter/urutan otomatis balik ke halaman 1.
- **Jebakan CI3 yang sempat memecahkan 3 halaman:** `count_all_results($tabel, FALSE)` menyetel FROM **dan** mempertahankan state, jadi `->get($tabel)` sesudahnya menghasilkan `FROM x, x` (error 1066). Pola benar: `->from($tabel)` di depan → `count_all_results('', FALSE)` → `->get()` tanpa argumen.
- **Keamanan yang diverifikasi langsung:** (a) kolom sort di-whitelist — `?sort=aduan.id`, `?sort=(SELECT 1)`, `?sort=aduan.nama;--` semuanya jatuh ke default, tidak ada yang masuk ORDER BY; (b) pencarian **tidak menembus scope** — admin bidang perumahan mencari kata milik aduan bidang pertanahan mendapat 0 baris, sementara superadmin menemukannya (sesuai kewenangan).
- **Sisa yang memang dibiarkan:** `views/components/portal_data_table.php` tetap dipakai portal publik (`pages/pengembang/sertifikasi.php`) dan tabel Alpine di `pages/data_spasial/sebaran.php` — keduanya konteks portal publik dengan token warna `--portal-*`, bukan shell admin. Memaksa mereka ke pola admin berarti mencampur dua sistem token; **B8 memang cuma menyatukan sisi admin.**

### Catatan temuan saat eksekusi (bukan bagian rencana awal)

- **Penyimpangan dari rencana yang perlu diketahui** (rencana §4 di atas sengaja TIDAK diedit supaya jejaknya jelas — ini daftar bedanya dengan hasil nyata):
  - Fase 1 merencanakan `terima()`/`tolak()` terpisah → diimplementasikan sebagai satu `proses($id)` (alasan di bawah).
  - Fase 1 merencanakan reuse `views/components/portal_data_table.php` untuk tabel modul baru → **tidak dilakukan**. `admin/srp2/pending.php` dan `admin/aduan/index.php` memakai tabel server-rendered polos + komponen `pagination.php` baru. Alasan: `portal_data_table` adalah komponen **portal publik** (token `--portal-*`, sorting/paginasi client-side atas seluruh baris) — memakainya di shell admin berarti mencampur dua sistem token warna DAN bertentangan dengan paginasi server-side yang justru jadi target B7. Konsekuensinya jumlah paradigma tabel belum berkurang seperti dibayangkan B8; penyatuan penuh butuh keputusan tersendiri soal komponen tabel admin.
  - Fase 4 B9 merencanakan "hapus controller `User_Profile`" → controller **dipertahankan sebagai redirect stub** ke `akun/profil`, mengikuti konvensi repo untuk bookmark lama (sama seperti `Pengembang::daftar()`/`formulir()`). Hanya viewnya yang diarsipkan.
  - Fase 0 B3 merencanakan link profil topbar bercabang per role (`admin` → `User_Profile`) → setelah peleburan B9 di Fase 4, cabang itu dihapus; topbar sekarang selalu ke `akun/profil` untuk semua role.
- **Kode mati yang ikut dibersihkan:** `Admin_model::count_pending_queue()`/`count_pending_srp2()` dihapus setelah badge disatukan ke `pending_where` — ditemukan lewat verifikasi ulang anchor, bukan saat eksekusi fase.
- **B10 (BARU, ditemukan & diperbaiki di Fase 1):** ketiga form di `admin/srp2/index.php` (tambah pengembang, edit baris, hapus) **tidak menyertakan token CSRF sama sekali** padahal `csrf_protection` aktif global — artinya seluruh fitur kelola Direktori SRP2 **sudah rusak di produksi** (setiap submit dibalas 403 Forbidden), tidak terdeteksi audit manapun karena audit membaca kode controller, bukan mencoba submit form. Diverifikasi dengan submit nyata (403 sebelum, 303 sesudah). Pelajaran untuk audit berikutnya: cek keberadaan token CSRF di **view**, bukan cuma konfigurasi global.
- **Penyimpangan sadar dari draft PRD:** PRD menulis dua endpoint terpisah `terima()`/`tolak()`. Implementasi memakai satu `proses($id)` dengan field `status` — mengikuti pola `Admin_Kemitraan::proses()` yang sudah terbukti, supaya komponen `review_form.php` benar-benar dipakai ulang tanpa modifikasi bentuk endpoint. Semua aturan PRD (approve auto-link + idempotent, tolak wajib catatan di server, dokumen lewat endpoint ber-guard) tetap dipenuhi.
- **B11 (BARU, ditemukan & diperbaiki di Fase 2):** `Admin::update_status()` menampilkan pesan sukses *"telah disinkronisasi dengan API SIMPERUM"* berdasarkan `$mock_api_status = true` yang di-hardcode — kode cURL aslinya cuma komentar, tidak ada request apa pun yang pernah dikirim. Ini beda kelas dari "fitur belum ada" (mis. sertifikat PDF SRP2 yang sudah jujur ditandai nonaktif): ini **aktif memberi tahu admin bahwa data sudah konsisten di sistem eksternal pemerintah padahal tidak**. Pesan dijujurkan. Jangan kembalikan klaim sinkronisasi sampai integrasinya nyata.
- **Asumsi produk yang perlu dikonfirmasi (Fase 3):** untuk pemohon yang sudah login DAN profilnya punya `kabupaten_id`, domisili profil sengaja MENANG atas pilihan dropdown di form. Ini menutup pemalsuan wilayah, tapi kalau kebijakan sebenarnya "warga boleh mengajukan ke kabupaten lain", ubah di `Program_model::resolve_kabupaten_id()` — satu tempat, bukan tersebar. Saat ini praktis belum berdampak karena `usr_users.kabupaten_id` baru terisi untuk akun staf `admin_kabkota`, bukan warga.
- **SIMPERUM tetap belum ada (dikonfirmasi user 26 Jul 2026)** — integrasinya menyusul. Sampai itu terjadi, jangan ada pesan/UI yang menyiratkan data sudah tersinkronisasi.
- **Batas jujur soal komponen badge:** tabel antrean (`admin/antrean/dashboard.php`) merender badge lewat Alpine di sisi klien (`renderBadge()` JS), jadi **tidak** memakai `admin/components/status_badge.php` yang PHP. Menyatukan keduanya butuh konversi tabel itu ke server-rendered — itu pekerjaan Fase 4 (B7/B8), bukan Fase 2. Yang tercapai di Fase 2: dari dua salinan JS `renderBadge` jadi satu.
- **`Pengembang::dokumen_persyaratan()` sekarang delegasi ke `application/helpers/srp2_helper.php`** — sebelumnya array 14 dokumen ditulis inline di controller; kalau halaman admin menyalinnya, dua daftar bisa berbeda diam-diam. Satu sumber kebenaran, key harus cocok dengan `srp2_documents.document_key`.
