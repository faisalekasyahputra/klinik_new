<?php
/**
 * Uji perjalanan MAHASISWA — pendaftaran KKN / Magang, lewat HTTP Apache nyata.
 *
 * Menempuh jalur seperti orangnya: halaman informasi publik → daftar → kirim →
 * lihat status di /akun → admin memproses → keputusan sampai ke pendaftar.
 *
 * Bukti diambil dari DB dan DISK, bukan dari flash di layar. Yang dijaga:
 * peran BUKAN mahasiswa tidak bisa mendaftar, `user_id` tidak bisa disuntik
 * lewat POST, surat pengantar tidak tersaji ke publik, dan pendaftar lain
 * tidak bisa melihat pendaftaran orang lain.
 *
 *   php docs/engineering/uji_perjalanan_mahasiswa.php
 *
 * Env opsional: UJI_BASE_URL
 * Seluruh akun & baris uji dihapus di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

function cek($condition, $label) {
    $GLOBALS['uji_total']++;
    echo ($condition ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $condition) { $GLOBALS['uji_gagal']++; }
    return (bool) $condition;
}

function wajib($condition, $label) {
    if ( ! cek($condition, $label)) {
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        bersihkan();
        exit(1);
    }
}

function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) { continue; }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Baris pertama menang: .env punya beberapa blok DB_*.
        if ( ! array_key_exists($key, $out)) { $out[$key] = trim($value); }
    }
    return $out;
}

$env = env_config(ENV_PATH);
$db = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
if ($db->connect_error) {
    fwrite(STDERR, "Koneksi DB gagal: {$db->connect_error}\n");
    exit(1);
}

function q($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : NULL;
    $stmt->close();
    return $row;
}

/** Prepared statement mengembalikan tipe native — selalu di-cast (AGENTS.md §0e). */
function skalar_int($sql, $params = []) { $r = q($sql, $params); return $r ? (int) reset($r) : 0; }
function skalar_str($sql, $params = []) { $r = q($sql, $params); $v = $r ? reset($r) : NULL; return $v === NULL ? '' : (string) $v; }

// ---------------------------------------------------------------- HTTP

$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) { $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujimhs_'); }
    return $jars[$nama];
}

function http($nama, $path, ?array $post = NULL, $follow = TRUE, array $files = [], array $headers = []) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => jar($nama),
        CURLOPT_COOKIEFILE     => jar($nama),
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($headers) { curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); }
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if ($files) {
            foreach ($files as $field => $tmp) { $post[$field] = new CURLFile($tmp, 'application/pdf', basename($tmp)); }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
    }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $url  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'url' => $url];
}

function csrf($nama, $path) {
    $res = http($nama, $path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $res['body'], $m) ? $m[1] : '';
}

function login($nama, $email, $password) {
    // Kode HTTP saja TIDAK cukup: login gagal juga membalas 200 (redirect
    // diikuti). Yang menentukan isi JSON-nya — dan cabang JSON itu HANYA menyala
    // untuk permintaan AJAX, jadi headernya wajib dikirim. Tanpa header ini
    // do_login me-redirect, body-nya HTML, json_decode NULL, dan harness
    // melaporkan "login gagal" pada login yang sebenarnya berhasil.
    $res = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $password,
    ], TRUE, [], ['X-Requested-With: XMLHttpRequest']);
    $json = json_decode($res['body'], TRUE);
    return ($json['status'] ?? '') === 'success';
}

$stamp = time();
$akun = [
    'mhs'   => "uji_mhs_a_{$stamp}@example.test",
    'mhs2'  => "uji_mhs_b_{$stamp}@example.test",
    'warga' => "uji_mhs_w_{$stamp}@example.test",
];

function bersihkan() {
    global $db, $jars, $akun;
    if (isset($akun)) {
        foreach ($akun as $email) {
            $uid = $db->query("SELECT id FROM usr_users WHERE email = '"
                . $db->real_escape_string($email) . "'")->fetch_assoc()['id'] ?? NULL;
            if ($uid) { $db->query('DELETE FROM kkn_magang_pendaftaran WHERE user_id = ' . (int) $uid); }
        }
    }
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_mhs_%'");
    foreach ($jars as $f) { @unlink($f); }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji perjalanan Mahasiswa — KKN & Magang\n";

$admin = q('SELECT id, email FROM usr_users WHERE role = ? LIMIT 1', ['admin']);
wajib($admin && ! empty($admin['email']), 'Akun superadmin tersedia untuk sisi peninjau');

foreach (['mhs' => 'mahasiswa', 'mhs2' => 'mahasiswa', 'warga' => 'warga'] as $k => $role) {
    $db->query(sprintf(
        "INSERT INTO usr_users (email, password, role, name, username)
         VALUES ('%s', '%s', '%s', 'Uji %s', 'uji_mhs_%s_%d')",
        $db->real_escape_string($akun[$k]),
        $db->real_escape_string(password_hash('UjiMhs!2026', PASSWORD_BCRYPT)),
        $db->real_escape_string($role), $k, $k, $stamp));
}
$UID  = skalar_int('SELECT id FROM usr_users WHERE email = ?', [$akun['mhs']]);
$UID2 = skalar_int('SELECT id FROM usr_users WHERE email = ?', [$akun['mhs2']]);

$pdf = tempnam(sys_get_temp_dir(), 'surat_') . '.pdf';
file_put_contents($pdf, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");

try {
    // ------------------------------------------------ halaman informasi publik
    foreach (['KemitraanPortal' => 'Portal kemitraan',
              'KemitraanPortal/kkn' => 'Halaman KKN Tematik',
              'KemitraanPortal/magang' => 'Halaman Magang'] as $path => $label) {
        cek(http('anon', $path)['code'] === 200, $label . ' terbuka untuk publik');
    }

    // ------------------------------------------------------------- gerbang
    cek(http('anon', 'KemitraanPortal/daftar/ngawur')['code'] === 404,
        'Jenis pendaftaran tak dikenal ditolak 404');

    $anon = http('anon', 'KemitraanPortal/daftar/kkn');
    cek(strpos($anon['url'], 'Auth/login') !== FALSE,
        'Pengunjung tanpa sesi diarahkan ke login, bukan diberi formulir');

    wajib(login('warga', $akun['warga'], 'UjiMhs!2026'), 'Login akun warga berhasil');
    $w = http('warga', 'KemitraanPortal/daftar/kkn');
    cek(strpos($w['url'], 'akun') !== FALSE && strpos($w['body'], 'name="instansi_asal"') === FALSE,
        'Peran BUKAN mahasiswa tidak mendapat formulir pendaftaran');

    // Gerbang layar saja tidak cukup — pintu tulisnya diuji langsung.
    $t = csrf('warga', 'akun');
    http('warga', 'KemitraanPortal/simpan', [
        'csrf_kpkp_token' => $t, 'jenis' => 'kkn', 'instansi_asal' => 'Univ Tembus',
        'no_hp' => '081200000000', 'divisi_atau_tema' => 'Tembus gerbang',
        'periode_mulai' => '2026-09-01', 'periode_selesai' => '2026-10-01']);
    cek(skalar_int('SELECT COUNT(*) c FROM kkn_magang_pendaftaran WHERE instansi_asal = ?',
        ['Univ Tembus']) === 0,
        'POST langsung dari peran warga TIDAK menulis baris');

    // ------------------------------------------------------------ mahasiswa
    wajib(login('mhs', $akun['mhs'], 'UjiMhs!2026'), 'Login akun mahasiswa berhasil');
    $form = http('mhs', 'KemitraanPortal/daftar/kkn');
    wajib($form['code'] === 200 && strpos($form['body'], 'name="instansi_asal"') !== FALSE,
        'Mahasiswa mendapat formulir pendaftaran');
    cek(stripos($form['body'], 'name="user_id"') === FALSE,
        'Nol input user_id di formulir — identitas dari sesi');

    // ------------------------------------------------------------------ CSRF
    http('mhs', 'KemitraanPortal/simpan', [
        'jenis' => 'kkn', 'instansi_asal' => 'Univ Tanpa Token', 'no_hp' => '081200000001',
        'divisi_atau_tema' => 'x', 'periode_mulai' => '2026-09-01', 'periode_selesai' => '2026-10-01']);
    cek(skalar_int('SELECT COUNT(*) c FROM kkn_magang_pendaftaran WHERE instansi_asal = ?',
        ['Univ Tanpa Token']) === 0,
        'POST tanpa token CSRF tidak menulis apa pun');

    // -------------------------------------------------------- validasi server
    $tolak = [
        'no_hp bukan angka' => ['no_hp' => 'nol-delapan', 'periode_mulai' => '2026-09-01'],
        'no_hp terlalu pendek' => ['no_hp' => '0812', 'periode_mulai' => '2026-09-01'],
        'tanggal bukan format tanggal' => ['no_hp' => '081200000002', 'periode_mulai' => '01/09/2026'],
    ];
    foreach ($tolak as $label => $ubah) {
        $t = csrf('mhs', 'KemitraanPortal/daftar/kkn');
        http('mhs', 'KemitraanPortal/simpan', array_merge([
            'csrf_kpkp_token' => $t, 'jenis' => 'kkn', 'instansi_asal' => 'Univ Tolak ' . $label,
            'divisi_atau_tema' => 'x', 'periode_selesai' => '2026-10-01'], $ubah));
        cek(skalar_int('SELECT COUNT(*) c FROM kkn_magang_pendaftaran WHERE instansi_asal = ?',
            ['Univ Tolak ' . $label]) === 0, "Ditolak server: {$label}");
    }

    // --------------------------------------------------- kirim yang sah + IDOR
    // `user_id` DISUNTIKKAN ke payload. Kalau controller memakainya alih-alih
    // sesi, pendaftaran ini akan tercatat atas nama mahasiswa KEDUA.
    $t = csrf('mhs', 'KemitraanPortal/daftar/kkn');
    $kirim = http('mhs', 'KemitraanPortal/simpan', [
        'csrf_kpkp_token' => $t, 'jenis' => 'kkn',
        'user_id'          => $UID2,
        'instansi_asal'    => 'Universitas Uji Mahasiswa',
        'no_hp'            => '081234567890',
        'divisi_atau_tema' => 'Infrastruktur dan Teknologi Digital',
        'periode_mulai'    => '2026-09-01',
        'periode_selesai'  => '2026-10-31',
    ], TRUE, ['file_surat_pengantar' => $pdf]);
    wajib($kirim['code'] === 200, 'Kirim pendaftaran berhasil');

    $PEND = skalar_int('SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal = ?',
        ['Universitas Uji Mahasiswa']);
    wajib($PEND > 0, 'Baris pendaftaran tercatat di DB');
    cek(skalar_int('SELECT user_id FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) === $UID,
        'user_id dari SESI, bukan dari POST — suntikan IDOR diabaikan');
    cek(skalar_str('SELECT jenis FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) === 'kkn',
        'Jenis tersimpan apa adanya');

    // ------------------------------------------------------- surat pengantar
    $berkas = skalar_str('SELECT file_surat_pengantar FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]);
    cek($berkas !== '', 'Surat pengantar tercatat di ledger');
    if ($berkas !== '') {
        cek(http('anon', 'private_uploads/kemitraan/' . $PEND . '/' . $berkas)['code'] !== 200,
            'Surat pengantar TIDAK tersaji langsung ke publik');
        $curi = http('mhs2', 'Admin_Kemitraan/lihat_dokumen/' . $PEND);
        cek($curi['code'] !== 200 || strpos($curi['body'], '%PDF') === FALSE,
            'Mahasiswa lain tidak bisa mengunduh surat pengantar orang lain');
    }

    // ------------------------------------------------------ status di /akun
    $akun_mhs = http('mhs', 'akun')['body'];
    cek(strpos($akun_mhs, 'Universitas Uji Mahasiswa') !== FALSE,
        'Pendaftaran tampil di halaman akun pemiliknya');

    wajib(login('mhs2', $akun['mhs2'], 'UjiMhs!2026'), 'Login mahasiswa kedua berhasil');
    cek(strpos(http('mhs2', 'akun')['body'], 'Universitas Uji Mahasiswa') === FALSE,
        'Pendaftaran TIDAK bocor ke akun mahasiswa lain');

    // ------------------------------------------------------------ sisi admin
    cek(strpos(http('mhs', 'Admin_Kemitraan')['url'], 'Admin_Kemitraan') === FALSE
        || strpos(http('mhs', 'Admin_Kemitraan')['body'], 'Universitas Uji Mahasiswa') === FALSE,
        'Mahasiswa tidak bisa membuka layar peninjauan admin');

    $adm = $admin['email'];
    echo "  --    (peninjau: {$adm})\n";
    if (login('adm', $adm, getenv('UJI_ADMIN_PASSWORD') ?: 'password')) {
        cek(strpos(http('adm', 'Admin_Kemitraan')['body'], 'Universitas Uji Mahasiswa') !== FALSE,
            'Pendaftaran muncul di layar peninjauan admin');

        $t = csrf('adm', 'Admin_Kemitraan');
        http('adm', 'Admin_Kemitraan/proses/' . $PEND, [
            'csrf_kpkp_token' => $t, 'status' => 'Status Ngawur', 'catatan_admin' => '']);
        cek(skalar_str('SELECT status FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) !== 'Status Ngawur',
            'Status di luar daftar ditolak server');

        $t = csrf('adm', 'Admin_Kemitraan');
        http('adm', 'Admin_Kemitraan/proses/' . $PEND, [
            'csrf_kpkp_token' => $t, 'status' => 'Diterima',
            'catatan_admin' => 'Silakan lapor ke Sekretariat 1 September.']);
        cek(skalar_str('SELECT status FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) === 'Diterima',
            'Keputusan admin tersimpan');
        cek(skalar_int('SELECT reviewed_by FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) === (int) $admin['id']
            && skalar_str('SELECT reviewed_at FROM kkn_magang_pendaftaran WHERE id = ?', [$PEND]) !== '',
            'Peninjau dan waktunya tercatat');

        $akun_mhs = http('mhs', 'akun')['body'];
        cek(strpos($akun_mhs, 'Silakan lapor ke Sekretariat') !== FALSE,
            'Catatan admin benar-benar sampai ke layar mahasiswa');
    } else {
        cek(FALSE, 'Login superadmin berhasil (set UJI_ADMIN_PASSWORD bila berbeda)');
    }

} finally {
    @unlink($pdf);
    bersihkan();
}

cek(skalar_int("SELECT COUNT(*) c FROM usr_users WHERE email LIKE 'uji_mhs_%'") === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
