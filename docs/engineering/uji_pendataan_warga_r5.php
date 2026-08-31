<?php
/**
 * Uji R5 ruleset rekomendasi warga melalui HTTP Apache + pemeriksaan DB.
 * Jalankan: php docs/engineering/uji_pendataan_warga_r5.php
 * Env opsional: UJI_BASE_URL, UJI_WARGA_PASSWORD
 *
 * Skrip hanya membuat akun/draft berawalan uji_r5_ dan menghapusnya saat selesai.
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('PASSWORD', getenv('UJI_WARGA_PASSWORD') ?: 'UjiWargaR5!');
define('BASEPATH', dirname(__DIR__, 2) . '/system/');
require dirname(__DIR__, 2) . '/application/libraries/Warga_ruleset.php';

$GLOBALS['total'] = $GLOBALS['gagal'] = 0;
$GLOBALS['users'] = $GLOBALS['assessments'] = $GLOBALS['rate_limit_original'] = [];
$GLOBALS['db'] = NULL;
$GLOBALS['restored_pb'] = FALSE;

function cek($ok, $label) { $GLOBALS['total']++; echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n"; if (!$ok) $GLOBALS['gagal']++; return $ok; }
function wajib($ok, $label) { if (!cek($ok, $label)) exit(1); }
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line); if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) continue;
        [$key, $value] = explode('=', $line, 2); if (!array_key_exists(trim($key), $out)) $out[trim($key)] = trim($value);
    }
    foreach (['DB_HOST','DB_USER','DB_PASS','DB_NAME'] as $key) if (getenv($key) !== FALSE) $out[$key] = getenv($key);
    return $out;
}
class Db {
    private $m;
    function __construct($e) { $this->m = new mysqli($e['DB_HOST'], $e['DB_USER'], $e['DB_PASS'] ?? '', $e['DB_NAME']); if ($this->m->connect_error) die("Koneksi DB gagal: {$this->m->connect_error}\n"); }
    function run($sql, $p = []) { $s = $this->prep($sql, $p); $id = $s->insert_id; $s->close(); return $id; }
    function row($sql, $p = []) { $s = $this->prep($sql, $p); $r = $s->get_result()->fetch_assoc(); $s->close(); return $r ?: NULL; }
    function rows($sql, $p = []) { $s = $this->prep($sql, $p); $r = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); return $r; }
    function scalar($sql, $p = []) { $r = $this->row($sql, $p); return $r ? reset($r) : NULL; }
    private function prep($sql, $p) { $s = $this->m->prepare($sql); if (!$s) die("Prepare gagal: {$this->m->error}\n"); if ($p) { $t = str_repeat('s', count($p)); $s->bind_param($t, ...$p); } if (!$s->execute()) die("Query gagal: {$s->error}\n"); return $s; }
}
class Session {
    private $cookie; public $csrf = NULL;
    function __construct() { $this->cookie = tempnam(sys_get_temp_dir(), 'uji_r5_'); }
    function __destruct() { @unlink($this->cookie); }
    function get($path) { return $this->call($path, [CURLOPT_HTTPGET => TRUE]); }
    function post($path, $fields) { if ($this->csrf) $fields['csrf_kpkp_token'] = $this->csrf; return $this->call($path, [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => http_build_query($fields), CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']]); }
    private function call($path, $options) {
        $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, $options + [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $this->cookie, CURLOPT_COOKIEFILE => $this->cookie, CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_HEADER => TRUE, CURLOPT_TIMEOUT => 30]);
        $raw = curl_exec($ch); if ($raw === FALSE) die('curl gagal: ' . curl_error($ch) . "\n"); $info = curl_getinfo($ch); curl_close($ch);
        $body = substr($raw, $info['header_size']); if (preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/', $body, $m)) $this->csrf = $m[1];
        return ['status' => $info['http_code'], 'body' => $body];
    }
}
function login($email) { $s = new Session(); $s->get('Auth/login'); $r = $s->post('Auth/do_login', ['email'=>$email,'password'=>PASSWORD]); wajib($r['status'] === 200 && (json_decode($r['body'], TRUE)['status'] ?? '') === 'success', "Login $email"); $s->get('warga/pendataan'); return $s; }
function make_user($db, $suffix) { $email = 'uji_r5_'.$suffix.'_'.time().'_'.mt_rand(1000,9999).'@example.test'; $id = $db->run("INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at) VALUES (?,?,'Uji R5',?,'warga','active',1,NOW())", [$email,password_hash(PASSWORD,PASSWORD_BCRYPT),'uji_r5_'.$suffix]); $GLOBALS['users'][]=$id; return [$id,$email]; }
/**
 * NIK fixture SIMPERUM adalah sumber daya BERSAMA yang langka: cuma tujuh, dan
 * satu profil warga mengikatnya EKSKLUSIF lewat `nik_lookup_hash`. Begitu ada
 * akun mana pun yang memegangnya, `Simperum_gateway::lookup()` gagal di
 * `save_profile()` dengan `nik_already_bound`, tidak ada draft yang lahir, dan
 * uji ini merah di "Draft warga tersedia" - pesan yang menunjuk ke tempat yang
 * sepenuhnya salah.
 */
function nik_bebas($db,$env,$nik) {
    $p=$db->row('SELECT p.id, u.email FROM sf_profil_warga p LEFT JOIN usr_users u ON u.id=p.user_id WHERE p.nik_lookup_hash=?',
        [hash_hmac('sha256',$nik,$env['KPKP_DATA_PEPPER'] ?? '')]);
    wajib(!$p, $p ? "NIK fixture {$nik} SEDANG DIPEGANG profil #{$p['id']} milik ".($p['email'] ?? '[akun sudah terhapus]').' - lepaskan ikatannya atau pakai DB uji bersih' : "NIK fixture {$nik} bebas dipakai");
}
function draft($db, $user) { $r=$db->row("SELECT a.* FROM sf_penilaian_perumahan a WHERE a.user_id=? AND a.status='draft' ORDER BY a.id DESC LIMIT 1",[$user]); wajib((bool)$r,'Draft warga tersedia'); if (!in_array((int)$r['id'],$GLOBALS['assessments'],TRUE)) $GLOBALS['assessments'][]=(int)$r['id']; return $r; }
function post_step($s,$d,$step,$data) { return $s->post('warga/pendataan',$data+['action'=>'save','step'=>$step,'direction'=>'next','assessment_id'=>$d['id'],'lock_version'=>$d['lock_version']]); }
function redirect_ok($r) { return in_array($r['status'],[302,303],TRUE); }
function citizen() { return ['family_card_number'=>'0000000000005555','full_name'=>'Warga Uji R5','address'=>'Alamat Uji R5','phone'=>'081234567890','birth_date'=>'1980-01-01','gender_code'=>'male','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'private_employee','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable']; }
/* Wizard berubah 23-24 Agt 2026: `citizen_data` DIHAPUS (cfbd760 + migrasi 049)
   dan isiannya pindah ke `housing_family_detail`, sementara `housing_family`
   kini menampung tujuh isian matriks xlsx yang menentukan rekomendasi awal.
   Harness menyusul 31 Agt 2026. */
function matriks() { return ['matrix_income_code'=>'income_0_1_5','matrix_dtks_status'=>'dtks_ya','matrix_land_ownership_code'=>'land_none','matrix_current_housing_code'=>'house_none_or_rent','matrix_environment_condition_code'=>'env_slum_uninhabitable','matrix_occupation_finance_code'=>'work_stable_or_unstable_no_subsidy','matrix_marital_family_code'=>'family_married']; }
function housing($status,$candidate='0') { return ['housing_status_code'=>$status,'land_title_code'=>'hm','area_condition_code'=>'slum','occupant_count'=>'3','family_count'=>'1','house_area_m2'=>'36','has_other_land'=>'0','has_other_house'=>'0','owns_candidate_land'=>$candidate,'assistance_source_code'=>'','assistance_year'=>'']; }
function recommendations($db,$assessment) { return $db->rows('SELECT p.kode_program,r.ruleset_version,r.eligibility_status,r.reason_codes_json,r.input_snapshot_sha256 FROM sf_rekomendasi_penilaian r JOIN sf_programs p ON p.id=r.program_id WHERE r.assessment_id=? ORDER BY r.ruleset_version,p.kode_program',[$assessment]); }
function by_code($rows,$code) { foreach($rows as $row) if($row['kode_program']===$code) return $row; return NULL; }
/**
 * `warga_lookup` dibatasi 10 percobaan/60 detik dengan IP sebagai salah satu
 * dimensinya, dan semua harness datang dari 127.0.0.1 yang sama. Sendirian uji
 * ini muat; berurutan lewat runner, ember IP-nya jebol dan lookup ditolak -
 * merahnya lalu muncul di tempat yang tak berhubungan dan terbaca seperti
 * flake padahal deterministik. Embernya dipinjam lalu dikembalikan utuh, bukan
 * dikosongkan. Pola dari R6.
 */
function preserve_rate_key($db,$policy,$dimension,$value) {
    $key=hash('sha256',$policy.':'.$dimension.':'.$value);
    if(array_key_exists($key,$GLOBALS['rate_limit_original']))return;
    $GLOBALS['rate_limit_original'][$key]=$db->row('SELECT limit_key,window_started_at,failed_attempts FROM sys_rate_limits WHERE limit_key=?',[$key]);
    $db->run('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);
}
function preserve_rate_ips($db,$policy) {
    preserve_rate_key($db,$policy,'ip','127.0.0.1');
    preserve_rate_key($db,$policy,'ip','::1');
}
function cleanup() {
    $db=$GLOBALS['db']; if(!$db) return;
    if (!$GLOBALS['restored_pb']) $db->run("UPDATE sf_programs SET kode_program='pb' WHERE kode_program='pb_uji_hilang'");
    foreach(array_unique($GLOBALS['assessments']) as $id) $db->run('DELETE FROM sf_penilaian_perumahan WHERE id=?',[$id]);
    foreach(array_unique($GLOBALS['users']) as $id) $db->run('DELETE FROM usr_users WHERE id=?',[$id]);
    foreach($GLOBALS['rate_limit_original'] as $key=>$row) {
        $db->run('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);
        if($row) $db->run('INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)',
            [$row['limit_key'],$row['window_started_at'],$row['failed_attempts']]);
    }
}
register_shutdown_function('cleanup');

if (!is_file(ENV_PATH)) die(".env tidak ditemukan.\n");
$env=env_config(ENV_PATH); $GLOBALS['db']=$db=new Db($env);
echo "=== UJI PENDATAAN WARGA R5 ===\nTarget: ".BASE_URL." | DB: {$env['DB_NAME']}\n\n";

// Kontrak ruleset murni: semua desil, tanpa ketergantungan fixture/DB.
$rules=new Warga_ruleset();
$expected=[1=>['rtlh','pb','rumah_apung'],2=>['rtlh','pb','rumah_apung'],3=>['rtlh','pb','rumah_apung'],4=>['pb','omah_sekeng','rumah_apung'],5=>['flpp','oemah_lestari','rumah_apung'],6=>['flpp','oemah_lestari','rumah_apung'],7=>['flpp','oemah_lestari','rumah_apung'],8=>['flpp','oemah_lestari','rumah_apung'],9=>['oemah_lestari','rumah_apung'],10=>['oemah_lestari','rumah_apung']];
foreach($expected as $desil=>$codes) cek($rules->route_candidates($desil)===$codes,"Routing desil $desil tepat");
cek(in_array('pb',$rules->route_candidates(3),TRUE) && !in_array('omah_sekeng',$rules->route_candidates(3),TRUE),'SIM-03 desil 3 merutekan PB, bukan Omah Sekeng');
$existing=['assessment_track'=>'existing_house','roof_condition_code'=>'moderate_damage'];
cek($rules->evaluate('rtlh',$existing,['welfare_decile'=>2])['eligibility_status']==='eligible','RTLH kerusakan sedang memenuhi simulasi');
cek($rules->evaluate('rtlh',['assessment_track'=>'existing_house','latrine_type_code'=>'none'],['welfare_decile'=>2])['reason_codes']===['SIM_RTLH_SANITATION'],'RTLH sanitasi kritis menjadi alasan simulasi');
$land=['assessment_track'=>'candidate_land','owns_candidate_land'=>1,'candidate_land_address'=>'Jl. Uji','candidate_land_title_code'=>'hm','candidate_land_origin_code'=>'inheritance','land_length_m'=>8,'land_width_m'=>12,'land_area_m2'=>96];
cek($rules->evaluate('pb',$land,['welfare_decile'=>3])['eligibility_status']==='eligible','PB memeriksa fakta calon lahan');
cek($rules->evaluate('omah_sekeng',['assessment_track'=>'existing_house'],['welfare_decile'=>4,'self_help_capability_code'=>'capable'])['eligibility_status']==='needs_data','Desil 4 Omah Sekeng menunggu kebutuhan rumah/perbaikan terkonfirmasi');
foreach(['flpp','oemah_lestari'] as $code) cek($rules->evaluate($code,['assessment_track'=>'financing','housing_status_code'=>'rent','has_other_house'=>'0'],['welfare_decile'=>6,'income_band_code'=>'6_8'])['eligibility_status']==='potential',strtoupper($code).' pembiayaan + penghasilan berpotensi');
foreach(range(1,10) as $desil) cek($rules->evaluate('rumah_apung',[],['welfare_decile'=>$desil])['eligibility_status']==='needs_data','Rumah Apung desil '.$desil.' selalu needs_data');

// Jalur HTTP nyata memakai SIM-01 (desil 2) hingga rekomendasi dipersist server.
// Empat policy, bukan cuma warga_lookup: `warga_submit` batasnya 5 per JAM.
// Dimensi `nik` ikut dipinjam karena ember NIK fixture ini dipakai bersama r4
// dan r6.
foreach(['warga_lookup','warga_submit','warga_start_revision','admin_queue_decision'] as $policy)preserve_rate_ips($db,$policy);
preserve_rate_key($db,'warga_lookup','nik',hash_hmac('sha256','0000000000000001',$env['KPKP_DATA_PEPPER']));
nik_bebas($db,$env,'0000000000000001');
[$user,$email]=make_user($db,'owner'); $owner=login($email);
$r=$owner->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']); wajib(redirect_ok($r),'Lookup SIM-01');
$d=draft($db,$user); wajib(redirect_ok(post_step($owner,$d,'housing_family',matriks())),'Simpan isian matriks');
$d=draft($db,$user); if ($d['current_step']==='preliminary_recommendation') { wajib(redirect_ok(post_step($owner,$d,'preliminary_recommendation',[])),'Lewati rekomendasi awal'); $d=draft($db,$user); }
wajib(redirect_ok(post_step($owner,$d,'housing_family_detail',citizen()+housing('owned'))),'Simpan data warga + rumah eksisting');
$d=draft($db,$user); $building=['foundation_condition_code'=>'good','column_condition_code'=>'good','beam_condition_code'=>'moderate_damage','roof_frame_condition_code'=>'good','floor_material_code'=>'cement_plaster','floor_condition_code'=>'good','wall_material_code'=>'wall','wall_condition_code'=>'good','roof_material_code'=>'clay_tile','roof_condition_code'=>'good']; wajib(redirect_ok(post_step($owner,$d,'building_condition',$building)),'Simpan kerusakan RTLH');
$d=draft($db,$user); $san=['has_window'=>'1','has_ventilation'=>'1','water_source_code'=>'well','latrine_type_code'=>'swan_neck','feces_disposal_code'=>'septic_tank','septic_distance_code'=>'gte_10','lighting_source_code'=>'pln','cooking_fuel_code'=>'electric_gas']; wajib(redirect_ok(post_step($owner,$d,'sanitation',$san)),'Simpan sanitasi');
$d=draft($db,$user); wajib(redirect_ok(post_step($owner,$d,'location_evidence',['location_lat'=>'-7.123456','location_lng'=>'110.123456','location_accuracy_m'=>'8'])),'Simpan lokasi dan hitung rekomendasi server');
$d=draft($db,$user); $rows=recommendations($db,$d['id']); $rtlh=by_code($rows,'rtlh'); $pb=by_code($rows,'pb'); $apung=by_code($rows,'rumah_apung');
$eligiblePage=$owner->get('warga/pendataan'); $eligibleAction=strpos($eligiblePage['body'],'Program dapat diajukan')!==FALSE && strpos($eligiblePage['body'],'Ajukan program yang dipilih')!==FALSE && strpos($eligiblePage['body'],'name="recommendation_id"')!==FALSE;
$db->run("UPDATE sf_rekomendasi_penilaian SET eligibility_status='needs_data' WHERE assessment_id=? AND ruleset_version='SIM-2026-01'",[$d['id']]); $blockedPage=$owner->get('warga/pendataan'); $blockedAction=strpos($blockedPage['body'],'Pengajuan belum dapat dikirim')!==FALSE && strpos($blockedPage['body'],'Lihat layanan lain')!==FALSE && strpos($blockedPage['body'],'Ajukan program yang dipilih')===FALSE;
foreach($rows as $row) $db->run("UPDATE sf_rekomendasi_penilaian r JOIN sf_programs p ON p.id=r.program_id SET r.eligibility_status=? WHERE r.assessment_id=? AND r.ruleset_version=? AND p.kode_program=?",[$row['eligibility_status'],$d['id'],$row['ruleset_version'],$row['kode_program']]);
cek(count($rows)===3 && $rtlh['eligibility_status']==='eligible' && $pb && $apung['eligibility_status']==='needs_data' && $eligibleAction && $blockedAction,'Hasil server dan tindakan Review sesuai status rekomendasi');
cek(json_decode($rtlh['reason_codes_json'],TRUE)===['SIM_RTLH_DAMAGE'],'Reason code tersimpan sebagai JSON dapat didekode');
$hash1=$rtlh['input_snapshot_sha256']; cek((bool)preg_match('/^[a-f0-9]{64}$/',$hash1),'Hash input 64 hex tersimpan');

// Jalankan ulang ruleset versi sama sambil menyisipkan versi lama; versi lama harus tetap ada dan hasil sama idempoten.
$db->run("INSERT INTO sf_rekomendasi_penilaian (assessment_id,program_id,ruleset_version,eligibility_status,reason_codes_json,input_snapshot_sha256,evaluated_at) SELECT ?,id,'SIM-OLDER','needs_data','[\"SIM_LEGACY\"]',REPEAT('a',64),NOW() FROM sf_programs WHERE kode_program='rtlh'",[$d['id']]);
$db->run("UPDATE sf_penilaian_perumahan SET current_step='location_evidence', lock_version=lock_version+1 WHERE id=?",[$d['id']]); $d=draft($db,$user);
wajib(redirect_ok(post_step($owner,$d,'location_evidence',['location_lat'=>'-7.123456','location_lng'=>'110.123456','location_accuracy_m'=>'8'])),'Hitung ulang ruleset versi sama');
$rows=recommendations($db,$d['id']); cek((int)$db->scalar("SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=? AND ruleset_version='SIM-OLDER'",[$d['id']])===1,'Ruleset versi lain tidak dihapus');
cek((int)$db->scalar("SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=? AND ruleset_version='SIM-2026-01'",[$d['id']])===3,'Ganti ruleset aktif idempoten tanpa duplikat');
cek(by_code($rows,'rtlh')['input_snapshot_sha256']===$hash1,'Hash deterministik untuk input sama');

// Mengubah input relevan lewat jalur warga harus menghasilkan hash berbeda.
$db->run("UPDATE sf_penilaian_perumahan SET current_step='building_condition', lock_version=lock_version+1 WHERE id=?",[$d['id']]); $d=draft($db,$user); $building['beam_condition_code']='good'; $building['roof_condition_code']='moderate_damage'; wajib(redirect_ok(post_step($owner,$d,'building_condition',$building)),'Ubah kondisi relevan');
$d=draft($db,$user); wajib(redirect_ok(post_step($owner,$d,'sanitation',$san)),'Simpan ulang sanitasi'); $d=draft($db,$user); wajib(redirect_ok(post_step($owner,$d,'location_evidence',['location_lat'=>'-7.123456','location_lng'=>'110.123456','location_accuracy_m'=>'8'])),'Hitung ulang setelah input berubah');
$hash2=by_code(recommendations($db,$d['id']),'rtlh')['input_snapshot_sha256']; cek($hash2!==$hash1,'Hash berubah ketika input scoring relevan berubah');

// Program hilang harus membatalkan seluruh write (termasuk delete versi aktif).
$before=(int)$db->scalar("SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=? AND ruleset_version='SIM-2026-01'",[$d['id']]);
$db->run("UPDATE sf_programs SET kode_program='pb_uji_hilang' WHERE kode_program='pb'");
$db->run("UPDATE sf_penilaian_perumahan SET current_step='location_evidence', lock_version=lock_version+1 WHERE id=?",[$d['id']]); $d=draft($db,$user); $r=post_step($owner,$d,'location_evidence',['location_lat'=>'-7.123456','location_lng'=>'110.123456','location_accuracy_m'=>'8']);
$db->run("UPDATE sf_programs SET kode_program='pb' WHERE kode_program='pb_uji_hilang'"); $GLOBALS['restored_pb']=TRUE;
cek(redirect_ok($r) && (int)$db->scalar("SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=? AND ruleset_version='SIM-2026-01'",[$d['id']])===$before,'Program hilang menggagalkan write secara atomik');

// Pemilik lain tidak dapat membaca/menulis draft beserta rekomendasinya.
[$attacker,$attackerEmail]=make_user($db,'attacker'); $other=login($attackerEmail); $count_before=(int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=?',[$d['id']]); $forge=$other->post('warga/pendataan',['action'=>'save','step'=>'location_evidence','direction'=>'next','assessment_id'=>$d['id'],'lock_version'=>$d['lock_version'],'location_lat'=>'-7','location_lng'=>'110','location_accuracy_m'=>'8']); $page=$other->get('warga/pendataan'); cek(redirect_ok($forge) && (int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=?',[$d['id']])===$count_before && strpos($page['body'],'Kondisi kerusakan rumah menjadi pertimbangan simulasi.')===FALSE,'Reader/writer rekomendasi dibatasi ownership');

echo "\n=== RINGKASAN ===\n{$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal.\n";
exit($GLOBALS['gagal'] ? 1 : 0);
