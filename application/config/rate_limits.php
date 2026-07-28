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
];
