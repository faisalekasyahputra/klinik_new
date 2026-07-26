# Sisa Pekerjaan Role Pengembang — 27 Juli 2026

> Hasil audit ujung-ke-ujung enam tahap perjalanan role `pengembang`, dijalankan terhadap kode SETELAH commit `5db1ba4` (fitur Minta Perbaikan). Tiap celah berat diuji ulang oleh penguji terpisah yang tugasnya membantah klaim — yang tidak lolos tidak masuk daftar ini.

**Putusan: BELUM-SELESAI**

Yang selesai adalah FITUR "Minta Perbaikan" (commit 5db1ba4): admin punya tiga keputusan dengan catatan wajib divalidasi server, wizard mendarat di langkah yang menampilkan catatan, indikator langkah benar, dokumen terkunci saat Pending/Diterima, dan draft SRP2 kini dijamin ada di semua jalur masuk lewat Auth_model::ensure_srp2_draft(). Itu memang tuntas dan terverifikasi. Tapi ROLE PENGEMBANG sebagai satu perjalanan utuh belum selesai: dari enam tahap, tiga dinyatakan belum tuntas (kirim-pengajuan, verifikasi-admin, pasca-keputusan) dan dua "tuntas dengan catatan". Dua hal menghentikan pemakaian nyata hari ini — (1) kredensial demo pengembang dan admin dipajang di halaman publik dan terbukti live bisa dipakai masuk sebagai pengembang tersertifikasi, (2) tombol "Terima" milik admin bisa gagal total sambil menampilkan flash sukses (Admin_Srp2.php:133 tidak memeriksa satu pun nilai balik query, tanpa transaksi), sehingga pengajuan yang paling wajar — perusahaan yang namanya sudah ada di 66 baris seed direktori — nyangkut Pending selamanya. Verifikasi ulang saya di kode saat ini: working tree bersih, keempat lokasi inti (Admin_Srp2.php:117-138, profil.php:117, Umum.php:565, Auth.php:686, Pengembang.php:165) masih persis seperti dilaporkan, dan blok "Kredensial Demo" masih ada di syarat.php:176 maupun login.php:147.

## Status per tahap

| Tahap | Status |
|---|---|
| daftar-masuk | tuntas-dengan-catatan |
| unggah-dokumen | tuntas-dengan-catatan |
| kirim-pengajuan | belum-tuntas |
| verifikasi-admin | belum-tuntas |
| pasca-keputusan | belum-tuntas |
| dashboard-akun | belum-tuntas |

---

## 🛑 Pemblokir (2)

### Hapus/gate kredensial demo di halaman publik (pengembang + admin)

`application/views/pages/pengembang/syarat.php:176 dan application/views/pages/auth/login.php:137-160`

Terbukti end-to-end, bukan dugaan: tamu anonim menerima pengembang@example.com/password di HTML, password_verify TRUE, dan POST do_login mengembalikan sesi pengembang dengan pengajuan berstatus Diterima (akses /akun, /akun/profil, /Pengembang/result/7 semuanya 200). Blok login.php memajang 7 akun termasuk admin@klinikpkp.jatengprov.go.id, dan keenamnya berpassword 'password'. View ini ikut ter-deploy ke staging/production. Ini harus beres sebelum apa pun yang lain dianggap aman.

### Admin_Srp2::proses() melapor "Pengajuan diterima" padahal seluruh penulisan gagal

`application/controllers/Admin_Srp2.php:133-145`  ⏳ *perlu verifikasi runtime*

Tidak ada transaksi dan tidak satu pun nilai balik insert()/update() diperiksa. Nama perusahaan bentrok UNIQUE (66 nama seed) atau NULL -> insert gagal -> insert_id() 0 -> UPDATE srp2_registrations ditolak FK 1452 -> status tetap Pending, reviewed_by/reviewed_at kosong, tapi baris 145 tetap set flash sukses. Di production db_debug FALSE (database.php:85) seluruh rantai ini senyap. Pemohon melihat "Dalam Peninjauan" selamanya, admin yakin sudah memproses. Melanggar AGENTS.md §0d (dilarang menampilkan sukses karangan). Rantai errno-nya sudah dibuktikan mekanis lewat mysqli+rollback; yang belum dijalankan adalah alur HTTP-nya utuh.


## 🔴 Tinggi (6)

### kirim_pengajuan() tidak memvalidasi nama_perusahaan (akar penyebab kegagalan approve)

`application/controllers/Pengembang.php:191`

Satu-satunya syarat kirim adalah 14 dokumen. nama_perusahaan boleh NULL (jalur role di-set admin; usr_users id 24 memang NULL di DB lokal), boleh string kosong (save_onboarding tidak memvalidasinya di server), dan boleh duplikat persis nama yang sudah ada di direktori. Memperbaiki Admin_Srp2 saja hanya mengubah kegagalan senyap jadi kegagalan berisik; gerbangnya harus di sini supaya baris Pending yang mustahil disetujui tidak pernah lahir.

### "Lihat Profil Publik" memakai ID pengajuan untuk halaman ber-ID direktori

`application/views/pages/pengaturan/profil.php:117`

Diuji live: registrasi id=7 milik pengembang@example.com membuka /Pengembang/profil/7 yang menampilkan "PT. PUSAKA LAWU INDONESIA" — perusahaan orang lain, lengkap dengan badge Bersertifikat. Kolom yang benar (certified_developer_id) sudah ada tapi tidak dipakai, dan tombolnya tetap dirender walau kolom itu NULL sehingga tidak ada jalan benar sama sekali dari dashboard ke profil publik.

### Halaman publik mencap "Terverifikasi SRP2" untuk registrasi status APA PUN

`application/controllers/Umum.php:565 (dan :460, :470)`

get_where('srp2_registrations', ['nama_perusahaan' => $nama]) tanpa filter status, view hanya cek !empty(). Draft yang belum pernah dikirim pun tampil sebagai terverifikasi + badge Verified + tombol Download Sertifikat, tanpa login. Siapa pun bisa mendaftar dengan nama perusahaan orang lain dan langsung mendapat cap verifikasi di halaman publik pemerintah. Dibuktikan dengan merender view asli lewat harness (Draft dan Ditolak sama-sama memunculkan kartu hijau); yang belum dijalankan cuma alur pendaftarannya karena butuh menulis ke DB.

### Pengajuan yang sudah Diterima lalu Ditolak tetap aktif di direktori publik

`application/controllers/Admin_Srp2.php:117`  ⏳ *perlu verifikasi runtime*

Blok direktori hanya jalan saat status 'Diterima'. Pengecualian yang sengaja dibuat untuk "Minta Perbaikan" (dan memang dijelaskan di UI) ikut menutupi cabang Tolak yang tidak disengaja: certified_developer_id tetap menempel, status_aktif tetap 1, dan pengembang yang resmi ditolak masih tampil di /Pengembang/sertifikasi dengan badge Bersertifikat. Tidak ada peringatan apa pun di halaman admin untuk kasus ini.

### Reflected XSS di google_callback() — salah satu pintu masuk pengembang

`application/controllers/Auth.php:686`  ⏳ *perlu verifikasi runtime*

base_url($redirect_to) di-echo mentah ke dalam string JavaScript berkutip tunggal; sanitize_redirect() menolak URL eksternal tapi meloloskan path relatif berisi kutip tunggal. Payload disimpan di session lewat ?from= lalu dieksekusi di jendela korban setelah dia menyelesaikan login Google, same-origin dan sudah terautentikasi. Tidak ada Content-Security-Policy sama sekali. Sudah dibuktikan di lab bahwa CI_Security::xss_clean() mempertahankan payload utuh; yang belum diuji end-to-end adalah alur OAuth Google sungguhan.

### Ganti dokumen meninggalkan berkas fisik yatim yang selamat dari hapus akun

`application/controllers/Pengembang.php:165`

db->replace() menimpa baris DB tanpa unlink stored_name lama. Terukur di lingkungan lokal: 20 berkas di private_uploads/srp2/7/ melawan 14 baris DB — 6 yatim (~7 MB) dari satu pengajuan uji. Karena _cleanup_owned_files() hanya menghapus nama yang masih tercatat di DB, akta/NPWP/laporan keuangan/data pengurus tetap tersimpan di server setelah pengguna menghapus akunnya (cakupan UU PDP). Fitur Minta Perbaikan justru memperbanyak siklus ganti dokumen.


## 🟠 Sedang (14)

### Auth::lanjutkan() bisa membuat draft kedua yang menutupi pengajuan yang sudah dikirim

`application/controllers/Auth.php:552`  ⏳ *perlu verifikasi runtime*

ensure_srp2_draft($uid,'Draft') menyempitkan pencarian tapi tetap INSERT kalau tidak ketemu; flag session srp2_quick_registration bertahan sepanjang sesi. Pemohon yang membuka /Auth/verify_pending setelah mengirim akan mendapat Draft kosong baru yang menang di semua query ORDER BY id DESC — wizard kembali 0/14 dan pengajuan Pending lenyap dari pandangannya padahal admin masih melihatnya. Belum direproduksi live (mandat read-only).

### catatan_admin lama tidak dibersihkan saat kirim ulang — /akun dan wizard bertentangan

`application/controllers/Pengembang.php:198`

Update hanya menulis status Pending; catatan penolakan/perbaikan lama (juga reviewed_by/reviewed_at) tetap menempel. Dashboard /akun menampilkan badge "Dalam Peninjauan" DITAMBAH kotak catatan penolakan lama, sementara wizard sengaja menyembunyikannya. Dua permukaan yang sama-sama dilihat pemohon menceritakan hal berbeda tepat sesudah dia memperbaiki dan mengirim ulang — persis alur yang baru saja dibangun.

### /akun/profil tidak menampilkan catatan admin untuk pengajuan yang dibuka kembali

`application/views/pages/pengaturan/profil.php:108`

Blok catatan hanya dirender saat status 'Ditolak'. Untuk keputusan ketiga yang baru ditambahkan (Draft + catatan wajib), halaman ini menampilkan badge "Lengkapi Dokumen" — sama persis dengan draft yang belum pernah dikirim — dan nol keterangan, padahal flash admin berbunyi "Pengembang melihat catatan Anda di dashboardnya". Ini lubang langsung di fitur yang baru dinyatakan selesai. Cabang else-nya juga mencap status tak dikenal sebagai "Ditolak".

### Identitas pemohon tidak pernah dikumpulkan, dan yang ada tidak ditampilkan ke verifikator

`application/models/Auth_model.php:210 dan application/views/admin/srp2/detail.php:36`

Grep menyeluruh: tidak ada satu baris kode pun yang menulis nama_peserta, nik_ktp, jabatan, atau no_whatsapp ke srp2_registrations (formulir 12-field lama diarsipkan tanpa pengganti). Di sisi lain halaman keputusan admin juga tidak merender NIK/NIB/asosiasi/no_keanggotaan yang ADA di baris pengajuan lama, sehingga admin diminta memutuskan sertifikasi tanpa nilai pembanding di layar. Penguji menurunkan tingkatnya karena admin tetap bisa membuka 14 berkas dan halaman resi yang merender kolom kosong itu yatim (tidak tertaut dari mana pun) — jadi ini gap proses, bukan layar rusak.

### Tidak ada validasi transisi status di sisi server, dan pengajuan yang sudah diputuskan hilang dari jangkauan admin

`application/controllers/Admin_Srp2.php:89 dan :26`

proses() tidak pernah melihat status_verifikasi lama, sehingga satu POST rakitan bisa menerbitkan Draft berisi 0 dokumen ke direktori publik lengkap dengan reviewed_by — satu-satunya penjaga hari ini adalah kondisi di view. Dan pending() memfilter mati status='Pending', jadi setelah Tolak/Minta-Perbaikan admin hanya bisa membuka kembali pengajuan itu dengan menebak URL detail/<id>; tidak ada cara memantau siapa yang diminta perbaikan tapi tak kunjung mengirim ulang.

### Data pengajuan bisa diubah pemohon saat Pending/Diterima, dan UPDATE-nya tanpa batas baris

`application/controllers/Pengaturan.php:141-182`

update_pengembang_profile() tidak pernah memeriksa status_verifikasi padahal dokumen sengaja dikunci saat Pending/Diterima. Akibatnya nama/alamat bisa bergeser di bawah tangan admin yang sedang menilai (yang tersalin ke direktori adalah nilai terbaru), dan sesudah Diterima nama bisa diganti bebas sehingga pencocokan nama string di Pengembang::profil() bisa menarik kontak pemohon ke profil publik perusahaan lain. UPDATE-nya juga hanya where('user_id') tanpa id, menimpa semua baris milik user itu.

### Direktori publik tidak pernah tersinkron: perubahan profil tidak menular, registrasi Diterima lama tidak di-backfill

`application/controllers/Pengaturan.php:181 dan application/migrations/20260701000014_add_srp2_certified_developer_link.php`

Baris direktori hanya diisi sekali saat approve, jadi ganti alamat/website/Instagram tidak pernah sampai ke publik walau labelnya berbunyi "Kontak publik — ditampilkan di halaman profil pengembang". Migrasi penambah kolom juga tidak mengisi baris yang sudah Diterima sebelum fitur upsert ada: registrasi id=7 berstatus Diterima, certified_developer_id NULL, dan tidak ada baris direktori bernama sama — dashboardnya bilang Diterima tapi dia tidak ada di direktori publik.

### Jalur unggah non-AJAX (dokumen.php) rusak di tiga arah

`application/controllers/Pengembang.php:159 dan application/views/pages/pengembang/dokumen.php`

Halaman ini nyata dicapai lewat Auth::lanjutkan() dan mulai_unggah(). (a) Penulisan DB baru terjadi setelah seluruh loop move_uploaded_file, jadi satu berkas gagal validasi membuat semua berkas sebelumnya mendarat di disk tanpa baris DB — yatim permanen. (b) View tidak menerima daftar document_key yang sudah ada, jadi ke-14 baris selalu tampil kosong walau badge berkata 13/14, mendorong unggah ulang semuanya. (c) View tidak sadar status: form dan tombol Kirim tetap tampil saat Pending/Diterima, ditolak server dengan pesan error yang terlihat seperti sistem rusak.

### Pengembang tidak bisa membuka kembali dokumen yang sudah dia unggah

`application/controllers/Pengembang.php:116`

Tidak ada endpoint penyaji berkas untuk pemohon; serve_private_file() hanya dipakai sisi pengelola. Wizard cuma menampilkan badge "Tersimpan" tanpa original_name, jadi pemohon tak tahu berkas mana menempati slot mana. Paling menyakitkan tepat pada fitur Minta Perbaikan: admin menulis "Form 4 salah", pemohon tidak punya cara memeriksa apa yang dulu dia kirim. Dashboard malah melabeli tombolnya "Lihat Dokumen" padahal yang dibuka cuma daftar centang.

### Wizard: batal ganti berkas mengunci tombol Kirim, dan state tidak diperbarui setelah kirim

`application/views/pages/pengembang/syarat.php:437 dan :563`

clearFile() menyetel status 'idle' bukan 'done', sehingga dokumen yang SUDAH ada di server terhitung hilang: hitungan turun ke 13 dan tombol Kirim disabled padahal server memegang 14/14 — dan yang paling sering menekan tombol Ganti justru pemohon yang sedang memperbaiki. submitPengajuan() juga hanya menaikkan step tanpa menyetel status='Pending', jadi kembali ke panel 3 memperlihatkan mode dapat-diedit untuk pengajuan yang sudah terkunci. Keduanya pulih dengan reload, tapi tidak ada petunjuk apa pun.

### Gerbang pendaftaran terbuka: captcha nonaktif, tanpa rate limit, verifikasi email hanya simulasi

`application/controllers/Auth.php:250`  ⏳ *perlu verifikasi runtime*

Verifikasi reCAPTCHA dilewati seluruhnya kalau kunci kosong, dan di .env lokal keduanya memang kosong. do_register tidak punya pembatasan laju sama sekali (berbeda dari do_login). do_verify_email() menyetel email_verified_at tanpa token dan tidak ada jalur yang menolak akun belum terverifikasi. Satu POST bisa menciptakan akun pengembang aktif berulang-ulang, masing-masing berhak membuka pengajuan ke meja admin. Perlu dicek apakah staging/production mengisi kunci reCAPTCHA.

### Akun pengembang tanpa jalan pemulihan dan tanpa identitas: lupa sandi mati, daftar cepat melewati onboarding, role diubah admin tak menyegarkan sesi

`application/controllers/Auth.php:280`

Tiga hal saling memperburuk. Daftar cepat menyetel profile_completed=1 sehingga akun tidak pernah diminta nama/username/NIK/telepon (tiga dari empat akun pengembang di DB punya name dan username NULL). Lupa kata sandi belum berfungsi (form action="#", tidak ada method pemroses). Jadi saat akun terkunci, admin pun tidak punya dasar memverifikasi permintaan reset selain email. Terpisah: Admin_Users::update_role() tidak menyegarkan sesi, sehingga pengguna yang baru diangkat jadi pengembang ditolak wizard dengan kartu "bukan akun pengembang" tanpa diberitahu bahwa dia cuma perlu keluar-masuk.

### Ganti password tanpa password lama, hapus akun tanpa re-autentikasi server

`application/controllers/Pengaturan.php:227 dan :255`

Kekuatan password baru divalidasi, kepemilikan akun tidak. delete_account() hanya memeriksa method POST; konfirmasi ketik-nama sepenuhnya di klien. Siapa pun yang sempat memakai sesi pengembang bisa mengunci pemilik aslinya keluar atau menghapus seluruh pengajuan SRP2-nya lewat FK CASCADE. Sudah tercatat di AGENTS.md §18 dan masih berlaku apa adanya.

### is_numeric vs (int) tidak sinkron — berkas bisa mendarat di direktori yang tak pernah dibaca siapa pun

`application/controllers/Pengembang.php:147`

Guard memakai is_numeric($id), query memakai (int)$id, tapi $id MENTAH diteruskan ke private_upload_dir(). Untuk /simpan_dokumen/7.0: kepemilikan sah, baris DB registration_id=7, tapi berkas tersimpan ke srp2/70/. Admin melihat 404 untuk SETIAP dokumen sementara hitungan 14/14 lolos dan pengajuan tampak lengkap; _cleanup_owned_files() juga buta terhadap direktori itu. permitted_uri_chars mengizinkan titik dan ketiga varian id diterima router.


## 🟡 Rendah (2)

### Rapikan pelaporan sukses dan audit trail sisi admin

`application/controllers/Admin_Srp2.php:106 dan :178`

Pola sama seperti pemblokir #2 di jalur manual: save() menampilkan "Daftar pengembang diperbarui." tanpa memeriksa hasil query, jadi bentrok UNIQUE tampil sebagai sukses di production. Selain itu catatan_admin dihapus saat pengajuan Diterima, sehingga alasan "dulu diminta perbaikan karena X" hilang permanen — tidak ada tabel log lain yang menyimpannya. Halaman direktori juga satu-satunya tabel admin SRP2 yang belum memakai pola server-side B8 (66 baris dirender sekaligus).

### Kumpulan gesekan kecil yang menyesatkan pengguna

`application/views/pages/pengembang/syarat.php:532`  ⏳ *perlu verifikasi runtime*

Token CSRF kedaluwarsa di tengah unggah tampil sebagai "Koneksi terputus" dan tombol Ulangi gagal terus (satu-satunya jalan keluar reload, tak diberitahukan). $_FILES['name'] berbentuk array memicu TypeError 500 sebelum pemeriksaan error. Penguncian akun tidak pernah mereset penghitung sehingga satu salah ketik langsung mengunci 15 menit lagi. Konfirmasi hapus akun mustahil dipenuhi akun tanpa username (dibandingkan dengan literal null). Kartu "Anda sudah terdaftar" menampilkan nama kosong karena do_register tidak mengembalikan name. Judul baris SRP2 di /akun kosong saat nama_perusahaan NULL. Halaman resi lama v_sertifikasi.php masih menaut berkas ke .assets/uploads/ dan yatim — kandidat dihapus.

---

## Ternyata sudah aman — jangan dikerjakan ulang

- DIBANTAH — "Stored XSS skema javascript: di admin/srp2/detail.php:46". Nilai javascript:/vbscript:/data:base64 tidak pernah sampai ke DB: global_xss_filtering = TRUE (config.php:444) membuat $this->input->post() menjalankan xss_clean, dan _do_never_allowed() mengganti pola itu jadi [removed] di seluruh string. Diuji dengan kelas CI_Security ASLI dari repo ini atas belasan varian (beda huruf, pemenggalan kata, URL-encode, null byte) — semuanya mati; varian entity (&colon;, &#58;) lolos filter tapi mati di render karena html_escape meng-escape &. Jalur tulisnya cuma satu (Pengaturan.php:182) dan sudah terfilter. Sisa yang benar: detail.php:46 memang tidak memvalidasi skema sendiri, jadi pertahanannya bergantung pada satu setelan global yang komentarnya sendiri menandai DEPRECATED — pengerasan murah (pola $safe_url sudah ada di profil.php:2-4), bukan celah yang bisa direproduksi hari ini.
- Konsolidasi draft SRP2 nyata di kode, bukan klaim dokumen: Auth_model::ensure_srp2_draft() dipanggil di kelima jalur masuk (daftar cepat, login termasuk non-AJAX, onboarding, Google callback, wizard), idempotent, dan nama_perusahaan nullable sehingga draft untuk akun tanpa nama perusahaan tetap ter-insert. Kekhawatiran "draft gagal terbentuk" tidak terbukti.
- Fitur Minta Perbaikan benar-benar utuh: catatan wajib divalidasi di SERVER untuk Ditolak maupun Draft (bukan cuma atribut required HTML), reviewed_by/reviewed_at terisi, baris direktori sengaja tidak disentuh saat dibuka kembali dan itu dijelaskan di UI, dan pemohon memang bisa mengedit lagi karena kunci hanya menutup Pending/Diterima.
- Fondasi keamanan unggah masih utuh: anti-IDOR WHERE id AND user_id, kunci status ditegakkan server dengan 409 (bukan cuma UI), whitelist ekstensi + MIME asli via finfo + cap 2 MB + nama acak 16 byte, penyimpanan privat, dan proteksinya diuji live — GET langsung ke berkas lewat HTTP membalas 403 sementara aplikasinya 200.
- Hitungan 14/14 tidak bisa dicurangi: srp2_documents punya UNIQUE (registration_id, document_key) plus FK ON DELETE CASCADE — diverifikasi lewat SHOW CREATE TABLE, bukan dari dokumen.
- Halaman resi lama Pengembang/result/{id} tidak membocorkan data lintas akun: disaring ['id' => $id, 'user_id' => sesi], id milik orang lain menghasilkan 404.
- Tabel pending admin sudah aman dari SQL injection lewat ?sort=: table_state() mem-whitelist kolom sort secara ketat, dan seluruh form admin SRP2 ber-CSRF dengan guard method POST.
- Tombol "Download Sertifikat" di /akun/profil jujur — disabled dengan penjelasan, tidak mengulang pola sukses karangan.
