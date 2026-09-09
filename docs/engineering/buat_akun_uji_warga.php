<?php
/**
 * Membuat SATU akun warga siap-pakai untuk menelusuri alur bisnis dengan
 * tangan, dari daftar sampai layar diagnosa.
 *
 *   php docs/engineering/buat_akun_uji_warga.php
 *
 * Env opsional:
 *   UJI_BASE_URL   bawaan http://localhost/klinik_new
 *   UJI_EMAIL      bawaan warga.uji+<stempel>@akunuji.test
 *   UJI_PASSWORD   bawaan UjiWarga123!
 *
 * KENAPA MEMBUAT AKUN BARU, BUKAN ME-RESET YANG LAMA. AGENTS.md §20 melarang
 * menghapus data demo/uji di DB dev tanpa bertanya lebih dulu (keputusan user
 * 2 Agt 2026, berlaku sampai merge ke `main`), dan larangan itu ada karena
 * sudah dua kali ada agent yang menyapunya karena mengira itu sampah. Akun
 * yang baru dibuat sudah bersih menurut definisinya sendiri: nol draft, nol
 * antrean, nol penilaian. Jadi "reset" di sini artinya JALANKAN ULANG skrip
 * ini - bukan menghapus apa pun.
 *
 * SEMUA LEWAT ENDPOINT SUNGGUHAN, NOL `INSERT` LANGSUNG. Itu disengaja: kalau
 * skrip ini hijau, ia sekaligus membuktikan langkah daftar dan onboarding
 * memang jalan. Menyuntik baris ke `usr_users` akan menghasilkan akun yang
 * bisa dipakai TAPI tidak membuktikan apa pun tentang alurnya - dan alur itu
 * justru yang ditanyakan. Pola yang sama dipakai pendaftaran kemitraan #656
 * (§20c): dibuat lewat endpoint, bukan INSERT.
 *
 * BUKAN `@example.test`. Domain itu sudah punya arti khusus di repo ini:
 * `jalankan_semua.php` menyensus `%@example.test` sebelum dan sesudah tiap
 * jalan lalu menghitung sisanya sebagai MERAH, karena itu penanda kebocoran
 * harness. Akun ini SENGAJA dibiarkan hidup, jadi memakai domain itu akan
 * membuat sensus tersebut merah karena hal yang bukan kebocoran.
 */

define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('STEMPEL', date('ymdHis'));
define('EMAIL', getenv('UJI_EMAIL') ?: 'warga.uji+' . STEMPEL . '@akunuji.test');
define('SANDI', getenv('UJI_PASSWORD') ?: 'UjiWarga123!');

/* NIK sintetis. Awalan 3374 (Kota Semarang) dipertahankan supaya logika yang
   menurunkan wilayah dari NIK tetap berjalan wajar, sementara badannya diisi
   stempel waktu - pola yang tidak mungkin menjadi tanggal lahir yang sah,
   jadi peluang bertabrakan dengan NIK warga sungguhan praktis nol. */
define('NIK', '3374' . STEMPEL);

$jar = sys_get_temp_dir() . '/kuki_akun_uji_' . getmypid() . '.txt';
register_shutdown_function(function () use ($jar) { @unlink($jar); });

$total = 0; $gagal = 0;
function cek($benar, $label) {
    global $total, $gagal;
    $total++;
    echo ($benar ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $benar) { $gagal++; }
    return (bool) $benar;
}
function wajib($benar, $label) {
    if ( ! cek($benar, $label)) {
        fwrite(STDERR, "\nBerhenti: prasyarat gagal, akun TIDAK jadi dibuat.\n");
        exit(1);
    }
}

function minta($path, ?array $post = NULL) {
    global $jar;
    $ch = curl_init(BASE . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT        => 40,
    ];
    if ($post !== NULL) {
        $opt[CURLOPT_POST] = TRUE;
        $opt[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $kode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return ['kode' => $kode, 'body' => $body];
}

/* CSRF dibaca dari halamannya, bukan dari config: `csrf_regenerate` bisa
   dinyalakan kapan saja, dan skrip yang menyimpan token lama akan gagal
   dengan pesan yang membingungkan. */
function csrf($html) {
    if (preg_match('/name="csrf-token-name" content="([^"]+)"/', $html, $a)
        && preg_match('/name="csrf-token-hash" content="([^"]+)"/', $html, $b)) {
        return [$a[1] => $b[1]];
    }
    if (preg_match('/name="(csrf_[a-z_]+)"\s+value="([^"]+)"/', $html, $m)) {
        return [$m[1] => $m[2]];
    }
    return [];
}

echo "=== BUAT AKUN UJI WARGA ===\n";
echo "Target : " . BASE . "\n";
echo "Email  : " . EMAIL . "\n\n";

echo "1. Halaman daftar\n";
$r = minta('register');
wajib($r['kode'] === 200, 'Halaman daftar terbuka');
$t = csrf($r['body']);
wajib( ! empty($t), 'Token CSRF terbaca dari halaman');

echo "\n2. Kirim pendaftaran\n";
$r = minta('Auth/do_register', $t + [
    'email'            => EMAIL,
    'password'         => SANDI,
    'password_confirm' => SANDI,
    'tos_agree'        => '1',
]);
wajib($r['kode'] === 200, 'Endpoint pendaftaran membalas 200');
cek(stripos($r['body'], 'Terlalu banyak percobaan') === FALSE,
    'Tidak tertahan batas laju pendaftaran');

/* ⚠️ ASSERT INI YANG MEMBUAT SISANYA BERARTI. Tanpa dia skrip ini LULUS
   PALSU saat emailnya sudah terpakai: pendaftaran ditolak, lalu langkah 4
   masuk ke akun LAMA, dan seluruh sisa pemeriksaan hijau untuk akun yang
   bukan buatan skrip ini. Terbukti sekali dengan menjalankan ulang memakai
   UJI_EMAIL yang sama - 13 dari 13 hijau padahal nol akun baru lahir.
   Pesannya sengaja tidak menyebut "email sudah terdaftar" (Auth.php:304
   menghindari formulir ini jadi alat pengecek keanggotaan), jadi yang
   dicocokkan bagian yang netral itu. */
wajib(stripos($r['body'], 'Pendaftaran tidak dapat diproses') === FALSE,
    'Akun BARU benar-benar lahir, bukan menumpang akun yang sudah ada');

echo "\n3. Onboarding: peran, identitas, NIK\n";
$r = minta('onboarding');
wajib($r['kode'] === 200, 'Halaman onboarding terbuka (berarti sesi pendaftaran hidup)');
$t = csrf($r['body']) ?: $t;

$r = minta('Auth/save_onboarding', $t + [
    'role'            => 'warga',
    'username'        => 'wargauji' . STEMPEL,
    'nama_lengkap'    => 'Warga Uji Alur ' . STEMPEL,
    'nik_identitas'   => NIK,
    'alamat_domisili' => 'Jl. Uji Alur No. 1, Kota Semarang',
    'phone'           => '081200000000',
]);
wajib($r['kode'] === 200, 'Endpoint onboarding membalas 200');
cek(stripos($r['body'], 'Pilih peran yang valid') === FALSE, 'Peran warga diterima');
cek(stripos($r['body'], 'NIK harus terdiri') === FALSE, 'NIK diterima');
cek(stripos($r['body'], 'wajib harus diisi') === FALSE, 'Seluruh medan wajib terisi');

echo "\n4. Masuk sebagai akun baru\n";
$r = minta('auth/login');
$t = csrf($r['body']) ?: $t;
$r = minta('Auth/do_login', $t + ['email' => EMAIL, 'password' => SANDI]);
wajib($r['kode'] === 200, 'Endpoint login membalas 200');

echo "\n5. Layar pendataan/diagnosa terbuka sebagai warga\n";
$r = minta('warga/pendataan');
wajib($r['kode'] === 200, 'Halaman pendataan membalas 200');
cek(stripos($r['body'], 'Akses pendataan hanya untuk akun warga') === FALSE,
    'TIDAK ditolak gerbang peran warga');
/* Penanda yang dipakai adalah LABEL STEP PERTAMA dari
   Warga::STEP_LABELS, bukan tebakan kata seperti "wizard" - kalau
   labelnya kelak berubah, assert ini merah dan memaksa dibaca ulang,
   bukan diam-diam lulus karena kebetulan ada kata yang cocok. */
cek(strpos($r['body'], 'Masukkan NIK') !== FALSE,
    'Step pertama wizard (Masukkan NIK) benar-benar dirender');

echo "\n=== {$total} pemeriksaan, {$gagal} merah ===\n";
if ($gagal === 0) {
    echo "\nAkun siap dipakai:\n";
    echo "  Alamat  : " . BASE . "/auth/login\n";
    echo "  Email   : " . EMAIL . "\n";
    echo "  Sandi   : " . SANDI . "\n";
    echo "  NIK     : " . NIK . " (sintetis)\n";
    echo "\nButuh yang bersih lagi? Jalankan ulang skrip ini - ia membuat akun\n";
    echo "baru tiap kali, jadi tidak ada yang perlu dihapus.\n";
}
exit($gagal === 0 ? 0 : 1);
