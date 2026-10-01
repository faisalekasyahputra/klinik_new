<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji: NIK dari pendaftaran mengisi kolom Cek NIK di wizard warga (26 Sep 2026).
 *
 *   php docs/engineering/uji_nik_dari_akun.php
 *
 * Warga yang sudah mengisi NIK saat daftar cukup klik Cek NIK tanpa mengetik ulang.
 * Memakai fixture API-01 (NIK 3399..., bukan NIK warga). Akun uji dibuat dan dihapus sendiri.
 */
define('BASEPATH','x'); define('APPPATH', dirname(__DIR__, 2) . '/application/'); function log_message(){}
$B=rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/'; $env=[]; foreach(file(dirname(__DIR__, 2) . '/.env',FILE_IGNORE_NEW_LINES) as $l){$l=trim($l); if($l===''||$l[0]==='#'||!strpos($l,'='))continue; [$k,$v]=explode('=',$l,2); $env[trim($k)]??=trim($v); putenv(trim($k).'='.trim($v));}
require APPPATH.'libraries/Encryption_lib.php'; $enc=new Encryption_lib();
$db=new mysqli($env['DB_HOST'],$env['DB_USER'],$env['DB_PASS']??'',$env['DB_NAME']);
$tag='e2enik'.bin2hex(random_bytes(2)); $pw='E2e#'.bin2hex(random_bytes(5)); $ids=[];
$http=function($jar,$p,$post=null)use($B){$c=curl_init($B.$p);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_FOLLOWLOCATION=>1,CURLOPT_COOKIEJAR=>$jar,CURLOPT_COOKIEFILE=>$jar,CURLOPT_TIMEOUT=>60]);if($post!==null)curl_setopt($c,CURLOPT_POSTFIELDS,http_build_query($post));$b=(string)curl_exec($c);$k=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);return [$k,html_entity_decode($b)];};
$csrf=function($jar){foreach(file($jar) as $l){$p=explode("\t",trim($l));if(($p[5]??'')==='csrf_kpkp_cookie')return $p[6];}return '';};
$ok=0;$gagal=0;$cek=function($c,$l)use(&$ok,&$gagal){$c?$ok++:$gagal++; echo ($c?'  OK    ':'  GAGAL ').$l."\n";};
/* Sejak prefill otomatis suite ini memakai dua warga_lookup; ember dipinjam lalu dikembalikan utuh di finally
   (pola uji_data_simperum, jangan kosongkan tabel). ::1 dihitung per blok /64: '0000000000000000/64'. */
$rate_asli=[]; $pinjam=function($key)use($db,&$rate_asli){ if(array_key_exists($key,$rate_asli))return;
 $st=$db->prepare('SELECT limit_key,window_started_at,failed_attempts FROM sys_rate_limits WHERE limit_key=?');$st->bind_param('s',$key);$st->execute(); $rate_asli[$key]=$st->get_result()->fetch_assoc();
 $st=$db->prepare('DELETE FROM sys_rate_limits WHERE limit_key=?');$st->bind_param('s',$key);$st->execute(); };
try{
 $nik='3399991508850001';
 foreach(['warga_lookup','login'] as $p) foreach(['127.0.0.1','::1','0000000000000000/64'] as $ip) $pinjam(hash('sha256',"$p:ip:$ip"));
 $pinjam(hash('sha256','warga_lookup:nik:'.$enc->deterministic_hash($nik)));
 foreach([[$nik,'dengan NIK'],[null,'tanpa NIK']] as $i=>[$n,$ket]){
  $e="{$tag}_{$i}@example.test"; $h=password_hash($pw,PASSWORD_BCRYPT); $nc=$n?$enc->encrypt($n):null; $nh=$n?$enc->deterministic_hash($n):null;
  $st=$db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at,nik,nik_lookup_hash) VALUES ('Uji NIK',?,?,'warga','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW(),?,?)");
  $st->bind_param('ssss',$e,$h,$nc,$nh);$st->execute();$id=$db->insert_id;$ids[]=$id;
  foreach(['warga_lookup','warga_lookup_jam','warga_lookup_harian'] as $p) $pinjam(hash('sha256',"$p:account:$id"));
  $j=tempnam(sys_get_temp_dir(),'e2e'); $http($j,'Auth/login'); $http($j,'Auth/do_login',['email'=>$e,'password'=>$pw,'csrf_kpkp_token'=>$csrf($j)]);
  [$k,$b]=$http($j,'warga/pendataan');
  preg_match('/<input id="nik" name="nik"[^>]*value="([^"]*)"/',$b,$m);
  if($n){
   // Sejak 26 Sep 2026 (jaring pengaman Warga::pendataan): akun ber-NIK tanpa draft langsung dilookup, form sudah berisi.
   $d=$db->query("SELECT current_step FROM sf_penilaian_perumahan WHERE user_id=$id")->fetch_assoc();
   $cek(($d['current_step']??'')==='housing_family' && strpos($b,'name="step" value="housing_family"')!==false, "Akun $ket: halaman diagnosa langsung berisi data SIMPERUM tanpa klik Cek NIK");
   // Prefill otomatis hanya sekali per sesi; sesudah draft hilang, kolom Cek NIK tetap terisi dan satu klik cukup.
   $db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=$id");
   [$k,$b]=$http($j,'warga/pendataan'); preg_match('/<input id="nik" name="nik"[^>]*value="([^"]*)"/',$b,$m);
   $cek(($m[1]??'')===$n && strpos($b,'name="step" value="find_data"')!==false, "Akun $ket: prefill otomatis tidak berulang dalam sesi yang sama; kolom Cek NIK terisi");
   [$k,$b]=$http($j,'warga/pendataan',['step'=>'find_data','nik'=>$m[1],'csrf_kpkp_token'=>$csrf($j)]);
   $d=$db->query("SELECT current_step FROM sf_penilaian_perumahan WHERE user_id=$id")->fetch_assoc();
   $cek(($d['current_step']??'')==='housing_family', "Akun $ket: sekali klik Cek NIK langsung maju ke langkah berikutnya");
  } else { $cek(($m[1]??'x')==='' && strpos($b,'Terisi dari NIK')===false, "Akun $ket: kolom kosong, tanpa petunjuk"); }
  @unlink($j);
 }
 // UAT warga #8: NIK yang terkunci di usr_users akun lain (pemilik belum mengisi pendataan,
 // jadi belum punya sf_profil_warga) tetap ditolak "sudah terdaftar pada akun lain".
 $nik2='3399990101700003'; $pinjam(hash('sha256','warga_lookup:nik:'.$enc->deterministic_hash($nik2)));
 $akun=[];
 foreach(['pemilik'=>$nik2,'perebut'=>null] as $peran=>$n){
  $e="{$tag}_{$peran}@example.test"; $h=password_hash($pw,PASSWORD_BCRYPT); $nc=$n?$enc->encrypt($n):null; $nh=$n?$enc->deterministic_hash($n):null;
  $st=$db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at,nik,nik_lookup_hash) VALUES ('Uji NIK',?,?,'warga','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW(),?,?)");
  $st->bind_param('ssss',$e,$h,$nc,$nh);$st->execute();$akun[$peran]=$ids[]=$db->insert_id;
 }
 $id=$akun['perebut']; foreach(['warga_lookup','warga_lookup_jam','warga_lookup_harian'] as $p) $pinjam(hash('sha256',"$p:account:$id"));
 $j=tempnam(sys_get_temp_dir(),'e2e'); $http($j,'Auth/login'); $http($j,'Auth/do_login',['email'=>"{$tag}_perebut@example.test",'password'=>$pw,'csrf_kpkp_token'=>$csrf($j)]);
 $http($j,'warga/pendataan');
 [$k,$b]=$http($j,'warga/pendataan',['step'=>'find_data','nik'=>$nik2,'csrf_kpkp_token'=>$csrf($j)]);
 $profil=$db->query("SELECT COUNT(*) FROM sf_profil_warga WHERE user_id=$id")->fetch_row()[0];
 $cek(strpos($b,'sudah terdaftar pada akun lain')!==false && (int)$profil===0, "NIK terkunci di akun lain (tanpa profil pendataan): akun kedua ditolak, tidak ada profil tercipta");
 @unlink($j);
 // Onboarding warga dengan NIK milik akun lain (di sf_profil_warga atau usr_users) ditolak SAAT onboarding.
 // Dulu lolos, lalu prefill SIMPERUM gagal diam-diam (nik_already_bound) dan warga terus kembali ke Cek NIK.
 $nik3='3399990101700005'; $pinjam(hash('sha256','warga_lookup:nik:'.$enc->deterministic_hash($nik3)));
 $st=$db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji NIK',?,?,'warga','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
 $e="{$tag}_profil@example.test"; $h=password_hash($pw,PASSWORD_BCRYPT); $st->bind_param('ss',$e,$h);$st->execute(); $ids[]=$pid=$db->insert_id;
 $st=$db->prepare("INSERT INTO sf_profil_warga (user_id,nik_ciphertext,nik_lookup_hash,full_name_ciphertext) VALUES (?,?,?,?)");
 $nc=$enc->encrypt($nik3); $nh=$enc->deterministic_hash($nik3); $fn=$enc->encrypt('Uji NIK'); $st->bind_param('isss',$pid,$nc,$nh,$fn); $st->execute();
 $st=$db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji NIK',?,?,NULL,'active',0,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
 $e="{$tag}_baru@example.test"; $st->bind_param('ss',$e,$h);$st->execute(); $ids[]=$id=$db->insert_id;
 foreach(['warga_lookup','warga_lookup_jam','warga_lookup_harian'] as $p) $pinjam(hash('sha256',"$p:account:$id"));
 $j=tempnam(sys_get_temp_dir(),'e2e'); $http($j,'Auth/login'); $http($j,'Auth/do_login',['email'=>$e,'password'=>$pw,'csrf_kpkp_token'=>$csrf($j)]);
 foreach([[$nik3,'profil pendataan'],[$nik2,'usr_users']] as [$n,$ket]){
  $http($j,'Auth/onboarding');
  [$k,$b]=$http($j,'Auth/save_onboarding',['csrf_kpkp_token'=>$csrf($j),'role'=>'warga','username'=>$tag.'baru','nama_lengkap'=>'Uji NIK','nik_identitas'=>$n,'alamat_domisili'=>'Alamat uji','phone'=>'081234567890']);
  $u=$db->query("SELECT profile_completed,nik_lookup_hash FROM usr_users WHERE id=$id")->fetch_assoc();
  $cek(strpos($b,'sudah terdaftar pada akun lain')!==false && (int)$u['profile_completed']===0 && $u['nik_lookup_hash']===null, "Onboarding warga dengan NIK terikat di $ket akun lain ditolak berpesan, akun tidak terikat");
 }
 @unlink($j);
} finally {
 foreach($ids as $id){$db->query("DELETE FROM sf_profil_warga WHERE user_id=$id");$db->query("DELETE FROM sf_penilaian_perumahan WHERE user_id=$id");$db->query("DELETE FROM usr_users WHERE id=$id");}
 $db->query("DELETE FROM sf_rekaman_simperum WHERE source_record_key LIKE 'SYN-API-%'");
 foreach($rate_asli as $key=>$r){ $st=$db->prepare('DELETE FROM sys_rate_limits WHERE limit_key=?');$st->bind_param('s',$key);$st->execute();
  if($r){$st=$db->prepare('INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)');$st->bind_param('ssi',$r['limit_key'],$r['window_started_at'],$r['failed_attempts']);$st->execute();} }
 echo "RINGKASAN: ".($ok+$gagal)." pemeriksaan, $gagal gagal; akun tersisa ".$db->query("SELECT COUNT(*) FROM usr_users WHERE email LIKE '{$tag}%'")->fetch_row()[0]."\n";
}
exit($gagal ? 1 : 0);
