<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji R4 pendataan warga melalui HTTP Apache.
 * Jalankan: php docs/engineering/uji_pendataan_warga_r4.php
 * Env opsional: UJI_BASE_URL, UJI_WARGA_PASSWORD
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('PASSWORD', getenv('UJI_WARGA_PASSWORD') ?: 'UjiWargaR4!');
$GLOBALS['total'] = $GLOBALS['gagal'] = 0;
$GLOBALS['users'] = $GLOBALS['assessments'] = $GLOBALS['rate_limit_original'] = [];
$GLOBALS['db'] = NULL;
$GLOBALS['private_root'] = NULL;

function cek($ok, $label) { $GLOBALS['total']++; echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n"; if (!$ok) $GLOBALS['gagal']++; return $ok; }
function wajib($ok, $label) { if (!cek($ok, $label)) exit(1); }
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line); if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) continue;
        [$key, $value] = explode('=', $line, 2); if (!array_key_exists(trim($key), $out)) $out[trim($key)] = trim($value);
    }
    foreach (['DB_HOST','DB_USER','DB_PASS','DB_NAME','PRIVATE_UPLOADS_PATH'] as $key) if (getenv($key) !== FALSE) $out[$key] = getenv($key);
    return $out;
}
class Db {
    private $m;
    function __construct($e) { $this->m = new mysqli($e['DB_HOST'], $e['DB_USER'], $e['DB_PASS'] ?? '', $e['DB_NAME']); if ($this->m->connect_error) die("Koneksi DB gagal: {$this->m->connect_error}\n"); }
    function run($sql, $p = []) { $s = $this->prep($sql, $p); $id = $s->insert_id; $s->close(); return $id; }
    function row($sql, $p = []) { $s = $this->prep($sql, $p); $r = $s->get_result()->fetch_assoc(); $s->close(); return $r ?: NULL; }
    function scalar($sql, $p = []) { $r = $this->row($sql, $p); return $r ? reset($r) : NULL; }
    private function prep($sql, $p) { $s = $this->m->prepare($sql); if (!$s) die("Prepare gagal: {$this->m->error}\n"); if ($p) { $t = str_repeat('s', count($p)); $s->bind_param($t, ...$p); } if (!$s->execute()) die("Query gagal: {$s->error}\n"); return $s; }
}
class Session {
    private $cookie; public $csrf = NULL;
    function __construct() { $this->cookie = tempnam(sys_get_temp_dir(), 'uji_r4_'); }
    function __destruct() { @unlink($this->cookie); }
    function get($path) { return $this->call($path, [CURLOPT_HTTPGET => TRUE]); }
    function post($path, $fields) { if ($this->csrf) $fields['csrf_kpkp_token'] = $this->csrf; return $this->call($path, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']]); }
    function upload($path, $fields, $fileField, $filePath, $filename, $headers = []) {
        if ($this->csrf) $fields['csrf_kpkp_token'] = $this->csrf;
        $fields[$fileField] = new CURLFile($filePath, 'image/png', $filename);
        return $this->call($path, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $fields, CURLOPT_HTTPHEADER => $headers]);
    }
    private function call($path, $options) {
        $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, $options + [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $this->cookie, CURLOPT_COOKIEFILE => $this->cookie, CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_HEADER => TRUE, CURLOPT_TIMEOUT => 30]);
        $raw = curl_exec($ch); if ($raw === FALSE) die('curl gagal: ' . curl_error($ch) . "\n"); $info = curl_getinfo($ch); curl_close($ch);
        $body = substr($raw, $info['header_size']);
        if (preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/', $body, $m)) $this->csrf = $m[1];
        return ['status' => $info['http_code'], 'body' => $body];
    }
}
function login($email) {
    $s = new Session(); $s->get('Auth/login'); $r = $s->post('Auth/do_login', ['email' => $email, 'password' => PASSWORD]);
    wajib($r['status'] === 200 && (json_decode($r['body'], TRUE)['status'] ?? '') === 'success', "Login $email"); $s->get('warga/pendataan'); return $s;
}
/* Nama akun sama dengan nama fixture: sejak 3 Okt 2026 lookup ber-akun mengikat NIK hanya kalau
   nama lengkap akun dan tanggal lahir cocok dengan data SIMPERUM (Simperum_gateway::lookup). */
function make_user($db, $suffix, $nama = 'Uji R4') {
    $email = "uji_r4_{$suffix}_" . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = $db->run("INSERT INTO usr_akun (email,kata_sandi,nama,nama_pengguna,peran,status,profil_lengkap,created_at) VALUES (?,?,?,?,'warga','active',1,NOW())", [$email, password_hash(PASSWORD, PASSWORD_BCRYPT), $nama, "uji_r4_{$suffix}"]);
    $GLOBALS['users'][] = $id; return [$id, $email];
}
/**
 * NIK fixture SIMPERUM adalah sumber daya BERSAMA yang langka: cuma tujuh, dan
 * satu profil warga mengikatnya EKSKLUSIF lewat `nik_lookup_hash`. Begitu ada
 * akun mana pun yang memegangnya, `Simperum_gateway::lookup()` gagal di
 * `save_profile()` dengan `nik_already_bound`, tidak ada draft yang lahir, dan
 * uji ini merah di "Draft warga tersedia" - pesan yang menunjuk ke tempat yang
 * sepenuhnya salah. Ikatan itu bukan bug produk: penjaga NIK-ganda memang harus
 * begitu. Diperiksa di depan supaya kegagalannya bisa dibaca sekali lihat.
 */
function nik_bebas($db, $env, $nik) {
    $p = $db->row('SELECT p.id, u.email FROM sf_profil_warga p
                   LEFT JOIN usr_akun u ON u.id = p.user_id
                   WHERE p.nik_lookup_hash = ?',
        [hash_hmac('sha256', $nik, $env['KPKP_DATA_PEPPER'] ?? '')]);
    wajib( ! $p, $p
        ? "NIK fixture {$nik} SEDANG DIPEGANG profil #{$p['id']} milik "
          . ($p['email'] ?? '[akun sudah terhapus]') . ' - lepaskan ikatannya atau pakai DB uji bersih'
        : "NIK fixture {$nik} bebas dipakai");
}

function draft($db, $user) {
    $r = $db->row('SELECT a.* FROM sf_penilaian_perumahan a WHERE a.user_id=? AND a.status=\'draft\' ORDER BY a.id DESC LIMIT 1', [$user]);
    wajib((bool)$r, 'Draft warga tersedia'); if (!in_array((int)$r['id'], $GLOBALS['assessments'], TRUE)) $GLOBALS['assessments'][] = (int)$r['id']; return $r;
}
function post_step($s, $d, $step, $data) { $r = $s->post('warga/pendataan', $data + ['action'=>'save','step'=>$step,'direction'=>'next','penilaian_id'=>$d['id'],'versi_kunci'=>$d['versi_kunci']]); return $r; }
function citizen_fields() { return ['family_card_number'=>'0000000000001111','full_name'=>'Warga Uji R4','address'=>'Alamat Uji R4','phone'=>'081234567890','birth_date'=>'1980-01-01','jenis_kelamin'=>'male','status_perkawinan'=>'married','pendidikan'=>'senior_high','pekerjaan'=>'private_employee','kelompok_penghasilan'=>'2_2_2_6','mampu_swadaya'=>'capable','punya_tabungan'=>'1']; }
/* Wizard berubah 23-24 Agt 2026: `citizen_data` DIHAPUS (cfbd760 + migrasi 049),
   isiannya pindah ke `housing_family_detail`, dan `housing_family` kini berisi
   tujuh isian matriks xlsx. Harness menyusul 31 Agt 2026. */
/* Berubah lagi 8-10 Sep 2026 (157e275 + 22c790f): pendapatan jadi angka rupiah, DTKS tidak
   ditanyakan, data profil dasar WAJIB di langkah ini, dan CABANG ditentukan di sini dari
   `matriks_rumah_sekarang` (milik sendiri = existing_house, selain itu = candidate_land).
   Jalur `financing` tidak lagi bisa dicapai dari wizard. Harness menyusul 18 Sep 2026. */
function matriks($rumah = 'house_none_or_rent') { return ['matriks_rumah_sekarang'=>$rumah,'kawasan_perumahan'=>'slum','matriks_kepemilikan_lahan'=>'land_none','matriks_kondisi_lingkungan'=>'env_slum_uninhabitable','matriks_pekerjaan_keuangan'=>'work_stable_or_unstable_no_subsidy','matriks_status_keluarga'=>'family_married','phone'=>'081234567890','birth_date'=>'1980-01-01','jenis_kelamin'=>'male','status_perkawinan'=>'married','pendidikan'=>'senior_high','pekerjaan'=>'trader','stabilitas_pekerjaan'=>'permanent','penghasilan_bulanan'=>'1200000']; }
/* Bawa draft dari `housing_family` sampai berhenti di `housing_family_detail`. */
function maju_ke_detail($s, $db, $uid, $rumah = 'house_none_or_rent') {
  $d = draft($db, $uid);
  post_step($s, $d, 'housing_family', matriks($rumah)); $d = draft($db, $uid);
  if ($d['langkah_sekarang'] === 'preliminary_recommendation') { post_step($s, $d, 'preliminary_recommendation', []); $d = draft($db, $uid); }
  return $d;
}
function housing_fields($status, $candidate) { return ['kepemilikan_rumah'=>$status,'kepemilikan_lahan'=>'hm','kawasan_perumahan'=>'slum','jml_penghuni'=>'3','jml_kk'=>'1','luas_rumah'=>'36','tanah_lain'=>'0','rumah_lain'=>'0','punya_lahan_calon'=>$candidate,'bantuan_perumahan'=>'','tahun_intervensi'=>'']; }
function png_with_text($text) {
    $raw = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLKUwAAAABJRU5ErkJggg==');
    $iend = strrpos($raw, 'IEND') - 4; $data = 'Comment' . "\0" . $text; $chunk = pack('N', strlen($data)) . 'tEXt' . $data . pack('N', crc32('tEXt' . $data));
    $path = tempnam(sys_get_temp_dir(), 'uji_r4_png_') . '.png'; file_put_contents($path, substr($raw, 0, $iend) . $chunk . substr($raw, $iend)); return $path;
}
/**
 * `warga_lookup` dibatasi 10 percobaan per 60 detik dan salah satu dimensinya
 * adalah IP - sementara SELURUH harness di repo ini datang dari 127.0.0.1.
 * Dijalankan sendirian uji ini muat; dijalankan berurutan bersama r3/r5/r6
 * lewat runner, ember IP-nya jebol di tengah jalan dan lookup ditolak. Merahnya
 * lalu muncul di tempat yang tidak ada hubungannya ("Draft warga tersedia"),
 * berpindah-pindah tiap jalan, dan terbaca seperti flake padahal deterministik.
 *
 * Ember IP-nya dipinjam, bukan dikosongkan: isinya diingat dan dikembalikan
 * utuh di cleanup, supaya uji ini tidak diam-diam melonggarkan pembatas laju
 * untuk siapa pun yang jalan sesudahnya. Pola ini disalin dari R6.
 */
function preserve_rate_key($db, $policy, $dimension, $value) {
    $key = hash('sha256', $policy . ':' . $dimension . ':' . $value);
    if (array_key_exists($key, $GLOBALS['rate_limit_original'])) return;
    $GLOBALS['rate_limit_original'][$key] = $db->row(
        'SELECT kunci,jendela_mulai_at,jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$key]);
    $db->run('DELETE FROM sys_batas_laju WHERE kunci=?', [$key]);
}
function preserve_rate_ips($db, $policy) {
    preserve_rate_key($db, $policy, 'ip', '127.0.0.1');
    preserve_rate_key($db, $policy, 'ip', '::1');
}
function cleanup() {
    $db = $GLOBALS['db']; if (!$db) return;
    foreach (array_unique($GLOBALS['assessments']) as $id) {
        $dir = rtrim((string)$GLOBALS['private_root'], '/\\') . DIRECTORY_SEPARATOR . 'warga_assessment' . DIRECTORY_SEPARATOR . $id;
        if (is_dir($dir)) { foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) @unlink($f); @rmdir($dir); }
        $db->run('DELETE FROM sf_penilaian_perumahan WHERE id=?', [$id]);
    }
    foreach (array_unique($GLOBALS['users']) as $id) $db->run('DELETE FROM usr_akun WHERE id=?', [$id]);
    foreach ($GLOBALS['rate_limit_original'] as $key => $row) {
        $db->run('DELETE FROM sys_batas_laju WHERE kunci=?', [$key]);
        if ($row) {
            $db->run('INSERT INTO sys_batas_laju (kunci,jendela_mulai_at,jumlah_gagal) VALUES (?,?,?)',
                [$row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']]);
        }
    }
}
register_shutdown_function('cleanup');

if (!is_file(ENV_PATH)) die(".env tidak ditemukan.\n");
$env = env_config(ENV_PATH); $GLOBALS['db'] = $db = new Db($env);
$root = $env['PRIVATE_UPLOADS_PATH'] ?? ''; if ($root === '') die("PRIVATE_UPLOADS_PATH wajib untuk uji R4.\n");
if (!preg_match('#^(?:[A-Za-z]:|[/\\\\])#', $root)) $root = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $root;
$GLOBALS['private_root'] = $root;
echo "=== UJI PENDATAAN WARGA R4 ===\nTarget: " . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

// Empat policy, bukan cuma warga_lookup: `warga_submit` batasnya 5 per JAM,
// jadi ia jebol jauh lebih cepat daripada lookup begitu suite dijalankan
// berturut-turut. Dimensi `nik` ikut dipinjam karena ember NIK fixture ini
// dipakai bersama r5 dan r6 - membersihkan ember IP saja menyisakan tabrakan
// yang muncul acak di suite mana pun yang kebetulan jalan belakangan.
foreach (['warga_lookup', 'warga_submit', 'warga_start_revision', 'admin_queue_decision'] as $policy) {
    preserve_rate_ips($db, $policy);
}
preserve_rate_key($db, 'warga_lookup', 'nik', hash_hmac('sha256', '0000000000000001', $env['KPKP_DATA_PEPPER']));
// verifikasi_nik (3 Okt 2026) ikut dipinjam: percobaan gagal dari jalan lain mengunci NIK fixture 24 jam.
foreach (['0000000000000001', '0000000000000003', '0000000000000004'] as $n) { preserve_rate_key($db, 'verifikasi_nik', 'nik', hash_hmac('sha256', $n, $env['KPKP_DATA_PEPPER'])); }
nik_bebas($db, $env, '0000000000000001');

// Existing house: lookup → langkah 1/2 → bangunan → sanitasi → lokasi.
[$existingUser, $existingEmail] = make_user($db, 'existing', 'Warga Simulasi RTLH'); $existing = login($existingEmail);
$r = $existing->post('warga/pendataan', ['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']); wajib(in_array($r['status'], [302,303], TRUE), 'Lookup existing redirect');
$d = draft($db, $existingUser); wajib($d['langkah_sekarang'] === 'housing_family', 'Existing masuk isian matriks');
$d = maju_ke_detail($existing, $db, $existingUser, 'house_owned'); wajib($d['langkah_sekarang'] === 'housing_family_detail', 'Existing sampai langkah detail');
$r = post_step($existing, $d, 'housing_family_detail', citizen_fields() + housing_fields('owned','0')); wajib(in_array($r['status'], [302,303], TRUE), 'Existing simpan Data Warga'); $d = draft($db, $existingUser);
wajib($d['jalur_penilaian']==='existing_house' && $d['langkah_sekarang']==='building_condition', 'Satu submit detail menetapkan cabang rumah eksisting dan lanjut ke Kondisi Bangunan');
$invalid = post_step($existing, $d, 'building_condition', ['kondisi_pondasi'=>'PALSUE_ENUM']); cek(in_array($invalid['status'], [302,303], TRUE) && draft($db,$existingUser)['langkah_sekarang']==='building_condition', 'Enum kondisi bangunan ilegal ditolak');
$conditions=['kondisi_pondasi'=>'good','kondisi_kolom'=>'minor_damage','kondisi_balok'=>'moderate_damage','kondisi_rangka'=>'good','bahan_lantai'=>'cement_plaster','kondisi_lantai'=>'minor_damage','bahan_dinding'=>'wall','kondisi_dinding'=>'good','bahan_atap'=>'clay_tile','kondisi_atap'=>'good'];
$d=draft($db,$existingUser); $r=post_step($existing,$d,'building_condition',$conditions); wajib(in_array($r['status'],[302,303],TRUE),'Existing simpan kondisi bangunan'); $d=draft($db,$existingUser); wajib($d['langkah_sekarang']==='sanitation','Existing lanjut sanitasi');
$san=['ada_jendela'=>'1','ada_ventilasi'=>'1','sumber_air'=>'well','penggunaan_kamar_mandi'=>'own','jenis_kloset'=>'swan_neck','pembuangan_tinja'=>'septic_tank','jarak_septic_tank'=>'gte_10','penerangan'=>'pln','bahan_bakar_masak'=>'electric_gas'];
$r=post_step($existing,$d,'sanitation',$san); wajib(in_array($r['status'],[302,303],TRUE),'Existing simpan sanitasi'); $d=draft($db,$existingUser); wajib($d['langkah_sekarang']==='location_evidence','Existing menuju lokasi');

// Candidate land: branch skips building/sanitation and encrypts address/coordinates.
[$landUser,$landEmail]=make_user($db,'land','Warga Simulasi Calon Lahan'); $land=login($landEmail); $land->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000003','birth_date'=>'1988-03-03']); $d=maju_ke_detail($land,$db,$landUser); post_step($land,$d,'housing_family_detail',citizen_fields()+housing_fields('rent','1')); $d=draft($db,$landUser); wajib($d['jalur_penilaian']==='candidate_land' && $d['langkah_sekarang']==='candidate_land','Calon lahan melewati bangunan/sanitasi');
$landData=['candidate_land_address'=>'Alamat Tanah Uji Rahasia','status_lahan_calon'=>'hm','asal_lahan_calon'=>'inheritance','hubungan_pemilik_lahan'=>'parent','panjang_lahan_m'=>'8','lebar_lahan_m'=>'12'];
$r=post_step($land,$d,'candidate_land',$landData); wajib(in_array($r['status'],[302,303],TRUE),'Calon lahan tersimpan'); $d=draft($db,$landUser); wajib($d['langkah_sekarang']==='location_evidence' && (float)$d['luas_lahan_m2']===96.0,'Area tanah dihitung server');
$raw=$db->row('SELECT alamat_lahan_calon_ciphertext FROM sf_penilaian_perumahan WHERE id=?',[$d['id']]); cek(strpos((string)$raw['alamat_lahan_calon_ciphertext'],'Alamat Tanah Uji Rahasia')===FALSE,'Alamat tanah tidak plaintext di DB');

// Dulu: jalur `financing` melompati kedua modul cabang. Sejak 157e275 (8 Sep 2026) wizard hanya
// punya DUA cabang, jadi penyewa tanpa lahan lain pun masuk calon lahan; `financing` tinggal jenis
// yang sah di model untuk draft lama. Yang dijaga: jalur itu tidak bisa dicapai lagi dari wizard.
[$financeUser,$financeEmail]=make_user($db,'finance','Warga Simulasi Pembiayaan'); $finance=login($financeEmail); $finance->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000004','birth_date'=>'1987-04-04']); $d=maju_ke_detail($finance,$db,$financeUser); post_step($finance,$d,'housing_family_detail',citizen_fields()+housing_fields('rent','0')); $d=draft($db,$financeUser); wajib($d['jalur_penilaian']==='candidate_land' && $d['langkah_sekarang']==='candidate_land','Penyewa tanpa lahan lain masuk cabang calon lahan - jalur financing tidak bisa dicapai dari wizard');

// Coordinates and evidence upload/replace/IDOR on existing draft.
$d=draft($db,$existingUser); $r=post_step($existing,$d,'location_evidence',['location_lat'=>'-7.123456','location_lng'=>'110.123456','akurasi_lokasi_m'=>'8']); wajib(in_array($r['status'],[302,303],TRUE),'Koordinat tersimpan'); $d=draft($db,$existingUser); $raw=$db->row('SELECT geo_lat_ciphertext,geo_lng_ciphertext FROM sf_penilaian_perumahan WHERE id=?',[$d['id']]); cek(strpos($raw['geo_lat_ciphertext'],'-7.123456')===FALSE && strpos($raw['geo_lng_ciphertext'],'110.123456')===FALSE,'Koordinat tidak plaintext di DB');
$empty=$existing->post('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo']); $emptyPage=$existing->get('warga/pendataan'); $emptyCount=(int)$db->scalar('SELECT COUNT(*) FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']]);
$png=png_with_text('RAHASIA_R4'); $up=$existing->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$png,'metadata.png'); wajib(in_array($empty['status'],[302,303],TRUE) && strpos($emptyPage['body'],'Pilih berkas JPG/PNG terlebih dahulu.')!==FALSE && $emptyCount===0 && in_array($up['status'],[302,303],TRUE),'Unggah kosong ditolak ramah dan PNG valid diunggah');
$file=$db->row('SELECT path_privat, sha256 FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']]); wajib((bool)$file,'Ledger bukti lahir'); $path=rtrim($GLOBALS['private_root'],'/\\').DIRECTORY_SEPARATOR.'warga_assessment'.DIRECTORY_SEPARATOR.$d['id'].DIRECTORY_SEPARATOR.basename($file['path_privat']); cek(is_file($path) && strpos((string)file_get_contents($path),'RAHASIA_R4')===FALSE && strpos((string)file_get_contents($path),'tEXt')===FALSE,'PNG tersimpan privat dan metadata text dibuang');
$old=$path; $png2=png_with_text('RAHASIA_R4_GANTI'); $up=$existing->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$png2,'replace.png'); wajib(in_array($up['status'],[302,303],TRUE),'PNG pengganti diunggah'); $file2=$db->row('SELECT path_privat FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']]); $new=rtrim($GLOBALS['private_root'],'/\\').DIRECTORY_SEPARATOR.'warga_assessment'.DIRECTORY_SEPARATOR.$d['id'].DIRECTORY_SEPARATOR.basename($file2['path_privat']); cek(is_file($new) && !is_file($old) && (int)$db->scalar('SELECT COUNT(*) FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']])===1,'Ganti bukti menghapus file lama dan mempertahankan satu ledger');
[$attackerUser,$attackerEmail]=make_user($db,'attacker'); $attacker=login($attackerEmail); $forged=$attacker->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$png2,'forged.png'); cek($forged['status']===404,'Unggah forge milik warga lain ditolak');
$direct=(new Session())->get('private_uploads/warga_assessment/'.$d['id'].'/'.basename($file2['path_privat'])); cek($direct['status']!==200,'URL langsung private tidak dapat diakses');
// Kartu bukti (fetch + Accept JSON): satu foto per permintaan, jawaban JSON tanpa redirect.
$json=['Accept: application/json','X-Requested-With: XMLHttpRequest']; $png3=png_with_text('R4_JSON'); $js=$existing->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$png3,'json.png',$json); $jj=json_decode($js['body'],TRUE); $file3=$db->row('SELECT path_privat FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']]); cek($js['status']===200 && ($jj['status']??'')==='ok' && substr((string)($jj['ukuran']??''),-3)===' KB' && ($jj['waktu']??'')!=='' && $file3['path_privat']!==$file2['path_privat'] && !is_file($new),'Unggah JSON satu foto tersimpan tanpa redirect, berkas lama terhapus');
$txt=tempnam(sys_get_temp_dir(),'r4t'); file_put_contents($txt,'bukan gambar'); $jbad=$existing->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$txt,'palsu.png',$json); $jb=json_decode($jbad['body'],TRUE); cek($jbad['status']===200 && ($jb['status']??'')==='error' && ($jb['message']??'')==='Bukti harus berupa JPG/PNG yang valid.' && $db->scalar('SELECT path_privat FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas=\'self_photo\'',[$d['id']])===$file3['path_privat'],'Unggah JSON berkas palsu ditolak dengan pesan, ledger tidak berubah');
@unlink($png3); @unlink($txt);
@unlink($png); @unlink($png2);

echo "\n=== RINGKASAN ===\n{$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal.\n";
exit($GLOBALS['gagal'] ? 1 : 0);
