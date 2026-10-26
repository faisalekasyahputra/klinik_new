<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php';
require_once __DIR__ . '/_akun_demo.php'; // superadmin dan admin kab/kota demo dipinjam selama uji
date_default_timezone_set('Asia/Jakarta');
/*
 * Uji layar Aturan rekomendasi dan Tambah program (tahap 2 dan 3, migrasi 085, 26 Okt 2026):
 *   1. Hanya superadmin; admin kab/kota tidak bisa membuka maupun membuat draf; aksi tulis hanya POST.
 *   2. Tambah program: kode divalidasi dan unik, tercatat di audit, belum ditawarkan sebelum aturan terbit.
 *   3. Draf: salinan versi terbit; isian di luar daftar tetap mesin ditolak; uji coba menghitung contoh berubah.
 *   4. Terbitkan: wajib konfirmasi; versi lama jadi arsip; mesin langsung memakai versi baru (program baru
 *      ditawarkan ke warga desil 5 sampai 8 yang belum punya rumah, baris matriks baru muncul di rekomendasi awal).
 * Semua yang dibuat DIPULIHKAN (versi terbit asal, program uji dihapus), termasuk bila uji berhenti di tengah.
 * Jalankan: php docs/engineering/uji_aturan_editor.php
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('BASEPATH', __DIR__);
define('KODE_UJI', 'uji_aturan_' . date('His') . mt_rand(10, 99));
require dirname(__DIR__, 2) . '/application/libraries/Warga_ruleset.php';
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
require __DIR__ . '/_aturan_db.php';

$total = 0; $gagal = 0;
function cek($ok, $label) { global $total, $gagal; $total++; echo ($ok ? '  OK    ' : '  GAGAL ') . "$label\n"; if ( ! $ok) { $gagal++; } return (bool) $ok; }
function wajib($ok, $label) { if ( ! cek($ok, $label)) { fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); } }
function db() { return aturan_db_uji(); }
function nilai($sql, array $p = []) {
    $st = db()->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map('strval', $p)); }
    $st->execute();
    $r = $st->get_result();
    return $r ? ($r->fetch_row()[0] ?? NULL) : NULL;
}
function http($n, $path, ?array $post = NULL) {
    static $jar = [];
    $jar[$n] = $jar[$n] ?? tempnam(sys_get_temp_dir(), 'uja_');
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $jar[$n], CURLOPT_COOKIEFILE => $jar[$n],
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60]
        + ($post !== NULL ? [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
    $b = (string) curl_exec($ch);
    $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['kode' => $kode, 'body' => $b];
}
function token($n, $path) {
    $b = http($n, $path)['body'];
    return preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $b, $m) ? $m[1]
        : (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $b, $m) ? $m[1] : '');
}
function kirim($n, $halaman, $aksi, array $data) { return http($n, $aksi, ['csrf_kpkp_token' => token($n, $halaman)] + $data); }
function login($n, $email) {
    $r = http($n, 'Auth/do_login', ['csrf_kpkp_token' => token($n, 'Auth/login'), 'email' => $email, 'password' => pinjam_akun_demo($email)]);
    return $r['kode'] === 200;
}

// Keadaan awal + pemulih.
$ASAL = aturan_kode_aktif_uji();
$ID_VERSI_MAKS = (int) nilai('SELECT COALESCE(MAX(id), 0) FROM aturan_versi');
wajib($ASAL !== NULL && ! nilai("SELECT COUNT(*) FROM aturan_versi WHERE status = 'draf'"), "Versi terbit $ASAL ada dan belum ada draf lain");
register_shutdown_function(function () use ($ASAL, $ID_VERSI_MAKS) {
    db()->query("DELETE FROM aturan_versi WHERE id > $ID_VERSI_MAKS");
    $st = db()->prepare("UPDATE aturan_versi SET status = 'terbit' WHERE kode = ?"); $st->bind_param('s', $ASAL); $st->execute();
    $st = db()->prepare('DELETE FROM sf_program WHERE kode_program = ?'); $k = KODE_UJI; $st->bind_param('s', $k); $st->execute();
    echo "  (pulih: versi terbit $ASAL, versi dan program uji dihapus)\n";
});

echo "1. HANYA SUPERADMIN\n";
wajib(login('kab', 'adminkabkota@example.com'), 'Admin kab/kota demo masuk');
$r = http('kab', 'Admin_Aturan');
cek(strpos($r['body'], 'data-daftar-program') === FALSE, 'Admin kab/kota tidak bisa membuka Aturan rekomendasi');
http('kab', 'Admin_Aturan/buat_draf', ['csrf_kpkp_token' => token('kab', 'Admin_Kabkota')]);
cek( ! nilai("SELECT COUNT(*) FROM aturan_versi WHERE status = 'draf'"), 'Admin kab/kota tidak bisa membuat draf');
wajib(login('su', 'admin@klinikpkp.jatengprov.go.id'), 'Superadmin demo masuk');
$r = http('su', 'Admin_Aturan');
cek($r['kode'] === 200 && strpos($r['body'], 'data-buat-draf') !== FALSE && strpos($r['body'], $ASAL) !== FALSE, "Superadmin melihat versi terbit $ASAL dan tombol Buat draf");
cek(http('su', 'Admin_Aturan/buat_draf')['kode'] === 404 && ! nilai("SELECT COUNT(*) FROM aturan_versi WHERE status = 'draf'"), 'Buat draf lewat GET ditolak 404');

echo "\n2. TAMBAH PROGRAM\n";
$kat = http('su', 'Admin_Katalog_Program')['body'];
cek(strpos($kat, 'Admin_Katalog_Program/tambah') !== FALSE && strpos($kat, 'Admin_Aturan') !== FALSE, 'Katalog punya tombol Tambah program dan Aturan rekomendasi');
$baru = ['kode_program' => KODE_UJI, 'kategori_id' => 1, 'nama_program' => 'Program Uji ' . KODE_UJI, 'deskripsi_singkat' => 'Program sintetis uji.',
    'syarat_utama' => str_repeat('Syarat panjang per baris. ', 20), 'is_active' => 1, 'urutan' => 50];
kirim('su', 'Admin_Katalog_Program/tambah', 'Admin_Katalog_Program/simpan_baru', ['kode_program' => 'Kode Salah!'] + $baru);
cek( ! nilai("SELECT COUNT(*) FROM sf_program WHERE kode_program IN ('Kode Salah!', 'kode salah!')"), 'Kode program di luar format ditolak');
kirim('su', 'Admin_Katalog_Program/tambah', 'Admin_Katalog_Program/simpan_baru', ['kode_program' => 'flpp'] + $baru);
cek((int) nilai("SELECT COUNT(*) FROM sf_program WHERE kode_program = 'flpp'") === 1, 'Kode program yang sudah dipakai ditolak');
kirim('su', 'Admin_Katalog_Program/tambah', 'Admin_Katalog_Program/simpan_baru', $baru);
$PID = (int) nilai('SELECT id FROM sf_program WHERE kode_program = ?', [KODE_UJI]);
wajib($PID > 0, 'Program baru tersimpan');
cek(mb_strlen((string) nilai('SELECT syarat_utama FROM sf_program WHERE id = ?', [$PID])) > 300, 'Syarat utama lebih dari 300 karakter tersimpan utuh (kolom TEXT)');
cek((int) nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi = 'program_dibuat' AND objek_id = ?", [$PID]) === 1, 'Pembuatan program tercatat di audit');
cek( ! in_array(KODE_UJI, mesin_warga_uji()->route_candidates(6), TRUE), 'Program baru belum ditawarkan ke warga sebelum aturan terbit');
cek(strpos(http('su', 'Admin_Katalog_Program')['body'], 'belum ditawarkan') !== FALSE, 'Katalog menandai program baru belum ditawarkan');

echo "\n3. DRAF DAN VALIDASI\n";
kirim('su', 'Admin_Aturan', 'Admin_Aturan/buat_draf', []);
$DRAF = nilai("SELECT kode FROM aturan_versi WHERE status = 'draf'");
wajib($DRAF !== NULL, "Draf dibuat ($DRAF)");
cek((int) nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ?', [$DRAF]) === (int) nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ?', [$ASAL])
    && (int) nilai('SELECT COUNT(*) FROM aturan_matriks_baris WHERE versi = ?', [$DRAF]) === (int) nilai('SELECT COUNT(*) FROM aturan_matriks_baris WHERE versi = ?', [$ASAL]),
    'Draf berisi salinan utuh aturan program dan matriks versi terbit');
// Urutan matriks draf sebagai satu teks: penanda bahwa simpan per program tidak menggeser baris program lain.
$urutan_matriks = fn() => nilai("SELECT GROUP_CONCAT(nama_skema ORDER BY urutan SEPARATOR '|') FROM aturan_matriks_baris WHERE versi = ?", [$DRAF]);
$asal_matriks = $urutan_matriks();
$hal = http('su', 'Admin_Aturan')['body'];
cek(strpos($hal, 'data-program="' . KODE_UJI . '"') !== FALSE && strpos($hal, 'Admin_Aturan/program/' . $PID) !== FALSE,
    'Daftar aturan menampilkan kartu tiap program, termasuk program baru, dengan tautan Atur');
$hp = http('su', 'Admin_Aturan/program/' . $PID)['body'];
cek(strpos($hp, 'data-kartu-desil') !== FALSE && strpos($hp, 'data-kartu-cek') !== FALSE && strpos($hp, 'data-kartu-matriks') !== FALSE
    && strpos($hp, 'Admin_Aturan/simpan/' . $PID) !== FALSE, 'Halaman aturan program: desil, cek data, matriks, dan tombol Simpan draf');
$simpan = fn($pid, array $prog, array $baris = []) => kirim('su', 'Admin_Aturan/program/' . $pid, 'Admin_Aturan/simpan/' . $pid, ['program' => $prog, 'baris' => $baris]);
$simpan($PID, ['ikut' => 1, 'desil' => [5, 11], 'cek' => 'pembiayaan', 'urutan' => 7]);
cek( ! nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ? AND program_id = ?', [$DRAF, $PID]), 'Desil di luar 1 sampai 10 ditolak, draf tidak berubah');
$simpan($PID, ['ikut' => 1, 'desil' => [5], 'cek' => 'cek_karangan', 'urutan' => 7]);
cek( ! nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ? AND program_id = ?', [$DRAF, $PID]), 'Jenis cek yang tidak dikenal mesin ditolak');
$NAMA_BARIS = 'Program Uji ' . KODE_UJI . ' Penyewa';
$simpan($PID, ['ikut' => 1, 'desil' => [5, 6, 7, 8], 'cek' => 'pembiayaan', 'urutan' => 7],
    [['rentang_penghasilan' => 'income_2_8_8_5', 'rumah' => 'rumah_karangan', 'nama_skema' => $NAMA_BARIS]]);
cek( ! nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ? AND program_id = ?', [$DRAF, $PID]) && $urutan_matriks() === $asal_matriks,
    'Nilai kolom matriks yang tidak dikenal ditolak, aturan dan matriks tidak berubah');
$simpan($PID, ['ikut' => 1, 'desil' => [5, 6, 7, 8], 'cek' => 'pembiayaan', 'urutan' => 7],
    [['rentang_penghasilan' => 'income_2_8_8_5', 'rumah' => 'house_none_or_rent', 'nama_skema' => 'Nama Lain']]);
cek( ! nilai('SELECT COUNT(*) FROM aturan_program WHERE versi = ? AND program_id = ?', [$DRAF, $PID]), 'Baris matriks yang namanya bukan program ini ditolak');
$simpan($PID, ['ikut' => 1, 'desil' => [5, 6, 7, 8], 'cek' => 'pembiayaan', 'urutan' => 7],
    [['rentang_penghasilan' => 'income_2_8_8_5', 'rumah' => 'house_none_or_rent', 'nama_skema' => $NAMA_BARIS]]);
cek(nilai('SELECT CONCAT(desil_sasaran, "|", jenis_cek, "|", kode_alasan) FROM aturan_program WHERE versi = ? AND program_id = ?', [$DRAF, $PID])
    === '5,6,7,8|pembiayaan|SIM_PEMBIAYAAN_INCOME', 'Program baru masuk draf dengan desil 5 sampai 8 dan kode alasan umum pembiayaan');
cek($urutan_matriks() === $asal_matriks . '|' . $NAMA_BARIS, 'Baris matriks program baru ditambahkan di akhir; baris program lain tetap pada urutannya');
cek(nilai("SELECT kode_alasan FROM aturan_program a JOIN sf_program p ON p.id = a.program_id WHERE a.versi = ? AND p.kode_program = 'flpp'", [$DRAF]) === 'SIM_FLPP_INCOME',
    'Program lain tidak tersentuh (FLPP tetap dengan kode alasannya sendiri)');

// Simpan program lama tanpa perubahan: aturan dan urutan seluruh matriks tetap sama persis.
$pb = (int) nilai("SELECT id FROM sf_program WHERE kode_program = 'pb'");
$st = db()->prepare("SELECT rentang_penghasilan, desil, lahan, rumah, lingkungan, finansial, usia, keluarga, nama_skema FROM aturan_matriks_baris WHERE versi = ? AND nama_skema LIKE 'PB %' ORDER BY urutan");
$st->bind_param('s', $DRAF); $st->execute();
$baris_pb = array_map(fn($r) => array_map(fn($v) => $v ?? '', ['desil' => $r['desil'] === NULL ? [] : explode(',', $r['desil'])] + $r), $st->get_result()->fetch_all(MYSQLI_ASSOC));
$aturan_pb = nilai("SELECT CONCAT(desil_sasaran, '|', jenis_cek, '|', urutan) FROM aturan_program WHERE versi = ? AND program_id = ?", [$DRAF, $pb]);
$simpan($pb, ['ikut' => 1, 'desil' => [1, 2, 3, 4], 'cek' => 'pb_lahan', 'urutan' => 2], $baris_pb);
cek(count($baris_pb) === 9 && $urutan_matriks() === $asal_matriks . '|' . $NAMA_BARIS
    && nilai("SELECT CONCAT(desil_sasaran, '|', jenis_cek, '|', urutan) FROM aturan_program WHERE versi = ? AND program_id = ?", [$DRAF, $pb]) === $aturan_pb,
    'Menyimpan PB tanpa perubahan tidak menggeser 9 baris matriksnya maupun program lain');
cek( ! in_array(KODE_UJI, mesin_warga_uji()->route_candidates(6), TRUE), 'Draf belum berlaku: mesin masih memakai versi terbit');
$hal = http('su', 'Admin_Aturan')['body'];
cek(preg_match('/data-jumlah-berubah[^>]*>(\d+) contoh berubah/', $hal, $m) && (int) $m[1] > 0 && strpos($hal, 'Program Uji ' . KODE_UJI) !== FALSE,
    'Uji coba menghitung contoh warga yang berubah hasil (' . ($m[1] ?? '?') . ') dan menyebut program baru');
cek(strpos(http('su', 'Admin_Aturan/program/' . $PID)['body'], 'Ditawarkan (baru)') !== FALSE, 'Uji coba di halaman program menandai warga yang baru ditawari');

echo "\n4. TERBITKAN\n";
kirim('su', 'Admin_Aturan', 'Admin_Aturan/terbitkan', []);
cek(nilai('SELECT status FROM aturan_versi WHERE kode = ?', [$DRAF]) === 'draf', 'Terbitkan tanpa konfirmasi ditolak');
kirim('su', 'Admin_Aturan', 'Admin_Aturan/terbitkan', ['konfirmasi' => 1]);
cek(aturan_kode_aktif_uji() === $DRAF && nilai('SELECT status FROM aturan_versi WHERE kode = ?', [$ASAL]) === 'arsip', "Draf terbit, $ASAL menjadi arsip");
cek((int) nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi = 'aturan_diterbitkan' AND ringkasan LIKE ?", ['%' . $DRAF . '%']) === 1, 'Penerbitan tercatat di audit');
$w = mesin_warga_uji();
$warga = ['jalur_penilaian' => 'candidate_land', 'kepemilikan_rumah' => 'rent', 'rumah_lain' => '0'];
$e = $w->evaluate(KODE_UJI, $warga, ['desil_kesejahteraan' => 6, 'penghasilan_bulanan' => 4000000]);
cek(in_array(KODE_UJI, $w->route_candidates(6), TRUE) && ! in_array(KODE_UJI, $w->route_candidates(3), TRUE)
    && $e === ['status_kelayakan' => 'potential', 'reason_codes' => ['SIM_PEMBIAYAAN_INCOME']], 'Mesin langsung menawarkan program baru ke desil 6 yang menyewa, tidak ke desil 3');
$awal = mesin_matriks_uji()->preliminary(['kepemilikan_rumah' => 'rent', 'tanah_lain' => '0'], ['penghasilan_bulanan' => 4000000, 'stabilitas_pekerjaan' => 'permanent',
    'status_perkawinan' => 'single', 'birth_date' => '1995-01-01'], '2026-10-26');
cek(in_array($NAMA_BARIS, array_column($awal['items'], 'program_name'), TRUE) && $awal['versi_aturan'] === $DRAF, 'Rekomendasi awal memuat baris matriks baru dan versi baru');
$hal = http('su', 'Admin_Aturan')['body'];
cek(strpos($hal, 'data-buat-draf') !== FALSE && strpos($hal, $DRAF) !== FALSE, 'Sesudah terbit: tidak ada draf, versi terbit baru tampil');
$kat = http('su', 'Admin_Katalog_Program')['body'];
$p = strpos($kat, KODE_UJI); $mb = $p === FALSE ? '' : substr($kat, strrpos(substr($kat, 0, $p), '<tr'), 1500);
cek($mb !== '' && strpos(substr($mb, 0, strpos($mb, '</tr>')), 'belum ditawarkan') === FALSE,
    'Sesudah terbit katalog tidak lagi menandai program baru "belum ditawarkan"');

echo "\n=== $total pemeriksaan, $gagal merah ===\n";
exit($gagal ? 1 : 0);
