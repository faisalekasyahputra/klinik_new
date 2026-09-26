<?php
/**
 * Uji: cermin data SIMPERUM (sf_data_simperum, migrasi 064), prefill otomatis, dan penyegaran mingguan.
 *
 *   php docs/engineering/uji_data_simperum.php
 *
 * Keputusan pemilik produk 26 Sep 2026: data SIMPERUM warga terdaftar disimpan di DB kita (hanya GET),
 * form diagnosa sudah berisi tanpa klik Cek NIK, disegarkan mingguan, dan ada angka warga terdaftar.
 * Memakai fixture API-01/API-02 (NIK 3399..., bukan NIK warga) di mode simulation. Akun @example.test
 * dibuat dan dihapus sendiri; ember pembatas laju dipinjam lalu dikembalikan utuh.
 */
define('BASEPATH', 'x'); define('APPPATH', dirname(__DIR__, 2) . '/application/'); function log_message() {}
$root = dirname(__DIR__, 2);
$B = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || ! strpos($l, '=')) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); putenv(trim($k) . '=' . trim($v)); }
require APPPATH . 'libraries/Encryption_lib.php'; $enc = new Encryption_lib();
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujids' . bin2hex(random_bytes(3)); $pw = 'Ds1#' . bin2hex(random_bytes(5)); $ids = []; $jars = []; $rate_asli = [];
$ok = 0; $gagal = 0;
$cek = function ($c, $l) use (&$ok, &$gagal) { $c ? $ok++ : $gagal++; echo ($c ? '  OK    ' : '  GAGAL ') . $l . "\n"; return $c; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); return $r ? $r->fetch_assoc() : NULL; };
$http = function ($jar, $p, $post = NULL) use ($B) { $c = curl_init($B . $p); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]); if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post)); $b = (string) curl_exec($c); $k = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$k, html_entity_decode($b)]; };
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$jar = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'ds'); $jars[] = $j; return $j; };
$akun = function ($role, $kab = NULL, $lengkap = 1) use ($db, $tag, $pw, &$ids) {
    $e = "{$tag}_{$role}_" . count($ids) . '@example.test'; $h = password_hash($pw, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,kabupaten_id,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji DS',?,?,?,?,'active',?,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('sssii', $e, $h, $role, $kab, $lengkap); $st->execute(); $ids[] = $db->insert_id; return [$db->insert_id, $e];
};
$login = function ($email) use ($http, $csrf, $jar, $pw) { $j = $jar(); $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $pw, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
/* Ember dipinjam (disimpan lalu dikosongkan) dan dikembalikan utuh di finally, pola uji_pendataan_warga_r3:
   simulasi lain berjalan di localhost dengan IP yang sama, jadi ember tidak boleh dikosongkan permanen. */
$pinjam = function ($key) use ($db, &$rate_asli) {
    if (array_key_exists($key, $rate_asli)) return;
    $st = $db->prepare('SELECT limit_key,window_started_at,failed_attempts FROM sys_rate_limits WHERE limit_key=?'); $st->bind_param('s', $key); $st->execute();
    $rate_asli[$key] = $st->get_result()->fetch_assoc();
    $st = $db->prepare('DELETE FROM sys_rate_limits WHERE limit_key=?'); $st->bind_param('s', $key); $st->execute();
};
$angka = function ($html, $pola) { return preg_match($pola, $html, $m) ? (int) str_replace(',', '', $m[1]) : -1; };

$NIK = '3399991508850001'; $NIK_ANON = '3399995506900002';
$hash = $enc->deterministic_hash($NIK); $hash_anon = $enc->deterministic_hash($NIK_ANON);
try {
    echo "=== UJI CERMIN DATA SIMPERUM ===\n";
    if ( ! $cek((int) $satu("SELECT COUNT(*) n FROM usr_users WHERE nik_lookup_hash='$hash'")['n'] === 0, 'Prasyarat: NIK API-01 belum terdaftar di akun mana pun')) { throw new RuntimeException('NIK fixture masih terpakai akun lain'); }
    // ::1 dihitung per blok /64 (anti_automation_ip_bucket), jadi kunci nyatanya '0000000000000000/64'.
    foreach (['warga_lookup', 'rtlh_cek_anon', 'login'] as $p) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) $pinjam(hash('sha256', "$p:ip:$ip"));
    foreach ([$NIK, $NIK_ANON] as $n) $pinjam(hash('sha256', 'warga_lookup:nik:' . $enc->deterministic_hash($n)));

    echo "A. Onboarding warga ber-NIK\n";
    [$uid, $email] = $akun('warga', NULL, 0);
    foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian'] as $p) $pinjam(hash('sha256', "$p:account:$uid"));
    $jw = $login($email);
    $http($jw, 'Auth/save_onboarding', ['role' => 'warga', 'username' => $tag, 'nama_lengkap' => 'Warga Uji DS', 'nik_identitas' => $NIK,
        'alamat_domisili' => 'Jl. Uji No. 1, Kota Semarang', 'phone' => '081200000000', 'csrf_kpkp_token' => $csrf($jw)]);
    $cek((int) $satu("SELECT COUNT(*) n FROM usr_users WHERE id=$uid AND nik_lookup_hash='$hash'")['n'] === 1, 'NIK onboarding terikat ke akun (kunci hitung)');
    $row = $satu("SELECT * FROM sf_data_simperum WHERE user_id=$uid");
    $cek($row && $row['response_status'] === 'found' && $row['nik_lookup_hash'] === $hash, 'Onboarding langsung membuat baris cermin found');
    $cek($row && $row['sumber_air'] === '12' && $row['kepemilikan_rumah'] === '1' && $row['atap_id'] === '5' && $row['ada_pondasi'] === '0'
        && $row['bantuan_perumahan'] === '0' && $row['letak_sanitasi'] === NULL && $row['kode_dagri'] === '3374120003' && $row['idbdt'] === 'SYN-API-01',
        'Kode mentah SIMPERUM tersimpan apa adanya (sumber_air 12, kepemilikan_rumah 1, nilai "0" tidak hilang)');
    $cek($row && (int) $row['kabupaten_id'] === 3374, 'kabupaten_id diturunkan dari KodeDagri');
    $polos = $row ? implode('|', $row) : '';
    $cek($row && stripos($polos, 'SUGENG') === FALSE && strpos($polos, $NIK) === FALSE && stripos($polos, 'SINTETIS') === FALSE
        && $enc->decrypt($row['nama_ciphertext']) === 'SUGENG SINTETIS' && $enc->decrypt($row['nik_ciphertext']) === $NIK
        && $enc->decrypt($row['geo_lat_ciphertext']) === '-6.98412', 'Kolom PII terenkripsi (tidak ada nama/NIK/alamat polos di baris)');
    $cek($row && strtotime($row['next_refresh_at']) - strtotime($row['fetched_at']) === 7 * 86400, 'next_refresh_at = fetched_at + 7 hari');

    echo "B. Halaman diagnosa sudah berisi\n";
    $draft = $satu("SELECT id, current_step, water_source_code FROM sf_penilaian_perumahan WHERE user_id=$uid ORDER BY id DESC LIMIT 1");
    $cek($draft && $draft['current_step'] === 'housing_family' && $draft['water_source_code'] === 'other_unfit', 'Draft dibuat dari SIMPERUM saat onboarding (tanpa klik Cek NIK)');
    [$k, $b] = $http($jw, 'warga/pendataan');
    $cek($k === 200 && strpos($b, 'name="step" value="housing_family"') !== FALSE && strpos($b, 'name="step" value="find_data"') === FALSE,
        'warga/pendataan langsung di langkah sesudah find_data');
    $dom = new DOMDocument(); @$dom->loadHTML($b); $xp = new DOMXPath($dom);
    $cek($xp->query('//select[@name="occupation_code"]/option[@selected and @value!=""]')->length === 1
        && $xp->query('//select[@name="education_code"]/option[@selected and @value!=""]')->length === 1, 'Form berisi data SIMPERUM (pekerjaan, pendidikan terpilih)');

    echo "C. Pencarian anonim tidak menulis cermin\n";
    $n_anon = (int) $satu("SELECT COUNT(*) n FROM sf_data_simperum WHERE nik_lookup_hash='$hash_anon'")['n'];
    $jt = $jar(); $http($jt, 'cek_rtlh');
    [$k, $b] = $http($jt, 'Cek_Rtlh/periksa', ['nik' => $NIK_ANON, 'csrf_kpkp_token' => $csrf($jt)]);
    $cek($k === 200 && strpos($b, 'NIK ****0002') !== FALSE, 'Cek Data Rumah anonim menjawab');
    $cek((int) $satu("SELECT COUNT(*) n FROM sf_data_simperum WHERE nik_lookup_hash='$hash_anon'")['n'] === $n_anon, 'Cek_Rtlh anonim TIDAK membuat baris cermin');

    echo "D. Penyegaran mingguan (CLI)\n";
    $db->query("UPDATE sf_data_simperum SET fetched_at='2020-01-01 00:00:00', next_refresh_at='2020-01-08 00:00:00' WHERE user_id=$uid");
    $db->query("UPDATE sf_penilaian_perumahan SET water_source_code='pdam' WHERE id=" . (int) $draft['id']); // koreksi warga
    $profil = $satu("SELECT * FROM sf_profil_warga WHERE user_id=$uid");
    $d_sebelum = $satu("SELECT lock_version, updated_at, water_source_code FROM sf_penilaian_perumahan WHERE id=" . (int) $draft['id']);
    $n_draft = (int) $satu("SELECT COUNT(*) n FROM sf_penilaian_perumahan WHERE user_id=$uid")['n'];
    $php = PHP_BINARY; $keluar = [];
    putenv('CI_ENV=development'); // mode simulation; production memaksa api
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($root . '/index.php') . ' simperum_segarkan index 5 2>&1';
    exec($cmd, $keluar, $kode);
    $teks = implode("\n", $keluar);
    $cek($kode === 0 && strpos($teks, 'Penyegaran SIMPERUM') !== FALSE && preg_match('/found\s+[1-9]/', $teks), 'CLI simperum_segarkan berjalan dan menghitung found') || print("        (kode $kode) " . str_replace("\n", ' / ', $teks) . "\n");
    $cek(strpos($teks, $NIK) === FALSE && stripos($teks, 'SUGENG') === FALSE, 'Keluaran CLI hanya angka (tanpa NIK/nama)');
    $baru = $satu("SELECT * FROM sf_data_simperum WHERE user_id=$uid");
    $cek($baru && strtotime($baru['fetched_at']) > strtotime('2020-01-02') && strtotime($baru['next_refresh_at']) - strtotime($baru['fetched_at']) === 7 * 86400
        && (int) $baru['snapshot_id'] !== (int) $row['snapshot_id'], 'fetched_at diperbarui, next_refresh_at +7 hari, snapshot baru');
    $profil2 = $satu("SELECT * FROM sf_profil_warga WHERE user_id=$uid");
    $d_sesudah = $satu("SELECT lock_version, updated_at, water_source_code FROM sf_penilaian_perumahan WHERE id=" . (int) $draft['id']);
    $cek($profil2 == $profil && $d_sesudah == $d_sebelum && $d_sesudah['water_source_code'] === 'pdam'
        && (int) $satu("SELECT COUNT(*) n FROM sf_penilaian_perumahan WHERE user_id=$uid")['n'] === $n_draft,
        'Penyegaran tidak menyentuh profil, draft, maupun koreksi warga');
    [$k, $b] = $http($jar(), 'simperum_segarkan');
    $cek($k === 404, 'simperum_segarkan lewat web dijawab 404 (hanya CLI)');

    echo "E. Angka di dashboard\n";
    [, $eAdm] = $akun('admin');
    [$k, $b] = $http($login($eAdm), 'Admin_Dashboard');
    $wt = (int) $satu("SELECT COUNT(*) n FROM usr_users WHERE role='warga' AND nik_lookup_hash IS NOT NULL")['n'];
    $tc = (int) $satu("SELECT COUNT(*) n FROM sf_data_simperum WHERE response_status='found'")['n'];
    $cek($angka($b, '#Warga terdaftar</dt>\s*<dd[^>]*>([\d,]+)<#') === $wt && $wt >= 1, "Super admin: kartu Warga terdaftar = $wt");
    $cek($angka($b, '#Tercocokkan SIMPERUM</dt>\s*<dd[^>]*>([\d,]+)<#') === $tc && $tc >= 1, "Super admin: kartu Tercocokkan SIMPERUM = $tc");
    foreach ([3374, 3301] as $kab) {
        [, $eK] = $akun('admin_kabkota', $kab);
        [$k, $b] = $http($login($eK), 'Admin_Kabkota');
        $n = (int) $satu("SELECT COUNT(*) n FROM sf_data_simperum WHERE response_status='found' AND kabupaten_id=$kab")['n'];
        $cek($angka($b, '#data-tercocokkan-simperum>([\d,]+)<#') === $n && ($kab !== 3374 || $n >= 1), "Admin kab/kota $kab: hanya menghitung wilayahnya ($n)");
    }

    echo "F. Hapus akun menghapus cermin\n";
    $db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=$uid");
    $db->query("DELETE FROM usr_users WHERE id=$uid");
    $cek((int) $satu("SELECT COUNT(*) n FROM sf_data_simperum WHERE user_id=$uid OR nik_lookup_hash='$hash'")['n'] === 0, 'Baris cermin ikut terhapus (FK CASCADE)');
} catch (Throwable $e) {
    $cek(FALSE, 'Pengecualian: ' . $e->getMessage());
} finally {
    foreach ($ids as $id) { $db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=$id"); $db->query("DELETE FROM usr_users WHERE id=$id"); }
    $db->query("DELETE FROM sf_rekaman_simperum WHERE source_record_key LIKE 'SYN-API-%'");
    foreach ($rate_asli as $key => $r) {
        $st = $db->prepare('DELETE FROM sys_rate_limits WHERE limit_key=?'); $st->bind_param('s', $key); $st->execute();
        if ($r) { $st = $db->prepare('INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)'); $st->bind_param('ssi', $r['limit_key'], $r['window_started_at'], $r['failed_attempts']); $st->execute(); }
    }
    foreach ($jars as $j) @unlink($j);
    echo "RINGKASAN: " . ($ok + $gagal) . " pemeriksaan, $gagal gagal; akun tersisa " . $db->query("SELECT COUNT(*) FROM usr_users WHERE email LIKE '{$tag}%'")->fetch_row()[0] . "\n";
}
exit($gagal ? 1 : 0);
