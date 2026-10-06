<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/**
 * Menu SEO Halaman (Admin_Seo, migrasi 075; permintaan user 6 Okt 2026).
 *
 *   php docs/engineering/uji_seo_admin.php
 *
 * Dijaga:
 *   1. Super Admin melihat daftar halaman; akun lain ditolak; endpoint tulis hanya POST.
 *   2. Timpaan judul, deskripsi, dan gambar tampil di halaman publik (title, description, og:image);
 *      gambar unggahan dikodekan ulang ke JPG 1200x630; judul kepanjangan dan kunci asing ditolak.
 *   3. Indeks bisa dipaksa disembunyikan; Kembalikan menghapus timpaan dan berkas unggahannya.
 *
 * Halaman uji: /simulasi_kpr. Timpaan dan berkas buatan suite dihapus sendiri; ember login dipinjam.
 */
define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}
$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    if ( ! array_key_exists(trim($k), $env)) { $env[trim($k)] = trim($v, " \t\"'"); }
}
mysqli_report(MYSQLI_REPORT_OFF);
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
    cek(FALSE, 'DB lokal (suite ini menulis timpaan SEO)');
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
    exit(1);
}
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
function satu($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map('strval', $p)); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : NULL;
}
function jalan($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($x) => $x === NULL ? NULL : (string) $x, $p)); }
    $st->execute();
    return $st->affected_rows;
}
function minta($jar, $path, $post = NULL) {
    $c = curl_init(BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        // CURLFile hanya terkirim lewat array (multipart); selain itu pakai bentuk urlencoded.
        $ada_berkas = (bool) array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($c, CURLOPT_POSTFIELDS, $ada_berkas ? $post : http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $r = ['kode' => curl_getinfo($c, CURLINFO_HTTP_CODE), 'url' => curl_getinfo($c, CURLINFO_EFFECTIVE_URL), 'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8')];
    curl_close($c);
    return $r;
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'usa'); }
function masuk($email, $sandi) { $j = jar(); minta($j, 'Auth/login'); minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]); return $j; }
$ember_asli = [];
function ember_ip($policy) {
    global $ember_asli;
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
        $k = hash('sha256', "$policy:ip:$ip");
        if ( ! array_key_exists($k, $ember_asli)) { $ember_asli[$k] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$k]); }
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$k]);
    }
}
function meta($b, $re) { return preg_match($re, $b, $x) ? html_entity_decode($x[1], ENT_QUOTES, 'UTF-8') : NULL; }
function pesan_ada($badan, $jenis, $kata) {
    if ( ! preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $badan, $m)) { return FALSE; }
    foreach (json_decode($m[1], TRUE) ?: [] as $p) { if (($p['type'] ?? '') === $jenis && strpos((string) ($p['message'] ?? ''), $kata) !== FALSE) { return TRUE; } }
    return FALSE;
}

$KUNCI = 'simulasi_kpr';
$AGEN_SANDI = 'AgenUji!2026'; // sandi mainan seed_agen_peran.php, hanya ada di DB lokal
$baris_asli = satu('SELECT * FROM seo_halaman WHERE kunci=?', [$KUNCI]);
$gambar_uji = sys_get_temp_dir() . '/uji_seo_' . bin2hex(random_bytes(3)) . '.jpg';
// Salinan aset JPG 1920x1072, bukan dibuat dengan GD: PHP CLI runner suite bisa tanpa ekstensi GD.
copy(APP_ROOT . '/assets/img/hero/hero-perumahan-subsidi-opt.jpeg', $gambar_uji);
$berkas_baru = NULL;

try {
    if ($baris_asli) { jalan('DELETE FROM seo_halaman WHERE kunci=?', [$KUNCI]); }
    echo "== 1. Akses\n";
    ember_ip('login');
    $jA = masuk('agen_admin@agen.test', $AGEN_SANDI);
    $r = minta($jA, 'Admin_Seo');
    cek($r['kode'] === 200 && strpos($r['badan'], 'data-seo-daftar') !== FALSE && strpos($r['badan'], '/program-pemerintah/oemah-lestari') !== FALSE,
        'Super Admin membuka daftar SEO Halaman (halaman config dan program)');
    $jW = masuk('agen_warga@agen.test', $AGEN_SANDI);
    $r = minta($jW, 'Admin_Seo');
    cek(strpos($r['badan'], 'data-seo-daftar') === FALSE, 'Warga tidak bisa membuka SEO Halaman');
    cek(minta($jA, 'Admin_Seo/simpan')['kode'] >= 400, 'Simpan hanya menerima POST');

    echo "\n== 2. Timpaan tampil di halaman publik\n";
    $r = minta($jA, 'Admin_Seo/ubah?halaman=' . $KUNCI);
    cek(strpos($r['badan'], 'data-seo-ubah') !== FALSE, 'Formulir ubah terbuka');
    $r = minta($jA, 'Admin_Seo/simpan', ['kunci' => $KUNCI, 'judul' => str_repeat('x', 61), 'deskripsi' => '', 'indeks' => 'bawaan']);
    cek(pesan_ada($r['badan'], 'error', 'maksimal') && ! satu('SELECT id FROM seo_halaman WHERE kunci=?', [$KUNCI]), 'Judul lebih dari 60 karakter ditolak');
    minta($jA, 'Admin_Seo/ubah?halaman=' . $KUNCI);
    $r = minta($jA, 'Admin_Seo/simpan', ['kunci' => 'akun/profil', 'judul' => 'Coba', 'indeks' => 'ya']);
    cek($r['kode'] === 404 && ! satu('SELECT id FROM seo_halaman WHERE kunci=?', ['akun/profil']), 'Halaman di luar daftar (pribadi) tidak bisa diatur');
    minta($jA, 'Admin_Seo/ubah?halaman=' . $KUNCI);
    $r = minta($jA, 'Admin_Seo/simpan', ['kunci' => $KUNCI, 'judul' => 'Hitung Cicilan KPR Subsidi', 'deskripsi' => 'Deskripsi uji SEO admin untuk simulasi KPR.',
        'indeks' => 'bawaan', 'gambar' => new CURLFile($gambar_uji, 'image/jpeg', 'uji.jpg')]);
    $row = satu('SELECT * FROM seo_halaman WHERE kunci=?', [$KUNCI]);
    $berkas_baru = $row['gambar'] ?? NULL;
    cek(pesan_ada($r['badan'], 'success', 'disimpan') && $row && $row['judul'] === 'Hitung Cicilan KPR Subsidi', 'Timpaan tersimpan');
    $ukuran = $berkas_baru ? @getimagesize(APP_ROOT . '/' . $berkas_baru) : FALSE;
    cek($ukuran && $ukuran[0] === 1200 && $ukuran[1] === 630 && $ukuran['mime'] === 'image/jpeg' && strpos($berkas_baru, 'assets/img/og/unggahan/') === 0,
        'Gambar unggahan dikodekan ulang ke JPG 1200x630 di folder unggahan');
    $p = minta(jar(), $KUNCI)['badan'];
    cek(meta($p, '#<title>(.*?)</title>#') === 'Hitung Cicilan KPR Subsidi | Klinik PKP Jawa Tengah'
        && meta($p, '#<meta name="description" content="([^"]*)"#') === 'Deskripsi uji SEO admin untuk simulasi KPR.'
        && meta($p, '#<meta property="og:image" content="([^"]*)"#') === BASE . $berkas_baru, 'Halaman publik memakai judul, deskripsi, dan gambar timpaan');

    echo "\n== 3. Indeks dan kembalikan\n";
    minta($jA, 'Admin_Seo/ubah?halaman=' . $KUNCI);
    minta($jA, 'Admin_Seo/simpan', ['kunci' => $KUNCI, 'judul' => 'Hitung Cicilan KPR Subsidi', 'deskripsi' => '', 'indeks' => 'tidak']);
    $p = minta(jar(), $KUNCI)['badan'];
    cek(strpos((string) meta($p, '#<meta name="robots" content="([^"]*)"#'), 'noindex') !== FALSE && strpos($p, 'rel="canonical"') === FALSE
        && satu('SELECT gambar FROM seo_halaman WHERE kunci=?', [$KUNCI])['gambar'] === $berkas_baru, 'Indeks dipaksa disembunyikan; gambar unggahan tetap');
    minta($jA, 'Admin_Seo/ubah?halaman=' . $KUNCI);
    $r = minta($jA, 'Admin_Seo/kembalikan', ['kunci' => $KUNCI]);
    $p = minta(jar(), $KUNCI)['badan'];
    cek( ! satu('SELECT id FROM seo_halaman WHERE kunci=?', [$KUNCI]) && ! is_file(APP_ROOT . '/' . $berkas_baru)
        && strpos((string) meta($p, '#<title>(.*?)</title>#'), 'Simulasi KPR') === 0 && strpos((string) meta($p, '#<meta name="robots" content="([^"]*)"#'), 'noindex') === FALSE,
        'Kembalikan menghapus timpaan dan berkasnya; halaman kembali ke bawaan');
    cek((int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE aksi IN ('seo_diubah','seo_dikembalikan') AND objek_id=? AND created_at >= NOW() - INTERVAL 5 MINUTE", [$KUNCI])['n'] >= 3,
        'Perubahan tercatat di jejak audit');
} catch (Throwable $e) {
    cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    $row = satu('SELECT gambar FROM seo_halaman WHERE kunci=?', [$KUNCI]);
    jalan('DELETE FROM seo_halaman WHERE kunci IN (?, ?)', [$KUNCI, 'akun/profil']);
    foreach (array_filter([$berkas_baru, $row['gambar'] ?? NULL]) as $g) { if (strpos($g, 'assets/img/og/unggahan/') === 0) { @unlink(APP_ROOT . '/' . $g); } }
    if ($baris_asli) { jalan('INSERT INTO seo_halaman (' . implode(',', array_keys($baris_asli)) . ') VALUES (' . implode(',', array_fill(0, count($baris_asli), '?')) . ')', array_values($baris_asli)); }
    jalan("DELETE FROM sys_jejak_audit WHERE aksi IN ('seo_diubah','seo_dikembalikan') AND objek_id=? AND created_at >= NOW() - INTERVAL 10 MINUTE", [$KUNCI]);
    foreach ($ember_asli as $k => $b) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$k]);
        if ($b) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($b)); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    @unlink($gambar_uji);
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
