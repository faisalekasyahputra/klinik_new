<?php
/**
 * Pembersih data sampel dan kredensial bawaan (form keamanan poin 13.3).
 *
 *   php docs/engineering/bersihkan_data_sampel.php        KERING: hanya melaporkan apa yang akan diubah
 *   php docs/engineering/bersihkan_data_sampel.php --ya   menjalankan (CADANGKAN database dulu)
 *
 * Membaca koneksi dari env (DB_HOST, DB_USER, DB_PASS, DB_NAME), sama dengan aplikasi; di server:
 *   set -a && . ./.env && set +a && php docs/engineering/bersihkan_data_sampel.php
 *
 * Yang dilakukan (semuanya idempoten):
 *  1. AKUN DENGAN KATA SANDI BAWAAN. Setiap akun yang hash-nya cocok dengan daftar kata sandi bawaan
 *     yang umum (mis. `password`, pernah tertulis di dokumen publik) dianggap bocor:
 *       - peran admin: kata sandi diganti acak (dicetak SEKALI ke terminal, tidak disimpan di mana pun),
 *         dipaksa diganti saat masuk pertama (password_expires_at), sesi dicabut;
 *       - peran lain: status menjadi `nonaktif` (gerbang login menolaknya) dan sesi dicabut. Tidak dihapus:
 *         superadmin dapat mengaktifkannya kembali dari layar Pengguna dan menetapkan kata sandi baru.
 *  2. DATA DEMO KKN: pendaftaran bertema `DEMO-SERTIFIKAT-KKN-*` (dibuat migrasi 055 di semua lingkungan)
 *     beserta pesertanya (CASCADE). Dulu menjadi sertifikat "sah" yang dapat dicari publik lewat NIM.
 *  3. DATA SIMULASI SIMPERUM: antrean bernama/ber-NIK fiktif dan snapshot ber-source_mode=simulation.
 * Hasil (hanya jumlah) dicatat di jejak audit sebagai `data_sampel_dibersihkan`.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

mysqli_report(MYSQLI_REPORT_OFF);
$ya = in_array('--ya', $argv, TRUE);
$m = @new mysqli(getenv('DB_HOST') ?: '127.0.0.1', (string) getenv('DB_USER'), (string) getenv('DB_PASS'), (string) getenv('DB_NAME'));
if ($m->connect_errno) { fwrite(STDERR, "Tidak dapat terhubung ke database (cek env DB_*).\n"); exit(2); }
$m->set_charset('utf8mb4');
echo 'Database: ' . $m->query('SELECT DATABASE()')->fetch_row()[0] . ($ya ? "  [MODE UBAH]\n" : "  [KERING]\n");

const KATA_SANDI_BAWAAN = ['password', 'Password', 'password1', 'password123', 'Password123', 'Password1', 'admin', 'admin123', 'Admin123',
    '12345678', '123456', '1234567890', 'qwerty', 'qwerty123', 'rahasia', 'klinikpkp', 'Klinik123', 'changeme', 'letmein'];

function samar($email) { $p = explode('@', (string) $email, 2); return substr($p[0], 0, 2) . '***@' . ($p[1] ?? '?'); }
function acak($panjang = 24) {
    $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $s = ''; for ($i = 0; $i < $panjang; $i++) { $s .= $abjad[random_int(0, strlen($abjad) - 1)]; }
    return $s;
}
function jumlah($m, $sql) { $r = $m->query($sql); return $r ? (int) $r->fetch_row()[0] : 0; }

$ringkas = ['akun_dinonaktifkan' => 0, 'akun_admin_diputar' => 0, 'demo_kkn_dihapus' => 0, 'antrean_simulasi_dihapus' => 0, 'snapshot_simulasi_dihapus' => 0];
$baru_admin = [];

// ---- 1. Akun berkata sandi bawaan
echo "\n== 1. Akun dengan kata sandi bawaan ==\n";
$akun = $m->query("SELECT id, email, role, status, password FROM usr_users WHERE password IS NOT NULL AND password <> ''")->fetch_all(MYSQLI_ASSOC);
$bocor = [];
foreach ($akun as $a) {
    foreach (KATA_SANDI_BAWAAN as $k) { if (password_verify($k, $a['password'])) { $bocor[] = $a; break; } }
}
if ( ! $bocor) { echo "  tidak ada\n"; }
foreach ($bocor as $a) {
    $admin = $a['role'] === 'admin';
    echo sprintf("  #%d %-28s peran=%-14s status=%-10s -> %s\n", $a['id'], samar($a['email']), $a['role'], $a['status'] ?? '-', $admin ? 'kata sandi DIPUTAR + wajib ganti' : 'DINONAKTIFKAN');
    if ( ! $ya) { continue; }
    if ($admin) {
        $sandi = acak();
        $hash = password_hash($sandi, PASSWORD_BCRYPT);
        $st = $m->prepare("UPDATE usr_users SET password = ?, password_changed_at = NOW(), password_expires_at = (NOW() - INTERVAL 1 MINUTE), login_attempts = 0, locked_until = NULL,
                           active_session_hash = NULL, active_session_id_hash = NULL, active_session_at = NULL WHERE id = ?");
        $st->bind_param('si', $hash, $a['id']); $st->execute();
        if ($st->affected_rows === 1) { $ringkas['akun_admin_diputar']++; $baru_admin[] = [$a['email'], $sandi]; }
    } else {
        $st = $m->prepare("UPDATE usr_users SET status = 'nonaktif', active_session_hash = NULL, active_session_id_hash = NULL, active_session_at = NULL WHERE id = ? AND (status IS NULL OR status <> 'nonaktif')");
        $st->bind_param('i', $a['id']); $st->execute();
        $ringkas['akun_dinonaktifkan'] += $st->affected_rows > 0 ? 1 : 0;
    }
}

// ---- 2. Data demo KKN
echo "\n== 2. Data demo KKN ==\n";
$n_demo = jumlah($m, "SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE divisi_atau_tema LIKE 'DEMO-SERTIFIKAT-KKN-%'");
$n_peserta = jumlah($m, "SELECT COUNT(*) FROM kkn_peserta p JOIN kkn_magang_pendaftaran k ON k.id = p.pendaftaran_id WHERE k.divisi_atau_tema LIKE 'DEMO-SERTIFIKAT-KKN-%'");
echo "  pendaftaran demo: $n_demo, peserta demo: $n_peserta\n";
if ($ya && $n_demo > 0) { $m->query("DELETE FROM kkn_magang_pendaftaran WHERE divisi_atau_tema LIKE 'DEMO-SERTIFIKAT-KKN-%'"); $ringkas['demo_kkn_dihapus'] = $m->affected_rows; }

// ---- 3. Data simulasi SIMPERUM
echo "\n== 3. Data simulasi SIMPERUM ==\n";
$kondisi_antrean = "source_mode = 'simulation' AND (nama_lengkap LIKE '%Simulasi%' OR nik_pengaju LIKE '000000000000%')";
$n_antrean = jumlah($m, "SELECT COUNT(*) FROM sf_housing_queue WHERE $kondisi_antrean");
$n_snap = jumlah($m, "SELECT COUNT(*) FROM sf_rekaman_simperum WHERE source_mode = 'simulation'");
echo "  antrean simulasi: $n_antrean, snapshot simulasi: $n_snap\n";
if ($ya) {
    if ($n_antrean > 0) { $m->query("DELETE FROM sf_housing_queue WHERE $kondisi_antrean"); $ringkas['antrean_simulasi_dihapus'] = $m->affected_rows; }
    if ($n_snap > 0) { $m->query("DELETE FROM sf_rekaman_simperum WHERE source_mode = 'simulation'"); $ringkas['snapshot_simulasi_dihapus'] = $m->affected_rows; }
}

if ($ya) {
    $st = $m->prepare("INSERT INTO sys_jejak_audit (actor_role, aksi, objek_tipe, ringkasan, detail_json, ip, created_at) VALUES ('sistem', 'data_sampel_dibersihkan', 'konfigurasi', ?, ?, 'cli', NOW())");
    $ring = 'Pembersih data sampel: ' . array_sum($ringkas) . ' entri';
    $json = json_encode($ringkas);
    $st->bind_param('ss', $ring, $json); $st->execute();
}

echo "\n== Ringkasan ==\n";
foreach ($ringkas as $k => $v) { echo sprintf("  %-28s %d\n", $k, $v); }
if ($baru_admin) {
    echo "\n== KATA SANDI BARU (dicetak sekali; catat sekarang, tidak disimpan di mana pun) ==\n";
    foreach ($baru_admin as [$email, $sandi]) { echo "  $email\n  $sandi\n"; }
    echo "  Akun ini WAJIB mengganti kata sandi saat masuk pertama.\n";
}
if ( ! $ya) { echo "\nKERING: tidak ada yang diubah. Tambahkan --ya untuk menjalankan.\n"; }
