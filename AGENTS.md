# AGENTS.md — Panduan & Pusat Sinkronisasi untuk AI Coding Agent

> Peta navigasi teknis DAN titik temu antar agent (Claude Code, Cursor, Copilot, dll) yang bekerja di repo ini.
> Isi ditulis dari pembacaan kode langsung, bukan asumsi dokumen lama. Tiap klaim penting mencantumkan tanggal verifikasi.
> Spesifikasi produk/bisnis: [`docs/README.md`](docs/README.md). [`README.md`](README.md) di root = panduan setup untuk manusia — ✅ disegarkan 27 Jul 2026 (dulu ditandai usang; langkah `php index.php migrate` yang hilang sudah ditambahkan, jumlah controller & tabel dikoreksi).

---

## 0. BACA INI DULU — Status Terkini & Protokol Antar-Agent

**Terakhir disinkronkan: 27 Juli 2026** (setelah roadmap pengembang T0–T4 mendarat — lihat §0b). Kalau kamu agent yang baru masuk, baca bagian ini sampai habis sebelum menyentuh apa pun.

### 0a. Keadaan lingkungan saat ini

> 🔄 **PETA LINGKUNGAN BERUBAH 27 Jul 2026 — keputusan user.** Situs staging kini **menjadi production**: "kita produksi menggunakan staging saat ini, nanti jika kita butuhkan `main` kita bisa menghidupkannya kembali." Tiga situs lain dimatikan (403) — berkas & DB utuh, tinggal hapus blok `SITUS DIMATIKAN` di `.htaccess` server untuk menghidupkan lagi.

| | Situs | Branch | Status |
|---|---|---|---|
| **Lokal** | `localhost/klinik_new` | `feature/homepage-portal-v2` | skema `20260701000016` |
| **PRODUCTION (aktif)** | `floralwhite-lion-710022` | `feature/homepage-portal-v2` — auto-deploy | KODE + DB skema `20260701000016` (T0–T5 lengkap), ter-push & termigrasi 27 Jul 2026 |
| ~~production lama~~ | `palegreen-mink-703421` | `main` — beku sejak 19 Jul | 🔴 **DIMATIKAN** |
| ~~staging lama~~ | `darkseagreen-hamster-214338` | `feature/ui-ux-revamp` | 🔴 **DIMATIKAN** |
| ~~instalasi mati~~ | `darkgreen-cattle-889861` | tanpa git, Mei | 🔴 **DIMATIKAN** |

> ⚠️ **Konsekuensi yang gampang terlewat:** push ke `feature/homepage-portal-v2` sekarang **langsung merilis ke PRODUCTION**, bukan lagi ke staging yang aman. Tidak ada lagi lingkungan uji terpisah — `darkseagreen` yang dulu berbagi DB dengan situs ini sudah dimatikan. Uji di lokal dulu.
>
> Nama DB-nya tetap `u504551489_klinikstg` (menyesatkan, tapi jangan diganti tanpa alasan kuat — mengganti nama DB produksi berjalan lebih berisiko daripada namanya yang keliru).
>
> ✅ **T0–T5 roadmap pengembang SUDAH di-push DAN termigrasi di production, 27 Jul 2026** (`26a3a1a`.. lewat SSH, atas perintah eksplisit user). Diverifikasi lewat `Migrate::status()` (diagnostik baca-saja, lihat `Migrate.php`) SEBELUM dan SESUDAH migrasi — production ternyata SUDAH punya tabel `migrations` di versi `...14` (paragraf "tabel migrations tidak ada sama sekali" di bawah adalah tentang production LAMA yang beku (`palegreen`/`main`), ditulis SEBELUM keputusan staging-jadi-production, BUKAN tentang `floralwhite-lion` — sumber salah baca yang sempat membuat dokumen ini kontradiksi diri sendiri). Migrasi 15 & 16 berjalan sukses (`Migrasi sukses, versi skema sekarang: 20260701000016`), dan tiga rute diuji langsung lewat HTTP (`Auth/login`, `Pengembang/sertifikasi`, `Auth/forgot_password`) — ketiganya 200.
>
> `main` + `palegreen` kalau dihidupkan lagi nanti: DB-nya **belum pernah dimigrasi sama sekali** (tabel `migrations` tidak ada, cuma 14 tabel baseline), jadi butuh migrasi **01–16 seluruhnya**.

> ⚠️ **Dikoreksi lewat SSH 27 Jul 2026 — dokumen ini dulu keliru.** Ini tentang `palegreen-mink`/`main` (production LAMA yang sekarang beku), BUKAN `floralwhite-lion` yang aktif sekarang — dua paragraf ini sempat tertulis seolah tentang production yang sama dan membuat dokumen ini kontradiksi diri sendiri sampai diverifikasi ulang lewat `Migrate::status()` di paragraf atas. `palegreen`/`main` BUKAN "tertinggal di `20260701000010`": **tabel `migrations` tidak ada sama sekali**, dan isinya cuma 14 tabel baseline dari `schema_klinikpkp.sql`. Tidak ada `srp2_*`, `aduan`, `kabupaten`, `bidang`, `kkn_magang_pendaftaran`, `sys_rate_limits`, tanpa kolom reviewer, tanpa FK.
>
> **Konsekuensi untuk rilis:** saat `main` akhirnya dibuka, `palegreen` butuh migrasi **01–16 seluruhnya**, bukan cuma 11–16. Sebagian rute SRP2 di `palegreen` hari ini memang 500 (`Unable to load the requested file: pages/pengembang/sertifikasi.php`) karena kode lama + skema baseline.

**Ada EMPAT instalasi Klinik PKP di akun Hostinger `u504551489`, bukan dua** (ditemukan 27 Jul 2026):

| Situs | DB | Status |
|---|---|---|
| `palegreen-mink-703421` | `u504551489_klinikpkp` | **production** |
| `floralwhite-lion-710022` | `u504551489_klinikstg` | **staging** |
| `darkseagreen-hamster-214338` | `u504551489_klinikstg` | staging LAMA — masih hidup, **berbagi DB dengan staging** |
| `darkgreen-cattle-889861` | `u504551489_kliknikpkp` | instalasi keempat (perhatikan nama DB salah ketik) |

Tiga yang pertama sempat menyajikan `.env` publik. Kalau menyentuh staging, ingat dua situs menunjuk DB yang sama — perubahan di satu terlihat di keduanya.

> 🚫 **`main` tidak boleh disentuh tanpa perintah eksplisit user.** Detail & urutan rilis yang benar ada di §1. Staging bebas — push branch fitur otomatis merilis ke sana.
>
> **Ditegaskan user 26 Jul 2026:** belum ada rencana rilis sama sekali — proyek ini masih panjang. Jangan menawarkan merge ke `main`, jangan menyiapkan PR ke `main`, jangan menjalankan migrasi production. Anggap `main` beku sampai user sendiri yang membukanya.

### 0b. Yang baru saja mendarat (jangan dikerjakan ulang)

Semua sudah selesai + terverifikasi lewat HTTP nyata, bukan hanya dibaca kodenya:
- **Audit 5 role** → `docs/engineering/AUDIT_*.md` + [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) (baca ringkasannya dulu)
- **Normalisasi skema** (FK + kolom reviewer) → migrasi `20260701000011`–`14`, lihat §16
- **Dashboard terpadu** Fase 0–4 + B8 → [`ANCHOR_DASHBOARD_TERPADU.md`](docs/architecture/ANCHOR_DASHBOARD_TERPADU.md), lihat §17
- **PRD verifikasi admin SRP2** Fase 0–4 → [`PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md)
- **Semua unggahan pindah ke penyimpanan privat** → lihat peringatan di §17 checklist poin 4
- **Fitur "Minta Perbaikan" SRP2** (27 Jul 2026, `5db1ba4`) — admin bisa mengembalikan pengajuan ke pemohon dengan catatan wajib, tanpa mencap "Ditolak". Diuji di browser sungguhan; detail & aturan turunannya di §18
- **Roadmap pengembang↔admin T0–T4 SELESAI** (27 Jul 2026, `9a0488c`..`bc0f6af`) — perjalanan SRP2 kini bisa ditempuh utuh dari daftar sampai tampil di direktori publik. Ringkas per tahap, detail di masing-masing commit message:
  - **T0** `9a0488c` — cap "Terverifikasi SRP2" di halaman publik disaring status (dulu nama perusahaan mana pun lolos), flash "berhasil diunduh (Simulasi)" dicabut, `do_register` kena rate limit. Penghapusan blok kredensial demo di tahap ini **sengaja dibalik lagi** oleh `d9c28e8` atas permintaan user — lihat §17 poin 14.
  - **T1a** `f74047f` — gerbang hulu (`kirim_pengajuan`, `save_onboarding`, `update_pengembang_profile`) + `Admin_Srp2::proses()` bertransaksi, transisi status ditegakkan server, `WHERE` menyertakan status asal.
  - **T1b** `22e176a` — antrean admin bisa menampilkan status non-default, layar keputusan merender data pembanding, `save()` berhenti mengarang sukses, `catatan_admin` tidak lagi di-NULL-kan saat Diterima, Ganti→Batal di wizard tidak lagi mengunci tombol Kirim.
  - **T2** `195ec64` — semua layar pemohon (`/pengembang/syarat`, `/akun`, `/akun/profil`, halaman dokumen) memakai `Auth_model::srp2_state()`; kirim ulang membersihkan catatan/reviewer; draft dobel ditutup di `ensure_srp2_draft()`.
  - **T3** `de7e7f2` — dua jalur unggah dijadikan satu (view `pengembang/dokumen` diarsipkan, `mulai_unggah()`/`dokumen()` redirect ke wizard), ganti lampiran meng-`unlink()` berkas lama, `$id` dinormalisasi sekali di pintu masuk, `_cleanup_owned_files()` menyapu berdasarkan isi DISK, dan `lihat_dokumen_saya()` memberi pemohon cara melihat kembali berkasnya. Pembersih sekali-jalan sudah dijalankan di lokal: 6 yatim, 5,5 MB — **belum dijalankan di production**.
  - **T4** `bc0f6af` — migrasi backfill `20260701000016`, tautan profil publik lewat FK bukan pencocokan nama, satu `upsert_direktori_publik()` dipanggil dari approve maupun update profil, direktori admin pakai pola server-side B8.
  - **T5** (27 Jul 2026) — akun punya jalan pulang dan bukti kepemilikan. Ganti password (`Pengaturan::update_profile()`) dan hapus akun (`Pengaturan::delete_account()`) sekarang WAJIB `current_password` diverifikasi server lewat `password_verify()` — sebelumnya siapa pun yang sempat memakai sesi bisa mengunci pemilik asli keluar atau menghapus SELURUH pengajuan SRP2-nya lewat FK CASCADE. Konfirmasi ketik-nama di modal hapus akun diperbaiki: fallback berantai `username ?? name ?? email` (email selalu ada, `NOT NULL UNIQUE` di skema) — sebelumnya akun dengan username DAN name NULL sekaligus (3 akun legacy, id 14/15/16) tidak mungkin mengonfirmasi hapus akunnya sendiri karena JS membandingkan input dengan literal `null`. Daftar cepat SRP2 (`Auth::do_register()`) tidak lagi menyisakan `name`/`username` NULL — diturunkan dari email + nama perusahaan yang MEMANG sudah diisi (`Auth_model::generate_unique_username()`), bukan dikarang; pemohon tetap bebas menggantinya di `/akun/profil`. XSS di `Auth::google_callback()` — `base_url($redirect_to)` yang dulu di-echo mentah ke string JS berkutip tunggal (sanitize_redirect() menolak URL eksternal tapi tidak membuang kutip satu) sekarang di-`json_encode()` lewat helper `_oauth_close_popup()`, plus CSP `script-src 'nonce-...'` scoped ke satu endpoint itu saja (bukan global). Pesan wizard SRP2 untuk akun yang baru dipromosikan admin (`Admin_Users::update_role()` tidak menyegarkan sesi) diperbaiki: menyarankan keluar-masuk lagi dulu, bukan langsung menyuruh daftar akun baru.
    > **Dua keputusan sadar user 27 Jul 2026, JANGAN "diperbaiki" tanpa perintah baru:** (1) Lupa password: form palsu `action="#"` DIHAPUS (bukan dibangun jadi email asli) — repo ini nol infrastruktur SMTP/email library, dan user memilih tidak membangunnya sekarang. Halaman `Auth/forgot_password` kini statis, jujur "hubungi admin". (2) reCAPTCHA di login/register: verifikasinya tetap dilewati total kalau kunci kosong (`Auth.php`) — user memilih membiarkannya apa adanya, bukan mengisi kunci atau menghapus widget-nya. `do_verify_email()` (dummy, `email_verified_at` tanpa token) TIDAK disentuh — nol gating di kode mana pun yang membaca kolom itu, jadi murni kosmetik, sama seperti `Auth::verify_email($token)` yang juga tidak pernah dipakai (tidak ada yang mengirim link-nya).
    > **Verifikasi:** (b)/(c)/(d)/(e) diuji end-to-end via curl + query DB langsung (akun uji dibuat & dihapus lewat jalur nyata, dibersihkan sesudahnya) dan lewat browser sungguhan (field + modal + `checkDeleteConfirm()` dicek via DOM). XSS google_callback **tidak** bisa diuji end-to-end (butuh kode OAuth Google asli yang berhasil, tidak bisa disimulasikan dari CLI) — json_encode dibuktikan lewat skrip PHP terisolasi yang membandingkan payload lolos vs tertahan.
  - **T6 — ROADMAP PENGEMBANG SELESAI SEPENUHNYA** (27 Jul 2026). Panen: [`docs/engineering/uji_perjalanan_srp2.php`](docs/engineering/uji_perjalanan_srp2.php) — skrip PHP CLI + curl (bukan framework, repo ini tidak punya `tests/`) yang menempuh perjalanan penuh (daftar → 14 dokumen → kirim → approve → direktori publik → profil publik benar) plus 3 uji negatif (transisi ilegal, gerbang hulu, transaksi tabel-kedua-gagal). **Definisi selesainya BUKAN skrip hijau sekali** — dibuktikan dengan sengaja membalikkan transaksi T1a di `Admin_Srp2::proses()` (plus memaksa `db_debug=FALSE` menirukan production, sama seperti pola T1a) dan skrip itu MERAH persis di titik yang diramalkan, lalu HIJAU lagi setelah dikembalikan (nol diff sesudahnya, diverifikasi `git diff`). Dijalankan di DB migrasi bersih terpisah (`klinikpkp_uji_t6`, baseline `schema_klinikpkp.sql` + `migrate` sampai 16), bukan DB dev yang sudah dipakai berulang.
    > **Perbaikan tersebar yang ikut selesai:** penguncian akun kini reset penghitung kalau lockout sebelumnya sudah lewat waktu (`Auth_model::increment_login_attempts()`); respons `do_register()` menyertakan `name`; judul baris SRP2 kosong di `/akun` kini placeholder jujur (dipicu nyata oleh `Admin_Users::update_role()` yang bisa mempromosikan ke `pengembang` tanpa nama perusahaan); `v_sertifikasi.php` + `Pengembang::result()` yatim dihapus (nol pemanggil, diverifikasi grep); `admin/srp2/detail.php` mengeraskan validasi skema URL sendiri (bukan bug — Stored XSS di sana sudah DIBANTAH, ini murni pengerasan lepas dari `global_xss_filtering` yang DEPRECATED); `dashboard_modules.php` dapat `status_column`+`owner_column` eksplisit di 6 entri (cetakan untuk role berikutnya).
    > **Dokumentasi:** AGENTS.md §17 dapat 3 pertanyaan baru + catatan "checklist 8 langkah cuma soal jalur tulis + layar admin"; PRD pertanyaan 4 diperluas (dua jalur MENULIS, bukan cuma MEMBUAT); [`SISA_PEKERJAAN_ROLE_PENGEMBANG.md`](docs/engineering/SISA_PEKERJAAN_ROLE_PENGEMBANG.md) diperbarui status akhir PER dari 24 temuan (21 selesai, 1 dibantah, 2 keputusan sadar user, 1 sengaja tidak dikerjakan).

### 0c. Yang masih terbuka

> ✅ **Paparan `.env` SUDAH DITUTUP di ketiga situs (27 Jul 2026, lewat SSH).** Blok penolak dotfile + `docs/` dipasang langsung di `.htaccess` server (cadangan `.htaccess.bak-20260726-202512` ada di tiap situs). Diverifikasi dari luar: `.env`, `AGENTS.md`, `docs/` → **403**; halaman depan & aset → **200**; `.well-known` → 404 (lolos, jadi perpanjangan SSL aman). `CI_ENV=production` juga dipasang — **lewat `SetEnv` di `.htaccess`, BUKAN di `.env`**, lihat jebakan di §0e. Dibuktikan dengan berkas uji sesaat: `CI_ENV=[production] display_errors=[]`.
>
> 🔥 **YANG MASIH TERBUKA — akun admin production bisa diambil alih SEKARANG.** Diverifikasi 27 Jul 2026 dengan `password_verify()` langsung ke hash di DB production: sandi `password` **COCOK** untuk `admin@klinikpkp.jatengprov.go.id` (id 9, role admin), `warga@example.com` (id 10), dan `dev1@example.com` (id 11) — dan halaman login production **masih memajang kredensial itu** karena `main` beku sehingga penghapusan blok demo belum ter-deploy. Hanya 3 dari 7 akun yang diiklankan benar-benar ada di production.
>
> Tindakan yang tersisa untuk user, bukan agent: (a) **ganti sandi `admin@klinikpkp.jatengprov.go.id`** — lewat alur aplikasi sendiri, bukan SQL, supaya tidak salah kunci diri sendiri; (b) nonaktifkan/hapus `warga@example.com` dan `dev1@example.com`; (c) **rotasi** password DB, Google client secret, dan Gemini API key — semuanya sudah terlanjur terbaca publik selama `.env` terbuka. ⚠️ **`KPKP_DATA_KEY` JANGAN dirotasi begitu saja** — NIK & alamat terenkripsi akan hilang permanen (§6).
>
> Catatan: `dev1@example.com` di production masih menyimpan role rusak `pages/pengembang/pengembang` (bug copy-paste lama). Kodenya sudah diperbaiki, datanya belum.

1. Pengujian manual staging oleh user (semua verifikasi sejauh ini lewat curl)
2. Migrasi DB production — **setelah** merge, bukan sebelum
3. ~~Isi `PRIVATE_UPLOADS_PATH` di `.env` server~~ — ✅ **SELESAI 27 Jul 2026 lewat SSH.** Diisi eksplisit di ketiga instalasi (production aktif + dua yang dimatikan, supaya tidak jadi jebakan kalau dihidupkan lagi), jalur absolut sejajar `public_html`, izin `700`, cadangan `.env.bak-*` tersimpan. Diverifikasi dengan memanggil `private_uploads_root()` yang ASLI lewat HTTP: root terbaca di luar DocumentRoot, `/private_uploads/` dan turunannya 404, aplikasi tetap sehat.
   > **Kenapa eksplisit, padahal fallback-nya kebetulan benar:** fallback `dirname(FCPATH)/private_uploads` bergantung pada tata letak hosting. Asumsi yang sama pernah SALAH di XAMPP lokal — folder induk aplikasi ternyata sama dengan DocumentRoot, dan dokumen SRP2 bisa diunduh tanpa login. Aman-karena-kebetulan bukan aman.
4. Di luar kita: integrasi SIMPERUM (belum ada), generator sertifikat PDF
5. **Temuan pembacaan 26 Jul 2026 yang belum diperbaiki** — daftar lengkap 141 temuan di [`PEMBACAAN_CODEBASE_26JUL2026.md`](docs/engineering/PEMBACAAN_CODEBASE_26JUL2026.md), ringkasan yang paling berdampak di §18.
6. ✅ **Penuntasan role `pengembang` ↔ admin — SELESAI SEPENUHNYA (27 Jul 2026), T0–T6.** 24 sisa pekerjaan ([`SISA_PEKERJAAN_ROLE_PENGEMBANG.md`](docs/engineering/SISA_PEKERJAAN_ROLE_PENGEMBANG.md), status akhir per-temuan sudah dicatat di sana) dituntaskan lewat 8 tahap di [`ROADMAP_PENGEMBANG_ADMIN.md`](docs/product/ROADMAP_PENGEMBANG_ADMIN.md) — ringkas per tahap di §0b. Perjalanan SRP2 kini bisa ditempuh utuh dan dibuktikan lewat skrip yang benar-benar gagal kalau perbaikannya dibalik ([`uji_perjalanan_srp2.php`](docs/engineering/uji_perjalanan_srp2.php)), bukan cuma checklist. **Jangan kerjakan ulang temuan mana pun di sini** — kalau menemukan sesuatu yang kelihatan seperti salah satu dari 24 temuan itu, cek dulu status akhirnya di `SISA_PEKERJAAN_ROLE_PENGEMBANG.md`, kemungkinan besar sudah tercatat (selesai, dibantah, atau sengaja tidak dikerjakan).
   - **Kerja nyata berikutnya di area ini: role WARGA, MAHASISWA, ADMIN_KABKOTA, ADMIN_BIDANG** — pakai [§19 Metode Baku Backend Role ↔ Admin](#19-metode-baku-backend-role--admin) sebagai cetakan (ditulis persis untuk ini), dan `dashboard_modules.php` sudah punya `status_column`+`owner_column` per domain supaya tidak perlu ditebak ulang. §19 langkah 1 mewajibkan Kartu Domain diisi SEBELUM menulis kode.
   - Item non-kode yang masih murni tanggung jawab user, bukan agent: rotasi sandi `admin@klinikpkp.jatengprov.go.id` di production (§0c poin lain), dan keputusan kapan (kalau pernah) reCAPTCHA/lupa-password-email dibangun sungguhan.

✅ Sudah dibereskan 26 Jul 2026: lokasi berkas privat kini dari `PRIVATE_UPLOADS_PATH` di `.env` (helper `private_upload`, satu sumber untuk controller & model) — dan dokumen produk/engineering usang (`IMPLEMENTATION_ROADMAP`, `PRODUCT_REQUIREMENTS_DOCUMENT`, `DESAIN_STATUS_TIKET_PENGAJUAN`, `SRP2_ACCOUNT_FLOW`, `ROLE_DATA_RELATION_MAP`) — klaim yang sudah tidak benar dikoreksi, teks aslinya tetap dikutip sebagai jejak. Verifikasi `private_uploads` di production & staging: keduanya 404 CI, aman.

📖 **Pembacaan codebase menyeluruh (26 Jul 2026)** → [`PEMBACAAN_CODEBASE_26JUL2026.md`](docs/engineering/PEMBACAAN_CODEBASE_26JUL2026.md). Seluruh kode proyek dibaca per subsistem: **100% controller (29), model (9), library (4), helper (5), `core/MY_Controller`, dan 14 migrasi**. Hasilnya 141 temuan + peta tiap subsistem (isi, alur end-to-end, kondisi kesehatan). Dokumen itu adalah **peta paling akurat yang kita punya** — kalau kamu butuh memahami subsistem yang belum pernah kamu sentuh, baca bagiannya di sana sebelum menjelajah kode sendiri. Sebagian temuannya juga MENGOREKSI dokumen ini; koreksinya sudah diterapkan ke §3–§17, ringkasan dampaknya di §18.

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
| `CI_ENV` ditaruh di `.env` | Tidak berpengaruh **sama sekali**: `index.php:56` mendefinisikan `ENVIRONMENT` dari `$_SERVER['CI_ENV']`, sedangkan `.env` baru diurai di baris 310 — terlambat 250 baris. Harus lewat `SetEnv CI_ENV production` di `.htaccess`. Percobaan pertama 27 Jul 2026 jatuh ke jebakan ini, ketahuan hanya karena dibuktikan dengan berkas uji |
| Rewrite CI dikira melindungi berkas | `RewriteCond !-f` cuma menangani URL yang TIDAK ada wujudnya — setiap berkas nyata (`.env`, `docs/`) tersaji apa adanya (§0c) |
| Memblokir semua dotfile di `.htaccess` | Ikut memblokir `.well-known` → perpanjangan sertifikat SSL gagal, situs mati saat kedaluwarsa. Pakai negative lookahead |
| Nama tabel ditebak dari nama fitur | `check_forum_rate_limit('diskusi')` — tabelnya `forum_diskusi`; chat menulis ke `tb_chat` yang tidak ada di skema (§18) |
| Program di `Smart_filter` dikira ada di DB | `omah_sekeng` (`sf_programs` id 6) hanya ada karena INSERT manual di DB lokal — tidak ada migrasinya, jadi lingkungan lain bisa gagal FK (§18) |
| DB lokal jalan dikira bukti kode benar | Baris `omah_sekeng` ditambal manual 30 Jun; lokal mulus, instalasi fresh gagal. Tanya selalu: ini lahir dari migrasi atau dari tangan orang? |
| `load->model('Auth_model')` lalu dipanggil `$this->auth_model` | CI mendaftarkan properti PERSIS seperti penulisan saat load — beda huruf besar/kecil = `Undefined property`, halaman 500. Kena 27 Jul 2026 di T2 (`/akun` mati), ketahuan cuma karena responsnya 1191 byte; `php -l` tidak menangkapnya |
| MySQL XAMPP tidak mau naik | `multi-master.info` di `mysql/data` bisa terisi potongan teks log error, lalu MariaDB mencoba menyalakan replikasi palsu dan gagal. Proyek ini tidak pakai replikasi — singkirkan berkas `multi-master.info` + `master-*.info` + `mysql-relay-bin-*`, jangan sentuh `.frm`/`.ibd`/`ibdata1`. Sudah 2x terjadi (23 & 26 Jul 2026) |

### 0f. Protokol pemeliharaan dokumen ini

**Permintaan user 26 Jul 2026: AGENTS.md diperbarui berkala supaya konteks tidak hilang antar sesi.** Ini bukan formalitas — konteks yang hilang berarti agent berikutnya mengulang pekerjaan yang sudah selesai, atau lebih buruk, mempercayai klaim yang sudah tidak benar.

**Kapan wajib memperbarui:**
1. Setiap pekerjaan besar mendarat → perbarui §0b dan §0c.
2. Setiap kali menemukan klaim di dokumen ini yang ternyata salah → **perbaiki saat itu juga**, jangan ditunda. Ini yang paling sering terlewat: 12 klaim usang menumpuk sampai pembacaan 26 Jul 2026 (§18).
3. Setiap kali sebuah jebakan memakan korban → satu baris di tabel §0e. Tabel itu satu-satunya hal yang mencegah kesalahan yang sama terulang.
4. Setiap kali keputusan diambil user (mis. `main` dibekukan) → catat beserta tanggalnya, karena keputusan bisa berubah dan agent perlu tahu mana yang masih berlaku.

**Cara menulis yang menahan waktu:** cantumkan tanggal verifikasi pada klaim yang bisa basi. Bedakan "dibaca di kode" dari "diuji live" — pembacaan 26 Jul 2026 menemukan beberapa hal yang hanya ketahuan saat benar-benar dicoba (paparan `.env` salah satunya). Kalau sebuah klaim belum diverifikasi, tulis apa adanya "belum diverifikasi" alih-alih menghilangkan keraguannya.

**Yang TIDAK masuk sini:** detail temuan panjang (tempatnya `docs/engineering/`), spesifikasi produk (`docs/README.md`), dan apa pun yang bisa dibaca langsung dari kode dalam satu perintah. Dokumen ini peta dan titik temu, bukan salinan kode.

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
php index.php migrate   # WAJIB — schema .sql itu snapshot lama, migrasi 01..14 yang melengkapinya
```

> ⚠️ **`.env.example` tidak lengkap (26 Jul 2026):** template itu TIDAK memuat `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME`, `SITE_URL`, maupun `CI_ENV` — padahal ketiganya benar-benar dibaca kode (`database.php`, `config.php`, `index.php`). Setup baru yang cuma menyalin template akan diam-diam jatuh ke default `root`/`klinikpkp`/`localhost` dan berjalan dalam mode `development` (pesan error mendetail tampil ke publik). Isi manual ketiga kelompok itu.
>
> **Parser `.env` di sini buatan sendiri, bukan dotenv standar:** first-wins (nilai pertama menang, makanya blok aktif harus di ATAS blok yang dikomentari — lihat peringatan 3 blok `DB_PASS` di §1), tanda kutip TIDAK di-strip (`KEY="x"` menyimpan kutipnya), dan komentar inline tidak didukung (semua setelah `=` jadi nilai).

- Entry point: [`index.php`](index.php) (root, bukan `application/`)
- `.env` dibaca via `getenv()` di `application/config/*.php` (lihat `config.php` untuk `base_url`)
- **Jangan edit** folder `system/` (core CodeIgniter) atau `vendor/`

## 3. Struktur Direktori

```
application/
├── config/        # database.php, routes.php, autoload.php (autoload: email, session, database; helper: url, file, ternak)
├── controllers/    # 29 file, lihat tabel §4
├── core/           # MY_Controller.php — base class hierarchy, lihat §5
├── helpers/        # 5 file: forum, ternak, srp2, admin_table, private_upload
├── hooks/          # kosong (hooks dinonaktifkan di config.php)
├── libraries/      # Encryption_lib, Smart_filter, Sikaper_api, Ternak_api — lihat §6
├── migrations/     # 20260701000001..14 — SUMBER KEBENARAN skema, lihat §8
├── models/         # 9 file, lihat §7
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
| `Admin.php`, `Admin_Content.php`, `Admin_Dashboard.php`, `Admin_Settings.php`, `Admin_Users.php`, `Admin_Srp2.php`, `Admin_Kemitraan.php` | Panel superadmin (extends `Admin_Controller`) — lihat §16. ⚠️ `Admin_Settings` masih **mockup penuh**, lihat §18 |
| `Admin_Aduan.php` | Superadmin **read-only** lintas bidang untuk `aduan` (sengaja tanpa endpoint tulis; kelola tetap di `Admin_Bidang`). Menopang menu sidebar "Semua Aduan" (`aduan_semua` di registry) — sempat tidak tercatat di dokumen ini sampai 26 Jul 2026 |
| `Admin_Kabkota.php`, `Admin_Bidang.php` | Panel admin ter-scope (extends `Admin_Kabkota_Controller`/`Admin_Bidang_Controller`) — lihat §16 |
| `Migrate.php` | Runner migrasi (`php index.php migrate` → `latest()`), di-gate CLI/localhost saja |
| `KemitraanPortal.php` | Info + pendaftaran KKN/Magang (`daftar($jenis)`/`simpan()`, login-gated role `mahasiswa`) |
| `Bank_desain.php`, `Berita.php`, `Kemitraan.php`, `Kabupaten.php`, `User_Profile.php` | Fitur pendukung, ukuran kecil |

Controller besar (`Auth.php` ~26KB, `Index.php` ~20KB, `Umum.php` ~17KB) — kandidat untuk dipecah kalau melakukan refactor besar.

## 5. Base Controller Hierarchy (`application/core/MY_Controller.php`)

```
CI_Controller
└── MY_Controller               # security headers di SETIAP request (lihat catatan di bawah)
    ├── Public_Controller        # untuk route publik, tidak ada guard tambahan
    ├── Admin_Controller         # redirect ke Auth/login jika !is_logged || role !== 'admin'; punya render_admin()
    ├── Admin_Kabkota_Controller # role !== 'admin_kabkota' → redirect; expose $this->my_kabupaten_id dari session; punya render_scoped_admin()
    └── Admin_Bidang_Controller  # role !== 'admin_bidang' → redirect; expose $this->my_bidang_kode dari session; punya render_scoped_admin()
```

Helper session di `MY_Controller`: `is_logged_in()`, `get_user_id()`, `current_role()`, `has_role($role)`, `sanitize_redirect()` (cegah open-redirect — cek ini sebelum pakai `redirect($_GET['next'])` gaya apa pun).

> Catatan: TIDAK ada kelas `Auth_Controller`. Kalau menemukan referensi itu di dokumen lama/checkpoint, itu keliru — controller auth cukup pakai `Public_Controller` biasa + cek session manual.

> ⚠️ **Istilah "CSP-lite" di dokumen ini dulu menyesatkan (dikoreksi 26 Jul 2026): TIDAK ADA header Content-Security-Policy sama sekali.** Yang benar-benar dipasang `set_security_headers()`: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection` (sudah deprecated di browser modern), `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies`, dan HSTS — **HSTS hanya kalau `$_SERVER['HTTPS'] === 'on'`, yang bisa terlewat di balik reverse proxy.** Jangan mengandalkan CSP sebagai lapisan penahan XSS saat menilai temuan; lapisan itu belum ada.

> Satu pengecualian yang perlu diketahui: `Sikaper.php` extends `CI_Controller` langsung, bukan `MY_Controller` — satu-satunya controller publik yang lolos security headers (§18).

## 6. Libraries (`application/libraries/`)

| Library | Fungsi |
|---|---|
| `Encryption_lib.php` | AES-256-GCM encrypt/decrypt + SHA-256 deterministic hash (untuk lookup NIK terenkripsi). Kunci dari `.env`: `KPKP_DATA_KEY`, `KPKP_DATA_PEPPER`. **JANGAN GANTI kunci ini setelah data terenkripsi** — data akan hilang permanen. |
| `Smart_filter.php` | Kalkulasi Desil UN HABITAT Matrix untuk wizard diagnosa kelayakan di `Program.php` |
| `Sikaper_api.php` | Integrasi API Sikaper |
| `Ternak_api.php` | Integrasi API Ternak Web (katalog bank desain) |

## 7. Models (`application/models/`)

`Auth_model`, `User_model`, `Forum_model`, `Program_model`, `Chat_model`, `Aduan_model`, `Admin_model`, `Setting_model`, `Buka_peta`.

Dua di antaranya **kode mati** (diverifikasi 26 Jul 2026, jangan dikira aktif):
- `Chat_model` — satu-satunya kode yang menyentuh `chat_rooms`/`chat_messages`, di-load di constructor `Chat.php` tapi **tidak pernah dipanggil sekali pun**. Chat yang nyata menulis ke `tb_chat` (§8).
- `Buka_peta` — GIS/spasial dengan nama tabel dinamis via parameter; di-load di `Pengembang.php` tapi **nol pemanggil** di seluruh `application/`. Isinya legacy berbahaya kalau dihidupkan (auth `sha1()` ke tabel `user` yang bukan `usr_users`, SQL rusak). Jangan pernah sambungkan parameter nama tabelnya ke input user. Filenya juga diawali BOM UTF-8 sebelum tag `<?php`.

## 8. Database

Schema baseline (snapshot lama, TIDAK real-time): [`docs/engineering/schema_klinikpkp.sql`](docs/engineering/schema_klinikpkp.sql). Perubahan skema SEJAK migration library diaktifkan (lihat §16) ada di `application/migrations/*.php` — itu sumber kebenaran untuk tabel/kolom terbaru, bukan file `.sql` di `docs/engineering/`. Jalankan `php index.php migrate` untuk menyamakan skema DB manapun yang sedang ditunjuk `.env` ke migrasi terbaru.

**Konvensi prefix modular** (tabel baru ikuti pola ini):
- `usr_` — akun & data user (`usr_users`, `usr_documents`)
- `sf_` — Smart Filter (`sf_programs`, `sf_program_kategori`, `sf_housing_queue`)
- `forum_` — forum diskusi (`forum_diskusi`, `forum_komentar`, `forum_likes`)
- `sys_` — sistem (`sys_menu`, `sys_multi`, `sys_settings`, `sys_ticket_lookup_limits`)
- ~~`chat_` — konsultasi (`chat_rooms`, `chat_messages`)~~ ⚠️ **Klaim ini SALAH, dikoreksi 26 Jul 2026.** `Chat.php` menulis & membaca `tb_chat` — tabel yang **tidak ada di `schema_klinikpkp.sql` maupun migrasi mana pun**, jadi instalasi fresh membuat seluruh chat gagal DB error. `chat_rooms`/`chat_messages` hanya disentuh `Chat_model` yang tidak pernah dipanggil. Lihat §18.
- `data_sosmed_perumahan` — sosmed pengembang
- `srp2_` — Sertifikasi Pengembang (`srp2_registrations`, `srp2_certified_developers`, `srp2_documents`) — lihat §14
- Tabel tanpa prefix modular tapi tetap resmi (dibuat lewat `application/migrations/`, bukan lagi `.sql` lepas): `aduan`, `kabupaten`, `bidang`, `kkn_magang_pendaftaran` — lihat §16

**Tabel legacy tanpa prefix** (dipakai lewat parameter dinamis di `Buka_peta.php`, tidak ada di `schema_klinikpkp.sql` — asumsikan sudah ada di DB dari baseline sebelum migrasi ini): `kondisi`, `bendung`, `irigasi`, `saluran_pembuang`.

Jangan hapus tabel existing saat menambah fitur baru — tambah tabel baru mengikuti konvensi prefix di atas.

## 9. Keamanan — JANGAN DIRUSAK

Fondasi ini perlakukan sebagai kontrak, bukan implementation detail yang bebas diubah. **Jangan longgarkan.** Tapi jangan pula dibaca sebagai jaminan bahwa sistem ini sudah aman menyeluruh — pembacaan 26 Jul 2026 (§18) menemukan lubang nyata di luar daftar ini, dan dua poin di bawah ternyata lebih sempit dari bunyinya:

1. CSRF protection aktif global (CodeIgniter native) — dengan 9 URI dikecualikan di `config.php`, **termasuk 3 endpoint TULIS chat** (§18)
2. Google OAuth dengan state token kriptografis + anti-redirect (`sanitize_redirect()`)
3. Anti-IDOR — ID user selalu dari `session`/`get_user_id()`, **bukan** dari POST/GET body. Pola ini konsisten dan terverifikasi di seluruh subsistem
4. `Admin_Controller` guard berbasis session role, backend-enforced (bukan hanya sembunyikan UI)
5. AES-256-GCM untuk NIK & Alamat (`Encryption_lib`), SHA-256 deterministic hash untuk lookup — ⚠️ **hanya berlaku di SATU jalur: onboarding `usr_users`.** NIK tersimpan **plaintext** di `sf_housing_queue.nik_pengaju` (lokasi PII NIK terbesar di sistem) dan `srp2_registrations.nik_ktp`. Lebih jauh: `decrypt()` dan `nik_lookup_hash` tidak pernah dibaca balik di mana pun, dan `Encryption_lib` **fail-open** (kunci invalid → simpan plaintext diam-diam, dan `log_threshold=0` mematikan semua log sehingga tidak akan ketahuan)
6. Security headers global di `MY_Controller::set_security_headers()` — **tanpa CSP**, lihat catatan §5
7. Kredensial hanya lewat `.env`, tidak pernah hardcode — ⚠️ **dilanggar di `application/config/sikaper.php`**: username + password API Sikaper hardcode dan sudah masuk riwayat git (§18)

## 10. Routes Penting (`application/config/routes.php`)

Default controller: `Index`. Banyak clean-URL alias ke `Index/*` (`umum`, `profil`, `pengembang`, `simulasi_kpr`, dll), plus alias auth (`login`, `register`, `onboarding`) dan AJAX endpoints (`ajax_articles`, `load_more`, `cari_wil`). Cek file ini sebelum menambah route baru — banyak alias sudah dipakai di frontend, jangan duplikasi path.

**Hub "Nggolek Omah"** (ditambahkan setelah §11 fase UI/UX) — hero card di homepage sekarang link ke `golek_omah`, hub kecil dengan 3 menu card:
- `golek_omah` → `Index::golek_omah()` → `pages/golek_omah/index.php` — halaman hub.
- `cari_rumah` → `Index::cari_rumah()` → `pages/perumahan/cari_rumah.php`. Reuse AJAX `cari_wil`/`load_more` yang sama.
- `panduan_desain` → `Index::panduan_desain()` → `pages/bank_desain/panduan_desain.php`. Reuse AJAX `ajax_house_designs`. Jangan disamakan dengan route `materia` (`Index::materia()`) — itu placeholder kosong tidak terkait.
- `solusi_pembiayaan` → `Program/solusi_pembiayaan` — wizard diagnosa pembiayaan dengan hasil rekomendasi.
- `solusi_pembiayaan/hasil` → `Program/hasil_diagnosa` — hasil program yang bisa dipilih warga untuk diajukan.
- `solusi_pembiayaan/ajukan` → `Program/ajukan_solusi` — POST-only, memvalidasi program dari session sebelum masuk `sf_housing_queue`.
- `solusi_pembiayaan/cek-tiket` → `Program/cek_tiket` — POST-only lookup publik memakai `ticket_code` + empat digit terakhir NIK; jangan tampilkan PII.
- `cek_status_pengajuan` → `Program/cek_status_pengajuan` — tab navbar dan halaman publik untuk cek status tanpa login.

> ⚠️ **Dikoreksi 26 Jul 2026.** Dokumen ini dulu menyebut `cari_rumah`/`panduan_desain` sebagai **duplikat** dari section `#cari-perumahan`/`#bank-desain` yang "masih ada dan masih jalan di homepage". Itu sudah tidak benar: `awal.php` sekarang murni grid kartu portal dan tidak memuat kedua section itu sama sekali — sisanya hanya ada di `pages/home/archive/tentang_hingga_berita.php` yang tidak dirender siapa pun. **Halaman mandiri kini satu-satunya, bukan duplikasi.** Konsekuensinya: `ajax_articles()` dan `ajax_perumahan()` jadi endpoint yatim (hanya dirujuk view archive), begitu juga route `kemitraan` dan `materia`.

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

**Anti-IDOR wajib untuk role scoped:** setiap `update_status()` di `Admin_Kabkota`/`Admin_Bidang` WHERE clause-nya harus dobel — `WHERE id = ? AND kabupaten_id = ?` / `WHERE id = ? AND bidang = ?` — supaya admin kabupaten/bidang lain tidak bisa mengubah data di luar scope-nya walau tahu ID barisnya.

> ⚠️ **Dikoreksi 26 Jul 2026.** Dokumen ini dulu menyuruh "cek `affected_rows()` untuk membedakan berhasil vs bukan milik scope ini". **Jangan ikuti instruksi lama itu** — kode nyata sudah sengaja meninggalkannya, karena `affected_rows() == 0` juga terjadi saat admin mengirim ulang nilai yang sama (lihat `AUDIT_ROLE_ADMIN_SCOPED.md` #6), sehingga aksi yang sah dilaporkan gagal. Pola yang benar dan sudah dipakai: **pre-check `count_all_results()` dalam scope** untuk memastikan baris itu memang milik scope ini, baru `UPDATE` dengan WHERE ganda.

**`/akun` sekarang 2 halaman, bukan 1:** `Pengaturan::index()` (route `akun`) = "Status Pengajuan" — satu list gabungan SEMUA jenis pengajuan user (antrean, aduan, SRP2, KKN/Magang) diurut tanggal, item utama sidebar. `Pengaturan::profil()` (route `akun/profil`, baru) = form edit profil + data perusahaan SRP2 + hapus akun (dulu semua nyampur jadi satu di `index()`). Redirect dari `update_profile()`/`update_pengembang_profile()`/`delete_account()` (gagal) sekarang ke `akun/profil`, bukan `akun`.

**Semua dashboard login (bukan cuma admin) satu tema:** `MY_Controller::render_user_dashboard($view, $data, $scoped_menu)` — dipakai `Pengaturan::index()` (dashboard `/akun` untuk role `warga`/`pengembang`/`mahasiswa`) supaya reuse shell admin yang sama (`admin/index.php`: sidebar+topbar) seperti `Admin_Kabkota`/`Admin_Bidang`, bukan lagi halaman portal terpisah bertema gelap sendiri. Bedanya dengan `render_admin()` (khusus `Admin_Controller`, inject `pending_count`): `render_user_dashboard()` bisa dipanggil controller manapun yang extend `MY_Controller` langsung, tanpa gate role tambahan (gate login tetap tanggung jawab controller pemanggil). View yang dipakai lewat method ini WAJIB pakai token `bg-white dark:bg-brand-card`/dst (lihat `application/views/admin/layouts/head.php` untuk daftar token `brand-*`) dan ikon Phosphor (`ph-*`) — bukan `fa-solid`, karena Font Awesome TIDAK di-load di shell admin (cuma dimuat di halaman publik `layouts/main.php`).

**Tabel baru** (migrasi `application/migrations/2026070100000{8,9,10}_*.php`):
- `kabupaten` (id = kode wilayah Kemendagri 4 digit, nama) — 35 kabupaten/kota Jawa Tengah, sama persis dengan array lama di `Index.php` (`kabupaten_kota_jateng`, sekarang jadi tabel nyata).
- `bidang` (kode, nama) — formalisasi 5 nilai yang sudah dipakai `aduan.bidang` (perumahan/kawasan/pertanahan/pengembang/umum). Kolom `aduan.bidang` **tetap varchar**, bukan FK — sengaja tidak diubah supaya tidak menyentuh `Umum::simpan_aduan()`.
- `kkn_magang_pendaftaran` — pendaftaran nyata untuk `KemitraanPortal` (sebelumnya halaman statis tanpa form/tabel sama sekali).
- `sf_housing_queue.kabupaten_id`, `usr_users.kabupaten_id`, `usr_users.bidang_kode`, `aduan.catatan_admin` — kolom baru.

**Sidebar admin ter-scope:** `application/views/admin/layouts/sidebar.php` reuse layout admin superadmin yang sama — kalau view dipanggil dengan variabel `$scoped_menu` (array `[label, icon, url, segment]`), sidebar render menu ringkas itu alih-alih menu superadmin penuh. Dipakai lewat `render_scoped_admin()` di `Admin_Kabkota_Controller`/`Admin_Bidang_Controller`.

**Diketahui belum lengkap (di luar scope sesi ini):**
- ~~`Program::ajukan_solusi()` belum mengisi `kabupaten_id`~~ — **sudah tidak berlaku (diverifikasi 26 Jul 2026).** Kedua jalur kini mengisinya lewat satu pintu `Program_model::resolve_kabupaten_id()` (domisili profil user login menang > pilihan form yang divalidasi ke tabel `kabupaten` > NULL untuk tamu), dan `insert_housing_queue()` memaksa key `kabupaten_id` ada. **Temuan audit "`sf_housing_queue.kabupaten_id` dipercaya mentah dari request warga" — satu-satunya temuan tingkat Tinggi — juga SUDAH DIPERBAIKI**, jangan dikerjakan ulang. Data lama tetap bisa `NULL`.
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
- `Pengembang::simpan_dokumen()` / `kirim_pengajuan()` — balas JSON per aksi. Validasi keamanan upload (whitelist ekstensi, cek MIME asli via `finfo`, **cap 2 MB** — dokumen ini dulu keliru menulis 1 MB, dikoreksi 26 Jul 2026 — nama file acak, folder di luar webroot) **tidak disentuh**, cuma cara membalas responsnya yang bercabang.

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

**Audit sistem/keamanan/konsistensi seluruh role (26 Jul 2026):** [`docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) — baca sebelum membangun role baru yang lebih kompleks. Audit per role: [`AUDIT_ROLE_PENGEMBANG.md`](docs/engineering/AUDIT_ROLE_PENGEMBANG.md), [`AUDIT_ROLE_WARGA.md`](docs/engineering/AUDIT_ROLE_WARGA.md), [`AUDIT_ROLE_MAHASISWA.md`](docs/engineering/AUDIT_ROLE_MAHASISWA.md), [`AUDIT_ROLE_ADMIN_SCOPED.md`](docs/engineering/AUDIT_ROLE_ADMIN_SCOPED.md) (`admin_kabkota`+`admin_bidang`). Semua temuan murni observasi, belum diperbaiki. Ringkasan pola lintas-role yang paling berdampak — **keempatnya SUDAH DIPERBAIKI, diverifikasi ulang 26 Jul 2026; dikutip di sini sebagai jejak sejarah, JANGAN dikerjakan ulang:** (1) ~~tidak ada alur admin approve/reject `srp2_registrations`~~ → `Admin_Srp2` sudah fungsional penuh; (2) ~~upload di direktori publik di 3 lokasi~~ → `Auth::_handle_uploads()`, `Umum::simpan_aduan()`, dan `KemitraanPortal::simpan()` ketiganya kini lewat `store_private_upload()`; (3) ~~`delete_user_account()` tidak membersihkan tabel turunan~~ → kini meng-`unlink()` berkas fisik SRP2/kemitraan/onboarding lalu mengandalkan FK `ON DELETE` sadar-domain dari migrasi `20260701000012`; (4) ~~`sf_housing_queue.kabupaten_id` dipercaya mentah~~ → lewat `resolve_kabupaten_id()` (§16). Roadmap perbaikan gap SRP2 ada di [`docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md), termasuk checklist "Prinsip Umum untuk Role Baru" yang wajib diisi sebelum role kompleks berikutnya dibangun.

**Dashboard terpadu:** [`docs/architecture/ANCHOR_DASHBOARD_TERPADU.md`](docs/architecture/ANCHOR_DASHBOARD_TERPADU.md) — dokumen ANCHOR untuk seluruh pekerjaan dashboard, baca sebelum menyentuh sidebar/render helper/controller admin mana pun. **Fase 0–4 SELESAI SEMUA** (26 Jul 2026), lihat §17 di bawah + tabel status di anchor.

## 17. Dashboard Terpadu — Registry Modul

Semua role login memakai SATU shell dashboard (`application/views/admin/index.php`: sidebar+topbar+content slot). Menu sidebar dibangun dari **satu registry**: [`application/config/dashboard_modules.php`](application/config/dashboard_modules.php), difilter per role+scope oleh `MY_Controller::dashboard_menu()`. Ketiga render helper (`render_admin()`, `render_scoped_admin()`, `render_user_dashboard()`) sekarang semuanya delegasi ke `render_user_dashboard()` — nama method dipertahankan karena banyak call site, tapi perilakunya sudah seragam. Registry ini juga merangkap peta role→tabel pengajuan→reviewer (menggantikan rencana `role_admin_map.php` yang disebut di `DESAIN_NORMALISASI_SKEMA_ROLE.md` — jangan buat file itu).

> ⚠️ **Registry = menu yang TAMPIL, BUKAN otorisasi.** Penegakan akses tetap di constructor base controller (`Admin_Controller`/`Admin_Kabkota_Controller`/`Admin_Bidang_Controller`) + WHERE ganda scope + `affected_rows()`. Modul terdaftar untuk role X tapi controller-nya menolak role X = menu tampil, akses ditolak — itu perilaku BENAR (fail-closed). Perbaikannya selalu di registry, JANGAN pernah melonggarkan guard controller.

**Cara menambah modul dashboard (checklist wajib, urutan tetap):**
> ⚠️ **Checklist ini perlu, tapi TIDAK cukup — dibuktikan 27 Jul 2026.** Role `pengembang` dibangun mengikuti kedelapan langkah di bawah dan tetap menghasilkan 24 celah. §17 mengatur **cara membangun**; yang lolos adalah **apa yang terjadi saat sesuatu gagal atau berubah**. Baca [§19 Metode Baku Backend Role ↔ Admin](#19-metode-baku-backend-role--admin) berbarengan dengan checklist ini — §19 prasyarat dan pelengkapnya, bukan penggantinya.
>
> ⚠️ **Asumsi tersembunyi (ditemukan T6, 27 Jul 2026): checklist 8 langkah ini MENGATUR JALUR TULIS DAN LAYAR ADMIN SAJA.** Tidak satu poin pun menanyakan permukaan PUBLIK (direktori, profil pengembang) atau layar PEMOHON (wizard, `/akun`) — dan empat dari enam temuan Tinggi di roadmap pengembang lolos persis lewat celah itu (T2, T4). Menambah butir ke-9 yang dibaca sebagai daftar belanja tidak menutup asumsi ini; yang menutup adalah TAHU bahwa checklist ini punya batas, dan bertanya di luar batasnya lewat tiga pertanyaan di poin 17.

1. Tentukan reviewer & scope pakai checklist "Prinsip Umum untuk Role Baru" di [`docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md`](docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md) — SEBELUM menulis kode. Isi juga **Kartu Domain** (§19 langkah 1).
2. Buat/pakai controller yang extend base class sesuai role penegak. Guard di constructor, bukan di method.
3. Endpoint tulis: POST-only, whitelist field eksplisit, WHERE ganda scope bila ter-scope, cek `affected_rows()`, isi `reviewed_by`/`reviewed_at`.
4. Lampiran: WAJIB lewat `MY_Controller::store_private_upload()` (validasi ekstensi + MIME asli via `finfo` + batas ukuran + nama acak, simpan ke `private_uploads/{domain}/{pemilik}/`) dan disajikan lewat `serve_private_file()` di endpoint ber-guard. Tidak ada pengecualian, jangan bikin jalur unggah sendiri.
   > ⚠️ **Poin ini mencakup GANTI dan HAPUS, bukan hanya unggah dan sajikan** (ditambahkan 27 Jul 2026 setelah ditemukan 6 berkas yatim / 5,5 MB dari satu pengajuan uji):
   > - **Mengganti lampiran wajib `unlink()` berkas lama.** Ambil `stored_name` lama SEBELUM menimpa barisnya — `db->replace()` menghapus baris berikut nama berkasnya, dan sesudah itu berkas fisiknya mustahil ditemukan lagi.
   > - **Hapus akun wajib menyapu direktori berdasarkan ISI DISK**, bukan hanya nama yang tercatat DB. Menyapu dari DB meninggalkan yatim hasil penggantian — artinya akta, NPWP, dan laporan keuangan selamat dari penghapusan akun. Itu kewajiban retensi UU PDP, bukan kerapian.
   > - **Endpoint baca wajib ada untuk PEMILIK data**, bukan hanya pengelola. Tanpa itu permintaan perbaikan tidak bisa ditindaklanjuti: pemohon tidak punya cara memeriksa apa yang dulu dia kirim.
   > - **Normalisasi ID sekali di pintu masuk.** Guard `is_numeric()` + query `(int)` + path memakai nilai mentah = berkas mendarat di direktori yang tidak dibaca siapa pun, sementara hitungan dokumen tetap lolos.
   > - **Validasi SEMUA berkas dulu, baru pindahkan.** Validasi dan `move_uploaded_file()` dalam satu loop membuat kegagalan di tengah meninggalkan berkas tanpa baris DB — yatim permanen.
   > ⚠️ **"private_uploads di luar webroot" TIDAK selalu benar.** Diverifikasi 26 Jul 2026: di layout XAMPP lokal `dirname(FCPATH)` sama dengan DocumentRoot Apache, sehingga `http://localhost/private_uploads/srp2/7/xxx.pdf` menyajikan dokumen SRP2 tanpa login. Karena itu `ensure_private_uploads_protected()` menulis `.htaccess` penolak akses di akar `private_uploads/` — dibuat oleh kode, bukan manual, sebab direktori itu di luar repo git. Jangan hapus pemanggilannya. **Batas:** `.htaccess` hanya dipatuhi Apache/LiteSpeed; kalau pindah ke nginx, proteksi ini tidak berlaku dan wajib diganti aturan server atau direktorinya benar-benar dipindah keluar DocumentRoot. **Belum diverifikasi di production** — cek sendiri apakah `dirname(FCPATH)` di sana juga tersaji web.
5. View: render lewat helper yang sesuai; token `brand-*` + ikon Phosphor `ph-*` (**Font Awesome TIDAK di-load di shell admin** — memakai `fa-*` di view admin = ikon blank); skeleton §14.
6. Komponen bersama WAJIB dipakai, jangan bikin varian baru: `admin/components/status_badge.php` (petakan status domainmu ke `pending|process|ok|reject`), `review_form.php` (form keputusan), `table_toolbar.php` (kotak cari + slot filter), `pagination.php`. Pola tabel admin **selalu**: `table_state()` (whitelist kolom sort) → terapkan filter → `count_all_results('', FALSE)` → `paginate_state()` → ambil baris. Semua link tabel lewat `admin_table_url()` supaya filter/cari/urut saling terbawa. **Jangan** kirim semua baris ke browser lalu filter di klien — pola itu sudah dihapus dari sisi admin (B8).
   > Dua jebakan yang sudah memakan korban: (a) `count_all_results($tabel, FALSE)` menyetel FROM **dan** menyimpan state — diikuti `->get($tabel)` jadi `FROM x, x` (error 1066); pakai `->from($tabel)` di depan lalu `count_all_results('', FALSE)` lalu `->get()` tanpa argumen. (b) Nama kolom sort TIDAK PERNAH boleh dari input langsung ke ORDER BY — CI query builder tidak meng-escape nama kolom seperti nilai; whitelist lewat `table_state()`.
7. TERAKHIR: satu entri di `dashboard_modules.php`. Active-state dihitung otomatis — jangan tulis logika active sendiri di view. Kalau modulmu punya antrean, isi `table` + `pending_where` (deklarasi tunggal "apa arti belum diproses" untuk domain itu — dipakai badge sidebar sekaligus kartu overview), plus `ringkas` kalau mau muncul sebagai kartu di `Admin_Dashboard`.
8. FK baru ke `usr_users.id` = `INT` signed, bukan `UNSIGNED` (lihat catatan migrasi `20260701000011/12`).
9. **Aksi yang menulis ke lebih dari satu tabel WAJIB dibungkus transaksi + diperiksa nilai baliknya**, dan `INSERT` ke tabel ber-`UNIQUE` wajib punya cabang gagal yang TERLIHAT pengguna. Pola: `trans_start()` → tulis → `trans_complete()` → `trans_status()` (contoh: `User_model.php:36`, `Admin_Srp2::proses()`). Ingat `db_debug` MATI di production: query gagal tidak menghentikan eksekusi, ia hanya mengembalikan FALSE dan alur berjalan terus sampai menyetel flash sukses.
10. **Setiap modul berstatus wajib punya daftar transisi sah yang ditegakkan di SERVER, bukan di view** — dan `UPDATE`-nya menyertakan status asal di `WHERE`. Dengan status asal di `WHERE`, `affected_rows() === 0` jadi tidak ambigu (asal selalu ≠ tujuan), sehingga instrumen verifikasi di poin 3 kembali bekerja. Kondisi `if` di view itu UI, bukan otorisasi.
11. **Layar keputusan wajib menampilkan data pembanding yang jadi dasarnya**, dan **antrean reviewer wajib bisa menampilkan status non-default** — kalau keputusan yang sudah diambil hilang dari jangkauan, tidak ada yang bisa memantau tindak lanjutnya (mis. "siapa yang diminta perbaikan tapi belum kirim ulang"). Status jadi FILTER dengan nilai default dari `pending_where` di registry, bukan klausa `WHERE` mati. Kolom yang kosong ditandai apa adanya, jangan disembunyikan — supaya terlihat bahwa datanya memang belum dikumpulkan.
12. **Untuk setiap modul, sebutkan PERMUKAAN PUBLIK-nya dan transisi status mana yang wajib menular ke sana.** Tautan antar tabel lewat kolom FK, **tidak pernah pencocokan nama string** — nama tidak unique, bisa diedit, dan bisa beda collation antar tabel sehingga join-nya bahkan tidak sah (`srp2_registrations` `general_ci` vs `srp2_certified_developers` `unicode_ci`). **PR yang menambah kolom FK wajib MENCABUT jalur lama yang digantikannya di PR yang sama** — menambah kolom tanpa mencabut meninggalkan dua definisi "entitas yang sama", dan migrasi yang menambah kolom wajib punya pasangan backfill untuk baris lama.
    > Pelajaran turunan: mengunci data setelah disetujui terdengar aman, tapi **pisahkan identitas dari kontak**. Nama perusahaan (yang diverifikasi admin + kunci UNIQUE direktori) dikunci; alamat/website/sosmed justru harus bisa diperbarui pemiliknya dan menular ke publik — form-nya sendiri menjanjikan itu.
13. **Setiap layar yang menampilkan keadaan pengajuan pemohon WAJIB memakai satu fungsi keadaan bersama** (pola `Auth_model::srp2_state()`) — **jangan query sendiri.** Ini sudah **dua kali** jadi bug: wizard menampilkan 0/14 dokumen karena `Auth::do_login()` tidak punya akses ke keadaan, dan `/akun/profil` menampilkan "Lengkapi Dokumen" polos untuk pengajuan yang dibuka kembali karena ia menyalin logikanya sendiri. Dua permukaan, dua salinan, dua cerita berbeda ke orang yang sama.
14. **Kredensial demo di view: DIIZINKAN SELAMA MASA UJI COBA, dengan syarat.** Keputusan user 27 Jul 2026 — dinas sedang menguji sistem dan tanpa kredensial di layar mereka tidak bisa menelusuri keenam peran. Blok demo di `pages/auth/login.php` dan wizard SRP2 memang sengaja ada, **bukan kelalaian, jangan dihapus tanpa perintah baru**.
    - **Syarat yang harus tetap benar:** setiap akun yang tercantum WAJIB akun demo berisi data contoh. Begitu sistem memuat data warga sungguhan, atau ada akun tercantum yang memegang wewenang nyata, blok ini **harus dicabut**.
    - ⚠️ **Yang belum beres:** `admin@klinikpkp.jatengprov.go.id` bersandi `password` dan tercantum di blok itu. Selama sandinya belum diganti, siapa pun pengunjung situs bisa masuk sebagai admin penuh. Terverifikasi 27 Jul 2026 lewat `password_verify()` ke hash production.
    - **Jangan menggantinya dengan gate lingkungan.** Itu fail-open: `index.php` mendefault `ENVIRONMENT` ke `development` bila `CI_ENV` tidak diset, jadi server yang lupa menyetelnya justru tetap memajangnya. Kalau memang harus disembunyikan, hapus bloknya.
15. **Endpoint publik yang bisa dipanggil berulang oleh anonim WAJIB lewat `MY_Controller::rate_limit_sisa()` + `rate_limit_catat()`** (tabel `sys_rate_limits`, pisahkan penghitung lewat argumen `$scope`). Jangan bikin varian baru dan jangan mengandalkan reCAPTCHA sebagai gantinya — verifikasinya dilewati seluruhnya kalau kuncinya kosong.
16. **reCAPTCHA di `Auth::do_login()`/`do_register()`: DIBIARKAN APA ADANYA, dengan syarat.** Keputusan user 27 Jul 2026 (roadmap T5) — widget tampil tapi verifikasinya dilewati total kalau `recaptcha_secret_key` kosong (memang kosong di `.env` lokal), sengaja **tidak** diisi kunci maupun dihapus widget-nya untuk sekarang. **Jangan "perbaiki" ini tanpa perintah baru** — baik mengisi kunci (butuh user daftar ke Google reCAPTCHA) maupun menghapus widget adalah keputusan produk, bukan bug yang boleh ditebak agent. Satu paket dengan ini: `Auth::do_verify_email()` (dummy, menyetel `email_verified_at` tanpa token) dan `Auth::verify_email($token)` (token-based, tidak pernah dipanggil karena tidak ada yang mengirim link-nya) juga dibiarkan — nol gating di kode mana pun membaca `email_verified_at`, jadi murni kosmetik, bukan celah keamanan aktif.
17. **Tiga pertanyaan tambahan, WAJIB dijawab untuk modul baru mana pun** (ditulis SETELAH T0–T5 selesai, jadi berdasar temuan nyata, bukan tebakan — dan persis mengisi celah "checklist ini cuma soal jalur tulis + layar admin" di atas):
    - **(a) Permukaan publik mana yang menampilkan data ini, disaring status apa, dan disinkronkan KAPAN setelah data berubah?** Contoh konkret: direktori publik SRP2 (`Pengembang::sertifikasi()`) harus menyaring `status_aktif=1`, dan `Pengaturan::update_pengembang_profile()` harus menulis ulang baris direktori itu SETIAP kali kontak pengembang berubah, bukan cuma sekali saat approve (T4). Ini kluster temuan TERBESAR di roadmap pengembang (T0, T4) dan nol pertanyaan lain yang menyentuhnya.
    - **(b) Semua permukaan yang menampilkan keadaan pengajuan ke pemohon — dari fungsi keadaan yang MANA? Sebutkan namanya.** Contoh konkret: `Auth_model::srp2_state()` seharusnya jadi satu-satunya sumber, tapi dua permukaan (`Auth::do_login()` cabang AJAX, `Pengaturan::profil()`) sempat menyalin logikanya sendiri dan menampilkan keadaan tamu untuk pengembang lama (T2). Kalau jawabannya "tidak ada nama fungsi, tiap permukaan query sendiri" — itu tandanya modul ini akan mengulang bug yang sama.
    - **(c) Apa syarat minimum sebuah baris boleh masuk antrean reviewer, dan di endpoint mana syarat itu ditegakkan?** Akar pemblokir #2 di roadmap pengembang: sebelum T1a, `kirim_pengajuan()` cuma mensyaratkan 14 dokumen — nama perusahaan kosong atau duplikat tetap lolos ke meja admin sebagai baris yang MUSTAHIL disetujui. Jawabannya harus menyebut fungsi/method persis, bukan "sudah divalidasi di form" (form itu UI, bukan gerbang).

> ⚠️ **Jangan pernah menampilkan angka, status, atau pesan sukses karangan.** Dua kali ditemukan di repo ini: overview dengan "Publikasi Aktif = 24" hardcode + chart dummy + feed nama fiktif (B2), dan `Admin::update_status()` yang mengklaim "telah disinkronisasi dengan API SIMPERUM" padahal tidak ada request apa pun yang dikirim (B11). Kalau sebuah metrik belum bisa dihitung atau integrasi belum ada, hilangkan elemennya atau tulis apa adanya — **jangan disimulasikan**. Integrasi SIMPERUM memang belum ada dan akan menyusul.

**Normalisasi skema (26 Jul 2026):** [`docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md`](docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) — Opsi A (konvensi konsisten, tabel domain tetap terpisah) dipilih dan migrasi intinya **sudah dijalankan**: `srp2_registrations`, `aduan`, `kkn_magang_pendaftaran`, `sf_housing_queue` sekarang semua punya `reviewed_by`/`reviewed_at` + FK `user_id` ke `usr_users.id` dengan `ON DELETE` yang sadar per domain (migrasi `20260701000011`-`20260701000013`). **Catatan penting untuk kolom baru mana pun yang menunjuk `usr_users.id`:** gunakan `INT` biasa, BUKAN `UNSIGNED` — `usr_users.id` adalah `int(11)` signed (peninggalan skema lama), FK ke kolom unsigned akan gagal (errno 150). `User_model::delete_user_account()` sudah diperbarui untuk `unlink()` file fisik SRP2/KKN-Magang sebelum baris DB ter-CASCADE. Kolom `reviewed_by`/`reviewed_at` **sudah diisi** keempat jalur reviewer sejak Fase 2. ~~**Yang masih terbuka:** lokasi upload publik belum dipindah ke `private_uploads/` — 3 lokasi.~~ **SUDAH SELESAI** (diverifikasi baris demi baris 26 Jul 2026): `Auth::_handle_uploads()`, `Umum::simpan_aduan()`, dan `KemitraanPortal::simpan()` ketiganya memakai `store_private_upload()`. Satu-satunya penulis direktori publik yang tersisa adalah `Admin_Content.php` untuk gambar hero — dan itu memang konten publik, bukan kelalaian.

**Follow-up yang sengaja di luar scope sesi ini** (jangan dikerjakan tanpa arahan baru, cukup dicatat):
- Generator sertifikat PDF asli belum ada — tombol "Download Sertifikat" di dashboard akun sengaja nonaktif dengan pesan jujur, bukan simulasi.
- ~~`Auth::save_onboarding()` menulis kolom `nama_perusahaan`/`alamat_kantor`/`telp_kantor` yang tidak ada di skema~~ — **sudah tidak berlaku**, ketiga kolom itu memang ada di `usr_users` (lihat §16). Catatan lama ini usang, jangan diikuti.
- [`docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md`](docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md) dan [`docs/product/IMPLEMENTATION_ROADMAP.md`](docs/product/IMPLEMENTATION_ROADMAP.md) masih menyebut menu Pengembang/SP2 sebagai halaman statis/"belum berupa sistem interaktif" ("view-only") — **usang (superseded)**, sudah dikonfirmasi user untuk dibuat interaktif penuh sesi ini.

## 18. Temuan Pembacaan 26 Jul 2026 — Belum Diperbaiki

Ringkasan yang paling berdampak dari 141 temuan. Detail lengkap per subsistem: [`PEMBACAAN_CODEBASE_26JUL2026.md`](docs/engineering/PEMBACAAN_CODEBASE_26JUL2026.md). **Diperbarui 27 Jul 2026:** paparan `.env` sudah ditutup di ketiga situs, dan temuan yang bersinggungan dengan perjalanan SRP2 ikut selesai lewat T0–T4 (§0b) — yang selesai ditandai ✅ di tempatnya, jangan dikerjakan ulang. Sisanya masih terbuka.

> **Cara membaca:** yang bertanda ⏳ **belum diverifikasi runtime** — saat pembacaan dibuat, Apache & MySQL lokal mati. Konfirmasi dulu sebelum memperbaiki, jangan langsung percaya. Sisanya sudah diverifikasi live atau terbukti mutlak dari kode.

> ⚠️ **Batas pembacaan ini — "141 temuan" BUKAN berarti "sudah tidak ada masalah lain".** Cakupannya 100% untuk controller/model/library/helper/migrasi, tapi **empat berkas yang HIDUP tidak terbaca sama sekali**, jadi masalah di sana tidak akan muncul di daftar mana pun:
> - `pages/profil/sejarah_visi.php`, `tugas_pokok.php`, `struktur_organisasi.php` — dirender `Index.php` baris 515/521/527
> - `pages/perumahan/housing_carrier1.php` (40KB) — dirender `Umum::housing()`, form dengan `action="#"` yang belum tersambung backend
>
> Belum ada yang memeriksa keempatnya. Kalau menyentuh area itu, baca dulu — jangan berasumsi sudah teraudit.

### Keamanan

| Temuan | Lokasi |
|---|---|
| ✅ `.env` + `docs/` tersaji publik (HTTP 200 di **production & staging**) — **paparannya SUDAH ditutup 27 Jul 2026 di ketiga situs** (§0c). Yang MASIH terbuka: kredensial yang terlanjur terbaca belum dirotasi | `.htaccess` (lokal + ketiga server) |
| Kotak **"Kredensial Demo"** memajang email admin + password `password` di halaman login, tanpa gate lingkungan — ikut tampil di production. Ada juga di wizard SRP2. ✅ **Diverifikasi 26 Jul 2026: akun `admin@klinikpkp.jatengprov.go.id` dan `warga@example.com` MEMANG ADA di DB lokal, dan login dengan password `password` BERHASIL.** Terkonfirmasi ADA di production (3 dari 7 akun). ⚠️ **Bloknya sengaja DIPERTAHANKAN atas keputusan user 27 Jul 2026** — syarat & batasnya di §17 poin 14; yang harus dibereskan bukan bloknya melainkan sandi akun adminnya (§0c) | `pages/auth/login.php:136`, `pages/pengembang/syarat.php:176` |
| Password API Sikaper **hardcode & sudah masuk riwayat git** (melanggar §9 poin 7) — rotasi kredensialnya, memindah ke `.env` saja tidak cukup | `config/sikaper.php:13` |
| Chat: `session_id` dibuat browser pakai `Math.random()`, tidak terikat sesi server → siapa pun yang menebaknya membaca riwayat chat + nama/email/HP orang lain | `Chat.php:147`, `layouts/footer.php:161` |
| Chat: `api_bot()` public-routable, tanpa login, tanpa CSRF (dikecualikan), tanpa rate limit → kuota Gemini bisa dikuras siapa saja | `Chat.php:80` |
| `submit_antrean()` menerima `program_id`, NIK, dan seluruh data survei mentah dari POST tanpa validasi — bertentangan dengan §15 (jalur `ajukan_solusi` patuh, jalur ini tidak) | `Program.php:383` |
| Verifikasi TLS **dimatikan di semua** klien HTTP keluar (Sikumbang, Sikaper, Ternak, Gemini) — dikomentari "sementara dev lokal" tapi berlaku unconditional di production | `Index.php:126` dkk, `Sikaper_api.php:36`, `Ternak_api.php:42` |
| `report_komentar()` tanpa login & tanpa dedup → 5× POST menyembunyikan komentar siapa pun, tanpa jalur pemulihan | `Umum.php:372` |
| ✅ **SELESAI di T5 — Ganti password & hapus akun tanpa re-autentikasi.** Keduanya sekarang wajib `current_password` diverifikasi `password_verify()` server-side, diuji end-to-end (curl + query DB) | `Pengaturan.php:227` |
| ✅ **SELESAI di T5 — Reflected XSS `google_callback()`.** Terbukti nyata lewat pembuktian terisolasi (bukan cuma dugaan): `base_url($redirect_to)` di-echo mentah ke string JS berkutip tunggal, dan `sanitize_redirect()` cuma menolak URL eksternal, tidak membuang kutip satu. Sekarang `json_encode()` + CSP nonce'd scoped ke endpoint itu saja. ⚠️ Fix-nya tidak diuji end-to-end (butuh kode OAuth Google asli yang berhasil) | `Auth.php:681` |
| Stored XSS via skema URI: pemohon bisa menyimpan `javascript:` sebagai website. ⚠️ **Klaim ini DIBANTAH lewat pengujian** (dicatat di roadmap T6) — jangan diperbaiki sebagai bug; yang tersisa cuma pengerasan: validasi skema URL sendiri alih-alih bergantung `global_xss_filtering` yang komentarnya sendiri menandai DEPRECATED | `admin/srp2/detail.php:46` |
| `cookie_secure = FALSE` meski production HTTPS | `config/config.php:414` |
| `ENVIRONMENT` default `development` kalau `CI_ENV` tidak diset → error mendetail tampil publik. ✅ **Sudah dipasang di production 27 Jul 2026** lewat `SetEnv` di `.htaccess` (BUKAN `.env` — jebakan di §0e), dibuktikan dengan berkas uji. Sifat fail-open-nya tetap berlaku untuk instalasi baru | `index.php:56` |

### Rusak total — ✅ DIUJI LIVE 26 Jul 2026 (bukan lagi dugaan)

Diuji lewat HTTP nyata di lokal (login `warga@example.com`, POST betulan). Bukan hasil membaca kode:

- ✅ **Forum TIDAK BISA posting maupun balas — TERBUKTI.** Keduanya mati dengan error 1146:
  - `Umum/tambah_aksi` → `Table 'klinikpkp.diskusi' doesn't exist`
  - `Umum/balas_aksi` → `Table 'klinikpkp.komentar' doesn't exist`

  Sumbernya satu: `check_forum_rate_limit($table, ...)` di `helpers/forum_helper.php:75` menerima `'diskusi'`/`'komentar'` dari `Umum.php:209` dan `:330`, padahal tabel nyatanya `forum_diskusi`/`forum_komentar`. **Perbaikan paling kecil ada di helper, bukan di pemanggil** — satu tempat, kedua pemanggil ikut benar sekaligus.
- ✅ **Seluruh chat gagal — TERBUKTI.** `POST Chat/register_session` → `Table 'klinikpkp.tb_chat' doesn't exist` (error 1146). Endpoint ini dikecualikan dari CSRF dan tanpa login, jadi siapa pun yang membuka widget chat di halaman mana pun langsung kena. `chat_rooms`/`chat_messages` justru ADA di DB tapi menganggur — hanya disentuh `Chat_model` yang tidak pernah dipanggil (§7).
- ❌ **"Pengajuan Desil 4 gagal karena `omah_sekeng` id 6 tidak ada" — KLAIM INI SALAH, jangan diikuti.** Barisnya ADA di DB lokal (`id=6`, `kode_program='omah_sekeng'`, `created_at 2026-06-30`). **Tapi masalah nyatanya lebih halus dan lebih berbahaya:** baris itu dimasukkan MANUAL, **tidak ada migrasi mana pun yang membuatnya** dan tidak ada di `schema_klinikpkp.sql`. Artinya lokal jalan (jadi tidak ada yang sadar) sementara **instalasi fresh dan lingkungan mana pun yang tidak kebagian INSERT manual itu akan gagal** dengan pelanggaran FK saat pemohon Desil 4 memilih Omah Sekeng. ⏳ Belum diverifikasi apakah staging & production punya barisnya. Perbaikan benar: buat migrasi yang meng-seed baris ini, jangan andalkan DB lokal.
- ✅ **SELESAI di T1a (`f74047f`) — Approve SRP2 bisa gagal sambil melapor sukses.** Terbukti nyata, bukan dugaan: `INSERT` ke direktori bentrok `UNIQUE` nama sementara `db_debug` mati di production. Sekarang `proses()` bertransaksi dan flash-nya ditentukan dari hasil; diuji dengan menirukan kondisi production di lokal.
- ⏳ **Onboarding role `vendor`** menulis kolom `nama_usaha`/`alamat_usaha`/`jenis_usaha` yang tidak ada di skema; kartu Vendor masih aktif di UI. `Auth.php:466`

> 📌 **Pelajaran dari pengujian ini, berlaku umum:** dua dugaan terbukti benar, satu terbukti SALAH — dan yang salah itu justru menyembunyikan masalah yang lebih berbahaya (DB lokal sudah ditambal manual, lingkungan lain belum). **Kalau DB lokal jalan, itu bukan bukti kode benar.** Selalu tanya: apakah keadaan ini lahir dari migrasi, atau dari tangan seseorang yang lupa mencatat?

### Masih melanggar aturan "jangan tampilkan angka/status karangan" (§17)

Empat lokasi lolos dari pembersihan B2/B11: halaman **Pengaturan Sistem admin** seluruhnya mockup (nilai palsu, tombol Simpan di luar form, toggle mati) `admin/settings/index.php`; **Statistika** menampilkan angka dummy berlabel "Sumber: Simperum/Sikumbang/Sikaper" dengan multiplier `crc32()` per kabupaten `Statistika.php:26`; **`listkabupaten`** punya tabel intervensi fiktif tanpa label simulasi; dan ~~**`Umum::download_sertifikat()`**~~ — ✅ yang keempat sudah dicabut di T0 (`9a0488c`), diganti keterangan jujur. Tiga sisanya masih berdiri.

### ✅ Fitur "Minta Perbaikan" SRP2 — SELESAI & terverifikasi (27 Jul 2026, commit `5db1ba4`)

Admin punya keputusan ketiga selain Terima/Tolak: mengembalikan pengajuan ke `Draft` dengan catatan wajib, dari status Pending maupun Diterima. Baris direktori publik sengaja tidak disentuh saat dibuka kembali.

Dua bug yang membuatnya tidak berfungsi sudah diperbaiki, keduanya berakar sama: **keadaan SRP2 pemohon dulu hanya dihitung di `Pengembang::syarat()`** sehingga jalur lain melihat keadaan tamu. Sekarang `Auth_model::srp2_state()` jadi **satu-satunya sumber**, dipakai `Pengembang::syarat()` maupun cabang AJAX `Auth::do_login()`.

> 📌 **Kalau menambah jalur baru yang perlu tahu status pengajuan pemohon, panggil `srp2_state()` — jangan query sendiri.** Duplikasi query itulah yang dulu membuat wizard menampilkan 0/14 dokumen padahal server sudah lengkap.

Diverifikasi di browser sungguhan, empat cabang: Draft+catatan mendarat di kartu perbaikan, login lewat wizard menampilkan 14/14, Draft biasa dan Diterima tidak berubah perilakunya.

### Kandidat dibersihkan

`Bank_desain.php` (error total, view tidak ada), `Kemitraan.php` & `Kabupaten.php` (halaman kosong/mockup, sudah digantikan `KemitraanPortal`), `Buka_peta.php` + baris load-nya di `Pengembang.php` (nol pemanggil), `Sikaper.php` (API explorer debug yang terbuka publik), route `pengaturan` (menunjuk method yang tidak ada → 404), dan berkas satu-kali-pakai yang masih ter-track di root: `apply_tokens.js`, `image.png`, `image copy.png` — melanggar aturan §3 kita sendiri.


## 19. Metode Baku Backend Role ↔ Admin

> Disusun 27 Jul 2026 setelah role `pengembang` **lulus checklist §17 delapan langkah dan tetap menghasilkan 24 celah**. Jadi yang kurang bukan ketelitian, tapi cakupan. Berlaku untuk SEMUA domain — warga, mahasiswa, admin ter-scope menyusul dengan pola yang sama.

### Beda dengan §17

§17 adalah checklist KONSTRUKSI: apakah modulnya ada, di kelas base yang benar, memakai komponen yang benar, terdaftar di registry. Metode ini adalah checklist EKSEKUSI & SIKLUS HIDUP: apakah tulisannya benar-benar mendarat, apakah statusnya hanya berpindah lewat jalur sah, apakah semua permukaan menceritakan hal yang sama, dan apakah berkas/data yang lahir punya yang bertanggung jawab menghapusnya. Role pengembang lulus §17 delapan langkah dan tetap menghasilkan 24 celah — jadi yang kurang bukan ketelitian, tapi cakupan.

EMPAT LUBANG YANG TERBUKTI, dan Metode ini persis menambal keempatnya:

(a) §17 SELURUHNYA tentang sisi ADMIN. Controller publik (`Umum`, `Pengembang::sertifikasi()/profil()`) dan endpoint tulis milik PEMOHON (`Pengembang::kirim_pengajuan()`, `Pengaturan::update_pengembang_profile()`) bukan modul dashboard, jadi tidak pernah menyentuh checklist mana pun. Empat dari enam temuan Tinggi duduk persis di dua area itu.

(b) §17 langkah 3 membayangkan SATU endpoint = SATU UPDATE. Kata "transaksi" tidak muncul sekali pun di §17 maupun di PRD. `Admin_Srp2::proses()` menulis dua tabel; pemblokir #2 duduk persis di situ.

(c) §17 langkah 4 mengatur MENULIS dan MENYAJIKAN berkas dan itu memang dipatuhi rapi (fondasi unggahnya terbukti utuh, 403 di uji live). Kata "hapus"/"unlink" tidak ada. GANTI dan HAPUS tidak diatur siapa pun, dan PRD pertanyaan 5 justru menganjurkan `ON DELETE CASCADE` — yang menghapus baris berisi nama berkas dan membuat berkasnya mustahil ditemukan lagi.

(d) YANG PALING MENENTUKAN — aturannya sudah ada, penegaknya tidak. §17 langkah 3 SUDAH mewajibkan `affected_rows()`; §17 SUDAH melarang sukses karangan. `grep -rn affected_rows application/` = 5 hasil, dua di antaranya cuma komentar (`Admin_Bidang.php:71`, `Admin_Kabkota.php:40`), tiga sisanya di `Buka_peta.php` dan `Setting_model.php`. NOL di `Admin_Srp2.php`, nol di keempat jalur reviewer lain. `trans_start` cuma dipakai `User_model.php:36` dan `:117`. Menambah aturan ke-25 tidak akan menutup ini. Karena itu Langkah 12 (skrip uji perjalanan yang bisa gagal merah) adalah satu-satunya butir yang TIDAK boleh dipotong.

DAN SATU HAL YANG TIDAK BOLEH DIULANG: §17 langkah 3 menyuruh cek `affected_rows()`, sementara komentar di `Admin_Kabkota.php:40-43` menjelaskan kenapa `affected_rows()` saja tidak cukup (MySQL membalas 0 juga saat nilai barunya sama). Satu-satunya instruksi verifikasi di checklist dilumpuhkan catatan kakinya sendiri. Metode ini memperbaikinya, bukan membuangnya: dengan klausa `WHERE <kolom_status> = <status_asal>` (Langkah 6), `affected_rows() === 0` jadi TIDAK ambigu — asal ≠ tujuan, jadi 0 baris pasti berarti gagal atau kalah balapan. §17 langkah 3 tetap berlaku apa adanya, cuma ditambah satu klausa.

DI MANA INI DITULIS (supaya tidak lahir sumber kebenaran kedua, yang dilarang CLAUDE.md): Langkah 5–12 masuk ke AGENTS.md §17 sebagai perluasan langkah 3 — §17 = aturan yang berlaku untuk SEMUA domain, dibaca tiap agent. Langkah 1–4 (isi kontrak per domain) masuk ke PRD domain itu — PRD = ISI, §17 = ATURAN. Jangan bikin dokumen ketiga.

BUKAN PENGGANTI §17. Jalankan §17 langkah 1–8 seperti biasa; Metode ini prasyarat dan pelengkapnya. Butir §17 yang sudah cukup dan sengaja TIDAK saya ulang di sini: POST-only, guard di constructor, whitelist field, WHERE ganda scope, `store_private_upload()`, komponen admin bersama, pola tabel B8, FK `INT` signed.

### Urutan langkah

1. LANGKAH 1 — KARTU DOMAIN, satu halaman di PRD domain, SEBELUM baris kode pertama. Enam jawaban, dan tiga terakhir yang selama ini tidak pernah ditanyakan: (a) tabel induk; (b) KOLOM PEMILIK dan apakah boleh NULL — untuk aduan warga jawabannya BOLEH (`Umum.php:124-126` sengaja menerima `user_id` NULL dari tamu), dan kalau boleh NULL maka seluruh anti-IDOR `WHERE user_id` tidak berlaku dan kamu wajib memutuskan sekarang: tiket pelacakan, atau aduan wajib login; (c) berapa baris per pemilik yang sah — satu (status berpindah) atau banyak (riwayat); (d) NAMA KOLOM STATUS dan daftar nilainya — jangan asumsikan `status_verifikasi`, empat domain di repo ini memakai empat nama berbeda dengan empat ragam kapitalisasi (`srp2_registrations.status_verifikasi`='Pending', `aduan.status`='Baru', `kkn_magang_pendaftaran.status`='Diajukan', `sf_housing_queue.status_antrean`='pending'); (e) apakah domain ini ter-scope, dan kalau ya, baris ber-scope NULL masuk antrean SIAPA (hari ini: tidak ada siapa pun, karena controller ter-scope menyaring `kabupaten_id = X`); (f) kolom PII apa yang dikumpulkan.

2. LANGKAH 2 — TABEL TRANSISI, di PRD yang sama. Kolom: status_asal | aksi | status_tujuan | siapa yang berwenang | apa yang terjadi pada tabel/berkas TURUNAN. Syarat yang bisa gagal (bukan "muat di satu layar"): setiap status punya minimal satu panah masuk dan satu panah keluar, tidak ada status yang hanya bisa dicapai lewat DEFAULT kolom, dan setiap panah KELUAR dari status yang menerbitkan sesuatu ke publik punya kalimat tertulis tentang pencabutannya. Wajib memuat panah BALIK (dikembalikan ke pemohon) — diagram di PRD_VERIFIKASI_ADMIN_SRP2.md hanya pohon maju Diterima/Ditolak, dan dari ketiadaan panah balik itulah S2 dan S3 lahir. Kalau domainnya PUNYA transisi yang memindahkan SCOPE (aduan salah kabupaten, disposisi antar bidang), itu transisi yang mengubah PEMILIK — tulis terpisah, karena `WHERE scope` tidak menjaganya.

3. LANGKAH 3 — INVENTARIS PERMUKAAN, termasuk yang anonim. Daftar SETIAP layar yang membaca tabel ini, dan di sebelah tiap layar tulis: status mana yang boleh ia tampilkan, dan kalimat apa yang boleh ia klaim. Untuk SRP2 baris terakhir daftar itu (`Umum.php:460`, `:565`) yang tidak pernah ditulis, dan dari situ lahir cap "Terverifikasi SRP2" untuk Draft yang belum pernah dikirim. Sekaligus hitung PERMUKAAN, bukan cuma titik query: `grep -rn 'action="#"' application/views/` hari ini mengembalikan 6 hasil — itu daftar fitur yang UI-nya berbohong. Kalau domainmu punya dua permukaan untuk satu aksi (`pages/umum/aduan.php:22` hidup vs `pages/umum/form_aduan.php:12` mati tapi dirender `Umum::form_aduan()`), putuskan mana yang dihapus SEKARANG, bukan setelah keduanya rusak.

4. LANGKAH 4 — DEKLARASI, ke DUA alamat yang SUDAH ADA. Jangan bikin berkas baru. (a) Helper domain (pola `application/helpers/srp2_helper.php` yang sudah ada dan sudah berisi `srp2_dokumen_persyaratan()`): daftar status + label publik + kelas badge (`pending|process|ok|reject`) + tabel transisi Langkah 2 + fungsi `boleh_diubah_pemohon($baris)`. (b) `application/config/dashboard_modules.php`: tambah `status_column` dan `owner_column` per entri (hari ini nama kolom status hanya terkubur sebagai key di dalam `pending_where`), plus `public_where` dan `editable_where` di sebelah `pending_where` yang sudah ada. Registry ADALAH berkas deklarasi — literal status di situ sah; yang dilarang adalah literal status di `application/controllers/` dan `application/views/`.

5. LANGKAH 5 — MIGRASI YANG MENEGAKKAN. Kolom status dengan himpunan nilai dibatasi (ENUM/CHECK) dan DEFAULT = status AWAL siklus hidup, bukan status "menunggu admin" (`20260701000001_add_srp2_registrations.php:26` mendefault ke 'Pending', jadi baris apa pun yang di-insert tanpa menyebut status lahir langsung di meja admin — keselamatan hari ini bergantung pada satu baris kode yang kebetulan eksplisit). Indeks pada kolom status dan kolom scope — SRP2 puluhan baris, `aduan` dan `kkn_magang_pendaftaran` bisa ribuan. Untuk kolom PII: kalau butuh UNIQUE, ikuti pola `usr_users` (ciphertext + kolom `*_lookup_hash` terpisah), JANGAN pola `srp2_registrations.nik_ktp` yang VARCHAR(16) plaintext ber-UNIQUE — bentuk itu secara desain menutup pintu ke enkripsi §9, dan menyalinnya ke warga+mahasiswa melahirkan tiga tabel PII plaintext sekaligus.

6. LANGKAH 6 — SATU FUNGSI TRANSISI DI MODEL, satu-satunya penulis kolom status. Urutan di dalamnya tetap: (1) baca baris; (2) cek pasangan (status_asal, status_tujuan, aktor) ada di tabel Langkah 2, tolak kalau tidak; (3) cek invarian status tujuan (Langkah 7); (4) susun payload LENGKAP — kolom yang wajib TERISI dan kolom yang wajib DIKOSONGKAN, dua-duanya, karena menulis kolom status saja itulah yang membuat catatan penolakan lama menempel di `Pengembang.php:198`; (5) `trans_start()` … `trans_complete()` … `return trans_status()` kalau menyentuh lebih dari satu tabel — pakai pola yang SUDAH ADA di `User_model.php:36-53`, jangan pola transaksi kedua; (6) UPDATE dengan `WHERE id AND <kolom_status> = <status_asal>` + scope. Klausa status_asal itu yang membuat `affected_rows()` jadi instrumen yang tidak ambigu, sekaligus mencegah dua tab admin saling menimpa.

7. LANGKAH 7 — INVARIAN DI HULU, BUKAN DI FORMULIR. Untuk tiap kolom yang nanti akan DISALIN endpoint reviewer ke tabel lain, tulis constraint tabel tujuannya (NOT NULL/UNIQUE/panjang) lalu tegakkan constraint yang sama di gerbang "kirim". Alasannya: pengajuan diisi sepotong-sepotong lewat banyak formulir (daftar cepat, onboarding, /akun/profil, wizard) sehingga tiap formulir bisa memenuhi §17 sambil hasil GABUNGANNYA tetap tidak sah — itu akar pemblokir #2. Prinsipnya satu kalimat: antrean reviewer tidak boleh berisi baris yang mustahil disetujui.

8. LANGKAH 8 — SATU GERBANG TULIS PEMOHON UNTUK SEMUA ENDPOINT-NYA. `boleh_diubah_pemohon()` dipanggil oleh SETIAP endpoint tulis milik pemohon — lampiran maupun data. Hari ini dokumen dikunci (`Pengembang.php:136`, dua salinan literal) sementara `Pengaturan::update_pengembang_profile()` tidak dikunci sama sekali, jadi gerbang Langkah 7 bisa dilewati: kirim dengan nama valid, lalu ganti jadi duplikat selagi Pending. Setiap UPDATE milik pemohon menyertakan `WHERE id`, tidak pernah `WHERE user_id` saja. Balas 409 untuk AJAX, flash error untuk non-AJAX, pesan sama.

9. LANGKAH 9 — SIKLUS HIDUP BERKAS: satu penulis, satu penghapus, di model yang sama. Penulis WAJIB memanggil penghapus untuk nilai lama sebelum menimpanya. Urutan baku: tulis/kunci baris DB dulu, baru pindahkan berkas — kebalikannya (`Pengembang.php:159` memindahkan seluruh loop lalu menulis DB setelahnya) membuat satu berkas gagal validasi meninggalkan semua berkas sebelumnya yatim permanen. Id yang menyusun path WAJIB variabel yang SAMA PERSIS dengan yang dipakai di WHERE — satu variabel yang sudah di-cast di pintu masuk fungsi, bukan dua. Dan di PR yang SAMA dengan migrasi FK-nya, daftarkan domain baru ke `User_model::_cleanup_owned_files()`, karena CASCADE menghapus baris berisi nama berkas dan setelah itu berkasnya tidak bisa ditemukan siapa pun (cakupan UU PDP).

10. LANGKAH 10 — TIMBAL BALIK BACA, dua arah. Untuk tiap berkas/kolom yang jadi DASAR keputusan, sebut endpoint yang membuatnya bisa dilihat oleh KEDUA pihak. Sisi pemohon: `MY_Controller::serve_private_file()` sudah ada, selama ini cuma dipakai pengelola — salin guard-nya dari pola anti-IDOR di `simpan_dokumen()`, jangan bikin jalur penyaji baru. Sisi reviewer: layar keputusan merender kolom yang jadi pembanding. Aturannya keras: kalau tidak ada endpoint untuk salah satu pihak, transisi "minta perbaikan" belum boleh diaktifkan — tanpa ini reviewer menulis "Form 4 salah" dan pemohon tidak punya cara memeriksa apa yang dulu dia kirim.

11. LANGKAH 11 — PERMUKAAN PEMBACA, semuanya dari deklarasi Langkah 4. Satu fungsi keadaan untuk SEMUA layar pemohon (pola `Auth_model::srp2_state()` yang sudah ada dan docblock-nya sudah menyebut dirinya "SATU sumber"). Halaman publik memfilter status dengan `public_where` eksplisit, tidak pernah dengan `!empty()`. Antrean reviewer memakai FILTER status dengan nilai default dari `pending_where` — registry menyuplai NILAI DEFAULT FILTER, bukan klausa WHERE mati; keduanya beda, dan `Admin_Srp2.php:26` hari ini adalah versi WHERE mati. Tidak ada cabang `else` yang menebak label. Setelah aksi tulis berhasil, klien mengambil state dari RESPONS SERVER dalam bentuk yang sama persis dengan render awal — tidak menghitung ulang sendiri (`syarat.php:437` menghitung ulang dan salah).

12. LANGKAH 12 — SATU SKRIP PERJALANAN YANG BISA GAGAL MERAH. Ini butir yang tidak boleh dipotong. Repo ini TIDAK punya `tests/` sama sekali, jadi jangan mengandaikan harness: satu berkas PHP CLI + curl, dijalankan `php docs/engineering/uji_perjalanan_<domain>.php`, `assert()` atas status HTTP dan isi respons, exit code non-nol kalau gagal. Isinya perjalanan penuh dari daftar sampai tampil di permukaan publik, PLUS tiga uji negatif yang justru jadi intinya: (a) POST transisi ilegal → ditolak tanpa menyentuh DB; (b) tulisan yang PASTI gagal di tabel kedua → pesan GAGAL, status tidak berubah, tidak ada baris parsial; (c) gerbang hulu → baris tidak lahir. Uji (b) dijalankan dengan `db_debug` FALSE (setel `$db['default']['db_debug'] = FALSE` di `application/config/database.php`, kembalikan sesudahnya) — JANGAN dengan menyetel `CI_ENV=production`, karena itu ikut mematikan tampilan error untuk semua sebab lain dan uji yang gagal karena hal lain jadi tak terlihat. Definisi selesai skripnya: balikkan satu perbaikan, jalankan lagi, skripnya MERAH. Skrip yang tidak pernah gagal tidak membuktikan apa pun.

### Pola kegagalan & aturan pencegah

| Pola | Aturan pencegah | Cara cek saat review |
|---|---|---|
| **Sukses dicetak dari posisi baris kode, bukan dari hasil tulis** | Tidak boleh ada penanda sukses pada level indentasi yang sama dengan query tulis. Keputusan yang menyentuh >1 tabel dibungkus `trans_start()`/`trans_complete()`/`trans_status()` — pakai pola yang sudah ada di `User_model.php:36-53`, jangan pola kedua. Pesan gagal menyebut sebabnya apa adanya ("Nama perusahaan sudah terdaftar di direktori"), dan redirect kembali ke halaman detail, bukan ke daftar antrean. | Untuk tiap endpoint tulis: tunjuk baris `set_flashdata('success'` atau `status:success`, telusuri ke ATAS. Kalau tidak melewati satu `if` yang menguji hasil DB (`trans_status()`, `affected_rows()`, atau baca-ulang baris) → temuan, tanpa perlu debat. Lalu hitung tabel yang disentuh satu keputusan: lebih dari satu tanpa `trans_start()` → temuan. Grep pembanding hari ini: `grep -rn "affected_rows\\|trans_start" application/` = 7 hasil, dua di antaranya cuma komentar, nol di seluruh jalur reviewer. |
| **Status berpindah tanpa ada yang membaca status asal** | Otorisasi bukan fungsi role saja, tapi fungsi (role × status_asal × status_tujuan). Guard di constructor menjawab SIAPA boleh masuk; ia secara struktural tidak bisa menjawab DARI KEADAAN APA. Tabel transisi Langkah 2 ditegakkan di server, di fungsi transisi Langkah 6, dan UPDATE-nya menyertakan status asal di WHERE. | Buka endpoint keputusan dan tunjuk baris tempat status ASAL dibandingkan. Kalau tidak bisa ditunjuk → temuan, seberapa pun lengkap whitelist status tujuannya. Cek kedua: apakah klausa UPDATE memuat `<kolom_status> = <status_asal>`; kalau tidak, dua tab admin bisa saling menimpa tanpa jejak. |
| **Baris yang mustahil disetujui boleh masuk antrean** | Validasi terakhir milik TRANSISI, bukan milik formulir — karena satu pengajuan diisi oleh banyak formulir dan tiap formulir bisa lulus §17 sambil hasil gabungannya tidak sah. Dan begitu baris masuk antrean, SELURUH endpoint tulis pemohon (lampiran maupun data) memakai satu fungsi kunci yang sama, bukan sebagian. | Daftar kolom yang endpoint reviewer SALIN ke tabel lain. Untuk tiap kolom, buka skema tabel tujuannya (NOT NULL? UNIQUE? panjang?), lalu cari di mana constraint itu ditegakkan di sisi pemohon. Tidak ketemu → temuan. Uji yang membuktikannya: POST kirim dengan nilai yang menabrak constraint tujuan; kalau baris berpindah ke status menunggu-keputusan, gerbangnya tidak ada. |
| **Permukaan publik menyaring keberadaan, bukan status — dan menautkan lewat nama** | Setiap pembacaan tabel pengajuan dari controller non-admin menyertakan `public_where` dari registry, di query yang sama. Tautan antar tabel lewat kolom FK, tidak pernah pencocokan nama — dan PR yang menambah kolom FK wajib MENCABUT jalur nama yang digantikannya, bukan hidup berdampingan. Menambah selalu bisa dicentang; mencabut tidak ada di checklist mana pun, jadi harus ditulis eksplisit. | `grep -rn '<nama_tabel_pengajuan>' application/controllers/` lalu buang hasil di `Admin_*`. Setiap sisa yang tidak punya klausa status → temuan. Cek kedua: setiap teks klaim di view ("Terverifikasi", "Bersertifikat", "Verified") harus bisa ditelusuri ke klausa status itu; kalau berhenti di `!empty()` atau `isset()` → temuan. Uji ANONIM dengan empat baris berstatus berbeda; hanya satu yang boleh memunculkan klaim. |
| **Aksesor pajangan — "satu sumber" yang dipakai dua dari dua puluh** | Nama tabel domain hanya muncul di berkas model dan di registry (`application/migrations/` tentu dikecualikan — grep-nya dibatasi ke `controllers/` dan `views/`, bukan "di luar berkas deklarasi" yang mustahil nol). Dan modelnya dibuat SEBELUM controller pertama, supaya controller pertama tidak punya pilihan selain memakainya — kalau modelnya menyusul, yang terjadi persis `srp2_state()` hari ini. | Hitung pemanggil aksesor versus jumlah titik query mentah ke tabel yang sama: `grep -rn '<nama_fungsi>' application/` lawan `grep -rn "'<nama_tabel>'" application/controllers/ application/views/`. Rasio timpang → aksesornya pajangan. Aturan review yang bisa gagal: PR yang MENAMBAH aksesor wajib mencantumkan daftar pemanggil lama yang dipindahkan; aksesor baru tanpa satu pun pemanggil lama dihapus → tolak. |
| **Selektor baca ≠ selektor tulis** | Setiap UPDATE/DELETE pada tabel yang BISA punya lebih dari satu baris per pemilik menyertakan PK, dan PK itu berasal dari baris yang sama persis dengan yang dirender pembacanya. `where('user_id')` sudah benar untuk KEAMANAN (anti-IDOR) — itulah kenapa review berhenti puas di situ; cacatnya soal identitas baris, bukan otorisasi. Kalau desainnya memang satu baris per pemilik, tegakkan di satu fungsi pembuat (Langkah 6), bukan lewat `order_by` yang tersebar. | Buka query pembaca dan query penulis untuk SATU layar berdampingan. Klausa WHERE-nya harus cocok kata per kata, ditambah `id`. Beda satu klausa → temuan. Cek turunannya: `grep -rn "order_by('id', 'DESC')" application/controllers/` — tiap hit adalah tempat "barisnya yang mana" jadi kesepakatan diam-diam. |
| **Berkas mendarat, buku besarnya tidak — dan tidak ada yang menghapusnya** | Baris DB adalah buku besar: tidak boleh ada berkas tanpa baris yang menunjuknya, dan tidak boleh ada baris yang menunjuk berkas hilang. Satu penulis, satu penghapus, penulis memanggil penghapus untuk nilai lama. Tulis/kunci baris DB dulu, baru pindahkan berkas. §17 langkah 4 mengatur MASUKNYA berkas dan itu memang utuh — yang tidak diatur adalah GANTI dan HAPUS. | Hitung berkas nyata: jumlah berkas di `private_uploads/{domain}/{id}/` harus sama dengan `COUNT(*)` baris berkas untuk id itu — untuk baris yang dokumennya SUDAH pernah diganti minimal sekali, bukan yang masih perawan. Angka inilah yang mengungkap 20 lawan 14. Cek kedua: dalam satu fungsi, apakah nilai untuk guard, untuk WHERE, dan untuk path adalah variabel yang sama persis; `is_numeric($x)` di baris atas lalu `$x` mentah di baris bawah → temuan. |
| **Migrasi menambah kolom tautan, tidak mengisi baris lama** | Migrasi yang menambah kolom tautan/status ke tabel BERISI DATA wajib membawa UPDATE backfill di migrasi yang sama. PRD pertanyaan 5 ("FK sejak migrasi pertama") bicara tentang tabel BARU dan karena itu tidak menangkap kasus ini sama sekali. Dan urutannya mengikat: backfill DULU, baru ganti join lama ke FK — kebalikannya menghapus badge dan tombol milik pihak yang SAH. | Setelah migrasi: `SELECT COUNT(*) FROM <tabel> WHERE <status> = '<final>' AND <kolom_fk> IS NULL` harus 0. Jalankan migrasinya DUA KALI berturut-turut; hasil DB harus identik (idempoten) — wajib, karena di repo ini migrasi duduk lama di branch fitur sementara production tertinggal di skema lebih lama. |
| **Kolom tanpa penulis** | Kolom yang tidak punya penulis tidak boleh ada di skema, dan kolom yang tidak dirender di layar keputusan tidak boleh diklaim sebagai dasar keputusan. Kalau reviewer tidak punya nilai pembanding di layar, modul itu belum siap dipakai — dan itu keputusan desain yang diambil di Langkah 1, bukan bug yang muncul saat audit. | Untuk tiap kolom di migrasi domain, grep namanya di `application/controllers/` dan `application/models/`. Nol penulis → hapus kolomnya atau tulis penulisnya, di PR yang sama. Sepuluh menit, sekali per domain. |
| **Pengaman yang mati diam-diam saat konfigurasinya kosong** | Fail-closed atau jangan dipasang sama sekali. Widget yang terlihat menjaga tapi tidak menjaga masuk kategori yang sama dengan sukses karangan (§17). Untuk hal yang tidak boleh bocor sama sekali (kredensial di view), pilih PENGHAPUSAN, bukan gate — penghapusan tidak punya mode gagal. | Grep setiap pemeriksaan keamanan yang dibungkus `if (!empty($config))` atau `if ($key)`. Untuk tiap satu, tanya: apa yang terjadi kalau konfigurasinya kosong — ditolak, atau dilewati? Dilewati → temuan. Untuk gate lingkungan: cek nilai DEFAULT konstantanya, bukan cabang yang kelihatan. |
| **Sesi sebagai replika role & scope, tanpa jalur invalidasi** | Untuk tiap nilai otorisasi yang direplikasi ke sesi, sebut siapa pemilik nilai aslinya DAN apa yang terjadi saat nilai itu berubah. Perbaikan termurah yang jujur: kartu penolakan memberi tahu "keluar lalu masuk lagi" — jauh lebih murah daripada menyegarkan sesi pengguna lain dari sisi admin. Yang tidak boleh: diam. | Daftar setiap nilai yang dibaca dari `$this->session->userdata()` untuk keputusan OTORISASI. Untuk tiap satu, cari endpoint yang bisa mengubah sumbernya di DB. Ada, tapi tidak ada yang menyegarkan/mem-flag sesi → temuan. |
| **Aksi berisiko tanpa bukti kepemilikan, dan pintu masuk tanpa batas laju** | Batas laju ditentukan per KELAS endpoint (tanpa login, atau menciptakan baris) dan memakai mekanisme yang SUDAH ADA di `do_login`, bukan mekanisme baru. Aksi destruktif membuktikan kepemilikan di server; konfirmasi di klien adalah kenyamanan, bukan kontrol. Konfirmasi jangan pernah berdasar kolom yang boleh NULL — hari ini akun tanpa `username` mustahil memenuhi konfirmasi hapus akun karena dibandingkan dengan literal `null`. | Dua daftar. (a) Setiap endpoint yang menghapus data, mengubah kredensial, atau memicu CASCADE: apakah ada verifikasi kepemilikan DI SERVER? POST langsung dengan curl tanpa melewati layar konfirmasi — kalau berhasil, temuan. (b) Setiap endpoint yang bisa dipanggil tanpa login atau yang menciptakan baris baru: adakah batas laju? Untuk domain warga (aduan bisa dikirim tamu) ini pemblokir, bukan hiasan — satu skrip bisa mengisi meja `admin_bidang` tanpa batas. |
| **Kegagalan yang tidak punya jalan pulih** | Sesi/CSRF kedaluwarsa punya kode dan pesan sendiri, tidak dilebur ke "koneksi bermasalah". Bentuk input yang tak terduga diperiksa SEBELUM dipakai, bukan sesudah. Dan klien mengambil state dari respons server dalam bentuk yang sama persis dengan render awal — satu bentuk data, dua tempat pakai. | Untuk setiap balasan error (terutama AJAX), baca teksnya: apakah menyebut penyebab yang benar, dan apakah menyebut satu tindakan pemulihan? Untuk state klien: sesudah aksi tulis berhasil, apakah klien memakai isi respons server, atau menghitung ulang sendiri? Menghitung ulang → temuan; kalau ia sudah salah sekali, ia akan salah lagi. |

**Rincian gejala & contoh nyata tiap pola:**

- **Sukses dicetak dari posisi baris kode, bukan dari hasil tulis** — Nilai balik `insert()`/`update()` tidak pernah masuk ke satu pun percabangan. Pesan sukses berada di jalur lurus sesudah query, jadi ia dicetak oleh fakta "eksekusi sampai ke baris ini". Kalau operasinya menyentuh lebih dari satu tabel, kegagalan di tulisan pertama tidak menghentikan yang kedua dan sistem berakhir di keadaan yang bukan sebelum maupun sesudah. Di production `db_debug` FALSE (`database.php:85`) seluruh rantainya senyap. *Contoh: `application/controllers/Admin_Srp2.php:117-145` — insert direktori, `insert_id()` dipakai mentah, update registrasi, flash sukses; nol pemeriksaan, nol transaksi. Nama bentrok UNIQUE (66 nama seed) → `insert_id()` 0 → UPDATE ditolak FK 1452 → status tetap Pending, tapi flash tetap "Pengajuan diterima". Pola sama di `Admin_Srp2.php:178` (`save()`), `Admin_Kemitraan.php:66`, `Admin_Kabkota.php:54`, `Admin_Users.php:82`.*
- **Status berpindah tanpa ada yang membaca status asal** — Endpoint keputusan memvalidasi status TUJUAN dengan whitelist rapi, tapi tidak pernah membaca status ASAL — jadi semua transisi legal, termasuk yang mustahil menurut proses. Barisnya diambil dari DB tapi hanya dipakai menyalin field. Satu-satunya penjaga ada di view, jadi satu POST rakitan menerobos seluruh aturan alur. *Contoh: `application/controllers/Admin_Srp2.php:82-90` — whitelist `['Diterima','Ditolak','Draft']` rapi, `$reg` dibaca di `:89`, dan `$reg->status_verifikasi` tidak pernah diuji sebelum UPDATE di `:138`. Draft berisi 0 dokumen bisa diterbitkan ke direktori publik lengkap dengan `reviewed_by`. Penjaganya cuma kondisi di `application/views/admin/srp2/detail.php:52` dan `:69`. Pola identik sudah tersalin ke `Admin_Kemitraan.php:60`, `Admin_Bidang.php:63`, `Admin_Kabkota.php:57`.*
- **Baris yang mustahil disetujui boleh masuk antrean** — Syarat yang menentukan apakah keputusan reviewer bisa DIEKSEKUSI tidak divalidasi di hulu. Antrean berisi baris yang secara fisik tidak mungkin disetujui, dan memperbaiki endpoint reviewer saja hanya mengubah kegagalan senyap jadi kegagalan berisik — reviewer tetap buntu. *Contoh: `application/controllers/Pengembang.php:191` (`kirim_pengajuan`) — satu-satunya syarat kirim adalah 14 dokumen. `nama_perusahaan` boleh NULL, boleh string kosong, boleh duplikat persis nama direktori — padahal `Admin_Srp2.php:122` memakainya sebagai kunci UNIQUE tabel tujuan. Diperparah `Pengaturan::update_pengembang_profile()` yang tidak memeriksa status sama sekali, jadi gerbang mana pun di titik kirim bisa dilewati setelahnya.*
- **Permukaan publik menyaring keberadaan, bukan status — dan menautkan lewat nama** — Halaman publik menyimpulkan "terverifikasi" dari ADA/TIDAK-nya baris, bukan dari nilai statusnya, dan menghubungkan pengajuan ke direktori lewat pencocokan nama string. Hasilnya klaim resmi tentang pihak ketiga yang tidak punya jalur rollback. *Contoh: `application/controllers/Umum.php:565` — `get_where('srp2_registrations', ['nama_perusahaan' => $nama])` tanpa filter status apa pun; view `detail_pengembang.php:477` cuma `if (!empty($local_data))` lalu mencetak "Terverifikasi SRP2" + badge Verified. Draft yang belum pernah dikirim tampil bercap, tanpa login. `Umum.php:460` mengambil SELURUH tabel lalu memakai kolom `nib` sebagai `sp2_status`. Pencocokan nama yang sama masih hidup di `Pengembang.php:99` padahal kolom FK `certified_developer_id` sudah dibuat migrasi `20260701000014` justru untuk menggantikannya.*
- **Aksesor pajangan — "satu sumber" yang dipakai dua dari dua puluh** — Sebuah fungsi dideklarasikan sebagai satu-satunya sumber, lengkap dengan docblock yang menjelaskan bug yang dia perbaiki, lalu dipakai 1-2 pemanggil sementara sisanya tetap query mentah. Bug yang sama muncul lagi di permukaan berikutnya, dan tiap kemunculan diperlakukan sebagai bug baru. Cirinya: memperbaiki bug di satu layar tidak otomatis memperbaikinya di layar lain. *Contoh: `application/models/Auth_model.php:232` (`srp2_state()`, docblock: "SATU sumber untuk semua yang butuh tahu sudah sampai mana orang ini") dipakai `Pengembang::syarat()` dan cabang AJAX `Auth::do_login()` saja. `Pengaturan::profil()` (`:56`, `:135`), `Pengaturan::update_pengembang_profile()` (`:149`), dan `Pengembang::dokumen()` (`:118-122`) semuanya query sendiri. Saudaranya di registry: `pending_where` di `dashboard_modules.php:112` disebut "deklarasi tunggal", tapi query antrean sebenarnya menulis ulang literalnya di `Admin_Srp2.php:26`.*
- **Selektor baca ≠ selektor tulis** — Form menampilkan satu baris, tombol Simpan menulis ke baris lain atau ke semua baris. Tidak pernah error, tidak pernah terlihat di layar penulis — yang berubah adalah baris yang tidak sedang dilihat siapa pun. *Contoh: `application/controllers/Pengaturan.php` — tiga selektor untuk satu layar: `:135` `where('user_id')->order_by('id','DESC')->row()` (form menampilkan baris TERBARU), `:149` `get_where(['user_id'])->row()` tanpa `order_by` (validasi keberadaan memakai baris PERTAMA), `:181` `where('user_id')->update()` tanpa `id` sama sekali (menimpa SEMUA baris user itu). Dan tidak ada UNIQUE yang menjamin satu baris per user.*
- **Berkas mendarat, buku besarnya tidak — dan tidak ada yang menghapusnya** — Ada fungsi yang menulis berkas dan fungsi yang menyajikannya, tidak ada yang bertanggung jawab menghapusnya. Baris DB ditimpa, nama berkas lamanya lenyap bersamanya, berkasnya tinggal di disk selamanya — tak terhitung, tak terjangkau, tak terhapus saat akun dihapus. Varian kedua: id yang menyusun path bukan id yang di-query, jadi berkas mendarat di direktori yang tidak pernah dibaca siapa pun. *Contoh: `application/controllers/Pengembang.php:165` — `db->replace('srp2_documents', $row)` menghapus baris lama beserta `stored_name`-nya lalu menyisipkan baru, tanpa `unlink()`. Terukur: 20 berkas fisik lawan 14 baris DB. `User_model::_cleanup_owned_files()` hanya bisa menghapus nama yang MASIH tercatat, jadi yatimnya selamat dari hapus akun. Varian id: `Pengembang.php:127` guard `is_numeric($id)`, `:129` query `(int) $id`, `:147` `$id` MENTAH ke `private_upload_dir()`, dan `private_upload_helper.php:55` membuang karakter non-alfanumerik sehingga "7.0" jadi direktori "70".*
- **Migrasi menambah kolom tautan, tidak mengisi baris lama** — Kolom FK baru ditambahkan sebagai nullable ke tabel yang sudah berisi data, tanpa UPDATE untuk baris lama. Semua yang bisa dicek otomatis benar — tipe, constraint, indeks — dan yang salah adalah ISI. NULL lalu berarti dua hal berbeda ("belum diputuskan" untuk baris baru, "terlewat" untuk baris lama) yang tidak ada kode mana pun bisa membedakan. *Contoh: `application/migrations/20260701000014_add_srp2_certified_developer_link.php` menambah `certified_developer_id` + FK, tanpa satu pun UPDATE untuk baris berstatus 'Diterima'. Registrasi id=7 berstatus Diterima, kolomnya NULL, dan tidak ada baris direktori bernama sama — dashboardnya bilang Diterima sementara dia tidak ada di direktori publik.*
- **Kolom tanpa penulis** — Kolom ada di skema dan dirender di layar, tapi tidak ada satu baris kode pun yang pernah menulisinya. Layar menampilkan kosong selamanya dan orang menyangka datanya "belum diisi" padahal tidak pernah bisa diisi. *Contoh: `nama_peserta`, `nik_ktp`, `jabatan`, `no_whatsapp` di `srp2_registrations` (migrasi `20260701000001` baris 12-15, `nik_ktp` bahkan ber-UNIQUE). `grep -rn 'nik_ktp' application/controllers/ application/models/` = NOL hasil. Formulir 12-field lama diarsipkan tanpa pengganti.*
- **Pengaman yang mati diam-diam saat konfigurasinya kosong** — Sebuah pengaman dilewati SELURUHNYA kalau kuncinya kosong atau kalau sebuah konstanta tidak diset. Di lingkungan pengembangan pengaman itu selalu mati — jadi "sudah diuji" berarti "diuji tanpa pengaman", dan tidak ada yang tahu. *Contoh: `application/controllers/Auth.php:250` — verifikasi reCAPTCHA dibungkus `if (!empty($this->recaptcha_secret_key))`, dan di `.env` lokal kuncinya memang kosong. Sekeluarga: `index.php:56` mendefault `ENVIRONMENT` ke `development` kalau `CI_ENV` tidak diset, yang membuat gate `ENVIRONMENT === 'development'` jadi FAIL-OPEN — server yang lupa menyetel `CI_ENV` justru memajang blok yang mau digerbangi. Ditambah `config/config.php:414` `cookie_secure = FALSE` meski production HTTPS, dan verifikasi TLS dimatikan unconditional di semua klien HTTP keluar (§18).*
- **Sesi sebagai replika role & scope, tanpa jalur invalidasi** — Otorisasi dibaca dari sesi yang disetel saat login, sementara sumbernya di DB bisa diubah kapan saja oleh orang lain — dan tidak ada yang menyegarkan sesinya. Ini lubang di pertahanan yang justru diandalkan §17. *Contoh: `MY_Controller.php:530` dan `:563` mengambil `kabupaten_id`/`bidang_kode` DARI SESI. `Admin_Users::update_role()` menulis `role`+`kabupaten_id`+`bidang_kode` ke DB dan tidak menyentuh sesi mana pun. Akibat (a): admin ter-scope yang dipindah wilayah terus bekerja pada wilayah LAMA selama sesinya hidup — ini menembus seluruh pertahanan "WHERE ganda scope". Akibat (b): pengguna yang baru diangkat jadi pengembang ditolak wizard dengan kartu "bukan akun pengembang" tanpa diberi tahu bahwa dia cuma perlu keluar-masuk.*
- **Aksi berisiko tanpa bukti kepemilikan, dan pintu masuk tanpa batas laju** — Aksi destruktif atau bernilai tinggi hanya memeriksa method POST; konfirmasinya seluruhnya di klien. Dan batas laju dipasang per endpoint yang kebetulan diingat, bukan per KELAS endpoint, sehingga endpoint yang ditulis belakangan lolos tanpa apa-apa. *Contoh: `Pengaturan.php:227` ganti password tanpa memverifikasi password lama; `:255` `delete_account()` hanya memeriksa method POST, konfirmasi ketik-nama sepenuhnya di klien — dan hapus akun memicu FK CASCADE ke seluruh pengajuan. Batas laju: `Auth::do_login()` punya penghitung + lockout (`Auth.php:101-120`), `do_register` tidak punya apa pun; §18 menambah `Umum::report_komentar()` tanpa login & tanpa dedup dan `Chat::api_bot()` public-routable tanpa login/CSRF/rate limit.*
- **Kegagalan yang tidak punya jalan pulih** — "Kegagalan" diperlakukan sebagai sinonim "query gagal", sehingga seluruh kelas kegagalan lain tidak punya penanganan: token kedaluwarsa, bentuk input tak terduga, penghitung yang tidak pernah direset. Pesannya menyebut sebab yang salah dan tidak menyebut satu pun tindakan pemulihan. Sepupunya: state klien tidak disinkronkan dari respons server sesudah aksi berhasil. *Contoh: `syarat.php:532` — token CSRF kedaluwarsa di tengah unggah tampil sebagai "Koneksi terputus" dengan tombol Ulangi yang gagal terus; satu-satunya jalan keluar reload, dan itu tidak diberitahukan. `$_FILES['name']` berbentuk array memicu TypeError 500 SEBELUM pemeriksaan error. Penguncian akun tidak mereset penghitung, jadi satu salah ketik langsung mengunci 15 menit lagi. State klien: `syarat.php:437` `clearFile()` menyetel `'idle'` bukan `'done'`, sehingga membatalkan Ganti pada dokumen yang SUDAH di server menurunkan hitungan ke 13 dan mengunci tombol Kirim.*
