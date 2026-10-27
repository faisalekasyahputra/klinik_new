<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pilihan B dinas (WA 27 Okt 2026): matriks ikut menentukan program yang bisa dipilih warga. Disimpan per versi
 * aturan supaya Super Admin menyalakan atau mematikannya lewat draf dan uji coba, tanpa rilis kode. Bawaan 0
 * (perilaku lama: desil + syarat data); kode lama tidak membaca kolom ini, jadi migrasi aman sebelum push.
 */
class Migration_Matriks_menentukan extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('matriks_menentukan', 'aturan_versi')) {
            $this->db->query("ALTER TABLE aturan_versi ADD matriks_menentukan TINYINT(1) NOT NULL DEFAULT 0
                COMMENT '1 = program hanya bisa dipilih bila juga cocok dengan baris matriksnya' AFTER status");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('matriks_menentukan', 'aturan_versi')) {
            $this->db->query('ALTER TABLE aturan_versi DROP COLUMN matriks_menentukan');
        }
    }
}
