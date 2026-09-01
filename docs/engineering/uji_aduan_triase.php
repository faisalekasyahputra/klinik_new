<?php
/**
 * Uji TRIASE ADUAN - aduan lahir tanpa bidang, superadmin yang merutekan.
 *
 *   php docs/engineering/uji_aduan_triase.php
 *
 * Revisi dinas 3 Agt 2026 mencabut dropdown "Bidang Tujuan" dari formulir aduan.
 * Yang dijaga di sini bukan "dropdownnya sudah hilang" - itu terlihat mata dan
 * tidak perlu harness. Yang dijaga adalah enam janji yang rusaknya SENYAP:
 *
 *   1. GERBANGNYA DI SERVER, BUKAN DI FORMULIR. Menghapus <select> hanya
 *      menghapus cara SOPAN mengirim bidang. Kalau `simpan_aduan()` masih
 *      membaca POST['bidang'], siapa pun bisa merutekan aduannya sendiri ke
 *      bidang mana pun lewat satu field tersembunyi - melewati triase lewat
 *      pintu belakang, dan tidak ada satu pun layar yang akan terlihat aneh.
 *      Karena itu di sini formulirnya dikirim BESERTA `bidang` yang sah.
 *   2. NULL BENAR-BENAR MENYEMBUNYIKAN. Klaim "NULL tidak cocok dengan WHERE
 *      mana pun" diuji dengan MEMBUKA meja admin bidang, bukan dengan membaca
 *      ulang kodenya.
 *   3. RUTE TIDAK BISA DIUBAH SETELAH DIPROSES, tapi MASIH BISA diperbaiki
 *      selagi 'Baru'. Dua-duanya diuji; menguji satu saja akan hijau untuk
 *      endpoint yang menolak semuanya, maupun untuk yang menerima semuanya.
 *   4. YANG DITOLAK DIBUKTIKAN LEWAT KEADAAN DB, BUKAN KODE HTTP. Permintaan
 *      dari peran yang salah DIALIHKAN, jadi curl yang mengikuti redirect
 *      menerima 200 dari halaman lain. Uji yang berhenti di "kodenya bukan 200"
 *      akan hijau untuk endpoint yang terbuka lebar.
 *   5. PAPAN ADUAN TIDAK MEMBOCORKAN ISI. Diperiksa dengan menanam penanda unik
 *      di `pesan` dan `email` lalu menyapu SELURUH sumber halaman - bukan dengan
 *      memeriksa kolom yang kebetulan diingat.
 *   6. JEJAKNYA DUA ARAH: yang berhasil DAN yang ditolak.
 *
 * Data & akun dibuat sendiri, dibersihkan sendiri (termasuk baris
 * `sys_jejak_audit` yang ditimbulkannya). Email uji berakhiran @example.test
 * supaya sensus kebocoran di jalankan_semua.php bisa melihatnya.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiTriase!2026');

// Penanda unik: dipakai untuk menemukan baris uji DAN untuk membuktikan bahwa
// isi aduan tidak muncul di papan.
define('CAP', 'UJITRIASE' . date('YmdHis') . mt_rand(100, 999));
define('RAHASIA_PESAN', 'RAHASIAISI' . CAP);
define('NAMA_PELAPOR', 'Zulfikar Bagaskara Wicaksana');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['aduan'] = [];
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
    $id = $stmt->insert_id;
    $stmt->close();
    return $out ?: ['__id' => $id];
}

function tulis($sql, $params = []) { return (int) (q($sql, $params)['__id'] ?? 0); }
function nilai($sql, $params = []) { $r = q($sql, $params); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

/**
 * Bidang satu aduan APA ADANYA - NULL tetap NULL, bukan '' .
 * nilai() mengembalikan NULL juga untuk baris yang tidak ada, jadi keberadaan
 * barisnya dipastikan lewat pemanggilnya (id-nya baru saja dibuat).
 */
function bidang_aduan($id) { return nilai('SELECT bidang FROM aduan WHERE id=?', [$id]); }
function status_aduan($id) { return nilai('SELECT status FROM aduan WHERE id=?', [$id]); }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) {
        $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujtr_');
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
 * tidak ketemu. Shell admin hanya memasang <input>; shell portal memasang meta.
 * POST tanpa token ditolak SEBELUM controller mana pun berjalan - dan penolakan
 * itu tidak mengubah apa-apa, jadi uji tulis yang kehilangan tokennya jadi hijau
 * hampa: ia mengira sudah membuktikan gerbangnya menahan, padahal yang ditahan
 * cuma tokennya sendiri.
 */
function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }

    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path} (sesi '{$nama}', kode {$r['code']}).\n"
        . "Tanpa token setiap POST di berkas ini ditolak CSRF dan seluruh uji tulis lulus hampa.\n");
    exit(1);
}

function login($nama, $email, $sandi = SANDI) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $sandi,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix, $bidang_kode = NULL) {
    $email = 'uji_triase_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,bidang_kode,status,profile_completed,created_at)
         VALUES (?,?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Triase ' . $suffix,
         'uji_triase_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $bidang_kode]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/**
 * Kirim satu aduan lewat FORMULIR SUNGGUHAN, bukan INSERT langsung.
 * Insert langsung akan melewati persis jalur yang sedang diuji.
 */
function kirim_aduan($nama_sesi, $judul, array $tambahan = []) {
    $r = http($nama_sesi, 'umum/simpan_aduan', [
        'csrf_kpkp_token' => csrf($nama_sesi, 'umum/aduan'),
        'nama'  => NAMA_PELAPOR,
        'email' => 'pelapor_' . CAP . '@example.test',
        'judul' => $judul,
        'pesan' => 'Isi aduan yang tidak boleh muncul di papan: ' . RAHASIA_PESAN,
    ] + $tambahan);
    $id = (int) nilai('SELECT id FROM aduan WHERE judul=? ORDER BY id DESC LIMIT 1', [$judul]);
    if ($id) { $GLOBALS['aduan'][] = $id; }
    return [$id, $r];
}

/** Berapa baris jejak untuk satu aksi atas satu aduan, sejak uji ini mulai. */
function jejak($aksi, $id) {
    return (int) nilai(
        "SELECT COUNT(*) c FROM sys_jejak_audit
         WHERE aksi=? AND objek_tipe='aduan' AND objek_id=? AND created_at >= ?",
        [$aksi, (string) $id, MULAI]);
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['aduan'] as $id) {
        q("DELETE FROM sys_jejak_audit WHERE objek_tipe='aduan' AND objek_id=?", [(string) $id]);
        q('DELETE FROM aduan WHERE id=?', [$id]);
    }
    // Sisa jejak milik akun uji (aksi yang objeknya bukan aduan, mis. login).
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['aduan'] = $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI TRIASE ADUAN (bidang NULL -> superadmin merutekan) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

// ------------------------------------------------ PRASYARAT SKEMA
echo "== 0. Prasyarat skema (migrasi 20260701000034) ==\n";
$kolom = q("SELECT IS_NULLABLE n, COLUMN_DEFAULT d FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aduan' AND COLUMN_NAME='bidang'");
wajib(($kolom['n'] ?? '') === 'YES', 'Kolom aduan.bidang sudah NULL-able');
// MariaDB 10.2+ mengembalikan STRING 'NULL' untuk DEFAULT NULL, bukan SQL NULL.
// Membandingkan ke NULL akan merah untuk skema yang benar - yang diperiksa di
// sini adalah absennya sentinel 'umum', bukan bentuk balasan information_schema.
cek(stripos((string) $kolom['d'], 'umum') === FALSE, "DEFAULT 'umum' yang basi sudah dicabut");
wajib((int) nilai("SELECT COUNT(*) c FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sys_jejak_audit'") === 1,
    'Tabel sys_jejak_audit ada (migrasi 20260701000033)');

$bidang_a = nilai('SELECT kode FROM bidang ORDER BY kode ASC LIMIT 1');
$bidang_b = nilai('SELECT kode FROM bidang ORDER BY kode DESC LIMIT 1');
wajib($bidang_a && $bidang_b && $bidang_a !== $bidang_b, "Ada minimal dua bidang ({$bidang_a}, {$bidang_b})");

[$idSuper, $emailSuper] = buat_akun('admin', 'super');
[$idBidangA, $emailBidangA] = buat_akun('admin_bidang', 'bidangA', $bidang_a);
[$idWarga, $emailWarga] = buat_akun('user', 'warga');

wajib(login('super', $emailSuper), 'Login superadmin');
wajib(login('bidA', $emailBidangA), 'Login admin bidang ' . $bidang_a);
wajib(login('warga', $emailWarga), 'Login warga');

// ------------------------------------------------ 1. FORMULIR
echo "\n== 1. Formulir tidak lagi meminta bidang ==\n";
/* 🔻 KONTRAKNYA DIBALIK 17 Agt 2026 (a77909d) dan harness ini menyusul 1 Sep
   2026. Aduan TIDAK lagi boleh dikirim tamu: gerbang dipasang di aduan() (form)
   DAN di simpan_aduan() (POST), yang kedua karena endpointnya bisa dipanggil
   langsung tanpa lewat form. Asersi lama "halaman aduan terbuka untuk tamu"
   karena itu bukan cuma usang, ia menuntut kebalikan dari keputusan sekarang. */
$tamuAduan = http('tamu', 'umum/aduan');
cek(strpos($tamuAduan['url'], 'Auth/login') !== FALSE, 'Tamu diarahkan ke login, aduan tidak lagi terbuka');
cek(strpos($tamuAduan['body'], 'id="aduan-form"') === FALSE, 'Formulir aduan tidak ikut terkirim ke tamu');

$form = http('warga', 'umum/aduan');
cek($form['code'] === 200, 'Warga yang login mendapat halaman aduan');

/**
 * Diperiksa di dalam <form>-nya saja, BUKAN menyapu seluruh halaman.
 *
 * Versi pertama pemeriksaan ini memakai `stripos($body, 'Bidang Tujuan')` dan
 * langsung merah untuk halaman yang sebenarnya sudah benar: frasa itu juga ada
 * di FAQ footer portal - yang justru baru ditulis ulang pada revisi yang sama
 * ("Anda tidak perlu memilih bidang tujuan"). Persis jebakan yang sudah
 * terdokumentasi di uji_regresi_tampilan.php, dan tetap saja terinjak.
 */
preg_match('/<form[^>]+id="aduan-form".*?<\/form>/s', $form['body'], $fm);
$formulir = $fm[0] ?? '';
wajib($formulir !== '', 'Formulir aduan terbaca dari halaman');
cek(stripos($formulir, 'Bidang Tujuan') === FALSE, 'Label "Bidang Tujuan" tidak ada lagi di formulir');
cek(strpos($form['body'], 'opsiBidang') === FALSE, 'Daftar bidang tidak lagi dikirim ke browser');
cek( ! preg_match('/<(input|select)[^>]*name="bidang"/i', $form['body']),
    'Tidak ada field name="bidang" tersisa (termasuk yang tersembunyi)');

// ------------------------------------------------ 2. LAHIR NULL
echo "\n== 2. Aduan lahir tanpa bidang ==\n";
/* Pengirimnya warga yang login, bukan tamu: sejak a77909d simpan_aduan()
   punya gerbangnya sendiri, jadi kiriman tamu tidak akan pernah lahir. */
[$id1, $r1] = kirim_aduan('warga', 'Uji triase A ' . CAP);
wajib($id1 > 0, 'Aduan terkirim lewat formulir sungguhan (id ' . $id1 . ')');
cek(bidang_aduan($id1) === NULL, 'Bidangnya NULL - masuk antrean triase');
cek(status_aduan($id1) === 'Baru', 'Statusnya Baru');
cek(stripos($r1['body'], 'diarahkan ke') === FALSE,
    'Pesan sukses tidak lagi menjanjikan bidang tujuan');

// ------------------------------------------------ 3. GERBANG DI SERVER
echo "\n== 3. `bidang` dari POST DIABAIKAN, bukan cuma dihapus dari formulir ==\n";
/**
 * Ini pemeriksaan terpenting di berkas ini. Menghapus <select> hanya menghapus
 * cara sopan mengirimkannya; kalau controllernya masih membaca POST['bidang'],
 * gerbang triase dilewati lewat satu field tersembunyi dan tak ada layar yang
 * terlihat aneh. Kode yang dikirim SAH, jadi yang diuji bukan validasi input
 * melainkan APAKAH NILAINYA DIPAKAI SAMA SEKALI.
 */
[$id2, ] = kirim_aduan('warga', 'Uji triase selundupan ' . CAP, ['bidang' => $bidang_b]);
wajib($id2 > 0, 'Aduan dengan field bidang selundupan terkirim (id ' . $id2 . ')');
cek(bidang_aduan($id2) === NULL,
    "POST bidang={$bidang_b} DIABAIKAN - pelapor tidak bisa merutekan aduannya sendiri");

// ------------------------------------------------ 4. NULL = TAK TERLIHAT
echo "\n== 4. Belum ditriase = tidak muncul di meja bidang mana pun ==\n";
$meja = http('bidA', 'Admin_Bidang');
wajib($meja['code'] === 200 && strpos($meja['url'], 'Auth/login') === FALSE,
    'Meja admin bidang terbuka');
cek(strpos($meja['body'], 'Uji triase A ' . CAP) === FALSE,
    'Aduan ber-bidang NULL TIDAK muncul di meja admin bidang');

$papan_admin = http('super', 'Admin_Aduan');
cek(strpos($papan_admin['body'], 'Uji triase A ' . CAP) !== FALSE,
    'Tapi superadmin tetap melihatnya di Pantau Aduan');
cek(stripos($papan_admin['body'], 'menunggu diteruskan') !== FALSE,
    'Callout antrean triase muncul (hambatan terlihat tanpa dicari)');
$saring = http('super', 'Admin_Aduan?bidang=belum');
cek(strpos($saring['body'], 'Uji triase A ' . CAP) !== FALSE,
    'Filter "Belum diteruskan" menampilkan aduan yang belum dirutekan');

// ------------------------------------------------ 5. PENOLAKAN
echo "\n== 5. Yang harus ditolak (dibuktikan lewat DB, bukan kode HTTP) ==\n";
$t_super = csrf('super', 'Admin_Aduan');

http('super', 'Admin_Aduan/triase/' . $id1, ['csrf_kpkp_token' => $t_super, 'bidang' => 'bidang_ngawur_xyz']);
cek(bidang_aduan($id1) === NULL, 'Kode bidang ngawur ditolak - bidang tetap NULL');

http('super', 'Admin_Aduan/triase/' . $id1, ['csrf_kpkp_token' => $t_super, 'bidang' => '']);
cek(bidang_aduan($id1) === NULL, 'Bidang kosong ditolak');

cek(http('super', 'Admin_Aduan/triase/' . $id1 . '?bidang=' . $bidang_a)['code'] === 404,
    'GET ke endpoint triase dibalas 404 - hanya POST yang boleh menulis');
cek(bidang_aduan($id1) === NULL, 'GET tadi tidak mengubah apa pun');

http('super', 'Admin_Aduan/triase/' . $id1, ['bidang' => $bidang_a]);   // tanpa token
cek(bidang_aduan($id1) === NULL, 'POST tanpa token CSRF tidak mengubah apa pun');

// Peran lain: dialihkan, jadi kode HTTP-nya 200 dari halaman lain - yang
// membuktikan penolakan adalah keadaan DB.
http('warga', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('warga', 'umum/aduan'), 'bidang' => $bidang_a]);
cek(bidang_aduan($id1) === NULL, 'Warga TIDAK bisa mentriase (dicek dari DB, bukan kode HTTP)');

http('bidA', 'Admin_Bidang');   // pastikan sesinya hidup
http('bidA', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('bidA', 'umum/aduan'), 'bidang' => $bidang_a]);
cek(bidang_aduan($id1) === NULL, 'Admin bidang TIDAK bisa mentriase - itu kewenangan superadmin');

// ------------------------------------------------ 6. TRIASE BERHASIL
echo "\n== 6. Triase oleh superadmin ==\n";
$r = http('super', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('super', 'Admin_Aduan'), 'bidang' => $bidang_a]);
cek($r['code'] === 200, 'Permintaan triase diterima server');
cek(bidang_aduan($id1) === $bidang_a, "Bidang menjadi {$bidang_a}");
cek(status_aduan($id1) === 'Baru', 'Status TIDAK ikut berubah - triase merutekan, bukan memutuskan');
cek(jejak('aduan_ditriase', $id1) === 1, 'Satu baris jejak audit aduan_ditriase tercatat');

$meja2 = http('bidA', 'Admin_Bidang');
cek(strpos($meja2['body'], 'Uji triase A ' . CAP) !== FALSE,
    'Sesudah ditriase, aduannya MUNCUL di meja admin bidang yang dituju');

// ------------------------------------------------ 7. SALAH RUTE MASIH BISA DIPERBAIKI
echo "\n== 7. Selagi masih Baru, salah rute masih bisa diperbaiki ==\n";
http('super', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('super', 'Admin_Aduan'), 'bidang' => $bidang_b]);
cek(bidang_aduan($id1) === $bidang_b, "Rute dipindah ke {$bidang_b}");
cek(jejak('aduan_ditriase', $id1) === 2, 'Perpindahannya ikut tercatat (dua baris jejak)');

// Kembalikan ke bidang A supaya admin bidang A bisa memprosesnya di bagian 8.
http('super', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('super', 'Admin_Aduan'), 'bidang' => $bidang_a]);
wajib(bidang_aduan($id1) === $bidang_a, "Dikembalikan ke {$bidang_a} untuk bagian berikutnya");

// ------------------------------------------------ 8. SUDAH DIPROSES = RUTE TERKUNCI
echo "\n== 8. Sesudah diproses admin bidang, rutenya terkunci ==\n";
$JAWABAN = 'Sudah ditinjau lapangan, ' . CAP;
http('bidA', 'Admin_Bidang/update_status/' . $id1, [
    'csrf_kpkp_token' => csrf('bidA', 'umum/aduan'),
    'status' => 'Diproses', 'catatan_admin' => $JAWABAN,
]);
wajib(status_aduan($id1) === 'Diproses', 'Admin bidang mengubah status jadi Diproses');

http('super', 'Admin_Aduan/triase/' . $id1, [
    'csrf_kpkp_token' => csrf('super', 'Admin_Aduan'), 'bidang' => $bidang_b]);
cek(bidang_aduan($id1) === $bidang_a,
    'Rute TIDAK berubah - aduan yang sedang dikerjakan tidak ditarik dari tangan penanganya');
cek(jejak('aduan_triase_ditolak', $id1) === 1,
    'Penolakannya ikut tercatat di jejak audit (jejak dua arah)');

// ------------------------------------------------ 9. PAPAN ADUAN
echo "\n== 9. Papan aduan: wajib login, dan tidak membocorkan isi ==\n";
$tamu = http('papan_tamu', 'umum/papan_aduan');
cek(strpos($tamu['url'], 'Auth/login') !== FALSE, 'Tamu dialihkan ke halaman login');
cek(strpos($tamu['body'], 'Uji triase A ' . CAP) === FALSE, 'Tamu tidak melihat satu judul pun');

$papan = http('warga', 'umum/papan_aduan');
wajib($papan['code'] === 200 && strpos($papan['url'], 'Auth/login') === FALSE,
    'Pengguna login bisa membuka papan aduan');
cek(strpos($papan['body'], 'Uji triase A ' . CAP) !== FALSE, 'Judul aduan tampil');
cek(strpos($papan['body'], $JAWABAN) !== FALSE, 'Jawaban dinas (catatan_admin) tampil');

/**
 * Kebocoran diperiksa dengan MENYAPU SELURUH sumber halaman, bukan dengan
 * memeriksa kolom yang kebetulan diingat. Penandanya ditanam saat aduan dibuat,
 * jadi kalau `pesan`/`email` bocor lewat jalur mana pun - atribut data-, komentar
 * HTML, blok x-data - penandanya ikut terbawa.
 */
cek(strpos($papan['body'], RAHASIA_PESAN) === FALSE, 'Isi aduan (`pesan`) TIDAK ada di sumber halaman');

/**
 * Dan satu pemeriksaan STATIS, karena janjinya lebih keras dari yang bisa
 * dibuktikan lewat HTML: `pesan`/`email`/`lampiran` tidak pernah DI-SELECT,
 * bukan sekadar tidak dirender. Menambahkannya ke SELECT tidak akan mengubah
 * satu byte pun di halaman hari ini - sapuan penanda di atas tetap hijau - dan
 * baru berbuah setahun lagi ketika seseorang menambahkan satu baris tampilan.
 * Yang tidak diambil tidak bisa bocor karena kelalaian.
 */
// Batas method dicari lewat 'function' BERIKUTNYA, bukan lewat kurung tutup
// ber-indentasi tetap: Umum.php ber-indentasi TAB, dan versi pertama penjaga ini
// mematok empat spasi sehingga tidak pernah cocok - `wajib` di bawahnya merah
// untuk kode yang benar, dan seluruh sisa bagian ini tak pernah dijalankan.
preg_match('/function papan_aduan\(\).*?(?=\n\s*(?:\/\*|private|public|protected)\s)/s',
    file_get_contents(APP_ROOT . '/application/controllers/Umum.php'), $pm);
preg_match("/->select\('([^']*)'\)/", $pm[0] ?? '', $sm);
$kolom_papan = array_map('trim', explode(',', $sm[1] ?? ''));
wajib($kolom_papan !== [''], 'Daftar kolom SELECT papan terbaca (' . implode(', ', $kolom_papan) . ')');
foreach (['pesan', 'email', 'lampiran', '*'] as $terlarang) {
    cek( ! in_array($terlarang, $kolom_papan, TRUE), "Kolom '{$terlarang}' tidak ikut di-SELECT papan_aduan()");
}
cek(strpos($papan['body'], 'pelapor_' . CAP . '@example.test') === FALSE, 'Email pelapor TIDAK ada');
cek(strpos($papan['body'], NAMA_PELAPOR) === FALSE, 'Nama lengkap pelapor TIDAK ada');
cek(strpos($papan['body'], 'Z. B. W.') !== FALSE, 'Yang tampil hanya inisialnya');
cek(strpos($papan['body'], 'Admin_Aduan/lihat_lampiran') === FALSE, 'Tidak ada tautan lampiran');
cek( ! preg_match('/<input[^>]+name="q"/i', $papan['body']),
    'Tidak ada kotak cari - pencarian bebas adalah alat pembuka samaran');

// Aduan yang belum ditriase tampil sebagai "sedang ditinjau", bukan nama bidang karangan.
cek(strpos($papan['body'], 'Uji triase selundupan ' . CAP) !== FALSE,
    'Aduan yang belum ditriase tetap tampil di papan');
cek(stripos($papan['body'], 'Sedang ditinjau') !== FALSE,
    'Bidang NULL dibaca "sedang ditinjau", bukan dikarang jadi nama bidang');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
