<?php
/**
 * Uji STRUKTUR & CAKUPAN - master bidang/wilayah dan penjaga kuncinya.
 *
 *   php docs/engineering/uji_struktur_cakupan.php
 *
 * P1 lapisan "master & kendali". Empat janji, dan yang pertama adalah alasan
 * layar ini ada sama sekali:
 *
 *   1. KUNCI TIDAK BISA DIUBAH DARI UI. `bidang.kode` dan `kabupaten.id`
 *      dirujuk sembilan tempat, dan EMPAT di antaranya TANPA foreign key
 *      (`usr_users.bidang_kode`, `usr_users.kabupaten_id`, `aduan.bidang`,
 *      `kkn_magang_slot.bidang_kode`). Mengganti kunci lewat formulir membuat
 *      keempatnya yatim TANPA SATU PUN GALAT - admin bidang kehilangan mejanya,
 *      aduan hilang dari semua dashboard, dan semuanya tetap membalas 200.
 *      Diuji dengan MENEMBAKKAN kode baru ke endpoint-nya, bukan dengan
 *      memastikan formulirnya tidak punya field itu.
 *   2. HITUNGAN YATIM BENAR-BENAR MENGHITUNG. Ia satu-satunya penjaga yang
 *      tersisa untuk empat jalur tanpa FK itu. Diuji dengan MEMBUAT satu baris
 *      yatim sungguhan lalu memastikan angkanya naik - hitungan yang selalu nol
 *      terlihat persis seperti sistem yang sehat.
 *   3. GANTI NAMA BEKERJA DAN TERCATAT.
 *   4. PERAN LAIN TIDAK DAPAT LAYAR INI, dibuktikan lewat keadaan DB dan isi
 *      halaman - bukan kode HTTP, karena peran salah DIALIHKAN dan curl yang
 *      mengikuti redirect menerima 200 dari halaman lain.
 *
 * Semua perubahan pada tabel master DIPULIHKAN, termasuk kalau prosesnya mati
 * di tengah (shutdown handler).
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiStruktur!2026');
define('CAP', 'UJISTR' . date('YmdHis') . mt_rand(100, 999));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['jar']   = [];
$GLOBALS['aduan_uji'] = [];
$GLOBALS['pulih_nama'] = [];   // [tabel, kolom_kunci, kunci, nama_asli]

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}
function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

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
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }
function nilai($sql, $p = []) { $r = q($sql, $p); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujst_'); }
    return $GLOBALS['jar'][$nama];
}

function http($nama, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => sesi($nama),
        CURLOPT_COOKIEFILE => sesi($nama), CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30,
    ];
    if ($ajax) { $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => (int) $info['http_code'], 'body' => $body, 'url' => (string) $info['url']];
}

function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path}.\n");
    exit(1);
}

function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'), 'email' => $email, 'password' => SANDI,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix, $kab = NULL, $bidang = NULL) {
    $email = 'uji_struktur_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,kabupaten_id,bidang_kode,status,profile_completed,created_at)
         VALUES (?,?,?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Struktur ' . $suffix,
         'uji_struktur_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $kab, $bidang]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/** Catat nama asli SEBELUM diubah supaya bisa dipulihkan apa pun yang terjadi. */
function jaga_nama($tabel, $kolom, $kunci) {
    $asli = nilai("SELECT nama FROM `{$tabel}` WHERE `{$kolom}`=?", [$kunci]);
    $GLOBALS['pulih_nama'][] = [$tabel, $kolom, $kunci, $asli];
    return $asli;
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    /**
     * Nama master dipulihkan DULU - ini satu-satunya yang menyentuh data nyata.
     *
     * ⚠️ PEMULIHAN KEDUA ADA KARENA PEMULIHAN PERTAMA PERNAH KALAH. Saat
     * penjaga kunci dimutasi (mensimulasikan controller yang menerima `id` dari
     * POST), `kabupaten.id` 3301 BENAR-BENAR berubah jadi 9999 - dan `UPDATE
     * ... WHERE id=3301` di bawah tidak menemukan apa pun, jadi barisnya
     * tertinggal rusak sampai diperbaiki tangan. Harness yang menguji penjaga
     * kunci HARUS bisa memulihkan kunci, bukan cuma nama.
     */
    foreach ($GLOBALS['pulih_nama'] as [$tabel, $kolom, $kunci, $asli]) {
        if ($asli === NULL) { continue; }
        $masih_ada = (int) nilai("SELECT COUNT(*) c FROM `{$tabel}` WHERE `{$kolom}`=?", [$kunci]);
        if ($masih_ada) {
            q("UPDATE `{$tabel}` SET nama=? WHERE `{$kolom}`=?", [$asli, $kunci]);
            continue;
        }
        // Kuncinya hilang: cari barisnya lewat nama uji, lalu pulihkan keduanya.
        $sesat = nilai("SELECT `{$kolom}` FROM `{$tabel}` WHERE nama LIKE ?", ['%' . CAP . '%']);
        if ($sesat !== NULL) {
            q("UPDATE `{$tabel}` SET `{$kolom}`=?, nama=? WHERE `{$kolom}`=?", [$kunci, $asli, $sesat]);
            fwrite(STDERR, "PERINGATAN: kunci {$tabel}.{$kolom} sempat berubah jadi {$sesat}, sudah dipulihkan ke {$kunci}.\n");
        }
    }
    $GLOBALS['pulih_nama'] = [];
    foreach ($GLOBALS['aduan_uji'] as $id) { q('DELETE FROM aduan WHERE id=?', [$id]); }
    $GLOBALS['aduan_uji'] = [];
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI STRUKTUR & CAKUPAN ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

echo "== 0. Prasyarat ==\n";
$jml_bidang = (int) nilai('SELECT COUNT(*) c FROM bidang');
$jml_kab    = (int) nilai('SELECT COUNT(*) c FROM kabupaten');
wajib($jml_bidang > 0 && $jml_kab > 0, "Master terisi ({$jml_bidang} bidang, {$jml_kab} wilayah)");
$kode_uji = (string) nilai('SELECT kode FROM bidang ORDER BY kode ASC LIMIT 1');
$id_uji   = (string) nilai('SELECT id FROM kabupaten ORDER BY id ASC LIMIT 1');

[$idA, $emailA] = buat_akun('admin', 'super');
[$idK, $emailK] = buat_akun('admin_kabkota', 'kabkota', $id_uji);
wajib(login('a', $emailA), 'Login superadmin');
wajib(login('k', $emailK), 'Login admin kabkota');

// ------------------------------------------------ 1. LAYAR
echo "\n== 1. Layar menampilkan struktur penuh ==\n";
$hal = http('a', 'Admin_Struktur');
wajib($hal['code'] === 200 && strpos($hal['url'], 'Auth/login') === FALSE, 'Layar terbuka untuk superadmin');

preg_match_all('/<tbody[^>]*>(.*?)<\/tbody>/s', $hal['body'], $tb);
cek(count($tb[1]) === 2, 'Dua tabel: bidang dan wilayah');
cek(preg_match_all('/<tr[^>]*>/', $tb[1][0] ?? '', $x) === $jml_bidang,
    "Tabel bidang memuat {$jml_bidang} baris");
cek(preg_match_all('/<tr[^>]*>/', $tb[1][1] ?? '', $x) === $jml_kab,
    "Tabel wilayah memuat {$jml_kab} baris - SEMUA, bukan yang berpetugas saja");
cek(stripos($hal['body'], 'tidak bisa diubah') !== FALSE,
    'Layar menyatakan kodenya tidak bisa diubah, bukan cuma tidak menyediakan fieldnya');

// ------------------------------------------------ 2. KUNCI TIDAK BISA DIUBAH
echo "\n== 2. Kunci TIDAK bisa diubah lewat endpoint ==\n";
/**
 * Ditembakkan langsung, bukan diperiksa dari formulirnya. Formulir yang tidak
 * punya field bukan gerbang - gerbangnya adalah controller yang tidak pernah
 * membaca field itu.
 */
/**
 * SASARANNYA KABUPATEN, dan sengaja yang TIDAK punya baris turunan.
 *
 * Versi pertama menembak `bidang`, dan dua dari tiga pemeriksaannya HIJAU
 * PALSU: ternyata kelima bidang punya baris `kkn_magang_bidang`, jadi yang
 * menolak penggantian kunci adalah FOREIGN KEY-nya, bukan controller ini -
 * mutasi yang menerima `kode` dari POST tetap lolos dua cek itu. Kabupaten
 * tanpa `rd_laporan`/`sf_housing_queue`/`sf_penilaian_perumahan` tidak punya
 * penyelamat itu; kalau controllernya lengah, kuncinya BENAR-BENAR berubah.
 */
$id_bebas = (string) nilai(
    'SELECT k.id FROM kabupaten k
     WHERE NOT EXISTS (SELECT 1 FROM rd_laporan a WHERE a.kabupaten_id = k.id)
       AND NOT EXISTS (SELECT 1 FROM sf_housing_queue b WHERE b.kabupaten_id = k.id)
       AND NOT EXISTS (SELECT 1 FROM sf_penilaian_perumahan c WHERE c.kabupaten_id = k.id)
     ORDER BY k.id ASC LIMIT 1');
wajib($id_bebas !== '', 'Ada kabupaten tanpa baris turunan (tidak ada FK yang menahan)');
$nama_asli = jaga_nama('kabupaten', 'id', $id_bebas);

http('a', 'Admin_Struktur/ubah_nama', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Struktur'),
    'jenis' => 'kabupaten', 'kunci' => $id_bebas,
    'nama'  => 'Nama Baru ' . CAP,
    // Yang diselundupkan - dua nama field yang mungkin dipakai implementasi:
    'id'    => '9999',
    'kode'  => '9999',
]);
cek((int) nilai('SELECT COUNT(*) c FROM kabupaten WHERE id=?', [$id_bebas]) === 1,
    "Kunci ASLI ({$id_bebas}) masih ada - POST `id`/`kode` diabaikan");
cek((int) nilai('SELECT COUNT(*) c FROM kabupaten WHERE id=?', ['9999']) === 0,
    'Kunci selundupan TIDAK tercipta');
cek(nilai('SELECT nama FROM kabupaten WHERE id=?', [$id_bebas]) === 'Nama Baru ' . CAP,
    'Sementara namanya memang berubah - jadi permintaannya benar-benar diproses');

// ------------------------------------------------ 3. JEJAK
echo "\n== 3. Perubahan tercatat ==\n";
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='master_nama_diubah' AND actor_id=? AND created_at >= ?",
    [$idA, MULAI]) === 1, 'Satu baris jejak audit master_nama_diubah');
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='master_nama_diubah' AND actor_id=? AND ringkasan LIKE ?",
    [$idA, '%' . $nama_asli . '%']) === 1, 'Jejaknya memuat nama LAMA - tanpa itu perubahannya tak bisa ditelusuri');

// ------------------------------------------------ 4. YANG DITOLAK
echo "\n== 4. Yang harus ditolak ==\n";
cek(http('a', 'Admin_Struktur/ubah_nama?jenis=bidang&kunci=' . $kode_uji . '&nama=lewat+get')['code'] === 404,
    'GET ke endpoint tulis dibalas 404');

http('a', 'Admin_Struktur/ubah_nama', ['jenis' => 'bidang', 'kunci' => $kode_uji, 'nama' => 'Tanpa Token']);
cek(nilai('SELECT nama FROM bidang WHERE kode=?', [$kode_uji]) !== 'Tanpa Token',
    'POST tanpa token CSRF tidak mengubah apa pun');

/**
 * SASARANNYA `aduan`, bukan `usr_users`.
 *
 * Versi pertama memakai `usr_users` dan HIJAU PALSU: mutasi yang mencabut
 * whitelist tetap lolos, karena `usr_users` menyimpan namanya di kolom `name`,
 * bukan `nama` - query-nya gagal karena kebetulan penamaan, bukan karena
 * gerbangnya. `aduan` punya `id` DAN `nama` (nama pelapor), jadi ia sasaran
 * yang benar-benar bisa tertulis kalau whitelistnya lengah.
 */
$id_aduan = tulis(
    'INSERT INTO aduan (user_id,nama,email,judul,pesan,bidang,status,created_at,updated_at)
     VALUES (NULL,?,?,?,"isi uji",NULL,"Baru",NOW(),NOW())',
    ['Pelapor Uji ' . CAP, 'pelapor_' . CAP . '@example.test', 'Aduan uji struktur ' . CAP]);
$GLOBALS['aduan_uji'][] = $id_aduan;
wajib($id_aduan > 0, 'Baris aduan uji dibuat sebagai sasaran whitelist');

http('a', 'Admin_Struktur/ubah_nama', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Struktur'),
    'jenis' => 'aduan', 'kunci' => (string) $id_aduan, 'nama' => 'Nama Dirampas ' . CAP]);
cek(nilai('SELECT nama FROM aduan WHERE id=?', [$id_aduan]) === 'Pelapor Uji ' . CAP,
    'Jenis di luar whitelist ditolak - tabel lain (aduan) tidak bisa disentuh lewat sini');

$sebelum = nilai('SELECT nama FROM bidang WHERE kode=?', [$kode_uji]);
http('a', 'Admin_Struktur/ubah_nama', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Struktur'),
    'jenis' => 'bidang', 'kunci' => $kode_uji, 'nama' => '   ']);
cek(nilai('SELECT nama FROM bidang WHERE kode=?', [$kode_uji]) === $sebelum, 'Nama kosong ditolak');

// Peran lain: DIALIHKAN, jadi kodenya 200 dari halaman lain. Buktinya DB.
http('k', 'Admin_Struktur/ubah_nama', [
    'csrf_kpkp_token' => csrf('k', 'Admin_Kabkota'),
    'jenis' => 'bidang', 'kunci' => $kode_uji, 'nama' => 'Diubah Kabkota']);
cek(nilai('SELECT nama FROM bidang WHERE kode=?', [$kode_uji]) !== 'Diubah Kabkota',
    'Admin kabkota TIDAK bisa mengubah master (dicek dari DB, bukan kode HTTP)');
cek(strpos(http('k', 'Admin_Struktur')['body'], 'Kode Kemendagri') === FALSE,
    'Dan tidak mendapat layarnya');

// ------------------------------------------------ 5. HITUNGAN YATIM
echo "\n== 5. Hitungan yatim benar-benar menghitung ==\n";
/**
 * Pemeriksaan yang paling mudah jadi hijau hampa: angka yang SELALU nol
 * terlihat persis seperti sistem yang sehat. Karena itu di sini dibuat satu
 * baris yatim SUNGGUHAN - akun dengan `bidang_kode` yang tidak ada di tabel
 * `bidang` (dan memang tidak ada FK yang menahannya) - lalu angkanya diperiksa
 * naik, dan turun lagi setelah dipulihkan.
 */
$blok = static function ($body) {
    preg_match('/<ul class="mt-2 space-y-0\.5 text-xs">(.*?)<\/ul>/s', $body, $m);
    return $m[1] ?? '';
};
$awal = $blok(http('a', 'Admin_Struktur')['body']);
wajib($awal !== '', 'Blok hitungan yatim terbaca dari halaman');
cek(preg_match('/>0<\/span> - Petugas bidang/', $awal) === 1,
    'Sebelum dirusak: nol petugas bidang yatim');

[$idY, $emailY] = buat_akun('admin_bidang', 'yatim', NULL, 'bidang_hantu_' . CAP);
$rusak = $blok(http('a', 'Admin_Struktur')['body']);
cek(preg_match('/>1<\/span> - Petugas bidang/', $rusak) === 1,
    'Sesudah satu baris yatim dibuat: angkanya NAIK jadi 1');
cek(stripos(http('a', 'Admin_Struktur')['body'], 'menunjuk data yang tidak ada lagi') !== FALSE,
    'Callout-nya berubah jadi peringatan, bukan tetap hijau');

q('UPDATE usr_users SET bidang_kode=NULL WHERE id=?', [$idY]);
$pulih = $blok(http('a', 'Admin_Struktur')['body']);
cek(preg_match('/>0<\/span> - Petugas bidang/', $pulih) === 1,
    'Sesudah dipulihkan: kembali nol - angkanya mengikuti keadaan, bukan tetap');

// ------------------------------------------------ 6. CAKUPAN
echo "\n== 6. Cakupan petugas ==\n";
$hal2 = http('a', 'Admin_Struktur')['body'];
$tanpa_petugas = (int) nilai(
    'SELECT COUNT(*) c FROM kabupaten k WHERE NOT EXISTS
     (SELECT 1 FROM usr_users u WHERE u.role="admin_kabkota" AND u.kabupaten_id=k.id)');
cek(strpos($hal2, $tanpa_petugas . ' wilayah belum punya petugas') !== FALSE,
    "Jumlah wilayah tanpa petugas disebut ({$tanpa_petugas})");
cek(substr_count($hal2, 'Belum ada</span>') === $tanpa_petugas,
    'Dan tiap barisnya sendiri ditandai, bukan cuma angkanya di judul');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
