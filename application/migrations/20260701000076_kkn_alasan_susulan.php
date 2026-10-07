<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * KKN input susulan (keputusan user 7 Okt 2026, menggantikan aturan 29 Sep yang menolak KKN berperiode
 * lewat). Universitas boleh mengajukan KKN yang seluruh periodenya sudah lewat (paling lama setahun ke
 * belakang) asalkan mengisi alasan; alasannya disimpan di sini dan ditampilkan ke admin dengan label
 * Susulan. NULL = pengajuan biasa. Lihat KemitraanPortal::kkn_tambah().
 */
class Migration_Kkn_alasan_susulan extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('alasan_susulan', 'kkn_magang_pendaftaran')) {
            $this->dbforge->add_column('kkn_magang_pendaftaran', [
                'alasan_susulan' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => TRUE, 'after' => 'periode_selesai'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('alasan_susulan', 'kkn_magang_pendaftaran')) {
            $this->dbforge->drop_column('kkn_magang_pendaftaran', 'alasan_susulan');
        }
    }
}
