<?php
/**
 * Uji D3 — Rekam Data: kirim, BNBA, dan pewarisan antar bulan.
 *
 * Lewat HTTP Apache sungguhan. Bukti diambil dari DB dan DISK, bukan dari flash
 * di layar: berkas yang diganti harus benar-benar hilang dari disk, dan berkas
 * privat harus benar-benar tidak tersaji ke pihak yang tidak berhak.
 *
 *   php docs/engineering/uji_rekam_data_d3.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 * Tahun sentinel 2099; seluruh jejak DB dan disk dihapus di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('TAHUN', 2099);
define('BULAN', 6);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

function cek($condition, $label) {
    $GLOBALS['uji_total']++;
    echo ($condition ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $condition) {
        $GLOBALS['uji_gagal']++;
    }
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
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Baris pertama menang: .env memuat tiga blok DB_*; yang terakhir adalah
        // kredensial production (AGENTS.md §0e).
        if ( ! array_key_exists($key, $out)) {
            $out[$key] = trim($value);
        }
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

/** Meniru private_uploads_root(): relatif dihitung dari FCPATH aplikasi. */
function akar_privat($env) {
    $dari_env = $env['PRIVATE_UPLOADS_PATH'] ?? '';
    if (trim($dari_env) === '') {
        return dirname(APP_ROOT) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR;
    }
    $path = rtrim(trim($dari_env), "/\\");
    $absolut = $path !== '' && ($path[0] === '/' || $path[0] === '\\' || preg_match('#^[A-Za-z]:#', $path));
    if ( ! $absolut) {
        $path = rtrim(APP_ROOT, "/\\") . DIRECTORY_SEPARATOR . $path;
    }
    return $path . DIRECTORY_SEPARATOR;
}

function dir_bnba($laporan_id) {
    global $env;
    return akar_privat($env) . 'rekam_bnba' . DIRECTORY_SEPARATOR . (int) $laporan_id . DIRECTORY_SEPARATOR;
}

function isi_dir($dir) {
    if ( ! is_dir($dir)) {
        return [];
    }
    return array_values(array_diff(scandir($dir), ['.', '..']));
}

function q($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : NULL;
    $stmt->close();
    return $row;
}

function skalar($sql, $params = []) {
    $row = q($sql, $params);
    return $row ? reset($row) : NULL;
}

// ---------------------------------------------------------------- HTTP

$jars = [];
$berkas_uji = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujid3_');
    }
    return $jars[$nama];
}

function http($nama, $path, ?array $post = NULL, $follow = TRUE, ?array $files = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => $follow,
    ];
    if ($nama !== NULL) {
        $opt[CURLOPT_COOKIEJAR]  = jar($nama);
        $opt[CURLOPT_COOKIEFILE] = jar($nama);
    }
    curl_setopt_array($ch, $opt);

    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if ($files) {
            foreach ($files as $field => $path_file) {
                $post[$field] = new CURLFile($path_file);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
    }

    $raw  = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return ['code' => $code, 'body' => substr($raw, $size), 'type' => $type];
}

function csrf($nama, $path) {
    $res = http($nama, $path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $res['body'], $m) ? $m[1] : '';
}

function login($nama, $email, $password) {
    $token = csrf($nama, 'Auth/login');
    return http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => $token, 'email' => $email, 'password' => $password,
    ])['code'] === 200;
}

function bersihkan() {
    global $db, $jars, $berkas_uji;
    $res = $db->query('SELECT id FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    while ($row = $res->fetch_assoc()) {
        $dir = dir_bnba((int) $row['id']);
        foreach (isi_dir($dir) as $f) {
            @unlink($dir . $f);
        }
        @rmdir($dir);
    }
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_rd_d3_%'");
    foreach (array_merge($jars, $berkas_uji) as $f) {
        @unlink($f);
    }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji D3 — Kirim, BNBA, Pewarisan\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];

$kab_lain = (int) skalar('SELECT id FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
wajib($kab_lain > 0, 'Ada kabupaten kedua untuk uji scope');

$stamp = time();
$email_lain = "uji_rd_d3_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_users (email, password, role, kabupaten_id, name, username)
     VALUES ('%s', '%s', 'admin_kabkota', %d, 'Uji D3 Lain', 'uji_rd_d3_%d')",
    $db->real_escape_string($email_lain),
    $db->real_escape_string(password_hash('UjiRdD3!', PASSWORD_BCRYPT)),
    $kab_lain, $stamp));

// Berkas uji dibuat di sini supaya tidak bergantung pada fixture di repo.
$pdf_a = tempnam(sys_get_temp_dir(), 'bnba_a_') . '.pdf';
$pdf_b = tempnam(sys_get_temp_dir(), 'bnba_b_') . '.pdf';
$txt   = tempnam(sys_get_temp_dir(), 'bnba_x_') . '.txt';
file_put_contents($pdf_a, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
file_put_contents($pdf_b, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n% versi kedua\ntrailer<</Root 1 0 R>>\n%%EOF\n");
file_put_contents($txt, "bukan berkas yang diizinkan\n");
$berkas_uji = [$pdf_a, $pdf_b, $txt];

try {
    wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota berhasil');
    wajib(login('lain', $email_lain, 'UjiRdD3!'), 'Login admin wilayah lain berhasil');

    $url = 'Rekam_Perumahan?tahun=' . TAHUN . '&bulan=' . BULAN;
    wajib(http('kab', $url)['code'] === 200, 'Layar input terbuka');
    $LAP = (int) skalar('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN]);
    wajib($LAP > 0, 'Draft periode tersedia');

    // ------------------------------------------------- unggah tanpa berkas
    $t = csrf('kab', $url);
    $kosong = http('kab', 'Rekam_Perumahan/unggah_bnba', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    cek($kosong['code'] === 200, 'Unggah tanpa memilih berkas TIDAK 404');
    cek(strpos($kosong['body'], 'Pilih berkas') !== FALSE, 'Pesannya bisa dibaca pengguna');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP]) === 0,
        'Unggah kosong tidak melahirkan baris ledger');
    cek(isi_dir(dir_bnba($LAP)) === [], 'Unggah kosong tidak melahirkan berkas di disk');

    // ------------------------------------------------- jenis berkas ditolak
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/unggah_bnba',
        ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP], TRUE, ['bnba' => $txt]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP]) === 0,
        'Berkas .txt ditolak, nol baris ledger');
    cek(isi_dir(dir_bnba($LAP)) === [], 'Berkas .txt tidak mendarat di disk');

    // ------------------------------------------------------- unggah sah
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/unggah_bnba',
        ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP], TRUE, ['bnba' => $pdf_a]);
    $bnba = q('SELECT private_path, nama_asli, ukuran FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP]);
    cek($bnba !== NULL, 'PDF tersimpan di ledger');
    cek(count(isi_dir(dir_bnba($LAP))) === 1, 'Tepat satu berkas di disk');
    cek($bnba && is_file(dir_bnba($LAP) . $bnba['private_path']),
        'Nama di ledger cocok dengan berkas nyata di disk');

    // --------------------------------------------------------- penyajian
    $unduh = http('kab', 'Rekam_Perumahan/unduh_bnba/' . $LAP);
    cek($unduh['code'] === 200, 'Pemilik wilayah bisa mengunduh');
    cek($unduh['body'] === file_get_contents($pdf_a), 'Byte yang tersaji sama persis dengan yang diunggah');
    cek(stripos($unduh['type'], 'pdf') !== FALSE,
        'Content-Type PDF, bukan text/html (jebakan readfile §0e)');

    $lintas = http('lain', 'Rekam_Perumahan/unduh_bnba/' . $LAP);
    cek($lintas['code'] === 404, 'Admin wilayah lain dapat 404, bukan berkasnya');
    cek(strpos($lintas['body'], '%PDF') === FALSE, 'Nol byte PDF bocor ke wilayah lain');

    $anonim = http(NULL, 'Rekam_Perumahan/unduh_bnba/' . $LAP, NULL, FALSE);
    cek($anonim['code'] !== 200, 'Tanpa sesi tidak mendapat 200');
    cek(strpos($anonim['body'], '%PDF') === FALSE, 'Nol byte PDF bocor ke anonim');

    // Berkas privat tidak boleh tersaji langsung lewat URL, terlepas dari endpoint.
    $langsung = http(NULL, '../private_uploads/rekam_bnba/' . $LAP . '/' . $bnba['private_path'], NULL, FALSE);
    cek($langsung['code'] !== 200, 'Akses langsung ke private_uploads tidak 200');

    // ------------------------------------------------------ unggah ulang
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/unggah_bnba',
        ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP], TRUE, ['bnba' => $pdf_b]);
    $bnba2 = q('SELECT private_path FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP]) === 1,
        'Unggah ulang mengganti, bukan menambah baris');
    cek(count(isi_dir(dir_bnba($LAP))) === 1, 'Berkas lama benar-benar hilang dari disk');
    cek($bnba2 && $bnba2['private_path'] !== $bnba['private_path'], 'Berkas yang tersimpan memang yang baru');
    cek(http('kab', 'Rekam_Perumahan/unduh_bnba/' . $LAP)['body'] === file_get_contents($pdf_b),
        'Yang tersaji sekarang isi berkas kedua');

    // ------------------------------------------------------------- kirim
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    cek(skalar('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'draft',
        'Kirim ditolak selama masih ada sumber dana belum dijawab');

    $sumber = ['apbd_kabkota', 'apbn_bsps', 'apbn_dak', 'apbn_kemensos', 'apbn_dana_desa',
        'apbn_kl_lain', 'baznas_ri', 'baznas_kabkota', 'csr', 'dana_lainnya'];
    foreach ($sumber as $i => $kode) {
        $t = csrf('kab', $url);
        http('kab', 'Rekam_Perumahan/simpan_gerbang', [
            'csrf_kpkp_token' => $t, 'laporan_id' => $LAP,
            'sumber_dana' => $kode, 'ada' => $i === 0 ? '1' : '0',
        ]);
    }
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bagian WHERE laporan_id = ?', [$LAP]) === 10,
        'Sepuluh sumber dana terjawab');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota',
        'program[pk_rtlh][unit]' => 25, 'program[pk_rtlh][anggaran]' => 5000000000,
        'program[pb_rtlh][unit]' => 4, 'program[pb_rtlh][anggaran]' => 800000000,
    ]);

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    $lap = q('SELECT status, submitted_by, submitted_at FROM rd_laporan WHERE id = ?', [$LAP]);
    cek($lap['status'] === 'terkirim', 'Kirim berhasil setelah lengkap');
    cek((int) $lap['submitted_by'] === (int) $admin['id'] && $lap['submitted_at'] !== NULL,
        'Pengirim dan waktunya tercatat');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    cek((string) skalar('SELECT submitted_at FROM rd_laporan WHERE id = ?', [$LAP]) === (string) $lap['submitted_at'],
        'Kirim ulang laporan terkirim ditolak, stempel tidak berubah');

    // ------------------------------------------------------- layar terkunci
    $layar = http('kab', $url);
    cek(strpos($layar['body'], 'Kirim laporan') === FALSE, 'Tombol Kirim hilang setelah terkirim');
    cek(strpos($layar['body'], 'name="bnba"') === FALSE, 'Form unggah BNBA hilang setelah terkirim');
    cek(strpos($layar['body'], 'terkunci') !== FALSE, 'Layar menjelaskan kondisi terkunci');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/unggah_bnba',
        ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP], TRUE, ['bnba' => $pdf_a]);
    cek(skalar('SELECT private_path FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP])
        === $bnba2['private_path'], 'Unggah setelah terkirim ditolak, berkas lama utuh');
    cek(count(isi_dir(dir_bnba($LAP))) === 1, 'Nol berkas yatim dari unggahan yang ditolak');

    // --------------------------------------------------------- pewarisan
    $url7 = 'Rekam_Perumahan?tahun=' . TAHUN . '&bulan=' . (BULAN + 1);
    http('kab', $url7);
    $LAP7 = (int) skalar('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN + 1]);
    cek($LAP7 > 0, 'Periode berikutnya dibuat');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bagian WHERE laporan_id = ?', [$LAP7]) === 10,
        'Sepuluh jawaban gerbang diwarisi');
    cek((string) skalar('SELECT unit FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$LAP7, 'apbd_kabkota', 'pk_rtlh']) === '25',
        'Angka diwarisi apa adanya, tidak dinolkan');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bnba WHERE laporan_id = ?', [$LAP7]) === 0,
        'BNBA TIDAK ikut diwarisi — berkas bulan lalu bukan bukti bulan ini');
    cek(skalar('SELECT status FROM rd_laporan WHERE id = ?', [$LAP7]) === 'draft',
        'Periode warisan mulai sebagai draft, bukan terkirim');

} finally {
    bersihkan();
}

cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
