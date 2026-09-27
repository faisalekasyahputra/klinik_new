<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Default kolom `asosiasi` dipulihkan dari string 'NULL' ke SQL NULL.
 *
 * PENYEBAB. Migrasi 051 menulis ulang default dari information_schema. Di
 * MariaDB, COLUMN_DEFAULT kolom ber-default NULL berisi TEKS "NULL", lalu
 * 051 mengutipnya sehingga lahir `DEFAULT 'NULL'`. Akibatnya pengajuan SRP2
 * yang diterima tanpa asosiasi (Auth_model::upsert_direktori_publik() tidak
 * menyebut kolomnya) tersimpan sebagai kata "NULL" dan tampil begitu di
 * direktori publik (temuan simulasi 27 Sep 2026). 051 sudah diperbaiki untuk
 * DB baru; migrasi ini membetulkan DB yang terlanjur menjalankannya.
 *
 * Baris bernilai 'NULL' ikut dikosongkan: itu bukan kode di srp2_asosiasi.
 */
class Migration_Asosiasi_default_null extends CI_Migration {

    const KOLOM = ['psu_serah_terima', 'srp2_certified_developers'];

    public function up() {
        foreach (self::KOLOM as $tabel) {
            if ( ! $this->db->table_exists($tabel) || ! $this->db->field_exists('asosiasi', $tabel)) { continue; }
            if ($this->db->query("ALTER TABLE `{$tabel}` ALTER COLUMN `asosiasi` SET DEFAULT NULL") === FALSE) {
                throw new RuntimeException("Migrasi 065: default {$tabel}.asosiasi gagal dipulihkan.");
            }
            $this->db->query("UPDATE `{$tabel}` SET `asosiasi` = NULL WHERE `asosiasi` = 'NULL'");
        }
    }

    public function down() {
        // Tidak ada yang dikembalikan: keadaan sebelumnya adalah bug.
    }
}
