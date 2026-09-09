<?php
/**
 * Penjaga integrasi API Sikaper (Disperakim Jateng).
 *
 *   php docs/engineering/uji_sikaper_api.php
 *
 * DUA LAPIS, dan lapis pertama yang paling penting.
 *
 * LAPIS 1 - STATIS, selalu jalan, tidak menyentuh jaringan sama sekali.
 * Menjaga dua utang §9/§18 supaya tidak diam-diam kembali: kredensial tidak
 * boleh ditulis di `config/sikaper.php` (di sana pernah hardcode dan SUDAH
 * MASUK RIWAYAT GIT), dan verifikasi TLS tidak boleh dimatikan lagi. Dua-
 * duanya pernah berdiri lama justru karena tidak ada yang memeriksanya.
 *
 * LAPIS 2 - KONTRAK HIDUP, menyentuh API dinas.
 * SENGAJA MELEWATI, BUKAN MERAH, kalau kredensial belum ada di `.env` atau
 * host-nya tidak bisa dihubungi. Uji yang merah karena server orang lain
 * sedang mati adalah uji yang tidak berguna - prinsip yang sama sudah dipakai
 * uji_cari_rumah_sikumbang.php dan uji_sikumbang_cadangan.php.
 *
 * 🔻 CATATAN ALAMAT, dan ini yang memakan waktu berhari-hari.
 * `config/sikaper.php` dulu menunjuk `sikaper.disperakim.jatengprov.go.id`.
 * Host itu milik dinas dan situs publiknya hidup, TAPI API-nya menolak
 * kredensial dengan `401 Unauthorized` - diuji ulang 9 Sep 2026, masih 401
 * dengan kredensial yang terbukti benar. Host yang melayani API adalah
 * `egov.phicos.co.id/jateng/sikaper_new/api/v2`. Jadi 401 yang lama BUKAN
 * soal kredensial, melainkan soal alamat - dan itu mustahil disimpulkan dari
 * pesannya, karena 401-nya identik dengan atau tanpa auth.
 */

define('BASEPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('CONFIG_PATH', dirname(__DIR__, 2) . '/application/config/sikaper.php');
define('LIB_PATH', dirname(__DIR__, 2) . '/application/libraries/Sikaper_api.php');

$total = 0; $gagal = 0; $lewat = 0;
function cek($b, $l) {
    global $total, $gagal;
    $total++;
    echo ($b ? '  OK    ' : '  GAGAL ') . $l . "\n";
    if ( ! $b) { $gagal++; }
    return (bool) $b;
}
function lewati($l) {
    global $lewat;
    $lewat++;
    echo "  LEWAT {$l}\n";
}

echo "=== PENJAGA INTEGRASI API SIKAPER ===\n\n";

// ---------------------------------------------------------------------------
echo "LAPIS 1. Statis - utang keamanan tidak boleh kembali\n";
// ---------------------------------------------------------------------------
$config_src = (string) file_get_contents(CONFIG_PATH);
$lib_src    = (string) file_get_contents(LIB_PATH);

/* Yang dicari BUKAN satu password tertentu, melainkan BENTUKNYA: nilai literal
   apa pun di kanan `sikaper_api_password`. Mencocokkan password yang sudah
   bocor akan lulus begitu seseorang menuliskan password BARU di sana - persis
   kesalahan yang sedang dijaga. */
cek(preg_match('/sikaper_api_password[^\n]*=\s*[\'"][^\'"]+[\'"]/', $config_src) !== 1,
    'config/sikaper.php TIDAK memuat password literal (harus lewat getenv)');
cek(preg_match('/sikaper_api_username[^\n]*=\s*[\'"][^\'"]+[\'"]/', $config_src) !== 1,
    'config/sikaper.php TIDAK memuat username literal');
cek(strpos($config_src, 'getenv(\'SIKAPER_PASSWORD\')') !== FALSE,
    'Password dibaca dari environment');

cek(preg_match('/CURLOPT_SSL_VERIFYPEER\s*,\s*(false|FALSE|0)\b/', $lib_src) !== 1,
    'Sikaper_api TIDAK mematikan verifikasi TLS');
cek(preg_match('/CURLOPT_SSL_VERIFYPEER\s*,\s*(TRUE|true|1)\b/', $lib_src) === 1,
    'Sikaper_api menyalakan verifikasi TLS secara eksplisit');
cek(strpos($lib_src, 'CURLOPT_CONNECTTIMEOUT') !== FALSE,
    'Ada batas waktu fase sambung, bukan cuma batas total');

/* Base URL bawaan harus menunjuk host yang BENAR-BENAR melayani API. Kalau
   suatu saat ada yang mengembalikannya ke sikaper.disperakim..., asersi ini
   merah sebelum orang itu menghabiskan sehari mengejar 401. */
cek(strpos($config_src, 'egov.phicos.co.id/jateng/sikaper_new/api/v2') !== FALSE,
    'Base URL bawaan menunjuk host yang melayani API (phicos), bukan yang 401');

// ---------------------------------------------------------------------------
echo "\nLAPIS 2. Kontrak hidup ke API dinas\n";
// ---------------------------------------------------------------------------
$env = [];
if (is_file(ENV_PATH)) {
    foreach (file(ENV_PATH, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        if ( ! array_key_exists(trim($k), $env)) { $env[trim($k)] = trim($v); }
    }
}

if (empty($env['SIKAPER_USERNAME']) || empty($env['SIKAPER_PASSWORD'])) {
    lewati('SIKAPER_USERNAME/PASSWORD belum ada di .env - lapis hidup dilewati, bukan merah.');
} else {
    require LIB_PATH;
    $kelas = new ReflectionClass('Sikaper_api');
    $api = $kelas->newInstanceWithoutConstructor();
    foreach ([
        'base_url' => rtrim($env['SIKAPER_BASE_URL'] ?? 'https://egov.phicos.co.id/jateng/sikaper_new/api/v2', '/') . '/',
        'username' => $env['SIKAPER_USERNAME'],
        'password' => $env['SIKAPER_PASSWORD'],
    ] as $nama => $nilai) {
        $prop = $kelas->getProperty($nama);
        $prop->setAccessible(TRUE);
        $prop->setValue($api, $nilai);
    }

    $info = $api->get_info_hari_habitat();

    if (empty($info['status'])) {
        lewati('API tidak bisa dihubungi / menolak (' . ($info['message'] ?? 'tanpa pesan')
            . '). Lapis hidup dilewati - bisa jadi jaringan, bukan kode kita.');
    } else {
        cek(($info['data']['status'] ?? NULL) === TRUE, 'hari_habitat/info menjawab status true');
        $lomba = $info['data']['data'][0] ?? [];
        cek( ! empty($lomba['id']), 'info memuat id lomba yang bisa dipakai endpoint lain');
        cek(isset($lomba['tema_lomba']), 'info memuat tema_lomba');

        $id_lomba = $lomba['id'] ?? '';
        if ($id_lomba !== '') {
            $jadwal = $api->get_jadwal_hari_habitat($id_lomba);
            cek( ! empty($jadwal['status']), 'hari_habitat/jadwal menjawab');

            $peserta = $api->get_detail_peserta_hari_habitat($id_lomba);
            cek( ! empty($peserta['status']), 'hari_habitat/detail_peserta menjawab');
            /* Penjaga privasi, bukan penjaga fungsi. Endpoint ini memuat nomor
               HP dan nama PIC; asersi ini ada supaya siapa pun yang kelak
               menampilkannya ke halaman publik melihat peringatannya di sini
               dulu, bukan sesudah data kontak orang tersaji. */
            $baris0 = $peserta['data']['peserta'][0] ?? [];
            cek(array_key_exists('no_hp', $baris0) || $baris0 === [],
                'detail_peserta memuat data kontak (no_hp) - JANGAN tampilkan mentah ke publik');
        }

        $kawasan = $api->get_data_kawasan('2024');
        cek( ! empty($kawasan['status']), 'data_kawasan/kawasan menjawab');
        $daftar = $kawasan['data']['data'] ?? [];
        cek(is_array($daftar) && count($daftar) > 0,
            'kawasan 2024 mengembalikan baris (' . (is_array($daftar) ? count($daftar) : 0) . ')');

        $id_kawasan = $daftar[0]['id'] ?? '';
        if ($id_kawasan !== '') {
            cek( ! empty($api->get_detail_kawasan($id_kawasan)['status']), 'data_kawasan/detail_kawasan menjawab');
            cek( ! empty($api->get_data_rtrw($id_kawasan)['status']), 'data_kawasan/data_rtrw menjawab');
        }
    }
}

// ---------------------------------------------------------------------------
echo "\nLAPIS 3. Halaman Kawasan Kumuh lewat HTTP\n";
// ---------------------------------------------------------------------------
$base = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/');
$ambil = function ($path) use ($base) {
    $ch = curl_init($base . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 90]);
    $body = (string) curl_exec($ch);
    $kode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return [$kode, $body];
};

list($kode, $html) = $ambil('kawasan_kumuh');
if ($kode === 0) {
    lewati('Server web tidak berjalan (HTTP 0) - lapis halaman dilewati.');
} else {
    /* Penjaga positif lebih dulu, supaya sebabnya terbaca kalau halamannya
       500 alih-alih hanya "teks tidak ketemu". */
    cek($kode === 200, "Halaman /kawasan_kumuh membalas 200 (dapat {$kode})");
    cek(strpos($html, 'Data Kawasan Kumuh Jawa Tengah') !== FALSE, 'Judul halaman dirender');
    $n = preg_match('/(\d+) kawasan ditampilkan/', $html, $m) === 1 ? (int) $m[1] : 0;
    cek($n > 0, 'Ada baris kawasan yang tampil (' . $n . ')');
    cek(strpos($html, 'belum bisa ditampilkan') === FALSE, 'Bukan halaman keadaan gagal');

    /* Data pribadi TIDAK boleh bocor ke halaman publik ini. Endpoint peserta
       Lomba Habitat memuat no_hp dan nama PIC; asersi ini yang berteriak
       kalau kelak ada yang menyambungkannya ke sini. */
    cek(stripos($html, 'no_hp') === FALSE && preg_match('/\b08\d{9,11}\b/', $html) !== 1,
        'Nol nomor HP / data kontak di halaman publik');

    if (preg_match('#kawasan_kumuh/detail/([A-Za-z0-9_-]+)#', $html, $m)) {
        list($kode_d, $html_d) = $ambil('kawasan_kumuh/detail/' . $m[1]);
        cek($kode_d === 200, "Halaman detail membalas 200 (dapat {$kode_d})");
        cek(strpos($html_d, 'Rincian RT/RW') !== FALSE, 'Detail merender bagian RT/RW');
    }

    /* URUT & HALAMAN - diperiksa NILAINYA, bukan cuma "ada tautannya".
       Kolom teks dibaca dari sel <td> pertama tiap baris, kolom angka dari sel
       skor akhir; keduanya harus benar-benar terurut. Mengecek keberadaan
       tombol saja akan lulus walau urutannya kacau. */
    $sel_teks = function ($html) {
        preg_match_all('/<td class="px-4 py-3 font-bold" style="color:var\(--portal-text\)">([^<]*)/', $html, $m);
        return $m[1];
    };
    $sel_angka = function ($html) {
        preg_match_all('/text-right font-bold" style="color:var\(--portal-text\)">(\d+)/', $html, $m);
        return array_map('intval', $m[1]);
    };
    $terurut = function (array $a, $naik, $teks) {
        for ($i = 1; $i < count($a); $i++) {
            $c = $teks ? strcasecmp($a[$i - 1], $a[$i]) : ($a[$i - 1] <=> $a[$i]);
            if ($naik ? $c > 0 : $c < 0) { return FALSE; }
        }
        return count($a) > 1;
    };

    list(, $h_asc)  = $ambil('kawasan_kumuh?urut=kawasan&arah=asc');
    list(, $h_desc) = $ambil('kawasan_kumuh?urut=kawasan&arah=desc');
    cek($terurut($sel_teks($h_asc), TRUE, TRUE),   'Urut kawasan menaik: sel teks benar-benar A-Z (tanpa peduli huruf besar-kecil)');
    cek($terurut($sel_teks($h_desc), FALSE, TRUE), 'Urut kawasan menurun: sel teks benar-benar Z-A');

    list(, $h_num) = $ambil('kawasan_kumuh?urut=skor_akhir&arah=desc');
    cek($terurut($sel_angka($h_num), FALSE, FALSE), 'Urut skor akhir menurun: dibandingkan sebagai ANGKA (50 sebelum 9)');

    list(, $h_hal) = $ambil('kawasan_kumuh?per=25&hal=2');
    cek(preg_match('/Menampilkan 26&ndash;50, halaman 2 dari (\d+)/', $h_hal) === 1,
        'Halaman 2 @25 menampilkan baris 26-50');
    cek(substr_count($h_hal, '<tr class="border-t"') === 25, 'Halaman 2 memuat tepat 25 baris');

    list(, $h_akhir) = $ambil('kawasan_kumuh?per=25&hal=99999');
    preg_match('/halaman (\d+) dari (\d+)/', $h_akhir, $mh);
    cek(isset($mh[1], $mh[2]) && $mh[1] === $mh[2] && (int) $mh[2] > 1,
        'Nomor halaman di luar batas dijepit ke halaman terakhir (' . ($mh[1] ?? '?') . ' dari ' . ($mh[2] ?? '?') . ')');

    list(, $h_per) = $ambil('kawasan_kumuh?per=7');
    cek(preg_match('/Menampilkan 1&ndash;25,/', $h_per) === 1, 'Nilai per-halaman di luar whitelist jatuh ke 25');

    /* Penyaring TIDAK boleh hilang saat kepala kolom diklik - itu cara paling
       sunyi sebuah tabel membuang pilihan pengguna. */
    list(, $h_kab) = $ambil('kawasan_kumuh?tahun=2025&kab=3301');
    cek(preg_match('/href="[^"]*kab=3301[^"]*urut=kawasan/', $h_kab) === 1,
        'Tautan kepala kolom mempertahankan penyaring kabupaten');

    list($kode_u, ) = $ambil('kawasan_kumuh?urut=' . rawurlencode('x;DROP') . '&arah=zz&hal=-3');
    cek($kode_u === 200, 'Parameter urut/arah/hal yang ngawur dijatuhkan ke bawaan, bukan galat');

    list($kode_x, ) = $ambil('kawasan_kumuh/detail/..%2f..%2fetc');
    cek($kode_x === 404, "ID berbentuk aneh ditolak 404 (dapat {$kode_x})");
}

echo "\n=== {$total} pemeriksaan, {$gagal} merah, {$lewat} dilewati ===\n";
exit($gagal === 0 ? 0 : 1);
