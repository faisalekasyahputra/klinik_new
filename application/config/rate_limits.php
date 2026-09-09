<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Satu registry untuk seluruh pembatas laju berbasis sys_rate_limits.
 * `dimensions` menentukan penghitung yang berdiri sendiri. Permintaan ditolak
 * bila salah satu dimensi mencapai batasnya.
 */
$config['rate_limit_policies'] = [
    'register' => [
        'limit' => 5,
        'window' => 600,
        'dimensions' => ['ip'],
    ],
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
    /* Cek_Rtlh dibuka utk anonim 14 Agt 2026 (halamannya saja, bukan
       hasilnya - lihat komentar panjang di Cek_Rtlh.php). Cabang anonim
       TIDAK memanggil Simperum_gateway sama sekali (tidak ada hasil yang
       dikembalikan), jadi batas ini murni menahan spam submit form, bukan
       anti-enumerasi sungguhan seperti rtlh_cek/rtlh_cek_harian - pola sama
       dengan warga_lookup_anon. */
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
];
