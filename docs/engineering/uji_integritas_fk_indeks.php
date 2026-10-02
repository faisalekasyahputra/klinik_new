<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji migrasi 069: kunci asing yang tadinya diandaikan kode, dan perapian indeks usr_users.
 *
 *   php docs/engineering/uji_integritas_fk_indeks.php
 *
 * Berjalan di DB dari .env (lokal; JANGAN production: menulis dan menghapus baris uji).
 * Data uji bertanda unik (surel @example.test, kode/teks berawalan tag) dan dibersihkan di akhir,
 * juga saat gagal. Daftar FK/indeks dibaca dari berkas migrasinya, jadi uji ini tidak bisa
 * menyimpang dari yang dipasang.
 *
 *  1. Setiap FK ada dengan aturan ON DELETE yang dimaksud; setiap indeks baru ada.
 *  2. Nilai yatim ditolak DB (errno 1452) di ke-13 kolom.
 *  3. Hapus induk: SET NULL / CASCADE / RESTRICT berperilaku sesuai rancangan.
 *  4. Indeks email kembar hilang, email tetap UNIQUE; username UNIQUE (NULL boleh banyak).
 *  5. Migrate::status() melaporkan bentuk 069 TERPASANG.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__, 2);
$total = 0;
$gagal = 0;
function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $gagal++; }
    return (bool) $kondisi;
}

echo "Uji integritas FK + indeks (migrasi 069)\n";

define('BASEPATH', $root . '/system/');
if ( ! class_exists('CI_Migration')) { class CI_Migration {} }
require_once $root . '/application/migrations/20260701000069_fk_indeks_integritas.php';
$FK = Migration_Fk_indeks_integritas::FK;
$INDEKS = Migration_Fk_indeks_integritas::INDEKS;

$env = [];
foreach (@file($root . '/.env', FILE_IGNORE_NEW_LINES) ?: [] as $b) {
    $b = trim($b);
    if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $b, 2);
    if ( ! isset($env[trim($k)])) { $env[trim($k)] = trim($v); }
}
mysqli_report(MYSQLI_REPORT_OFF);
$m = @new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
if ($m->connect_error) { cek(FALSE, 'koneksi DB dari .env'); exit(1); }
$m->set_charset('utf8mb4');

/** Jalankan kueri berparameter; kembalikan errno (0 = sukses). */
function jalan($sql, array $b = []) {
    global $m;
    $st = $m->prepare($sql);
    if ( ! $st) { return $m->errno; }
    if ($b) { $st->bind_param(str_repeat('s', count($b)), ...array_map(function ($v) { return $v === NULL ? NULL : (string) $v; }, $b)); }
    $st->execute();
    return $st->errno;
}
function nilai($sql, array $b = []) {
    global $m;
    $st = $m->prepare($sql);
    if ($b) { $st->bind_param(str_repeat('s', count($b)), ...array_map('strval', $b)); }
    $st->execute();
    $r = $st->get_result();
    $row = $r ? $r->fetch_row() : NULL;
    return $row ? $row[0] : NULL;
}
function id_terakhir() { global $m; return (int) $m->insert_id; }

$tag = 'u69' . bin2hex(random_bytes(4));          // 11 karakter: muat di kode bidang/asosiasi (30)
$kab = (int) nilai('SELECT GREATEST(9000, MAX(id)) + 1 + FLOOR(RAND() * 500) FROM kabupaten');
$hash = function ($s) use ($tag) { return str_pad($tag . $s, 64, '0'); };

$bersih = function () use ($m, $tag, $kab) {
    $m->query("DELETE FROM forum_diskusi WHERE judul_topik LIKE '{$tag}%'");
    $m->query("DELETE FROM aduan WHERE judul LIKE '{$tag}%'");
    $m->query("DELETE FROM psu_serah_terima WHERE nama_perumahan LIKE '{$tag}%'");
    $m->query("DELETE FROM srp2_registrations WHERE nama_perusahaan LIKE '{$tag}%'");
    $m->query("DELETE FROM srp2_certified_developers WHERE nama_perusahaan LIKE '{$tag}%'");
    $m->query("DELETE FROM sf_data_simperum WHERE nik_lookup_hash LIKE '{$tag}%'");
    $m->query("DELETE FROM sf_rekaman_simperum WHERE nik_lookup_hash LIKE '{$tag}%'");
    $m->query("DELETE FROM usr_users WHERE email LIKE '%{$tag}@example.test'");
    $m->query("DELETE FROM srp2_asosiasi WHERE kode LIKE '{$tag}%'");
    $m->query("DELETE FROM bidang WHERE kode = '{$tag}'");
    $m->query("DELETE FROM kabupaten WHERE id = {$kab} AND nama LIKE '{$tag}%'");
};
register_shutdown_function($bersih);

// ------------------------------------------------------------------ 1. bentuk skema
$aturan = [];
$r = $m->query("SELECT r.CONSTRAINT_NAME n, r.DELETE_RULE d, k.TABLE_NAME t, k.COLUMN_NAME c, k.REFERENCED_TABLE_NAME rt, k.REFERENCED_COLUMN_NAME rc
    FROM information_schema.REFERENTIAL_CONSTRAINTS r JOIN information_schema.KEY_COLUMN_USAGE k
      ON k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA AND k.CONSTRAINT_NAME = r.CONSTRAINT_NAME AND k.TABLE_NAME = r.TABLE_NAME
    WHERE r.CONSTRAINT_SCHEMA = DATABASE()");
foreach ($r->fetch_all(MYSQLI_ASSOC) as $b) { $aturan[$b['n']] = $b; }
foreach ($FK as $nama => [$t, $k, $induk, $ki, $hapus]) {
    $a = $aturan[$nama] ?? NULL;
    cek($a && $a['t'] === $t && $a['c'] === $k && $a['rt'] === $induk && $a['rc'] === $ki && $a['d'] === $hapus,
        "FK $nama: $t.$k -> $induk.$ki ON DELETE $hapus" . ($a ? '' : ' (tidak ada)'));
}
foreach ($INDEKS as $nama => [$t]) {
    cek((int) nilai('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$t, $nama]) > 0,
        "indeks $t.$nama ada");
}
cek(nilai("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sf_data_simperum' AND COLUMN_NAME = 'kabupaten_id'") === 'int(10) unsigned',
    'sf_data_simperum.kabupaten_id INT(10) UNSIGNED (sama dengan kabupaten.id)');
cek(nilai("SELECT CONCAT(COLUMN_TYPE, ' ', COLLATION_NAME) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_certified_developers' AND COLUMN_NAME = 'asosiasi'") === 'varchar(30) utf8mb4_unicode_ci',
    'srp2_certified_developers.asosiasi VARCHAR(30) utf8mb4_unicode_ci (sama dengan srp2_asosiasi.kode)');

// ------------------------------------------------------------------ fixture (semua bertanda)
jalan('INSERT INTO kabupaten (id, nama) VALUES (?, ?)', [$kab, $tag . ' kab']);
jalan("INSERT INTO bidang (kode, nama) VALUES (?, ?)", [$tag, $tag . ' bidang']);
jalan("INSERT INTO srp2_asosiasi (kode, nama, created_at) VALUES (?, ?, NOW())", [$tag, $tag . ' asosiasi']);
$surel = function ($s) use ($tag) { return $s . '_' . $tag . '@example.test'; };
jalan('INSERT INTO usr_users (email, username) VALUES (?, ?)', [$surel('warga'), $tag . 'w']);
$uid = id_terakhir();
jalan("INSERT INTO usr_users (email, role, kabupaten_id, bidang_kode) VALUES (?, 'admin_kabkota', ?, ?)", [$surel('staf'), $kab, $tag]);
$staf = id_terakhir();
cek($uid > 0 && $staf > 0, 'fixture akun uji (kabupaten dan bidang sah) tersimpan');
jalan("INSERT INTO forum_diskusi (nama_user, email_user, judul_topik, kategori, isi_diskusi, user_id, created_at) VALUES ('Uji', ?, ?, 'umum', 'isi', ?, NOW())", [$surel('warga'), $tag . ' topik', $uid]);
$did = id_terakhir();
jalan("INSERT INTO forum_komentar (id_diskusi, nama_komentator, isi_komentar, user_id, role, created_at) VALUES (?, 'Uji', ?, ?, 'Warga', NOW())", [$did, $tag . ' komentar', $uid]);
$kid = id_terakhir();
jalan("INSERT INTO forum_likes (user_id, target_type, target_id) VALUES (?, 'diskusi', ?)", [$uid, $did]);
jalan("INSERT INTO aduan (nama, email, judul, pesan, bidang) VALUES ('Uji', ?, ?, 'pesan', ?)", [$surel('warga'), $tag . ' aduan', $tag]);
$aid = id_terakhir();
jalan("INSERT INTO srp2_certified_developers (nama_perusahaan, kabupaten_id, asosiasi) VALUES (?, ?, NULL)", [$tag . ' PT', $kab]);
$devid = id_terakhir();
jalan("INSERT INTO srp2_registrations (nama_perusahaan, asosiasi) VALUES (?, ?)", [$tag . ' daftar', $tag]);
$regid = id_terakhir();
jalan("INSERT INTO psu_serah_terima (nama_perumahan, nama_pengembang, asosiasi) VALUES (?, 'Uji', NULL)", [$tag . ' psu']);
$psuid = id_terakhir();
jalan("INSERT INTO sf_rekaman_simperum (nik_lookup_hash, source_mode, response_status, fetched_at, expires_at) VALUES (?, 'simulation', 'found', NOW(), NOW())", [$hash('s')]);
$snap = id_terakhir();
jalan("INSERT INTO sf_data_simperum (user_id, nik_lookup_hash, kabupaten_id, response_status, source_mode, snapshot_id, fetched_at, next_refresh_at) VALUES (?, ?, ?, 'found', 'simulation', ?, NOW(), NOW())", [$uid, $hash('d'), $kab, $snap]);
$simid = id_terakhir();
cek(min($did, $kid, $aid, $devid, $regid, $psuid, $snap, $simid) > 0, 'fixture forum, aduan, SRP2, PSU, SIMPERUM tersimpan dengan rujukan sah');

// ------------------------------------------------------------------ 2. yatim ditolak
$tidak_ada = $tag . 'x';
$yatim = [
    'fk_forum_komentar_diskusi'      => ['UPDATE forum_komentar SET id_diskusi = ? WHERE id_komentar = ?', [2147483000, $kid]],
    'fk_forum_komentar_user'         => ['UPDATE forum_komentar SET user_id = ? WHERE id_komentar = ?', [2147483000, $kid]],
    'fk_forum_diskusi_user'          => ['UPDATE forum_diskusi SET user_id = ? WHERE id_diskusi = ?', [2147483000, $did]],
    'fk_forum_likes_user'            => ["INSERT INTO forum_likes (user_id, target_type, target_id) VALUES (?, 'komentar', ?)", [2147483000, $kid]],
    'fk_usr_users_kabupaten'         => ['UPDATE usr_users SET kabupaten_id = ? WHERE id = ?', [$kab + 1000, $staf]],
    'fk_usr_users_bidang'            => ['UPDATE usr_users SET bidang_kode = ? WHERE id = ?', [$tidak_ada, $staf]],
    'fk_aduan_bidang'                => ['UPDATE aduan SET bidang = ? WHERE id = ?', [$tidak_ada, $aid]],
    'fk_srp2_direktori_kabupaten'    => ['UPDATE srp2_certified_developers SET kabupaten_id = ? WHERE id = ?', [$kab + 1000, $devid]],
    'fk_srp2_direktori_asosiasi'     => ['UPDATE srp2_certified_developers SET asosiasi = ? WHERE id = ?', [$tidak_ada, $devid]],
    'fk_srp2_registrations_asosiasi' => ['UPDATE srp2_registrations SET asosiasi = ? WHERE id = ?', [$tidak_ada, $regid]],
    'fk_psu_asosiasi'                => ['UPDATE psu_serah_terima SET asosiasi = ? WHERE id = ?', [$tidak_ada, $psuid]],
    'fk_sf_data_simperum_kabupaten'  => ['UPDATE sf_data_simperum SET kabupaten_id = ? WHERE id = ?', [$kab + 1000, $simid]],
    'fk_sf_data_simperum_snapshot'   => ['UPDATE sf_data_simperum SET snapshot_id = ? WHERE id = ?', [9000000000, $simid]],
];
foreach ($yatim as $nama => [$sql, $b]) {
    cek(jalan($sql, $b) === 1452, "$nama: nilai yatim ditolak (errno 1452)");
}
cek(jalan('UPDATE aduan SET bidang = ? WHERE id = ?', ['', $aid]) === 1452, 'aduan.bidang string kosong ditolak (NULL-lah "belum ditriase")');
cek(jalan('UPDATE srp2_registrations SET asosiasi = NULL WHERE id = ?', [$regid]) === 0
    && jalan('UPDATE srp2_registrations SET asosiasi = ? WHERE id = ?', [$tag, $regid]) === 0, 'asosiasi NULL tetap boleh');

// ------------------------------------------------------------------ 3. hapus induk
cek(jalan('DELETE FROM srp2_asosiasi WHERE kode = ?', [$tag]) === 1451, 'hapus asosiasi yang masih dipakai pengajuan ditolak (RESTRICT)');
cek(jalan('UPDATE srp2_asosiasi SET kode = ? WHERE kode = ?', [$tidak_ada, $tag]) === 1451, 'mengganti kode asosiasi yang dipakai ditolak (tanpa ON UPDATE CASCADE)');
cek(jalan('DELETE FROM bidang WHERE kode = ?', [$tag]) === 1451, 'hapus bidang yang masih jadi cakupan staf ditolak (RESTRICT)');
cek(jalan('DELETE FROM kabupaten WHERE id = ?', [$kab]) === 1451, 'hapus kabupaten yang masih jadi cakupan staf ditolak (RESTRICT)');
jalan('UPDATE usr_users SET kabupaten_id = NULL, bidang_kode = NULL WHERE id = ?', [$staf]);
cek(jalan('DELETE FROM bidang WHERE kode = ?', [$tag]) === 0 && nilai('SELECT COUNT(*) FROM aduan WHERE id = ? AND bidang IS NULL', [$aid]) == 1,
    'hapus bidang: aduan kembali ke antrean triase (SET NULL), barisnya tetap');
cek(jalan('DELETE FROM kabupaten WHERE id = ?', [$kab]) === 0
    && nilai('SELECT COUNT(*) FROM srp2_certified_developers WHERE id = ? AND kabupaten_id IS NULL', [$devid]) == 1
    && nilai('SELECT COUNT(*) FROM sf_data_simperum WHERE id = ? AND kabupaten_id IS NULL', [$simid]) == 1,
    'hapus kabupaten: direktori SRP2 dan cermin SIMPERUM kehilangan kabupaten (SET NULL), barisnya tetap');
cek(jalan('DELETE FROM sf_rekaman_simperum WHERE id = ?', [$snap]) === 0 && nilai('SELECT COUNT(*) FROM sf_data_simperum WHERE id = ? AND snapshot_id IS NULL', [$simid]) == 1,
    'hapus snapshot (seperti penyapu retensi): cermin SIMPERUM tetap, snapshot_id NULL (SET NULL)');
cek(jalan('DELETE FROM usr_users WHERE id = ?', [$uid]) === 0, 'hapus akun yang punya topik, komentar, suka, cermin SIMPERUM berhasil');
cek(nilai('SELECT COUNT(*) FROM forum_diskusi WHERE id_diskusi = ? AND user_id IS NULL', [$did]) == 1
    && nilai('SELECT COUNT(*) FROM forum_komentar WHERE id_komentar = ? AND user_id IS NULL', [$kid]) == 1,
    'hapus akun: topik dan komentar bertahan tanpa tautan akun (SET NULL)');
cek(nilai('SELECT COUNT(*) FROM forum_likes WHERE target_type = ? AND target_id = ?', ['diskusi', $did]) == 0, 'hapus akun: tanda suka ikut terhapus (CASCADE)');
cek(nilai('SELECT COUNT(*) FROM sf_data_simperum WHERE id = ?', [$simid]) == 0, 'hapus akun: cermin SIMPERUM ikut terhapus (CASCADE lama, tetap)');
cek(jalan('DELETE FROM forum_diskusi WHERE id_diskusi = ?', [$did]) === 0 && nilai('SELECT COUNT(*) FROM forum_komentar WHERE id_komentar = ?', [$kid]) == 0,
    'hapus topik: komentarnya ikut terhapus (CASCADE)');

// ------------------------------------------------------------------ 4. indeks usr_users
cek((int) nilai("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usr_users' AND INDEX_NAME = 'idx_users_email'") === 0,
    'indeks kembar idx_users_email sudah tidak ada');
cek((int) nilai("SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usr_users' AND COLUMN_NAME = 'email' AND NON_UNIQUE = 0") === 1,
    'email tepat satu indeks UNIQUE');
cek(jalan('INSERT INTO usr_users (email) VALUES (?)', [strtoupper($surel('staf'))]) === 1062, 'surel kembar (beda huruf besar) tetap ditolak (errno 1062)');
jalan('INSERT INTO usr_users (email, username) VALUES (?, ?)', [$surel('a'), $tag . 'u']);
cek(jalan('INSERT INTO usr_users (email, username) VALUES (?, ?)', [$surel('b'), strtoupper($tag . 'u')]) === 1062, 'username kembar ditolak (errno 1062)');
cek(jalan('INSERT INTO usr_users (email) VALUES (?)', [$surel('c')]) === 0 && jalan('INSERT INTO usr_users (email) VALUES (?)', [$surel('d')]) === 0,
    'username NULL boleh lebih dari satu');

// ------------------------------------------------------------------ 5. diagnostik
$status = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/index.php') . ' migrate status 2>&1');
cek(preg_match('/^FK \+ indeks integritas \(migrasi 069\): TERPASANG \(13 FK, 11 indeks, email UNIQUE tunggal\)$/m', $status) === 1,
    'Migrate::status() melaporkan bentuk 069 TERPASANG');

// ------------------------------------------------------------------ 6. kode yang bergantung pada FK
$aso = file_get_contents($root . '/application/controllers/Admin_Asosiasi.php');
cek(substr_count($aso, "'psu_serah_terima'") >= 2, 'Admin_Asosiasi menghitung pemakaian di psu_serah_terima (index dan hapus), bukan hanya ditolak FK');
cek(strpos(file_get_contents($root . '/application/models/Housing_assessment_model.php'), "count_all_results('kabupaten')") !== FALSE,
    'cermin SIMPERUM menolkan kabupaten_id yang tidak ada di tabel kabupaten sebelum upsert');

// ------------------------------------------------------------------ 7. alur hapus lewat HTTP
// Hapus akun (Pengaturan::delete_account) dan hapus asosiasi (Admin_Asosiasi::hapus) dijalankan
// sungguhan di aplikasi lokal, karena dua alur itu yang paling dulu kena kalau aturan FK salah.
$audit0 = (int) nilai('SELECT IFNULL(MAX(id), 0) FROM sys_jejak_audit');
$base = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/');
$sandi = 'UjiFk069!' . substr($tag, 3);
$jar = [];
$http = function ($nama, $path, ?array $post = NULL, $ajax = FALSE) use ($base, &$jar) {
    $jar[$nama] = $jar[$nama] ?? tempnam(sys_get_temp_dir(), 'uj69_');
    $ch = curl_init($base . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $jar[$nama], CURLOPT_COOKIEFILE => $jar[$nama],
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30]
        + ($ajax ? [CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']] : [])
        + ($post !== NULL ? [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
    $body = (string) curl_exec($ch);
    $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['body' => $body, 'url' => $url];
};
$csrf = function ($nama, $path) use ($http) {
    $b = $http($nama, $path)['body'];
    return preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $b, $mm) || preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $b, $mm) ? $mm[1] : '';
};
$masuk = function ($nama, $email) use ($http, $csrf, $sandi) {
    $r = $http($nama, 'Auth/do_login', ['csrf_kpkp_token' => $csrf($nama, 'Auth/login'), 'email' => $email, 'password' => $sandi], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
};
$akun = function ($peran, $s) use ($surel, $sandi, $tag) {
    jalan("INSERT INTO usr_users (email, password, name, username, role, status, profile_completed, phone, created_at, password_changed_at)
        VALUES (?, ?, ?, ?, ?, 'active', 1, '081200000069', NOW(), NOW())", [$surel($s), password_hash($sandi, PASSWORD_BCRYPT), 'Uji ' . $s, $tag . $s, $peran]);
    return id_terakhir();
};

$hw = $akun('warga', 'hapus');
jalan("INSERT INTO forum_diskusi (nama_user, email_user, judul_topik, kategori, isi_diskusi, user_id, created_at) VALUES ('Uji', ?, ?, 'umum', 'isi', ?, NOW())", [$surel('hapus'), $tag . ' topik http', $hw]);
$hd = id_terakhir();
jalan("INSERT INTO forum_komentar (id_diskusi, nama_komentator, isi_komentar, user_id, role, created_at) VALUES (?, 'Uji', ?, ?, 'Warga', NOW())", [$hd, $tag . ' komentar http', $hw]);
$hk = id_terakhir();
jalan("INSERT INTO forum_likes (user_id, target_type, target_id) VALUES (?, 'diskusi', ?)", [$hw, $hd]);
if (cek($hw > 0 && $masuk('w', $surel('hapus')), 'HTTP: warga uji masuk')) {
    $http('w', 'akun/delete', ['csrf_kpkp_token' => $csrf('w', 'akun/profil'), 'current_password' => $sandi]);
    cek((int) nilai('SELECT COUNT(*) FROM usr_users WHERE id = ?', [$hw]) === 0, 'HTTP hapus akun (Pengaturan::delete_account): akun terhapus');
    cek((int) nilai("SELECT COUNT(*) FROM forum_diskusi WHERE id_diskusi = ? AND user_id IS NULL AND email_user = 'akun-dihapus@invalid'", [$hd]) === 1
        && (int) nilai("SELECT COUNT(*) FROM forum_komentar WHERE id_komentar = ? AND user_id IS NULL AND nama_komentator = 'Akun Dihapus'", [$hk]) === 1,
        'HTTP hapus akun: topik dan komentar bertahan teranonim');
    cek((int) nilai('SELECT COUNT(*) FROM forum_likes WHERE user_id = ?', [$hw]) === 0, 'HTTP hapus akun: tanda suka terhapus');
}

$adm = $akun('admin', 'admin');
jalan("INSERT INTO srp2_asosiasi (kode, nama, created_at) VALUES (?, ?, NOW())", [$tag . 'h', $tag . ' asosiasi http']);
$asoid = id_terakhir();
jalan("INSERT INTO psu_serah_terima (nama_perumahan, nama_pengembang, asosiasi) VALUES (?, 'Uji', ?)", [$tag . ' psu http', $tag . 'h']);
$psu2 = id_terakhir();
if (cek($adm > 0 && $asoid > 0 && $psu2 > 0 && $masuk('a', $surel('admin')), 'HTTP: admin uji masuk, asosiasi uji dipakai satu baris PSU')) {
    $r = $http('a', 'Admin_Asosiasi/hapus', ['csrf_kpkp_token' => $csrf('a', 'Admin_Asosiasi'), 'id' => $asoid]);
    cek((int) nilai('SELECT COUNT(*) FROM srp2_asosiasi WHERE id = ?', [$asoid]) === 1 && strpos($r['body'], 'masih dipakai 1 data') !== FALSE,
        'HTTP hapus asosiasi yang dipakai PSU: ditolak dengan pesan yang terbaca, bukan "dihapus"');
    jalan('DELETE FROM psu_serah_terima WHERE id = ?', [$psu2]);
    $http('a', 'Admin_Asosiasi/hapus', ['csrf_kpkp_token' => $csrf('a', 'Admin_Asosiasi'), 'id' => $asoid]);
    cek((int) nilai('SELECT COUNT(*) FROM srp2_asosiasi WHERE id = ?', [$asoid]) === 0, 'HTTP hapus asosiasi yang tidak dipakai: terhapus');
}
foreach ($jar as $f) { @unlink($f); }
// Jejak audit yang lahir dari dua akun uji ini saja (pelaku admin uji, atau pelaku NULL = warga uji yang sudah dihapus).
$m->query('DELETE FROM sys_jejak_audit WHERE id > ' . $audit0 . ' AND (actor_id = ' . (int) $adm . " OR (actor_id IS NULL AND IFNULL(actor_role, '') <> 'sistem'))");

$bersih();
cek((int) nilai("SELECT COUNT(*) FROM usr_users WHERE email LIKE ?", ['%' . $tag . '@example.test']) === 0, 'data uji bersih');

echo "\nHasil: " . ($total - $gagal) . "/$total OK\n";
exit($gagal ? 1 : 0);
