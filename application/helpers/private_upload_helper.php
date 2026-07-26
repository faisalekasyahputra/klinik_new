<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lokasi penyimpanan berkas privat — SATU sumber kebenaran.
 *
 * Dibuat sebagai helper (bukan method di MY_Controller) supaya bisa dipakai
 * model juga: User_model::delete_user_account() perlu menghapus file fisik dan
 * dia bukan controller.
 */

if ( ! function_exists('private_uploads_root')) {
    /**
     * Akar direktori berkas privat, selalu diakhiri pemisah direktori.
     *
     * Diambil dari `PRIVATE_UPLOADS_PATH` di .env kalau diisi. Kalau tidak,
     * jatuh ke `dirname(FCPATH)/private_uploads/` — perilaku lama, dipertahankan
     * supaya deployment yang .env-nya belum diperbarui tidak langsung rusak.
     *
     * KENAPA JADI VARIABEL: `dirname(FCPATH)` menganggap induk folder aplikasi
     * otomatis di luar DocumentRoot. Itu TIDAK selalu benar — di XAMPP lokal
     * `dirname(FCPATH)` justru DocumentRoot itu sendiri, sehingga seluruh berkas
     * privat (termasuk dokumen SRP2) tersaji lewat HTTP; diverifikasi 26 Jul 2026.
     * Dengan variabel ini, keamanannya jadi keputusan sadar per-lingkungan,
     * bukan kebetulan tata letak deployment.
     *
     * Path relatif diperbolehkan dan dihitung dari FCPATH, supaya nilai seperti
     * `../simpanan_privat` tetap bekerja tanpa perlu tahu path absolut server.
     */
    function private_uploads_root() {
        $dari_env = getenv('PRIVATE_UPLOADS_PATH');

        if (is_string($dari_env) && trim($dari_env) !== '') {
            $path = rtrim(trim($dari_env), "/\\");
            // Deteksi absolut: diawali "/" (unix) atau "X:" (windows).
            $absolut = $path !== '' && ($path[0] === '/' || $path[0] === '\\' || preg_match('#^[A-Za-z]:#', $path));
            if ( ! $absolut) {
                $path = rtrim(FCPATH, "/\\") . DIRECTORY_SEPARATOR . $path;
            }
            return $path . DIRECTORY_SEPARATOR;
        }

        return dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR;
    }
}

if ( ! function_exists('private_uploads_dir')) {
    /**
     * Direktori berkas privat milik satu pemilik dalam satu domain, mis.
     * private_uploads/srp2/7/. Nama domain & pemilik di-sanitasi supaya tidak
     * bisa dipakai keluar dari akar lewat "..".
     */
    function private_uploads_dir($domain, $owner_id) {
        $domain   = preg_replace('/[^a-z0-9_]/i', '', (string) $domain);
        $owner_id = preg_replace('/[^a-z0-9_]/i', '', (string) $owner_id);
        return private_uploads_root() . $domain . DIRECTORY_SEPARATOR . $owner_id . DIRECTORY_SEPARATOR;
    }
}
