<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji halaman Kebijakan Privasi dan Syarat dan Ketentuan (4 Okt 2026).
 *
 *   php docs/engineering/uji_halaman_hukum.php
 *   UJI_BASE_URL=http://localhost/klinik_privasi php docs/engineering/uji_halaman_hukum.php
 *
 * Tanpa DB dan tanpa akun: semua yang diperiksa terlihat oleh tamu anonim.
 *   1. /kebijakan-privasi dan /syarat-ketentuan menjawab 200 dan memuat bagian kuncinya.
 *   2. Angka retensi di halaman privasi sama dengan config/data_lifecycle.php (bukan karangan).
 *   3. Spanduk draf mengikuti $config['dokumen_hukum_draf'] (config/kebijakan_data.php): di
 *      halaman hidup sesuai nilai sekarang, dan kepala bersamanya dirender ulang untuk TRUE dan FALSE.
 *   4. Formulir daftar tidak lagi memuat tautan mati href="#" untuk S&K dan privasi.
 *   5. Footer publik (halaman hidup) dan footer dashboard memuat kedua tautan.
 */
$AKAR = dirname(__DIR__, 2);
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$total = 0; $gagal = 0;
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if ( ! $ok) { $gagal++; } echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$ambil = function ($jalur) use ($BASE) {
    $c = curl_init($BASE . $jalur);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30]);
    $b = (string) curl_exec($c); $k = (int) curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
    return [$k, $b];
};
$konfig = function ($berkas) use ($AKAR) {
    if ( ! defined('BASEPATH')) { define('BASEPATH', $AKAR . '/system/'); }
    $config = []; include $AKAR . '/application/config/' . $berkas . '.php'; return $config;
};
$kebijakan = $konfig('kebijakan_data');
$retensi = $konfig('data_lifecycle')['data_lifecycle']['retensi'];

echo "=== UJI HALAMAN HUKUM ($BASE) ===\n";

/* 1. Kedua halaman. Penjaga positif (200 dan berbadan) SEBELUM asersi negatif (§0e). */
[$kp, $priv] = $ambil('kebijakan-privasi');
[$ks, $sk] = $ambil('syarat-ketentuan');
$cek($kp === 200 && strlen($priv) > 5000, "Tamu membuka /kebijakan-privasi: HTTP $kp");
$cek($ks === 200 && strlen($sk) > 5000, "Tamu membuka /syarat-ketentuan: HTTP $ks");
foreach (['pengelola', 'data-dikumpulkan', 'sumber', 'tujuan', 'siapa-melihat', 'keamanan', 'retensi', 'hak', 'cookie', 'kontak'] as $id) {
    $cek(strpos($priv, 'id="' . $id . '"') !== FALSE, "Kebijakan Privasi punya bagian #$id");
}
foreach (['Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah', 'Undang-Undang Nomor 27 Tahun 2022', 'AES-256-GCM', 'Unduh Data Saya', 'Hapus Akun Saya', 'Ajukan Penghapusan Data Layanan', 'data contoh'] as $frasa) {
    $cek(strpos($priv, $frasa) !== FALSE, "Kebijakan Privasi menyebut \"$frasa\"");
}
foreach (['layanan', 'pengguna', 'akun', 'larangan', 'aduan-konsultasi', 'rekomendasi', 'pengembang', 'kemitraan', 'hukum', 'kontak'] as $id) {
    $cek(strpos($sk, 'id="' . $id . '"') !== FALSE, "Syarat dan Ketentuan punya bagian #$id");
}
foreach (['Satu orang satu akun', 'Keputusan tetap ada di dinas', 'dinonaktifkan', 'hukum Republik Indonesia'] as $frasa) {
    $cek(strpos($sk, $frasa) !== FALSE, "Syarat dan Ketentuan menyebut \"$frasa\"");
}
$cek(strpos($priv, 'Terakhir diperbarui: ' . $kebijakan['dokumen_hukum_diperbarui']) !== FALSE
    && strpos($sk, 'Terakhir diperbarui: ' . $kebijakan['dokumen_hukum_diperbarui']) !== FALSE, 'Kedua halaman menampilkan tanggal pembaruan dari config');
$cek(strpos($priv, $BASE . 'syarat-ketentuan') !== FALSE && strpos($sk, $BASE . 'kebijakan-privasi') !== FALSE, 'Kedua halaman saling menautkan');

/* 2. Angka retensi diambil dari config, jadi mengubah config mengubah halaman. */
$teks = preg_replace('/\s+/', ' ', strip_tags($priv));
foreach ([
    'Log aplikasi: ' . $retensi['log_aplikasi_hari'] . ' hari',
    'Penghitung pembatas percobaan: ' . $retensi['rate_limit_hari'] . ' hari',
    'Langganan notifikasi yang sudah dimatikan: ' . $retensi['langganan_push_nonaktif_hari'] . ' hari',
    'Jejak audit: ' . round($retensi['jejak_audit_hari'] / 365) . ' tahun',
    'dihapus ' . $retensi['snapshot_simperum_lewat_hari'] . ' hari setelah kedaluwarsa',
    'terverifikasi: ' . $retensi['draf_nik_dipindah_hari'] . ' hari',
] as $frasa) {
    $cek(strpos($teks, $frasa) !== FALSE, "Retensi sesuai config: \"$frasa\"");
}

/* 3. Spanduk draf. */
$draf = ! empty($kebijakan['dokumen_hukum_draf']);
$ada = function ($html) { return strpos($html, 'id="spanduk-draf-hukum"') !== FALSE; };
$cek($ada($priv) === $draf && $ada($sk) === $draf, 'Spanduk draf di halaman hidup sesuai dokumen_hukum_draf = ' . var_export($draf, TRUE));
$render = function ($nilai) use ($AKAR) {
    $draf = $nilai; $diperbarui = '1 Januari 2000'; $judul_dokumen = 'Uji'; $pengantar = 'x'; $tautan_lain = '#'; $label_lain = 'y';
    ob_start(); include $AKAR . '/application/views/pages/umum/dokumen_hukum_kepala.php'; return (string) ob_get_clean();
};
$cek($ada($render(TRUE)) && strpos($render(TRUE), 'Versi draf, menunggu peninjauan dinas') !== FALSE, 'Kepala dokumen dengan draf TRUE memasang spanduk');
$cek( ! $ada($render(FALSE)) && strpos($render(FALSE), 'Terakhir diperbarui: 1 Januari 2000') !== FALSE, 'Kepala dokumen dengan draf FALSE tanpa spanduk, tanggal tetap tampil');

/* 4. Formulir daftar. */
[$kr, $daftar] = $ambil('Auth/register');
$cek($kr === 200 && strpos($daftar, 'name="tos_agree"') !== FALSE, "Halaman daftar terbuka dengan centang persetujuan: HTTP $kr");
$cek(preg_match('/<a href="#"[^>]*>\s*(Ketentuan Layanan|Kebijakan Privasi)/', $daftar) === 0, 'Tidak ada tautan mati href="#" untuk Ketentuan Layanan/Kebijakan Privasi');
$cek(strpos($daftar, $BASE . 'syarat-ketentuan') !== FALSE && strpos($daftar, $BASE . 'kebijakan-privasi') !== FALSE, 'Formulir daftar menautkan kedua halaman');
$srp2 = (string) file_get_contents($AKAR . '/application/views/pages/pengembang/syarat.php');
$cek(strpos($srp2, "base_url('syarat-ketentuan')") !== FALSE && strpos($srp2, "base_url('kebijakan-privasi')") !== FALSE, 'Persetujuan daftar SRP2 menautkan kedua halaman');

/* 5. Footer. */
[$kb, $beranda] = $ambil('');
$footer = substr($beranda, (int) strrpos($beranda, '&copy;'));
$cek($kb === 200 && strpos($footer, $BASE . 'kebijakan-privasi') !== FALSE && strpos($footer, $BASE . 'syarat-ketentuan') !== FALSE, "Footer beranda publik memuat kedua tautan: HTTP $kb");
$adm = (string) file_get_contents($AKAR . '/application/views/admin/layouts/footer.php');
$cek(strpos($adm, "base_url('kebijakan-privasi')") !== FALSE && strpos($adm, "base_url('syarat-ketentuan')") !== FALSE && strpos($adm, 'href="#"') === FALSE, 'Footer dashboard memuat kedua tautan, tanpa href="#"');
foreach (['application/views/pages/auth/login.php', 'application/views/components/login_modal.php'] as $berkas_masuk) {
    $isi_masuk = (string) file_get_contents($AKAR . '/' . $berkas_masuk);
    $cek(strpos($isi_masuk, "base_url('kebijakan-privasi')") !== FALSE && strpos($isi_masuk, "base_url('syarat-ketentuan')") !== FALSE, "$berkas_masuk menautkan Kebijakan Privasi dan Syarat dan Ketentuan");
}

/* Aturan repo: tanpa em dash dan en dash di berkas baru. */
foreach (['application/views/pages/umum/kebijakan_privasi.php', 'application/views/pages/umum/syarat_ketentuan.php', 'application/views/pages/umum/dokumen_hukum_kepala.php', 'docs/engineering/uji_halaman_hukum.php'] as $r) {
    $s = (string) file_get_contents($AKAR . '/' . $r);
    $cek($s !== '' && strpos($s, "\u{2014}") === FALSE && strpos($s, "\u{2013}") === FALSE, "$r tanpa em/en dash");
}

echo "\n$total pemeriksaan, $gagal gagal\n";
exit($gagal ? 1 : 0);

