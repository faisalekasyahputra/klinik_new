<?php
/**
 * Uji AKSES STAF — `Admin_Users::ubah_status/buka_kunci/reset_sandi` dan layar
 * bacanya `Admin_Audit`.
 *
 *   php docs/engineering/uji_akses_staf.php
 *
 * Layar ini lahir 3 Agt 2026 sebagai satu-satunya cara mencabut akses tanpa
 * menyentuh DB. Yang dijaga di sini bukan "tombolnya ada", melainkan lima janji
 * yang rusaknya SENYAP — semuanya tetap membalas 200 dan tetap terlihat benar
 * dari layar:
 *
 *   1. SAKLARNYA TIDAK BERBOHONG. `status` sempat cuma DITULIS, tidak pernah
 *      DIBACA saat login. Menonaktifkan akun mengubah badge dan tidak mengubah
 *      apa pun yang lain. Karena itu tiap perubahan status di sini diikuti
 *      LOGIN SUNGGUHAN, bukan sekadar SELECT ke kolomnya.
 *   2. PELINDUNGNYA BENAR-BENAR MENAHAN. Superadmin terakhir dan akun sendiri
 *      tidak boleh dicabut aksesnya; keduanya tidak punya jalan pulih lewat
 *      aplikasi.
 *   3. PERAN LAIN TIDAK BISA MENYENTUHNYA — dan buktinya keadaan DB, BUKAN kode
 *      HTTP. Permintaan non-AJAX dari peran yang salah DIALIHKAN, jadi curl yang
 *      mengikuti redirect menerima 200 dari halaman lain. Uji yang berhenti di
 *      "kodenya bukan 200" akan hijau untuk endpoint yang sepenuhnya terbuka.
 *   4. JEJAKNYA LENGKAP DUA ARAH. Yang berhasil DAN yang ditolak. Jejak yang
 *      cuma merekam keberhasilan tidak bisa menjawab "siapa yang mencoba
 *      mematikan panel ini" — dan justru itu yang perlu terlihat.
 *   5. SANDI TIDAK IKUT TERCATAT. Jejak audit dibaca orang yang tidak selalu
 *      berhak tahu isinya. Diperiksa dengan menyapu SEMUA kolom, bukan hanya
 *      kolom yang kebetulan diingat.
 *
 * Data & akun dibuat sendiri, dibersihkan sendiri (termasuk baris
 * `sys_jejak_audit` yang ditimbulkannya). Email uji berakhiran @example.test
 * supaya sensus kebocoran di jalankan_semua.php bisa melihatnya.
 *
 * PERINGATAN SATU-SATUNYA: pemeriksaan "superadmin terakhir" TIDAK BISA
 * dipentaskan tanpa menyentuh baris nyata. Penjaganya menghitung superadmin
 * yang MASIH BISA MASUK di seluruh tabel, jadi selama masih ada satu superadmin
 * lain yang aktif, cabang itu tidak pernah tereksekusi — termasuk akun yang
 * sedang menjalankan tindakannya sendiri. Karena itu berkas ini menonaktifkan
 * SEMENTARA setiap superadmin lain, mengirim SATU permintaan, lalu memulihkan
 * nilainya persis semula (dan memulihkannya lagi lewat shutdown handler kalau
 * prosesnya mati di tengah). Perintah pemulihan manualnya dicetak SEBELUM
 * jendela itu dibuka — kalau sesuatu terjadi, barisnya sudah ada di layar.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('CAP', 'UJI-AKSES-' . date('YmdHis'));

define('SANDI', 'UjiAkses!2026');
define('SANDI_BARU', 'SandiBaru!' . date('YmdHis'));
define('SANDI_PENDEK', 'abc1234');           // 7 karakter — harus ditolak.

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['emails'] = [];
$GLOBALS['jar'] = [];
$GLOBALS['pulih_admin'] = [];   // id => status asli, selama jendela "superadmin terakhir"

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) {
        bersihkan();
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        exit(1);
    }
}

/** Parser .env milik index.php: kemunculan PERTAMA menang, baris '#' dilewati. */
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        $k = trim($k);
        if ( ! isset($out[$k])) { $out[$k] = trim($v); }
    }
    return $out;
}

function q($sql, $params = []) {
    $stmt = $GLOBALS['db']->prepare($sql);
    if ( ! $stmt) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = $res ? $res->fetch_assoc() : NULL;
    $id = $stmt->insert_id;
    $stmt->close();
    return $out ?: ['__id' => $id];
}

function tulis($sql, $params = []) { return (int) (q($sql, $params)['__id'] ?? 0); }
function nilai($sql, $params = []) { $r = q($sql, $params); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function kolom($id, $nama) { return nilai("SELECT `{$nama}` FROM usr_users WHERE id=?", [$id]); }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) {
        $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujas_');
    }
    return $GLOBALS['jar'][$nama];
}

function http($nama, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => sesi($nama),
        CURLOPT_COOKIEFILE     => sesi($nama),
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT        => 30,
    ];
    if ($ajax) { $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => (int) $info['http_code'], 'body' => $body, 'url' => (string) $info['url']];
}

/**
 * Token CSRF dari DUA bentuk yang beredar di repo ini, dan BERHENTI FATAL kalau
 * tidak ketemu.
 *
 * Kenapa dua: shell admin (`admin/layouts/head.php`) TIDAK memasang meta CSRF —
 * tokennya cuma ada sebagai <input> di dalam formulir. Shell portal
 * (`layouts/head.php`) sebaliknya: meta ada, formulir tidak selalu. Peran non-
 * admin yang hendak menembak endpoint admin cuma bisa mengambil tokennya dari
 * meta portal, jadi harness yang hanya mengenal satu bentuk tidak akan pernah
 * bisa membuktikan butir 8 sama sekali.
 *
 * Kenapa fatal, bukan string kosong: POST tanpa token DITOLAK CSRF sebelum
 * controller mana pun berjalan — dan penolakan itu tidak mengubah apa-apa. Uji
 * tulis yang kehilangan tokennya karena itu jadi HIJAU HAMPA: ia mengira sudah
 * membuktikan "endpointnya menahan", padahal yang ditahan cuma tokennya sendiri.
 */
function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }

    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path} (sesi '{$nama}', kode {$r['code']}).\n"
        . "Tanpa token, setiap POST di berkas ini ditolak CSRF dan seluruh uji tulis lulus hampa.\n");
    exit(1);
}

/**
 * Login dengan JAR BARU tiap panggilan.
 *
 * Sesi lama yang masih hidup membuat "berhasil masuk" tidak berarti apa-apa:
 * gerbang status/kunci hanya dibaca SAAT login, jadi memakai ulang jar akan
 * membuat akun yang sudah dinonaktifkan tetap terlihat bisa bekerja.
 *
 * Header AJAX wajib: hanya cabang itu yang membalas JSON. Tanpa itu balasannya
 * HTML, json_decode NULL, dan harness melaporkan "gagal" untuk login yang
 * sebenarnya berhasil.
 */
function coba_login($email, $sandi) {
    static $n = 0;
    $nama = 'coba' . (++$n);
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $sandi,
    ], TRUE);
    $j = json_decode($r['body'], TRUE);
    return [($j['status'] ?? '') === 'success', (string) ($j['message'] ?? '')];
}

/** Login yang sesinya DIPAKAI seterusnya (pelaku tindakan). */
function login($nama, $email, $sandi = SANDI) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $sandi,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix) {
    $email = 'uji_akses_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
         VALUES (?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Akses ' . $suffix,
         'uji_akses_' . $suffix . '_' . mt_rand(10000, 99999), $peran]
    );
    $GLOBALS['users'][] = $id;
    $GLOBALS['emails'][] = $email;
    return [$id, $email];
}

/** Berapa baris jejak untuk satu aksi atas satu objek, sejak uji ini mulai. */
function jejak($aksi, $objek_id) {
    return (int) nilai(
        "SELECT COUNT(*) c FROM sys_jejak_audit
         WHERE aksi=? AND objek_tipe='usr_users' AND objek_id=? AND created_at >= ?",
        [$aksi, (string) $objek_id, MULAI]);
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }

    // Pemulihan superadmin lain didahulukan: kalau prosesnya mati di dalam
    // jendela, ini satu-satunya yang benar-benar mendesak.
    foreach ($GLOBALS['pulih_admin'] as $id => $status) {
        q('UPDATE usr_users SET status=? WHERE id=?', [$status, $id]);
    }
    $GLOBALS['pulih_admin'] = [];

    // Jejak audit dihapus SEBELUM akunnya: FK-nya ON DELETE SET NULL, jadi
    // menghapus user duluan mengosongkan actor_id dan barisnya jadi lebih sulit
    // dikenali. Dicocokkan lewat email pelaku DAN objek, karena satu tindakan
    // bisa punya salah satunya saja.
    foreach ($GLOBALS['users'] as $id) {
        q("DELETE FROM sys_jejak_audit WHERE objek_tipe='usr_users' AND objek_id=?", [(string) $id]);
    }
    foreach ($GLOBALS['emails'] as $email) {
        q('DELETE FROM sys_jejak_audit WHERE actor_email=?', [$email]);
    }
    foreach ($GLOBALS['users'] as $id) { q('DELETE FROM usr_users WHERE id=?', [$id]); }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

// Satu detik mundur: created_at berpresisi detik, dan baris pertama bisa lahir
// pada detik yang sama dengan penanda ini.
define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI AKSES STAF (nonaktif / kunci / reset sandi / jejak audit) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

wajib((int) nilai("SELECT COUNT(*) c FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sys_jejak_audit'") === 1,
    'Tabel sys_jejak_audit ada (migrasi 20260701000033 sudah jalan)');

[$idA, $emailA] = buat_akun('admin', 'pelaku');        // superadmin pelaku
[$idB, $emailB] = buat_akun('admin', 'sasaran_super'); // sasaran pelindung "superadmin terakhir"
[$idS, $emailS] = buat_akun('admin_bidang', 'staf');   // akun staf yang dikelola
[$idM, $emailM] = buat_akun('mahasiswa', 'mahasiswa'); // peran yang tidak berhak

wajib(login('a', $emailA), 'Login superadmin pelaku');
$layar = http('a', 'Admin_Users');
wajib($layar['code'] === 200 && strpos($layar['url'], 'Auth/login') === FALSE
    && strpos($layar['body'], 'Daftar Pengguna') !== FALSE, 'Layar Manajemen Pengguna terbuka');

[$masuk, ] = coba_login($emailS, SANDI);
wajib($masuk, 'Akun staf bisa masuk sebelum diapa-apakan (dasar pembanding)');

// ===================================================== 1. NONAKTIF = TIDAK MASUK
echo "\n== 1. Dinonaktifkan: bukan cuma kolomnya yang berubah ==\n";
$r = http('a', 'Admin_Users/ubah_status', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idS, 'status' => 'nonaktif',
]);
cek($r['code'] === 200, 'Permintaan nonaktifkan diterima server');
cek(kolom($idS, 'status') === 'nonaktif', 'Kolom status menjadi nonaktif');
[$masuk, $pesan] = coba_login($emailS, SANDI);
cek( ! $masuk, 'Akun nonaktif BENAR-BENAR tidak bisa login lagi');
cek(stripos($pesan, 'dinonaktifkan') !== FALSE, 'Penolakannya menyebut sebab yang benar (bukan "password salah")');

// ===================================================== 2. AKTIF LAGI = BISA MASUK
echo "\n== 2. Diaktifkan kembali ==\n";
http('a', 'Admin_Users/ubah_status', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idS, 'status' => 'active',
]);
cek(kolom($idS, 'status') === 'active', 'Kolom status kembali active');
[$masuk, ] = coba_login($emailS, SANDI);
cek($masuk, 'Akun yang diaktifkan kembali bisa login');

// ===================================================== 9. GET TIDAK MENULIS
echo "\n== 9. GET ke endpoint tulis ditolak ==\n";
foreach (['ubah_status', 'buka_kunci', 'reset_sandi'] as $ep) {
    cek(http('a', 'Admin_Users/' . $ep . '?id=' . $idS . '&status=nonaktif')['code'] === 404,
        "GET Admin_Users/{$ep} dibalas 404 — hanya POST yang boleh menulis");
}
cek(kolom($idS, 'status') === 'active', 'Tiga GET tadi tidak mengubah status akun staf');

// ===================================================== 10. POST TANPA CSRF
echo "\n== 10. POST tanpa token CSRF ==\n";
$hash_sebelum = (string) kolom($idS, 'password');
http('a', 'Admin_Users/ubah_status', ['id' => $idS, 'status' => 'nonaktif']);
http('a', 'Admin_Users/reset_sandi', ['id' => $idS, 'password' => SANDI_BARU]);
cek(kolom($idS, 'status') === 'active', 'POST ubah_status tanpa token tidak mengubah status');
cek((string) kolom($idS, 'password') === $hash_sebelum, 'POST reset_sandi tanpa token tidak mengubah sandi');

// ===================================================== 3a. TERKUNCI
echo "\n== 3. Akun terkunci ==\n";
/**
 * Kunci dipasang LANGSUNG ke DB, bukan dengan lima kali salah sandi lewat HTTP.
 * Yang diuji berkas ini adalah tombol BUKA KUNCI-nya; menempuh ulang mekanisme
 * penguncian cuma menyalin cakupan uji lain dan menambah sebab merah yang bukan
 * miliknya.
 */
q('UPDATE usr_users SET login_attempts=5, locked_until=DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id=?', [$idS]);
[$masuk, $pesan] = coba_login($emailS, SANDI);
cek( ! $masuk, 'Akun terkunci tidak bisa login walau sandinya benar');
cek(stripos($pesan, 'terkunci') !== FALSE, 'Penolakannya menyebut kunci, bukan sandi salah');

// ===================================================== 8. PERAN LAIN NIHIL EFEK
echo "\n== 8. Peran bukan admin: dibuktikan lewat DB, bukan kode HTTP ==\n";
wajib(login('m', $emailM), 'Login mahasiswa');
$tok_m = csrf('m', '/');   // token dari meta portal — shell admin tidak memasangnya
$hash_sebelum = (string) kolom($idS, 'password');
$kunci_sebelum = (string) kolom($idS, 'locked_until');

$tolak = http('m', 'Admin_Users/ubah_status', ['csrf_kpkp_token' => $tok_m, 'id' => $idS, 'status' => 'nonaktif']);
http('m', 'Admin_Users/buka_kunci', ['csrf_kpkp_token' => $tok_m, 'id' => $idS]);
http('m', 'Admin_Users/reset_sandi', ['csrf_kpkp_token' => $tok_m, 'id' => $idS, 'password' => SANDI_BARU]);

// Sengaja dicatat sebagai pemeriksaan tersendiri: inilah alasan sisa blok ini
// membaca DB. Peran yang salah DIALIHKAN, dan curl yang mengikuti redirect
// menerima 200 dari halaman lain — "bukan 200" bukan bukti apa pun di sini.
cek($tolak['code'] === 200 && strpos($tolak['url'], 'Admin_Users') === FALSE,
    'Permintaan peran salah dibalas 200 dari halaman lain (kode HTTP tidak membuktikan apa-apa)');
cek(kolom($idS, 'status') === 'active', 'Mahasiswa tidak berhasil menonaktifkan akun staf');

/* BUTIR 24 PUTARAN 2 — yang salah peran TIDAK BOLEH dilempar ke halaman masuk.
   Sampai 10 Agt 2026 keduanya diperlakukan sama, sehingga orang yang sudah
   login diminta login lagi tanpa penjelasan. Sekarang ia mendarat di layar yang
   menyebutkan bahwa yang tidak cocok adalah PERANNYA, bukan sesinya.

   Diperiksa dari ISI HALAMAN, bukan kode HTTP — alasannya sama dengan blok di
   atas: permintaan yang dialihkan tetap berbalas 200 dari halaman lain. */
$salah_peran = http('m', 'Admin_Users');
cek(strpos($salah_peran['url'], 'Auth/login') === FALSE,
    'Yang SUDAH login tidak dilempar ke halaman masuk lagi');
cek(strpos($salah_peran['body'], 'bukan untuk peran Anda') !== FALSE,
    'Mendarat di layar akses ditolak yang menjelaskan sebabnya');
cek(strpos($salah_peran['body'], 'tidak perlu login ulang') !== FALSE,
    'Layar itu menegaskan sesinya masih sehat — inti kebingungannya');
cek(strpos($salah_peran['body'], 'Auth/logout') !== FALSE,
    'Ada jalan keluar ke akun lain, jadi bukan jalan buntu');
cek((string) kolom($idS, 'locked_until') === $kunci_sebelum && (int) kolom($idS, 'login_attempts') === 5,
    'Mahasiswa tidak berhasil membuka kunci akun staf');
cek((string) kolom($idS, 'password') === $hash_sebelum, 'Mahasiswa tidak berhasil mengganti sandi akun staf');
cek((int) nilai('SELECT COUNT(*) c FROM sys_jejak_audit WHERE actor_id=? AND created_at >= ?', [$idM, MULAI]) === 0,
    'Nol jejak audit atas nama mahasiswa (tindakannya memang tidak pernah berjalan)');

// ===================================================== 3b. BUKA KUNCI
http('a', 'Admin_Users/buka_kunci', ['csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idS]);
cek((int) kolom($idS, 'login_attempts') === 0 && kolom($idS, 'locked_until') === NULL,
    'buka_kunci mengosongkan penghitung gagal dan masa kunci');
[$masuk, ] = coba_login($emailS, SANDI);
cek($masuk, 'Setelah buka_kunci, akun bisa login lagi');

// ===================================================== 5. SANDI TERLALU PENDEK
echo "\n== 5. Reset sandi < 8 karakter ditolak ==\n";
$hash_sebelum = (string) kolom($idS, 'password');
http('a', 'Admin_Users/reset_sandi', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idS, 'password' => SANDI_PENDEK,
]);
cek((string) kolom($idS, 'password') === $hash_sebelum, 'Sandi pendek ditolak dan HASH TIDAK BERUBAH');
cek(jejak('sandi_direset', $idS) === 0, 'Penolakan tidak meninggalkan jejak "sandi_direset" palsu');
[$masuk, ] = coba_login($emailS, SANDI_PENDEK);
cek( ! $masuk, 'Sandi pendek yang ditolak tidak pernah berlaku');

// ===================================================== 4. RESET SANDI SAH
echo "\n== 4. Reset sandi sah ==\n";
http('a', 'Admin_Users/reset_sandi', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idS, 'password' => SANDI_BARU,
]);
cek((string) kolom($idS, 'password') !== $hash_sebelum, 'Hash sandi berubah');
[$masuk, ] = coba_login($emailS, SANDI_BARU);
cek($masuk, 'Sandi BARU berlaku');
[$masuk, ] = coba_login($emailS, SANDI);
cek( ! $masuk, 'Sandi LAMA tidak berlaku lagi');

// ===================================================== 7. AKUN SENDIRI
echo "\n== 7. Pelindung: akun sendiri tidak bisa dinonaktifkan ==\n";
$r = http('a', 'Admin_Users/ubah_status', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idA, 'status' => 'nonaktif',
]);
cek(kolom($idA, 'status') === 'active', 'Superadmin tidak bisa menonaktifkan akunnya sendiri');
cek(jejak('tindakan_diri_sendiri_ditolak', $idA) === 1, 'Percobaan atas akun sendiri tercatat sebagai _ditolak');
[$masuk, ] = coba_login($emailA, SANDI);
cek($masuk, 'Pelaku masih bisa masuk sesudah percobaan itu ditolak');

// ============================================ 6. PENGUNCIAN TOTAL PANEL
echo "
== 6. Pelindung: panel tidak bisa dikunci dari semua orang ==
";
/**
 * YANG DIUJI DI SINI BUKAN tombol Nonaktifkan, melainkan UBAH ROLE.
 *
 * Versi pertama berkas ini memanggungkan penjaga superadmin-terakhir di
 * `ubah_status` dengan menonaktifkan setiap superadmin lain untuk satu
 * permintaan. Dua hal membuatnya tidak sahih:
 *
 * 1. Cabang itu TIDAK BISA DICAPAI lewat HTTP. Pelakunya sendiri selalu
 *    terhitung sebagai "admin lain yang masih bisa masuk" — menurut definisi ia
 *    sedang masuk — jadi target tidak pernah menjadi yang terakhir. Kalau
 *    targetnya diri sendiri, penjaga akun-sendiri menyala lebih dulu.
 * 2. Memarkir SEMUA superadmin lain ikut memarkir pelakunya, dan sejak
 *    pemeriksaan status per-permintaan dipasang di MY_Controller, itu memutus
 *    sesi pelaku di tengah jalan. Uji yang harus membunuh dirinya sendiri untuk
 *    memanggungkan sebuah cabang sedang memberi tahu bahwa cabang itu fiktif.
 *
 * Lubang penguncian yang BENAR-BENAR bisa terjadi ada di `update_role`:
 * superadmin menurunkan role DIRINYA SENDIRI menjadi warga. Tidak ada penjaga
 * akun-sendiri di sana — "mengubah role" tidak terlihat seperti mencabut akses
 * sampai akibatnya terjadi — dan sesudahnya tidak ada satu akun pun yang bisa
 * membuka panel, termasuk untuk membatalkannya.
 *
 * Dipanggungkan dengan memarkir superadmin lain SELAIN pelaku, jadi sesi
 * pelaku tetap hidup dan skenarionya persis seperti di dunia nyata: satu-satunya
 * superadmin menurunkan dirinya sendiri.
 */
$lain = [];
$res = $GLOBALS['db']->query("SELECT id, status FROM usr_users WHERE role='admin' AND id <> " . (int) $idA);
foreach ($res ?: [] as $row) { $lain[(int) $row['id']] = $row['status']; }

echo "  ! " . count($lain) . " superadmin lain diparkir SEMENTARA (pelaku TIDAK ikut diparkir).
";
echo "    Pulihkan manual bila proses ini mati di tengah:
";
foreach ($lain as $id => $st) {
    echo "      UPDATE usr_users SET status=" . ($st === NULL ? 'NULL' : "'" . $st . "'") . " WHERE id={$id};
";
}
$GLOBALS['pulih_admin'] = $lain;
foreach (array_keys($lain) as $id) { q("UPDATE usr_users SET status='nonaktif' WHERE id=?", [$id]); }

$r = http('a', 'Admin_Users/update_role', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Users'), 'id' => $idA, 'role' => 'warga',
]);

foreach ($lain as $id => $st) { q('UPDATE usr_users SET status=? WHERE id=?', [$st, $id]); }
$GLOBALS['pulih_admin'] = [];

cek(kolom($idA, 'role') === 'admin',
    'Superadmin TERAKHIR tidak bisa menurunkan role dirinya sendiri');
cek(jejak('role_diubah_ditolak', $idA) === 1, 'Percobaan itu tercatat sebagai _ditolak');
cek(jejak('role_diubah', $idA) === 0, 'Tidak ada jejak keberhasilan yang menyertainya');
cek(strpos($r['body'], 'satu-satunya Super Admin') !== FALSE,
    'Alasannya disampaikan ke pelaku, bukan gagal senyap');
[$masuk, ] = coba_login($emailA, SANDI);
cek($masuk, 'Pelaku masih bisa masuk — panelnya selamat');
$pulih = TRUE;
foreach ($lain as $id => $st) { if (kolom($id, 'status') !== $st) { $pulih = FALSE; } }
cek($pulih, 'Status superadmin lain dipulihkan persis semula');

// ===================================================== 11 & 12. JEJAK AUDIT
echo "\n== 11-12. Jejak audit: yang berhasil DAN yang ditolak ==\n";
foreach (['akun_dinonaktifkan' => $idS, 'akun_diaktifkan' => $idS,
          'kunci_dibuka' => $idS, 'sandi_direset' => $idS] as $aksi => $objek) {
    cek(jejak($aksi, $objek) === 1, "Jejak '{$aksi}' tercatat tepat satu kali");
}
foreach (['tindakan_diri_sendiri_ditolak' => $idA, 'role_diubah_ditolak' => $idA] as $aksi => $objek) {
    cek(jejak($aksi, $objek) === 1, "Jejak percobaan DITOLAK '{$aksi}' tercatat");
}
cek((int) nilai('SELECT COUNT(*) c FROM sys_jejak_audit WHERE actor_id=? AND created_at >= ? AND (actor_email<>? OR actor_role<>?)',
    [$idA, MULAI, $emailA, 'admin']) === 0,
    'Pelaku setiap jejak diambil dari sesi (email + role tersalin apa adanya)');

// ===================================================== 13. SANDI TIDAK BOCOR
echo "\n== 13. Sandi tidak pernah masuk jejak audit ==\n";
/**
 * Disapu SEMUA kolom lewat CONCAT_WS, bukan hanya `ringkasan` dan `detail_json`.
 * Kebocoran yang mungkin terjadi justru lewat kolom yang tidak diingat saat
 * menulis ujinya — dan menyebutkan kolom satu per satu berarti daftar itu harus
 * ikut diperbarui setiap kali skemanya tumbuh.
 */
$sapu = "SELECT COUNT(*) c FROM sys_jejak_audit WHERE CONCAT_WS('|',
    COALESCE(actor_email,''), COALESCE(actor_role,''), aksi, COALESCE(objek_tipe,''),
    COALESCE(objek_id,''), ringkasan, COALESCE(detail_json,''), COALESCE(ip,'')) LIKE ?";
foreach (['sandi baru' => SANDI_BARU, 'sandi awal' => SANDI, 'sandi pendek' => SANDI_PENDEK] as $label => $s) {
    cek((int) nilai($sapu, ['%' . $s . '%']) === 0, "Nol baris jejak memuat {$label}");
}
cek((int) nilai($sapu, ['%$2y$%']) === 0, 'Nol baris jejak memuat hash bcrypt');

// ===================================================== 14. LAYAR JEJAK AUDIT
echo "\n== 14-15. Layar Admin_Audit ==\n";
/**
 * Dicari lewat ?q= supaya barisnya tidak perlu kebetulan berada di halaman 1,
 * dan yang dicocokkan adalah RINGKASAN UTUH — bukan alamat emailnya saja.
 * Kotak pencarian ikut mencetak kata kunci yang diketik, jadi memeriksa
 * "emailnya ada di halaman" akan hijau bahkan ketika nol baris ditemukan.
 */
$ringkasan_s = 'Menonaktifkan akun ' . $emailS;
$ringkasan_b = 'DITOLAK: menurunkan role Super Admin terakhir (' . $emailA . ')';

$audit = http('a', 'Admin_Audit?q=' . urlencode($emailS));
cek($audit['code'] === 200 && strpos($audit['url'], 'Admin_Audit') !== FALSE, 'Superadmin membuka layar Jejak Audit');
cek(strpos($audit['body'], 'Riwayat Tindakan') !== FALSE, 'Layarnya benar-benar layar jejak audit');
cek(strpos($audit['body'], $ringkasan_s) !== FALSE, 'Ringkasan tindakan yang barusan dicatat tampil apa adanya');

$audit_b = http('a', 'Admin_Audit?q=' . urlencode($emailA));
cek(strpos($audit_b['body'], $ringkasan_b) !== FALSE, 'Percobaan yang DITOLAK ikut tampil di layar yang sama');

// ===================================================== 15. PERAN LAIN
$audit_m = http('m', 'Admin_Audit?q=' . urlencode($emailS));
cek(strpos($audit_m['body'], $ringkasan_s) === FALSE
    && strpos($audit_m['body'], 'Riwayat Tindakan') === FALSE
    && strpos($audit_m['url'], 'Admin_Audit') === FALSE,
    'Mahasiswa tidak kebagian ISI layar jejak audit (dibuktikan dari isinya, bukan kodenya)');
cek($audit_m['code'] === 200, 'Padahal balasannya tetap 200 — sekali lagi, kode HTTP bukan bukti');

// ===================================================== BERSIH
echo "\n== Bersih-bersih ==\n";
bersihkan();
$sisa_akun = (int) nilai('SELECT COUNT(*) c FROM usr_users WHERE email LIKE ?', ['uji_akses_%@example.test']);
$sisa_jejak = (int) nilai('SELECT COUNT(*) c FROM sys_jejak_audit WHERE actor_email LIKE ?', ['uji_akses_%@example.test']);
$GLOBALS['users'] = $GLOBALS['emails'] = [];
cek($sisa_akun === 0, 'Akun uji dibersihkan');
cek($sisa_jejak === 0, 'Baris sys_jejak_audit buatan uji ini dibersihkan');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
