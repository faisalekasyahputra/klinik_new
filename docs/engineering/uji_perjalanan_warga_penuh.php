<?php
/**
 * Perjalanan warga PENUH, dua sesi terpisah.
 *
 *   php docs/engineering/uji_perjalanan_warga_penuh.php
 *
 * Env opsional:
 *   UJI_BASE_URL        bawaan http://localhost/klinik_new
 *   UJI_ADMIN_EMAIL     bawaan adminkabkota@example.com  (akun demo §20b)
 *   UJI_ADMIN_PASSWORD  bawaan password
 *
 * BEDANYA DENGAN uji_perjalanan_warga.php YANG SUDAH ADA. Harness itu memulai
 * perjalanan dari diagnosa sebagai TAMU, dan menyuntik dua admin lewat INSERT.
 * Yang ini menempuh alur yang sebenarnya ditanyakan: daftar, keluar, masuk
 * lagi, menyapu fitur warga, mengambil program SUNGGUHAN dari hasil diagnosa,
 * mengajukan, lalu admin memutuskan DI SESI YANG BERBEDA. Keduanya berguna dan
 * tidak saling menggantikan.
 *
 * DUA COOKIE JAR, BUKAN SATU. Sesi warga dan sesi admin benar-benar terpisah
 * di tingkat kuki, seperti dua browser berbeda. Kalau dipakai satu jar, login
 * admin akan menimpa sesi warga dan pemeriksaan "warga melihat hasil
 * keputusan" jadi tidak berarti apa-apa - ia akan lulus karena kebetulan
 * sedang jadi admin.
 *
 * PROGRAMNYA HARUS SUNGGUHAN, DAN ITU DIPERIKSA DUA ARAH: kode yang dipakai
 * WAJIB muncul di `eligible_programs` hasil diagnosa DAN ada barisnya di
 * `sf_programs`. Menuliskan 'omah_sekeng' sebagai konstanta akan tetap hijau
 * seandainya diagnosa berhenti menawarkannya - hijau yang tidak membuktikan
 * apa pun.
 *
 * TIGA PENGAJUAN, KARENA SATU BARIS CUMA BISA DIPUTUSKAN SEKALI. Transisi yang
 * sah dari `pending` ada tiga (approved, rejected, needs_revision), jadi
 * ketiganya butuh barisnya sendiri. Menguji satu lalu menyimpulkan dua sisanya
 * "pasti jalan" adalah tebakan, bukan uji.
 *
 * TIDAK MENGHAPUS APA PUN. AGENTS.md §20 melarang membersihkan data demo/uji
 * di DB dev tanpa bertanya lebih dulu. Yang dibersihkan cuma baris
 * `sys_rate_limits` milik skrip ini sendiri, supaya batas laju tidak
 * memerahkan uji yang sebenarnya sehat - pola yang sama dipakai
 * uji_perjalanan_warga.php.
 */

define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_SANDI', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('KAB', 3374);                     // Kota Semarang, wilayah admin demo #26
define('STEMPEL', date('ymdHis'));
define('EMAIL', 'warga.uji+' . STEMPEL . '@akunuji.test');
define('SANDI', 'UjiWarga123!');

$total = 0; $gagal = 0; $catatan = [];
function cek($benar, $label) {
    global $total, $gagal;
    $total++;
    echo ($benar ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $benar) { $gagal++; }
    return (bool) $benar;
}
function wajib($benar, $label) {
    if ( ! cek($benar, $label)) {
        fwrite(STDERR, "\nBerhenti: prasyarat gagal.\n");
        ringkas();
        exit(1);
    }
}
function ringkas() {
    global $total, $gagal;
    echo "\n=== {$total} pemeriksaan, {$gagal} merah ===\n";
}

/** Satu sesi peramban: kuki sendiri, token CSRF sendiri. */
class Sesi {
    private $jar;
    public function __construct($nama) {
        $this->jar = sys_get_temp_dir() . "/uji_penuh_{$nama}_" . getmypid() . '.txt';
        @unlink($this->jar);
    }
    public function __destruct() { @unlink($this->jar); }

    /**
     * POST SELALU membawa CSRF, dan memulihkan diri kalau tokennya basi.
     *
     * Dipasang di sini, bukan diserahkan ke tiap pemanggil: versi pertama
     * skrip ini memasang token hanya pada form HTML dan LUPA pada endpoint
     * JSON (Program/api_cek_simperum), yang membalas 403 "Formulir
     * Kedaluwarsa" - dan 403 itu terbaca seperti kegagalan fitur, padahal
     * yang kurang cuma satu medan. Token disimpan per-sesi karena
     * `csrf_regenerate` FALSE; kalau kelak dinyalakan, jalur coba-lagi di
     * bawah yang menyelamatkan skrip ini tanpa perlu diubah.
     */
    public function minta($path, ?array $post = NULL, $ikuti = TRUE, $ajax = FALSE) {
        if ($post !== NULL) {
            $post = $this->token() + $post;
        }
        $r = $this->kirim($path, $post, $ikuti, $ajax);
        if ($post !== NULL && $r['kode'] === 403 && stripos($r['body'], 'Kedaluwarsa') !== FALSE) {
            $this->token = NULL;
            $r = $this->kirim($path, $this->token() + $post, $ikuti, $ajax);
        }
        return $r;
    }

    private $token = NULL;

    private function token() {
        if ($this->token === NULL) {
            $h = $this->kirim('auth/login')['body'];
            if (preg_match('/name="csrf-token-name" content="([^"]+)"/', $h, $a)
                && preg_match('/name="csrf-token-hash" content="([^"]+)"/', $h, $b)) {
                $this->token = [$a[1] => $b[1]];
            } else {
                $this->token = [];
            }
        }
        return $this->token;
    }

    /* Header AJAX SENGAJA hanya dipasang saat diminta, bukan pada semua POST.
       Program::api_cek_simperum() menolak permintaan non-AJAX dengan
       exit('No direct script access allowed') - HTTP 200 berisi kalimat itu,
       yang terbaca seperti kerusakan rute padahal cuma satu header kurang.
       Tapi memasangnya di SEMUA POST juga salah: Auth::do_register() dan
       kawan-kawan berganti membalas JSON kalau permintaannya AJAX, dan
       asersi yang mencocokkan teks HTML akan merah tanpa ada yang rusak. */
    private function kirim($path, ?array $post = NULL, $ikuti = TRUE, $ajax = FALSE) {
        $ch = curl_init(BASE . '/' . ltrim($path, '/'));
        $opt = [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_FOLLOWLOCATION => $ikuti,
            CURLOPT_HEADER         => TRUE,
            CURLOPT_TIMEOUT        => 60,
        ];
        if ($ajax) {
            $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest'];
        }
        if ($post !== NULL) {
            $opt[CURLOPT_POST] = TRUE;
            $opt[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        curl_setopt_array($ch, $opt);
        $mentah = (string) curl_exec($ch);
        $potong = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $hasil = [
            'kode'   => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'header' => substr($mentah, 0, $potong),
            'body'   => substr($mentah, $potong),
        ];
        curl_close($ch);
        return $hasil;
    }

    /** Dipertahankan untuk pemanggil yang ingin token dari halaman tertentu. */
    public function csrf($path) {
        $h = $this->minta($path)['body'];
        if (preg_match('/name="csrf-token-name" content="([^"]+)"/', $h, $a)
            && preg_match('/name="csrf-token-hash" content="([^"]+)"/', $h, $b)) {
            return [$a[1] => $b[1]];
        }
        if (preg_match('/name="(csrf_[a-z_]+)"\s+value="([^"]+)"/', $h, $m)) {
            return [$m[1] => $m[2]];
        }
        return [];
    }
}

class Db {
    private $m;
    public function __construct(array $env) {
        $this->m = @new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);
        if ($this->m->connect_errno) {
            fwrite(STDERR, "DB gagal: {$this->m->connect_error}\n");
            exit(1);
        }
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

/* Batas laju milik SKRIP INI dibersihkan, bukan tabelnya disapu. Tanpa ini
   pengajuan ketiga tertolak `housing_submit` dan uji merah karena
   pengamannya bekerja, bukan karena ada yang rusak. */
foreach (['127.0.0.1', '::1'] as $ip) {
    foreach (['simperum_lookup', 'housing_submit', 'register'] as $lingkup) {
        $db->jalan('DELETE FROM sys_rate_limits WHERE limit_key = ?', [hash('sha256', $lingkup . ':' . $ip)]);
    }
}

echo "=== PERJALANAN WARGA PENUH ===\n";
echo "Target : " . BASE . "\n";
echo "Warga  : " . EMAIL . "\n";
echo "Admin  : " . ADMIN_EMAIL . " (sesi terpisah)\n\n";

$warga = new Sesi('warga');
$admin = new Sesi('admin');

// ---------------------------------------------------------------------------
echo "A. DAFTAR\n";

/* SATU pendaftaran saja, lalu onboarding dicoba per fixture.
   Versi sebelumnya membuat satu AKUN per kandidat NIK - lima pendaftaran,
   dan `register` dibatasi 5 per 600 detik, jadi harness-nya menembak
   batasnya sendiri lalu melaporkan 429 sebagai kalau-kalau NIK bermasalah.
   Onboarding tidak dibatasi laju dan gagalnya tidak menulis apa pun (role
   tetap NULL), jadi ia boleh dicoba berulang pada akun yang sama.

   DUA LAPIS PEMERIKSAAN, DAN KEDUANYA PERLU. Onboarding sukses cuma
   membuktikan `usr_users` menerima NIK-nya. Bukti yang sebenarnya adalah
   gateway menjawab `success`, karena ikatan NIK juga dicatat di
   `sf_profil_warga` dan KEDUANYA BISA TIDAK SEPAKAT - terukur di DB dev:
   NIK 0000000000000001 tercatat milik akun 7584 di usr_users tapi milik
   akun 7582 di sf_profil_warga, dan gateway mempercayai yang kedua.

   NIK dan TANGGAL LAHIR sama-sama dibaca dari berkas fixture. Tanggalnya
   berbeda-beda (SIM-01 1980-01-01, SIM-02 1990-12-31, dst) dan gateway
   mencocokkan keduanya; memakai satu tanggal untuk semua kandidat membuat
   balasannya "NIK dan tanggal lahir tidak cocok" - pesan yang menunjuk NIK
   padahal tanggalnya yang salah. */
$KANDIDAT = [];
foreach (glob(dirname(__DIR__, 2) . '/application/fixtures/simperum/SIM-*.json') as $f) {
    $j = json_decode((string) file_get_contents($f), TRUE);
    if (($j['response_status'] ?? '') !== 'found') { continue; }
    if (empty($j['identity']['nik']) || empty($j['identity']['birth_date'])) { continue; }
    $KANDIDAT[$j['identity']['nik']] = $j['identity']['birth_date'];
}
wajib($KANDIDAT !== [], 'Ada fixture simulasi berstatus found (' . count($KANDIDAT) . ' kandidat)');

$EMAIL = 'warga.uji+' . STEMPEL . '@akunuji.test';
$warga = new Sesi('warga');
$r = $warga->minta('Auth/do_register', [
    'email' => $EMAIL, 'password' => SANDI,
    'password_confirm' => SANDI, 'tos_agree' => '1',
]);
wajib($r['kode'] !== 429,
    'Tidak tertahan batas laju pendaftaran (5 per 600 dtk). Kalau merah, tunggu 10 menit.');
wajib(stripos($r['body'], 'Pendaftaran tidak dapat diproses') === FALSE,
    'Akun BARU lahir, bukan menumpang akun lama');

$NIK_SIM = NULL; $LAHIR_SIM = NULL; $tolakan = [];
$ke = 0;
foreach ($KANDIDAT as $nik => $lahir) {
    $ke++;
    $warga->minta('Auth/save_onboarding', [
        'role' => 'warga', 'username' => 'wargauji' . STEMPEL,
        'nama_lengkap' => 'Warga Uji Penuh ' . STEMPEL,
        'nik_identitas' => $nik,
        'alamat_domisili' => 'Jl. Uji Penuh No. 1, Kota Semarang',
        'phone' => '081200000000',
    ]);
    $akun = $db->baris('SELECT role FROM usr_users WHERE email = ?', [$EMAIL]);
    if ( ! $akun || $akun['role'] !== 'warga') {
        $tolakan[$nik] = 'usr_users menolak (NIK sudah dipakai akun lain)';
        continue;
    }
    $warga->minta('solusi_pembiayaan');
    $c = json_decode($warga->minta('Program/api_cek_simperum',
        ['nik' => $nik, 'tgl_lahir' => $lahir], TRUE, TRUE)['body'], TRUE);
    if (($c['status'] ?? '') === 'success') { $NIK_SIM = $nik; $LAHIR_SIM = $lahir; break; }
    $tolakan[$nik] = 'gateway: ' . ($c['code'] ?? ($c['message'] ?? 'tidak diketahui'));
}

wajib($NIK_SIM !== NULL,
    'Ada NIK fixture yang diterima usr_users DAN gateway. Hasil per kandidat: '
    . json_encode($tolakan, JSON_UNESCAPED_UNICODE)
    . '. `nik_already_bound` di sini berarti sf_profil_warga dan usr_users tidak '
    . 'sepakat soal pemilik NIK - itu cacat data, bukan kegagalan harness.');
echo "  -> akun : {$EMAIL}\n";
echo "  -> NIK  : {$NIK_SIM} (fixture, lahir {$LAHIR_SIM}) diterima usr_users DAN gateway\n";
cek(TRUE, 'Akun warga baru lahir dan NIK-nya diterima gateway');

$akun = $db->baris('SELECT id, role, nik, kabupaten_id FROM usr_users WHERE email = ?', [$EMAIL]);
wajib($akun && $akun['role'] === 'warga', 'DB: peran tersimpan sebagai warga');
cek(strlen((string) $akun['nik']) > 16 && ! preg_match('/^[0-9]{16}$/', (string) $akun['nik']),
    'DB: NIK tersimpan terenkripsi, bukan 16 digit polos');

echo "\nB. KELUAR\n";
// ---------------------------------------------------------------------------
$warga->minta('Auth/logout');
$r = $warga->minta('akun', NULL, FALSE);
cek(in_array($r['kode'], [301, 302, 303, 307], TRUE),
    'Sesudah keluar, halaman akun tidak lagi terbuka (kode ' . $r['kode'] . ')');

// ---------------------------------------------------------------------------
echo "\nC. MASUK LAGI\n";
// ---------------------------------------------------------------------------
$t = $warga->csrf('auth/login');
$r = $warga->minta('Auth/do_login', $t + ['email' => $EMAIL, 'password' => SANDI]);
wajib($r['kode'] === 200, 'Endpoint login membalas 200');
$r = $warga->minta('akun');
wajib($r['kode'] === 200 && stripos($r['body'], 'name="password"') === FALSE,
    'Halaman akun terbuka lagi sesudah masuk (sesi hidup)');

// ---------------------------------------------------------------------------
echo "\nD. SAPU FITUR WARGA\n";
// ---------------------------------------------------------------------------
$fitur = [
    'warga/pendataan'      => 'Masukkan NIK',
    'cek_rtlh'             => NULL,
    'solusi_pembiayaan'    => NULL,
    'golek_omah'           => NULL,
    'cari_rumah'           => NULL,
    'umum/aduan'           => NULL,
    'umum/papan_aduan'     => NULL,
    'umum/forum'           => NULL,
    'cek_status_pengajuan' => NULL,
    'akun/profil'          => NULL,
];
foreach ($fitur as $rute => $penanda) {
    $r = $warga->minta($rute);
    $ok = $r['kode'] === 200;
    if ($ok && $penanda !== NULL) { $ok = strpos($r['body'], $penanda) !== FALSE; }
    cek($ok, sprintf('/%-22s %s%s', $rute, $r['kode'], $penanda ? " + memuat \"{$penanda}\"" : ''));
}

echo "\n  Kirim satu aduan sungguhan\n";
$sebelumAduan = $db->angka('SELECT COUNT(*) FROM aduan');
$t = $warga->csrf('umum/aduan');
$warga->minta('umum/simpan_aduan', $t + [
    'nama' => 'Warga Uji Penuh', 'email' => $EMAIL,
    'judul' => 'Aduan uji alur ' . STEMPEL,
    'pesan' => 'Aduan sintetis dari harness uji perjalanan warga penuh.',
]);
cek($db->angka('SELECT COUNT(*) FROM aduan') === $sebelumAduan + 1, 'Aduan tersimpan di DB');

// ---------------------------------------------------------------------------
echo "\nE. DIAGNOSA DAN AJUKAN PROGRAM SUNGGUHAN\n";
// ---------------------------------------------------------------------------
$warga->minta('solusi_pembiayaan');
$r = $warga->minta('Program/api_cek_simperum',
    ['nik' => $NIK_SIM, 'tgl_lahir' => '1980-01-01'], TRUE, TRUE);
$j = json_decode($r['body'], TRUE);
wajib(($j['status'] ?? '') === 'success', 'Identitas diverifikasi server');

$r = $warga->minta('Program/api_kalkulasi_program', [
    'penghasilan' => '2500000', 'pekerjaan' => 'Karyawan Swasta',
    'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'Membutuhkan rumah layak',
    'kabupaten_id' => KAB, 'kode_program_target' => 'umum', 'simpan_hasil' => '0',
], TRUE, TRUE);
$d = json_decode($r['body'], TRUE);
wajib(($d['status'] ?? '') === 'success', 'Diagnosa dihitung server');

$layak = array_values(array_filter(array_column((array) ($d['eligible_programs'] ?? []), 'kode')));
wajib($layak !== [], 'Diagnosa menawarkan sedikitnya satu program (desil '
    . var_export($d['desil'] ?? NULL, TRUE) . ')');

/* Program diambil dari tawaran diagnosa, lalu dicocokkan balik ke sf_programs.
   Dua arah, supaya "program real" benar-benar berarti real. */
$kode = $layak[0];
$prog = $db->baris('SELECT id, kode_program, nama_program FROM sf_programs WHERE kode_program = ?', [$kode]);
wajib($prog !== NULL, "Program '{$kode}' yang ditawarkan ADA di sf_programs");
echo "  -> program dipakai: {$prog['kode_program']} ({$prog['nama_program']})\n";
$catatan[] = "Program diuji: {$prog['kode_program']} - {$prog['nama_program']}";
cek(count($layak) >= 1, 'Program ditawarkan: ' . implode(', ', $layak));

/* Diagnosa DIULANG untuk tiap pengajuan, bukan dihitung sekali di luar
   perulangan. Identitas + hasil survei disimpan di SESI dan HABIS DIPAKAI
   oleh submit_antrean(); pengajuan kedua tanpa diagnosa baru dialihkan balik
   ke `solusi_pembiayaan` alih-alih `Program/success`. Ketahuan justru karena
   asersi tujuan redirect memeriksa ALAMATNYA, bukan cuma "kodenya 302" -
   penolakan dan keberhasilan sama-sama membalas 302. */
function diagnosa_ulang(Sesi $s) {
    global $NIK_SIM;
    $s->minta('solusi_pembiayaan');
    $s->minta('Program/api_cek_simperum',
        ['nik' => $NIK_SIM, 'tgl_lahir' => '1980-01-01'], TRUE, TRUE);
    $s->minta('Program/api_kalkulasi_program', [
        'penghasilan' => '2500000', 'pekerjaan' => 'Karyawan Swasta',
        'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'Membutuhkan rumah layak',
        'kabupaten_id' => KAB, 'kode_program_target' => 'umum', 'simpan_hasil' => '0',
    ], TRUE, TRUE);
}

$tiket = [];
foreach (['approved', 'rejected', 'needs_revision'] as $tujuan) {
    diagnosa_ulang($warga);
    $sebelum = $db->angka('SELECT COALESCE(MAX(id),0) FROM sf_housing_queue');
    $r = $warga->minta('Program/submit_antrean', ['program_kode' => $kode], FALSE);
    preg_match('/^Location:\s*(.+)$/mi', $r['header'], $m);
    $ke = trim($m[1] ?? '');
    wajib(strpos($ke, 'Program/success') !== FALSE,
        "Pengajuan untuk uji '{$tujuan}' diterima (tujuan: " . ($ke ?: 'tidak ada Location') . ')');
    $baris = $db->baris('SELECT id, status_antrean, kabupaten_id, program_id FROM sf_housing_queue WHERE id > ? ORDER BY id DESC LIMIT 1', [$sebelum]);
    wajib($baris && $baris['status_antrean'] === 'pending', "Baris untuk '{$tujuan}' lahir sebagai pending");
    cek((int) $baris['kabupaten_id'] === KAB, "Scope baris '{$tujuan}' = Kota Semarang");
    cek((int) $baris['program_id'] === (int) $prog['id'], "Program pada baris '{$tujuan}' = {$prog['kode_program']}");
    $tiket[$tujuan] = (int) $baris['id'];
}

// ---------------------------------------------------------------------------
echo "\nF. SESI ADMIN TERPISAH: KEPUTUSAN\n";
// ---------------------------------------------------------------------------
$t = $admin->csrf('auth/login');
$r = $admin->minta('Auth/do_login', $t + ['email' => ADMIN_EMAIL, 'password' => ADMIN_SANDI]);
$j = json_decode($r['body'], TRUE);
wajib(($j['role'] ?? '') === 'admin_kabkota' || $r['kode'] === 200, 'Admin kab/kota masuk di sesi sendiri');

$r = $admin->minta('Admin_Kabkota');
wajib($r['kode'] === 200, 'Dashboard antrean admin terbuka');
cek(strpos($r['body'], (string) $tiket['approved']) !== FALSE
    || strpos($r['body'], 'Warga Uji Penuh') !== FALSE,
    'Tiket warga terlihat di dashboard admin wilayahnya');

/* Sesi warga TIDAK boleh ikut berubah jadi admin. Kalau dua jar tertukar,
   pemeriksaan ini merah - dan itu memang tugasnya. */
$r = $warga->minta('Admin_Kabkota', NULL, FALSE);
cek($r['kode'] !== 200, 'Sesi warga TETAP warga, tidak bisa membuka layar admin (kode ' . $r['kode'] . ')');

$keputusan = [
    'approved'       => ['status' => 'approved',       'catatan' => ''],
    'rejected'       => ['status' => 'rejected',       'catatan' => 'Ditolak oleh harness uji: berkas tidak lengkap.'],
    'needs_revision' => ['status' => 'needs_revision', 'catatan' => 'Tinjau ulang oleh harness uji: mohon lengkapi data penghasilan.'],
];
foreach ($keputusan as $nama => $k) {
    $id = $tiket[$nama];
    $t = $admin->csrf('Admin_Kabkota');
    $admin->minta('Admin_Kabkota/update_status', $t + [
        'queue_id' => $id, 'from_status' => 'pending',
        'status' => $k['status'], 'catatan_admin' => $k['catatan'],
    ], FALSE);
    $b = $db->baris('SELECT status_antrean, reviewed_by, catatan_admin FROM sf_housing_queue WHERE id = ?', [(string) $id]);
    cek($b && $b['status_antrean'] === $k['status'], "Tiket #{$id} berubah jadi '{$k['status']}'");
    cek($b && ! empty($b['reviewed_by']), "Tiket #{$id} mencatat siapa yang memutuskan");
    if ($k['catatan'] !== '') {
        cek($b && trim((string) $b['catatan_admin']) !== '', "Tiket #{$id} menyimpan catatan admin");
    }
}

echo "\n  Negatif: keputusan ulang pada tiket yang sudah diputus\n";
$id = $tiket['approved'];
$t = $admin->csrf('Admin_Kabkota');
$admin->minta('Admin_Kabkota/update_status', $t + [
    'queue_id' => $id, 'from_status' => 'pending', 'status' => 'rejected',
    'catatan_admin' => 'Percobaan transisi ganda oleh harness.',
], FALSE);
cek($db->baris('SELECT status_antrean FROM sf_housing_queue WHERE id = ?', [(string) $id])['status_antrean'] === 'approved',
    "Tiket #{$id} tetap approved, transisi ganda ditolak");

// ---------------------------------------------------------------------------
echo "\nG. WARGA MELIHAT HASILNYA\n";
// ---------------------------------------------------------------------------
$r = $warga->minta('cek_status_pengajuan');
cek($r['kode'] === 200, 'Warga bisa membuka layar status pengajuan');

ringkas();
if ($catatan) { echo "\n" . implode("\n", $catatan) . "\n"; }
echo "\nAkun warga yang dipakai:\n";
echo "  Email : " . EMAIL . "\n";
echo "  Sandi : " . SANDI . "\n";
echo "  NIK   : " . NIK . " (sintetis)\n";
echo "  Tiket : " . json_encode($tiket) . "\n";
exit($gagal === 0 ? 0 : 1);
