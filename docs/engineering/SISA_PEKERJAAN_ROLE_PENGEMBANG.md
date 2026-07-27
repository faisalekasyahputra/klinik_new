# Sisa Pekerjaan Role Pengembang — 27 Juli 2026

> Hasil audit ujung-ke-ujung enam tahap perjalanan role `pengembang`, dijalankan terhadap kode SETELAH commit `5db1ba4` (fitur Minta Perbaikan). Tiap celah berat diuji ulang oleh penguji terpisah yang tugasnya membantah klaim — yang tidak lolos tidak masuk daftar ini.

**Putusan ASLI (27 Jul 2026, sebelum roadmap): BELUM-SELESAI.**

> ## ✅ STATUS AKHIR — 27 Jul 2026, roadmap T0–T6 SELESAI
>
> Semua 24 temuan di bawah sudah diberi status akhir per-butir (lihat penanda di tiap bagian): **21 SELESAI diperbaiki** (T0–T6, diverifikasi HTTP nyata di sebagian besar tahap, plus skrip uji perjalanan `uji_perjalanan_srp2.php` yang MERAH kalau perbaikan T1a dibalik), **1 DIBANTAH** (Stored XSS `detail.php:46`, sudah diuji dengan `CI_Security` asli), dan **2 KEPUTUSAN SADAR user** (kredensial demo dipertahankan untuk masa uji coba dinas; reCAPTCHA/verifikasi-email dibiarkan apa adanya — lihat AGENTS.md §17 poin 14 & 16). Satu item (formulir identitas 12-field) **sengaja tidak dikerjakan** — keputusan produk, bukan bug, lihat roadmap `ROADMAP_PENGEMBANG_ADMIN.md` bagian "Sengaja TIDAK masuk roadmap ini".
>
> **Yang MASIH terbuka, bukan tanggung jawab agent:** sandi akun admin production (`admin@klinikpkp.jatengprov.go.id`) masih `password` — rotasi ini aksi user, bukan kode. Status production sesungguhnya: kode + migrasi 01–16 SUDAH di-push dan dijalankan di production 27 Jul 2026 (lihat AGENTS.md §0a).
>
> Ringkasan per tahap detail di [`ROADMAP_PENGEMBANG_ADMIN.md`](../product/ROADMAP_PENGEMBANG_ADMIN.md), hash komit per tahap di `AGENTS.md` §0b.

Yang selesai adalah FITUR "Minta Perbaikan" (commit 5db1ba4): admin punya tiga keputusan dengan catatan wajib divalidasi server, wizard mendarat di langkah yang menampilkan catatan, indikator langkah benar, dokumen terkunci saat Pending/Diterima, dan draft SRP2 kini dijamin ada di semua jalur masuk lewat Auth_model::ensure_srp2_draft(). Itu memang tuntas dan terverifikasi. Tapi ROLE PENGEMBANG sebagai satu perjalanan utuh belum selesai: dari enam tahap, tiga dinyatakan belum tuntas (kirim-pengajuan, verifikasi-admin, pasca-keputusan) dan dua "tuntas dengan catatan". Dua hal menghentikan pemakaian nyata hari ini — (1) kredensial demo pengembang dan admin dipajang di halaman publik dan terbukti live bisa dipakai masuk sebagai pengembang tersertifikasi, (2) tombol "Terima" milik admin bisa gagal total sambil menampilkan flash sukses (Admin_Srp2.php:133 tidak memeriksa satu pun nilai balik query, tanpa transaksi), sehingga pengajuan yang paling wajar — perusahaan yang namanya sudah ada di 66 baris seed direktori — nyangkut Pending selamanya. Verifikasi ulang saya di kode saat ini: working tree bersih, keempat lokasi inti (Admin_Srp2.php:117-138, profil.php:117, Umum.php:565, Auth.php:686, Pengembang.php:165) masih persis seperti dilaporkan, dan blok "Kredensial Demo" masih ada di syarat.php:176 maupun login.php:147.

## Status per tahap

| Tahap | Status (27 Jul 2026, awal) | Status akhir (roadmap T0–T6) |
|---|---|---|
| daftar-masuk | tuntas-dengan-catatan | ✅ SELESAI (T0, T5) |
| unggah-dokumen | tuntas-dengan-catatan | ✅ SELESAI (T3) |
| kirim-pengajuan | belum-tuntas | ✅ SELESAI (T1a) |
| verifikasi-admin | belum-tuntas | ✅ SELESAI (T1a, T1b) |
| pasca-keputusan | belum-tuntas | ✅ SELESAI (T2, T4) |
| dashboard-akun | belum-tuntas | ✅ SELESAI (T2, T5, T6) |

---

## 🛑 Pemblokir (2)

### Hapus/gate kredensial demo di halaman publik (pengembang + admin)

`application/views/pages/pengembang/syarat.php:176 dan application/views/pages/auth/login.php:137-160`

Terbukti end-to-end, bukan dugaan: tamu anonim menerima pengembang@example.com/password di HTML, password_verify TRUE, dan POST do_login mengembalikan sesi pengembang dengan pengajuan berstatus Diterima (akses /akun, /akun/profil, /Pengembang/result/7 semuanya 200). Blok login.php memajang 7 akun termasuk admin@klinikpkp.jatengprov.go.id, dan keenamnya berpassword 'password'. View ini ikut ter-deploy ke staging/production. Ini harus beres sebelum apa pun yang lain dianggap aman.

> **⚠️ STATUS AKHIR: KEPUTUSAN SADAR user, bukan celah terlewat.** T0 (9a0488c) MENGHAPUS blok ini. Lalu user secara eksplisit meminta dikembalikan (`d9c28e8`, 27 Jul 2026) untuk masa uji coba dinas — enam akun demo diizinkan SELAMA berisi data contoh (AGENTS.md §17 poin 14). Yang MASIH terbuka dan BUKAN tanggung jawab agent: sandi `admin@klinikpkp.jatengprov.go.id` di production masih `password` — rotasi ini aksi user (ganti lewat aplikasi, bukan SQL langsung).

### Admin_Srp2::proses() melapor "Pengajuan diterima" padahal seluruh penulisan gagal

`application/controllers/Admin_Srp2.php:133-145`  ⏳ *perlu verifikasi runtime*

Tidak ada transaksi dan tidak satu pun nilai balik insert()/update() diperiksa. Nama perusahaan bentrok UNIQUE (66 nama seed) atau NULL -> insert gagal -> insert_id() 0 -> UPDATE srp2_registrations ditolak FK 1452 -> status tetap Pending, reviewed_by/reviewed_at kosong, tapi baris 145 tetap set flash sukses. Di production db_debug FALSE (database.php:85) seluruh rantai ini senyap. Pemohon melihat "Dalam Peninjauan" selamanya, admin yakin sudah memproses. Melanggar AGENTS.md §0d (dilarang menampilkan sukses karangan). Rantai errno-nya sudah dibuktikan mekanis lewat mysqli+rollback; yang belum dijalankan adalah alur HTTP-nya utuh.

> **✅ SELESAI — T1a (`f74047f`).** `Admin_Srp2::proses()` dibungkus `trans_start()`/`trans_complete()`/`trans_status()`, flash ditentukan dari hasil. Diuji dengan db_debug dipaksa FALSE (menirukan production) DAN lewat skrip uji perjalanan T6 (`uji_perjalanan_srp2.php`, uji negatif N3) yang secara eksplisit membuktikan: kalau fix ini DIBALIK, status berubah jadi "Diterima" padahal `certified_developer_id` tetap NULL — persis silent-partial-success yang dilaporkan di sini. Skrip MERAH saat dibalik, HIJAU setelah dikembalikan.


## 🔴 Tinggi (6)

### kirim_pengajuan() tidak memvalidasi nama_perusahaan (akar penyebab kegagalan approve)

`application/controllers/Pengembang.php:191`

Satu-satunya syarat kirim adalah 14 dokumen. nama_perusahaan boleh NULL (jalur role di-set admin; usr_users id 24 memang NULL di DB lokal), boleh string kosong (save_onboarding tidak memvalidasinya di server), dan boleh duplikat persis nama yang sudah ada di direktori. Memperbaiki Admin_Srp2 saja hanya mengubah kegagalan senyap jadi kegagalan berisik; gerbangnya harus di sini supaya baris Pending yang mustahil disetujui tidak pernah lahir.

> **✅ SELESAI — T1a (`f74047f`).** `kirim_pengajuan()` menolak nama kosong dan nama bentrok direktori; `save_onboarding()` juga divalidasi di server (hulu kedua). Diverifikasi ulang lewat skrip uji perjalanan T6 — uji negatif N2 membuktikan baris dengan nama bentrok TIDAK PERNAH lahir jadi Pending (tetap Draft).

### "Lihat Profil Publik" memakai ID pengajuan untuk halaman ber-ID direktori

`application/views/pages/pengaturan/profil.php:117`

Diuji live: registrasi id=7 milik pengembang@example.com membuka /Pengembang/profil/7 yang menampilkan "PT. PUSAKA LAWU INDONESIA" — perusahaan orang lain, lengkap dengan badge Bersertifikat. Kolom yang benar (certified_developer_id) sudah ada tapi tidak dipakai, dan tombolnya tetap dirender walau kolom itu NULL sehingga tidak ada jalan benar sama sekali dari dashboard ke profil publik.

> **✅ SELESAI — T4 (`bc0f6af`).** Tombol memakai `certified_developer_id`, tidak dirender kalau NULL. Migrasi backfill `20260701000016` mengisi kolom itu untuk registrasi Diterima lama. Diverifikasi lewat skrip uji perjalanan T6: tombol menunjuk cid yang benar, dan profil publik menampilkan perusahaan yang benar (bukan perusahaan lain).

### Halaman publik mencap "Terverifikasi SRP2" untuk registrasi status APA PUN

`application/controllers/Umum.php:565 (dan :460, :470)`

get_where('srp2_registrations', ['nama_perusahaan' => $nama]) tanpa filter status, view hanya cek !empty(). Draft yang belum pernah dikirim pun tampil sebagai terverifikasi + badge Verified + tombol Download Sertifikat, tanpa login. Siapa pun bisa mendaftar dengan nama perusahaan orang lain dan langsung mendapat cap verifikasi di halaman publik pemerintah. Dibuktikan dengan merender view asli lewat harness (Draft dan Ditolak sama-sama memunculkan kartu hijau); yang belum dijalankan cuma alur pendaftarannya karena butuh menulis ke DB.

> **✅ SELESAI — T0 (`9a0488c`).** Cap "Terverifikasi" kini HANYA dari `srp2_certified_developers WHERE status_aktif=1` — sumber yang sama dengan `Pengembang::sertifikasi()`. Diverifikasi HTTP nyata: Draft dan Diterima-tanpa-baris-direktori TIDAK dicap terverifikasi, cap hilang saat `status_aktif` dimatikan dan kembali saat dinyalakan.

### Pengajuan yang sudah Diterima lalu Ditolak tetap aktif di direktori publik

`application/controllers/Admin_Srp2.php:117`  ⏳ *perlu verifikasi runtime*

Blok direktori hanya jalan saat status 'Diterima'. Pengecualian yang sengaja dibuat untuk "Minta Perbaikan" (dan memang dijelaskan di UI) ikut menutupi cabang Tolak yang tidak disengaja: certified_developer_id tetap menempel, status_aktif tetap 1, dan pengembang yang resmi ditolak masih tampil di /Pengembang/sertifikasi dengan badge Bersertifikat. Tidak ada peringatan apa pun di halaman admin untuk kasus ini.

> **✅ SELESAI — T1a (`f74047f`).** Cabang Tolak (termasuk yang sebelumnya pernah Diterima) mencabut `status_aktif=0` secara eksplisit, dipisah dari pengecualian Minta Perbaikan.

### Reflected XSS di google_callback() — salah satu pintu masuk pengembang

`application/controllers/Auth.php:686`  ⏳ *perlu verifikasi runtime*

base_url($redirect_to) di-echo mentah ke dalam string JavaScript berkutip tunggal; sanitize_redirect() menolak URL eksternal tapi meloloskan path relatif berisi kutip tunggal. Payload disimpan di session lewat ?from= lalu dieksekusi di jendela korban setelah dia menyelesaikan login Google, same-origin dan sudah terautentikasi. Tidak ada Content-Security-Policy sama sekali. Sudah dibuktikan di lab bahwa CI_Security::xss_clean() mempertahankan payload utuh; yang belum diuji end-to-end adalah alur OAuth Google sungguhan.

> **✅ SELESAI — T5.** `$redirect_to` di-`json_encode()` lewat helper `_oauth_close_popup()`, plus CSP `script-src 'nonce-...'` per-response. Payload single-quote dibuktikan tetap di dalam string JS lewat skrip PHP terisolasi (json_encode menghasilkan `\/` untuk `/` dan escape penuh untuk kutip). ⚠️ Alur OAuth Google SUNGGUHAN (kode otorisasi asli) tetap belum diuji end-to-end — tidak bisa disimulasikan dari CLI.

### Ganti dokumen meninggalkan berkas fisik yatim yang selamat dari hapus akun

`application/controllers/Pengembang.php:165`

db->replace() menimpa baris DB tanpa unlink stored_name lama. Terukur di lingkungan lokal: 20 berkas di private_uploads/srp2/7/ melawan 14 baris DB — 6 yatim (~7 MB) dari satu pengajuan uji. Karena _cleanup_owned_files() hanya menghapus nama yang masih tercatat di DB, akta/NPWP/laporan keuangan/data pengurus tetap tersimpan di server setelah pengguna menghapus akunnya (cakupan UU PDP). Fitur Minta Perbaikan justru memperbanyak siklus ganti dokumen.

> **✅ SELESAI — T3 (`de7e7f2`).** `stored_name` lama diambil sebelum `replace()`, di-`unlink()` sesudahnya. `_cleanup_owned_files()` menyapu berdasarkan isi DISK. Pembersih sekali-jalan sudah dijalankan di lokal (6 yatim, 5,5 MB) — **belum dijalankan di production**, dicatat di AGENTS.md §0b.


## 🟠 Sedang (14)

### Auth::lanjutkan() bisa membuat draft kedua yang menutupi pengajuan yang sudah dikirim

`application/controllers/Auth.php:552`  ⏳ *perlu verifikasi runtime*

ensure_srp2_draft($uid,'Draft') menyempitkan pencarian tapi tetap INSERT kalau tidak ketemu; flag session srp2_quick_registration bertahan sepanjang sesi. Pemohon yang membuka /Auth/verify_pending setelah mengirim akan mendapat Draft kosong baru yang menang di semua query ORDER BY id DESC — wizard kembali 0/14 dan pengajuan Pending lenyap dari pandangannya padahal admin masih melihatnya. Belum direproduksi live (mandat read-only).

> **✅ SELESAI — T2 (`195ec64`), dengan SATU KOREKSI terhadap klaim di atas.** Guard dipasang di `ensure_srp2_draft()` sendiri (bukan di pemanggilnya): tidak pernah INSERT kalau user sudah punya baris non-Draft. Klaim "flag bertahan sepanjang sesi" TERBANTAH SEBAGIAN — `Auth::lanjutkan()` meng-`unset_userdata()` flag itu tepat setelah dipakai, jendelanya lebih sempit (daftar cepat → kirim pengajuan TANPA pernah membuka `/Auth/lanjutkan` → baru buka), tapi akibatnya sama. Diverifikasi: buka verify_pending lalu lanjutkan saat Pending tidak melahirkan baris kedua.

### catatan_admin lama tidak dibersihkan saat kirim ulang — /akun dan wizard bertentangan

`application/controllers/Pengembang.php:198`

Update hanya menulis status Pending; catatan penolakan/perbaikan lama (juga reviewed_by/reviewed_at) tetap menempel. Dashboard /akun menampilkan badge "Dalam Peninjauan" DITAMBAH kotak catatan penolakan lama, sementara wizard sengaja menyembunyikannya. Dua permukaan yang sama-sama dilihat pemohon menceritakan hal berbeda tepat sesudah dia memperbaiki dan mengirim ulang — persis alur yang baru saja dibangun.

> **✅ SELESAI — T2 (`195ec64`).** Kirim ulang membersihkan `catatan_admin`, `reviewed_by`, `reviewed_at`. Diverifikasi: sesudah kirim ulang ketiga layar pemohon "Dalam Peninjauan" dengan nol jejak catatan lama, dan kolomnya NULL di DB.

### /akun/profil tidak menampilkan catatan admin untuk pengajuan yang dibuka kembali

`application/views/pages/pengaturan/profil.php:108`

Blok catatan hanya dirender saat status 'Ditolak'. Untuk keputusan ketiga yang baru ditambahkan (Draft + catatan wajib), halaman ini menampilkan badge "Lengkapi Dokumen" — sama persis dengan draft yang belum pernah dikirim — dan nol keterangan, padahal flash admin berbunyi "Pengembang melihat catatan Anda di dashboardnya". Ini lubang langsung di fitur yang baru dinyatakan selesai. Cabang else-nya juga mencap status tak dikenal sebagai "Ditolak".

> **✅ SELESAI — T2 (`195ec64`).** Halaman membedakan Draft-dibuka-kembali dari draft biasa dan menampilkan catatan admin untuk KEDUA keputusan. Cabang else diganti: status ditampilkan apa adanya, tidak lagi dicap "Ditolak".

### Identitas pemohon tidak pernah dikumpulkan, dan yang ada tidak ditampilkan ke verifikator

`application/models/Auth_model.php:210 dan application/views/admin/srp2/detail.php:36`

Grep menyeluruh: tidak ada satu baris kode pun yang menulis nama_peserta, nik_ktp, jabatan, atau no_whatsapp ke srp2_registrations (formulir 12-field lama diarsipkan tanpa pengganti). Di sisi lain halaman keputusan admin juga tidak merender NIK/NIB/asosiasi/no_keanggotaan yang ADA di baris pengajuan lama, sehingga admin diminta memutuskan sertifikasi tanpa nilai pembanding di layar. Penguji menurunkan tingkatnya karena admin tetap bisa membuka 14 berkas dan halaman resi yang merender kolom kosong itu yatim (tidak tertaut dari mana pun) — jadi ini gap proses, bukan layar rusak.

> **SEBAGIAN SELESAI.** ✅ Bagian "tidak ditampilkan ke verifikator" — **SELESAI T1b** (`22e176a`): `detail.php` merender nik_ktp/nama_peserta/jabatan/nib/asosiasi/no_keanggotaan/no_whatsapp yang sudah ada di baris pengajuan, kolom kosong ditandai apa adanya. ❌ Bagian "tidak pernah dikumpulkan" (formulir 12-field) **SENGAJA TIDAK DIKERJAKAN** — keputusan PRODUK (field mana yang benar-benar dibutuhkan verifikator harus diputuskan user, bukan ditebak agent), bukan bug. Lihat `ROADMAP_PENGEMBANG_ADMIN.md` bagian "Sengaja TIDAK masuk roadmap ini" untuk alasan lengkap dan peringatan pola kolom (`nik_ktp` VARCHAR plaintext UNIQUE) yang JANGAN disalin kalau user memutuskan membangunnya nanti.

### Tidak ada validasi transisi status di sisi server, dan pengajuan yang sudah diputuskan hilang dari jangkauan admin

`application/controllers/Admin_Srp2.php:89 dan :26`

proses() tidak pernah melihat status_verifikasi lama, sehingga satu POST rakitan bisa menerbitkan Draft berisi 0 dokumen ke direktori publik lengkap dengan reviewed_by — satu-satunya penjaga hari ini adalah kondisi di view. Dan pending() memfilter mati status='Pending', jadi setelah Tolak/Minta-Perbaikan admin hanya bisa membuka kembali pengajuan itu dengan menebak URL detail/<id>; tidak ada cara memantau siapa yang diminta perbaikan tapi tak kunjung mengirim ulang.

> **✅ SELESAI — T1a (`f74047f`) untuk validasi transisi, T1b (`22e176a`) untuk jangkauan admin.** Daftar transisi sah ditegakkan server + status asal di WHERE. Status jadi FILTER (default dari registry `pending_where`), bukan WHERE mati — admin bisa menyaring "diminta perbaikan tapi belum kirim ulang". Diverifikasi via skrip uji T6 (N1): transisi Draft->Diterima ditolak server.

### Data pengajuan bisa diubah pemohon saat Pending/Diterima, dan UPDATE-nya tanpa batas baris

`application/controllers/Pengaturan.php:141-182`

update_pengembang_profile() tidak pernah memeriksa status_verifikasi padahal dokumen sengaja dikunci saat Pending/Diterima. Akibatnya nama/alamat bisa bergeser di bawah tangan admin yang sedang menilai (yang tersalin ke direktori adalah nilai terbaru), dan sesudah Diterima nama bisa diganti bebas sehingga pencocokan nama string di Pengembang::profil() bisa menarik kontak pemohon ke profil publik perusahaan lain. UPDATE-nya juga hanya where('user_id') tanpa id, menimpa semua baris milik user itu.

> **✅ SELESAI — T1a (`f74047f`).** Menolak perubahan saat status ∈ {Pending, Diterima}; UPDATE menyertakan `where('id')`. T4 (`bc0f6af`) memisahkan identitas (nama, dikunci) dari kontak (alamat/website/sosmed, boleh berubah + menular ke publik) — mengunci semuanya akan mencegah pengembang yang pindah kantor memperbarui listing publiknya.

### Direktori publik tidak pernah tersinkron: perubahan profil tidak menular, registrasi Diterima lama tidak di-backfill

`application/controllers/Pengaturan.php:181 dan application/migrations/20260701000014_add_srp2_certified_developer_link.php`

Baris direktori hanya diisi sekali saat approve, jadi ganti alamat/website/Instagram tidak pernah sampai ke publik walau labelnya berbunyi "Kontak publik — ditampilkan di halaman profil pengembang". Migrasi penambah kolom juga tidak mengisi baris yang sudah Diterima sebelum fitur upsert ada: registrasi id=7 berstatus Diterima, certified_developer_id NULL, dan tidak ada baris direktori bernama sama — dashboardnya bilang Diterima tapi dia tidak ada di direktori publik.

> **✅ SELESAI — T4 (`bc0f6af`).** Satu `upsert_direktori_publik()` dipanggil dari approve MAUPUN update profil pemohon. Migrasi backfill `20260701000016` (idempoten, dijalankan 2x berturut-turut menghasilkan DB identik) mengisi baris lama. Diverifikasi: ubah alamat kantor → alamat baru tampil di profil publik.

### Jalur unggah non-AJAX (dokumen.php) rusak di tiga arah

`application/controllers/Pengembang.php:159 dan application/views/pages/pengembang/dokumen.php`

Halaman ini nyata dicapai lewat Auth::lanjutkan() dan mulai_unggah(). (a) Penulisan DB baru terjadi setelah seluruh loop move_uploaded_file, jadi satu berkas gagal validasi membuat semua berkas sebelumnya mendarat di disk tanpa baris DB — yatim permanen. (b) View tidak menerima daftar document_key yang sudah ada, jadi ke-14 baris selalu tampil kosong walau badge berkata 13/14, mendorong unggah ulang semuanya. (c) View tidak sadar status: form dan tombol Kirim tetap tampil saat Pending/Diterima, ditolak server dengan pesan error yang terlihat seperti sistem rusak.

> **✅ SELESAI — T3 (`de7e7f2`).** Dua jalur unggah dijadikan SATU: `mulai_unggah()`/`dokumen()` redirect ke wizard, view lama diarsipkan (dihapus T6, lihat temuan Rendah). Akar tiga sub-temuan ini hilang sekaligus dengan menghapus jalur yang memang tidak dipakai, bukan menambal ketiganya.

### Pengembang tidak bisa membuka kembali dokumen yang sudah dia unggah

`application/controllers/Pengembang.php:116`

Tidak ada endpoint penyaji berkas untuk pemohon; serve_private_file() hanya dipakai sisi pengelola. Wizard cuma menampilkan badge "Tersimpan" tanpa original_name, jadi pemohon tak tahu berkas mana menempati slot mana. Paling menyakitkan tepat pada fitur Minta Perbaikan: admin menulis "Form 4 salah", pemohon tidak punya cara memeriksa apa yang dulu dia kirim. Dashboard malah melabeli tombolnya "Lihat Dokumen" padahal yang dibuka cuma daftar centang.

> **✅ SELESAI — T3 (`de7e7f2`).** `lihat_dokumen_saya()` menyajikan berkas ke pemiliknya sendiri lewat `serve_private_file()`, anti-IDOR pola sama dengan `simpan_dokumen()`. Diuji: dokumen sendiri 200, milik registrasi orang lain 404.

### Wizard: batal ganti berkas mengunci tombol Kirim, dan state tidak diperbarui setelah kirim

`application/views/pages/pengembang/syarat.php:437 dan :563`

clearFile() menyetel status 'idle' bukan 'done', sehingga dokumen yang SUDAH ada di server terhitung hilang: hitungan turun ke 13 dan tombol Kirim disabled padahal server memegang 14/14 — dan yang paling sering menekan tombol Ganti justru pemohon yang sedang memperbaiki. submitPengajuan() juga hanya menaikkan step tanpa menyetel status='Pending', jadi kembali ke panel 3 memperlihatkan mode dapat-diedit untuk pengajuan yang sudah terkunci. Keduanya pulih dengan reload, tapi tidak ada petunjuk apa pun.

> **✅ SELESAI — clearFile() di T1b (`22e176a`), submitPengajuan() di T2 (`195ec64`).** Diverifikasi di browser sungguhan: Ganti→Batal mengembalikan 14/14 dengan tombol Kirim tetap aktif TANPA reload; kembali ke panel 3 sesudah kirim menampilkan mode read-only.

### Gerbang pendaftaran terbuka: captcha nonaktif, tanpa rate limit, verifikasi email hanya simulasi

`application/controllers/Auth.php:250`  ⏳ *perlu verifikasi runtime*

Verifikasi reCAPTCHA dilewati seluruhnya kalau kunci kosong, dan di .env lokal keduanya memang kosong. do_register tidak punya pembatasan laju sama sekali (berbeda dari do_login). do_verify_email() menyetel email_verified_at tanpa token dan tidak ada jalur yang menolak akun belum terverifikasi. Satu POST bisa menciptakan akun pengembang aktif berulang-ulang, masing-masing berhak membuka pengajuan ke meja admin. Perlu dicek apakah staging/production mengisi kunci reCAPTCHA.

> **SEBAGIAN SELESAI.** ✅ Rate limit — **SELESAI T0** (`9a0488c`): 5 percobaan/10 menit per IP, pola disalin dari `do_login()`. Diuji: 20 POST beruntun ditahan mulai percobaan ke-6. ⚠️ reCAPTCHA & `do_verify_email()` dummy — **KEPUTUSAN SADAR user, T5**: dibiarkan apa adanya, bukan diisi kunci maupun dihapus widget-nya. Nol gating di kode manapun membaca `email_verified_at`, jadi murni kosmetik, bukan celah aktif. Jangan "perbaiki" tanpa perintah baru — lihat AGENTS.md §17 poin 16.

### Akun pengembang tanpa jalan pemulihan dan tanpa identitas: lupa sandi mati, daftar cepat melewati onboarding, role diubah admin tak menyegarkan sesi

`application/controllers/Auth.php:280`

Tiga hal saling memperburuk. Daftar cepat menyetel profile_completed=1 sehingga akun tidak pernah diminta nama/username/NIK/telepon (tiga dari empat akun pengembang di DB punya name dan username NULL). Lupa kata sandi belum berfungsi (form action="#", tidak ada method pemroses). Jadi saat akun terkunci, admin pun tidak punya dasar memverifikasi permintaan reset selain email. Terpisah: Admin_Users::update_role() tidak menyegarkan sesi, sehingga pengguna yang baru diangkat jadi pengembang ditolak wizard dengan kartu "bukan akun pengembang" tanpa diberitahu bahwa dia cuma perlu keluar-masuk.

> **SEBAGIAN SELESAI, tiga bagian.** ✅ Daftar cepat tanpa identitas — **SELESAI T5**: `Auth_model::generate_unique_username()` menurunkan name/username dari email + nama perusahaan yang memang sudah diisi. ⚠️ Lupa sandi — **KEPUTUSAN SADAR user, T5**: form palsu DIHAPUS (bukan dibangun jadi email asli — repo ini nol infrastruktur SMTP). ✅ Role diubah admin tak refresh sesi — **SELESAI T6**: perbaikan TERMURAH dipilih sengaja (pesan wizard menyarankan keluar-masuk lagi), bukan menyegarkan sesi user lain dari sisi admin.

### Ganti password tanpa password lama, hapus akun tanpa re-autentikasi server

`application/controllers/Pengaturan.php:227 dan :255`

Kekuatan password baru divalidasi, kepemilikan akun tidak. delete_account() hanya memeriksa method POST; konfirmasi ketik-nama sepenuhnya di klien. Siapa pun yang sempat memakai sesi pengembang bisa mengunci pemilik aslinya keluar atau menghapus seluruh pengajuan SRP2-nya lewat FK CASCADE. Sudah tercatat di AGENTS.md §18 dan masih berlaku apa adanya.

> **✅ SELESAI — T5.** Keduanya wajib `current_password` diverifikasi `password_verify()` server-side. Diuji end-to-end via curl: password salah ditolak (akun tidak berubah/tidak terhapus), password benar diterima, akun terhapus lewat jalur nyata.

### is_numeric vs (int) tidak sinkron — berkas bisa mendarat di direktori yang tak pernah dibaca siapa pun

`application/controllers/Pengembang.php:147`

Guard memakai is_numeric($id), query memakai (int)$id, tapi $id MENTAH diteruskan ke private_upload_dir(). Untuk /simpan_dokumen/7.0: kepemilikan sah, baris DB registration_id=7, tapi berkas tersimpan ke srp2/70/. Admin melihat 404 untuk SETIAP dokumen sementara hitungan 14/14 lolos dan pengajuan tampak lengkap; _cleanup_owned_files() juga buta terhadap direktori itu. permitted_uri_chars mengizinkan titik dan ketiga varian id diterima router.

> **✅ SELESAI — T3 (`de7e7f2`).** `$id` dinormalisasi SEKALI di pintu masuk `simpan_dokumen()`, dipakai untuk SEMUA operasi di method itu. Diuji: `POST /Pengembang/simpan_dokumen/7.0` mendarat di `srp2/7/`, tidak membuat direktori ketiga.


## 🟡 Rendah (2)

### Rapikan pelaporan sukses dan audit trail sisi admin

`application/controllers/Admin_Srp2.php:106 dan :178`

Pola sama seperti pemblokir #2 di jalur manual: save() menampilkan "Daftar pengembang diperbarui." tanpa memeriksa hasil query, jadi bentrok UNIQUE tampil sebagai sukses di production. Selain itu catatan_admin dihapus saat pengajuan Diterima, sehingga alasan "dulu diminta perbaikan karena X" hilang permanen — tidak ada tabel log lain yang menyimpannya. Halaman direktori juga satu-satunya tabel admin SRP2 yang belum memakai pola server-side B8 (66 baris dirender sekaligus).

> **✅ SELESAI — ketiganya di T1b (`22e176a`) kecuali B8 di T4 (`bc0f6af`).** `save()` memeriksa hasil query; `catatan_admin` tidak lagi di-NULL-kan saat Diterima (satu baris, bukan tabel log baru — usulan tabel log dibuang, lihat roadmap); halaman Direktori SRP2 kini pakai pola `table_state()`→`paginate_state()` yang sama dengan `pending()`.

### Kumpulan gesekan kecil yang menyesatkan pengguna

`application/views/pages/pengembang/syarat.php:532`  ⏳ *perlu verifikasi runtime*

Token CSRF kedaluwarsa di tengah unggah tampil sebagai "Koneksi terputus" dan tombol Ulangi gagal terus (satu-satunya jalan keluar reload, tak diberitahukan). $_FILES['name'] berbentuk array memicu TypeError 500 sebelum pemeriksaan error. Penguncian akun tidak pernah mereset penghitung sehingga satu salah ketik langsung mengunci 15 menit lagi. Konfirmasi hapus akun mustahil dipenuhi akun tanpa username (dibandingkan dengan literal null). Kartu "Anda sudah terdaftar" menampilkan nama kosong karena do_register tidak mengembalikan name. Judul baris SRP2 di /akun kosong saat nama_perusahaan NULL. Halaman resi lama v_sertifikasi.php masih menaut berkas ke .assets/uploads/ dan yatim — kandidat dihapus.

> **✅ SEMUA TUJUH SUB-TEMUAN SELESAI**, tersebar tiga tahap:
> - Token CSRF kedaluwarsa + `$_FILES` array TypeError — **T3** (`de7e7f2`): sesi kedaluwarsa dikenali dari 403 sebelum membaca JSON, spanduk pemulihan; array ditolak sebelum `pathinfo()`.
> - Penguncian akun tidak reset penghitung — **T6**: `increment_login_attempts()` mereset ke 0 kalau lockout sebelumnya sudah lewat waktu.
> - Konfirmasi hapus akun literal `null` — **T5**: fallback dirantai `username ?? name ?? email` (email selalu ada, `NOT NULL UNIQUE`).
> - Kartu "sudah terdaftar" nama kosong — **T6**: respons `do_register()` kini menyertakan `name`.
> - Judul baris SRP2 kosong — **T6**: placeholder jujur `(Nama perusahaan belum diisi)`, dipicu nyata oleh `Admin_Users::update_role()` yang bisa mempromosikan akun ke `pengembang` tanpa nama perusahaan.
> - `v_sertifikasi.php` yatim — **DIHAPUS T6**, bersama `Pengembang::result()` (nol pemanggil terverifikasi lewat grep sebelum dihapus).

---

## Ternyata sudah aman — jangan dikerjakan ulang

- DIBANTAH — "Stored XSS skema javascript: di admin/srp2/detail.php:46". Nilai javascript:/vbscript:/data:base64 tidak pernah sampai ke DB: global_xss_filtering = TRUE (config.php:444) membuat $this->input->post() menjalankan xss_clean, dan _do_never_allowed() mengganti pola itu jadi [removed] di seluruh string. Diuji dengan kelas CI_Security ASLI dari repo ini atas belasan varian (beda huruf, pemenggalan kata, URL-encode, null byte) — semuanya mati; varian entity (&colon;, &#58;) lolos filter tapi mati di render karena html_escape meng-escape &. Jalur tulisnya cuma satu (Pengaturan.php:182) dan sudah terfilter. Sisa yang benar: detail.php:46 memang tidak memvalidasi skema sendiri, jadi pertahanannya bergantung pada satu setelan global yang komentarnya sendiri menandai DEPRECATED — pengerasan murah (pola $safe_url sudah ada di profil.php:2-4), bukan celah yang bisa direproduksi hari ini.
  > **✅ Pengerasan diterapkan T6.** `detail.php` sekarang memvalidasi skema URL sendiri (`$safe_url`, pola disalin dari `pages/pengembang/profil.php:2-4`) — tautan non-http(s) tidak lagi dirender sama sekali, tidak bergantung `global_xss_filtering` lagi.
- Konsolidasi draft SRP2 nyata di kode, bukan klaim dokumen: Auth_model::ensure_srp2_draft() dipanggil di kelima jalur masuk (daftar cepat, login termasuk non-AJAX, onboarding, Google callback, wizard), idempotent, dan nama_perusahaan nullable sehingga draft untuk akun tanpa nama perusahaan tetap ter-insert. Kekhawatiran "draft gagal terbentuk" tidak terbukti.
- Fitur Minta Perbaikan benar-benar utuh: catatan wajib divalidasi di SERVER untuk Ditolak maupun Draft (bukan cuma atribut required HTML), reviewed_by/reviewed_at terisi, baris direktori sengaja tidak disentuh saat dibuka kembali dan itu dijelaskan di UI, dan pemohon memang bisa mengedit lagi karena kunci hanya menutup Pending/Diterima.
- Fondasi keamanan unggah masih utuh: anti-IDOR WHERE id AND user_id, kunci status ditegakkan server dengan 409 (bukan cuma UI), whitelist ekstensi + MIME asli via finfo + cap 2 MB + nama acak 16 byte, penyimpanan privat, dan proteksinya diuji live — GET langsung ke berkas lewat HTTP membalas 403 sementara aplikasinya 200.
- Hitungan 14/14 tidak bisa dicurangi: srp2_documents punya UNIQUE (registration_id, document_key) plus FK ON DELETE CASCADE — diverifikasi lewat SHOW CREATE TABLE, bukan dari dokumen.
- Halaman resi lama Pengembang/result/{id} tidak membocorkan data lintas akun: disaring ['id' => $id, 'user_id' => sesi], id milik orang lain menghasilkan 404.
- Tabel pending admin sudah aman dari SQL injection lewat ?sort=: table_state() mem-whitelist kolom sort secara ketat, dan seluruh form admin SRP2 ber-CSRF dengan guard method POST.
- Tombol "Download Sertifikat" di /akun/profil jujur — disabled dengan penjelasan, tidak mengulang pola sukses karangan.
