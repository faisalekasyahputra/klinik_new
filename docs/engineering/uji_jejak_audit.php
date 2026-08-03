<?php
/**
 * Penjaga statis untuk layar Jejak Audit (Admin_Audit).
 *
 *   php docs/engineering/uji_jejak_audit.php
 *
 * TIDAK butuh server maupun DB — sengaja. Yang dijaga di sini adalah dua janji
 * yang rusaknya SENYAP, bukan tampilannya:
 *
 * 1. LAYARNYA READ-ONLY. Ini bukan preferensi gaya. Jejak audit yang bisa
 *    dihapus lewat UI tidak melindungi siapa pun — orang yang paling ingin satu
 *    baris hilang justru orang yang memegang tombolnya. Penjaganya statis
 *    karena method tulis yang ditambahkan enam bulan lagi akan lolos semua uji
 *    fungsional: ia BEKERJA, itu masalahnya.
 *
 * 2. WHITELIST SORT MENUNJUK KOLOM YANG BENAR-BENAR ADA. `db_debug` MATI di
 *    production (AGENTS.md §17 poin 9): ORDER BY ke kolom salah ketik tidak
 *    melempar apa pun, query cuma mengembalikan FALSE dan layarnya kosong
 *    seolah memang belum ada jejak. Kolomnya dibaca dari migrasi 033, bukan
 *    disalin ke sini — daftar kembar akan berbohong begitu skemanya berubah.
 */

define('APP_ROOT', dirname(__DIR__, 2));

$total = 0; $gagal = 0;
$cek = function ($kondisi, $label) use (&$total, &$gagal) {
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $gagal++; }
};

$controller = file_get_contents(APP_ROOT . '/application/controllers/Admin_Audit.php');
$migrasi    = file_get_contents(APP_ROOT . '/application/migrations/20260701000033_jejak_audit_pusat.php');

echo "== Admin_Audit read-only ==\n";

// Method publik = endpoint yang bisa dipanggil lewat URL. Hanya index() yang boleh.
preg_match_all('/^\s*public\s+function\s+(\w+)/m', $controller, $m);
$publik = array_diff($m[1], ['__construct']);
$cek($publik === ['index'] || array_values($publik) === ['index'],
    'Hanya index() yang publik (ditemukan: ' . (implode(', ', $publik) ?: 'tidak ada') . ')');

// Panggilan tulis query builder, sekalipun lewat method privat.
foreach (['insert', 'update', 'delete', 'replace', 'truncate', 'empty_table'] as $tulis) {
    $cek( ! preg_match('/->' . $tulis . '\s*\(/', $controller), "Tidak memanggil db->{$tulis}()");
}

echo "\n== Whitelist sort menunjuk kolom nyata ==\n";

// Kolom dibaca dari CREATE TABLE di migrasinya, bukan didaftar ulang di sini.
preg_match_all('/^\s*`(\w+)`\s+[A-Z]/m', $migrasi, $km);
$kolom = $km[1];
$cek(in_array('created_at', $kolom, TRUE), 'Kolom migrasi terbaca (' . count($kolom) . ' kolom)');

preg_match('/table_state\(\[(.*?)\]/s', $controller, $sm);
preg_match_all("/'(\w+)'/", $sm[1] ?? '', $wm);
$whitelist = $wm[1];
$cek($whitelist !== [], 'Whitelist sort terbaca (' . implode(', ', $whitelist) . ')');
foreach ($whitelist as $w) {
    $cek(in_array($w, $kolom, TRUE), "Kolom sort '{$w}' ada di sys_jejak_audit");
}

// Filter ?aksi= wajib dicocokkan ke daftar DISTINCT, bukan dipakai apa adanya.
$cek((bool) preg_match('/in_array\(\$aksi,\s*\$data\[.aksi_tersedia.\],\s*TRUE\)/', $controller),
    'Filter ?aksi= dicocokkan ke daftar yang ada di tabel');

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
