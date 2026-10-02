<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji Direktori SRP2 bertaut akun (keputusan pemilik produk 2 Okt 2026, migrasi 066):
 *
 *   php docs/engineering/uji_srp2_direktori_akun.php
 *
 * Daftar ringkas + halaman ubah admin, foto/logo (valid/tidak valid, logo tiruan bila
 * kosong), "Buatkan akun" (sandi awal wajib diganti, onboarding sudah lengkap), email
 * ganda dan tautan kedua ditolak, Profil Perusahaan pengembang (tampil di publik, anti-
 * IDOR, gerbang ganti sandi), reset sandi, lepas tautan, tautan otomatis saat pengajuan
 * diterima, dan hapus entri membuang berkas fotonya.
 *
 * Akun @example.test, entri direktori bertanda unik, berkas foto, dan jejak audit
 * miliknya dibuat dan dihapus sendiri. Ember batas laju login dipinjam lalu dikembalikan.
 */
$AKAR = dirname(__DIR__, 2);
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file($AKAR . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujisda' . bin2hex(random_bytes(3)); $TAG = strtoupper($tag);
$sandi = 'Sd1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $tmp = [];

$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$nilai = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$entri = fn($id) => $db->query('SELECT * FROM srp2_direktori_pengembang WHERE id=' . (int) $id)->fetch_assoc();
/** @return array [kode, body ter-decode, url_akhir] */
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $berkas = array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $berkas ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch); $kode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch); unset($ch); // PHP 8: jar ditulis saat handle dilepas
    return [$kode, html_entity_decode($b, ENT_QUOTES, 'UTF-8'), $url];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'usda'); $jars[] = $j; return $j; };
$login = function ($email, $pw) use ($http, $csrf, $sesi) {
    $j = $sesi(); $http($j, 'Auth/login');
    $http($j, 'Auth/do_login', ['email' => $email, 'password' => $pw, 'csrf_kpkp_token' => $csrf($j)]);
    return $j;
};
$kirim = function ($jar, $path, array $isi) use ($http, $csrf) { return $http($jar, $path, ['csrf_kpkp_token' => $csrf($jar)] + $isi); };
$masuk = fn($jar) => strpos($http($jar, 'akun/profil')[2], 'Auth/login') === FALSE;
$akun_db = function ($role) use ($db, $tag, $sandi) {
    static $n = 0; $e = "{$tag}_{$role}" . (++$n) . '@example.test'; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $db->query("INSERT INTO usr_akun (nama,nama_pengguna,email,kata_sandi,peran,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at)
        VALUES ('Uji {$role}','{$tag}{$n}','{$e}','{$h}','{$role}','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    return [(int) $db->insert_id, $e];
};
/* Gambar uji dibuat dengan GD hanya sebagai PNG: PHP CLI runner (Herd) punya GD tanpa JPEG.
   Metadata disisipkan sebagai potongan tEXt tepat sesudah IHDR, seperti teks kamera/editor.
   JPEG & GIF memakai berkas mini tetap (base64) supaya uji tidak bergantung pada GD CLI. */
$gambar = function ($w = 600, $h = 400, $teks = '') use (&$tmp) {
    $f = tempnam(sys_get_temp_dir(), 'usdg') . '.png'; $tmp[] = $f;
    $im = imagecreatetruecolor($w, $h); imagefill($im, 0, 0, imagecolorallocate($im, 20, 120, 160));
    imagepng($im, $f); imagedestroy($im);
    if ($teks !== '') {
        $isi = file_get_contents($f); $data = "Comment\0" . $teks;
        $chunk = pack('N', strlen($data)) . 'tEXt' . $data . pack('N', crc32('tEXt' . $data));
        file_put_contents($f, substr($isi, 0, 33) . $chunk . substr($isi, 33)); // 8 tanda + 25 IHDR
    }
    return $f;
};
$tetap = function ($ext, $b64) use (&$tmp) { $f = tempnam(sys_get_temp_dir(), 'usdf') . '.' . $ext; $tmp[] = $f; file_put_contents($f, base64_decode($b64)); return $f; };
$JPG = '/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gODAK/9sAQwAGBAUGBQQGBgUGBwcGCAoQCgoJCQoUDg8MEBcUGBgXFBYWGh0lHxobIxwWFiAsICMmJykqKRkfLTAtKDAlKCko/9sAQwEHBwcKCAoTCgoTKBoWGigoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgo/8AAEQgAEAAQAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8AyKKKK+RP0g//2Q==';
$GIF = 'R0lGODdhBAAEAIAAAAQCBAAAACwAAAAABAAEAAACBISPCQUAOw==';
$berkas = function ($path, $mime, $nama) { return new CURLFile($path, $mime, $nama); };
$jejak = fn($aksi, $tipe, $id) => (int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='{$aksi}' AND objek_tipe='{$tipe}' AND objek_id='" . (int) $id . "'");
$sandi_flash = fn($body) => preg_match('#data-sandi-awal>.*?font-mono[^>]*>\s*([^<\s]+)\s*<#s', $body, $m) ? $m[1] : '';

$ember = [];
foreach (['login', 'profile_password'] as $pol) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
    $k = hash('sha256', $pol . ':ip:' . $ip);
    $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
    $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
}
$ids = []; $foto_awal = [];
try {
    echo "=== UJI DIREKTORI SRP2 BERTAUT AKUN ===\n";
    $cek((int) $nilai("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE()
        AND TABLE_NAME='srp2_direktori_pengembang' AND CONSTRAINT_NAME IN ('fk_srp2_direktori_user','uq_srp2_direktori_user')") === 2,
        'PRASYARAT: migrasi 066 terpasang (UNIQUE + FK user_id)');

    $namaA = "PT. YURIS PRATAMA {$TAG}"; $namaB = "CV. {$TAG} BETA SENTOSA";
    $db->query("INSERT INTO srp2_direktori_pengembang (nama_perusahaan, alamat_kantor, status_aktif, status_sertifikasi, sertifikat_berakhir)
        VALUES ('{$namaA}', 'Jl. Awal {$TAG}', 1, 'bersertifikat', DATE_ADD(CURDATE(), INTERVAL 1 YEAR)), ('{$namaB}', 'Jl. Beta {$TAG}', 1, 'bersertifikat', NULL)");
    $idA = (int) $nilai("SELECT id FROM srp2_direktori_pengembang WHERE nama_perusahaan='{$namaA}'");
    $idB = (int) $nilai("SELECT id FROM srp2_direktori_pengembang WHERE nama_perusahaan='{$namaB}'");
    $ids = [$idA, $idB];

    [$idAdm, $eAdm] = $akun_db('admin');
    $jAdm = $login($eAdm, $sandi);
    $jTamu = $sesi();

    echo "\n-- Daftar ringkas & logo tiruan --\n";
    [$k, $daftar] = $http($jAdm, 'Admin_Srp2?q=' . urlencode($TAG));
    $cek($k === 200 && strpos($daftar, 'data-direktori-ringkas') !== FALSE, 'PRASYARAT: daftar Direktori SRP2 terbuka (200, tabel ringkas)');
    $cek(strpos($daftar, 'name="nama_perusahaan"') === FALSE && strpos($daftar, 'Admin_Srp2/save') === FALSE,
        'Daftar tidak lagi berisi formulir sunting per baris');
    $cek(strpos($daftar, 'Admin_Srp2/ubah/' . $idA) !== FALSE && strpos($daftar, 'Belum ada akun') !== FALSE
        && strpos($daftar, 'Jl. Awal ' . $TAG) !== FALSE && strpos($daftar, 'Tidak berlaku') !== FALSE && strpos($daftar, '>Berlaku<') !== FALSE,
        'Baris memuat alamat singkat, status Berlaku/Tidak berlaku, akun, dan tombol Ubah ke halaman detail');
    $cek(strpos($daftar, 'data-logo-mock="YP"') !== FALSE && strpos($daftar, 'data-logo-mock="UB"') !== FALSE,
        'Tanpa foto: logo tiruan berinisial benar ("PT. YURIS PRATAMA ..." -> YP, "CV. ... BETA" -> UB)');

    echo "\n-- Halaman ubah & simpan --\n";
    [$k, $hal] = $http($jAdm, 'Admin_Srp2/ubah/' . $idA);
    $cek($k === 200 && substr_count($hal, 'data-kartu="') >= 5 && strpos($hal, 'name="foto_profil"') !== FALSE && strpos($hal, 'data-form-buat-akun') !== FALSE,
        'Halaman ubah: kartu Profil, Kontak, Sertifikasi, Akun, Hapus + unggah foto + formulir Buatkan akun');
    [$k, $tambah] = $http($jAdm, 'Admin_Srp2/tambah');
    $cek($k === 200 && strpos($tambah, 'name="id" value="0"') !== FALSE && strpos($tambah, 'data-form-buat-akun') === FALSE, 'Tambah pengembang memakai formulir yang sama, kosong, tanpa kartu akun');
    $cek($http($jAdm, 'Admin_Srp2/ubah/999999999')[0] === 404, 'Ubah id yang tidak ada dijawab 404');

    $isian = ['id' => $idA, 'nama_perusahaan' => $namaA, 'alamat_kantor' => 'Jl. Admin ' . $TAG, 'kabupaten_id' => 0, 'asosiasi' => '',
        'no_keanggotaan' => 'KTA-' . $TAG, 'nib' => '1234567890123', 'npwp' => '', 'no_whatsapp' => '0812-3456-7890', 'email_kontak' => "kontak.{$tag}@example.test",
        'website' => 'https://' . $tag . '.example.test', 'instagram' => '', 'sosmed_lainnya' => '', 'status_sertifikasi' => 'bersertifikat',
        'sertifikat_terbit' => '', 'sertifikat_berakhir' => date('Y-m-d', strtotime('+1 year')), 'status_aktif' => 1];
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', $isian);
    $a = $entri($idA);
    $cek($a['alamat_kantor'] === 'Jl. Admin ' . $TAG && $a['nib'] === '1234567890123' && $a['no_whatsapp'] === '081234567890'
        && $a['email_kontak'] === "kontak.{$tag}@example.test" && $a['no_keanggotaan'] === 'KTA-' . $TAG,
        'Simpan detail: alamat, NIB, WhatsApp (dinormalkan ke angka), email kontak, no keanggotaan tersimpan');
    $cek(strpos($hal, 'Daftar pengembang diperbarui') !== FALSE && $jejak('srp2_direktori_diubah', 'srp2_direktori_pengembang', $idA) === 1,
        'Simpan kembali ke halaman ubah dengan pesan sukses dan tercatat di jejak audit');
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', ['nib' => '123'] + $isian);
    $cek($entri($idA)['nib'] === '1234567890123' && strpos($hal, 'NIB harus 13 digit angka') !== FALSE, 'NIB bukan 13 digit ditolak, nilai lama bertahan');
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', ['nama_perusahaan' => $namaB] + $isian);
    $cek($entri($idA)['nama_perusahaan'] === $namaA && strpos($hal, 'sudah dipakai baris lain') !== FALSE, 'Nama yang dipakai baris lain ditolak dengan pesan jelas');

    echo "\n-- Foto/logo --\n";
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', ['foto_profil' => $berkas($gambar(900, 600, 'RAHASIA-GPS-' . $TAG), 'image/png', 'logo.png')] + $isian);
    $a = $entri($idA); $fotoA = (string) $a['foto_profil'];
    $cek((bool) preg_match('#^assets/img/pengembang/unggahan/[a-f0-9]{32}\.png$#', $fotoA) && is_file($AKAR . '/' . $fotoA), 'Foto PNG sah tersimpan dengan nama acak di folder publik');
    $dim = $fotoA ? @getimagesize($AKAR . '/' . $fotoA) : FALSE;
    $cek($dim && max($dim[0], $dim[1]) <= 512 && strpos((string) @file_get_contents($AKAR . '/' . $fotoA), 'RAHASIA-GPS') === FALSE,
        'Foto digambar ulang: diperkecil ke 512 px dan metadata (potongan teks PNG) terbuang');
    $palsu = tempnam(sys_get_temp_dir(), 'usdp') . '.png'; $tmp[] = $palsu; file_put_contents($palsu, '<?php echo "x"; ?>');
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', ['foto_profil' => $berkas($palsu, 'image/png', 'logo.png')] + $isian);
    $cek($entri($idA)['foto_profil'] === $fotoA && strpos($hal, 'Foto harus JPG, PNG, atau WEBP') !== FALSE, 'Teks berkedok .png ditolak, foto lama bertahan');
    $gif = $tetap('gif', $GIF);
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/save', ['foto_profil' => $berkas($gif, 'image/gif', 'logo.gif')] + $isian);
    $cek($entri($idA)['foto_profil'] === $fotoA && strpos($hal, 'Foto harus JPG, PNG, atau WEBP') !== FALSE, 'GIF ditolak (hanya JPG/PNG/WEBP)');
    [, $daftar] = $http($jAdm, 'Admin_Srp2?q=' . urlencode($TAG));
    $cek(strpos($daftar, 'src="' . $BASE . $fotoA . '"') !== FALSE && strpos($daftar, 'data-logo-mock="YP"') === FALSE && strpos($daftar, 'data-logo-mock="UB"') !== FALSE,
        'Daftar admin: baris berfoto menampilkan gambarnya, baris tanpa foto tetap logo tiruan');
    [, $pub] = $http($jTamu, 'Pengembang/sertifikasi');
    $cek(strpos($pub, 'src="' . $BASE . $fotoA . '"') !== FALSE && strpos($pub, 'data-logo-mock="UB"') !== FALSE, 'Direktori publik: foto untuk A, logo tiruan UB untuk B');
    [, $pubB] = $http($jTamu, 'Pengembang/profil/' . $idB);
    $cek(strpos($pubB, $namaB) !== FALSE && strpos($pubB, 'data-logo-mock="UB"') !== FALSE, 'Profil publik tanpa foto menampilkan logo tiruan');
    $cek(strpos($http($jAdm, 'Admin_Srp2/ubah/' . $idA)[1], 'src="' . $BASE . $fotoA . '"') !== FALSE
        && strpos($http($jAdm, 'Admin_Srp2/ubah/' . $idB)[1], 'data-logo-mock="UB"') !== FALSE, 'Halaman ubah admin: foto bila ada, logo tiruan bila belum');
    [, $pubA] = $http($jTamu, 'Pengembang/profil/' . $idA);
    $cek(strpos($pubA, 'src="' . $BASE . $fotoA . '"') !== FALSE && strpos($pubA, 'data-logo-mock') === FALSE, 'Profil publik berfoto menampilkan gambarnya, bukan logo tiruan');

    echo "\n-- Buatkan akun --\n";
    $eA = "{$tag}_pemilik@example.test";
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/buat_akun/' . $idA, ['email' => $eA, 'nama_pj' => 'Budi ' . $TAG, 'no_whatsapp' => '081234567890', 'password' => '']);
    $sandiA = $sandi_flash($hal);
    $u = $db->query("SELECT * FROM usr_akun WHERE email='{$eA}'")->fetch_assoc();
    $uidA = (int) ($u['id'] ?? 0);
    $cek($uidA > 0 && $u['peran'] === 'pengembang' && $u['status'] === 'active' && (int) $u['profil_lengkap'] === 1
        && ! array_key_exists('nama_perusahaan', $u) && ! array_key_exists('alamat_kantor', $u),
        'Akun pengembang aktif lahir dengan onboarding lengkap; data perusahaan tetap di baris direktori (migrasi 070)');
    $cek($sandiA !== '' && password_verify($sandiA, (string) ($u['kata_sandi'] ?? '')) && strtotime($u['sandi_kedaluwarsa_at']) <= strtotime($u['sandi_diganti_at']),
        'Sandi awal yang dibangkitkan tampil sekali ke admin, sah, dan wajib diganti di login pertama');
    $reg = $db->query("SELECT * FROM srp2_pengajuan WHERE user_id={$uidA}")->fetch_assoc();
    $cek($reg && $reg['status_verifikasi'] === 'Diterima' && (int) $reg['pengembang_id'] === $idA && $reg['nib'] === '1234567890123'
        && $reg['nama_peserta'] === 'Budi ' . $TAG, 'Pengajuan SRP2 Diterima dibuat menunjuk entri ini (NIB & penanggung jawab tersalin)');
    $cek((int) $entri($idA)['user_id'] === $uidA, 'Entri direktori tertaut ke akun baru');
    $cek($jejak('srp2_akun_dibuat', 'srp2_direktori_pengembang', $idA) === 1
        && (int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE detail_json LIKE '%" . $db->real_escape_string($sandiA) . "%'") === 0,
        'Tercatat di jejak audit (srp2_akun_dibuat) tanpa isi sandinya');
    $http($jAdm, 'Admin_Srp2/ubah/' . $idA); // flash sekali pakai sudah habis di permintaan sebelumnya
    $cek($sandi_flash($http($jAdm, 'Admin_Srp2/ubah/' . $idA)[1]) === '', 'Sandi awal tidak tampil lagi pada kunjungan berikutnya');

    [, $hal] = $kirim($jAdm, 'Admin_Srp2/buat_akun/' . $idB, ['email' => strtoupper($eA), 'password' => '']);
    $cek($entri($idB)['user_id'] === NULL && (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE email='{$eA}'") === 1 && strpos($hal, 'email tersebut sudah terdaftar') !== FALSE,
        'Email yang sudah dipakai ditolak, entri B tetap tanpa akun');
    $eLain = "{$tag}_kedua@example.test";
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/buat_akun/' . $idA, ['email' => $eLain, 'password' => '']);
    $cek((int) $entri($idA)['user_id'] === $uidA && (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE email='{$eLain}'") === 0 && strpos($hal, 'sudah tertaut') !== FALSE,
        'Tautan kedua ke entri yang sudah tertaut ditolak, tidak ada akun yatim');
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/buat_akun/' . $idB, ['email' => $eLain, 'password' => 'lemah123']);
    $cek($entri($idB)['user_id'] === NULL && strpos($hal, 'huruf besar, angka, dan simbol') !== FALSE, 'Sandi awal lemah ditolak dengan aturan sandi_kuat');
    [, $hal] = $http($jAdm, 'Admin_Srp2/ubah/' . $idA);
    $cek(strpos($hal, 'data-akun-email>' . $eA) !== FALSE && strpos($hal, 'Reset sandi') !== FALSE && strpos($hal, 'Lepas tautan') !== FALSE && strpos($hal, 'belum diganti pemilik') !== FALSE,
        'Kartu akun menampilkan email tertaut, status sandi awal, Reset sandi, dan Lepas tautan');
    [, $daftar] = $http($jAdm, 'Admin_Srp2?q=' . urlencode($TAG));
    $cek(strpos($daftar, $eA) !== FALSE && strpos($daftar, 'Tertaut') !== FALSE, 'Daftar admin menandai entri tertaut beserta emailnya');

    echo "\n-- Login pertama pengembang --\n";
    $jP = $login($eA, $sandiA);
    [, , $url] = $http($jP, 'akun/perusahaan');
    $cek($masuk($jP) && strpos($url, 'akun/profil?password_expired=1') !== FALSE, 'Login pertama: Profil Perusahaan menolak sebelum sandi awal diganti');
    [, , $url] = $http($jP, 'Auth/onboarding');
    $cek(strpos($url, 'onboarding') === FALSE, 'Onboarding tidak diminta lagi (profil sudah lengkap)');
    $sandiBaru = 'Br1#' . bin2hex(random_bytes(5));
    $kirim($jP, 'akun/update', ['username' => $u['nama_pengguna'], 'name' => $u['nama'], 'phone' => '081234567890',
        'password' => $sandiBaru, 'password_confirm' => $sandiBaru, 'current_password' => $sandiA]);
    $cek((int) $nilai("SELECT sandi_kedaluwarsa_at > NOW() FROM usr_akun WHERE id={$uidA}") === 1, 'PRASYARAT: pengembang mengganti sandi awalnya lewat Profil Saya');
    [$k, $hal] = $http($jP, 'akun/perusahaan');
    $cek($k === 200 && strpos($hal, 'data-form-perusahaan') !== FALSE && strpos($hal, $namaA) !== FALSE && strpos($hal, 'src="' . $BASE . $fotoA . '"') !== FALSE
        && strpos($hal, 'name="nama_perusahaan"') === FALSE && strpos($hal, 'name="npwp"') === FALSE,
        'Profil Perusahaan terbuka: foto tampil, nama/NPWP hanya dibaca (tanpa isian)');
    [, $hal] = $http($jP, 'akun/profil');
    $cek(strpos($hal, 'data-ke-profil-perusahaan') !== FALSE && strpos($hal, 'akun/update_pengembang') === FALSE, 'Profil Saya mengarahkan data perusahaan ke Profil Perusahaan (satu formulir)');

    echo "\n-- Pengembang mengubah profilnya sendiri --\n";
    $isianP = ['alamat_kantor' => 'Jl. Pemilik ' . $TAG, 'asosiasi' => '', 'no_keanggotaan' => 'KTA2-' . $TAG, 'nib' => '9876543210123',
        'no_whatsapp' => '+62 811 2222 3333', 'email_kontak' => "pemilik.{$tag}@example.test", 'website' => '', 'instagram' => 'https://instagram.com/' . $tag, 'sosmed_lainnya' => '',
        // Medan milik dinas & sasaran lain: harus diabaikan.
        'nama_perusahaan' => 'PT DIUBAH ' . $TAG, 'status_aktif' => 0, 'kabupaten_id' => 1, 'sertifikat_berakhir' => '2000-01-01', 'id' => $idB];
    [, $hal] = $kirim($jP, 'akun/perusahaan/simpan', ['foto_profil' => $berkas($tetap('jpg', $JPG), 'image/jpeg', 'baru.jpg')] + $isianP);
    $a = $entri($idA);
    $cek($a['alamat_kantor'] === 'Jl. Pemilik ' . $TAG && $a['no_whatsapp'] === '6281122223333' && $a['nib'] === '9876543210123'
        && $a['email_kontak'] === "pemilik.{$tag}@example.test" && $a['instagram'] === 'https://instagram.com/' . $tag,
        'Pengembang menyimpan alamat, WhatsApp, NIB, email kontak, Instagram ke barisnya sendiri');
    $cek($a['nama_perusahaan'] === $namaA && (int) $a['status_aktif'] === 1 && $a['kabupaten_id'] === NULL && $a['sertifikat_berakhir'] === date('Y-m-d', strtotime('+1 year')),
        'Nama, penayangan, wilayah, dan masa berlaku yang dikirim pengembang diabaikan');
    $fotoA2 = (string) $a['foto_profil'];
    $cek($fotoA2 !== $fotoA && (bool) preg_match('#\.jpg$#', $fotoA2) && is_file($AKAR . '/' . $fotoA2) && ! is_file($AKAR . '/' . $fotoA), 'Foto baru pengembang menggantikan foto lama (berkas lama dihapus)');
    $b = $entri($idB);
    $cek($b['alamat_kantor'] === 'Jl. Beta ' . $TAG && $b['foto_profil'] === NULL && $b['nib'] === NULL, 'Anti-IDOR: id entri lain di formulir tidak menyentuh entri B');
    $cek(strpos($hal, 'langsung tampil di direktori publik') !== FALSE && $jejak('srp2_profil_diubah_pengembang', 'srp2_direktori_pengembang', $idA) === 1,
        'Pesan sukses jujur dan tercatat di jejak audit (srp2_profil_diubah_pengembang)');
    $reg = $db->query("SELECT * FROM srp2_pengajuan WHERE user_id={$uidA}")->fetch_assoc();
    $cek($reg['alamat_kantor'] === 'Jl. Pemilik ' . $TAG && $reg['no_keanggotaan'] === 'KTA2-' . $TAG && $reg['nib'] === '9876543210123',
        'Pengajuan SRP2 akun ikut tersinkron dari direktori');
    [, $hal] = $kirim($jP, 'akun/perusahaan/simpan', ['website' => 'javascript:alert(1)'] + $isianP);
    $cek($entri($idA)['website'] === NULL && strpos($hal, 'URL http/https') !== FALSE, 'Tautan bukan http/https ditolak dengan aturan yang sama dengan admin');
    [, $pubA] = $http($jTamu, 'Pengembang/profil/' . $idA);
    $cek(strpos($pubA, 'Jl. Pemilik ' . $TAG) !== FALSE && strpos($pubA, 'https://wa.me/6281122223333') !== FALSE
        && strpos($pubA, "pemilik.{$tag}@example.test") !== FALSE && strpos($pubA, 'src="' . $BASE . $fotoA2 . '"') !== FALSE,
        'Profil publik langsung menampilkan alamat, WhatsApp, email, dan foto baru');
    $cek(strpos($pubA, '9876543210123') === FALSE, 'NIB tidak ditampilkan di profil publik');
    [, $daftar] = $http($jAdm, 'Admin_Srp2?q=' . urlencode($TAG));
    $cek(strpos($daftar, 'Jl. Pemilik ' . $TAG) !== FALSE && strpos($daftar, 'src="' . $BASE . $fotoA2 . '"') !== FALSE, 'Daftar admin langsung menampilkan perubahan pengembang');
    [, , $url] = $kirim($jP, 'akun/update_pengembang', ['nama_perusahaan' => $namaA, 'alamat_kantor' => 'Jl. Lewat Profil Saya', 'asosiasi' => '', 'no_keanggotaan' => 'x']);
    $cek(strpos($url, 'akun/perusahaan') !== FALSE && $entri($idA)['alamat_kantor'] === 'Jl. Pemilik ' . $TAG, 'Formulir lama Profil Saya ditolak untuk akun tertaut (tidak menimpa direktori)');

    echo "\n-- Akun lain: tidak tertaut & tertaut lewat pengajuan diterima --\n";
    [$uidL, $eL] = $akun_db('pengembang');
    $jL = $login($eL, $sandi);
    [, $hal] = $http($jL, 'akun/perusahaan');
    $cek(strpos($hal, 'data-perusahaan-belum-tertaut') !== FALSE, 'Pengembang tak tertaut melihat penjelasan, bukan formulir');
    $kirim($jL, 'akun/perusahaan/simpan', array_merge($isianP, ['alamat_kantor' => 'Jl. Penyusup', 'id' => $idA]));
    $cek($entri($idA)['alamat_kantor'] === 'Jl. Pemilik ' . $TAG && $entri($idB)['alamat_kantor'] === 'Jl. Beta ' . $TAG, 'Anti-IDOR: pengembang tak tertaut tidak bisa menulis entri mana pun');
    [$jW] = [$login($akun_db('warga')[1], $sandi)];
    $cek($http($jW, 'akun/perusahaan')[0] === 404, 'Profil Perusahaan hanya untuk peran pengembang (warga 404)');

    $namaG = "PT {$TAG} GAMMA"; $nibG = (string) random_int(1000000000000, 9999999999999);
    $db->query("INSERT INTO srp2_pengajuan (user_id, email, nama_perusahaan, nib, no_keanggotaan, no_whatsapp, alamat_kantor, status_verifikasi)
        VALUES ({$uidL}, '{$eL}', '{$namaG}', '{$nibG}', 'KTA-G', '081299998888', 'Jl. Gamma', 'Pending')");
    $ridG = (int) $db->insert_id;
    $http($jAdm, 'Admin_Srp2/detail/' . $ridG);
    $kirim($jAdm, 'Admin_Srp2/proses/' . $ridG, ['status' => 'Diterima']);
    $g = $db->query("SELECT * FROM srp2_direktori_pengembang WHERE nama_perusahaan='{$namaG}'")->fetch_assoc();
    $idG = (int) ($g['id'] ?? 0); if ($idG) { $ids[] = $idG; }
    $cek($g && (int) $g['user_id'] === $uidL && $g['nib'] === $nibG && $g['no_keanggotaan'] === 'KTA-G' && $g['no_whatsapp'] === '081299998888' && $g['email_kontak'] === $eL,
        'Pengajuan diterima: entri baru tertaut ke pemohon dan NIB/keanggotaan/WhatsApp/email ikut tersalin');
    [, $hal] = $http($jL, 'akun/perusahaan');
    $cek(strpos($hal, 'data-form-perusahaan') !== FALSE && strpos($hal, 'data-logo-mock="UG"') !== FALSE, 'Pemohon yang diterima langsung punya Profil Perusahaan (logo tiruan UG)');
    $kirim($jL, 'akun/perusahaan/simpan', array_merge($isianP, ['id' => $idA, 'alamat_kantor' => 'Jl. Gamma Baru', 'nib' => '']));
    $cek($entri($idG)['alamat_kantor'] === 'Jl. Gamma Baru' && $entri($idA)['alamat_kantor'] === 'Jl. Pemilik ' . $TAG, 'Anti-IDOR: akun tertaut lain hanya mengubah barisnya sendiri');

    echo "\n-- Reset sandi & lepas tautan --\n";
    [, $hal] = $kirim($jAdm, 'Admin_Srp2/reset_sandi_akun/' . $idA, ['password' => '']);
    $sandiR = $sandi_flash($hal);
    $u = $db->query("SELECT * FROM usr_akun WHERE id={$uidA}")->fetch_assoc();
    $cek($sandiR !== '' && password_verify($sandiR, $u['kata_sandi']) && strtotime($u['sandi_kedaluwarsa_at']) <= strtotime($u['sandi_diganti_at']) && $u['sesi_aktif_hash'] === NULL,
        'Reset sandi: sandi baru tampil sekali, wajib diganti, sesi dicabut');
    $cek( ! $masuk($jP), 'Sesi pengembang yang lama berakhir sesudah reset sandi');
    $jP = $login($eA, $sandiR);
    $cek(strpos($http($jP, 'akun/perusahaan')[2], 'password_expired=1') !== FALSE, 'Login dengan sandi reset sah dan diarahkan mengganti sandi');
    $cek($jejak('srp2_akun_sandi_direset', 'usr_akun', $uidA) === 1, 'Reset sandi tercatat di jejak audit');

    [, $hal] = $kirim($jAdm, 'Admin_Srp2/lepas_akun/' . $idA, []);
    $cek($entri($idA)['user_id'] === NULL && (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE id={$uidA}") === 1
        && $nilai("SELECT pengembang_id FROM srp2_pengajuan WHERE user_id={$uidA}") === NULL,
        'Lepas tautan: entri tanpa pemilik, akun tetap ada, pengajuannya tidak lagi menunjuk entri');
    $cek(strpos($hal, 'data-form-buat-akun') !== FALSE && $jejak('srp2_akun_dilepas', 'srp2_direktori_pengembang', $idA) === 1, 'Kartu akun kembali ke Buatkan akun; tercatat di jejak audit');
    $db->query("UPDATE usr_akun SET sandi_kedaluwarsa_at=DATE_ADD(NOW(), INTERVAL 90 DAY) WHERE id={$uidA}");
    $jP = $login($eA, $sandiR);
    $cek(strpos($http($jP, 'akun/perusahaan')[1], 'data-perusahaan-belum-tertaut') !== FALSE, 'Akun yang dilepas tidak lagi memegang Profil Perusahaan');

    echo "\n-- Hapus entri --\n";
    $kirim($jAdm, 'Admin_Srp2/delete/' . $idA, []);
    clearstatcache(); // berkas dihapus proses Apache; cache stat CLI masih ingat hasil lama
    $cek($entri($idA) === NULL && ! is_file($AKAR . '/' . $fotoA2) && $jejak('srp2_direktori_dihapus', 'srp2_direktori_pengembang', $idA) === 1,
        'Hapus entri membuang barisnya, berkas fotonya, dan tercatat di jejak audit');
} catch (Throwable $e) {
    // Galat tak terduga dicatat sebagai GAGAL supaya pembersihan di bawah tetap berjalan.
    $cek(FALSE, "Galat tak terduga: " . $e->getMessage() . " (baris " . $e->getLine() . ")");
} finally {
    foreach ($ids as $id) { $f = $nilai('SELECT foto_profil FROM srp2_direktori_pengembang WHERE id=' . (int) $id); if ($f && strpos($f, 'assets/img/pengembang/unggahan/') === 0) @unlink($AKAR . '/' . $f); }
    $uids = array_map(fn($r) => (int) $r[0], $db->query("SELECT id FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'")->fetch_all());
    foreach ($uids as $id) {
        $db->query("DELETE FROM srp2_pengajuan WHERE user_id={$id}");
        $db->query("DELETE FROM usr_dokumen WHERE user_id={$id}");
    }
    $daftar_id = implode(',', array_map('intval', $ids ?: [0]));
    $db->query("DELETE FROM srp2_direktori_pengembang WHERE id IN ({$daftar_id}) OR nama_perusahaan LIKE '%{$TAG}%'");
    $uid_txt = implode(',', array_map(fn($i) => "'{$i}'", $uids ?: [0]));
    $db->query("DELETE FROM sys_jejak_audit WHERE (objek_tipe='srp2_direktori_pengembang' AND objek_id IN ({$daftar_id}))
        OR (objek_tipe='usr_akun' AND objek_id IN ({$uid_txt})) OR pelaku_email LIKE '{$tag}\\_%@example.test'");
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'");
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
    foreach (array_merge($jars, $tmp) as $f) { @unlink($f); }
    $sisa = (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE email LIKE '{$tag}\\_%@example.test'") + (int) $nilai("SELECT COUNT(*) FROM srp2_direktori_pengembang WHERE nama_perusahaan LIKE '%{$TAG}%'");
    $cek($sisa === 0, 'Data uji (akun, entri direktori, foto) dibersihkan');
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
