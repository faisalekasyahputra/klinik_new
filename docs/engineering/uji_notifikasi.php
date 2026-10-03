<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)

$root = dirname(__DIR__, 2);
$checks = [];
$check = static function (bool $ok, string $label) use (&$checks): void {
    $checks[] = [$ok, $label];
};
$read = static function (string $path) use ($root): string {
    $contents = file_get_contents($root . '/' . $path);
    return $contents === false ? '' : $contents;
};

$component = $read('application/views/components/notification_center.php');
$portal = $read('application/views/layouts/main.php');
$admin = $read('application/views/admin/index.php');
$portalHead = $read('application/views/layouts/head.php');
$adminHead = $read('application/views/admin/layouts/head.php');
$authViews = [
    $read('application/views/pages/auth/login.php'),
    $read('application/views/pages/auth/register.php'),
    $read('application/views/pages/auth/onboarding.php'),
];
$javascript = $read('assets/js/notifications.js');
$legacyJavascript = $read('assets/js/script.js');

$check($component !== '' && $javascript !== '' && $read('assets/css/notifications.css') !== '', 'aset dan renderer notifikasi tersedia');
$check(str_contains($portal, "load->view('components/notification_center')"), 'renderer terpasang pada shell portal');
$check(str_contains($admin, "load->view('components/notification_center')"), 'renderer terpasang pada shell dashboard');
$check(str_contains($portalHead, 'assets/js/notifications.js') && str_contains($portalHead, 'assets/css/notifications.css'), 'aset dimuat shell portal');
$check(str_contains($adminHead, 'assets/js/notifications.js') && str_contains($adminHead, 'assets/css/notifications.css'), 'aset dimuat shell dashboard');
$check(count(array_filter($authViews, static fn(string $view): bool =>
    str_contains($view, "load->view('components/notification_center')")
    && str_contains($view, 'assets/js/notifications.js')
    && str_contains($view, 'assets/css/notifications.css')
)) === count($authViews), 'renderer dan aset terpasang pada halaman autentikasi');
$check(str_contains($component, "['success', 'error', 'warning', 'info']"), 'flashdata memakai whitelist empat tipe');
$check(str_contains($javascript, 'global.KPKP.notify = api') && str_contains($javascript, "type === 'success' || type === 'info' ? 5000 : 0"), 'API dan durasi semantik terkunci');
$check(str_contains($javascript, 'dialog: dialog') && str_contains($javascript, 'showModal') && str_contains($javascript, "tujuan.origin !== global.location.origin"),
    'dialog galat bersama tersedia dan hanya menerima tautan se-origin');
$check(str_contains($javascript, "document.body.classList.contains('auth-page')") && str_contains($javascript, "item.type === 'error' && (halamanAuth || Array.isArray(item.aksi))"),
    'galat di halaman autentikasi (dan galat bertombol di mana pun) tampil sebagai dialog');
$check(str_contains($component, "flashdata('galat_aksi')") && str_contains($component, "preg_match('#^[A-Za-z0-9_/\\-]{1,100}\$#D'"),
    'tombol pengarah dari flashdata galat_aksi dibatasi ke rute internal');
$check(str_contains($javascript, 'konfirmasi: konfirmasi') && str_contains($javascript, "form.hasAttribute('data-konfirmasi')") && str_contains($javascript, '}, true);'),
    'konfirmasi bergaya tersedia dan formulir data-konfirmasi ditahan di fase capture');
$check(!str_contains($legacyJavascript, 'setupFormHandling') && !str_contains($legacyJavascript, 'Data berhasil dikirim'), 'intersepsi form dan sukses palsu lama terhapus');

$violations = [];
$views = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/application/views', FilesystemIterator::SKIP_DOTS)
);
foreach ($views as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (str_contains($path, '/archive/') || str_contains($path, '/errors/')) {
        continue;
    }
    $contents = file_get_contents($path) ?: '';
    if (preg_match('/flashdata\s*\(\s*[\'"](?:success|error|warning|info)[\'"]\s*\)/', $contents)) {
        $violations[] = str_replace(str_replace('\\', '/', $root) . '/', '', $path) . ' (renderer flash lokal)';
    }
    if (preg_match('/\b(?:window\.)?alert\s*\(/', $contents)) {
        $violations[] = str_replace(str_replace('\\', '/', $root) . '/', '', $path) . ' (alert modal)';
    }
    // confirm() bawaan diganti data-konfirmasi / KPKP.notify.konfirmasi() (3 Okt 2026).
    if (preg_match('/(?<![\w.])(?:window\.)?confirm\s*\(/', $contents)) {
        $violations[] = str_replace(str_replace('\\', '/', $root) . '/', '', $path) . ' (confirm bawaan)';
    }
}
$check($violations === [], 'tidak ada renderer flash lokal atau alert modal aktif' . ($violations ? ': ' . implode(', ', $violations) : ''));

$passed = count(array_filter($checks, static fn(array $result): bool => $result[0]));
foreach ($checks as [$ok, $label]) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
}
echo sprintf('%d/%d pemeriksaan lulus.%s', $passed, count($checks), PHP_EOL);
exit($passed === count($checks) ? 0 : 1);
