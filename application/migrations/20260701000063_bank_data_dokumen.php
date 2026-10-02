<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dokumen Bank Data yang diunggah admin (daftar revisi dinas 23 Sep 2026: "tambah menu bank data
 * untuk admin update pdf bank data, card statistika PDF, yang akan tampil di data"). Dua jenis:
 * buku_data dan statistika. Berkas PDF-nya publik (disimpan di assets/dokumen/unggahan/, di-gitignore
 * supaya bertahan di setiap deploy); tabel ini hanya katalognya. Tabel baru, tidak mengubah data lain.
 */
class Migration_Bank_data_dokumen extends CI_Migration {
    public function up() {
        if ( ! $this->db->table_exists('sf_bank_data_dokumen')) {
            $this->dbforge->add_field([
                'id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'jenis'         => ['type' => 'VARCHAR', 'constraint' => 20],
                'judul'         => ['type' => 'VARCHAR', 'constraint' => 150],
                'deskripsi'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
                'berkas'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'ukuran'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'default' => 0],
                'aktif'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'urutan'        => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => TRUE, 'default' => 0],
                'diunggah_oleh' => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE],
                'created_at'    => ['type' => 'DATETIME', 'null' => TRUE],
                'updated_at'    => ['type' => 'DATETIME', 'null' => TRUE],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key(['jenis', 'aktif', 'urutan']);
            $this->dbforge->create_table('sf_bank_data_dokumen', TRUE, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
        }
        if ( ! $this->db->table_exists('sf_bank_data_dokumen')) {
            throw new RuntimeException('Tabel sf_bank_data_dokumen belum terbentuk.');
        }
    }

    public function down() {
        if ($this->db->table_exists('sf_bank_data_dokumen')) {
            if ($this->db->count_all('sf_bank_data_dokumen') > 0) {
                throw new RuntimeException('Rollback ditolak: dokumen Bank Data sudah terisi.');
            }
            $this->dbforge->drop_table('sf_bank_data_dokumen', TRUE);
        }
    }
}
