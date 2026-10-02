<?php
/**
 * Penjaga regresi keamanan berkas unggahan (form keamanan poin 11.1 kuota dan 11.4 pemindaian).
 * Offline: tanpa basis data dan tanpa jaringan keluar (clamd palsu hanya di 127.0.0.1).
 * Jalankan:  php tests/upload_security_test.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
require $app . '/libraries/Upload_scanner.php';
require $app . '/libraries/Upload_quota.php';
$config = []; require $app . '/config/upload_policy.php'; $POLICY = $config['upload_policy'];

// ------------------------------------------------------------------ mode pekerja (proses anak)
if (($argv[1] ?? '') === 'kuota-pekerja') {
    [, , $root, $actor, $jumlah, $mulai] = $argv;
    $p = $POLICY; $p['reservation_ttl'] = 600;
    $q = new Upload_quota(['root' => $root, 'policy' => $p]);
    while (microtime(TRUE) < (float) $mulai) { /* gerbang: semua pekerja mulai bersamaan */ }
    $ok = 0;
    for ($i = 0; $i < (int) $jumlah; $i++) {
        $err = NULL;
        if ($q->reserve($actor, ['files' => 10, 'bytes' => 10 * 1048576], 'uji', 1, bin2hex(random_bytes(8)) . '.pdf', 1000, $err)) { $ok++; }
    }
    echo $ok;
    exit(0);
}
if (($argv[1] ?? '') === 'clamd-server') {
    $srv = stream_socket_server('tcp://127.0.0.1:0', $en, $es);
    file_put_contents($argv[2], substr(strrchr(stream_socket_get_name($srv, FALSE), ':'), 1));
    $sisa = 6;
    while ($sisa-- > 0 && ($c = @stream_socket_accept($srv, 30))) {
        stream_set_timeout($c, 5);
        $perintah = ''; while (($ch = fread($c, 1)) !== FALSE && $ch !== '' && $ch !== "\0") { $perintah .= $ch; }
        $isi = '';
        while (TRUE) {
            $h = fread($c, 4); if (strlen($h) < 4) { break; }
            $n = unpack('N', $h)[1]; if ($n === 0) { break; }
            while ($n > 0) { $b = fread($c, $n); if ($b === FALSE || $b === '') { break 2; } $isi .= $b; $n -= strlen($b); }
        }
        $mode = (string) @file_get_contents($argv[2] . '.mode');
        if ($mode === 'sampah') { fwrite($c, "Halo dunia\0"); }
        elseif (strpos($isi, 'CLAMTEST') !== FALSE) { fwrite($c, "stream: Uji.Virus.Palsu FOUND\0"); }
        else { fwrite($c, "stream: OK\0"); }
        fclose($c);
    }
    exit(0);
}

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function sumber($rel) {
    global $app;
    $t = @file_get_contents($app . '/' . $rel);
    if ($t === FALSE) { throw new RuntimeException("Tidak bisa membaca application/$rel"); }
    return $t;
}
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_unggah_' . bin2hex(random_bytes(4));
mkdir($tmp, 0700, TRUE);
register_shutdown_function(function () use ($tmp) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($tmp);
});
function berkas($nama, $isi) { global $tmp; $p = $tmp . DIRECTORY_SEPARATOR . $nama; file_put_contents($p, $isi); return $p; }

// ------------------------------------------------------------------ bahan uji
check(function_exists('imagecreatetruecolor'), 'Uji butuh ekstensi GD');
$im = imagecreatetruecolor(40, 30); ob_start(); imagejpeg($im); $JPG = ob_get_clean(); ob_start(); imagepng($im); $PNG = ob_get_clean();
$PDF_BERSIH = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R/OpenAction[3 0 R/Fit]>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
function pdf_objstm($isi) { $z = gzcompress($isi); return "%PDF-1.5\n5 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Filter /FlateDecode /Length " . strlen($z) . " >>\nstream\n" . $z . "\nendstream\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF\n"; }
function xlsx($tambahan = [], $lewati = []) {
    global $tmp; $p = $tmp . DIRECTORY_SEPARATOR . 'x_' . bin2hex(random_bytes(4)) . '.xlsx';
    $z = new ZipArchive(); $z->open($p, ZipArchive::CREATE);
    $z->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
    $z->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook/>');
    $z->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet><sheetData/></worksheet>');
    foreach ($tambahan as $n => $v) { $z->addFromString($n, $v); }
    $z->close(); return $p;
}
$S = new Upload_scanner(['clamd' => '']);
$kode = function ($path, $ext) use ($S) { $r = $S->scan($path, $ext); return $r['ok'] ? 'OK' : $r['code']; };

// ------------------------------------------------------------------ 1. Berkas sah lolos (tanpa positif palsu)
check($kode(berkas('a.jpg', $JPG), 'jpg') === 'OK', 'JPEG sah harus lolos');
check($kode(berkas('a.png', $PNG), 'png') === 'OK', 'PNG sah harus lolos');
check($kode(berkas('a.pdf', $PDF_BERSIH), 'pdf') === 'OK', 'PDF biasa (termasuk /OpenAction tujuan halaman) harus lolos');
check($kode(xlsx(), 'xlsx') === 'OK', 'XLSX sah harus lolos');
$acak = random_bytes(3 * 1048576);
$pdf_biner = "%PDF-1.4\n4 0 obj<</Length 9>>stream\n" . $acak . '/JS(/JavaScript/Launch' . "\nendstream\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
check($kode(berkas('b.pdf', $pdf_biner), 'pdf') === 'OK', 'Nama seperti /JS di DALAM isi stream biner bukan konten aktif (tidak boleh positif palsu)');
$sha = $S->scan(berkas('c.jpg', $JPG), 'jpg')['sha256'];
check($sha === hash('sha256', $JPG), 'Hasil pemindaian harus memuat SHA-256 berkas');
// Positif palsu: data biner acak sebesar foto asli TIDAK boleh membuat berkas sah ditolak (dulu `<?=` polos ~18% per 3 MB).
$salah_tolak = 0;
for ($i = 0; $i < 25; $i++) { if ($kode(berkas('rnd.jpg', $JPG . random_bytes(2 * 1048576)), 'jpg') !== 'OK') { $salah_tolak++; } }
check($salah_tolak === 0, "Berkas biner acak sah salah ditolak $salah_tolak dari 25 kali (positif palsu)");
check($kode(berkas('sh1.jpg', $JPG . '<?=$_GET[c]?>'), 'jpg') === 'kode_php', 'Short echo tag dengan variabel di gambar harus ditolak');
check($kode(berkas('sh2.jpg', $JPG . '<?= system("id")'), 'jpg') === 'kode_php', 'Short echo tag dengan fungsi eksekusi harus ditolak');

// ------------------------------------------------------------------ 2. Berkas berbahaya ditolak
check($kode(berkas('m1.jpg', $JPG . '<?php system($_GET["c"]); ?>'), 'jpg') === 'kode_php', 'Kode PHP yang ditempel di gambar harus ditolak');
check($kode(berkas('m2.png', $PNG . '<?= `id` ?>'), 'png') === 'kode_php', 'Short tag PHP echo di gambar harus ditolak');
check($kode(berkas('m3.jpg', $JPG . '<SCRIPT src=//x/y.js></script>'), 'jpg') === 'skrip_html', 'Skrip HTML yang ditempel di gambar harus ditolak');
check($kode(berkas('m4.pdf', $PDF_BERSIH . 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'), 'pdf') === 'eicar', 'String uji antivirus EICAR harus ditolak');
check($kode(berkas('m5.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert(1))>>>>endobj\n%%EOF"), 'pdf') === 'pdf_aktif', 'PDF dengan JavaScript harus ditolak');
check($kode(berkas('m6.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog/OpenAction<</S/J#61vaScript/J#53(x)>>>>endobj\n%%EOF"), 'pdf') === 'pdf_aktif', 'Nama PDF yang disamarkan dengan #XX harus tetap ketahuan');
check($kode(berkas('m7.pdf', pdf_objstm('4 0 << /S /JavaScript /JS (app.alert(1)) >>')), 'pdf') === 'pdf_aktif', 'JavaScript yang disembunyikan di ObjStm terkompresi harus ketahuan');
check($kode(berkas('m8.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R/Encrypt 9 0 R>>\n%%EOF"), 'pdf') === 'pdf_terenkripsi', 'PDF terenkripsi tidak dapat dipindai dan harus ditolak');
check($kode(berkas('m9.pdf', "%PDF-1.4\n1 0 obj<</Type/Filespec/EF<</F 2 0 R>>/Type/EmbeddedFile>>endobj\n%%EOF"), 'pdf') === 'pdf_aktif', 'Lampiran tertanam di PDF harus ditolak');
check($kode(berkas('m10.pdf', "%PDF-1.5\n5 0 obj\n<< /Type /ObjStm /N 1 /First 4 /Length 10 >>\nstream\nabcdefghij\nendstream\nendobj\n%%EOF"), 'pdf') === 'pdf_tak_dapat_dipindai', 'ObjStm yang tidak bisa didekompresi = tidak bisa dipastikan aman, ditolak');
check($kode(berkas('m11.pdf', 'bukan pdf sama sekali'), 'pdf') === 'tipe_tak_sesuai', 'Berkas berekstensi pdf tanpa isi PDF harus ditolak');
check($kode(berkas('m12.jpg', $PNG), 'jpg') === 'tipe_tak_sesuai', 'PNG yang diberi ekstensi jpg harus ditolak');
check($kode(berkas('m13.png', 'GIF89a bukan gambar'), 'png') === 'tipe_tak_sesuai', 'Berkas bukan gambar berekstensi png harus ditolak');
check($kode(berkas('m14.txt', "#!/bin/sh\nrm -rf /\n"), 'txt') === 'shebang', 'Skrip shell harus ditolak');
check($kode(berkas('m15.txt', ''), 'txt') === 'kosong', 'Berkas kosong harus ditolak');
check($kode($tmp . '/tidak_ada.pdf', 'pdf') === 'kosong', 'Berkas yang tidak ada harus ditolak');
check($kode(xlsx(['xl/vbaProject.bin' => 'x']), 'xlsx') === 'excel_berbahaya', 'XLSX dengan makro VBA harus ditolak');
check($kode(xlsx(['xl/embeddings/oleObject1.bin' => 'x']), 'xlsx') === 'excel_berbahaya', 'XLSX dengan objek OLE tertanam harus ditolak');
check($kode(xlsx(['xl/externalLinks/externalLink1.xml' => '<x/>']), 'xlsx') === 'excel_berbahaya', 'XLSX dengan tautan luar harus ditolak');
check($kode(xlsx(['xl/sharedStrings.xml' => '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY a "b">]><sst/>']), 'xlsx') === 'excel_berbahaya', 'XLSX dengan DOCTYPE/ENTITY (XXE, billion laughs) harus ditolak');
check($kode(xlsx(['xl/worksheets/_rels/sheet1.xml.rels' => '<Relationships><Relationship Id="r1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/oleObject" Target="file:///x" TargetMode="External"/></Relationships>']), 'xlsx') === 'excel_berbahaya', 'Relasi eksternal berbahaya harus ditolak');
check($kode(xlsx(['xl/worksheets/_rels/sheet1.xml.rels' => '<Relationships><Relationship Id="r1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://contoh.id" TargetMode="External"/></Relationships>']), 'xlsx') === 'OK', 'Hyperlink eksternal biasa di XLSX sah dan tidak boleh ditolak');
check($kode(xlsx(['../luar.txt' => 'x']), 'xlsx') === 'excel_berbahaya', 'Entri zip dengan ../ harus ditolak');
check($kode(xlsx(['xl/loader.php' => 'x']), 'xlsx') === 'excel_berbahaya', 'Entri .php di dalam XLSX harus ditolak');
check($kode(xlsx(['xl/media/c.xml' => '<a><?php echo 1;?></a>']), 'xlsx') === 'kode_php', 'Kode PHP di dalam entri XLSX harus ditolak');
check($kode(berkas('n.xlsx', 'PK bukan zip'), 'xlsx') === 'tipe_tak_sesuai', 'Berkas berekstensi xlsx yang bukan Excel harus ditolak');
check($kode(berkas('o.xls', hex2bin('d0cf11e0a1b11ae1') . str_repeat("\0", 64) . "V\0B\0A\0"), 'xls') === 'excel_berbahaya', 'XLS dengan penyimpanan VBA harus ditolak');
check($kode(berkas('o2.xls', hex2bin('d0cf11e0a1b11ae1') . str_repeat("\0", 64)), 'xls') === 'OK', 'XLS tanpa makro harus lolos');

// Batas dari kebijakan (bom zip dan bom dimensi gambar), diuji dengan kebijakan kecil.
$kecil = $POLICY; $kecil['scan']['zip_max_uncompressed'] = 200000; $kecil['scan']['zip_max_ratio'] = 50; $kecil['scan']['image_max_pixels'] = 100;
$Sk = new Upload_scanner(['policy' => $kecil, 'clamd' => '']);
check($Sk->scan(xlsx(['xl/besar.bin' => str_repeat('A', 300000)]), 'xlsx')['code'] === 'excel_berbahaya', 'Zip yang mengembang melewati batas total harus ditolak');
$rasio = $kecil; $rasio['scan']['zip_max_uncompressed'] = 50 * 1048576;
$Sr = new Upload_scanner(['policy' => $rasio, 'clamd' => '']);
check($Sr->scan(xlsx(['xl/besar.bin' => str_repeat('A', 3 * 1048576)]), 'xlsx')['code'] === 'excel_berbahaya', 'Zip dengan rasio kompresi ekstrem harus ditolak (bom zip)');
check($Sk->scan(berkas('g.png', $PNG), 'png')['code'] === 'gambar_terlalu_besar', 'Gambar dengan jumlah piksel di atas batas (bom dekompresi) harus ditolak');

// ------------------------------------------------------------------ 3. ClamAV (clamd) dengan server palsu
$berkas_port = $tmp . DIRECTORY_SEPARATOR . 'clamd.port';
$server = proc_open([PHP_BINARY, __FILE__, 'clamd-server', $berkas_port], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipa_srv);
for ($i = 0; $i < 100 && ! is_file($berkas_port); $i++) { usleep(50000); }
check(is_file($berkas_port), 'Server clamd palsu gagal berjalan');
$port = trim((string) file_get_contents($berkas_port));
$Sc = new Upload_scanner(['clamd' => "tcp://127.0.0.1:$port"]);
check($Sc->scan(berkas('cl1.jpg', $JPG), 'jpg')['ok'] === TRUE, 'Berkas bersih harus lolos bila clamd menjawab OK');
$r = $Sc->scan(berkas('cl2.jpg', $JPG . 'CLAMTEST'), 'jpg');
check( ! $r['ok'] && $r['code'] === 'antivirus' && strpos($r['detail'], 'Uji.Virus.Palsu') !== FALSE, 'Temuan clamd harus menolak dengan kode antivirus dan nama ancaman');
$besar = berkas('cl3.pdf', $PDF_BERSIH . str_repeat('x', 200000));
check($Sc->scan($besar, 'pdf')['ok'] === TRUE, 'Berkas > 64 KB harus terkirim utuh lewat beberapa potongan INSTREAM');
file_put_contents($berkas_port . '.mode', 'sampah');
check($Sc->scan(berkas('cl4.jpg', $JPG), 'jpg')['code'] === 'pemindai_tak_tersedia', 'Balasan clamd yang tak dikenali harus menolak (fail-closed)');
$mati = new Upload_scanner(['clamd' => 'tcp://127.0.0.1:1']);
check($mati->scan(berkas('cl5.jpg', $JPG), 'jpg')['code'] === 'pemindai_tak_tersedia', 'clamd tidak terjangkau harus menolak (fail-closed), bukan meloloskan');
$salah = new Upload_scanner(['clamd' => 'http://contoh.id']);
check($salah->scan(berkas('cl6.jpg', $JPG), 'jpg')['code'] === 'pemindai_tak_tersedia', 'Alamat clamd selain unix:// atau tcp:// harus ditolak');
check((new Upload_scanner(['clamd' => '']))->scan(berkas('cl7.jpg', $JPG), 'jpg')['ok'] === TRUE, 'Tanpa CLAMD_ADDRESS pemindai bawaan saja yang berjalan');
proc_terminate($server); foreach ($pipa_srv as $p) { @fclose($p); } proc_close($server);

// ------------------------------------------------------------------ 4. Kuota per pengguna (11.1)
$kuota_policy = $POLICY; $kuota_policy['quota']['warga'] = ['files' => 3, 'bytes' => 100]; $kuota_policy['reservation_ttl'] = 1;
$root = $tmp . DIRECTORY_SEPARATOR . 'priv' . DIRECTORY_SEPARATOR; mkdir($root, 0700, TRUE);
$Q = new Upload_quota(['root' => $root, 'policy' => $kuota_policy]);
function simpan($root, $domain, $owner, $nama, $isi) { $d = $root . $domain . DIRECTORY_SEPARATOR . $owner . DIRECTORY_SEPARATOR; @mkdir($d, 0700, TRUE); file_put_contents($d . $nama, $isi); }

$id = $Q->identify(5, 'warga', '203.0.113.1');
check($id['actor'] === 'u5' && $id['limits']['files'] === 3, 'Pengguna login memakai batas perannya');
check($Q->identify(5, 'peran_baru', '203.0.113.1')['limits'] === $POLICY['quota']['_default'], 'Peran tak dikenal memakai batas bawaan');
$a1 = $Q->identify(0, NULL, '203.0.113.1'); $a2 = $Q->identify(0, NULL, '203.0.113.2');
check($a1['limits'] === $POLICY['quota']['anon'] && $a1['actor'] !== $a2['actor'] && strpos($a1['actor'], '203') === FALSE, 'Tamu diikat ke IP (di-hash) dan memakai batas anon');

$err = NULL;
foreach (['a1.pdf' => 'x', 'a2.pdf' => 'x', 'a3.pdf' => 'x'] as $n => $isi) {
    simpan($root, 'warga_assessment', 7, $n, $isi);
    check($Q->reserve('u5', $id['limits'], 'warga_assessment', 7, $n, 1, $err), "Berkas $n masih di bawah kuota");
}
check(array_values($Q->usage('u5')) === [3, 3], 'Pemakaian = 3 berkas, 3 byte');
simpan($root, 'warga_assessment', 7, 'a4.pdf', 'x');
check( ! $Q->reserve('u5', $id['limits'], 'warga_assessment', 7, 'a4.pdf', 1, $err) && strpos($err, 'maksimal 3 berkas') !== FALSE, 'Berkas ke-4 harus ditolak karena jumlah');
check(array_values($Q->usage('u5')) === [3, 3], 'Penolakan tidak boleh meninggalkan penanda');

// Kuota ukuran: dihitung dari ukuran SEBENARNYA di disk, bukan yang dilaporkan.
$Q2 = new Upload_quota(['root' => $root, 'policy' => $kuota_policy]);
simpan($root, 'kemitraan', 9, 'b1.pdf', str_repeat('x', 90));
check($Q2->reserve('u6', $id['limits'], 'kemitraan', 9, 'b1.pdf', 1, $err), 'Berkas 90 byte (dilaporkan 1) tercatat');
check(array_values($Q2->usage('u6'))[1] === 90, 'Ukuran yang dihitung adalah ukuran nyata di disk (90), bukan yang dilaporkan (1)');
check( ! $Q2->reserve('u6', $id['limits'], 'kemitraan', 9, 'b2.pdf', 20, $err) && strpos($err, 'total maksimal') !== FALSE, 'Berkas yang membuat total melewati batas ukuran harus ditolak');

// Swa-pulih: berkas dihapus dari disk (di titik hapus mana pun) membebaskan kuota, tanpa kait penghapusan.
@unlink($root . 'kemitraan/9/b1.pdf');
foreach ((array) glob($root . '_pemilik/u6/kemitraan~9~b1.pdf~*') as $m) { touch($m, time() - 30); }   // lewati masa pemesanan
check(array_values($Q2->usage('u6')) === [0, 0], 'Berkas yang dihapus dari disk harus membebaskan kuota (penanda basi dibuang)');
check($Q2->reserve('u6', $id['limits'], 'kemitraan', 9, 'b3.pdf', 20, $err), 'Sesudah dibebaskan, unggahan baru diterima');

// Pemesanan yang belum disusul berkasnya tetap dihitung sebentar (mencegah balapan), lalu dibatalkan.
check(array_values($Q2->usage('u6')) === [1, 20], 'Pemesanan aktif dihitung dengan ukuran yang dilaporkan');
$Q2->release('u6', 'kemitraan', 9, 'b3.pdf');
check(array_values($Q2->usage('u6')) === [0, 0], 'release() membatalkan pemesanan');

// Tamu: jendela waktu.
$anon = $Q->identify(0, NULL, '198.51.100.7');
$lim_anon = ['files' => 2, 'bytes' => 1000, 'window' => 60];
simpan($root, 'aduan', 1, 'c1.pdf', 'x'); simpan($root, 'aduan', 2, 'c2.pdf', 'x');
check($Q->reserve($anon['actor'], $lim_anon, 'aduan', 1, 'c1.pdf', 1, $err) && $Q->reserve($anon['actor'], $lim_anon, 'aduan', 2, 'c2.pdf', 1, $err), 'Tamu boleh 2 berkas dalam jendela');
check( ! $Q->reserve($anon['actor'], $lim_anon, 'aduan', 3, 'c3.pdf', 1, $err), 'Tamu ke-3 dalam jendela ditolak');
foreach ((array) glob($root . '_pemilik/' . $anon['actor'] . '/*') as $m) { touch($m, time() - 120); }
check($Q->reserve($anon['actor'], $lim_anon, 'aduan', 3, 'c3.pdf', 1, $err), 'Sesudah jendela berlalu, penanda lama gugur dan tamu boleh lagi');

// Keamanan jalur dan pembersihan akun.
$Q->release('u5', 'warga_assessment', 7, 'a4.pdf');
$Q->reserve('u5', ['files' => 99, 'bytes' => 99999], '../../etc', '..', 'passwd', 1, $err);
foreach ((array) glob($root . '_pemilik/u5/*') as $f) {
    check(strpos(basename($f), '..') === FALSE && strpos(basename($f), '/') === FALSE, 'Nama domain/pemilik dengan ../ tidak boleh lolos ke penanda: ' . basename($f));
}
check(is_dir($root . '_pemilik/u5'), 'Buku kuota u5 ada');
$Q->forget('u5');
check( ! is_dir($root . '_pemilik/u5'), 'forget() menghapus seluruh buku kuota akun (dipanggil saat akun dihapus)');

// Konkurensi: 8 proses x 5 percobaan pada satu pengguna dengan batas 10 berkas; yang lolos harus PERSIS 10.
$rk = $tmp . DIRECTORY_SEPARATOR . 'konkuren' . DIRECTORY_SEPARATOR; mkdir($rk, 0700, TRUE);
$mulai = microtime(TRUE) + 1.5; $anak = [];
for ($i = 0; $i < 8; $i++) {
    $p = proc_open([PHP_BINARY, __FILE__, 'kuota-pekerja', $rk, 'u99', '5', sprintf('%.6F', $mulai)], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp);
    $anak[] = [$p, $pp];
}
$lolos = 0;
foreach ($anak as [$p, $pp]) { $lolos += (int) stream_get_contents($pp[1]); fclose($pp[1]); fclose($pp[2]); proc_close($p); }
check($lolos === 10, "Pemesanan serentak harus lolos PERSIS 10 dari 40 percobaan (batas 10), dapat $lolos");

// ------------------------------------------------------------------ 5. Kebijakan
$peran = ['warga', 'mahasiswa', 'pengembang', 'admin', 'admin_kabkota', 'admin_bidang', 'anon', '_default'];
foreach ($peran as $r) {
    check(isset($POLICY['quota'][$r]) && $POLICY['quota'][$r]['files'] > 0 && $POLICY['quota'][$r]['bytes'] > 0, "Kuota peran $r harus terdefinisi");
}
check( ! empty($POLICY['quota']['anon']['window']), 'Kuota tamu harus berjendela waktu');
check($POLICY['quota']['warga']['bytes'] < $POLICY['quota']['admin']['bytes'], 'Kuota admin lebih longgar daripada warga');

// ------------------------------------------------------------------ 6. Pemasangan: tidak ada jalur unggah yang lolos
$my = sumber('core/MY_Controller.php');
check(preg_match('/function store_private_upload\(.*?\R    \}\R/s', $my, $m) === 1, 'store_private_upload tidak ditemukan');
$isi = $m[0];
$p_scan = strpos($isi, 'scan_uploaded_file('); $p_kuota = strpos($isi, 'reserve_upload_quota('); $p_pindah = strpos($isi, 'move_uploaded_file(');
check($p_scan !== FALSE && $p_kuota !== FALSE && $p_pindah !== FALSE && $p_scan < $p_kuota && $p_kuota < $p_pindah, 'store_private_upload harus memindai, lalu memesan kuota, baru memindahkan berkas');
check(strpos($isi, 'release_upload_quota(') !== FALSE, 'Kegagalan memindahkan berkas harus melepas pemesanan kuota');
check(strpos($my, "'berkas_berbahaya'") !== FALSE && strpos($my, 'security_alert->raise(') !== FALSE, 'Berkas ditolak harus menjadi peringatan keamanan');
check(preg_match('/function scan_uploaded_file.*?catch \(Throwable.*?pemindai_tak_tersedia/s', $my) === 1, 'Galat pemindai harus menolak berkas (fail-closed)');

// Setiap controller penerima unggahan wajib memakai pemindaian (langsung atau lewat store_private_upload).
$penerima = []; $tanpa_scan = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app . '/controllers', FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() !== 'php') { continue; }
    $s = file_get_contents($f->getPathname());
    if (preg_match('/move_uploaded_file\(|->do_upload\(|store_private_upload\(|\$_FILES\[[^\]]+\]\[\'tmp_name\'\]|\$file\[\'tmp_name\'\]/', $s)) {
        $penerima[] = $f->getFilename();
        if (strpos($s, 'scan_uploaded_file(') === FALSE && strpos($s, 'store_private_upload(') === FALSE) { $tanpa_scan[] = $f->getFilename(); }
    }
}
check(count($penerima) >= 8, 'Jumlah controller penerima unggahan yang ditemukan terlalu sedikit (' . count($penerima) . ')');
check($tanpa_scan === [], 'Controller penerima unggahan tanpa pemindaian: ' . implode(', ', $tanpa_scan));
foreach (['Pengembang', 'KemitraanPortal', 'Admin_Psu', 'Admin_Katalog_Program', 'Admin_Content'] as $c) {
    check(strpos(sumber("controllers/$c.php"), 'scan_uploaded_file(') !== FALSE, "$c harus memanggil scan_uploaded_file()");
}
check(strpos(sumber('controllers/Pengembang.php'), 'reserve_upload_quota(') !== FALSE, 'Pengembang (unggah berkas ganda) harus memesan kuota sendiri');
check(strpos(sumber('models/User_model.php'), 'upload_quota->forget(') !== FALSE, 'Penghapusan akun harus membersihkan buku kuota');

// Regresi: kolom berkas tiap dokumen SRP2 harus ada di allowlist Input_guard, kalau tidak unggahan pengembang ditolak 400.
require_once $app . '/helpers/srp2_helper.php';
$config = []; require $app . '/config/input_validation.php'; $boleh = array_flip($config['input_allowed_fields']);
$hilang = array_values(array_filter(array_keys(srp2_dokumen_persyaratan()), function ($k) use ($boleh) { return ! isset($boleh[$k]); }));
check($hilang === [], 'Kolom berkas SRP2 belum ada di allowlist Input_guard: ' . implode(', ', $hilang));

echo "upload_security_test: OK ($total pemeriksaan; 8 proses serentak memesan tepat 10 dari 40)\n";
