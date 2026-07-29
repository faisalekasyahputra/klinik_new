# Roadmap Pelunasan Utang Teknis

> Disusun 29 Juli 2026 dari inventaris utang teknis terverifikasi 29 Juli (22 butir
> aktif, kode A/B/C). Argumen urutannya ada di
> [`PRD_PELUNASAN_UTANG_TEKNIS.md`](./PRD_PELUNASAN_UTANG_TEKNIS.md) §4 — roadmap ini
> menjalankannya, tidak mengulang alasannya.

**Urutan tahap TIDAK mengikuti urutan kode temuan.** A1 tidak dikerjakan lebih dulu
karena namanya A1. Susunannya: yang mengubah sifat kegagalan seluruh sistem → yang
menjadi berbahaya justru karenanya → pintu anonim termurah → saluran keluar →
kejujuran permukaan publik → keputusan besar yang tertunda.

Berkas uji tunggal untuk seluruh roadmap: **`docs/engineering/uji_utang_teknis.php`**
(PHP CLI + curl + `assert()`, exit code non-nol kalau gagal). Tiap tahap MENAMBAH blok
assert ke berkas yang sama — jangan bikin berkas kedua.

---

## U0 — Hentikan drift repo ↔ production, dan kirim pertanyaannya hari ini

**Tujuan.** Nol setelan keamanan yang hidup hanya di server dan mati setiap deploy —
plus, di hari yang sama, user menerima tabel keputusan supaya jamnya mulai berjalan
paralel dengan tahap berikutnya.

**Butir yang ditutup:** B1, B11.

**Isi:**

- **[B1] Taruh `SetEnv CI_ENV production` di `.htaccess` REPO, bukan di server.**
  Ini akar yang benar, bukan gejalanya. Catatan F1 inventaris: `git log -S"SetEnv CI_ENV" -- .htaccess`
  **kosong** — baris itu tidak pernah ada di repo; `.htaccess` server sudah identik
  dengan repo SEBELUM rilis 29 Juli (hash sama di server, `fbd72a8`, dan `2f83243`),
  jadi baris itu hilang sebelum rilis, bukan tertimpa olehnya. Memperbaikinya dengan
  login ke panel hosting = menjadwalkan kejadian yang sama untuk rilis berikutnya.
- **[B1, lapis kedua] Pertimbangkan default fail-closed di `index.php:56`.**
  Hari ini: `define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development')`.
  Default `development` adalah pola "pengaman yang mati diam-diam saat konfigurasinya
  kosong" yang sudah punya baris sendiri di tabel pola kegagalan §19. Perubahannya
  satu baris, tapi ia **mengubah perilaku instalasi lokal setiap developer** — jadi
  keputusannya dicatat eksplisit di tahap ini, bukan diselipkan. Kalau diambil,
  `.env`/vhost lokal wajib menyetel `CI_ENV=development` dan itu masuk
  `docs/engineering/SETUP_DATABASE.md`.
- **[B11] `cookie_secure = TRUE` di `config/config.php`.** TERBANTAH sebagai bug di
  production (cookie ber-`secure` + HSTS aktif), tapi akarnya identik dengan B1:
  setelan keamanan yang hidup hanya di luar repo. Instalasi baru dari repo akan lahir
  tanpa `secure`. Satu baris, satu tema, satu tahap.
- **[Deliverable ke USER, bukan catatan kaki] Kirim tabel §6 PRD** — delapan
  keputusan, masing-masing dengan pilihan dan konsekuensinya. Ini yang membuka U4, U5,
  U6 dan sebagian U3. Dikirim di U0 justru karena waktu berpikir user tidak boleh
  antre di belakang pekerjaan kode.
- **[Aksi user, tidak bisa dipercepat agent] Terapkan `.htaccess` yang sudah berisi
  `SetEnv` ke production** lewat File Manager/SSH atau lewat rilis berikutnya.
  Sampai itu terjadi, B1 **belum selesai di production** walau selesai di repo — tulis
  apa adanya.

**Berkas yang disentuh:** `.htaccess`, `application/config/config.php`,
`index.php` (kalau lapis kedua diambil), `docs/engineering/SETUP_DATABASE.md`
(kalau lapis kedua diambil).

**Cara membuktikan:**

1. Lokal: `curl -s http://localhost/klinik_new/Umum/info_rumah | grep -c "A PHP Error"` → **0**.
2. Lokal: `curl -sI http://localhost/klinik_new/ | grep -i "set-cookie"` → memuat
   `secure` (di lingkungan HTTPS; di HTTP lokal cukup buktikan nilainya di config
   sudah TRUE dan catat batas ujinya).
3. Production, setelah user menerapkan: `curl -s https://<host>/Umum/info_rumah` →
   nol `A PHP Error was encountered`, nol `/home/u504551489`. **Ini yang membedakan
   "selesai di repo" dari "selesai".**
4. **Uji balik:** hapus baris `SetEnv` dari `.htaccess` lokal, ulangi (1) → path server
   muncul lagi. Kalau tidak muncul, `.htaccess` lokalmu tidak dibaca dan uji (1) tidak
   membuktikan apa pun.

**Risiko regresi.** Tinggi dan terarah — inilah tahap paling berbahaya di seluruh
roadmap justru karena perbaikannya benar:

- **Setelah tahap ini, kegagalan tulis di production menjadi SENYAP.** `db_debug`
  ikut mati (`database.php:85`: `'db_debug' => (ENVIRONMENT !== 'production')`).
  Enam titik flash "berhasil" tanpa cek (A5) mulai berbohong, dan rate limit forum
  (C1b) mulai terlewati diam-diam. **U1 adalah mitigasinya dan tidak boleh berjarak
  jauh dari U0.** Kalau U1 tidak bisa segera menyusul, tunda penerapan ke production
  — bukan tunda perbaikan repo-nya.
- Kalau lapis kedua (`index.php:56`) diambil, developer yang belum menyetel `CI_ENV`
  lokal akan tiba-tiba kehilangan tampilan error. Itu tujuannya, tapi ia akan terbaca
  sebagai "aplikasinya rusak".

**Beban.** Menit di sisi repo. Bagian production menunggu user.

---

## U1 — Yang menjadi berbahaya justru karena U0

**Tujuan.** Tidak ada layar yang mengatakan "berhasil" tanpa memeriksa hasil tulis,
dan forum benar-benar bisa diposting — dua kelas yang efeknya baru muncul setelah
lampu sorot U0 dipadamkan.

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
- **[C1b] Verifikasi ulang perilaku dengan `db_debug` FALSE.** Setel
  `$db['default']['db_debug'] = FALSE` di `application/config/database.php:85` secara
  eksplisit, kembalikan sesudahnya. **JANGAN lewat `CI_ENV=production`** — itu ikut
  mematikan tampilan error untuk semua sebab lain, sehingga uji yang gagal karena hal
  lain jadi tak terlihat (§19 langkah 12).

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
4. **Uji balik (ini definisi selesainya):** kembalikan `'diskusi'` mentah ke
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

## U2 — Tutup pintu anonim yang paling murah dieksploitasi

**Tujuan.** Tidak ada endpoint yang bisa dipanggil anonim untuk menguras kuota,
menahan worker PHP, atau menyensor komentar orang lain.

**Butir yang ditutup:** B2, B3, B6 (dan konsekuensinya pada butir E).

**Isi:**

- **JANGAN targetkan CSRF.** B12 sudah membuktikan token bisa diambil anonim lewat
  satu GET (`csrf_regenerate=FALSE`, double-submit). B12 ada di inventaris justru
  untuk mencegah perbaikan salah sasaran. Yang menutup: **guard login + rate limit**.
- **JANGAN bikin mekanisme rate limit baru.** `application/config/rate_limits.php`
  sudah berisi 8 policy dengan dimensi `ip`/`account`/`nik`/`object`, dipakai lewat
  `MY_Controller::rate_limit_sisa()` + `rate_limit_catat()` (tabel `sys_rate_limits`)
  oleh `Program::cek_tiket()`, `Warga::lookup()`, `Admin_Kabkota::update_status()`.
  Perbaikan di bawah menambah **entri policy**, bukan mekanisme (§17 poin 15,
  catatan F4 inventaris).
- **[B2] `Chat.php:80`: `public function api_bot` → non-public.** Satu kata.
  `grep -rn "api_bot" application/` mengembalikan tepat dua hasil: deklarasi di `:80`
  dan pemanggil internal di `:62` (`$this->api_bot($pesan_warga)` dari
  `kirim_pesan_lanjutan()`). Mengubahnya jadi `private` mencabut routabilitas GET
  tanpa menyentuh perilaku internal. **Dikerjakan sekarang, tidak menunggu keputusan
  chat (§6 PRD nomor 7), karena perubahan satu kata ini tidak terbuang di bawah
  keputusan yang mana pun** — sementara kuota Gemini terkuras setiap hari menunggu.
- **[B3] `Umum::report_komentar()` (`Umum.php:374`): guard login + dedup.** Guard-nya
  sudah ada 16 baris di bawah, di berkas yang sama: `Umum::toggle_like()` (`:390-395`)
  memanggil `is_logged_in()` dan membalas `'Login required'`. Salin. Untuk dedup,
  bentuk datanya juga sudah ada: `forum_likes` (`user_id` + `target_type` +
  `target_id`, dipakai `Forum_model::toggle_like()` `:121-126`) adalah persis bentuk
  "satu aksi per pengguna per objek" yang dibutuhkan — hari ini
  `Forum_model::report_komentar()` (`:96-100`) hanya menaikkan `report_count` tanpa
  mencatat siapa pelapornya, jadi 5 POST dari satu orang sama nilainya dengan 5 orang.
  Tambah policy rate limit untuk kelas endpoint ini sekalian.
- **[B6] Cabut route `$route['sikaper']` di `application/config/routes.php:125`.**
  `Sikaper.php:4` satu-satunya controller yang `extends CI_Controller` — di luar
  `MY_Controller`, jadi di luar security header — dan `index()` (`:21-25`) memicu
  **5 panggilan upstream** memakai kredensial dinas per hit anonim. Setelah route
  dicabut, `application/views/pages/sikaper/index.php` menjadi yatim (butir E): hapus
  di tahap ini juga, jangan tinggalkan sebagai "nanti". Apakah `Sikaper.php` dan
  `Sikaper_api.php` ikut dihapus tergantung keputusan #5 (§6 PRD) — route dulu.

**Berkas yang disentuh:** `application/controllers/Chat.php`,
`application/controllers/Umum.php`, `application/models/Forum_model.php`,
`application/config/rate_limits.php`, `application/config/routes.php`,
`application/views/pages/sikaper/index.php` (dihapus),
`docs/engineering/uji_utang_teknis.php`. Kemungkinan satu migrasi untuk tabel pelapor.

**Cara membuktikan:**

1. `curl -s -o /dev/null -w "%{http_code}" http://localhost/klinik_new/Chat/api_bot/halo` → **404**
   (hari ini: 200 dalam 1,8–6 detik). Uji balik: kembalikan `public` → 200 lagi.
2. 6× POST `Umum/report_komentar` anonim untuk satu `id_komentar` → semuanya ditolak,
   dan `SELECT report_count, is_deleted FROM forum_komentar WHERE id_komentar=?`
   **tidak berubah sama sekali**.
3. 5 akun login berbeda melapor komentar yang sama → `report_count` = 5. Akun pertama
   melapor lagi → tetap 5, dan responsnya menjelaskan sebabnya.
4. `curl -s -o /dev/null -w "%{http_code}" http://localhost/klinik_new/sikaper` → **404**,
   dan durasinya turun dari 5,45 detik ke tingkat 404 biasa. Uji pembeda antara
   "route dicabut" dan "halaman kosong": responsnya tidak boleh memicu satu pun
   panggilan upstream.
5. `grep -rn "pages/sikaper" application/` → nol pemanggil SEBELUM berkasnya dihapus.

**Risiko regresi.**

- Widget chat di `layouts/footer.php` sudah rusak hari ini (C2: `tb_chat` tidak ada),
  jadi B2 tidak memutus fitur yang berjalan. Tapi kalau keputusan #7 nanti jatuh ke
  "bangun", `kirim_pesan_lanjutan()` tetap memanggil `api_bot()` secara internal —
  pastikan `private`, bukan dihapus.
- Guard login pada B3 **menghapus kemampuan tamu melaporkan komentar.** Itu memang
  tujuannya, tapi sebutkan ke user: kanal konsultasi warga kehilangan satu jalur
  moderasi anonim. Layar moderasi admin adalah fitur baru dan **tidak** dikerjakan
  di sini (§7 PRD).
- Mencabut route `/sikaper` menghilangkan satu halaman yang mungkin dipakai seseorang
  untuk melihat data Sikaper. Konfirmasi dengan user kalau ragu — tapi jangan
  pertahankan sebagai API explorer publik.

**Beban.** B2 satu kata. B6 satu baris + satu penghapusan berkas. B3 yang terbesar
(butuh tabel/kolom pelapor kalau dedup dibuat benar). Satu sesi.

---

## U3 — Saluran keluar diverifikasi, dan kunci berhenti gagal-terbuka

**Tujuan.** Kredensial dinas berhenti dikirim di atas koneksi yang tidak diverifikasi,
dan library enkripsi berhenti mengembalikan plaintext saat kuncinya hilang.

**Butir yang ditutup:** B4, B9. B5 **sebagian** (dan itu batasnya, lihat di bawah).

**Isi:**

- **[B4] 12 titik `CURLOPT_SSL_VERIFYPEER => false` di 6 berkas, semuanya
  unconditional.** Dihitung sendiri 29 Jul: `Index.php:126, :178, :239, :309, :386, :583`
  (6), `Chat.php:121` (+`:122` VERIFYHOST), `Umum.php:70, :513` (2),
  `Ternak_api.php:42` (+`:43`), `Sikumbang.php:82`, `Sikaper_api.php:36`.
  **Pembanding yang BENAR ada di repo yang sama:** `Simperum_gateway.php:155-156`
  (`CURLOPT_SSL_VERIFYPEER => TRUE`, `CURLOPT_SSL_VERIFYHOST => 2`). Salin polanya;
  jangan bikin wrapper HTTP baru. Dua titik terparah: `Sikaper_api.php:36` mengirim
  header `Authorization: Basic`, dan `Chat.php:121` mengirim API key Gemini di query
  string (`Chat.php:86`).
- **Urutan internal mengikat: B4 SEBELUM B5.** Rotasi kredensial Sikaper menunggu
  pihak ketiga dan bisa lama. Selama menunggu, memperbaiki TLS **mengurangi laju
  panen** kredensial yang belum bisa dirotasi. Urutan sebaliknya berarti menunggu
  pihak ketiga sambil terus membocorkan.
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
  harus gagal keras**. Ini pola "pengaman yang mati diam-diam saat konfigurasinya
  kosong" yang sudah punya baris sendiri di tabel §19.
- **[B5, yang bisa kita kerjakan] Pindahkan kredensial Sikaper dari
  `config/sikaper.php:13-14` ke `.env`** — **tapi catat apa adanya bahwa ini NOL
  perbaikan keamanan** selama nilainya belum dirotasi: ia sudah masuk 2 commit riwayat
  git (`git log -S`), dan riwayat git tidak bisa dilupakan dengan memindahkan
  berkasnya. Nilainya hanya sebagai pencegah kebocoran BERIKUTNYA. **Rotasinya ada di
  tangan pengelola API Sikaper, bukan kita** — lihat keputusan #5 (§6 PRD). Jangan
  menuliskan tahap ini sebagai "B5 selesai".

**Berkas yang disentuh:** `application/controllers/Index.php`,
`application/controllers/Chat.php`, `application/controllers/Umum.php`,
`application/controllers/Sikumbang.php`, `application/libraries/Ternak_api.php`,
`application/libraries/Sikaper_api.php`, `application/libraries/Encryption_lib.php`,
`application/config/sikaper.php`, `.env.example`,
`docs/engineering/uji_utang_teknis.php`.

**Cara membuktikan:**

1. `grep -rn "SSL_VERIFYPEER" application/ | grep -c "false"` → **0**. Ini uji
   termurah, tapi ia hanya membuktikan diff-nya benar, bukan TLS-nya bekerja.
2. **Yang benar-benar membuktikan:** arahkan satu klien ke host bersertifikat tidak
   sah (mis. `https://expired.badssl.com/` atau host uji lokal) → permintaan **gagal**
   dengan galat sertifikat. Hari ini ia berhasil. **Uji balik:** kembalikan
   `VERIFYPEER => false` pada klien itu → berhasil lagi.
3. Setelah B4, panggil tiap integrasi yang masih dipakai (Sikumbang, Ternak, Gemini,
   Simperum) sekali → semuanya masih 200. Yang gagal dicatat namanya beserta sebab
   sertifikatnya; **jangan** dimatikan verifikasinya.
4. **B9:** kosongkan `KPKP_DATA_KEY` di `.env` lokal, panggil `encrypt('12345')` →
   **exception/FALSE**, bukan `'12345'`. Uji balik: kembalikan `return $plaintext` →
   assert merah.
5. `grep -rn "sikaper_api_password" application/config/` → nol nilai literal.

**Risiko regresi.**

- **Terbesar di seluruh roadmap dalam hal "bisa mematahkan fitur yang berjalan".**
  Menyalakan verifikasi TLS bisa mematikan integrasi yang selama ini hidup justru
  karena verifikasinya mati. Respons Sikumbang di-cache 24 jam, jadi kegagalannya
  bisa baru terlihat sehari kemudian — **jangan nyatakan selesai di hari yang sama.**
- B9 mengubah `encrypt()` dari "selalu mengembalikan sesuatu" menjadi "bisa gagal".
  Telusuri pemanggilnya: jalur yang tidak menangani kegagalan akan berubah dari
  "menyimpan plaintext diam-diam" menjadi "error". Yang kedua benar, tapi ia akan
  muncul sebagai kerusakan baru di layar. Periksa pemanggilnya sebelum, bukan sesudah.
- Memindahkan kredensial Sikaper ke `.env` akan mematikan integrasinya di setiap
  lingkungan yang `.env`-nya belum diisi — termasuk staging dan production.

**Beban.** Sedang. 12 titik mekanis + satu library + satu config, tapi verifikasinya
menuntut memanggil upstream sungguhan dan menunggu cache. 1–2 sesi, dan satu di
antaranya menunggu.

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
   sistem itu. Kalau halaman dipertahankan dengan data nyata, uji pembeda termurahnya:
   **kedua respons tidak boleh menampilkan persentase yang identik**.
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
> minggu, dengan §17 checklist penuh + §19 dua belas langkah. **Jangan menyentuh
> `Chat.php` sebelum jawabannya turun** — kecuali B2 di U2, yang satu kata dan tidak
> terbuang di bawah pilihan mana pun.

**Isi bila pilihan (a) — cabut:**

- Hapus `application/controllers/Chat.php`, `application/models/Chat_model.php`,
  widget chat di `application/views/layouts/footer.php` (termasuk `:165`,
  `session_id` dari `Math.random()`), dan route terkait.
- Tabel `chat_rooms`/`chat_messages` ADA di DB tapi 0 baris, hanya disentuh
  `Chat_model` yang nol pemanggil, dan **tidak lahir dari migrasi** — instalasi baru
  tidak akan punya. Jangan tulis migrasi drop untuk tabel yang tidak pernah dibuat
  migrasi; catat sebagai pembersihan DB manual di lingkungan yang punya (pelajaran
  `omah_sekeng`, §0e: keadaan yang lahir dari tangan seseorang bukan keadaan yang
  bisa diandalkan kode).
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
  jaringan ke `/Chat/*`. `SELECT COUNT(*) FROM chat_rooms` = 0 dan tidak ada kode yang
  membacanya.
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

## U6 — NIK, dan penutupan status yang tidak di tangan kita

**Tujuan.** Menutup butir terakhir yang tersisa, dan menuliskan apa adanya mana yang
tidak bisa kita selesaikan sendiri.

**Butir yang ditutup:** B8 (setelah keputusan #8). B5 dan B10 **dinyatakan statusnya**,
tidak diklaim selesai.

**Isi:**

- **[B8, setelah keputusan #8] NIK plaintext di `sf_housing_queue.nik_pengaju`.**
  Yang membuat butir ini bukan sekadar "enkripsi kolomnya", dibaca sendiri 29 Jul:
  `Program_model.php:121` menulis `'nik_pengaju' => $nik` mentah, dan
  `Program_model.php:205` mencari tiket dengan
  `->where('RIGHT(nik_pengaju, 4) =', $nik_suffix, FALSE)`. **Pencarian sufiks itu
  mustahil di atas ciphertext AES-GCM.** Jadi mengenkripsi kolom ini **memutus alur
  cek tiket warga** yang sudah dipresentasikan ke dinas
  ([`DESAIN_STATUS_TIKET_PENGAJUAN.md`](./DESAIN_STATUS_TIKET_PENGAJUAN.md)).
  Pola yang benar sudah ada di repo: `usr_users` menyimpan ciphertext + kolom
  `*_lookup_hash` terpisah (§19 langkah 5 melarang eksplisit menyalin pola
  `srp2_registrations.nik_ktp` VARCHAR(16) plaintext ber-UNIQUE). Perbaikannya =
  migrasi data + kolom hash sufiks + mengubah `Program_model.php:205`. Butuh migrasi
  data tersendiri karena menyentuh data lama, persis batas yang sudah dicatat
  [`PRD_WARGA_ADMIN_KABKOTA.md`](./PRD_WARGA_ADMIN_KABKOTA.md) §Batas Fase.
- **[B5] Tulis statusnya apa adanya.** Kredensial sudah dipindah ke `.env` (U3), nilai
  lamanya **masih sah dan masih ada di riwayat git**. Selesai hanya setelah pengelola
  API Sikaper merotasinya. Kalau keputusan #5 jawabannya "tidak ada kanal", opsi yang
  tersisa adalah mencabut integrasi Sikaper sepenuhnya — dan itu keputusan produk,
  bukan pekerjaan tahap ini.
- **[B10] Tulis statusnya apa adanya.** Sampai user menunjukkan hasil **percobaan
  login yang GAGAL** ke `admin@klinikpkp.jatengprov.go.id` di production, statusnya
  **MASIH TERBUKA** — bukan "sudah saya ganti". Agent tidak boleh mencoba login
  production; auditor 29 Juli juga sengaja tidak. Selama ini terbuka, blok kredensial
  demo yang sengaja dipertahankan (§17 poin 14) **melanggar syaratnya sendiri**:
  syaratnya adalah setiap akun tercantum harus akun demo, dan akun admin penuh bukan
  akun demo.
- **[Penutup] Perbarui `AGENTS.md` §18** dengan status akhir PER BUTIR, termasuk yang
  **TERBANTAH** (B11 di production, dan A5/C1b yang premis `db_debug`-nya salah —
  lihat Catatan kejujuran nomor 2). Yang terbantah lebih penting dicatat daripada yang
  diperbaiki: itulah yang mencegah agent berikutnya "memperbaiki" bug yang tidak ada.

**Berkas yang disentuh:** `application/models/Program_model.php`, satu migrasi baru,
`AGENTS.md`, `docs/engineering/uji_utang_teknis.php`.

**Cara membuktikan:**

1. `SELECT nik_pengaju FROM sf_housing_queue LIMIT 5` → tidak ada yang cocok
   `^\d{16}$`.
2. Cek tiket end-to-end lewat HTTP: tiket + 4 digit NIK yang benar → status muncul;
   4 digit yang salah → ditolak. **Ini uji yang menentukan** — kalau alur cek tiket
   putus, migrasinya belum selesai walau kolomnya sudah terenkripsi.
3. Migrasi dijalankan **2× berturut-turut** → hasil DB identik (idempoten).
4. `SELECT COUNT(*) FROM sf_housing_queue WHERE <kolom_hash> IS NULL` = 0 (backfill
   baris lama; §19 pola "migrasi menambah kolom tautan, tidak mengisi baris lama").

**Risiko regresi.** Tertinggi dari sisi data: menyentuh baris yang sudah ada di
production. Uji di salinan dulu. Dan ingat batas yang sudah ditulis §9 AGENTS.md dan
§0c: **`KPKP_DATA_KEY` tidak boleh dirotasi** — data terenkripsi yang ada akan hilang
permanen.

**Beban.** B8 hari–minggu tergantung jawaban #8. Sisanya menulis status.

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
- **[ROADMAP LAIN] T0–T6 role pengembang** dan **R0–R9 alur warga**. **ALASAN:**
  keduanya bekerja per-perjalanan; menyisipkan temuan lintas-domain membuat tiap
  tahapnya berhenti menghasilkan perjalanan yang bisa ditempuh. Bagian
  "Sengaja TIDAK masuk roadmap ini" milik [`ROADMAP_PENGEMBANG_ADMIN.md`](./ROADMAP_PENGEMBANG_ADMIN.md)
  bahkan menyarankan roadmap terpisah untuk butir-butir ini — ini roadmap terpisah itu.
  **Satu titik singgung yang harus dikoordinasikan:** `Admin_Srp2.php` disentuh U1
  (A5, `:296`) dan T1a/T1b. Jangan dua pola.
- **[PENGERASAN, BUKAN UTANG] CSP.** Production sudah punya X-Frame-Options, nosniff,
  HSTS 1 tahun, Referrer-Policy, Permissions-Policy; CSP nihil. **ALASAN:** bukan
  butir inventaris, dan memasangnya di portal berisi Leaflet + inline script butuh
  sesi tersendiri dengan definisi selesai sendiri.
- **[FITUR BARU] Layar moderasi forum untuk B3.** **ALASAN:** B3 selesai dengan guard
  login + dedup + rate limit. Layar admin untuk meninjau laporan adalah produk baru
  dan tunduk §17 checklist penuh. Kalau user memintanya, buat entri
  `dashboard_modules.php`-nya sebagai modul, bukan sebagai tambalan di U2.
- **[USULAN YANG DIBUANG] Wrapper HTTP baru untuk memusatkan setelan TLS (B4).**
  **ALASAN:** 12 titik di 6 berkas dengan pola yang sudah benar di
  `Simperum_gateway.php:155-156`. Menyalin dua baris ke 6 tempat lebih kecil daripada
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
- **[AKSI USER, BUKAN AGENT] Rotasi sandi 7 akun seed di production, rotasi kredensial
  Sikaper, penerapan `.htaccess` ke production, pengisian kunci reCAPTCHA.**
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
dipindah. **Saya tidak bisa memverifikasi ini di production sendiri; ia inferensi
dari tiga berkas + satu bukti runtime milik auditor.**

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

**6. Nol akses production, nol akses staging.** Semua yang saya tulis tentang
production berasal dari verifikasi runtime auditor 29 Juli, bukan dari saya. Saya
tidak menjalankan satu pun request ke production dan tidak menjalankan satu pun query
DB. Termasuk: apakah `.htaccess` sudah/akan diterapkan, apakah 7 akun seed masih ada
di sana, apakah `chat_rooms` ada di DB production.

**7. B10 sengaja BELUM DIVERIFIKASI.** Auditor tidak mencoba login production, dan
saya juga tidak. Statusnya ditulis MASIH TERBUKA — itu bukan kesimpulan bahwa sandinya
masih `password`, melainkan pernyataan bahwa **tidak ada yang tahu**, dan tidak-tahu
diperlakukan sebagai terbuka.

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

**10. Tiga keputusan yang saya ambil sendiri dan bisa dibantah:**
(a) **B1 lebih dulu** meski ia mematikan display_errors — alasannya di PRD §4 Aturan 1;
kalau seseorang berpendapat production layak dipakai sebagai instrumen diagnosa
sebentar lagi, urutannya berubah. (b) **B2 tidak menunggu keputusan chat** — karena
perbaikannya satu kata dan tidak terbuang di bawah keputusan mana pun; kalau ternyata
ada pemanggil `api_bot` di luar `application/` (saya hanya grep di sana), ini salah.
(c) **Tidak membuat wrapper HTTP untuk B4** — 12 titik disalin manual dari pola
`Simperum_gateway.php:155-156`; kalau klien ke-7 lahir, keputusan ini pantas dibuka
lagi.

**11. Skrip `uji_utang_teknis.php` belum ada, dan belum pernah dijalankan.** Semua
"cara membuktikan" di roadmap ini adalah **spesifikasi**, bukan uji yang sudah lulus.
Butir yang tidak boleh dipotong dari tahap mana pun adalah **uji balik**-nya: balikkan
satu perbaikan, jalankan lagi, skripnya MERAH. Skrip yang tidak pernah gagal tidak
membuktikan apa pun (§19 langkah 12).
