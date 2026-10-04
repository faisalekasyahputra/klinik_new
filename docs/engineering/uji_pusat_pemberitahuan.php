<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/*
 * Uji Pusat Pemberitahuan (4 Okt 2026): halaman /pemberitahuan, ringkasan "Perlu tindakan" di topbar,
 * dan badge sidebar yang bertooltip serta menautkan filter yang cocok.
 *
 *   php docs/engineering/uji_pusat_pemberitahuan.php
 *
 * Dijaga, per peran staf (akun seed_agen_peran.php: admin, admin_kabkota, admin_bidang):
 *  - jumlah tiap bagian halaman = hitungan DB yang ditulis ulang di sini = badge sidebar di respons yang sama;
 *  - jumlah baris yang ditampilkan = min(jumlah, 50);
 *  - cakupan: kab/kota hanya melihat antrean kabupatennya, bidang hanya aduan & magang bidangnya;
 *  - nol data pribadi di isi halaman (nama, surel, judul, NIM, no HP fixture; tidak ada deret 16 digit);
 *  - tautan baris fixture dan tautan modul terbuka 200 untuk peran itu;
 *  - topbar memuat "Perlu tindakan" dengan angka yang sama dan tautan ke bagiannya, plus "Lihat semua";
 *  - badge sidebar ber-title "N ..." dan tautan menu ber-badge membawa filter yang cocok.
 * Peran non-staf (warga, pengembang, mahasiswa) dijawab 404, tamu dialihkan ke login.
 *
 * Lokal saja. Fixture (akun @example.test, aduan, antrean, pendaftaran magang) dibuat lalu dihapus sendiri;
 * ember batas laju login dipinjam lalu dikembalikan.
 */
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('APP_ROOT', dirname(__DIR__, 2));
define('SANDI_AGEN', 'AgenUji!2026'); // seed_agen_peran.php

$total = 0; $gagal = 0;
function cek($ok, $label) {
    global $total, $gagal;
    $total++;
    if ( ! $ok) { $gagal++; }
    echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n";
    return (bool) $ok;
}

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $b) {
    $b = trim($b);
    if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $b, 2);
    $env[trim($k)] = $env[trim($k)] ?? trim($v);
}
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) { die("TOLAK: DB bukan lokal.\n"); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($db->connect_error) { die("Koneksi DB gagal.\n"); }
$db->query("SET time_zone = '+07:00'");

function jalan($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map('strval', $p)); }
    $st->execute();
    $r = $st->get_result();
    $out = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $id = $st->insert_id;
    $st->close();
    return ['rows' => $out, 'id' => (int) $id];
}
function satu($sql, array $p = []) { return jalan($sql, $p)['rows'][0] ?? NULL; }
function nilai($sql, array $p = []) { $r = satu($sql, $p); return $r ? reset($r) : NULL; }

$jars = [];
function minta($jar, $path, $post = NULL, $ajax = FALSE) {
    $c = curl_init(BASE . ltrim($path, '/'));
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) { curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $badan = (string) curl_exec($c);
    $hasil = ['kode' => (int) curl_getinfo($c, CURLINFO_HTTP_CODE), 'badan' => $badan, 'url' => (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL)];
    curl_close($c);
    return $hasil;
}
function masuk($email) {
    global $jars;
    $j = tempnam(sys_get_temp_dir(), 'ujpb');
    $jars[] = $j;
    $b = minta($j, 'Auth/login')['badan'];
    $t = preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $b, $m) ? $m[1] : '';
    $r = minta($j, 'Auth/do_login', ['email' => $email, 'password' => SANDI_AGEN, 'csrf_kpkp_token' => $t], TRUE);
    return (json_decode($r['badan'], TRUE)['status'] ?? '') === 'success' ? $j : NULL;
}
/** Isi halaman di dalam <main id="main-content">, tanpa script/style. */
function isi_main($html) {
    $a = strpos($html, 'id="main-content"');
    $b = strrpos($html, '</main>');
    if ($a === FALSE || $b === FALSE) { return ''; }
    return preg_replace('#<(script|style)\b.*?</\1>#s', '', substr($html, $a, $b - $a));
}
function teks($html) { return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(str_replace('>', '> ', $html))), ENT_QUOTES, 'UTF-8'); }

/* ---------------------------------------------------------------- Ember batas laju login: dipinjam */
$ember_asli = [];
function pinjam_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
foreach (['127.0.0.1', '0000000000000000/64'] as $ip) { pinjam_ember(hash('sha256', "login:ip:$ip")); }

$AKUN = [
    'admin' => 'agen_admin@agen.test', 'admin_kabkota' => 'agen_admin_kabkota@agen.test', 'admin_bidang' => 'agen_admin_bidang@agen.test',
    'warga' => 'agen_warga@agen.test', 'pengembang' => 'agen_pengembang@agen.test', 'mahasiswa' => 'agen_mahasiswa@agen.test',
];
foreach ($AKUN as $email) { pinjam_ember(hash('sha256', 'login_akun:key:' . hash('sha256', '127.0.0.1|' . strtolower($email)))); }

$TAG = 'ujpb' . bin2hex(random_bytes(3));
$bersih = [];
try {
    echo "== Prasyarat ==\n";
    $kab = (int) nilai("SELECT kabupaten_id FROM usr_akun WHERE email=? AND peran='admin_kabkota' AND status='active'", [$AKUN['admin_kabkota']]);
    $bid = (string) nilai("SELECT bidang_kode FROM usr_akun WHERE email=? AND peran='admin_bidang' AND status='active'", [$AKUN['admin_bidang']]);
    $ada_admin = nilai("SELECT COUNT(*) FROM usr_akun WHERE email=? AND peran='admin' AND status='active'", [$AKUN['admin']]);
    if ( ! cek($kab > 0 && $bid !== '' && (int) $ada_admin === 1, 'Akun seed_agen_peran.php tersedia (jalankan seed_agen_peran.php bila GAGAL)')) { throw new RuntimeException('prasyarat'); }
    $kab_lain = (int) nilai('SELECT id FROM kabupaten WHERE id<>? ORDER BY id LIMIT 1', [$kab]);
    $bid_lain = (string) nilai('SELECT kode FROM bidang WHERE kode<>? ORDER BY kode LIMIT 1', [$bid]);
    $program = (int) nilai('SELECT id FROM sf_program ORDER BY id LIMIT 1');
    cek($kab_lain > 0 && $bid_lain !== '' && $program > 0, 'Kabupaten lain, bidang lain, dan program acuan tersedia');

    /* Keadaan kosong, SEBELUM fixture: bidang agen biasanya tanpa antrean. Bila ternyata ada, dilewati. */
    $antre_bid = (int) nilai("SELECT (SELECT COUNT(*) FROM aduan WHERE status='Baru' AND bidang_kode=?)
        + (SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE status='Ditinjau Bidang' AND bidang_kode=?)", [$bid, $bid]);
    if ($antre_bid === 0 && ($jk = masuk($AKUN['admin_bidang']))) {
        $rk = minta($jk, 'pemberitahuan');
        cek($rk['kode'] === 200 && strpos($rk['badan'], 'data-kosong-pemberitahuan') !== FALSE && strpos($rk['badan'], 'data-modul-pemberitahuan') === FALSE,
            'admin_bidang tanpa antrean: keadaan kosong tampil, tanpa bagian modul');
        cek(strpos($rk['badan'], 'Tidak ada yang menunggu tindakan.') !== FALSE && strpos($rk['badan'], 'data-perlu-tindakan="') === FALSE,
            'admin_bidang tanpa antrean: topbar Perlu tindakan kosong');
    } else {
        echo "  (lewat: bidang agen sudah punya antrean, keadaan kosong tidak bisa diuji)\n";
    }

    /* Fixture. String pribadi berpenanda $TAG supaya pemeriksaan "tidak bocor" tidak tertipu kebetulan. */
    $PII = ["Nama Rahasia $TAG", "$TAG.pelapor@example.test", "Judul Pribadi $TAG", "Pesan Pribadi $TAG", "NIM$TAG", "Kampus $TAG"];
    $hp = '0899' . random_int(1000000, 9999999);
    $PII[] = $hp;
    $uid = jalan("INSERT INTO usr_akun (nama,email,nama_pengguna,kata_sandi,peran,status,profil_lengkap,created_at)
        VALUES (?,?,?,'x','mahasiswa','active',1,NOW())", ["Nama Rahasia $TAG", "$TAG.mhs@example.test", $TAG])['id'];
    $bersih[] = fn() => jalan('DELETE FROM usr_akun WHERE id=?', [$uid]);
    $aduan = function ($bidang) use ($TAG, $uid, &$bersih) {
        $id = jalan("INSERT INTO aduan (user_id,nama,email,judul,pesan,bidang_kode,status,created_at) VALUES (?,?,?,?,?,?,'Baru',NOW())",
            [$uid, "Nama Rahasia $TAG", "$TAG.pelapor@example.test", "Judul Pribadi $TAG", "Pesan Pribadi $TAG", $bidang])['id'];
        $bersih[] = fn() => jalan('DELETE FROM aduan WHERE id=?', [$id]);
        return $id;
    };
    $antrean = function ($kab_id, $kode, $hari) use ($program, &$bersih) {
        $id = jalan("INSERT INTO sf_antrean_pengajuan (kunci_pengajuan,kode_tiket,kabupaten_id,program_id,status_antrean,created_at,updated_at)
            VALUES (?,?,?,?,'pending',DATE_SUB(NOW(), INTERVAL $hari DAY),NOW())", [bin2hex(random_bytes(16)), $kode, $kab_id, $program])['id'];
        $bersih[] = fn() => jalan('DELETE FROM sf_antrean_pengajuan WHERE id=?', [$id]);
        return $id;
    };
    $magang = function ($status, $bidang) use ($TAG, $uid, $hp, &$bersih) {
        $id = jalan("INSERT INTO kkn_magang_pendaftaran (user_id,nim,jenis,instansi_asal,no_hp,divisi_atau_tema,bidang_kode,status,created_at)
            VALUES (?,?,'magang',?,?,'Uji',?,?,NOW())", [$uid, "NIM$TAG", "Kampus $TAG", $hp, $bidang, $status])['id'];
        $bersih[] = fn() => jalan('DELETE FROM kkn_magang_pendaftaran WHERE id=?', [$id]);
        return $id;
    };
    $kode_a = strtoupper("U" . substr($TAG, 4) . "A"); $kode_b = strtoupper("U" . substr($TAG, 4) . "B"); // kode_tiket varchar(10)
    $FX = [
        'aduan_a' => $aduan($bid), 'aduan_b' => $aduan($bid_lain),
        'antrean_a' => $antrean($kab, $kode_a, 3), 'antrean_b' => $antrean($kab_lain, $kode_b, 0),
        'magang_a' => $magang('Ditinjau Bidang', $bid), 'magang_b' => $magang('Ditinjau Bidang', $bid_lain),
        'magang_c' => $magang('Diajukan', $bid),
    ];
    cek(min($FX) > 0, 'Fixture aduan, antrean, dan pendaftaran magang terbentuk');

    /* Hitungan DB ditulis ulang di sini (bukan dibaca dari registry) supaya uji ini tidak ikut salah
       bila registry atau kodenya melenceng. Kunci = kunci modul registry. */
    $kab_q = (int) $kab; $bid_q = $db->real_escape_string($bid);
    $PERAN = [
        'admin' => [
            'validasi_antrean' => ["SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE status_antrean='pending'", 'Admin?status=pending'],
            'srp2_verifikasi'  => ["SELECT COUNT(*) FROM srp2_pengajuan WHERE status_verifikasi='Pending'", 'Admin_Srp2/pending?status=Pending'],
            'aduan_semua'      => ["SELECT COUNT(*) FROM aduan WHERE status='Baru'", 'Admin_Aduan?status=Baru'],
            'kemitraan'        => ["SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE status='Diajukan'", 'Admin_Kemitraan?status=Diajukan'],
            'konsultasi_janji' => ["SELECT COUNT(*) FROM forum_janji_temu WHERE status='diajukan'", 'Admin_Konsultasi?status=diajukan'],
        ],
        'admin_kabkota' => [
            'antrean_kabkota' => ["SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE status_antrean='pending' AND kabupaten_id=$kab_q", 'Admin_Kabkota?status=pending'],
        ],
        'admin_bidang' => [
            'aduan_bidang'     => ["SELECT COUNT(*) FROM aduan WHERE status='Baru' AND bidang_kode='$bid_q'", 'Admin_Bidang?status=Baru'],
            // Halaman magang bidang tidak punya filter status: menunya tetap ke modul tanpa parameter.
            'kemitraan_bidang' => ["SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE status='Ditinjau Bidang' AND bidang_kode='$bid_q'", 'Kemitraan_Bidang'],
        ],
    ];
    // [peran, modul, fixture, harus tampil?]
    $CAKUPAN = [
        ['admin', 'validasi_antrean', 'antrean_a', TRUE], ['admin', 'validasi_antrean', 'antrean_b', TRUE],
        ['admin', 'aduan_semua', 'aduan_a', TRUE], ['admin', 'aduan_semua', 'aduan_b', TRUE],
        ['admin', 'kemitraan', 'magang_c', TRUE],
        ['admin_kabkota', 'antrean_kabkota', 'antrean_a', TRUE], ['admin_kabkota', 'antrean_kabkota', 'antrean_b', FALSE],
        ['admin_bidang', 'aduan_bidang', 'aduan_a', TRUE], ['admin_bidang', 'aduan_bidang', 'aduan_b', FALSE],
        ['admin_bidang', 'kemitraan_bidang', 'magang_a', TRUE], ['admin_bidang', 'kemitraan_bidang', 'magang_b', FALSE],
    ];

    foreach ($PERAN as $peran => $modul) {
        echo "\n== $peran ==\n";
        $j = masuk($AKUN[$peran]);
        if ( ! cek($j !== NULL, "$peran: login berhasil")) { continue; }
        $r = minta($j, 'pemberitahuan');
        if ( ! cek($r['kode'] === 200 && strpos($r['badan'], 'id="main-content"') !== FALSE, "$peran: /pemberitahuan terbuka 200 di kerangka admin")) { continue; }
        $html = $r['badan'];
        $main = isi_main($html);
        cek(strpos($html, 'A PHP Error was encountered') === FALSE && strpos($html, 'Severity:') === FALSE, "$peran: tanpa galat PHP");
        $ada_bagian = FALSE;

        foreach ($modul as $kunci => [$sql, $href_filter]) {
            $n = (int) nilai($sql);
            preg_match('/<section\b[^>]*data-modul-pemberitahuan="' . $kunci . '"[^>]*data-jumlah="(\d+)"[^>]*>(.*?)<\/section>/s', $main, $m);
            preg_match('/data-badge-modul="' . $kunci . '"[^>]*title="(\d+) ([^"]+)"[^>]*>(\d+)</', $html, $bd);
            if ($n === 0) {
                cek(empty($m), "$peran/$kunci: DB 0 baris, tidak ada bagian di halaman");
                cek(empty($bd), "$peran/$kunci: DB 0 baris, tidak ada badge sidebar");
                continue;
            }
            $ada_bagian = TRUE;
            cek(isset($m[1]) && (int) $m[1] === $n, "$peran/$kunci: jumlah bagian = DB ($n, dapat " . ($m[1] ?? '-') . ')');
            cek(isset($bd[3]) && (int) $bd[3] === $n, "$peran/$kunci: badge sidebar = DB ($n, dapat " . ($bd[3] ?? '-') . ')');
            cek(isset($bd[1]) && (int) $bd[1] === $n && trim($bd[2]) !== '', "$peran/$kunci: badge bertooltip \"$n ...\" (" . ($bd[1] ?? '-') . ' ' . ($bd[2] ?? '') . ')');
            $baris = isset($m[2]) ? preg_match_all('/data-baris-pemberitahuan="' . $kunci . ':\d+"/', $m[2]) : -1;
            cek($baris === min($n, 50), "$peran/$kunci: baris tampil = min(jumlah, 50) (dapat $baris)");
            cek(isset($m[2]) && (strpos($m[2], 'Cara menyelesaikan') !== FALSE), "$peran/$kunci: ada petunjuk cara menyelesaikan");
            if ($n > 50) { cek(strpos($m[2] ?? '', 'data-tautan-modul') !== FALSE, "$peran/$kunci: lebih dari 50, ada tautan lihat semua di modul"); }

            // Menu sidebar yang ber-badge membawa filter yang cocok dengan badge-nya.
            preg_match('/<a href="([^"]+)"(?:(?!<\/a>).)*data-badge-modul="' . $kunci . '"/s', $html, $ah);
            cek(isset($ah[1]) && substr(html_entity_decode($ah[1]), -strlen($href_filter)) === $href_filter, "$peran/$kunci: menu ber-badge menaut ke $href_filter (dapat " . ($ah[1] ?? '-') . ')');
            if (isset($ah[1])) {
                $rf = minta($j, substr(html_entity_decode($ah[1]), strlen(BASE)));
                cek($rf['kode'] === 200 && strpos($rf['badan'], 'id="main-content"') !== FALSE, "$peran/$kunci: tautan menu ber-filter terbuka 200");
            }

            // Topbar: Perlu tindakan.
            preg_match('/<a\b[^>]*href="([^"]*)"[^>]*data-perlu-tindakan="' . $kunci . '"[^>]*>(.*?)<\/a>/s', $html, $tb);
            cek(isset($tb[1]) && strpos($tb[1], 'pemberitahuan#modul-' . $kunci) !== FALSE, "$peran/$kunci: topbar Perlu tindakan menaut ke bagiannya");
            cek(isset($tb[2]) && preg_match('/>\s*' . $n . '\s*</', $tb[2]) === 1, "$peran/$kunci: topbar menyebut angka $n");
            cek(strpos($main, 'id="modul-' . $kunci . '"') !== FALSE, "$peran/$kunci: bagian halaman ber-id modul-$kunci untuk tautan topbar");
        }
        cek(preg_match('/<a\b[^>]*data-perlu-tindakan-semua[^>]*href="[^"]*pemberitahuan"|<a\b[^>]*href="[^"]*pemberitahuan"[^>]*data-perlu-tindakan-semua/', $html) === 1, "$peran: topbar punya tautan Lihat semua ke /pemberitahuan");
        if ( ! $ada_bagian) { cek(strpos($main, 'data-kosong-pemberitahuan') !== FALSE, "$peran: keadaan kosong tampil"); }

        // Cakupan per fixture.
        foreach ($CAKUPAN as [$p, $kunci, $fx, $harus]) {
            if ($p !== $peran) { continue; }
            $ada = strpos($main, 'data-baris-pemberitahuan="' . $kunci . ':' . $FX[$fx] . '"') !== FALSE;
            $n = (int) nilai($modul[$kunci][0]);
            if ($harus && $n > 50) { echo "  (lewat: $kunci > 50 baris, fixture $fx mungkin di luar 50 terbaru)\n"; continue; }
            cek($ada === $harus, "$peran/$kunci: fixture $fx " . ($harus ? 'tampil' : 'TIDAK tampil (di luar cakupan)'));
        }
        if ($peran === 'admin_kabkota') {
            cek(strpos($main, $kode_a) !== FALSE && strpos($main, $kode_b) === FALSE, 'admin_kabkota: kode tiket wilayahnya tampil, kode wilayah lain tidak');
            cek(preg_match('#<tr>(?:(?!</tr>).)*Menunggu 3 hari(?:(?!</tr>).)*data-baris-pemberitahuan="antrean_kabkota:' . $FX['antrean_a'] . '"#s', $main) === 1, 'admin_kabkota: umur fixture tampil "Menunggu 3 hari"');
        }

        // Tanpa data pribadi.
        $bocor = array_values(array_filter($PII, fn($s) => stripos($main, $s) !== FALSE));
        cek($main !== '' && $bocor === [], "$peran: nol string pribadi fixture di isi halaman" . ($bocor ? ' (bocor: ' . implode(', ', $bocor) . ')' : ''));
        cek(preg_match('/(?<!\d)\d{16}(?!\d)/', teks($main)) === 0, "$peran: nol deret 16 digit (NIK/KK) di isi halaman");

        // Tautan baris fixture dan tautan modul terbuka 200 untuk peran ini.
        $tautan = [];
        foreach ($CAKUPAN as [$p, $kunci, $fx, $harus]) {
            if ($p === $peran && $harus && preg_match('/<a\b[^>]*href="([^"]+)"[^>]*data-baris-pemberitahuan="' . $kunci . ':' . $FX[$fx] . '"/', $main, $am)) { $tautan[] = $am[1]; }
        }
        preg_match_all('/<a\b[^>]*href="([^"]+)"[^>]*data-tautan-modul/', $main, $tm);
        $tautan = array_unique(array_merge($tautan, $tm[1]));
        cek(count($tautan) > 0, "$peran: ada tautan tindakan untuk diuji");
        foreach ($tautan as $t) {
            $t = html_entity_decode($t);
            $rt = minta($j, substr($t, strlen(BASE)));
            cek(strpos($t, BASE) === 0 && $rt['kode'] === 200 && stripos($rt['url'], 'login') === FALSE && strpos($rt['badan'], 'id="main-content"') !== FALSE,
                "$peran: tautan " . substr($t, strlen(BASE)) . " terbuka 200 (dapat {$rt['kode']})");
        }

        // Ringkasan topbar juga hadir di halaman modul lain, bukan cuma di /pemberitahuan.
        $lain = ['admin' => 'Admin_Dashboard', 'admin_kabkota' => 'Admin_Kabkota', 'admin_bidang' => 'Admin_Bidang'][$peran];
        $rl = minta($j, $lain);
        cek($rl['kode'] === 200 && strpos($rl['badan'], 'data-perlu-tindakan-semua') !== FALSE, "$peran: topbar Perlu tindakan tampil juga di $lain");
    }

    echo "\n== Bukan staf ==\n";
    foreach (['warga', 'pengembang', 'mahasiswa'] as $peran) {
        $j = masuk($AKUN[$peran]);
        if ( ! cek($j !== NULL, "$peran: login berhasil")) { continue; }
        $r = minta($j, 'pemberitahuan');
        cek($r['kode'] === 404 && strpos($r['badan'], 'data-modul-pemberitahuan') === FALSE, "$peran: /pemberitahuan dijawab 404 (dapat {$r['kode']})");
    }
    $tamu = tempnam(sys_get_temp_dir(), 'ujpb');
    $jars[] = $tamu;
    $r = minta($tamu, 'pemberitahuan');
    cek(stripos($r['url'], 'login') !== FALSE && strpos($r['badan'], 'data-modul-pemberitahuan') === FALSE, 'tamu: /pemberitahuan dialihkan ke login');
} catch (Throwable $e) {
    if ($e->getMessage() !== 'prasyarat') { cek(FALSE, 'Suite melempar ' . get_class($e) . ': ' . $e->getMessage() . ' (baris ' . $e->getLine() . ')'); }
} finally {
    foreach (array_reverse($bersih) as $f) { try { $f(); } catch (Throwable $e) { echo '  (bersih gagal: ' . $e->getMessage() . ")\n"; } }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', [$baris['kunci'], $baris['jendela_mulai_at'], $baris['jumlah_gagal']]); }
    }
    foreach ($jars as $f) { @unlink($f); }
    cek((int) nilai("SELECT COUNT(*) FROM usr_akun WHERE email LIKE ?", ["$TAG%"]) === 0, 'Nol akun fixture tertinggal');
}
echo "\nRINGKASAN: $total pemeriksaan, $gagal gagal\n";
exit($gagal ? 1 : 0);
