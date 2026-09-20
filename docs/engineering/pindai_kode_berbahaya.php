<?php
/**
 * PEMINDAI KODE BERBAHAYA (form keamanan poin 9.1 dan 9.2).
 *
 * Analisis statis untuk mendeteksi kode yang berpotensi berbahaya pada kode buatan
 * sendiri dan memverifikasi integritas kode pihak ketiga. Bukan pengganti
 * review manusia; ia menjaga agar kelas masalah tertentu TIDAK BISA masuk diam-diam.
 *
 *   php docs/engineering/pindai_kode_berbahaya.php [mode]
 *
 * mode:
 *   kode        (bawaan) pindai PHP dan JavaScript buatan sendiri.
 *   integritas  hash inti CodeIgniter, pdf.js, dan Tailwind lokal vs manifest (offline).
 *   upstream    unduh rilis RESMI, bandingkan dengan berkas lokal, dan cocokkan manifest (butuh internet).
 *   pustaka     composer.lock vs composer.json, vendor/ terpasang vs lock, dan audit kerentanan (butuh composer).
 *               Tambahkan --ketat di SERVER: vendor/ berbeda dari lock menjadi GAGAL (di mesin dev hanya peringatan).
 *   deploy      pohon berkas server vs git, dan tidak ada PHP di direktori aset (jalankan DI SERVER).
 *   semua       kode + integritas + pustaka.
 *   tulis-manifest  bangkitkan ulang docs/engineering/integritas_manifest.json (setelah diverifikasi ke upstream).
 *
 * Kode keluar 1 bila ada temuan. Daftar izin (allowlist) ada di bagian PINDAI_* di bawah:
 * menambah entri = keputusan keamanan yang harus lewat review.
 */

// ---------------------------------------------------------------- daftar izin
/** File yang boleh membuka koneksi KELUAR beserta tujuannya (deteksi "telepon pulang", ASVS 10.1.1/10.2.1). */
const PINDAI_JARINGAN_DIIZINKAN = [
    'controllers/Auth.php'                => 'reCAPTCHA (Google) dan login Google OAuth',
    'controllers/Chat.php'                => 'Gemini API (jalur dikarantina 404)',
    'controllers/Index.php'               => 'proxy foto Sikumbang (Tapera)',
    'helpers/sikumbang_helper.php'        => 'Sikumbang (Tapera)',
    'libraries/Sikaper_api.php'           => 'Sikaper (egov Jateng)',
    'libraries/Simperum_gateway.php'      => 'SIMPERUM (Disperakim Jateng)',
    'libraries/Ternak_api.php'            => 'API Ternak',
    'libraries/Web_push_service.php'      => 'layanan push peramban (Web Push)',
];
/** File yang boleh menerima unggahan (move_uploaded_file). */
const PINDAI_UNGGAH_DIIZINKAN = [
    'core/MY_Controller.php'                  => 'penyimpanan unggahan pribadi (di luar webroot)',
    // Ditinjau 20 Sep 2026: ekstensi berasal dari MIME hasil sniffing server (bukan nama berkas
    // pengguna), nama acak, batas 3 MB, getimagesize(), metadata dibersihkan; hanya .jpg/.png.
    'controllers/Admin_Katalog_Program.php'  => 'gambar katalog program (aset publik, hanya jpg/png)',
    // Ditinjau 20 Sep 2026: ekstensi dan MIME harus cocok dengan daftar putih (pdf/jpg/png),
    // nama acak 128 bit, batas 2 MB, satu berkas per dokumen.
    'controllers/Pengembang.php'             => 'dokumen pengajuan pengembang (pdf/jpg/png, penyimpanan pribadi)',
];
/** Host yang boleh dituju oleh fetch/XHR/WebSocket dari JavaScript. */
const PINDAI_JS_HOST_DIIZINKAN = [];
/** Berkas JS yang boleh membuat elemen <script> secara dinamis, dan alasannya. */
const PINDAI_JS_SCRIPT_DINAMIS_DIIZINKAN = [
    'assets/js/admin-progressive.js'      => 'menjalankan ulang <script> hasil navigasi progresif; menyalin semua atribut termasuk integrity',
    'views/layouts/footer.php'            => 'sama, untuk layout publik',
];

// ---------------------------------------------------------------- utilitas
function pindai_akar() { return str_replace(chr(92), '/', realpath(__DIR__ . '/../..')); }
function pindai_rel($path) { return ltrim(substr(str_replace(chr(92), '/', $path), strlen(pindai_akar())), '/'); }
function pindai_normal($isi) { return str_replace("\r\n", "\n", $isi); }
function pindai_daftar($dir, array $ext, array $lewati = []) {
    $hasil = [];
    if (!is_dir($dir)) { return $hasil; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || !in_array(strtolower($f->getExtension()), $ext, true)) { continue; }
        $p = str_replace(chr(92), '/', $f->getPathname());
        foreach ($lewati as $l) { if (strpos($p, $l) !== false) { continue 2; } }
        $hasil[] = $p;
    }
    sort($hasil);
    return $hasil;
}
/** Buang komentar (PHP) supaya penjelasan di komentar tidak dihitung sebagai kode. */
function pindai_tanpa_komentar($kode) {
    $out = '';
    foreach (token_get_all($kode) as $t) {
        if (is_array($t)) { $out .= in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? str_repeat("\n", substr_count($t[1], "\n")) : $t[1]; }
        else { $out .= $t; }
    }
    return $out;
}

// ---------------------------------------------------------------- pemindai PHP
/** @return array<int, array{0:string,1:int,2:string}> [aturan, baris, keterangan] */
function pindai_php($kode, $rel) {
    $temuan = [];
    $tok = token_get_all($kode);
    $n = count($tok);
    $eksekusi = ['exec', 'system', 'shell_exec', 'passthru', 'popen', 'proc_open', 'pcntl_exec', 'assert', 'create_function', 'dl'];
    $jaringan = ['curl_init', 'curl_multi_init', 'fsockopen', 'pfsockopen', 'stream_socket_client', 'socket_create', 'socket_connect', 'mail', 'get_headers', 'ftp_connect', 'ssh2_connect'];
    $jaringan_kelas = ['soapclient', 'guzzlehttp\client', 'minishlink\webpush\webpush', 'webpush', 'google\client', 'google_client'];
    $baca_url = ['fopen', 'file_get_contents', 'file', 'copy', 'readfile', 'simplexml_load_file'];
    $tulis = ['file_put_contents', 'fopen', 'fwrite', 'copy', 'rename', 'move_uploaded_file', 'touch'];
    $samar = ['gzinflate', 'gzuncompress', 'str_rot13', 'convert_uudecode'];
    $chr = 0;
    for ($i = 0; $i < $n; $i++) {
        $t = $tok[$i]; $baris = is_array($t) ? $t[2] : 0;
        if (!$baris) { for ($k = $i; $k >= 0; $k--) { if (is_array($tok[$k])) { $baris = $tok[$k][2]; break; } } }
        if (is_array($t) && $t[0] === T_EVAL) { $temuan[] = ['eksekusi-dinamis', $baris, 'eval']; continue; }
        if ($t === '`') { $temuan[] = ['eksekusi-dinamis', $baris, 'operator backtick (eksekusi shell)']; continue; }
        if ($t === '$' && isset($tok[$i + 1]) && ($tok[$i + 1] === '$' || (is_array($tok[$i + 1]) && $tok[$i + 1][0] === T_VARIABLE) || $tok[$i + 1] === '{')) {
            $temuan[] = ['samaran', $baris, 'variabel-variabel ($$ atau ${...})'];
        }
        if (is_array($t) && in_array($t[0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], true)) {
            $dinamis = false;
            for ($k = $i + 1; $k < $n && $tok[$k] !== ';'; $k++) {
                if (is_array($tok[$k]) && $tok[$k][0] === T_VARIABLE && $tok[$k][1] !== '$this') { $dinamis = true; break; }
            }
            if ($dinamis) { $temuan[] = ['berkas-berisiko', $baris, 'include/require dengan jalur dinamis (variabel)']; }
            continue;
        }
        if (is_array($t) && $t[0] === T_NEW) {
            for ($k = $i + 1; $k < $n && is_array($tok[$k]) && $tok[$k][0] === T_WHITESPACE; $k++);
            if (isset($tok[$k]) && is_array($tok[$k]) && in_array($tok[$k][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)
                && in_array(strtolower(ltrim($tok[$k][1], '\\')), $jaringan_kelas, true)) {
                $temuan[] = ['jaringan-keluar', $baris, 'new ' . $tok[$k][1]];
            }
            continue;
        }
        if (!is_array($t) || $t[0] !== T_STRING) {
            if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $s = $t[1];
                if (strlen($s) > 300 && preg_match('/^[\'"][A-Za-z0-9+\/=\s]{300,}[\'"]$/', $s)) { $temuan[] = ['samaran', $baris, 'literal panjang mirip base64 (' . strlen($s) . ' karakter)']; }
                if ($s[0] === '"' && preg_match_all('/\\\\x[0-9a-fA-F]{2}/', $s) >= 4) { $temuan[] = ['samaran', $baris, 'literal ber-escape heksadesimal (bisa menyembunyikan nama fungsi)']; }
            }
            continue;
        }
        $nama = strtolower($t[1]);
        $sebelum = null; for ($k = $i - 1; $k >= 0; $k--) { if (!is_array($tok[$k]) || $tok[$k][0] !== T_WHITESPACE) { $sebelum = $tok[$k]; break; } }
        $setelah = null; for ($k = $i + 1; $k < $n; $k++) { if (!is_array($tok[$k]) || $tok[$k][0] !== T_WHITESPACE) { $setelah = $tok[$k]; $idx = $k; break; } }
        if ($setelah !== '(') { continue; }
        // metode/fungsi bernama sama (mis. $db->exec, Kelas::system) bukan fungsi bawaan PHP
        if (is_array($sebelum) && in_array($sebelum[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true)) { continue; }
        // argumen pertama (literal?) untuk aturan berbasis isi
        $arg = null; for ($k = $idx + 1; $k < $n; $k++) { if (!is_array($tok[$k]) || $tok[$k][0] !== T_WHITESPACE) { $arg = $tok[$k]; break; } }
        $arg_teks = is_array($arg) ? $arg[1] : (string) $arg;
        if (in_array($nama, $eksekusi, true)) { $temuan[] = ['eksekusi-dinamis', $baris, $nama . '()']; }
        if (in_array($nama, $jaringan, true)) { $temuan[] = ['jaringan-keluar', $baris, $nama . '()']; }
        if (in_array($nama, $baca_url, true) && preg_match('/^[\'"](https?|ftp):\/\//i', $arg_teks)) { $temuan[] = ['jaringan-keluar', $baris, $nama . '() ke URL']; }
        if ($nama === 'move_uploaded_file') { $temuan[] = ['unggahan', $baris, 'move_uploaded_file()']; }
        if (in_array($nama, $samar, true)) { $temuan[] = ['samaran', $baris, $nama . '()']; }
        if ($nama === 'base64_decode' && is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING && strlen($arg[1]) > 200) { $temuan[] = ['samaran', $baris, 'base64_decode() literal panjang']; }
        if ($nama === 'chr') { $chr++; }
        if (in_array($nama, ['preg_replace', 'mb_ereg_replace']) && preg_match('/^[\'"](.).*\1[a-zA-Z]*e[a-zA-Z]*[\'"]$/s', $arg_teks)) { $temuan[] = ['eksekusi-dinamis', $baris, $nama . '() dengan modifier /e']; }
        if (in_array($nama, $tulis, true)) {
            // cari literal berekstensi PHP di argumen pemanggilan ini
            $dalam = 1; $lit = '';
            for ($k = $idx + 1; $k < $n && $dalam > 0; $k++) {
                if ($tok[$k] === '(') $dalam++; elseif ($tok[$k] === ')') $dalam--;
                elseif (is_array($tok[$k]) && $tok[$k][0] === T_CONSTANT_ENCAPSED_STRING) $lit .= $tok[$k][1];
            }
            if (preg_match('/\.(php[0-9]?|phtml|phar|pht)[\'"]/i', $lit)) { $temuan[] = ['berkas-berisiko', $baris, $nama . '() menulis berkas berekstensi eksekusi PHP']; }
        }
    }
    if ($chr >= 8) { $temuan[] = ['samaran', 0, "chr() dipakai $chr kali dalam satu berkas (pola penyusunan string tersamar)"]; }
    $bersih = pindai_tanpa_komentar($kode);
    if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\s*\[[^\]]*\]\s*\(/', $bersih, $m, PREG_OFFSET_CAPTURE)) { $temuan[] = ['eksekusi-dinamis', substr_count(substr($bersih, 0, $m[0][1]), "\n") + 1, 'fungsi dipanggil dari nilai masukan pengguna']; }
    if (preg_match('/\b(extract|unserialize|call_user_func(_array)?)\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/', $bersih, $m, PREG_OFFSET_CAPTURE)) { $temuan[] = ['eksekusi-dinamis', substr_count(substr($bersih, 0, $m[0][1]), "\n") + 1, $m[1][0] . '() atas masukan pengguna']; }
    if (preg_match('/c99shell|r57shell|b374k|filesman|weevely|indoxploit|\bwso\s*shell/i', $bersih, $m, PREG_OFFSET_CAPTURE)) { $temuan[] = ['tanda-webshell', substr_count(substr($bersih, 0, $m[0][1]), "\n") + 1, "tanda webshell dikenal: {$m[0][0]}"]; }
    return $temuan;
}

// ---------------------------------------------------------------- pemindai JavaScript
function pindai_js($kode, $rel) {
    $temuan = [];
    $aturan = [
        ['eksekusi-dinamis', '/\beval\s*\(/',                                   'eval()'],
        ['eksekusi-dinamis', '/\bnew\s+Function\s*\(/',                         'new Function()'],
        ['eksekusi-dinamis', '/\bset(Timeout|Interval)\s*\(\s*[\'"`]/',         'setTimeout/setInterval dengan string'],
        ['eksekusi-dinamis', '/\bdocument\.write(ln)?\s*\(/',                   'document.write()'],
        ['eksekusi-dinamis', '/\bimportScripts\s*\(/',                          'importScripts()'],
        ['jaringan-keluar',  '/\bnew\s+(WebSocket|EventSource)\s*\(/',          'WebSocket/EventSource'],
        ['jaringan-keluar',  '/\bsendBeacon\s*\(/',                             'navigator.sendBeacon()'],
        ['jaringan-keluar',  '/\bnew\s+Worker\s*\(\s*[\'"`]https?:/',           'Worker dari URL luar'],
        ['samaran',          '/\b(eval|Function)\b[^;]{0,80}\batob\s*\(|\batob\s*\([^)]*\)[^;]{0,80}\b(eval|Function)\b/', 'atob() dieksekusi (payload tersamar)'],
    ];
    foreach ($aturan as [$kode_aturan, $re, $ket]) {
        if (preg_match_all($re, $kode, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as $hit) { $temuan[] = [$kode_aturan, substr_count(substr($kode, 0, $hit[1]), "\n") + 1, $ket]; }
        }
    }
    // fetch/XHR ke host luar yang tidak diizinkan
    if (preg_match_all('#(fetch\s*\(|\.open\s*\(\s*[\'"][A-Za-z]+[\'"]\s*,)\s*[\'"`]https?://([a-z0-9.-]+)#i', $kode, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($m as $h) { if (!in_array(strtolower($h[2][0]), PINDAI_JS_HOST_DIIZINKAN, true)) { $temuan[] = ['jaringan-keluar', substr_count(substr($kode, 0, $h[0][1]), "\n") + 1, 'permintaan JS ke host luar: ' . $h[2][0]]; } }
    }
    if (preg_match_all('/\.src\s*=\s*[\'"`]https?:/', $kode, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) { $temuan[] = ['jaringan-keluar', substr_count(substr($kode, 0, $hit[1]), "\n") + 1, 'menetapkan .src ke URL luar']; }
    }
    if (preg_match_all('/createElement\s*\(\s*[\'"]script[\'"]\s*\)/', $kode, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $hit) {
            if (!isset(PINDAI_JS_SCRIPT_DINAMIS_DIIZINKAN[$rel])) { $temuan[] = ['jaringan-keluar', substr_count(substr($kode, 0, $hit[1]), "\n") + 1, 'membuat elemen <script> secara dinamis']; }
        }
    }
    return $temuan;
}
/** Skrip inline dalam view PHP/HTML (tanpa src). */
function pindai_js_inline($isi) {
    $blok = [];
    if (preg_match_all('#<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>#is', $isi, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $b) { $blok[] = [$b[0], substr_count(substr($isi, 0, $b[1]), "\n") + 1]; }
    }
    return $blok;
}

// ---------------------------------------------------------------- pemindai gabungan
/** Pindai seluruh kode buatan sendiri. @return array<string, array<int, array>> per berkas. */
function pindai_semua_kode() {
    $akar = pindai_akar(); $hasil = [];
    $php = array_merge([$akar . '/index.php'], pindai_daftar($akar . '/application', ['php'], ['/application/logs/', '/application/cache/']));
    foreach ($php as $p) {
        $rel = pindai_rel($p); $rel_app = preg_replace('#^application/#', '', $rel);
        $isi = file_get_contents($p);
        foreach (pindai_php($isi, $rel_app) as $t) { $hasil[$rel][] = $t; }
        // skrip inline di view
        if (strpos($rel, 'application/views/') === 0) {
            foreach (pindai_js_inline($isi) as [$js, $baris0]) {
                foreach (pindai_js($js, $rel_app) as $t) { $t[1] += $baris0 - 1; $t[2] = '[skrip inline] ' . $t[2]; $hasil[$rel][] = $t; }
            }
            // <script> dinamis dalam view dilaporkan lewat pindai_js dengan $rel_app; footer.php diizinkan
        }
    }
    foreach (array_merge(pindai_daftar($akar . '/assets/js', ['js'], ['/assets/js/vendor/']), glob($akar . '/*.js') ?: []) as $p) {
        $rel = pindai_rel($p);
        foreach (pindai_js(file_get_contents($p), $rel) as $t) { $hasil[$rel][] = $t; }
    }
    return $hasil;
}
/** Terapkan daftar izin. Yang tersisa = temuan yang HARUS ditinjau. */
function pindai_terapkan_izin(array $hasil) {
    $sisa = [];
    foreach ($hasil as $rel => $daftar) {
        $rel_app = preg_replace('#^application/#', '', $rel);
        foreach ($daftar as $t) {
            [$aturan, $baris, $ket] = $t;
            if ($aturan === 'jaringan-keluar' && isset(PINDAI_JARINGAN_DIIZINKAN[$rel_app])) { continue; }
            if ($aturan === 'unggahan' && isset(PINDAI_UNGGAH_DIIZINKAN[$rel_app])) { continue; }
            $sisa[$rel][] = $t;
        }
    }
    return $sisa;
}

// ---------------------------------------------------------------- integritas kode pihak ketiga
function pindai_manifest_path() { return pindai_akar() . '/docs/engineering/integritas_manifest.json'; }
function pindai_hash_lokal() {
    $akar = pindai_akar(); $h = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/system', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) { if ($f->isFile()) { $h['system/' . substr(str_replace(chr(92), '/', $f->getPathname()), strlen($akar . '/system') + 1)] = hash('sha256', pindai_normal(file_get_contents($f->getPathname()))); } }
    foreach (['assets/js/vendor/pdfjs/pdf.min.js', 'assets/js/vendor/pdfjs/pdf.worker.min.js', 'assets/js/vendor/tailwind-3.4.17.js'] as $rel) {
        if (is_file($akar . '/' . $rel)) { $h[$rel] = hash('sha256', pindai_normal(file_get_contents($akar . '/' . $rel))); }
    }
    ksort($h);
    return $h;
}

// ---------------------------------------------------------------- CLI
if (defined('PINDAI_SEBAGAI_PUSTAKA') || PHP_SAPI !== 'cli') { return; }

function pindai_cetak_temuan(array $sisa) {
    $jumlah = 0;
    foreach ($sisa as $rel => $daftar) { foreach ($daftar as [$aturan, $baris, $ket]) { $jumlah++; printf("  [%s] %s:%d  %s\n", $aturan, $rel, $baris, $ket); } }
    return $jumlah;
}
function pindai_jalankan(array $argv_cmd) {
    $p = proc_open($argv_cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, pindai_akar());
    if (!is_resource($p)) { return [-1, '', 'gagal menjalankan']; }
    $o = stream_get_contents($pipes[1]); $e = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($p), $o, $e];
}
function pindai_composer() {
    // Cari composer(.phar) di PATH dan jalankan dengan PHP yang SAMA dengan alat ini
    // (di server itu PHP 8.3; `php` bawaan hosting terlalu lama untuk dependensi).
    $kandidat = [];
    foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
        foreach (['composer.phar', 'composer'] as $nama) {
            $f = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $nama;
            if ($dir !== '' && is_file($f)) { $kandidat[] = [PHP_BINARY, $f]; }
        }
    }
    $kandidat[] = [PHP_BINARY, '/usr/local/bin/composer'];
    foreach ($kandidat as $c) {
        [$rc] = @pindai_jalankan(array_merge($c, ['--version']));
        if ($rc === 0) { return $c; }
    }
    return null;
}

$mode = $argv[1] ?? 'kode'; $gagal = 0;
$akar = pindai_akar();
echo "PEMINDAI KODE BERBAHAYA - mode: $mode\nWaktu: " . date('Y-m-d H:i:s T') . "  PHP " . PHP_VERSION . "\n\n";

if (in_array($mode, ['kode', 'semua'], true)) {
    echo "== 1. Analisis statis kode buatan sendiri (PHP dan JavaScript) ==\n";
    $mentah = pindai_semua_kode(); $sisa = pindai_terapkan_izin($mentah);
    $total = array_sum(array_map('count', $mentah)); $berkas = count($mentah);
    echo "  pindai: application/, index.php, assets/js (tanpa vendor/), skrip inline di view\n";
    echo "  sinyal mentah: $total di $berkas berkas; sesudah daftar izin yang ditinjau: " . array_sum(array_map('count', $sisa)) . "\n";
    if ($sisa) { $gagal += pindai_cetak_temuan($sisa); echo "[GAGAL] ada temuan yang belum ditinjau\n"; } else { echo "[LULUS] tidak ada temuan di luar daftar izin\n"; }
    echo "  koneksi keluar yang diizinkan (" . count(PINDAI_JARINGAN_DIIZINKAN) . " berkas):\n";
    foreach (PINDAI_JARINGAN_DIIZINKAN as $f => $ket) { echo "    - $f: $ket\n"; }
    echo "\n";
}
if (in_array($mode, ['integritas', 'semua'], true)) {
    echo "== 2. Integritas kode pihak ketiga (hash vs manifest) ==\n";
    $man = json_decode(@file_get_contents(pindai_manifest_path()), true);
    if (!$man) { echo "[GAGAL] manifest tidak terbaca\n"; $gagal++; }
    else {
        $lokal = pindai_hash_lokal(); $beda = []; $baru = array_diff_key($lokal, $man['berkas']); $hilang = array_diff_key($man['berkas'], $lokal);
        foreach ($man['berkas'] as $rel => $hash) { if (isset($lokal[$rel]) && $lokal[$rel] !== $hash) { $beda[] = $rel; } }
        echo '  berkas dalam manifest: ' . count($man['berkas']) . ', lokal: ' . count($lokal) . "\n";
        if ($beda || $baru || $hilang) {
            $gagal += count($beda) + count($baru) + count($hilang);
            foreach ($beda as $b) echo "  [GAGAL] BERUBAH: $b\n"; foreach (array_keys($baru) as $b) echo "  [GAGAL] TAMBAHAN tak terdaftar: $b\n"; foreach (array_keys($hilang) as $b) echo "  [GAGAL] HILANG: $b\n";
        } else { echo "[LULUS] seluruh " . count($lokal) . " berkas identik dengan manifest (yang dicocokkan ke rilis resmi pada " . ($man['diverifikasi_upstream'] ?? '?') . ")\n"; }
    }
    echo "\n";
}
if ($mode === 'upstream') {
    echo "== 3. Verifikasi ke rilis RESMI (butuh internet) ==\n";
    $man = json_decode(@file_get_contents(pindai_manifest_path()), true); $tmp = sys_get_temp_dir() . '/pindai_' . getmypid(); @mkdir($tmp);
    $resmi = [];
    $zip = $tmp . '/ci.zip'; file_put_contents($zip, @file_get_contents('https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.zip', false, transport_ctx()));
    $z = new ZipArchive;
    if ($z->open($zip) === true) { for ($i = 0; $i < $z->numFiles; $i++) { $nm = $z->getNameIndex($i); if (preg_match('#^CodeIgniter-3\.1\.13/system/(.+[^/])$#', $nm, $mm)) { $resmi['system/' . $mm[1]] = hash('sha256', pindai_normal($z->getFromIndex($i))); } } $z->close(); } else { echo "[GAGAL] tidak dapat mengunduh CodeIgniter 3.1.13\n"; $gagal++; }
    foreach (['assets/js/vendor/pdfjs/pdf.min.js' => 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js', 'assets/js/vendor/pdfjs/pdf.worker.min.js' => 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js', 'assets/js/vendor/tailwind-3.4.17.js' => 'https://cdn.tailwindcss.com/3.4.17'] as $rel => $url) {
        $isi = @file_get_contents($url, false, transport_ctx()); if ($isi !== false) { $resmi[$rel] = hash('sha256', pindai_normal($isi)); } else { echo "[GAGAL] tidak dapat mengunduh $url\n"; $gagal++; }
    }
    $lokal = pindai_hash_lokal(); $beda = []; foreach ($lokal as $rel => $h) { if (!isset($resmi[$rel]) || $resmi[$rel] !== $h) { $beda[] = $rel; } }
    $hilang = array_diff_key($resmi, $lokal);
    echo '  berkas resmi: ' . count($resmi) . ', lokal: ' . count($lokal) . "\n";
    if ($beda || $hilang) { foreach ($beda as $b) echo "  [GAGAL] beda dari rilis resmi: $b\n"; foreach (array_keys($hilang) as $b) echo "  [GAGAL] ada di rilis resmi, hilang lokal: $b\n"; $gagal += count($beda) + count($hilang); }
    else { echo "[LULUS] seluruh " . count($lokal) . " berkas lokal identik dengan rilis resmi (CodeIgniter 3.1.13, pdf.js 3.11.174, Tailwind 3.4.17; akhir baris dinormalisasi ke LF)\n"; }
    echo ($man && $man['berkas'] === $lokal ? "[LULUS] manifest sama dengan berkas lokal\n" : "[GAGAL] manifest tidak sama dengan berkas lokal - jalankan tulis-manifest sesudah verifikasi\n");
    if (!($man && $man['berkas'] === $lokal)) { $gagal++; }
    echo "\n";
}
function transport_ctx() { return stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'Klinik-PKP-pindai/1.0'], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]); }
if ($mode === 'tulis-manifest') {
    $lokal = pindai_hash_lokal();
    $man = ['keterangan' => 'Hash SHA-256 (akhir baris dinormalisasi ke LF) kode pihak ketiga yang di-vendor. Bangkitkan ulang HANYA sesudah `php docs/engineering/pindai_kode_berbahaya.php upstream` menyatakan seluruhnya identik dengan rilis resmi.',
        'sumber' => ['system/' => 'CodeIgniter 3.1.13 (github.com/bcit-ci/CodeIgniter, tag 3.1.13)', 'assets/js/vendor/pdfjs/' => 'pdfjs-dist 3.11.174 (npm, build/)', 'assets/js/vendor/tailwind-3.4.17.js' => 'cdn.tailwindcss.com/3.4.17'],
        'diverifikasi_upstream' => date('Y-m-d'), 'berkas' => $lokal];
    file_put_contents(pindai_manifest_path(), json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "manifest ditulis: " . count($lokal) . " berkas\n"; exit(0);
}
if (in_array($mode, ['pustaka', 'semua'], true)) {
    echo "== 4. Pustaka composer ==\n";
    $lock = @json_decode(@file_get_contents($akar . '/composer.lock'), true);
    if (!$lock) { echo "[GAGAL] composer.lock tidak ada atau tidak terbaca (versi pustaka tidak terkunci)\n"; $gagal++; }
    else {
        echo '  composer.lock: ' . count($lock['packages']) . ' paket produksi, ' . count($lock['packages-dev'] ?? []) . " paket dev\n";
        if (!empty($lock['packages-dev'])) { echo "[PERINGATAN] ada paket dev di lock; deploy memasangnya di production\n"; }
        $inst = @json_decode(@file_get_contents($akar . '/vendor/composer/installed.json'), true);
        if ($inst) {
            $a = []; $b = [];
            foreach ($lock['packages'] as $x) { $a[$x['name']] = $x['version'] . ' ' . substr($x['dist']['reference'] ?? '-', 0, 10); }
            foreach (($inst['packages'] ?? $inst) as $x) { $b[$x['name']] = $x['version'] . ' ' . substr($x['dist']['reference'] ?? '-', 0, 10); }
            $selisih = []; foreach ($a as $k => $v) { if (($b[$k] ?? null) !== $v) { $selisih[] = "$k lock=$v terpasang=" . ($b[$k] ?? '(tidak ada)'); } }
            $ekstra = array_diff_key($b, $a);
            if ($selisih && !in_array('--ketat', $argv, true)) {
                echo '  [PERINGATAN] ' . count($selisih) . " paket vendor/ berbeda dari lock (wajar di mesin dev dengan PHP lebih lama dari platform produksi; hanya pemeriksaan --ketat di server yang mengikat)\n";
            } elseif ($selisih) { foreach ($selisih as $s) echo "  [GAGAL] $s\n"; $gagal += count($selisih); } else { echo "[LULUS] semua " . count($a) . " paket di composer.lock terpasang persis di vendor/\n"; }
            if ($ekstra) { echo '  [PERINGATAN] paket terpasang di luar lock produksi: ' . implode(', ', array_keys($ekstra)) . "\n"; }
            // Sisa deploy lama: deploy mempertahankan isi vendor/ sebelumnya, jadi direktori paket yang sudah tidak
            // dipasang composer (mis. alat dev) bisa tertinggal di disk walau installed.json tidak lagi mencatatnya.
            $sisa_dir = [];
            foreach (glob($akar . '/vendor/*', GLOB_ONLYDIR) ?: [] as $v) {
                $vn = basename($v); if (in_array($vn, ['composer', 'bin'], true)) { continue; }
                foreach (glob($v . '/*', GLOB_ONLYDIR) ?: [] as $pk) { if (!isset($b[$vn . '/' . basename($pk)])) { $sisa_dir[] = $vn . '/' . basename($pk); } }
            }
            $bin_ok = []; foreach (($inst['packages'] ?? $inst) as $x) { foreach ((array) ($x['bin'] ?? []) as $bn) { $bin_ok[basename($bn)] = true; } }
            foreach (glob($akar . '/vendor/bin/*') ?: [] as $bn) { if (!isset($bin_ok[basename($bn)])) { $sisa_dir[] = 'bin/' . basename($bn); } }
            if ($sisa_dir) {
                if (in_array('--ketat', $argv, true)) { echo '[GAGAL] vendor/ berisi ' . count($sisa_dir) . " entri sisa deploy lama yang tidak dipasang composer:\n    " . implode("\n    ", $sisa_dir) . "\n"; $gagal += count($sisa_dir); }
                else { echo '  [PERINGATAN] vendor/ berisi ' . count($sisa_dir) . " entri yang tidak dipasang composer (sisa; --ketat menjadikannya GAGAL)\n"; }
            } else { echo "[LULUS] vendor/ hanya berisi paket yang dipasang composer (tanpa sisa deploy lama)\n"; }
        } else { echo "  vendor/ tidak ada di mesin ini; pemeriksaan terpasang dilewati\n"; }
        $c = pindai_composer();
        if ($c) {
            [$rc, $o, $e] = pindai_jalankan(array_merge($c, ['validate', '--no-check-publish', '--no-interaction']));
            echo $rc === 0 ? "[LULUS] composer validate: lock sinkron dengan composer.json\n" : "[GAGAL] composer validate:\n" . trim($o . $e) . "\n"; if ($rc !== 0) { $gagal++; }
            [$rc, $o, $e] = pindai_jalankan(array_merge($c, ['audit', '--locked', '--no-interaction']));
            echo $rc === 0 ? "[LULUS] composer audit: tidak ada advisori keamanan pada versi terkunci\n" : "[GAGAL] composer audit menemukan masalah:\n" . trim(substr($o . $e, 0, 1500)) . "\n"; if ($rc !== 0) { $gagal++; }
        } else { echo "[LEWAT] composer tidak ditemukan; validate dan audit dilewati\n"; }
    }
    echo "\n";
}
if ($mode === 'deploy') {
    echo "== 5. Integritas pohon berkas terpasang (jalankan di server) ==\n";
    [$rc, $o] = pindai_jalankan(['git', 'status', '--porcelain']);
    if ($rc !== 0) { echo "[LEWAT] bukan repositori git atau git tidak ada\n"; }
    else {
        $baris = array_filter(explode("\n", trim($o)));
        if ($baris) { echo "[GAGAL] pohon berkas TIDAK sama dengan commit (" . count($baris) . " perbedaan):\n"; foreach (array_slice($baris, 0, 20) as $b) echo "  $b\n"; $gagal += count($baris); }
        else { [, $h] = pindai_jalankan(['git', 'rev-parse', '--short', 'HEAD']); echo '[LULUS] tidak ada berkas berubah atau asing terhadap commit ' . trim($h) . "\n"; }
    }
    $asing = []; $log_ok = 0;
    foreach (['assets', 'uploads', 'application/cache', 'application/logs'] as $d) {
        foreach (pindai_daftar($akar . '/' . $d, ['php', 'phtml', 'phar', 'pht', 'php5', 'php7']) as $p) {
            $rel = pindai_rel($p);
            // Berkas log CodeIgniter memang berekstensi .php (baris pertama = penjaga BASEPATH). Sah bila namanya
            // log-YYYY-MM-DD.php dan hanya ada SATU tag <?php; tag kedua berarti isi log memuat kode (keracunan log).
            if (strpos($rel, 'application/logs/') === 0 && preg_match('#/log-\d{4}-\d{2}-\d{2}\.php$#', $rel)) {
                if (substr_count(file_get_contents($p), '<' . '?php') <= 1) { $log_ok++; continue; }
                $asing[] = "$rel (memuat tag PHP kedua di dalam isi log)"; continue;
            }
            $asing[] = $rel;
        }
    }
    if ($asing) { echo "[GAGAL] berkas PHP di direktori yang tidak boleh berisi kode:\n"; foreach ($asing as $a) echo "  $a\n"; $gagal += count($asing); }
    else { echo "[LULUS] tidak ada berkas PHP di assets/, uploads/, application/cache/; $log_ok berkas log CodeIgniter sah (satu tag PHP penjaga)\n"; }
    echo "\n";
}
echo $gagal ? "RINGKASAN: $gagal temuan/kegagalan\n" : "RINGKASAN: bersih\n";
exit($gagal ? 1 : 0);
