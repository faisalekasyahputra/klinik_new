# Siklus Hidup Informasi yang Dikecualikan - pertukaran, penghapusan, dan audit

Menjawab satu butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 7 "Proteksi Data"):

- **7.3** Melakukan pertukaran, penghapusan, dan audit informasi yang dikecualikan.

("Informasi yang dikecualikan" = data pribadi dan rahasia: NIK, NPWP, profil warga, dokumen identitas, kredensial.)
Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Keadaan sebelum (21 Sep 2026)

| Temuan | Dampak |
|---|---|
| **Retensi tidak punya penyapu** (tercatat di AGENTS.md sebagai "belum dikerjakan"). Snapshot SIMPERUM (terenkripsi, profil RTLH per NIK) punya `expires_at` tetapi tidak ada pekerjaan yang menghapusnya. Uji pada salinan DB lokal: **22 dari 28 snapshot sudah kedaluwarsa dan masih tersimpan**. Penghitung laju, token surel kedaluwarsa, langganan push nonaktif, dan log juga menumpuk | Data pribadi bertahan jauh melewati masa berlaku yang dinyatakan aplikasi sendiri |
| Hapus akun hanya menyapu berkas SRP2, **satu dari lima** kolom berkas KKN/Magang (surat pengantar), dan dokumen onboarding | Proposal, laporan akhir, surat balasan, dan surat SIMPERUM tertinggal di disk sebagai berkas yatim setelah barisnya hilang lewat CASCADE |
| **Draf penilaian warga** tidak dihapus saat akun dihapus (kunci asing `SET NULL`), lengkap dengan data terenkripsi dan foto buktinya | Data pribadi (identitas, lokasi, foto rumah) bertahan tanpa pemilik |
| Surel akun tetap terbaca di `sys_jejak_audit` (kolom pelaku, dan disebut di teks "Membuka kunci akun x@y" oleh admin) dan di `forum_diskusi.email_user` | Identitas orang yang sudah menghapus akunnya tetap dapat dibaca |
| Tidak ada catatan siapa staf yang membuka berkas identitas privat atau melihat NPWP/profil warga yang terdekripsi | Akses ke informasi pribadi oleh staf tidak dapat diaudit |
| Tidak ada register aliran data ke pihak luar; tidak ada pemeriksaan bahwa setiap jalur unduhan diaudit | Pertukaran data tidak terpantau |
| Tidak ada pemeriksaan bahwa setiap kolom yang menunjuk ke akun punya kebijakan penghapusan, dan bahwa ekspor data akun mencakup semua tabel milik akun | Tabel baru dapat lolos dari penghapusan dan ekspor tanpa ketahuan |

Yang sudah baik dan diverifikasi (tidak diubah): ekspor data akun oleh pemilik (butuh kata sandi, dibatasi laju, diaudit), 6 pengunduh spreadsheet diaudit (`rekap_diunduh`), penyimpanan berkas privat di luar webroot, enkripsi AES-256-GCM untuk NIK/NPWP/payload di tabel profil dan snapshot.

## 2. Yang dibangun

| Bagian | Isi |
|---|---|
| Kebijakan | `config/data_lifecycle.php`: masa retensi, nasib SETIAP kolom yang menunjuk `usr_users` (26 kolom: `cascade` atau `set_null` beserta alasannya), kolom pemilik tanpa kunci asing, cakupan ekspor akun (tabel diekspor dan yang dikecualikan beralasan), register pertukaran data, dan pengaturan audit akses |
| **Penghapusan 1: retensi** | `libraries/Penyapu_retensi.php`. Menghapus: snapshot SIMPERUM yang sudah lewat `expires_at` + 7 hari **kecuali yang dirujuk penilaian TERKIRIM** (bukti asal data sebuah arsip); penghitung laju > 2 hari; token surel kedaluwarsa > 1 hari (dikosongkan); langganan push nonaktif > 90 hari; log aplikasi > 180 hari; jejak audit > 5 tahun. Sengaja tidak disapu: cache respons layanan luar (data publik, dipakai sebagai cadangan saat layanan itu mati), sesi (dikelola PHP/hosting), berkas unggahan (milik akun). Mode kering hanya menghitung. Hasil tiap putaran dicatat di jejak audit (`retensi_dijalankan`, hanya jumlah) |
| Pemicu retensi | Sekali per 24 jam. **Hosting tidak menyediakan cron di SSH**, maka penyapu dipicu permintaan web pertama yang mendapati berkas penanda basi, dijalankan SESUDAH respons terkirim ke klien, dijaga `flock` (satu proses), dan gagal diam-diam. Pelari manual/cron: `php index.php retensi jalankan [kering]` (hanya CLI) |
| **Penghapusan 2: hapus akun** | `libraries/Data_erasure.php`, dipanggil `User_model::delete_user_account()`: menyapu SELURUH direktori berkas KKN/Magang milik akun (kelima kolom, termasuk yang diunggah admin) dan DRAF penilaian warga beserta foto dan barisnya; menyamarkan surel akun di jejak audit (kolom pelaku dan penyebutan di teks ringkasan/rincian) menjadi pseudonim stabil `akun-dihapus-<12 heks>` berkunci HMAC (`KPKP_DATA_PEPPER`); `forum_diskusi.email_user` disamarkan; catatan `akun_dihapus` memuat jumlah berkas dan draf yang disapu. Yang SENGAJA tetap (arsip layanan/keputusan) dan alasannya ada di config |
| **Pertukaran** | Register aliran data (9 pihak): data apa, arah, perlindungan, dan apakah pribadi. **Tes menggagalkan** bila ada berkas yang membuka koneksi keluar (daftar izin pemindai kode berbahaya) tetapi tidak terdaftar, atau sebaliknya; aliran data pribadi wajib menyebut HTTPS/soket. Ekspor akun tercakup ke 10 tabel milik akun; tabel lain dikecualikan dengan alasan tertulis |
| **Audit akses staf** | `MY_Controller::catat_akses_data_pribadi()`: pembukaan berkas privat oleh staf (`serve_private_file`, satu-satunya pintu), profil warga terdekripsi di detail antrean, NPWP di daftar SRP2, dan detail pengajuan SRP2 dicatat di `sys_jejak_audit` (pelaku, peran, objek, waktu). Pemilik yang melihat datanya sendiri tidak dicatat. Ditekan per pelaku+objek 10 menit supaya menyegarkan halaman tidak membanjiri log |
| Penjaga | `tests/data_lifecycle_test.php` (offline) dan `tests/data_lifecycle_db_test.php` (MySQL nyata; wajib `UJI_DB_BOLEH_TULIS=1` karena menjalankan penyapu sungguhan) |

## 3. Sikap kegagalan (sengaja)

- Penyapu retensi dan audit akses **gagal diam-diam** (dicatat di log): pengamat tidak boleh menggagalkan permintaan yang sedang dilayani. Konsekuensinya audit akses bisa terlewat bila DB bermasalah pada saat itu.
- Hapus akun: berkas dan draf disapu SEBELUM transaksi DB (`unlink` tidak dapat di-rollback; pola yang sama dengan penyapu SRP2 yang sudah ada).
- Pseudonim audit memakai HMAC dengan `KPKP_DATA_PEPPER`; tanpa kunci itu pseudonim tidak dapat dicocokkan dengan daftar surel tebakan.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/data_lifecycle_db_test.php` (MySQL nyata, 150 pemeriksaan) | **Skema vs kebijakan:** 26 kolom FK ke `usr_users` dicocokkan dengan kebijakan (dua arah, aturan `CASCADE`/`SET NULL` harus sama), setiap kolom `user_id` tanpa FK harus tercatat, setiap tabel milik akun harus diekspor atau dikecualikan beralasan. **Retensi** pada tabel nyata dengan fixture: snapshot lama terhapus, dalam masa tenggang tetap, dirujuk arsip tetap, dirujuk draf terhapus (draf tidak ikut hilang), penghitung laju, token surel, langganan push (hanya nonaktif yang lama), audit > 5 tahun, log (hanya `log-YYYY-MM-DD.php` yang lama), mode kering tidak mengubah apa pun, idempoten, catatan audit hanya jumlah. **Hapus akun**: 5 berkas KKN + foto draf + draf terhapus, penilaian terkirim dan berkasnya utuh, berkas akun lain tidak tersentuh, surel tidak tersisa terbaca di audit, konteks tetap terbaca lewat pseudonim, CASCADE menghapus baris KKN |
| `tests/data_lifecycle_test.php` (offline, 112 pemeriksaan) | Kebijakan retensi masuk akal (audit ≥ 2 tahun, log ≥ 90 hari, penghitung laju ≥ jendela terpanjang), register pertukaran = daftar koneksi keluar yang diizinkan, **hanya tiga file boleh menyajikan unduhan** dan tiap pengunduh spreadsheet mengaudit, audit akses staf terpasang, pseudonim, pelari CLI dan pemicu web |
| Uji mutasi | Menyapu hanya satu berkas KKN, tidak melindungi snapshot arsip, menghapus satu kebijakan FK, dan tidak menyamarkan audit masing-masing menggagalkan tes |
| Uji HTTP lokal | Permintaan web pertama memicu penyapu (penanda dibuat, satu catatan audit) dan permintaan kedua tidak menyapu lagi; akses staf ke daftar/detail SRP2 tercatat sekali per jendela; **hapus akun lewat `akun/delete` dengan data di banyak tabel**: direktori KKN dan draf hilang, penilaian terkirim tetap, `email_user` disamarkan, nol surel terbaca di audit, penyebutan oleh admin menjadi pseudonim |
| Pemindai kode berbahaya | Bersih; `register_shutdown_function` di `MY_Controller` ditinjau dan dimasukkan ke daftar izin dengan alasan |

## 5. Prosedur operasional

1. **Melihat apa yang akan disapu**: `php index.php retensi jalankan kering`. Menjalankan sungguhan: tanpa `kering` (juga otomatis harian).
2. **Menyetel masa retensi**: `config/data_lifecycle.php`; tes menjaga batas bawah wajar.
3. **Tabel baru yang menunjuk akun**: tambahkan kebijakan di `fk_usr_users`/`kolom_tanpa_fk` dan (bila milik pemilik) di `ekspor_akun`; tes DB menggagalkan bila lupa. Jalankan dengan `UJI_DB_BOLEH_TULIS=1` di DB lokal, **jangan** di production.
4. **Koneksi keluar baru**: daftarkan di `pertukaran` beserta data yang dikirim; tes menggagalkan bila lupa.
5. **Melihat akses staf**: Jejak Audit, aksi `akses_berkas_privat`, `akses_penilaian_warga`, `akses_npwp_srp2`, `akses_pengajuan_srp2`; penyapu: `retensi_dijalankan`.

## 6. Batas yang diakui

- **Arsip layanan tetap ada setelah akun dihapus, sesuai rancangan yang sudah ada:** aduan (pesan, nama, surel, lampiran), antrean pengajuan (`sf_housing_queue`) dan penilaian TERKIRIM. Penghapusannya lewat permintaan penghapusan data layanan yang ditinjau admin (fitur yang sudah ada), bukan otomatis. Aplikasi ini tidak menghapus arsip keputusan dinas secara sepihak.
- **`sf_housing_queue.nik_pengaju` (alur lama "solusi pembiayaan") menyimpan NIK dan nama TANPA enkripsi.** Kolom itu ditulis `Program_model::create_housing_submission` dan dibaca layar admin (disamarkan 4 digit terakhir) dan pencarian antrean (LIKE atas NIK dan nama). Mengenkripsinya memutus pencarian tersebut dan butuh indeks pencarian baru (migrasi). **Belum diubah; butuh keputusan pemilik sistem.** Jalur baru (`Housing_assessment_model`) sudah menulis `NULL` di kolom itu dan menyimpan profil terenkripsi.
- **Audit akses gagal diam-diam**; dan hanya mencakup jalur yang dikenal (berkas privat lewat `serve_private_file`, profil warga terdekripsi di detail antrean, NPWP dan detail SRP2). Akses langsung ke database oleh administrator server tidak tercakup.
- **Jejak audit dapat diubah** oleh siapa pun yang punya akses tulis DB (tidak ada rantai hash atau penyimpanan tak-ubah); pseudonimisasi saat hapus akun sendiri adalah pengubahan yang disengaja.
- **Penyapu bergantung pada lalu lintas web** (pemicu sekali per hari oleh permintaan pertama); pada situs yang sepi ia tertunda sampai ada permintaan. Tidak ada cron di hosting ini; bila kelak tersedia, jalankan `php index.php retensi jalankan` harian.
- **Berkas yatim yang sudah ada sebelum rilis ini tidak disapu otomatis** (berkas KKN dan draf dari akun yang dihapus sebelumnya). Penyapu hanya bekerja pada akun yang dihapus sesudah rilis; pembersihan yatim lama adalah pekerjaan terpisah yang perlu dicocokkan dengan DB.
- Log aplikasi lebih tua dari 180 hari dihapus; bila dinas membutuhkan retensi log lebih lama untuk penyelidikan, naikkan `log_aplikasi_hari`.
- Pertukaran data dengan SIMPERUM mengirim NIK di URI keluar (kontrak API mereka; lihat [`URI_DAN_METODE_HTTP.md`](URI_DAN_METODE_HTTP.md)).
