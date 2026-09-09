<?php
/**
 * Panen kelas Tailwind dari view PORTAL menjadi CSS statis.
 *
 * Masalah yang dipecahkan: portal memuat tailwind lewat CDN runtime
 * (cdn.tailwindcss.com, defer) + tailwind.min.css statis yang beku sejak
 * Jun 2026. Kelas yang tidak ada di file statis (semua varian sm:*, kelas
 * halaman baru) baru bergaya SETELAH CDN jalan -> first paint telanjang,
 * halaman "bergerak" (padding/grid menyusul). Skeleton hanya menutupi
 * gejalanya.
 *
 * Cara pakai (dua langkah, butuh browser karena generator CSS-nya ya CDN
 * tailwind itu sendiri):
 *   1) php docs/engineering/panen_tailwind.php
 *      -> menulis uji_harvest_tailwind.html + uji_harvest_receiver.php di
 *         root proyek, mencetak token & URL.
 *   2) Buka URL itu di browser (localhost). Halaman mengumpulkan CSS hasil
 *      generate CDN, membuang preflight (reset elemen) supaya tidak
 *      menimpa design-system.css, lalu POST ke receiver yang menulis
 *      assets/css/tailwind-generated.css. Setelah selesai HAPUS kedua
 *      berkas uji_harvest_* dari root.
 *
 * Jalankan ulang setiap kali menambah kelas tailwind baru di view portal
 * (application/views/ di luar admin/). Lupa menjalankan bukan bencana:
 * CDN runtime tetap terpasang sebagai jaring pengaman, hanya kembali ada
 * kedipan pada kelas yang belum terpanen.
 */

$root = dirname(__DIR__, 2);
// Mode: 'portal' (default) atau 'admin'. Shell admin punya config sendiri
// (darkMode class + warna brand) sehingga dipanen terpisah.
$mode = $argv[1] ?? 'portal';
if ($mode === 'admin') {
    // BUKAN cuma views/admin. Tiga view di luar direktori itu juga dirender
    // lewat render_user_dashboard()/render_scoped_admin(), artinya mereka
    // memakai shell admin dan memuat tailwind-admin.css - tapi kelasnya tidak
    // pernah ikut terpanen ke sana. Akibatnya `sm:grid-cols-2` dan `md:p-8` di
    // pages/pengaturan/profil.php tidak ada di CSS statis mana pun, dan
    // halaman itu selalu cat-pertama telanjang sampai CDN menyusul.
    //
    // Panen portal juga menyapu views/pages, tapi keluarannya
    // tailwind-generated.css - berkas yang TIDAK dimuat shell admin. Jadi
    // "sudah terpanen di portal" bukan jawaban untuk halaman ini.
    //
    // Daftar ini diturunkan dari pemanggil sungguhan:
    //   grep -rho "render_user_dashboard('[^']*'\|render_scoped_admin('[^']*'" application/controllers/
    $dirs = [
        $root . '/application/views/admin',
        $root . '/application/views/admin_bidang',
        $root . '/application/views/pages/pengaturan',
    ];
    $out_css = 'tailwind-admin.css';
    $config_js = "tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['\"Plus Jakarta Sans\"', 'sans-serif'] }, colors: { brand: { primary: '#d6fb00', hover: '#b5d400', light: '#ecffb6', muted: '#8aacb0', dark: '#0a1a1f', card: '#0f2933' } } } } };";
} else {
    $dirs = [
        $root . '/application/views/pages',
        $root . '/application/views/layouts',
        $root . '/application/views/components',
    ];
    $out_css = 'tailwind-generated.css';
    $config_js = '';
}

$tokens = [];
$files = [];
foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() !== 'php') { continue; }
        if (strpos($f->getPathname(), DIRECTORY_SEPARATOR . 'archive' . DIRECTORY_SEPARATOR) !== FALSE) { continue; }
        $files[] = $f->getPathname();
    }
}

$valid = function ($t) {
    if ($t === '' || strlen($t) > 120) { return FALSE; }
    // Tolak sisa interpolasi PHP/JS; kurung () hanya sah di nilai arbitrer [..var(..)..]
    if (preg_match('/[<>?$"\'=;{}`\\\\]/', $t)) { return FALSE; }
    if ((strpos($t, '(') !== FALSE || strpos($t, ')') !== FALSE) && strpos($t, '[') === FALSE) { return FALSE; }
    return (bool) preg_match('/^[!a-zA-Z0-9\-]/', $t);
};

foreach ($files as $path) {
    $src = file_get_contents($path);
    $blobs = [];
    if (preg_match_all('/class\s*=\s*"([^"]*)"/i', $src, $m)) { $blobs = array_merge($blobs, $m[1]); }
    if (preg_match_all("/class\s*=\s*'([^']*)'/i", $src, $m)) { $blobs = array_merge($blobs, $m[1]); }
    // String berkutip tunggal di JS/Alpine (:class, innerHTML skeleton, dst.)
    if (preg_match_all("/'([a-z0-9!\\-\\[\\]\\/\\.:%#_,() ]{2,})'/i", $src, $m)) { $blobs = array_merge($blobs, $m[1]); }
    foreach ($blobs as $blob) {
        foreach (preg_split('/\s+/', $blob) as $t) {
            if ($valid($t)) { $tokens[$t] = TRUE; }
        }
    }
}
$tokens = array_keys($tokens);
sort($tokens);
echo count($files) . " berkas view, " . count($tokens) . " token unik\n";

$token_auth = bin2hex(random_bytes(16));
$chunks = array_chunk($tokens, 400);
$divs = '';
foreach ($chunks as $chunk) {
    $divs .= '<div class="' . htmlspecialchars(implode(' ', $chunk), ENT_QUOTES) . '"></div>' . "\n";
}

file_put_contents($root . '/uji_harvest_receiver.php', <<<PHP
<?php
// Receiver sementara panen tailwind - HAPUS setelah panen selesai.
if (!in_array(\$_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], TRUE)) { http_response_code(403); exit; }
if ((\$_SERVER['HTTP_X_HARVEST_TOKEN'] ?? '') !== '$token_auth') { http_response_code(403); exit; }
\$css = file_get_contents('php://input');
if (strlen(\$css) < 1000) { http_response_code(422); exit('css terlalu kecil'); }
\$n = file_put_contents(__DIR__ . '/assets/css/$out_css', "/* Hasil panen kelas view ($mode) - regenerasi: php docs/engineering/panen_tailwind.php $mode */\\n" . \$css, LOCK_EX);
header('Content-Type: application/json');
echo json_encode(['written' => \$n]);
PHP
);

file_put_contents($root . '/uji_harvest_tailwind.html', <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Panen Tailwind</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>$config_js</script>
</head>
<body>
<p id="status">memanen…</p>
<div style="display:none">
$divs
</div>
<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        var out = [];
        var keep = function (rule) {
            if (rule.type === CSSRule.KEYFRAMES_RULE) { return rule.cssText; }
            if (rule.type === CSSRule.MEDIA_RULE || rule.type === CSSRule.SUPPORTS_RULE) {
                var inner = [];
                for (var r of rule.cssRules) { var k = keep(r); if (k) inner.push(k); }
                if (!inner.length) return '';
                return (rule.type === CSSRule.MEDIA_RULE ? '@media ' + rule.conditionText : '@supports ' + rule.conditionText) + '{' + inner.join('') + '}';
            }
            if (rule.selectorText) {
                // Buang preflight (selector elemen murni) supaya tidak menimpa
                // design-system.css; pertahankan utilitas (.kelas) dan rule
                // universal berisi default --tw-*.
                if (rule.selectorText.indexOf('.') !== -1) { return rule.cssText; }
                if (rule.cssText.indexOf('--tw-') !== -1) { return rule.cssText; }
            }
            return '';
        };
        for (var sheet of document.styleSheets) {
            if (!(sheet.ownerNode && sheet.ownerNode.tagName === 'STYLE')) continue;
            try { for (var rule of sheet.cssRules) { var k = keep(rule); if (k) out.push(k); } } catch (e) {}
        }
        var css = out.join('\\n');
        fetch('uji_harvest_receiver.php', {
            method: 'POST',
            headers: {'X-Harvest-Token': '$token_auth', 'Content-Type': 'text/css'},
            body: css
        }).then(r => r.json()).then(j => {
            document.getElementById('status').textContent = 'SELESAI: ' + j.written + ' byte tertulis (' + css.length + ' byte css)';
        }).catch(e => { document.getElementById('status').textContent = 'GAGAL: ' + e; });
    }, 1500);
});
</script>
</body>
</html>
HTML
);

echo "Tertulis: uji_harvest_tailwind.html + uji_harvest_receiver.php di root proyek.\n";
echo "Buka: http://localhost/klinik_new/uji_harvest_tailwind.html lalu tunggu status SELESAI.\n";
echo "Sesudahnya hapus kedua berkas uji_harvest_* itu.\n";
