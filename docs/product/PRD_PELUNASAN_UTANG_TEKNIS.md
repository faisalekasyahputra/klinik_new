# PRD Pelunasan Utang Teknis

**Tanggal:** 29 Juli 2026
**Sumber isi:** inventaris utang teknis terverifikasi 29 Juli 2026 (dua audit subagent + verifikasi silang)
**Urutan pelaksanaan:** [`ROADMAP_PELUNASAN_UTANG_TEKNIS.md`](./ROADMAP_PELUNASAN_UTANG_TEKNIS.md)
**Status:** **DRAFT TERKOREKSI — belum boleh dieksekusi langsung ke production**

> **Koreksi 29 Juli 2026 (audit pasca-commit `ac42941`):** inventaris A/B/C di
> dokumen awal berguna tetapi tidak lengkap. Sembilan temuan stabilisasi terbaru
> ditambahkan sebagai S1–S9 di §3.5. Roadmap wajib memenuhi kontrak keselamatan
> harness dan rilis di §5.1 sebelum satu tahap pun didelegasikan.

> **Konteks yang membentuk seluruh dokumen ini:** portal ini SEDANG dipakai uji coba
> dinas di production. Setiap temuan di bawah punya pengunjung anonim hari ini.
> Itu mengubah urutan — bukan cuma daftar isinya.

---

## 1. Latar — dan kenapa angkanya bukan "141 temuan" lagi

`AGENTS.md` §18 masih menyebut "141 temuan" dari pembacaan 26 Juli 2026. Angka itu
**tidak boleh dipakai sebagai ukuran utang hari ini**, karena tiga alasan:

1. **Sebagian besar sudah selesai.** Paparan `.env`, tiga lokasi upload publik,
   IDOR penyajian berkas, `Migrate.php`, SQL injection, header keamanan,
   `action="#"`, klaim "tersinkronisasi" palsu — semuanya sudah ditutup dan
   diverifikasi runtime (bagian D inventaris). Mengerjakannya ulang adalah biaya
   murni.
2. **Sebagian terbantah.** `cookie_secure = FALSE` (B11) memang benar di kode,
   tapi production TERBUKTI mengirim cookie ber-`secure` + HSTS. Yang tersisa
   bukan bug melainkan drift. `Stored XSS` skema URI sudah dibantah lewat
   pengujian sejak roadmap pengembang. Memperbaiki bug yang tidak ada menambah
   kode tanpa menambah keamanan dan menyesatkan agent berikutnya.
3. **Sebagian asumsi lamanya salah, bukan cuma usang.** Ini yang paling
   menentukan — lihat §3.2.

Yang dipakai dokumen awal adalah **inventaris prioritas 29 Juli** dengan 22 label
A/B/C. Itu **bukan angka total utang aktif**: B12 adalah pembatas desain, bukan bug;
sebagian butir berstatus drift/terbantah; dan audit pasca-commit menemukan S1–S9.
Dokumen ini karena itu menjadi inventaris prioritas yang dapat ditambah, bukan
klaim bahwa seluruh utang codebase sudah terhitung.

**Yang tidak berubah:** aturan §17 "jangan pernah menampilkan angka, status, atau
pesan sukses karangan" dan §19 langkah 12 "selesai = ada cara membuktikannya GAGAL".
Dokumen ini adalah penerapan keduanya ke tiga kelompok inventaris awal plus lapisan
stabilisasi S, bukan aturan baru.

---

## 2. Kenapa dokumen ini TIDAK memakai "Kartu Domain"

Cetakan PRD di repo ini ([`PRD_WARGA_ADMIN_KABKOTA.md`](./PRD_WARGA_ADMIN_KABKOTA.md))
dibuka dengan Kartu Domain: tabel induk, kolom pemilik, kardinalitas, kolom status,
scope, PII. Bentuk itu mengandaikan **satu tabel dengan satu siklus hidup**.

Dokumen ini tidak punya itu. Kelompok A menyentuh view tanpa tabel sama sekali
(`pages/spasial/*`, `struktur_organisasi.php` — HTML literal, nol query). Kelompok B
menyentuh konfigurasi server, klien HTTP keluar, dan library enkripsi. Hanya
kelompok C yang punya tabel (`forum_diskusi`/`forum_komentar`, `tb_chat` yang tidak
ada). **Memaksakan Kartu Domain di sini menghasilkan tabel berisi enam "—".**

Yang dipakai sebagai gantinya, mempertahankan semangat cetakan (kontrak eksplisit
sebelum kode, transisi, permukaan, gerbang, definisi selesai):

| Bagian cetakan | Padanan di dokumen ini | Alasan |
|---|---|---|
| Kartu Domain | **Kartu Kelompok** (§3.1) — sifat kerusakan, siapa yang bisa memicunya, siapa yang berwenang memutuskan | A/B/C dibagi menurut jenis kepercayaan; S adalah lapisan regresi lintas-perjalanan |
| Tabel Transisi | **tidak dipakai** | Tidak ada satu pun butir yang memindahkan status pengajuan. B3 satu-satunya yang mengubah keadaan data (`is_deleted`) dan itu efek samping bug, bukan transisi yang dirancang |
| Inventaris Permukaan | **Inventaris Permukaan Publik** (§3.3) — dipertahankan penuh | Justru inti kelompok A: tiap butir A adalah permukaan yang mengklaim sesuatu yang tidak benar |
| Gerbang Pengajuan | **Gerbang Anonim** (§3.4) | Padanan yang jujur: yang perlu digerbangi di sini bukan pengajuan, tapi endpoint yang bisa dipanggil tanpa login |
| Definisi Selesai | dipertahankan, **per kelompok** (§5) | |
| Batas Fase | **Batas** (§7) | |

---

## 3. Kartu Kelompok, dan fakta yang mengoreksi asumsi lama

### 3.1 Tiga kelompok

| | **A — Kepercayaan publik** | **B — Keamanan** | **C — Fitur mati** |
|---|---|---|---|
| Butir | A1–A6 | B1–B12 | C1, C1b, C2, C3 |
| Yang dilanggar | Kredibilitas institusi | Kerahasiaan & ketersediaan | Janji fungsional di UI |
| Pemicu | Pengunjung mana pun yang MEMBACA | Anonim, tanpa alat khusus | Pengguna yang mencoba memakai |
| Bisa dibalikkan? | **Tidak** — sekali dikutip, kutipannya hidup sendiri | Sebagian (rotasi kredensial) | Ya |
| Yang berwenang memutuskan | **User** untuk 4 dari 6 butir | Agent untuk implementasi; **User** untuk B5, B8, B10, dan perilaku ambang B3 | **User** untuk C2/C3 |
| Sifat perbaikan | Keputusan produk (isi apa yang menggantikan) | Perbaikan teknis | Campuran |

`B1–B12` adalah rentang label inventaris, bukan dua belas bug aktif: B12 merupakan
pembatas desain dan B11 adalah drift repo/environment. S1–S9 dicatat terpisah di
§3.5 agar kode lama tidak dinomori ulang.

**Konsekuensi paling penting dari tabel ini:** kelompok A bukan bug. Tidak ada yang
"rusak" di `pages/spasial/sebaran_rusun.php` — ia berjalan persis seperti yang
ditulis. Yang salah adalah APA yang ditulis, dan mengganti apa yang ditulis
membutuhkan seseorang yang tahu angka benarnya. Itu user, bukan agent. Agent hanya
boleh **mencabut**, tidak boleh **mengarang penggantinya** (§17).

### 3.2 Koreksi yang mengubah urutan: `db_debug` di production TIDAK mati

Inventaris menandai dua butir dengan kalimat "`db_debug` mati di production":
A5 (bukti) dan C1b (inferensi). Keduanya **bertentangan dengan B1**, yang
diverifikasi runtime 29 Juli.

Rantainya, dibaca sendiri di kode hari ini:

- `index.php:56` — `define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development')`
- `application/config/database.php:85` — `'db_debug' => (ENVIRONMENT !== 'production')`
- `.htaccess` repo (dibaca utuh) — **nol** `SetEnv CI_ENV`, sesuai catatan F1 inventaris
  (`git log -S"SetEnv CI_ENV" -- .htaccess` = kosong)
- B1 diverifikasi runtime: `GET /Umum/info_rumah` di production memuat
  "A PHP Error was encountered" + 3 path `/home/u504551489/...`

Kesimpulan: **selama B1 belum diperbaiki, `ENVIRONMENT` di production adalah
`development`, jadi `db_debug` bernilai TRUE di sana.** Kalimat "`db_debug` mati di
production" di A5 dan C1b adalah sisa asumsi dari 27 Juli, ketika `SetEnv` masih
terpasang di server.

Ini bukan koreksi kosmetik. Ia membalik arah bahaya:

| | Hari ini (B1 terbuka) | Sesudah B1 diperbaiki |
|---|---|---|
| A5 — 6 flash "berhasil" tanpa cek | Kegagalan tulis **berisik**: error tampil. Berbahaya karena membocorkan, bukan karena menyesatkan | Kegagalan tulis **senyap**: admin diberi tahu "Akun staff baru berhasil dibuat" atas INSERT yang ditolak UNIQUE |
| C1 — forum posting/balas | Mati **berisik**: 1146 tampil ke pengguna | Skenario C1b aktif: `count_all_results()` → FALSE → `FALSE < 5` = TRUE → rate limit terlewati |

Artinya: **memperbaiki B1 MENCIPTAKAN kondisi yang membuat A5 dan C1b berbahaya.**
Bukan menyembunyikan masalah yang sudah ada — menyalakannya.

### 3.3 Inventaris Permukaan Publik (kelompok A)

Setiap baris: apa yang permukaan ini KLAIM hari ini, dan apakah klaim itu punya
sumber.

| Permukaan | Klaim yang ditampilkan | Sumber klaim | Butir |
|---|---|---|---|
| `pages/spasial/sebaran_rusun.php` | 8.420 unit (`:140`); 6 rusunawa **bernama nyata** dengan okupansi (`:204-209`); "Terdapat keluhan kerusakan aset" (`:155`) | Literal di view. Nol query, nol API | A1 |
| `pages/spasial/profil_kumuh.php` | 4.250,8 Ha kawasan kumuh (`:132`) | Literal di view | A1 |
| `pages/spasial/sebaran_sdgs.php` | Rp 1,2 Triliun tersalurkan (`:132`); 142.500 KK (`:138`) | Literal di view | A1 |
| `Statistika.php:26-58` | 21 metrik berlabel "Sumber: Simperum / Sikumbang / Sikaper / Sikunang / Bank Tanah" | `konstanta × ((crc32($kabupaten) % 80) + 20)/1000` (`:21-22`) | A2 |
| `Statistika.php:39` | Persentase penanganan kumuh 70,5% | `round((850.2/1205.5)*100, 1)` — **tanpa multiplier**, jadi identik untuk 35 kab/kota | A2 |
| `pages/data_spasial/listkabupaten.php` | "Daftar Intervensi" Rp 500 jt (`:67`), Rp 750 jt (`:84`) + tombol Tambah/Edit/Hapus | Literal di view; tombol tanpa handler | A3 |
| `pages/profil/struktur_organisasi.php:63,66` | Nama **individu nyata** sebagai Kepala Dinas | Literal di view. Nol sumber data | A4 |
| `pages/profil/struktur_organisasi.php` (4 posisi lain) | "Nama Pejabat" — placeholder tampil ke publik | Literal di view | A4 |
| `admin/settings/index.php` | Nama aplikasi, email & telepon dinas (`:37,48,52`); toggle "Mode Pemeliharaan" (`:58`) | `<form class="space-y-5">` (`:34`) tanpa `action`/`method`/CSRF; tombol Simpan di `:7` **di luar** form; 4 tab `href="#"` (`:14,17,20,23`) | A6 |

**Koreksi terhadap inventaris (dibaca sendiri, 29 Jul):** ketiga halaman `spasial`
SUDAH memuat "(Data Simulasi)" di subjudulnya (`sebaran_rusun.php:48`,
`profil_kumuh.php:48`, `sebaran_sdgs.php:48`). Jadi ini bukan kasus "nol kejujuran".
Yang membuat A1 tetap Tinggi adalah hal lain, dan pembedaan ini menentukan
perbaikannya: **label simulasi tidak melisensikan penamaan aset dan lokasi NYATA**
(Rusunawa Kudu, Kraton, Pekalongan, Kaligawe, Mangkang, Cilacap — lengkap dengan
koordinat asli) dengan okupansi karangan dan tuduhan kerusakan aset. Angka
agregatnya bisa dibela sebagai simulasi berlabel; nama objek nyata dengan atribut
palsu tidak bisa.

Yang membuat A2 **lebih berat daripada A1** meski keduanya angka palsu: A1 berlabel,
A2 tidak — dan A2 menyebut nama sistem sumber yang sungguh-sungguh ada
("Sumber: Simperum"). Determinisme `crc32()` memperburuknya: angkanya konsisten
setiap kali dibuka untuk kabupaten yang sama, yang justru membuatnya **terasa** hasil
query.

### 3.4 Gerbang Anonim (kelompok B)

Endpoint yang bisa dipanggil tanpa login, hari ini:

| Endpoint | Gerbang hari ini | Yang dibutuhkan | Butir |
|---|---|---|---|
| `Chat::api_bot()` (`Chat.php:80`) | **Nihil**. `public`, jadi routable lewat GET `/Chat/api_bot/<pesan>`. Selain itu `kirim_pesan_lanjutan()` yang juga publik memanggilnya di `:62` | Jadikan method non-public **dan** nonaktifkan/gerbangi caller publik sampai keputusan chat turun. Mengubah satu kata saja tidak menghentikan pengurasan kuota | B2 |
| `Umum::report_komentar()` (`Umum.php:374`) | **Nihil**. Bandingkan `Umum::toggle_like()` 16 baris di bawahnya (`:390-395`) yang memanggil `is_logged_in()` | Guard login (salin dari `:392`) + dedup per (pelapor, objek) | B3 |
| `Sikaper::index()` (`Sikaper.php:13`) | **Nihil**, dan `Sikaper.php:4` satu-satunya controller yang `extends CI_Controller` — di luar `MY_Controller`, jadi di luar security header. Tiap hit anonim = 5 panggilan upstream memakai kredensial dinas (`:21-25`) | Hapus/nonaktifkan controller publik **dan** alias route. Menghapus alias saja tidak menutup routing konvensional CI3 (`/Sikaper/index`) | B6 |
| `Chat::register_session()` / `kirim_pesan_lanjutan()` / `ambil_pesan()` | Nihil; `ambil_pesan()` (`:157-159`) `result_array()` tanpa `select()` → seluruh kolom termasuk `nama_warga`/`email_warga`/`hp_warga` (`:24-26`), dikunci `session_id` buatan browser (`layouts/footer.php:165`, `Math.random()`) | Tergantung keputusan chat (§6 nomor 7) | B7, C2 |

**Yang TIDAK menutup gerbang ini: CSRF.** B12 sudah membuktikannya —
`csrf_regenerate=FALSE` + double-submit berarti token bisa diambil anonim lewat satu
GET. B12 bukan bug; ia ada di inventaris justru untuk mencegah perbaikan yang salah
sasaran.

**Yang menutupnya, dan sudah ada di repo:** `application/config/rate_limits.php`
(8 policy, dimensi `ip`/`account`/`nik`/`object`) dipakai lewat
`rate_limit_consume()` atau `rate_limit_inspect()`+`rate_limit_hit()`, lalu
`rate_limit_reject()`, di tabel `sys_rate_limits`.
§17 poin 15 melarang membuat mekanisme kedua. B3 menambah **entri policy** pada
registry yang sama. B2 tidak selesai hanya dengan membuat `api_bot()` private karena
`kirim_pesan_lanjutan()` tetap routable dan memanggilnya. Containment termurah:
sembunyikan widget dan buat seluruh endpoint Chat eksternal 404 sampai keputusan
chat turun; bila chat dibangun, barulah pakai policy registry yang sama.

### 3.5 Lapisan stabilisasi terbaru (S1–S9)

Audit pasca-commit `ac42941` menemukan sembilan butir yang tidak ada di inventaris
A/B/C. Semuanya berada dalam scope dokumen ini; menyatakan seluruh roadmap Warga
“di luar scope” tidak boleh dipakai untuk mengabaikannya.

| Kode | Fakta kode/runtime | Dampak | Kontrak perbaikan |
|---|---|---|---|
| **S1** | Portal dan dashboard melakukan `pushState()` tanpa `replaceState()` untuk halaman awal; `popstate` mengabaikan state kosong | Tombol Back mengubah URL tetapi isi tetap halaman kedua; direproduksi di browser 29 Jul | Rekam state awal, muat state kosong/URL saat Back, uji maju→mundur→maju pada portal dan admin |
| **S2** | Loader global `fetch()` dahulu lalu `window.location.href` untuk dokumen utuh; atribut `data-no-page-transition` tidak dihormati | GET ganda; `Umum::detail()` dapat menaikkan `view_count` dua kali per klik | Hormati opt-out sebelum fetch dan batasi loader ke respons/tautan yang memang partial; satu klik = satu GET |
| **S3** | `submit_owned_assessment()` tidak membaca `sf_berkas_penilaian` | Warga dapat mengajukan tanpa bukti yang diwajibkan matriks simulasi | Matriks `SIM-2026-01` di bawah ditegakkan server dan memeriksa ledger **serta file fisik**; syarat resmi Dinas/dua bukti kondisional tetap menunggu OPEN-WRG-008 |
| **S4** | Hapus akun tidak membersihkan assessment/snapshot/berkas Warga; FK owner memakai `SET NULL` | Data pribadi tetap hidup setelah UI “Hapus Akun Secara Permanen” | Segera luruskan janji UI; cleanup menunggu kebijakan retensi semua kelas data, bukan hanya tombol hapus akun |
| **S5** | Runner fresh hanya mengganti `DB_NAME`, tidak `PRIVATE_UPLOADS_PATH` | ID DB sementara dapat bertabrakan dan pembersih menghapus berkas dev | Seluruh harness DB-sementara wajib memakai root upload sementara yang unik dan dipulihkan byte-identik |
| **S6** | `Admin_Kabkota::update_status()` memakai policy keputusan, `Admin::update_status()` tidak | Jalur superadmin melewati rate limit yang sudah tersedia | Kedua jalur memakai policy registry yang sama dengan dimensi akun+objek |
| **S7** | Audit Warga hanya mencatat transisi antrean; perubahan PII, submit, dan refresh SIMPERUM tidak semuanya menjadi event | FR-WRG-020 belum terpenuhi | Setelah keputusan retensi S4: event berisi actor/object/versi/waktu/reason; refresh memakai `attempt_id` requested→succeeded/failed; canonical plaintext diff memakai **keyed HMAC**, bukan ciphertext; riwayat keputusan tetap sumber transisi |
| **S8** | `AGENTS.md`, roadmap Warga, dan status produksi saling bertentangan; `migration_version` masih menunjuk versi lama | Agent berikutnya dapat merilis dengan urutan salah atau memanggil `current()` yang menurunkan skema | Satu sumber status aktif; dokumen lama diberi penanda historis; check memastikan `migration_version` = migrasi tracked tertinggi dan tooling proyek melarang `current()` |
| **S9** | `/Umum/info_rumah` sendiri rusak karena include layout yang tidak ada | Menutup `display_errors` menghilangkan bocoran tetapi tidak membuat halaman berfungsi | Cabut route/view legacy atau render lewat layout yang benar; uji B1 harus terpisah dari uji fungsi halaman |

#### Matriks bukti S3 yang sudah sah untuk simulasi `SIM-2026-01`

Matriks ini berasal dari
[`ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md`](./ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md)
R3/R6, bukan aturan resmi Dinas:

| `assessment_track` | Wajib untuk submit simulasi | Kondisional, belum menjadi gate | Opsional/non-blocking |
|---|---|---|---|
| `existing_house` | `self_photo`, `house_front_photo`, `house_side_photo`, `roof_photo`, `floor_photo`, `wall_photo`, `latrine_photo` | — | `land_photo` |
| `candidate_land` | `candidate_land_photo`, `recipient_photo`, `id_card_photo`, `family_card_photo` | `land_owner_family_card_photo`, `land_transfer_proof` | `verification_report` hanya milik admin dan bukan jenis unggah Warga |
| `financing` | `id_card_photo`, `family_card_photo` | — | Bukti struktur tidak relevan |
| `undetermined` | Tidak boleh submit | — | — |

Untuk dua bukti kondisional calon lahan, kode belum mempunyai pemicu kanonik
“pemilik bukan pemohon” dan “pindah tangan relevan”. Keduanya **tidak boleh
diwajibkan berdasarkan tebakan** sampai keputusan #11. Saat validasi dijalankan,
baris ledger saja tidak cukup. Gunakan resolver storage yang memahami
`storage_assessment_id` pada revisi; `realpath` harus berada di root privat, file
harus terbaca, dan ukuran+SHA-256 harus cocok dengan ledger. Validasi mengikuti
required `file_kind` track aktif; KTP/KK yang overlap boleh dipakai ulang karena
ledger belum memiliki `track_at_upload`. Kebijakan freshness/reupload belum ada dan
tidak boleh dikarang.

---

## 4. Prinsip urutan

Empat aturan, dipakai berurutan saat dua butir bersaing. Ini bukan daftar; tiap
aturan punya lawan yang saya tolak, dan alasannya ditulis.

### Aturan 1 — Yang mengubah SIFAT kegagalan seluruh sistem didahulukan, dan tidak boleh berjalan sendirian

B1 lebih dulu. Bukan karena "Tinggi + effort menit" — itu argumen yang membuat orang
mengurutkan berdasarkan tabel dampak dan berhenti berpikir. Alasannya: selama B1
terbuka, **setiap bug lain di sistem ini adalah kebocoran informasi**. Bug forum
1146 bukan lagi fitur mati, ia mencetak nama database dan path server ke pengunjung.
Kegagalan query mana pun mencetak SQL beserta datanya. Tidak ada butir lain yang
mengubah arti seluruh daftar seperti ini.

**Lawannya, dan ini nyata:** memperbaiki B1 mematikan display_errors — yang berarti
**menyembunyikan bug yang sedang kita buru.** Kalau kita sedang memakai production
sebagai instrumen diagnosa, kita baru saja mematikan instrumennya.

**Saya menolak lawan itu, tiga alasan:**

1. **Production tidak pernah boleh jadi instrumen diagnosa.** §19 langkah 12 sudah
   menetapkan instrumen yang benar: reproduksi lokal dengan `db_debug` disetel FALSE
   secara eksplisit di `database.php:85` — **bukan** lewat `CI_ENV=production`,
   karena yang terakhir ikut mematikan tampilan error untuk semua sebab lain
   sehingga uji yang gagal karena hal lain jadi tak terlihat. Instrumen yang kita
   butuhkan adalah saklar lokal yang kita pegang, bukan kebocoran production yang
   kebetulan informatif.
2. **Arah bahayanya berlawanan dengan yang diasumsikan (§3.2).** Memperbaiki B1
   tidak menyembunyikan A5 dan C1b — ia **mengaktifkannya**. Hari ini kegagalan tulis
   di production berisik; sesudah B1 ia senyap dan berbohong. Jadi B1 bukan
   "perbaikan yang menutupi", ia "perbaikan yang menciptakan utang baru pada butir
   yang sudah kita ketahui".
3. **Karena itu B1 tidak boleh dirilis sendirian.** A5 dan C1 harus diselesaikan
   dan dibuktikan **lebih dulu di lokal**, lalu B1+B11+A5+C1 dirilis sebagai satu
   unit atomik. Pada branch auto-deploy, “push U0 lalu lanjut U1” tetap menciptakan
   jendela production yang salah walau jaraknya hanya beberapa menit.

Akar B1 bukan sekadar seseorang lupa menyetel. `SetEnv CI_ENV production` tidak ada
di repo dan konfigurasi server dapat hilang saat deploy. Tetapi menaruh
`SetEnv CI_ENV production` tanpa kondisi di `.htaccess` repo juga **salah**:
`.htaccess` yang sama dibaca Apache XAMPP sehingga localhost ikut menjadi
production.

Kontrak yang aman:

1. `index.php` **fail-closed** ke `production` bila `CI_ENV` tidak tersedia;
2. lingkungan lokal secara eksplisit menyetel `CI_ENV=development` di vhost/konfigurasi
   Apache lokal yang tidak ikut Git;
3. nilai cookie mengikuti environment
   (`cookie_secure = ENVIRONMENT === 'production'`), bukan `TRUE` global yang
   memutus sesi HTTP localhost;
4. tindakan server manual boleh dipakai sebagai containment sementara dengan izin
   user, tetapi bukan dianggap perbaikan permanen.

B11 masuk paket yang sama karena repo hari ini masih `cookie_secure = FALSE`
sementara production menambahkan `secure` dari konfigurasi di luar repo. Targetnya
adalah perilaku yang eksplisit dan dapat direproduksi per-environment, bukan satu
nilai global untuk semua lingkungan.

### Aturan 2 — Kelompok A punya jam dinding, tapi jam itu dimulai oleh USER

A adalah risiko reputasi institusi, bukan bug teknis. Perbedaannya praktis, bukan
filosofis:

- Kerusakan B dan C **berhenti** begitu diperbaiki. Kerusakan A tidak: satu tangkapan
  layar "Rp 1,2 Triliun tersalurkan" dari situs `jatengprov.go.id` hidup terpisah dari
  situsnya. Tidak ada perbaikan kode yang menariknya kembali.
- Karena itu urgensi A diukur dalam **hari sejak sekarang**, bukan dalam posisi
  antrean.
- Tapi **4 dari 6 butir A tidak bisa dikerjakan agent** (A1, A2, A4, A6). Bukan karena
  sulit — karena agent yang mengganti "4.250,8 Ha" dengan angka lain sedang melakukan
  persis pelanggaran §17 yang mau kita hentikan.

Menaruh A di depan berarti seluruh pekerjaan menunggu jawaban user. Menaruhnya di
belakang berarti jam dindingnya terus berjalan. **Resolusinya: yang memblokir A bukan
antrean, melainkan pertanyaannya — jadi pertanyaannya dikirim di hari pertama.**
Tabel keputusan di §6 adalah deliverable U0, bukan lampiran. Waktu berpikir user
berjalan paralel dengan U1–U3. A3 (satu-satunya butir A tanpa keputusan) ikut
dikerjakan begitu berkasnya tersentuh.

### Aturan 3 — Ketergantungan teknis yang nyata, dan satu keputusan yang mematikan lima temuan

Tiga rantai mengikat urutan, sisanya bebas:

1. **Siapkan A5+C1 → buktikan dengan `db_debug=FALSE` → rilis bersama B1+B11.**
   C1 akan mengubah perilaku production, bukan cuma memperbaiki lokal (catatan F2
   inventaris). Urutan implementasi ini menutup jalur tulis senyap sebelum
   `display_errors` production dimatikan; urutan deploy-nya tetap satu paket.
2. **B4 sebelum Sikaper boleh diaktifkan kembali.** U2 mengarantina controller
   publiknya. Jika keputusan #5 mempertahankan integrasi, perbaiki TLS library,
   preseed secret baru, lalu gunakan kredensial yang sudah dirotasi. Jika integrasi
   dicabut, hapus library/config; jangan memindahkan secret lama ke `.env`.
3. **Keputusan chat (§6 nomor 7) mendahului C2, C3, B7, dan perbaikan TLS di
   `Chat.php`.** Kalau chat dicabut, satu penghapusan mematikan C2 + C3 + B7
   sekaligus, plus menghapus 1 dari 12 titik TLS B4 (`Chat.php:121`). Kalau chat
   dibangun, biayanya §17 checklist penuh +
   §19 dua belas langkah — bukan menit, tapi minggu. **Selisih biaya kedua pilihan
   adalah yang terbesar di seluruh dokumen ini.** Mengerjakan apa pun di `Chat.php`
   sebelum keputusannya turun berisiko dibuang.

**Pengecualian yang saya ambil sendiri terhadap rantai ke-3, dan bisa dibantah:**
B2 tetap dikerjakan lebih dulu, tidak menunggu keputusan chat, tetapi sebagai
**containment lengkap**: `api_bot()` non-public, tiga endpoint Chat eksternal dibuat
404, dan widget disembunyikan. Alasannya: caller publik `kirim_pesan_lanjutan()`
tetap routable dan dapat mencapai Gemini setelah write berhasil walau `api_bot()`
sudah private. Containment ini
reversibel dan tidak menentukan apakah U5 akhirnya membangun atau mencabut chat.

### Aturan 4 — Satu berkas dibuka sekali

`Chat.php` disentuh hanya untuk containment B2; perbaikan B4/B7/C2 menunggu
keputusan chat agar kode yang sedang dikarantina tidak dikembangkan sia-sia.
`Index.php` menyimpan 6 dari 12 titik
B4 (`:126, :178, :239, :309, :386, :583`) sekaligus ketiga renderer halaman spasial
(`:539, :544, :549`) — B4 dan A1 bertemu di berkas ini, jadi kalau A1 sudah punya
jawaban saat U3 berjalan, keduanya satu perjalanan editor.

---

## 5. Definisi Selesai per kelompok

Mengikuti §19 langkah 12: **selesai = ada cara membuktikannya GAGAL kalau
perbaikannya dibalik.** Bukan "sudah diubah", bukan "sudah dibaca ulang". PHP CLI +
curl cukup untuk server/DB, tetapi **tidak menjalankan JavaScript** dan tidak dapat
membuktikan History API atau jumlah request browser.

**Satu perintah/pintu, dua runner:** `docs/engineering/uji_utang_teknis.php`
menjalankan bukti server/DB dan memanggil runner browser terpisah untuk S1/S2.
Runner browser memakai alat otomasi yang sudah tersedia di lingkungan pengujian,
bukan dependency runtime aplikasi. Keduanya wajib mengembalikan exit code non-nol
saat gagal; S1/S2 tidak boleh ditandai selesai dari curl atau inspeksi manual DOM.

### 5.1 Kontrak keselamatan harness dan mutation proof

Skrip tersebut **belum boleh dibuat/dijalankan** sebelum memenuhi semuanya:

1. gunakan worktree/salinan dan vhost Apache uji yang terpisah dari aplikasi dev;
   abort bila host bukan localhost atau nama DB tidak memakai prefix DB uji;
2. vhost uji mempunyai `.env` sendiri yang memuat `SITE_URL` host uji unik, DB dan
   `PRIVATE_UPLOADS_PATH` sementara, `SIMPERUM_MODE=simulation`, serta
   `GEMINI_API_URL` stub localhost. `.env` dibuat minimal dengan satu definisi per key
   dan key/pepper 64-hex sintetis unik; jangan salin secret dev/production. Vhost
   menyetel `CI_ENV=testing`, child CLI juga menerimanya; environment child saja
   tidak mengubah proses Apache;
3. setiap redirect, form action, dan request aplikasi wajib same-origin host uji;
   redirect keluar host adalah kegagalan, bukan diikuti. Asset CDN yang sudah ada
   di-intercept ke fixture lokal atau diblokir, bukan memaksa vendoring baru.
   Endpoint Chat dikarantina dan network egress non-stub ditolak; API key kosong
   saja bukan pengaman;
4. jangan pernah mengubah `.env`, DB, atau root upload worktree utama untuk tes;
5. tulis recovery marker berisi path absolut terverifikasi, DB uji, dan backup
   konfigurasi. `finally` membersihkan kegagalan biasa; proses berikutnya membersihkan
   resource yatim dari hard-kill/power loss. Jangan mengklaim `finally` berjalan
   setelah proses dibunuh;
6. tidak pernah memanggil production, Gemini, SIMPERUM, atau upstream nyata kecuali
   flag izin eksplisit dan data uji resmi tersedia;
7. mutation proof berjalan di salinan/worktree sekali pakai, bukan dengan
   meninggalkan source utama dalam keadaan sengaja dirusak;
8. setelah selesai: DB dan folder sementara terhapus, file sumber byte-identik,
   serta `git diff` hanya berisi perubahan yang memang sedang dikembangkan.

Runner yang hanya mengganti `DB_NAME` dinyatakan **tidak aman**, walaupun pernah
menghasilkan 224/224.

### Kelompok A — kepercayaan publik

**Bentuk buktinya adalah ketiadaan.** Ambil HTML respons anonim, cari literal yang
dicabut, hasilnya harus nol.

- Untuk tiap butir A yang sudah diputuskan: `curl` tanpa cookie ke URL-nya, grep
  respons untuk literal spesifiknya (`4,250.8`, `8,420`, `142,500`, `1.2`,
  `Rusunawa Kudu`, `Nama Pejabat`, `Rp 500.000.000`, `Mode Pemeliharaan`) → nol
  hasil, ATAU halaman membalas 404 karena route-nya dicabut.
- Untuk A2: tiap metrik yang dipertahankan wajib mempunyai provenance yang dapat
  diverifikasi (adapter/dataset, waktu pengambilan, dan status nyata/simulasi).
  `curl` tidak boleh menemukan label “Sumber: Simperum” pada angka yang tidak lahir
  dari respons/snapshot Simperum. Membandingkan dua kabupaten saja **bukan bukti**:
  data nyata boleh sama dan angka palsu juga dapat dibuat berbeda.
- **Uji balik yang mendefinisikan selesai:** kembalikan satu literal ke view,
  jalankan skripnya → **MERAH**. Kalau skripnya tetap hijau, ia mengecek URL yang
  salah dan tidak membuktikan apa pun.

### Kelompok B — keamanan

Buktinya adalah **penolakan** yang bisa diamati dari luar.

- **B1:** pada vhost/salinan uji, canary sintetis yang sengaja memicu notice dan
  galat DB tidak boleh memuat `A PHP Error was encountered`, SQL, atau path absolut.
  Child/vhost tanpa `CI_ENV` harus terbukti memilih `production`; cookie mengikuti
  environment. Uji balik mengembalikan default ke `development` dan harus membuat
  canary merah. `/Umum/info_rumah` hanya bukti insiden 29 Juli, bukan canary
  permanen karena S9 boleh mencabutnya.
- **B2:** GET langsung `api_bot` serta POST anonim
  `register_session`/`kirim_pesan_lanjutan`/`ambil_pesan` → **404**, widget tidak
  tampil, dan upstream Gemini stub mencatat nol panggilan. Uji balik mengaktifkan
  caller publik di salinan uji → hitungan upstream bertambah.
- **B3:** tabel dedup harus memiliki `UNIQUE (id_komentar, user_id)` dan FK yang
  sesuai; transaksi mengunci baris komentar induk, insert ledger, menghitung ulang
  laporan unik, dan menjaga `is_deleted` tidak berubah pada U2. Jika #10(b) dipilih,
  perubahan visibilitas baru boleh hidup bersama modul moderasi terpisah. 6× POST
  `report_komentar` anonim untuk satu `id_komentar` → semuanya ditolak
  dan `SELECT report_count, is_deleted FROM forum_komentar WHERE id_komentar=?`
  **tidak berubah**. Lalu dengan 5 akun login berbeda → `report_count` naik tepat 5,
  akun ke-1 melapor dua kali dan dua request paralel → tetap 5; `is_deleted` tetap 0.
- **B4:** arahkan satu klien **yang masih aktif** ke server TLS lokal dengan
  sertifikat uji tidak sah →
  permintaan **gagal**. Jangan bergantung pada layanan publik untuk tes. Ini
  satu-satunya
  cara membuktikan TLS benar-benar diverifikasi; membaca `CURLOPT_SSL_VERIFYPEER => true`
  di diff hanya membuktikan diff-nya benar. Pembanding yang sudah benar ada di repo:
  `Simperum_gateway.php:155-156`. Titik di `Chat.php` baru masuk scope bila keputusan
  #7 adalah membangun; bila chat dicabut, titik itu hilang bersama berkasnya.
- **B6:** `GET /sikaper`, `/Sikaper`, dan `/Sikaper/index` anonim → 404. Upstream
  stub menghitung tepat nol panggilan; durasi respons bukan alat bukti.
- **B9:** kosongkan `KPKP_DATA_KEY`, lalu secara terpisah
  `KPKP_DATA_PEPPER`: `encrypt()`/`decrypt()`/`deterministic_hash()` harus melempar
  exception sesuai secret-nya, transaksi tulis/lookup berhenti, dan DB tidak berubah.
  Ciphertext rusak tidak boleh dikembalikan sebagai plaintext; compatibility legacy
  harus eksplisit di reader/migrasi, bukan fallback global. `FALSE` atau HMAC dengan
  secret kosong tidak cukup. Pepper khusus tiket/audit juga fail-closed pada batas
  fiturnya. Uji balik plaintext/HMAC-secret-kosong → assert merah.
- **B5, B10:** tidak punya definisi selesai yang bisa dijalankan agent. Lihat §6.

### Kelompok C — fitur mati

Buktinya adalah **baris yang mendarat**, diukur di DB, bukan flash di layar.

- **C1:** POST `Umum/tambah_aksi` dan `Umum/balas_aksi` sebagai user login → 200, dan
  `SELECT COUNT(*)` di `forum_diskusi`/`forum_komentar` bertambah tepat 1. Uji balik:
  kembalikan `'diskusi'`/`'komentar'` mentah ke `forum_helper.php:75` → 1146 lagi.
- **C1b — wajib dijalankan DUA KALI, dan inilah butir yang membedakan uji ini dari
  uji lokal biasa:** ulangi dengan `db_debug` disetel FALSE di `database.php:85`
  (kembalikan sesudahnya; JANGAN lewat `CI_ENV`, §19 langkah 12). Posting ke-6 dalam
  satu jam harus **DITOLAK**. Kalau ia lolos, `count_all_results()` masih
  mengembalikan FALSE di suatu tempat dan rate limit forum masih fiktif — persis
  keadaan production hari ini menurut inferensi C1b, yang **belum pernah diverifikasi**.
- **C2/C3:** bentuk buktinya tergantung keputusan. Kalau dicabut: `grep -rn "tb_chat" application/`
  = nol, dan halaman mana pun yang dulu memuat widget tidak lagi memuat skrip chat.
  Kalau dibangun: perjalanan penuh warga→operator→balas, mengikuti §19 dua belas
  langkah, dengan layar admin yang benar-benar bisa dimasuki (C3).

### Kelompok S — stabilisasi alur terbaru

- **S1/S2:** runner browser menempuh halaman A→B→Back→Forward pada portal dan admin;
  URL, judul, konten, dan menu aktif selalu cocok. Satu klik forum menghasilkan
  tepat satu request detail dan `view_count` naik tepat satu.
- **S3:** untuk setiap cabang matriks simulasi, submit tanpa masing-masing bukti
  wajib ditolak server dengan reason code stabil; baris ledger tanpa file fisik
  terbaca juga ditolak. Dua bukti kondisional tidak menjadi gate sampai keputusan
  #11 menetapkan trigger resminya.
- **S4:** sebelum keputusan retensi, UI tidak boleh lagi menjanjikan penghapusan
  permanen seluruh data. Setelah keputusan, pemeriksaan disk+DB+backup membuktikan
  setiap kelas data mengikuti durasi dan tindakan yang ditetapkan.
- **S5:** jalankan runner fresh dengan fixture ber-ID sama seperti dev; hash seluruh
  folder dev sebelum/sesudah harus identik, folder sementara terhapus, dan simulasi
  hard-kill meninggalkan hanya resource terisolasi yang dibersihkan pada run berikutnya.
- **S6:** request keputusan ke-ambang+1 melalui Admin Kab/Kota dan superadmin sama-sama
  ditolak `429` dengan `Retry-After`.
- **S7:** setelah keputusan #9, perubahan PII, submit, dan refresh
  SIMPERUM memakai `attempt_id`: requested dicatat sebelum network, succeeded atomik
  dengan snapshot/profile, failed hanya menyimpan kelas galat. Canonical plaintext
  diff memakai domain-separated `KPKP_AUDIT_PEPPER`, bukan ciphertext acak; cache hit
  yang me-reencrypt nilai sama menghasilkan nol diff PII. Keputusan antrean tetap
  bersumber dari `sf_riwayat_keputusan_antrean`; audit hanya mereferensikannya.
- **S8:** pemeriksa dokumentasi memastikan hanya satu status produksi aktif dan
  tidak ada instruksi “push kode lalu migrasi” yang berlawanan dengan runbook aktif.
  `migration_version` sama dengan nomor tertinggi seluruh migrasi yang tracked dalam
  commit rilis, worktree kotor/untracked ditolak, nol pemanggilan
  `migration->current()` pada tooling, dan mutation ke versi lama membuat pemeriksa
  merah.
- **S9:** `/Umum/info_rumah` 404 bila dicabut, atau 200 dengan layout lengkap bila
  dipertahankan; menyembunyikan warning saja tidak dihitung sebagai selesai.

---

## 6. Keputusan user yang menghambat

**Agent tidak boleh mengambil keputusan produk yang masih terbuka di tabel ini.**
Baris yang sudah diputuskan tetap dicatat sebagai ledger agar agent tidak
menanyakannya ulang atau diam-diam membalik keputusan user.

| # | Butir | Pertanyaan persis yang harus dijawab | Pilihan & konsekuensinya | Terblokir selama belum dijawab |
|---|---|---|---|---|
| 1 | **A1** | Tiga halaman spasial (`/sebaran_rusun`, `/profil_kumuh`, `/sebaran_sdgs`) — dicabut, atau dipertahankan? Kalau dipertahankan, **dari mana angka & daftar rusunawa yang benar diambil?** | (a) **Cabut** — hapus 3 route + 3 view, footer diperbaiki. Menit. Dinas kehilangan 3 halaman peta. (b) **Isi dengan data nyata** — butuh sumber data yang hari ini tidak ada di sistem; label "(Data Simulasi)" dicabut. Hari–minggu, tergantung sumbernya. (c) **Pertahankan sebagai demo** — ganti nama rusunawa nyata jadi fiktif, hapus koordinat asli dan klaim "Terdapat keluhan kerusakan aset" (`sebaran_rusun.php:155`), pertahankan label simulasi. Jam. | Seluruh A1. Berkas `pages/spasial/sebaran_rtlh.php` (butir E — nol renderer) ikut menunggu karena penghapusannya ikut pilihan (a) |
| 2 | **A2** | Halaman `/statistika` — dicabut, atau disambungkan? | (a) **Cabut** — menit. (b) **Sambungkan** — adapter Sikumbang & Sikaper ada; SIMPERUM ada tapi **mode masih `simulation`** sampai smoke test + sumber desil selesai (§17). Jadi pilihan (b) mewarisi blocker yang belum selesai, dan sebagian metrik akan tetap kosong. (c) **Pertahankan dengan label jujur** — cabut label "Sumber: X", ganti jadi keterangan simulasi. Jam. Tetap berisiko dikutip | Seluruh A2. Perhatikan: pilihan (b) menaruh A2 di belakang pekerjaan SIMPERUM yang bukan bagian dokumen ini |
| 3 | **A4** | Struktur organisasi — **siapa penyedia nama & jabatan resmi, dan dari dokumen apa?** | (a) **Cabut halaman** — menit. (b) **Kosongkan nama, pertahankan bagan jabatan** — jam, tidak butuh data dari siapa pun, dan menghentikan penyebutan individu nyata tanpa sumber hari itu juga. (c) **Isi dari dokumen resmi dinas** — butuh dokumen dari user; sekaligus butuh kesepakatan siapa yang memperbaruinya saat ada mutasi | Seluruh A4. Catatan: pilihan (b) tersedia sebagai mitigasi cepat sambil menunggu (c) — ini satu-satunya butir A yang punya jalur dua langkah |
| 4 | **A6** | Layar "Pengaturan Sistem" admin — dihapus, atau dinyatakan sungguhan? | (a) **Hapus layar** — menit. Penyimpanan yang NYATA sudah ada di `Admin_Content.php:44` + `Setting_model`, jadi tidak ada fungsi yang hilang. (b) **Sambungkan ke `Setting_model`** — hari; butuh keputusan field mana yang benar-benar dikelola, dan "Mode Pemeliharaan" butuh mekanisme yang belum ada sama sekali. | Seluruh A6. **Risiko selama menunggu spesifik dan tinggi:** dinas bisa menyalakan toggle "Mode Pemeliharaan" dan mengira situs tertutup, padahal terbuka penuh |
| 5 | **B5** | Kredensial Sikaper sudah masuk 2 commit riwayat git. **Apakah user punya kanal ke pengelola API Sikaper untuk meminta rotasi?** | (a) **Ada kanal** → minta rotasi; setelah itu pindahkan ke `.env`. (b) **Tidak ada kanal** → memindahkan ke `.env` adalah **nol perbaikan** (nilai lamanya tetap di riwayat git dan tetap sah). Satu-satunya mitigasi yang di tangan kita adalah **menghentikan pemakaian API-nya** — yang berarti mencabut `Sikaper.php` + `Sikaper_api.php` sepenuhnya, bukan hanya route-nya (B6) | Klaim “B5 selesai” terblokir. **Kita tidak bisa menjanjikan rotasi**—itu di tangan pihak ketiga. Yang tetap bisa dikerjakan: B4 untuk klien aktif dan B6 |
| 6 | **B10** | Halaman login production memajang email admin + sandi `password` | **SUDAH DIPUTUSKAN USER 27 Jul:** dipertahankan selama uji coba dinas. `AGENTS.md` §0c mencatat sandi masih cocok, jadi risikonya **AKTIF DAN DITERIMA SEMENTARA**, bukan “belum diverifikasi”. Penutupnya tetap rotasi melalui alur aplikasi setelah masa uji selesai; agent tidak boleh mencoba login atau merotasi tanpa perintah baru | Tidak memblokir kerja lokal, tetapi memblokir klaim “production aman” |
| 7 | **C2 + C3 + B7** | Fitur chat — **nyata atau dicabut?** Hari ini 3 endpoint menulis/membaca `tb_chat` yang tidak pernah ada di skema, migrasi, maupun dump; `chat_rooms`/`chat_messages` ada tapi 0 baris, disentuh hanya oleh `Chat_model` yang nol pemanggil, dan tidak lahir dari migrasi. Status `'admin'` dan label "Petugas" menjanjikan operator manusia yang **nol layar admin**-nya (nol entri di `dashboard_modules.php`) | (a) **Cabut** — hapus `Chat.php`, `Chat_model`, widget di `layouts/footer.php`, tabel yatim. Menit–jam. **Mematikan C2, C3, B7 sekaligus, plus 1 dari 12 titik B4.** (b) **Bangun** — butuh migrasi tabel, layar operator admin (§17 checklist penuh), `session_id` terikat sesi server (B7), dan §19 dua belas langkah. Minggu. Dan: **siapa yang akan duduk sebagai operator?** Tanpa jawaban itu, (b) menghasilkan layar kosong | Seluruh C2, C3, B7. **Selisih biaya kedua pilihan yang terbesar di dokumen ini** — mengerjakan `Chat.php` sebelum ini dijawab berisiko dibuang |
| 8 | **B8** | NIK plaintext di `sf_housing_queue.nik_pengaju`. **Boleh mengubah cara warga mengecek status tiket?** | Ini bukan sekadar “enkripsi kolomnya”: pembaca publik mencari `RIGHT(nik_pengaju, 4)`, sedangkan admin melakukan `LIKE` dan view mem-mask kolom yang sama. Pilihan publik: (a) tiket acak minimal 128-bit tanpa faktor NIK—tetapkan juga cara tiket lama tetap dapat diakses/reissue; atau (b) NIK terenkripsi + lookup HMAC `ticket_code\|4-digit` memakai `KPKP_QUEUE_LOOKUP_PEPPER`. Admin tetap mendapat exact-search lewat hash NIK penuh dan display ter-mask dari dekripsi berotorisasi; partial `LIKE` NIK dihapus. Policy lookup wajib berdimensi IP+ticket dan memberi `429`+`Retry-After`. Migrasi memakai kolom baru, backfill, compatibility-write/dual-read, lalu pembaca pindah ke data baru sambil writer legacy saja masih mempertahankan plaintext selama jendela rollback. Penulisan dan kolom plaintext dihentikan pada dua fase terpisah setelah jendela itu berakhir | Seluruh B8, format/migrasi tiket publik, dan kontrak pencarian admin |
| 9 | **S4 + S7** | Berapa lama raw snapshot/cache, draft terbengkalai, assessment submitted/superseded, antrean, audit, dan foto disimpan—termasuk setelah akun dihapus? | Untuk tiap kelas data tentukan durasi serta: (a) purge setelah masa tunggu; (b) anonimisasi PII + hapus file, pertahankan agregat; atau (c) retensi legal dengan dasar, scope akses, jadwal hapus, dan perlakuan backup. Tetapkan pula apakah audit immutable, dianonimkan, atau ikut purge. UI wajib menjelaskan akibat sebenarnya | Cleanup berkala/akun Warga, skema audit S7, backup, dan acceptance criteria S4/S7 |
| 10 | **B3** | Apa yang terjadi setelah komentar mencapai ambang laporan unik? | (a) hanya catat, tidak sembunyikan—selesai di U2. (b) auto-hide sementara + antrean moderasi/admin restore—**roadmap domain terpisah**; U2 tetap ledger-only sampai modul itu lengkap. (c) hapus otomatis—tidak dianjurkan dan tidak diimplementasikan tanpa persetujuan risiko baru. Lima akun tidak boleh menjadi sensor permanen | U2 tidak terblokir: selalu ledger-only. Keputusan hanya menentukan apakah roadmap moderasi baru perlu dibuat |
| 11 | **S3 / OPEN-WRG-008** | Apa syarat bukti resmi Dinas per cabang, dan field/value apa yang berarti “pemilik bukan pemohon” atau “pindah tangan relevan”? | Matriks simulasi di §3.5 dapat ditegakkan sekarang. Untuk aturan resmi: tetapkan trigger dua bukti kondisional calon lahan, apakah bukti pindah tangan menerima PDF (UI Warga saat ini hanya JPG/PNG), batas ukuran, status `verification_report` admin, serta apakah KTP/KK jalur financing berlaku di luar simulasi. Agent dilarang memetakan sendiri `candidate_land_origin_code`/`land_owner_relationship_code` | Dua bukti kondisional dan klaim “syarat resmi Dinas”; tidak memblokir gate wajib simulasi |

**Catatan B8:** “compatibility-write” tidak memberi izin menulis NIK plaintext
ke wizard baru yang hari ini sengaja menyimpan `NULL`. Hanya writer legacy yang sudah
plaintext boleh mempertahankannya selama jendela transisi; kedua writer wajib mengisi
ciphertext/hash baru.

**Catatan S7:** bila keputusan #9 mengizinkan audit bertahan, seluruh environment
wajib dipreseed `KPKP_AUDIT_PEPPER` 64 hex dan memakai canonicalization/domain
separation + golden vector yang sama. Tidak ada fallback ke pepper lain.

---

## 7. Batas — yang SENGAJA di luar dokumen ini

- **Bagian D inventaris (sudah beres).** Unggahan berkas, penyajian berkas privat,
  `Migrate.php`, SQL injection, proteksi berkas sensitif production, header keamanan,
  `admin/dashboard.php`, empat halaman pertanahan, `kemitraan_portal/*`,
  `sejarah_visi.php`, `tugas_pokok.php`, `action="#"`, klaim "tersinkronisasi".
  **Jangan dikerjakan ulang.** Catatan §17 "3 lokasi upload publik" sudah usang.
- **Roadmap pengembang T0–T6** ([`ROADMAP_PENGEMBANG_ADMIN.md`](./ROADMAP_PENGEMBANG_ADMIN.md))
  dan roadmap warga R0–R9 ([`ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md`](./ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md)).
  Keduanya tetap menjadi catatan perjalanan historis. Namun S1–S9 adalah regresi/
  kekurangan yang ditemukan setelah klaim selesai, sehingga **masuk scope dokumen
  ini** dan mengalahkan status lama yang bertentangan. Selain itu, dokumen ini
  tidak menyisipkan ulang tahap historisnya:
  roadmap pengembang bekerja per-perjalanan, dan menyisipkan temuan lintas-domain
  membuat tiap tahapnya berhenti menghasilkan perjalanan yang bisa ditempuh — itu
  sudah dinyatakan eksplisit di bagian "Sengaja TIDAK masuk roadmap ini" milik
  dokumen itu, yang bahkan menyarankan roadmap terpisah. Ini roadmap terpisah itu.
- **CSP.** Production sudah punya X-Frame-Options, nosniff, HSTS 1 tahun,
  Referrer-Policy, Permissions-Policy; **CSP masih nihil**. Itu pengerasan, bukan
  utang dari inventaris ini, dan memasangnya di portal berisi Leaflet + inline script
  butuh sesi tersendiri. Dicatat, tidak dijadwalkan di sini.
- **Butir E (yatim).** `pages/spasial/sebaran_rtlh.php` (nol renderer) dan
  `pages/sikaper/index.php` (yatim saat controller publik B6 ditutup). Penghapusannya mengikuti
  keputusan #1 dan pekerjaan B6 — bukan tahap sendiri.
- **Fitur baru apa pun.** Termasuk layar moderasi forum untuk B3 dan generator
  sertifikat. B3 selesai dengan guard+dedup ledger-only; jika #10(b), auto-hide tidak
  boleh aktif sampai roadmap moderasi baru (antrean+restore) selesai.
- **Tanggal rilis dan pembukaan `main`.** Di luar kendali dokumen ini. Sebaliknya,
  kontrak backup, environment prerequisite, urutan migrasi/kode, smoke test, dan
  rollback **wajib** ada di roadmap karena branch aktif auto-deploy ke production.
- **Rotasi kredensial yang butuh pihak ketiga** (B5), perubahan konfigurasi hosting
  untuk containment environment, dan rotasi sandi B10 melalui alur aplikasi setelah
  masa uji. Kita menulis apa yang harus dilakukan; agent tidak menjadwalkan atau
  menjalankannya tanpa izin baru.
