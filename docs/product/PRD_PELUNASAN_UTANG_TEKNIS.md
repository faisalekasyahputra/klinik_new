# PRD Pelunasan Utang Teknis

**Tanggal:** 29 Juli 2026
**Sumber isi:** inventaris utang teknis terverifikasi 29 Juli 2026 (dua audit subagent + verifikasi silang)
**Urutan pelaksanaan:** [`ROADMAP_PELUNASAN_UTANG_TEKNIS.md`](./ROADMAP_PELUNASAN_UTANG_TEKNIS.md)

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

Yang dipakai dokumen ini adalah **inventaris 29 Juli**: 22 butir aktif, masing-masing
dicek ulang terhadap kode/runtime hari itu, terbagi tiga kelompok — **A** (melanggar
kepercayaan publik), **B** (keamanan), **C** (fitur mati).

**Yang tidak berubah:** aturan §17 "jangan pernah menampilkan angka, status, atau
pesan sukses karangan" dan §19 langkah 12 "selesai = ada cara membuktikannya GAGAL".
Dokumen ini adalah penerapan keduanya ke tiga kelompok temuan, bukan aturan baru.

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
| Kartu Domain | **Kartu Kelompok** (§3.1) — sifat kerusakan, siapa yang bisa memicunya, siapa yang berwenang memutuskan | Yang dibagi tiga kelompok ini bukan tabel, tapi jenis kepercayaan yang dilanggar |
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
| Yang berwenang memutuskan | **User** untuk 4 dari 6 butir | Agent, kecuali B5 & B10 | **User** untuk C2/C3 |
| Sifat perbaikan | Keputusan produk (isi apa yang menggantikan) | Perbaikan teknis | Campuran |

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
| `Chat::api_bot()` (`Chat.php:80`) | **Nihil**. `public`, jadi routable lewat GET `/Chat/api_bot/<pesan>`. Menembak Gemini sungguhan, 2–6 detik per hit | Tidak perlu digerbangi — perlu **tidak routable**. Satu-satunya pemanggil ada di kelas yang sama (`Chat.php:62`) | B2 |
| `Umum::report_komentar()` (`Umum.php:374`) | **Nihil**. Bandingkan `Umum::toggle_like()` 16 baris di bawahnya (`:390-395`) yang memanggil `is_logged_in()` | Guard login (salin dari `:392`) + dedup per (pelapor, objek) | B3 |
| `Sikaper::index()` (`Sikaper.php:13`) | **Nihil**, dan `Sikaper.php:4` satu-satunya controller yang `extends CI_Controller` — di luar `MY_Controller`, jadi di luar security header. Tiap hit anonim = 5 panggilan upstream memakai kredensial dinas (`:21-25`) | Cabut route `routes.php:125` | B6 |
| `Chat::register_session()` / `kirim_pesan_lanjutan()` / `ambil_pesan()` | Nihil; `ambil_pesan()` (`:157-159`) `result_array()` tanpa `select()` → seluruh kolom termasuk `nama_warga`/`email_warga`/`hp_warga` (`:24-26`), dikunci `session_id` buatan browser (`layouts/footer.php:165`, `Math.random()`) | Tergantung keputusan chat (§6 nomor 7) | B7, C2 |

**Yang TIDAK menutup gerbang ini: CSRF.** B12 sudah membuktikannya —
`csrf_regenerate=FALSE` + double-submit berarti token bisa diambil anonim lewat satu
GET. B12 bukan bug; ia ada di inventaris justru untuk mencegah perbaikan yang salah
sasaran.

**Yang menutupnya, dan sudah ada di repo:** `application/config/rate_limits.php`
(8 policy, dimensi `ip`/`account`/`nik`/`object`) dipakai lewat
`MY_Controller::rate_limit_sisa()` + `rate_limit_catat()` di tabel `sys_rate_limits`.
§17 poin 15 melarang membuat mekanisme kedua. Perbaikan B2/B3 menambah **entri
policy**, bukan mekanisme.

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
3. **Karena itu B1 tidak boleh dirilis sendirian.** Konsekuensi logisnya bukan
   menunda B1, tapi mengikatnya: A5 dan C1 dikerjakan **langsung setelahnya, sebagai
   satu paket rilis**, bukan sebagai tahap yang "nanti menyusul". Yang membuat
   urutan ini aman bukan urutannya, melainkan jaraknya.

Dan akar B1 bukan "seseorang lupa menyetel". Catatan F1 inventaris membuktikan
`SetEnv CI_ENV production` **tidak pernah ada di repo**, dan `.htaccess` server sudah
identik dengan repo SEBELUM rilis 29 Juli. Artinya konfigurasi keamanan production
selama ini dipasang manual di server. **Deploy mana pun akan menghapusnya lagi.**
Memperbaiki B1 dengan cara login ke server dan menambah satu baris = menjadwalkan
kejadian yang sama untuk rilis berikutnya. Perbaikan yang benar ada di `.htaccess`
REPO.

B11 masuk paket yang sama, karena akarnya identik: `cookie_secure = FALSE` di kode
sementara production mengirim cookie ber-`secure`. Sekali lagi, konfigurasi yang
hidup hanya di server. Dampaknya Rendah, biayanya menit, dan menggabungkannya
membuat U0 punya satu tema yang bisa diperiksa: **nol setelan keamanan yang hidup
hanya di luar repo.**

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

1. **B1 → A5 → C1.** Sudah dibahas di Aturan 1. C1 punya lapisan tambahan: perbaikan
   helper akan **mengubah perilaku production**, bukan cuma memperbaiki lokal (catatan
   F2 inventaris). Setelah B1, verifikasi C1 wajib diulang dengan `db_debug` FALSE,
   karena skenario C1b (rate limit terlewati diam-diam) baru bisa muncul di sana.
2. **B4 sebelum B5.** Rotasi kredensial Sikaper (B5) menunggu pihak ketiga dan bisa
   lama. Selama menunggu, `Sikaper_api.php:36` tetap mengirim header
   `Authorization: Basic` di atas koneksi yang verifikasi sertifikatnya dimatikan.
   Memperbaiki TLS lebih dulu **mengurangi laju panen** kredensial yang belum bisa
   dirotasi. Urutan sebaliknya menunggu pihak ketiga sambil terus membocorkan.
3. **Keputusan chat (§6 nomor 7) mendahului C2, C3, dan B7.** Kalau chat dicabut,
   satu penghapusan mematikan C2 + C3 + B7 sekaligus, plus menghapus 1 dari 12 titik
   TLS B4 (`Chat.php:121`). Kalau chat dibangun, biayanya §17 checklist penuh +
   §19 dua belas langkah — bukan menit, tapi minggu. **Selisih biaya kedua pilihan
   adalah yang terbesar di seluruh dokumen ini.** Mengerjakan apa pun di `Chat.php`
   sebelum keputusannya turun berisiko dibuang.

**Pengecualian yang saya ambil sendiri terhadap rantai ke-3, dan bisa dibantah:**
B2 tetap dikerjakan lebih dulu, tidak menunggu keputusan chat. Alasannya biaya
perbaikannya nol-koma: `api_bot()` (`Chat.php:80`) `public` padahal satu-satunya
pemanggilnya ada di kelas yang sama (`Chat.php:62`) — `grep -rn "api_bot" application/`
mengembalikan tepat dua hasil, deklarasi dan pemanggil internal itu. Mengubah
`public` menjadi `private` mencabut routabilitasnya tanpa menyentuh perilaku
internal, dan **tidak terbuang di bawah keputusan chat yang mana pun**. Menunggu
keputusan untuk perubahan satu kata sementara kuota Gemini terkuras adalah menunggu
yang mahal.

### Aturan 4 — Satu berkas dibuka sekali

`Chat.php` disentuh B2, B4, B7, C2 → jangan dicicil empat kali (kecuali B2 di atas,
yang satu kata dan tidak akan dibuka ulang). `Index.php` menyimpan 6 dari 12 titik
B4 (`:126, :178, :239, :309, :386, :583`) sekaligus ketiga renderer halaman spasial
(`:539, :544, :549`) — B4 dan A1 bertemu di berkas ini, jadi kalau A1 sudah punya
jawaban saat U3 berjalan, keduanya satu perjalanan editor.

---

## 5. Definisi Selesai per kelompok

Mengikuti §19 langkah 12: **selesai = ada cara membuktikannya GAGAL kalau
perbaikannya dibalik.** Bukan "sudah diubah", bukan "sudah dibaca ulang". Repo ini
tidak punya `tests/`, jadi jangan mengandaikan harness — polanya sudah ditetapkan:
satu berkas PHP CLI + curl, `assert()` atas status HTTP dan isi respons, exit code
non-nol kalau gagal.

**Satu berkas untuk ketiga kelompok:** `docs/engineering/uji_utang_teknis.php`.
Tiap tahap roadmap MENAMBAH blok assert ke berkas yang sama. Alasan: berkas kedua
melahirkan pertanyaan "yang mana yang harus saya jalankan", dan jawabannya selalu
dijawab salah oleh orang yang sedang buru-buru.

### Kelompok A — kepercayaan publik

**Bentuk buktinya adalah ketiadaan.** Ambil HTML respons anonim, cari literal yang
dicabut, hasilnya harus nol.

- Untuk tiap butir A yang sudah diputuskan: `curl` tanpa cookie ke URL-nya, grep
  respons untuk literal spesifiknya (`4,250.8`, `8,420`, `142,500`, `1.2`,
  `Rusunawa Kudu`, `Nama Pejabat`, `Rp 500.000.000`, `Mode Pemeliharaan`) → nol
  hasil, ATAU halaman membalas 404 karena route-nya dicabut.
- Untuk A2: `curl "/statistika?kabupaten=Kabupaten+Kudus"` dan
  `"?kabupaten=Kabupaten+Brebes"` → tidak ada lagi label "Sumber: Simperum" di
  sebelah angka yang tidak berasal dari Simperum. Uji pembeda yang paling murah:
  **kedua respons tidak boleh menampilkan persentase yang identik** kalau halaman
  dipertahankan dengan data nyata — hari ini keduanya 70,5% karena `Statistika.php:39`
  tidak mengalikan multiplier.
- **Uji balik yang mendefinisikan selesai:** kembalikan satu literal ke view,
  jalankan skripnya → **MERAH**. Kalau skripnya tetap hijau, ia mengecek URL yang
  salah dan tidak membuktikan apa pun.

### Kelompok B — keamanan

Buktinya adalah **penolakan** yang bisa diamati dari luar.

- **B1:** `curl` ke endpoint yang diketahui memicu PHP notice (`GET /Umum/info_rumah`,
  itu yang dipakai auditor) → respons **tidak** memuat `A PHP Error was encountered`
  dan **tidak** memuat `/home/u504551489`. Uji balik: hapus `SetEnv` dari `.htaccess`
  lokal, ulangi → path muncul lagi.
- **B2:** `GET /Chat/api_bot/halo` anonim → **404** (hari ini: 200 dalam 1,8–6 detik).
  Ini juga uji balik yang bersih: kembalikan `public` → 200 lagi.
- **B3:** 6× POST `report_komentar` anonim untuk satu `id_komentar` → semuanya ditolak
  dan `SELECT report_count, is_deleted FROM forum_komentar WHERE id_komentar=?`
  **tidak berubah**. Lalu dengan 5 akun login berbeda → `report_count` naik tepat 5,
  dan akun ke-1 melapor dua kali → tetap 5. Uji balik: cabut guard login → hitungannya
  naik dari POST anonim.
- **B4:** arahkan satu klien ke host bersertifikat tidak sah (`https://expired.badssl.com/`
  atau host uji lokal) → permintaan **gagal**. Hari ini ia berhasil. Ini satu-satunya
  cara membuktikan TLS benar-benar diverifikasi; membaca `CURLOPT_SSL_VERIFYPEER => true`
  di diff hanya membuktikan diff-nya benar. Pembanding yang sudah benar ada di repo:
  `Simperum_gateway.php:155-156`.
- **B6:** `GET /sikaper` anonim → 404, dan durasinya turun dari 5,45 detik ke tingkat
  404 biasa. Uji tambahan yang membedakan "route dicabut" dari "halaman kosong":
  respons tidak boleh memicu satu pun panggilan upstream.
- **B9:** panggil `Encryption_lib::encrypt()` dengan `KPKP_DATA_KEY` sengaja
  dikosongkan → **exception atau FALSE**, bukan plaintext. Uji balik: kembalikan
  `return $plaintext` di `Encryption_lib.php:71` → assert-nya merah.
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

---

## 6. Keputusan user yang menghambat

**Agent tidak boleh mengambil satu pun keputusan di tabel ini.** Sebagian karena
butuh data yang hanya user punya, sebagian karena menebaknya persis pelanggaran §17
yang sedang diperbaiki.

| # | Butir | Pertanyaan persis yang harus dijawab | Pilihan & konsekuensinya | Terblokir selama belum dijawab |
|---|---|---|---|---|
| 1 | **A1** | Tiga halaman spasial (`/sebaran_rusun`, `/profil_kumuh`, `/sebaran_sdgs`) — dicabut, atau dipertahankan? Kalau dipertahankan, **dari mana angka & daftar rusunawa yang benar diambil?** | (a) **Cabut** — hapus 3 route + 3 view, footer diperbaiki. Menit. Dinas kehilangan 3 halaman peta. (b) **Isi dengan data nyata** — butuh sumber data yang hari ini tidak ada di sistem; label "(Data Simulasi)" dicabut. Hari–minggu, tergantung sumbernya. (c) **Pertahankan sebagai demo** — ganti nama rusunawa nyata jadi fiktif, hapus koordinat asli dan klaim "Terdapat keluhan kerusakan aset" (`sebaran_rusun.php:155`), pertahankan label simulasi. Jam. | Seluruh A1. Berkas `pages/spasial/sebaran_rtlh.php` (butir E — nol renderer) ikut menunggu karena penghapusannya ikut pilihan (a) |
| 2 | **A2** | Halaman `/statistika` — dicabut, atau disambungkan? | (a) **Cabut** — menit. (b) **Sambungkan** — adapter Sikumbang & Sikaper ada; SIMPERUM ada tapi **mode masih `simulation`** sampai smoke test + sumber desil selesai (§17). Jadi pilihan (b) mewarisi blocker yang belum selesai, dan sebagian metrik akan tetap kosong. (c) **Pertahankan dengan label jujur** — cabut label "Sumber: X", ganti jadi keterangan simulasi. Jam. Tetap berisiko dikutip | Seluruh A2. Perhatikan: pilihan (b) menaruh A2 di belakang pekerjaan SIMPERUM yang bukan bagian dokumen ini |
| 3 | **A4** | Struktur organisasi — **siapa penyedia nama & jabatan resmi, dan dari dokumen apa?** | (a) **Cabut halaman** — menit. (b) **Kosongkan nama, pertahankan bagan jabatan** — jam, tidak butuh data dari siapa pun, dan menghentikan penyebutan individu nyata tanpa sumber hari itu juga. (c) **Isi dari dokumen resmi dinas** — butuh dokumen dari user; sekaligus butuh kesepakatan siapa yang memperbaruinya saat ada mutasi | Seluruh A4. Catatan: pilihan (b) tersedia sebagai mitigasi cepat sambil menunggu (c) — ini satu-satunya butir A yang punya jalur dua langkah |
| 4 | **A6** | Layar "Pengaturan Sistem" admin — dihapus, atau dinyatakan sungguhan? | (a) **Hapus layar** — menit. Penyimpanan yang NYATA sudah ada di `Admin_Content.php:44` + `Setting_model`, jadi tidak ada fungsi yang hilang. (b) **Sambungkan ke `Setting_model`** — hari; butuh keputusan field mana yang benar-benar dikelola, dan "Mode Pemeliharaan" butuh mekanisme yang belum ada sama sekali. | Seluruh A6. **Risiko selama menunggu spesifik dan tinggi:** dinas bisa menyalakan toggle "Mode Pemeliharaan" dan mengira situs tertutup, padahal terbuka penuh |
| 5 | **B5** | Kredensial Sikaper sudah masuk 2 commit riwayat git. **Apakah user punya kanal ke pengelola API Sikaper untuk meminta rotasi?** | (a) **Ada kanal** → minta rotasi; setelah itu pindahkan ke `.env`. (b) **Tidak ada kanal** → memindahkan ke `.env` adalah **nol perbaikan** (nilai lamanya tetap di riwayat git dan tetap sah). Satu-satunya mitigasi yang di tangan kita adalah **menghentikan pemakaian API-nya** — yang berarti mencabut `Sikaper.php` + `Sikaper_api.php` sepenuhnya, bukan hanya route-nya (B6) | B5 dinyatakan selesai. **Kita tidak bisa menjanjikan ini** — rotasi ada di tangan pihak ketiga. Yang bisa kita kerjakan tanpa jawaban: B4 (TLS) dan B6 (route) |
| 6 | **B10** | Halaman login production memajang email admin + sandi `password`. **Apakah sandi akun `admin@klinikpkp.jatengprov.go.id` di production sudah diganti?** | (a) **Sudah** → tunjukkan hasil percobaan login yang GAGAL; blok kredensial demo tetap boleh berdiri selama syarat §17 poin 14 terpenuhi. (b) **Belum** → siapa pun pengunjung situs bisa masuk sebagai admin penuh hari ini | **BELUM DIVERIFIKASI** — auditor sengaja tidak mencoba login production, dan agent juga tidak boleh. Sampai user menunjukkan buktinya, status ditulis apa adanya: **MASIH TERBUKA**. Selama itu, setiap klaim "production sudah aman" dari dokumen mana pun tidak sahih |
| 7 | **C2 + C3 + B7** | Fitur chat — **nyata atau dicabut?** Hari ini 3 endpoint menulis/membaca `tb_chat` yang tidak pernah ada di skema, migrasi, maupun dump; `chat_rooms`/`chat_messages` ada tapi 0 baris, disentuh hanya oleh `Chat_model` yang nol pemanggil, dan tidak lahir dari migrasi. Status `'admin'` dan label "Petugas" menjanjikan operator manusia yang **nol layar admin**-nya (nol entri di `dashboard_modules.php`) | (a) **Cabut** — hapus `Chat.php`, `Chat_model`, widget di `layouts/footer.php`, tabel yatim. Menit–jam. **Mematikan C2, C3, B7 sekaligus, plus 1 dari 12 titik B4.** (b) **Bangun** — butuh migrasi tabel, layar operator admin (§17 checklist penuh), `session_id` terikat sesi server (B7), dan §19 dua belas langkah. Minggu. Dan: **siapa yang akan duduk sebagai operator?** Tanpa jawaban itu, (b) menghasilkan layar kosong | Seluruh C2, C3, B7. **Selisih biaya kedua pilihan yang terbesar di dokumen ini** — mengerjakan `Chat.php` sebelum ini dijawab berisiko dibuang |
| 8 | **B8** | NIK plaintext di `sf_housing_queue.nik_pengaju`. **Boleh mengubah cara warga mengecek status tiket?** | Ini bukan sekadar "enkripsi kolomnya". Dibaca sendiri 29 Jul: `Program_model.php:121` menulis NIK plaintext, dan `Program_model.php:205` mencari tiket dengan `RIGHT(nik_pengaju, 4) =` — **pencarian itu mustahil di atas ciphertext AES-GCM**. (a) **Enkripsi + ganti mekanisme cek tiket** (kolom hash 4-digit terpisah, pola `usr_users` + `*_lookup_hash`; atau tiket saja tanpa NIK) — mengubah alur yang sudah dipresentasikan ke dinas. (b) **Biarkan plaintext** — digabung B1 bisa tercetak ke browser saat query gagal. (c) Enkripsi setelah B1 ditutup, terima risiko sisa | Seluruh B8. Butir ini **tidak ada di catatan inventaris** — ditemukan saat membaca kode untuk PRD ini, dan ia mengubah B8 dari "Hari" menjadi keputusan produk |

---

## 7. Batas — yang SENGAJA di luar dokumen ini

- **Bagian D inventaris (sudah beres).** Unggahan berkas, penyajian berkas privat,
  `Migrate.php`, SQL injection, proteksi berkas sensitif production, header keamanan,
  `admin/dashboard.php`, empat halaman pertanahan, `kemitraan_portal/*`,
  `sejarah_visi.php`, `tugas_pokok.php`, `action="#"`, klaim "tersinkronisasi".
  **Jangan dikerjakan ulang.** Catatan §17 "3 lokasi upload publik" sudah usang.
- **Roadmap pengembang T0–T6** ([`ROADMAP_PENGEMBANG_ADMIN.md`](./ROADMAP_PENGEMBANG_ADMIN.md))
  dan roadmap warga R0–R9 ([`ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md`](./ROADMAP_FORM_WARGA_SIMULASI_SIMPERUM.md)).
  Keduanya masih berlaku. Dokumen ini sengaja **tidak** menyisipkan butirnya ke sana:
  roadmap pengembang bekerja per-perjalanan, dan menyisipkan temuan lintas-domain
  membuat tiap tahapnya berhenti menghasilkan perjalanan yang bisa ditempuh — itu
  sudah dinyatakan eksplisit di bagian "Sengaja TIDAK masuk roadmap ini" milik
  dokumen itu, yang bahkan menyarankan roadmap terpisah. Ini roadmap terpisah itu.
- **CSP.** Production sudah punya X-Frame-Options, nosniff, HSTS 1 tahun,
  Referrer-Policy, Permissions-Policy; **CSP masih nihil**. Itu pengerasan, bukan
  utang dari inventaris ini, dan memasangnya di portal berisi Leaflet + inline script
  butuh sesi tersendiri. Dicatat, tidak dijadwalkan di sini.
- **Butir E (yatim).** `pages/spasial/sebaran_rtlh.php` (nol renderer) dan
  `pages/sikaper/index.php` (yatim begitu route B6 dicabut). Penghapusannya mengikuti
  keputusan #1 dan pekerjaan B6 — bukan tahap sendiri.
- **Fitur baru apa pun.** Termasuk layar moderasi forum untuk B3 dan generator
  sertifikat. B3 selesai dengan guard + dedup; layar moderasi adalah produk baru.
- **Penjadwalan rilis dan pembukaan `main`.** Di luar kendali dokumen ini.
- **Rotasi kredensial yang butuh pihak ketiga** (B5) dan **tindakan di panel
  hosting** (B10, penerapan `.htaccess` ke production). Kita menulis apa yang harus
  dilakukan dan cara membuktikannya; kita tidak bisa menjanjikan kapan selesai.
