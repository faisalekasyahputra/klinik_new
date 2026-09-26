<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cermin data SIMPERUM di basis data kita (keputusan pemilik produk 26 Sep 2026): satu baris per NIK
 * warga yang TERDAFTAR di web ini, berbentuk sama dengan rekaman GetDataRTLH (kode mentah dinas,
 * snake_case). Hanya diisi dari hasil GET; SIMPERUM tidak pernah ditulisi. Identitas (NIK, nama,
 * alamat, koordinat) terenkripsi Encryption_lib; kode mentah bukan data pribadi dan disimpan polos.
 * nik_lookup_hash = Encryption_lib::deterministic_hash(), kunci hitung warga tercocokkan.
 *
 * user_id CASCADE: hanya NIK terdaftar yang boleh ada di sini, jadi akun hilang = barisnya hilang.
 * snapshot_id SENGAJA tanpa FK: penyapu retensi dan suite menghapus sf_rekaman_simperum langsung.
 */
class Migration_Data_simperum extends CI_Migration {

    /** Kolom kode mentah SIMPERUM, urutan mengikuti respons GetDataRTLH. */
    private $kode = [
        'tahun_intervensi', 'sumber_dana_id', 'atap_id', 'lantai_id', 'dinding_id',
        'kondisi_atap', 'kondisi_lantai', 'kondisi_dinding', 'jenis_kelamin', 'tahun_lahir',
        'pendidikan', 'pekerjaan', 'penghasilan', 'bantuan_perumahan', 'kawasan_perumahan',
        'kepemilikan_lahan', 'kepemilikan_rumah', 'tanah_lain', 'rumah_lain', 'luas_rumah',
        'jml_penghuni', 'jml_kk', 'ada_pondasi', 'kondisi_kolom', 'kondisi_balok', 'kondisi_rangka',
        'ada_jendela', 'ada_ventilasi', 'sumber_air', 'penerangan', 'letak_sanitasi', 'kamar_mandi',
        'jarak_septic_tank', 'mampu_swadaya',
    ];

    public function up() {
        if ( ! $this->db->table_exists('sf_data_simperum')) {
            // user_id INT(11) bertanda: usr_users.id bertanda, FK gagal kalau unsigned (catatan migrasi 011/012).
            $kolom = [
                'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE],
                'user_id'            => ['type' => 'INT', 'constraint' => 11],
                'nik_lookup_hash'    => ['type' => 'CHAR', 'constraint' => 64],
                'nik_ciphertext'     => ['type' => 'TEXT', 'null' => TRUE],
                'nama_ciphertext'    => ['type' => 'TEXT', 'null' => TRUE],
                'alamat_ciphertext'  => ['type' => 'TEXT', 'null' => TRUE],
                'geo_lat_ciphertext' => ['type' => 'TEXT', 'null' => TRUE],
                'geo_lng_ciphertext' => ['type' => 'TEXT', 'null' => TRUE],
                'idbdt'              => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => TRUE],
                'kode_dagri'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE],
                'kabupaten_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE],
            ];
            foreach ($this->kode as $nama) {
                $kolom[$nama] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE];
            }
            $kolom += [
                'response_status' => ['type' => 'VARCHAR', 'constraint' => 20],
                'source_mode'     => ['type' => 'VARCHAR', 'constraint' => 20],
                'snapshot_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE],
                'fetched_at'      => ['type' => 'DATETIME'],
                'next_refresh_at' => ['type' => 'DATETIME'],
                'created_at'      => ['type' => 'DATETIME', 'null' => TRUE],
                'updated_at'      => ['type' => 'DATETIME', 'null' => TRUE],
            ];
            $this->dbforge->add_field($kolom);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->add_key('kabupaten_id');
            $this->dbforge->add_key('next_refresh_at');
            $this->dbforge->add_key('user_id');
            $this->dbforge->create_table('sf_data_simperum', TRUE, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
            $this->db->query('ALTER TABLE sf_data_simperum ADD UNIQUE KEY uq_sf_data_simperum_nik (nik_lookup_hash)');
            $this->db->query('ALTER TABLE sf_data_simperum ADD CONSTRAINT fk_sf_data_simperum_user
                FOREIGN KEY (user_id) REFERENCES usr_users (id) ON DELETE CASCADE');
        }
        if ( ! $this->db->table_exists('sf_data_simperum')) {
            throw new RuntimeException('Tabel sf_data_simperum belum terbentuk.');
        }
    }

    public function down() {
        if ($this->db->table_exists('sf_data_simperum')) {
            if ($this->db->count_all('sf_data_simperum') > 0) {
                throw new RuntimeException('Rollback ditolak: cermin data SIMPERUM sudah terisi.');
            }
            $this->dbforge->drop_table('sf_data_simperum', TRUE);
        }
    }
}
