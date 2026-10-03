<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Perbaikan keamanan tingkat sedang (3 Okt 2026).
 *
 *   php docs/engineering/uji_keamanan_sedang.php
 *
 *   G1. Onboarding tidak bisa dikirim ulang dari akun lengkap (sudah tertutup sebelumnya; penjaga regresi).
 *   G2. Login: satu pesan dan waktu setara untuk akun ada/tidak ada/nonaktif/tanpa sandi; tidak ada
 *       kunci per akun yang bisa dipasang orang lain; penebak ditahan per pasangan IP + nama masuk.
 *   G3. Sakelar B2 menunggu keputusan: berkas identitas (KTP, KK, foto diri) tidak tersaji ke admin
 *       kab/kota, foto kondisi rumah tetap tersaji; superadmin tidak terkena.
 *   G4. Proxy foto SIKUMBANG: hanya path foto yang sah, satu foto satu berkas cache, folder cache disapu
 *       (umur + batas ukuran), parameter pencarian disaring sebelum menjadi kunci cache.
 *   G5. Aksi tulis akun universitas digerbangi hak modul universitas_bidang.
 *   G6. Penampil PDF memanggil getDocument dengan isEvalSupported: false.
 *   G7. Data SIKUMBANG di peta /sebaran di-escape sebelum masuk HTML; onclick foto memakai literal JSON.
 *   G8. Daftar isi direktori di bawah assets/ ditolak; PDF Bank Data tersembunyi tidak terdaftar.
 *
 * Bukti menggigit: dijalankan terhadap kode main (ecdbd85) merah di setiap grup G2-G8 (lihat catatan
 * commit). G1 sudah tertutup di main, jadi hijau di sana juga.
 *
 * Lokal saja (menolak jalan bila DB bukan lokal). Akun uji @uji-sedang.test dibuat dan dihapus sendiri;
 * akun seed_agen_peran.php dipakai untuk admin kab/kota, admin bidang, dan super admin. Ember batas laju
 * yang tersentuh dipinjam lalu dikembalikan. Dua permintaan keluar ke SIKUMBANG sama dengan pengunjung biasa
 * (satu foto yang tidak ada, pemanasan cache pencarian bawaan).
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
define('FCPATH', APP_ROOT . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
define('SANDI_AGEN', 'AgenUji!2026'); // seed_agen_peran.php
if ( ! function_exists('log_message')) { function log_message() {} }

$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    $k = trim($k); $v = trim($v, " \t\"'");
    if ( ! array_key_exists($k, $env)) { $env[$k] = $v; putenv("$k=$v"); }
}
mysqli_report(MYSQLI_REPORT_OFF);
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
    cek(FALSE, 'DB lokal (suite ini menulis akun dan baris uji)');
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n"; exit(1);
}
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$db->query("SET time_zone = '+07:00'");
function satu($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($x) => $x === NULL ? NULL : (string) $x, $p)); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : NULL;
}
function jalan($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($x) => $x === NULL ? NULL : (string) $x, $p)); }
    $st->execute();
    return $st->insert_id ?: $st->affected_rows;
}

/* ------------------------------------------------------------------ HTTP: jar per sesi, IPv4 atau IPv6 */
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'ujs'); }
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
/** @return array kode, badan, url, lokasi, tipe, detik */
function minta($jar, $path, $post = NULL, array $o = []) {
    $c = curl_init(BASE . $path);
    $kepala = [];
    if ( ! empty($o['ajax'])) { $kepala[] = 'X-Requested-With: XMLHttpRequest'; }
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => $o['ikuti'] ?? TRUE,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $kepala,
        CURLOPT_IPRESOLVE => ! empty($o['ip6']) ? CURL_IPRESOLVE_V6 : CURL_IPRESOLVE_V4]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $mulai = microtime(TRUE);
    $badan = (string) curl_exec($c);
    $hasil = ['kode' => (int) curl_getinfo($c, CURLINFO_HTTP_CODE), 'badan' => $badan,
        'url' => (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL), 'lokasi' => (string) curl_getinfo($c, CURLINFO_REDIRECT_URL),
        'tipe' => (string) curl_getinfo($c, CURLINFO_CONTENT_TYPE), 'detik' => microtime(TRUE) - $mulai];
    curl_close($c);
    return $hasil;
}
/** Satu percobaan login AJAX dari jar baru. @return [jar, json|null, kode] */
function coba_masuk($email, $sandi, $ip6 = FALSE) {
    $j = jar();
    minta($j, 'Auth/login', NULL, ['ip6' => $ip6]);
    $r = minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi], ['ajax' => TRUE, 'ip6' => $ip6]);
    return [$j, json_decode($r['badan'], TRUE), $r['kode'], $r['detik']];
}
function masuk_halaman($email, $sandi) {
    [$j, $json] = coba_masuk($email, $sandi);
    return ($json['status'] ?? '') === 'success' ? $j : NULL;
}

/* ------------------------------------------------------------------ Ember batas laju: dipinjam lalu dikembalikan */
$ember_asli = [];
function pinjam_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
$IP = ['127.0.0.1', '0000000000000000/64']; // ::1 dihitung per blok /64 (anti_automation_ip_bucket)
foreach (['login', 'foto_hulu'] as $pol) { foreach ($IP as $ip) { pinjam_ember(hash('sha256', "$pol:ip:$ip")); } }
function ember_pasangan($ip, $login) { pinjam_ember(hash('sha256', 'login_akun:key:' . hash('sha256', $ip . '|' . strtolower($login)))); }

$TAG = 'ujisedang' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
// Biaya 10 = bawaan PHP Apache lokal (8.2). Runner memakai `php` di PATH (bisa 8.4, bawaan 12): tanpa biaya
// eksplisit akun uji jadi lebih lambat dari akun sungguhan dan uji waktu G2 merah semu.
$HASH = password_hash($SANDI, PASSWORD_BCRYPT, ['cost' => 10]);
$MULAI = date('Y-m-d H:i:s');
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji, $IP;
    $email = $TAG . '_' . count($akun_uji) . '@uji-sedang.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'nama_pengguna' => $TAG . count($akun_uji), 'kata_sandi' => $HASH,
        'peran' => 'warga', 'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    foreach ($IP as $ip) { ember_pasangan($ip, $email); ember_pasangan($ip, $isi['nama_pengguna']); }
    return [(int) $id, $email];
}

$bersih = [];  // penutup tambahan (berkas, baris) dijalankan di finally, urutan terbalik
try {
    /* ============================================================== G1 */
    echo "== G1. Onboarding dari akun lengkap (penjaga regresi)\n";
    [$W1, $e1] = akun_baru('Uji Lengkap');
    $j = masuk_halaman($e1, $SANDI);
    cek($j !== NULL, 'PRASYARAT: akun uji lengkap bisa masuk');
    if ($j) {
        minta($j, 'Auth/save_onboarding', ['role' => 'mahasiswa', 'username' => $TAG . 'ganti', 'nama_lengkap' => 'Uji Lengkap',
            'alamat_domisili' => 'Alamat uji', 'phone' => '081234567890']);
        $u = satu('SELECT peran, nama_pengguna FROM usr_akun WHERE id=?', [$W1]);
        cek($u['peran'] === 'warga' && $u['nama_pengguna'] !== $TAG . 'ganti', 'Kirim ulang onboarding tidak mengganti peran maupun username');
    }

    /* ============================================================== G2 */
    echo "\n== G2. Login: tanpa enumerasi, tanpa penguncian oleh orang lain\n";
    [$Wk, $ek] = akun_baru('Uji Dikenal');
    [$Wn, $en] = akun_baru('Uji Nonaktif', ['status' => 'nonaktif']);
    [$Wg, $eg] = akun_baru('Uji Google', ['kata_sandi' => NULL, 'google_id' => 'uji' . bin2hex(random_bytes(6))]);
    $e_tak_ada = $TAG . '_tidakada@uji-sedang.test';
    foreach ($IP as $ip) { ember_pasangan($ip, $e_tak_ada); ember_pasangan($ip, $TAG . 'tidakada'); }
    $pesan = [];
    foreach (['tidak ada' => $e_tak_ada, 'sandi salah' => $ek, 'nonaktif' => $en, 'tanpa sandi (Google)' => $eg,
              'username tidak ada' => $TAG . 'tidakada', 'username dikenal' => $TAG . '1'] as $ket => $login) {
        [, $json, $kode] = coba_masuk($login, 'SalahSandi#123');
        $pesan[$ket] = ($json['status'] ?? '?') . '|' . ($json['message'] ?? '?');
    }
    cek(count(array_unique($pesan)) === 1 && strpos(reset($pesan), 'error|') === 0,
        'Satu pesan yang sama untuk akun tidak ada, sandi salah, nonaktif, tanpa sandi, username ada/tidak');
    if (count(array_unique($pesan)) !== 1) { foreach ($pesan as $k => $v) { echo "        $k: $v\n"; } }
    cek(stripos(implode(' ', $pesan), 'sisa') === FALSE && stripos(implode(' ', $pesan), 'terkunci') === FALSE
        && stripos(implode(' ', $pesan), 'dinonaktifkan') === FALSE, 'Pesan tidak menyebut sisa percobaan, kunci, atau status akun');
    [, $json] = coba_masuk($en, $SANDI);
    cek(($json['status'] ?? '') !== 'success' && stripos($json['message'] ?? '', 'dinonaktifkan') !== FALSE,
        'Akun nonaktif dengan sandi BENAR tetap ditolak; sebabnya hanya terbaca pemegang sandi');

    // Waktu: akun tidak ada juga menjalankan bcrypt (median 4 percobaan, berselang-seling).
    [$Wt, $et] = akun_baru('Uji Waktu');
    $e_tak_ada2 = $TAG . '_waktu_tidakada@uji-sedang.test';
    foreach ($IP as $ip) { ember_pasangan($ip, $e_tak_ada2); }
    $t_ada = $t_tidak = [];
    for ($i = 0; $i < 4; $i++) {
        $t_tidak[] = coba_masuk($e_tak_ada2, 'SalahSandi#123')[3];
        $t_ada[] = coba_masuk($et, 'SalahSandi#123')[3];
    }
    $median = function ($a) { sort($a); return ($a[1] + $a[2]) / 2; };
    $rasio = $median($t_tidak) / max(0.001, $median($t_ada));
    cek($rasio >= 0.8, sprintf('Waktu akun tidak ada setara akun ada (rasio median %.2f, batas 0,80)', $rasio));

    // Penguncian: penebak dari IPv4 tidak bisa mengunci pemilik (super admin) yang masuk dari IPv6.
    [$Wa, $ea] = akun_baru('Uji Super Admin', ['peran' => 'admin']);
    for ($i = 0; $i < 5; $i++) { coba_masuk($ea, 'SalahSandi#' . $i); }
    [, $json, $kode] = coba_masuk($ea, $SANDI);
    cek($kode === 429 && ($json['status'] ?? '') !== 'success', 'Penebak (IPv4) ditahan 429 sesudah 5 gagal, walau kini sandinya benar');
    [, $json] = coba_masuk($ea, $SANDI, TRUE);
    cek(($json['status'] ?? '') === 'success', 'Pemilik super admin dari IP lain (IPv6) tetap bisa masuk dengan sandi benar');
    $u = satu('SELECT terkunci_sampai FROM usr_akun WHERE id=?', [$Wa]);
    cek($u['terkunci_sampai'] === NULL, 'Login gagal tidak lagi menulis kunci akun (terkunci_sampai)');
    $alert = satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE aksi='peringatan_keamanan' AND objek_tipe='login_beruntun'
        AND created_at >= ? AND JSON_VALUE(detail_json, '$.akun_id') = ?", [$MULAI, $Wa]);
    cek((int) $alert['n'] >= 1, 'Gagal beruntun tetap menjadi peringatan keamanan untuk admin (login_beruntun)');
    $e_tak_ada3 = $TAG . '_kunci_tidakada@uji-sedang.test';
    foreach ($IP as $ip) { ember_pasangan($ip, $e_tak_ada3); }
    for ($i = 0; $i < 5; $i++) { coba_masuk($e_tak_ada3, 'SalahSandi#' . $i); }
    [, , $kode] = coba_masuk($e_tak_ada3, 'SalahSandi#9');
    cek($kode === 429, 'Nama masuk yang tidak ada ditahan dengan cara yang sama (tidak bisa dipakai membedakan akun)');

    /* ============================================================== G3 */
    echo "\n== G3. Sakelar B2: berkas identitas tidak tersaji ke admin kab/kota\n";
    $kebijakan = (string) @file_get_contents(APPPATH . 'config/kebijakan_data.php');
    $menunggu = strpos($kebijakan, "\$config['identitas_warga_kabkota'] = 'menunggu_keputusan';") !== FALSE;
    $kk = satu("SELECT id, kabupaten_id FROM usr_akun WHERE email='agen_admin_kabkota@agen.test' AND peran='admin_kabkota'");
    $sa = satu("SELECT id FROM usr_akun WHERE email='agen_admin@agen.test' AND peran='admin'");
    if ( ! $menunggu) {
        cek(TRUE, 'Sakelar B2 sudah diputuskan tampil: grup ini tidak berlaku');
    } elseif (cek($kk && $kk['kabupaten_id'] && $sa, 'PRASYARAT: akun seed admin kab/kota dan super admin ada (seed_agen_peran.php)')) {
        [$Ww] = akun_baru('Uji Pemohon B2');
        $pid = jalan("INSERT INTO sf_penilaian_perumahan (user_id, status, created_at) VALUES (?, 'submitted', NOW())", [$Ww]);
        $bersih[] = fn() => jalan('DELETE FROM sf_penilaian_perumahan WHERE id=?', [$pid]);
        require_once APPPATH . 'helpers/private_upload_helper.php';
        $dir = private_uploads_dir('warga_assessment', $pid);
        @mkdir($dir, 0755, TRUE);
        $bersih[] = function () use ($dir) { foreach (glob($dir . '*') as $f) { @unlink($f); } @rmdir($dir); };
        // JPEG 4x4 abu-abu (dibuat sekali dengan GD); tertanam supaya tidak bergantung pada GD ber-JPEG di PHP CLI.
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gNTAK/9sAQwAQCwwODAoQDg0OEhEQExgoGhgWFhgxIyUdKDozPTw5Mzg3QEhcTkBEV0U3OFBtUVdfYmdoZz5NcXlwZHhcZWdj/9sAQwEREhIYFRgvGhovY0I4QmNjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2Nj/8AAEQgABAAEAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A8/ooooA//9k=');
        foreach (['id_card_photo', 'family_card_photo', 'self_photo', 'house_front_photo'] as $jenis) {
            $nama = $jenis . '_' . bin2hex(random_bytes(4)) . '.jpg';
            file_put_contents($dir . $nama, $jpeg);
            jalan('INSERT INTO sf_berkas_penilaian (penilaian_id, jenis_berkas, path_privat, mime_type, ukuran_byte, sha256, created_at) VALUES (?,?,?,?,?,?,NOW())',
                [$pid, $jenis, $nama, 'image/jpeg', strlen($jpeg), hash('sha256', $jpeg . $nama)]);
        }
        $bersih[] = fn() => jalan('DELETE FROM sf_berkas_penilaian WHERE penilaian_id=?', [$pid]);
        $prog = satu('SELECT id FROM sf_program ORDER BY id LIMIT 1');
        $qid = jalan("INSERT INTO sf_antrean_pengajuan (user_id, program_id, kabupaten_id, penilaian_id, kode_tiket, status_antrean, created_at)
            VALUES (?,?,?,?,?, 'pending', NOW())", [$Ww, $prog['id'], $kk['kabupaten_id'], $pid, 'UJSD' . random_int(100000, 999999)]);
        $bersih[] = fn() => jalan('DELETE FROM sf_antrean_pengajuan WHERE id=?', [$qid]);

        $jk = masuk_halaman('agen_admin_kabkota@agen.test', SANDI_AGEN);
        if (cek($jk !== NULL, 'PRASYARAT: admin kab/kota (seed) masuk')) {
            $r = minta($jk, 'Admin_Kabkota/detail/' . $qid);
            cek($r['kode'] === 200 && strpos($r['badan'], 'Bukti privat') !== FALSE, 'PRASYARAT: detail antrean terbuka');
            cek(strpos($r['badan'], 'Lihat Foto KTP') === FALSE && strpos($r['badan'], 'Lihat Foto KK') === FALSE
                && strpos($r['badan'], 'Lihat Foto Diri') === FALSE, 'Detail tidak menautkan Foto KTP, Foto KK, Foto Diri');
            cek(strpos($r['badan'], 'Lihat Rumah Depan') !== FALSE, 'Foto kondisi rumah tetap ditautkan');
            foreach (['id_card_photo', 'family_card_photo', 'self_photo'] as $jenis) {
                $r = minta($jk, "Admin_Kabkota/evidence/$qid/$jenis");
                cek($r['kode'] === 404 && strpos($r['tipe'], 'image/') !== 0, "Berkas $jenis lewat URL langsung: 404");
            }
            $r = minta($jk, "Admin_Kabkota/evidence/$qid/house_front_photo");
            cek($r['kode'] === 200 && strpos($r['tipe'], 'image/jpeg') === 0, 'Foto rumah lewat URL langsung: 200 image/jpeg');
        }
        $js = masuk_halaman('agen_admin@agen.test', SANDI_AGEN);
        if (cek($js !== NULL, 'PRASYARAT: super admin (seed) masuk')) {
            $r = minta($js, "Admin/evidence/$qid/id_card_photo");
            cek($r['kode'] === 200 && strpos($r['tipe'], 'image/jpeg') === 0, 'Super admin tidak terkena sakelar B2: Foto KTP 200');
        }
    }

    /* ============================================================== G4 */
    echo "\n== G4. Proxy foto dan kunci cache SIKUMBANG\n";
    require_once APPPATH . 'helpers/transport_helper.php';
    require_once APPPATH . 'helpers/sikumbang_helper.php';
    if (cek(function_exists('sikumbang_foto_path') && function_exists('sikumbang_param'), 'Penyaring path foto dan parameter pencarian ada')) {
        $sah = ['public/upload/1647487281086-14ac5ebc-ea71-45b9-af42-f5aae8db3509.jpg', 'public/generated/images/fotoContoh-1579755836771.jpg',
            'public/upload/2024/10/05/file-lokasi-2ed6fc23-e788-47dc-b639-60236c6ccc06.jpeg', 'public/upload/x_y--z.PNG'];
        foreach ($sah as $p) { cek(sikumbang_foto_path($p) === $p, "Path sah diterima: $p"); }
        $tolak = ['public/upload/a.jpg?n=1', 'public/upload/a.jpg#1', 'x/../public/upload/a.jpg', 'public/upload/../a.jpg', 'public//upload/a.jpg',
            'public/upload/a.jpg%3Fn', 'ajax/lokasi/search', 'public/upload/a.php', 'public/upload/a.jpg.php', '/public/upload/a.jpg',
            'https://contoh.test/public/a.jpg', 'public/upload/' . str_repeat('a', 200) . '.jpg', ''];
        $lolos = array_filter($tolak, fn($p) => sikumbang_foto_path($p) !== NULL);
        cek( ! $lolos, 'Varian path ditolak (query, fragmen, segmen titik, ganda, non-gambar, panjang): ' . ($lolos ? implode(' , ', $lolos) : 'semua'));
        cek(sikumbang_param('sort', 'zz' . mt_rand(), 'terbaru') === 'terbaru' && sikumbang_param('sort', 'subsidi-termurah', 'terbaru') === 'subsidi-termurah',
            'sort di luar daftar izin jatuh ke bawaan');
        cek(sikumbang_param('searchBy', 'apa saja', 'nama-perumahan') === 'nama-perumahan' && sikumbang_param('status_rumah', 'x', 'subsidi') === 'subsidi'
            && sikumbang_param('kodeWilayah', '3374', '33') === '3374' && sikumbang_param('kodeWilayah', '3374x', '33') === '33',
            'searchBy, status_rumah, kodeWilayah disaring');
        cek(sikumbang_param('keyword', "  griya \t  indah  ") === 'griya indah' && mb_strlen(sikumbang_param('keyword', str_repeat('a', 500))) === 60,
            'Kata kunci dirapikan dan dipotong 60 karakter');
    }

    $cache_foto = FCPATH . 'assets/cache_foto/';
    $daftar = fn() => array_map('basename', glob($cache_foto . '*.jpg') ?: []);
    $foto_awal = $daftar();
    $bersih[] = function () use ($daftar, $foto_awal, $cache_foto) { foreach (array_diff($daftar(), $foto_awal) as $f) { @unlink($cache_foto . $f); } };
    $sah_tersimpan = NULL;
    foreach (glob(APPPATH . 'cache/*.json') ?: [] as $f) {
        $j = json_decode((string) file_get_contents($f), TRUE);
        foreach ((array) ($j['data'] ?? []) as $row) {
            foreach ((array) ($row['foto'] ?? []) as $u) {
                $pos = strpos((string) $u, 'public');
                $p = $pos === FALSE ? '' : stripslashes(substr($u, $pos));
                if ($p !== '' && is_file($cache_foto . md5($p) . '.jpg')) { $sah_tersimpan = $p; break 3; }
            }
        }
    }
    $jt = jar();
    if (cek($sah_tersimpan !== NULL, 'PRASYARAT: ada foto SIKUMBANG yang sudah tersimpan di cache')) {
        $r = minta($jt, 'Index/buka_foto?path=' . urlencode($sah_tersimpan), NULL, ['ikuti' => FALSE]);
        cek($r['kode'] === 302 && substr($r['lokasi'], -strlen(md5($sah_tersimpan) . '.jpg')) === md5($sah_tersimpan) . '.jpg',
            'Foto sah yang tersimpan: 302 ke berkas statisnya (kartu rumah tetap tampil)');
        $varian = [$sah_tersimpan . '?n=' . mt_rand(), 'a' . mt_rand() . '/../' . $sah_tersimpan, $sah_tersimpan . '#' . mt_rand(),
            str_replace('public/', 'public/./', $sah_tersimpan)];
        $kode = array_map(fn($v) => minta($jt, 'Index/buka_foto?path=' . urlencode($v), NULL, ['ikuti' => FALSE])['kode'], $varian);
        cek($kode === [404, 404, 404, 404], 'Varian path dari foto yang sama ditolak 404 (' . implode(',', $kode) . ')');
    }
    $r = minta($jt, 'Index/buka_foto?path=' . urlencode('ajax/lokasi/search?kodeWilayah=33&limit=100&z=' . mt_rand()), NULL, ['ikuti' => FALSE]);
    cek($r['kode'] === 404, 'Endpoint data SIKUMBANG lewat proxy foto ditolak 404');
    $r = minta($jt, 'Index/buka_foto?path=' . urlencode('public/upload/' . $TAG . '-tidak-ada.jpg'), NULL, ['ikuti' => FALSE]);
    cek($r['kode'] === 302 && strpos($r['lokasi'], 'default-placeholder.svg') !== FALSE, 'Foto sah yang tidak ada di hulu: placeholder lokal');
    cek(array_diff($daftar(), $foto_awal) === [], 'Tidak ada berkas cache foto baru dari varian atau balasan bukan gambar');

    // Penyapu: umur lalu batas ukuran, di folder sementara (folder nyata tidak disentuh).
    require_once APPPATH . 'libraries/Penyapu_retensi.php';
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ujs_web_' . bin2hex(random_bytes(3));
    $tdir = $tmp . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'cache_foto' . DIRECTORY_SEPARATOR;
    @mkdir($tdir, 0755, TRUE);
    $bersih[] = function () use ($tmp, $tdir) { foreach (glob($tdir . '*') as $f) { @unlink($f); } @rmdir($tdir); @rmdir(dirname($tdir)); @rmdir($tmp); };
    $buat = function ($nama, $byte, $umur_detik) use ($tdir) { file_put_contents($tdir . $nama, str_repeat('x', $byte)); touch($tdir . $nama, time() - $umur_detik); };
    $buat($tua = md5('tua') . '.jpg', 1000, 40 * 86400);
    $buat($baru = md5('baru') . '.jpg', 1000, 3600);
    $buat('index.html', 10, 90 * 86400);
    $penyapu = new Penyapu_retensi(['db' => NULL, 'policy' => [], 'app' => $tmp . DIRECTORY_SEPARATOR . 'application', 'web' => $tmp]);
    if (cek(method_exists($penyapu, 'jalankan') && (new ReflectionClass($penyapu))->hasMethod('sapu_foto'), 'Penyapu retensi punya tugas cache foto')) {
        $sapu = new ReflectionMethod($penyapu, 'sapu_foto');
        $sapu->setAccessible(TRUE);
        $h = $sapu->invoke($penyapu, 30, 512, FALSE);
        cek($h['jumlah'] === 1 && ! is_file($tdir . $tua) && is_file($tdir . $baru) && is_file($tdir . 'index.html'),
            'Foto lebih tua dari 30 hari disapu; foto baru dan index.html tetap');
        $buat($a = md5('a') . '.jpg', 600 * 1024, 3 * 3600);
        $buat($b = md5('b') . '.jpg', 600 * 1024, 2 * 3600);
        $buat($c = md5('c') . '.jpg', 600 * 1024, 1800);
        $h = $sapu->invoke($penyapu, 30, 1, FALSE);
        cek( ! is_file($tdir . $a) && ! is_file($tdir . $b) && is_file($tdir . $c) && is_file($tdir . $baru),
            'Melewati batas ukuran: yang tertua disapu lebih dulu sampai total di bawah batas');
    }
    $config = [];
    require APPPATH . 'config/rate_limits.php';
    cek(isset($config['rate_limit_policies']['foto_hulu']), 'Unduhan foto ke hulu punya batas laju sendiri (foto_hulu)');
    $config = [];
    require APPPATH . 'config/anti_automation.php';
    $cari = $config['route_classes']['cari'] ?? [];
    cek( ! array_diff(['index/sebaran', 'umum/sebaran', 'index/ajax_perumahan', 'sikumbang/index', 'index/load_more'], $cari),
        'Rute pencarian SIKUMBANG masuk kelas laju cari');

    // Kunci cache: nilai sembarang tidak melahirkan berkas cache baru.
    minta($jt, 'ajax_perumahan');
    minta($jt, 'sebaran');
    $cache_awal = glob(APPPATH . 'cache/*.json') ?: [];
    $bersih[] = function () use ($cache_awal) { foreach (array_diff(glob(APPPATH . 'cache/*.json') ?: [], $cache_awal) as $f) { @unlink($f); } };
    minta($jt, 'ajax_perumahan?limit=9x' . mt_rand() . '&page=1x' . mt_rand() . '&sort=zz' . mt_rand());
    minta($jt, 'sebaran?sort=zz' . mt_rand() . '&limit=' . mt_rand(11, 9999));
    $baru_cache = array_diff(glob(APPPATH . 'cache/*.json') ?: [], $cache_awal);
    cek($baru_cache === [], 'Parameter sort/limit/page sembarang tidak membuat berkas cache SIKUMBANG baru (' . count($baru_cache) . ' baru)');

    /* ============================================================== G5 */
    echo "\n== G5. Aksi tulis akun universitas digerbangi hak modul universitas_bidang\n";
    $ab = satu("SELECT id FROM usr_akun WHERE email='agen_admin_bidang@agen.test' AND peran='admin_bidang' AND bidang_kode IS NOT NULL");
    if (cek($ab !== NULL, 'PRASYARAT: akun seed admin bidang ada')) {
        $AB = (int) $ab['id'];
        $hak_asli = [];
        $res = $db->query('SELECT * FROM usr_hak_modul_admin WHERE user_id=' . $AB);
        while ($row = $res->fetch_assoc()) { $hak_asli[] = $row; }
        $bersih[] = function () use ($AB, $hak_asli) {
            jalan('DELETE FROM usr_hak_modul_admin WHERE user_id=?', [$AB]);
            foreach ($hak_asli as $row) { jalan('INSERT INTO usr_hak_modul_admin (' . implode(',', array_keys($row)) . ') VALUES (' . implode(',', array_fill(0, count($row), '?')) . ')', array_values($row)); }
        };
        $setel = function ($univ) use ($AB) {
            jalan('DELETE FROM usr_hak_modul_admin WHERE user_id=?', [$AB]);
            foreach (['kemitraan_bidang' => 1, 'universitas_bidang' => $univ, 'aduan_bidang' => 1, 'rekam_tinjauan' => 1] as $k => $v) {
                jalan('INSERT INTO usr_hak_modul_admin (user_id, kunci_modul, diizinkan, updated_at) VALUES (?,?,?,NOW())', [$AB, $k, $v]);
            }
        };
        $setel(0);
        $jb = masuk_halaman('agen_admin_bidang@agen.test', SANDI_AGEN);
        if (cek($jb !== NULL, 'PRASYARAT: admin bidang (seed) masuk')) {
            cek(minta($jb, 'Kemitraan_Bidang')['kode'] === 200, 'Modul magang (diizinkan) tetap terbuka');
            cek(minta($jb, 'Kemitraan_Bidang/universitas')['kode'] === 403, 'Halaman Akun Universitas (dicabut) 403');
            $kode = [];
            foreach (['buat_universitas' => [], 'ubah_universitas' => ['id' => 0], 'sandi_universitas' => ['id' => 0, 'password' => 'Uji#Sandi123'],
                      'status_universitas' => ['id' => 0, 'status' => 'nonaktif']] as $aksi => $isi) {
                $kode[$aksi] = minta($jb, 'Kemitraan_Bidang/' . $aksi, $isi, ['ikuti' => FALSE])['kode'];
            }
            cek(array_values(array_unique($kode)) === [403],
                'Keempat aksi tulis universitas ditolak 403 saat hak modulnya dicabut (' . json_encode($kode) . ')');
            $setel(1);
            $r = minta($jb, 'Kemitraan_Bidang/sandi_universitas', ['id' => 0, 'password' => 'Uji#Sandi123'], ['ikuti' => FALSE]);
            cek($r['kode'] !== 403, 'Dengan hak modul diizinkan, aksi sampai ke penangannya (bukan 403)');
        }
    }

    /* ============================================================== G6 */
    echo "\n== G6. Penampil PDF\n";
    $viewer = (string) @file_get_contents(APPPATH . 'views/pages/data_spasial/_dokumen_viewer.php');
    cek(preg_match('/getDocument\(\{\s*url:\s*pdfUrl,\s*isEvalSupported:\s*false\s*\}\)/', $viewer) === 1
        && preg_match_all('/getDocument\(/', $viewer) === 1, 'Satu-satunya getDocument memakai isEvalSupported: false');
    $r = minta($jt, 'Dokumen');
    cek($r['kode'] === 200 && strpos($r['badan'], 'isEvalSupported: false') !== FALSE, 'Halaman Dokumen menyajikan opsi itu');

    /* ============================================================== G7 */
    echo "\n== G7. Data SIKUMBANG di peta /sebaran dan onclick foto\n";
    $seb = (string) @file_get_contents(APPPATH . 'views/pages/data_spasial/sebaran.php');
    cek(strpos($seb, 'JSON_HEX_TAG') !== FALSE && strpos($seb, 'const esc =') !== FALSE, 'Data hulu dipasang dengan JSON_HEX_* dan ada fungsi esc()');
    $mentah = [];
    foreach (['${item.namaPerumahan', '${tipeRaw', '${item.idLokasi}', '${item.nama}', '${item.kabupaten', '${item.status}'] as $pola) {
        if (strpos($seb, $pola) !== FALSE) { $mentah[] = $pola; }
    }
    cek( ! $mentah, 'Tidak ada nilai hulu yang masuk template HTML tanpa esc()/idAman(): ' . ($mentah ? implode(' ', $mentah) : 'bersih'));
    $r = minta($jt, 'sebaran');
    cek($r['kode'] === 200 && strpos($r['badan'], 'const esc =') !== FALSE, 'Halaman /sebaran menyajikan versi ber-escape');
    $dp = (string) @file_get_contents(APPPATH . 'views/pages/pengembang/detail_pengembang.php');
    cek(strpos($dp, "openLightbox('<?") === FALSE && substr_count($dp, 'openLightbox(<?= htmlspecialchars(json_encode(') === 3,
        'onclick foto di detail pengembang memakai literal JSON, bukan string berkutip tunggal');

    /* ============================================================== G8 */
    echo "\n== G8. Daftar isi direktori\n";
    $dirs = ['assets/', 'assets/cache_foto/', 'assets/dokumen/unggahan/', 'assets/img/pengembang/unggahan/', 'assets/img/program/unggahan/', 'assets/uploads/', 'assets/cache_foto'];
    $kode = [];
    foreach ($dirs as $d) { $kode[$d] = minta($jt, $d)['kode']; }
    cek(array_unique(array_values($kode)) === [403], 'Direktori di bawah assets/ dijawab 403, bukan daftar isi (' . json_encode($kode) . ')');
    cek(minta($jt, 'assets/img/default-placeholder.svg')['kode'] === 200 && minta($jt, 'assets/dokumen/contoh_bank_data.pdf')['kode'] === 200,
        'Berkas statis di assets/ tetap 200');
    cek(minta($jt, '.env')['kode'] === 403 && minta($jt, 'docs/')['kode'] === 403 && minta($jt, 'composer.json')['kode'] === 403,
        'Jalur rahasia tetap 403');
    cek(minta($jt, '')['kode'] === 200 && minta($jt, 'Auth/login')['kode'] === 200 && minta($jt, 'cari_rumah')['kode'] === 200,
        'Rute aplikasi tetap 200');
    $dir_bd = FCPATH . 'assets/dokumen/unggahan/';
    @mkdir($dir_bd, 0755, TRUE);
    $nama_pdf = 'buku_data-' . bin2hex(random_bytes(8)) . '.pdf';
    copy(FCPATH . 'assets/dokumen/contoh_bank_data.pdf', $dir_bd . $nama_pdf);
    $bersih[] = fn() => @unlink($dir_bd . $nama_pdf);
    $bd = jalan("INSERT INTO sf_bank_data_dokumen (jenis, judul, berkas, ukuran, aktif, created_at, updated_at) VALUES ('buku_data', ?, ?, ?, 0, NOW(), NOW())",
        ['Uji Tersembunyi ' . $TAG, 'assets/dokumen/unggahan/' . $nama_pdf, filesize($dir_bd . $nama_pdf)]);
    $bersih[] = fn() => jalan('DELETE FROM sf_bank_data_dokumen WHERE id=?', [$bd]);
    $r = minta($jt, 'assets/dokumen/unggahan/');
    cek(strpos($r['badan'], $nama_pdf) === FALSE, 'Nama PDF Bank Data tersembunyi tidak bisa didaftar dari folder unggahan');
    cek(minta($jt, 'Dokumen/lihat/' . $bd)['kode'] === 404, 'Dokumen/lihat untuk PDF tersembunyi tetap 404');
    jalan('UPDATE sf_bank_data_dokumen SET aktif=1 WHERE id=?', [$bd]);
    $r = minta($jt, 'Dokumen/lihat/' . $bd);
    cek($r['kode'] === 200 && strpos($r['badan'], $nama_pdf) !== FALSE, 'Sesudah diaktifkan, Dokumen/lihat 200 dan menampilkan PDF-nya');
} catch (Throwable $e) {
    cek(FALSE, 'Suite melempar ' . get_class($e) . ': ' . $e->getMessage() . ' (baris ' . $e->getLine() . ')');
} finally {
    foreach (array_reverse($bersih) as $f) { try { $f(); } catch (Throwable $e) { echo '  (bersih gagal: ' . $e->getMessage() . ")\n"; } }
    foreach ($akun_uji as $id) {
        jalan('DELETE FROM sf_profil_warga WHERE user_id=?', [$id]);
        jalan('DELETE FROM usr_akun WHERE id=?', [$id]);
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', [$baris['kunci'], $baris['jendela_mulai_at'], $baris['jumlah_gagal']]); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    $sisa = satu("SELECT COUNT(*) n FROM usr_akun WHERE email LIKE '%@uji-sedang.test'");
    cek((int) $sisa['n'] === 0, 'Nol akun uji tertinggal');
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
