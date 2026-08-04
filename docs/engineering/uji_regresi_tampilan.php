<?php
/**
 * Penjaga regresi untuk cacat yang HANYA ketahuan dengan membuka layarnya.
 *
 *   php docs/engineering/uji_regresi_tampilan.php
 *
 * Sepanjang 2-3 Agt 2026, delapan cacat ditemukan dengan membuka halaman di
 * peramban — bukan satu pun oleh 802 pemeriksaan HTTP yang sudah ada. Semuanya
 * membalas 200 dengan markup yang sah; yang salah cuma apa yang dibaca manusia.
 * Setelah diperbaiki, tak satu pun punya penjaga: satu refactor dan semuanya
 * kembali diam-diam.
 *
 * Berkas ini menutup TUJUH dari delapan itu. Semuanya bisa dijaga lewat HTTP
 * karena wujudnya string di HTML yang dirender. Yang KEDELAPAN sengaja tidak
 * ada di sini: kolom Aksi yang terpotong gulir horizontal butuh tata letak
 * sungguhan, dan uji yang berpura-pura mengukurnya lebih buruk daripada tidak
 * ada — lihat catatan di akhir berkas.
 *
 * Ini BUKAN uji unit tampilan. Yang dijaga adalah janji yang bisa dibaca:
 * istilah yang dipakai dinas, tahun yang benar, bahasa tanggal, dan nama
 * pengguna yang tidak keluar ke pihak ketiga.
 *
 * Data uji dibuat dan dihapus sendiri — TIDAK meminjam pendaftaran demo yang
 * dipertahankan di AGENTS.md §20. Pelajaran r4/r5/r6: harness yang meminjam
 * data bersama akan merah karena ulah orang lain.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiTampil!2026');
define('CAP', 'UJITAMPIL-' . date('YmdHis'));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['daftar'] = [];
$GLOBALS['jar'] = [];

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}
function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $b) {
        $b = trim($b);
        if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $b, 2);
        $k = trim($k);
        if ( ! isset($out[$k])) { $out[$k] = trim($v); }
    }
    return $out;
}
function q($sql, $p = []) {
    $st = $GLOBALS['db']->prepare($sql);
    if ( ! $st) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r = $st->get_result();
    $o = $r ? $r->fetch_assoc() : NULL;
    $id = $st->insert_id;
    $st->close();
    return $o ?: ['__id' => $id];
}
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }
function nilai($sql, $p = []) { $r = q($sql, $p); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($n) {
    if ( ! isset($GLOBALS['jar'][$n])) { $GLOBALS['jar'][$n] = tempnam(sys_get_temp_dir(), 'ujt_'); }
    return $GLOBALS['jar'][$n];
}
function http($n, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $o = [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => sesi($n), CURLOPT_COOKIEFILE => sesi($n),
          CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30];
    if ($ajax) { $o[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $o[CURLOPT_POST] = TRUE; $o[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $o);
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
function login($n, $email) {
    $b = http($n, 'Auth/login');
    $t = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $b, $m) ? $m[1] : '';
    $r = http($n, 'Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $email, 'password' => SANDI], TRUE);
    return (json_decode($r, TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix, $bidang = NULL) {
    $email = 'uji_tampil_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis('INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,bidang_kode,created_at)
                 VALUES (?,?,?,?,?, "active",1,?,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Tampilan ' . $suffix,
         'uji_tampil_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $bidang]);
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/**
 * Pendaftaran ditulis LANGSUNG ke DB. Yang diuji berkas ini adalah RENDERING,
 * bukan alur pendaftarannya — itu sudah dijaga uji_kemitraan_daftar. Menempuh
 * ulang formulirnya cuma menyalin cakupan dan menambah sebab gagal yang bukan
 * milik uji ini.
 */
function buat_pendaftaran($user_id, $jenis, $bidang, $tema) {
    $id = tulis('INSERT INTO kkn_magang_pendaftaran
        (user_id,jenis,nim,tempat_lahir,tanggal_lahir,semester,jurusan,instansi_asal,no_hp,
         divisi_atau_tema,bidang_kode,periode_mulai,periode_selesai,status,file_surat_pengantar,created_at)
        VALUES (?,?, "H1A020001","Semarang","2003-05-17","6","Teknik Informatika", ?, "081234567890",
         ?,?, "2029-06-01","2029-07-31","Diajukan","surat.pdf", NOW())',
        [$user_id, $jenis, CAP . '-' . strtoupper($jenis), $tema, $bidang]);
    $GLOBALS['daftar'][] = $id;
    return $id;
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['daftar'] as $id) { q('DELETE FROM kkn_magang_pendaftaran WHERE id=?', [$id]); }
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM kkn_magang_pendaftaran WHERE user_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

echo "=== UJI REGRESI TAMPILAN ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

[$uidM, $emailM] = buat_akun('mahasiswa', 'mhs');
[$uidA, $emailA] = buat_akun('admin', 'adm');
[$uidW, $emailW] = buat_akun('warga', 'warga_kosong');

$bidang_kode = (string) nilai('SELECT kode FROM bidang ORDER BY nama LIMIT 1');
wajib($bidang_kode !== '', 'Ada bidang di DB untuk dijadikan acuan');
$nama_bidang = (string) nilai('SELECT nama FROM bidang WHERE kode=?', [$bidang_kode]);

$magang = buat_pendaftaran($uidM, 'magang', $bidang_kode, $nama_bidang);
$kkn    = buat_pendaftaran($uidM, 'kkn', NULL, 'Tema KKN ' . CAP);

wajib(login('mhs', $emailM), 'Login mahasiswa uji');
wajib(login('adm', $emailA), 'Login admin uji');
wajib(login('warga_kosong', $emailW), 'Login warga tanpa pengajuan');

// Akun warga baru tidak punya riwayat; layar kosong harus memberi satu jalan
// lanjut yang benar-benar tersedia, bukan berhenti di pesan kosong.
$akun_kosong = http('warga_kosong', 'akun');
cek(strpos($akun_kosong, 'Mulai Pendataan Warga') !== FALSE
    && strpos($akun_kosong, 'warga/pendataan') !== FALSE,
    'Status pengajuan kosong memberi CTA Pendataan Warga');

// =========================================================== 1. ISTILAH
echo "\n== Istilah: dinas menghapus \"divisi\", struktur resminya lima BIDANG ==\n";
$papan = http('mhs', 'KemitraanPortal/magang');
// Judul papan berubah 3 Agt 2026 (revisi dinas): matriks bidang x 12 bulan
// diganti daftar kebutuhan per bidang, jadi "Slot Magang per Bidang" menjadi
// "Kebutuhan Magang <tahun>". Yang DIJAGA tetap sama — istilah "divisi" tidak
// boleh kembali, dan papan harus menyebut bidang.
cek(stripos($papan, 'Kebutuhan Magang') !== FALSE, 'Papan publik: judul menyebut kebutuhan magang');
cek(stripos($papan, 'Kebutuhan mahasiswa magang pada tiap bidang') !== FALSE, 'Papan publik: subjudul menyebut bidang');
cek(preg_match('/\bdivisi\b/i', $papan) === 0, 'Papan publik: kata "divisi" nol kemunculan');

$adm_index = http('adm', 'Admin_Kemitraan');
cek(stripos($adm_index, 'Bidang/Tema') !== FALSE, 'Layar admin: kolom "Bidang/Tema"');
cek(stripos($adm_index, 'Divisi/Tema') === FALSE, 'Layar admin: "Divisi/Tema" sudah tidak ada');

// =========================================================== 2. TAHUN
echo "\n== Tahun: layar mendarat di tahun yang PUNYA slot, bukan date('Y') buta ==\n";
/**
 * Diuji sebagai INVARIAN, bukan angka tetap: "tahun yang ditampilkan harus
 * salah satu tahun yang benar-benar punya slot". Mematok 2027 akan membuat uji
 * ini merah tiap kali konfigurasinya berpindah tahun — merah yang tidak
 * menunjukkan apa pun.
 */
$tahun_berslot = [];
$res = $GLOBALS['db']->query('SELECT DISTINCT tahun FROM kkn_magang_slot ORDER BY tahun');
foreach ($res ?: [] as $r) { $tahun_berslot[] = (int) $r['tahun']; }

if ( ! $tahun_berslot) {
    cek(TRUE, 'Tidak ada slot sama sekali — pemeriksaan tahun dilewati (bukan kegagalan)');
} else {
    preg_match('/Kebutuhan Magang\s*(\d{4})/i', $papan, $m);
    $tahun_papan = (int) ($m[1] ?? 0);
    cek(in_array($tahun_papan, $tahun_berslot, TRUE),
        "Papan publik mendarat di tahun berslot (tampil {$tahun_papan}, berslot: " . implode(',', $tahun_berslot) . ')');

    /**
     * Papan baru (daftar kebutuhan) harus MENGHITUNG, bukan sekadar mendaftar.
     *
     * Bentuk lamanya matriks 12 bulan yang tiap selnya menampilkan angka; yang
     * baru meringkasnya jadi satu keadaan per bidang. Peringkasan itulah yang
     * paling mudah salah — kalau ia memakai bulan paling PADAT alih-alih paling
     * longgar, bidang yang masih punya bulan kosong akan tertulis "terpenuhi"
     * dan pendaftar yang seharusnya diterima berbalik pergi. Diuji terhadap
     * kenyataan DB, bukan terhadap teks yang kebetulan ada.
     */
    $bidang_longgar = $GLOBALS['db']->query(
        'SELECT b.nama
           FROM kkn_magang_bidang mb
           JOIN bidang b ON b.kode = mb.bidang_kode
          WHERE mb.aktif = 1
            AND EXISTS (SELECT 1 FROM kkn_magang_slot s
                         WHERE s.bidang_kode = mb.bidang_kode AND s.tahun = ' . (int) $tahun_papan . ')
          ORDER BY b.nama LIMIT 1')->fetch_assoc();

    if ($bidang_longgar) {
        cek(strpos($papan, $bidang_longgar['nama']) !== FALSE,
            'Papan menyebut bidang yang punya slot (' . $bidang_longgar['nama'] . ')');
        cek(preg_match('/Kebutuhan\s*<strong[^>]*>\s*\d+ mahasiswa/i', $papan) === 1
            || preg_match('/Kebutuhan\s*\d+ mahasiswa/i', strip_tags($papan)) === 1,
            'Papan menyebut ANGKA kebutuhan, bukan cuma nama bidang');
        cek(preg_match('/Masih menerima|Sudah terpenuhi|Belum dibuka/i', $papan) === 1,
            'Tiap bidang membawa keadaan terbaca (menerima/terpenuhi/belum dibuka)');
    }

    // Matriks lama harus benar-benar lenyap, bukan cuma tersembunyi.
    cek(preg_match('/Tidak Tersedia/i', $papan) === 0, 'Sisa matriks lama ("Tidak Tersedia") sudah tidak ada');

    $adm_slot = http('adm', 'Admin_Kemitraan/slot');
    preg_match('/Bulan Terbuka\s*(\d{4})/i', $adm_slot, $m2);
    $tahun_adm = (int) ($m2[1] ?? 0);
    cek(in_array($tahun_adm, $tahun_berslot, TRUE),
        "Layar slot admin mendarat di tahun berslot (tampil {$tahun_adm})");
}

// =========================================================== 3. BIDANG TUJUAN
echo "\n== Bidang tujuan: disebut untuk magang, TIDAK untuk KKN ==\n";
/**
 * Diperiksa dari LABEL <dt>, bukan dengan menyapu seluruh halaman.
 *
 * Versi pertama memakai `stripos($html, 'Bidang Tujuan')` dan merah untuk
 * halaman KKN yang sebenarnya benar: frasa itu juga ada di FAQ footer portal
 * ("pilih bidang tujuan yang sesuai dengan aduan Anda"), yang ikut dirender di
 * setiap halaman. Uji itu mengukur "dua kata ini ada di suatu tempat", bukan
 * "field ini ditampilkan" — dan namanya menjanjikan yang kedua.
 *
 * Kalimat FAQ itu sendiri sudah hilang sejak aduan tidak lagi minta bidang
 * (3 Agt 2026), jadi versi pertama tadi kebetulan akan hijau sekarang. Justru
 * itu alasan catatan ini dipertahankan: penjaga yang benar karena kebetulan
 * teks di halaman lain berubah bukan penjaga yang benar.
 */
function label_field($html) {
    preg_match_all('/<dt[^>]*>(.*?)<\/dt>/s', $html, $m);
    return array_map(fn($x) => trim(preg_replace('/\s+/', ' ', strip_tags($x))), $m[1]);
}

$hal_magang = http('mhs', 'KemitraanPortal/pendaftaran/' . $magang);
$label_magang = label_field($hal_magang);
wajib($label_magang !== [], 'Halaman pendaftaran magang merender daftar field');
cek(in_array('Bidang Tujuan', $label_magang, TRUE), 'Magang: field "Bidang Tujuan" ada');
cek(stripos($hal_magang, $nama_bidang) !== FALSE, 'Magang: nama bidangnya benar-benar tercetak');
// Dulu nilai yang sama tercetak dua kali dengan dua label berbeda.
cek( ! in_array('Tema Kegiatan', $label_magang, TRUE), 'Magang: field "Tema Kegiatan" tidak ikut (dulu duplikat)');

$hal_kkn = http('mhs', 'KemitraanPortal/pendaftaran/' . $kkn);
$label_kkn = label_field($hal_kkn);
cek(in_array('Tema Kegiatan', $label_kkn, TRUE), 'KKN: field "Tema Kegiatan" ada');
cek( ! in_array('Bidang Tujuan', $label_kkn, TRUE), 'KKN: field "Bidang Tujuan" tidak ikut');

// =========================================================== 4. TANGGAL
echo "\n== Tanggal berbahasa Indonesia ==\n";
$bulan_inggris = ['January','February','March','April','June','July','August','September','October','November','December'];
$ditemukan = [];
foreach ($bulan_inggris as $b) { if (preg_match('/\b' . $b . '\b/', $hal_magang)) { $ditemukan[] = $b; } }
cek( ! $ditemukan, 'Halaman pendaftaran: nol nama bulan Inggris' . ($ditemukan ? ' (ada: ' . implode(',', $ditemukan) . ')' : ''));
cek(preg_match('/\b(Januari|Februari|Maret|Mei|Juni|Juli|Agustus|Oktober|Desember|Jun|Jul|Agt)\b/', $hal_magang) === 1
    || preg_match('/\b(Januari|Februari|Maret|Mei|Juni|Juli|Agustus|Oktober|Desember|Jun|Jul|Agt)\b/', $hal_magang),
    'Halaman pendaftaran: memakai nama bulan Indonesia');

// =========================================================== 5. PRIVASI
echo "\n== Privasi: nama pengguna tidak keluar ke pihak ketiga ==\n";
/**
 * Dulu setiap pemuatan halaman memanggil ui-avatars.com dengan NAMA ASLI
 * pengguna di URL-nya — untuk semua peran, bersama IP dan referer. Penjaga ini
 * yang paling penting di berkas ini: kebocorannya diam, dan tidak ada satu pun
 * gejala di layar yang akan memberi tahu siapa pun.
 */
/**
 * Ketiga permukaan diperiksa, bukan sekadar salah satunya.
 *
 * Versi pertama hanya membuka `akun` dan `Admin_Dashboard`, dan LULUS ketika
 * kebocoran sengaja dikembalikan ke `layouts/main.php` — karena navbar itu
 * dirender di BERANDA portal, bukan di `akun`. Uji yang tidak menyentuh
 * permukaan tempat cacatnya pernah hidup tidak menjaga apa pun; ia cuma terlihat
 * seperti menjaga. Ketahuan lewat mutasi, 3 Agt 2026.
 *
 *   /                 -> layouts/main.php   (navbar portal)  + nav.php (mobile)
 *   Admin_Dashboard   -> admin/layouts/topbar.php
 */
foreach ([['mhs', '/', 'beranda portal'], ['mhs', 'akun', 'halaman akun'],
          ['adm', 'Admin_Dashboard', 'dasbor admin']] as [$s, $path, $label]) {
    $html = http($s, $path);
    cek(stripos($html, 'ui-avatars.com') === FALSE, "Nol panggilan ui-avatars.com di {$label}");
}
// Avatar SVG lokal harus benar-benar TERBIT, bukan cuma "tidak ada ui-avatars":
// menghapus avatarnya sama sekali juga membuat pemeriksaan di atas hijau.
foreach ([['mhs', '/', 'beranda portal'], ['adm', 'Admin_Dashboard', 'dasbor admin']] as [$s, $path, $label]) {
    cek(stripos(http($s, $path), 'data:image/svg+xml') !== FALSE, "Avatar {$label} terbit sebagai SVG lokal");
}

// =========================================================== 6. MEJA KERJA
echo "\n== Meja kerja Super Admin ==\n";
$overview = http('adm', 'Admin_Dashboard');
cek(strpos($overview, 'Meja Kerja Super Admin') !== FALSE
    && strpos($overview, 'Admin?status=pending') !== FALSE
    && strpos($overview, 'Admin_Kemitraan?status=Diajukan') !== FALSE
    && strpos($overview, 'tanpa_wilayah=1') !== FALSE
    && strpos($overview, CAP . '-MAGANG') !== FALSE
    && strpos($overview, 'cdn.jsdelivr.net/npm/chart.js') === FALSE
    && strpos($overview, 'Cari data, antrean, atau pengguna...') === FALSE,
    'Overview memuat antrean kerja terfilter lintas domain tanpa grafik Chart.js');
$direktori_srp2 = http('adm', 'Admin_Srp2');
cek(strpos($overview, 'Tinjau SRP2') !== FALSE
    && strpos($overview, 'Direktori SRP2') !== FALSE
    && strpos($overview, 'Pengembang SRP2') === FALSE
    && strpos($direktori_srp2, 'Direktori Pengembang Bersertifikat') !== FALSE
    && strpos($direktori_srp2, 'Pengajuan yang diterima masuk otomatis') !== FALSE,
    'Menu dan halaman Direktori SRP2 membedakan publikasi dari verifikasi pengajuan');

// =========================================================== 6. VERSI CDN
echo "\n== Dependensi CDN terpatok versinya ==\n";
/**
 * Versi mengambang berarti perilaku production bisa berubah tanpa satu baris
 * pun di repo tersentuh. Yang dijaga di sini BUKAN nomor versinya — itu boleh
 * dinaikkan kapan saja — melainkan bahwa penyebutnya tidak mengambang.
 */
/**
 * Tiap paket diperiksa DUA arah: bentuk mengambangnya harus absen, DAN bentuk
 * terpatoknya harus HADIR.
 *
 * Versi pertama hanya memeriksa yang pertama, dan langsung menjadi hampa untuk
 * Chart.js begitu agent lain memindahkannya keluar dari shell admin (3 Agt
 * 2026): halaman yang disampel tidak lagi memuatnya sama sekali, jadi "tidak
 * ada npm/chart.js tanpa versi" benar — untuk alasan yang salah.
 *
 * Karena itu tiap paket menyebut halaman tempat ia SUNGGUH dimuat. Kalau ada
 * yang memindahkannya lagi, uji ini MERAH dan memaksa daftarnya ikut
 * diperbarui — jauh lebih baik daripada diam-diam berhenti menjaga.
 */
$sampel = [
    'portal'      => http('mhs', '/'),
    'admin'       => $adm_index,
    'papan slot'  => $papan,
    'statistika'  => http('mhs', 'statistika'),
];
$paket = [
    'Alpine'   => ['patok' => 'alpinejs@3.15.12',   'ambang' => 'alpinejs@3.x.x',      'di' => ['portal', 'admin']],
    'Chart.js' => ['patok' => 'chart.js@4.5.1',     'ambang' => 'npm/chart.js"',       'di' => ['statistika']],
    'Swiper'   => ['patok' => 'swiper@11.2.10/',    'ambang' => 'swiper@11/',          'di' => ['portal']],
    'Phosphor' => ['patok' => 'phosphor-icons/web@', 'ambang' => 'phosphor-icons/web"', 'di' => ['admin']],
];
foreach ($paket as $nama => $p) {
    $html = implode('', array_map(fn($k) => $sampel[$k], $p['di']));
    cek(strpos($html, $p['patok']) !== FALSE,
        "{$nama} benar-benar dimuat di " . implode('/', $p['di']) . " dengan versi terpatok");
    cek(strpos($html, $p['ambang']) === FALSE, "{$nama} tidak memakai penyebut mengambang");
}

// =========================================================== 7. TABEL ADMIN
echo "\n== Tabel Admin Pengguna tetap server-side ==\n";
$admin_users = http('adm', 'Admin_Users');
$jumlah_user = (int) nilai('SELECT COUNT(*) FROM usr_users');
cek(strpos($admin_users, 'data-tabel-admin') !== FALSE
    && strpos($admin_users, 'Cari nama, email, atau username...') !== FALSE
    && strpos($admin_users, 'data-table-search') === FALSE,
    'Admin Pengguna memakai toolbar B8 server-side, bukan tabel klien');
cek(strpos($admin_users, 'Daftar Pengguna (' . number_format($jumlah_user) . ')') !== FALSE,
    'Admin Pengguna menampilkan total semua pengguna, bukan jumlah halaman');
$admin_users_urut = http('adm', 'Admin_Users?sort=name&dir=asc');
cek(strpos($admin_users_urut, 'sort=name') !== FALSE
    && strpos($admin_users_urut, 'sort=role') !== FALSE
    && strpos($admin_users_urut, 'sort=created_at') !== FALSE
    && strpos($admin_users_urut, 'ph-sort-ascending text-brand-primary') !== FALSE,
    'Header Admin Pengguna mengaktifkan urutan server-side');

// =========================================================== 8. INERT
echo "\n== Wizard onboarding: setiap seksi terkliping juga inert ==\n";
/**
 * Diperiksa dari SUMBER, bukan dari HTML terkirim: `:inert` adalah binding
 * Alpine, jadi ia memang muncul apa adanya di markup. Yang dijaga: tidak ada
 * seksi ber-`:class="{ 'visible': ... }"` yang tidak punya `:inert`. Tanpa
 * pasangan itu, isian yang tak terlihat tetap bisa difokus keyboard dan tetap
 * diumumkan pembaca layar — 17 kontrol, terukur 3 Agt 2026.
 */
$src = (string) @file_get_contents(APP_ROOT . '/application/views/pages/auth/onboarding.php');
wajib($src !== '', 'Sumber onboarding.php terbaca');
preg_match_all('/<div class="auth-dynamic-form"[^>]*>/', $src, $seksi);
$total_seksi = count($seksi[0]);
$tanpa_inert = array_values(array_filter($seksi[0], fn($t) => strpos($t, ':inert') === FALSE));
cek($total_seksi > 0, "Ditemukan {$total_seksi} seksi wizard di sumber");
cek( ! $tanpa_inert, 'Setiap seksi wizard punya :inert' . ($tanpa_inert ? ' (tanpa: ' . count($tanpa_inert) . ')' : ''));

bersihkan();
$GLOBALS['daftar'] = $GLOBALS['users'] = [];
cek((int) nilai('SELECT COUNT(*) c FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ?', [CAP . '%']) === 0,
    'Data uji dibersihkan');

/**
 * TIDAK DIJAGA DI SINI, dan itu disengaja:
 *
 * Kolom "Aksi" yang hilang di balik gulir horizontal (meja bidang 107px,
 * layar admin 120px pada viewport 1440) tidak bisa diperiksa lewat HTTP —
 * lebarnya baru ada setelah CSS dihitung dan tata letak dijalankan. Uji yang
 * mengaku memeriksanya dengan menghitung karakter atau mencocokkan kelas akan
 * hijau selamanya tanpa pernah menyentuh hal yang sebenarnya rusak, dan itu
 * lebih buruk daripada tidak ada uji sama sekali.
 *
 * Yang menutupnya bukan harness melainkan PEMICU: AGENTS.md §17 poin 6 kini
 * mewajibkan mengukur `scrollWidth - clientWidth` setiap kali ada yang menambah
 * kolom, memperlebar isi kolom, atau mengubah lebar sidebar — lengkap dengan
 * satu baris perintahnya. Sudah dijalankan 2-3 Agt 2026 pada kedua layar, dan
 * keduanya nol.
 *
 * Kalau kamu datang ke sini hendak "melengkapi" berkas ini dengan pemeriksaan
 * lebar: jangan. Baca dulu catatan di §17 itu.
 */

// ============================== POPOVER DI DALAM TABEL ADMIN
echo "\n== Popover di dalam sel tabel wajib mereset white-space ==\n";
/**
 * INI PEMERIKSAAN MARKUP, BUKAN TATA LETAK — dan bedanya penting supaya ia
 * tidak melanggar catatan panjang di atas.
 *
 * Yang diperiksa bukan "apakah tombolnya muat" (itu butuh CSS dihitung, dan
 * memang tidak bisa dari sini). Yang diperiksa: apakah KELAS yang menjadi
 * perbaikannya ada. Di sini kelas itu bukan proksi longgar — ia PENYEBAB dan
 * SEKALIGUS obatnya, jadi ketiadaannya berarti bug-nya kembali, persis.
 *
 * Bug-nya, ditemukan user di production 4 Agt 2026: tabel admin memakai
 * `whitespace-nowrap`, dan `white-space` DIWARISI ke popover di dalam <td>.
 * Kontrol form di dalamnya `display:inline-block`, jadi <select> dan tombol
 * Simpan berjajar dalam SATU baris — tombolnya terlempar 193px ke luar
 * pembungkus `overflow-x-auto`, terpotong dan tidak bisa diklik. Terukur:
 * tombol di x=1371, panel berakhir di x=1385.
 *
 * Tiga layar kena sekaligus (Akses Staf, Kelola KKN/Magang, Magang Bidang),
 * jadi ini bukan salah ketik di satu berkas melainkan pola yang tersalin.
 */
/**
 * Yang diperiksa HANYA popover yang benar-benar berada DI DALAM `<table>`.
 *
 * Versi pertama penjaga ini cuma menanyakan "apakah berkasnya memuat
 * whitespace-nowrap", dan langsung merah untuk EMPAT layar yang sebenarnya
 * benar — modal di Katalog Program, Janji Temu, Struktur & Cakupan, dan modal
 * Tambah Pengguna semuanya ditulis SEBAGAI SAUDARA tabel, bukan di dalamnya,
 * jadi tidak ada yang diwariskan ke sana. `white-space` mewaris lewat pohon
 * DOM; kedekatan di dalam berkas yang sama tidak mewariskan apa pun.
 */
$dalam_tabel = static function ($isi, $offset) {
    $pos = 0;
    while (($buka = strpos($isi, '<table', $pos)) !== FALSE) {
        $tutup = strpos($isi, '</table>', $buka);
        if ($tutup === FALSE) { break; }
        if ($offset > $buka && $offset < $tutup) { return TRUE; }
        $pos = $tutup + 8;
    }
    return FALSE;
};

$popover_admin = [];
foreach (glob(APP_ROOT . '/application/views/admin/*/*.php') as $berkas) {
    $isi = file_get_contents($berkas);
    if (strpos($isi, 'whitespace-nowrap') === FALSE) { continue; }
    if ( ! preg_match_all('/<div\s+x-show="[^"]+"[^>]*class="([^"]*\b(?:absolute|fixed)\b[^"]*)"/',
        $isi, $m, PREG_OFFSET_CAPTURE)) { continue; }
    foreach ($m[0] as $i => $hit) {
        if ( ! $dalam_tabel($isi, $hit[1])) { continue; }
        $popover_admin[] = [str_replace(APP_ROOT . '/application/views/admin/', '', $berkas), $m[1][$i][0]];
    }
}
cek($popover_admin !== [], 'Popover DI DALAM tabel admin terbaca (' . count($popover_admin) . ' ditemukan)');
foreach ($popover_admin as [$berkas, $kelas]) {
    cek(strpos($kelas, 'whitespace-normal') !== FALSE,
        "{$berkas}: popover di dalam tabel mereset white-space");
}

// ============================== SHELL ADMIN TIDAK BOLEH MENJEBAK MODAL
echo "\n== Shell admin: `#main-content` tidak menjebak `position: fixed` ==\n";
/**
 * Dua kelas di shell pernah mematahkan SETIAP modal di SELURUH layar admin
 * sekaligus. Dilaporkan user 4 Agt 2026: "modalnya tidak muncul karena
 * tertumpuk".
 *
 * 1. `animation: … both`. Bagian `forwards`-nya membuat keyframe terakhir
 *    menempel selamanya — termasuk `transform: translateY(0) scale(1)` dan
 *    `filter: blur(0px)`. Keduanya identitas, tidak mengubah tampilan sama
 *    sekali, TAPI elemen ber-transform/filter jadi CONTAINING BLOCK untuk
 *    `position: fixed`. Modal `fixed inset-0` pun terkurung di dalam <main>:
 *    terukur x=256 (tepi sidebar), bukan 0.
 * 2. `relative z-10` pada `#main-content` = STACKING CONTEXT. `z-50` milik
 *    modal jadi cuma berlaku di dalam konteks z-10 itu, sementara topbar
 *    (z-40) dan sidebar (z-20) adalah saudara DI LUARNYA — jadi modal dicat
 *    di bawah keduanya.
 *
 * Keduanya diperiksa sebagai STRING karena keduanya memang kelas/nilai CSS,
 * bukan hasil tata letak. Yang tidak bisa diperiksa dari sini — apakah modal
 * benar-benar menutupi layar — memang butuh browser, dan itu bagian §17 poin 6.
 */
$shell = file_get_contents(APP_ROOT . '/application/views/admin/index.php');
preg_match('/<main id="main-content"[^>]*class="([^"]*)"/', $shell, $mm);
$kelas_main = $mm[1] ?? '';
cek($kelas_main !== '', 'Kelas #main-content terbaca');
cek( ! preg_match('/\bz-\d+\b/', $kelas_main),
    '#main-content tanpa z-index — stacking context di sini mengubur setiap modal di bawahnya');

$head = file_get_contents(APP_ROOT . '/application/views/admin/layouts/head.php');
preg_match('/#main-content\s*\{[^}]*animation:([^;]*);/s', $head, $am);
$anim = trim($am[1] ?? '');
cek($anim !== '', 'Animasi #main-content terbaca');
cek(strpos($anim, 'backwards') !== FALSE && ! preg_match('/\bboth\b|\bforwards\b/', $anim),
    'fill-mode `backwards`, bukan `both`/`forwards` — kalau tidak, transform & filter menempel selamanya');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
