<?php
/**
 * Uji peran `pengembang` (SRP2) yang BISA DIJALANKAN DI DB DEV.
 *
 *   php docs/engineering/uji_pengembang_srp2_dev.php
 *
 * Sudah ada `uji_perjalanan_srp2.php`, dan ia lebih dalam: menempuh perjalanan
 * penuh sampai direktori publik, plus uji transaksi tabel-kedua-gagal. Tapi
 * headernya mensyaratkan DB uji bersih terpisah, jadi dalam praktiknya ia nyaris
 * tidak pernah dijalankan — runner pun menandainya `lewat`. Akibatnya peran
 * `pengembang` efektif nol cakupan sehari-hari, padahal ia memegang unggahan
 * dokumen perusahaan: KTP, NIB, laporan keuangan, akta pengurus.
 *
 * Berkas ini sengaja TIDAK menduplikasi perjalanan penuh itu. Ia mengambil
 * bagian yang paling mahal kalau bocor dan paling murah diuji di DB bersama:
 *
 *   1. ANTI-IDOR di empat pintu — baca dokumen, tulis dokumen, kirim pengajuan,
 *      dan profil publik. Semua id datang dari URL; satu-satunya yang menahan
 *      adalah `WHERE user_id` dari sesi.
 *   2. GERBANG HULU kirim_pengajuan. Baris yang mustahil disetujui tidak boleh
 *      lahir jadi Pending sama sekali — bukan ditolak nanti di meja admin.
 *   3. KUNCI SETELAH DIKIRIM. Pending/Diterima berarti dokumen tidak bisa
 *      diganti diam-diam saat sedang ditinjau.
 *   4. LEDGER ADA TAPI BERKAS HILANG. Insiden nyata 29 Jul 2026; pemilik sah
 *      berhak tahu bedanya "tidak pernah ada" dan "tercatat namun hilang".
 *
 * Semua akun dan baris dibuat serta dihapus sendiri.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiBang!2026');
define('CAP', 'UJIBANG' . date('YmdHis'));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['regs'] = [];
$GLOBALS['sertifikat'] = [];
$GLOBALS['jar'] = [];

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
    $id  = $stmt->insert_id;
    $stmt->close();
    return $out ?: ['__id' => $id];
}
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }
function nilai($sql, $p = []) { $r = q($sql, $p); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujp_'); }
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

/**
 * Token CSRF halaman.
 *
 * DUA bentuk beredar dan harus dicoba keduanya: `<input name="csrf_kpkp_token">`
 * di form biasa, dan `<meta name="csrf-token-hash">` di halaman yang menulis
 * lewat fetch — wizard SRP2 termasuk yang kedua, dan `Pengembang/syarat` sama
 * sekali tidak punya input CSRF.
 *
 * Mengembalikan '' dianggap KESALAHAN FATAL, bukan token kosong. Versi pertama
 * berkas ini hanya mengenal bentuk input, jadi setiap POST-nya ditolak 403 —
 * dan SELURUH uji negatifnya lulus hampa: "status tidak berubah" memang benar,
 * tapi karena CSRF, bukan karena gerbang yang sedang diuji. Uji negatif yang
 * lulus tanpa pernah menyentuh kodenya lebih berbahaya daripada uji yang merah.
 */
function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/name="csrf-token-hash" content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    fwrite(STDERR, "Berhenti: token CSRF tidak ditemukan di /{$path}. "
        . "Tanpa token, seluruh uji tulis di berkas ini lulus hampa.\n");
    bersihkan();
    exit(1);
}

function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'), 'email' => $email, 'password' => SANDI,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix) {
    $email = 'uji_bang_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
         VALUES (?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Bang ' . $suffix,
         'uji_bang_' . $suffix . '_' . mt_rand(10000, 99999), $peran]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

function dir_srp2($id) {
    $akar = $GLOBALS['akar_privat'];
    return rtrim($akar, '/\\') . DIRECTORY_SEPARATOR . 'srp2' . DIRECTORY_SEPARATOR . (int) $id;
}

/** Isi ledger + berkas fisiknya, seperti unggahan yang benar-benar mendarat. */
function isi_dokumen($reg_id, array $kunci) {
    $dir = dir_srp2($reg_id);
    if ( ! is_dir($dir)) { @mkdir($dir, 0777, TRUE); }
    foreach ($kunci as $k) {
        $simpan = $k . '_' . mt_rand(100000, 999999) . '.pdf';
        file_put_contents($dir . DIRECTORY_SEPARATOR . $simpan, "%PDF-1.4\n% " . $k . "\n%%EOF\n");
        q('INSERT INTO srp2_documents (registration_id,document_key,original_name,stored_name,mime_type,file_size,created_at)
           VALUES (?,?,?,?,"application/pdf",64,NOW())', [$reg_id, $k, $k . '.pdf', $simpan]);
    }
}

function status_reg($id) { return (string) nilai('SELECT status_verifikasi FROM srp2_registrations WHERE id=?', [$id]); }

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['regs'] as $id) {
        $dir = dir_srp2($id);
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) { @unlink($f); }
            @rmdir($dir);
        }
        q('DELETE FROM srp2_documents WHERE registration_id=?', [$id]);
        q('DELETE FROM srp2_registrations WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['sertifikat'] as $id) { q('DELETE FROM srp2_certified_developers WHERE id=?', [$id]); }
    foreach ($GLOBALS['users'] as $id) {
        // Draft bisa lahir sendiri saat GET /Pengembang/syarat — sapu berdasarkan
        // pemiliknya, bukan cuma id yang sempat kita catat.
        foreach ($GLOBALS['db']->query('SELECT id FROM srp2_registrations WHERE user_id=' . (int) $id) as $r) {
            $dir = dir_srp2($r['id']);
            if (is_dir($dir)) {
                foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) { @unlink($f); }
                @rmdir($dir);
            }
            q('DELETE FROM srp2_documents WHERE registration_id=?', [$r['id']]);
        }
        q('DELETE FROM srp2_registrations WHERE user_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

$akar = $env['PRIVATE_UPLOADS_PATH'] ?? '';
if ($akar === '') { die("PRIVATE_UPLOADS_PATH wajib untuk uji ini.\n"); }
if ( ! preg_match('#^(?:[A-Za-z]:|[/\\\\])#', $akar)) { $akar = APP_ROOT . DIRECTORY_SEPARATOR . $akar; }
$GLOBALS['akar_privat'] = $akar;

echo "=== UJI PENGEMBANG SRP2 (DB dev) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

$SEMUA_DOK = ['form_1','form_2a','form_2b','form_3','form_4','form_5','form_6',
              'form_6b','form_7','form_8','form_9','form_10','form_11','form_13'];

[$uidA, $emailA] = buat_akun('pengembang', 'a');
[$uidB, $emailB] = buat_akun('pengembang', 'b');
[$uidW, $emailW] = buat_akun('warga', 'w');

// ------------------------------------------------------------------ gerbang
wajib(login('a', $emailA), 'Login pengembang A');
$s = http('a', 'Pengembang/syarat');
wajib($s['code'] === 200, 'Pengembang mendapat wizard syarat');

// Draft dibuat aplikasi sendiri lewat srp2_state() — dipakai apa adanya alih-alih
// disuntik, supaya yang diuji adalah baris yang benar-benar dilahirkan produk.
$regA = (int) nilai('SELECT id FROM srp2_registrations WHERE user_id=? ORDER BY id DESC LIMIT 1', [$uidA]);
wajib($regA > 0, 'Draft pengajuan A lahir dari kunjungan wizard');
$GLOBALS['regs'][] = $regA;

wajib(login('b', $emailB), 'Login pengembang B');
http('b', 'Pengembang/syarat');
$regB = (int) nilai('SELECT id FROM srp2_registrations WHERE user_id=? ORDER BY id DESC LIMIT 1', [$uidB]);
wajib($regB > 0 && $regB !== $regA, 'Draft pengajuan B lahir terpisah');
$GLOBALS['regs'][] = $regB;

isi_dokumen($regA, $SEMUA_DOK);
isi_dokumen($regB, ['form_1']);

/**
 * SYARAT & FORMULIR WAJIB LOGIN — revisi dinas 3 Agt 2026.
 *
 * Asersi lama di sini berbunyi "Warga tetap dapat halaman syarat (publik)" dan
 * memang benar untuk perilaku LAMA. Dibalik, bukan dihapus: yang dijaga
 * sekarang adalah isinya TIDAK TERKIRIM sama sekali kepada yang belum berhak.
 *
 * Diperiksa dari ISI, bukan dari kode HTTP. Halamannya memang tetap 200 —
 * pintu masuk (masuk/daftar cepat) harus tetap terbuka, jadi redirect buta ke
 * Auth/login justru menutup jalur pendaftarannya sendiri. Yang membedakan
 * "digerbangi" dari "disembunyikan" adalah apakah HTML-nya ikut terkirim, dan
 * `x-show` Alpine tetap mengirimkannya.
 */
$anon = http('anon', 'Pengembang/syarat');
foreach (['Akta notaris', 'Kemenkumham', 'Form 2.A', 'lampiran_SRPP'] as $rahasia) {
    cek(strpos($anon['body'], $rahasia) === FALSE,
        "Anonim: isi syarat \"{$rahasia}\" tidak ikut terkirim");
}
cek(strpos($anon['body'], 'Daftar Cepat') !== FALSE, 'Anonim: pintu masuk/daftar tetap terbuka');

wajib(login('w', $emailW), 'Login warga');
$w = http('w', 'Pengembang/syarat');
cek(strpos($w['body'], 'Akta notaris') === FALSE, 'Warga (salah peran) tidak kebagian isi syarat');
cek(strpos($w['body'], 'form_10') === FALSE, 'Warga tidak kebagian daftar dokumen di konfigurasi wizard');
// Yang dibuktikan: TIDAK ADA ISI DOKUMEN YANG SAMPAI — bukan "dibalas 404".
// Untuk permintaan non-AJAX, `akses_pengembang()` me-REDIRECT peran yang salah
// ke halaman masuk, jadi curl yang mengikuti redirect menerima 200 dari halaman
// lain. Memeriksa kodenya membuat uji ini merah untuk gerbang yang bekerja.
$wd = http('w', 'Pengembang/lihat_dokumen_saya/' . $regA . '/form_1');
cek(strpos($wd['body'], '%PDF') === FALSE, 'Warga tidak kebagian isi dokumen pengembang mana pun');

// -------------------------------------------- keterangan per formulir (butir 4-5)
/**
 * Yang dijaga: keterangan SAMPAI ke wizard pemohon, dan formulir yang belum
 * punya keterangan tidak memaksa apa pun tampil. Isi keterangannya sendiri
 * sengaja tidak dipatok di sini — dinas masih akan melengkapinya, dan uji yang
 * mematok kalimatnya akan merah tiap kali satu formulir dilengkapi.
 */
$syarat_a = http('a', 'Pengembang/syarat');
cek(strpos($syarat_a['body'], 'Data bisa didapatkan dari asosiasi') !== FALSE,
    'Keterangan form 10/11 sampai ke wizard pemohon');
cek(preg_match('/&quot;keterangan&quot;:\{/', $syarat_a['body']) === 1
    || strpos($syarat_a['body'], '"keterangan":{') !== FALSE,
    'Konfigurasi wizard membawa peta keterangan');
cek(strpos($syarat_a['body'], 'Akta notaris') !== FALSE,
    'Pengembang TETAP melihat isi syarat (gerbang tidak kebablasan)');

// ------------------------------------------------------------ anti-IDOR baca
$sendiri = http('a', 'Pengembang/lihat_dokumen_saya/' . $regA . '/form_1');
cek($sendiri['code'] === 200 && strpos($sendiri['body'], '%PDF') === 0, 'Dokumen sendiri tersaji');

$lain = http('a', 'Pengembang/lihat_dokumen_saya/' . $regB . '/form_1');
cek($lain['code'] === 404, 'Dokumen pengembang lain dibalas 404');
cek(strpos($lain['body'], '%PDF') === FALSE, 'Isi dokumen pengembang lain tidak ikut terkirim');
cek(http('a', 'Pengembang/lihat_dokumen_saya/' . $regA . '/form_tidak_ada')['code'] === 404,
    'Kunci dokumen yang tidak dikenal ditolak');
cek(http('a', 'Pengembang/lihat_dokumen_saya/abc/form_1')['code'] === 404, 'Id bukan angka ditolak');

// ------------------------------------------------------------ anti-IDOR tulis
cek(http('a', 'Pengembang/simpan_dokumen/' . $regA)['code'] === 404, 'GET ke simpan_dokumen ditolak');
cek(http('a', 'Pengembang/kirim_pengajuan/' . $regA)['code'] === 404, 'GET ke kirim_pengajuan ditolak');

$dok_b_sebelum = (int) nilai('SELECT COUNT(*) c FROM srp2_documents WHERE registration_id=?', [$regB]);
$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/simpan_dokumen/' . $regB, ['csrf_kpkp_token' => $tok, 'document_key' => 'form_2a']);
cek((int) nilai('SELECT COUNT(*) c FROM srp2_documents WHERE registration_id=?', [$regB]) === $dok_b_sebelum,
    'Menulis dokumen ke pengajuan orang lain tidak menambah baris');

$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/kirim_pengajuan/' . $regB, ['csrf_kpkp_token' => $tok]);
cek(status_reg($regB) !== 'Pending', 'Mengirim pengajuan orang lain tidak mengubah statusnya');

// ------------------------------------------------------------- gerbang hulu
// (a) dokumen belum lengkap — B baru punya satu.
//
// Nama perusahaannya SENGAJA diisi lebih dulu. Tanpa itu, gerbang nama-kosong
// yang menahannya, bukan gerbang kelengkapan yang sedang diuji: mutasi yang
// melumpuhkan pemeriksaan 14-dokumen tetap membuat uji ini hijau. Uji negatif
// harus menyisakan TEPAT SATU alasan gagal, kalau tidak ia mengukur hal lain
// daripada namanya.
q('UPDATE srp2_registrations SET nama_perusahaan=? WHERE id=?', [CAP . ' Bangun Sendiri', $regB]);

// Tidak perlu login ulang: tiap nama sesi punya cookie jar sendiri yang tetap
// terautentikasi sepanjang skrip. Login berulang justru menambah permukaan
// gagal yang tidak ada hubungannya dengan apa yang diuji di sini.
$tok = csrf('b', 'Pengembang/syarat');
http('b', 'Pengembang/kirim_pengajuan/' . $regB, ['csrf_kpkp_token' => $tok]);
cek(status_reg($regB) !== 'Pending', 'Dokumen belum lengkap: pengajuan tidak lahir jadi Pending');

// (b) nama perusahaan kosong — 14 dokumen saja tidak cukup.
q('UPDATE srp2_registrations SET nama_perusahaan=NULL WHERE id=?', [$regA]);
$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/kirim_pengajuan/' . $regA, ['csrf_kpkp_token' => $tok]);
cek(status_reg($regA) !== 'Pending', 'Nama perusahaan kosong: pengajuan tidak lahir jadi Pending');

// (c) nama bentrok dengan direktori publik milik orang lain. Barisnya HARUS
//     tidak pernah lahir: kolom nama di direktori NOT NULL + UNIQUE, jadi kalau
//     ia lolos ke meja admin, approve-nya mustahil dan pemohon menunggu sesuatu
//     yang tidak akan pernah terjadi.
$nama_bentrok = CAP . ' Membangun Jaya';
$sert = tulis('INSERT INTO srp2_certified_developers (nama_perusahaan,status_aktif,created_at)
               VALUES (?,1,NOW())', [$nama_bentrok]);
$GLOBALS['sertifikat'][] = $sert;
q('UPDATE srp2_registrations SET nama_perusahaan=? WHERE id=?', [$nama_bentrok, $regA]);
$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/kirim_pengajuan/' . $regA, ['csrf_kpkp_token' => $tok]);
cek(status_reg($regA) !== 'Pending', 'Nama bentrok direktori: pengajuan tidak lahir jadi Pending');

// ------------------------------------------------------------ jalur positif
// `reviewed_by` punya FK ke usr_users — id karangan (dulu 1) langsung ditolak
// DB. Dipakai akun uji yang benar-benar ada; ia toh cuma perlu jadi jejak lama
// yang harus terhapus saat kirim ulang.
q('UPDATE srp2_registrations SET nama_perusahaan=?, catatan_admin="catatan lama", reviewed_by=?, reviewed_at=NOW()
   WHERE id=?', [CAP . ' Karya Mandiri', $uidW, $regA]);
$tok = csrf('a', 'Pengembang/syarat');
$kirim = http('a', 'Pengembang/kirim_pengajuan/' . $regA, ['csrf_kpkp_token' => $tok]);
cek($kirim['code'] === 200, 'Kirim pengajuan lengkap diterima server');
cek(status_reg($regA) === 'Pending', 'Status berubah menjadi Pending');

// Jejak keputusan LAMA harus ikut bersih. Kalau tidak, /akun menampilkan badge
// "Dalam Peninjauan" DITAMBAH kotak penolakan lama — dua permukaan yang
// sama-sama dilihat pemohon menceritakan hal berbeda.
cek(nilai('SELECT catatan_admin FROM srp2_registrations WHERE id=?', [$regA]) === NULL,
    'Catatan penolakan lama ikut dibersihkan saat kirim ulang');
cek(nilai('SELECT reviewed_at FROM srp2_registrations WHERE id=?', [$regA]) === NULL,
    'Jejak waktu tinjauan lama ikut dibersihkan');

// -------------------------------------------------------- kunci pasca-kirim
$dok_a_sebelum = (int) nilai('SELECT COUNT(*) c FROM srp2_documents WHERE registration_id=?', [$regA]);
$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/simpan_dokumen/' . $regA, ['csrf_kpkp_token' => $tok, 'document_key' => 'form_1']);
cek((int) nilai('SELECT COUNT(*) c FROM srp2_documents WHERE registration_id=?', [$regA]) === $dok_a_sebelum,
    'Dokumen terkunci setelah status Pending');

$tok = csrf('a', 'Pengembang/syarat');
http('a', 'Pengembang/kirim_pengajuan/' . $regA, ['csrf_kpkp_token' => $tok]);
cek(status_reg($regA) === 'Pending', 'Kirim ulang saat Pending tidak menggandakan apa pun');

// ------------------------------------------- ledger ada, berkasnya lenyap
// Insiden nyata 29 Jul 2026. Pemilik sah berhak tahu bedanya "tidak pernah ada"
// dan "tercatat namun hilang" — 404 bisu membuat pemohon menyalahkan dirinya.
$hilang = (string) nilai('SELECT stored_name FROM srp2_documents WHERE registration_id=? AND document_key="form_3"', [$regA]);
@unlink(dir_srp2($regA) . DIRECTORY_SEPARATOR . $hilang);
$r = http('a', 'Pengembang/lihat_dokumen_saya/' . $regA . '/form_3');
cek($r['code'] === 200 && strpos($r['body'], '%PDF') === FALSE,
    'Berkas hilang dari disk tidak dibalas 404 bisu ke pemiliknya');
cek(stripos($r['body'], 'tidak tersedia') !== FALSE || stripos($r['body'], 'unggah ulang') !== FALSE,
    'Pemilik diberi tahu dokumennya hilang dan apa yang harus dilakukan');

// ------------------------------------------------------------ profil publik
cek(http('a', 'Pengembang/profil/' . $sert)['code'] === 200, 'Profil pengembang bersertifikat aktif terbuka');
q('UPDATE srp2_certified_developers SET status_aktif=0 WHERE id=?', [$sert]);
cek(http('a', 'Pengembang/profil/' . $sert)['code'] === 404, 'Profil yang dinonaktifkan tidak lagi terbuka');
cek(http('a', 'Pengembang/profil/abc')['code'] === 404, 'Profil dengan id bukan angka ditolak');

bersihkan();
$GLOBALS['regs'] = $GLOBALS['users'] = $GLOBALS['sertifikat'] = [];
cek((int) nilai('SELECT COUNT(*) c FROM srp2_certified_developers WHERE nama_perusahaan LIKE ?', [CAP . '%']) === 0
    && (int) nilai('SELECT COUNT(*) c FROM usr_users WHERE email LIKE ?', ['uji_bang_%']) === 0,
    'Data uji dibersihkan');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
