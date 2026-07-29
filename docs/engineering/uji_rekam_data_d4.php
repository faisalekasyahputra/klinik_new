<?php
/**
 * Uji D4 — Rekam Data: Kawasan lengkap.
 *
 * Yang dibuktikan di sini adalah hal-hal yang justru HILANG dari form dinas:
 * tanpa batas 20 intervensi, "tidak ada progres" bisa dikirim tanpa dipaksa
 * mengisi Total Luas, total anggaran dihitung bukan diketik, dan satuan tidak
 * pernah disimpan sehingga tidak bisa bertentangan dengan indikatornya.
 *
 *   php docs/engineering/uji_rekam_data_d4.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 * Tahun sentinel 2099; seluruh jejaknya dihapus di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('TAHUN', 2099);

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
        // Baris pertama menang: .env punya tiga blok DB_*; yang terakhir production.
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

/**
 * Satu kolom sebagai array integer.
 *
 * Cast ke int BUKAN kosmetik: `mysqli` prepared statement mengembalikan tipe
 * NATIVE (integer), sedangkan `query()` biasa mengembalikan string. Tanpa
 * normalisasi, `===` terhadap array string membuat harness merah padahal
 * datanya benar.
 */
function kolom_int($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = (int) reset($row);
    }
    $stmt->close();
    return $out;
}

// ---------------------------------------------------------------- HTTP

$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujid4_');
    }
    return $jars[$nama];
}

function http($nama, $path, ?array $post = NULL, $follow = TRUE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => jar($nama),
        CURLOPT_COOKIEFILE     => jar($nama),
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 30,
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
    $res = http($nama, $path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $res['body'], $m) ? $m[1] : '';
}

function login($nama, $email, $password) {
    return http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $password,
    ])['code'] === 200;
}

function bersihkan() {
    global $db, $jars;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_rd_d4_%'");
    foreach ($jars as $f) {
        @unlink($f);
    }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji D4 — Kawasan lengkap\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];

$kab_lain = (int) skalar('SELECT id FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
wajib($kab_lain > 0, 'Ada kabupaten kedua untuk uji scope');

$stamp = time();
$email_lain = "uji_rd_d4_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_users (email, password, role, kabupaten_id, name, username)
     VALUES ('%s', '%s', 'admin_kabkota', %d, 'Uji D4 Lain', 'uji_rd_d4_%d')",
    $db->real_escape_string($email_lain),
    $db->real_escape_string(password_hash('UjiRdD4!', PASSWORD_BCRYPT)),
    $kab_lain, $stamp));

function laporan_kawasan($kab, $bulan) {
    return (int) skalar('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['kawasan', $kab, TAHUN, $bulan]);
}

try {
    wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota berhasil');
    wajib(login('lain', $email_lain, 'UjiRdD4!'), 'Login admin wilayah lain berhasil');

    // ---------------------------------------------- bentuk skema
    cek(skalar("SHOW COLUMNS FROM rd_kawasan_intervensi LIKE 'satuan'") === NULL,
        'Kolom `satuan` tidak ada — diturunkan dari indikator, tidak disimpan');
    cek(skalar("SHOW COLUMNS FROM rd_kawasan_ringkasan LIKE 'total_anggaran'") === NULL,
        'Kolom `total_anggaran` tidak ada — dihitung, tidak disimpan');

    // ------------------------------------------------------ periode 6
    $url = 'Rekam_Kawasan?tahun=' . TAHUN . '&bulan=6';
    wajib(http('kab', $url)['code'] === 200, 'Layar Kawasan terbuka');
    $LAP = laporan_kawasan($KAB, 6);
    wajib($LAP > 0, 'Draft kawasan dibuat otomatis');

    // ---------------------------------------------------- ringkasan
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_ringkasan', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'ada_penanganan' => '1', 'ada_progres' => '0',
        'catatan_progres' => '', 'total_luas_ha' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_ringkasan WHERE laporan_id = ?', [$LAP]) === 0,
        'Tidak ada progres tanpa catatan ditolak server');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_ringkasan', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'ada_penanganan' => '1', 'ada_progres' => '1',
        'catatan_progres' => '', 'total_luas_ha' => '12.75']);
    cek((string) skalar('SELECT total_luas_ha FROM rd_kawasan_ringkasan WHERE laporan_id = ?', [$LAP]) === '12.75',
        'Total luas tersimpan dengan pecahan');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_ringkasan', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'ada_penanganan' => '1', 'ada_progres' => '1',
        'total_luas_ha' => '-3']);
    cek((string) skalar('SELECT total_luas_ha FROM rd_kawasan_ringkasan WHERE laporan_id = ?', [$LAP]) === '12.75',
        'Luas negatif ditolak, nilai lama utuh');

    // -------------------------------------------- kirim ditolak: nol intervensi
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    cek(skalar('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'draft',
        'Kirim ditolak: mengaku ada progres tapi nol intervensi');

    // ------------------------------------------------ tanpa batas 20
    $tambah = function ($nama, $indikator = 'drainase', $anggaran = 1000000, $padat = 100000) use (&$LAP, $url) {
        $t = csrf('kab', $url);
        return http('kab', 'Rekam_Kawasan/simpan_intervensi', [
            'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'intervensi_id' => '',
            'indikator' => $indikator, 'nama_kegiatan' => $nama,
            'lokasi_teks' => 'RT 1 RW 1, Desa Uji, Kec. Uji',
            'sumber_anggaran' => 'apbd_kabkota', 'keterangan_sumber' => '',
            'volume' => '10.5', 'nilai_anggaran' => $anggaran, 'nilai_padat_karya' => $padat,
        ]);
    };
    for ($i = 1; $i <= 25; $i++) {
        $tambah('Kegiatan ke-' . $i);
    }
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 25,
        'Dua puluh lima intervensi tersimpan — batas 20 form dinas tidak dibawa ke sini');
    $urutan = kolom_int('SELECT urutan FROM rd_kawasan_intervensi WHERE laporan_id = ? ORDER BY urutan', [$LAP]);
    cek($urutan === range(1, 25), 'Urutan 1..25 tanpa bolong');

    // -------------------------------------------------------- validasi
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'indikator' => 'tidak_ada', 'nama_kegiatan' => 'X',
        'lokasi_teks' => 'X', 'sumber_anggaran' => 'apbd_kabkota',
        'volume' => '1', 'nilai_anggaran' => '1', 'nilai_padat_karya' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 25,
        'Indikator tak dikenal ditolak');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'indikator' => 'drainase', 'nama_kegiatan' => 'X',
        'lokasi_teks' => 'X', 'sumber_anggaran' => 'kas_pribadi',
        'volume' => '1', 'nilai_anggaran' => '1', 'nilai_padat_karya' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 25,
        'Sumber anggaran tak dikenal ditolak');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'indikator' => 'drainase', 'nama_kegiatan' => 'X',
        'lokasi_teks' => 'X', 'sumber_anggaran' => 'apbd_kabkota',
        'volume' => '1', 'nilai_anggaran' => '-500', 'nilai_padat_karya' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 25,
        'Anggaran negatif ditolak');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'indikator' => 'drainase', 'nama_kegiatan' => '',
        'lokasi_teks' => '', 'sumber_anggaran' => 'apbd_kabkota',
        'volume' => '1', 'nilai_anggaran' => '1', 'nilai_padat_karya' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 25,
        'Nama kegiatan & lokasi kosong ditolak');

    // ----------------------------------------------- total dihitung
    $harusnya = q('SELECT COALESCE(SUM(nilai_anggaran),0) a, COALESCE(SUM(nilai_padat_karya),0) p
        FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]);
    $layar = http('kab', $url)['body'];
    cek(strpos($layar, 'Rp ' . number_format((int) $harusnya['a'], 0, ',', '.')) !== FALSE,
        'Total anggaran di layar sama dengan SUM intervensi');
    cek(strpos($layar, 'Rp ' . number_format((int) $harusnya['p'], 0, ',', '.')) !== FALSE,
        'Total padat karya di layar sama dengan SUM intervensi');
    cek(strpos($layar, 'name="total_anggaran"') === FALSE,
        'Nol input total anggaran — tidak bisa diketik petugas');

    // --------------------------------------------- hapus merapatkan urutan
    $tengah = (int) skalar('SELECT id FROM rd_kawasan_intervensi WHERE laporan_id = ? AND urutan = 13', [$LAP]);
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/hapus_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'intervensi_id' => $tengah]);
    $urutan = kolom_int('SELECT urutan FROM rd_kawasan_intervensi WHERE laporan_id = ? ORDER BY urutan', [$LAP]);
    cek(count($urutan) === 24, 'Satu intervensi terhapus');
    cek($urutan === range(1, 24), 'Urutan dirapatkan, tidak bolong di tengah');

    // -------------------------------------------------------- ubah
    $pertama = (int) skalar('SELECT id FROM rd_kawasan_intervensi WHERE laporan_id = ? AND urutan = 1', [$LAP]);
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'intervensi_id' => $pertama, 'indikator' => 'air_minum',
        'nama_kegiatan' => 'Kegiatan diperbarui', 'lokasi_teks' => 'RT 2 RW 2, Desa Uji',
        'sumber_anggaran' => 'dana_desa', 'keterangan_sumber' => '',
        'volume' => '85', 'nilai_anggaran' => '212500000', 'nilai_padat_karya' => '0']);
    $ubah = q('SELECT indikator, nama_kegiatan, urutan FROM rd_kawasan_intervensi WHERE id = ?', [$pertama]);
    cek($ubah['indikator'] === 'air_minum' && $ubah['nama_kegiatan'] === 'Kegiatan diperbarui',
        'Ubah intervensi tersimpan');
    cek((int) $ubah['urutan'] === 1, 'Ubah tidak memindahkan urutan');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 24,
        'Ubah tidak menambah baris baru');

    // ------------------------------------------------------- scope
    $t = csrf('lain', 'Rekam_Kawasan');
    http('lain', 'Rekam_Kawasan/simpan_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'indikator' => 'drainase', 'nama_kegiatan' => 'Sisipan wilayah lain',
        'lokasi_teks' => 'X', 'sumber_anggaran' => 'apbd_kabkota',
        'volume' => '1', 'nilai_anggaran' => '1', 'nilai_padat_karya' => '0']);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 24,
        'Admin wilayah lain tidak bisa menambah intervensi');

    $t = csrf('lain', 'Rekam_Kawasan');
    http('lain', 'Rekam_Kawasan/hapus_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'intervensi_id' => $pertama]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === 24,
        'Admin wilayah lain tidak bisa menghapus intervensi');

    // Hitung ulang di sini, JANGAN mengunci 24 untuk uji-uji sesudahnya. Kalau
    // guard scope dilepas untuk uji balik, penghapusan lintas wilayah berhasil
    // dan jumlahnya bergeser — tanpa pembacaan ulang ini, satu mutasi
    // memerahkan empat uji sekaligus dan titik sebenarnya jadi kabur
    // (pelajaran D2, AGENTS.md §0e).
    $sisa = (int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]);

    // -------------------------------------------------------- kirim
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP]);
    cek(skalar('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Kirim berhasil setelah ada intervensi');

    $layar = http('kab', $url)['body'];
    cek(strpos($layar, 'name="nama_kegiatan"') === FALSE, 'Form tambah intervensi hilang setelah terkirim');
    cek(strpos($layar, 'terkunci') !== FALSE, 'Layar menjelaskan kondisi terkunci');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Kawasan/hapus_intervensi', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP, 'intervensi_id' => $pertama]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP]) === $sisa,
        'Laporan terkirim tidak bisa diubah kabupaten');

    // --------------------------------------------------- pewarisan
    http('kab', 'Rekam_Kawasan?tahun=' . TAHUN . '&bulan=7');
    $LAP7 = laporan_kawasan($KAB, 7);
    cek($LAP7 > 0, 'Periode berikutnya dibuat');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_kawasan_intervensi WHERE laporan_id = ?', [$LAP7]) === $sisa,
        $sisa . ' intervensi diwarisi');
    cek((string) skalar('SELECT total_luas_ha FROM rd_kawasan_ringkasan WHERE laporan_id = ?', [$LAP7]) === '12.75',
        'Ringkasan ikut diwarisi apa adanya');
    $urutan7 = kolom_int('SELECT urutan FROM rd_kawasan_intervensi WHERE laporan_id = ? ORDER BY urutan', [$LAP7]);
    cek($urutan7 === range(1, $sisa), 'Urutan warisan tetap rapat');

    // ------------------- "tidak ada penanganan" boleh dikirim polos
    $url8 = 'Rekam_Kawasan?tahun=' . TAHUN . '&bulan=8';
    http('kab', $url8);
    $LAP8 = laporan_kawasan($KAB, 8);
    $t = csrf('kab', $url8);
    http('kab', 'Rekam_Kawasan/simpan_ringkasan', ['csrf_kpkp_token' => $t,
        'laporan_id' => $LAP8, 'ada_penanganan' => '0', 'ada_progres' => '0',
        'catatan_progres' => '', 'total_luas_ha' => '0']);
    cek((int) skalar('SELECT ada_penanganan FROM rd_kawasan_ringkasan WHERE laporan_id = ?', [$LAP8]) === 0,
        '"Tidak ada penanganan" tersimpan tanpa catatan dan tanpa luas');
    $t = csrf('kab', $url8);
    http('kab', 'Rekam_Kawasan/kirim', ['csrf_kpkp_token' => $t, 'laporan_id' => $LAP8]);
    cek(skalar('SELECT status FROM rd_laporan WHERE id = ?', [$LAP8]) === 'terkirim',
        '"Tidak ada penanganan" bisa dikirim tanpa dipaksa isi Total Luas');

} finally {
    bersihkan();
}

cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
