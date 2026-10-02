<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Direktori SRP2 bisa ditautkan ke akun pengembang (keputusan pemilik produk 2 Okt 2026).
 *
 * Sebagian besar dari 68 baris direktori diisi dinas secara manual, bukan lahir dari
 * pengajuan. Dinas akan membuatkan akun supaya perusahaan memperbarui datanya sendiri,
 * jadi baris direktori butuh pemilik (`user_id`) dan medan kontak yang selama ini hanya
 * ada di formulir pengajuan (`nib`, `no_keanggotaan`, `no_whatsapp`) plus foto/logo dan
 * email kontak publik.
 *
 * `user_id` UNIQUE: satu akun memegang paling banyak satu perusahaan, dan Profil
 * Perusahaan menentukan barisnya dari sesi lewat kolom ini (anti-IDOR). FK ON DELETE
 * SET NULL: akun yang dihapus melepas tautannya, baris direktorinya tetap.
 * `usr_users.id` itu INT(11) SIGNED, jadi kolom ini juga signed (jebakan errno 150, §0e).
 *
 * Backfill: baris direktori yang punya pengajuan Diterima ber-user_id langsung
 * tertaut ke pemilik pengajuan itu (pengajuan terbaru bila lebih dari satu), kecuali
 * akun yang sama sudah memegang baris lain.
 */
class Migration_Srp2_direktori_akun extends CI_Migration {

    const TABEL = 'srp2_certified_developers';
    const KOLOM = ['user_id', 'foto_profil', 'nib', 'no_keanggotaan', 'no_whatsapp', 'email_kontak'];

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            throw new RuntimeException('Migrasi 066: tabel ' . self::TABEL . ' tidak ada.');
        }

        $definisi = [
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE, 'comment' => 'Akun pengembang pemilik baris; FK usr_users.id'],
            'foto_profil'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE, 'comment' => 'Path relatif foto/logo publik'],
            'nib'            => ['type' => 'VARCHAR', 'constraint' => 13, 'null' => TRUE],
            'no_keanggotaan' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => TRUE],
            'no_whatsapp'    => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => TRUE, 'comment' => 'Angka saja'],
            'email_kontak'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE],
        ];
        $tambah = [];
        foreach ($definisi as $kolom => $def) {
            if ( ! $this->db->field_exists($kolom, self::TABEL)) { $tambah[$kolom] = $def; }
        }
        if ($tambah) { $this->dbforge->add_column(self::TABEL, $tambah); }

        // Indeks dan FK dipasang terpisah dan kegagalannya MELEMPAR: migrasi yang
        // separuh jalan tetap tercatat sukses kalau dibiarkan senyap (riwayat 031).
        if ( ! $this->ada_indeks('uq_srp2_direktori_user')) {
            $this->wajib('ALTER TABLE `' . self::TABEL . '` ADD UNIQUE KEY `uq_srp2_direktori_user` (`user_id`)');
        }
        if ( ! $this->ada_fk('fk_srp2_direktori_user')) {
            $this->wajib('ALTER TABLE `' . self::TABEL . '` ADD CONSTRAINT `fk_srp2_direktori_user`'
                . ' FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL');
        }

        $pasangan = $this->db->query("SELECT r.certified_developer_id cid, r.user_id uid
            FROM srp2_registrations r JOIN usr_users u ON u.id = r.user_id
            WHERE r.status_verifikasi = 'Diterima' AND r.certified_developer_id IS NOT NULL
            ORDER BY r.id DESC")->result();
        foreach ($pasangan as $p) {
            $sudah = $this->db->where('user_id', (int) $p->uid)->count_all_results(self::TABEL);
            if ($sudah) { continue; }
            $this->db->where('id', (int) $p->cid)->where('user_id IS NULL', NULL, FALSE)
                ->update(self::TABEL, ['user_id' => (int) $p->uid]);
        }
    }

    public function down()
    {
        if ($this->ada_fk('fk_srp2_direktori_user')) {
            $this->wajib('ALTER TABLE `' . self::TABEL . '` DROP FOREIGN KEY `fk_srp2_direktori_user`');
        }
        if ($this->ada_indeks('uq_srp2_direktori_user')) {
            $this->wajib('ALTER TABLE `' . self::TABEL . '` DROP INDEX `uq_srp2_direktori_user`');
        }
        foreach (self::KOLOM as $kolom) {
            if ($this->db->field_exists($kolom, self::TABEL)) { $this->dbforge->drop_column(self::TABEL, $kolom); }
        }
    }

    private function ada_indeks($nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [self::TABEL, $nama])->row('n') > 0;
    }

    private function ada_fk($nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'", [self::TABEL, $nama])->row('n') > 0;
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 066 gagal: ' . $sql);
        }
    }
}
