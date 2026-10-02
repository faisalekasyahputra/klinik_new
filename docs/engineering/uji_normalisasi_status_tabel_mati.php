<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji tabel mati, kosakata status tertutup, dan penyapu cache (Fase 3 normalisasi langkah 5, migrasi 071):
 *
 *   php docs/engineering/uji_normalisasi_status_tabel_mati.php
 *
 * - sys_menu, sys_multi, data_sosmed_perumahan tidak ada, dan tidak dirujuk kode aplikasi.
 * - Tiap CHECK di Migration_Status_tertutup_tabel_mati::CEK terpasang, nol baris di luar himpunan,
 *   himpunannya sama dengan whitelist/konstanta penulisnya, dan menolak nilai asing (termasuk beda
 *   huruf besar-kecil) pada sesi TANPA strict mode, persis koneksi aplikasi.
 * - Penyapu retensi menyapu cache hulu yang basi di folder sementara, tidak pernah index.html,
 *   .htaccess, penanda retensi, atau berkas lain; mode kering hanya menghitung.
 *
 * Tidak menulis data: percobaan tulis CHECK berada di dalam transaksi yang selalu di-ROLLBACK
 * (dan memang ditolak), penyapu diuji di folder sementara, bukan application/cache.
 */
define('BASEPATH', 'uji');
$AKAR = dirname(__DIR__, 2);
$env = [];
foreach (file($AKAR . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$total = 0; $gagal = 0;
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$sumber = fn($rel) => (string) file_get_contents($AKAR . '/application/' . $rel);
$daftar = function ($isi, $pola) { return preg_match($pola, $isi, $m) ? array_map(fn($s) => trim($s, " '"), explode(',', $m[1])) : NULL; };

if ( ! class_exists('CI_Migration')) { class CI_Migration {} }
if ( ! class_exists('CI_Model')) { class CI_Model {} }
require $AKAR . '/application/migrations/20260701000071_status_tertutup_tabel_mati.php';
require $AKAR . '/application/migrations/20260701000072_penamaan_indonesia.php';
$M = 'Migration_Status_tertutup_tabel_mati';
$cache_uji = NULL;

try {
    echo "=== UJI STATUS TERTUTUP, TABEL MATI, PENYAPU CACHE (MIGRASI 071) ===\n";

    echo "\n-- Tabel mati --\n";
    $mati = array_keys($M::TABEL_MATI);
    $cek((int) $satu("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME IN ('" . implode("','", $mati) . "')") === 0, 'Tidak ada lagi: ' . implode(', ', $mati));
    preg_match("/migration_version'\] = (\d+);/", file_get_contents($AKAR . '/application/config/migration.php'), $vm);
    $cek(($vm[1] ?? '') >= '20260701000071' && (string) $satu('SELECT version FROM migrations') === $vm[1], 'Config dan DB di versi yang sama, paling rendah 20260701000071');
    $status = (string) shell_exec('php ' . escapeshellarg($AKAR . '/index.php') . ' migrate status 2>&1');
    $cek(strpos($status, 'status tertutup + tabel mati (migrasi 071): TERPASANG') !== FALSE, 'Migrate::status melaporkan 071 TERPASANG');
    $rujukan = [];
    $pindai = [$AKAR . '/application', $AKAR . '/assets/js'];
    foreach ($pindai as $akar) {
        if ( ! is_dir($akar)) continue;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar, FilesystemIterator::SKIP_DOTS)) as $f) {
            $p = str_replace('\\', '/', $f->getPathname());
            if ( ! preg_match('/\.(php|js)$/', $p) || strpos($p, '/migrations/') !== FALSE || strpos($p, '/logs/') !== FALSE || strpos($p, '.min.js') !== FALSE) continue;
            if (preg_match('/\b(' . implode('|', $mati) . ')\b/', file_get_contents($p))) $rujukan[] = substr($p, strlen($AKAR) + 1);
        }
    }
    $cek($rujukan === [], 'Nol rujukan ke tabel mati di application/ dan assets/js' . ($rujukan ? ' - ' . implode(', ', $rujukan) : ''));

    echo "\n-- Kosakata status (CHECK) --\n";
    $klausa = [];
    foreach ($db->query("SELECT TABLE_NAME, CONSTRAINT_NAME, CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE()")->fetch_all(MYSQLI_ASSOC) as $r) $klausa[$r['TABLE_NAME'] . '.' . $r['CONSTRAINT_NAME']] = $r['CHECK_CLAUSE'];
    $db->query("SET SESSION sql_mode = ''"); // koneksi aplikasi: stricton FALSE membuang STRICT_*
    // Tabel/kolom di konstanta 071 memakai nama sebelum migrasi 072; diterjemahkan lewat peta 072.
    foreach ($M::CEK as $nama => [$tabel_071, $kolom_071, $nilai]) {
        [$tabel, $kolom] = [Migration_Penamaan_indonesia::tabel($tabel_071), Migration_Penamaan_indonesia::kolom($tabel_071, $kolom_071)];
        $k = $klausa["$tabel.$nama"] ?? '';
        $lengkap = $k !== '' && stripos($k, 'binary') !== FALSE;
        foreach ($nilai as $v) $lengkap = $lengkap && strpos($k, "'$v'") !== FALSE;
        $cek($lengkap, "$tabel.$kolom: CHECK $nama terpasang, BINARY, memuat " . count($nilai) . ' nilai');
        $cek((int) $satu("SELECT COUNT(*) FROM `$tabel` WHERE `$kolom` IS NOT NULL AND NOT (" . $M::ekspresi($kolom, $nilai) . ')') === 0,
            "$tabel.$kolom: nol baris di luar himpunan");
        $id = $satu("SELECT id FROM `$tabel` ORDER BY id LIMIT 1");
        if ($id === NULL) { $cek(TRUE, "$tabel.$kolom: tabel kosong, uji tulis dilewati"); continue; }
        $db->query('START TRANSACTION');
        $asing = $db->query("UPDATE `$tabel` SET `$kolom` = 'nilai_asing_071' WHERE id = " . (int) $id);
        $huruf = $db->query("UPDATE `$tabel` SET `$kolom` = '" . strtoupper($nilai[0]) . "' WHERE id = " . (int) $id);
        $db->query('ROLLBACK');
        $cek($asing === FALSE && $huruf === FALSE, "$tabel.$kolom: nilai asing dan '" . strtoupper($nilai[0]) . "' ditolak pada sesi tanpa strict mode");
    }
    $sama = function ($a, $b) { if ($a === NULL) return FALSE; sort($a); sort($b); return $a === $b; };
    $C = $M::CEK;
    $cek($sama($daftar($sumber('controllers/Admin_Aduan.php'), '/\$status_sah = \[([^\]]+)\]/'), $C['ck_aduan_status'][2])
        && $sama($daftar($sumber('controllers/Admin_Bidang.php'), "/in_array\(\\\$status, \[([^\]]+)\], TRUE\)/"), $C['ck_aduan_status'][2]),
        'aduan: himpunan = whitelist Admin_Aduan dan Admin_Bidang');
    $cek($sama($daftar($sumber('controllers/Admin_Kemitraan.php'), '/\$status_sah = \[([^\]]+)\]/'), $C['ck_kkn_pendaftaran_status'][2]),
        'kkn_magang_pendaftaran: himpunan = $status_sah Admin_Kemitraan');
    $cek($sama($daftar($sumber('controllers/Admin_Srp2.php'), '/\$status_pilihan = \[([^\]]+)\]/'), $C['ck_srp2_status_verifikasi'][2]),
        'srp2_pengajuan: himpunan = $status_pilihan Admin_Srp2');
    require_once $AKAR . '/application/models/Janji_temu_model.php';
    $alur = array_keys(Janji_temu_model::ALUR);
    foreach (Janji_temu_model::ALUR as $ke) $alur = array_merge($alur, array_keys($ke));
    $cek($sama(array_values(array_unique($alur)), $C['ck_janji_temu_status'][2]), 'forum_janji_temu: himpunan = Janji_temu_model::ALUR');
    require_once $AKAR . '/application/helpers/housing_queue_helper.php';
    $enum = $satu("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sf_antrean_pengajuan' AND COLUMN_NAME='status_antrean'");
    preg_match_all("/'([^']+)'/", (string) $enum, $em);
    $cek($sama(array_keys(housing_queue_statuses()), $C['ck_riwayat_antrean_ke'][2]) && $sama($em[1], $C['ck_riwayat_antrean_ke'][2])
        && $C['ck_riwayat_antrean_dari'][2] === $C['ck_riwayat_antrean_ke'][2], 'sf_riwayat_keputusan_antrean: himpunan = housing_queue_statuses() = ENUM status_antrean');
    $kode = $sumber('models/Auth_model.php') . $sumber('controllers/Admin_Users.php') . $sumber('controllers/Kemitraan_Bidang.php') . $sumber('models/Housing_assessment_model.php');
    $ada = TRUE;
    foreach (array_merge($C['ck_usr_users_status'][2], $C['ck_penilaian_status'][2]) as $v) $ada = $ada && strpos($kode, "'$v'") !== FALSE;
    $cek($ada, 'usr_akun.status dan sf_penilaian_perumahan.status: tiap nilai himpunan memang ditulis penulisnya');

    echo "\n-- Penyapu cache hulu --\n";
    $config = []; require $AKAR . '/application/config/data_lifecycle.php';
    $pol = $config['data_lifecycle']['retensi'];
    $cek(is_int($pol['cache_hulu_hari'] ?? NULL) && $pol['cache_hulu_hari'] >= 2, 'cache_hulu_hari bilangan bulat dan melewati TTL terpanjang (1 hari)');
    require_once $AKAR . '/application/libraries/Penyapu_retensi.php';
    $app = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji071_' . bin2hex(random_bytes(3)) . DIRECTORY_SEPARATOR;
    $cache_uji = $app . 'cache' . DIRECTORY_SEPARATOR;
    mkdir($cache_uji, 0777, TRUE);
    $lama = time() - ($pol['cache_hulu_hari'] + 1) * 86400;
    $berkas = ['ajax_perumahan_lama.json' => $lama, 'sikaper_baru.json' => time() - 3600, 'sikumbang_detail_x_gagal.flag' => time() - 2 * 86400,
        'sikumbang_gagal.flag' => time() - 30, 'index.html' => $lama, '.htaccess' => $lama, 'retensi_terakhir' => $lama, 'catatan.txt' => $lama];
    foreach ($berkas as $b => $t) { file_put_contents($cache_uji . $b, 'x'); touch($cache_uji . $b, $t); }
    $db_mati = new class { function query() { return FALSE; } function affected_rows() { return 0; } };
    $penyapu = new Penyapu_retensi(['db' => $db_mati, 'policy' => $pol, 'app' => $app]);
    $kering = $penyapu->jalankan(TRUE)['tugas']['cache_hulu'] ?? NULL;
    clearstatcache();
    $cek(($kering['jumlah'] ?? NULL) === 2 && count(glob($cache_uji . '{,.}*', GLOB_BRACE)) - 2 === count($berkas), 'Mode kering menghitung 2 berkas basi dan tidak menghapus apa pun');
    $hasil = $penyapu->jalankan(FALSE)['tugas']['cache_hulu'] ?? NULL;
    clearstatcache();
    $sisa = array_values(array_filter(array_keys($berkas), fn($b) => is_file($cache_uji . $b)));
    $cek(($hasil['jumlah'] ?? NULL) === 2 && $sisa === ['sikaper_baru.json', 'sikumbang_gagal.flag', 'index.html', '.htaccess', 'retensi_terakhir', 'catatan.txt'],
        'Menyapu json basi dan bendera gagal lewat sehari; json segar, bendera baru, index.html, .htaccess, penanda, berkas lain utuh');
    $retensi = $sumber('controllers/Retensi.php');
    $cek(strpos($sumber('libraries/Penyapu_retensi.php'), "\$hasil['cache_hulu'] = \$this->sapu_cache(") !== FALSE && strpos($retensi, "foreach (\$hasil['tugas']") !== FALSE,
        'Penyapu cache ikut jalankan(), jadi ikut pemicu harian web dan `php index.php retensi jalankan [kering]`');
    $cek(preg_match('/preg_match\(\'\/\^\[A-Za-z0-9\]\{1,32\}\$\/\', \(string\) \$idLokasi\)\)\s*\{\s*show_404\(\);\s*return;\s*\}\s*\$cache_file/', $sumber('controllers/Index.php')) === 1,
        'detail_perum menolak idLokasi selain huruf/angka sebelum menjadi nama berkas cache');
} catch (Throwable $e) {
    $cek(FALSE, 'Pengecualian: ' . $e->getMessage());
} finally {
    if ($cache_uji && is_dir($cache_uji)) {
        foreach (glob($cache_uji . '{,.}*', GLOB_BRACE) as $f) if (is_file($f)) @unlink($f);
        @rmdir($cache_uji); @rmdir(dirname($cache_uji));
    }
}

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
