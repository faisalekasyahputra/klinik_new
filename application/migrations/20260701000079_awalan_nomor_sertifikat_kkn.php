<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Awalan nomor sertifikat per KKN dan nomor urut yang menempel di peserta (permintaan dinas 7 Okt 2026,
 * menyusul migrasi 078): "600.2/69 ditentukan admin per periode, dua digit terakhir otomatis menempel di
 * peserta dan auto increment".
 *
 * - kkn_magang_pendaftaran.awalan_nomor_sertifikat: NULL = nomor otomatis lama (600.2/69. + kkn_peserta.id).
 *   Terisi = nomor otomatis peserta KKN ini menjadi <awalan>.<urut dua digit>.
 * - kkn_peserta.urut: nomor urut peserta di dalam KKN-nya, diberikan saat masuk daftar (MY_Controller::
 *   ganti_roster_kkn) dan tidak pernah dipakai ulang, jadi tidak bergeser bila peserta lain dihapus.
 *   Diisi untuk baris lama menurut urutan id.
 * Aturannya di MY_Controller::nomor_sertifikat_kkn. Nomor manual per peserta (kkn_peserta.nomor_sertifikat, migrasi 078)
 * tidak dipakai lagi sejak nomor diatur per periode (keputusan user 7 Okt 2026); kolomnya dibiarkan.
 */
class Migration_Awalan_nomor_sertifikat_kkn extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('awalan_nomor_sertifikat', 'kkn_magang_pendaftaran')) {
            $this->dbforge->add_column('kkn_magang_pendaftaran', [
                'awalan_nomor_sertifikat' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => TRUE, 'after' => 'tanggal_sertifikat'],
            ]);
        }
        if ( ! $this->db->field_exists('urut', 'kkn_peserta')) {
            $this->dbforge->add_column('kkn_peserta', [
                'urut' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'null' => TRUE, 'after' => 'nama'],
            ]);
            $urut = [];
            foreach ($this->db->select('id, pendaftaran_id')->order_by('pendaftaran_id', 'ASC')->order_by('id', 'ASC')->get('kkn_peserta')->result() as $p) {
                $urut[$p->pendaftaran_id] = ($urut[$p->pendaftaran_id] ?? 0) + 1;
                $this->db->where('id', (int) $p->id)->update('kkn_peserta', ['urut' => $urut[$p->pendaftaran_id]]);
            }
        }
    }

    public function down()
    {
        if ($this->db->field_exists('urut', 'kkn_peserta')) {
            $this->dbforge->drop_column('kkn_peserta', 'urut');
        }
        if ($this->db->field_exists('awalan_nomor_sertifikat', 'kkn_magang_pendaftaran')) {
            $this->dbforge->drop_column('kkn_magang_pendaftaran', 'awalan_nomor_sertifikat');
        }
    }
}
