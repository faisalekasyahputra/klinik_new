<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reset kata sandi mandiri lewat email (keputusan pemilik produk 6 Okt 2026). Tautan sekali pakai
 * berlaku 30 menit; yang disimpan HANYA sidik SHA-256 tokennya, jadi isi tabel yang bocor tidak bisa
 * dipakai membuka akun. Token dikosongkan begitu dipakai atau digantikan permintaan baru.
 * Lihat Auth::kirim_tautan_sandi() dan Auth::simpan_sandi().
 */
class Migration_Token_reset_sandi extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('token_sandi_hash', 'usr_akun')) {
            $this->dbforge->add_column('usr_akun', [
                'token_sandi_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => TRUE, 'after' => 'sandi_kedaluwarsa_at'],
                'token_sandi_kedaluwarsa' => ['type' => 'DATETIME', 'null' => TRUE, 'after' => 'token_sandi_hash'],
            ]);
            $this->db->query('ALTER TABLE usr_akun ADD UNIQUE KEY uq_usr_akun_token_sandi (token_sandi_hash)');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('token_sandi_hash', 'usr_akun')) {
            $this->db->query('ALTER TABLE usr_akun DROP INDEX uq_usr_akun_token_sandi');
            $this->dbforge->drop_column('usr_akun', 'token_sandi_kedaluwarsa');
            $this->dbforge->drop_column('usr_akun', 'token_sandi_hash');
        }
    }
}
