<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Identitas mahasiswa + berkas proposal pada pendaftaran KKN/Magang.
 *
 * Formulir lama hanya menyimpan instansi asal, no. HP, divisi/tema, periode,
 * dan satu berkas surat pengantar. Mockup pendaftaran meminta NIM, tempat &
 * tanggal lahir, semester, jurusan, serta proposal - enam hal yang tidak punya
 * tempat sama sekali di tabel ini.
 *
 * SEMUA kolom NULL, dan itu disengaja: tabel ini sudah berisi pendaftaran lama
 * yang tidak pernah ditanyai keenam hal itu. Memaksa NOT NULL berarti mengarang
 * nilai untuk baris yang sudah ada. Kewajiban diisi ditegakkan di validasi
 * formulir untuk pendaftaran BARU, bukan di skema - itu tempat yang benar untuk
 * aturan yang berlaku sejak hari ini, bukan surut ke belakang.
 *
 * `tempat_lahir` dan `tanggal_lahir` sengaja DUA kolom, bukan satu "TTL". Di
 * mockup ia satu baris, tapi menyimpan tanggal sebagai teks bebas membuat
 * urutan dan hitungan usia mustahil tanpa membongkar ulang datanya nanti.
 * Formulirnya tetap menampilkan keduanya berdampingan dalam satu baris.
 */
class Migration_Kemitraan_identitas_mahasiswa extends CI_Migration
{
    const TABEL = 'kkn_magang_pendaftaran';

    /** kolom => definisi, urut sesuai tampilan formulir. */
    private $kolom = [
        'nim'           => "VARCHAR(30) NULL COMMENT 'Nomor induk mahasiswa' AFTER `user_id`",
        'tempat_lahir'  => "VARCHAR(100) NULL AFTER `nim`",
        'tanggal_lahir' => "DATE NULL AFTER `tempat_lahir`",
        'semester'      => "TINYINT UNSIGNED NULL COMMENT 'Semester saat mendaftar' AFTER `tanggal_lahir`",
        'jurusan'       => "VARCHAR(150) NULL AFTER `semester`",
        'file_proposal' => "VARCHAR(255) NULL COMMENT 'Khusus magang; disimpan di private_uploads/kemitraan/{id}/' AFTER `file_surat_pengantar`",
    ];

    public function up()
    {
        foreach ($this->kolom as $nama => $definisi) {
            // Idempoten: migrasi ini pernah dijalankan sebagian kalau ALTER
            // gagal di tengah, dan di production `db_debug` mati sehingga
            // kegagalan itu tidak melempar apa pun.
            if ($this->db->field_exists($nama, self::TABEL)) { continue; }
            $this->db->query('ALTER TABLE `' . self::TABEL . '` ADD COLUMN `' . $nama . '` ' . $definisi);
        }
    }

    public function down()
    {
        // Menurunkan ini MEMBUANG isi keenam kolom. Tidak ditahan seperti
        // migrasi 024 karena di sini tidak ada bentuk yang berubah - kolom
        // lama tidak disentuh, jadi turun-naik tidak merusak data yang sudah
        // ada sebelumnya. Yang hilang hanya yang diisi setelah migrasi ini.
        foreach (array_reverse(array_keys($this->kolom)) as $nama) {
            if ( ! $this->db->field_exists($nama, self::TABEL)) { continue; }
            $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP COLUMN `' . $nama . '`');
        }
    }
}
