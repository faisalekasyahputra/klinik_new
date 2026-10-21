<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Identitas mahasiswa pindah ke Profil Saya (permintaan user 21 Okt 2026): NIM, tempat/tanggal lahir,
 * jurusan, dan kampus disimpan di usr_akun supaya formulir daftar magang bisa terisi otomatis dari profil.
 *
 * Kolom pendaftaran (migrasi 025) TIDAK diubah: tiap pendaftaran tetap menyimpan salinannya sendiri,
 * karena yang ditinjau admin adalah data saat mendaftar, bukan isi profil hari ini. Semester sengaja
 * tidak ikut - nilainya berubah tiap enam bulan, jadi tetap ditanyakan per pendaftaran.
 *
 * Disimpan polos seperti di kkn_magang_pendaftaran (bukan dienkripsi seperti NIK warga): kolom ini
 * bukan kunci identitas dan tidak dipakai untuk pencocokan.
 *
 * Baris lama diisi dari pendaftaran magang terakhir tiap akun mahasiswa, supaya yang sudah pernah
 * mendaftar tidak perlu mengetik ulang.
 */
class Migration_Identitas_mahasiswa_di_profil extends CI_Migration {

    private $kolom = [
        'nim'           => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => TRUE],
        'tempat_lahir'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE],
        'tanggal_lahir' => ['type' => 'DATE', 'null' => TRUE],
        'jurusan'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE],
        'instansi_asal' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => TRUE],
    ];

    public function up()
    {
        $setelah = 'no_hp';
        foreach ($this->kolom as $nama => $def) {
            if ( ! $this->db->field_exists($nama, 'usr_akun')) {
                $this->dbforge->add_column('usr_akun', [$nama => $def + ['after' => $setelah]]);
            }
            $setelah = $nama;
        }

        $this->db->query("UPDATE usr_akun u
            JOIN kkn_magang_pendaftaran p ON p.id = (
                SELECT MAX(p2.id) FROM kkn_magang_pendaftaran p2
                WHERE p2.user_id = u.id AND p2.jenis = 'magang')
            SET u.nim = COALESCE(u.nim, p.nim),
                u.tempat_lahir = COALESCE(u.tempat_lahir, p.tempat_lahir),
                u.tanggal_lahir = COALESCE(u.tanggal_lahir, p.tanggal_lahir),
                u.jurusan = COALESCE(u.jurusan, p.jurusan),
                u.instansi_asal = COALESCE(u.instansi_asal, p.instansi_asal)
            WHERE u.peran = 'mahasiswa'");
    }

    public function down()
    {
        foreach (array_keys($this->kolom) as $nama) {
            if ($this->db->field_exists($nama, 'usr_akun')) {
                $this->dbforge->drop_column('usr_akun', $nama);
            }
        }
    }
}
