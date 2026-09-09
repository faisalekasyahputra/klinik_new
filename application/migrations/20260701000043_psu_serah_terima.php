<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serah Terima PSU (Prasarana, Sarana, Utilitas) perumahan - butir "aktifkan
 * kartu PSU" permintaan user 14 Agt 2026. Kartu ini di beranda sudah lama
 * berlabel "Segera Hadir" (pages/home/awal.php) tanpa halaman di baliknya.
 *
 * KOLOM DIRANCANG, BUKAN DITEBAK ULANG:
 *
 * - `nama_pengembang` TEKS BEBAS, BUKAN dropdown wajib ke
 *   srp2_certified_developers - keputusan sadar, bukan malas. Menautkannya
 *   sebagai FK WAJIB berarti proyek PSU yang pengembangnya belum/tidak
 *   bersertifikat SRP2 (proyek lama, atau sedang dalam proses sertifikasi)
 *   tidak bisa dicatat sama sekali. `pengembang_id` tetap ADA sebagai
 *   pranala OPSIONAL (boleh NULL) untuk yang memang sudah terdaftar di
 *   direktori SRP2 - dua dunia data yang tidak saling memaksa.
 * - `asosiasi` disimpan sebagai KODE (kolom sama gaya dengan
 *   srp2_certified_developers.asosiasi/srp2_registrations.asosiasi),
 *   divalidasi ke `srp2_asosiasi` yang sudah ada (migrasi 042) - SATU daftar
 *   asosiasi untuk seluruh aplikasi, bukan daftar kedua yang bisa menyimpang.
 * - `status_serah_terima` TIGA TAHAP UMUM (Belum Diserahkan/Proses
 *   Verifikasi/Sudah Diserahkan) - istilah umum, BUKAN kutipan regulasi
 *   tertentu (mis. Permendagri 9/2009 punya tahapan lebih rinci). Sengaja
 *   digeneralkan sampai dinas mengirim rumusan resminya sendiri - pola yang
 *   SAMA dipakai `pages/perumahan/cari_rumah.php` untuk definisi
 *   subsidi/non-subsidi dan `srp2_certified_developers.status_sertifikasi`.
 *   Kalau dinas mengirim tahapan resmi, ganti nilai ENUM di sini + label di
 *   Admin_Psu.php (satu tempat, `STATUS_SERAH_TERIMA`).
 *
 * NOL SEED DATA STATUS/HANDOVER SUNGGUHAN. Migrasi ini cuma membuat
 * tabelnya. Data dummy (kalau diminta) ditulis TERPISAH lewat CRUD
 * Admin_Psu, dengan nama proyek/pengembang FIKTIF yang jelas ditandai
 * dummy - BUKAN memasang status serah terima (klaim kepatuhan, berpotensi
 * mencemarkan nama) pada salah satu dari 67 pengembang sungguhan di
 * direktori SRP2. Pelajaran langsung dari percobaan data dummy kolom
 * Asosiasi sebelumnya (session yang sama) - user menghentikan proses itu
 * begitu konsekuensinya terlihat.
 */
class Migration_Psu_serah_terima extends CI_Migration {

    const TABEL = 'psu_serah_terima';

    public function up()
    {
        if ($this->db->table_exists(self::TABEL)) {
            log_message('debug', 'Migrasi 043: ' . self::TABEL . ' sudah ada, dilewati.');
            return;
        }

        $this->dbforge->add_field([
            'id'               => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'nama_perumahan'   => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => FALSE],
            'nama_pengembang'  => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => FALSE],
            // Pranala opsional ke direktori SRP2 - lihat komentar kepala berkas.
            'pengembang_id'    => ['type' => 'INT', 'unsigned' => TRUE, 'null' => TRUE],
            // Collation-nya DISELARASKAN ke srp2_asosiasi.kode LEWAT ALTER
            // TERPISAH di bawah, BUKAN lewat kunci 'collation' di sini -
            // CI3 dbforge TIDAK MENGENAL kunci itu (diperiksa langsung ke
            // system/database/), diam-diam diabaikan tanpa galat kalau
            // dipakai. Ketahuan justru dari akibatnya: kolom ini sempat
            // jadi utf8mb4_general_ci (ikut default tabel) padahal
            // srp2_asosiasi.kode yang akan di-JOIN adalah unicode_ci -
            // errno 1267 yang SAMA seperti yang sudah diperbaiki di
            // migrasi 042. Jangan ulangi kesalahan yang sama di sini.
            'asosiasi'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => TRUE],
            'kabupaten_id'     => ['type' => 'INT', 'unsigned' => TRUE, 'null' => TRUE],
            'status_serah_terima' => [
                'type' => 'ENUM', 'constraint' => ['belum_diserahkan', 'proses_verifikasi', 'sudah_diserahkan'],
                'null' => FALSE, 'default' => 'belum_diserahkan',
            ],
            'tanggal_serah_terima' => ['type' => 'DATE', 'null' => TRUE,
                                        'comment' => 'Diisi kalau status sudah_diserahkan'],
            'keterangan'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
            'status_aktif'     => ['type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 1,
                                    'comment' => '0 = disembunyikan dari halaman publik'],
            'created_at'       => ['type' => 'DATETIME', 'null' => TRUE],
            'updated_at'       => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('kabupaten_id');
        $this->dbforge->add_key('pengembang_id');
        $this->dbforge->create_table(self::TABEL, TRUE, [
            'ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci',
        ]);

        /* `asosiasi` diselaraskan ke collation srp2_asosiasi.kode SETELAH
           tabel dibuat - lihat komentar di definisi kolomnya di atas. */
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL . '` MODIFY `asosiasi`
             VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL'
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 043: MODIFY collation kolom asosiasi GAGAL.');
        }

        /* FK OPSIONAL (nullable), ON DELETE SET NULL - menghapus baris
           direktori SRP2 atau kabupaten TIDAK BOLEH menghapus riwayat PSU,
           cukup melepas pranalanya. `db_debug` mati di production; ALTER
           yang gagal dicatat, bukan dipercaya diam-diam (pola sama dgn
           migrasi 038/042). */
        foreach ([
            'fk_psu_pengembang' => "ALTER TABLE `" . self::TABEL . "`
                ADD CONSTRAINT `fk_psu_pengembang` FOREIGN KEY (`pengembang_id`)
                REFERENCES `srp2_certified_developers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE",
            'fk_psu_kabupaten' => "ALTER TABLE `" . self::TABEL . "`
                ADD CONSTRAINT `fk_psu_kabupaten` FOREIGN KEY (`kabupaten_id`)
                REFERENCES `kabupaten` (`id`) ON DELETE SET NULL ON UPDATE CASCADE",
        ] as $nama_fk => $sql) {
            if ($this->db->query($sql) === FALSE) {
                log_message('error', 'Migrasi 043: FK ' . $nama_fk . ' GAGAL dipasang.');
            }
        }
    }

    public function down()
    {
        if ($this->db->table_exists(self::TABEL)) {
            $this->dbforge->drop_table(self::TABEL, TRUE);
        }
    }
}
