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
   ini muat; berurutan lewat runner, ember IP-nya jebol dan lookup ditolak -
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
/* R3 menyentuh tiga NIK fixture, bukan satu - embernya per-NIK, jadi ketiganya
   harus dipinjam atau tabrakannya cuma berpindah ke fixture berikutnya. */
foreach(['0000000000000002','0000000000000003','0000000000000005'] as $n)
  pinjam_rate($db,hash('sha256','warga_lookup:nik:'.hash_hmac('sha256',$n,$pepper)));
/* WIZARD BERUBAH 23-24 Agt 2026 dan harness ini menyusulnya, 31 Agt 2026.
   Step `citizen_data` DIHAPUS permanen (cfbd760 + migrasi 049); isiannya pindah
   jadi sub-bagian di `housing_family_detail`. Sementara `housing_family` kini
   menampung tujuh isian matriks xlsx yang MENENTUKAN rekomendasi awal, jadi
   ia tidak bisa dilewati begitu saja. Urutannya sekarang:
   find_data -> housing_family (matriks) -> preliminary_recommendation ->
   housing_family_detail (data warga + rumah/keluarga). */
/* Langkah pada POST diambil dari `current_step` draft, TIDAK dipatok nama.
   Penjaga di Warga.php menolak dengan show_404 kalau step yang dikirim bukan
   langkah draft saat itu, jadi nama yang dipatok membuat uji lulus karena
   permintaannya tidak pernah sampai, bukan karena penjaganya bekerja. Persis
   itu yang terjadi sesudah `citizen_data` dihapus. */
function matriks() {
  return ['matrix_income_code'=>'income_0_1_5','matrix_dtks_status'=>'dtks_ya',
          'matrix_land_ownership_code'=>'land_none','matrix_current_housing_code'=>'house_none_or_rent',
          'matrix_environment_condition_code'=>'env_slum_uninhabitable',
          'matrix_occupation_finance_code'=>'work_stable_or_unstable_no_subsidy',
          'matrix_marital_family_code'=>'family_married'];
}
/* Membawa draft dari `housing_family` sampai berhenti di `housing_family_detail`,
   lalu mengembalikan barisnya. Dipakai sebelum memeriksa paragraf desil, sebab
   paragraf itu hanya dirender di langkah detail. */
function maju_ke_detail($sesi,$db,$uid) {
  $d=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=?',[$uid]);
  $sesi->call('warga/pendataan',array_merge(['step'=>'housing_family','assessment_id'=>$d['id'],'lock_version'=>$d['lock_version'],'direction'=>'next'],matriks()));
  $d=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$d['id']]);
  if ($d['current_step']==='preliminary_recommendation') {
    $sesi->call('warga/pendataan',['step'=>'preliminary_recommendation','assessment_id'=>$d['id'],'lock_version'=>$d['lock_version'],'direction'=>'next']);
    $d=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$d['id']]);
  }
  return $d;
}

try {
  $a=user($db,"r3_{$stamp}_a@example.test",'Warga Uji R3 A',$pass);$b=user($db,"r3_{$stamp}_b@example.test",'Warga Uji R3 B',$pass);$ids=[$a,$b];
  $sa=new HTTP(); check($sa->login("r3_{$stamp}_a@example.test",$pass),'akun SIM-02 login');
  [$s]=$sa->call('warga/pendataan',['step'=>'find_data','nik'=>'0000000000000002','birth_date'=>'1990-12-31']); check(in_array($s,[302,303],true),'lookup SIM-02 memakai PRG');
  $draft=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=?',[$a]); check($draft && $draft['current_step']==='housing_family','lookup membuat draft milik warga');
  /* Butir A6 - kalimat desil, diperiksa DI LANGKAH `citizen_data` karena hanya
     di situ paragrafnya dirender.

     🔻 VERSI PERTAMA BLOK INI SALAH TEMPAT dan menghasilkan HIJAU HAMPA. Ia
     dipasang sesudah langkah tersimpan, saat wizard sudah pindah ke
     `housing_family` dan paragraf desil tidak ada di halaman. Asersi negatif
     "tidak mengaku data resmi" LULUS justru karena tidak ada apa pun untuk
     diperiksa. Karena itu sekarang keberadaan paragrafnya jadi PRASYARAT: kalau
     ia tidak dirender, seluruh blok ini merah, bukan diam-diam hijau.

     Yang dijaga bukan susunan katanya, melainkan bahwa SUMBERNYA disebut jujur
     mengikuti mode. Dev bermode simulasi - dan per 10 Agt 2026 production pun
     begitu (`SIMPERUM_MODE` tidak diset). Desil adalah angka yang dipakai orang
     menilai haknya atas bantuan; menyebutnya "resmi" saat ia berasal dari
     fixture adalah kekeliruan termahal di halaman ini. */
  $draft=maju_ke_detail($sa,$db,$a);
  check($draft['current_step']==='housing_family_detail','wizard sampai di langkah detail');
  [, $bodyDesil]=$sa->call('warga/pendataan');
  /* Modal pemberitahuan SIMPERUM belum aktif. Muncul SETIAP halaman dibuka
     (keputusan user 10 Agt 2026) - jadi tidak boleh ada penyimpanan "sudah
     pernah dilihat" yang membuatnya diam di layar berikutnya. */
  check(strpos($bodyDesil,'id="modal-simperum"')!==false,'Modal SIMPERUM belum aktif dirender saat mode simulasi');
  check(strpos($bodyDesil,'SIMPERUM belum diaktifkan')!==false,'Modal menyebut sebabnya: belum disetujui, bukan rusak');
  /* Modal WAJIB hilang sendiri begitu SIMPERUM aktif - kalau tidak, kelak ia
     berbohong ke arah sebaliknya dan memberitahu penguji bahwa data sungguhan
     itu contoh. Diperiksa STRUKTURAL, dan itu disebut apa adanya: jalur `api`
     tidak bisa dijalankan dari sini tanpa mengubah `.env` situs yang sedang
     diuji, dan mengutak-atik `.env` dari dalam harness adalah risiko yang jauh
     lebih besar daripada yang dijaganya (lihat AGENTS.md §18). */
  $komponen=(string)@file_get_contents(dirname(__DIR__,2).'/application/views/components/modal_simperum_simulasi.php');
  check(preg_match("/simperum_mode[^\\n]*!==\\s*'simulation'\\s*\\)\\s*\\{\\s*return;/",$komponen)===1,
        'Modal berhenti merender saat mode BUKAN simulasi (struktural)');
  /* Dipersempit ke blok modalnya saja. Versi pertama memindai SELURUH halaman
     dan merah karena skrip lain (tema, layout) memang memakai sessionStorage -
     merah palsu yang menuduh kode yang tidak bersalah. */
  $blokModal = preg_match('#id="modal-simperum".*?</script>#s',$bodyDesil,$mm) ? $mm[0] : '';
  check($blokModal!=='','PRASYARAT: blok modal ditemukan utuh untuk diperiksa');
  check($blokModal!=='' && strpos($blokModal,'Storage')===false && strpos($blokModal,'document.cookie')===false,'Modal tidak diingat - muncul tiap halaman dibuka');
  $adaParagraf=strpos($bodyDesil,'Kelompok kesejahteraan (desil)')!==false;
  check($adaParagraf,'A6: paragraf desil dirender di langkah data warga (PRASYARAT)');
  if ($adaParagraf) {
    check(strpos($bodyDesil,'tidak dihitung ulang dari penghasilan yang Anda isi')!==false,'A6: alasan tidak dihitung ulang tetap disampaikan');
    check(strpos($bodyDesil,'data simulasi')!==false,'A6: mode simulasi disebut apa adanya');
    check(strpos($bodyDesil,'diambil dari data resmi SIMPERUM')===false,'A6: TIDAK mengaku "data resmi" saat sumbernya fixture');
  } else {
    check(false,'A6: tiga cek isi dilewati karena paragrafnya tidak ada');
  }
  $before=(int)$db->val("SELECT COUNT(*) FROM sf_rekaman_simperum WHERE source_record_key='SIM-02'");
  [$s]=$sa->call('warga/pendataan',['step'=>'housing_family_detail','assessment_id'=>$draft['id'],'lock_version'=>$draft['lock_version'],'direction'=>'next','family_card_number'=>'0000000000002002','full_name'=>'Warga Simulasi Parsial','phone'=>'080000000002','birth_date'=>'1990-12-31','address'=>'Alamat Sintetis','gender_code'=>'male','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'trader','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable','housing_status_code'=>'owned','occupant_count'=>'3','family_count'=>'1']); check(in_array($s,[302,303],true),'langkah data warga tersimpan');
  $after=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$draft['id']]); check($after['current_step']==='building_condition','save melanjutkan draft');
  $sa->call('Auth/logout'); $sa2=new HTTP(); check($sa2->login("r3_{$stamp}_a@example.test",$pass),'login ulang'); [$s,$body]=$sa2->call('warga/pendataan'); check($s===200 && strpos($body,'Rumah')!==false,'draft tetap setelah logout/login');
  $sa2->call('warga/pendataan'); check((int)$db->val("SELECT COUNT(*) FROM sf_rekaman_simperum WHERE source_record_key='SIM-02'")===$before,'next/reload tidak membuat snapshot baru');
  $fixture=['key'=>'SIM-05','nik'=>'0000000000000005','birth'=>'1985-05-05'];
  if ($db->val("SELECT p.id FROM sf_profil_warga p JOIN sf_rekaman_simperum s ON s.nik_lookup_hash=p.nik_lookup_hash WHERE s.source_record_key='SIM-05' LIMIT 1")) {
      $fixture=['key'=>'SIM-03','nik'=>'0000000000000003','birth'=>'1988-03-03'];
  }
  $sb=new HTTP(); check($sb->login("r3_{$stamp}_b@example.test",$pass),'akun koreksi login'); $sb->call('warga/pendataan',['step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $d2=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=?',[$b]); check((bool)$d2,'fixture koreksi membuat draft'); $snap=$d2?$db->one('SELECT payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d2['simperum_snapshot_id']]):null;
  if ($d2) {$d2=maju_ke_detail($sb,$db,$b);}
  if ($d2) {$sb->call('warga/pendataan',['step'=>'housing_family_detail','assessment_id'=>$d2['id'],'lock_version'=>$d2['lock_version'],'direction'=>'next','family_card_number'=>'0000000000005005','full_name'=>'Warga Simulasi Koreksi','phone'=>'080000000005','birth_date'=>$fixture['birth'],'address'=>'Alamat Koreksi Warga','gender_code'=>'female','marital_status_code'=>'married','education_code'=>'senior_high','occupation_code'=>'trader','income_band_code'=>'2_2_2_6','self_help_capability_code'=>'capable','housing_status_code'=>'owned','occupant_count'=>'3','family_count'=>'1']);} check($d2 && $snap['payload_sha256']===$db->val('SELECT payload_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d2['simperum_snapshot_id']]),'koreksi profil tidak mengubah snapshot');
  $sb->call('warga/pendataan',['action'=>'lookup','step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $d2fresh=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$d2['id']]); $sb->call('warga/pendataan',['step'=>$d2fresh['current_step'],'assessment_id'=>$d2fresh['id'],'lock_version'=>$d2fresh['lock_version'],'direction'=>'back']); [, $overrideBody]=$sb->call('warga/pendataan'); check(strpos($overrideBody,'Alamat Koreksi Warga')!==false,'lookup ulang tidak menimpa koreksi warga');
  $db->q("UPDATE sf_rekaman_simperum SET expires_at='2000-01-01 00:00:00' WHERE id=?",[$d2['simperum_snapshot_id']]); $sb->call('warga/pendataan',['action'=>'lookup','step'=>'find_data','nik'=>$fixture['nik'],'birth_date'=>$fixture['birth']]); $refreshed=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE user_id=? ORDER BY id DESC LIMIT 1',[$b]); $refreshOk=$refreshed && (int)$refreshed['id']!==(int)$d2['id'] && (int)$refreshed['previous_version_id']===(int)$d2['id'] && (int)$refreshed['simperum_snapshot_id']!==(int)$d2['simperum_snapshot_id']; if(!$refreshOk) echo '  DIAG refresh='.json_encode(['old_assessment'=>$d2['id'],'old_snapshot'=>$d2['simperum_snapshot_id'],'latest_assessment'=>$refreshed['id']??null,'latest_parent'=>$refreshed['previous_version_id']??null,'latest_snapshot'=>$refreshed['simperum_snapshot_id']??null])."\n"; check($refreshOk,'snapshot sumber baru membuat versi assessment baru');
  $beforeOwner=$db->val('SELECT lock_version FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]); $sb->call('warga/pendataan',['step'=>$after['current_step'],'assessment_id'=>$after['id'],'lock_version'=>$after['lock_version'],'direction'=>'back']); check((string)$beforeOwner===(string)$db->val('SELECT lock_version FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]),'forged assessment milik warga lain ditolak');
  $fresh=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$after['id']]); $sa2->call('warga/pendataan',['step'=>$fresh['current_step'],'assessment_id'=>$fresh['id'],'lock_version'=>$fresh['lock_version'],'direction'=>'back']); $once=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$fresh['id']]); $sa2->call('warga/pendataan',['step'=>$fresh['current_step'],'assessment_id'=>$fresh['id'],'lock_version'=>$fresh['lock_version'],'direction'=>'back']); $twice=$db->one('SELECT * FROM sf_penilaian_perumahan WHERE id=?',[$fresh['id']]); check((int)$once['lock_version']===(int)$fresh['lock_version']+1 && (int)$twice['lock_version']===(int)$once['lock_version'],'stale lock kedua tidak overwrite diam-diam');

  /* BUTIR 21 PUTARAN 2 - isian NIK di Profil Saya, dan kunci di baliknya.

     Yang dijaga bukan "isiannya ada", melainkan tiga hal yang bisa rusak tanpa
     satu pun galat:
       1. NIK tersimpan TERENKRIPSI, dan tidak pernah tampil utuh di layar.
       2. Sekali terisi, TIDAK bisa diubah sendiri - bahkan lewat kiriman POST
          langsung yang melewati formulir. Ini intinya: NIK yang bisa diganti
          sendiri berarti satu orang bisa berpindah memakai NIK orang lain,
          termasuk sesudah pengajuannya dinilai.
       3. Satu NIK hanya untuk satu akun. */
  $nikBaru = '32' . str_pad((string)mt_rand(1,99999999999999), 14, '0', STR_PAD_LEFT);
  $sc = new HTTP(); check($sc->login("r3_{$stamp}_a@example.test",$pass),'login untuk uji NIK profil');
  [, $bodyProfil] = $sc->call('akun/profil');
  check(strpos($bodyProfil,'name="nik"')!==false,'Isian NIK muncul saat akun belum punya NIK');

  $sc->call('akun/profil');
  $sc->call('Pengaturan/update_profile',['name'=>'Warga Uji NIK','phone'=>'08123456789','nik'=>$nikBaru]);
  $tersimpan = $db->one('SELECT nik, nik_lookup_hash FROM usr_users WHERE id=?',[$a]);
  check(!empty($tersimpan['nik_lookup_hash']),'NIK tersimpan dan punya sidik pencarian');
  check(!empty($tersimpan['nik']) && strpos((string)$tersimpan['nik'],$nikBaru)===false,
        'NIK disimpan terenkripsi, angka aslinya tidak ada di kolom');

  [, $bodyKunci] = $sc->call('akun/profil');
  check(strpos($bodyKunci,$nikBaru)===false,'NIK tidak pernah tampil utuh di halaman profil');
  check(strpos($bodyKunci,'name="nik"')===false,'Isian NIK terkunci, tidak dikirim ulang formulir');

  /* Percobaan menimpa lewat POST langsung, melewati formulir. Dibaca dari DB,
     bukan dari pesan layar. */
  $nikPalsu = '33' . str_pad((string)mt_rand(1,99999999999999), 14, '0', STR_PAD_LEFT);
  $sc->call('akun/profil');
  $sc->call('Pengaturan/update_profile',['name'=>'Warga Uji NIK','phone'=>'08123456789','nik'=>$nikPalsu]);
  $sesudah = $db->one('SELECT nik_lookup_hash FROM usr_users WHERE id=?',[$a]);
  check($sesudah['nik_lookup_hash']===$tersimpan['nik_lookup_hash'],
        'NIK terkunci TIDAK berubah walau POST langsung mengirim NIK lain');

  /* Akun kedua tidak boleh memakai NIK yang sama. */
  $sd = new HTTP(); check($sd->login("r3_{$stamp}_b@example.test",$pass),'login akun kedua');
  $sd->call('akun/profil');
  $sd->call('Pengaturan/update_profile',['name'=>'Warga Kedua','phone'=>'08987654321','nik'=>$nikBaru]);
  check(empty($db->val('SELECT nik_lookup_hash FROM usr_users WHERE id=?',[$b])),
        'NIK yang sudah dipakai akun lain DITOLAK - satu NIK satu akun');

} finally { cleanup($db,$ids); }
check((int)$db->val('SELECT COUNT(*) FROM usr_users WHERE email LIKE ?',["r3_{$stamp}_%"])===0,'akun/profil/draft uji dibersihkan');
echo "=== RINGKASAN: {$ok} OK, {$fail} gagal ===\n"; exit($fail?1:0);
