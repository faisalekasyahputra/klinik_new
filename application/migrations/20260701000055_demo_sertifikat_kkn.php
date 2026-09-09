<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dua peserta demo lintas universitas untuk uji cetak sertifikat KKN.
 *
 * Data dibuat idempoten dan diberi penanda khusus. Pendaftaran menggunakan
 * akun universitas demo yang sudah tersedia, tetapi nama kampus pada setiap
 * batch berbeda agar hasil sertifikat dapat diuji lintas universitas.
 */
class Migration_Demo_sertifikat_kkn extends CI_Migration {

    private const PENANDA = 'DEMO-SERTIFIKAT-KKN-';
    private const PESERTA = [
        [
            'nim' => '21120226000123',
            'nama' => 'Andi Pratama',
            'universitas' => 'Universitas Diponegoro',
            'tema' => 'DEMO-SERTIFIKAT-KKN-UNDIP',
        ],
        [
            'nim' => '530142600012',
            'nama' => 'Siti Rahmawati',
            'universitas' => 'Universitas Negeri Semarang',
            'tema' => 'DEMO-SERTIFIKAT-KKN-UNNES',
        ],
    ];

    public function up()
    {
        if ( ! $this->db->table_exists('usr_users')
            || ! $this->db->table_exists('kkn_magang_pendaftaran')
            || ! $this->db->table_exists('kkn_peserta')) {
            log_message('error', 'Migrasi 055: tabel akun/KKN belum tersedia.');
            return;
        }

        $akun = $this->db->select('id')
            ->where(['email' => 'universitas@example.com', 'role' => 'universitas'])
            ->get('usr_users')->row_array();
        if ( ! $akun) {
            $akun = $this->db->select('id')->where('role', 'universitas')
                ->order_by('id', 'ASC')->limit(1)->get('usr_users')->row_array();
        }
        if ( ! $akun) {
            log_message('error', 'Migrasi 055: akun universitas demo tidak tersedia; data demo tidak dibuat.');
            return;
        }

        $this->db->trans_start();
        foreach (self::PESERTA as $demo) {
            $induk = $this->db->select('id')->where([
                'jenis' => 'kkn',
                'divisi_atau_tema' => $demo['tema'],
            ])->get('kkn_magang_pendaftaran')->row_array();

            if ( ! $induk) {
                $this->db->insert('kkn_magang_pendaftaran', [
                    'user_id' => (int) $akun['id'],
                    'jenis' => 'kkn',
                    'instansi_asal' => $demo['universitas'],
                    'no_hp' => '081234567890',
                    'divisi_atau_tema' => $demo['tema'],
                    'periode_mulai' => '2025-01-10',
                    'periode_selesai' => '2025-02-20',
                    'status' => 'Diterima',
                    'catatan_admin' => 'Data demo untuk pengujian cetak sertifikat.',
                ]);
                $pendaftaran_id = (int) $this->db->insert_id();
            } else {
                $pendaftaran_id = (int) $induk['id'];
                $this->db->where('id', $pendaftaran_id)->update('kkn_magang_pendaftaran', [
                    'instansi_asal' => $demo['universitas'],
                    'periode_mulai' => '2025-01-10',
                    'periode_selesai' => '2025-02-20',
                    'status' => 'Diterima',
                ]);
            }

            $ada = $this->db->where([
                'pendaftaran_id' => $pendaftaran_id,
                'nim' => $demo['nim'],
            ])->count_all_results('kkn_peserta');
            if ( ! $ada) {
                $this->db->insert('kkn_peserta', [
                    'pendaftaran_id' => $pendaftaran_id,
                    'nim' => $demo['nim'],
                    'nama' => $demo['nama'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Migrasi 055: gagal membuat data demo sertifikat KKN.');
        }
    }

    public function down()
    {
        if ( ! $this->db->table_exists('kkn_magang_pendaftaran')) { return; }

        $ids = $this->db->select('id')->like('divisi_atau_tema', self::PENANDA, 'after')
            ->get('kkn_magang_pendaftaran')->result_array();
        $ids = array_column($ids, 'id');
        if ($ids && $this->db->table_exists('kkn_peserta')) {
            $this->db->where_in('pendaftaran_id', $ids)->delete('kkn_peserta');
        }
        if ($ids) {
            $this->db->where_in('id', $ids)->delete('kkn_magang_pendaftaran');
        }
    }
}