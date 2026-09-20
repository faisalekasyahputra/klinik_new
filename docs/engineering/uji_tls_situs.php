<?php
/**
 * ALAT UJI KEAMANAN KOMUNIKASI SITUS (form keamanan poin 8.2 dan 8.3).
 *
 * Menguji situs YANG BERJALAN dari luar, bukan kode. Jalankan berkala
 * (prosedur dan jadwal: docs/engineering/KEAMANAN_KOMUNIKASI.md):
 *
 *   php docs/engineering/uji_tls_situs.php https://<situs-production> [--cafile=/jalur/ca.pem]
 *
 * Yang diperiksa (kebijakan: application/config/transport_security.php):
 *   1. protokol TLS: TLS 1.0/1.1 (dan SSLv3) HARUS ditolak server, TLS 1.2/1.3 diterima;
 *   2. cipher lemah TLS 1.2 (RC4, 3DES, CBC-SHA, NULL, dst) HARUS ditolak;
 *   3. sertifikat: rantai dan nama host sah, sisa masa berlaku, jenis dan panjang kunci,
 *      algoritma tanda tangan;
 *   4. HTTP (port 80) dialihkan ke HTTPS;
 *   5. respons HTTPS: HSTS, cookie Secure+HttpOnly, upgrade-insecure-requests.
 *
 * Status: LULUS | GAGAL | PERINGATAN | LEWAT. LEWAT = uji TIDAK DAPAT dilakukan dari mesin
 * ini (mis. OpenSSL lokal tidak punya TLS 1.0 atau cipher itu), bukan tanda situs aman.
 * Sebuah uji hanya LULUS bila server memberi penolakan yang jelas. Kode keluar 1 bila ada GAGAL.
 * Tidak mengubah apa pun di situs; hanya membuka koneksi dan satu GET ke beranda.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../../system/');
require __DIR__ . '/../../application/helpers/transport_helper.php';
$policy = transport_policy();

// ------------------------------------------------------------- argumen
$url = null; $cafile = null; $timeout = 15;
foreach (array_slice($argv, 1) as $a) {
    if (strpos($a, '--cafile=') === 0)       { $cafile = substr($a, 9); }
    elseif (strpos($a, '--timeout=') === 0)  { $timeout = max(3, (int) substr($a, 10)); }
    elseif ($url === null)                   { $url = $a; }
}
if ($url === null || !transport_is_https_url($url)) {
    fwrite(STDERR, "Pemakaian: php docs/engineering/uji_tls_situs.php https://<situs> [--cafile=...] [--timeout=15]\n");
    exit(2);
}
$host = parse_url($url, PHP_URL_HOST);
$port = parse_url($url, PHP_URL_PORT) ?: 443;

// CA: argumen > php.ini > lokasi umum (XAMPP Windows tidak selalu mengatur CA).
if ($cafile === null) {
    foreach ([ini_get('openssl.cafile'), ini_get('curl.cainfo'),
              'C:/xampp/apache/bin/curl-ca-bundle.crt', 'C:/xampp/php/extras/ssl/cacert.pem',
              '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt'] as $c) {
        if ($c && is_file($c)) { $cafile = $c; break; }
    }
}

// ------------------------------------------------------------- pencatatan hasil
$hasil = ['LULUS' => 0, 'GAGAL' => 0, 'PERINGATAN' => 0, 'LEWAT' => 0];
function catat($status, $judul, $rincian = '') {
    global $hasil;
    $hasil[$status]++;
    printf("[%-10s] %s%s\n", $status, $judul, $rincian !== '' ? "\n             $rincian" : '');
}

/** Buka koneksi TLS; kembalikan [diterima(bool), teks galat, meta crypto, sertifikat]. */
function tls_coba($host, $port, array $ssl, $timeout) {
    global $cafile;
    $ssl += ['SNI_enabled' => true, 'peer_name' => $host, 'capture_peer_cert' => true];
    if ($cafile && !isset($ssl['cafile'])) { $ssl['cafile'] = $cafile; }
    $ctx = stream_context_create(['ssl' => $ssl]);
    $peringatan = '';
    set_error_handler(function ($no, $msg) use (&$peringatan) { $peringatan .= $msg . "\n"; return true; });
    $fp = stream_socket_client("ssl://$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    restore_error_handler();
    if ($fp === false) { return [false, strtolower($errstr . ' ' . $peringatan), null, null]; }
    $meta = stream_get_meta_data($fp)['crypto'] ?? [];
    $opts = stream_context_get_options($ctx);
    $cert = $opts['ssl']['peer_certificate'] ?? null;
    fclose($fp);
    return [true, '', $meta, $cert];
}

/** 'ditolak' hanya bila server memberi penolakan yang jelas; selain itu 'tak-pasti'. */
function klasifikasi_gagal($teks) {
    foreach (['alert protocol version', 'alert handshake failure', 'handshake failure',
              'alert insufficient security', 'alert illegal parameter'] as $p) {
        if (strpos($teks, $p) !== false) { return 'ditolak'; }
    }
    return 'tak-pasti';
}

// Probe protokol/cipher: sertifikat TIDAK diverifikasi (yang diuji penerimaan server, bukan sertifikat).
$longgar = ['verify_peer' => false, 'verify_peer_name' => false, 'security_level' => 0];

echo "UJI KEAMANAN KOMUNIKASI - situs: $host:$port\n";
echo 'Waktu    : ' . date('Y-m-d H:i:s T') . "\n";
echo 'Penguji  : PHP ' . PHP_VERSION . ' / ' . OPENSSL_VERSION_TEXT . "\n";
echo 'CA       : ' . ($cafile ?: '(bawaan sistem)') . "\n\n";

// ------------------------------------------------------------- 1. protokol
echo "== 1. Versi protokol TLS ==\n";
$metode = [
    'SSLv3'  => 'STREAM_CRYPTO_METHOD_SSLv3_CLIENT',
    'TLSv1.0' => 'STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT',
    'TLSv1.1' => 'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT',
    'TLSv1.2' => 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT',
    'TLSv1.3' => 'STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT',
];
$diterima = [];
foreach ($metode as $nama => $konstanta) {
    $terlarang = in_array($nama, $policy['transport_forbidden_tls'], true);
    if (!defined($konstanta)) { catat('LEWAT', "$nama: PHP ini tidak punya metode uji untuk protokol ini"); continue; }
    $ssl = ['crypto_method' => constant($konstanta)] + $longgar;
    if ($terlarang) { $ssl['ciphers'] = 'ALL:@SECLEVEL=0'; }
    [$ok, $teks, $meta] = tls_coba($host, $port, $ssl, $timeout);
    if ($ok) {
        // "Diterima" hanya sah bila protokol yang BENAR-BENAR dinegosiasikan sama dengan yang diuji.
        // PHP/OpenSSL tertentu tidak bisa memaksa protokol lama (mis. SSLv3) dan diam-diam bernegosiasi
        // otomatis; tanpa pemeriksaan ini alat melaporkan "SSLv3 diterima" padahal yang dipakai TLS 1.3.
        $dinegosiasi = $meta['protocol'] ?? '';
        if ($dinegosiasi !== $nama) {
            catat('LEWAT', "$nama: klien ini tidak dapat memaksa protokol itu (yang dinegosiasikan: " . ($dinegosiasi ?: 'tidak diketahui') . ')');
            continue;
        }
        $diterima[] = $nama;
        $terlarang ? catat('GAGAL', "$nama DITERIMA server (harus ditolak)", 'cipher: ' . ($meta['cipher_name'] ?? '?'))
                   : catat('LULUS', "$nama diterima", 'cipher: ' . ($meta['cipher_name'] ?? '?') . ' (' . ($meta['cipher_bits'] ?? '?') . ' bit)');
        continue;
    }
    $kls = klasifikasi_gagal($teks);
    if ($terlarang) {
        $kls === 'ditolak' ? catat('LULUS', "$nama ditolak server (penolakan eksplisit)")
                           : catat('LEWAT', "$nama: gagal, tetapi bukan penolakan server yang jelas (mungkin klien ini tidak mampu memulai protokol itu)", trim(substr($teks, 0, 140)));
    } else {
        catat('GAGAL', "$nama tidak diterima server (harus diterima)", trim(substr($teks, 0, 140)));
    }
}
if (!array_intersect($diterima, $policy['transport_allowed_tls'])) {
    catat('GAGAL', 'Tidak ada protokol TLS yang diizinkan (1.2/1.3) yang diterima - hasil di bawah tidak berarti');
}

// ------------------------------------------------------------- 2. cipher lemah
echo "\n== 2. Cipher lemah pada TLS 1.2 ==\n";
$ditolak = []; $tak_pasti = []; $lolos = [];
foreach ($policy['transport_weak_ciphers'] as $cipher) {
    [$ok, $teks, $meta] = tls_coba($host, $port, ['crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, 'ciphers' => "$cipher:@SECLEVEL=0"] + $longgar, $timeout);
    if ($ok) { $lolos[] = $cipher . ' (dinegosiasikan: ' . ($meta['cipher_name'] ?? '?') . ')'; }
    elseif (klasifikasi_gagal($teks) === 'ditolak') { $ditolak[] = $cipher; }
    else { $tak_pasti[] = $cipher; }
}
$total = count($policy['transport_weak_ciphers']);
if ($lolos) {
    catat('GAGAL', 'Server MENERIMA cipher lemah: ' . implode(', ', $lolos));
} elseif ($ditolak) {
    catat('LULUS', 'Tidak ada cipher lemah yang diterima (' . count($ditolak) . " dari $total ditolak eksplisit oleh server)",
        'ditolak server: ' . implode(', ', $ditolak) . ($tak_pasti
            ? "\n             tidak dapat diuji dari mesin ini (klien tidak memilikinya, TIDAK dihitung lulus): " . implode(', ', $tak_pasti) : ''));
} else {
    catat('LEWAT', "Tidak satu pun dari $total cipher lemah dapat diuji dari mesin ini");
}
[$ok, , $meta] = tls_coba($host, $port, ['crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT] + $longgar, $timeout);
if ($ok) { catat('LULUS', 'Cipher TLS 1.2 yang dinegosiasikan: ' . ($meta['cipher_name'] ?? '?') . ' (' . ($meta['cipher_bits'] ?? '?') . ' bit)'); }

// ------------------------------------------------------------- 3. sertifikat
echo "\n== 3. Sertifikat ==\n";
[$ok, $teks, $meta, $cert] = tls_coba($host, $port, ['verify_peer' => true, 'verify_peer_name' => true], $timeout);
if ($ok) {
    catat('LULUS', 'Rantai sertifikat sah dan nama host cocok');
} elseif (strpos($teks, 'unable to get local issuer') !== false || strpos($teks, 'unable to get issuer') !== false) {
    catat('LEWAT', 'Rantai tidak dapat diverifikasi dari mesin ini: tidak ada bundel CA (beri --cafile=...)');
} else {
    catat('GAGAL', 'Verifikasi sertifikat gagal', trim(substr($teks, 0, 160)));
}
if (!$cert) { [, , , $cert] = tls_coba($host, $port, $longgar, $timeout); }
if ($cert) {
    $x = openssl_x509_parse($cert);
    $sisa = (int) floor(($x['validTo_time_t'] - time()) / 86400);
    $berlaku = date('Y-m-d', $x['validFrom_time_t']) . ' s.d. ' . date('Y-m-d', $x['validTo_time_t']);
    if ($sisa < 0)                                  { catat('GAGAL', "Sertifikat KEDALUWARSA ($berlaku)"); }
    elseif ($sisa < $policy['transport_cert_warn_days']) { catat('PERINGATAN', "Sertifikat berakhir $sisa hari lagi ($berlaku)"); }
    else                                            { catat('LULUS', "Masa berlaku sertifikat: $sisa hari lagi ($berlaku)"); }
    $d = openssl_pkey_get_details(openssl_pkey_get_public($cert));
    $jenis = [OPENSSL_KEYTYPE_RSA => 'RSA', OPENSSL_KEYTYPE_EC => 'EC'][$d['type']] ?? 'lain';
    $cukup = ($jenis === 'RSA' && $d['bits'] >= 2048) || ($jenis === 'EC' && $d['bits'] >= 256);
    catat($cukup ? 'LULUS' : 'GAGAL', "Kunci sertifikat: $jenis {$d['bits']} bit");
    $sig = strtolower($x['signatureTypeSN'] ?? '');
    catat((strpos($sig, 'md5') !== false || strpos($sig, 'sha1') !== false) ? 'GAGAL' : 'LULUS', 'Algoritma tanda tangan sertifikat: ' . ($x['signatureTypeLN'] ?? '?'));
} else {
    catat('LEWAT', 'Sertifikat tidak dapat dibaca dari mesin ini');
}

// ------------------------------------------------------------- 4 & 5. HTTP
function http_ambil($alamat, array $tambahan, $timeout) {
    $ch = curl_init($alamat);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => $timeout, CURLOPT_USERAGENT => 'Klinik-PKP-uji-tls/1.0'] + $tambahan);
    $badan = curl_exec($ch);
    $info = ['galat' => curl_error($ch), 'kode' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE)];
    $kepala = is_string($badan) ? substr($badan, 0, (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE)) : '';
    curl_close($ch);
    return $info + ['kepala' => $kepala];
}

echo "\n== 4. HTTP dialihkan ke HTTPS ==\n";
if (!extension_loaded('curl')) {
    catat('LEWAT', 'Ekstensi curl tidak ada; uji HTTP dan header dilewati');
} else {
    $h = http_ambil("http://$host/", [], $timeout);
    if ($h['kode'] === 0) {
        catat('LEWAT', 'Port 80 tidak dapat dijangkau dari mesin ini', $h['galat']);
    } elseif (in_array($h['kode'], [301, 302, 307, 308], true) && preg_match('/^location:\s*https:\/\//im', $h['kepala'])) {
        catat('LULUS', "http://$host/ dialihkan ke HTTPS (HTTP {$h['kode']})");
    } else {
        catat('GAGAL', "http://$host/ tidak dialihkan ke HTTPS (HTTP {$h['kode']})");
    }

    echo "\n== 5. Respons HTTPS ==\n";
    $opsi = transport_curl_options();
    if ($cafile) { $opsi[CURLOPT_CAINFO] = $cafile; }
    $h = http_ambil("https://$host/", $opsi, $timeout);
    if ($h['kode'] === 0) {
        catat('GAGAL', 'Permintaan HTTPS gagal dengan kebijakan TLS aplikasi (TLS 1.2+, sertifikat diverifikasi)', $h['galat']);
    } else {
        catat('LULUS', "GET https://$host/ berhasil dengan kebijakan TLS aplikasi (HTTP {$h['kode']})");
        if (preg_match('/^strict-transport-security:\s*(.+)$/im', $h['kepala'], $m) && preg_match('/max-age=(\d+)/i', $m[1], $ma)) {
            $umur = (int) $ma[1];
            catat($umur >= $policy['transport_hsts_min_age'] ? 'LULUS' : 'GAGAL',
                "HSTS aktif, max-age $umur detik (batas kebijakan {$policy['transport_hsts_min_age']})"
                . (stripos($m[1], 'includesubdomains') !== false ? ', includeSubDomains' : ''));
        } else {
            catat('GAGAL', 'Header Strict-Transport-Security tidak ada');
        }
        preg_match_all('/^set-cookie:\s*(.+)$/im', $h['kepala'], $cookies);
        if (!$cookies[1]) {
            catat('LEWAT', 'Tidak ada cookie pada respons ini');
        } else {
            $lemah = [];
            foreach ($cookies[1] as $c) {
                if (stripos($c, '; secure') === false || stripos($c, 'httponly') === false) { $lemah[] = trim(explode('=', $c, 2)[0]); }
            }
            catat($lemah ? 'GAGAL' : 'LULUS', $lemah ? 'Cookie tanpa Secure+HttpOnly: ' . implode(', ', $lemah)
                                                    : count($cookies[1]) . ' cookie, semuanya Secure dan HttpOnly');
        }
        catat(preg_match('/^content-security-policy:.*upgrade-insecure-requests/im', $h['kepala']) ? 'LULUS' : 'PERINGATAN',
            'CSP upgrade-insecure-requests (cegah konten campuran)');
    }
}

// ------------------------------------------------------------- ringkasan
echo "\nRINGKASAN: {$hasil['LULUS']} lulus, {$hasil['GAGAL']} gagal, {$hasil['PERINGATAN']} peringatan, {$hasil['LEWAT']} dilewati (tidak dapat diuji dari mesin ini)\n";
exit($hasil['GAGAL'] > 0 ? 1 : 0);
