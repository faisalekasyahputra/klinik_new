<?php
/**
 * Uji UAT alur universitas (sheet UAT "universitas" dinas, temuan U1-U8 28 Sep 2026).
 *
 *   php docs/engineering/uji_uat_universitas.php
 *
 * Patokan dinas: akun universitas dibuatkan admin bidang lalu diserahkan ke universitas,
 * universitas mengajukan KKN dengan surat permohonan, mengunggah roster, dokumentasi, dan
 * laporan; admin memutuskan dan menetapkan tanggal sertifikat; peserta mencetak lewat NIM.
 * Tiap kelompok di bawah menjaga satu kelompok perbaikan. Semua akun @example.test, KKN,
 * peserta, dan berkas dibuat dan dihapus sendiri; ember batas laju per IP yang disentuh
 * dipinjam lalu dikembalikan persis (jejak audit dibiarkan).
 */
$AKAR = dirname(__DIR__, 2);
require $AKAR . '/vendor/autoload.php';
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file($AKAR . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujiuat' . bin2hex(random_bytes(3));
$sandi = 'Uu1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $tmp = []; $uid = [];
$uploads = strtr(trim($env['PRIVATE_UPLOADS_PATH'] ?? ''), [chr(92) => '/']);
$uploads = $uploads === '' ? dirname($AKAR) . '/private_uploads' : rtrim(preg_match('#^([A-Za-z]:/|/)#', $uploads) ? $uploads : $AKAR . '/' . ltrim($uploads, '/'), '/');

$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$nilai = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$akun = function ($role, $bidang = NULL, $telp = '081234567890') use ($db, $tag, $sandi, &$uid) {
    $e = "{$tag}_{$role}" . count($uid) . '@example.test'; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,bidang_kode,phone,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES (?,?,?,?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $nama = "Uji {$role} {$tag}";
    $st->bind_param('ssssss', $nama, $e, $h, $role, $bidang, $telp); $st->execute();
    $uid[] = $db->insert_id; return [$db->insert_id, $e];
};
/** @return array [kode, body, url_akhir] - body sudah di-decode entitasnya. */
$http = function ($jar, $path, $post = NULL, $ajax = FALSE) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($ajax) curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
    if ($post !== NULL) {
        $berkas = array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $berkas ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch); $kode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch); unset($ch);
    return [$kode, html_entity_decode($b, ENT_QUOTES, 'UTF-8'), $url];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'uat'); $jars[] = $j; return $j; };
$login = function ($email, $ajax = FALSE) use ($http, $csrf, $sesi, $sandi) {
    $j = $sesi(); $http($j, 'Auth/login');
    $r = $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)], $ajax);
    return [$j, $r];
};
$kirim = function ($jar, $path, array $isi) use ($http, $csrf) { return $http($jar, $path, ['csrf_kpkp_token' => $csrf($jar)] + $isi); };
$pdf = function () use (&$tmp) { $f = tempnam(sys_get_temp_dir(), 'uatp') . '.pdf'; $tmp[] = $f; file_put_contents($f, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n"); return new CURLFile($f, 'application/pdf', 'surat.pdf'); };
$xlsx = function (array $baris) use (&$tmp) {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $book->getActiveSheet()->fromArray(array_merge([['NIM', 'Nama']], $baris), NULL, 'A1', TRUE);
    foreach ($baris as $i => $r) { $book->getActiveSheet()->setCellValueExplicit('A' . ($i + 2), $r[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); }
    $f = tempnam(sys_get_temp_dir(), 'uatx') . '.xlsx'; $tmp[] = $f;
    \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx')->save($f); return $f;
};
$roster = function ($jar, $id, array $baris) use ($kirim, $xlsx) {
    return $kirim($jar, 'KemitraanPortal/kkn_upload_peserta/' . $id, ['file_peserta' => new CURLFile($xlsx($baris), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'roster.xlsx')]);
};
$kkn = function ($user, $mulai, $selesai, $status, $tambahan = '') use ($db, $tag) {
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,created_at) VALUES ({$user},'kkn','{$tag} Kampus','081234567890','Tema {$tag}','{$mulai}','{$selesai}','{$status}',NOW())");
    $id = $db->insert_id;
    if ($tambahan !== '') $db->query("UPDATE kkn_magang_pendaftaran SET {$tambahan} WHERE id={$id}");
    return $id;
};
$peserta = function ($kkn, $nim, $nama) use ($db) { $st = $db->prepare("INSERT INTO kkn_peserta (pendaftaran_id,nim,nama,created_at) VALUES (?,?,?,NOW())"); $st->bind_param('iss', $kkn, $nim, $nama); $st->execute(); };
// Ember pencarian sertifikat (5/jam per IP) dikosongkan sebelum TIAP pencarian; isi aslinya
// sudah disimpan di awal dan dikembalikan di akhir.
$ember_ip = function ($pol) { $k = []; foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) $k[] = hash('sha256', $pol . ':ip:' . $ip); return $k; };
$cari = function ($nim, $jar = NULL) use ($http, $csrf, $sesi, $db, $ember_ip) {
    foreach ($ember_ip('sertifikat_kkn_lookup') as $k) $db->query("DELETE FROM sys_rate_limits WHERE limit_key='$k'");
    $j = $jar ?: $sesi(); $http($j, 'KemitraanPortal/sertifikat_kkn');
    return [$j, $http($j, 'KemitraanPortal/cek_sertifikat_kkn', ['csrf_kpkp_token' => $csrf($j), 'nim' => $nim])[1]];
};
$bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl = function ($ymd) use ($bulan) { $t = strtotime($ymd); return date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t); };

$ember = [];
try {
    echo "=== UJI UAT UNIVERSITAS ===\n";
    foreach (['sertifikat_kkn_lookup', 'login'] as $pol) foreach ($ember_ip($pol) as $k) {
        $ember[$k] = $db->query("SELECT limit_key, window_started_at, failed_attempts FROM sys_rate_limits WHERE limit_key='$k'")->fetch_assoc();
        $db->query("DELETE FROM sys_rate_limits WHERE limit_key='$k'");
    }
    [$idAdm, $eAdm] = $akun('admin');
    [$idBid, $eBid] = $akun('admin_bidang', 'kawasan');
    [$idBid0, $eBid0] = $akun('admin_bidang', NULL);
    [$idA, $eA] = $akun('universitas');
    [$idB, $eB] = $akun('universitas');
    [$idC, $eC] = $akun('universitas');
    [$idW, $eW] = $akun('warga');
    [$jAdm] = $login($eAdm); [$jBid] = $login($eBid); [$jA] = $login($eA);

    // === KELOMPOK 1: akun universitas dibuat admin bidang / superadmin ===
    echo "\n-- Akun universitas (U1, U2) --\n";
    [$k, $hal] = $http($jBid, 'Kemitraan_Bidang/universitas');
    $cek($k === 200 && strpos($hal, 'Akun Universitas') !== FALSE, 'Admin bidang membuka halaman Akun Universitas (200)');
    $cek($k === 200 && strpos($hal, 'Admin_Kemitraan') === FALSE, 'Halaman admin bidang tidak menaut ke tab superadmin Admin_Kemitraan (Pendaftaran, Slot, Akun)');
    [, $r] = $login($eBid0, TRUE);
    $json = json_decode($r[1], TRUE);
    $cek(($json['status'] ?? '') === 'success' && ($json['dashboard_url'] ?? '') !== 'Kemitraan_Bidang/universitas', 'Admin bidang tanpa bidang tidak diarahkan ke menu yang akan menolaknya (dashboard_url=' . ($json['dashboard_url'] ?? '?') . ')');

    $eBaru = "{$tag}_u1univ@example.test";
    $buat = function ($jar, $path, $email, $telp = '', $pw = NULL, $extra = []) use ($kirim, $sandi) {
        return $kirim($jar, $path, ['name' => 'Univ ' . $email, 'email' => $email, 'phone' => $telp, 'password' => $pw ?? $sandi, 'role' => 'universitas'] + $extra);
    };
    $buat($jBid, 'Kemitraan_Bidang/buat_universitas', $eBaru, '081234567890');
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$eBaru}' AND role='universitas'") === 1, 'Admin bidang membuat akun universitas');
    [, $hal] = $buat($jBid, 'Kemitraan_Bidang/buat_universitas', strtoupper(substr($eBaru, 0, 6)) . substr($eBaru, 6), '081234567890');
    $cek(strpos($hal, 'email tersebut sudah terdaftar') !== FALSE && (int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$eBaru}'") === 1, 'Email ganda (beda huruf besar) ditolak dengan pesan "email tersebut sudah terdaftar"');
    $buat($jBid, 'Kemitraan_Bidang/buat_universitas', "{$tag}_hp1@example.test", 'bukan-nomor-xx');
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$tag}_hp1@example.test'") === 0, 'Nomor HP bukan angka ditolak server');
    $buat($jBid, 'Kemitraan_Bidang/buat_universitas', "{$tag}_hp2@example.test", str_repeat('9', 25));
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$tag}_hp2@example.test'") === 0, 'Nomor HP lebih dari 20 karakter ditolak, bukan dipotong diam-diam');
    $buat($jBid, 'Kemitraan_Bidang/buat_universitas', "{$tag}_pw1@example.test", '', 'abcd1234');
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$tag}_pw1@example.test'") === 0, 'Admin bidang: sandi awal tanpa huruf besar/simbol ditolak (aturan sama dengan ganti sandi)');
    $http($jAdm, 'Admin_Kemitraan/universitas');
    $buat($jAdm, 'Admin_Users/create_staff', "{$tag}_pw2@example.test", '', 'abcd1234');
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$tag}_pw2@example.test'") === 0, 'Superadmin: sandi awal lemah ditolak create_staff');
    [, $hal] = $http($jAdm, 'Admin_Kemitraan/universitas');
    $cek(preg_match('/name="kembali"\s+value="Admin_Kemitraan\/universitas"/', $hal) === 1, 'Formulir Tambah Universitas superadmin membawa tujuan kembali');
    [, , $akhir] = $buat($jAdm, 'Admin_Users/create_staff', "{$tag}_u2univ@example.test", '081234567890', NULL, ['kembali' => 'Admin_Kemitraan/universitas']);
    $cek((int) $nilai("SELECT COUNT(*) FROM usr_users WHERE email='{$tag}_u2univ@example.test'") === 1 && substr($akhir, -strlen('Admin_Kemitraan/universitas')) === 'Admin_Kemitraan/universitas', 'Sesudah Tambah Universitas superadmin kembali ke tab Universitas (' . basename(dirname($akhir)) . '/' . basename($akhir) . ')');
    [, , $akhir] = $buat($jAdm, 'Admin_Users/create_staff', "{$tag}_u2univ2@example.test", '', NULL, ['kembali' => 'https://example.test/jahat']);
    $cek(strpos($akhir, 'example.test/jahat') === FALSE && substr($akhir, -strlen('Admin_Users')) === 'Admin_Users', 'Tujuan kembali di luar daftar diabaikan (tetap ke Admin_Users)');

    // === KELOMPOK 2: pengajuan KKN ===
    echo "\n-- Pengajuan KKN (U3) --\n";
    $jml = fn() => (int) $nilai("SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE user_id={$idA}");
    $tambah = function ($mulai, $selesai, $ket) use ($kirim, $jA, $pdf) {
        return $kirim($jA, 'KemitraanPortal/kkn_tambah', ['periode_mulai' => $mulai, 'periode_selesai' => $selesai, 'keterangan' => $ket, 'file_surat_pengantar' => $pdf(), 'file_surat_simperum' => $pdf()]);
    };
    $awal = $jml();
    $tambah('2098-02-31', '2098-03-15', "KKN {$tag} tanggal mustahil");
    $tambah('2098-13-45', '2098-13-46', "KKN {$tag} bulan 13");
    $cek($jml() === $awal && (int) $nilai("SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE user_id={$idA} AND periode_mulai='0000-00-00'") === 0, 'Tanggal kalender mustahil (31 Feb, bulan 13) ditolak, tanpa baris 0000-00-00');
    [, $hal] = $kirim($jA, 'KemitraanPortal/kkn_tambah', ['periode_mulai' => '2098-02-01', 'periode_selesai' => '2098-03-01', 'keterangan' => "Isian lama {$tag}", 'file_surat_pengantar' => $pdf()]);
    $cek(preg_match('/id="kt-keterangan"[^>]*value="Isian lama ' . $tag . '"/', $hal) === 1 && strpos($hal, 'value="2098-02-01"') !== FALSE, 'Sesudah ditolak, modal dibuka ulang dengan periode dan keterangan yang sudah diketik');
    $cek(preg_match('/<form[^>]*kkn_tambah[^>]*onsubmit="[^"]*disabled/', $hal) === 1, 'Tombol Ajukan KKN dinonaktifkan begitu formulir dikirim (lindung kirim ganda)');
    $awal = $jml();
    $tambah('2098-11-01', '2098-12-15', "KKN {$tag} ganda");
    [, $hal] = $tambah('2098-11-01', '2098-12-15', "KKN {$tag} ganda");
    $cek($jml() === $awal + 1, 'Pengajuan KKN identik (periode + keterangan) yang masih berjalan tidak membuat baris kedua');
    $cek(stripos($hal, 'sudah diajukan') !== FALSE, 'Pengajuan ganda diberi tahu sebagai duplikat');

    // === KELOMPOK 3: gerbang status, laporan, dokumentasi ===
    echo "\n-- KKN tertutup, laporan, dokumentasi (U3, U4, U6) --\n";
    $batal = $kkn($idA, '2026-01-01', '2026-02-01', 'Dibatalkan');
    $tolak = $kkn($idA, '2026-01-01', '2026-02-01', 'Ditolak');
    $kirim($jA, 'KemitraanPortal/kkn_simpan_dokumentasi/' . $batal, ['link_dokumentasi' => 'https://example.test/dok']);
    $cek($nilai("SELECT link_dokumentasi FROM kkn_magang_pendaftaran WHERE id={$batal}") === NULL, 'KKN Dibatalkan menolak link dokumentasi');
    $kirim($jA, 'KemitraanPortal/kkn_upload_laporan/' . $batal, ['file_laporan' => $pdf()]);
    $cek($nilai("SELECT file_laporan_akhir FROM kkn_magang_pendaftaran WHERE id={$batal}") === NULL && ! is_dir("{$uploads}/kemitraan/{$batal}"), 'KKN Dibatalkan menolak laporan akhir (tidak ada berkas mendarat)');
    $roster($jA, $batal, [['U4B001', 'Peserta Batal']]);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$batal}") === 0, 'KKN Dibatalkan menolak unggah roster');
    $roster($jA, $tolak, [['U4T001', 'Peserta Tolak']]);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$tolak}") === 0, 'KKN Ditolak menolak unggah roster');
    [$k, $hal] = $http($jA, 'KemitraanPortal/pendaftaran/' . $batal);
    $cek($k === 200 && strpos($hal, 'kkn_upload_peserta/') === FALSE && strpos($hal, 'kkn_simpan_dokumentasi/') === FALSE && strpos($hal, 'kkn_upload_laporan/') === FALSE, 'Detail KKN Dibatalkan tidak menampilkan formulir roster, dokumentasi, dan laporan');

    $lap = $kkn($idA, '2026-01-01', '2026-02-01', 'Diterima');
    $kirim($jA, 'KemitraanPortal/kkn_upload_laporan/' . $lap, ['file_laporan' => $pdf()]);
    $lama = $nilai("SELECT file_laporan_akhir FROM kkn_magang_pendaftaran WHERE id={$lap}");
    $kirim($jA, 'KemitraanPortal/kkn_upload_laporan/' . $lap, ['file_laporan' => $pdf()]);
    $baru = $nilai("SELECT file_laporan_akhir FROM kkn_magang_pendaftaran WHERE id={$lap}");
    $cek($lama && $baru && $lama !== $baru && ! is_file("{$uploads}/kemitraan/{$lap}/{$lama}") && is_file("{$uploads}/kemitraan/{$lap}/{$baru}"), 'Mengganti laporan akhir membuang berkas lama dari disk (kuota ikut lepas)');
    [, $hal] = $http($jAdm, 'Admin_Kemitraan?q=' . urlencode($tag));
    $cek(strpos($hal, 'Admin_Kemitraan/lihat_dokumen/' . $lap . '/laporan') !== FALSE, 'Daftar admin menaut ke laporan akhir KKN');
    [, $hal] = $kirim($jA, 'KemitraanPortal/kkn_simpan_dokumentasi/' . $lap, ['link_dokumentasi' => 'ftp://contoh.test/x']);
    $cek(strpos($hal, 'http:// atau https://') !== FALSE, 'Pesan galat link menyebut http:// atau https:// (sesuai aturan yang diterapkan)');

    // === KELOMPOK 4: roster peserta ===
    echo "\n-- Roster peserta (U4, U5) --\n";
    $ros = $kkn($idA, '2098-01-01', '2098-02-01', 'Diajukan');
    $roster($jA, $ros, [['21.11.1234', 'Peserta Titik'], ['21-11-5678', 'Peserta Strip']]);
    $nims = array_column($db->query("SELECT nim FROM kkn_peserta WHERE pendaftaran_id={$ros} ORDER BY nim")->fetch_all(), 0);
    $cek($nims === ['21111234', '21115678'], 'NIM bertitik/berstrip dinormalkan ke bentuk yang bisa dicari (' . implode(',', $nims) . ')');
    [, $hal] = $roster($jA, $ros, [['U4081', 'Lala Ganda'], ['U4082', 'Lili'], ['U4081', 'Lala Ganda']]);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$ros}") === 2 && stripos($hal, 'Baris 4') !== FALSE, 'NIM ganda dalam satu berkas ditolak dan barisnya disebut');
    [, $hal] = $roster($jA, $ros, [['U4#!1', 'Simbol']]);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$ros} AND nim LIKE 'U4#%'") === 0 && stripos($hal, 'Baris 2') !== FALSE, 'NIM bersimbol ditolak dengan nomor baris');
    [, $hal] = $roster($jA, $ros, [[str_repeat('7', 31), 'Panjang']]);
    $cek(strpos($hal, 'lebih dari 30 karakter') !== FALSE && stripos($hal, 'tidak lengkap') === FALSE, 'Pesan NIM terlalu panjang tidak menyebut baris "tidak lengkap"');
    if ( ! defined('BASEPATH')) define('BASEPATH', $AKAR . '/system/');
    require_once $AKAR . '/application/libraries/Upload_scanner.php';
    $bom = $xlsx([['U4BOM', 'Bom']]);
    $z = new ZipArchive(); $z->open($bom); $z->addFromString('xl/media/pad.bin', str_repeat("\0", 5 * 1048576)); $z->close();
    $scan = (new Upload_scanner(['clamd' => '']))->scan($bom, 'xlsx');
    $cek(empty($scan['ok']) && stripos($scan['message'] ?? '', 'makro') === FALSE, 'Bom zip ditolak dengan pesan yang bukan tentang makro (' . ($scan['code'] ?? '?') . ')');

    // === KELOMPOK 5: sertifikat dan keputusan admin ===
    echo "\n-- Sertifikat dan keputusan admin (U5, U7) --\n";
    [, $hal] = $cari('21.11.1234');
    $cek(strpos($hal, 'NIM tidak valid') === FALSE && strpos($hal, 'belum dapat diterbitkan') !== FALSE, 'Pencarian sertifikat memakai aturan NIM yang sama dengan roster: 21.11.1234 menemukan baris 21111234');
    $nimS = 'U5' . strtoupper(bin2hex(random_bytes(4)));
    $sert = $kkn($idB, '2026-01-01', '2026-02-01', 'Diterima', "tanggal_sertifikat='2026-03-01'");
    $peserta($sert, $nimS, "Nama Sesi {$tag}");
    [$jT, $hal] = $cari($nimS);
    $cek(strpos($hal, "Nama Sesi {$tag}") !== FALSE, 'Tamu menemukan sertifikat yang sah');
    $db->query("UPDATE kkn_magang_pendaftaran SET tanggal_sertifikat=NULL WHERE id={$sert}");
    $cek(substr($http($jT, 'KemitraanPortal/sertifikat_kkn_pdf')[1], 0, 4) !== '%PDF', 'Sesudah tanggal ditarik, PDF tidak terbit lagi dari sesi lama');
    $cek(strpos($http($jT, 'KemitraanPortal/cetak_sertifikat_kkn')[1], '<embed src=') === FALSE,'Sesudah tanggal ditarik, halaman cetak tidak menampilkan sertifikat dari sesi lama');

    $hari = date('Y-m-d');
    $nimH = 'U5H' . strtoupper(bin2hex(random_bytes(3)));
    $hini = $kkn($idB, '2026-01-01', $hari, 'Diterima', "tanggal_sertifikat='2026-03-01'");
    $peserta($hini, $nimH, 'Peserta Hari Ini');
    [, $hal] = $cari($nimH);
    $cek(strpos($hal, 'dapat dicetak mulai ' . $tgl(date('Y-m-d', strtotime('+1 day')))) !== FALSE, 'Pesan "dapat dicetak mulai" menyebut hari pertama cetak benar-benar bisa (besok)');

    $depan = $kkn($idB, '2098-10-01', '2098-12-31', 'Diterima');
    [, $hal] = $kirim($jAdm, 'Admin_Kemitraan/tanggal_sertifikat/' . $depan, ['tanggal_sertifikat' => '2098-10-15']);
    $cek(stripos($hal, 'sudah bisa mencetak') === FALSE && strpos($hal, $tgl('2099-01-01')) !== FALSE, 'Flash admin untuk tanggal sebelum periode selesai menyebut kapan peserta bisa mencetak');

    $nimY = 'U7' . strtoupper(bin2hex(random_bytes(4)));
    $sah = $kkn($idB, '2026-08-01', '2026-09-01', 'Diterima', "tanggal_sertifikat='2026-09-10'");
    $peserta($sah, $nimY, "Mahasiswa Sah {$tag}");
    $bayang = $kkn($idA, '2098-01-01', '2098-10-31', 'Diajukan');
    $peserta($bayang, $nimY, 'Pembayang');
    $bayang2 = $kkn($idA, '2098-01-01', '2098-11-30', 'Dibatalkan');
    $peserta($bayang2, $nimY, 'Pembayang Batal');
    [, $hal] = $cari($nimY);
    $cek(strpos($hal, "Mahasiswa Sah {$tag}") !== FALSE, 'Roster universitas lain (Diajukan/Dibatalkan) tidak membayangi sertifikat sah');

    $bls = $kkn($idA, '2026-01-01', '2026-02-01', 'Diterima');
    $http($jAdm, 'Admin_Kemitraan/ubah/' . $bls);
    [, $hal] = $kirim($jAdm, 'Admin_Kemitraan/unggah_balasan/' . $bls, ['file_surat_balasan' => $pdf()]);
    $cek(strpos($hal, 'Surat balasan diunggah') !== FALSE && strpos($hal, 'Mahasiswa sudah bisa') === FALSE, 'Flash surat balasan KKN tidak menyebut "Mahasiswa"');
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE objek_tipe='kkn_magang_pendaftaran' AND objek_id='{$bls}' AND aksi='kemitraan_balasan'") === 1, 'Unggah surat balasan tercatat di jejak audit');
    $kirim($jAdm, 'Admin_Kemitraan/proses/' . $bls, ['status' => 'Ditolak', 'catatan_admin' => "Alasan {$tag}"]);
    $kirim($jAdm, 'Admin_Kemitraan/proses/' . $bls, ['status' => 'Diterima', 'catatan_admin' => '']);
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE objek_tipe='kkn_magang_pendaftaran' AND objek_id='{$bls}' AND aksi='kemitraan_keputusan'") === 2
        && (int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE objek_id='{$bls}' AND aksi='kemitraan_keputusan' AND detail_json LIKE '%\"catatan_lama\":\"Alasan {$tag}\"%' AND detail_json LIKE '%\"status_baru\":\"Diterima\"%'") === 1,
        'Keputusan admin tercatat di audit, termasuk alasan penolakan yang ditimpa');

    // === KELOMPOK 6: akun dan halaman umum ===
    echo "\n-- Akun dan halaman umum (U8) --\n";
    $http($jA, 'Admin_Kemitraan');
    [$k, $hal] = $http($jA, 'Auth/akses_ditolak');
    $cek($k === 200 && preg_match('/Peran akun Anda<\/dt>\s*<dd[^>]*>Universitas</', $hal) === 1, 'Layar akses ditolak menampilkan peran "Universitas"');
    [$jC] = $login($eC);
    $http($jC, 'akun/profil');
    $jawab = [];
    for ($i = 0; $i < 8; $i++) { $jawab[] = $kirim($jC, 'akun/delete', ['current_password' => 'SalahSandi#' . $i])[0]; }
    $cek(in_array(429, $jawab, TRUE) && (int) $nilai("SELECT COUNT(*) FROM usr_users WHERE id={$idC}") === 1, 'Tebakan sandi berulang di hapus akun dibatasi (429), akun tetap ada');
    [$jW] = $login($eW);
    [, $hal] = $http($jW, 'KemitraanPortal');
    $cek(strpos($hal, 'akun yang sesuai') !== FALSE, 'Warga di KemitraanPortal diberi tahu butuh akun yang sesuai');
    [, $hal] = $http($jW, 'akun');
    $cek(strpos($hal, 'KKN dan Magang memerlukan akun') === FALSE, 'Pesan penolakan KemitraanPortal tidak muncul lagi di halaman berikutnya');
    [, $hal] = $http($sesi(), 'Auth/login?msg=account_deleted');
    $cek(stripos($hal, 'akun Anda sudah dihapus') !== FALSE, 'Halaman login mengonfirmasi akun sudah dihapus');
} finally {
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_rate_limits WHERE limit_key='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES (?,?,?)'); $st->bind_param('ssi', $row['limit_key'], $row['window_started_at'], $row['failed_attempts']); $st->execute(); }
    }
    $semua = array_column($db->query("SELECT id FROM usr_users WHERE email LIKE '{$tag}%@example.test'")->fetch_all(), 0);
    foreach ($semua as $u) {
        foreach (['account_delete', 'tulis_akun', 'account_export'] as $pol) $db->query("DELETE FROM sys_rate_limits WHERE limit_key='" . hash('sha256', "{$pol}:account:{$u}") . "'");
        foreach (array_column($db->query("SELECT id FROM kkn_magang_pendaftaran WHERE user_id=" . (int) $u)->fetch_all(), 0) as $p) {
            foreach (glob("{$uploads}/kemitraan/{$p}/*") ?: [] as $f) @unlink($f);
            @rmdir("{$uploads}/kemitraan/{$p}");
        }
        foreach (glob("{$uploads}/_pemilik/u{$u}/*") ?: [] as $f) @unlink($f);
        @rmdir("{$uploads}/_pemilik/u{$u}");
        $db->query("DELETE FROM kkn_magang_pendaftaran WHERE user_id=" . (int) $u);
        $db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=" . (int) $u);
    }
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}%@example.test'");
    foreach (array_merge($jars, $tmp) as $f) @unlink($f);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
