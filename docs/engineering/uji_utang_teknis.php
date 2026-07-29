<?php
/**
 * Check pelunasan utang teknis — tahap U1 (butir C1, C1b, A5).
 *
 *   php docs/engineering/uji_utang_teknis.php
 *
 * WAJIB dijalankan terhadap worktree + DB uji, bukan aplikasi dev. Skrip ini
 * ABORT kalau `.env` yang terbaca menunjuk nama DB tanpa akhiran `_utang`,
 * atau kalau host-nya bukan localhost.
 *
 * Yang dibuktikan:
 *  C1  — pembatas laju forum benar-benar membatasi. Sebelum perbaikan,
 *        `count_all_results('diskusi')` selalu errno 1146 → FALSE, dan
 *        `FALSE < 5` bernilai TRUE, jadi pembatasnya fiktif.
 *  C1b — perilaku yang sama diulang dengan `db_debug` FALSE, meniru production.
 *        Ini yang membedakan check ini dari uji lokal biasa: dengan db_debug
 *        menyala kegagalan query berisik dan mudah terlihat; dimatikan, ia diam.
 *  A5  — enam titik yang dulu memasang flash sukses tanpa memeriksa hasil tulis
 *        kini melapor gagal saat tulisannya memang gagal.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_utang', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');

$total = 0;
$gagal = 0;

function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) {
        $gagal++;
    }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) {
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        bersihkan();
        exit(1);
    }
}

function env_values($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        if ( ! array_key_exists($k, $out)) {
            $out[$k] = trim($v);
        }
    }
    return $out;
}

$env = env_values(ENV_PATH);

// -------------------------------------------------------------- pagar keras
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
    fwrite(STDERR, "DB_HOST bukan localhost — dibatalkan.\n");
    exit(1);
}
if (substr($env['DB_NAME'] ?? '', -6) !== '_utang') {
    fwrite(STDERR, "DB_NAME ({$env['DB_NAME']}) tidak berakhiran _utang.\n"
        . "Check ini menulis dan menghapus data; ia menolak berjalan di DB dev.\n");
    exit(1);
}

$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($db->connect_error) {
    fwrite(STDERR, "Koneksi DB gagal: {$db->connect_error}\n");
    exit(1);
}

function skalar_int($sql) {
    global $db;
    $r = $db->query($sql);
    if ( ! $r) {
        return 0;
    }
    // `reset()` menerima parameter BY REFERENCE, jadi hasil fetch harus ditaruh
    // di variabel dulu. Bentuk `reset($r->fetch_assoc())` memicu Notice yang
    // mencemari stdout dan membuat output harness tidak bisa dipercaya (§0e).
    $row = $r->fetch_assoc();
    return $row ? (int) reset($row) : 0;
}

// ---------------------------------------------------------------- HTTP
$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujiut_');
    }
    return $jars[$nama];
}

function http($nama, $path, ?array $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($nama),
        CURLOPT_COOKIEFILE => jar($nama), CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function csrf($nama, $path) {
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/',
        http($nama, $path)['body'], $m) ? $m[1] : '';
}

function login($nama, $email, $sandi) {
    return http($nama, 'Auth/do_login', ['csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $sandi])['code'] === 200;
}

// ------------------------------------------------- saklar db_debug lokal
$dbConfigPath = APP_ROOT . '/application/config/database.php';
$dbConfigAsli = file_get_contents($dbConfigPath);

/**
 * Menyetel db_debug SECARA EKSPLISIT di salinan uji — BUKAN lewat CI_ENV.
 * Memakai CI_ENV ikut mematikan tampilan error untuk semua sebab lain,
 * sehingga uji yang gagal karena hal lain jadi tak terlihat (§19 langkah 12).
 */
function db_debug($nyala) {
    global $dbConfigPath, $dbConfigAsli;
    $baru = preg_replace("/'db_debug'\s*=>\s*[^,]+,/",
        "'db_debug' => " . ($nyala ? 'TRUE' : 'FALSE') . ',', $dbConfigAsli, 1, $n);
    if ($n !== 1) {
        fwrite(STDERR, "Gagal mengalihkan db_debug.\n");
        exit(1);
    }
    file_put_contents($dbConfigPath, $baru, LOCK_EX);
}

function bersihkan() {
    global $db, $jars, $dbConfigPath, $dbConfigAsli;
    file_put_contents($dbConfigPath, $dbConfigAsli, LOCK_EX);
    $db->query("DELETE FROM forum_diskusi WHERE judul_topik LIKE 'Uji utang teknis%'");
    $db->query("DELETE FROM srp2_certified_developers WHERE nama_perusahaan LIKE 'Uji Utang%'");
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_utang_%'");
    foreach ($jars as $f) {
        @unlink($f);
    }
}

// ---------------------------------------------------------------- prasyarat
echo "Check utang teknis — U1 (C1, C1b, A5)\n";
echo "Sasaran: " . BASE_URL . " · DB {$env['DB_NAME']}\n";

$stamp = time();
$warga = "uji_utang_warga_{$stamp}@example.test";
$admin = "uji_utang_admin_{$stamp}@example.test";
$sandi = 'UjiUtang123!';
foreach ([[$warga, 'warga'], [$admin, 'admin']] as [$em, $role]) {
    $db->query(sprintf(
        "INSERT INTO usr_users (email, password, role, name, username, status)
         VALUES ('%s','%s','%s','Uji Utang','%s','active')",
        $db->real_escape_string($em),
        $db->real_escape_string(password_hash($sandi, PASSWORD_BCRYPT)),
        $db->real_escape_string($role),
        $db->real_escape_string(explode('@', $em)[0])));
}

try {
    wajib(http(NULL, '')['code'] === 200, 'Situs uji merespons');
    wajib(login('warga', $warga, $sandi), 'Login warga berhasil');
    wajib(login('admin', $admin, $sandi), 'Login admin berhasil');

    $posting = function ($nama, $n) {
        return http($nama, 'Umum/tambah_aksi', [
            'csrf_kpkp_token' => csrf($nama, 'Umum/forum'),
            'judul_topik' => "Uji utang teknis topik nomor {$n} untuk pembatas laju",
            'kategori' => 'Lainnya',
            'isi_diskusi' => 'Isi diskusi uji yang panjangnya melebihi dua puluh karakter.',
        ]);
    };

    // ------------------------------------------------ C1 (db_debug menyala)
    $sebelum = skalar_int("SELECT COUNT(*) c FROM forum_diskusi");
    $posting('warga', 1);
    cek(skalar_int("SELECT COUNT(*) c FROM forum_diskusi") === $sebelum + 1,
        'C1 — posting forum benar-benar mendarat di forum_diskusi');

    for ($i = 2; $i <= 5; $i++) {
        $posting('warga', $i);
    }
    cek(skalar_int("SELECT COUNT(*) c FROM forum_diskusi") === $sebelum + 5,
        'C1 — lima posting pertama diterima');

    $res = $posting('warga', 6);
    cek(skalar_int("SELECT COUNT(*) c FROM forum_diskusi") === $sebelum + 5,
        'C1 — posting KEENAM ditolak pembatas laju');
    cek(strpos($res['body'], 'terlalu sering') !== FALSE,
        'C1 — penolakannya dijelaskan ke pengguna');

    // ------------------------------------------------------- C1b (db_debug MATI)
    db_debug(FALSE);
    $sebelum2 = skalar_int("SELECT COUNT(*) c FROM forum_diskusi");
    $res = $posting('warga', 7);
    cek(skalar_int("SELECT COUNT(*) c FROM forum_diskusi") === $sebelum2,
        'C1b — dengan db_debug FALSE, posting ke-6+ TETAP ditolak');
    cek(strpos($res['body'], 'A PHP Error') === FALSE && strpos($res['body'], 'errno') === FALSE,
        'C1b — nol bocoran galat DB ke layar');

    // ------------------------------------------- A5 (tetap db_debug MATI)
    $jumlahUser = skalar_int("SELECT COUNT(*) c FROM usr_users");
    $res = http('admin', 'Admin_Users/create_staff', [
        'csrf_kpkp_token' => csrf('admin', 'Admin_Users'),
        'email' => $warga, 'name' => 'Duplikat', 'role' => 'warga', 'password' => $sandi,
    ]);
    cek(skalar_int("SELECT COUNT(*) c FROM usr_users") === $jumlahUser,
        'A5 — email duplikat: nol akun bertambah');
    // KOREKSI terhadap premis roadmap: butir A5 menyebut Admin_Users::create_staff()
    // sebagai titik paling berbahaya karena "email duplikat = INSERT ditolak senyap".
    // Ternyata tidak — form_validation sudah memasang `is_unique[usr_users.email]`,
    // jadi duplikat tertahan SEBELUM mencapai INSERT dan pesannya datang dari
    // validasi. Pemeriksaan hasil INSERT yang ditambahkan tetap benar sebagai
    // pertahanan lapis kedua (constraint lain, DB tumbang), tapi skenario yang
    // diramalkan roadmap tidak pernah terjadi. Uji ini karena itu menuntut
    // adanya PESAN GAGAL apa pun — bukan kata-kata tertentu yang saya karang.
    cek(stripos($res['body'], 'unique') !== FALSE
        || stripos($res['body'], 'sudah terdaftar') !== FALSE
        || stripos($res['body'], 'belum dibuat') !== FALSE,
        'A5 — layar mengatakan GAGAL, bukan "berhasil dibuat"');
    cek(stripos($res['body'], 'berhasil dibuat') === FALSE,
        'A5 — nol pesan sukses palsu di layar');

    $res = http('admin', 'Admin_Srp2/delete/999999', [
        'csrf_kpkp_token' => csrf('admin', 'Admin_Srp2')]);
    cek(stripos($res['body'], 'tidak ditemukan') !== FALSE,
        'A5 — hapus pengembang yang tidak ada dilaporkan gagal');
    cek(stripos($res['body'], 'dihapus dari daftar') === FALSE,
        'A5 — nol klaim "dihapus" untuk baris yang tidak ada');

    $res = http('admin', 'Umum/delete_diskusi', [
        'csrf_kpkp_token' => csrf('admin', 'Umum/forum'), 'id_diskusi' => 999999]);
    cek(stripos($res['body'], 'tidak ditemukan') !== FALSE
        || stripos($res['body'], 'sudah terhapus') !== FALSE,
        'A5 — hapus diskusi yang tidak ada dilaporkan gagal');
    cek(stripos($res['body'], 'berhasil dihapus') === FALSE,
        'A5 — nol klaim "berhasil dihapus" untuk id yang tidak ada');

    db_debug(TRUE);

    // ------------------------------------------------ U0: environment fail-closed
    // Dibuktikan lewat AKIBAT NYATA-nya, bukan dengan membaca diff: method uji
    // di Migrate menolak jalan saat ENVIRONMENT production. Jadi perintah yang
    // sama harus GAGAL tanpa CI_ENV dan BERHASIL dengan CI_ENV=development.
    $jalankan = function ($env) {
        $desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open([PHP_BINARY, 'index.php', 'migrate', 'uji_rekam_data_d1'],
            $desc, $pipes, APP_ROOT, $env === NULL ? NULL : array_merge(getenv(), $env));
        $out = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        return ['kode' => proc_close($proc), 'out' => $out];
    };

    $tanpa = $jalankan([]);
    cek(strpos($tanpa['out'], 'RINGKASAN') === FALSE,
        'U0 — CLI tanpa CI_ENV jatuh ke production: method uji menolak jalan');

    $dengan = $jalankan(['CI_ENV' => 'development']);
    cek(strpos($dengan['out'], 'RINGKASAN') !== FALSE,
        'U0 — CLI dengan CI_ENV=development berjalan normal');

    // CI_ENV kosong harus diperlakukan sebagai TIDAK ADA, bukan environment
    // bernama "" — kalau tidak, satu variabel kosong di server memulihkan
    // perilaku fail-open yang baru saja ditutup.
    $kosong = $jalankan(['CI_ENV' => '']);
    cek(strpos($kosong['out'], 'RINGKASAN') === FALSE,
        'U0 — CI_ENV kosong tetap dianggap production (fail-closed)');

    // B11: cookie mengikuti environment. Lewat HTTP (vhost development) tidak
    // boleh ber-Secure, karena browser akan menolaknya di http://localhost.
    $ch = curl_init(BASE_URL . '/Auth/login');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_HEADER => TRUE,
        CURLOPT_NOBODY => FALSE, CURLOPT_TIMEOUT => 30]);
    $header = substr((string) curl_exec($ch), 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
    curl_close($ch);
    preg_match_all('/^Set-Cookie:.*$/mi', $header, $ck);
    $adaCookie = ! empty($ck[0]);
    cek($adaCookie, 'B11 — server mengirim cookie sesi');
    cek($adaCookie && stripos(implode("\n", $ck[0]), 'Secure') === FALSE,
        'B11 — cookie lokal TANPA atribut Secure (sesi HTTP localhost tidak putus)');

    // ------------------------------------------------ U4: kejujuran permukaan
    // Bentuk buktinya adalah KETIADAAN: ambil respons anonim, cari literal yang
    // dicabut, hasilnya harus nol. Tanpa cookie sama sekali — halaman-halaman
    // ini publik, dan itulah yang membuatnya bisa dikutip siapa saja.
    foreach (['sebaran_rusun', 'profil_kumuh', 'sebaran_sdgs', 'struktur', 'Admin_Settings'] as $u) {
        cek(http(NULL, $u)['code'] === 404, "U4 — /{$u} dicabut (404)");
    }

    // Halaman tetangga WAJIB tetap hidup. Mencabut route tanpa memperbaiki
    // penautnya menghasilkan 404 di seluruh situs — itu risiko regresi yang
    // disebut roadmap secara eksplisit.
    foreach (['' => 'beranda', 'sebaran' => 'sebaran', 'listkabupaten' => 'listkabupaten',
        'profil' => 'profil'] as $u => $nama) {
        cek(http(NULL, $u)['code'] === 200, "U4 — {$nama} tetap hidup (200)");
    }

    // Literal yang tidak boleh muncul lagi di permukaan publik mana pun.
    $literal = ['8.420', '4.250,8', '142.500', 'Rusunawa Kudu', 'Nama Pejabat',
        'Rp 500.000.000', 'Rp 750.000.000', 'Mode Pemeliharaan', 'Terdapat keluhan'];
    $halaman = ['' => 'beranda', 'listkabupaten' => 'listkabupaten', 'profil' => 'profil'];
    $bocor = [];
    foreach ($halaman as $u => $nama) {
        $isi = http(NULL, $u)['body'];
        foreach ($literal as $lit) {
            if (strpos($isi, $lit) !== FALSE) {
                $bocor[] = "{$nama}:{$lit}";
            }
        }
    }
    cek($bocor === [], 'U4 — nol literal karangan tersisa di permukaan publik'
        . ($bocor ? ' — ditemukan: ' . implode(', ', $bocor) : ''));

} finally {
    bersihkan();
}

$pulih = file_get_contents($dbConfigPath) === $dbConfigAsli;
cek($pulih, 'database.php pulih byte-identik');
cek(skalar_int("SELECT COUNT(*) c FROM usr_users WHERE email LIKE 'uji_utang_%'") === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
