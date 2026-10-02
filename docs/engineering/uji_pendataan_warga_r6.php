<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
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
    $id=$db->run("INSERT INTO usr_akun (email,kata_sandi,nama,nama_pengguna,peran,status,profil_lengkap,kabupaten_id,created_at) VALUES (?,?,'Uji R6',?,?,'active',1,?,NOW())",[$email,password_hash(PASSWORD,PASSWORD_BCRYPT),$username,$role,$kabupaten]);
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
    $p=$db->row('SELECT p.id, u.email FROM sf_profil_warga p LEFT JOIN usr_akun u ON u.id=p.user_id WHERE p.nik_lookup_hash=?',
        [hash_hmac('sha256',$nik,$env['KPKP_DATA_PEPPER'] ?? '')]);
    wajib(!$p, $p ? "NIK fixture {$nik} SEDANG DIPEGANG profil #{$p['id']} milik ".($p['email'] ?? '[akun sudah terhapus]').' - lepaskan ikatannya atau pakai DB uji bersih' : "NIK fixture {$nik} bebas dipakai");
}
function draft($db,$user) {
    $r=$db->row("SELECT * FROM sf_penilaian_perumahan WHERE user_id=? AND status='draft' ORDER BY id DESC LIMIT 1",[$user]);
    wajib((bool)$r,'Draft warga tersedia');
    if(!in_array((int)$r['id'],$GLOBALS['assessments'],TRUE))$GLOBALS['assessments'][]=(int)$r['id'];
    if(!empty($r['rekaman_simperum_id'])&&!in_array((int)$r['rekaman_simperum_id'],$GLOBALS['snapshots'],TRUE))$GLOBALS['snapshots'][]=(int)$r['rekaman_simperum_id'];
    return $r;
}
function post_step($s,$d,$step,$data){return $s->post('warga/pendataan',$data+['action'=>'save','step'=>$step,'direction'=>'next','penilaian_id'=>$d['id'],'versi_kunci'=>$d['versi_kunci']]);}
function citizen($name='Warga Uji R6'){return ['family_card_number'=>'0000000000006666','full_name'=>$name,'address'=>'Alamat Koreksi Uji R6','phone'=>'081234567890','birth_date'=>'1980-01-01','jenis_kelamin'=>'male','status_perkawinan'=>'married','pendidikan'=>'senior_high','pekerjaan'=>'private_employee','kelompok_penghasilan'=>'2_2_2_6','mampu_swadaya'=>'capable','punya_tabungan'=>'1'];}
/* Wizard berubah 23-24 Agt 2026: `citizen_data` DIHAPUS (cfbd760 + migrasi 049),
   isiannya pindah ke `housing_family_detail`, dan `housing_family` kini berisi
   tujuh isian matriks xlsx. Harness menyusul 31 Agt 2026. */
/* Harness disusulkan 18 Sep 2026 ke wizard dua cabang (157e275 + 22c790f): pendapatan angka, data
   profil dasar wajib di langkah matriks, cabang ditentukan dari matriks_rumah_sekarang. */
function matriks($rumah = 'house_owned') { return ['matriks_rumah_sekarang'=>$rumah,'kawasan_perumahan'=>'slum','matriks_kepemilikan_lahan'=>'land_none','matriks_kondisi_lingkungan'=>'env_slum_uninhabitable','matriks_pekerjaan_keuangan'=>'work_stable_or_unstable_no_subsidy','matriks_status_keluarga'=>'family_married','phone'=>'081234567890','birth_date'=>'1980-01-01','jenis_kelamin'=>'male','status_perkawinan'=>'married','pendidikan'=>'senior_high','pekerjaan'=>'trader','stabilitas_pekerjaan'=>'permanent','penghasilan_bulanan'=>'1200000']; }
function lewati_rekomendasi($s,$db,$uid){$d=draft($db,$uid); if($d['langkah_sekarang']==='preliminary_recommendation'){post_step($s,$d,'preliminary_recommendation',[]); $d=draft($db,$uid);} return $d;}
function housing(){return ['kepemilikan_rumah'=>'owned','kepemilikan_lahan'=>'hm','kawasan_perumahan'=>'slum','jml_penghuni'=>'3','jml_kk'=>'1','luas_rumah'=>'36','tanah_lain'=>'0','rumah_lain'=>'0','punya_lahan_calon'=>'0','bantuan_perumahan'=>'','tahun_intervensi'=>''];}
function building($roof='good'){return ['kondisi_pondasi'=>'good','kondisi_kolom'=>'good','kondisi_balok'=>'moderate_damage','kondisi_rangka'=>'good','bahan_lantai'=>'cement_plaster','kondisi_lantai'=>'good','bahan_dinding'=>'wall','kondisi_dinding'=>'good','bahan_atap'=>'clay_tile','kondisi_atap'=>$roof];}
function sanitation(){return ['ada_jendela'=>'1','ada_ventilasi'=>'1','sumber_air'=>'well','penggunaan_kamar_mandi'=>'own','jenis_kloset'=>'swan_neck','pembuangan_tinja'=>'septic_tank','jarak_septic_tank'=>'gte_10','penerangan'=>'pln','bahan_bakar_masak'=>'electric_gas'];}
function complete_sim01($db,$user,$session,$name) {
    $r=$session->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']); wajib(redirect_ok($r),'Lookup SIM-01');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'housing_family',matriks())),'Simpan isian matriks');
    $d=lewati_rekomendasi($session,$db,$user);
    wajib(redirect_ok(post_step($session,$d,'housing_family_detail',citizen($name)+housing())),'Simpan data warga + rumah eksisting');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'building_condition',building())),'Simpan kondisi RTLH');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'sanitation',sanitation())),'Simpan sanitasi');
    $d=draft($db,$user); wajib(redirect_ok(post_step($session,$d,'location_evidence',['location_lat'=>'-7.005145','location_lng'=>'110.438125','akurasi_lokasi_m'=>'8'])),'Hitung rekomendasi R5');
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
        'SELECT kunci,jendela_mulai_at,jumlah_gagal FROM sys_batas_laju WHERE kunci=?',
        [$key]
    );
    $db->run('DELETE FROM sys_batas_laju WHERE kunci=?',[$key]);
}
function preserve_rate_ips($db,$policy){
    preserve_rate_key($db,$policy,'ip','127.0.0.1');
    preserve_rate_key($db,$policy,'ip','::1');
    preserve_rate_key($db,$policy,'ip','0000000000000000/64'); // ::1 dikelompokkan per /64
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
        $db->run("DELETE FROM sf_antrean_pengajuan WHERE user_id IN ($ids)");
        $db->run("DELETE FROM sf_penilaian_perumahan WHERE user_id IN ($ids)");
        $db->run("DELETE FROM sf_profil_warga WHERE user_id IN ($ids)");
    }
    foreach(array_unique($GLOBALS['snapshots']) as $id)$db->run('DELETE FROM sf_rekaman_simperum WHERE id=?',[$id]);
    foreach(array_unique($GLOBALS['users']) as $id)$db->run('DELETE FROM usr_akun WHERE id=?',[$id]);
    foreach($GLOBALS['rate_limit_original'] as $key=>$row){
        $db->run('DELETE FROM sys_batas_laju WHERE kunci=?',[$key]);
        if($row){
            $db->run(
                'INSERT INTO sys_batas_laju (kunci,jendela_mulai_at,jumlah_gagal) VALUES (?,?,?)',
                [$row['kunci'],$row['jendela_mulai_at'],$row['jumlah_gagal']]
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

// Dua assessment diperlukan agar manipulasi rekomendasi_id lintas pemilik benar-benar diuji.
[$ownerId,$ownerEmail]=make_user($db,'owner');
foreach(['warga_lookup','warga_submit','warga_start_revision'] as $policy)preserve_rate_key($db,$policy,'account',$ownerId);
$owner=login_session($ownerEmail,'warga/pendataan');
$d=complete_sim01($db,$ownerId,$owner,'Pemilik R6');
preserve_rate_key($db,'warga_submit','object',$d['id']);
$rtlh=$db->row("SELECT r.*,p.kode_program FROM sf_rekomendasi_penilaian r JOIN sf_program p ON p.id=r.program_id WHERE r.penilaian_id=? AND p.kode_program='rtlh'",[$d['id']]);
$needs=$db->row("SELECT r.*,p.kode_program FROM sf_rekomendasi_penilaian r JOIN sf_program p ON p.id=r.program_id WHERE r.penilaian_id=? AND r.status_kelayakan='needs_data' ORDER BY r.id LIMIT 1",[$d['id']]);
wajib($rtlh&&$rtlh['status_kelayakan']==='eligible','SIM-01 memilih rekomendasi RTLH eligible');

[$otherId,$otherEmail]=make_user($db,'other');
$otherAssessment=$db->run("INSERT INTO sf_penilaian_perumahan (user_id,kabupaten_id,jalur_penilaian,status,langkah_sekarang,no_versi,versi_kunci,mode_sumber) VALUES (?,3374,'existing_house','draft','review',1,0,'simulation')",[$otherId]);
$GLOBALS['assessments'][]=(int)$otherAssessment;
$otherRecId=$db->run("INSERT INTO sf_rekomendasi_penilaian (penilaian_id,program_id,versi_aturan,status_kelayakan,kode_alasan_json,masukan_sha256,evaluated_at) VALUES (?,?,?,'eligible','[\"SIM_RTLH_DAMAGE\"]',REPEAT('b',64),NOW())",[$otherAssessment,$rtlh['program_id'],$rtlh['versi_aturan']]);
$otherRec=$db->row('SELECT * FROM sf_rekomendasi_penilaian WHERE id=?',[$otherRecId]);

// Bukti pada versi awal harus tetap dapat dibaca setelah dibuat revisi.
$png=png_fixture();$up=$owner->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$png,'r6.png');@unlink($png);
wajib(redirect_ok($up),'Unggah bukti privat sebelum submit');
$file=$db->row("SELECT * FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas='self_photo'",[$d['id']]);wajib((bool)$file,'Ledger bukti versi awal tersedia');
$physical=rtrim($root,'/\\').DIRECTORY_SEPARATOR.'warga_assessment'.DIRECTORY_SEPARATOR.$d['id'].DIRECTORY_SEPARATOR.basename($file['path_privat']);
$GLOBALS['physical_files'][]=$physical;wajib(is_file($physical),'Berkas fisik versi awal tersedia');

// Manipulasi recommendation lintas assessment dan needs_data harus gagal tanpa side effect.
$r=$owner->post('warga/pendataan',['action'=>'submit','penilaian_id'=>$d['id'],'rekomendasi_id'=>$otherRec['id']]);
cek(redirect_ok($r)&&(int)$db->scalar('SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId])===0&&raw_assessment($db,$d['id'])['status']==='draft','Recommendation milik assessment lain ditolak');
$r=$owner->post('warga/pendataan',['action'=>'submit','penilaian_id'=>$d['id'],'rekomendasi_id'=>$needs['id']]);
cek(redirect_ok($r)&&(int)$db->scalar('SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId])===0&&raw_assessment($db,$d['id'])['status']==='draft','Recommendation needs_data ditolak');

$snapshotBefore=$db->row('SELECT id,muatan_ciphertext,muatan_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d['rekaman_simperum_id']]);
$assessmentBefore=raw_assessment($db,$d['id']);$fileBefore=$db->row('SELECT jenis_berkas,path_privat,sha256,ukuran_byte FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
$r=$owner->post('warga/pendataan',['action'=>'submit','penilaian_id'=>$d['id'],'rekomendasi_id'=>$rtlh['id']]);wajib(redirect_ok($r),'Submit assessment valid');
$queue=$db->row('SELECT * FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId]);$submitted=raw_assessment($db,$d['id']);
cek($queue&&$submitted['status']==='submitted'&&!empty($submitted['submitted_at']),'Submit atomik membuat assessment submitted dan queue');
cek(!empty($submitted['salinan_profil_ciphertext'])&&strpos($submitted['salinan_profil_ciphertext'],'Pemilik R6')===FALSE&&strpos($submitted['salinan_profil_ciphertext'],'{')!==0,'Snapshot profil tersimpan terenkripsi');
cek(!array_key_exists('nik_pengaju',$queue)&&!array_key_exists('nama_lengkap',$queue)&&$queue['nik_pengaju_ciphertext']===NULL&&$queue['nama_lengkap_ciphertext']===NULL&&$queue['mode_sumber']==='simulation','Queue baru tidak menyimpan PII plaintext dan berlabel simulasi');
cek((int)$queue['kabupaten_id']===3374&&(int)$queue['penilaian_id']===(int)$d['id']&&(int)$queue['rekomendasi_id']===(int)$rtlh['id']&&(int)$queue['program_id']===(int)$rtlh['program_id'],'Queue menyimpan scope dan rekomendasi server');
$queueId=(int)$queue['id'];$ticket=$queue['kode_tiket'];
preserve_rate_key($db,'warga_start_revision','object',$queueId);
preserve_rate_key($db,'admin_queue_decision','object',$queueId);

// Idempotency submit: request sama mengembalikan queue/ticket yang sama.
$owner->post('warga/pendataan',['action'=>'submit','penilaian_id'=>$d['id'],'rekomendasi_id'=>$rtlh['id']]);
cek((int)$db->scalar('SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId])===1&&$db->scalar('SELECT kode_tiket FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId])===$ticket,'Double submit tetap satu queue dan satu tiket');

// Semua writer assessment submitted ditolak dan tidak mengubah row/ledger/rekomendasi.
$recCount=(int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE penilaian_id=?',[$d['id']]);
$owner->post('warga/pendataan',['action'=>'save','step'=>'review','direction'=>'next','penilaian_id'=>$d['id'],'versi_kunci'=>$submitted['versi_kunci']]);
$badPng=png_fixture();$owner->upload('warga/pendataan',['action'=>'upload','penilaian_id'=>$d['id'],'jenis_berkas'=>'self_photo'],'self_photo',$badPng,'blocked.png');@unlink($badPng);
$afterBlocked=raw_assessment($db,$d['id']);$fileBlocked=$db->row('SELECT jenis_berkas,path_privat,sha256,ukuran_byte FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
cek($afterBlocked['updated_at']===$submitted['updated_at']&&$fileBlocked==$fileBefore&&(int)$db->scalar('SELECT COUNT(*) FROM sf_rekomendasi_penilaian WHERE penilaian_id=?',[$d['id']])===$recCount,'Assessment submitted menolak edit/upload/mutasi rekomendasi');

// Admin Semarang dapat membaca detail dan bukti; wilayah lain tidak.
[$admin1Id,$admin1Email]=make_user($db,'admin_semarang_1','admin_kabkota',3374);
[$admin2Id,$admin2Email]=make_user($db,'admin_semarang_2','admin_kabkota',3374);
[$foreignId,$foreignEmail]=make_user($db,'admin_banyumas','admin_kabkota',3302);
foreach([$admin1Id,$admin2Id,$foreignId] as $adminId)preserve_rate_key($db,'admin_queue_decision','account',$adminId);
$admin1=login_session($admin1Email,'Admin_Kabkota');$admin2=login_session($admin2Email,'Admin_Kabkota');$foreign=login_session($foreignEmail,'Admin_Kabkota');
$detail=$admin1->get('Admin_Kabkota/detail/'.$queueId);$evidence=$admin1->get('Admin_Kabkota/evidence/'.$queueId.'/self_photo');
cek($detail['status']===200&&strpos($detail['body'],$ticket)!==FALSE&&strpos($detail['body'],'Mode Simulasi')!==FALSE&&strpos($detail['body'],'Dipilih warga')!==FALSE,'Admin Semarang melihat detail, ruleset, dan mode simulasi');
cek($evidence['status']===200&&$evidence['body']!=='','Admin Semarang membaca bukti privat');
// B2 (config/kebijakan_data.php): selama menunggu keputusan dinas, detail ikut menyamarkan identitas seperti daftar antrean.
$b2Menunggu=strpos((string)file_get_contents(dirname(__DIR__,2).'/application/config/kebijakan_data.php'),"= 'menunggu_keputusan';")!==FALSE;
if($b2Menunggu)cek(strpos($detail['body'],'Warga Contoh')!==FALSE&&strpos($detail['body'],'Alamat Koreksi Uji R6')===FALSE&&strpos($detail['body'],'Warga Uji R6')===FALSE,'B2: detail admin kab/kota tidak menampilkan nama dan alamat asli warga');
cek($foreign->get('Admin_Kabkota/detail/'.$queueId)['status']===404&&$foreign->get('Admin_Kabkota/evidence/'.$queueId.'/self_photo')['status']===404,'Admin wilayah lain gagal membaca detail dan bukti');
$foreign->post('Admin_Kabkota/update_status',['antrean_id'=>$queueId,'status_awal'=>'pending','status'=>'approved','catatan_admin'=>'']);
cek($db->scalar('SELECT status_antrean FROM sf_antrean_pengajuan WHERE id=?',[$queueId])==='pending','Admin wilayah lain gagal menulis keputusan');

// Catatan wajib, lalu dua keputusan paralel dari status asal yang sama: tepat satu history lahir.
$admin1->post('Admin_Kabkota/update_status',['antrean_id'=>$queueId,'status_awal'=>'pending','status'=>'needs_revision','catatan_admin'=>'']);
cek($db->scalar('SELECT status_antrean FROM sf_antrean_pengajuan WHERE id=?',[$queueId])==='pending','needs_revision tanpa catatan ditolak');
$admin1->get('Admin_Kabkota/detail/'.$queueId);$admin2->get('Admin_Kabkota/detail/'.$queueId);
$beforeHistory=(int)$db->scalar("SELECT COUNT(*) FROM sf_riwayat_keputusan_antrean WHERE antrean_id=? AND status_awal='pending' AND status_akhir='needs_revision'",[$queueId]);
parallel_posts([
    $admin1->asyncPost('Admin_Kabkota/update_status',['antrean_id'=>$queueId,'status_awal'=>'pending','status'=>'needs_revision','catatan_admin'=>'Perbaiki data atap A']),
    $admin2->asyncPost('Admin_Kabkota/update_status',['antrean_id'=>$queueId,'status_awal'=>'pending','status'=>'needs_revision','catatan_admin'=>'Perbaiki data atap B']),
]);
$queue=$db->row('SELECT * FROM sf_antrean_pengajuan WHERE id=?',[$queueId]);
$afterHistory=(int)$db->scalar("SELECT COUNT(*) FROM sf_riwayat_keputusan_antrean WHERE antrean_id=? AND status_awal='pending' AND status_akhir='needs_revision'",[$queueId]);
cek($queue['status_antrean']==='needs_revision'&&$afterHistory===$beforeHistory+1&&in_array($queue['catatan_admin'],['Perbaiki data atap A','Perbaiki data atap B'],TRUE),'CAS dua keputusan hanya menerima satu transisi');

// /akun menampilkan status, catatan, dan aksi perbaikan.
$akun=$owner->get('akun');
cek($akun['status']===200&&strpos($akun['body'],'Perlu Perbaikan')!==FALSE&&strpos($akun['body'],$queue['catatan_admin'])!==FALSE&&strpos($akun['body'],'Mulai Perbaikan')!==FALSE,'/akun menampilkan status, catatan, dan aksi perbaikan');

// Start revision dua kali harus menghasilkan satu draft version+1 dan tidak mengubah sumber.
$owner->post('warga/pendataan',['action'=>'start_revision','antrean_id'=>$queueId]);
$revision=draft($db,$ownerId);
preserve_rate_key($db,'warga_submit','object',$revision['id']);
$owner->post('warga/pendataan',['action'=>'start_revision','antrean_id'=>$queueId]);
$revisionAgain=draft($db,$ownerId);
cek((int)$revision['id']===(int)$revisionAgain['id']&&(int)$revision['versi_sebelumnya_id']===(int)$d['id']&&(int)$revision['no_versi']===(int)$d['no_versi']+1,'Start revision idempoten, version+1, dan previous link benar');
$oldAfterRevision=raw_assessment($db,$d['id']);$oldFileAfterRevision=$db->row('SELECT jenis_berkas,path_privat,sha256,ukuran_byte FROM sf_berkas_penilaian WHERE id=?',[$file['id']]);
$revisionFile=$db->row("SELECT * FROM sf_berkas_penilaian WHERE penilaian_id=? AND jenis_berkas='self_photo'",[$revision['id']]);
cek($oldAfterRevision==$submitted&&$oldFileAfterRevision==$fileBefore&&$revisionFile['path_privat']===$file['path_privat']&&is_file($physical),'Versi lama, snapshot data, ledger, dan berkas tetap utuh/readable');

// Edit seluruh langkah revisi, rescore, dan resubmit rekomendasi server baru.
$r=post_step($owner,$revision,'housing_family',matriks());wajib(redirect_ok($r),'Revisi isian matriks');
$revision=lewati_rekomendasi($owner,$db,$ownerId);
wajib(redirect_ok(post_step($owner,$revision,'housing_family_detail',citizen('Pemilik R6 Direvisi')+housing())),'Revisi data warga + rumah keluarga');
$revision=draft($db,$ownerId);$revisedBuilding=building('moderate_damage');wajib(redirect_ok(post_step($owner,$revision,'building_condition',$revisedBuilding)),'Revisi kondisi bangunan');
$revision=draft($db,$ownerId);wajib(redirect_ok(post_step($owner,$revision,'sanitation',sanitation())),'Revisi sanitasi');
$revision=draft($db,$ownerId);wajib(redirect_ok(post_step($owner,$revision,'location_evidence',['location_lat'=>'-7.005145','location_lng'=>'110.438125','akurasi_lokasi_m'=>'8'])),'Rescore revisi');
$revision=draft($db,$ownerId);$revisedRtlh=$db->row("SELECT r.* FROM sf_rekomendasi_penilaian r JOIN sf_program p ON p.id=r.program_id WHERE r.penilaian_id=? AND p.kode_program='rtlh' AND r.status_kelayakan='eligible'",[$revision['id']]);wajib((bool)$revisedRtlh,'Rekomendasi RTLH revisi tersedia');
$owner->post('warga/pendataan',['action'=>'submit','penilaian_id'=>$revision['id'],'rekomendasi_id'=>$revisedRtlh['id']]);
$queueAfterResubmit=$db->row('SELECT * FROM sf_antrean_pengajuan WHERE id=?',[$queueId]);$oldFinal=raw_assessment($db,$d['id']);$revisionSubmitted=raw_assessment($db,$revision['id']);
cek((int)$db->scalar('SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE user_id=?',[$ownerId])===1&&$queueAfterResubmit['kode_tiket']===$ticket&&(int)$queueAfterResubmit['penilaian_id']===(int)$revision['id']&&$queueAfterResubmit['status_antrean']==='pending','Resubmit memakai queue dan tiket yang sama');
cek($oldFinal['status']==='superseded'&&$revisionSubmitted['status']==='submitted','Versi lama superseded dan revisi submitted');
$history=$db->rows('SELECT status_awal,status_akhir,catatan FROM sf_riwayat_keputusan_antrean WHERE antrean_id=? ORDER BY id',[$queueId]);
cek(count($history)===3&&$history[0]['status_akhir']==='pending'&&$history[1]['status_akhir']==='needs_revision'&&$history[2]['status_awal']==='needs_revision'&&$history[2]['status_akhir']==='pending','Riwayat submit, revisi, dan resubmit utuh');

// Admin menyetujui revisi; akun berubah menjadi read-only tanpa aksi revisi.
$admin1->get('Admin_Kabkota/detail/'.$queueId);
$admin1->post('Admin_Kabkota/update_status',['antrean_id'=>$queueId,'status_awal'=>'pending','status'=>'approved','catatan_admin'=>'Data sudah sesuai']);
cek($db->scalar('SELECT status_antrean FROM sf_antrean_pengajuan WHERE id=?',[$queueId])==='approved','Admin menyetujui revisi');
$akun=$owner->get('akun');
cek(strpos($akun['body'],'Disetujui')!==FALSE&&strpos($akun['body'],$ticket)!==FALSE&&strpos($akun['body'],'Mulai Perbaikan')===FALSE,'/akun menampilkan approved dan tidak menawarkan revisi');

// Snapshot sumber dan isi submission lama tidak berubah selain lifecycle status resmi.
$snapshotAfter=$db->row('SELECT id,muatan_ciphertext,muatan_sha256 FROM sf_rekaman_simperum WHERE id=?',[$d['rekaman_simperum_id']]);
$oldComparable=$oldFinal;$submittedComparable=$submitted;unset($oldComparable['status'],$submittedComparable['status'],$oldComparable['updated_at'],$submittedComparable['updated_at']);
cek($snapshotAfter==$snapshotBefore,'Ciphertext dan hash raw snapshot tidak berubah sepanjang revisi');
cek($oldComparable==$submittedComparable&&$oldFinal['status']==='superseded'&&$db->row('SELECT jenis_berkas,path_privat,sha256,ukuran_byte FROM sf_berkas_penilaian WHERE id=?',[$file['id']])==$fileBefore,'Submission lama immutable selain status superseded resmi');

echo "\n=== RINGKASAN ===\n{$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal.\n";
exit($GLOBALS['gagal']?1:0);
