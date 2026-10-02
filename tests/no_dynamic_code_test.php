<?php
/** Regression guard: application input must never become executable code. */
$root = realpath(__DIR__ . '/../application');
if ($root === false) { throw new RuntimeException('Application directory missing'); }
$violations = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
    $tokens = token_get_all(file_get_contents($file->getPathname()));
    foreach ($tokens as $token) {
        if (is_array($token) && $token[0] === T_EVAL) {
            $violations[] = $file->getPathname() . ':' . $token[2] . ' eval';
        }
        if (is_array($token) && $token[0] === T_STRING && in_array(strtolower($token[1]),
            ['assert', 'create_function', 'shell_exec', 'exec', 'system', 'passthru'], true)) {
            $violations[] = $file->getPathname() . ':' . $token[2] . ' ' . $token[1];
        }
    }
}
$config = file_get_contents($root . '/config/config.php');
if (!preg_match('/\$config\[\x27rewrite_short_tags\x27\]\s*=\s*FALSE\s*;/', $config)) {
    $violations[] = 'rewrite_short_tags must remain FALSE';
}
if ($violations) {
    fwrite(STDERR, implode(PHP_EOL, $violations) . PHP_EOL);
    exit(1);
}
echo "No dynamic code execution in first-party PHP; short-tag eval path disabled.\n";
