<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
require_once __DIR__ . '/_akun_demo.php'; // akun demo nonaktif sejak migrasi 073: dipinjam selama suite berjalan
date_default_timezone_set('Asia/Jakarta');
/*
 * Uji tampilan halaman detail admin (2 Okt 2026): Detail Penilaian Warga
 * (Admin/detail dan Admin_Kabkota/detail, satu view), Detail Aduan, Detail SRP2,
 * dan Peserta KKN. Memeriksa HTML hasil render lewat HTTP: kartu pekat
 * (.kartu-admin) di setiap bagian, tidak ada kode mentah atau tanggal ISO di
 * layar petugas, tombol memakai set tombol bersama, dan B2 tetap tersamar.
 *
 * Akun yang dipakai adalah akun demo/uji yang sudah ada (tidak membuat akun):
 *   superadmin admin@klinikpkp.jatengprov.go.id, admin kab/kota adminkabkota@example.com
 *   (Kota Semarang, pemilik antrean contoh), agen_admin_kabkota@agen.test (kab/kota lain).
 * Jalankan: php docs/engineering/uji_ui_halaman_detail.php
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));

$total = 0; $gagal = 0;
function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $gagal++; }
    return (bool) $kondisi;
}
function http($n, $path, ?array $post = NULL, $ajax = FALSE) {
    static $jar = [];
    $jar[$n] = $jar[$n] ?? tempnam(sys_get_temp_dir(), 'ujd_');
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $o = [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $jar[$n], CURLOPT_COOKIEFILE => $jar[$n],
          CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60];
    if ($ajax) { $o[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $o[CURLOPT_POST] = TRUE; $o[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $o);
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
function login($n, $email, $sandi) {
    $b = http($n, 'Auth/login');
    $t = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $b, $m) ? $m[1] : '';
    $r = http($n, 'Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $email, 'password' => $sandi], TRUE);
    return (json_decode($r, TRUE)['status'] ?? '') === 'success';
}
// Isi halaman saja: mulai dari judul halaman sampai footer, tanpa <script>/<style>.
function isi($html) {
    $a = strpos($html, 'data-judul-halaman>');
    $b = strrpos($html, '<footer');
    if ($a === FALSE) { return ''; }
    $s = substr($html, $a, ($b !== FALSE && $b > $a ? $b : strlen($html)) - $a);
    return preg_replace('#<(script|style)\b.*?</\1>#s', '', $s);
}
function teks($isi) { return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(str_replace('>', '> ', $isi))), ENT_QUOTES, 'UTF-8'); }
function section_tanpa_kartu($isi) {
    preg_match_all('/<section\b([^>]*)>/', $isi, $m);
    return count(array_filter($m[1], fn($a) => strpos($a, 'kartu-admin') === FALSE));
}
// Salinan pemindai tombol_liar_admin() di uji_regresi_tampilan.php (blok "Fondasi tampilan admin").
function tombol_liar($isi) {
    $isi = preg_replace_callback('/<\?(?:php|=)?(.*?)\?>/s', fn($m) => '{' . str_replace(['"', "'", '<', '>'], ' ', $m[1]) . '}', $isi);
    preg_match_all('/<(button|a)\b((?:"[^"]*"|\'[^\']*\'|[^>\'"])*)>/s', $isi, $m, PREG_SET_ORDER);
    $liar = 0;
    foreach ($m as $t) {
        if ($t[1] === 'a' && ! preg_match('/tombol-|rounded[^"]*\bpy-|\bpy-[^"]*rounded/', $t[2])) { continue; }
        if ($t[1] === 'a' && ! preg_match('/\b(?:bg-|border\b|tombol-)/', $t[2])) { continue; }
        if (preg_match('/\b(?:tombol-utama|tombol-kedua|tombol-aksi|chip-filter|tombol-tab|tombol-ikon)\b/', $t[2])) { continue; }
        $liar++;
    }
    return $liar;
}

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $b) {
    $b = trim($b);
    if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $b, 2);
    $env[trim($k)] = $env[trim($k)] ?? trim($v);
}
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($db->connect_error) { die("Koneksi DB gagal.\n"); }
$satu = fn($sql) => ($r = $db->query($sql)) && ($row = $r->fetch_row()) ? $row[0] : NULL;

echo "== Berkas view: tombol memakai set tombol bersama ==\n";
foreach (['admin/antrean/detail.php', 'admin/aduan/detail.php', 'admin/srp2/detail.php', 'admin/kemitraan/peserta.php'] as $v) {
    $sumber = (string) file_get_contents(APP_ROOT . '/application/views/' . $v);
    cek(tombol_liar($sumber) === 0, "{$v}: utang tombol nol");
    cek(preg_match('/\x{2013}|\x{2014}/u', $sumber) === 0, "{$v}: tanpa en dash atau em dash");
    cek(strpos($sumber, 'admin/components/judul_halaman') !== FALSE, "{$v}: judul memakai komponen judul_halaman");
}

echo "\n== Detail Penilaian Warga (superadmin) ==\n";
if ( ! login('su', 'admin@klinikpkp.jatengprov.go.id', pinjam_akun_demo('admin@klinikpkp.jatengprov.go.id'))) { cek(FALSE, 'Login superadmin'); exit(1); }
// Antrean contoh 49 (cabang calon lahan, isian lahan lengkap); bila tidak ada, antrean terbaru.
$qid = (int) ($satu('SELECT id FROM sf_antrean_pengajuan WHERE id = 49') ?: $satu('SELECT MAX(id) FROM sf_antrean_pengajuan'));
$html = http('su', 'Admin/detail/' . $qid);
$isi = isi($html);
$t = teks($isi);
// Nama efektif yang dilihat superadmin; dipakai untuk memastikan admin kab/kota tidak melihatnya (B2).
$nama_asli = preg_match('#Nama lengkap</th><td[^>]*>[^<]*</td><td[^>]*>([^<]+)</td>#', $isi, $mn) ? html_entity_decode($mn[1]) : '';
cek($isi !== '' && strpos($t, 'Detail Penilaian Warga') !== FALSE, "Admin/detail/{$qid} dirender dengan judul_halaman");
cek(section_tanpa_kartu($isi) === 0 && substr_count($isi, '<section') >= 5, 'Setiap section berkelas kartu-admin (' . substr_count($isi, '<section') . ' section)');
cek(strpos($isi, 'class="tumpuk-bagian"') !== FALSE, 'Jarak antarbagian memakai .tumpuk-bagian');
cek(preg_match('/<a [^>]*class="tombol-kedua"[^>]*>.*?Kembali ke antrean/s', $isi) === 1, 'Tautan kembali bergaya .tombol-kedua');
foreach (['hm<', 'inheritance', 'parent<', 'eligible', 'needs_data', 'not_eligible', 'SIM-2026', 'Ruleset', '>slum<', '>rent<'] as $mentah) {
    cek(strpos($isi, $mentah) === FALSE, "Tidak ada kode mentah \"{$mentah}\"");
}
cek(preg_match('/\b\d{4}-\d{2}-\d{2}\b/', $t) === 0, 'Tidak ada tanggal ISO mentah di layar');
cek(preg_match('/\b\d+\.\d{2}\b/', $t) === 0, 'Tidak ada desimal gaya Inggris (12.00)');
cek(preg_match('/\x{2013}|\x{2014}/u', $t) === 0, 'Tidak ada en dash atau em dash di layar');
$asesmen = $db->query('SELECT a.* FROM sf_antrean_pengajuan q JOIN sf_penilaian_perumahan a ON a.id = q.penilaian_id WHERE q.id = ' . $qid);
$a = $asesmen ? $asesmen->fetch_assoc() : NULL;
if ($a && ($a['status_lahan_calon'] ?? '') === 'hm') { cek(strpos($t, 'Sertifikat HM') !== FALSE, 'Sertifikat calon lahan "hm" tampil "Sertifikat HM"'); }
if ($a && ($a['asal_lahan_calon'] ?? '') === 'inheritance') { cek(strpos($t, 'Warisan') !== FALSE, 'Asal tanah "inheritance" tampil "Warisan"'); }
if ($a && ($a['hubungan_pemilik_lahan'] ?? '') === 'parent') { cek(strpos($t, 'Orang Tua') !== FALSE, 'Hubungan "parent" tampil "Orang Tua"'); }
if (strpos($isi, 'name="status" value="approved"') !== FALSE) {
    cek(substr_count($isi, 'class="pilihan-keputusan"') === 3, 'Tiga pilihan keputusan bergaya .pilihan-keputusan');
    cek(preg_match('/<button class="tombol-utama"><i class="ph [^"]+"><\/i><span>Simpan keputusan<\/span><\/button>/', $isi) === 1, 'Tombol Simpan keputusan memakai .tombol-utama berikon');
}

echo "\n== Detail Penilaian Warga (admin kab/kota, B2) ==\n";
$kab_q = (int) $satu('SELECT kabupaten_id FROM sf_antrean_pengajuan WHERE id = ' . $qid);
if ($kab_q === (int) $satu("SELECT kabupaten_id FROM usr_akun WHERE email = 'adminkabkota@example.com'") && login('kab', 'adminkabkota@example.com', pinjam_akun_demo('adminkabkota@example.com'))) {
    $isi_k = isi(http('kab', 'Admin_Kabkota/detail/' . $qid));
    $t_k = teks($isi_k);
    cek($isi_k !== '' && section_tanpa_kartu($isi_k) === 0, "Admin_Kabkota/detail/{$qid}: view sama, setiap section berkelas kartu-admin");
    $b2 = (string) @file_get_contents(APP_ROOT . '/application/config/kebijakan_data.php');
    if (strpos($b2, "'menunggu_keputusan'") !== FALSE) {
        cek(strpos($t_k, 'Warga Contoh') !== FALSE && $nama_asli !== '' && strpos($t_k, $nama_asli) === FALSE, 'B2: identitas warga tetap tersamar untuk admin kab/kota');
    }
    cek(stripos($isi_k, 'inheritance') === FALSE && stripos($isi_k, 'needs_data') === FALSE, 'Kab/kota: label Indonesia juga, tanpa kode mentah');
} else {
    echo "  LEWAT antrean {$qid} bukan milik adminkabkota@example.com\n";
}
if (login('agen', 'agen_admin_kabkota@agen.test', 'AgenUji!2026')) {
    $kab_agen = (int) $satu("SELECT kabupaten_id FROM usr_akun WHERE email = 'agen_admin_kabkota@agen.test'");
    if ($kab_agen !== $kab_q) {
        cek(strpos(http('agen', 'Admin_Kabkota/detail/' . $qid), 'data-judul-halaman>') === FALSE, 'Cakupan wilayah: admin kab/kota lain tidak bisa membuka antrean ini');
    }
}

echo "\n== Detail Aduan ==\n";
$aid = (int) $satu('SELECT MAX(id) FROM aduan');
if ($aid) {
    $isi = isi(http('su', 'Admin_Aduan/detail/' . $aid));
    cek($isi !== '' && substr_count($isi, 'kartu-admin') >= 2, "Admin_Aduan/detail/{$aid}: bagian berkelas kartu-admin");
    cek(strpos($isi, 'class="tumpuk-bagian"') !== FALSE && strpos($isi, 'style="border-color:var(--portal-border') === FALSE, 'Aduan: .tumpuk-bagian, tanpa bingkai tembus bergaya portal');
    cek(preg_match('/class="tombol-kedua"[^>]*>.*?Kembali ke daftar aduan/s', $isi) === 1, 'Aduan: tautan kembali .tombol-kedua');
    cek(preg_match('/\b\d{4}-\d{2}-\d{2}\b/', teks($isi)) === 0, 'Aduan: tanpa tanggal ISO');
}

echo "\n== Detail SRP2 ==\n";
$sid = (int) ($satu("SELECT id FROM srp2_pengajuan WHERE status_verifikasi = 'Pending' ORDER BY id LIMIT 1") ?: $satu('SELECT MIN(id) FROM srp2_pengajuan'));
if ($sid) {
    $isi = isi(http('su', 'Admin_Srp2/detail/' . $sid));
    $t = teks($isi);
    cek($isi !== '' && section_tanpa_kartu($isi) === 0 && substr_count($isi, '<section') >= 2, "Admin_Srp2/detail/{$sid}: setiap section berkelas kartu-admin");
    cek(strpos($isi, 'rounded-3xl') === FALSE && strpos($isi, 'class="tumpuk-bagian"') !== FALSE, 'SRP2: kartu ringkas (bukan rounded-3xl) dan .tumpuk-bagian');
    cek(preg_match('/class="tombol-kedua"[^>]*>.*?Kembali ke daftar menunggu/s', $isi) === 1, 'SRP2: tautan kembali .tombol-kedua');
    cek(preg_match('/\b(?:direktur_utama|manajer_proyek|penanggung_jawab|staf_legal)\b/', $t) === 0, 'SRP2: jabatan tampil sebagai label, bukan kode');
}

echo "\n== Peserta KKN ==\n";
$kid = (int) $satu("SELECT MIN(id) FROM kkn_magang_pendaftaran WHERE jenis = 'kkn'");
if ($kid) {
    $isi = isi(http('su', 'Admin_Kemitraan/peserta/' . $kid));
    cek(strpos($isi, 'class="kartu-admin overflow-hidden"') !== FALSE, "Admin_Kemitraan/peserta/{$kid}: kartu tabel .kartu-admin");
    cek(preg_match('/class="tombol-kedua"[^>]*>.*?Kembali/s', $isi) === 1, 'Peserta: tautan kembali .tombol-kedua');
}

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
