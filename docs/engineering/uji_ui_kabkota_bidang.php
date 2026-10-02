<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * Uji tampilan layar Admin Kab/Kota dan Admin Bidang (rekam data, pendataan awal, aduan bidang,
 * kemitraan bidang) beserta layar pantau superadmin yang memakai view rekam yang sama.
 *
 *   php docs/engineering/uji_ui_kabkota_bidang.php
 *
 * Dijaga: setiap layar terbuka 200; di dalam <main> tidak ada tombol di luar set bersama
 * (.tombol-utama/.tombol-kedua/.tombol-aksi/.chip-filter/.tombol-tab/.tombol-ikon), tidak ada
 * gradien, tidak ada label tombol berhuruf kapital semua; status draf memakai label pengguna
 * "Draft, belum dikirim". Akun (@example.test) dan laporan uji dibuat lalu dihapus sendiri,
 * pada kabupaten dan tahun yang belum punya laporan, supaya tidak menyentuh data lain.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$root = dirname(__DIR__, 2);
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    if ( ! isset($env[trim($k)])) { $env[trim($k)] = trim($v); }
}
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujiuikb' . bin2hex(random_bytes(3));
$sandi = 'Pa1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $users = []; $laporan = [];

function cek($ok, $label) {
    global $total, $gagal;
    $total++;
    if ( ! $ok) { $gagal++; }
    echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n";
}

function akun($role, $kab = NULL, $bidang = NULL) {
    global $db, $tag, $sandi, $users;
    $e = "{$tag}_{$role}_" . mt_rand(1000, 9999) . '@example.test';
    $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_akun (nama,email,nama_pengguna,kata_sandi,peran,kabupaten_id,bidang_kode,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at)
        VALUES ('Uji UI KB',?,?,?,?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $u = $tag . mt_rand(10000, 99999);
    $st->bind_param('ssssis', $e, $u, $h, $role, $kab, $bidang);
    $st->execute();
    $users[] = $db->insert_id;
    return $e;
}

function laporan($domain, $kab, $tahun, $tw, $status, $step = 'review') {
    global $db, $laporan;
    $kirim = $status === 'draft' ? NULL : date('Y-m-d H:i:s');
    $st = $db->prepare("INSERT INTO rd_laporan (domain,kabupaten_id,tahun,triwulan,status,langkah_sekarang,submitted_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())");
    $st->bind_param('siiisss', $domain, $kab, $tahun, $tw, $status, $step, $kirim);
    $st->execute();
    return $laporan[] = (int) $db->insert_id;
}

function http($jar, $path, $post = NULL) {
    global $BASE;
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']]);
    if ($post !== NULL) { curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $b = (string) curl_exec($ch);
    $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $u = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [$kode, $b, $u];
}

function masuk($email) {
    global $jars, $sandi;
    $j = tempnam(sys_get_temp_dir(), 'uikb');
    $jars[] = $j;
    [, $b] = http($j, 'Auth/login');
    preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $b, $m);
    [, $r] = http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $m[1] ?? '']);
    return [(json_decode($r, TRUE)['status'] ?? '') === 'success', $j];
}

/** Tombol/tautan-bergaya-tombol di luar set bersama; aturan sama dengan pemindai uji_regresi_tampilan.php. */
function tombol_liar($isi) {
    $isi = preg_replace_callback('/<\?(?:php|=)?(.*?)\?>/s', fn($m) => '{' . str_replace(['"', "'", '<', '>'], ' ', $m[1]) . '}', $isi);
    preg_match_all('/<(button|a)\b((?:"[^"]*"|\'[^\']*\'|[^>\'"])*)>/s', $isi, $m, PREG_SET_ORDER);
    $liar = [];
    foreach ($m as $t) {
        if ($t[1] === 'a' && ! preg_match('/tombol-|rounded[^"]*\bpy-|\bpy-[^"]*rounded/', $t[2])) { continue; }
        if ($t[1] === 'a' && ! preg_match('/\b(?:bg-|border\b|tombol-)/', $t[2])) { continue; }
        if (preg_match('/\b(?:tombol-utama|tombol-kedua|tombol-aksi|chip-filter|tombol-tab|tombol-ikon)\b/', $t[2])) { continue; }
        $liar[] = substr(preg_replace('/\s+/', ' ', $t[2]), 0, 90);
    }
    return $liar;
}

/** Label tombol yang seluruhnya huruf kapital (singkatan pendek seperti BNBA atau TW I dibiarkan). */
function label_kapital($html) {
    preg_match_all('#<button\b[^>]*>(.*?)</button>#s', $html, $m);
    $salah = [];
    foreach ($m[1] as $isi) {
        $teks = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($isi))));
        $huruf = preg_replace('/[^A-Za-z]/', '', $teks);
        if (strlen($huruf) > 5 && strtoupper($huruf) === $huruf) { $salah[] = $teks; }
    }
    return $salah;
}

function periksa_layar($jar, $path, $nama, array $harus = []) {
    [$kode, $b] = http($jar, $path);
    cek($kode === 200 && strpos($b, 'id="main-content"') !== FALSE, "{$nama}: terbuka 200 di kerangka admin");
    $awal = strpos($b, 'id="main-content"');
    $akhir = strrpos($b, '</main>');
    $main = $awal !== FALSE && $akhir !== FALSE ? substr($b, $awal, $akhir - $awal) : '';
    $liar = tombol_liar($main);
    cek($main !== '' && $liar === [], "{$nama}: semua tombol memakai set bersama" . ($liar ? ' (liar: ' . implode(' | ', $liar) . ')' : ''));
    cek($main !== '' && preg_match('/gradient\(|bg-gradient-|\b(?:from|via)-(?:[a-z]+-\d|brand)/', $main) === 0, "{$nama}: tanpa gradien");
    $kap = label_kapital($main);
    cek($kap === [], "{$nama}: tidak ada label tombol berhuruf kapital semua" . ($kap ? ' (' . implode(', ', $kap) . ')' : ''));
    cek(strpos($main, 'uppercase') === FALSE || preg_match('/<(?:button|a)\b[^>]*\buppercase\b/', $main) === 0, "{$nama}: tombol tidak dipaksa huruf besar");
    cek(strpos($b, 'A PHP Error was encountered') === FALSE && strpos($b, 'Severity:') === FALSE, "{$nama}: tanpa galat PHP");
    foreach ($harus as $teks => $label) { cek(strpos($main, $teks) !== FALSE, "{$nama}: {$label}"); }
    return $main;
}

try {
    echo "=== UJI TAMPILAN LAYAR ADMIN KAB/KOTA DAN ADMIN BIDANG ===\n";

    echo "\n-- Sumber view: utang tombol nol\n";
    $berkas = array_merge(glob($root . '/application/views/admin/rekam/*.php'), glob($root . '/application/views/admin_bidang/*.php'),
        glob($root . '/application/views/admin/kemitraan_bidang/*.php'), [$root . '/application/views/admin/antrean/pendataan_awal.php']);
    foreach ($berkas as $f) {
        $isi = (string) file_get_contents($f);
        $nama = str_replace($root . '/application/views/', '', $f);
        cek(tombol_liar($isi) === [], "{$nama}: tombol di luar set bersama = 0");
        cek(preg_match('/gradient\(|bg-gradient-|\b(?:from|via)-(?:[a-z]+-\d|brand)/', $isi) === 0, "{$nama}: tanpa gradien");
        cek(strpos($isi, "\u{2014}") === FALSE && strpos($isi, "\u{2013}") === FALSE, "{$nama}: tanpa em dash atau en dash");
    }
    foreach (['perumahan_wizard.php', 'perumahan_capaian.php'] as $f) {
        $isi = (string) file_get_contents($root . '/application/views/admin/rekam/' . $f);
        cek(strpos($isi, "'draft' => 'Draft, belum dikirim'") !== FALSE, "{$f}: label draf sama dengan Riwayat");
    }

    // Kabupaten dan tahun yang belum punya laporan sama sekali: semua baris di sana milik uji ini.
    $T = (int) date('Y') - 3;
    $kab = (int) $db->query("SELECT k.id FROM kabupaten k WHERE NOT EXISTS (SELECT 1 FROM rd_laporan l WHERE l.kabupaten_id=k.id AND l.tahun={$T}) ORDER BY k.id DESC LIMIT 1")->fetch_row()[0];
    $draftP = laporan('perumahan', $kab, $T, 1, 'draft', 'isian');
    $db->query("INSERT INTO rd_perumahan_program (laporan_id,program,dilaporkan) VALUES ({$draftP},'pk_rtlh',1)");
    $kirimP = laporan('perumahan', $kab, $T, 2, 'terkirim');
    laporan('kawasan', $kab, $T, 1, 'draft', 'periode');
    $kirimK = laporan('kawasan', $kab, $T, 2, 'terkirim');

    echo "\n-- Admin kab/kota\n";
    [$ok, $j] = masuk(akun('admin_kabkota', $kab));
    cek($ok, 'Akun uji admin kab/kota masuk');
    periksa_layar($j, 'Rekam_Data', 'Sambutan Rekam Data');
    periksa_layar($j, "Rekam_Perumahan?tahun={$T}&triwulan=1", 'Capaian Perumahan (draf)', ['Draft, belum dikirim' => 'status draf berlabel pengguna']);
    periksa_layar($j, "Rekam_Perumahan/rekap?tahun={$T}&triwulan=2", 'Rekap Perumahan');
    periksa_layar($j, "Rekam_Perumahan/riwayat?tahun={$T}", 'Riwayat Perumahan', ['Draft, belum dikirim' => 'status draf berlabel pengguna']);
    periksa_layar($j, "Rekam_Perumahan/input?tahun={$T}", 'Wizard Perumahan: periode');
    foreach (['program', 'isian', 'bnba', 'review'] as $l) {
        periksa_layar($j, "Rekam_Perumahan/input?laporan={$draftP}&langkah={$l}", "Wizard Perumahan: {$l}", ['Draft, belum dikirim' => 'status draf berlabel pengguna']);
    }
    periksa_layar($j, "Rekam_Kawasan?tahun={$T}&triwulan=1", 'Input Kawasan (draf)', ['Draft, belum dikirim' => 'status draf berlabel pengguna']);
    periksa_layar($j, "Rekam_Kawasan/rekap?tahun={$T}&triwulan=2", 'Rekap Kawasan');
    periksa_layar($j, "Rekam_Kawasan/riwayat?tahun={$T}", 'Riwayat Kawasan');
    periksa_layar($j, 'Admin_Kabkota/pendataan_awal', 'Pendataan Awal Warga');
    $n = (int) $db->query("SELECT COUNT(*) FROM rd_laporan WHERE kabupaten_id={$kab} AND tahun={$T}")->fetch_row()[0];
    cek($n === 4, 'Membuka layar tidak membuat laporan baru');

    echo "\n-- Admin bidang (perumahan)\n";
    [$ok, $j] = masuk(akun('admin_bidang', NULL, 'perumahan'));
    cek($ok, 'Akun uji admin bidang masuk');
    periksa_layar($j, "Rekam_Tinjauan?tahun={$T}", 'Tinjauan Perumahan: daftar');
    periksa_layar($j, "Rekam_Tinjauan/detail/{$kirimP}", 'Tinjauan Perumahan: detail', ['Terima laporan' => 'tombol keputusan tampil']);
    periksa_layar($j, 'Admin_Bidang', 'Aduan Bidang');
    periksa_layar($j, 'Kemitraan_Bidang', 'Kemitraan Bidang', ['Atur kuota &amp; bulan magang' => 'tombol kuota berlabel huruf kalimat']);

    echo "\n-- Superadmin (pantau, view rekam yang sama)\n";
    [$ok, $j] = masuk(akun('admin'));
    cek($ok, 'Akun uji superadmin masuk');
    periksa_layar($j, "Admin_Rekam_Data?tahun={$T}&triwulan=2", 'Pantau Rekam Data');
    periksa_layar($j, "Admin_Rekam_Data/detail/{$kirimK}", 'Pantau: detail Kawasan');
} finally {
    if ($laporan) {
        $ids = implode(',', array_map('intval', $laporan));
        foreach (['rd_perumahan_program', 'rd_perumahan_baris', 'rd_perumahan_bnba', 'rd_kawasan_ringkasan', 'rd_kawasan_intervensi'] as $t) {
            $db->query("DELETE FROM {$t} WHERE laporan_id IN ({$ids})");
        }
        $db->query("DELETE FROM rd_laporan WHERE id IN ({$ids})");
    }
    foreach ($users as $id) {
        $db->query('DELETE FROM sys_jejak_audit WHERE pelaku_id=' . (int) $id);
        $db->query('DELETE FROM usr_akun WHERE id=' . (int) $id);
    }
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}_%@example.test'");
    foreach ($jars as $jj) { @unlink($jj); }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
