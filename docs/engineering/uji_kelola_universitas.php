<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji keputusan pemilik produk 29 Sep 2026 untuk akun universitas dan KKN.
 *
 *   php docs/engineering/uji_kelola_universitas.php
 *
 * Butir 1: admin bidang menyunting, mereset sandi, menonaktifkan dan mengaktifkan kembali akun
 *          universitas (hanya role universitas; peran lain 404).
 * Butir 2: sandi dari admin (akun baru, reset) wajib diganti di login pertama.
 * Butir 3: pengajuan KKN yang seluruh periodenya lewat ditolak; laporan akhir hanya untuk Diterima.
 * Butir 4: roster peserta terkunci begitu tanggal sertifikat ditetapkan, terbuka lagi bila ditarik.
 * Butir 5: nomor HP di profil divalidasi; verifikasi sandi di profil dibatasi lajunya.
 *
 * Semua akun @example.test, KKN, peserta, dan berkas dibuat dan dihapus sendiri. Ember batas laju
 * per IP yang disentuh dipinjam lalu dikembalikan persis (jejak audit dibiarkan).
 */
$AKAR = dirname(__DIR__, 2);
require $AKAR . '/vendor/autoload.php';
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path($AKAR), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujikelola' . bin2hex(random_bytes(3));
$sandi = 'Uk1#' . bin2hex(random_bytes(5));
$sandi2 = 'Uk2#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $tmp = []; $uid = [];
$uploads = strtr(trim($env['PRIVATE_UPLOADS_PATH'] ?? ''), [chr(92) => '/']);
$uploads = $uploads === '' ? dirname($AKAR) . '/private_uploads' : rtrim(preg_match('#^([A-Za-z]:/|/)#', $uploads) ? $uploads : $AKAR . '/' . ltrim($uploads, '/'), '/');

$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$nilai = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$baris = function ($id) use ($db) { return $db->query('SELECT * FROM usr_akun WHERE id=' . (int) $id)->fetch_assoc(); };
$akun = function ($role, $bidang = NULL, $telp = '081234567890') use ($db, $tag, $sandi, &$uid) {
    $e = "{$tag}_{$role}" . count($uid) . '@example.test'; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_akun (nama,email,kata_sandi,peran,bidang_kode,no_hp,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at) VALUES (?,?,?,?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $nama = "Uji {$role} {$tag}";
    $st->bind_param('ssssss', $nama, $e, $h, $role, $bidang, $telp); $st->execute();
    $uid[] = $db->insert_id; return [$db->insert_id, $e];
};
/** @return array [kode, body, url_akhir] - body sudah di-decode entitasnya. */
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $berkas = array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $berkas ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch); $kode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch); unset($ch);
    return [$kode, html_entity_decode($b, ENT_QUOTES, 'UTF-8'), $url];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'ukl'); $jars[] = $j; return $j; };
$login = function ($email, $pw = NULL) use ($http, $csrf, $sesi, $sandi) {
    $j = $sesi(); $http($j, 'Auth/login');
    $r = $http($j, 'Auth/do_login', ['email' => $email, 'password' => $pw ?? $sandi, 'csrf_kpkp_token' => $csrf($j)]);
    return [$j, $r];
};
$kirim = function ($jar, $path, array $isi) use ($http, $csrf) { return $http($jar, $path, ['csrf_kpkp_token' => $csrf($jar)] + $isi); };
$pdf = function () use (&$tmp) { $f = tempnam(sys_get_temp_dir(), 'uklp') . '.pdf'; $tmp[] = $f; file_put_contents($f, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n"); return new CURLFile($f, 'application/pdf', 'surat.pdf'); };
$xlsx = function (array $baris) use (&$tmp) {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $book->getActiveSheet()->fromArray([['NIM', 'Nama']], NULL, 'A1');
    foreach ($baris as $i => $r) {
        $book->getActiveSheet()->setCellValueExplicit('A' . ($i + 2), $r[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $book->getActiveSheet()->setCellValue('B' . ($i + 2), $r[1]);
    }
    $f = tempnam(sys_get_temp_dir(), 'uklx') . '.xlsx'; $tmp[] = $f;
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
$jejak = fn($aksi, $id) => (int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='{$aksi}' AND objek_tipe='usr_akun' AND objek_id='" . (int) $id . "'");
$kedaluwarsa = fn($id) => (int) $nilai("SELECT sandi_kedaluwarsa_at <= NOW() FROM usr_akun WHERE id=" . (int) $id) === 1;
// Masih login? akun/profil terbuka untuk setiap peran, juga saat sandi wajib diganti.
$masuk = fn($jar) => strpos($http($jar, 'akun/profil')[2], 'Auth/login') === FALSE;
$ember_ip = function ($pol) { $k = []; foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) $k[] = hash('sha256', $pol . ':ip:' . $ip); return $k; };

$ember = [];
try {
    echo "=== UJI KELOLA UNIVERSITAS (keputusan 29 Sep 2026) ===\n";
    foreach (['login', 'profile_password'] as $pol) foreach ($ember_ip($pol) as $k) {
        $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
    }
    [$idAdm, $eAdm] = $akun('admin');
    [$idBid, $eBid] = $akun('admin_bidang', 'kawasan');
    [$idU, $eU] = $akun('universitas');
    [$idU2, $eU2] = $akun('universitas');
    [$idU3, $eU3] = $akun('universitas');
    [$idU4, $eU4] = $akun('universitas');
    [$idW, $eW] = $akun('warga');
    [$idP, $eP] = $akun('pengembang');
    [$idM, $eM] = $akun('mahasiswa');
    [$idAdm2] = $akun('admin');
    [$idBid2] = $akun('admin_bidang', 'kawasan');
    [$jAdm] = $login($eAdm); [$jBid] = $login($eBid);
    $http($jBid, 'Kemitraan_Bidang/universitas');

    // === BUTIR 1: pengelolaan akun universitas oleh admin bidang ===
    echo "\n-- Butir 1: admin bidang mengelola akun universitas --\n";
    [, $hal] = $http($jBid, 'Kemitraan_Bidang/universitas');
    $cek(strpos($hal, 'Kemitraan_Bidang/ubah_universitas') !== FALSE && strpos($hal, 'Kemitraan_Bidang/sandi_universitas') !== FALSE
        && strpos($hal, 'Kemitraan_Bidang/status_universitas') !== FALSE && strpos($hal, 'dilakukan oleh superadmin') === FALSE,
        'Halaman admin bidang memuat formulir sunting, reset sandi, dan status; teks "dilakukan oleh superadmin" hilang');

    $eUbah = "{$tag}_ubah@example.test";
    [$k] = $kirim($jBid, 'Kemitraan_Bidang/ubah_universitas', ['id' => $idU, 'name' => "Univ Baru {$tag}", 'email' => $eUbah, 'phone' => '0812 3456 7890', 'role' => 'admin']);
    $u = $baris($idU);
    $cek($u['nama'] === "Univ Baru {$tag}" && $u['email'] === $eUbah && $u['no_hp'] === '0812 3456 7890' && $u['peran'] === 'universitas',
        'Sunting nama, email, HP tersimpan; role=admin yang disisipkan diabaikan (role tetap universitas)');
    $cek($jejak('universitas_diubah', $idU) === 1, 'Sunting tercatat di jejak audit dengan objek usr_akun id');
    $eU = $eUbah;

    [, $hal] = $kirim($jBid, 'Kemitraan_Bidang/ubah_universitas', ['id' => $idU, 'name' => 'X', 'email' => strtoupper($eU2), 'phone' => '']);
    $cek($baris($idU)['email'] === $eU && strpos($hal, 'email tersebut sudah terdaftar') !== FALSE, 'Sunting ke email milik akun lain ditolak dengan pesan jelas');
    [, $hal] = $kirim($jBid, 'Kemitraan_Bidang/ubah_universitas', ['id' => $idU, 'name' => 'X', 'email' => $eU, 'phone' => 'bukan-nomor-xx']);
    $cek($baris($idU)['no_hp'] === '0812 3456 7890' && strpos($hal, 'Nomor HP hanya boleh berisi angka') !== FALSE, 'Sunting dengan HP sampah ditolak, data tidak berubah');

    $hash = $baris($idU)['kata_sandi'];
    [, $hal] = $kirim($jBid, 'Kemitraan_Bidang/sandi_universitas', ['id' => $idU, 'password' => 'abcd1234']);
    $cek($baris($idU)['kata_sandi'] === $hash && strpos($hal, 'harus minimal 8 karakter, mengandung huruf besar') !== FALSE, 'Reset sandi lemah ditolak dengan aturan sandi_kuat');

    [$jU] = $login($eU);
    $hidup = $masuk($jU);
    $kirim($jBid, 'Kemitraan_Bidang/sandi_universitas', ['id' => $idU, 'password' => $sandi2]);
    $u = $baris($idU);
    $cek(password_verify($sandi2, $u['kata_sandi']) && $u['sesi_aktif_hash'] === NULL && $u['sesi_aktif_id_hash'] === NULL && $kedaluwarsa($idU),
        'Reset sandi sah: sandi baru berlaku, sesi lama dicabut, sandi wajib diganti');
    $cek($jejak('universitas_sandi_direset', $idU) === 1 && (int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE objek_id='{$idU}' AND detail_json LIKE '%{$sandi2}%'") === 0,
        'Reset sandi tercatat di audit tanpa isi sandinya');
    $cek($hidup && ! $masuk($jU), 'Sesi universitas yang lama berakhir sesudah reset sandi');

    [$jU] = $login($eU, $sandi2);
    $hidup = $masuk($jU);
    $kirim($jBid, 'Kemitraan_Bidang/status_universitas', ['id' => $idU, 'status' => 'nonaktif']);
    $u = $baris($idU);
    $cek($u['status'] === 'nonaktif' && $u['sesi_aktif_hash'] === NULL && $jejak('universitas_dinonaktifkan', $idU) === 1,
        'Nonaktifkan: status nonaktif, sesi dikosongkan, tercatat di audit');
    $cek($hidup && ! $masuk($jU), 'Sesi akun yang dinonaktifkan langsung berakhir');
    [, $r] = $login($eU, $sandi2);
    $cek(strpos($r[2], 'Auth/login') !== FALSE && strpos($r[1], 'dinonaktifkan') !== FALSE, 'Akun nonaktif tidak bisa login');
    $kirim($jBid, 'Kemitraan_Bidang/status_universitas', ['id' => $idU, 'status' => 'active']);
    [, $r] = $login($eU, $sandi2);
    $cek($baris($idU)['status'] === 'active' && strpos($r[2], 'Auth/login') === FALSE && $jejak('universitas_diaktifkan', $idU) === 1,
        'Aktifkan kembali: akun bisa login lagi, tercatat di audit');

    // Batas peran: id akun peran lain (disisipkan lewat formulir) -> 404 dan tidak ada yang berubah.
    $sasaran = ['warga' => $idW, 'pengembang' => $idP, 'mahasiswa' => $idM, 'admin' => $idAdm2, 'admin_bidang' => $idBid2, 'diri sendiri' => $idBid, 'tidak ada' => 99999999];
    foreach ($sasaran as $peran => $id) {
        $sebelum = $baris($id);
        $kode = [];
        $kode[] = $kirim($jBid, 'Kemitraan_Bidang/ubah_universitas', ['id' => $id, 'name' => 'Diretas', 'email' => "{$tag}_retas{$id}@example.test", 'phone' => ''])[0];
        $kode[] = $kirim($jBid, 'Kemitraan_Bidang/sandi_universitas', ['id' => $id, 'password' => $sandi2])[0];
        $kode[] = $kirim($jBid, 'Kemitraan_Bidang/status_universitas', ['id' => $id, 'status' => 'nonaktif'])[0];
        $cek($kode === [404, 404, 404] && $baris($id) == $sebelum, "Akun {$peran}: ketiga aksi 404 dan baris tidak berubah (" . implode(',', $kode) . ')');
    }
    $cek($http($jBid, 'Kemitraan_Bidang/status_universitas?id=' . $idU2 . '&status=nonaktif')[0] === 404 && $baris($idU2)['status'] === 'active', 'Aksi lewat GET ditolak 404');
    $http($jBid, 'Kemitraan_Bidang/status_universitas', ['id' => $idU2, 'status' => 'nonaktif']);
    $cek($baris($idU2)['status'] === 'active', 'Aksi tanpa token CSRF tidak mengubah apa pun');

    // === BUTIR 2: sandi dari admin wajib diganti di login pertama ===
    echo "\n-- Butir 2: paksa ganti sandi di login pertama --\n";
    $eBaru = "{$tag}_baru@example.test";
    $kirim($jBid, 'Kemitraan_Bidang/buat_universitas', ['name' => 'Univ Baru', 'email' => $eBaru, 'phone' => '081234567890', 'password' => $sandi]);
    $idBaru = (int) $nilai("SELECT id FROM usr_akun WHERE email='{$eBaru}'");
    [$jBaru, $r] = $login($eBaru);
    $cek($idBaru > 0 && strpos($r[2], 'akun/profil?password_expired=1') !== FALSE && stripos($r[1], 'sandi awal dari admin') !== FALSE,
        'Akun buatan admin bidang: login pertama diarahkan ke ganti sandi dengan pesan sandi awal dari admin');
    [, , $akhir] = $http($jBaru, 'KemitraanPortal/kkn_dashboard');
    $kirim($jBaru, 'akun/update', ['name' => 'Univ Baru', 'phone' => '081234567890', 'password' => $sandi2, 'password_confirm' => $sandi2, 'current_password' => $sandi]);
    [$k, , $akhir2] = $http($jBaru, 'KemitraanPortal/kkn_dashboard');
    $cek(strpos($akhir, 'password_expired=1') !== FALSE && (int) $nilai("SELECT sandi_kedaluwarsa_at > DATE_ADD(NOW(), INTERVAL 89 DAY) FROM usr_akun WHERE id={$idBaru}") === 1
        && $k === 200 && strpos($akhir2, 'kkn_dashboard') !== FALSE,
        'Sebelum ganti sandi dashboard tertahan; sesudahnya masa berlaku 90 hari dan dashboard terbuka');
    $eStaf = "{$tag}_staf@example.test";
    $http($jAdm, 'Admin_Users');
    $kirim($jAdm, 'Admin_Users/create_staff', ['name' => 'Univ Staf', 'email' => $eStaf, 'phone' => '', 'password' => $sandi, 'role' => 'universitas']);
    $idStaf = (int) $nilai("SELECT id FROM usr_akun WHERE email='{$eStaf}'");
    $cek($idStaf > 0 && $kedaluwarsa($idStaf), 'Akun buatan superadmin (create_staff) wajib ganti sandi di login pertama');
    $kirim($jAdm, 'Admin_Users/reset_sandi', ['id' => $idU2, 'password' => $sandi2]);
    $cek(password_verify($sandi2, $baris($idU2)['kata_sandi']) && $kedaluwarsa($idU2), 'Sesudah reset sandi oleh superadmin, sandi wajib diganti di login berikutnya');

    // === BUTIR 3: periode lampau dan laporan sebelum Diterima ===
    echo "\n-- Butir 3: periode KKN lampau, laporan hanya untuk Diterima --\n";
    // Akun tersendiri supaya butir ini tidak bergantung pada hasil butir 1 dan 2.
    [$jU] = $login($eU3);
    $idU = $idU3;
    $jml = fn() => (int) $nilai("SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE user_id={$idU}");
    $tambah = fn($mulai, $selesai, $ket, $alasan = NULL) => $kirim($jU, 'KemitraanPortal/kkn_tambah', ['periode_mulai' => $mulai, 'periode_selesai' => $selesai, 'keterangan' => $ket, 'file_surat_pengantar' => $pdf(), 'file_surat_simperum' => $pdf()] + ($alasan !== NULL ? ['alasan_susulan' => $alasan] : []));
    // Input susulan (keputusan user 7 Okt 2026, menggantikan penolakan 29 Sep): periode lewat boleh dengan alasan.
    $awal = $jml();
    [, $hal] = $tambah(date('Y-m-d', strtotime('-40 days')), date('Y-m-d', strtotime('-1 day')), "KKN lampau {$tag}");
    $cek($jml() === $awal && stripos($hal, 'input susulan') !== FALSE && stripos($hal, 'Periksa isian KKN') !== FALSE,
        'KKN berperiode lewat tanpa alasan ditolak dengan pemberitahuan "Periksa isian KKN" yang meminta alasan susulan');
    $tambah(date('Y-m-d', strtotime('-40 days')), date('Y-m-d', strtotime('-1 day')), "KKN lampau pendek {$tag}", 'terlalu pendek');
    $cek($jml() === $awal, 'Alasan susulan kurang dari 20 karakter ditolak');
    $tambah(date('Y-m-d', strtotime('-430 days')), date('Y-m-d', strtotime('-400 days')), "KKN terlalu lama {$tag}", 'KKN lama sekali, surat baru ditemukan di arsip kampus.');
    $cek($jml() === $awal, 'Input susulan lebih dari setahun ke belakang ditolak');
    $tambah(date('Y-m-d', strtotime('-40 days')), date('Y-m-d', strtotime('-1 day')), "KKN susulan {$tag}", 'KKN sudah berjalan, surat dari kampus baru terbit minggu ini.');
    $cek($jml() === $awal + 1 && $nilai("SELECT alasan_susulan FROM kkn_magang_pendaftaran WHERE user_id={$idU} AND divisi_atau_tema='KKN susulan {$tag}'") === 'KKN sudah berjalan, surat dari kampus baru terbit minggu ini.',
        'KKN berperiode lewat dengan alasan diterima sebagai susulan, alasannya tersimpan');
    // KKN lama yang belum tercatat (aplikasi selesai sesudah periodenya): surat opsional, alasan tetap wajib.
    $kirim($jU, 'KemitraanPortal/kkn_tambah', ['periode_mulai' => date('Y-m-d', strtotime('-90 days')), 'periode_selesai' => date('Y-m-d', strtotime('-60 days')),
        'keterangan' => "KKN susulan tanpa surat {$tag}", 'alasan_susulan' => 'Pencatatan susulan: KKN berlangsung sebelum aplikasi tersedia.']);
    $cek($jml() === $awal + 2 && $nilai("SELECT CONCAT(COALESCE(file_surat_pengantar,'-'), COALESCE(file_surat_simperum,'-'), status) FROM kkn_magang_pendaftaran WHERE user_id={$idU} AND divisi_atau_tema='KKN susulan tanpa surat {$tag}'") === '--Diajukan',
        'KKN susulan tanpa kedua surat diterima sebagai Diajukan dengan kolom surat kosong');
    $kirim($jU, 'KemitraanPortal/kkn_tambah', ['periode_mulai' => date('Y-m-d', strtotime('+10 days')), 'periode_selesai' => date('Y-m-d', strtotime('+40 days')),
        'keterangan' => "KKN biasa tanpa surat {$tag}"]);
    $cek($jml() === $awal + 2, 'KKN biasa (belum lewat) tanpa surat tetap ditolak');
    $awal += 1;
    $tambah(date('Y-m-d', strtotime('-10 days')), date('Y-m-d'), "KKN berakhir hari ini {$tag}");
    $cek($jml() === $awal + 2 && $nilai("SELECT alasan_susulan FROM kkn_magang_pendaftaran WHERE user_id={$idU} AND divisi_atau_tema='KKN berakhir hari ini {$tag}'") === NULL,
        'KKN yang berakhir hari ini diterima tanpa alasan, bukan susulan');

    $diajukan = $kkn($idU, '2026-01-01', '2026-02-01', 'Diajukan');
    [, $hal] = $kirim($jU, 'KemitraanPortal/kkn_upload_laporan/' . $diajukan, ['file_laporan' => $pdf()]);
    $cek($nilai("SELECT file_laporan_akhir FROM kkn_magang_pendaftaran WHERE id={$diajukan}") === NULL && ! is_dir("{$uploads}/kemitraan/{$diajukan}") && stripos($hal, 'diterima') !== FALSE,
        'Laporan akhir KKN yang belum Diterima ditolak (tidak ada berkas mendarat)');
    $diterima = $kkn($idU, '2026-01-01', '2026-02-01', 'Diterima');
    [, $halA] = $http($jU, 'KemitraanPortal/pendaftaran/' . $diajukan);
    [, $halB] = $http($jU, 'KemitraanPortal/pendaftaran/' . $diterima);
    $cek(strpos($halA, 'kkn_upload_laporan/') === FALSE && strpos($halB, 'kkn_upload_laporan/') !== FALSE,
        'Formulir laporan tidak ditawarkan sebelum Diterima, ditawarkan sesudah Diterima dan periode lewat');

    // === BUTIR 4: roster terkunci setelah tanggal sertifikat ditetapkan ===
    echo "\n-- Butir 4: roster terkunci oleh tanggal sertifikat --\n";
    $ros = $kkn($idU, '2026-01-01', '2026-02-01', 'Diterima');
    $roster($jU, $ros, [['K4A001', 'Awal']]);
    $http($jAdm, 'Admin_Kemitraan');
    $kirim($jAdm, 'Admin_Kemitraan/tanggal_sertifikat/' . $ros, ['tanggal_sertifikat' => '2026-03-01']);
    [, $hal] = $roster($jU, $ros, [['K4B001', 'Pengganti']]);
    $nims = array_column($db->query("SELECT nim FROM kkn_peserta WHERE pendaftaran_id={$ros}")->fetch_all(), 0);
    $cek($nims === ['K4A001'] && stripos($hal, 'hubungi admin') !== FALSE, 'Roster ditolak dengan pesan "hubungi admin" sesudah tanggal sertifikat ditetapkan');
    [, $hal] = $http($jU, 'KemitraanPortal/pendaftaran/' . $ros);
    $cek(strpos($hal, 'kkn_upload_peserta/') === FALSE && strpos($hal, 'kkn_upload_laporan/') !== FALSE, 'Formulir roster disembunyikan (formulir laporan tetap ada)');
    $kirim($jAdm, 'Admin_Kemitraan/tanggal_sertifikat/' . $ros, ['tanggal_sertifikat' => '']);
    $roster($jU, $ros, [['K4C001', 'Sesudah Ditarik']]);
    [, $hal] = $http($jU, 'KemitraanPortal/pendaftaran/' . $ros);
    $nims = array_column($db->query("SELECT nim FROM kkn_peserta WHERE pendaftaran_id={$ros}")->fetch_all(), 0);
    $cek($nims === ['K4C001'] && strpos($hal, 'kkn_upload_peserta/') !== FALSE, 'Sesudah tanggal ditarik roster terbuka lagi dan formulirnya tampil');

    // === BUTIR 5: HP dan batas laju sandi di profil ===
    echo "\n-- Butir 5: validasi HP dan batas laju sandi di profil --\n";
    [$jP] = $login($eU4);
    $idU2 = $idU4;
    [, $hal] = $kirim($jP, 'akun/update', ['name' => 'Univ Dua', 'phone' => 'bukan-nomor-xx']);
    $sampah = $baris($idU2)['no_hp'] === '081234567890' && strpos($hal, 'Nomor HP hanya boleh berisi angka') !== FALSE;
    $kirim($jP, 'akun/update', ['name' => 'Univ Dua', 'phone' => str_repeat('9', 25)]);
    $panjang = $baris($idU2)['no_hp'] === '081234567890';
    $kirim($jP, 'akun/update', ['name' => 'Univ Dua', 'phone' => '+62 812-3456-7899']);
    $cek($sampah && $panjang && $baris($idU2)['no_hp'] === '+62 812-3456-7899', 'HP sampah dan HP lebih dari 20 karakter di profil ditolak (tidak dipotong); HP sah tersimpan');
    $kode = [];
    for ($i = 0; $i < 6; $i++) {
        $kode[] = $kirim($jP, 'akun/update', ['name' => 'Univ Dua', 'phone' => '081234567890', 'password' => $sandi2, 'password_confirm' => $sandi2, 'current_password' => 'SalahSandi#' . $i])[0];
    }
    $cek(array_slice($kode, 0, 5) === [200, 200, 200, 200, 200] && $kode[5] === 429 && password_verify($sandi, $baris($idU2)['kata_sandi']),
        'Percobaan sandi salah ke-6 di profil dijawab 429 (' . implode(',', $kode) . ')');
} finally {
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
    $semua = array_column($db->query("SELECT id FROM usr_akun WHERE email LIKE '{$tag}%@example.test'")->fetch_all(), 0);
    foreach ($semua as $u) {
        foreach (['profile_password', 'account_delete', 'tulis_akun', 'account_export'] as $pol) $db->query("DELETE FROM sys_batas_laju WHERE kunci='" . hash('sha256', "{$pol}:account:{$u}") . "'");
        foreach (array_column($db->query("SELECT id FROM kkn_magang_pendaftaran WHERE user_id=" . (int) $u)->fetch_all(), 0) as $p) {
            foreach (glob("{$uploads}/kemitraan/{$p}/*") ?: [] as $f) @unlink($f);
            @rmdir("{$uploads}/kemitraan/{$p}");
        }
        foreach (glob("{$uploads}/_pemilik/u{$u}/*") ?: [] as $f) @unlink($f);
        @rmdir("{$uploads}/_pemilik/u{$u}");
        $db->query("DELETE FROM kkn_magang_pendaftaran WHERE user_id=" . (int) $u);
        $db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=" . (int) $u);
    }
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}%@example.test'");
    foreach (array_merge($jars, $tmp) as $f) @unlink($f);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
