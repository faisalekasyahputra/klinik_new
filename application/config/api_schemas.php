<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Skema endpoint API dan layanan web (form keamanan poin 12.5 dan 12.7).
 * Sumber tunggal untuk libraries/Api_schema.php, MY_Controller::enforce_api_schema(),
 * helpers/anti_automation_helper.php (kelas laju per endpoint) dan tests/api_schema_test.php.
 * Penjelasan dan prosedur: docs/engineering/KEAMANAN_API.md.
 *
 * Kunci   : "controller/metode" huruf kecil.
 * class   : kelas batas laju TAMBAHAN (config/rate_limits.php: kelas_<class>_ip / _akun) di atas
 *           batas global; `api` untuk endpoint bergaya layanan web.
 * ajax    : wajib XHR (X-Requested-With), sama seperti yang sudah dituntut handlernya.
 * json    : respons galat berbentuk JSON (untuk endpoint yang dipanggil skrip).
 * methods : metode yang diizinkan => {source: form|query|json, unknown: reject|ignore, fields, files}.
 * segments: aturan untuk segmen URI sesudah nama metode.
 * invalid : (opsional) untuk formulir peramban: {redirect, flash} menggantikan halaman galat 422.
 *
 * Tiap metode publik yang menghasilkan JSON WAJIB terdaftar di sini atau di `api_schema_exempt`
 * beserta alasannya; tests/api_schema_test.php menggagalkan bila ada yang terlewat.
 */

/* Field yang selalu boleh ada (token CSRF). Nama token bisa diubah di config.php, dan
   MY_Controller menambahkan nama aslinya saat berjalan. */
$config['api_schema_common'] = ['csrf_kpkp_token'];

$id = ['type' => 'int', 'min' => 1, 'max' => 999999999];
$sandi = ['type' => 'string', 'min_len' => 1, 'max_len' => 255];
$bot = [
    'bot_token' => ['type' => 'string', 'max_len' => 400],
    'situs_web' => ['type' => 'string', 'max_len' => 200],
];

$config['api_schemas'] = [

    // ------------------------------------------------------------- Program (kalkulator dan SIMPERUM)
    'program/api_cek_simperum' => [
        'class' => 'api', 'ajax' => TRUE, 'json' => TRUE,
        'methods' => ['POST' => ['fields' => [
            'nik'       => ['type' => 'string', 'required' => TRUE, 'pattern' => '/^\d{16}$/D'],
            'tgl_lahir' => ['type' => 'date', 'required' => TRUE],
        ]]],
    ],
    'program/api_kalkulasi_program' => [
        'class' => 'api', 'ajax' => TRUE, 'json' => TRUE,
        'methods' => ['POST' => ['fields' => [
            'penghasilan'        => ['type' => 'int', 'required' => TRUE, 'min' => 0, 'max' => 100000000],
            'pekerjaan'          => ['type' => 'enum', 'required' => TRUE, 'values' => ['PNS/TNI/POLRI', 'Karyawan Swasta', 'Wiraswasta', 'Pekerja Informal', 'Lainnya']],
            'status_kepemilikan' => ['type' => 'enum', 'required' => TRUE, 'values' => ['Sewa/Kontrak', 'Numpang/Keluarga', 'Punya Lahan Belum Bangun', 'Punya Rumah Tidak Layak', 'Punya Rumah Layak']],
            'alasan_pengajuan'   => ['type' => 'string', 'required' => TRUE, 'min_len' => 1, 'max_len' => 5000],
            'kabupaten_id'       => ['type' => 'int', 'min' => 1, 'max' => 999999],
            'kode_program_target' => ['type' => 'string', 'max_len' => 60, 'pattern' => '/^[A-Za-z0-9_-]+$/D'],
            'simpan_hasil'       => ['type' => 'enum', 'values' => ['0', '1']],
        ]]],
    ],
    'program/cek_tiket' => [   // dicabut (410): menjawab "sudah pindah" apa pun isi kirimannya
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['unknown' => 'ignore', 'fields' => []]],
    ],

    // ------------------------------------------------------------- Web Push (admin)
    'push/config' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['GET' => ['unknown' => 'reject', 'fields' => ['_' => ['type' => 'int', 'min' => 0]]]],
    ],
    'push/subscribe' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['fields' => [
            'subscription' => ['type' => 'object', 'json' => TRUE, 'required' => TRUE, 'max_bytes' => 8192, 'unknown' => 'ignore', 'fields' => [
                'endpoint'       => ['type' => 'url', 'required' => TRUE, 'https_only' => TRUE, 'max_len' => 4096],
                'expirationTime' => ['type' => 'int', 'min' => 0],
                'keys'           => ['type' => 'object', 'required' => TRUE, 'unknown' => 'ignore', 'fields' => [
                    'p256dh' => ['type' => 'string', 'required' => TRUE, 'max_len' => 255, 'pattern' => '/^[A-Za-z0-9_\-+\/=]+$/D'],
                    'auth'   => ['type' => 'string', 'required' => TRUE, 'max_len' => 255, 'pattern' => '/^[A-Za-z0-9_\-+\/=]+$/D'],
                ]],
            ]],
        ]]],
    ],
    'push/unsubscribe' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['fields' => [
            'endpoint' => ['type' => 'url', 'required' => TRUE, 'https_only' => TRUE, 'max_len' => 4096],
        ]]],
    ],

    // ------------------------------------------------------------- Forum (skrip halaman)
    'umum/toggle_like' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['fields' => [
            'type' => ['type' => 'enum', 'required' => TRUE, 'values' => ['diskusi', 'komentar']],
            'id'   => $id + ['required' => TRUE],
        ]]],
    ],
    'umum/report_komentar' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['fields' => ['id' => $id + ['required' => TRUE]]]],
    ],

    // ------------------------------------------------------------- Autentikasi (ajax bila dari modal)
    'auth/do_login' => [
        'class' => 'api',
        'methods' => ['POST' => ['fields' => $bot + [
            'email'       => ['type' => 'string', 'required' => TRUE, 'min_len' => 1, 'max_len' => 150],
            'password'    => $sandi + ['required' => TRUE],
            'redirect_to' => ['type' => 'string', 'max_len' => 500],
        ]]],
    ],
    'auth/do_register' => [
        'class' => 'api',
        'methods' => ['POST' => ['fields' => $bot + [
            'email'            => ['type' => 'string', 'required' => TRUE, 'min_len' => 3, 'max_len' => 150],
            'password'         => $sandi + ['required' => TRUE],
            'password_confirm' => $sandi,
            'tos_agree'        => ['type' => 'bool'],
            'srp2_pengembang'  => ['type' => 'bool'],
            'nama_perusahaan'  => ['type' => 'string', 'max_len' => 200],
        ]]],
    ],
    'auth/do_verify_email' => [
        'class' => 'api', 'json' => TRUE,
        'methods' => ['POST' => ['fields' => []]],
    ],

    // ------------------------------------------------------------- Dokumen pengembang (multipart, XHR)
    'pengembang/simpan_dokumen' => [
        'class' => 'api', 'json' => FALSE,   // dipakai XHR (JSON) DAN formulir peramban (pengalihan + flash): galat JSON hanya untuk XHR
        'segments' => [$id + ['required' => TRUE]],
        'methods' => ['POST' => [
            'fields' => ['return_to' => ['type' => 'enum', 'values' => ['dashboard']]],
            'files'  => ['pattern' => '/^form_\d{1,2}[ab]?$/D', 'max' => 14],
        ]],
    ],
    'pengembang/kirim_pengajuan' => [
        'class' => 'api', 'json' => FALSE,   // dipakai XHR (JSON) DAN formulir peramban (pengalihan + flash): galat JSON hanya untuk XHR
        'segments' => [$id + ['required' => TRUE]],
        'methods' => ['POST' => ['fields' => ['return_to' => ['type' => 'enum', 'values' => ['dashboard']]]]],
    ],

    // ------------------------------------------------------------- Pencarian publik
    'kemitraanportal/cek_sertifikat_kkn' => [
        'class' => 'api',
        'invalid' => ['redirect' => 'KemitraanPortal/sertifikat_kkn', 'flash' => 'NIM tidak valid. Periksa kembali dan coba lagi.'],
        'methods' => [
            'POST' => ['fields' => ['nim' => ['type' => 'string', 'required' => TRUE, 'min_len' => 1, 'max_len' => 30, 'pattern' => '/^[A-Za-z0-9]+$/D']]],
            'GET'  => ['unknown' => 'ignore', 'fields' => []],   // handler mengalihkan GET kembali ke formulir
        ],
    ],
    'index/cari_wil' => [
        'class' => 'cari', 'json' => TRUE,
        'methods' => ['GET' => ['unknown' => 'reject', 'fields' => [
            'kodeWilayah'  => ['type' => 'string', 'max_len' => 10, 'pattern' => '/^\d{2,10}$/D'],
            'keyword'      => ['type' => 'string', 'max_len' => 100],
            'searchBy'     => ['type' => 'string', 'max_len' => 30, 'pattern' => '/^[A-Za-z_-]+$/D'],
            'sort'         => ['type' => 'string', 'max_len' => 30, 'pattern' => '/^[A-Za-z_-]+$/D'],
            'status_rumah' => ['type' => 'string', 'max_len' => 20, 'pattern' => '/^[A-Za-z_-]+$/D'],
            'page'         => ['type' => 'int', 'min' => 0, 'max' => 100000],
            'limit'        => ['type' => 'int', 'min' => 0, 'max' => 500],
            '_'            => ['type' => 'int', 'min' => 0],
        ]]],
    ],

    // ------------------------------------------------------------- Keputusan antrean admin (formulir, XHR opsional)
    'admin/update_status' => [
        'class' => 'api',
        'methods' => ['POST' => ['fields' => [
            'queue_id'      => $id + ['required' => TRUE],
            'from_status'   => ['type' => 'string', 'max_len' => 50],
            'status'        => ['type' => 'string', 'required' => TRUE, 'max_len' => 50],
            'catatan_admin' => ['type' => 'string', 'max_len' => 5000],
        ]]],
    ],
    'admin_kabkota/update_status' => [
        'class' => 'api',
        'methods' => ['POST' => ['fields' => [
            'queue_id'      => $id + ['required' => TRUE],
            'from_status'   => ['type' => 'string', 'max_len' => 50],
            'status'        => ['type' => 'string', 'required' => TRUE, 'max_len' => 50],
            'catatan_admin' => ['type' => 'string', 'max_len' => 5000],
        ]]],
    ],
];

/* Metode publik yang menyebut JSON tetapi BUKAN endpoint API bergaya layanan web. Alasan wajib. */
$config['api_schema_exempt'] = [
    'auth/google'                     => 'pengalihan OAuth; json_encode hanya untuk string JS di halaman penutup popup',
    'index/panduan_desain'            => 'halaman HTML; hanya membaca is_ajax_request untuk memilih tata letak',
    'pengaturan/export_account_data'  => 'unduhan berkas JSON hasil ekspor data akun; masukannya satu kata sandi, dilindungi batas laju khusus dan audit',
    'migrate/index'                   => 'hanya CLI atau loopback (menolak selain itu dengan 404)',
    'migrate/uji_warga_r2'            => 'hanya CLI atau loopback',
    'migrate/simperum_probe'          => 'hanya CLI atau loopback',
];
