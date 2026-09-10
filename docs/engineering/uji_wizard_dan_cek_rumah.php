<?php
/**
 * Dua fitur yang dipakai warga sungguhan hari ini:
 *   1. Cek Data Rumah (Cek_Rtlh) - pencari NIK
 *   2. Wizard pendataan baru (Warga::pendataan) sampai tiket lahir
 * lalu keputusan admin di SESI TERPISAH.
 *
 *   php docs/engineering/uji_wizard_dan_cek_rumah.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 *
 * MENGGANTIKAN uji_perjalanan_warga_penuh.php. Harness itu menempuh jalur
 * diagnosa LAMA (/solusi_pembiayaan) yang sudah dimatikan begitu
 * `simperum_mode` bernilai `api` - Program::api_cek_simperum() membalas 409
 * "hanya tersedia melalui Wizard Baru Warga". Production bermode `api`, jadi
 * menguji jalur itu di lokal (yang bermode `simulation`) membuktikan sesuatu
 * yang tidak dipakai siapa pun.
 *
 * MEMBERSIHKAN DIRI SENDIRI. Akun dan seluruh barisnya dihapus di akhir,
 * berhasil maupun gagal. Ini BUKAN pelanggaran §20: yang dihapus hanya yang
 * dibuat oleh jalannya skrip ini sendiri, tidak pernah data demo yang lain.
 * Justru harness yang MENINGGALKAN akun-lah yang bermasalah - kolam NIK
 * fixture cuma lima (SIM-01..SIM-05) dan tiap akun mengikatnya PERMANEN,
 * jadi harness yang tidak bersih-bersih akan menghabiskannya dalam beberapa
 * kali jalan lalu memblokir dirinya sendiri. Itu sudah terjadi sekali.
 */

require __DIR__ . '/lib_wizard_warga.php';

define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_SANDI', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
/* Wilayah akun admin demo #26. Fixture dipilih agar tiketnya mendarat di
   sini, kalau tidak keputusan admin ditolak karena di luar cakupan. */
define('KAB_ADMIN', 3374);
define('STEMPEL', date('ymdHis'));
define('EMAIL', 'warga.uji+' . STEMPEL . '@akunuji.test');
define('SANDI', 'UjiWarga123!');

$total = 0; $gagal = 0; $catatan = [];
function cek($b, $l) {
    global $total, $gagal;
    $total++;
    echo ($b ? '  OK    ' : '  GAGAL ') . $l . "\n";
    if ( ! $b) { $gagal++; }
    return (bool) $b;
}
function wajib($b, $l) {
    if ( ! cek($b, $l)) { fwrite(STDERR, "\nBerhenti: prasyarat gagal.\n"); selesai(1); }
}

class Sesi {
    private $jar; private $token = NULL;
    public function __construct($nama) {
        $this->jar = sys_get_temp_dir() . "/uji_wcr_{$nama}_" . getmypid() . '.txt';
        @unlink($this->jar);
    }
    public function __destruct() { @unlink($this->jar); }

    public function minta($path, ?array $post = NULL, $ikuti = TRUE, $ajax = FALSE) {
        if ($post !== NULL) { $post = $this->token() + $post; }
        $r = $this->kirim($path, $post, $ikuti, $ajax);
        if ($post !== NULL && $r['kode'] === 403 && stripos($r['body'], 'Kedaluwarsa') !== FALSE) {
            $this->token = NULL;
            $r = $this->kirim($path, $this->token() + $post, $ikuti, $ajax);
        }
        return $r;
    }
    public function lupakan_token() { $this->token = NULL; }

    private function token() {
        if ($this->token === NULL) {
            $h = $this->kirim('auth/login')['body'];
            $this->token = [];
            if (preg_match('/name="csrf-token-name" content="([^"]+)"/', $h, $a)
                && preg_match('/name="csrf-token-hash" content="([^"]+)"/', $h, $b)) {
                $this->token = [$a[1] => $b[1]];
            } elseif (preg_match('/name="(csrf_[a-z_]+)"\s+value="([^"]+)"/', $h, $m)) {
                $this->token = [$m[1] => $m[2]];
            }
        }
        return $this->token;
    }

    private function kirim($path, ?array $post = NULL, $ikuti = TRUE, $ajax = FALSE) {
        $ch = curl_init(BASE . '/' . ltrim($path, '/'));
        $opt = [
            CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar, CURLOPT_FOLLOWLOCATION => $ikuti,
            CURLOPT_HEADER => TRUE, CURLOPT_TIMEOUT => 60,
        ];
        if ($ajax) { $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
        if ($post !== NULL) { $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post); }
        curl_setopt_array($ch, $opt);
        $mentah = (string) curl_exec($ch);
        $potong = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $out = [
            'kode' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'header' => substr($mentah, 0, $potong),
            'body' => substr($mentah, $potong),
        ];
        curl_close($ch);
        return $out;
    }
}

class Db {
    private $m;
    public function __construct(array $env) {
        $this->m = @new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
        if ($this->m->connect_errno) { fwrite(STDERR, "DB gagal.\n"); exit(1); }
        $this->m->set_charset('utf8mb4');
    }
    public function baris($sql, array $p = []) {
        $s = $this->m->prepare($sql);
        if ($p) { $s->bind_param(str_repeat('s', count($p)), ...$p); }
        $s->execute();
        return $s->get_result()->fetch_assoc();
    }
    public function angka($sql, array $p = []) {
        $r = $this->baris($sql, $p);
        return $r ? (int) array_values($r)[0] : 0;
    }
    public function jalan($sql, array $p = []) {
        $s = $this->m->prepare($sql);
        if ($p) { $s->bind_param(str_repeat('s', count($p)), ...$p); }
        return $s->execute();
    }
}

function env_baca($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $b) {
        $b = trim($b);
        if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $b, 2);
        if ( ! array_key_exists(trim($k), $out)) { $out[trim($k)] = trim($v); }
    }
    return $out;
}

$env = env_baca(ENV_PATH);
$db = new Db($env);

/**
 * Hapus SEGALA yang dibuat jalannya skrip ini, lalu keluar.
 * Dipasang juga sebagai shutdown handler supaya kegagalan di tengah tidak
 * meninggalkan akun yang mengunci NIK fixture.
 */
function selesai($kode = NULL) {
    global $db, $total, $gagal, $catatan;
    static $sudah = FALSE;
    if ($sudah) { return; }
    $sudah = TRUE;

    $akun = $db->baris('SELECT id FROM usr_users WHERE email = ?', [EMAIL]);
    if ($akun) {
        $id = (string) $akun['id'];
        $db->jalan('DELETE FROM sf_riwayat_keputusan_antrean WHERE queue_id IN (SELECT id FROM sf_housing_queue WHERE user_id = ?)', [$id]);
        $db->jalan('DELETE FROM sf_rekomendasi_penilaian WHERE assessment_id IN (SELECT id FROM sf_penilaian_perumahan WHERE user_id = ?)', [$id]);
        $db->jalan('DELETE FROM sf_housing_queue WHERE user_id = ?', [$id]);
        $db->jalan('DELETE FROM sf_penilaian_perumahan WHERE user_id = ?', [$id]);
        $db->jalan('DELETE FROM sf_profil_warga WHERE user_id = ?', [$id]);
        $db->jalan('DELETE FROM sf_rekaman_simperum WHERE requested_by = ?', [$id]);
        $db->jalan('DELETE FROM aduan WHERE user_id = ?', [$id]);
        $db->jalan('DELETE FROM usr_users WHERE id = ?', [$id]);
        echo "\n  (bersih-bersih: akun uji #{$id} dan seluruh barisnya dihapus)\n";
    }

    if ($kode === NULL) {
        echo "\n=== {$total} pemeriksaan, {$gagal} merah ===\n";
        if ($catatan) { echo implode("\n", $catatan) . "\n"; }
        exit($gagal === 0 ? 0 : 1);
    }
    exit($kode);
}
register_shutdown_function('selesai', NULL);

/* Batas laju milik skrip ini sendiri. Bentuk kunci sha256("<policy>:<dimensi>:<nilai>")
   sesuai Rate_limiter::resolve() - bukan "<policy>:<ip>" seperti harness lama,
   yang DELETE-nya tidak pernah mengenai baris apa pun. */
foreach (['127.0.0.1', '::1'] as $ip) {
    /* `warga_submit` WAJIB ikut: dimensinya ip+account+object (5 per 3600 dtk),
       dan dimensi IP itu yang tersentuh lintas jalan walau tiap jalan memakai
       akun dan draft yang baru. Tanpa baris ini, harness hijau pada jalan
       pertama lalu merah pada jalan berikutnya di jam yang sama - merah yang
       menuduh fitur padahal pengamannya yang bekerja. */
    foreach (['register', 'simperum_lookup', 'warga_lookup', 'rtlh_cek', 'rtlh_cek_harian',
              'warga_submit', 'warga_start_revision', 'admin_queue_decision',
              'rtlh_cek_anon'] as $p) {
        $db->jalan('DELETE FROM sys_rate_limits WHERE limit_key = ?', [hash('sha256', $p . ':ip:' . $ip)]);
    }
}

/* NIK fixture + tanggal lahirnya dibaca dari berkasnya. Tanggalnya berbeda
   per fixture dan gateway mencocokkan keduanya. */
$UTAMA = []; $CADANGAN = [];
foreach (glob(dirname(__DIR__, 2) . '/application/fixtures/simperum/SIM-*.json') as $f) {
    $j = json_decode((string) file_get_contents($f), TRUE);
    if (($j['response_status'] ?? '') !== 'found') { continue; }
    if (empty($j['identity']['nik']) || empty($j['identity']['birth_date'])) { continue; }
    /* Wilayah tiket diturunkan dari ISI FIXTURE, bukan dari isian formulir -
       terukur 9 Sep 2026 sesudah perubahan UAT. Fixture yang wilayahnya BUKAN
       KAB melahirkan tiket di luar cakupan admin demo, dan seluruh bagian
       keputusan admin merah walau penegakan cakupannya justru bekerja benar.
       Karena itu yang sewilayah didahulukan, bukan diambil urut nama berkas. */
    if (strpos((string) file_get_contents($f), (string) KAB_ADMIN) !== FALSE) {
        $UTAMA[$j['identity']['nik']] = $j['identity']['birth_date'];
    } else {
        $CADANGAN[$j['identity']['nik']] = $j['identity']['birth_date'];
    }
}
$KANDIDAT = $UTAMA + $CADANGAN;

echo "=== WIZARD BARU + CEK DATA RUMAH ===\n";
echo "Target : " . BASE . "\n";
echo "Warga  : " . EMAIL . "\n\n";

$warga = new Sesi('warga');
$admin = new Sesi('admin');

// ---------------------------------------------------------------------------
echo "A. AKUN WARGA\n";
// ---------------------------------------------------------------------------
wajib($KANDIDAT !== [], 'Fixture simulasi tersedia (' . count($KANDIDAT) . ' NIK)');

$r = $warga->minta('Auth/do_register', [
    'email' => EMAIL, 'password' => SANDI, 'password_confirm' => SANDI, 'tos_agree' => '1',
]);
/* ⚠️ KODE 200 DIPERIKSA LEBIH DULU, dan itu bukan formalitas. Asersi di
   bawahnya berbentuk "pesan galat TIDAK ada", dan pesan yang tidak ada
   otomatis benar ketika responsnya KOSONG. Terbukti 9 Sep 2026: Apache lokal
   mati, tiap permintaan membalas HTTP 0 dengan badan kosong, dan harness ini
   melaporkan "Akun baru lahir" OK untuk akun yang tidak pernah lahir. Asersi
   negatif tanpa penjaga positif di depannya adalah hijau yang tidak berarti. */
wajib($r['kode'] === 200,
    'Server menjawab (HTTP ' . $r['kode'] . '). Kode 0 berarti Apache mati, bukan fitur rusak.');
wajib($r['kode'] !== 429, 'Tidak tertahan batas laju pendaftaran');
wajib(stripos($r['body'], 'Pendaftaran tidak dapat diproses') === FALSE, 'Akun baru lahir');

$NIK = NULL; $LAHIR = NULL; $tolak = [];
foreach ($KANDIDAT as $nik => $lahir) {
    $warga->minta('Auth/save_onboarding', [
        'role' => 'warga', 'username' => 'wargauji' . STEMPEL,
        'nama_lengkap' => 'Warga Uji ' . STEMPEL, 'nik_identitas' => $nik,
        'alamat_domisili' => 'Jl. Uji No. 1, Kota Semarang', 'phone' => '081200000000',
    ]);
    $a = $db->baris('SELECT role FROM usr_users WHERE email = ?', [EMAIL]);
    if ($a && $a['role'] === 'warga') { $NIK = $nik; $LAHIR = $lahir; break; }
    $tolak[$nik] = 'ditolak onboarding';
}
wajib($NIK !== NULL, 'Satu NIK fixture terikat ke akun uji. Ditolak: ' . json_encode($tolak));
echo "  -> NIK {$NIK} (lahir {$LAHIR})\n";

$akun = $db->baris('SELECT id, role, nik FROM usr_users WHERE email = ?', [EMAIL]);
cek($akun['role'] === 'warga', 'DB: peran warga');
cek( ! preg_match('/^[0-9]{16}$/', (string) $akun['nik']), 'DB: NIK terenkripsi, bukan 16 digit polos');

// ---------------------------------------------------------------------------
echo "\nB. CEK DATA RUMAH - TAMU ANONIM\n";
// ---------------------------------------------------------------------------
$tamu = new Sesi('tamu');
$r = $tamu->minta('cek_rtlh');
cek($r['kode'] === 200, 'Halaman terbuka untuk tamu (tidak diusir ke login)');

$r = $tamu->minta('Cek_Rtlh/periksa', ['nik' => $NIK]);
$penanda_hasil = 'NIK ****' . substr($NIK, -4);
cek($r['kode'] === 200 && strpos($r['body'], $penanda_hasil) !== FALSE, 'Tamu melihat hasil dengan NIK tersamar');
cek(strpos($r['body'], '>Nama</dt>') === FALSE && strpos($r['body'], '>Alamat</dt>') === FALSE, 'Hasil tamu tidak memuat nama atau alamat');
cek(stripos($r['body'], 'Masuk untuk melihat hasilnya') === FALSE, 'Hasil tamu tidak diganti permintaan login');

// ---------------------------------------------------------------------------
echo "\nC. CEK DATA RUMAH - SUDAH LOGIN\n";
// ---------------------------------------------------------------------------
$warga->lupakan_token();
$r = $warga->minta('Auth/do_login', ['email' => EMAIL, 'password' => SANDI]);
wajib($r['kode'] === 200, 'Warga masuk');
foreach (['Rekam_Data', 'KemitraanPortal'] as $path) {
    $menu = $warga->minta($path);
    cek($menu['kode'] === 200 && strpos($menu['body'], 'type="password"') !== FALSE
        && strpos($menu['body'], 'akun yang sesuai') !== FALSE,
        'UAT warga: ' . $path . ' meminta login akun yang sesuai');
}
$menu = $warga->minta('tab/pengembang');
cek($menu['kode'] === 200 && strpos($menu['body'], 'Daftar Pengembang Tersertifikasi') !== FALSE
    && strpos($menu['body'], 'Formulir Pendaftaran SRP2') !== FALSE, 'UAT warga 10: menu pilihan pengembang');
foreach (['Pengembang/daftar', 'Pengembang/formulir'] as $path) {
    $menu = $warga->minta($path);
    cek($menu['kode'] === 200 && strpos($menu['body'], 'Akun ini bukan akun pengembang') !== FALSE
        && strpos($menu['body'], 'Kembali ke Beranda') !== FALSE, 'UAT warga 11/12: peringatan dan kembali ke beranda (' . $path . ')');
}
$menu = $warga->minta('tab/bankdata');
cek($menu['kode'] === 200 && strpos($menu['body'], 'Buku Data') !== FALSE
    && strpos($menu['body'], 'dokumen-viewer-root') === FALSE, 'UAT warga 18: kategori Bank Data sebelum flipbook');

$r = $warga->minta('Cek_Rtlh/periksa', ['nik' => '123', 'tgl_lahir' => $LAHIR]);
cek(stripos($r['body'], 'NIK harus 16 digit') !== FALSE, 'NIK cacat ditolak dengan pesan yang jelas');

$profil_sebelum = $db->angka('SELECT COUNT(*) FROM sf_profil_warga WHERE user_id = ?', [(string) $akun['id']]);
$r = $warga->minta('Cek_Rtlh/periksa', ['nik' => $NIK, 'tgl_lahir' => $LAHIR]);
cek($r['kode'] === 200, 'Pencarian NIK dijawab untuk akun yang sudah masuk');
/* Penanda yang SAMA dengan yang dipakai di bagian tamu, cuma kebalikan
   harapannya - jadi kalau blok hasilnya berubah bentuk, dua-duanya merah
   bersamaan dan ketahuan, bukan satu diam-diam lulus. */
cek(strpos($r['body'], $penanda_hasil) !== FALSE,
    "Blok hasil dirender untuk akun yang sudah masuk ({$penanda_hasil})");

/* PENJAGA EFEK SAMPING, dan ini yang paling mudah rusak diam-diam.
   Cek_Rtlh::periksa() memanggil lookup() dengan $requested_by NULL SENGAJA,
   supaya cek cepat tidak menimpa profil pendataan siapa pun. Kalau kelak ada
   yang "merapikan" dengan mengisinya, memeriksa NIK orang lain akan menimpa
   profil warga itu - dan tidak ada yang berteriak. */
cek($db->angka('SELECT COUNT(*) FROM sf_profil_warga WHERE user_id = ?', [(string) $akun['id']]) === $profil_sebelum,
    'Cek cepat TIDAK menulis profil warga (requested_by tetap NULL)');

// ---------------------------------------------------------------------------
echo "\nD. WIZARD BARU SAMPAI TIKET\n";
// ---------------------------------------------------------------------------
$h = $warga->minta('warga/pendataan')['body'];
$form = wizard_form($h);
wajib($form !== NULL, 'Formulir wizard dirender');
wajib(wizard_step($form) === 'find_data', 'Mulai dari step find_data');

$h = $warga->minta('warga/pendataan',
    wizard_medan($form, ['action' => 'lookup', 'nik' => $NIK, 'tgl_lahir' => $LAHIR]))['body'];
wajib(wizard_step($h) !== 'find_data', 'Lookup SIMPERUM membawa maju dari find_data');
foreach (['monthly_income', 'occupation_code', 'education_code', 'employment_stability_code', 'area_condition_code', 'birth_date', 'marital_status_code', 'gender_code', 'phone'] as $field) {
    cek(strpos($h, 'name="' . $field . '"') !== FALSE, 'UAT warga 9: data awal memuat ' . $field);
}
cek(strpos($h, 'name="matrix_dtks_status"') === FALSE, 'UAT warga 9: tidak meminta status DTKS');
cek(strpos($h, 'name="matrix_income_code"') === FALSE, 'UAT warga 9: pendapatan angka, bukan pilihan rentang gaji');
$dom_prefill = wizard_dom($h);
$xpath_prefill = new DOMXPath($dom_prefill);
cek($xpath_prefill->query('//select[@name="matrix_current_housing_code"]/option[@selected and @value!=""]')->length === 1,
    'Status tempat tinggal otomatis terpilih dari SIMPERUM');
foreach (['occupation_code', 'education_code', 'gender_code'] as $field) {
    cek($xpath_prefill->query('//select[@name="' . $field . '"]/option[@selected and @value!=""]')->length === 1,
        'SIMPERUM mengisi otomatis ' . $field);
}

$dilewati = [];
$profile_before = $db->baris('SELECT phone_ciphertext, field_provenance_json FROM sf_profil_warga WHERE user_id=?', [(string) $akun['id']]);
$source_phone = $xpath_prefill->query('//input[@name="phone"]')->item(0)->getAttribute('value');
$db->jalan('UPDATE sf_profil_warga SET phone_ciphertext=NULL WHERE user_id=?', [(string) $akun['id']]);
$h = $warga->minta('warga/pendataan')['body'];
$xpath_account = new DOMXPath(wizard_dom($h));
cek($xpath_account->query('//input[@name="phone" and @value="081200000000"]')->length === 1,
    'Draft lama tanpa HP sumber terisi dari profil akun yang sudah didaftarkan');
cek($xpath_account->query('//input[@name="monthly_income" and @value=""]')->length === 1,
    'Rentang penghasilan SIMPERUM tidak dikarang menjadi nominal rupiah');
$corrected = json_decode($profile_before['field_provenance_json'], TRUE);
$corrected['phone'] = ['source' => 'citizen_correction'];
$db->jalan('UPDATE sf_profil_warga SET phone_ciphertext=?, field_provenance_json=? WHERE user_id=?',
    [$profile_before['phone_ciphertext'], json_encode($corrected), (string) $akun['id']]);
$db->jalan("UPDATE sf_penilaian_perumahan SET matrix_current_housing_code='house_none_or_rent' WHERE user_id=?", [(string) $akun['id']]);
$xpath_corrected = new DOMXPath(wizard_dom($warga->minta('warga/pendataan')['body']));
cek($xpath_corrected->query('//input[@name="phone"]')->item(0)->getAttribute('value') === $source_phone,
    'HP koreksi tersimpan tidak ditimpa HP akun');
cek($xpath_corrected->query('//select[@name="matrix_current_housing_code"]/option[@selected and @value="house_none_or_rent"]')->length === 1,
    'Pilihan status tersimpan tetap menang atas status rumah sumber');
$db->jalan('UPDATE sf_penilaian_perumahan SET matrix_current_housing_code=NULL WHERE user_id=?', [(string) $akun['id']]);
$db->jalan('UPDATE sf_profil_warga SET phone_ciphertext=NULL, field_provenance_json=? WHERE user_id=?',
    [$profile_before['field_provenance_json'], (string) $akun['id']]);
for ($i = 0; $i < 14; $i++) {
    $form = wizard_form($h);
    $step = wizard_step($form);
    if ($step === NULL) { break; }
    if ($step === 'housing_family_detail') {
        $xpath_detail = new DOMXPath($form->ownerDocument);
        foreach (['assistance_source_code', 'assistance_year'] as $field) {
            cek($xpath_detail->query('//*[@name="' . $field . '" and not(ancestor::*[@hidden])]')->length === 1,
                'UAT warga 9: sumber/tahun bantuan terlihat pada kedua cabang (' . $field . ')');
        }
    }
    $dilewati[] = $step;
    if ($step === 'review') { break; }
    $paksa = ['action' => 'save'];
    if ($step === 'housing_family') $paksa['matrix_current_housing_code'] = getenv('UJI_CABANG') === 'tanah' ? 'house_none_or_rent' : 'house_owned';
    if ($step === 'housing_family_detail') $paksa['has_other_land'] = '1';
    $baru = $warga->minta('warga/pendataan', wizard_medan($form, $paksa))['body'];
    if (wizard_step($baru) === $step) {
        wajib(FALSE, "Wizard mandek di step '{$step}' (tidak maju setelah simpan)");
    }
    $h = $baru;
}
cek(in_array('review', $dilewati, TRUE), 'Wizard tembus sampai review: ' . implode(' -> ', $dilewati));
cek(getenv('UJI_CABANG') === 'tanah'
    ? in_array('candidate_land', $dilewati, TRUE) && ! in_array('building_condition', $dilewati, TRUE)
    : in_array('building_condition', $dilewati, TRUE) && ! in_array('candidate_land', $dilewati, TRUE),
    'UAT warga 9: hanya cabang sesuai pilihan yang dilewati');
cek(array_search('preliminary_recommendation', $dilewati, TRUE) < array_search('housing_family_detail', $dilewati, TRUE), 'UAT warga 9: rekomendasi tampil sebelum data pelengkap');

$form = wizard_form($h);
$payload = wizard_medan($form, ['action' => 'submit']);
/* Radio ini HANYA dirender kalau ada rekomendasi berstatus eligible/potential
   (pendataan.php). Ketiadaannya berarti mesin rekomendasi tidak menghasilkan
   apa pun - itulah yang terjadi pada jalur isi-manual, karena welfare_decile
   hanya terisi dari SIMPERUM. Diperiksa terpisah supaya sebabnya terbaca. */
wajib(isset($payload['recommendation_id']) && $payload['recommendation_id'] !== '',
    'Step review menawarkan program untuk diajukan (recommendation_id dirender)');

$sebelum = $db->angka('SELECT COALESCE(MAX(id),0) FROM sf_housing_queue');
$r = $warga->minta('warga/pendataan', $payload);
preg_match('/tiket ([A-Z0-9\/-]+)/i', $r['body'], $m);
/* Kalau gagal, SEBABNYA ikut dicetak. Submit bisa ditolak karena batas laju
   (`warga_submit`), rekomendasi kedaluwarsa, atau draft berubah - dan merah
   tanpa sebab memaksa penyelidikan ulang dari nol tiap kali. */
$sebab = '';
if (empty($m[1])) {
    $bersih = preg_replace('/\s+/', ' ', strip_tags($r['body']));
    foreach (['Batas pengiriman pengajuan tercapai', 'Pengajuan belum dapat dikirim',
              'rekomendasi tidak dapat diajukan', 'Draft sudah berubah',
              'Terlalu banyak'] as $pola) {
        if (($i = stripos($bersih, $pola)) !== FALSE) {
            $sebab = ' [' . trim(substr($bersih, $i, 90)) . ']';
            break;
        }
    }
    if ($sebab === '') { $sebab = ' [sebab tidak dikenali]'; }
}
cek( ! empty($m[1]), 'Pengajuan terkirim dan bernomor tiket'
    . ( ! empty($m[1]) ? " ({$m[1]})" : $sebab));

$tiket = $db->baris(
    'SELECT q.id, q.status_antrean, q.kabupaten_id, p.kode_program, p.nama_program
     FROM sf_housing_queue q LEFT JOIN sf_programs p ON p.id = q.program_id
     WHERE q.id > ? ORDER BY q.id DESC LIMIT 1', [(string) $sebelum]);
wajib($tiket !== NULL, 'Baris antrean lahir di DB');
cek($tiket['status_antrean'] === 'pending', 'Lahir sebagai pending');
cek( ! empty($tiket['kode_program']), 'Terikat ke program SUNGGUHAN dari sf_programs: '
    . $tiket['kode_program'] . ' (' . $tiket['nama_program'] . ')');
$catatan[] = "Program yang diajukan: {$tiket['kode_program']} - {$tiket['nama_program']}";
$catatan[] = "Kabupaten tiket: {$tiket['kabupaten_id']}";

// ---------------------------------------------------------------------------
echo "\nE. KEPUTUSAN ADMIN DI SESI TERPISAH\n";
// ---------------------------------------------------------------------------
$r = $admin->minta('Auth/do_login', ['email' => ADMIN_EMAIL, 'password' => ADMIN_SANDI]);
wajib($r['kode'] === 200, 'Admin kab/kota masuk di sesi sendiri');

$d = $admin->minta('Admin_Kabkota');
cek($d['kode'] === 200, 'Dashboard antrean admin terbuka');
cek(strpos($d['body'], (string) $tiket['id']) !== FALSE, "Tiket #{$tiket['id']} terlihat oleh admin wilayahnya");

/* Dua sesi harus benar-benar terpisah. Kalau kuki tertukar, pemeriksaan ini
   merah - dan itu memang tugasnya. */
$cek_silang = $warga->minta('Admin_Kabkota', NULL, FALSE);
cek($cek_silang['kode'] !== 200, 'Sesi warga tetap warga, tidak bisa membuka layar admin');

$admin->lupakan_token();
$admin->minta('Admin_Kabkota/detail/' . $tiket['id']);
$admin->minta('Admin_Kabkota/update_status', [
    'queue_id' => $tiket['id'], 'from_status' => 'pending',
    'status' => 'needs_revision', 'catatan_admin' => 'Tinjau ulang oleh harness uji.',
], FALSE);
$b = $db->baris('SELECT status_antrean, reviewed_by, catatan_admin FROM sf_housing_queue WHERE id = ?', [(string) $tiket['id']]);
cek($b['status_antrean'] === 'needs_revision', 'Keputusan tinjau ulang tersimpan');
cek( ! empty($b['reviewed_by']), 'Pengambil keputusan tercatat');
cek(trim((string) $b['catatan_admin']) !== '', 'Catatan admin tersimpan');

$admin->minta('Admin_Kabkota/update_status', [
    'queue_id' => $tiket['id'], 'from_status' => 'pending',
    'status' => 'approved', 'catatan_admin' => '',
], FALSE);
cek($db->baris('SELECT status_antrean FROM sf_housing_queue WHERE id = ?', [(string) $tiket['id']])['status_antrean'] === 'needs_revision',
    'Transisi dari status yang sudah berubah DITOLAK (tetap needs_revision)');

selesai();
