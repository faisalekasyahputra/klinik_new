<?php
/**
 * Uji basis data untuk siklus hidup informasi yang dikecualikan (form keamanan poin 7.3):
 * retensi, penghapusan akun, cakupan ekspor. Berjalan terhadap MySQL nyata dengan data uji bertanda
 * unik yang dibersihkan sesudahnya. Env: DB_HOST (127.0.0.1), DB_USER (root), DB_PASS, DB_NAME (klinikpkp).
 * Tanpa DB: gagal, kecuali UJI_DB_BOLEH_LEWATI=1.
 *
 * PERINGATAN: uji ini MENJALANKAN penyapu retensi sungguhan (menghapus SEMUA entri kedaluwarsa di tabel
 * yang disapu, bukan hanya data ujinya) dan menulis/menghapus baris uji. Karena itu ia hanya berjalan bila
 * UJI_DB_BOLEH_TULIS=1, dan TIDAK boleh diarahkan ke database production.
 *   UJI_DB_BOLEH_TULIS=1 php tests/data_lifecycle_db_test.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
$config = []; require $app . '/config/data_lifecycle.php'; $LC = $config['data_lifecycle'];
require $app . '/libraries/Penyapu_retensi.php';
require $app . '/libraries/Data_erasure.php';

class UjiHasil {
    private $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function row_array() { return $this->rows[0] ?? NULL; }
    public function result_array() { return $this->rows; }
}
class UjiDb {
    public $m; private $aff = 0;
    public function __construct($m) { $this->m = $m; }
    public function query($sql, $binds = []) {
        $st = $this->m->prepare($sql);
        if ( ! $st) { throw new RuntimeException('Kueri gagal disiapkan: ' . $this->m->error . ' :: ' . $sql); }
        if ($binds) { $st->bind_param(str_repeat('s', count($binds)), ...array_map(function ($b) { return $b === NULL ? NULL : (string) $b; }, $binds)); }
        if ( ! $st->execute()) { throw new RuntimeException('Kueri gagal: ' . $st->error); }
        $this->aff = $st->affected_rows;
        $res = $st->get_result();
        if ($res instanceof mysqli_result) { return new UjiHasil($res->fetch_all(MYSQLI_ASSOC)); }
        return TRUE;
    }
    public function affected_rows() { return $this->aff; }
}

if (getenv('UJI_DB_BOLEH_TULIS') !== '1') {
    echo "data_lifecycle_db_test: DILEWATI (set UJI_DB_BOLEH_TULIS=1; uji ini menjalankan penyapu retensi sungguhan, jangan arahkan ke production)\n";
    exit(0);
}
mysqli_report(MYSQLI_REPORT_OFF);
$m = @new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'klinikpkp');
if ($m->connect_errno) {
    if (getenv('UJI_DB_BOLEH_LEWATI') === '1') { echo "data_lifecycle_db_test: DILEWATI (tidak ada koneksi MySQL)\n"; exit(0); }
    fwrite(STDERR, "data_lifecycle_db_test: GAGAL, MySQL tidak terhubung (set UJI_DB_BOLEH_LEWATI=1 untuk melewati)\n");
    exit(1);
}
$m->set_charset('utf8mb4');
$db = new UjiDb($m);

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function q($sql, $binds = []) { global $db; return $db->query($sql, $binds); }
function satu($sql, $binds = []) { $r = q($sql, $binds); return $r->row_array(); }
function jumlah($sql, $binds = []) { return (int) (satu('SELECT COUNT(*) AS n FROM (' . $sql . ') t', $binds)['n'] ?? -1); }

$tag = 'uji_yl_' . bin2hex(random_bytes(4));
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tag;
mkdir($tmp . '/priv', 0700, TRUE); mkdir($tmp . '/app/logs', 0700, TRUE);
$bersih = function () use ($tag, $tmp, $m) {
    $m->query("DELETE FROM sf_penilaian_perumahan WHERE housing_status_code = '$tag'");
    $m->query("DELETE FROM sf_rekaman_simperum WHERE nik_lookup_hash LIKE '{$tag}%'");
    $m->query("DELETE FROM sys_rate_limits WHERE limit_key LIKE '{$tag}%'");
    $m->query("DELETE FROM sys_push_subscriptions WHERE endpoint_hash LIKE '{$tag}%'");
    $m->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal = '$tag'");
    $m->query("DELETE FROM forum_diskusi WHERE judul_topik = '$tag'");
    $m->query("DELETE FROM sys_jejak_audit WHERE ringkasan LIKE '%{$tag}%' OR actor_email LIKE '%{$tag}%' OR aksi = 'uji_yl'");
    $m->query("DELETE FROM usr_users WHERE email LIKE '%{$tag}%'");
    if (is_dir($tmp)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($tmp);
    }
};
register_shutdown_function($bersih);

// ------------------------------------------------------------------ 1. Skema nyata vs kebijakan penghapusan
$fk = [];
$r = q("SELECT k.TABLE_NAME t, k.COLUMN_NAME c, r.DELETE_RULE d FROM information_schema.KEY_COLUMN_USAGE k
        JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
        WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = 'usr_users'");
foreach ($r->result_array() as $b) { $fk[$b['t'] . '.' . $b['c']] = $b['d'] === 'CASCADE' ? 'cascade' : ($b['d'] === 'SET NULL' ? 'set_null' : strtolower($b['d'])); }
$policy = array_map(function ($v) { return $v[0]; }, $LC['fk_usr_users']);
check(count($fk) >= 20, 'Jumlah FK ke usr_users yang ditemukan terlalu sedikit (' . count($fk) . ')');
$hilang = array_diff_key($fk, $policy);
check($hilang === [], 'Kolom yang menunjuk usr_users TANPA kebijakan penghapusan di config/data_lifecycle.php: ' . implode(', ', array_keys($hilang)));
$basi = array_diff_key($policy, $fk);
check($basi === [], 'Kebijakan menunjuk kolom yang tidak ada di skema: ' . implode(', ', array_keys($basi)));
foreach ($fk as $kol => $aturan) { check($policy[$kol] === $aturan, "Kebijakan $kol ('{$policy[$kol]}') tidak sesuai aturan skema nyata ('$aturan')"); }
foreach ($LC['fk_usr_users'] as $kol => [$aturan, $alasan]) { check(strlen(trim($alasan)) >= 8, "Kebijakan $kol wajib beralasan"); }

// Kolom pemilik tanpa kunci asing harus ditangani eksplisit.
$tanpa = [];
foreach (q("SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'user_id'")->result_array() as $b) {
    $kol = $b['t'] . '.' . $b['c'];
    if ( ! isset($fk[$kol])) { $tanpa[] = $kol; }
}
$tak_tercatat = array_diff($tanpa, array_keys($LC['kolom_tanpa_fk']));
check($tak_tercatat === [], 'Kolom user_id tanpa kunci asing dan tanpa penanganan tercatat: ' . implode(', ', $tak_tercatat));

// Penghapusan akun menangani kolom tanpa FK secara eksplisit di kode.
$um = file_get_contents($app . '/models/User_model.php');
check(strpos($um, "update('forum_komentar'") !== FALSE && strpos($um, "update('forum_diskusi'") !== FALSE && strpos($um, "delete('forum_likes')") !== FALSE, 'User_model harus menangani forum_komentar, forum_diskusi, forum_likes');
check(strpos($um, "'email_user' => 'akun-dihapus@invalid'") !== FALSE, 'Surel pengirim di forum_diskusi harus disamarkan saat akun dihapus');
check(strpos($um, 'data_erasure->sapu_berkas(') !== FALSE && strpos($um, 'data_erasure->samarkan_audit(') !== FALSE && strpos($um, 'Data_erasure::pseudonim_surel(') !== FALSE, 'delete_user_account harus memakai Data_erasure (sapu berkas, samarkan audit, pseudonim)');
check(strpos($um, 'upload_quota->forget(') !== FALSE, 'Hapus akun harus membersihkan buku kuota unggahan');

// ------------------------------------------------------------------ 2. Cakupan ekspor vs skema
$diekspor = $LC['ekspor_akun']['tabel']; $dikecualikan = array_keys($LC['ekspor_akun']['dikecualikan']);
$pemilik = [];
foreach (q("SELECT TABLE_NAME t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'user_id'")->result_array() as $b) { $pemilik[$b['t']] = TRUE; }
foreach (array_keys($pemilik) as $tabel) {
    check(in_array($tabel, $diekspor, TRUE) || in_array($tabel, $dikecualikan, TRUE), "Tabel milik akun '$tabel' tidak ada di ekspor data akun dan tidak dikecualikan dengan alasan");
}
foreach ($diekspor as $tabel) { check(isset($pemilik[$tabel]), "Tabel ekspor '$tabel' tidak punya kolom user_id di skema"); }
foreach ($LC['ekspor_akun']['dikecualikan'] as $tabel => $alasan) { check(isset($pemilik[$tabel]) && strlen(trim($alasan)) >= 15, "Pengecualian ekspor '$tabel' harus tabel milik akun dan beralasan"); }
foreach ($diekspor as $tabel) { check(strpos($um, "'$tabel'") !== FALSE, "User_model::export_account_data tidak memuat tabel '$tabel' yang dijanjikan kebijakan"); }

// ------------------------------------------------------------------ 3. Penyapu retensi pada tabel nyata
$hari = function ($n) { return date('Y-m-d H:i:s', time() - $n * 86400); };
$pol = $LC['retensi'];
$sn = function ($nama, $exp) use ($tag) {
    q("INSERT INTO sf_rekaman_simperum (nik_lookup_hash, source_mode, response_status, fetched_at, expires_at, created_at) VALUES (?, 'simulation', 'found', ?, ?, NOW())",
      [str_pad($tag . $nama, 64, '0'), $exp, $exp]);
    return (int) satu('SELECT id FROM sf_rekaman_simperum WHERE nik_lookup_hash = ?', [str_pad($tag . $nama, 64, '0')])['id'];
};
$s_lama = $sn('a', $hari(10)); $s_tenggang = $sn('b', $hari(3)); $s_arsip = $sn('c', $hari(10)); $s_draf = $sn('d', $hari(10)); $s_segar = $sn('e', date('Y-m-d H:i:s', time() + 86400));
q("INSERT INTO sf_penilaian_perumahan (status, simperum_snapshot_id, housing_status_code) VALUES ('submitted', ?, ?)", [$s_arsip, $tag]);
q("INSERT INTO sf_penilaian_perumahan (status, simperum_snapshot_id, housing_status_code) VALUES ('draft', ?, ?)", [$s_draf, $tag]);

q('INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES (?, ?, 3), (?, NOW(), 3)', [$tag . 'lama', $hari(5), $tag . 'baru']);
q('INSERT INTO usr_users (email, email_token, email_token_expiry) VALUES (?, ?, ?), (?, ?, ?)', ["a_{$tag}@contoh.test", 'tokenlama', $hari(3), "b_{$tag}@contoh.test", 'tokenbaru', date('Y-m-d H:i:s', time() + 3600)]);
$uid_a = (int) satu('SELECT id FROM usr_users WHERE email = ?', ["a_{$tag}@contoh.test"])['id'];
$uid_b = (int) satu('SELECT id FROM usr_users WHERE email = ?', ["b_{$tag}@contoh.test"])['id'];
$ps = function ($nama, $aktif, $umur) use ($tag, $uid_b) {
    q("INSERT INTO sys_push_subscriptions (user_id, endpoint_hash, endpoint, public_key, auth_token, aktif, created_at, updated_at) VALUES (?, ?, 'https://push.example/x', 'k', 'a', ?, ?, ?)",
      [$uid_b, str_pad($tag . $nama, 64, '0'), $aktif, $umur, $umur]);
};
$ps('mati_lama', 0, $hari($pol['langganan_push_nonaktif_hari'] + 10)); $ps('mati_baru', 0, $hari(2)); $ps('hidup_lama', 1, $hari(400));
q("INSERT INTO sys_jejak_audit (aksi, ringkasan, created_at) VALUES ('uji_yl', ?, ?), ('uji_yl', ?, ?)", ["audit sangat lama $tag", $hari($pol['jejak_audit_hari'] + 30), "audit baru $tag", $hari(1)]);
// log: satu tua, satu baru, satu bernama lain
$logdir = $tmp . '/app/logs/';
file_put_contents($logdir . 'log-2020-01-01.php', 'x'); touch($logdir . 'log-2020-01-01.php', time() - ($pol['log_aplikasi_hari'] + 5) * 86400);
file_put_contents($logdir . 'log-2026-09-20.php', 'x'); file_put_contents($logdir . 'index.html', 'x'); touch($logdir . 'index.html', time() - 900 * 86400);

$pr = new Penyapu_retensi(['db' => $db, 'policy' => $pol, 'app' => $tmp . '/app']);
$ada = function ($id) { return jumlah('SELECT id FROM sf_rekaman_simperum WHERE id = ' . (int) $id) === 1; };

// Mode kering: menghitung, tidak mengubah apa pun.
$kering = $pr->jalankan(TRUE);
check($kering['kering'] === TRUE && $kering['tugas']['snapshot_simperum']['jumlah'] >= 2, 'Mode kering harus melaporkan snapshot yang akan dihapus');
check($ada($s_lama) && $ada($s_draf) && is_file($logdir . 'log-2020-01-01.php') && jumlah("SELECT 1 FROM sys_rate_limits WHERE limit_key = '{$tag}lama'") === 1, 'Mode kering tidak boleh mengubah data apa pun');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE aksi = 'retensi_dijalankan' AND ringkasan LIKE '%kering%'") === 0 && $pr->catat($kering) === FALSE, 'Mode kering tidak boleh menulis jejak audit');

$hasil = $pr->jalankan(FALSE);
check($hasil['kering'] === FALSE && $hasil['total'] >= 6, 'Putaran nyata harus menghapus entri kedaluwarsa');
foreach ($hasil['tugas'] as $nama => $h) { check($h['galat'] === NULL, "Tugas retensi $nama gagal: " . $h['galat']); }
check( ! $ada($s_lama), 'Snapshot kedaluwarsa > masa tenggang dan tak dirujuk harus dihapus');
check($ada($s_tenggang), 'Snapshot yang baru kedaluwarsa (dalam masa tenggang) harus tetap ada');
check($ada($s_arsip), 'Snapshot yang dirujuk penilaian TERKIRIM (arsip) harus tetap ada walau kedaluwarsa');
check( ! $ada($s_draf), 'Snapshot yang hanya dirujuk DRAF boleh dihapus');
check(satu("SELECT simperum_snapshot_id AS s FROM sf_penilaian_perumahan WHERE housing_status_code = ? AND status = 'draft'", [$tag])['s'] === NULL, 'Draf yang snapshotnya dihapus harus kehilangan rujukan (FK SET NULL), bukan ikut terhapus');
check($ada($s_segar), 'Snapshot yang belum kedaluwarsa harus tetap ada');
check(jumlah("SELECT 1 FROM sys_rate_limits WHERE limit_key = '{$tag}lama'") === 0 && jumlah("SELECT 1 FROM sys_rate_limits WHERE limit_key = '{$tag}baru'") === 1, 'Penghitung laju lama dihapus, yang baru tetap');
check(satu('SELECT email_token AS t FROM usr_users WHERE id = ?', [$uid_a])['t'] === NULL && satu('SELECT email_token_expiry AS t FROM usr_users WHERE id = ?', [$uid_a])['t'] === NULL, 'Token surel kedaluwarsa harus dikosongkan');
check(satu('SELECT email_token AS t FROM usr_users WHERE id = ?', [$uid_b])['t'] === 'tokenbaru', 'Token surel yang masih berlaku harus tetap');
check(jumlah("SELECT 1 FROM sys_push_subscriptions WHERE endpoint_hash = '" . str_pad($tag . 'mati_lama', 64, '0') . "'") === 0
    && jumlah("SELECT 1 FROM sys_push_subscriptions WHERE endpoint_hash = '" . str_pad($tag . 'mati_baru', 64, '0') . "'") === 1
    && jumlah("SELECT 1 FROM sys_push_subscriptions WHERE endpoint_hash = '" . str_pad($tag . 'hidup_lama', 64, '0') . "'") === 1, 'Hanya langganan push NONAKTIF yang lama dihapus');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE ringkasan = 'audit sangat lama $tag'") === 0 && jumlah("SELECT 1 FROM sys_jejak_audit WHERE ringkasan = 'audit baru $tag'") === 1, 'Jejak audit melewati batas 5 tahun dihapus, yang baru tetap');
check( ! is_file($logdir . 'log-2020-01-01.php') && is_file($logdir . 'log-2026-09-20.php') && is_file($logdir . 'index.html'), 'Log lama dihapus; log baru dan berkas bernama lain (index.html) tetap');
check($pr->catat($hasil, 'uji') === TRUE && jumlah("SELECT 1 FROM sys_jejak_audit WHERE aksi = 'retensi_dijalankan' AND actor_role = 'sistem'") >= 1, 'Putaran nyata dicatat di jejak audit');
$ringkas = satu("SELECT detail_json AS d FROM sys_jejak_audit WHERE aksi = 'retensi_dijalankan' ORDER BY id DESC LIMIT 1")['d'];
check(is_array(json_decode($ringkas, TRUE)) && strpos($ringkas, '@') === FALSE, 'Catatan retensi hanya memuat jumlah, tanpa isi data');
$ulang = $pr->jalankan(FALSE);
check($ulang['tugas']['snapshot_simperum']['jumlah'] === 0 && $ulang['tugas']['rate_limit']['jumlah'] === 0 && $ulang['tugas']['token_surel']['jumlah'] === 0, 'Putaran kedua idempoten (tidak ada lagi yang dihapus)');
$m->query("DELETE FROM sys_jejak_audit WHERE aksi = 'retensi_dijalankan' AND actor_role = 'sistem' AND detail_json LIKE '%snapshot_simperum%' AND created_at >= NOW() - INTERVAL 1 MINUTE");

// Jatuh tempo penanda.
$marker = $tmp . '/marker';
check(Penyapu_retensi::jatuh_tempo($marker, 86400), 'Tanpa penanda = jatuh tempo');
touch($marker);
check( ! Penyapu_retensi::jatuh_tempo($marker, 86400), 'Penanda baru = belum jatuh tempo');
touch($marker, time() - 90000);
check(Penyapu_retensi::jatuh_tempo($marker, 86400), 'Penanda lebih tua dari interval = jatuh tempo');

// ------------------------------------------------------------------ 4. Hapus akun: berkas dan draf disapu, audit disamarkan
$root = $tmp . '/priv/';
$email = "hapus_{$tag}@contoh.test";
q('INSERT INTO usr_users (email) VALUES (?)', [$email]);
$uid = (int) satu('SELECT id FROM usr_users WHERE email = ?', [$email])['id'];
q("INSERT INTO kkn_magang_pendaftaran (user_id, jenis, instansi_asal, no_hp) VALUES (?, 'kkn', ?, '0800')", [$uid, $tag]);
$kid = (int) satu('SELECT id FROM kkn_magang_pendaftaran WHERE user_id = ? AND instansi_asal = ?', [$uid, $tag])['id'];
mkdir($root . "kemitraan/$kid", 0700, TRUE);
foreach (['surat_pengantar', 'proposal', 'laporan_akhir', 'surat_balasan', 'surat_simperum'] as $b) { file_put_contents($root . "kemitraan/$kid/$b.pdf", 'x'); }
q("INSERT INTO sf_penilaian_perumahan (user_id, status, housing_status_code) VALUES (?, 'draft', ?)", [$uid, $tag]);
q("INSERT INTO sf_penilaian_perumahan (user_id, status, housing_status_code) VALUES (?, 'submitted', ?)", [$uid, $tag]);
$draf_id = (int) satu("SELECT id FROM sf_penilaian_perumahan WHERE user_id = ? AND status = 'draft'", [$uid])['id'];
$kirim_id = (int) satu("SELECT id FROM sf_penilaian_perumahan WHERE user_id = ? AND status = 'submitted'", [$uid])['id'];
foreach ([$draf_id, $kirim_id] as $aid) {
    mkdir($root . "warga_assessment/$aid", 0700, TRUE);
    file_put_contents($root . "warga_assessment/$aid/foto.jpg", 'x');
    q("INSERT INTO sf_berkas_penilaian (assessment_id, file_kind, private_path, mime_type, size_bytes, sha256) VALUES (?, 'foto', 'foto.jpg', 'image/jpeg', 1, ?)", [$aid, str_repeat('a', 64)]);
}
// Berkas milik akun LAIN tidak boleh tersentuh.
q("INSERT INTO kkn_magang_pendaftaran (user_id, jenis, instansi_asal, no_hp) VALUES (?, 'kkn', ?, '0800')", [$uid_b, $tag]);
$kid_lain = (int) satu('SELECT id FROM kkn_magang_pendaftaran WHERE user_id = ? AND instansi_asal = ?', [$uid_b, $tag])['id'];
mkdir($root . "kemitraan/$kid_lain", 0700, TRUE); file_put_contents($root . "kemitraan/$kid_lain/milik_lain.pdf", 'x');
q("INSERT INTO sys_jejak_audit (actor_id, actor_email, actor_role, aksi, ringkasan, detail_json, created_at) VALUES (?, ?, 'warga', 'uji_yl', ?, NULL, NOW())", [$uid, $email, "Warga masuk $tag"]);
q("INSERT INTO sys_jejak_audit (actor_id, actor_email, actor_role, aksi, ringkasan, detail_json, created_at) VALUES (?, ?, 'admin', 'uji_yl', ?, ?, NOW())", [$uid_b, "b_{$tag}@contoh.test", "Membuka kunci akun $email $tag", json_encode(['target' => $email, 'x' => $tag])]);
q("INSERT INTO forum_diskusi (nama_user, email_user, judul_topik, kategori, isi_diskusi, user_id, created_at) VALUES ('Nama', ?, ?, 'umum', 'isi', ?, NOW())", [$email, $tag, $uid]);

$er = new Data_erasure(['db' => $db, 'root' => $root, 'pepper' => 'lada-uji']);
$sapu = $er->sapu_berkas($uid);
check($sapu['berkas'] === 5 + 1 && $sapu['draf'] === 1, 'sapu_berkas: 5 berkas KKN + 1 foto draf dihapus, 1 draf dihapus; dapat ' . json_encode($sapu));
check( ! is_dir($root . "kemitraan/$kid") && ! is_dir($root . "warga_assessment/$draf_id"), 'Direktori KKN dan draf harus hilang dari disk (SEMUA berkas, bukan hanya surat pengantar)');
check(jumlah("SELECT 1 FROM sf_penilaian_perumahan WHERE id = $draf_id") === 0 && jumlah("SELECT 1 FROM sf_berkas_penilaian WHERE assessment_id = $draf_id") === 0, 'Baris draf dan baris berkasnya harus terhapus');
check(jumlah("SELECT 1 FROM sf_penilaian_perumahan WHERE id = $kirim_id") === 1 && is_file($root . "warga_assessment/$kirim_id/foto.jpg"), 'Penilaian TERKIRIM (arsip) dan berkasnya harus tetap ada');
check(is_file($root . "kemitraan/$kid_lain/milik_lain.pdf"), 'Berkas milik akun lain tidak boleh tersentuh');
check($er->sapu_berkas($uid) === ['berkas' => 0, 'draf' => 0], 'sapu_berkas idempoten');

$ubah = $er->samarkan_audit($uid, $email);
check($ubah >= 3, "samarkan_audit harus mengubah baris pelaku dan penyebutan oleh admin (dapat $ubah)");
$pseudo = Data_erasure::pseudonim_surel($email, 'lada-uji');
check(strpos($pseudo, '@') === FALSE && strpos($pseudo, 'hapus_') === FALSE && preg_match('/^akun-dihapus-[0-9a-f]{12}$/', $pseudo) === 1, 'Pseudonim tidak memuat bagian surel dan berbentuk tetap');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE actor_email = '$pseudo'") === 1, 'Surel pelaku diganti pseudonim');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE ringkasan LIKE '%hapus\\_{$tag}@%' OR detail_json LIKE '%hapus\\_{$tag}@%' OR actor_email LIKE '%hapus\\_{$tag}@%'") === 0, 'Tidak boleh ada surel terbaca yang tersisa di jejak audit');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE ringkasan = 'Membuka kunci akun $pseudo $tag'") === 1, 'Penyebutan surel di ringkasan audit oleh admin diganti pseudonim (konteks tetap terbaca)');
check(jumlah("SELECT 1 FROM sys_jejak_audit WHERE actor_email = 'b_{$tag}@contoh.test'") === 1, 'Baris audit milik akun lain tidak boleh berubah');
check(Data_erasure::pseudonim_surel($email, 'lada-uji') === Data_erasure::pseudonim_surel(strtoupper($email), 'lada-uji'), 'Pseudonim stabil dan tidak peka huruf besar/kecil');
check(Data_erasure::pseudonim_surel($email, 'lada-uji') !== Data_erasure::pseudonim_surel($email, 'lada-lain') && Data_erasure::pseudonim_surel($email, 'x') !== Data_erasure::pseudonim_surel("lain_{$tag}@contoh.test", 'x'), 'Pseudonim bergantung pada kunci dan pada surel');
check($er->samarkan_audit($uid, '') === 0, 'Surel kosong tidak mengubah apa pun');

// Menghapus akun: baris KKN ikut lewat CASCADE, dan tidak ada lagi baris/berkas milik akun.
q('DELETE FROM usr_users WHERE id = ?', [$uid]);
check(jumlah("SELECT 1 FROM kkn_magang_pendaftaran WHERE id = $kid") === 0, 'Pendaftaran KKN ikut terhapus lewat CASCADE');
check(jumlah("SELECT 1 FROM sf_penilaian_perumahan WHERE id = $kirim_id AND user_id IS NULL") === 1, 'Penilaian terkirim kehilangan tautan ke akun (SET NULL) tetapi tetap sebagai arsip');

// ------------------------------------------------------------------ 5. Pemasangan
$my = file_get_contents($app . '/core/MY_Controller.php');
check(preg_match('/function __construct\(\)\s*\{(.*?)\R    \}/s', $my, $ktor) === 1 && strpos($ktor[1], '$this->jadwalkan_retensi();') !== FALSE, 'Konstruktor MY_Controller harus memicu jadwalkan_retensi()');
check(preg_match('/function jadwalkan_retensi\(\).*?register_shutdown_function.*?fastcgi_finish_request.*?LOCK_EX \| LOCK_NB.*?catch \(Throwable/s', $my) === 1, 'Penyapu harus berjalan sesudah respons, dijaga flock, dan gagal diam-diam');
check(preg_match('/function serve_private_file.*?catat_akses_data_pribadi\(\'berkas_privat\'.*?readfile/s', $my) === 1, 'serve_private_file harus mencatat akses staf sebelum menyajikan berkas');
check(preg_match('/function assessment_detail_data.*?catat_akses_data_pribadi\(\'penilaian_warga\'/s', $my) === 1, 'Tampilan profil warga terdekripsi ke staf harus dicatat');
$srp = file_get_contents($app . '/controllers/Admin_Srp2.php');
check(strpos($srp, "catat_akses_data_pribadi('npwp_srp2'") !== FALSE && strpos($srp, "catat_akses_data_pribadi('pengajuan_srp2'") !== FALSE, 'Tampilan NPWP dan detail pengajuan SRP2 ke staf harus dicatat');
check(strpos(file_get_contents($app . '/controllers/Retensi.php'), 'is_cli_request()') !== FALSE, 'Pelari retensi hanya CLI');
$config = []; require $app . '/config/rate_limits.php';
check(isset($config['rate_limit_policies']['audit_akses_dedupe']) && ! empty($config['rate_limit_policies']['audit_akses_dedupe']['senyap']), 'Kebijakan penekan duplikat audit akses harus terdaftar dan senyap');

echo "data_lifecycle_db_test: OK ($total pemeriksaan pada skema nyata dengan " . count($fk) . " kolom FK ke usr_users)\n";
