<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Simpan NPWP pengembang pada pengajuan SRP2, bukan pada kolom NIK akun. */
class Migration_Srp2_registration_npwp extends CI_Migration {

    private const TABEL = 'srp2_registrations';
    private const INDEKS = 'uq_srp2_registration_npwp';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 056: tabel srp2_registrations tidak tersedia.');
            return;
        }

        $kolom = [];
        if ( ! $this->db->field_exists('npwp_ciphertext', self::TABEL)) {
            $kolom['npwp_ciphertext'] = [
                'type' => 'TEXT', 'null' => TRUE,
                'comment' => 'NPWP pengembang terenkripsi dari onboarding',
            ];
        }
        if ( ! $this->db->field_exists('npwp_lookup_hash', self::TABEL)) {
            $kolom['npwp_lookup_hash'] = [
                'type' => 'CHAR', 'constraint' => 64, 'null' => TRUE,
                'comment' => 'Sidik deterministik NPWP untuk keunikan',
            ];
        }
        if ($kolom) { $this->dbforge->add_column(self::TABEL, $kolom); }

        $ada = (int) $this->db->query(
            "SELECT COUNT(*) n FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [self::TABEL, self::INDEKS]
        )->row('n');
        if ( ! $ada) {
            $ok = $this->db->query('ALTER TABLE `' . self::TABEL . '` ADD UNIQUE KEY `' . self::INDEKS . '` (`npwp_lookup_hash`)');
            if ($ok === FALSE) { log_message('error', 'Migrasi 056: indeks unik NPWP gagal dibuat.'); }
        }
    }

    public function down()
    {
        if ( ! $this->db->table_exists(self::TABEL)) { return; }
        $ada = (int) $this->db->query(
            "SELECT COUNT(*) n FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [self::TABEL, self::INDEKS]
        )->row('n');
        if ($ada) { $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP INDEX `' . self::INDEKS . '`'); }
        foreach (['npwp_ciphertext', 'npwp_lookup_hash'] as $nama) {
            if ($this->db->field_exists($nama, self::TABEL)) { $this->dbforge->drop_column(self::TABEL, $nama); }
        }
    }
}