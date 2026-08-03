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
    'ticket_lookup' => [
        'limit' => 5,
        'window' => 60,
        'dimensions' => ['ip'],
    ],
    'warga_lookup' => [
        'limit' => 10,
        'window' => 60,
        'dimensions' => ['ip', 'account', 'nik'],
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
    // B3 — laporan komentar forum. ENTRI policy, bukan mekanisme baru:
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
     * melainkan penumpukan agenda tatap muka — setiap permintaan memakan waktu
     * petugas yang nyata.
     *
     * HANYA dimensi `account`, dan itu disengaja — dua dimensi lain sempat ikut
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
];
