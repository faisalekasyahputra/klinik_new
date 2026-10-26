<?php
/*
 * Uji cabang hunian layak (permintaan dinas 26 Okt 2026): wizard warga dengan rumah milik yang semua kondisinya
 * baik sampai review, lalu Simpan Data tanpa program. Memakai harness uji_wizard_dan_cek_rumah.php (akun uji
 * sendiri, dibersihkan otomatis) dengan UJI_CABANG=layak.
 * Jalankan: php docs/engineering/uji_hunian_layak_tersimpan.php
 */
putenv('UJI_CABANG=layak');
require __DIR__ . '/uji_wizard_dan_cek_rumah.php';
