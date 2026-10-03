<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Satu registry untuk seluruh pembatas laju berbasis sys_batas_laju.
 * `dimensions` menentukan penghitung yang berdiri sendiri. Permintaan ditolak
 * bila salah satu dimensi mencapai batasnya.
 */
$config['rate_limit_policies'] = [
    'account_export' => ['limit' => 3, 'window' => 3600, 'dimensions' => ['account'], 'concurrent_dimension' => 'account'],
    // Verifikasi sandi di akun/delete: tanpa batas khusus, sesi yang dibajak bisa menebak sandi
    // 120 kali/menit lewat batas umum tulis_akun (temuan UAT universitas U8).
    'account_delete' => ['limit' => 5, 'window' => 3600, 'dimensions' => ['account']],
    // Verifikasi sandi saat ganti sandi di akun/update. Hanya yang GAGAL dihitung (inspect lalu
    // hit, pola login), per akun dan per IP (keputusan pemilik produk 29 Sep 2026).
    'profile_password' => ['limit' => 5, 'window' => 3600, 'dimensions' => ['account', 'ip']],
    'privacy_deletion_request' => ['limit' => 2, 'window' => 86400, 'dimensions' => ['account']],
    // Formulir aduan (Umum::simpan_aduan): tiap kiriman masuk antrean triase bersama dan memicu push ke
    // semua super admin. Per akun 5/jam; per IP lebih longgar (20/jam) karena satu kantor kelurahan atau
    // CGNAT seluler berbagi alamat. Dua kebijakan, bukan satu berdimensi ganda, supaya batasnya berbeda.
    'aduan_kirim'    => ['limit' => 5,  'window' => 3600, 'dimensions' => ['account']],
    'aduan_kirim_ip' => ['limit' => 20, 'window' => 3600, 'dimensions' => ['ip']],
    'login' => [
        'limit' => 30,
        'window' => 300,
        'dimensions' => ['ip'],
    ],
    // Gagal masuk per pasangan IP + nama masuk (email/username apa adanya, huruf kecil). Pengganti
    // kunci per akun (3 Okt 2026): yang tertahan hanya IP penebak, pemilik akun dari IP lain tetap
    // bisa masuk. Kunci memakai nama masuk, bukan id akun, supaya akun ada dan tidak ada berperilaku sama.
    'login_akun' => ['limit' => 5, 'window' => 900, 'dimensions' => ['key']],
    // Index::buka_foto: unduhan foto yang BELUM ada di cache (hit cache tidak dihitung). Satu tampilan
    // /cari_rumah memuat 10-20 foto; 120 per 10 menit cukup untuk menjelajah, bukan untuk menyedot hulu.
    'foto_hulu' => ['limit' => 120, 'window' => 600, 'dimensions' => ['ip']],
    'register' => [
        'limit' => 5,
        'window' => 600,
        'dimensions' => ['ip'],
    ],
    /* Kode OTP pendaftaran (libraries/Otp_pendaftaran.php). Hitungan di sesi (jeda berlipat, 5 kiriman,
       5 kode salah) hilang begitu cookie dibuang atau email diganti, jadi batas yang mengikat dipegang
       di sini, per email TUJUAN (kunci = sha256 email huruf kecil, bukan email polos):
       - otp_kirim: setiap kode yang hendak dikirim, 5 per jam per email (meredam banjir email ke satu alamat).
       - otp_salah: hanya kode SALAH, 10 per jam per email; sesudah itu kode benar pun ditolak.
       - otp_salah_ip: hanya kode SALAH, 30 per jam per IP, untuk penebak yang berganti-ganti email.
       Akibatnya pendaftaran sah untuk email yang sedang dibanjiri orang lain ikut tertahan sampai
       jendelanya habis; pesannya umum dan tidak menyatakan apa pun tentang akun. */
    'otp_kirim'    => ['limit' => 5,  'window' => 3600, 'dimensions' => ['key']],
    'otp_salah'    => ['limit' => 10, 'window' => 3600, 'dimensions' => ['key']],
    'otp_salah_ip' => ['limit' => 30, 'window' => 3600, 'dimensions' => ['ip']],
    'simperum_lookup' => [
        'limit' => 10,
        'window' => 60,
        'dimensions' => ['ip'],
    ],
    'housing_submit' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip'],
    ],
    /* 'ticket_lookup' DIHAPUS 16 Agt 2026 - satu-satunya pemakainya
       (Program::cek_tiket()) sudah tidak melakukan pencarian tiket+NIK
       apa pun lagi, lihat komentar panjang di sana. Kebijakan rate limit
       untuk fitur yang sudah tidak ada cuma jadi entri mati. */
    'warga_lookup' => [
        'limit' => 10,
        'window' => 60,
        'dimensions' => ['ip', 'account', 'nik'],
        'concurrent_dimension' => 'account',
    ],
    /* Butir tanggal-lahir-dicabut (14 Agt 2026, Warga::pendataan()). Pola SAMA
       PERSIS dengan rtlh_cek/rtlh_cek_harian di bawah, dan alasannya sama:
       `warga_lookup` di atas dimensinya ip+account+NIK, jadi batasnya PER-NIK -
       mencoba NIK berbeda-beda tidak pernah kena batas itu. Selama tanggal
       lahir masih wajib, itu tidak masalah (tanggal lahir sendiri sudah
       pengaman anti-penelusuran). Sesudah dicabut, dua batas AKUN ini yang
       menggantikan perannya - TANPA dimensi nik, jadi menghitung TOTAL
       pencarian satu akun, bukan per-NIK yang dicoba. */
    'warga_lookup_jam' => [
        'limit' => 10,
        'window' => 3600,
        'dimensions' => ['account'],
    ],
    'warga_lookup_harian' => [
        'limit' => 25,
        'window' => 86400,
        'dimensions' => ['account'],
    ],
    /* Pencarian NIK ANONIM (14 Agt 2026, /warga/pendataan step "Temukan
       Data" dibuka untuk pengunjung belum login). Dimensi `account` di
       atas TIDAK BISA dipakai di sini - tidak ada akun sama sekali.
       Dimensi `ip` saja, dan sengaja LEBIH KETAT dari warga_lookup_jam/
       harian (bukan lebih longgar): pengunjung anonim tidak punya jejak
       akun, jadi risiko penelusuran per-permintaannya lebih tinggi,
       bukan lebih rendah. */
    /* Bukti kepemilikan NIK (nama akun + tanggal lahir, Simperum_gateway::verifikasi_pemilik,
       3 Okt 2026). Hanya percobaan yang TIDAK COCOK dihitung (inspect lalu hit, pola login).
       Per AKUN saja. Ember per NIK DICABUT (temuan integrasi-luar-08): lima tebakan salah dari
       akun mana pun mengunci NIK itu untuk SEMUA akun, termasuk pemiliknya, dan bisa diulang
       tiap hari. Tebakan lintas akun untuk satu NIK kini hanya DIHITUNG (verifikasi_nik_lintas,
       senyap) dan memicu peringatan ke Super Admin begitu melewati batasnya; tidak menahan siapa pun. */
    'verifikasi_nik' => [
        'limit' => 5,
        'window' => 86400,
        'dimensions' => ['account'],
    ],
    'verifikasi_nik_lintas' => ['limit' => 5, 'window' => 86400, 'dimensions' => ['nik'], 'senyap' => TRUE],
    'warga_lookup_anon' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip'],
    ],
    'warga_submit' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip', 'account', 'object'],
    ],
    'warga_start_revision' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip', 'account', 'object'],
    ],
    'admin_queue_decision' => [
        'limit' => 30,
        'window' => 60,
        'dimensions' => ['ip', 'account', 'object'],
        'concurrent_dimension' => 'object',
    ],
    // B3 - laporan komentar forum. ENTRI policy, bukan mekanisme baru:
    // §17 poin 15 melarang membuat pembatas laju kedua. Dedup di ledger sudah
    // menahan laporan berulang untuk komentar YANG SAMA, jadi policy ini
    // menahan pola lain: membanjiri BANYAK komentar sekaligus. Karena itu
    // dimensinya ip+account tanpa object.
    'forum_report' => [
        'limit' => 10,
        'window' => 300,
        'dimensions' => ['ip', 'account'],
    ],
    /*
     * Janji temu konsultasi. Per HARI, bukan per jam: yang dibatasi bukan spam
     * melainkan penumpukan agenda tatap muka - setiap permintaan memakan waktu
     * petugas yang nyata.
     *
     * HANYA dimensi `account`, dan itu disengaja - dua dimensi lain sempat ikut
     * ditulis lalu dicabut sebelum sempat naik:
     *
     * - `ip` pada batas 3/hari MERUSAK. Endpoint ini wajib login, jadi `account`
     *   sudah menjadi identitas yang sebenarnya; menambahkan IP berarti satu
     *   kantor kelurahan, satu warnet, atau satu blok CGNAT seluler berbagi
     *   jatah tiga permintaan sehari untuk semua orang di baliknya. Yang
     *   tertolak adalah warga yang tidak melakukan apa-apa selain memakai
     *   koneksi yang sama.
     * - `object` (id topik) TIDAK MENAMBAH APA-APA di sini: hanya pemilik topik
     *   yang bisa mengajukan, jadi penghitung per-topik selalu jadi bagian dari
     *   penghitung per-akun yang sudah ada.
     */
    'janji_temu_ajukan' => [
        'limit' => 3,
        'window' => 86400,
        'dimensions' => ['account'],
    ],
    /*
     * Cek RTLH. Tiap pencarian menyentuh API SIMPERUM dan menulis satu snapshot,
     * jadi yang dibatasi bukan cuma penyalahgunaan melainkan juga beban ke
     * sistem sebelah.
     *
     * Dimensi `account` saja, alasan yang sama dengan `janji_temu_ajukan`:
     * endpoint-nya wajib login, jadi akun ADALAH identitasnya, dan menambahkan
     * IP pada batas sekecil ini membuat satu kantor kelurahan atau satu blok
     * CGNAT berbagi jatah untuk semua orang di baliknya.
     *
     * Batas ini bekerja BERSAMA gerbang login, bukan menggantikannya - NIK +
     * tanggal lahir tidak menahan siapa pun (tanggalnya terkandung di NIK),
     * jadi kalau login dicabut, angka ini yang jadi satu-satunya penahan dan
     * ia tidak cukup.
     */
    /* Butir 5 putaran 2: batas HARIAN, pasangan dari yang per jam di bawah.
       Sesudah tanggal lahir dilepas, dua batas ini yang menggantikan perannya
       sebagai pengaman anti-penelusuran. */
    'rtlh_cek_harian' => [
        'limit' => 25,
        'window' => 86400,
        'dimensions' => ['account'],
    ],
    'rtlh_cek' => [
        'limit' => 10,
        'window' => 3600,
        'dimensions' => ['account'],
    ],
    /* UAT No. 18, 10 Sep 2026: tamu bisa melihat hasil Cek Data Rumah.
       Batas IP tetap 5/jam; membuat sesi baru tidak mengulang kuotanya. */
    'rtlh_cek_anon' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip'],
    ],
    /* Cetak Sertifikat KKN, permintaan user 22 Agt 2026 - pencarian NIM TANPA
       login (mahasiswa peserta KKN belum tentu punya akun sendiri, akun KKN
       ada di universitas). Pola SAMA PERSIS dengan warga_lookup_anon: dimensi
       `ip` saja (tidak ada akun untuk dijadikan dimensi), dan angkanya
       disamakan - ini pencarian anti-enumerasi sungguhan (hasilnya
       mengonfirmasi/menyangkal seseorang terdaftar KKN, kapan, dan status
       kelulusannya), bukan sekadar penahan spam submit. */
    'sertifikat_kkn_lookup' => [
        'limit' => 5,
        'window' => 3600,
        'dimensions' => ['ip'],
    ],

    /* ================================================================
       KONTROL ANTI-OTOMATISASI GLOBAL (form keamanan poin 10.4), 21 Sep 2026.
       Dipasang di MY_Controller::__construct(), jadi berlaku untuk SELURUH
       endpoint PHP, bukan hanya yang memanggil limiter sendiri. Penjelasan:
       docs/engineering/ANTI_OTOMATISASI.md.

       Semua memakai Rate_limiter::hit_fast() (satu kueri atomik) dan jendela
       per MENIT: kolom penghitung TINYINT UNSIGNED, jadi batas maksimum 255 per
       jendela, dan jendela per jam tidak bisa dipakai untuk angka sebesar ini.
       Angkanya sengaja LONGGAR: tujuannya menghentikan skrip (ratusan per menit),
       bukan pengguna. Halaman biasa memuat 1 permintaan PHP; jajak notifikasi
       dan navigasi progresif admin menambah beberapa per menit. Batas per-IP
       anonim dibuat lebih tinggi dari per-akun karena satu IP kantor/kampus bisa
       dipakai banyak orang (NAT); yang perlu lebih longgar dapat memakai
       ANTI_OTOMATISASI_IP_DIIZINKAN di .env (lihat helpers/anti_automation_helper.php).
       ================================================================ */
    'global_anon'  => ['limit' => 240, 'window' => 60, 'dimensions' => ['ip']],
    'global_akun'  => ['limit' => 240, 'window' => 60, 'dimensions' => ['account']],
    // Permintaan yang MENGUBAH keadaan (POST/PUT/PATCH/DELETE): logika bisnis berlebihan.
    'tulis_anon'   => ['limit' => 40,  'window' => 60, 'dimensions' => ['ip']],
    'tulis_akun'   => ['limit' => 120, 'window' => 60, 'dimensions' => ['account']],
    // Kelas endpoint yang mahal/rawan eksfiltrasi data (config/anti_automation.php: route_classes).
    'kelas_cari_ip'    => ['limit' => 60, 'window' => 60, 'dimensions' => ['ip']],
    'kelas_cari_akun'  => ['limit' => 60, 'window' => 60, 'dimensions' => ['account']],
    'kelas_api_ip'     => ['limit' => 40, 'window' => 60, 'dimensions' => ['ip']],
    'kelas_api_akun'   => ['limit' => 40, 'window' => 60, 'dimensions' => ['account']],
    'kelas_unduh_ip'   => ['limit' => 40, 'window' => 60, 'dimensions' => ['ip']],
    'kelas_unduh_akun' => ['limit' => 40, 'window' => 60, 'dimensions' => ['account']],
    // Unggahan berlebihan: dihitung per PERMINTAAN yang membawa berkas.
    'unggah_ip'    => ['limit' => 20, 'window' => 600, 'dimensions' => ['ip']],
    'unggah_akun'  => ['limit' => 40, 'window' => 600, 'dimensions' => ['account']],
    /* Internal untuk peringatan (libraries/Security_alert.php). `senyap` = pelampauannya
       sendiri TIDAK memicu peringatan (kalau tidak, peringatan memicu peringatan). */
    'audit_akses_dedupe' => ['limit' => 1, 'window' => 600, 'dimensions' => ['key'], 'senyap' => TRUE],
    'alert_dedupe'   => ['limit' => 1, 'window' => 1800, 'dimensions' => ['key'], 'senyap' => TRUE],
    'alert_eskalasi' => ['limit' => 3, 'window' => 3600, 'dimensions' => ['ip'],  'senyap' => TRUE],
];
