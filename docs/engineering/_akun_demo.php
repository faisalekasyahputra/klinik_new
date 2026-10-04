<?php
/**
 * Pembantu suite: pinjam akun demo. Sejak migrasi 073 akun demo (*@example.com dan super admin demo)
 * berstatus nonaktif tanpa sandi. Suite lama yang masuk sebagai akun demo memanggil
 * pinjam_akun_demo($email): akun diaktifkan dengan sandi acak sekali pakai (yang dikembalikan untuk
 * login), lalu status, sandi, dan sesinya dipulihkan PERSIS seperti semula saat proses berakhir
 * (register_shutdown_function, jalan juga bila suite berhenti karena galat). Hanya DB lokal.
 */
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php';

function pinjam_akun_demo($email) {
    static $db = NULL, $dipinjam = [];
    if (isset($dipinjam[$email])) { return $dipinjam[$email]; } // pinjam dua kali = sandi yang sama, satu pemulihan
    if ($db === NULL) {
        $env = [];
        foreach (file(env_berkas_path(dirname(__DIR__, 2)), FILE_IGNORE_NEW_LINES) as $l) {
            if (strpos($l, '=') === FALSE || ltrim($l)[0] === '#') { continue; }
            [$k, $v] = explode('=', $l, 2);
            $env[trim($k)] = trim($v, " \t\"'");
        }
        if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
            fwrite(STDERR, "pinjam_akun_demo: hanya untuk DB lokal\n");
            exit(1);
        }
        mysqli_report(MYSQLI_REPORT_OFF);
        $db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
    }
    $st = $db->prepare('SELECT status, kata_sandi, sesi_aktif_hash, sesi_aktif_id_hash, sesi_aktif_at FROM usr_akun WHERE email = ?');
    $st->bind_param('s', $email);
    $st->execute();
    $asal = $st->get_result()->fetch_assoc();
    if ( ! $asal) { return 'akun-demo-tidak-ada'; }
    $sandi = 'Pinjam#' . bin2hex(random_bytes(6)) . 'A1';
    $hash = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("UPDATE usr_akun SET status = IF(status = 'nonaktif', 'active', status), kata_sandi = ? WHERE email = ?");
    $st->bind_param('ss', $hash, $email);
    $st->execute();
    register_shutdown_function(function () use ($db, $email, $asal) {
        $st = $db->prepare('UPDATE usr_akun SET status = ?, kata_sandi = ?, sesi_aktif_hash = ?, sesi_aktif_id_hash = ?, sesi_aktif_at = ? WHERE email = ?');
        $st->bind_param('ssssss', $asal['status'], $asal['kata_sandi'], $asal['sesi_aktif_hash'], $asal['sesi_aktif_id_hash'], $asal['sesi_aktif_at'], $email);
        $st->execute();
    });
    return $dipinjam[$email] = $sandi;
}
