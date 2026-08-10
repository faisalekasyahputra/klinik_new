<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SRP2: status bertingkat, kabupaten, asosiasi, NPWP — butir 7, 8, 12 putaran 2.
 *
 * SATU MIGRASI UNTUK TIGA BUTIR, dan itu disengaja: ketiganya menyentuh tabel
 * yang sama. Memisahnya berarti tiga kali ALTER pada tabel yang sama untuk satu
 * rangkaian permintaan yang datang bersamaan.
 *
 * ── status_sertifikasi ───────────────────────────────────────────────────────
 * Empat tahap sesuai permintaan dinas. DEFAULT `bersertifikat`, dan itu BUKAN
 * tebakan: tabel ini bernama `srp2_certified_developers` dan layarnya berjudul
 * "Direktori Pengembang Bersertifikat" — setiap baris di dalamnya menurut
 * definisinya memang sudah bersertifikat. Menaruh default lain justru akan
 * berbohong tentang 68 baris yang sudah ada.
 *
 * `belum_mendaftar` sengaja disediakan meski tidak akan terpakai hari ini:
 * pengembang yang belum mendaftar belum punya baris di sini sama sekali. Ia ada
 * untuk saat dinas kelak memasukkan daftar pengembang yang dipantau tetapi
 * belum mengajukan. Pertanyaan itu sudah kami sampaikan ke dinas.
 *
 * ── TIDAK ADA kolom "aktif/non-aktif dari masa berlaku" ──────────────────────
 * Dinas minta penanda aktif bila sertifikat masih berlaku dan non-aktif bila
 * sudah lewat. Itu DITURUNKAN dari `sertifikat_berakhir`, bukan disimpan.
 * Penanda yang disimpan harus diperbarui oleh seseorang setiap hari; yang tidak
 * diperbarui akan menyatakan "aktif" untuk sertifikat yang kedaluwarsa kemarin,
 * dan tidak ada satu pun galat yang muncul. Diturunkan berarti selalu benar.
 *
 * `status_aktif` yang lama TIDAK diganggu — ia hal berbeda: sakelar manual admin
 * untuk menayangkan/menyembunyikan dari direktori publik.
 *
 * ── NPWP: dienkripsi + sidik pencarian ───────────────────────────────────────
 * Butir 8 menjadikan NPWP kunci identitas pengembang, dan butir 7 meminta hanya
 * admin yang bisa melihatnya. Keduanya mengarah ke perlakuan yang SAMA PERSIS
 * dengan NIK warga di sistem ini — polanya sudah ada, jadi tidak dibuat baru:
 *
 *   `npwp_ciphertext`  — nilai aslinya, terenkripsi
 *   `npwp_lookup_hash` — sidik deterministik ber-pepper, UNIQUE
 *
 * UNIQUE-nya yang menegakkan butir 8: satu NPWP hanya boleh dipakai satu baris,
 * sehingga perusahaan yang sama tidak bisa terdaftar dua kali dengan ejaan nama
 * berbeda. NULL dibiarkan berulang — MySQL memang tidak menganggap dua NULL
 * kembar — dan itu yang membuat 68 baris lama tetap sah selama NPWP-nya belum
 * diisi.
 *
 * ADITIF MURNI selain default status. Kode lama tetap jalan sesudahnya.
 */
class Migration_Srp2_status_npwp extends CI_Migration {

    const TABEL = 'srp2_certified_developers';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 040: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }

        $tambah = [];

        if ( ! $this->db->field_exists('status_sertifikasi', self::TABEL)) {
            $tambah['status_sertifikasi'] = [
                'type' => "ENUM('belum_mendaftar','mendaftar','masih_proses','bersertifikat')",
                'null' => FALSE, 'default' => 'bersertifikat',
                'comment' => 'Butir 7; baris lama memang sudah bersertifikat',
            ];
        }
        if ( ! $this->db->field_exists('kabupaten_id', self::TABEL)) {
            $tambah['kabupaten_id'] = [
                'type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'null' => TRUE,
                'comment' => 'Butir 7; NULL = belum tercatat',
            ];
        }
        if ( ! $this->db->field_exists('asosiasi', self::TABEL)) {
            $tambah['asosiasi'] = [
                'type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE,
                'comment' => 'Butir 12; ketik bebas sampai dinas mengirim daftar resmi',
            ];
        }
        if ( ! $this->db->field_exists('npwp_ciphertext', self::TABEL)) {
            $tambah['npwp_ciphertext'] = [
                'type' => 'TEXT', 'null' => TRUE,
                'comment' => 'Butir 8; terenkripsi, hanya admin yang boleh melihat',
            ];
        }
        if ( ! $this->db->field_exists('npwp_lookup_hash', self::TABEL)) {
            $tambah['npwp_lookup_hash'] = [
                'type' => 'CHAR', 'constraint' => 64, 'null' => TRUE,
                'comment' => 'Butir 8; sidik deterministik, UNIQUE — satu NPWP satu baris',
            ];
        }

        if ($tambah) {
            $this->dbforge->add_column(self::TABEL, $tambah);
        }

        /* UNIQUE dipasang terpisah dan kegagalannya DICATAT. `db_debug` mati di
           production: ALTER yang gagal tidak bersuara sementara migrasinya tetap
           tercatat sukses (riwayat 031). Tanpa UNIQUE, butir 8 tidak ditegakkan
           apa pun — dan itu justru inti permintaannya. Bentuknya diverifikasi
           `Migrate::status()`, bukan dipercaya dari sini. */
        $sudah = $this->db->query(
            "SELECT COUNT(*) n FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                AND INDEX_NAME = 'uq_srp2_npwp'", [self::TABEL]
        )->row('n');

        if ( ! (int) $sudah) {
            $ok = $this->db->query(
                'ALTER TABLE `' . self::TABEL . '` ADD UNIQUE KEY `uq_srp2_npwp` (`npwp_lookup_hash`)'
            );
            if ($ok === FALSE) {
                log_message('error', 'Migrasi 040: UNIQUE uq_srp2_npwp GAGAL dipasang.');
            }
        }
    }

    public function down()
    {
        if ( ! $this->db->table_exists(self::TABEL)) { return; }

        $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP INDEX `uq_srp2_npwp`');
        foreach (['status_sertifikasi', 'kabupaten_id', 'asosiasi', 'npwp_ciphertext', 'npwp_lookup_hash'] as $k) {
            if ($this->db->field_exists($k, self::TABEL)) {
                $this->dbforge->drop_column(self::TABEL, $k);
            }
        }
    }
}
