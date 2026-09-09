<?php
/**
 * Seed akun uji untuk agen-per-peran (.claude/agents/peran-*.md).
 *
 *   php docs/engineering/seed_agen_peran.php          # buat/segarkan
 *   php docs/engineering/seed_agen_peran.php --hapus  # bersihkan
 *
 * 🔴 LOKAL SAJA. Skrip ini MENOLAK JALAN kalau DB_HOST bukan localhost.
 * Production memakai `31.97.208.59`, dan akun ber-sandi yang tertulis di dalam
 * repo tidak boleh pernah ada di sana. Penjaganya di bawah, jangan dilepas.
 *
 * Semua akun memakai akhiran `@agen.test` supaya:
 *   - gampang dibedakan dari data demo yang DIPERTAHANKAN (AGENTS.md §20), dan
 *   - `--hapus` bisa menyapunya tanpa menyentuh apa pun yang lain.
 *
 * Sandinya sengaja seragam dan tertulis terang di sini: ini kredensial mainan
 * untuk basis data pengembangan, bukan rahasia. Yang berbahaya justru sandi
 * "rahasia" yang diam-diam ikut ter-commit - ini kebalikannya, terang-terangan
 * dan terkurung di localhost.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'AgenUji!2026');
define('TANDA', '@agen.test');

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

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);

// --- PENJAGA LOKAL. Jangan dilepas. -----------------------------------------
$host = strtolower(trim($env['DB_HOST'] ?? ''));
if ( ! in_array($host, ['localhost', '127.0.0.1', '::1'], TRUE)) {
    fwrite(STDERR, "TOLAK: DB_HOST = '{$host}'. Seed ini hanya untuk basis data LOKAL.\n");
    fwrite(STDERR, "Akun ber-sandi yang tertulis di repo tidak boleh ada di server mana pun.\n");
    exit(1);
}

$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($db->connect_error) { die("Koneksi DB gagal: {$db->connect_error}\n"); }

function q($db, $sql, $p = []) {
    $st = $db->prepare($sql);
    if ( ! $st) { fwrite(STDERR, $db->error . "\n"); exit(1); }
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r  = $st->get_result();
    $out = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $id = $st->insert_id;
    $st->close();
    return ['rows' => $out, 'id' => $id];
}

// --- Mode hapus --------------------------------------------------------------
if (in_array('--hapus', $argv, TRUE)) {
    $r = q($db, "SELECT id, email FROM usr_users WHERE email LIKE ?", ['%' . TANDA]);
    foreach ($r['rows'] as $u) {
        q($db, 'DELETE FROM kkn_magang_pendaftaran WHERE user_id=?', [$u['id']]);
        q($db, 'DELETE FROM usr_users WHERE id=?', [$u['id']]);
        echo "  hapus {$u['email']}\n";
    }
    echo "Selesai: " . count($r['rows']) . " akun agen dihapus.\n";
    exit(0);
}

// --- Acuan dari DB, bukan ditebak -------------------------------------------
$kab = q($db, "SELECT id, nama FROM kabupaten ORDER BY id LIMIT 1")['rows'][0] ?? NULL;
$bid = q($db, "SELECT kode, nama FROM bidang ORDER BY kode LIMIT 1")['rows'][0] ?? NULL;
if ( ! $kab) { die("Tabel `kabupaten` kosong - seed dasar belum jalan.\n"); }
if ( ! $bid) { die("Tabel `bidang` kosong - seed dasar belum jalan.\n"); }

/* Satu akun per peran. `profile_completed = 1` supaya login tidak dibelokkan
   ke wizard onboarding - agen menguji perjalanan perannya, bukan onboarding
   (itu sudah punya suite sendiri). */
$peran = [
    'warga'         => ['nama' => 'Agen Warga',        'kab' => NULL,        'bid' => NULL],
    'pengembang'    => ['nama' => 'Agen Pengembang',   'kab' => NULL,        'bid' => NULL],
    'mahasiswa'     => ['nama' => 'Agen Mahasiswa',    'kab' => NULL,        'bid' => NULL],
    'admin_kabkota' => ['nama' => 'Agen Admin Kabkota','kab' => $kab['id'],  'bid' => NULL],
    'admin_bidang'  => ['nama' => 'Agen Admin Bidang', 'kab' => NULL,        'bid' => $bid['kode']],
    'admin'         => ['nama' => 'Agen Super Admin',  'kab' => NULL,        'bid' => NULL],
];

$hash = password_hash(SANDI, PASSWORD_BCRYPT);
echo "=== SEED AGEN PERAN (lokal: {$env['DB_NAME']}) ===\n\n";
printf("%-16s %-34s %s\n", 'PERAN', 'EMAIL', 'CAKUPAN');
echo str_repeat('-', 86) . "\n";

foreach ($peran as $role => $cfg) {
    $email = 'agen_' . $role . TANDA;
    $ada = q($db, 'SELECT id FROM usr_users WHERE email=?', [$email])['rows'][0] ?? NULL;

    if ($ada) {
        // Disegarkan, bukan dilewati: sandi/role/scope dikembalikan ke keadaan
        // yang diketahui, supaya agen tidak gagal gara-gara sisa percobaan lama.
        q($db, 'UPDATE usr_users SET password=?, role=?, status="active", profile_completed=1,
                name=?, kabupaten_id=?, bidang_kode=? WHERE id=?',
            [$hash, $role, $cfg['nama'], $cfg['kab'], $cfg['bid'], $ada['id']]);
        $id = $ada['id'];
        $tanda = 'disegarkan';
    } else {
        $id = q($db, 'INSERT INTO usr_users (email,password,name,username,role,status,
                profile_completed,kabupaten_id,bidang_kode,created_at)
                VALUES (?,?,?,?,?, "active",1,?,?, NOW())',
            [$email, $hash, $cfg['nama'], 'agen_' . $role, $role, $cfg['kab'], $cfg['bid']])['id'];
        $tanda = 'dibuat';
    }

    $cakupan = $cfg['kab'] ? "kabupaten {$kab['nama']} ({$cfg['kab']})"
             : ($cfg['bid'] ? "bidang {$bid['nama']} ({$cfg['bid']})" : '-');
    printf("%-16s %-34s %s  [%s]\n", $role, $email, $cakupan, $tanda);
}

echo "\nSandi untuk SEMUA akun di atas: " . SANDI . "\n";
echo "Hapus lagi: php docs/engineering/seed_agen_peran.php --hapus\n";
