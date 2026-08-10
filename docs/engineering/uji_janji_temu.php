<?php
/**
 * Uji JANJI TEMU KONSULTASI — mekanisme di balik "Konsultasi Terjadwal".
 *
 *   php docs/engineering/uji_janji_temu.php
 *
 * Halaman `Umum/forum` sudah lama berjudul "Konsultasi TERJADWAL", memajang
 * tiga kartu alur ("Admin meninjau" -> "Agenda ditentukan" -> "Waktu
 * konsultasi disampaikan setelah ditinjau"), dan tombolnya berbunyi "Ajukan
 * Konsultasi" — sementara di belakangnya `tambah_aksi()` hanya menyisipkan satu
 * baris forum. Migrasi 035 + `Admin_Konsultasi` mengisi janji itu; berkas ini
 * menjaga enam hal yang rusaknya SENYAP:
 *
 *   1. MESIN KEADAANNYA SATU, DIPAKAI DUA SISI. Aturan transisi hidup di
 *      `Janji_temu_model::ALUR` dan dibaca warga MAUPUN petugas. Uji ini
 *      menembak transisi ilegal dari KEDUA sisi — whitelist yang benar di satu
 *      controller dan longgar di controller sebelah adalah persis kegagalan
 *      yang dilahirkan daftar kembar.
 *   2. TRANSISINYA ATOMIK. Status asal ikut di WHERE, jadi tombol yang ditekan
 *      dua kali tidak berbuah dua kali. Diuji dengan MENGULANG permintaan yang
 *      sama, bukan dengan membaca kodenya.
 *   3. GERBANG "RUANG KONSULTASI DULU" ADA DI SERVER. Menyembunyikan tombolnya
 *      bukan gerbang; di sini POST dikirim langsung ke endpoint-nya.
 *   4. ANTI-IDOR. Pemilik diambil dari SESI lalu dicocokkan ke baris. Diuji
 *      dengan akun kedua yang menembak id milik akun pertama.
 *   5. PENOLAKAN DIBUKTIKAN LEWAT KEADAAN DB, BUKAN KODE HTTP. Peran yang salah
 *      DIALIHKAN, jadi curl yang mengikuti redirect menerima 200 dari halaman
 *      lain. Uji yang berhenti di "kodenya bukan 200" akan hijau untuk endpoint
 *      yang terbuka lebar.
 *   6. ISI PENGAJUAN TIDAK BOCOR KE PEMBACA LAIN. `alasan` berisi kenapa
 *      seseorang merasa perlu bertemu petugas, dan halaman topiknya terbuka
 *      untuk tamu.
 *
 * Data & akun dibuat sendiri, dibersihkan sendiri. Email uji berakhiran
 * @example.test supaya sensus kebocoran di jalankan_semua.php melihatnya.
 *
 * PENGHITUNG RATE LIMIT SENGAJA TIDAK DIBERSIHKAN. `sys_rate_limits` hanya
 * menyimpan `limit_key` — SHA-256 atas policy + nilai dimensinya — jadi tidak
 * ada kolom yang bisa dicocokkan ke policy ini tanpa menyusun ulang hash-nya,
 * dan menghapus "semua baris sejak uji dimulai" akan ikut membuang penghitung
 * milik fitur lain. Barisnya tetap tertinggal, dan itu tidak apa-apa: kuncinya
 * mengandung id akun uji yang sudah dihapus, jadi tidak ada permintaan
 * berikutnya yang bisa menghasilkan kunci yang sama.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiJanji!2026');
define('CAP', 'UJIJANJI' . date('YmdHis') . mt_rand(100, 999));
define('RAHASIA_ALASAN', 'ALASANRAHASIA' . CAP);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users']   = [];
$GLOBALS['diskusi'] = [];
$GLOBALS['jar']     = [];

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

function janji($id_diskusi) {
    return q('SELECT * FROM forum_janji_temu WHERE id_diskusi=? ORDER BY id DESC LIMIT 1', [$id_diskusi]);
}
function status_janji($id) { return nilai('SELECT status FROM forum_janji_temu WHERE id=?', [$id]); }
function kolom_janji($id, $k) { return nilai("SELECT `{$k}` FROM forum_janji_temu WHERE id=?", [$id]); }
function jml_janji($id_diskusi) {
    return (int) nilai('SELECT COUNT(*) c FROM forum_janji_temu WHERE id_diskusi=?', [$id_diskusi]);
}

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) {
        $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujjt_');
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
 * tidak ketemu. POST tanpa token ditolak SEBELUM controller mana pun berjalan,
 * dan penolakan itu tidak mengubah apa-apa — uji tulis yang kehilangan tokennya
 * jadi hijau hampa: ia mengira gerbangnya menahan, padahal yang ditahan cuma
 * tokennya sendiri.
 */
function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }

    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path} (sesi '{$nama}', kode {$r['code']}).\n");
    exit(1);
}

function login($nama, $email, $sandi = SANDI) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $sandi,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix) {
    $email = 'uji_janji_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
         VALUES (?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Janji ' . $suffix,
         'uji_janji_' . $suffix . '_' . mt_rand(10000, 99999), $peran]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/** Topik forum langsung di DB — jalur pembuatannya bukan yang sedang diuji. */
function buat_topik($user_id, $nama, $email, $judul) {
    $id = tulis(
        'INSERT INTO forum_diskusi (nama_user,email_user,judul_topik,kategori,isi_diskusi,user_id,status,created_at)
         VALUES (?,?,?,"RTLH","Isi topik uji janji temu yang cukup panjang.",?,"open",NOW())',
        [$nama, $email, $judul, $user_id]
    );
    $GLOBALS['diskusi'][] = $id;
    return $id;
}

function beri_komentar($id_diskusi, $nama) {
    return tulis(
        'INSERT INTO forum_komentar (id_diskusi,nama_komentator,isi_komentar,role,created_at)
         VALUES (?,?,"Silakan lengkapi datanya dulu ya.","Petugas Disperakim",NOW())',
        [$id_diskusi, $nama]
    );
}

function jejak($aksi, $id) {
    return (int) nilai(
        "SELECT COUNT(*) c FROM sys_jejak_audit
         WHERE aksi=? AND objek_tipe='forum_janji_temu' AND objek_id=? AND created_at >= ?",
        [$aksi, (string) $id, MULAI]);
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['diskusi'] as $d) {
        foreach (q('SELECT GROUP_CONCAT(id) g FROM forum_janji_temu WHERE id_diskusi=?', [$d]) as $g) {
            foreach (array_filter(explode(',', (string) $g)) as $jid) {
                q("DELETE FROM sys_jejak_audit WHERE objek_tipe='forum_janji_temu' AND objek_id=?", [$jid]);
            }
        }
        // forum_janji_temu & forum_komentar ikut lenyap lewat FK CASCADE —
        // kecuali komentar, yang memang tidak punya FK. Dihapus eksplisit.
        q('DELETE FROM forum_komentar WHERE id_diskusi=?', [$d]);
        q('DELETE FROM forum_diskusi WHERE id_diskusi=?', [$d]);
    }
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['diskusi'] = $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI JANJI TEMU KONSULTASI (forum -> tatap muka) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

// ------------------------------------------------ 0. PRASYARAT SKEMA
echo "== 0. Prasyarat skema (migrasi 20260701000035) ==\n";
wajib((int) nilai("SELECT COUNT(*) c FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='forum_janji_temu'") === 1,
    'Tabel forum_janji_temu ada');
$fk = (int) nilai("SELECT COUNT(*) c FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='forum_janji_temu' AND CONSTRAINT_TYPE='FOREIGN KEY'");
cek($fk === 3, "Tiga FK terpasang (diskusi, user, reviewer) — ditemukan {$fk}");
wajib((int) nilai("SELECT COUNT(*) c FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sys_jejak_audit'") === 1,
    'Tabel sys_jejak_audit ada (migrasi 033)');

[$idW, $emailW]   = buat_akun('user', 'warga');
[$idW2, $emailW2] = buat_akun('user', 'warga2');
[$idA, $emailA]   = buat_akun('admin', 'admin');

wajib(login('w', $emailW), 'Login warga pemilik topik');
wajib(login('w2', $emailW2), 'Login warga lain');
wajib(login('a', $emailA), 'Login superadmin');

$topik = buat_topik($idW, 'Uji Janji ' . CAP, $emailW, 'Topik uji janji temu ' . CAP);
wajib($topik > 0, "Topik forum uji dibuat (id {$topik})");

// ------------------------------------------------ 1. GERBANG "RUANG KONSULTASI DULU"
echo "\n== 1. Belum ditanggapi = belum boleh ajukan (gerbang di SERVER) ==\n";
$post_ajukan = function ($sesi, $id_diskusi, $alasan) {
    return http($sesi, 'Umum/ajukan_janji_temu', [
        'csrf_kpkp_token' => csrf($sesi, 'Umum/detail/' . $id_diskusi),
        'id_diskusi' => $id_diskusi,
        'alasan' => $alasan,
    ]);
};

/* DIBALIK 5 Agt 2026 (revisi dinas butir E1). Sampai 4 Agt, topik yang belum
   ditanggapi petugas TIDAK boleh diajukan janji temu — itu terjemahan gagasan
   "berkonsultasi di forum dulu, tatap muka belakangan". Justru syarat itulah
   yang membuat dinas membuka menu Konsultasi Terjadwal dan menyimpulkan "belum
   ada pilihan bikin jadwalnya": tombolnya memang tidak pernah mereka lihat.

   Yang dijaga sekarang KEBALIKANNYA — dan sekaligus bahwa melepas satu syarat
   tidak ikut melepas yang lain. */
$hal_awal = http('w', 'Umum/detail/' . $topik);
cek(strpos($hal_awal['body'], 'Umum/ajukan_janji_temu') !== FALSE,
    'Topik tanpa tanggapan: tombol pengajuan SUDAH dirender (dulu disembunyikan)');
cek(stripos($hal_awal['body'], 'setelah topik ini ditanggapi') === FALSE,
    'Keterangan "tunggu ditanggapi dulu" sudah tidak ada — syaratnya memang hilang');

$r = $post_ajukan('w', $topik, 'Perlu bertemu langsung karena berkasnya banyak. ' . RAHASIA_ALASAN);
cek(jml_janji($topik) === 1, 'Topik tanpa tanggapan: pengajuan DITERIMA (satu baris)');

/* Sisi tampilan dan sisi server harus sepakat. Kalau tombolnya muncul memakai
   syarat yang lebih longgar daripada yang ditegakkan server, penekannya ditolak
   tanpa tahu sebabnya — itu justru cacat yang lebih membingungkan daripada
   tombol yang tidak ada. */
cek(strpos(http('w', 'Umum/detail/' . $topik)['body'], 'Umum/ajukan_janji_temu') === FALSE,
    'Sesudah diajukan, tombolnya hilang — tampilan sepakat dengan server');

beri_komentar($topik, 'Petugas Uji');

// ------------------------------------------------ 2. ANTI-IDOR
echo "\n== 2. Hanya pemilik topik ==\n";
// Dibandingkan ke jumlah SEBELUM, bukan ke nol. Versi pertama memakai `=== 0`
// dan ikut merah saat gerbang di bagian 1 dimutasi — pemeriksaan yang merah
// karena bagian lain rusak tidak memberi tahu apa pun tentang bagian ini.
$sebelum = jml_janji($topik);
$r = $post_ajukan('w2', $topik, 'Saya ingin ikut bertemu juga walau bukan topik saya.');
cek(jml_janji($topik) === $sebelum, 'Warga lain TIDAK bisa mengajukan untuk topik orang (dicek dari DB)');
cek($r['code'] === 404, 'Dibalas 404, bukan 403 — keberadaan topik orang tidak dikonfirmasi');

// ------------------------------------------------ 3. AJUKAN
echo "\n== 3. Pengajuan oleh pemilik ==\n";
$r = $post_ajukan('w', $topik, 'Perlu bertemu langsung karena berkasnya banyak. ' . RAHASIA_ALASAN);
cek(jml_janji($topik) === 1, 'Satu baris janji temu tercipta');
$j = janji($topik);
$jid = (int) $j['id'];
cek($j['status'] === 'diajukan', "Status awal 'diajukan'");
cek($j['jadwal_mulai'] === NULL, 'Jadwal masih kosong — belum ada yang menawarkan');
cek((int) $j['user_id'] === $idW, 'Pemohonnya diambil dari sesi, bukan dari POST');
cek(jejak('janji_temu_diajukan', $jid) === 1, 'Jejak audit janji_temu_diajukan tercatat');

// Alasan terlalu pendek ditolak.
$r = $post_ajukan('w', $topik, 'pendek');
cek(jml_janji($topik) === 1, 'Alasan < 20 karakter ditolak (tetap satu baris)');

echo "\n== 4. Satu janji hidup per topik ==\n";
$r = $post_ajukan('w', $topik, 'Mengajukan lagi padahal yang lama masih berjalan, harusnya ditolak.');
cek(jml_janji($topik) === 1, 'Pengajuan kedua saat ada yang berjalan DITOLAK');
cek(stripos($r['body'], 'sudah ada pengajuan') !== FALSE, 'Sebabnya disebut');

// ------------------------------------------------ 5. PENOLAKAN SISI ADMIN
echo "\n== 5. Yang harus ditolak di sisi petugas ==\n";
$t_admin = csrf('a', 'Admin_Konsultasi');

cek(http('a', 'Admin_Konsultasi/tawarkan/' . $jid . '?jadwal_mulai=2030-01-01+09:00&lokasi=X')['code'] === 404,
    'GET ke endpoint tulis dibalas 404 — hanya POST yang boleh menulis');
cek(status_janji($jid) === 'diajukan', 'GET tadi tidak mengubah apa pun');

http('a', 'Admin_Konsultasi/tawarkan/' . $jid, ['jadwal_mulai' => '2030-01-01 09:00', 'lokasi' => 'Tanpa token']);
cek(kolom_janji($jid, 'lokasi') === NULL, 'POST tanpa token CSRF tidak mengubah apa pun');

http('a', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => $t_admin, 'jadwal_mulai' => '2020-01-01 09:00', 'lokasi' => 'Ruang A']);
cek(status_janji($jid) === 'diajukan', 'Jadwal di MASA LALU ditolak');

http('a', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi'), 'jadwal_mulai' => date('Y-m-d H:i', time() + 86400), 'lokasi' => '']);
cek(status_janji($jid) === 'diajukan', 'Lokasi kosong ditolak — warga perlu tahu harus datang ke mana');

// Peran yang salah: DIALIHKAN, jadi kodenya 200 dari halaman lain. Buktinya DB.
http('w', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => csrf('w', 'Umum/detail/' . $topik),
    'jadwal_mulai' => date('Y-m-d H:i', time() + 86400), 'lokasi' => 'Ruang Warga']);
cek(status_janji($jid) === 'diajukan', 'Warga TIDAK bisa menawarkan jadwal (dicek dari DB, bukan kode HTTP)');

// ------------------------------------------------ 6. TAWARKAN
echo "\n== 6. Petugas menawarkan jadwal ==\n";
$JADWAL = date('Y-m-d H:i', time() + 3 * 86400);
$LOKASI = 'Ruang Rapat Uji ' . CAP;
http('a', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi'),
    'jadwal_mulai' => $JADWAL, 'lokasi' => $LOKASI, 'catatan_admin' => 'Bawa fotokopi KK.']);
cek(status_janji($jid) === 'ditawarkan', "Status menjadi 'ditawarkan'");
cek(kolom_janji($jid, 'lokasi') === $LOKASI, 'Lokasinya tersimpan');
cek((int) kolom_janji($jid, 'reviewed_by') === $idA, 'Petugas yang menawarkan ikut tercatat');
cek(jejak('janji_temu_ditawarkan', $jid) === 1, 'Jejak audit janji_temu_ditawarkan tercatat');

$hal = http('w', 'Umum/detail/' . $topik);
cek(strpos($hal['body'], $LOKASI) !== FALSE, 'Pemohon melihat lokasinya di halaman topik');

// ------------------------------------------------ 7. TRANSISI ILEGAL DARI SISI PETUGAS
echo "\n== 7. Transisi ilegal ditolak, dari kedua sisi ==\n";
http('a', 'Admin_Konsultasi/selesai/' . $jid, ['csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi')]);
cek(status_janji($jid) === 'ditawarkan',
    "'ditawarkan' -> 'selesai' ditolak — pertemuannya belum disepakati siapa pun");
cek(jejak('janji_temu_transisi_ditolak', $jid) >= 1, 'Penolakannya ikut tercatat (jejak dua arah)');

$post_respon = function ($sesi, $id_janji, $id_diskusi, $aksi) {
    return http($sesi, 'Umum/respon_janji_temu/' . $id_janji, [
        'csrf_kpkp_token' => csrf($sesi, 'Umum/detail/' . $id_diskusi),
        'aksi' => $aksi,
    ]);
};

$post_respon('w2', $jid, $topik, 'setujui');
cek(status_janji($jid) === 'ditawarkan', 'Warga lain TIDAK bisa menyetujui janji temu orang (anti-IDOR)');

// ------------------------------------------------ 8. MINTA JADWAL LAIN
echo "\n== 8. Warga minta jadwal lain ==\n";
$post_respon('w', $jid, $topik, 'jadwal_lain');
cek(status_janji($jid) === 'diajukan', "Kembali ke 'diajukan', bukan keadaan baru");
cek(kolom_janji($jid, 'jadwal_mulai') === NULL,
    'Jadwal lama DIKOSONGKAN — kalau tidak, barisnya terbaca seolah masih punya agenda');
cek(kolom_janji($jid, 'lokasi') === NULL, 'Lokasi lama ikut dikosongkan');

// ------------------------------------------------ 9. SETUJUI + ATOMIK
echo "\n== 9. Disepakati, dan tidak bisa disepakati dua kali ==\n";
http('a', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi'),
    'jadwal_mulai' => date('Y-m-d H:i', time() + 5 * 86400), 'lokasi' => $LOKASI]);
wajib(status_janji($jid) === 'ditawarkan', 'Ditawarkan ulang');

$post_respon('w', $jid, $topik, 'setujui');
cek(status_janji($jid) === 'disetujui', "Status menjadi 'disetujui'");
$audit_setuju = jejak('janji_temu_disetujui', $jid);

/**
 * Permintaan yang SAMA diulang.
 *
 * ⚠️ YANG DIBUKTIKAN DI SINI TERBATAS, dan batasnya disebut supaya tidak
 * dibaca lebih besar dari kenyataannya: ini membuktikan permintaan BERURUTAN
 * tidak berbuah dua kali. Ia TIDAK membuktikan `->where('status', $dari)` di
 * `Janji_temu_model::transisi()` bekerja — mencabut baris itu tidak memerahkan
 * satu pun pemeriksaan di berkas ini, karena `respon_janji_temu()` sudah
 * menolak lebih dulu lewat `boleh()`. WHERE itu lapis KEDUA, untuk permintaan
 * yang benar-benar bersamaan, dan harness berurutan tidak bisa menyentuhnya.
 * Jangan cabut lapis itu karena "tidak ada uji yang merah".
 */
$post_respon('w', $jid, $topik, 'setujui');
cek(status_janji($jid) === 'disetujui', 'Setujui yang kedua tidak mengubah status');
cek(jejak('janji_temu_disetujui', $jid) === $audit_setuju,
    'Dan tidak menambah jejak — permintaan berurutan tidak berbuah dua kali');

// ------------------------------------------------ 10. SELESAI + KEADAAN AKHIR
echo "\n== 10. Ditandai selesai, lalu terkunci ==\n";
http('a', 'Admin_Konsultasi/selesai/' . $jid, ['csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi')]);
cek(status_janji($jid) === 'selesai', "Status menjadi 'selesai'");
cek(jejak('janji_temu_selesai', $jid) === 1, 'Jejak audit janji_temu_selesai tercatat');

http('a', 'Admin_Konsultasi/tawarkan/' . $jid, [
    'csrf_kpkp_token' => csrf('a', 'Admin_Konsultasi'),
    'jadwal_mulai' => date('Y-m-d H:i', time() + 86400), 'lokasi' => 'Ruang Lain']);
cek(status_janji($jid) === 'selesai', 'Keadaan akhir tidak bisa ditawarkan ulang');
$post_respon('w', $jid, $topik, 'batalkan');
cek(status_janji($jid) === 'selesai', 'Keadaan akhir tidak bisa dibatalkan warga');

// ------------------------------------------------ 11. KERAHASIAAN
echo "\n== 11. Isi pengajuan tidak bocor ke pembaca lain ==\n";
$hal_lain = http('w2', 'Umum/detail/' . $topik);
cek($hal_lain['code'] === 200, 'Warga lain tetap bisa membaca topiknya (forum memang terbuka)');
/**
 * DUA pemeriksaan, dan yang kedua yang sebenarnya menggigit.
 *
 * `alasan` kebetulan memang tidak dirender di panel hari ini, jadi menyapu
 * penandanya saja nyaris hampa — ia hijau untuk halaman yang membocorkan
 * seluruh panel asal kolom itu tidak ikut dicetak. Yang diperiksa berikutnya
 * adalah PANELNYA sendiri tidak muncul untuk bukan-pemilik; itu yang akan
 * merah kalau gerbang kepemilikan di `Umum::detail` dilonggarkan.
 */
cek(strpos($hal_lain['body'], RAHASIA_ALASAN) === FALSE, 'Alasan pengajuan TIDAK terlihat pembaca lain');
cek(strpos($hal_lain['body'], 'Janji Temu Konsultasi') === FALSE,
    'Panel janji temu tidak dirender sama sekali untuk bukan-pemilik');
$hal_tamu = http('tamu', 'Umum/detail/' . $topik);
cek(strpos($hal_tamu['body'], RAHASIA_ALASAN) === FALSE, 'Dan tidak terlihat tamu');
cek(strpos($hal_tamu['body'], 'Umum/respon_janji_temu') === FALSE, 'Tamu tidak dapat tombol aksinya');

// ------------------------------------------------ 12. BATAS LAJU
echo "\n== 12. Batas laju pengajuan ==\n";
/**
 * Jatahnya 3/hari per AKUN. Satu sudah terpakai di bagian 3, jadi dua topik
 * baru lagi menghabiskannya dan yang keempat harus tertolak 429.
 * Topik baru tiap kali, karena "satu janji hidup per topik" akan menahan
 * duluan dan yang terukur jadi gerbang yang salah.
 */
$sisa_ditolak = FALSE;
for ($i = 2; $i <= 4; $i++) {
    $t = buat_topik($idW, 'Uji Janji ' . CAP, $emailW, 'Topik uji janji temu ' . CAP . ' #' . $i);
    beri_komentar($t, 'Petugas Uji');
    $r = $post_ajukan('w', $t, 'Alasan pengajuan yang cukup panjang untuk lolos validasi. #' . $i);
    if ($i < 4) {
        cek(jml_janji($t) === 1, "Pengajuan ke-{$i} masih dalam jatah");
    } else {
        cek(jml_janji($t) === 0, 'Pengajuan ke-4 dalam sehari DITOLAK batas laju');
        $sisa_ditolak = ($r['code'] === 429);
    }
}
cek($sisa_ditolak, 'Penolakannya 429, bukan 200 yang menyamar sebagai sukses');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
