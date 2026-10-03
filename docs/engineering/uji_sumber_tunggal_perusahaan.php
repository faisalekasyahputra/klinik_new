<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji satu sumber data perusahaan (Fase 3 normalisasi langkah 4, migrasi 070):
 *
 *   php docs/engineering/uji_sumber_tunggal_perusahaan.php
 *
 * - usr_akun tidak lagi punya kolom perusahaan; config dan Migrate::status di 070.
 * - Daftar cepat SRP2 dan onboarding pengembang menulis nama/alamat ke pengajuan SRP2 (draft),
 *   bukan usr_akun, dan TIDAK membuat baris direktori: yang belum tersertifikasi tidak
 *   pernah tampil di direktori publik (baris status_aktif=0 juga tidak).
 * - Pengajuan diterima admin -> baris direktori lewat fungsi bersama upsert_direktori_publik()
 *   (tertaut user_id, isi sama dengan pengajuan).
 * - Profil Perusahaan (akun/perusahaan), Admin_Srp2 ubah/save, dan Buatkan akun membaca dan
 *   menulis baris direktori YANG SAMA; profil publik tidak lagi dilengkapi dari pengajuan.
 *
 * Akun @example.test, pengajuan, baris direktori, dan jejak audit miliknya dibuat dan dihapus
 * sendiri. Ember batas laju register/login dipinjam lalu dikembalikan.
 */
$AKAR = dirname(__DIR__, 2);
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path($AKAR), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$tag = 'ujistp' . bin2hex(random_bytes(3)); $TAG = strtoupper($tag);
$sandi = 'St1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$http = function ($jar, $path, $post = NULL, $ajax = FALSE) use ($BASE) {
    $c = curl_init($BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($c); $k = (int) curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); unset($c); // PHP 8: jar ditulis saat handle dilepas
    return [html_entity_decode($b, ENT_QUOTES, 'UTF-8'), $k];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$jar = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'stp'); $jars[] = $j; return $j; };
$kirim = function ($j, $path, array $isi, $ajax = FALSE) use ($http, $csrf) { return $http($j, $path, ['csrf_kpkp_token' => $csrf($j)] + $isi, $ajax); };
$daftar = function ($email, array $tambahan = []) use ($jar, $http, $kirim, $sandi) {
    $j = $jar(); $http($j, 'Auth/login');
    [$b] = $kirim($j, 'Auth/do_register', ['email' => $email, 'password' => $sandi, 'password_confirm' => $sandi, 'tos_agree' => '1'] + $tambahan, TRUE);
    if ((json_decode($b, TRUE)['status'] ?? '') === 'otp_required') { // akun baru lahir sesudah kode OTP benar
        require_once __DIR__ . '/_otp_uji.php';
        [$b] = $kirim($j, 'Auth/do_verifikasi_email', ['kode_otp' => kode_otp_uji($email)], TRUE);
    }
    return [$j, json_decode($b, TRUE)['status'] ?? ''];
};
$reg = fn($uid) => $db->query("SELECT * FROM srp2_pengajuan WHERE user_id=" . (int) $uid . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
$dir_milik = fn($uid) => $db->query("SELECT * FROM srp2_direktori_pengembang WHERE user_id=" . (int) $uid)->fetch_assoc();
$publik = function ($nama) use ($http, $jar) { [$b] = $http($jar(), 'Pengembang/sertifikasi'); return strpos($b, $nama) !== FALSE; };

$ember = [];
foreach (['register', 'login'] as $pol) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
    $k = hash('sha256', $pol . ':ip:' . $ip);
    $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
    $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
}
$ids = [];
try {
    echo "=== UJI SATU SUMBER DATA PERUSAHAAN (MIGRASI 070) ===\n";

    echo "\n-- Skema --\n";
    $cek((int) $satu("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usr_akun'
        AND COLUMN_NAME IN ('nama_perusahaan','alamat_kantor','telp_kantor')") === 0, 'usr_akun tanpa kolom nama_perusahaan, alamat_kantor, telp_kantor');
    $cek(preg_match("/migration_version'\] = (\d+);/", file_get_contents($AKAR . '/application/config/migration.php'), $vm) && $vm[1] >= '20260701000070'
        && (string) $satu('SELECT version FROM migrations') === $vm[1], 'Config dan DB di versi yang sama, paling rendah 20260701000070');
    $status = (string) shell_exec('php ' . escapeshellarg($AKAR . '/index.php') . ' migrate status 2>&1');
    $cek(strpos($status, 'sumber tunggal perusahaan (migrasi 070): TERPASANG') !== FALSE, 'Migrate::status melaporkan 070 TERPASANG');
    $kode = '';
    foreach (['controllers', 'models', 'views', 'helpers', 'libraries', 'core'] as $d) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($AKAR . '/application/' . $d)) as $f) {
            if ($f->isFile() && substr($f->getFilename(), -4) === '.php') { $kode .= file_get_contents($f->getPathname()); }
        }
    }
    $cek( ! preg_match("/'telp_kantor'\s*=>|update\('usr_akun',\s*\\\$akun\)/", $kode), 'Tidak ada kode yang menulis telp_kantor atau menyalin data perusahaan ke usr_akun');
    $profil = file_get_contents($AKAR . '/application/controllers/Pengembang.php');
    $profil = substr($profil, strpos($profil, 'function profil('), 4000);
    $cek(strpos(substr($profil, 0, strpos($profil, "show_404")), "'pengembang_id' => (int) \$id") === FALSE,
        'Profil publik tidak lagi melengkapi baris direktori dari pengajuan');

    echo "\n-- Daftar cepat SRP2 --\n";
    $namaC = "PT CEPAT {$TAG}";
    [, $st] = $daftar("{$tag}_cepat@example.test", ['srp2_pengembang' => '1', 'nama_perusahaan' => strtolower($namaC)]);
    $uidC = (int) $satu("SELECT id FROM usr_akun WHERE email='{$tag}_cepat@example.test' AND peran='pengembang'");
    $r = $reg($uidC);
    $cek($st === 'success' && $uidC > 0 && ($r['status_verifikasi'] ?? '') === 'Draft' && ($r['nama_perusahaan'] ?? '') === $namaC,
        'Daftar cepat: nama perusahaan (huruf besar) tersimpan di draft pengajuan');
    $cek( ! $dir_milik($uidC) && (int) $satu("SELECT COUNT(*) FROM srp2_direktori_pengembang WHERE nama_perusahaan='{$namaC}'") === 0,
        'Daftar cepat tidak membuat baris direktori');

    echo "\n-- Onboarding pengembang --\n";
    $namaO = "PT ONBOARD {$TAG}"; $eO = "{$tag}_onboard@example.test";
    [$jO, $st] = $daftar($eO);
    $http($jO, 'Auth/onboarding');
    $npwp = '8' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $kirim($jO, 'Auth/save_onboarding', ['role' => 'pengembang', 'username' => $tag, 'nama_lengkap' => 'Uji ' . $TAG, 'npwp' => $npwp,
        'alamat_domisili' => 'Jl. Domisili ' . $TAG, 'phone' => '081234567890', 'nama_perusahaan' => $namaO, 'alamat_kantor' => 'Jl. Kantor ' . $TAG]);
    $uidO = (int) $satu("SELECT id FROM usr_akun WHERE email='{$eO}' AND peran='pengembang' AND profil_lengkap=1");
    $r = $reg($uidO);
    $cek($st === 'success' && $uidO > 0 && ($r['nama_perusahaan'] ?? '') === $namaO && ($r['alamat_kantor'] ?? '') === 'Jl. Kantor ' . $TAG,
        'Onboarding: nama dan alamat kantor tersimpan di draft pengajuan');
    $cek( ! $dir_milik($uidO) && (int) $satu("SELECT COUNT(*) FROM srp2_direktori_pengembang WHERE nama_perusahaan='{$namaO}'") === 0,
        'Onboarding tidak membuat baris direktori (belum tersertifikasi)');
    [$hal] = $http($jO, 'akun');
    $cek(strpos($hal, $namaO) !== FALSE, 'Status Pengajuan menampilkan nama dari pengajuan selama belum tertaut');
    $cek( ! $publik($namaO) && ! $publik($namaC), 'Pengembang yang belum tersertifikasi tidak tampil di direktori publik');

    echo "\n-- Baris direktori tidak tayang --\n";
    $namaN = "PT NONAKTIF {$TAG}";
    $db->query("INSERT INTO srp2_direktori_pengembang (nama_perusahaan, status_aktif) VALUES ('{$namaN}', 0)");
    $ids[] = $idN = (int) $db->insert_id;
    [, $k404] = $http($jar(), 'Pengembang/profil/' . $idN);
    $cek( ! $publik($namaN) && $k404 === 404, 'Baris status_aktif=0 tidak tampil di daftar publik dan profilnya 404');

    echo "\n-- Pengajuan diterima -> direktori (fungsi bersama) --\n";
    $rid = (int) $reg($uidO)['id'];
    $db->query("UPDATE srp2_pengajuan SET status_verifikasi='Pending' WHERE id={$rid}");
    $eA = "{$tag}_admin@example.test"; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $db->query("INSERT INTO usr_akun (nama,email,kata_sandi,peran,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at)
        VALUES ('Admin Uji','{$eA}','{$h}','admin','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $jA = $jar(); $http($jA, 'Auth/login');
    $kirim($jA, 'Auth/do_login', ['email' => $eA, 'password' => $sandi], TRUE);
    $http($jA, 'Admin_Srp2/detail/' . $rid);
    $kirim($jA, 'Admin_Srp2/proses/' . $rid, ['status' => 'Diterima']);
    $d = $dir_milik($uidO); $r = $reg($uidO);
    if ($d) { $ids[] = (int) $d['id']; }
    $cek($d && ($r['status_verifikasi'] ?? '') === 'Diterima' && (int) $r['pengembang_id'] === (int) $d['id'],
        'Diterima: pengajuan menunjuk baris direktori yang tertaut ke akun pemohon (user_id)');
    $cek($d && $d['nama_perusahaan'] === $namaO && $d['alamat_kantor'] === 'Jl. Kantor ' . $TAG && (int) $d['status_aktif'] === 1,
        'Isi baris direktori = isian pengajuan, dan tayang');
    $cek($publik($namaO), 'Sesudah diterima, perusahaan tampil di direktori publik');
    $cid = (int) ($d['id'] ?? 0);

    echo "\n-- Profil Perusahaan dan Admin_Srp2 memegang baris yang sama --\n";
    [$hal, $k] = $http($jO, 'akun/perusahaan');
    $cek($k === 200 && strpos($hal, 'data-form-perusahaan') !== FALSE && strpos($hal, 'Jl. Kantor ' . $TAG) !== FALSE,
        'Profil Perusahaan pemohon membaca baris direktorinya');
    $kirim($jO, 'akun/perusahaan/simpan', ['alamat_kantor' => 'Jl. Pemilik ' . $TAG, 'asosiasi' => '', 'no_keanggotaan' => '', 'nib' => '',
        'no_whatsapp' => '', 'email_kontak' => '', 'website' => '', 'instagram' => '', 'sosmed_lainnya' => '']);
    [$hal] = $http($jA, 'Admin_Srp2/ubah/' . $cid);
    $cek($dir_milik($uidO)['alamat_kantor'] === 'Jl. Pemilik ' . $TAG && strpos($hal, 'Jl. Pemilik ' . $TAG) !== FALSE
        && $reg($uidO)['alamat_kantor'] === 'Jl. Pemilik ' . $TAG,
        'Ubahan pemilik tersimpan di baris direktori, terbaca di Admin_Srp2/ubah, pengajuan ikut disinkron');
    $kirim($jA, 'Admin_Srp2/save', ['id' => $cid, 'nama_perusahaan' => $namaO, 'alamat_kantor' => 'Jl. Dinas ' . $TAG, 'kabupaten_id' => 0, 'asosiasi' => '',
        'no_keanggotaan' => '', 'nib' => '', 'npwp' => '', 'no_whatsapp' => '', 'email_kontak' => '', 'website' => '', 'instagram' => '', 'sosmed_lainnya' => '',
        'status_sertifikasi' => 'bersertifikat', 'sertifikat_terbit' => '', 'sertifikat_berakhir' => date('Y-m-d', strtotime('+1 year')), 'status_aktif' => 1]);
    [$hal] = $http($jO, 'akun/perusahaan');
    $cek($dir_milik($uidO)['alamat_kantor'] === 'Jl. Dinas ' . $TAG && strpos($hal, 'Jl. Dinas ' . $TAG) !== FALSE,
        'Ubahan admin di Admin_Srp2 terbaca di Profil Perusahaan pemohon');
    [$hal] = $http($jar(), 'Pengembang/profil/' . $cid);
    $cek(strpos($hal, 'Jl. Dinas ' . $TAG) !== FALSE, 'Profil publik menampilkan isi baris direktori');
    [$hal] = $http($jO, 'akun');
    $cek(strpos($hal, $namaO) !== FALSE, 'Status Pengajuan akun tertaut menampilkan nama dari baris direktori');

    echo "\n-- Buatkan akun --\n";
    $namaB = "CV BUATAN {$TAG}";
    $db->query("INSERT INTO srp2_direktori_pengembang (nama_perusahaan, alamat_kantor, status_aktif) VALUES ('{$namaB}', 'Jl. Buatan {$TAG}', 1)");
    $ids[] = $idB = (int) $db->insert_id;
    $eB = "{$tag}_buatan@example.test";
    $kirim($jA, 'Admin_Srp2/buat_akun/' . $idB, ['email' => $eB, 'nama_pj' => 'PJ ' . $TAG, 'no_whatsapp' => '081234567890', 'password' => '']);
    $uidB = (int) $satu("SELECT id FROM usr_akun WHERE email='{$eB}' AND peran='pengembang'");
    $rB = $reg($uidB); $dB = $dir_milik($uidB);
    $cek($uidB > 0 && $dB && (int) $dB['id'] === $idB && (int) ($rB['pengembang_id'] ?? 0) === $idB,
        'Buatkan akun: akun baru memegang baris direktori itu sendiri (bukan salinan)');
    [$hal] = $http($jA, 'Admin_Srp2/ubah/' . $idB);
    $cek(strpos($hal, $eB) !== FALSE && strpos($hal, 'Jl. Buatan ' . $TAG) !== FALSE, 'Admin_Srp2/ubah menampilkan akun tertaut dan isi baris yang sama');
} catch (Throwable $e) {
    $cek(FALSE, 'Galat tak terduga: ' . $e->getMessage() . ' (baris ' . $e->getLine() . ')');
} finally {
    $uids = array_map(fn($r) => (int) $r[0], $db->query("SELECT id FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'")->fetch_all());
    $rids = [];
    foreach ($uids as $id) {
        foreach ($db->query("SELECT id FROM srp2_pengajuan WHERE user_id={$id}")->fetch_all() as [$r]) {
            $rids[] = "'" . (int) $r . "'";
            $db->query("DELETE FROM srp2_dokumen WHERE pengajuan_id={$r}");
            $db->query("DELETE FROM srp2_pengajuan WHERE id={$r}");
        }
        $db->query("DELETE FROM usr_dokumen WHERE user_id={$id}");
    }
    $daftar_id = implode(',', array_map('intval', $ids ?: [0]));
    $uid_txt = implode(',', array_map(fn($i) => "'{$i}'", $uids ?: [0]));
    $db->query("DELETE FROM sys_jejak_audit WHERE (objek_tipe='srp2_direktori_pengembang' AND objek_id IN ({$daftar_id}))
        OR (objek_tipe='usr_akun' AND objek_id IN ({$uid_txt}))
        OR (objek_tipe='srp2_pengajuan' AND objek_id IN (" . (implode(',', $rids) ?: "'0'") . ")) OR pelaku_email LIKE '{$tag}\\_%@example.test'");
    $db->query("DELETE FROM srp2_direktori_pengembang WHERE id IN ({$daftar_id}) OR nama_perusahaan LIKE '%{$TAG}%'");
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'");
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
    foreach ($jars as $f) { @unlink($f); }
    $sisa = (int) $satu("SELECT COUNT(*) FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'")
        + (int) $satu("SELECT COUNT(*) FROM srp2_direktori_pengembang WHERE nama_perusahaan LIKE '%{$TAG}%'");
    $cek($sisa === 0, 'Data uji (akun, pengajuan, baris direktori) dibersihkan');
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
