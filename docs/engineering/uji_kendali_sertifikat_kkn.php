<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/**
 * Kendali sertifikat KKN di tangan dinas (keputusan user 7 Okt 2026, migrasi 077).
 *
 *   php docs/engineering/uji_kendali_sertifikat_kkn.php
 *
 * Dijaga:
 *   Tahap 1: admin mencatat KKN atas nama universitas (langsung Diterima, dicatat_oleh terisi, ganda ditolak),
 *            mengunggah roster sendiri, dan menerbitkan dari halaman Peserta; akun lain tidak bisa.
 *   Tahap 2: NIM yang ditemukan pada KKN berperiode lewat tanpa tanggal sertifikat ditawari tombol minta;
 *            permintaan tercatat sekali per peramban, tampil di Sertifikat KKN dan badge menu; menetapkan
 *            tanggal menutup permintaan; Abaikan menutupnya; NIM yang tidak ditemukan tidak ditawari apa pun.
 *   Nomor sertifikat per KKN (migrasi 078/079): halaman Peserta hanya pratinjau (nomor + Lihat sertifikat; edit per
 *            peserta dihapus atas keputusan user 7 Okt 2026); nomor = awalan admin + urut dua digit yang menempel di
 *            peserta (tidak dipakai ulang), bawaan 600.2/69. + id; awalan yang membuat nomor kembar ditolak; awalan
 *            dan tanggal tersimpan dari satu formulir; PDF peserta dan pratinjau admin memakai nomor yang sama.
 *
 * Akun, KKN, peserta, dan jejak audit uji dibuat lalu dihapus sendiri; ember laju per IP dipinjam lalu dipulihkan.
 */
$AKAR = dirname(__DIR__, 2);
require $AKAR . '/vendor/autoload.php';
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path($AKAR), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if ( ! isset($env[trim($k)])) $env[trim($k)] = trim($v, " \t\"'"); }
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) { echo "  GAGAL DB lokal\nRINGKASAN: 1 pemeriksaan, 1 gagal\n"; exit(1); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$tag = 'ujikendali' . bin2hex(random_bytes(3));
$sandi = 'Kk1#' . bin2hex(random_bytes(5));
$nim = 'KN' . strtoupper(bin2hex(random_bytes(4)));
$nim2 = 'KN2' . strtoupper(bin2hex(random_bytes(3)));
$total = 0; $gagal = 0; $jars = []; $tmp = []; $akun = []; $ember = [];
$audit_awal = (int) $db->query('SELECT COALESCE(MAX(id),0) FROM sys_jejak_audit')->fetch_row()[0];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if ( ! $ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$nilai = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $berkas = (bool) array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $berkas ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch); $u = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); $k = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['badan' => html_entity_decode($b, ENT_QUOTES, 'UTF-8'), 'url' => $u, 'kode' => $k];
};
$csrf = function ($jar) { foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'uks'); $jars[] = $j; return $j; };
$kirim = function ($jar, $path, array $isi) use ($http, $csrf) { return $http($jar, $path, ['csrf_kpkp_token' => $csrf($jar)] + $isi); };
$buat_akun = function ($peran, $urut) use ($db, $tag, $sandi, &$akun) {
    $e = "{$tag}_{$urut}@kendali.test"; $h = password_hash($sandi, PASSWORD_BCRYPT); $nama = "Uji {$peran} {$tag}";
    $st = $db->prepare("INSERT INTO usr_akun (nama,email,kata_sandi,peran,status,profil_lengkap,no_hp,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at) VALUES (?,?,?,?,'active',1,'081200000000',NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('ssss', $nama, $e, $h, $peran); $st->execute(); $akun[] = (int) $db->insert_id;
    return [(int) $db->insert_id, $e, $nama];
};
$login = function ($email) use ($http, $csrf, $sesi, $sandi) { $j = $sesi(); $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
$xlsx = function (array $baris) use (&$tmp) {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $book->getActiveSheet()->fromArray([['NIM', 'Nama']], NULL, 'A1');
    foreach ($baris as $i => $r) {
        $book->getActiveSheet()->setCellValueExplicit('A' . ($i + 2), $r[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $book->getActiveSheet()->setCellValue('B' . ($i + 2), $r[1]);
    }
    $f = tempnam(sys_get_temp_dir(), 'uksx') . '.xlsx'; $tmp[] = $f;
    \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx')->save($f); return $f;
};
$cari = function ($nim_cari) use ($http, $csrf, $sesi) { $j = $sesi(); $http($j, 'KemitraanPortal/sertifikat_kkn'); return [$j, $http($j, 'KemitraanPortal/cek_sertifikat_kkn', ['csrf_kpkp_token' => $csrf($j), 'nim' => $nim_cari])]; };

try {
    echo "=== UJI KENDALI SERTIFIKAT KKN ===\n";
    foreach (['sertifikat_kkn_lookup', 'sertifikat_kkn_minta', 'login'] as $pol) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
        $k = hash('sha256', $pol . ':ip:' . $ip);
        $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
    }
    [$adm, $eAdm] = $buat_akun('admin', 'adm');
    [$univ, , $namaUniv] = $buat_akun('universitas', 'univ');
    [, $eWarga] = $buat_akun('warga', 'warga');

    echo "\n== Tahap 1: admin mencatat KKN, roster, dan menerbitkan\n";
    $jA = $login($eAdm);
    $r = $http($jA, 'Admin_Kemitraan/catat');
    $cek(strpos($r['badan'], 'data-catat-kkn') !== FALSE && strpos($r['badan'], $namaUniv) !== FALSE, 'Formulir Catat KKN terbuka dengan pilihan akun universitas');
    $jW = $login($eWarga);
    $cek(strpos($http($jW, 'Admin_Kemitraan/catat')['badan'], 'data-catat-kkn') === FALSE, 'Akun selain admin tidak bisa membuka Catat KKN');
    $isi = ['universitas' => $univ, 'periode_mulai' => date('Y-m-d', strtotime('-80 days')), 'periode_selesai' => date('Y-m-d', strtotime('-50 days')),
        'keterangan' => "KKN lama {$tag}", 'catatan' => 'Dicatat susulan: berlangsung sebelum aplikasi tersedia.'];
    $r = $kirim($jA, 'Admin_Kemitraan/simpan_catat', $isi);
    $row = $db->query("SELECT * FROM kkn_magang_pendaftaran WHERE user_id={$univ} AND divisi_atau_tema='KKN lama {$tag}'")->fetch_assoc();
    $kkn = (int) ($row['id'] ?? 0);
    $cek($row && $row['status'] === 'Diterima' && (int) $row['dicatat_oleh'] === $adm && $row['instansi_asal'] === $namaUniv && strpos($r['url'], 'Admin_Kemitraan/peserta/' . $kkn) !== FALSE,
        'KKN dicatat atas nama universitas, langsung Diterima, dicatat_oleh = admin, diarahkan ke halaman Peserta');
    $kirim($jA, 'Admin_Kemitraan/simpan_catat', $isi);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE user_id={$univ} AND divisi_atau_tema='KKN lama {$tag}'") === 1, 'Catat ganda (universitas, periode, keterangan sama) ditolak');
    $cek($kirim($jA, 'Admin_Kemitraan/simpan_catat', ['universitas' => $univ] + $isi)['kode'] === 200
        && $http($jA, 'Admin_Kemitraan/simpan_catat')['kode'] >= 400, 'Simpan catat hanya menerima POST');
    $jU = $login("{$tag}_univ@kendali.test");
    $cek(strpos($http($jU, 'KemitraanPortal/kkn_dashboard')['badan'], "KKN lama {$tag}") !== FALSE, 'KKN yang dicatat admin tampil di dashboard universitasnya');
    $r = $kirim($jA, 'Admin_Kemitraan/unggah_peserta/' . $kkn, ['file_peserta' => new CURLFile($xlsx([[$nim, "Peserta {$tag}"], [$nim2, "Peserta dua {$tag}"]]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'roster.xlsx')]);
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$kkn}") === 2 && strpos($r['badan'], "Peserta {$tag}") !== FALSE, 'Admin mengunggah roster (2 peserta) dari halaman Peserta');
    $cek(strpos($r['badan'], 'data-kendali-sertifikat') !== FALSE, 'Halaman Peserta admin memuat unggah roster dan panel sertifikat');

    echo "\n== Pratinjau nomor dan sertifikat di halaman Peserta (migrasi 078/079)\n";
    $p1 = (int) $nilai("SELECT id FROM kkn_peserta WHERE pendaftaran_id={$kkn} AND nim='{$nim}'");
    $p2 = (int) $nilai("SELECT id FROM kkn_peserta WHERE pendaftaran_id={$kkn} AND nim='{$nim2}'");
    // PDF diambil mentah (tanpa html_entity_decode milik $http), lalu stream FPDF yang terkompresi dibuka.
    $teks_pdf = function ($jar, $jalur = 'KemitraanPortal/sertifikat_kkn_pdf') use ($BASE) {
        $ch = curl_init($BASE . $jalur);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
        $pdf = (string) curl_exec($ch); curl_close($ch);
        $teks = $pdf;
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m)) { foreach ($m[1] as $b) { $teks .= (string) @gzuncompress($b); } }
        return $teks;
    };
    $cek(strpos($r['badan'], 'data-nomor-sertifikat="' . $p1 . '">600.2/69.' . $p1 . '<') !== FALSE
        && strpos($r['badan'], 'Admin_Kemitraan/pratinjau_sertifikat/' . $p2) !== FALSE && strpos($r['badan'], 'name="nomor_sertifikat"') === FALSE,
        'Tiap peserta tampil dengan nomor bawaan dan tombol Lihat sertifikat, tanpa isian nomor per peserta');
    $cek(strpos($teks_pdf($jA, 'Admin_Kemitraan/pratinjau_sertifikat/' . $p1), '(Nomor : 600.2/69.' . $p1 . ')') !== FALSE,
        'Admin melihat sertifikat peserta sebelum terbit, dengan nomor yang sama');
    $cek(strpos($teks_pdf($jW, 'Admin_Kemitraan/pratinjau_sertifikat/' . $p1), '%PDF') !== 0, 'Akun selain admin tidak bisa melihat sertifikat lewat pratinjau admin');
    $cek($http($jA, 'Admin_Kemitraan/nomor_sertifikat/' . $p1)['kode'] === 404, 'Rute edit nomor per peserta sudah tidak ada');

    echo "\n== Tahap 2: permintaan sertifikat dari peserta\n";
    [$jM, $r] = $cari($nim);
    $cek(strpos($r['badan'], 'data-minta-sertifikat') !== FALSE && strpos($r['badan'], "Peserta {$tag}") === FALSE, 'NIM ditemukan, sertifikat belum terbit: tombol minta ditawarkan, nama tidak tampil');
    $r = $kirim($jM, 'KemitraanPortal/minta_sertifikat_kkn', []);
    $baris = $db->query("SELECT sertifikat_diminta_at, sertifikat_diminta_jumlah FROM kkn_magang_pendaftaran WHERE id={$kkn}")->fetch_assoc();
    $cek($baris['sertifikat_diminta_at'] !== NULL && (int) $baris['sertifikat_diminta_jumlah'] === 1 && stripos($r['badan'], 'Permintaan terkirim') !== FALSE, 'Permintaan tercatat (jumlah 1) dan peserta diberi konfirmasi');
    $kirim($jM, 'KemitraanPortal/minta_sertifikat_kkn', []);
    $cek((int) $nilai("SELECT sertifikat_diminta_jumlah FROM kkn_magang_pendaftaran WHERE id={$kkn}") === 1, 'Kiriman ulang tanpa pencarian baru tidak menambah hitungan');
    [$jM2] = $cari($nim);
    $r = $kirim($jM2, 'KemitraanPortal/minta_sertifikat_kkn', []);
    $cek((int) $nilai("SELECT sertifikat_diminta_jumlah FROM kkn_magang_pendaftaran WHERE id={$kkn}") === 2, 'Peserta lain (peramban lain) menambah hitungan menjadi 2');
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE id > {$audit_awal} AND aksi='sertifikat_kkn_diminta' AND objek_id='{$kkn}'") === 1, 'Hanya permintaan pertama yang dicatat di jejak audit');
    [, $r] = $cari('TIDAKADA' . strtoupper(bin2hex(random_bytes(3))));
    $cek(strpos($r['badan'], 'data-minta-sertifikat') === FALSE && stripos($r['badan'], 'menu Aduan') !== FALSE, 'NIM tidak ditemukan: tanpa tombol minta, diarahkan ke kampus atau menu Aduan');
    $r = $http($jA, 'Admin_Kemitraan/sertifikat');
    $cek(strpos($r['badan'], 'data-sertifikat-baris="' . $kkn . '"') !== FALSE && strpos($r['badan'], '2x diminta') !== FALSE, 'Halaman Sertifikat KKN admin memuat KKN yang diminta beserta hitungannya');
    $cek(strpos($r['badan'], 'data-badge-modul="sertifikat_kkn"') !== FALSE, 'Menu Sertifikat KKN memasang badge permintaan');
    $r = $kirim($jA, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn, ['tanggal_sertifikat' => date('Y-m-d'), 'kembali' => 'sertifikat']);
    $baris = $db->query("SELECT tanggal_sertifikat, sertifikat_diminta_at, sertifikat_diminta_jumlah FROM kkn_magang_pendaftaran WHERE id={$kkn}")->fetch_assoc();
    $cek($baris['tanggal_sertifikat'] === date('Y-m-d') && $baris['sertifikat_diminta_at'] === NULL && (int) $baris['sertifikat_diminta_jumlah'] === 0
        && strpos($r['url'], 'Admin_Kemitraan/sertifikat') !== FALSE, 'Terbitkan dari halaman Sertifikat KKN: tanggal tersimpan, permintaan tertutup, kembali ke halaman itu');
    [$jC, $r] = $cari($nim);
    $cek(strpos($r['badan'], "Peserta {$tag}") !== FALSE && substr($http($jC, 'KemitraanPortal/sertifikat_kkn_pdf')['badan'], 0, 4) === '%PDF', 'Sesudah terbit: peserta menemukan sertifikatnya dan PDF terbentuk');
    $cek(strpos($teks_pdf($jC), '(Nomor : 600.2/69.' . $p1 . ')') !== FALSE, 'PDF peserta mencetak nomor yang sama dengan pratinjau admin');

    // Unggah ulang: peserta 1 tetap (nama dibetulkan), peserta 2 keluar, peserta 3 masuk.
    $nim3 = 'KN3' . strtoupper(bin2hex(random_bytes(3)));
    $kirim($jA, 'Admin_Kemitraan/unggah_peserta/' . $kkn, ['file_peserta' => new CURLFile($xlsx([[$nim, "Peserta Betul {$tag}"], [$nim3, "Peserta tiga {$tag}"]]), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'roster.xlsx')]);
    $baris = $db->query("SELECT id, nama FROM kkn_peserta WHERE pendaftaran_id={$kkn} AND nim='{$nim}'")->fetch_assoc();
    $cek($baris && (int) $baris['id'] === $p1 && $baris['nama'] === "Peserta Betul {$tag}",
        'Unggah ulang mempertahankan baris peserta yang tetap ada (nomornya tidak bergeser), namanya ikut diperbarui');
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_peserta WHERE pendaftaran_id={$kkn}") === 2 && $nilai("SELECT id FROM kkn_peserta WHERE id={$p2}") === NULL
        && $nilai("SELECT id FROM kkn_peserta WHERE pendaftaran_id={$kkn} AND nim='{$nim3}'") !== NULL, 'Peserta yang keluar dihapus, peserta baru ditambahkan');

    echo "\n== Awalan nomor per KKN dan urut yang menempel di peserta (migrasi 079)\n";
    $p3 = (int) $nilai("SELECT id FROM kkn_peserta WHERE pendaftaran_id={$kkn} AND nim='{$nim3}'");
    $awalan_db = fn() => $nilai("SELECT awalan_nomor_sertifikat FROM kkn_magang_pendaftaran WHERE id={$kkn}");
    $cek((int) $nilai("SELECT urut FROM kkn_peserta WHERE id={$p1}") === 1 && (int) $nilai("SELECT urut FROM kkn_peserta WHERE id={$p3}") === 3,
        'Urut menempel: peserta yang tetap ada mempertahankan urutnya, peserta baru tidak memakai ulang urut peserta yang dihapus');
    $awalan = '600.2/U' . strtoupper(bin2hex(random_bytes(2)));
    $r = $kirim($jA, 'Admin_Kemitraan/awalan_nomor/' . $kkn, ['awalan_nomor' => $awalan . '.']);
    $cek($awalan_db() === $awalan && strpos($r['badan'], '>' . $awalan . '.01<') !== FALSE && strpos($r['badan'], '>' . $awalan . '.03<') !== FALSE,
        'Admin mengatur awalan per KKN (titik di ujung dibuang); nomor otomatis menjadi awalan + urut dua digit');
    $cek(strpos($teks_pdf($jC), '(Nomor : ' . $awalan . '.01)') !== FALSE, 'PDF mencetak awalan KKN + urut peserta');
    $cek(strpos($teks_pdf($jA, 'Admin_Kemitraan/pratinjau_sertifikat/' . $p3), '(Nomor : ' . $awalan . '.03)') !== FALSE, 'Pratinjau admin mengikuti awalan');
    // KKN lain yang sudah memakai awalan ZZ: awalan yang sama membuat nomor .01 kembar.
    $zz = 'ZZ' . strtoupper(bin2hex(random_bytes(2)));
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,awalan_nomor_sertifikat,created_at)
        VALUES ({$univ},'kkn','{$namaUniv}','081200000000','KKN awalan {$tag}','2026-01-01','2026-02-01','Diterima','{$zz}',NOW())");
    $db->query('INSERT INTO kkn_peserta (pendaftaran_id,nim,nama,urut,created_at) VALUES (' . (int) $db->insert_id . ",'ZZ{$nim}','Peserta lain {$tag}',1,NOW())");
    $kirim($jA, 'Admin_Kemitraan/awalan_nomor/' . $kkn, ['awalan_nomor' => $zz]);
    $cek($awalan_db() === $awalan, 'Awalan yang sudah dipakai KKN lain ditolak (nomor .01 akan kembar)');
    $kirim($jA, 'Admin_Kemitraan/awalan_nomor/' . $kkn, ['awalan_nomor' => 'A;B']);
    $cek($awalan_db() === $awalan, 'Awalan dengan karakter di luar huruf, angka, dan . , / - _ ( ) ditolak');
    $kirim($jW, 'Admin_Kemitraan/awalan_nomor/' . $kkn, ['awalan_nomor' => 'BUKAN-ADMIN']);
    $cek($awalan_db() === $awalan && $http($jA, 'Admin_Kemitraan/awalan_nomor/' . $kkn)['kode'] >= 400, 'Awalan hanya bisa diubah admin, lewat POST');
    $r = $kirim($jA, 'Admin_Kemitraan/awalan_nomor/' . $kkn, ['awalan_nomor' => '']);
    $cek($awalan_db() === NULL && strpos($r['badan'], '>600.2/69.' . $p1 . '<') !== FALSE, 'Mengosongkan awalan mengembalikan nomor bawaan 600.2/69. + id');
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE id > {$audit_awal} AND aksi='sertifikat_kkn_awalan' AND objek_id='{$kkn}'") === 2,
        'Perubahan awalan tercatat di jejak audit (atur dan kosongkan; penolakan tidak dicatat)');
    // Awalan di atas tanggal sertifikat, satu formulir (tab Pendaftaran dan halaman Peserta).
    $tgl_db = fn() => $nilai("SELECT tanggal_sertifikat FROM kkn_magang_pendaftaran WHERE id={$kkn}");
    $kemarin = date('Y-m-d', strtotime('-1 day'));
    $kirim($jA, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn, ['awalan_nomor' => $awalan, 'tanggal_sertifikat' => $kemarin, 'kembali' => 'peserta']);
    $cek($awalan_db() === $awalan && $tgl_db() === $kemarin, 'Awalan dan tanggal sertifikat tersimpan bersama dari satu formulir');
    $kirim($jA, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn, ['awalan_nomor' => 'A;B', 'tanggal_sertifikat' => date('Y-m-d'), 'kembali' => 'peserta']);
    $cek($awalan_db() === $awalan && $tgl_db() === $kemarin, 'Awalan ditolak: tanggal sertifikat juga tidak disimpan');
    $cek(strpos($http($jA, 'Admin_Kemitraan?q=' . urlencode($tag))['badan'], 'name="awalan_nomor" maxlength="80" value="' . $awalan . '"') !== FALSE,
        'Tab Pendaftaran memuat isian awalan di panel tanggal sertifikat');

    // Abaikan: KKN kedua yang diminta lalu ditutup tanpa tanggal.
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,sertifikat_diminta_at,sertifikat_diminta_jumlah,created_at)
        VALUES ({$univ},'kkn','{$namaUniv}','081200000000','KKN diabaikan {$tag}','2026-01-01','2026-02-01','Diterima',NOW(),3,NOW())");
    $kkn2 = (int) $db->insert_id;
    $http($jA, 'Admin_Kemitraan/sertifikat');
    $kirim($jA, 'Admin_Kemitraan/abaikan_permintaan/' . $kkn2, []);
    $baris = $db->query("SELECT tanggal_sertifikat, sertifikat_diminta_at, sertifikat_diminta_jumlah FROM kkn_magang_pendaftaran WHERE id={$kkn2}")->fetch_assoc();
    $cek($baris['sertifikat_diminta_at'] === NULL && (int) $baris['sertifikat_diminta_jumlah'] === 0 && $baris['tanggal_sertifikat'] === NULL, 'Abaikan menutup permintaan tanpa menetapkan tanggal');
} catch (Throwable $e) {
    $cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    if ($akun) {
        $daftar = implode(',', $akun);
        $kkn_uji = array_column($db->query("SELECT id FROM kkn_magang_pendaftaran WHERE user_id IN ($daftar)")->fetch_all(), 0);
        if ($kkn_uji) {
            $id_kkn = implode(',', array_map('intval', $kkn_uji));
            $db->query("DELETE FROM kkn_peserta WHERE pendaftaran_id IN ($id_kkn)");
            $db->query("DELETE FROM sys_jejak_audit WHERE id > {$audit_awal} AND objek_tipe='kkn_magang_pendaftaran' AND objek_id IN ('" . implode("','", $kkn_uji) . "')");
            $db->query("DELETE FROM kkn_magang_pendaftaran WHERE id IN ($id_kkn)");
        }
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    foreach ($ember as $k => $b) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($b) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $b['kunci'], $b['jendela_mulai_at'], $b['jumlah_gagal']); $st->execute(); }
    }
    foreach (array_merge($jars, $tmp) as $f) { @unlink($f); }
    echo 'Akun uji tersisa: ' . (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE email LIKE '{$tag}%'") . "\n";
}
echo "RINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
