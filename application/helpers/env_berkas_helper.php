<?php
/**
 * Lokasi berkas .env, satu sumber untuk index.php, skrip CLI, suite, dan seed.
 *
 * Urutan pencarian:
 *   1. satu tingkat di atas akar aplikasi (di production: di luar public_html, jadi tidak pernah
 *      tersaji lewat HTTP walau aturan .htaccess tersapu deploy);
 *   2. akar aplikasi (FCPATH), perilaku lama untuk lokal dan instalasi yang belum dipindah.
 * Bila keduanya ada, berkas di luar yang dipakai dan berkas di akar TIDAK dibaca sama sekali (tidak
 * digabung baris per baris), supaya nilai yang berlaku selalu bisa dibaca dari satu berkas.
 *
 * Di XAMPP lokal induk akar aplikasi adalah htdocs, yang tersaji dan dipakai bersama aplikasi lain:
 * di lokal .env tetap di akar aplikasi, jangan diletakkan di htdocs.
 *
 * Tanpa pagar BASEPATH: dimuat index.php sebelum CodeIgniter berjalan dan oleh skrip CLI tanpa CI.
 */
if ( ! function_exists('env_berkas_path')) {
    function env_berkas_path($akar_aplikasi)
    {
        $akar = rtrim((string) $akar_aplikasi, '/\\');
        $luar = dirname($akar) . DIRECTORY_SEPARATOR . '.env';
        return is_file($luar) && is_readable($luar) ? $luar : $akar . DIRECTORY_SEPARATOR . '.env';
    }
}
