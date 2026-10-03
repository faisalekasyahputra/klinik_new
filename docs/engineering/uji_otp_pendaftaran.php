<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji OTP email pendaftaran (libraries/Otp_pendaftaran.php, keputusan pemilik produk 3 Okt 2026).
 *
 *   php docs/engineering/uji_otp_pendaftaran.php
 *
 * Akun baru lahir HANYA sesudah kode benar. Alamat uji berakhiran .test, jadi kodenya dibaca dari
 * application/cache/otp_uji/ (lihat _otp_uji.php), tidak ada email yang terkirim. Akun uji dihapus sendiri.
 */
require_once __DIR__ . '/_otp_uji.php';
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path(dirname(__DIR__, 2)), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujiotp' . bin2hex(random_bytes(3)); $sandi = 'Ot1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$http = function ($jar, $path, $post = NULL, $ajax = FALSE) use ($BASE) {
    $c = curl_init($BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($c); $url = (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL); curl_close($c); unset($c); // PHP 8: jar ditulis saat handle dilepas
    return [html_entity_decode($b, ENT_QUOTES, 'UTF-8'), $url];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$kirim = function ($j, $path, array $isi = [], $ajax = FALSE) use ($http, $csrf) { return $http($j, $path, ['csrf_kpkp_token' => $csrf($j)] + $isi, $ajax); };
$jar = function () use (&$jars, $http) { $j = tempnam(sys_get_temp_dir(), 'otp'); $jars[] = $j; $http($j, 'Auth/register'); return $j; };
$akun = function ($email) use ($db) { $st = $db->prepare('SELECT id, email_verified_at, peran FROM usr_akun WHERE email=?'); $st->bind_param('s', $email); $st->execute(); return $st->get_result()->fetch_assoc(); };
$isian = fn($email) => ['email' => $email, 'password' => $sandi, 'password_confirm' => $sandi, 'tos_agree' => '1'];
$salah = fn($kode) => str_pad((string) (((int) $kode + 1) % 1000000), 6, '0', STR_PAD_LEFT);

// Ember batas laju per IP (pendaftaran, kode salah) dipinjam lalu dikembalikan utuh (::1 tercatat per /64).
$ember = []; $kunci_ip = [];
foreach (['register', 'otp_salah_ip', 'otp_kirim_ip'] as $pol) {
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
        $k = hash('sha256', $pol . ':ip:' . $ip); $kunci_ip[$pol][] = $k;
        $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
    }
}
$k = hash('sha256', 'otp_kirim_global:key:otp_global'); // plafon global harian ikut dipinjam
$ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
$db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
$kosongkan_register = function () use ($db, $kunci_ip) { foreach ($kunci_ip['register'] as $k) { $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'"); } };
// Ember per email (Otp_pendaftaran): kunci = sha256(lowercase email), email uji dibuat sendiri dan dihapus di akhir.
$kunci_email = fn($pol, $e) => hash('sha256', $pol . ':key:' . hash('sha256', strtolower($e)));
$berkas_kode = fn($e) => dirname(__DIR__, 2) . '/application/cache/otp_uji/' . sha1(strtolower($e)) . '.txt';
try {
    echo "=== UJI OTP EMAIL PENDAFTARAN ===\n";

    // 1. Formulir biasa: daftar tidak membuat akun, kode salah ditolak, kode benar membuat akun.
    $e1 = "{$tag}_a@example.test"; $j = $jar();
    [, $url] = $kirim($j, 'Auth/do_register', $isian($e1));
    $kode = kode_otp_uji($e1);
    $cek(substr($url, -21) === 'Auth/verifikasi_email' && preg_match('/^\d{6}$/', $kode) === 1, 'Daftar: dibawa ke halaman kode, kode 6 angka dibuat');
    $cek($akun($e1) === NULL, 'Daftar: akun BELUM dibuat sebelum kode benar');
    [$b] = $http($j, 'akun');
    $cek(stripos($b, 'Keluar dari akun') === FALSE && $akun($e1) === NULL, 'Sebelum kode benar tidak ada sesi masuk');
    [$b, $url] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $salah($kode)]);
    $cek(substr($url, -21) === 'Auth/verifikasi_email' && stripos($b, 'Kode verifikasi salah') !== FALSE && $akun($e1) === NULL, 'Kode salah: ditolak dengan pesan, akun tidak dibuat');
    [$b] = $http($j, 'Auth/verifikasi_email');
    $cek(preg_match('/id="kirimUlang"[^>]*data-tunggu="(\d+)"[^>]*disabled/', $b, $m) === 1 && (int) $m[1] > 50 && (int) $m[1] <= 60,
        'Halaman kode: tombol kirim ulang nonaktif dengan hitung mundur sekitar 60 detik');
    [$b] = $kirim($j, 'Auth/kirim_ulang_otp');
    $cek(kode_otp_uji($e1) === $kode && stripos($b, 'Tunggu') !== FALSE, 'Kirim ulang di bawah satu menit: ditolak server dengan pesan tunggu, tanpa kode baru');
    [$b] = $kirim($j, 'Auth/kirim_ulang_otp', [], TRUE);
    $d = json_decode($b, TRUE);
    $cek(($d['status'] ?? '') === 'error' && (int) ($d['tunggu'] ?? 0) > 50, 'Kirim ulang lewat XHR saat jeda: error beserta sisa detik untuk hitung mundur');
    [, $url] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $kode]);
    $a = $akun($e1);
    $cek($a !== NULL && $a['email_verified_at'] !== NULL, 'Kode benar: akun dibuat dengan email terverifikasi');
    $cek(substr($url, -15) === 'Auth/onboarding', 'Kode benar: langsung masuk dan dibawa ke onboarding');
    [, $url] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $kode]);
    $n = (int) $db->query("SELECT COUNT(*) FROM usr_akun WHERE email='" . $db->real_escape_string($e1) . "'")->fetch_row()[0];
    $cek($n === 1, 'Kode yang sama dikirim lagi: tidak membuat akun kedua');

    // 2. Lima kode salah mengunci kode itu, termasuk untuk kode yang benar.
    $e2 = "{$tag}_b@example.test"; $j = $jar();
    $kirim($j, 'Auth/do_register', $isian($e2));
    $kode = kode_otp_uji($e2);
    for ($i = 0; $i < 5; $i++) { [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $salah($kode)]); }
    $cek(stripos($b, 'Terlalu banyak kode salah') !== FALSE, 'Kode salah kelima: diminta kode baru');
    $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $kode]);
    $cek($akun($e2) === NULL, 'Sesudah lima kali salah kode yang benar pun tidak berlaku');

    // 3. Kode satu sesi tidak berlaku di sesi lain.
    $e3 = "{$tag}_c@example.test"; $j = $jar();
    $kirim($j, 'Auth/do_register', $isian($e3));
    $kirim($jar(), 'Auth/do_verifikasi_email', ['kode_otp' => kode_otp_uji($e3)]);
    $cek($akun($e3) === NULL, 'Kode dikirim dari sesi lain: akun tidak dibuat');

    // 3b. Email yang sudah terdaftar: judul dan tombol sama umumnya dengan galat pendaftaran lain,
    // supaya dialognya tidak menyatakan keanggotaan; hanya petunjuk netral di pesannya.
    [$b] = $kirim($jar(), 'Auth/do_register', $isian($e1));
    $cek(preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $b, $m) === 1
        && strpos($m[1], '"aksi"') === FALSE && strpos($m[1], 'Email sudah terdaftar') === FALSE
        && strpos($m[1], '"title":"Pendaftaran gagal"') !== FALSE && strpos($m[1], 'sudah punya akun, silakan masuk') !== FALSE,
        'Email sudah terdaftar: judul umum "Pendaftaran gagal", tanpa tombol khusus, hanya petunjuk netral');

    // 4. Tanpa pendaftaran tertunda.
    [, $url] = $http($jar(), 'Auth/verifikasi_email');
    $cek(substr($url, -13) === 'Auth/register', 'Halaman kode tanpa pendaftaran tertunda: kembali ke formulir daftar');

    // 5. Wizard SRP2 (XHR): otp_required, lalu kode benar membuat akun pengembang beserta draftnya.
    $e5 = "{$tag}_dev@example.test"; $j = $jar();
    [$b] = $kirim($j, 'Auth/do_register', $isian($e5) + ['srp2_pengembang' => '1', 'nama_perusahaan' => 'PT ' . strtoupper($tag)], TRUE);
    $cek((json_decode($b, TRUE)['status'] ?? '') === 'otp_required' && $akun($e5) === NULL, 'SRP2: daftar cepat menjawab otp_required, akun belum dibuat');
    [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $salah(kode_otp_uji($e5))], TRUE);
    $cek((json_decode($b, TRUE)['status'] ?? '') === 'error', 'SRP2: kode salah dijawab JSON error');
    [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => kode_otp_uji($e5)], TRUE);
    $d = json_decode($b, TRUE); $a = $akun($e5);
    $cek(($d['status'] ?? '') === 'success' && (int) ($d['pengajuan_id'] ?? 0) > 0 && $a && $a['peran'] === 'pengembang' && $a['email_verified_at'] !== NULL,
        'SRP2: kode benar membuat akun pengembang terverifikasi dan draft pengajuan');

    // 7. Kiriman kode dibatasi per email (5 per jam), bukan per sesi: cookie baru dan berganti email tidak mengulangnya.
    $e7 = "{$tag}_kirim@example.test"; $selingan = "{$tag}_selingan@example.test"; $terkirim = 0;
    for ($i = 0; $i < 5; $i++) {
        $kosongkan_register(); @unlink($berkas_kode($e7));
        $kirim($jar(), 'Auth/do_register', $isian($e7));
        $terkirim += kode_otp_uji($e7) !== '' ? 1 : 0;
    }
    $cek($terkirim === 5, 'Lima sesi baru untuk satu email: lima kode terkirim');
    $kosongkan_register(); @unlink($berkas_kode($e7)); $j = $jar();
    [$b] = $kirim($j, 'Auth/do_register', $isian($e7));
    $cek(kode_otp_uji($e7) === '' && stripos($b, 'Terlalu banyak permintaan kode') !== FALSE,
        'Sesi baru keenam untuk email yang sama: tidak ada kode baru, pesan umum');
    $kirim($j, 'Auth/do_register', $isian($selingan));
    $kirim($j, 'Auth/do_register', $isian($e7));
    $cek(kode_otp_uji($selingan) !== '' && kode_otp_uji($e7) === '', 'Berganti ke email lain lalu kembali: batas email pertama tetap berlaku');

    // 8. Kode salah dibatasi per email (10 per jam): sesi baru dan berganti email tidak memberi jatah tebakan baru.
    $e8 = "{$tag}_tebak@example.test";
    foreach (['A', 'B'] as $s) {
        $kosongkan_register(); $j = $jar();
        $kirim($j, 'Auth/do_register', $isian($e8)); $kode = kode_otp_uji($e8);
        for ($i = 0; $i < 5; $i++) { $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $salah($kode)]); }
    }
    $kosongkan_register(); $j = $jar();
    $kirim($j, 'Auth/do_register', $isian($e8));
    $kirim($j, 'Auth/do_register', $isian($selingan));
    $kirim($j, 'Auth/do_register', $isian($e8));
    [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => kode_otp_uji($e8)]);
    $cek($akun($e8) === NULL && stripos($b, 'Terlalu banyak percobaan kode') !== FALSE,
        'Sesudah 10 kode salah di dua sesi, sesi ketiga yang berganti email lalu kembali: kode benar pun ditolak');

    // 9. Kode salah per IP (30 per jam): penebak yang berganti-ganti email tetap tertahan. Ember diisi sampai satu sebelum batas.
    foreach ($kunci_ip['otp_salah_ip'] as $k) { $db->query("REPLACE INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES ('$k', NOW(), 29)"); }
    $e9 = "{$tag}_ip@example.test"; $kosongkan_register(); $j = $jar();
    $kirim($j, 'Auth/do_register', $isian($e9)); $kode = kode_otp_uji($e9);
    [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $salah($kode)]);
    $cek(stripos($b, 'Kode verifikasi salah') !== FALSE, 'IP pada 29 kode salah: tebakan ke-30 masih dijawab "salah"');
    [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => $kode]);
    $cek($akun($e9) === NULL && stripos($b, 'Terlalu banyak percobaan kode') !== FALSE, 'IP yang mencapai batas: kode benar untuk email baru pun ditolak');

    // 6. Jeda kirim ulang berlipat dua (unit, sesi tiruan): tidak perlu menunggu bermenit-menit.
    if ( ! defined('BASEPATH')) { define('BASEPATH', dirname(__DIR__, 2) . '/system/'); }
    if ( ! function_exists('get_instance')) {
        function &get_instance() {
            static $ci;
            $ci ??= (object) ['session' => new class { public $d = []; function userdata($k) { return $this->d[$k] ?? NULL; } function set_userdata($k, $v) { $this->d[$k] = $v; } function unset_userdata($k) { unset($this->d[$k]); } }];
            return $ci;
        }
    }
    require_once dirname(__DIR__, 2) . '/application/libraries/Otp_pendaftaran.php';
    $otp = new Otp_pendaftaran();
    $sesi = function ($jumlah, $lalu) { get_instance()->session->d['daftar_tertunda'] = ['email' => 'x@example.test', 'kirim_jumlah' => $jumlah, 'kirim_terakhir' => time() - $lalu]; };
    $jeda = [];
    foreach ([1, 2, 3, 4] as $n) { $sesi($n, 0); $jeda[] = $otp->sisa_jeda(); }
    $cek($jeda === [60, 120, 240, 480], 'Jeda kirim ulang berlipat dua: ' . implode(', ', $jeda) . ' detik');
    $sesi(2, 121);
    $cek($otp->sisa_jeda() === 0, 'Sesudah jeda habis tombol boleh dipakai lagi');
    $sesi(0, 0);
    $cek($otp->sisa_jeda() === 0, 'Sebelum kode pertama terkirim tidak ada jeda');
    $sesi(5, 9999);
    $cek($otp->batas_tercapai() && $otp->kirim() === 'batas', 'Kiriman kelima adalah yang terakhir: permintaan berikutnya ditolak');
} finally {
    $db->query("DELETE FROM sys_jejak_audit WHERE aksi='persetujuan_sk' AND objek_id IN (SELECT id FROM usr_akun WHERE email LIKE '{$tag}\_%@example.test')");
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}\_%@example.test'");
    foreach ($jars as $f) { @unlink($f); }
    foreach (['a', 'b', 'c', 'dev', 'kirim', 'selingan', 'tebak', 'ip'] as $s) {
        $e = "{$tag}_{$s}@example.test"; @unlink($berkas_kode($e));
        foreach (['otp_kirim', 'otp_salah'] as $pol) { $db->query("DELETE FROM sys_batas_laju WHERE kunci='" . $kunci_email($pol, $e) . "'"); }
    }
    foreach (glob(dirname(__DIR__, 2) . '/application/cache/otp_uji/*.txt') ?: [] as $f) { if (time() - filemtime($f) > 900) @unlink($f); }
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
