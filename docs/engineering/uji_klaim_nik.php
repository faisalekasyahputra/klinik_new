<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Klaim NIK oleh pemilik terverifikasi (keputusan pemilik produk, 3 Okt 2026).
 *
 *   php docs/engineering/uji_klaim_nik.php
 *
 * Ikatan NIK yang BELUM terverifikasi (usr_akun.nik dari onboarding/Profil Saya, atau profil
 * pendataan tanpa confirmed_at) kalah oleh akun yang lolos verifikasi nama + tanggal lahir di Cek
 * NIK: ikatannya berpindah dalam satu transaksi, akun lama tetap hidup tanpa NIK itu, jejak audit
 * tertulis. Ikatan terverifikasi tetap menang. Sebelum verifikasi lolos, jawaban untuk NIK yang
 * terikat (terverifikasi atau belum) sama persis dengan NIK yang tidak terikat siapa pun.
 *
 * Bukti bahwa suite ini menggigit: merah terhadap kode `main` 9b61a3d (lihat catatan commit).
 *
 * SIMPERUM hanya mode simulasi dengan fixture API-01/02/03 (NIK berawalan 3399, kabupaten fiktif,
 * bukan NIK warga); suite menolak jalan bila mode bukan simulation atau DB bukan lokal. Akun uji
 * (@klaim-nik.test) dibuat dan dihapus sendiri; ember batas laju dipinjam lalu dikembalikan utuh.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
if ( ! function_exists('log_message')) { function log_message() {} }

$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}

$env = [];
foreach (file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    $k = trim($k); $v = trim($v, " \t\"'");
    if ( ! array_key_exists($k, $env)) { $env[$k] = $v; putenv("$k=$v"); }
}
mysqli_report(MYSQLI_REPORT_OFF);
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE) || ($env['SIMPERUM_MODE'] ?? '') !== 'simulation') {
    cek(FALSE, 'DB lokal dan SIMPERUM mode simulasi (suite ini menulis akun uji)');
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
    exit(1);
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

define('FCPATH', APP_ROOT . DIRECTORY_SEPARATOR);
require_once APPPATH . 'helpers/private_upload_helper.php';
require_once APPPATH . 'libraries/Encryption_lib.php';
$enc = new Encryption_lib();
$h = fn($nik) => $enc->deterministic_hash($nik);

$TAG = 'klaimnik' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT);
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji;
    $email = $TAG . '_' . count($akun_uji) . '@klaim-nik.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'kata_sandi' => $HASH, 'peran' => 'warga',
        'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $email];
}

function minta($jar, $path, $post = NULL) {
    $c = curl_init(BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $kode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return ['kode' => $kode, 'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8')];
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'ujk'); }
function masuk($email, $sandi) {
    $j = jar();
    minta($j, 'Auth/login');
    minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]);
    return $j;
}
/** Pesan notifikasi yang dibawa halaman (flashdata), apa adanya. */
function pesan($badan) {
    return preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $badan, $m) ? (json_decode($m[1], TRUE) ?: []) : [];
}
$step = fn($b) => preg_match('/name="step" value="([a-z_]+)"/', $b, $m) ? $m[1] : '';

$ember_asli = [];
function kosongkan_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
function ember_ip($policy) { foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) { kosongkan_ember(hash('sha256', "$policy:ip:$ip")); } }
function gagal_tercatat($dimensi, $nilai) {
    global $enc;
    $nilai = $dimensi === 'nik' ? $enc->deterministic_hash($nilai) : $nilai;
    return (int) (satu('SELECT jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [hash('sha256', "verifikasi_nik:$dimensi:$nilai")])['jumlah_gagal'] ?? 0);
}

$NIK1 = '3399991508850001'; $LAHIR1 = '1985-08-15'; // API-01, SUGENG SINTETIS
$NIK2 = '3399995506900002'; $LAHIR2 = '1990-06-15'; // API-02, SRI SINTETIS
$NIK3 = '3399990101700003'; $LAHIR3 = '1970-01-01'; // API-03, PAIMAN SINTETIS
$AGEN_SANDI = 'AgenUji!2026'; // sandi mainan seed_agen_peran.php, hanya ada di DB lokal
$KAB = (int) satu('SELECT id FROM kabupaten ORDER BY id LIMIT 1')['id'];
$audit_awal = (int) satu('SELECT COALESCE(MAX(id), 0) n FROM sys_jejak_audit')['n'];

$lookup = function ($j, $nik, $lahir) {
    ember_ip('warga_lookup');
    return minta($j, 'warga/pendataan', ['step' => 'find_data', 'action' => 'lookup', 'nik' => $nik, 'birth_date' => $lahir]);
};
$profil = fn($id) => satu('SELECT id, nik_lookup_hash, confirmed_at FROM sf_profil_warga WHERE user_id=?', [$id]);
$akun = fn($id) => satu('SELECT nik, nik_lookup_hash, status, profil_lengkap FROM usr_akun WHERE id=?', [$id]);
$jumlah = fn($tabel, $nik) => (int) satu("SELECT COUNT(*) n FROM $tabel WHERE nik_lookup_hash=?", [$h($nik)])['n'];

try {
    echo "== Persiapan\n";
    foreach ([$NIK1, $NIK2, $NIK3] as $n) {
        $terikat = $jumlah('usr_akun', $n) + $jumlah('sf_profil_warga', $n) + $jumlah('sf_data_simperum', $n);
        if ( ! cek($terikat === 0, 'Fixture ' . substr($n, 0, 4) . '..' . substr($n, -4) . ' belum terikat ke akun mana pun')) { throw new RuntimeException('fixture terikat'); }
        foreach (['warga_lookup', 'verifikasi_nik'] as $p) { kosongkan_ember(hash('sha256', "$p:nik:" . $h($n))); }
    }
    ember_ip('login');

    // A: pemegang lama TANPA verifikasi (NIK diketik di onboarding + profil pendataan lama + draft berjalan).
    [$A, $e_a] = akun_baru('Uji Pemegang Lama', ['nik' => $enc->encrypt($NIK1), 'nik_lookup_hash' => $h($NIK1)]);
    $profil_a = jalan('INSERT INTO sf_profil_warga (user_id, mode_sumber, nik_ciphertext, nik_lookup_hash, nama_ciphertext) VALUES (?,?,?,?,?)',
        [$A, 'manual', $enc->encrypt($NIK1), $h($NIK1), $enc->encrypt('Uji Pemegang Lama')]);
    $draft_a = jalan("INSERT INTO sf_penilaian_perumahan (user_id, profil_warga_id, kabupaten_id, status, langkah_sekarang, mode_sumber) VALUES (?,?,?,'draft','housing_family','manual')",
        [$A, $profil_a, $KAB]);
    $kirim_a = jalan("INSERT INTO sf_penilaian_perumahan (user_id, profil_warga_id, kabupaten_id, status, langkah_sekarang, mode_sumber, submitted_at) VALUES (?,?,?,'submitted','review','manual',NOW())",
        [$A, $profil_a, $KAB]);
    $dir_a = private_uploads_root() . 'warga_assessment' . DIRECTORY_SEPARATOR . $draft_a;
    @mkdir($dir_a, 0700, TRUE);
    $bukti_a = $dir_a . DIRECTORY_SEPARATOR . 'uji_bukti.txt';
    file_put_contents($bukti_a, 'berkas uji');
    cek(is_file($bukti_a), 'Prasyarat: berkas bukti draft A ada di penyimpanan privat');
    jalan("INSERT INTO sf_data_simperum (user_id, nik_lookup_hash, nik_ciphertext, status_respons, mode_sumber, fetched_at, next_refresh_at, created_at, updated_at) VALUES (?,?,?,'not_found','simulation',NOW(),NOW(),NOW(),NOW())",
        [$A, $h($NIK1), $enc->encrypt($NIK1)]);
    // V: pemegang TERVERIFIKASI lewat alur sungguhan.
    [$V, $e_v] = akun_baru('Sri Sintetis');
    // A2: pemegang lama hanya lewat usr_akun.nik (belum pernah mengisi pendataan).
    [$A2, $e_a2] = akun_baru('Uji Pemegang Akun'); // NIK3 diikat sesudah bagian 1 (NIK3 = pembanding NIK bebas)
    [$B, $e_b] = akun_baru('SUGENG SINTETIS');
    [$C, $e_c] = akun_baru('SRI SINTETIS');
    [$D, $e_d] = akun_baru('Paiman Sintetis', ['peran' => NULL, 'profil_lengkap' => 0]);
    [$D2, $e_d2] = akun_baru('Uji Onboarding Dua', ['peran' => NULL, 'profil_lengkap' => 0]);
    foreach ($akun_uji as $id) {
        foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian', 'verifikasi_nik'] as $p) { kosongkan_ember(hash('sha256', "$p:account:$id")); }
    }

    $j_v = masuk($e_v, $SANDI);
    $r = $lookup($j_v, $NIK2, $LAHIR2);
    if ( ! cek($step($r['badan']) === 'housing_family' && ($profil($V)['confirmed_at'] ?? NULL) !== NULL, 'Prasyarat: akun V memegang NIK API-02 terverifikasi')) { throw new RuntimeException('prasyarat V'); }

    echo "\n== 1. Percobaan gagal: jawaban sama untuk NIK terikat (belum/terverifikasi) dan NIK bebas\n";
    $j_b = masuk($e_b, $SANDI);
    $salah = [];
    foreach ([[$NIK1, '1985-08-16', 'terikat belum terverifikasi'], [$NIK2, '1990-06-16', 'terikat terverifikasi'], [$NIK3, '1970-01-02', 'tidak terikat']] as [$n, $tgl, $ket]) {
        $r = $lookup($j_b, $n, $tgl);
        $salah[$ket] = [pesan($r['badan']), $step($r['badan'])];
    }
    $acuan = $salah['tidak terikat'];
    cek($acuan[1] === 'find_data' && $acuan[0] !== [], 'NIK tidak terikat + data salah: pesan tidak cocok, tetap di Cek NIK');
    cek($salah['terikat belum terverifikasi'] === $acuan, 'NIK terikat belum terverifikasi: pesan dan langkah identik dengan NIK tidak terikat');
    cek($salah['terikat terverifikasi'] === $acuan, 'NIK terikat terverifikasi: pesan dan langkah identik dengan NIK tidak terikat');
    cek(stripos(json_encode($salah), 'terhubung dengan akun lain') === FALSE, 'Sebelum verifikasi lolos, tidak ada jawaban yang menyebut ikatan ke akun lain');
    cek(gagal_tercatat('account', $B) === 3 && gagal_tercatat('nik', $NIK1) === 1, 'Ketiga percobaan gagal dihitung batas laju verifikasi_nik (per akun dan per NIK)');
    $a = $akun($A);
    cek($a['nik_lookup_hash'] === $h($NIK1) && $profil($A) && satu('SELECT status FROM sf_penilaian_perumahan WHERE id=?', [$draft_a])['status'] === 'draft'
        && $profil($B) === NULL && $akun($B)['nik_lookup_hash'] === NULL, 'Percobaan gagal tidak mengubah apa pun pada akun A maupun B');
    // A2: pemegang lama hanya lewat usr_akun.nik (NIK diketik, belum pernah mengisi pendataan).
    jalan('UPDATE usr_akun SET nik=?, nik_lookup_hash=? WHERE id=?', [$enc->encrypt($NIK3), $h($NIK3), $A2]);

    echo "\n== 2. Pemilik lolos verifikasi: ikatan belum terverifikasi berpindah\n";
    $r = $lookup($j_b, $NIK1, $LAHIR1);
    cek($step($r['badan']) === 'housing_family' && strpos($r['badan'], 'NIK terverifikasi') !== FALSE, 'B lolos verifikasi dan maju ke langkah berikutnya');
    $pb = $profil($B);
    cek($pb && $pb['nik_lookup_hash'] === $h($NIK1) && $pb['confirmed_at'] !== NULL, 'Profil B memegang NIK, terverifikasi');
    cek($akun($B)['nik_lookup_hash'] === $h($NIK1) && $enc->decrypt($akun($B)['nik']) === $NIK1, 'usr_akun.nik B diisi NIK itu (terenkripsi)');
    $a = $akun($A);
    cek($a['nik'] === NULL && $a['nik_lookup_hash'] === NULL, 'usr_akun.nik A dikosongkan');
    cek($profil($A) === NULL, 'Profil pendataan A untuk NIK itu dilepas');
    cek(satu('SELECT id FROM sf_penilaian_perumahan WHERE id=?', [$draft_a]) === NULL, 'Draft A yang belum dikirim dihapus (seperti saat akun dihapus)');
    clearstatcache();
    cek( ! is_file($bukti_a), 'Berkas bukti draft A ikut disapu dari penyimpanan privat');
    cek((satu('SELECT status FROM sf_penilaian_perumahan WHERE id=?', [$kirim_a])['status'] ?? '') === 'submitted',
        'Penilaian A yang sudah dikirim tetap ada (arsip dinas)');
    cek((int) satu('SELECT COUNT(*) n FROM sf_data_simperum WHERE user_id=?', [$A])['n'] === 0, 'Cermin SIMPERUM NIK itu tidak lagi melekat ke A');
    cek($a['status'] === 'active' && (int) $a['profil_lengkap'] === 1, 'Akun A tidak dihapus dan tetap aktif');
    cek($jumlah('usr_akun', $NIK1) === 1 && $jumlah('sf_profil_warga', $NIK1) === 1 && $jumlah('sf_data_simperum', $NIK1) <= 1
        && (int) (satu('SELECT user_id FROM sf_data_simperum WHERE nik_lookup_hash=?', [$h($NIK1)])['user_id'] ?? $B) === $B,
        'Keunikan sidik NIK tetap: satu akun, satu profil, cermin (bila ada) milik B');
    $jejak = satu("SELECT pelaku_id, objek_tipe, objek_id, ringkasan, detail_json FROM sys_jejak_audit WHERE id > ? AND aksi='nik_dipindahkan' AND objek_id=?", [$audit_awal, $A]);
    cek($jejak && (int) $jejak['pelaku_id'] === $B && $jejak['objek_tipe'] === 'usr_akun', 'Jejak audit nik_dipindahkan: pelaku B, objek akun A');
    cek($jejak && strpos($jejak['ringkasan'] . $jejak['detail_json'], $NIK1) === FALSE && strpos($jejak['ringkasan'] . $jejak['detail_json'], substr($NIK1, -6)) === FALSE,
        'Jejak audit tidak memuat NIK');
    $detail = json_decode($jejak['detail_json'] ?? '', TRUE) ?: [];
    cek(($detail['akun_penerima'] ?? 0) === $B && ($detail['draft_dihapus'] ?? -1) === 1, 'Rincian jejak: akun penerima dan jumlah draft yang dihapus');

    echo "\n== 3. Akun A tetap bisa masuk dan memakai aplikasi\n";
    $j_a = masuk($e_a, $SANDI);
    $r = minta($j_a, 'akun/profil');
    cek($r['kode'] === 200 && strpos($r['badan'], 'Auth/do_login') === FALSE, 'A masuk dan membuka Profil Saya');
    $r = minta($j_a, 'warga/pendataan');
    cek($r['kode'] === 200 && $step($r['badan']) === 'find_data' && strpos($r['badan'], $NIK1) === FALSE && stripos($r['badan'], 'SUGENG') === FALSE,
        'A membuka Pendataan: mulai dari Cek NIK, tanpa NIK atau data pemilik');

    echo "\n== 4. Ikatan terverifikasi tetap menang\n";
    $j_c = masuk($e_c, $SANDI);
    $r = $lookup($j_c, $NIK2, $LAHIR2);
    cek(strpos($r['badan'], 'sudah terhubung dengan akun lain') !== FALSE && strpos($r['badan'], 'menu Aduan') !== FALSE && $step($r['badan']) === 'find_data',
        'NIK terverifikasi milik V: C yang lolos nama + tanggal lahir tetap ditolak, diarahkan ke Aduan');
    cek($profil($C) === NULL && $akun($C)['nik_lookup_hash'] === NULL && $profil($V)['nik_lookup_hash'] === $h($NIK2) && $profil($V)['confirmed_at'] !== NULL,
        'V tetap memegang NIK terverifikasi, C tidak mendapat apa pun');
    cek(strpos($r['badan'], $e_v) === FALSE && gagal_tercatat('account', $C) === 0, 'Pemilik tidak disebut; percobaan yang cocok tidak dihitung sebagai tebakan gagal');
    cek( ! satu("SELECT id FROM sys_jejak_audit WHERE id > ? AND aksi='nik_dipindahkan' AND objek_id=?", [$audit_awal, $V]), 'Tidak ada jejak pemindahan untuk V');

    echo "\n== 5. Onboarding: NIK terikat ke akun lain tidak lagi menahan pendaftaran\n";
    $onb = function ($e, $nik, $user) use ($TAG, $SANDI) {
        $j = masuk($e, $SANDI);
        minta($j, 'Auth/onboarding');
        $r = minta($j, 'Auth/save_onboarding', ['role' => 'warga', 'username' => $TAG . $user, 'nama_lengkap' => $user === 'd' ? 'Paiman Sintetis' : 'Uji Onboarding Dua',
            'nik_identitas' => $nik, 'alamat_domisili' => 'Alamat uji', 'phone' => '081234567890']);
        return [$j, $r];
    };
    [$j_d, $r_d] = $onb($e_d, $NIK3, 'd');   // NIK3 dipegang A2 tanpa verifikasi
    [$j_d2, $r_d2] = $onb($e_d2, $NIK2, 'e'); // NIK2 dipegang V terverifikasi
    $u = $akun($D);
    cek((int) $u['profil_lengkap'] === 1 && $u['nik_lookup_hash'] === NULL, 'Onboarding selesai, akun disimpan tanpa NIK yang terikat ke akun lain');
    $pesan_d = array_column(pesan($r_d['badan']), 'message');
    cek((bool) preg_grep('/Cek NIK/', $pesan_d), 'Onboarding memberi tahu cara membuktikan kepemilikan di Cek NIK');
    cek(array_column(pesan($r_d2['badan']), 'message') === $pesan_d && (int) $akun($D2)['profil_lengkap'] === 1 && $akun($D2)['nik_lookup_hash'] === NULL,
        'Pesan onboarding identik untuk pemegang terverifikasi dan belum terverifikasi');
    $r = minta($j_d, 'warga/pendataan');
    cek($step($r['badan']) === 'find_data' && strpos($r['badan'], 'value="' . $NIK3 . '"') !== FALSE, 'Cek NIK terisi NIK dari onboarding');
    $r = $lookup($j_d, $NIK3, $LAHIR3);
    cek($step($r['badan']) === 'housing_family' && $akun($D)['nik_lookup_hash'] === $h($NIK3) && ($profil($D)['confirmed_at'] ?? NULL) !== NULL,
        'D lolos verifikasi dan mengambil NIK dari A2 (pemegang lewat usr_akun saja)');
    cek($akun($A2)['nik_lookup_hash'] === NULL && $profil($A2) === NULL && $jumlah('usr_akun', $NIK3) === 1,
        'A2 melepas NIK, keunikan sidik di usr_akun tetap');

    echo "\n== 6. Admin: status dan jejak terbaca\n";
    ember_ip('login');
    $j_adm = masuk('agen_admin@agen.test', $AGEN_SANDI);
    $r = minta($j_adm, 'Admin_Audit?aksi=nik_dipindahkan');
    cek($r['kode'] === 200 && strpos($r['badan'], 'NIK dipindahkan ke pemilik terverifikasi') !== FALSE && strpos($r['badan'], $NIK1) === FALSE,
        'Jejak Audit menampilkan label yang terbaca, tanpa NIK');
    $r = minta($j_adm, 'Admin_Users?q=' . urlencode($e_b));
    cek(strpos($r['badan'], 'NIK terverifikasi') !== FALSE, 'Manajemen Pengguna: B tampil NIK terverifikasi');
    $r = minta($j_adm, 'Admin_Users?q=' . urlencode($e_a));
    cek(strpos($r['badan'], $e_a) !== FALSE && strpos($r['badan'], 'NIK terverifikasi') === FALSE && strpos($r['badan'], 'NIK belum terverifikasi') === FALSE,
        'Manajemen Pengguna: A tampil tanpa status NIK');
} catch (Throwable $e) {
    cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    if ($akun_uji) {
        $daftar = implode(',', array_map('intval', $akun_uji));
        $db->query("DELETE FROM sys_jejak_audit WHERE id > $audit_awal AND aksi='nik_dipindahkan' AND objek_id IN ('" . implode("','", $akun_uji) . "')");
        foreach (['sf_data_simperum', 'sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { $db->query("DELETE FROM $t WHERE user_id IN ($daftar)"); }
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($baris)); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    if (isset($dir_a)) { @unlink($bukti_a); @rmdir($dir_a); }
    echo "Akun uji tersisa: " . (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE email LIKE ?', [$TAG . '%'])['n'] . "\n";
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
