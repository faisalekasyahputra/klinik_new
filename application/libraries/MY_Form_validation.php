<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Aturan validasi tambahan yang dipakai lebih dari satu titik. Pesannya ada di
 * application/language/english/form_validation_lang.php.
 */
class MY_Form_validation extends CI_Form_validation {

    /**
     * Aturan kekuatan sandi yang SAMA dengan daftar, onboarding, dan ganti sandi di profil
     * (minimal 8, huruf besar, angka, simbol). Dipakai titik yang membuat sandi untuk orang
     * lain (Admin_Users::create_staff, Kemitraan_Bidang::buat_universitas), yang sebelumnya
     * hanya menuntut 8 karakter (temuan UAT universitas U2, 28 Sep 2026).
     */
    public function sandi_kuat($str)
    {
        $str = (string) $str;
        return strlen($str) >= 8 && preg_match('/[A-Z]/', $str) && preg_match('/[0-9]/', $str)
            && preg_match('/[^A-Za-z0-9]/', $str);
    }

    /** Nomor HP/telepon: angka, boleh diawali +, spasi dan tanda hubung sebagai pemisah. */
    public function nomor_hp($str)
    {
        return (bool) preg_match('/^\+?[0-9][0-9 \-]{6,19}$/', (string) $str);
    }

    /**
     * Tanggal kalender Y-m-d yang sungguh ada. regex_match saja meloloskan 2026-02-31 dan
     * 2026-13-45, yang di MySQL non-strict tersimpan sebagai 0000-00-00 (temuan UAT U3).
     */
    public function tanggal_sah($str)
    {
        $d = DateTime::createFromFormat('!Y-m-d', (string) $str);
        return $d !== FALSE && $d->format('Y-m-d') === (string) $str;
    }
}
