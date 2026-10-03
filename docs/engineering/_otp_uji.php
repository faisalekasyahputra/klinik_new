<?php
/**
 * Pembantu suite: kode OTP pendaftaran untuk alamat uji (*.test). Di luar production
 * libraries/Otp_pendaftaran.php menulis kodenya ke application/cache/otp_uji/, bukan mengirim email.
 * Pakai: require_once __DIR__ . '/_otp_uji.php'; lalu kirim kode_otp_uji($email) ke Auth/do_verifikasi_email.
 */
function kode_otp_uji($email) {
    $f = dirname(__DIR__, 2) . '/application/cache/otp_uji/' . sha1(strtolower($email)) . '.txt';
    return is_file($f) ? trim((string) file_get_contents($f)) : '';
}
