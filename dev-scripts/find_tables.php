<?php
$dir = new RecursiveDirectoryIterator('C:\xampp\htdocs\klinik_new\application');
$iterator = new RecursiveIteratorIterator($dir);
$regex = "/(?<!\\$)\\b(programs|program_kategori|housing_queue|diskusi|komentar|users|user_documents|menu|multi|settings|sosmed_perumahan)\\b/i";
$tables = ['programs', 'program_kategori', 'housing_queue', 'diskusi', 'komentar', 'users', 'user_documents', 'menu', 'multi', 'settings', 'sosmed_perumahan'];

$matches = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            foreach ($tables as $table) {
                // Check if it looks like a DB call or SQL statement
                // $this->db->*(..., 'table_name'...)
                // 'table_name'
                // "table_name"
                // `table_name`
                if (preg_match("/(['\"`])$table\\1/", $line) || preg_match("/(?i:FROM|JOIN|UPDATE|INTO)\s+$table\b/", $line)) {
                    $matches[$file->getPathname()][] = [
                        'line' => $i + 1,
                        'text' => trim($line),
                        'table' => $table
                    ];
                }
            }
        }
    }
}

foreach ($matches as $file => $lines) {
    echo "FILE: $file\n";
    foreach ($lines as $l) {
        echo "  Line {$l['line']}: {$l['text']}\n";
    }
}
