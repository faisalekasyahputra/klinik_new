<?php
/**
 * Uji R6: submit immutable, antrean wilayah, revisi, dan keputusan admin.
 * Jalankan melalui Apache XAMPP:
 *   php docs/engineering/uji_pendataan_warga_r6.php
 *
 * Env opsional: UJI_BASE_URL, UJI_WARGA_PASSWORD
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('PASSWORD', getenv('UJI_WARGA_PASSWORD') ?: 'UjiWargaR6!');
$GLOBALS['total'] = $GLOBALS['gagal'] = 0;
$GLOBALS['users'] = $GLOBALS['assessments'] = $GLOBALS['snapshots'] = [];
$GLOBALS['physical_files'] = [];
$GLOBALS['db'] = NULL;
$GLOBALS['private_root'] = NULL;
$GLOBALS['rate_limit_original'] = [];

function cek($ok, $label) { $GLOBALS['total']++; echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n"; if (!$ok) $GLOBALS['gagal']++; return $ok; }
function wajib($ok, $label) { if (!cek($ok, $label)) exit(1); }
function redirect_ok($r) { return in_array($r['status'], [302, 303], TRUE); }
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) continue;
        [$key, $value] = explode('=', $line, 2);
        if (!array_key_exists(trim($key), $out)) $out[trim($key)] = trim($value);
    }
    foreach (['DB_HOST','DB_USER','DB_PASS','DB_NAME','PRIVATE_UPLOADS_PATH'] as $key) {
        if (getenv($key) !== FALSE) $out[$key] = getenv($key);
    }
    return $out;
}
class Db {
    private $m;
    function __construct($e) {
        $this->m = new mysqli($e['DB_HOST'], $e['DB_USER'], $e['DB_PASS'] ?? '', $e['DB_NAME']);
        if ($this->m->connect_error) die("Koneksi DB gagal: {$this->m->connect_error}\n");
    }
    function run($sql, $p = []) { $s=$this->prep($sql,$p); $id=$s->insert_id; $s->close(); return $id; }
    function row($sql, $p = []) { $s=$this->prep($sql,$p); $r=$s->get_result()->fetch_assoc(); $s->close(); return $r ?: NULL; }
    function rows($sql, $p = []) { $s=$this->prep($sql,$p); $r=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); return $r; }
    function scalar($sql, $p = []) { $r=$this->row($sql,$p); return $r ? reset($r) : NULL; }
    private function prep($sql, $p) {
        $s=$this->m->prepare($sql); if(!$s) die("Prepare gagal: {$this->m->error}\n");
        if($p){$types=str_repeat('s',count($p));$s->bind_param($types,...$p);}
        if(!$s->execute()) die("Query gagal: {$s->error}\n");
        return $s;
    }
}
class Session {
    private $cookie;
    public $csrf = NULL;
    function __construct() { $this->cookie=tempnam(sys_get_temp_dir(),'uji_r6_'); }
    function __destruct() { @unlink($this->cookie); }
    function get($path) { return $this->call($path,[CURLOPT_HTTPGET=>TRUE]); }
    function post($path,$fields) {
        if($this->csrf)$fields['csrf_kpkp_token']=$this->csrf;
        return $this->call($path,[CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>http_build_query($fields),CURLOPT_HTTPHEADER=>['X-Requested-With: XMLHttpRequest']]);
    }
    function upload($path,$fields,$fileField,$filePath,$filename) {
        if($this->csrf)$fields['csrf_kpkp_token']=$this->csrf;
        $fields[$fileField]=new CURLFile($filePath,'image/png',$filename);
        return $this->call($path,[CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>$fields]);
    }
    function asyncPost($path,$fields) {
        if($this->csrf)$fields['csrf_kpkp_token']=$this->csrf;
        $ch=curl_init(BASE_URL.'/'.ltrim($path,'/'));
        curl_setopt_array($ch,[CURLOPT_POST=>TRUE,CURLOPT_POSTFIELDS=>http_build_query($fields),CURLOPT_HTTPHEADER=>['X-Requested-With: XMLHttpRequest'],CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_COOKIEJAR=>$this->cookie,CURLOPT_COOKIEFILE=>$this->cookie,CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_HEADER=>TRUE,CURLOPT_TIMEOUT=>30]);
        return $ch;
    }
    private function call($path,$options) {
        $ch=curl_init(BASE_URL.'/'.ltrim($path,'/'));
        curl_setopt_array($ch,$options+[CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_COOKIEJAR=>$this->cookie,CURLOPT_COOKIEFILE=>$this->cookie,CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_HEADER=>TRUE,CURLOPT_TIMEOUT=>30]);
        $raw=curl_exec($ch); if($raw===FALSE)die('curl gagal: '.curl_error($ch)."\n");
        $info=curl_getinfo($ch); curl_close($ch); $body=substr($raw,$info['header_size']);
        if(preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/',$body,$m))$this->csrf=$m[1];
        return ['status'=>$info['http_code'],'body'=>$body];
    }
}
function parallel_posts(array $handles) {
    $mh=curl_multi_init();
    foreach($handles as $ch)curl_multi_add_handle($mh,$ch);
    do{$status=curl_multi_exec($mh,$running);if($running)curl_multi_select($mh,1.0);}while($running&&$status===CURLM_OK);
    $out=[];
    foreach($handles as $ch){$out[]=['status'=>curl_getinfo($ch,CURLINFO_HTTP_CODE),'body'=>curl_multi_getcontent($ch)];curl_multi_remove_handle($mh,$ch);curl_close($ch);}
    curl_multi_close($mh); return $out;
}
function login_session($email,$landing) {
    $s=new Session(); $s->get('Auth/login');
    $r=$s->post('Auth/do_login',['email'=>$email,'password'=>PASSWORD]);
    wajib($r['status']===200&&(json_decode($r['body'],TRUE)['status']??'')==='success',"Login $email");
    $s->get($landing); return $s;
}
function make_user($db,$suffix,$role='warga',$kabupaten=NULL) {
    $email='uji_r6_'.$suffix.'_'.time().'_'.mt_rand(1000,9999).'@example.test';
    $username='uji_r6_'.$suffix.'_'.mt_rand(1000,9999);
    $id=$db->run("INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,kabupaten_id,created_at) VALUES (?,?,'Uji R6',?,?,'active',1,?,NOW())",[$email,password_hash(PASSWORD,PASSWORD_BCRYPT),$username,$role,$kabupaten]);
    $GLOBALS['users'][]=$id; return [$id,$email];
}
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
function draft($db,$user) {
    $r=$db->row("SELECT * FROM sf_penilaian_perumahan WHERE user_id=? AND status='draft' ORDER BY id DESC LIMIT 1",[$user]);
    wajib((bool)$r,'Draft warga tersedia');
    if(!in_array((int)$r['id'],$GLOBALS['assessments'],TRUE))$GLOBALS['assessments'][]=(int)$r['id'];
    if(!empty($r['simperum_snapshot_id'])&&!in_array((int)$r['simperum_snapshot_id'],$GLOBALS['snapshots'],TRUE))$GLOBALS['snapshots'][]=(int)$r['simperum_snapshot_id'];
    return $r;
}
function post_step($s,$d,$step,$data){return $s->post('warga/pendataan',$data+['action'=>'save','step'=>$step,'direction'=>'next','assessment_id'=>$d['id'],'lock_version'=>$d['lock_version']]);}
function citizen($name='Warga Uji R6'){return ['family_card_number'=>'0000000000006666','full_name'=>$name,'address'=>'Alamat Koreksi Uji R6','phone'=>'081234567890','birth_date'=>'1980-01-01','gender_code'=>'male','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'private_employee','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable'];}
function housing(){return ['housing_status_code'=>'owned','land_title_code'=>'hm','area_condition_code'=>'slum','occupant_count'=>'3','family_count'=>'1','house_area_m2'=>'36','has_other_land'=>'0','has_other_house'=>'0','owns_candidate_land'=>'0','assistance_source_code'=>'','assistance_year'=>''];}
function building($roof='good'){return ['foundation_condition_code'=>'good','column_condition_code'=>'good','beam_condition_code'=>'moderate_damage','roof_frame_condition_code'=>'good','floor_material_code'=>'cement_plaster','floor_condition_code'=>'good','wall_material_code'=>'wall','wall_condition_code'=>'good','roof_material_code'=>'clay_tile','roof_condition_code'=>$roof];}
function sanitation(){return ['has_window'=>'1','has_ventilation'=>'1','water_source_code'=>'well','latrine_type_code'=>'swan_neck','feces_disposal_code'=>'septic_tank','septic_distance_code'=>'gte_10','lighting_source_code'=>'pln','cooking_fuel_code'=>'electric_gas'];}
function complete_sim01($db,$user,$session,$name) {
    $r=$session->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']); wajib(redirect_ok($r),'Lookup SIM-01');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'citizen_data',citizen($name))),'Simpan data warga');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'housing_family',housing())),'Pilih rumah eksisting');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'building_condition',building())),'Simpan kondisi RTLH');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'sanitation',sanitation())),'Simpan sanitasi');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'location_evidence',['location_lat'=>'-7.005145','location_lng'=>'110.438125','location_accuracy_m'=>'8'])),'Hitung rekomendasi R5');
    return draft($db,$user);
}
function png_fixture(){
    $path=tempnam(sys_get_temp_dir(),'uji_r6_png_').'.png';
    file_put_contents($path,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLKUwAAAABJRU5ErkJggg=='));
    return $path;
}
function raw_assessment($db,$id){return $db->row('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$id]);}
function preserve_rate_key($db,$policy,$dimension,$value){
    $key=hash('sha256',$policy.':'.$dimension.':'.$value);
    if(array_key_exists($key,$GLOBALS['rate_limit_original']))return;
    $GLOBALS['rate_limit_original'][$key]=$db->row(
        'SELECT limit_key,window_started_at,failed_attempts FROM sys_rate_limits WHERE limit_key=?',
        [$key]
    );
    $db->run('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);
}
function preserve_rate_ips($db,$policy){
    preserve_rate_key($db,$policy,'ip','127.0.0.1');
    preserve_rate_key($db,$policy,'ip','::1');
}
function cleanup(){
    $db=$GLOBALS['db']; if(!$db)return;
    foreach(array_unique($GLOBALS['physical_files']) as $path)if(is_file($path))@unlink($path);
    foreach(array_unique($GLOBALS['assessments']) as $id){
        $dir=rtrim((string)$GLOBALS['private_root'],'/\\').DIRECTORY_SEPARATOR.'warga_assessment'.DIRECTORY_SEPARATOR.$id;
        if(is_dir($dir)){foreach(glob($dir.DIRECTORY_SEPARATOR.'*')?:[] as $file)@unlink($file);@rmdir($dir);}
    }
    if($GLOBALS['users']){
        $ids=implode(',',array_map('intval',array_unique($GLOBALS['users'])));
        $db->run("DELETE FROM sf_housing_queue WHERE user_id IN ($ids)");
        $db->run("DELETE FROM sf_penilaian_perumahan WHERE user_id IN ($ids)");
        $db->run("DELETE FROM sf_profil_warga WHERE user_id IN ($ids)");
    }
    foreach(array_unique($GLOBALS['snapshots']) as $id)$db->run('DELETE FROM sf_rekaman_simperum WHERE id=?',[$id]);
    foreach(array_unique($GLOBALS['users']) as $id)$db->run('DELETE FROM usr_users WHERE id=?',[$id]);
    foreach($GLOBALS['rate_limit_original'] as $key=>$row){
        $db->run('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);
        if($row){
            $db->run(
                'INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)',
                [$row['limit_key'],$row['window_started_at'],$row['failed_attempts']]
            );
        }
    }
}
register_shutdown_function('cleanup');

if(!is_file(ENV_PATH))die(".env tidak ditemukan.\n");
$env=env_config(ENV_PATH);$GLOBALS['db']=$db=new Db($env);
$root=$env['PRIVATE_UPLOADS_PATH']??'';if($root==='')die("PRIVATE_UPLOADS_PATH wajib untuk uji R6.\n");
if(!preg_match('#^(?:[A-Za-z]:|[/\\\\])#',$root))$root=dirname(__DIR__,2).DIRECTORY_SEPARATOR.$root;
$GLOBALS['private_root']=$root;
nik_bebas($db,$env,'0000000000000001');
foreach(['warga_lookup','warga_submit','warga_start_revision','admin_queue_decision'] as $policy)preserve_rate_ips($db,$policy);
preserve_rate_key(
    $db,
    'warga_lookup',
    'nik',
    hash_hmac('sha256','0000000000000001',$env['KPKP_DATA_PEPPER'])
);
echo "=== UJI PENDATAAN WARGA R6 ===\nTarget: ".BASE_URL." | DB: {$env['DB_NAME']}\n\n";

// Dua assessment diperlukan agar manipulasi recommendation_id lintas pemilik benar-benar diuji.
[$ownerId,$ownerEmail]=make_user($db,'owner');
foreach(['warga_lookup','warga_submit','warga_start_revision'] as $policy)preserve_rate_key($db,$policy,'account',$ownerId);
$owner=login_session($ownerEmail,'warga/pendataan');
$d=complete_sim01($db,$ownerId,$owner,'Pemilik R6');
preserve_rate_key($db,'warga_submit','object',$d['id']);
$rtlh=$db->row("SELECT r.*,p.kode_program FROM sf_rekomendasi_penilaian r JOIN sf_programs p ON p.id=r.program_id WHERE r.assessment_id=? AND p.kode_program='rtlh'",[$d['id']]);
$needs=$db->row("SELECT r.*,p.kode_program FROM sf_rekomendasi_penilaian r JOIN sf_programs p ON p.id=r.program_id WHERE r.assessment_id=? AND r.eligibility_status='needs_data' ORDER BY r.id LIMIT 1",[$d['id']]);
wajib($rtlh&&$rtlh['eligibility_status']==='eligible','SIM-01 memilih rekomendasi RTLH eligible');

[$otherId,$otherEmail]=make_user($db,'other');
$otherAssessment=$db->run("INSERT INTO sf_penilaian_perumahan (user_id,kabupaten_id,assessment_track,status,current_step,version_no,lock_version,source_mode) VALUES (?,3374,'existing_house','draft','review',1,0,'simulation')",[$otherId]);
$GLOBALS['assessments'][]=(int)$otherAssessment;
$otherRecId=$db->run("INSERT INTO sf_rekomendasi_penilaian (assessment_id,program_id,ruleset_version,eligibility_status,reason_codes_json,input_snapshot_sha256,evaluated_at) VALUES (?,?,?,'eligible','[\"SIM_RTLH_DAMAGE\"]',REPEAT('b',64),NOW())",[$otherAssessment,$rtlh['program_id'],$rtlh['ruleset_version']]);
$otherRec=$db->row('SELECT * FROM sf_rekomendasi_penilaian WHERE id=?',[$otherRecId]);

// Bukti pada versi awal harus tetap dapat dibaca setelah dibuat revisi.
$png=png_fixture();$up=$owner->upload('warga/pendataan',['action'=>'upload','assessment_id'=>$d['id'],'file_kind'=>'self_photo'],'self_photo',$png,'r6.png');@unlink($png);
wajib(redirect_ok($up),'Unggah bukti privat sebelum submit');
$file=$db->row("SELECT * FROM sf_berkas_penilaian WHERE assessment_id=? AND file_kind='self_photo'",[$d['id']]);wajib((bool)$file,'Ledger bukti versi awal tersedia');
$physical=rtrim($root,'/\\').DIRECTORY_SEPARATOR.'warga_assessment'.DIRECTORY_SEPARATOR.$d['id'].DIRECTORY_SEPARATOR.basename($file['private_path']);
$GLOBALS['physical_files'][]=$physical;wajib(is_file($physical),'Berkas fisik versi awal tersedia');

// Manipulasi recommendation lintas assessment dan needs_data harus gagal tanpa side effect.
$r=$owner->post('warga/pendataan',['action'=>'submit','assessment_id'=>$d['id'],'recommendation_id'=>$otherRec['id']]);
cek(redirect_ok($r)&&(int)$db->scalar('SELECT COUNT(*) FROM sf_housing_queue WHERE user_id=?',[$ownerId])===0&&raw_assessment($db,$d['id'])['status']==='draft','Recommendation milik assessment lain ditolak');
$r=$owner->post('warga/pendataan',['action'=>'submit','assessment_id'=>$d['id'],'recommendation_id'=>$needs['id']]);
cek(redirect_ok($r)&&(int)$db->scalar('SELECT COUNT(*) FROM sf_housing_queue WHERE user_id=?',[$ownerId])===0&&raw_assessment($db,$d['id'])['status']==='draft','Recommendation needs_data ditolak');

$snapshotBefore=$db->row('SELECT id,payload_ciphertext,payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d['simperum_snapshot_id']]);
$assessmentBefore=raw_assessment($db,$d['id']);$fileBefore=$db->row('SELECT file_kind,private_path,sha256,size_bytes FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
$r=$owner->post('warga/pendataan',['action'=>'submit','assessment_id'=>$d['id'],'recommendation_id'=>$rtlh['id']]);wajib(redirect_ok($r),'Submit assessment valid');
$queue=$db->row('SELECT * FROM sf_housing_queue WHERE user_id=?',[$ownerId]);$submitted=raw_assessment($db,$d['id']);
cek($queue&&$submitted['status']==='submitted'&&!empty($submitted['submitted_at']),'Submit atomik membuat assessment submitted dan queue');
cek(!empty($submitted['profile_snapshot_ciphertext'])&&strpos($submitted['profile_snapshot_ciphertext'],'Pemilik R6')===FALSE&&strpos($submitted['profile_snapshot_ciphertext'],'{')!==0,'Snapshot profil tersimpan terenkripsi');
cek($queue['nik_pengaju']===NULL&&$queue['nama_lengkap']===NULL&&$queue['source_mode']==='simulation','Queue baru tidak menyimpan PII plaintext dan berlabel simulasi');
cek((int)$queue['kabupaten_id']===3374&&(int)$queue['assessment_id']===(int)$d['id']&&(int)$queue['recommendation_id']===(int)$rtlh['id']&&(int)$queue['program_id']===(int)$rtlh['program_id'],'Queue menyimpan scope dan rekomendasi server');
$queueId=(int)$queue['id'];$ticket=$queue['ticket_code'];
preserve_rate_key($db,'warga_start_revision','object',$queueId);
preserve_rate_key($db,'admin_queue_decision','object',$queueId);

// Idempotency submit: request sama mengembalikan queue/ticket yang sama.
$owner->post('warga/pendataan',['action'=>'submit','assessment_id'=>$d['id'],'recommendation_id'=>$rtlh['id']]);
cek((int)$db->scalar('SELECT COUNT(*) FROM sf_housing_queue WHERE user_id=?',[$ownerId])===1&&$db->scalar('SELECT ticket_code FROM sf_housing_queue WHERE user_id=?',[$ownerId])===$ticket,'Double submit tetap satu queue dan satu tiket');

// Semua writer assessment submitted ditolak dan tidak mengubah row/ledger/rekomendasi.
$recCount=(int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=?',[$d['id']]);
$owner->post('warga/pendataan',['action'=>'save','step'=>'review','direction'=>'next','assessment_id'=>$d['id'],'lock_version'=>$submitted['lock_version']]);
$badPng=png_fixture();$owner->upload('warga/pendataan',['action'=>'upload','assessment_id'=>$d['id'],'file_kind'=>'self_photo'],'self_photo',$badPng,'blocked.png');@unlink($badPng);
$afterBlocked=raw_assessment($db,$d['id']);$fileBlocked=$db->row('SELECT file_kind,private_path,sha256,size_bytes FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
cek($afterBlocked['updated_at']===$submitted['updated_at']&&$fileBlocked==$fileBefore&&(int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE assessment_id=?',[$d['id']])===$recCount,'Assessment submitted menolak edit/upload/mutasi rekomendasi');

// Admin Semarang dapat membaca detail dan bukti; wilayah lain tidak.
[$admin1Id,$admin1Email]=make_user($db,'admin_semarang_1','admin_kabkota',3374);
[$admin2Id,$admin2Email]=make_user($db,'admin_semarang_2','admin_kabkota',3374);
[$foreignId,$foreignEmail]=make_user($db,'admin_banyumas','admin_kabkota',3302);
foreach([$admin1Id,$admin2Id,$foreignId] as $adminId)preserve_rate_key($db,'admin_queue_decision','account',$adminId);
$admin1=login_session($admin1Email,'Admin_Kabkota');$admin2=login_session($admin2Email,'Admin_Kabkota');$foreign=login_session($foreignEmail,'Admin_Kabkota');
$detail=$admin1->get('Admin_Kabkota/detail/'.$queueId);$evidence=$admin1->get('Admin_Kabkota/evidence/'.$queueId.'/self_photo');
cek($detail['status']===200&&strpos($detail['body'],$ticket)!==FALSE&&strpos($detail['body'],'Mode Simulasi')!==FALSE&&strpos($detail['body'],'Dipilih warga')!==FALSE,'Admin Semarang melihat detail, ruleset, dan mode simulasi');
cek($evidence['status']===200&&$evidence['body']!=='','Admin Semarang membaca bukti privat');
cek($foreign->get('Admin_Kabkota/detail/'.$queueId)['status']===404&&$foreign->get('Admin_Kabkota/evidence/'.$queueId.'/self_photo')['status']===404,'Admin wilayah lain gagal membaca detail dan bukti');
$foreign->post('Admin_Kabkota/update_status',['queue_id'=>$queueId,'from_status'=>'pending','status'=>'approved','catatan_admin'=>'']);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id=?',[$queueId])==='pending','Admin wilayah lain gagal menulis keputusan');

// Catatan wajib, lalu dua keputusan paralel dari status asal yang sama: tepat satu history lahir.
$admin1->post('Admin_Kabkota/update_status',['queue_id'=>$queueId,'from_status'=>'pending','status'=>'needs_revision','catatan_admin'=>'']);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id=?',[$queueId])==='pending','needs_revision tanpa catatan ditolak');
$admin1->get('Admin_Kabkota/detail/'.$queueId);$admin2->get('Admin_Kabkota/detail/'.$queueId);
$beforeHistory=(int)$db->scalar("SELECT COUNT(*) FROM sf_riwayat_keputusan_antrean WHERE queue_id=? AND from_status='pending' AND to_status='needs_revision'",[$queueId]);
parallel_posts([
    $admin1->asyncPost('Admin_Kabkota/update_status',['queue_id'=>$queueId,'from_status'=>'pending','status'=>'needs_revision','catatan_admin'=>'Perbaiki data atap A']),
    $admin2->asyncPost('Admin_Kabkota/update_status',['queue_id'=>$queueId,'from_status'=>'pending','status'=>'needs_revision','catatan_admin'=>'Perbaiki data atap B']),
]);
$queue=$db->row('SELECT * FROM sf_housing_queue WHERE id=?',[$queueId]);
$afterHistory=(int)$db->scalar("SELECT COUNT(*) FROM sf_riwayat_keputusan_antrean WHERE queue_id=? AND from_status='pending' AND to_status='needs_revision'",[$queueId]);
cek($queue['status_antrean']==='needs_revision'&&$afterHistory===$beforeHistory+1&&in_array($queue['catatan_admin'],['Perbaiki data atap A','Perbaiki data atap B'],TRUE),'CAS dua keputusan hanya menerima satu transisi');

// /akun menampilkan status, catatan, dan aksi perbaikan.
$akun=$owner->get('akun');
cek($akun['status']===200&&strpos($akun['body'],'Perlu Perbaikan')!==FALSE&&strpos($akun['body'],$queue['catatan_admin'])!==FALSE&&strpos($akun['body'],'Mulai Perbaikan')!==FALSE,'/akun menampilkan status, catatan, dan aksi perbaikan');

// Start revision dua kali harus menghasilkan satu draft version+1 dan tidak mengubah sumber.
$owner->post('warga/pendataan',['action'=>'start_revision','queue_id'=>$queueId]);
$revision=draft($db,$ownerId);
preserve_rate_key($db,'warga_submit','object',$revision['id']);
$owner->post('warga/pendataan',['action'=>'start_revision','queue_id'=>$queueId]);
$revisionAgain=draft($db,$ownerId);
cek((int)$revision['id']===(int)$revisionAgain['id']&&(int)$revision['previous_version_id']===(int)$d['id']&&(int)$revision['version_no']===(int)$d['version_no']+1,'Start revision idempoten, version+1, dan previous link benar');
$oldAfterRevision=raw_assessment($db,$d['id']);$oldFileAfterRevision=$db->row('SELECT file_kind,private_path,sha256,size_bytes FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
$revisionFile=$db->row("SELECT * FROM sf_berkas_penilaian WHERE assessment_id=? AND file_kind='self_photo'",[$revision['id']]);
cek($oldAfterRevision==$submitted&&$oldFileAfterRevision==$fileBefore&&$revisionFile['private_path']===$file['private_path']&&is_file($physical),'Versi lama, snapshot data, ledger, dan berkas tetap utuh/readable');

// Edit seluruh langkah revisi, rescore, dan resubmit rekomendasi server baru.
$r=post_step($owner,$revision,'citizen_data',citizen('Pemilik R6 Direvisi'));wajib(redirect_ok($r),'Revisi data warga');
$revision=draft($db,$ownerId);wajib(redirect_ok(post_step($owner,$revision,'housing_family',housing())),'Revisi rumah keluarga');
$revision=draft($db,$ownerId);$revisedBuilding=building('moderate_damage');wajib(redirect_ok(post_step($owner,$revision,'building_condition',$revisedBuilding)),'Revisi kondisi bangunan');
$revision=draft($db,$ownerId);wajib(redirect_ok(post_step($owner,$revision,'sanitation',sanitation())),'Revisi sanitasi');
$revision=draft($db,$ownerId);wajib(redirect_ok(post_step($owner,$revision,'location_evidence',['location_lat'=>'-7.005145','location_lng'=>'110.438125','location_accuracy_m'=>'8'])),'Rescore revisi');
$revision=draft($db,$ownerId);$revisedRtlh=$db->row("SELECT r.* FROM sf_rekomendasi_penilaian r JOIN sf_programs p ON p.id=r.program_id WHERE r.assessment_id=? AND p.kode_program='rtlh' AND r.eligibility_status='eligible'",[$revision['id']]);wajib((bool)$revisedRtlh,'Rekomendasi RTLH revisi tersedia');
$owner->post('warga/pendataan',['action'=>'submit','assessment_id'=>$revision['id'],'recommendation_id'=>$revisedRtlh['id']]);
$queueAfterResubmit=$db->row('SELECT * FROM sf_housing_queue WHERE id=?',[$queueId]);$oldFinal=raw_assessment($db,$d['id']);$revisionSubmitted=raw_assessment($db,$revision['id']);
cek((int)$db->scalar('SELECT COUNT(*) FROM sf_housing_queue WHERE user_id=?',[$ownerId])===1&&$queueAfterResubmit['ticket_code']===$ticket&&(int)$queueAfterResubmit['assessment_id']===(int)$revision['id']&&$queueAfterResubmit['status_antrean']==='pending','Resubmit memakai queue dan tiket yang sama');
cek($oldFinal['status']==='superseded'&&$revisionSubmitted['status']==='submitted','Versi lama superseded dan revisi submitted');
$history=$db->rows('SELECT from_status,to_status,note FROM sf_riwayat_keputusan_antrean WHERE queue_id=? ORDER BY id',[$queueId]);
cek(count($history)===3&&$history[0]['to_status']==='pending'&&$history[1]['to_status']==='needs_revision'&&$history[2]['from_status']==='needs_revision'&&$history[2]['to_status']==='pending','Riwayat submit, revisi, dan resubmit utuh');

// Admin menyetujui revisi; akun berubah menjadi read-only tanpa aksi revisi.
$admin1->get('Admin_Kabkota/detail/'.$queueId);
$admin1->post('Admin_Kabkota/update_status',['queue_id'=>$queueId,'from_status'=>'pending','status'=>'approved','catatan_admin'=>'Data sudah sesuai']);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id=?',[$queueId])==='approved','Admin menyetujui revisi');
$akun=$owner->get('akun');
cek(strpos($akun['body'],'Disetujui')!==FALSE&&strpos($akun['body'],$ticket)!==FALSE&&strpos($akun['body'],'Mulai Perbaikan')===FALSE,'/akun menampilkan approved dan tidak menawarkan revisi');

// Snapshot sumber dan isi submission lama tidak berubah selain lifecycle status resmi.
$snapshotAfter=$db->row('SELECT id,payload_ciphertext,payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d['simperum_snapshot_id']]);
$oldComparable=$oldFinal;$submittedComparable=$submitted;unset($oldComparable['status'],$submittedComparable['status'],$oldComparable['updated_at'],$submittedComparable['updated_at']);
cek($snapshotAfter==$snapshotBefore,'Ciphertext dan hash raw snapshot tidak berubah sepanjang revisi');
cek($oldComparable==$submittedComparable&&$oldFinal['status']==='superseded'&&$db->row('SELECT file_kind,private_path,sha256,size_bytes FROM sf_berkas_penilaian WHERE id=?',[$file['id']])==$fileBefore,'Submission lama immutable selain status superseded resmi');

echo "\n=== RINGKASAN ===\n{$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal.\n";
exit($GLOBALS['gagal']?1:0);
