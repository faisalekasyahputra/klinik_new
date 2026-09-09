<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Slot magang: dari "bulan penuh" menjadi rentang tanggal di dalam bulan.
 *
 * Bentuk lama hanya bisa mengatakan "Juni dibuka". Kenyataannya divisi sering
 * membuka setengah bulan - 1 sampai 15 Juni saja - dan itu tidak punya tempat
 * untuk dinyatakan. Akibatnya admin menutup seluruh Juni atau membukanya penuh,
 * lalu menyaring manual di meja peninjauan. Persis pekerjaan yang seharusnya
 * dihapus oleh slot.
 *
 * Kedua kolom NOT NULL, dan itu disengaja. Sempat terpikir membiarkannya NULL
 * dengan arti "sebulan penuh", tapi itu menaruh dua bentuk data untuk satu
 * keadaan, dan setiap pembaca harus mengingat cabangnya. Baris lama diisi
 * dengan batas bulannya masing-masing lebih dulu, jadi maknanya persis sama
 * dengan sebelum migrasi - hanya dinyatakan secara eksplisit.
 *
 * Invarian yang dijaga penulisnya (Kemitraan_slot_model::tulis_ulang_divisi):
 * rentang selalu berada DI DALAM bulan barisnya. Slot lintas bulan dinyatakan
 * sebagai beberapa baris, satu per bulan - itulah yang membuat layar "atur per
 * bulan" bisa berdiri.
 */
class Migration_Kemitraan_slot_rentang_tanggal extends CI_Migration {

    const TABEL = 'kkn_magang_slot';

    public function up()
    {
        if ($this->db->field_exists('tgl_mulai', self::TABEL)) { return; }

        $this->db->query("ALTER TABLE `" . self::TABEL . "`
            ADD COLUMN `tgl_mulai` DATE NULL AFTER `bulan`,
            ADD COLUMN `tgl_selesai` DATE NULL AFTER `tgl_mulai`");

        // Baris lama berarti "sebulan penuh" - dituliskan apa adanya sebelum
        // kolomnya dijadikan wajib. LAST_DAY() mengurus Februari dan kabisat,
        // yang kalau dihitung sendiri pasti salah setidaknya sekali.
        $this->db->query("UPDATE `" . self::TABEL . "` SET
            `tgl_mulai`   = DATE(CONCAT(`tahun`, '-', LPAD(`bulan`, 2, '0'), '-01')),
            `tgl_selesai` = LAST_DAY(DATE(CONCAT(`tahun`, '-', LPAD(`bulan`, 2, '0'), '-01')))
            WHERE `tgl_mulai` IS NULL");

        $this->db->query("ALTER TABLE `" . self::TABEL . "`
            MODIFY `tgl_mulai` DATE NOT NULL,
            MODIFY `tgl_selesai` DATE NOT NULL");
    }

    public function down()
    {
        if ( ! $this->db->field_exists('tgl_mulai', self::TABEL)) { return; }
        $this->db->query("ALTER TABLE `" . self::TABEL . "`
            DROP COLUMN `tgl_mulai`, DROP COLUMN `tgl_selesai`");
    }
}
