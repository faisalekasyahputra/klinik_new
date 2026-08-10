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

// ------------------------------------------------------- B1: masa berlaku
/* Butir B1, 5 Agt 2026. Yang dijaga di sini BUKAN kolomnya melainkan
   PERILAKU SIMPANNYA, dan itu karena bug yang sudah berjalan hari ini:
   `Admin_Srp2::save()` merakit payload PENUH, sementara form BARIS di tabel
   tidak punya input `sosmed_lainnya`. Setiap "Simpan" pada sebuah baris
   menge-NULL-kan kolom itu diam-diam — nol dari 67 baris direktori punya
   nilai di sana. Dua kolom tanggal akan bernasib sama tanpa perbaikan akar,
   dan tanggal sertifikat yang hilang sendiri jauh lebih mahal. */
echo "\n== B1 — masa berlaku sertifikat ==\n";

/* Sesi superadmin dibuat DI SINI, dan prasyaratnya diperiksa keras.
   Versi pertama blok ini memakai sesi 'adm' yang tidak pernah di-login —
   POST-nya diarahkan ke layar masuk, DB tidak tersentuh, dan ketiga asersi
   "nilainya bertahan" lulus justru karena TIDAK TERJADI APA-APA. Uji yang
   hijau karena tidak menyentuh apa pun lebih berbahaya daripada uji merah. */
[$uidAdm, $emailAdm] = buat_akun('admin', 'adm');
wajib(login('adm', $emailAdm), 'Login superadmin uji');
wajib(strpos(http('adm', 'Admin_Srp2')['body'], 'Tambah pengembang') !== FALSE,
    'Sesi adm benar-benar sampai ke layar Direktori SRP2');

foreach (['sertifikat_terbit', 'sertifikat_berakhir'] as $k) {
    cek((int) nilai("SELECT COUNT(*) c FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_certified_developers'
          AND COLUMN_NAME = ? AND DATA_TYPE = 'date' AND IS_NULLABLE = 'YES'", [$k]) === 1,
        "Kolom `{$k}` DATE NULL ada (migrasi 037)");
}

$nama_b1 = CAP . ' Masa Berlaku';
$sert_b1 = tulis('INSERT INTO srp2_certified_developers
    (nama_perusahaan,status_aktif,sosmed_lainnya,sertifikat_terbit,sertifikat_berakhir,created_at)
    VALUES (?,1,?,?,?,NOW())',
    [$nama_b1, 'https://uji.test/sosmed', '2024-01-15', '2027-01-14']);
$GLOBALS['sertifikat'][] = $sert_b1;

/* Simpan ulang MENGIRIM HANYA nama + status — persis seperti form baris lama.
   Ketiga medan yang tidak dikirim HARUS bertahan. */
$tok_b1 = csrf('adm', 'Admin_Srp2');
http('adm', 'Admin_Srp2/save', [
    'csrf_kpkp_token' => $tok_b1, 'id' => $sert_b1,
    'nama_perusahaan' => $nama_b1, 'status_aktif' => 1,
]);
$sesudah = q('SELECT sosmed_lainnya, sertifikat_terbit, sertifikat_berakhir
              FROM srp2_certified_developers WHERE id=?', [$sert_b1]);
cek(($sesudah['sertifikat_terbit'] ?? '') === '2024-01-15',
    'Tanggal terbit BERTAHAN saat form tidak mengirimnya');
cek(($sesudah['sertifikat_berakhir'] ?? '') === '2027-01-14',
    'Tanggal akhir BERTAHAN saat form tidak mengirimnya');
cek(($sesudah['sosmed_lainnya'] ?? '') === 'https://uji.test/sosmed',
    'sosmed_lainnya BERTAHAN — bug lama yang menyapunya sudah tertutup');

/* Medan DIKIRIM KOSONG tetap harus mengosongkan. Kalau tidak, admin yang salah
   isi tanggal tidak punya cara membatalkannya. */
http('adm', 'Admin_Srp2/save', [
    'csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'), 'id' => $sert_b1,
    'nama_perusahaan' => $nama_b1, 'status_aktif' => 1, 'sertifikat_terbit' => '',
]);
cek(nilai('SELECT sertifikat_terbit t FROM srp2_certified_developers WHERE id=?', [$sert_b1]) === NULL,
    'Dikirim kosong tetap MENGOSONGKAN — beda dari tidak dikirim');

// Terbit sesudah berakhir ditolak.
http('adm', 'Admin_Srp2/save', [
    'csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'), 'id' => $sert_b1,
    'nama_perusahaan' => $nama_b1, 'status_aktif' => 1,
    'sertifikat_terbit' => '2030-01-01', 'sertifikat_berakhir' => '2029-01-01',
]);
cek(nilai('SELECT sertifikat_terbit t FROM srp2_certified_developers WHERE id=?', [$sert_b1]) !== '2030-01-01',
    'Terbit melewati tanggal akhir DITOLAK');

/* MariaDB lokal berjalan TANPA STRICT: '' pada kolom DATE mendarat sebagai
   '0000-00-00' tanpa galat, dan tanggal itu lolos ke layar. */
cek(nilai('SELECT COUNT(*) c FROM srp2_certified_developers
           WHERE sertifikat_terbit = ? OR sertifikat_berakhir = ?',
          ['0000-00-00', '0000-00-00']) === 0,
    'Nol tanggal 0000-00-00 di direktori');

/* Pengembang TIDAK boleh menulis masa berlaku sertifikatnya sendiri:
   `upsert_direktori_publik()` punya dua pemanggil, dan yang kedua adalah
   `Pengaturan::update_pengembang_profile()` — pengembang itu sendiri. */
$auth_src = (string) @file_get_contents(APP_ROOT . '/application/models/Auth_model.php');
cek($auth_src !== '' && preg_match('/function upsert_direktori_publik.*?\n    \}/s', $auth_src, $mu)
    && strpos($mu[0], 'sertifikat_') === FALSE,
    'upsert_direktori_publik() NOL kolom sertifikat — pengembang tidak menulis masa berlakunya sendiri');


/* ══════════════════════════════════════════════════════════════════════════
   BUTIR 7, 8, 12 PUTARAN 2 — status bertingkat, kabupaten, asosiasi, NPWP.

   Yang dijaga bukan "kolomnya ada", melainkan tiga janji yang bisa rusak
   TANPA satu pun galat:

     1. NPWP TIDAK PERNAH sampai ke halaman publik. Ini yang paling mahal:
        `Pengembang::profil()` dulu `SELECT *`, jadi menambah kolom saja sudah
        cukup untuk membocorkannya tanpa ada yang menyentuh baris itu.
     2. Satu NPWP hanya boleh dipakai satu baris (inti butir 8).
     3. Penanda masa berlaku DITURUNKAN, bukan disimpan — supaya tak pernah basi.
   ══════════════════════════════════════════════════════════════════════════ */
echo "\n== Butir 7/8/12: status, wilayah, asosiasi, NPWP ==\n";

/* Dibersihkan DI AWAL juga, bukan cuma di akhir. Kalau blok ini gagal di
   tengah, barisnya tertinggal — dan NPWP-nya yang ber-UNIQUE membuat
   jalankan berikutnya gagal di PRASYARAT, bukan di hal yang sedang diuji.
   Kejadian nyata saat menulis penjaga ini, dan justru membuktikan UNIQUE-nya
   bekerja. */
$GLOBALS['db']->query("DELETE FROM srp2_certified_developers WHERE nama_perusahaan LIKE 'UJI SRP2 %'");
$npwpA  = '09' . str_pad((string) mt_rand(1, 999999999999), 13, '0', STR_PAD_LEFT);
$namaA  = 'UJI SRP2 Alpha ' . mt_rand(1000, 9999);
$besok  = date('Y-m-d', strtotime('+1 day'));
$kemarin = date('Y-m-d', strtotime('-1 day'));

http('adm', 'Admin_Srp2/save', ['csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'),
    'nama_perusahaan' => $namaA, 'status_aktif' => 1,
    'status_sertifikasi' => 'masih_proses', 'asosiasi' => 'REI',
    'npwp' => $npwpA, 'kabupaten_id' => 0]);

$barisA = q('SELECT * FROM srp2_certified_developers WHERE nama_perusahaan = ?', [$namaA]);
wajib($barisA && ! isset($barisA['__id']), 'PRASYARAT: baris uji benar-benar tersimpan');

cek($barisA['status_sertifikasi'] === 'masih_proses', 'Status bertingkat tersimpan apa adanya');
cek($barisA['asosiasi'] === 'REI', 'Asosiasi tersimpan (butir 12)');
cek( ! empty($barisA['npwp_ciphertext']) && $barisA['npwp_ciphertext'] !== $npwpA,
    'NPWP disimpan TERENKRIPSI, bukan apa adanya');
cek(strlen((string) $barisA['npwp_lookup_hash']) === 64, 'Sidik pencarian NPWP terbentuk');
cek(strpos((string) $barisA['npwp_ciphertext'], $npwpA) === FALSE,
    'Angka NPWP tidak muncul mentah di dalam ciphertext');

/* Janji 1 — diperiksa dari HALAMAN PUBLIK sungguhan. Baris ini ditayangkan
   supaya benar-benar dirender; tanpa itu "NPWP tidak muncul" lulus hampa
   karena halamannya kosong. */
$publik = http('tamu_srp2', 'pengembang/profil/' . (int) $barisA['id']);
/* stripos, bukan strpos: halaman profil menampilkan nama perusahaan dalam
   HURUF KAPITAL. Versi pertama penjaga ini memakai strpos dan merah bukan
   karena produknya salah — probe manual kami kebetulan bernama kapital, jadi
   nyaris menyimpulkan halamannya rusak. */
wajib(stripos($publik['body'], $namaA) !== FALSE,
    'PRASYARAT: profil publik pengembang uji benar-benar terbuka');
cek(strpos($publik['body'], $npwpA) === FALSE, 'NPWP tidak muncul di profil publik');
cek(strpos($publik['body'], (string) $barisA['npwp_ciphertext']) === FALSE,
    'Ciphertext NPWP pun tidak ikut terkirim ke halaman publik');
cek(strpos($publik['body'], (string) $barisA['npwp_lookup_hash']) === FALSE,
    'Sidik pencarian tidak ikut terkirim — ia deterministik, jadi bisa diuji-tebak');

/* 🔻 DAN SATU PENJAGA STRUKTURAL, karena keempat cek di atas TIDAK menjaga
   daftar SELECT-nya. Terbukti lewat mutasi: mengembalikan `SELECT *` di
   `Pengembang::profil()` membuat keempatnya TETAP HIJAU — view-nya memang
   hanya mencetak medan tertentu, jadi ciphertext berhenti di memori PHP dan
   tidak sampai ke halaman.

   Risikonya laten, bukan nihil: begitu ada yang menambahkan satu perulangan
   atas seluruh medan baris itu — atau satu dump saat menelusuri galat —
   ciphertext dan sidik pencariannya ikut tercetak. Yang dijaga di sini adalah
   KEPUTUSANNYA: kolom disebut satu per satu, sehingga menambah kolom sensitif
   berikutnya menuntut orang memutuskan sadar apakah ia boleh ikut. */
$peng_src = (string) @file_get_contents(APP_ROOT . '/application/controllers/Pengembang.php');
cek(preg_match('/select\(\s*.id, nama_perusahaan/', $peng_src) === 1,
    'profil() menyebut kolom satu per satu, bukan SELECT * (struktural)');
/* Dicocokkan ke DAFTAR SELECT-nya, bukan ke seluruh berkas. Versi pertama
   mencari 'npwp' di mana pun dan langsung merah — satu-satunya penyebutan
   di sana adalah KOMENTAR PENJELASAN kami sendiri. Pola yang sama sudah
   pernah terjadi 5 Agt (penjaga "Cek Backlog" merah oleh komentarnya
   sendiri) dan sudah dicatat di AGENTS.md; kami mengulanginya hari ini. */
preg_match('/->select\(([^;]*?)\)\s*
\s*->get_where\(.srp2_certified_developers/s', $peng_src, $msel);
cek( ! empty($msel[1]) && stripos($msel[1], 'npwp') === FALSE,
    'Daftar SELECT profil publik TIDAK memuat satu pun kolom npwp');

/* Janji 2 — NPWP kembar ditolak. Dibaca dari JUMLAH BARIS, bukan pesan layar. */
$sebelum = (int) nilai('SELECT COUNT(*) c FROM srp2_certified_developers');
http('adm', 'Admin_Srp2/save', ['csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'),
    'nama_perusahaan' => 'UJI SRP2 Kembar ' . mt_rand(1000, 9999), 'status_aktif' => 1,
    'status_sertifikasi' => 'bersertifikat', 'npwp' => $npwpA, 'kabupaten_id' => 0]);
cek((int) nilai('SELECT COUNT(*) c FROM srp2_certified_developers') === $sebelum,
    'NPWP kembar DITOLAK — satu NPWP satu pengembang (butir 8)');

/* Janji 3 — penanda masa berlaku diturunkan. Diperiksa dari LAYAR, bukan dari
   kolom: yang dijanjikan ke dinas adalah apa yang mereka lihat. */
$kol = q("SELECT GROUP_CONCAT(COLUMN_NAME) c FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_certified_developers'");
cek(strpos((string) $kol['c'], 'status_berlaku') === FALSE,
    'TIDAK ada kolom penanda masa berlaku yang disimpan — ia diturunkan');

http('adm', 'Admin_Srp2/save', ['csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'),
    'id' => (int) $barisA['id'], 'nama_perusahaan' => $namaA, 'status_aktif' => 1,
    'status_sertifikasi' => 'bersertifikat', 'sertifikat_berakhir' => $kemarin,
    'npwp' => $npwpA, 'kabupaten_id' => 0]);
cek(strpos(http('adm', 'Admin_Srp2?q=' . urlencode($namaA))['body'], 'masa berlaku habis') !== FALSE,
    'Sertifikat yang habis kemarin ditandai non-aktif di layar');

http('adm', 'Admin_Srp2/save', ['csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'),
    'id' => (int) $barisA['id'], 'nama_perusahaan' => $namaA, 'status_aktif' => 1,
    'status_sertifikasi' => 'bersertifikat', 'sertifikat_berakhir' => $besok,
    'npwp' => $npwpA, 'kabupaten_id' => 0]);
$layarAktif = http('adm', 'Admin_Srp2?q=' . urlencode($namaA))['body'];
cek(strpos($layarAktif, 'masa berlaku habis') === FALSE,
    'Sertifikat yang masih berlaku TIDAK ditandai habis');

/* Status di luar daftar ditolak, tidak diam-diam diabaikan. */
http('adm', 'Admin_Srp2/save', ['csrf_kpkp_token' => csrf('adm', 'Admin_Srp2'),
    'id' => (int) $barisA['id'], 'nama_perusahaan' => $namaA, 'status_aktif' => 1,
    'status_sertifikasi' => 'status_karangan', 'kabupaten_id' => 0]);
cek(nilai('SELECT status_sertifikasi FROM srp2_certified_developers WHERE id = ?',
    [(int) $barisA['id']]) === 'bersertifikat',
    'Status karangan ditolak — nilai lama tidak berubah');

$GLOBALS['db']->query('DELETE FROM srp2_certified_developers WHERE id = ' . (int) $barisA['id']);
$GLOBALS['db']->query("DELETE FROM srp2_certified_developers WHERE nama_perusahaan LIKE 'UJI SRP2 %'");


bersihkan();
$GLOBALS['regs'] = $GLOBALS['users'] = $GLOBALS['sertifikat'] = [];
cek((int) nilai('SELECT COUNT(*) c FROM srp2_certified_developers WHERE nama_perusahaan LIKE ?', [CAP . '%']) === 0
    && (int) nilai('SELECT COUNT(*) c FROM usr_users WHERE email LIKE ?', ['uji_bang_%']) === 0,
    'Data uji dibersihkan');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
