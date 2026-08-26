<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menyelaraskan collation SEMUA kolom `asosiasi` yang isinya kode ke
 * `srp2_asosiasi.kode` (migrasi 042) - kolom rujukannya.
 *
 * PENYEBAB. Migrasi 043 sudah SADAR soal ini dan tetap salah: ia menuliskan
 * `COLLATE utf8mb4_unicode_ci` sebagai KONSTANTA, dengan komentar yang
 * menyebut itu "diselaraskan ke srp2_asosiasi.kode". Nyatanya
 * `srp2_asosiasi.kode` lahir `utf8mb4_general_ci` (ikut default tabel di
 * migrasi 042), jadi ALTER 043 itu justru MENJAUHKAN kolomnya dari acuan.
 * Berhasil tanpa galat, karena MODIFY collation memang selalu berhasil -
 * yang patah baru muncul di JOIN pertama, sebagai errno 1267.
 *
 * Ini pengulangan persis pelajaran migrasi 032: collation acuan itu DIBACA
 * dari information_schema, BUKAN ditebak. 032 melakukannya dengan benar,
 * 043 kembali menebak. Karena itu migrasi ini membaca acuannya, dan tidak
 * menuliskan satu pun nama collation di dalam SQL-nya.
 *
 * CAKUPAN - dua kolom, bukan satu. `Migrate::status()` cuma menyorot
 * `psu_serah_terima.asosiasi`, tapi `srp2_certified_developers.asosiasi`
 * meleset dengan cara yang sama persis dan menuju tabel acuan yang sama.
 * Menambal satu saja berarti menyisakan saudaranya untuk patah nanti.
 * `srp2_registrations.asosiasi` TIDAK disebut di sini karena sudah selaras.
 *
 * TIDAK IKUT: `data_sosmed_perumahan.asosiasi`. Kolom itu `utf8`
 * (bukan `utf8mb4`) peninggalan skema lama, tidak divalidasi ke
 * `srp2_asosiasi`, dan tidak pernah di-JOIN ke sana. Mengonversi charset-nya
 * urusan lain, bukan urusan migrasi ini.
 *
 * AMAN untuk data: yang berubah cuma aturan pembandingan, bukan isinya.
 * Panjang, nullability, default, dan komentar kolom dibaca ulang dari
 * information_schema lalu ditulis kembali apa adanya - MODIFY yang tidak
 * menyebutkan komentar akan MENGHAPUS komentar kolomnya, dan
 * `srp2_certified_developers.asosiasi` punya komentar yang menjelaskan
 * asal-usulnya.
 */
class Migration_Selaraskan_kolasi_asosiasi extends CI_Migration {

    /** Kolom-kolom yang isinya kode asosiasi dan harus sepadan dengan acuan. */
    const KOLOM = [
        ['psu_serah_terima', 'asosiasi'],
        ['srp2_certified_developers', 'asosiasi'],
    ];

    public function up()
    {
        $acuan = $this->meta('srp2_asosiasi', 'kode');

        /* Tanpa acuan yang terbaca, JANGAN menebak - menuliskan nama
           collation di sini persis kesalahan 043 yang sedang diperbaiki. */
        if ($acuan === NULL || $acuan->COLLATION_NAME === NULL) {
            log_message('error', 'Migrasi 051: collation srp2_asosiasi.kode tidak terbaca, dilewati.');
            return;
        }

        foreach (self::KOLOM as list($tabel, $kolom)) {
            $meta = $this->meta($tabel, $kolom);
            if ($meta === NULL) {
                log_message('debug', "Migrasi 051: {$tabel}.{$kolom} tidak ada, dilewati.");
                continue;
            }
            if ($meta->COLLATION_NAME === $acuan->COLLATION_NAME) {
                continue; // sudah selaras, mis. DB yang lahir setelah perbaikan ini
            }

            $sql = "ALTER TABLE `{$tabel}` MODIFY `{$kolom}` {$meta->COLUMN_TYPE}"
                 . ' CHARACTER SET ' . $acuan->CHARACTER_SET_NAME
                 . ' COLLATE ' . $acuan->COLLATION_NAME
                 . ($meta->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL')
                 . ($meta->COLUMN_DEFAULT === NULL ? '' : ' DEFAULT ' . $this->db->escape($meta->COLUMN_DEFAULT))
                 . ($meta->COLUMN_COMMENT === '' ? '' : ' COMMENT ' . $this->db->escape($meta->COLUMN_COMMENT));

            /* db_debug mati di production: ALTER yang gagal dicatat, bukan
               dipercaya diam-diam (pola sama dgn migrasi 038/042/043). */
            if ($this->db->query($sql) === FALSE) {
                log_message('error', "Migrasi 051: MODIFY collation {$tabel}.{$kolom} GAGAL.");
            }
        }
    }

    private function meta($tabel, $kolom)
    {
        return $this->db->query(
            'SELECT COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME, IS_NULLABLE,
                    COLUMN_DEFAULT, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$tabel, $kolom]
        )->row();
    }

    public function down()
    {
        // Tidak ada yang dikembalikan. Keadaan sebelumnya adalah dua kolom
        // yang collation-nya salah; memulihkannya cuma memasang kembali bug
        // yang sama. Bandingkan migrasi 032, yang beralasan identik.
    }
}
