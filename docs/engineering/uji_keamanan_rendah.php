<?php
// Bersyarat, bukan wajib: suite ini juga dijalankan terhadap main (sebelum helper ada) sebagai bukti merah.
if (is_file(dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php')) { require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; }
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Perbaikan keamanan tingkat rendah (3 Okt 2026).
 *
 *   php docs/engineering/uji_keamanan_rendah.php
 *
 *   R1. .env dicari satu tingkat di atas akar aplikasi lebih dulu, lalu di akar; yang di luar menang.
 *   R2. Permintaan penghapusan data di Papan Aduan hanya terlihat pemohon dan staf.
 *   R3. Konten artikel CMS Ternak: body disaring daftar izin, medan lain di-escape, tautan video https saja.
 *   R4. Konfirmasi hapus PSU/Asosiasi tidak lagi menaruh data di dalam string JS (penjaga regresi; sudah
 *       tertutup di 9e2ac17, jadi hijau juga di main).
 *   R5. NIK pembeli SIKUMBANG (nik*) dibuang sebelum cache ditulis; cache lama dibersihkan penyapu retensi.
 *   R6. Pencarian NIK antrean lewat POST lalu redirect; NIK tidak pernah ada di URL atau tautan halaman.
 *   R7. Formulir aduan punya batas laju sendiri per akun dan per IP.
 *   R8. Username tidak boleh berbentuk email; login dengan email hanya mencocokkan kolom email.
 *   R9. Langganan push hanya untuk host layanan Web Push resmi; pengiriman tanpa pengalihan.
 *   R10. assets/cache_foto tidak lagi terlacak git.
 *
 * Bukti menggigit: dijalankan terhadap main (60c2c43) merah di R1-R3 dan R5-R10 (lihat catatan commit).
 *
 * Lokal saja (menolak jalan bila DB bukan lokal). Akun uji @uji-rendah.test dibuat dan dihapus sendiri;
 * akun seed_agen_peran.php dipakai untuk super admin dan admin kab/kota. Ember batas laju yang tersentuh
 * dipinjam lalu dikembalikan. Tanpa permintaan keluar ke layanan luar: cache SIKUMBANG dan Ternak yang
 * dipakai adalah berkas sintetis yang dibuat dan dihapus sendiri.
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
function baca($rel) { return (string) @file_get_contents(APP_ROOT . '/' . $rel); }

$env = [];
foreach (file(function_exists('env_berkas_path') ? env_berkas_path(APP_ROOT) : APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES) as $l) {
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

/* ------------------------------------------------------------------ HTTP: jar per sesi */
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'ujr'); }
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
/** @return array kode, badan, url, lokasi */
function minta($jar, $path, $post = NULL, array $o = []) {
    $c = curl_init(BASE . $path);
    $kepala = [];
    if ( ! empty($o['ajax'])) { $kepala[] = 'X-Requested-With: XMLHttpRequest'; }
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => $o['ikuti'] ?? TRUE,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $kepala,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $hasil = ['kode' => (int) curl_getinfo($c, CURLINFO_HTTP_CODE), 'badan' => $badan,
        'url' => (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL), 'lokasi' => (string) curl_getinfo($c, CURLINFO_REDIRECT_URL)];
    curl_close($c);
    return $hasil;
}
/** @return [jar, json|null] */
function coba_masuk($login, $sandi) {
    $j = jar();
    minta($j, 'Auth/login');
    $r = minta($j, 'Auth/do_login', ['email' => $login, 'password' => $sandi], ['ajax' => TRUE]);
    return [$j, json_decode($r['badan'], TRUE)];
}
function masuk_halaman($login, $sandi) {
    [$j, $json] = coba_masuk($login, $sandi);
    return ($json['status'] ?? '') === 'success' ? $j : NULL;
}

/* ------------------------------------------------------------------ Ember batas laju: dipinjam lalu dikembalikan */
$ember_asli = [];
function pinjam_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
$IP = '127.0.0.1';
pinjam_ember(hash('sha256', "login:ip:$IP"));
function ember_pasangan($login) { global $IP; pinjam_ember(hash('sha256', 'login_akun:key:' . hash('sha256', $IP . '|' . strtolower($login)))); }
foreach (['agen_admin@agen.test', 'agen_admin_kabkota@agen.test'] as $e) { ember_pasangan($e); }

$TAG = 'ujirendah' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT, ['cost' => 10]);
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji;
    $email = $TAG . '_' . count($akun_uji) . '@uji-rendah.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'nama_pengguna' => $TAG . count($akun_uji), 'kata_sandi' => $HASH,
        'peran' => 'warga', 'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    ember_pasangan($email); ember_pasangan($isi['nama_pengguna']);
    return [(int) $id, $email];
}

$bersih = [];  // penutup tambahan (berkas, baris) dijalankan di finally, urutan terbalik
try {
    /* ============================================================== R1 */
    echo "== R1. Lokasi .env: luar akar dulu, lalu akar\n";
    $sem = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ujr_env_' . bin2hex(random_bytes(4));
    $app = $sem . DIRECTORY_SEPARATOR . 'public_html';
    @mkdir($app, 0700, TRUE);
    $bersih[] = function () use ($sem, $app) { @unlink($sem . '/.env'); @unlink($app . '/.env'); @rmdir($app); @rmdir($sem); };
    $di_akar = $app . DIRECTORY_SEPARATOR . '.env';
    $di_luar = $sem . DIRECTORY_SEPARATOR . '.env';
    if ( ! cek(function_exists('env_berkas_path'), 'Ada satu penentu lokasi .env (env_berkas_path)')) { goto r1_statis; }
    cek(env_berkas_path($app) === $di_akar, 'Tanpa berkas mana pun: jatuh ke akar aplikasi (perilaku lama)');
    file_put_contents($di_akar, "SUMBER=akar\n");
    cek(env_berkas_path($app . '/') === $di_akar, 'Hanya .env di akar: yang di akar dipakai (garis miring akhir tidak berpengaruh)');
    file_put_contents($di_luar, "SUMBER=luar\n");
    cek(env_berkas_path($app) === $di_luar, 'Keduanya ada: .env satu tingkat di atas akar yang menang');
    @unlink($di_akar);
    cek(env_berkas_path($app) === $di_luar, 'Hanya .env di luar: berkas di luar dipakai');
    r1_statis:
    $idx = baca('index.php');
    cek(strpos($idx, "env_berkas_path(FCPATH)") !== FALSE && strpos($idx, "FCPATH . '.env'") === FALSE,
        'index.php memuat .env lewat env_berkas_path(FCPATH), bukan FCPATH . \'.env\'');
    $langsung = [];
    foreach (array_merge(glob(APP_ROOT . '/docs/engineering/*.php'), glob(APP_ROOT . '/tests/*.php'), glob(APP_ROOT . '/application/controllers/*.php')) as $f) {
        if (realpath($f) !== realpath(__FILE__) && preg_match('/\.\s*(?:\'\/\.env\'|DIRECTORY_SEPARATOR\s*\.\s*\'\.env\')/', (string) file_get_contents($f))) { $langsung[] = basename($f); }
    }
    cek($langsung === [], 'Tidak ada suite, tes, atau controller yang menyusun path .env sendiri' . ($langsung ? ' (' . implode(', ', $langsung) . ')' : ''));

    /* ============================================================== R2 */
    echo "\n== R2. Permintaan penghapusan data di Papan Aduan\n";
    [$Wp, $ep] = akun_baru('Pemohon Hapus');
    [$Wl, $el] = akun_baru('Warga Lain');
    $judul_hapus = 'Permintaan Penghapusan Data Layanan';
    $penanda = 'Jawaban uji ' . $TAG;
    $ad_hapus = jalan("INSERT INTO aduan (user_id, nama, email, judul, pesan, status, catatan_admin, created_at) VALUES (?,?,?,?,?,'Diproses',?,NOW())",
        [$Wp, 'Pemohon Hapus', $ep, $judul_hapus, 'uji', $penanda]);
    $ad_biasa = jalan("INSERT INTO aduan (user_id, nama, email, judul, pesan, status, created_at) VALUES (?,?,?,?,?,'Baru',NOW())",
        [$Wl, 'Warga Lain', $el, 'Aduan biasa ' . $TAG, 'uji']);
    $bersih[] = fn() => jalan('DELETE FROM aduan WHERE id IN (?, ?)', [$ad_hapus, $ad_biasa]);
    $jl = masuk_halaman($el, $SANDI);
    $jp = masuk_halaman($ep, $SANDI);
    $ja = masuk_halaman('agen_admin@agen.test', SANDI_AGEN);
    cek($jl && $jp && $ja, 'PRASYARAT: warga lain, pemohon, dan super admin bisa masuk');
    if ($jl && $jp && $ja) {
        $lain = minta($jl, 'Umum/papan_aduan')['badan'];
        cek(strpos($lain, 'Aduan biasa ' . $TAG) !== FALSE, 'Warga lain tetap melihat aduan biasa di papan');
        cek(strpos($lain, $penanda) === FALSE, 'Warga lain TIDAK melihat permintaan penghapusan data maupun jawaban dinasnya');
        cek(strpos(minta($jp, 'Umum/papan_aduan')['badan'], $penanda) !== FALSE, 'Pemohon melihat permintaannya sendiri beserta jawaban dinas');
        cek(strpos(minta($ja, 'Umum/papan_aduan')['badan'], $penanda) !== FALSE, 'Super admin (staf) melihat permintaan itu');
        $total_lain = preg_match('/· (\d+) aduan/u', $lain, $m1) ? (int) $m1[1] : NULL;
        $total_semua = (int) satu('SELECT COUNT(*) n FROM aduan')['n'];
        cek($total_lain === NULL || $total_lain < $total_semua, 'Hitungan halaman warga lain tidak ikut menghitung permintaan penghapusan');
    }
    cek(strpos(baca('application/controllers/Pengaturan.php'), 'Aduan_model::JUDUL_PENGHAPUSAN_DATA') !== FALSE,
        'Judul permintaan diambil dari satu konstanta (Aduan_model::JUDUL_PENGHAPUSAN_DATA)');

    /* ============================================================== R3 */
    echo "\n== R3. Konten CMS Ternak\n";
    $racun = '<p>Isi aman ' . $TAG . ' <strong>tebal</strong></p><img src=x onerror="window.ujr=1"><script>window.ujr=2</script>'
        . '<a href="javascript:window.ujr=3">tautan</a><span style="position:fixed" onmouseover="window.ujr=4">s</span>';
    if (is_file(APP_ROOT . '/vendor/autoload.php')) { require_once APP_ROOT . '/vendor/autoload.php'; }
    require_once APPPATH . 'libraries/Ternak_api.php';
    if (cek(method_exists('Ternak_api', 'bersihkan_html'), 'Ada penyaring HTML artikel (Ternak_api::bersihkan_html)')) {
        $hasil = Ternak_api::bersihkan_html($racun);
        cek(strpos($hasil, '<strong>tebal</strong>') !== FALSE, 'Tag teks yang diizinkan tetap ada');
        cek(stripos($hasil, 'onerror') === FALSE && stripos($hasil, 'onmouseover') === FALSE && stripos($hasil, '<script') === FALSE
            && stripos($hasil, 'javascript:') === FALSE && stripos($hasil, 'style=') === FALSE && stripos($hasil, '<img') === FALSE,
            'Atribut on*, style, script, img, dan skema javascript: dibuang');
    }
    // Cache sintetis (tanpa penanda _disaring, seperti cache lama) supaya tidak ada permintaan ke CMS.
    $f_ternak = APPPATH . 'cache/ternak_site_data_jawa-iii.json';
    $asli_ternak = is_file($f_ternak) ? [file_get_contents($f_ternak), filemtime($f_ternak)] : NULL;
    $bersih[] = function () use ($f_ternak, $asli_ternak) {
        if ($asli_ternak) { file_put_contents($f_ternak, $asli_ternak[0]); touch($f_ternak, $asli_ternak[1]); } else { @unlink($f_ternak); }
    };
    $id_art = 990000000 + random_int(1, 99999);
    $kat_racun = 'Kat ' . $TAG . '"><svg onload="window.ujr=5">';
    $sintetis = ['site' => [], 'inherited' => ['articles' => []], 'local' => [
        'articles' => [['id' => $id_art, 'title' => 'Uji ' . $TAG, 'body' => $racun, 'path_image' => 'gambar.jpg" onerror="window.ujr=6',
            'createdAt' => date('c'), 'category' => ['name' => $kat_racun]]],
        'houseDesigns' => [['id' => $id_art, 'title' => 'Desain ' . $TAG, 'description' => 'd', 'path_image' => 'g.jpg', 'path_file' => 'f.pdf',
            'video' => ['link_video' => 'javascript:window.ujr=7']]],
    ]];
    file_put_contents($f_ternak, json_encode($sintetis));
    $jt = jar();
    $detail = minta($jt, 'Index/detail_artikel/' . $id_art)['badan'];
    cek(strpos($detail, 'Isi aman ' . $TAG) !== FALSE, 'PRASYARAT: halaman detail artikel memuat artikel sintetis');
    cek(stripos($detail, 'window.ujr=1') === FALSE && stripos($detail, '<script>window.ujr') === FALSE
        && stripos($detail, 'href="javascript:') === FALSE && stripos($detail, 'onmouseover') === FALSE,
        'Detail artikel: body tidak membawa onerror, script, onmouseover, atau javascript:');
    cek(strpos($detail, '<svg onload') === FALSE && strpos($detail, 'onerror="window.ujr=6') === FALSE,
        'Detail artikel: kategori dan path gambar di-escape');
    $berita = minta($jt, 'Berita')['badan'];
    cek(strpos($berita, $TAG) !== FALSE && strpos($berita, '<script>window.ujr') === FALSE && strpos($berita, '<svg onload') === FALSE,
        '/Berita: data artikel di skrip halaman tidak membawa tag mentah');
    cek(strpos($berita, 'tmp.innerHTML') === FALSE && strpos($berita, 'DOMParser') !== FALSE,
        '/Berita: cuplikan body dibuat lewat DOMParser, bukan innerHTML pada elemen lepas');
    $kartu = minta($jt, 'ajax_articles')['badan'];
    cek(strpos($kartu, (string) $id_art) !== FALSE && strpos($kartu, 'onerror="window.ujr=6') === FALSE,
        'Kartu artikel beranda: path gambar di-escape');
    $desain = minta($jt, 'ajax_house_designs')['badan'] . minta($jt, 'panduan_desain/' . $id_art)['badan'];
    cek(strpos($desain, 'Desain ' . $TAG) !== FALSE && stripos($desain, 'href="javascript:') === FALSE,
        'Kartu dan detail desain: tautan video selain https tidak ditautkan');

    /* ============================================================== R4 */
    echo "\n== R4. Konfirmasi hapus PSU/Asosiasi (penjaga regresi)\n";
    $psu = baca('application/views/admin/psu/index.php');
    $aso = baca('application/views/admin/asosiasi/index.php');
    cek(strpos($psu, 'data-konfirmasi=') !== FALSE && strpos($aso, 'data-konfirmasi=') !== FALSE
        && stripos($psu, 'confirm(') === FALSE && stripos($aso, 'confirm(') === FALSE,
        'Hapus PSU dan Asosiasi memakai data-konfirmasi, bukan confirm() di atribut onsubmit');
    $js_attr = [];
    foreach (glob(APPPATH . 'views/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE) as $v) {
        if (preg_match('/\bon[a-z]+="[^"]*\'[^"\']*<\?=\s*(html_escape|htmlspecialchars)\((?!json_encode|addslashes)/', (string) file_get_contents($v))) {
            $js_attr[] = substr($v, strlen(APPPATH));
        }
    }
    cek($js_attr === [], 'Tidak ada view yang menaruh html_escape di dalam string JS atribut on*' . ($js_attr ? ' (' . implode(', ', $js_attr) . ')' : ''));
    cek(preg_match('/text\.textContent\s*=\s*String\(message\)/', baca('assets/js/notifications.js')) === 1,
        'Dialog konfirmasi menulis pesan lewat textContent');
    $psu_view = $psu . baca('application/views/pages/psu/index.php');
    cek(preg_match_all('/<\?=\s*\$row->(nama_perumahan|nama_pengembang|keterangan)\b/', $psu_view) === 0,
        'Nama perumahan, pengembang, dan keterangan PSU (termasuk hasil impor Excel) tidak pernah dicetak tanpa escape');

    /* ============================================================== R5 */
    echo "\n== R5. NIK pembeli SIKUMBANG tidak masuk cache\n";
    require_once APPPATH . 'helpers/sikumbang_helper.php';
    $nik_palsu = '3399' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
    if (cek(function_exists('sikumbang_buang_nik'), 'Ada pembuang kunci nik* (sikumbang_buang_nik)')) {
        [$d, $n] = sikumbang_buang_nik(['detail' => ['namaPerumahan' => 'Griya Uji'], 'bangunan' => [
            ['blok' => 'A1', 'nikPemilik' => $nik_palsu, 'nikBooking' => $nik_palsu, 'tipe' => ['NIKlain' => 'x', 'luas' => 36]]]]);
        cek($n === 3 && strpos(json_encode($d), $nik_palsu) === FALSE && $d['bangunan'][0]['blok'] === 'A1'
            && $d['bangunan'][0]['tipe']['luas'] === 36 && $d['detail']['namaPerumahan'] === 'Griya Uji',
            'Semua kunci nik* dibuang di setiap kedalaman, medan lain utuh');
    }
    $sk = baca('application/helpers/sikumbang_helper.php');
    cek(preg_match('/function sikumbang_ambil\(.*?sikumbang_buang_nik\(\$urai\).*?return \[\$ok/s', $sk) === 1,
        'sikumbang_ambil membuang nik* sebelum balasan dikembalikan ke penulis cache');
    require_once APPPATH . 'libraries/Penyapu_retensi.php';
    $app_uji = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ujr_app_' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR;
    @mkdir($app_uji . 'cache', 0700, TRUE);
    $f_det = $app_uji . 'cache' . DIRECTORY_SEPARATOR . 'sikumbang_detail_UJI' . strtoupper(bin2hex(random_bytes(3))) . '.json';
    $f_lain = $app_uji . 'cache' . DIRECTORY_SEPARATOR . 'sikumbang_detail_UJIBERSIH.json';
    file_put_contents($f_det, json_encode(['detail' => ['namaPerumahan' => 'Griya Uji'], 'bangunan' => [['blok' => 'B2', 'nikPemilik' => $nik_palsu]]]));
    file_put_contents($f_lain, json_encode(['detail' => ['namaPerumahan' => 'Griya Bersih']]));
    $mtime_lama = time() - 2 * 86400;
    touch($f_det, $mtime_lama); touch($f_lain, $mtime_lama);
    $bersih[] = function () use ($app_uji, $f_det, $f_lain) { @unlink($f_det); @unlink($f_lain); @rmdir($app_uji . 'cache'); @rmdir($app_uji); };
    $penyapu = new Penyapu_retensi(['policy' => ['interval_detik' => 1], 'app' => $app_uji, 'db' => NULL]);
    if (cek(method_exists($penyapu, 'sapu_nik_sikumbang'), 'Penyapu retensi punya tugas pembersih NIK cache SIKUMBANG')) {
        $m = new ReflectionMethod('Penyapu_retensi', 'sapu_nik_sikumbang');
        $m->setAccessible(TRUE);
        $kering = $m->invoke($penyapu, TRUE);
        cek($kering['jumlah'] === 1 && strpos(file_get_contents($f_det), $nik_palsu) !== FALSE, 'Mode kering hanya menghitung (1 berkas), berkas tidak diubah');
        $jalan = $m->invoke($penyapu, FALSE);
        clearstatcache();
        $isi = (string) file_get_contents($f_det);
        cek($jalan['jumlah'] === 1 && strpos($isi, $nik_palsu) === FALSE && stripos($isi, '"nik') === FALSE && strpos($isi, 'Griya Uji') !== FALSE,
            'Jalan sungguhan: NIK dibuang dari berkas cache, data perumahan tetap');
        cek(filemtime($f_det) === $mtime_lama && is_file($f_lain) && filemtime($f_lain) === $mtime_lama,
            'Umur berkas dipertahankan dan cache tanpa NIK tidak disentuh');
        cek($m->invoke($penyapu, FALSE)['jumlah'] === 0, 'Putaran kedua: nol berkas (idempoten)');
        cek(strpos(baca('application/libraries/Penyapu_retensi.php'), "\$hasil['cache_nik_sikumbang'] = \$this->sapu_nik_sikumbang(\$kering)") !== FALSE,
            'Tugas itu ikut jalankan() sehingga hasilnya (jumlah saja) masuk jejak audit retensi');
    }
    $lc = baca('application/config/data_lifecycle.php');
    cek(preg_match("/'helpers\/sikumbang_helper\.php'.*?nikPemilik.*?'pribadi' => TRUE/s", $lc) === 1,
        'Register aliran data menyebut NIK pembeli SIKUMBANG dan menandainya pribadi');

    /* ============================================================== R6 */
    echo "\n== R6. Pencarian NIK antrean tidak lewat URL\n";
    $nik_cari = '3399' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
    foreach (['Admin' => 'agen_admin@agen.test', 'Admin_Kabkota' => 'agen_admin_kabkota@agen.test'] as $hal => $staf) {
        $js = masuk_halaman($staf, SANDI_AGEN);
        if ( ! cek($js !== NULL, "PRASYARAT: $staf bisa masuk")) { continue; }
        $awal = minta($js, $hal)['badan'];
        cek(preg_match('/<form method="post" action="[^"]*\/' . $hal . '"/', $awal) === 1, "$hal: kotak cari antrean dikirim POST");
        $r = minta($js, $hal, ['q' => $nik_cari], ['ikuti' => FALSE]);
        cek(in_array($r['kode'], [302, 303], TRUE) && strpos($r['lokasi'], $nik_cari) === FALSE && strpos($r['lokasi'], 'cari=') !== FALSE,
            "$hal: POST NIK dijawab redirect ke ?cari=<token>, tanpa NIK di URL");
        $hasil = strpos($r['lokasi'], BASE) === 0 ? minta($js, substr($r['lokasi'], strlen(BASE)))['badan'] : '';
        cek($hasil !== '' && strpos($hasil, $nik_cari) === FALSE && strpos($hasil, 'berakhiran ' . substr($nik_cari, -4)) !== FALSE,
            "$hal: halaman hasil tidak memuat NIK utuh, hanya label bertopeng");
        $get = minta($js, $hal . '?q=' . $nik_cari)['badan'];
        cek(substr_count($get, 'q=' . $nik_cari) === 0, "$hal: ?q=<NIK> lama tidak disalin ke tautan filter, urutan, dan halaman");
    }

    /* ============================================================== R7 */
    echo "\n== R7. Batas laju formulir aduan\n";
    $kunci_ip_aduan = hash('sha256', "aduan_kirim_ip:ip:$IP");
    pinjam_ember($kunci_ip_aduan);
    $push_hidup = is_dir(APP_ROOT . '/vendor/minishlink');
    if (cek( ! $push_hidup, 'PRASYARAT: paket Web Push tidak terpasang di lokal (kiriman aduan uji tidak memicu push ke perangkat sungguhan)')) {
        [$Wa, $ea] = akun_baru('Pelapor Beruntun');
        $ja = masuk_halaman($ea, $SANDI);
        $kode = [];
        for ($i = 1; $i <= 6; $i++) {
            minta($ja, 'umum/aduan');
            $kode[] = minta($ja, 'umum/simpan_aduan', ['nama' => 'Pelapor Beruntun', 'email' => $ea, 'judul' => "Aduan uji $i $TAG", 'pesan' => 'Isi uji'], ['ikuti' => FALSE])['kode'];
        }
        $n = (int) satu('SELECT COUNT(*) n FROM aduan WHERE user_id=?', [$Wa])['n'];
        cek(count(array_filter(array_slice($kode, 0, 5), fn($k) => $k === 303 || $k === 302)) === 5, 'Lima aduan pertama dalam satu jam diterima');
        cek($kode[5] === 429 && $n === 5, 'Aduan keenam dari akun yang sama dalam satu jam ditolak 429 (tersimpan ' . $n . ')');
        [$Wb, $eb] = akun_baru('Pelapor Satu IP');
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci_ip_aduan]);
        jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?, NOW(), 20)', [$kunci_ip_aduan]);
        $jb = masuk_halaman($eb, $SANDI);
        minta($jb, 'umum/aduan');
        $r = minta($jb, 'umum/simpan_aduan', ['nama' => 'Pelapor Satu IP', 'email' => $eb, 'judul' => "Aduan IP $TAG", 'pesan' => 'Isi uji'], ['ikuti' => FALSE]);
        cek($r['kode'] === 429 && (int) satu('SELECT COUNT(*) n FROM aduan WHERE user_id=?', [$Wb])['n'] === 0,
            'Akun baru dari IP yang sudah 20 kali mengirim dalam sejam ikut ditolak (batas per IP)');
    }

    /* ============================================================== R8 */
    echo "\n== R8. Username dan login email\n";
    [$Wpen, $epen] = akun_baru('Penyerang Username');      // id lebih kecil
    [$Wkor, $ekor] = akun_baru('Korban Email');            // id lebih besar
    $jpen = masuk_halaman($epen, $SANDI);
    if (cek($jpen !== NULL, 'PRASYARAT: akun penyerang bisa masuk')) {
        $u0 = satu('SELECT nama_pengguna FROM usr_akun WHERE id=?', [$Wpen])['nama_pengguna'];
        minta($jpen, 'akun/profil');
        minta($jpen, 'akun/update', ['username' => $ekor, 'name' => 'Penyerang Username', 'phone' => '']);
        cek(satu('SELECT nama_pengguna FROM usr_akun WHERE id=?', [$Wpen])['nama_pengguna'] === $u0, 'Profil Saya menolak username berupa email akun lain');
        minta($jpen, 'akun/update', ['username' => 'bukan@email', 'name' => 'Penyerang Username', 'phone' => '']);
        cek(satu('SELECT nama_pengguna FROM usr_akun WHERE id=?', [$Wpen])['nama_pengguna'] === $u0, 'Profil Saya menolak username ber-@');
        minta($jpen, 'akun/update', ['username' => $TAG . 'baru', 'name' => 'Penyerang Username', 'phone' => '']);
        cek(satu('SELECT nama_pengguna FROM usr_akun WHERE id=?', [$Wpen])['nama_pengguna'] === $TAG . 'baru', 'PEMBANDING: username biasa tetap bisa diganti');
    }
    cek(preg_match('/function save_onboarding.*?username_ditolak\(/s', baca('application/controllers/Auth.php')) === 1,
        'Onboarding memakai aturan username yang sama');
    // Data lama: username yang terlanjur sama dengan email akun lain tidak boleh membelokkan login email.
    jalan('UPDATE usr_akun SET nama_pengguna=? WHERE id=?', [$ekor, $Wpen]);
    [, $json] = coba_masuk($ekor, $SANDI);
    cek(($json['status'] ?? '') === 'success' && ($json['name'] ?? '') === 'Korban Email',
        'Login dengan email korban masuk ke akun korban, walau ada username yang sama dengan email itu');

    /* ============================================================== R9 */
    echo "\n== R9. Endpoint langganan Web Push\n";
    [$Wpu, $epu] = akun_baru('Warga Push');
    $jpu = masuk_halaman($epu, $SANDI);
    if (cek($jpu !== NULL, 'PRASYARAT: warga bisa masuk')) {
        minta($jpu, 'akun/profil');
        $kunci = ['p256dh' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U', 'auth' => 'tBHItJI5svbpez7KI4CCXg'];
        $langgan = function ($endpoint) use ($jpu, $kunci) {
            return minta($jpu, 'push/subscribe', ['subscription' => json_encode(['endpoint' => $endpoint, 'keys' => $kunci])], ['ajax' => TRUE])['kode'];
        };
        $ditolak = [];
        foreach (['https://penyerang.example/x', 'https://127.0.0.1/x', 'https://fcm.googleapis.com.penyerang.example/x', 'https://fcm.googleapis.com:8443/x'] as $e) {
            $ditolak[] = $langgan($e);
        }
        cek(array_unique($ditolak) === [422] && (int) satu('SELECT COUNT(*) n FROM sys_langganan_notifikasi WHERE user_id=?', [$Wpu])['n'] === 0,
            'Host di luar layanan push resmi, IP, dan port lain ditolak 422 (' . json_encode($ditolak) . ')');
        cek($langgan('https://fcm.googleapis.com/fcm/send/uji' . $TAG) === 200 && (int) satu('SELECT COUNT(*) n FROM sys_langganan_notifikasi WHERE user_id=?', [$Wpu])['n'] === 1,
            'PEMBANDING: endpoint FCM diterima');
    }
    cek(preg_match("/'allow_redirects'\s*=>\s*FALSE/", baca('application/libraries/Web_push_service.php')) === 1, 'Klien Web Push tidak mengikuti pengalihan');
    cek(preg_match('/function untuk_audiens.*?endpoint_sah\(/s', baca('application/models/Push_subscription_model.php')) === 1,
        'Langganan lama dengan host di luar daftar tidak dipilih untuk dikirimi');

    /* ============================================================== R10 */
    echo "\n== R10. assets/cache_foto tidak terlacak\n";
    $terlacak = trim((string) shell_exec('git -C ' . escapeshellarg(APP_ROOT) . ' ls-files assets/cache_foto'));
    cek($terlacak === '', 'git ls-files assets/cache_foto kosong (' . ($terlacak === '' ? 0 : count(explode("\n", $terlacak))) . ' berkas terlacak)');
    cek(preg_match('#^/assets/cache_foto/#m', baca('.gitignore')) === 1, 'Folder tetap di .gitignore');
} catch (Throwable $e) {
    cek(FALSE, 'Suite melempar ' . get_class($e) . ': ' . $e->getMessage() . ' (baris ' . $e->getLine() . ')');
} finally {
    foreach (array_reverse($bersih) as $f) { try { $f(); } catch (Throwable $e) { echo '  (bersih gagal: ' . $e->getMessage() . ")\n"; } }
    foreach ($akun_uji as $id) {
        jalan('DELETE FROM aduan WHERE user_id=?', [$id]);
        jalan('DELETE FROM sys_langganan_notifikasi WHERE user_id=?', [$id]);
        jalan('DELETE FROM usr_akun WHERE id=?', [$id]);
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', [$baris['kunci'], $baris['jendela_mulai_at'], $baris['jumlah_gagal']]); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    $sisa = satu("SELECT COUNT(*) n FROM usr_akun WHERE email LIKE '%@uji-rendah.test'");
    cek((int) $sisa['n'] === 0, 'Nol akun uji tertinggal');
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
