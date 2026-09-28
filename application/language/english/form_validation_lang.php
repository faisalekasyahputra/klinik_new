<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Pesan validasi form dalam bahasa Indonesia (UAT pengembang 27 Sep 2026: form aduan
 * menampilkan "The Nama field is required."). CodeIgniter mendahulukan berkas ini di atas
 * system/language/english/form_validation_lang.php, jadi config bahasa tetap 'english'
 * dan berkas bahasa sistem lain tidak ikut berubah.
 */
$lang['form_validation_required']                  = '{field} wajib diisi.';
$lang['form_validation_isset']                     = '{field} harus memiliki nilai.';
$lang['form_validation_valid_email']               = '{field} harus berisi alamat email yang valid.';
$lang['form_validation_valid_emails']              = '{field} harus berisi alamat email yang valid semuanya.';
$lang['form_validation_valid_url']                 = '{field} harus berisi URL yang valid.';
$lang['form_validation_valid_ip']                  = '{field} harus berisi alamat IP yang valid.';
$lang['form_validation_min_length']                = '{field} minimal {param} karakter.';
$lang['form_validation_max_length']                = '{field} maksimal {param} karakter.';
$lang['form_validation_exact_length']              = '{field} harus tepat {param} karakter.';
$lang['form_validation_alpha']                     = '{field} hanya boleh berisi huruf.';
$lang['form_validation_alpha_numeric']             = '{field} hanya boleh berisi huruf dan angka.';
$lang['form_validation_alpha_numeric_spaces']      = '{field} hanya boleh berisi huruf, angka, dan spasi.';
$lang['form_validation_alpha_dash']                = '{field} hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.';
$lang['form_validation_numeric']                   = '{field} hanya boleh berisi angka.';
$lang['form_validation_is_numeric']                = '{field} hanya boleh berisi angka.';
$lang['form_validation_integer']                   = '{field} harus berupa bilangan bulat.';
$lang['form_validation_regex_match']               = 'Format {field} tidak sesuai.';
$lang['form_validation_matches']                   = '{field} tidak sama dengan {param}.';
$lang['form_validation_differs']                   = '{field} harus berbeda dari {param}.';
$lang['form_validation_is_unique']                 = '{field} sudah dipakai.';
$lang['form_validation_is_natural']                = '{field} hanya boleh berisi angka nol atau lebih.';
$lang['form_validation_is_natural_no_zero']        = '{field} hanya boleh berisi angka lebih dari nol.';
$lang['form_validation_decimal']                   = '{field} harus berupa angka desimal.';
$lang['form_validation_less_than']                 = '{field} harus kurang dari {param}.';
$lang['form_validation_less_than_equal_to']        = '{field} harus kurang dari atau sama dengan {param}.';
$lang['form_validation_greater_than']              = '{field} harus lebih dari {param}.';
$lang['form_validation_greater_than_equal_to']     = '{field} harus lebih dari atau sama dengan {param}.';
$lang['form_validation_error_message_not_set']     = 'Pesan galat untuk {field} belum tersedia.';
$lang['form_validation_in_list']                   = '{field} harus salah satu dari: {param}.';
// Aturan tambahan di application/libraries/MY_Form_validation.php.
$lang['form_validation_sandi_kuat']                = '{field} harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.';
$lang['form_validation_nomor_hp']                  = '{field} hanya boleh berisi angka (boleh diawali +), 7 sampai 20 karakter.';
$lang['form_validation_tanggal_sah']               = '{field} bukan tanggal kalender yang sah.';
