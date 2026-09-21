<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan metode HTTP dan kebersihan URI (form keamanan poin 12.2 dan 12.4).
 * Sumber tunggal untuk libraries/Http_policy.php, MY_Controller::enforce_http_policy() dan
 * tests/http_uri_test.php. Penjelasan dan prosedur: docs/engineering/URI_DAN_METODE_HTTP.md.
 */
$config['http_policy'] = [

    /* 12.4: satu-satunya metode yang dilayani aplikasi. PUT, DELETE, PATCH, TRACE, CONNECT dst.
       tidak dipakai fitur mana pun dan ditolak 405. OPTIONS dijawab 204 dengan Allow milik rute. */
    'allowed_methods' => ['GET', 'HEAD', 'POST'],

    /* Penerowongan metode (mengubah POST menjadi PUT/DELETE lewat header atau parameter) dapat
       melewati pemeriksaan metode dan CSRF; ditolak 400. Nama header dalam bentuk kunci $_SERVER. */
    'method_override_headers' => ['HTTP_X_HTTP_METHOD_OVERRIDE', 'HTTP_X_HTTP_METHOD', 'HTTP_X_METHOD_OVERRIDE'],
    'method_override_params'  => ['_method'],

    /* 12.2: nama parameter yang TIDAK BOLEH ada di query string mana pun. URI masuk ke log server,
       riwayat peramban, header Referer, dan proxy; data pribadi dan rahasia hanya boleh di badan POST. */
    'sensitive_query' => [
        'nik', 'nik_ktp', 'nik_identitas', 'family_card_number', 'no_kk', 'nomor_kk',
        'password', 'password_confirm', 'current_password', 'new_password', 'passwd',
        'token', 'bot_token', 'csrf_kpkp_token', 'access_token', 'id_token', 'refresh_token',
        'api_key', 'apikey', 'key', 'secret', 'client_secret',
        'tgl_lahir', 'tanggal_lahir', 'birth_date',
        'email', 'nim', 'phone', 'no_hp', 'hp', 'no_whatsapp',
    ],

    /* Pola yang tidak boleh muncul di segmen jalur URI: deretan 16 digit (NIK/KK) dan alamat surel. */
    'sensitive_path' => ['/(?<!\d)\d{16}(?!\d)/', '/@/'],

    /* 12.4: endpoint yang MENGUBAH keadaan. Hanya POST; GET/HEAD dijawab 405 dengan Allow: POST.
       Metode aman (GET/HEAD) tidak boleh punya efek samping, dan CSRF hanya melindungi POST. */
    'post_only' => [
        'admin_content/update'                 => 'ubah konten beranda',
        'admin_users/ubah_status'              => 'aktif/nonaktifkan akun',
        'admin_users/buka_kunci'               => 'buka kunci akun',
        'admin_users/reset_nik'                => 'reset NIK warga',
        'admin_users/reset_sandi'              => 'reset kata sandi',
        'auth/do_login'                        => 'autentikasi',
        'auth/do_register'                     => 'buat akun',
        'auth/do_verify_email'                 => 'menandai surel terverifikasi (dulu bisa dipicu lewat GET)',
        'auth/save_onboarding'                 => 'simpan profil onboarding',
        'pengaturan/update_profile'            => 'ubah profil',
        'pengaturan/update_pengembang_profile' => 'ubah profil pengembang',
        'pengaturan/export_account_data'       => 'ekspor data akun (butuh kata sandi)',
        'program/api_cek_simperum'             => 'lookup SIMPERUM',
        'program/api_kalkulasi_program'        => 'kalkulasi dan simpan hasil ke sesi',
        'program/cek_tiket'                    => 'endpoint dicabut (410)',
        'program/submit_antrean'               => 'ajukan permohonan',
        'program/ajukan_solusi'                => 'ajukan solusi pembiayaan',
        'push/subscribe'                       => 'daftarkan langganan push',
        'push/unsubscribe'                     => 'nonaktifkan langganan push',
        'umum/toggle_like'                     => 'suka/batal suka',
        'umum/report_komentar'                 => 'laporkan komentar',
        'umum/update_status_diskusi'           => 'ubah status diskusi',
        'admin/update_status'                  => 'keputusan antrean',
        'admin_kabkota/update_status'          => 'keputusan antrean kabupaten/kota',
        'pengembang/simpan_dokumen'            => 'unggah dokumen SRP2',
        'pengembang/kirim_pengajuan'           => 'kirim pengajuan SRP2',
    ],

    /* Metode publik yang menyentuh data/berkas/sesi tetapi SENGAJA melayani GET, dengan alasan yang
       sudah ditinjau. Penjaga di tests/http_uri_test.php menggagalkan metode menulis lain yang
       tidak ada di `post_only` maupun di sini. */
    'get_write_exempt' => [
        'auth/google'                => 'pengalihan OAuth ke Google (menyimpan state di sesi); tidak mengubah data akun',
        'auth/google_callback'       => 'callback OAuth adalah GET menurut spesifikasi; dilindungi parameter state sekali pakai',
        'auth/logout'                => 'mengakhiri sesi lewat tautan; dampak CSRF rendah (hanya keluar), cookie sesi SameSite=Lax',
        'admin_rekam_data/export'    => 'unduhan berkas hasil ekspor (hanya baca)',
        'rekam_kawasan/export'       => 'unduhan berkas hasil ekspor (hanya baca)',
        'rekam_perumahan/export'     => 'unduhan berkas hasil ekspor (hanya baca)',
        'index/buka_foto'            => 'penyaji foto (hanya baca)',
        'jebakan/index'              => 'jalur jebakan pemindai; hanya mencatat peringatan',
        'migrate/uji_warga_r1'       => 'hanya CLI atau loopback (menolak selain itu dengan 404)',
        'migrate/uji_warga_r2'       => 'hanya CLI atau loopback',
        'migrate/simperum_probe'     => 'hanya CLI atau loopback',
        'migrate/uji_rekam_data_d1'  => 'hanya CLI atau loopback',
        'migrate/uji_wizard_w2'      => 'hanya CLI atau loopback',
    ],

    /* URI KELUAR (aplikasi memanggil layanan lain) yang membawa data pribadi di query string karena
       kontrak layanan itu menuntutnya. Bukan URI milik aplikasi ini; dicatat sebagai risiko yang diterima. */
    'outbound_sensitive_uri_exempt' => [
        'libraries/Simperum_gateway.php' => 'API SIMPERUM Disperakim menuntut NIK di query (GetDataRTLH?NIK=); dikirim lewat HTTPS, tidak dicatat aplikasi ini',
    ],
];
