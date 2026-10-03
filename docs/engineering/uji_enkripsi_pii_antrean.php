<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji: PII antrean perumahan dan NIK pemohon SRP2 tersimpan terenkripsi (migrasi 067).
 *
 *   php docs/engineering/uji_enkripsi_pii_antrean.php
 *
 * Fase 3 normalisasi langkah 1 (keputusan pemilik produk 2 Okt 2026). Yang dijaga:
 *   A. Bentuk skema: kolom polos sf_antrean_pengajuan (nik_pengaju, nama_lengkap, data_*_json) dan
 *      srp2_pengajuan.nik_ktp HILANG; pasangan *_ciphertext + sidik + indeksnya ADA;
 *      Migrate::status() melaporkannya.
 *   B. Isi DB: tidak ada baris antrean/SRP2 yang menyimpan NIK 16 digit polos, setiap ciphertext
 *      sungguhan terenkripsi, dan tidak ada kode aplikasi yang menulis kolom polos lagi.
 *   C. Layar: superadmin melihat nama TERDEKRIPSI (daftar antrean, Ringkasan Kerja, detail SRP2),
 *      admin kab/kota tetap tersamar (B2), NIK di daftar tetap tersamar 4 digit.
 *   D. Cari NIK lewat sidik: utuh 16 digit cocok, potongan tidak, dan NIK wilayah lain tidak bocor.
 *   E. Keunikan NIK SRP2 tetap ditegakkan DB (UNIQUE pada sidik).
 * Data uji: akun @example.test dan baris bertanda unik, NIK berawalan 99 (bukan kode provinsi
 * sah, jadi tidak mungkin milik warga sungguhan). Semuanya dihapus di finally. Keluaran tidak
 * pernah mencetak nilai PII.
 */
define('BASEPATH', 'x'); define('APPPATH', dirname(__DIR__, 2) . '/application/'); function log_message() {}
$root = dirname(__DIR__, 2);
$B = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path($root), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || ! strpos($l, '=')) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); if (getenv(trim($k)) === FALSE) putenv(trim($k) . '=' . trim($v)); }
require APPPATH . 'libraries/Encryption_lib.php'; $enc = new Encryption_lib();
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujipii' . bin2hex(random_bytes(3)); $pw = 'Pii1#' . bin2hex(random_bytes(5));
$ids = []; $antrean = []; $srp2 = []; $jars = []; $rate_asli = [];
$ok = 0; $gagal = 0;
$cek = function ($c, $l) use (&$ok, &$gagal) { $c ? $ok++ : $gagal++; echo ($c ? '  OK    ' : '  GAGAL ') . $l . "\n"; return $c; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); return $r ? $r->fetch_assoc() : NULL; };
$http = function ($jar, $p, $post = NULL) use ($B) { $c = curl_init($B . $p); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]); if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post)); $b = (string) curl_exec($c); $k = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); return [$k, html_entity_decode($b, ENT_QUOTES, 'UTF-8')]; };
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$jar = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'pii'); $jars[] = $j; return $j; };
$akun = function ($role, $kab = NULL) use ($db, $tag, $pw, &$ids) {
    $e = "{$tag}_{$role}_" . count($ids) . '@example.test'; $h = password_hash($pw, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_akun (nama,email,kata_sandi,peran,kabupaten_id,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at) VALUES ('Uji PII',?,?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('sssi', $e, $h, $role, $kab); $st->execute(); $ids[] = $db->insert_id; return [$db->insert_id, $e];
};
$login = function ($email) use ($http, $csrf, $jar, $pw) { $j = $jar(); $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $pw, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
$pinjam = function ($key) use ($db, &$rate_asli) {
    if (array_key_exists($key, $rate_asli)) return;
    $st = $db->prepare('SELECT kunci,jendela_mulai_at,jumlah_gagal FROM sys_batas_laju WHERE kunci=?'); $st->bind_param('s', $key); $st->execute();
    $rate_asli[$key] = $st->get_result()->fetch_assoc();
    $st = $db->prepare('DELETE FROM sys_batas_laju WHERE kunci=?'); $st->bind_param('s', $key); $st->execute();
};
// Tiket tepat 10 karakter: kolomnya varchar(10) dan MySQL non-strict memotong diam-diam (uji scope).
$tiket = function () { return 'UPI' . strtoupper(bin2hex(random_bytes(3))) . 'X'; };
$antre = function ($kab, $uid, $prog, $nama, $nik, $t) use ($db, $enc, &$antrean) {
    $survey = json_encode(['pekerjaan' => 'Karyawan Swasta', 'penghasilan' => 2500000, 'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'Uji enkripsi']);
    $st = $db->prepare("INSERT INTO sf_antrean_pengajuan (kode_tiket,user_id,kabupaten_id,program_id,nik_pengaju_ciphertext,nik_pengaju_lookup_hash,nama_lengkap_ciphertext,data_survey_json_ciphertext,status_antrean,mode_sumber,created_at) VALUES (?,?,?,?,?,?,?,?,'pending','legacy',NOW())");
    $c1 = $enc->encrypt($nik); $h = $enc->deterministic_hash($nik); $c2 = $enc->encrypt($nama); $c3 = $enc->encrypt($survey);
    $st->bind_param('siiissss', $t, $uid, $kab, $prog, $c1, $h, $c2, $c3); $st->execute(); $antrean[] = $db->insert_id; return $db->insert_id;
};

try {
    echo "=== UJI ENKRIPSI PII ANTREAN & SRP2 (migrasi 067) ===\n";
    foreach (['login'] as $p) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) $pinjam(hash('sha256', "$p:ip:$ip"));

    echo "A. Bentuk skema\n";
    $kolom = function ($t) use ($db) { return array_column($db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t'")->fetch_all(MYSQLI_ASSOC), 'COLUMN_NAME'); };
    $kq = $kolom('sf_antrean_pengajuan'); $ks = $kolom('srp2_pengajuan');
    $cek( ! array_intersect(['nik_pengaju', 'nama_lengkap', 'data_simperum_json', 'data_survey_json'], $kq), 'sf_antrean_pengajuan tidak lagi punya kolom NIK/nama/JSON polos');
    $cek( ! in_array('nik_ktp', $ks, TRUE), 'srp2_pengajuan tidak lagi punya kolom nik_ktp polos');
    $cek( ! array_diff(['nik_pengaju_ciphertext', 'nik_pengaju_lookup_hash', 'nama_lengkap_ciphertext', 'data_simperum_json_ciphertext', 'data_survey_json_ciphertext'], $kq), 'Pasangan terenkripsi antrean lengkap');
    $cek( ! array_diff(['nik_ktp_ciphertext', 'nik_ktp_lookup_hash'], $ks), 'Pasangan terenkripsi NIK SRP2 lengkap');
    $idx = $satu("SELECT GROUP_CONCAT(CONCAT(INDEX_NAME,':',NON_UNIQUE)) g FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND INDEX_NAME IN ('idx_sf_queue_nik_lookup','uq_srp2_registration_nik','uq_nik_ktp')")['g'] ?? '';
    $cek(strpos($idx, 'idx_sf_queue_nik_lookup:1') !== FALSE && strpos($idx, 'uq_srp2_registration_nik:0') !== FALSE && strpos($idx, 'uq_nik_ktp') === FALSE, 'Indeks sidik antrean ada, UNIQUE sidik NIK SRP2 ada, indeks polos uq_nik_ktp hilang');
    $status = shell_exec('"' . PHP_BINARY . '" ' . escapeshellarg($root . '/index.php') . ' migrate status 2>&1');
    $cek(substr_count((string) $status, 'PII terenkripsi (migrasi 067): TERPASANG') === 2, 'Migrate::status melaporkan bentuk 067 TERPASANG untuk kedua tabel');

    echo "B. Isi DB dan penulis\n";
    $polos16 = 0; $bukan_sandi = 0;
    foreach ($db->query('SELECT * FROM sf_antrean_pengajuan')->fetch_all(MYSQLI_ASSOC) as $r) {
        foreach ($r as $k => $v) {
            if ($v === NULL || $v === '' || in_array($k, ['kunci_pengajuan', 'nik_pengaju_lookup_hash'], TRUE)) continue;
            if (preg_match('/(?<!\d)\d{16}(?!\d)/', (string) $v)) $polos16++;
            if (substr($k, -11) === '_ciphertext' && ! $enc->is_encrypted($v)) $bukan_sandi++;
        }
    }
    foreach ($db->query('SELECT nik_ktp_ciphertext c FROM srp2_pengajuan WHERE nik_ktp_ciphertext IS NOT NULL')->fetch_all(MYSQLI_ASSOC) as $r) { if ( ! $enc->is_encrypted($r['c'])) $bukan_sandi++; }
    $cek($polos16 === 0, 'Tidak ada baris antrean yang memuat deret 16 digit polos (' . $polos16 . ' temuan)');
    $cek($bukan_sandi === 0, 'Setiap nilai *_ciphertext benar-benar ciphertext (' . $bukan_sandi . ' bukan)');
    $penulis = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (substr($f, -4) !== '.php' || strpos(str_replace('\\', '/', $f), '/migrations/') !== FALSE || strpos(str_replace('\\', '/', $f), '/logs/') !== FALSE) continue;
        $isi = file_get_contents($f);
        // Hanya berkas yang menyentuh kedua tabel; larik profil tersamar Simperum_gateway juga berkunci nama_lengkap.
        if (strpos($isi, 'sf_antrean_pengajuan') === FALSE && strpos($isi, 'srp2_pengajuan') === FALSE) continue;
        $penulis += preg_match_all("/'(nik_pengaju|nama_lengkap|data_simperum_json|data_survey_json|nik_ktp)'\s*=>/", $isi);
    }
    $cek($penulis === 0, 'Tidak ada kode aplikasi yang menulis kolom polos lama (' . $penulis . ' temuan)');
    $model = file_get_contents(APPPATH . 'models/Housing_assessment_model.php');
    $cek(strpos($model, "'nik_pengaju'") === FALSE && strpos($model, "'nama_lengkap' =>") === FALSE, 'Kiriman wizard tidak menyalin identitas ke antrean');

    echo "C. Layar: terdekripsi untuk superadmin, tersamar untuk kab/kota\n";
    $kab = array_column($db->query('SELECT id FROM kabupaten ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC), 'id');
    $prog = (int) ($satu('SELECT id FROM sf_program ORDER BY id LIMIT 1')['id'] ?? 0);
    if ( ! $cek(count($kab) === 2 && $prog > 0, 'Prasyarat: dua kabupaten dan satu program')) throw new RuntimeException('prasyarat');
    [$uidW] = $akun('warga');
    $nikA = '99' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $nikB = '99' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $namaA = "Warga {$tag} Alfa"; $namaB = "Warga {$tag} Beta";
    $tA = $tiket(); $tB = $tiket();
    $qA = $antre((int) $kab[0], $uidW, $prog, $namaA, $nikA, $tA);
    $qB = $antre((int) $kab[1], $uidW, $prog, $namaB, $nikB, $tB);
    $mentah = $satu("SELECT * FROM sf_antrean_pengajuan WHERE id=$qA");
    $cek($mentah && strpos(json_encode($mentah), $nikA) === FALSE && strpos(json_encode($mentah), $tag) === FALSE, 'Baris mentah tiket uji tidak memuat NIK maupun nama polos');

    [, $eS] = $akun('admin');
    $jS = $login($eS);
    [$s, $daftarS] = $http($jS, 'Admin?q=' . $tA);
    $cek($s === 200 && strpos($daftarS, $tA) !== FALSE, 'PRASYARAT: superadmin melihat tiket uji di daftar');
    $cek(strpos($daftarS, $namaA) !== FALSE, 'Superadmin melihat nama TERDEKRIPSI di daftar antrean');
    $cek(strpos($daftarS, str_repeat('•', 12) . substr($nikA, -4)) !== FALSE && strpos($daftarS, $nikA) === FALSE, 'NIK di daftar superadmin tetap tersamar 4 digit, tidak utuh');
    $cek(strpos($daftarS, 'Uji enkripsi') !== FALSE, 'Isi survei (JSON terenkripsi) terbaca di daftar');
    [, $ringkas] = $http($jS, 'Admin_Dashboard');
    $cek(strpos($ringkas, $namaB) !== FALSE || strpos($ringkas, $namaA) !== FALSE, 'Ringkasan Kerja superadmin menampilkan nama terdekripsi di aktivitas terkini');

    [, $eK] = $akun('admin_kabkota', (int) $kab[0]);
    $jK = $login($eK);
    [$s, $daftarK] = $http($jK, 'Admin_Kabkota');
    $cek($s === 200 && strpos($daftarK, $tA) !== FALSE, 'PRASYARAT: admin kab/kota A melihat tiket wilayahnya');
    $kebijakan = (string) file_get_contents(APPPATH . 'config/kebijakan_data.php');
    if (strpos($kebijakan, "\$config['identitas_warga_kabkota'] = 'menunggu_keputusan';") !== FALSE) {
        $cek(strpos($daftarK, $namaA) === FALSE && strpos($daftarK, 'Warga Contoh') !== FALSE, 'B2: admin kab/kota melihat data contoh, bukan nama terdekripsi');
    } else {
        $cek(strpos($daftarK, $namaA) !== FALSE, 'B2 sudah diputuskan tampil: admin kab/kota melihat nama terdekripsi');
    }
    $cek(strpos($daftarK, $nikA) === FALSE, 'NIK utuh tidak sampai ke layar admin kab/kota');

    echo "D. Cari NIK lewat sidik\n";
    [, $cari] = $http($jS, 'Admin?q=' . $nikA);
    $cek(strpos($cari, $tA) !== FALSE && strpos($cari, $tB) === FALSE, 'Superadmin: NIK utuh 16 digit menemukan tiketnya saja');
    [, $cari] = $http($jS, 'Admin?q=' . substr($nikA, 0, 12));
    $cek(strpos($cari, $tA) === FALSE, 'Potongan NIK tidak cocok (hanya pencocokan utuh lewat sidik)');
    [, $cari] = $http($jS, 'Admin?q=' . rawurlencode("Warga {$tag}"));
    $cek(strpos($cari, $tA) === FALSE, 'Pencarian nama dicabut (nama terenkripsi), tidak diam-diam menyaring di SQL');
    [, $cari] = $http($jK, 'Admin_Kabkota?q=' . $nikA);
    $cek(strpos($cari, $tA) !== FALSE, 'Admin kab/kota A menemukan NIK wilayahnya sendiri');
    [, $cari] = $http($jK, 'Admin_Kabkota?q=' . $nikB);
    $cek(strpos($cari, $tB) === FALSE, 'Admin kab/kota A mencari NIK wilayah B: tidak muncul');

    echo "E. NIK pemohon SRP2\n";
    $nikS = '99' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $ins = function ($nama) use ($db, $enc, $nikS, &$srp2) {
        $c = $enc->encrypt($nikS); $h = $enc->deterministic_hash($nikS);
        $st = $db->prepare("INSERT INTO srp2_pengajuan (nama_perusahaan,nama_peserta,nik_ktp_ciphertext,nik_ktp_lookup_hash) VALUES (?,'Uji PII',?,?)");
        $st->bind_param('sss', $nama, $c, $h); $ok = $st->execute(); $errno = $st->errno;
        if ($ok) $srp2[] = $db->insert_id;
        return [$ok, $errno, $ok ? $db->insert_id : 0];
    };
    [$ok1, , $sid] = $ins("UJI {$tag} SATU");
    [$ok2, $errno2] = $ins("UJI {$tag} DUA");
    $cek($ok1 && ! $ok2 && $errno2 === 1062, 'NIK pemohon SRP2 yang sama ditolak DB (UNIQUE pada sidik, errno ' . $errno2 . ')');
    [$s, $det] = $http($jS, 'Admin_Srp2/detail/' . $sid);
    $cek($s === 200 && strpos($det, $nikS) !== FALSE, 'Detail SRP2 superadmin menampilkan NIK pemohon terdekripsi');
    $cek((int) ($satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE aksi='akses_pengajuan_srp2' AND objek_id='$sid'")['n'] ?? 0) >= 1, 'Akses detail SRP2 tetap tercatat di jejak audit');
} catch (Throwable $e) {
    $cek(FALSE, 'Pengecualian: ' . get_class($e));
} finally {
    foreach ($antrean as $id) $db->query('DELETE FROM sf_antrean_pengajuan WHERE id=' . (int) $id);
    foreach ($srp2 as $id) $db->query('DELETE FROM srp2_pengajuan WHERE id=' . (int) $id);
    foreach ($ids as $id) $db->query('DELETE FROM usr_akun WHERE id=' . (int) $id);
    foreach ($rate_asli as $key => $r) {
        $st = $db->prepare('DELETE FROM sys_batas_laju WHERE kunci=?'); $st->bind_param('s', $key); $st->execute();
        if ($r) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci,jendela_mulai_at,jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $r['kunci'], $r['jendela_mulai_at'], $r['jumlah_gagal']); $st->execute(); }
    }
    foreach ($jars as $j) @unlink($j);
    echo "RINGKASAN: " . ($ok + $gagal) . " pemeriksaan, $gagal gagal; akun tersisa "
        . $db->query("SELECT COUNT(*) FROM usr_akun WHERE email LIKE '{$tag}%'")->fetch_row()[0] . ', baris SRP2 tersisa '
        . $db->query("SELECT COUNT(*) FROM srp2_pengajuan WHERE nama_perusahaan LIKE 'UJI {$tag}%'")->fetch_row()[0] . "\n";
}
exit($gagal ? 1 : 0);
