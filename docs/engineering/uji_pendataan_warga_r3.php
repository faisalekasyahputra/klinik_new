<?php
/* HTTP check R3. Jalankan: php docs/engineering/uji_pendataan_warga_r3.php */
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV', dirname(__DIR__, 2) . '/.env');
$ok = 0; $fail = 0;
function check($yes, $label) { global $ok, $fail; echo ($yes ? '  OK    ' : '  GAGAL ') . $label . "\n"; $yes ? $ok++ : $fail++; return $yes; }
function envv() { $out=[]; foreach (file(ENV, FILE_IGNORE_NEW_LINES) as $line) { $line=trim($line); if ($line!=='' && $line[0]!=='#' && strpos($line,'=')!==FALSE) { [$k,$v]=explode('=',$line,2); if(!isset($out[$k])) $out[$k]=trim($v); } } return $out; }
class DB { public $m; function __construct($e) { $this->m=new mysqli($e['DB_HOST'],$e['DB_USER'],$e['DB_PASS']??'', $e['DB_NAME']); if($this->m->connect_error) die("DB gagal\n"); } function q($sql,$p=[]) { $s=$this->m->prepare($sql); if(!$s) die($this->m->error."\n"); if($p){$t=str_repeat('s',count($p));$s->bind_param($t,...$p);} if(!$s->execute()) die($s->error."\n"); return $s; } function one($sql,$p=[]) {$s=$this->q($sql,$p);$r=$s->get_result()->fetch_assoc();$s->close();return $r;} function val($sql,$p=[]) {$r=$this->one($sql,$p);return $r?reset($r):null;} function id($sql,$p=[]) {$s=$this->q($sql,$p);$id=$s->insert_id;$s->close();return $id;} }
 class HTTP { public $jar,$csrf=''; function __construct() {$this->jar=tempnam(sys_get_temp_dir(),'r3_');} function __destruct(){@unlink($this->jar);} function call($path,$fields=null){$c=curl_init(BASE.'/'.ltrim($path,'/'));$o=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_COOKIEFILE=>$this->jar,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20];if($fields!==null){$fields['csrf_kpkp_token']=$this->csrf;$o+=[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($fields)];if($path==='Auth/do_login')$o[CURLOPT_HTTPHEADER]=['X-Requested-With: XMLHttpRequest'];}curl_setopt_array($c,$o);$raw=curl_exec($c);$info=curl_getinfo($c);curl_close($c);$body=substr($raw,$info['header_size']);if(preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/',$body,$m))$this->csrf=$m[1];return [$info['http_code'],$body];} function login($email,$pass){$this->call('Auth/login');[$s,$b]=$this->call('Auth/do_login',['email'=>$email,'password'=>$pass]);$b=ltrim($b,"\xEF\xBB\xBF");return $s===200 && (($j=json_decode($b,true))['status']??'')==='success';} }
if(!is_file(ENV)) die(".env tidak ditemukan\n"); $db=new DB(envv()); $stamp=time(); $pass='R3Uji!123'; $ids=[];
function user($db,$email,$name,$pass){return $db->id("INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at) VALUES (?,?,?,?, 'warga','active',1,NOW())",[$email,password_hash($pass,PASSWORD_BCRYPT),$name,strtok($email,'@')]);}
function cleanup($db,$ids){foreach($ids as $id){$db->q('DELETE FROM usr_users WHERE id=?',[$id]);}
  foreach($GLOBALS['rate_asli'] as $key=>$row){$db->q('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);
    if($row)$db->q('INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)',[$row['limit_key'],$row['window_started_at'],$row['failed_attempts']]);}}
/* `warga_lookup` dibatasi 10 percobaan/60 detik dengan IP sebagai salah satu
   dimensinya, dan semua harness datang dari 127.0.0.1 yang sama. Sendirian uji
   ini muat; berurutan lewat runner, ember IP-nya jebol dan lookup ditolak —
   merahnya muncul di tempat yang tak berhubungan dan terbaca seperti flake
   padahal deterministik. Embernya dipinjam lalu dikembalikan utuh di cleanup,
   bukan dikosongkan: uji tidak boleh diam-diam melonggarkan pembatas laju bagi
   siapa pun yang jalan sesudahnya. Pola dari R6. */
$GLOBALS['rate_asli']=[];
function pinjam_rate($db,$key){if(array_key_exists($key,$GLOBALS['rate_asli']))return;
  $GLOBALS['rate_asli'][$key]=$db->one('SELECT limit_key,window_started_at,failed_attempts FROM sys_rate_limits WHERE limit_key=?',[$key]);
  $db->q('DELETE FROM sys_rate_limits WHERE limit_key=?',[$key]);}
$pepper=envv()['KPKP_DATA_PEPPER'] ?? '';
foreach(['warga_lookup','warga_submit','warga_start_revision','admin_queue_decision'] as $policy)
  foreach(['127.0.0.1','::1'] as $ip) pinjam_rate($db,hash('sha256',$policy.':ip:'.$ip));
/* R3 menyentuh tiga NIK fixture, bukan satu — embernya per-NIK, jadi ketiganya
   harus dipinjam atau tabrakannya cuma berpindah ke fixture berikutnya. */
foreach(['0000000000000002','0000000000000003','0000000000000005'] as $n)
  pinjam_rate($db,hash('sha256','warga_lookup:nik:'.hash_hmac('sha256',$n,$pepper)));
try {
  $a=user($db,"r3_{$stamp}_a@example.test",'Warga Uji R3 A',$pass);$b=user($db,"r3_{$stamp}_b@example.test",'Warga Uji R3 B',$pass);$ids=[$a,$b];
  $sa=new HTTP(); check($sa->login("r3_{$stamp}_a@example.test",$pass),'akun SIM-02 login');
  [$s]=$sa->call('warga/pendataan',['step'=>'find_data','nik'=>'0000000000000002','birth_date'=>'1990-12-31']); check(in_array($s,[302,303],true),'lookup SIM-02 memakai PRG');
  $draft=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=?',[$a]); check($draft && $draft['current_step']==='citizen_data','lookup membuat draft milik warga');
  $before=(int)$db->val("SELECT COUNT(*) FROM sf_rekaman_simperum WHERE source_record_key='SIM-02'");
  [$s]=$sa->call('warga/pendataan',['step'=>'citizen_data','assessment_id'=>$draft['id'],'lock_version'=>$draft['lock_version'],'direction'=>'next','family_card_number'=>'0000000000002002','full_name'=>'Warga Simulasi Parsial','phone'=>'080000000002','birth_date'=>'1990-12-31','address'=>'Alamat Sintetis','gender_code'=>'male','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'trader','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable']); check(in_array($s,[302,303],true),'langkah data warga tersimpan');
  $after=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$draft['id']]); check($after['current_step']==='housing_family','save melanjutkan draft');
  $sa->call('Auth/logout'); $sa2=new HTTP(); check($sa2->login("r3_{$stamp}_a@example.test",$pass),'login ulang'); [$s,$body]=$sa2->call('warga/pendataan'); check($s===200 && strpos($body,'Rumah')!==false,'draft tetap setelah logout/login');
  $sa2->call('warga/pendataan'); check((int)$db->val("SELECT COUNT(*) FROM sf_rekaman_simperum WHERE source_record_key='SIM-02'")===$before,'next/reload tidak membuat snapshot baru');
  $fixture=['key'=>'SIM-05','nik'=>'0000000000000005','birth'=>'1985-05-05'];
  if ($db->val("SELECT p.id FROM sf_profil_warga p JOIN sf_rekaman_simperum s ON s.nik_lookup_hash=p.nik_lookup_hash WHERE s.source_record_key='SIM-05' LIMIT 1")) {
      $fixture=['key'=>'SIM-03','nik'=>'0000000000000003','birth'=>'1988-03-03'];
  }
  $sb=new HTTP(); check($sb->login("r3_{$stamp}_b@example.test",$pass),'akun koreksi login'); $sb->call('warga/pendataan',['step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $d2=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=?',[$b]); check((bool)$d2,'fixture koreksi membuat draft'); $snap=$d2?$db->one('SELECT payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d2['simperum_snapshot_id']]):null;
  if ($d2) {$sb->call('warga/pendataan',['step'=>'citizen_data','assessment_id'=>$d2['id'],'lock_version'=>$d2['lock_version'],'direction'=>'next','family_card_number'=>'0000000000005005','full_name'=>'Warga Simulasi Koreksi','phone'=>'080000000005','birth_date'=>$fixture['birth'],'address'=>'Alamat Koreksi Warga','gender_code'=>'female','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'trader','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable']);} check($d2 && $snap['payload_sha256']===$db->val('SELECT payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d2['simperum_snapshot_id']]),'koreksi profil tidak mengubah snapshot');
  $sb->call('warga/pendataan',['action'=>'lookup','step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $d2fresh=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$d2['id']]); $sb->call('warga/pendataan',['step'=>'housing_family','assessment_id'=>$d2fresh['id'],'lock_version'=>$d2fresh['lock_version'],'direction'=>'back']); [, $overrideBody]=$sb->call('warga/pendataan'); check(strpos($overrideBody,'Alamat Koreksi Warga')!==false,'lookup ulang tidak menimpa koreksi warga');
  $db->q("UPDATE sf_rekaman_simperum SET expires_at='2000-01-01 00:00:00' WHERE id=?",[$d2['simperum_snapshot_id']]); $sb->call('warga/pendataan',['action'=>'lookup','step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $refreshed=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=? ORDER BY id DESC LIMIT 1',[$b]); $refreshOk=$refreshed && (int)$refreshed['id']!==(int)$d2['id'] && (int)$refreshed['previous_version_id']===(int)$d2['id'] && (int)$refreshed['simperum_snapshot_id']!==(int)$d2['simperum_snapshot_id']; if(!$refreshOk) echo '  DIAG refresh='.json_encode(['old_assessment'=>$d2['id'],'old_snapshot'=>$d2['simperum_snapshot_id'],'latest_assessment'=>$refreshed['id']??null,'latest_parent'=>$refreshed['previous_version_id']??null,'latest_snapshot'=>$refreshed['simperum_snapshot_id']??null])."\n"; check($refreshOk,'snapshot sumber baru membuat versi assessment baru');
  $beforeOwner=$db->val('SELECT lock_version FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]); $sb->call('warga/pendataan',['step'=>'housing_family','assessment_id'=>$after['id'],'lock_version'=>$after['lock_version'],'direction'=>'back']); check((string)$beforeOwner===(string)$db->val('SELECT lock_version FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]),'forged assessment milik warga lain ditolak');
  $fresh=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]); $sa2->call('warga/pendataan',['step'=>'housing_family','assessment_id'=>$fresh['id'],'lock_version'=>$fresh['lock_version'],'direction'=>'back']); $once=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$fresh['id']]); $sa2->call('warga/pendataan',['step'=>'housing_family','assessment_id'=>$fresh['id'],'lock_version'=>$fresh['lock_version'],'direction'=>'back']); $twice=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$fresh['id']]); check((int)$once['lock_version']===(int)$fresh['lock_version']+1 && (int)$twice['lock_version']===(int)$once['lock_version'],'stale lock kedua tidak overwrite diam-diam');
} finally { cleanup($db,$ids); }
check((int)$db->val('SELECT COUNT(*) FROM usr_users WHERE email LIKE ?',["r3_{$stamp}_%"])===0,'akun/profil/draft uji dibersihkan');
echo "=== RINGKASAN: {$ok} OK, {$fail} gagal ===\n"; exit($fail?1:0);
