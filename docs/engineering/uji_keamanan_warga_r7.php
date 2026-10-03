<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Check keamanan minimum R7 untuk lookup pendataan warga.
 * Jalankan: php docs/engineering/uji_keamanan_warga_r7.php
 *
 * Check ini sengaja merah sampai registry rate limit warga memenuhi kontrak PRD.
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('PASSWORD', 'UjiWargaR7!');
$total = $failed = 0;
$userId = NULL;
$snapshotIds = [];
$rateKeysBefore = $rateKeysAfter = $scopeKeys = $testedRateKeys = $preservedRateRows = [];

function check_r7($ok, $label) {
    global $total, $failed;
    $total++;
    echo ($ok ? 'OK    ' : 'GAGAL ') . $label . "\n";
    if (!$ok) $failed++;
}
function env_r7($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) continue;
        [$key, $value] = explode('=', $line, 2);
        if (!array_key_exists(trim($key), $out)) $out[trim($key)] = trim($value);
    }
    return $out;
}
class DbR7 {
    public $m;
    function __construct($e) {
        $this->m = new mysqli($e['DB_HOST'], $e['DB_USER'], $e['DB_PASS'] ?? '', $e['DB_NAME']);
        if ($this->m->connect_error) die("Koneksi DB gagal: {$this->m->connect_error}\n");
    }
    function run($sql, $params = []) { $s=$this->prepare($sql,$params);$id=$s->insert_id;$s->close();return $id; }
    function rows($sql, $params = []) { $s=$this->prepare($sql,$params);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows; }
    function scalar($sql, $params = []) { $rows=$this->rows($sql,$params);return $rows ? reset($rows[0]) : NULL; }
    private function prepare($sql, $params) {
        $s=$this->m->prepare($sql);if(!$s)die("Prepare gagal: {$this->m->error}\n");
        if($params){$types=str_repeat('s',count($params));$s->bind_param($types,...$params);}
        if(!$s->execute())die("Query gagal: {$s->error}\n");
        return $s;
    }
}
class HttpR7 {
    private $cookie;
    public $csrf;
    function __construct() { $this->cookie=tempnam(sys_get_temp_dir(),'uji_r7_'); }
    function __destruct() { @unlink($this->cookie); }
    function get($path) { return $this->call($path, NULL, TRUE); }
    function post($path, array $fields, $withCsrf = TRUE, $ajax = TRUE) {
        if ($withCsrf && $this->csrf) $fields['csrf_kpkp_token']=$this->csrf;
        return $this->call($path, $fields, $ajax);
    }
    private function call($path, $fields, $ajax) {
        $ch=curl_init(BASE_URL.'/'.ltrim($path,'/'));
        $headers=$ajax?['X-Requested-With: XMLHttpRequest']:[];
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>TRUE,CURLOPT_COOKIEJAR=>$this->cookie,CURLOPT_COOKIEFILE=>$this->cookie,CURLOPT_FOLLOWLOCATION=>FALSE,CURLOPT_HEADER=>TRUE,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers]);
        if($fields!==NULL){curl_setopt($ch,CURLOPT_POST,TRUE);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($fields));}
        $raw=curl_exec($ch);if($raw===FALSE)die('curl gagal: '.curl_error($ch)."\n");
        $info=curl_getinfo($ch);curl_close($ch);
        $head=substr($raw,0,$info['header_size']);$body=substr($raw,$info['header_size']);
        if(preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/',$body,$m))$this->csrf=$m[1];
        preg_match('/^Retry-After:\s*(\d+)/mi',$head,$retry);
        return ['status'=>$info['http_code'],'body'=>$body,'retry_after'=>isset($retry[1])?(int)$retry[1]:NULL];
    }
}

$db = NULL;
function cleanup_r7() {
    global $db, $userId, $snapshotIds, $rateKeysBefore, $rateKeysAfter;
    global $scopeKeys, $testedRateKeys, $preservedRateRows;
    if (!$db) return;
    if ($userId) {
        $db->run('DELETE FROM sf_antrean_pengajuan WHERE user_id=?',[$userId]);
        $db->run('DELETE FROM sf_penilaian_perumahan WHERE user_id=?',[$userId]);
        $db->run('DELETE FROM sf_profil_warga WHERE user_id=?',[$userId]);
    }
    foreach(array_unique($snapshotIds) as $id)$db->run('DELETE FROM sf_rekaman_simperum WHERE id=?',[$id]);
    if ($userId)$db->run('DELETE FROM usr_akun WHERE id=?',[$userId]);
    foreach (array_unique(array_merge(
        $testedRateKeys,
        array_diff($rateKeysAfter, $rateKeysBefore)
    )) as $key) {
        $db->run('DELETE FROM sys_batas_laju WHERE kunci=?', [$key]);
    }
    foreach ($preservedRateRows as $row) {
        $db->run(
            'INSERT INTO sys_batas_laju (kunci,jendela_mulai_at,jumlah_gagal) VALUES (?,?,?)',
            [$row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']]
        );
    }
}
register_shutdown_function('cleanup_r7');

if(!is_file(ENV_PATH))die(".env tidak ditemukan.\n");
$env=env_r7(ENV_PATH);$db=new DbR7($env);
$email='uji_r7_'.time().'_'.mt_rand(1000,9999).'@example.test';
$userId=$db->run("INSERT INTO usr_akun (email,kata_sandi,nama,nama_pengguna,peran,status,profil_lengkap,created_at) VALUES (?,?,'Uji R7','uji_r7','warga','active',1,NOW())",[$email,password_hash(PASSWORD,PASSWORD_BCRYPT)]);

$nikUji='0000000000000001';
$nikHash=hash_hmac('sha256',$nikUji,$env['KPKP_DATA_PEPPER']);
$scopeKeys=[
    hash('sha256','register:ip:127.0.0.1'),
    hash('sha256','register:ip:::1'),
    // Aplikasi mengelompokkan IPv6 per /64: ::1 tercatat sebagai 0000000000000000/64.
    hash('sha256','register:ip:0000000000000000/64'),
];
$testedRateKeys=array_merge($scopeKeys,[
    hash('sha256','warga_lookup:ip:127.0.0.1'),
    hash('sha256','warga_lookup:ip:::1'),
    hash('sha256','warga_lookup:ip:0000000000000000/64'),
    hash('sha256','warga_lookup:account:'.$userId),
    hash('sha256','warga_lookup:nik:'.$nikHash),
    // Akun uji ini sengaja tidak bernama fixture: lookup-nya gagal verifikasi (3 Okt 2026), jadi
    // ember verifikasi_nik ikut dipinjam dan dikembalikan supaya NIK fixture tidak terkunci 24 jam.
    hash('sha256','verifikasi_nik:account:'.$userId),
    hash('sha256','verifikasi_nik:nik:'.$nikHash),
]);
foreach($testedRateKeys as $key){
    $rows=$db->rows('SELECT kunci,jendela_mulai_at,jumlah_gagal FROM sys_batas_laju WHERE kunci=?',[$key]);
    if($rows)$preservedRateRows[]=$rows[0];
    $db->run('DELETE FROM sys_batas_laju WHERE kunci=?',[$key]);
}
$rateKeysBefore=array_column($db->rows('SELECT kunci FROM sys_batas_laju'),'kunci');

$http=new HttpR7();$http->get('Auth/login');
$login=$http->post('Auth/do_login',['email'=>$email,'password'=>PASSWORD]);
if($login['status']!==200||(json_decode($login['body'],TRUE)['status']??'')!=='success')die("Login akun uji gagal.\n");
$http->get('warga/pendataan');

// CSRF: request valid secara bisnis tanpa token tidak boleh menyentuh state.
$csrf=$http->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01'],FALSE);
check_r7($csrf['status']===403
    && (int)$db->scalar('SELECT COUNT(*) FROM sf_profil_warga WHERE user_id=?',[$userId])===0
    && (int)$db->scalar('SELECT COUNT(*) FROM sf_penilaian_perumahan WHERE user_id=?',[$userId])===0,
    'CSRF ditolak 403 tanpa side effect');

// Scope lain yang penuh tidak boleh memblokir lookup warga.
foreach ($scopeKeys as $scopeKey) {
    $db->run("INSERT INTO sys_batas_laju (kunci,jendela_mulai_at,jumlah_gagal) VALUES (?,NOW(),255) ON DUPLICATE KEY UPDATE jendela_mulai_at=NOW(),jumlah_gagal=255",[$scopeKey]);
}
$first=$http->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']);
check_r7($first['status']!==429,'Penghitung scope register tidak memblokir lookup warga');

$snapshotIds=array_column($db->rows('SELECT rekaman_simperum_id FROM sf_penilaian_perumahan WHERE user_id=? AND rekaman_simperum_id IS NOT NULL',[$userId]),'rekaman_simperum_id');
$responses=[$first];
for($i=1;$i<11;$i++)$responses[]=$http->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']);
$blocked=$responses[10];
check_r7($blocked['status']===429&&$blocked['retry_after']!==NULL&&$blocked['retry_after']>0,
    'Lookup ke-11 diblokir 429 dengan Retry-After');

$rateKeysAfter=array_column($db->rows('SELECT kunci FROM sys_batas_laju'),'kunci');
$newKeys=array_values(array_diff($rateKeysAfter,$rateKeysBefore,$scopeKeys));
check_r7(count($newKeys)>=3
    && !in_array('0000000000000001',$newKeys,TRUE)
    && count(array_filter($newKeys,fn($key)=>preg_match('/^[a-f0-9]{64}$/',$key)))===count($newKeys),
    'Registry mencatat dimensi IP, akun, dan hash NIK tanpa PII mentah');

foreach($newKeys as $key)$db->run("UPDATE sys_batas_laju SET jendela_mulai_at=DATE_SUB(NOW(),INTERVAL 1 DAY) WHERE kunci=?",[$key]);
$reset=$http->post('warga/pendataan',['action'=>'lookup','nik'=>'0000000000000001','birth_date'=>'1980-01-01']);
check_r7($reset['status']!==429,'Jendela kedaluwarsa reset dan lookup dapat dilanjutkan');

echo "RINGKASAN: $total pemeriksaan, $failed gagal\n";
exit($failed?1:0);
