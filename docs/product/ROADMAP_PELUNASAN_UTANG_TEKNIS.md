# Roadmap Pelunasan Utang Teknis

> Disusun 29 Juli 2026 dari 22 label inventaris prioritas A/B/C, lalu dikoreksi
> dengan S1–S9. Argumen urutannya ada di
> [`PRD_PELUNASAN_UTANG_TEKNIS.md`](./PRD_PELUNASAN_UTANG_TEKNIS.md) §4 — roadmap ini
> menjalankannya, tidak mengulang alasannya.

**Status:** **DRAFT TERKOREKSI — dilarang push/migrasi production sebelum gate
rilis §Runbook terpenuhi.**

> **Koreksi pasca-commit `ac42941`:** angka 22 adalah label inventaris awal, bukan
> jumlah total utang. S1–S9 ditambahkan ke tahap S0. U0 dan U1 adalah satu unit
> rilis atomik; menghapus alias `/sikaper` saja tidak menutup controller CI3; dan
> seluruh harness wajib mengisolasi DB serta `PRIVATE_UPLOADS_PATH`.

> **Pesan untuk agent pelaksana:** jangan push branch auto-deploy, jangan menjalankan
> runner fresh lama, dan jangan menyentuh perubahan agent lain. Mulai di holding
> branch, baca P0 serta Runbook sampai habis, kerjakan satu paket yang dapat
> dibuktikan, lalu minta review independen sebelum meminta izin rilis.

**Urutan tahap TIDAK mengikuti urutan kode temuan.** A1 tidak dikerjakan lebih dulu
karena namanya A1. Susunannya: yang mengubah sifat kegagalan seluruh sistem → yang
menjadi berbahaya justru karenanya → pintu anonim termurah → saluran keluar →
kejujuran permukaan publik → keputusan besar yang tertunda.

Satu pintu pengujian memanggil dua runner: **`docs/engineering/uji_utang_teknis.php`**
untuk PHP/HTTP/DB dan runner browser untuk S1/S2. Curl tidak menjalankan JavaScript;
History API dan hitungan request tidak boleh dinyatakan lulus dari runner PHP.

Runner belum boleh dijalankan sebelum memakai worktree+vhost Apache uji terpisah,
DB ber-prefix uji, `.env` uji dengan `PRIVATE_UPLOADS_PATH` unik, dan
`SITE_URL` host uji unik, serta `SetEnv CI_ENV testing` pada vhost tersebut. Child
CLI juga harus menerima `CI_ENV=testing`; environment child tidak diwarisi Apache.
`.env` dibuat minimal dari nol, satu definisi per key, memakai key/pepper sintetis
unik—jangan menyalin secret dev/production. Set `SIMPERUM_MODE=simulation`, arahkan
Gemini ke stub lokal, karantina endpoint Chat, dan deny network egress selain stub.
Tolak redirect/request keluar origin host uji. Larang production/upstream nyata tanpa
flag izin, gunakan recovery marker,
bersihkan kegagalan biasa di `finally`, dan bersihkan resource yatim hard-kill pada
run berikutnya. Mutation proof tetap di salinan sekali pakai. Runner yang hanya
mengganti `DB_NAME` tidak memenuhi kontrak ini.

**Urutan eksekusi wajib (mengalahkan urutan nomor heading):**
**P0 → logika U1 → konfigurasi U0 → uji+rilis U0/U1 atomik → S0 sisanya → U2–U6.**

## P0 — Preflight keselamatan

**Status saat dokumen ini diperbaiki: BELUM TERPENUHI.** Edit dokumentasi di
worktree branch auto-deploy yang bercampur pekerjaan domain lain bukan holding
worktree dan tidak memberi izin memulai implementasi.

1. Buat holding branch yang tidak auto-deploy dan pastikan perubahan agent lain
   tidak ikut.
2. Kirim ledger sebelas keputusan §6 PRD kepada user pada hari pertama agar jawaban
   A/B/C/S dapat berjalan paralel dengan U1. B10 sudah diputuskan dan tidak ditanya
   ulang sebagai “belum diverifikasi”.
3. Perbaiki S5/harness pada worktree+vhost uji terpisah. `.env` vhost uji memuat
   `SITE_URL` unik, DB/upload sementara, mode SIMPERUM simulasi, key/pepper 64-hex
   sintetis, dan `GEMINI_API_URL` stub lokal. Jangan salin `.env` utama; tepat satu
   definisi tiap key. Endpoint Chat tetap dikarantina dan egress non-stub ditolak.
   Redirect/form/action dan request aplikasi wajib same-origin; asset CDN yang sudah
   ada di layout di-intercept ke fixture lokal atau diblokir eksplisit—tidak perlu
   divendor dalam roadmap ini. Buat recovery marker
   untuk hard-kill. Jangan menjalankan
   runner lama untuk membuktikan fix ini; gunakan pemeriksaan statis + fixture kecil.
4. Tutup S8 preflight: selaraskan status dokumen, set `migration_version` ke migrasi
   tertinggi dari **seluruh `application/migrations/*.php` yang tracked di commit
   rilis**, pastikan
   tooling tidak memanggil `migration->current()`, dan buat check yang merah bila
   versinya dimundurkan.
5. Siapkan vhost lokal utama `CI_ENV=development` dan buktikan login HTTP lokal tetap
   lintas-request.
6. Baru implementasikan logika U1. Setelah seluruh uji U1 hijau dengan
   `db_debug=FALSE`, implementasikan U0 dan jalankan paket penuh.

---

## U0 — Siapkan environment fail-closed (JANGAN dirilis tanpa U1)

**Tujuan.** Nol setelan keamanan yang hidup hanya di server, tanpa membuat
`.htaccess` bersama memaksa localhost menjadi production.

**Butir yang disiapkan:** B1, B11. Keduanya baru **ditutup** setelah paket U0+U1
dirilis dan diverifikasi bersama.

**Isi:**

- **[B1] Ubah default `index.php:56` menjadi `production` bila `CI_ENV` hilang.**
  Ini fail-closed: deploy yang kehilangan konfigurasi tetap aman.
- **[Lokal wajib eksplisit]** Sebelum perubahan kode, vhost/konfigurasi Apache XAMPP
  yang tidak ikut Git menyetel `SetEnv CI_ENV development`. Dokumentasikan di
  `SETUP_DATABASE.md`. Jangan membaca `CI_ENV` dari `.env` dengan urutan bootstrap
  sekarang karena `.env` baru dimuat sesudah konstanta ditetapkan.
- **[Larangan] Jangan taruh `SetEnv CI_ENV production` tanpa kondisi di `.htaccess`
  repo.** Berkas yang sama dipakai localhost dan akan mengubah seluruh instalasi
  menjadi production.
- **[B11] Setel cookie berdasarkan environment:**
  `cookie_secure = (ENVIRONMENT === 'production')`. Jangan `TRUE` global karena
  browser akan menolak cookie sesi Secure pada localhost HTTP.
- **[Containment darurat, opsional, izin user]** konfigurasi server production boleh
  disetel manual ke `production` untuk menghentikan bocoran sebelum rilis atomik,
  tetapi dicatat sebagai sementara dan tetap diikuti U1 secepatnya.

**Berkas yang disentuh:** `index.php`, `application/config/config.php`,
`docs/engineering/SETUP_DATABASE.md`. `.htaccess` repo tidak diberi environment
global.

**Cara membuktikan:**

1. Lokal HTTP tetap dapat login lintas-request dan cookie sesi **tidak** memiliki
   atribut `Secure`; diagnostic lokal memastikan environment `development`.
2. Salinan aplikasi tanpa `CI_ENV` memakai environment `production` dan cookie
   `Secure`.
3. Canary sintetis pada vhost uji sengaja memicu notice dan galat DB: respons tidak
   memuat warning, SQL, atau path absolut. Smoke production memeriksa beberapa route
   aktif untuk pola yang sama; `/Umum/info_rumah` bukan canary permanen karena S9
   boleh mencabutnya.
4. **Uji balik:** di salinan/worktree sekali pakai, kembalikan default ke
   `development` dan hilangkan env; canary membocorkan error lagi.

**Risiko regresi.** Tinggi dan terarah — inilah tahap paling berbahaya di seluruh
roadmap justru karena perbaikannya benar:

- **U0 dilarang dipush sendiri.** Setelah environment production aktif, kegagalan
  tulis menjadi senyap. `db_debug`
  ikut mati (`database.php:85`: `'db_debug' => (ENVIRONMENT !== 'production')`).
  Enam titik flash "berhasil" tanpa cek (A5) mulai berbohong, dan rate limit forum
  (C1b) dapat terlewati. Selesaikan dan uji U1 di working tree yang sama, lalu buat
  satu commit/rilis.
- Developer tanpa konfigurasi vhost lokal akan kehilangan tampilan error. Preflight
  lokal wajib selesai sebelum mengubah default.

**Beban.** Menit di sisi repo. Bagian production menunggu user.

---

## U1 — Tutup jalur tulis senyap, lalu rilis U0+U1 secara atomik

**Tujuan.** Tidak ada layar yang mengatakan “berhasil” tanpa memeriksa hasil tulis,
forum benar-benar bisa diposting, dan perubahan environment U0 tidak pernah hidup
sendirian di production.

**Butir yang ditutup:** A5, C1, C1b (verifikasi).

**Isi:**

- **[C1] Satu baris di `application/helpers/forum_helper.php:75`.** Helper menerima
  `'diskusi'`/`'komentar'` lalu memakainya mentah sebagai nama tabel di
  `count_all_results($table)`; tabel nyatanya `forum_diskusi`/`forum_komentar`.
  **Perbaikannya di callee, bukan di dua pemanggil** (`Umum.php:211`, `Umum.php:332`) —
  docblock helper sendiri (`:64`) menyatakan menerima kunci domain
  (`@param string $table 'diskusi' atau 'komentar'`), jadi pemanggilnya sudah benar
  dan helpernya yang melanggar kontraknya sendiri. Pola pemetaannya sudah ada di repo,
  di kelas yang mengelola tabel yang sama: `Forum_model.php:128`
  (`$table = ($target_type === 'diskusi') ? 'forum_diskusi' : 'forum_komentar';`).
  **Salin, jangan rancang ulang.** Satu tempat, dua pemanggil ikut benar sekaligus.
- **[A5] Enam titik flash sukses tanpa memeriksa hasil tulis.** `Umum.php:270`,
  `Umum.php:439`, `Admin_Users.php:82`, `Admin_Users.php:139`, `Admin_Srp2.php:296`,
  `Admin_Content.php:44`. Yang paling berbahaya diverifikasi sendiri:
  `Admin_Users.php:139-140` — `$this->db->insert('usr_users', $payload);` langsung
  diikuti `set_flashdata('success', 'Akun staff baru berhasil dibuat.')`, sementara
  `usr_users.email` ber-UNIQUE. Setelah U0, email duplikat = INSERT ditolak senyap +
  superadmin diberi tahu akunnya jadi. Polanya sudah dilarang §19 ("tidak boleh ada
  penanda sukses pada level indentasi yang sama dengan query tulis") dan pola
  benarnya sudah ada di `User_model.php:36-53`. Untuk aksi satu tabel cukup periksa
  nilai balik / `affected_rows()`; untuk yang menyentuh lebih dari satu tabel,
  `trans_start()`/`trans_complete()`/`trans_status()`.
- **[C1b] Verifikasi ulang perilaku dengan `db_debug` FALSE** di DB dan salinan
  konfigurasi worktree/vhost uji. Jangan menyunting `database.php` utama dan jangan
  memakai production sebagai instrumen tes.
- **[Gate rilis]** setelah seluruh assert U0+U1 hijau, commit sebagai satu unit.
  Tidak ada push per-tahap. Terapkan runbook production di bagian akhir dokumen.

**Berkas yang disentuh:** `application/helpers/forum_helper.php`,
`application/controllers/Umum.php`, `application/controllers/Admin_Users.php`,
`application/controllers/Admin_Srp2.php`, `application/controllers/Admin_Content.php`,
`docs/engineering/uji_utang_teknis.php`.

**Cara membuktikan:**

1. POST `Umum/tambah_aksi` dan `Umum/balas_aksi` sebagai user login → 200, dan
   `SELECT COUNT(*)` di `forum_diskusi`/`forum_komentar` naik tepat 1 masing-masing.
2. **Dengan `db_debug` FALSE**, posting ke-6 dalam satu jam → **DITOLAK**. Kalau lolos,
   `count_all_results()` masih mengembalikan FALSE di suatu tempat dan rate limit
   forum masih fiktif — itu C1b yang belum tertutup, bukan uji yang gagal.
3. **Dengan `db_debug` FALSE**, POST `Admin_Users` buat akun staff dengan email yang
   sudah ada → flash **BERBUNYI GAGAL** dan menyebut sebabnya; `SELECT COUNT(*) FROM usr_users`
   tidak bertambah.
4. Enam titik A5 masing-masing dipaksa gagal di DB uji → tidak satu pun memberi
   flash sukses dan tidak ada transaksi parsial.
5. **Uji balik (ini definisi selesainya):** kembalikan `'diskusi'` mentah ke
   `forum_helper.php:75`, jalankan skrip → **MERAH**. Lalu buang satu pemeriksaan
   hasil tulis di `Admin_Users.php`, jalankan lagi → **MERAH**. Skrip yang tidak
   pernah gagal tidak membuktikan apa pun.

**Risiko regresi.**

- **C1 mengubah perilaku production, bukan cuma memperbaiki lokal** (catatan F2
  inventaris). Sesudah U0 + U1, forum yang sebelumnya melewati rate limit diam-diam
  akan mulai menolak — pengguna yang terbiasa posting bebas akan melihat penolakan
  baru. Itu perilaku BENAR, tapi ia akan dilaporkan sebagai bug.
- A5 mengubah pesan sukses menjadi pesan gagal di jalur yang selama ini "selalu
  berhasil". Sebagian kegagalan yang selama ini tak terlihat akan muncul sekaligus.
  Itu utang lama yang menagih, bukan regresi — tapi bedakan keduanya sebelum panik.
- `Admin_Srp2.php:296` bersinggungan dengan roadmap pengembang T1a/T1b yang menyentuh
  berkas yang sama. Kalau T1a sudah berjalan di branch lain, koordinasikan; jangan
  memperbaiki dua kali dengan dua pola.

**Beban.** C1 satu baris. A5 enam titik kecil tapi tersebar di lima berkas. Satu sesi.

---

## S0 — Stabilkan alur terbaru dan alat pembuktiannya

**Tujuan.** Klaim “alur Warga/UI selesai” kembali sesuai kode nyata, dan tidak ada
harness yang dapat merusak data/file pengembangan.

**Butir yang ditutup:** S1, S2, gate bukti simulasi S3, S5, S6, S8, S9, serta
kejujuran UI S4. Cleanup/retensi S4 dan implementasi audit S7 menunggu keputusan #9;
dua gate kondisional S3 menunggu keputusan #11.

**Urutan internal:**

1. **[S5 — prasyarat P0]** verifikasi runner memakai worktree+vhost Apache uji,
   `.env` uji berisi DB/upload sementara, recovery marker hard-kill, dan tidak pernah
   menyentuh root upload atau `.env` dev. Environment child saja tidak cukup.
2. **[S1]** portal dan admin memanggil `history.replaceState()` untuk entry awal
   sebelum `pushState()` pertama. `popstate` harus memuat URL entry walau state
   kosong. Jangan membuat router baru; perbaiki dua loader yang sudah ada.
3. **[S2]** hormati `data-no-page-transition` sebelum `fetch`; batasi loader ke
   tautan partial yang dikenal atau kontrak response partial. Full-document,
   download, file privat, dan aksi ber-side-effect langsung memakai navigasi native.
4. **[S3]** pakai matriks simulasi `SIM-2026-01` yang sudah dikunci di PRD §3.5
   sebagai satu sumber server+UI. Submit membaca ledger assessment versi aktif dan
   memakai resolver storage yang sama dengan `files_with_storage_owner()`: revisi
   dapat menunjuk file fisik assessment lama melalui `storage_assessment_id`.
   `realpath` wajib tetap di root privat; file harus `is_file`+`is_readable`, ukuran
   dan SHA-256 cocok dengan ledger. Validasi hanya `file_kind` wajib untuk track aktif:
   kind yang tidak relevan diabaikan, sedangkan KTP/KK yang overlap boleh dipakai
   ulang. Jangan menambah `track_at_upload` atau memaksa reupload tanpa kebijakan
   freshness resmi. Dua bukti calon-lahan kondisional belum menjadi gate sampai
   keputusan #11; jangan menebak trigger-nya.
5. **[S6]** jalur `Admin::update_status()` memakai policy
   `admin_queue_decision` yang sama dengan Admin Kab/Kota, dimensi akun+objek.
6. **[S7 — BLOCKED keputusan #9]** setelah retensi audit ditetapkan, tambah migrasi
   audit. Refresh SIMPERUM memakai `attempt_id` unik: `requested` commit sebelum
   network call; `succeeded` atomik dengan snapshot/profile yang disimpan; `failed`
   mencatat kelas galat tanpa payload PII. Canonical diff plaintext memakai domain-
   separated HMAC `KPKP_AUDIT_PEPPER` (64 hex → binary key) dan golden vector, bukan
   SHA/ciphertext AES-GCM. Cache hit yang me-reencrypt nilai sama tidak boleh tampak
   sebagai perubahan. `sf_riwayat_keputusan_antrean` tetap sumber transisi; audit
   generik hanya mereferensikan riwayat keputusan.
7. **[S9]** karena `Umum/info_rumah` adalah halaman legacy rusak dan tidak punya
   pemanggil internal, pilihan default paling kecil adalah mencabut method+view.
   Kalau user menyatakan masih dibutuhkan, render lewat layout aktif dan buat
   perjalanan fungsionalnya.
8. **[S8 — prasyarat P0]** verifikasi status production/urutan rilis sudah selaras.
   Setiap commit migrasi menyetel `migration_version` ke nomor tertinggi dari seluruh
   berkas migrasi yang tracked di commit; tidak ada konsep “di luar scope” bagi
   `latest()`. Check juga menolak worktree kotor/untracked dan memastikan tooling
   tidak memanggil `migration->current()`.
9. **[S4]** segera ganti janji UI “Hapus Akun Secara Permanen” dengan dampak yang
   benar: akun/login dihapus, data layanan mengikuti kebijakan retensi. Cleanup DB,
   audit, backup, snapshot, dan file fisik tetap **BLOCKED** sampai keputusan #9.

**Berkas utama:** `application/views/layouts/footer.php`,
`assets/js/admin-progressive.js`, `application/views/pages/umum/forum.php`,
`application/models/Housing_assessment_model.php`, `application/controllers/Warga.php`,
`application/views/pages/warga/pendataan.php`, `application/controllers/Admin.php`,
`application/models/User_model.php`, `application/controllers/Pengaturan.php`,
`application/views/pages/pengaturan/profil.php`, `application/controllers/Umum.php`,
`application/libraries/Simperum_gateway.php`, `docs/engineering/uji_warga_fresh_r7.php`,
konfigurasi migrasi, serta dokumentasi status yang bertentangan. Tabel audit baru
hanya masuk setelah keputusan #9.

**Cara membuktikan:**

1. Runner browser portal dan admin: A→B→Back→Forward; URL, judul, konten, dan menu
   aktif selalu cocok.
2. Satu klik detail forum menghasilkan satu request dan `view_count` naik satu.
3. Setiap cabang Warga: hilangkan satu bukti wajib simulasi atau biarkan ledger
   menunjuk file hilang → submit ditolak; lengkapi matriks → berhasil tepat sekali.
4. Runner fresh memakai fixture ID yang sama dengan dev; hash folder upload dev
   sebelum/sesudah identik. Simulasi hard-kill meninggalkan hanya resource uji
   terisolasi dan run berikutnya membersihkannya.
5. Request keputusan ke-ambang+1 pada kedua role admin → `429` + `Retry-After`.
6. Setelah keputusan #9, `requested` dan hasil refresh punya `attempt_id` sama;
   sukses+snapshot/profile atomik, gagal hanya mencatat kelas galat. Perubahan
   PII/submit lahir satu kali dalam transaksi sumber, keputusan mereferensikan satu
   riwayat transisi, dan cache hit nilai sama menghasilkan nol diff PII. Payload tidak
   memuat NIK/alamat/nama; mutation HMAC/correlation membuat harness merah.
7. `/Umum/info_rumah` 404 jika dicabut atau 200 dengan layout lengkap jika
   dipertahankan—tidak cukup hanya menyembunyikan warning.
8. Check S8 membandingkan config dengan migrasi tracked tertinggi dan menemukan nol
   `migration->current()`; menurunkan versi di salinan membuatnya merah.
9. Uji balik S1, S3, S5, dan S6 masing-masing membuat harness merah.

**Risiko regresi.** S3 dapat menahan pengajuan demo yang sebelumnya lolos tanpa
bukti; sediakan fixture lengkap. S1/S2 menyentuh navigasi global—uji desktop dan
mobile. S4 menyentuh hak data dan tidak boleh ditebak agent.

---

## U2 — Tutup pintu anonim yang paling murah dieksploitasi

**Tujuan.** Tidak ada endpoint yang bisa dipanggil anonim untuk menguras kuota,
menahan worker PHP, atau menyensor komentar orang lain.

**Butir yang ditutup:** B2, gerbang+dedup B3, B6 (dan konsekuensinya pada butir E).
U2 selalu ledger-only dan tidak mengubah visibilitas komentar.

**Isi:**

- **JANGAN targetkan CSRF.** B12 sudah membuktikan token bisa diambil anonim lewat
  satu GET (`csrf_regenerate=FALSE`, double-submit). B12 ada di inventaris justru
  untuk mencegah perbaikan salah sasaran. Yang menutup: **guard login + rate limit**.
- **JANGAN bikin mekanisme rate limit baru.** `application/config/rate_limits.php`
  sudah berisi 8 policy dengan dimensi `ip`/`account`/`nik`/`object`, dipakai lewat
  `rate_limit_consume()` atau pasangan `rate_limit_inspect()`+`rate_limit_hit()`,
  lalu `rate_limit_reject()` (tabel `sys_rate_limits`). `Program::cek_tiket()` memakai
  pola inspect/hit; Warga/admin memakai policy registry yang sama.
  B3 menambah **entri policy**, bukan mekanisme (§17 poin 15). B2 tidak memerlukan
  policy setelah tidak routable.
- **[B2] Containment Chat sebelum keputusan #7.** Jadikan `api_bot()` non-public,
  tetapi jangan berhenti di sana: `kirim_pesan_lanjutan()` tetap publik dan
  memanggilnya sehingga kuota Gemini masih dapat dikuras. Sampai U5 diputuskan,
  tiga endpoint eksternal Chat membalas 404 dan widget disembunyikan. Containment
  ini reversibel; tidak menghapus controller/model dan tidak menentukan pilihan
  “bangun” versus “cabut”. Cabut juga tiga pengecualian Chat dari `csrf_exclude_uris`
  agar pembukaan kembali tidak otomatis fail-open. Untuk mutation proof saja,
  pindahkan base URL Gemini hardcoded ke `GEMINI_API_URL` dengan default resmi;
  `.env` uji menunjuk stub localhost dan egress eksternal tetap ditolak.
- **[B3] `Umum::report_komentar()` (`Umum.php:374`): guard login + dedup.** Guard-nya
  sudah ada 16 baris di bawah, di berkas yang sama: `Umum::toggle_like()` (`:390-395`)
  memanggil `is_logged_in()` dan membalas `'Login required'`. Salin. Untuk dedup,
  bentuk datanya juga sudah ada: `forum_likes` (`user_id` + `target_type` +
  `target_id`, dipakai `Forum_model::toggle_like()` `:121-126`) adalah persis bentuk
  "satu aksi per pengguna per objek" yang dibutuhkan — hari ini
  `Forum_model::report_komentar()` (`:96-100`) hanya menaikkan `report_count` tanpa
  mencatat siapa pelapornya, jadi 5 POST dari satu orang sama nilainya dengan 5 orang.
  Buat `forum_laporan_komentar` dengan FK dan
  `UNIQUE (id_komentar, user_id)`; transaksi mengunci baris komentar induk
  (`SELECT ... FOR UPDATE`), insert ledger, menghitung ulang laporan unik, lalu
  menjaga `is_deleted` tetap. Dua request paralel tidak boleh lolos. Jika keputusan
  #10(b), auto-hide baru boleh diaktifkan melalui roadmap moderasi terpisah yang
  sekaligus menyediakan antrean+restore. Tambah policy rate limit untuk endpoint ini.
- **[B6] Hapus/nonaktifkan controller publik `Sikaper.php`, alias route, dan view.**
  `Sikaper.php:4` satu-satunya controller yang `extends CI_Controller` — di luar
  `MY_Controller`, jadi di luar security header — dan `index()` (`:21-25`) memicu
  **5 panggilan upstream** memakai kredensial dinas per hit anonim. Menghapus
  `$route['sikaper']` saja tidak bekerja: CI3 masih merutekan `/Sikaper/index`
  secara konvensional. Library `Sikaper_api.php` boleh menunggu keputusan #5, tetapi
  controller dan view explorer publik harus hilang atau selalu `show_404()`.

**Berkas yang disentuh:** `application/controllers/Chat.php`,
`application/controllers/Umum.php`, `application/models/Forum_model.php`,
`application/config/rate_limits.php`, `application/config/config.php`,
`application/config/routes.php`, `application/views/layouts/footer.php`,
`application/controllers/Sikaper.php`,
`application/views/pages/sikaper/index.php` (dihapus),
`.env.example`, `docs/engineering/uji_utang_teknis.php`, dan satu migrasi tabel pelapor.

**Cara membuktikan:**

1. GET `/Chat/api_bot/halo` dan POST ke `register_session`,
   `kirim_pesan_lanjutan`, serta `ambil_pesan` → **404**. Widget tidak muncul dan
   upstream Gemini stub mencatat nol panggilan. Uji balik mengaktifkan caller publik
   di salinan uji → hitungan upstream bertambah.
2. 6× POST `Umum/report_komentar` anonim untuk satu `id_komentar` → semuanya ditolak,
   dan `SELECT report_count, is_deleted FROM forum_komentar WHERE id_komentar=?`
   **tidak berubah sama sekali**.
3. 5 akun login berbeda melapor komentar yang sama → `report_count` = 5. Akun pertama
   melapor lagi dan dua request paralel → tetap 5; `is_deleted` tetap 0.
4. `/sikaper`, `/Sikaper`, dan `/Sikaper/index` semuanya **404**; upstream stub
   mencatat nol panggilan. Durasi respons bukan bukti.
5. `grep -rn "pages/sikaper" application/` → nol pemanggil SEBELUM berkasnya dihapus.

**Risiko regresi.**

- Widget chat di `layouts/footer.php` sudah rusak hari ini (C2: `tb_chat` tidak ada),
  jadi containment B2 tidak memutus fitur yang sehat. Kalau keputusan #7 nanti
  “bangun”, endpoint dibuka kembali hanya setelah migrasi, binding sesi server,
  rate limit, dan layar operator lulus.
- Guard login pada B3 **menghapus kemampuan tamu melaporkan komentar.** Itu memang
  tujuannya, tapi sebutkan ke user: kanal konsultasi warga kehilangan satu jalur
  moderasi anonim. Layar moderasi admin adalah fitur baru dan **tidak** dikerjakan
  di sini (§7 PRD).
- Menutup controller explorer Sikaper menghilangkan halaman yang mungkin pernah
  dipakai seseorang. Konfirmasi sebelum production, tetapi jangan menganggap
  penghapusan alias route cukup atau mempertahankannya sebagai explorer anonim.

**Beban.** B2 containment kecil namun menyentuh controller+widget. B6 menghapus
permukaan publik, bukan sekadar satu route. B3 yang terbesar karena perlu migrasi
dedup atomik. Satu sesi.

---

## U3 — Saluran keluar diverifikasi, dan kunci berhenti gagal-terbuka

**Tujuan.** Klien yang masih aktif memverifikasi TLS, dan enkripsi/HMAC berhenti
gagal-terbuka saat key atau pepper hilang.

**Butir yang ditutup:** 10 titik B4 yang aktif dan B9. Titik B4 Chat/Sikaper tetap
kondisional; B5 hanya dinyatakan statusnya.

**Isi:**

- **[B4] Kerjakan 10 titik aktif di 4 berkas.** Total inventaris tetap 12 titik/6
  berkas: `Chat.php:121` dikarantina U2, dan `Sikaper_api.php:36` tidak punya caller
  setelah controller U2 ditutup. Scope tanpa keputusan: `Index.php:126, :178, :239,
  :309, :386, :583` (6), `Umum.php:70, :513` (2), `Ternak_api.php:42`, dan
  `Sikumbang.php:82`.
  **Pembanding yang BENAR ada di repo yang sama:** `Simperum_gateway.php:155-156`
  (`CURLOPT_SSL_VERIFYPEER => TRUE`, `CURLOPT_SSL_VERIFYHOST => 2`). Salin polanya;
  jangan bikin wrapper HTTP baru. Bila keputusan #7 adalah “bangun”, titik Chat
  diperbaiki dan diuji di U5 sebelum endpoint dibuka; bila “cabut”, titik itu hilang.
- **Sikaper bercabang tegas:** selama keputusan #5 pending, controller tetap 404,
  library/config tidak dipanggil, dan B4-Sikaper+B5 tetap terbuka. Jika
  dipertahankan, rotasi+preseed secret baru lalu perbaiki TLS sebelum reaktivasi.
  Jika dicabut, hapus `Sikaper_api.php` dan `config/sikaper.php`; jangan memoles atau
  memindahkan secret kode yatim.
- **[B4, konsekuensi yang harus diantisipasi] Sebagian upstream mungkin benar-benar
  bersertifikat bermasalah.** Kalau ada yang gagal setelah verifikasi dinyalakan,
  **jangan kembalikan ke `false`** — itu mengembalikan temuannya. Pilih: pasang CA
  bundle yang benar (`CURLOPT_CAINFO`), atau nyatakan integrasi itu tidak bisa
  dipakai sampai penyedianya memperbaiki sertifikatnya. Yang kedua adalah jawaban
  yang sah dan jujur.
- **[B9] `Encryption_lib.php:69-72`: `encrypt()` fail-open.** Hari ini
  `if (empty($plaintext) || empty($this->key)) { return $plaintext; }` — pemanggil
  tidak bisa membedakan "terenkripsi" dari "dikembalikan apa adanya karena kunci
  hilang", dan log lokal hari ini memuat kejadian nyata "KPKP_DATA_KEY tidak
  ditemukan". Pisahkan dua cabangnya: plaintext kosong boleh lewat; **kunci hilang
  harus melempar exception dan membatalkan transaksi tulis**. Kegagalan OpenSSL juga
  melempar, bukan `FALSE` yang dapat tersimpan sebagai nilai kosong.
  `decrypt()` dengan key hilang/data terenkripsi rusak juga tidak boleh mengembalikan
  input seolah plaintext; kompatibilitas plaintext legacy ditangani eksplisit pada
  migrasi/reader yang bersangkutan, bukan fallback global library. Telusuri semua
  pemanggil sebelum mengubah kontrak. `deterministic_hash()` juga harus
  melempar saat `KPKP_DATA_PEPPER` kosong; HMAC dengan secret kosong adalah
  fail-open. Fitur yang memakai pepper khusus tiket/audit memvalidasi pepper-nya
  sendiri sebelum query/tulis.
- **[B5 — hanya setelah keputusan #5(a) dan rotasi nyata]** preseed kredensial
  Sikaper baru pada seluruh environment, lalu pindahkan pembacaan dari literal
  `config/sikaper.php` ke environment dan hapus nilai literal. **Jangan menyalin
  secret lama yang masih sah ke `.env` lalu mengklaim mitigasi.** Jika tidak ada
  kanal rotasi, integrasi tetap dikarantina atau dicabut sesuai keputusan user.

**Berkas yang disentuh:** `application/controllers/Index.php`,
`application/controllers/Umum.php`,
`application/controllers/Sikumbang.php`, `application/libraries/Ternak_api.php`,
`application/libraries/Encryption_lib.php`, `docs/engineering/uji_utang_teknis.php`.
`Sikaper_api.php`, `config/sikaper.php`, dan `.env.example` hanya disentuh sesuai
cabang keputusan #5: perbaiki+env bila dipertahankan, hapus bila dicabut.

**Cara membuktikan:**

1. Check statis atas empat berkas aktif menemukan nol bentuk array maupun
   `curl_setopt(...CURLOPT_SSL_VERIFYPEER..., false)`. Chat dikecualikan selama
   containment. Sikaper dikecualikan saat pending, lalu wajib hilang atau lulus TLS
   sesuai keputusan. U5 wajib menghapus Chat atau memperbaikinya sebelum route dibuka.
2. **Yang benar-benar membuktikan:** arahkan klien di salinan uji ke server TLS
   lokal bersertifikat tidak sah → permintaan **gagal** dengan galat sertifikat.
   Jangan bergantung pada layanan publik. **Uji balik:** kembalikan
   `VERIFYPEER => false` pada klien itu → berhasil lagi.
3. Smoke test upstream aktif hanya jika `ALLOW_LIVE_UPSTREAM=1` dan izin/data uji
   kontraknya tersedia. Bukti TLS lokal sudah cukup untuk keamanan; live test hanya
   membuktikan kompatibilitas/availability.
4. **B9:** kosongkan `KPKP_DATA_KEY`, lalu `KPKP_DATA_PEPPER` secara terpisah.
   `encrypt('12345')`, `decrypt(<ciphertext>)`, dan
   `deterministic_hash('12345')` melempar sesuai secret-nya; ciphertext rusak tidak
   dikembalikan sebagai plaintext. Jalur tulis/lookup rollback dan DB tidak berubah.
   Uji balik plaintext/HMAC-secret-kosong → merah.
5. Sikaper: pending → tiga URL tetap 404 dan nol caller library; dipertahankan →
   environment dipreseed, secret dirotasi, TLS invalid ditolak, nol literal config;
   dicabut → nol controller/library/config/route/view.

**Risiko regresi.**

- **Terbesar di seluruh roadmap dalam hal "bisa mematahkan fitur yang berjalan".**
  Menyalakan verifikasi TLS bisa mematikan integrasi yang selama ini hidup justru
  karena verifikasinya mati. Respons Sikumbang di-cache 24 jam, jadi kegagalannya
  bisa baru terlihat sehari kemudian — **jangan nyatakan selesai di hari yang sama.**
- B9 mengubah operasi kriptografi dari "selalu mengembalikan sesuatu" menjadi
  exception.
  Telusuri pemanggilnya: jalur yang tidak menangani kegagalan akan berubah dari
  "menyimpan plaintext diam-diam" menjadi "error". Yang kedua benar, tapi ia akan
  muncul sebagai kerusakan baru di layar. Periksa pemanggilnya sebelum, bukan sesudah.
- B5 dilarang bergerak sebelum environment dipreseed dan rotasi dikonfirmasi; kalau
  tidak, integrasi tetap 404/karantina.

**Beban.** Sedang. 10 titik aktif + kontrak fail-closed key/pepper; satu titik
Sikaper hanya bila dipertahankan. Uji TLS lokal tidak menunggu upstream.

---

## U4 — Kejujuran permukaan publik

**Tujuan.** Nol angka, nama, atau tombol di permukaan publik yang mengklaim sesuatu
tanpa sumber.

**Butir yang ditutup:** A3 tanpa syarat; A1, A2, A4, A6 **hanya setelah keputusan
#1–#4 (§6 PRD) dijawab**.

> ⚠️ **Tahap ini sebagian TERBLOKIR sejak hari pertama, dan itu disengaja.**
> Menaruhnya lebih awal berarti seluruh roadmap menunggu jawaban user; menaruhnya
> lebih belakang berarti jam dindingnya terus berjalan. Kompromi yang diambil:
> pertanyaannya dikirim di U0, pekerjaannya mendarat di sini. **Kalau jawaban sudah
> turun sebelum U4 tiba, kerjakan lebih awal** — khususnya kalau jawabannya "cabut",
> yang biayanya menit dan tidak punya ketergantungan apa pun.

**Isi:**

- **[A3, TIDAK butuh keputusan] Cabut tabel "Daftar Intervensi" di
  `application/views/pages/data_spasial/listkabupaten.php`** — anggaran fiktif
  Rp 500.000.000 (`:67`) dan Rp 750.000.000 (`:84`), plus tombol Tambah Data (`:39`),
  Edit, dan Hapus (`:72`, `:89`) yang tidak punya handler. Halaman ini publik anonim
  (`routes.php:91`). Inventaris menandainya "Keputusan user? Tidak" karena tidak ada
  yang perlu diputuskan: tombol tanpa handler adalah UI yang berbohong (§19 langkah 3),
  dan anggaran tanpa sumber adalah angka karangan (§17). Menit.
- **[A1, setelah keputusan #1]** `pages/spasial/sebaran_rusun.php`,
  `profil_kumuh.php`, `sebaran_sdgs.php` + route `routes.php:97-99` + renderer
  `Index.php:539, :544, :549`. **Catatan yang mengubah bentuk perbaikannya:** ketiga
  halaman SUDAH memuat "(Data Simulasi)" di subjudul (`:48` masing-masing) — jadi yang
  dicabut bukan "kebohongan total", melainkan bagian yang tidak dilisensikan label
  simulasi: **nama rusunawa NYATA + koordinat asli** (`sebaran_rusun.php:204-209`),
  daftar rusunawa bernama nyata di tabel (`:82-117`), dan tuduhan
  "Terdapat keluhan kerusakan aset" (`:155`). Kalau keputusannya (a) cabut, hapus
  sekalian `pages/spasial/sebaran_rtlh.php` (butir E, nol renderer).
- **[A2, setelah keputusan #2]** `Statistika.php:26-58` — **21 metrik** (dihitung
  sendiri: perumahan 6 + kawasan 4 + pertanahan 3 + pengembang 4 + penerima_manfaat 4;
  inventaris menulis 22, lihat Catatan kejujuran nomor 3), semuanya
  `konstanta × ((crc32($kabupaten) % 80) + 20)/1000` (`:21-22`) dan dilabeli
  "Sumber: Simperum/Sikumbang/Sikaper/Sikunang/Bank Tanah". `:39` khusus: persentase
  `round((850.2/1205.5)*100, 1)` tanpa multiplier → **70,5% identik untuk 35
  kab/kota**.
- **[A4, setelah keputusan #3]** `pages/profil/struktur_organisasi.php:63,66` (nama
  individu nyata sebagai Kepala Dinas, nol sumber data) + 4 placeholder
  "Nama Pejabat" (mis. `:78`). **Jalur dua langkah tersedia dan sebaiknya dipakai:**
  pilihan (b) — kosongkan nama, pertahankan bagan jabatan — bisa dijalankan hari ini
  sebagai mitigasi sambil menunggu dokumen resmi untuk pilihan (c). Ini satu-satunya
  butir A yang punya mitigasi cepat tanpa menutup opsi akhirnya.
- **[A6, setelah keputusan #4]** `application/views/admin/settings/index.php` —
  `<form class="space-y-5">` (`:34`) tanpa `action`/`method`/CSRF, tombol
  "Simpan Perubahan" di `:7` **di luar** form, nilai literal (`:37`, `:48`, `:52`),
  toggle "Mode Pemeliharaan" mati (`:58`), 4 tab `href="#"` (`:14, :17, :20, :23`).
  Kalau keputusannya "sambungkan", penyimpanan yang NYATA sudah ada:
  `Admin_Content.php:44` (`Setting_model->update_batch_settings()`), dibaca
  `Index.php:23-24`. **Jangan bikin model penyimpanan setting kedua.**

**Berkas yang disentuh:** `application/views/pages/data_spasial/listkabupaten.php`,
`application/views/pages/spasial/*.php`, `application/views/pages/profil/struktur_organisasi.php`,
`application/views/admin/settings/index.php`, `application/controllers/Statistika.php`,
`application/controllers/Index.php`, `application/config/routes.php`,
`docs/engineering/uji_utang_teknis.php`.

**Cara membuktikan:**

1. `curl` **tanpa cookie** ke tiap URL, grep responsnya untuk literalnya:
   `4,250.8`, `8,420`, `142,500`, `1.2`, `Rusunawa Kudu`, `Nama Pejabat`,
   `Rp 500.000.000`, `Rp 750.000.000`, `Mode Pemeliharaan`, `Terdapat keluhan` → **nol
   hasil**, atau halaman membalas 404 karena route-nya dicabut.
2. `curl "/statistika?kabupaten=Kabupaten+Kudus"` dan `"?kabupaten=Kabupaten+Brebes"`
   → tidak ada lagi label "Sumber: <sistem>" di sebelah angka yang tidak berasal dari
   sistem itu. Kalau halaman dipertahankan, setiap metrik harus dapat ditautkan ke
   adapter/dataset, waktu pengambilan, dan status nyata/simulasi. Dua kabupaten
   menghasilkan angka berbeda bukan bukti provenance.
3. `grep -rn 'href="#"' application/views/admin/settings/` → nol (kalau layar
   dipertahankan).
4. `grep -rn "spasial/sebaran_rtlh\|pages/sikaper" application/` → nol pemanggil
   SEBELUM berkasnya dihapus.
5. **Uji balik:** kembalikan satu literal ke view, jalankan skrip → **MERAH**. Kalau
   tetap hijau, skripnya mengecek URL yang salah.

**Risiko regresi.**

- Rendah secara teknis (mayoritas diff-nya penghapusan view), **tinggi secara
  persepsi**: dinas sedang uji coba dan halaman yang mereka kenal akan hilang. Beri
  tahu sebelum, bukan sesudah.
- Footer menaut ke halaman spasial di SETIAP halaman. Mencabut route tanpa memperbaiki
  footer menghasilkan 404 di seluruh situs. Periksa `layouts/footer.php` di tahap yang
  sama.
- Kalau A6 dipilih "sambungkan": toggle "Mode Pemeliharaan" adalah **mekanisme yang
  belum ada sama sekali**, bukan field yang tinggal disimpan. Menyimpannya sebagai
  setting tanpa ada yang membacanya menghasilkan persis kelas bug "kolom tanpa
  penulis/pembaca" (§19). Hapus toggle-nya kalau mekanismenya tidak dibangun.

**Beban.** A3 menit. Sisanya menit (cabut) sampai hari–minggu (sambungkan ke data
nyata), sepenuhnya ditentukan jawaban user. **Jangan estimasi sebelum jawabannya
turun.**

---

## U5 — Chat: satu keputusan mematikan lima temuan

**Tujuan.** Fitur chat berhenti menjanjikan sesuatu yang tidak ada — entah dengan
dicabut, entah dengan benar-benar dibangun.

**Butir yang ditutup:** C2, C3, B7. Dan kalau dicabut, ikut menghapus 1 dari 12 titik
B4 (`Chat.php:121`) serta membuat B2 permanen.

> ⚠️ **TERBLOKIR PENUH oleh keputusan #7 (§6 PRD).** Selisih biaya kedua pilihan
> adalah yang terbesar di seluruh dokumen ini: (a) cabut = menit–jam; (b) bangun =
> minggu, dengan §17 checklist penuh + §19 dua belas langkah. **Jangan mengembangkan
> `Chat.php` sebelum jawabannya turun** — containment B2 di U2 tetap dipertahankan
> untuk menutup endpoint dan kuota selama menunggu.

**Isi bila pilihan (a) — cabut:**

- Hapus `application/controllers/Chat.php`, `application/models/Chat_model.php`,
  widget chat di `application/views/layouts/footer.php` (termasuk `:165`,
  `session_id` dari `Math.random()`), dan route terkait.
- Tabel `chat_rooms`/`chat_messages` ADA di DB tapi 0 baris, hanya disentuh
  `Chat_model` yang nol pemanggil, dan **tidak lahir dari migrasi** — instalasi baru
  tidak akan punya. Jangan tulis migrasi drop untuk tabel yang tidak pernah dibuat
  migrasi. Periksa dengan `table_exists()`; pembersihan DB manual di lingkungan yang
  punya adalah aksi destruktif dan memerlukan izin eksplisit user. Ketiadaan tabel
  pada DB fresh adalah hasil sah, bukan kegagalan (pelajaran `omah_sekeng`, §0e).
- `tb_chat` tidak perlu di-drop — ia memang tidak pernah ada.

**Isi bila pilihan (b) — bangun:**

- Ini **bukan** tahap roadmap ini lagi; ia modul baru dan tunduk pada §17 checklist
  penuh + §19 dua belas langkah, dimulai dari Kartu Domain. Yang minimal wajib:
  migrasi tabel yang sebenarnya (atau alihkan ketiga endpoint ke
  `chat_rooms`/`chat_messages` yang sudah ada, lengkap dengan migrasinya),
  **`session_id` terikat sesi server** bukan `Math.random()` browser (B7),
  `ambil_pesan()` (`Chat.php:157-159`) memakai `select()` eksplisit alih-alih
  `result_array()` telanjang yang mengembalikan `nama_warga`/`email_warga`/`hp_warga`
  (`:24-26`), rate limit lewat registry yang sudah ada, dan **satu entri di
  `dashboard_modules.php` + layar operator** — tanpa itu C3 tetap terbuka: status
  `'admin'` di `chat_rooms` dan label "Petugas" di UI menjanjikan operator manusia
  yang tidak punya layar untuk masuk.
- **Pertanyaan yang harus dijawab sebelum baris pertama:** siapa yang akan duduk
  sebagai operator? Tanpa jawaban itu, (b) menghasilkan layar kosong dan C3 hanya
  berpindah bentuk.

**Berkas yang disentuh:** tergantung pilihan. Minimal `application/controllers/Chat.php`,
`application/models/Chat_model.php`, `application/views/layouts/footer.php`,
`application/config/routes.php`.

**Cara membuktikan:**

- **Pilihan (a):** `grep -rn "tb_chat\|Chat_model\|api_bot" application/` → **nol**.
  Muat halaman mana pun sebagai anonim → nol skrip chat di HTML, nol permintaan
  jaringan ke `/Chat/*`. Jika `table_exists('chat_rooms')`, catat jumlah baris sebelum
  tindakan apa pun; jika tidak ada, jangan menjalankan `SELECT`. Penghapusan tabel/
  data manual tidak menjadi bagian bukti tanpa izin destruktif user.
- **Pilihan (b):** perjalanan penuh warga → operator → balas, di DB **hasil migrasi
  yang bersih** (bukan DB lokal yang sudah ditambal tangan), plus tiga uji negatif
  §19 langkah 12. Ditambah: `session_id` milik satu warga tidak bisa dipakai warga
  lain untuk membaca riwayat (uji anti-B7), dan responsnya tidak memuat
  email/HP siapa pun.

**Risiko regresi.**

- Pilihan (a) menghapus widget yang terlihat di setiap halaman. Pengguna yang pernah
  memakainya akan menyangka fiturnya rusak, padahal ia **sudah rusak sejak lama**
  (C2: 500 errno 1146 ×3). Sampaikan itu apa adanya.
- Pilihan (b) adalah modul baru di sistem yang sedang dipakai uji coba dinas.
  Mengerjakannya berarti menunda seluruh sisa roadmap.

**Beban.** (a) menit–jam. (b) minggu. **Jangan estimasi sebagai satu angka.**

---

## U6 — NIK antrean, lookup tiket, dan status di luar kendali agent

**Tujuan.** Kedua jalur pengajuan menghasilkan identitas antrean yang sama-sama dapat
digunakan tanpa NIK plaintext; lookup publik dan admin tetap bekerja.

**Butir yang ditutup:** B8 setelah keputusan #8 dan keselarasan retensi #9. B5/B10
hanya dinyatakan statusnya, tidak diklaim selesai.

**Fakta yang wajib ditangani bersama:**

- `Program_model.php:121` menulis NIK plaintext, sedangkan wizard baru
  `Housing_assessment_model.php:560-565` menulis `nik_pengaju = NULL`;
- lookup publik `Program_model.php:201-207` memakai `RIGHT(nik_pengaju, 4)`, sehingga
  tiket wizard baru tidak dapat diverifikasi dengan alur lama;
- pencarian admin memakai `LIKE nik_pengaju` dari query string
  (`MY_Controller.php:271-275`) dan dashboard memotong plaintext di view
  (`admin/antrean/dashboard.php:85-86`).

**Skema aditif bersama untuk kedua pilihan #8:**

```sql
nik_ciphertext TEXT NULL,
nik_lookup_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
nik_suffix_lookup_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
KEY idx_sf_queue_nik_lookup_hash (nik_lookup_hash)
```

`nik_lookup_hash` tidak `UNIQUE` karena satu NIK dapat mempunyai beberapa pengajuan.
Indeks suffix tidak perlu karena `ticket_code` sudah unik. Secret khusus
`KPKP_QUEUE_LOOKUP_PEPPER` wajib 64 hex/32 byte, tersedia di seluruh environment,
tanpa fallback ke `KPKP_DATA_PEPPER`. Domain hash dipisah:

```text
HMAC("sf_housing_queue:nik:v1\0" + NIK)
HMAC("sf_housing_queue:ticket-suffix:v1\0" + UPPER(ticket_code) + "|" + suffix)
```

Implementasi kanonik memvalidasi `/^[a-f0-9]{64}$/i`, menjalankan `hex2bin()`, lalu
`hash_hmac('sha256', $domain_payload, $binary_key, false)`. Writer, lookup, dan
backfill wajib lulus golden-vector ini; jangan memakai ASCII hex sebagai key:

| Test key hex | Payload | Hasil hex |
|---|---|---|
| `000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f` | `sf_housing_queue:nik:v1\0` + `0000000000000001` | `449a8f7d930b9fa10e6a7c04715919d7d53dea5b8ff89e076ddd6ebc5324507d` |
| key sama | `sf_housing_queue:ticket-suffix:v1\0` + `PKP-ABC123\|0001` | `92ffcfc2de365bcaada6047a48127e9c461f6d379eb08384548e102e6093bc7c` |

Pada tabel di atas `\0` berarti satu byte NUL, bukan dua karakter backslash+angka.

NIK dinormalisasi dan divalidasi tepat 16 digit. Key/pepper hilang atau tidak valid
harus 503/rollback, bukan HMAC secret kosong. Jadikan
`Program_model::insert_housing_queue()` gerbang tulis yang nyata dan gunakan dari
`Housing_assessment_model`; jangan menambah abstraction baru. Gateway menghasilkan
hash deterministik yang sama untuk input yang sama. Ciphertext AES-GCM **harus acak**
karena IV baru tiap enkripsi; nilainya boleh berbeda tetapi wajib terdekripsi ke NIK
yang sama.

**Lookup publik dan admin:**

1. Jika keputusan #8(a), migrasi aditif juga melebarkan `ticket_code` dari
   `VARCHAR(10)` menjadi `VARCHAR(36)`. Format baru:
   `PKP-` + 32 hex uppercase dari `random_bytes(16)` (128-bit), dan validator/UI
   menerima format baru+legacy selama transisi. Lookup publik format baru tidak
   meminta NIK. Tiket lama tidak boleh tiba-tiba putus: pertahankan
   jalur suffix-HMAC sampai masa retensinya berakhir, atau reissue token melalui
   kanal terautentikasi yang diputuskan user; jangan mengarang mekanisme pengiriman
   bagi pemilik tiket anonim. Jika #8(b), lookup memakai
   `ticket_code + nik_suffix_lookup_hash`; jangan hash 4 digit secara global.
2. Tambah dimensi registry `ticket`, bukan `object` yang hari ini meng-cast integer.
   Policy `ticket_lookup` berdimensi `ip`+`ticket`; format rusak memakai satu sentinel
   tetap. Hanya format/lookup gagal yang menaikkan counter; lookup sukses tidak.
   Kegagalan ke-6/60 detik → `429` + `Retry-After`, window kedaluwarsa memulihkan
   akses; limiter/pepper gagal → 503.
3. Tiket tidak dikenal dan suffix salah memberi status/pesan generik yang sama.
   Respons tidak memuat NIK, suffix, ciphertext, atau hash.
4. Keluarkan NIK dari pencarian GET umum admin. Jika pencarian NIK dipertahankan,
   gunakan POST+CSRF, tepat 16 digit, equality `nik_lookup_hash`, dan tetap scope
   `kabupaten_id`; NIK tidak masuk URL, flash, atau log.
5. Controller hanya mendekripsi baris yang sudah lolos scope+pagination, mengirim
   bentuk `••••••••••••1234`, lalu membuang ciphertext/hash/plaintext sebelum view.
   Key salah/hilang atau ciphertext rusak → 503 dan tidak menampilkan suffix apa pun.
   Selama dual-read, plaintext hanya fallback bila `nik_ciphertext IS NULL` dan
   `nik_pengaju` legacy lolos validasi 16 digit—bukan saat dekripsi gagal.

**Migrasi dan rollback wajib bertahap:**

1. Prasyarat: U3 fail-closed hijau, pepper dipreseed, keputusan #8 dicatat, dampak
   retensi #9 disepakati, dan backup teruji.
2. Migrasi **N** menambah tiga kolom+indeks; untuk pilihan #8(a) juga melebarkan
   `ticket_code` ke `VARCHAR(36)`. Kode lama tetap berjalan karena pelebaran kompatibel.
3. Deploy compatibility-write/dual-read: kedua writer mengisi kolom baru. Hanya
   writer legacy yang sudah menulis plaintext boleh meneruskannya sementara; wizard
   baru tetap `nik_pengaju = NULL` dan **tidak boleh diperkenalkan ke plaintext**.
   Pembaca baru mendahulukan kolom baru, plaintext hanya fallback legacy.
4. Backfill terpisah **N+1** berjalan batch, atomik, idempoten, tanpa mencetak NIK.
   Ambil NIK dari snapshot profil assessment saat submit; legacy tanpa assessment
   memakai plaintext valid; profil terkini hanya fallback yang ditandai untuk review.
   `ticket_code` kosong adalah blocker yang harus diselesaikan/dilaporkan. Pada
   pilihan #8(a), tiket legacy yang belum direissue tetap wajib mendapat
   `nik_suffix_lookup_hash` sampai jalur legacy berakhir.
5. Selama jendela observasi eksplisit, reader baru aktif dan compatibility-write
   plaintext legacy tetap hidup. Sebelum build ini menerima traffic, rollback pra-U6
   masih boleh. Setelah
   traffic masuk, target rollback minimum adalah build dual-reader/compatibility-writer:
   build pra-U6 akan kembali membuat tiket wizard dengan NIK NULL.
6. Setelah jendela berakhir, hentikan penulisan plaintext legacy. Mulai titik ini rollback
   hanya ke build yang memahami ciphertext/hash, bukan build pra-U6.
7. Migrasi destruktif **N+2**, pada rilis dan izin terpisah, menghapus semua referensi
   runtime lalu drop `nik_pengaju`. Jangan memakai `down()` sebagai strategi rollback;
   sesudah drop hanya backup teruji yang memulihkan data.

**B5/B10 apa adanya:**

- B5 dipindah ke environment hanya bila secret baru benar-benar sudah dirotasi dan
  dipreseed. Tanpa kanal rotasi, integrasi Sikaper tetap dikarantina/dicabut; status
  tetap terbuka.
- B10 tetap **RISIKO AKTIF YANG DITERIMA SEMENTARA** selama uji dinas. Rotasi hanya
  melalui alur aplikasi setelah instruksi user baru.

**Berkas yang disentuh:** `Program_model.php`, `Housing_assessment_model.php`,
`Program.php`, `MY_Controller.php`, `Admin.php`, `Admin_Kabkota.php`,
`Encryption_lib.php`, `Rate_limiter.php`, `config/rate_limits.php`,
`config/migration.php`, `.env.example`, dashboard antrean, view cek/sukses tiket,
tiga migrasi terpisah, harness utang teknis, R6, dan perjalanan Warga. Baseline SQL
lama tidak diubah untuk menghapus `nik_pengaju`.

**Cara membuktikan:**

1. Tiket dari jalur legacy dan wizard baru sama-sama dapat dicek; tiket salah dan
   suffix salah tidak dapat dibedakan dari respons.
2. Seluruh row yang dapat dipulihkan memiliki ciphertext+full hash. Suffix hash wajib
   untuk #8(b) **dan** tiket legacy #8(a) yang belum direissue; backfill kedua
   menghasilkan nol perubahan/duplikasi.
3. GET toolbar admin tidak memuat NIK; exact POST tetap terscope wilayah; payload
   view tidak mengandung plaintext/ciphertext/hash dan hanya menampilkan mask.
4. Satu IP dengan banyak tiket dan banyak IP pada satu tiket membuktikan kedua
   dimensi; kegagalan ke-6 menghasilkan `429`+`Retry-After`. Lima lookup sukses tidak
   menaikkan counter, dan sesudah 60 detik kegagalan lama tidak lagi memblokir.
5. Key/pepper hilang membatalkan writer, lookup, dan tampilan admin tanpa row parsial;
   key salah/ciphertext rusak menghasilkan 503, tidak pernah suffix ciphertext.
6. Sebelum traffic fase 3, rollback pra-U6 diuji. Setelah traffic fase 3, rollback
   hanya ke build dual-reader/compatibility-writer. Sebelum menjalankan fase 7,
   restore backup sudah harus teruji.

**Risiko regresi.** Tertinggi dari sisi data. Uji pada salinan production dan jangan
rotasi `KPKP_DATA_KEY`; data terenkripsi lama akan hilang permanen.

**Penutup dokumentasi:** perbarui `AGENTS.md` per butir, termasuk yang terbantah,
blocked, dan risiko diterima. B8 selesai hanya setelah fase yang benar-benar dirilis,
bukan karena migrasi aditif sudah ada.

---

## Runbook wajib sebelum rilis production

Branch `feature/homepage-portal-v2` auto-deploy langsung ke production. Karena itu
seluruh implementasi roadmap dikerjakan dahulu di holding branch yang **tidak**
terhubung deployment. Push ke branch aktif hanya **sekali per paket/rilis yang
disetujui** setelah gate hijau. U0+U1 adalah satu paket; fase U6 adalah beberapa
rilis terpisah dan tidak boleh dipaksa menjadi satu push.

### Gate pra-rilis

1. Keputusan user yang relevan tercatat di ledger §6 PRD; butir BLOCKED tidak ikut.
2. Worktree implementasi tidak mencampur perubahan agent lain.
3. Harness memakai worktree+vhost+DB+upload uji terpisah, seluruh tes tahap hijau,
   mutation proof merah saat guard dibalik, dan hard-kill recovery terbukti.
4. Daftar perubahan skema, environment, file privat, serta route ditulis sebelum
   menyentuh server.
5. `git fetch` menunjukkan branch remote masih pada base yang diuji. Jika bergerak,
   integrasikan perubahan lalu ulangi seluruh gate.
6. Revision yang benar-benar sedang tersaji di production diverifikasi lewat
   provider/SSH dan cocok dengan base migrasi yang diuji; remote branch saja tidak
   membuktikan deploy aktif.
7. Ada persetujuan eksplisit user untuk backup/migrasi/push **serta pemicu rollback**
   production.

### Urutan rilis

1. Backup DB production dan konfigurasi server (`.env`, `.htaccess`/vhost) bertanggal.
   Verifikasi backup dapat dibaca, jumlah tabel/ukuran masuk akal. Untuk rilis yang
   menyentuh PII/evidence, gunakan write-freeze nyata atau snapshot+reconciliation;
   jangan mengandalkan toggle “Mode Pemeliharaan” palsu.
2. Untuk rilis yang dapat menghapus/memindah bukti, buat manifest relative path +
   ukuran + checksum dan backup `private_uploads` bila kebijakan retensi mengizinkan.
   DB dump dan archive file harus berasal dari window konsisten; restore DB ke nama
   terisolasi, ekstrak archive ke root temp, lalu cocokkan ledger DB ↔ manifest ↔
   checksum **sebelum** mutasi destruktif.
   Jika purge legal harus irreversible, butuh izin eksplisit serta catatan mengapa
   backup dilarang dan kapan salinan lama dimusnahkan.
3. Siapkan environment prerequisite **sebelum kode**—misalnya
   `KPKP_QUEUE_LOOKUP_PEPPER`, `KPKP_AUDIT_PEPPER`, atau kredensial Sikaper **yang
   sudah dirotasi**. Jangan memindahkan secret sebelum semua environment mempunyai
   nilai valid.
4. Jalankan `php index.php migrate status` dan simpan output. Catat nama+hash seluruh
   berkas migrasi di server; berkas liar adalah blocker. Untuk migrasi additive,
   salin hanya berkas migrasi yang disetujui, verifikasi hash lagi, jalankan
   `php index.php migrate`, lalu jalankan `status` ulang dan cek versi/tabel/kolom
   yang diharapkan. Biarkan berkas migrasi tetap ada dan smoke test kode lama.
   Jangan pernah menjalankan `current()`.
5. Push satu commit/rangkaian commit yang sudah disetujui untuk paket ini ke branch
   auto-deploy.
   U0+U1 selalu ikut bersama.
6. Auto-deploy asynchronous: tunggu selesai dan buktikan commit/aset yang benar-benar
   tersaji sama dengan yang diuji sebelum smoke test.
7. Smoke test minimum: home, login, wizard Warga, akun, antrean admin, forum, endpoint
   yang ditutup, header/cookie, serta canary tanpa bocoran path/SQL.
8. Catat commit, versi skema, hasil smoke test, dan status keputusan di `AGENTS.md`;
   hapus klaim lama yang bertentangan, jangan hanya menambah paragraf baru.

**Pengecualian migrasi destruktif (mis. U6 N+2):** urutan “migrasi sebelum kode”
tidak berlaku. Deploy lebih dulu kode yang sudah nol referensi ke kolom lama saat
kolomnya masih ada, tunggu deployment dan smoke test. Restore backup ke DB terisolasi
dan buktikan dapat dipakai. Baru dengan izin rilis terpisah jalankan migrasi drop,
verifikasi status/skema, dan ulangi smoke test.

### Rollback

- Perubahan kode dengan migrasi additive: revert kode ke commit sebelumnya; skema
  baru boleh tetap ada bila benar-benar backward-compatible.
- Migrasi NIK: sebelum build compatibility menerima traffic, rollback pra-U6 boleh.
  Setelah menerima traffic, target minimum adalah build dual-reader/compatibility-
  writer; setelah plaintext legacy dihentikan rollback hanya ke build new-reader;
  setelah kolom di-drop pemulihan data hanya melalui backup teruji. Jangan
  mengandalkan `down()`.
- Perubahan environment: simpan nilai lama dan pulihkan bersama kode bila smoke test
  gagal.
- Setelah rollback, ulangi smoke test dan catat keadaan akhir; jangan meninggalkan
  production pada status “mungkin pulih”.

---

## Sengaja TIDAK masuk roadmap ini

- **[SUDAH SELESAI] Seluruh bagian D inventaris.** Unggahan berkas (ketiganya
  `store_private_upload()`, `/uploads/` & `/private_uploads/` production 404),
  penyajian berkas privat (11 pemanggil `serve_private_file()`, nol IDOR),
  `Migrate.php` (terbatas CLI/localhost), SQL injection (2 query mentah, keduanya
  parameterized), proteksi berkas sensitif production (403), header keamanan
  production, `admin/dashboard.php`, empat halaman pertanahan `Index.php:606-628`
  (contoh KEPATUHAN — layar menulis "pratinjau (dummy)" apa adanya),
  `kemitraan_portal/magang.php`, `sikunang.php`, `siperum.php`, `sejarah_visi.php`,
  `tugas_pokok.php`, `action="#"` (nol di luar `archive/`), klaim "tersinkronisasi"
  palsu (nol tersisa). **ALASAN:** sudah diverifikasi runtime 29 Juli. Catatan §17
  "3 lokasi upload publik" sudah usang — jangan diikuti.
- **[BUKAN BUG] B12.** Token CSRF bisa diambil anonim (`csrf_regenerate=FALSE`,
  double-submit). **ALASAN:** ia ada di inventaris bukan sebagai temuan melainkan
  sebagai **pembatas desain** — ia membuktikan CSRF tidak akan menutup B2 & B3. Yang
  "dikerjakan" dari B12 adalah tidak mengerjakannya: U2 tidak menargetkan CSRF.
- **[ROADMAP HISTORIS] T0–T6 role pengembang** dan **R0–R9 alur warga** tetap menjadi
  catatan perjalanan, tetapi status “selesai” tidak mengecualikan regresi/kekurangan
  S1–S9 yang ditemukan sesudahnya; S0 menutup bagian yang tidak BLOCKED dan mencatat
  keputusan yang masih dibutuhkan untuk S3/S4/S7. Tahap historis
  tidak disalin ulang karena
  keduanya bekerja per-perjalanan. Bagian
  "Sengaja TIDAK masuk roadmap ini" milik [`ROADMAP_PENGEMBANG_ADMIN.md`](./ROADMAP_PENGEMBANG_ADMIN.md)
  bahkan menyarankan roadmap terpisah untuk butir-butir ini — ini roadmap terpisah itu.
  **Satu titik singgung yang harus dikoordinasikan:** `Admin_Srp2.php` disentuh U1
  (A5, `:296`) dan T1a/T1b. Jangan dua pola.
- **[PENGERASAN, BUKAN UTANG] CSP.** Production sudah punya X-Frame-Options, nosniff,
  HSTS 1 tahun, Referrer-Policy, Permissions-Policy; CSP nihil. **ALASAN:** bukan
  butir inventaris, dan memasangnya di portal berisi Leaflet + inline script butuh
  sesi tersendiri dengan definisi selesai sendiri.
- **[FITUR BARU] Layar moderasi forum untuk B3.** **ALASAN:** gerbang keamanan B3
  selesai dengan login + dedup + rate limit dan U2 selalu ledger-only. Jika keputusan
  #10(b), layar antrean+restore dan auto-hide dibuat sebagai roadmap domain baru
  sebelum visibilitas otomatis diaktifkan. Layar admin adalah produk baru
  dan tunduk §17 checklist penuh. Kalau user memintanya, buat entri
  `dashboard_modules.php`-nya sebagai modul, bukan sebagai tambalan di U2.
- **[USULAN YANG DIBUANG] Wrapper HTTP baru untuk memusatkan setelan TLS (B4).**
  **ALASAN:** U3 menangani 10 titik aktif di 4 berkas dengan pola yang sudah benar di
  `Simperum_gateway.php:155-156`; Sikaper bercabang di #5 dan Chat diselesaikan U5.
  Menyalin dua baris
  lebih kecil daripada
  membangun abstraksi berpemanggil satu-per-berkas, dan wrapper baru adalah tempat
  ketiga yang harus dibaca orang berikutnya. Kalau nanti klien HTTP ke-7 lahir,
  pertanyaannya boleh dibuka lagi.
- **[USULAN YANG DIBUANG] Menggerbangi blok Kredensial Demo dengan
  `ENVIRONMENT === 'development'` sebagai mitigasi B10.** **ALASAN:** gate itu
  FAIL-OPEN — `index.php:56` mendefault ke `development` kalau `CI_ENV` tidak diset,
  jadi server yang lupa menyetelnya justru tetap memajangnya. Dan B1 baru saja
  membuktikan `CI_ENV` **memang bisa hilang dari server tanpa ada yang sadar**. Yang
  menutup B10 hanya rotasi sandi (keputusan #6). §17 poin 14 sudah melarang gate ini
  eksplisit.
- **[AKSI USER, BUKAN AGENT] Rotasi sandi akun admin penuh setelah masa uji berakhir,
  rotasi kredensial Sikaper, containment environment server bila diperlukan, dan
  pengisian kunci reCAPTCHA. Enam akun demo lain sengaja dipertahankan.**
  **ALASAN:** di luar kendali agent, sebagian di luar kendali user (Sikaper butuh
  pihak ketiga). Sudah dipisah sebagai keputusan #5 dan #6. **Jangan hitung sebagai
  tahap, dan jangan janjikan tanggalnya.**

---

## Catatan kejujuran

Bagian yang buktinya kurang — sebutkan apa adanya, jangan dianggap terverifikasi.

**1. Yang saya buka sendiri di sesi ini (pembacaan kode, BUKAN uji HTTP):**
`application/helpers/forum_helper.php:55-79`, `application/models/Forum_model.php:92-135`,
`application/controllers/Umum.php:370-399`, `application/controllers/Chat.php:1-175`,
`application/controllers/Statistika.php:1-70`, `application/controllers/Sikaper.php`,
`application/controllers/Admin_Users.php:70-142`, `application/models/Program_model.php:110-125,205`,
`application/libraries/Encryption_lib.php:55-85`, `application/config/sikaper.php`,
`application/config/rate_limits.php`, `application/config/database.php:85`,
`application/config/routes.php` (baris terkait), `index.php:45-69`, `.htaccess` (utuh),
`application/views/pages/spasial/*.php` (via grep bertarget),
`application/views/pages/profil/struktur_organisasi.php:55-79`,
`application/views/admin/settings/index.php` (via grep bertarget),
`application/views/pages/data_spasial/listkabupaten.php` (via grep bertarget),
`application/views/layouts/footer.php:165`, `application/config/dashboard_modules.php`
(pencarian `chat`). Klaim dengan berkas:baris di dua dokumen ini sudah dicocokkan ke
kode. Sisanya saya percaya dari inventaris 29 Juli.

**2. SATU KOREKSI terhadap bahan baku, dan ia mengubah urutan tahap.** Inventaris
menandai A5 ("`db_debug` mati di production") dan C1b ("dengan `db_debug` mati")
dengan premis yang **bertentangan dengan B1**-nya sendiri. Rantainya: `index.php:56`
mendefault `ENVIRONMENT` ke `development`; `database.php:85` menyetel
`db_debug = (ENVIRONMENT !== 'production')`; `.htaccess` repo tidak punya `SetEnv`
(dibaca utuh, konsisten dengan catatan F1); dan B1 diverifikasi runtime menunjukkan
error PHP tampil publik. **Jadi `db_debug` di production hari ini TRUE, bukan mati.**
Konsekuensinya: A5 dan C1b belum berbahaya sekarang — keduanya menjadi berbahaya
tepat setelah U0. Itulah kenapa U1 mengikat ke U0 dan bukan tahap yang bebas
dipindah. Audit koreksi kemudian memverifikasi GET production secara read-only:
respons masih memuat PHP warning dan path absolut.

**3. Hitungan metrik A2: saya menghitung 21, inventaris menulis 22.** Dari
`Statistika.php:26-58`: perumahan 6, kawasan 4, pertanahan 3, pengembang 4,
penerima_manfaat 4. Selisih satu kemungkinan karena inventaris ikut menghitung blok
"Publikasi" dari KRSjawa (`:70` dst.) yang justru **data nyata**. Tidak mengubah
keputusan apa pun; dicatat supaya angka di dokumen ini tidak dianggap salin-tempel.

**4. Ketiga halaman spasial SUDAH berlabel "(Data Simulasi)"** (`:48` di masing-masing
berkas). Inventaris tidak menyebutkannya. Ini mengubah bentuk perbaikan A1 — dari
"tambahkan kejujuran" menjadi "cabut bagian yang tidak dilisensikan label simulasi,
yaitu nama & koordinat aset NYATA dan tuduhan kerusakan aset". Diverifikasi sendiri
lewat grep.

**5. B8 lebih besar dari label "Hari" di inventaris.** `Program_model.php:205` mencari
tiket dengan `RIGHT(nik_pengaju, 4)`, yang mustahil di atas ciphertext. Jadi B8 bukan
pekerjaan enkripsi, melainkan **keputusan produk tentang cara warga mengecek status**.
Butir ini tidak ada di catatan inventaris; saya menemukannya saat membaca kode dan
menaikkannya ke tabel keputusan (#8).

**6. Akses production pada audit koreksi hanya GET anonim.** Diverifikasi 29 Jul:
`/Umum/info_rumah` membocorkan warning+path; `.env`, `AGENTS.md`, dan `docs/` tetap
403; login mengirim cookie `Secure` dan HSTS. Tidak ada login, POST, query DB, atau
perubahan server yang dilakukan. Keadaan DB/chat production tetap belum dibuktikan
ulang dalam audit ini.

**7. B10 mengikuti keputusan user dan AGENTS §0c.** Sandi admin dicatat masih aktif
dan sengaja dipertahankan sementara selama uji dinas. Itu risiko diterima, bukan
ketidaktahuan. Agent tetap dilarang mencoba login atau merotasi tanpa perintah.

**8. C1b masih inferensi, bukan temuan terverifikasi.** Klaim "di production forum
mungkin justru berhasil tanpa pembatas laju" berasal dari membaca kode, dan
membuktikannya butuh POST ke production — yang berarti membuat data di sistem yang
sedang dipakai dinas. Saya tidak melakukannya dan tidak menyarankan agent berikutnya
melakukannya tanpa izin user. **Verifikasi yang sah ada di lokal dengan `db_debug`
FALSE** (U1 cara membuktikan nomor 2), dan itu cukup untuk menentukan perbaikannya.

**9. Perkiraan beban adalah tebakan.** Tidak ada data historis kecepatan di repo ini
yang saya baca. Pakai untuk urutan relatif, jangan untuk janji jadwal. Tiga tahap
(U4, U5, U6) sengaja **tidak** saya beri angka karena bebannya ditentukan jawaban user,
bukan oleh kodenya.

**10. Tiga keputusan teknis yang masih bisa dibantah:**
(a) **A5+C1 disiapkan sebelum B1 lalu dirilis atomik**—production tidak dipakai
sebagai instrumen diagnosa. (b) **B2 containment tidak menunggu keputusan chat**:
caller publik juga ditutup karena membuat method private saja masih menyisakan jalur
ke Gemini setelah write berhasil. (c) **Tidak membuat wrapper HTTP untuk B4**—10
titik aktif disalin dari pola `Simperum_gateway.php:155-156`; Sikaper dan Chat
diselesaikan berdasarkan keputusan masing-masing. Kalau klien aktif baru lahir,
keputusan ini pantas dibuka.

**11. Runner PHP dan browser utang teknis belum ada dan belum pernah dijalankan.**
Semua "cara membuktikan" di roadmap ini adalah **spesifikasi**, bukan uji yang lulus.
Butir yang tidak boleh dipotong dari tahap mana pun adalah **uji balik**-nya: balikkan
satu perbaikan, jalankan lagi, skripnya MERAH. Skrip yang tidak pernah gagal tidak
membuktikan apa pun (§19 langkah 12).
