<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Janji temu konsultasi - mekanisme di balik janji yang sudah dipajang.
 *
 * Revisi dinas 3 Agt 2026, butir 6. Halaman `Umum/forum` berjudul "Konsultasi
 * TERJADWAL", memajang tiga kartu alur ("Admin meninjau" -> "Agenda
 * ditentukan" -> "Waktu konsultasi disampaikan setelah ditinjau"), dan
 * tombolnya berbunyi "Ajukan Konsultasi". Di belakangnya `tambah_aksi()` hanya
 * menyisipkan satu baris `forum_diskusi`. Tidak ada peninjauan, tidak ada
 * agenda, tidak ada jadwal - di mana pun. UI-nya menjanjikan tiga hal yang
 * tidak punya satu pun mekanisme.
 *
 * TABEL TERPISAH, bukan kolom tambahan di `forum_diskusi`. Satu topik bisa
 * melahirkan lebih dari satu janji temu sepanjang hidupnya (ditolak lalu
 * diajukan lagi setelah keadaannya berubah), dan riwayat penolakan justru yang
 * perlu terbaca. Kolom `jadwal_mulai` di `forum_diskusi` hanya bisa menyimpan
 * yang TERAKHIR, dan menghapus yang sebelumnya tanpa jejak.
 *
 * FORUM TETAP TAHAP PERTAMA. Janji temu hanya bisa diajukan setelah topiknya
 * ditanggapi - syaratnya ditegakkan di controller, bukan di sini. Alasannya:
 * "ruang konsultasi dulu" berarti pertanyaan yang sudah terjawab di forum tidak
 * perlu menghabiskan slot tatap muka, dan itu aturan proses yang bisa berubah,
 * bukan bentuk data.
 */
class Migration_Forum_janji_temu extends CI_Migration {

    /**
     * Bentuk kolom dibaca, tidak ditebak. `forum_diskusi` ternyata utf8
     * (utf8mb3) dengan `id_diskusi` int(11) SIGNED - beda dari default utf8mb4
     * yang dipakai tabel-tabel yang lebih baru. Menuliskannya sebagai literal
     * di sini akan ditolak errno 1253 (kolasi) atau 150 (tipe FK), persis dua
     * kegagalan yang sudah terjadi di migrasi 031/033.
     */
    private function bentuk($tabel, $kolom)
    {
        return $this->db->query(
            "SELECT COLUMN_TYPE t, CHARACTER_SET_NAME cs, COLLATION_NAME co
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$tabel, $kolom]
        )->row();
    }

    public function up()
    {
        if ($this->db->table_exists('forum_janji_temu')) { return; }

        $diskusi = $this->bentuk('forum_diskusi', 'id_diskusi');
        $user    = $this->bentuk('usr_users', 'id');
        $judul   = $this->bentuk('forum_diskusi', 'judul_topik');

        $tipe_diskusi = $diskusi->t ?? 'INT(11)';
        $tipe_user    = $user->t ?? 'INT(11)';
        $charset = $judul->cs ?? 'utf8mb4';
        $kolasi  = $judul->co ?? NULL;
        $teks    = $kolasi ? ' COLLATE ' . $kolasi : '';

        $sql = "CREATE TABLE `forum_janji_temu` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_diskusi`  {$tipe_diskusi} NOT NULL,
            `user_id`     {$tipe_user} NOT NULL,

            -- Kenapa perlu tatap muka. Wajib: tanpa ini admin tidak punya dasar
            -- memutuskan, dan 'diajukan' cuma berarti 'seseorang menekan tombol'.
            `alasan`      TEXT{$teks} NOT NULL,

            -- diajukan -> ditawarkan -> disetujui | ditolak | dibatalkan | selesai
            -- VARCHAR, bukan ENUM: menambah keadaan ke ENUM butuh ALTER TABLE di
            -- production, dan daftar sahnya sudah dijaga whitelist di controller.
            `status`      VARCHAR(20){$teks} NOT NULL DEFAULT 'diajukan',

            -- NULL sampai admin menawarkan. Bukan '0000-00-00': tanggal palsu
            -- akan lolos setiap pemeriksaan 'sudah ada jadwal?'.
            `jadwal_mulai` DATETIME NULL,
            `lokasi`      VARCHAR(160){$teks} NULL,

            `catatan_admin` TEXT{$teks} NULL,
            `catatan_user`  TEXT{$teks} NULL,

            `reviewed_by` {$tipe_user} NULL,
            `created_at`  DATETIME NOT NULL,
            `updated_at`  DATETIME NOT NULL,

            PRIMARY KEY (`id`),
            KEY `idx_jt_status` (`status`, `created_at`),
            KEY `idx_jt_diskusi` (`id_diskusi`),
            KEY `idx_jt_user` (`user_id`, `status`),

            -- CASCADE dua-duanya: janji temu tidak punya arti terpisah dari
            -- topik maupun orang yang mengajukannya. Bandingkan `reviewed_by`
            -- yang SET NULL - petugas yang keluar tidak boleh menghapus agenda.
            CONSTRAINT `fk_jt_diskusi` FOREIGN KEY (`id_diskusi`)
                REFERENCES `forum_diskusi` (`id_diskusi`) ON DELETE CASCADE,
            CONSTRAINT `fk_jt_user` FOREIGN KEY (`user_id`)
                REFERENCES `usr_users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_jt_reviewer` FOREIGN KEY (`reviewed_by`)
                REFERENCES `usr_users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}" . ($kolasi ? " COLLATE={$kolasi}" : '');

        $this->db->query($sql);
    }

    public function down()
    {
        if ( ! $this->db->table_exists('forum_janji_temu')) { return; }

        // Menolak menghapus agenda yang masih hidup. Baris di sini adalah janji
        // kepada warga tentang waktu dan tempat; rollback yang melenyapkannya
        // membatalkan pertemuan tanpa ada yang tahu - termasuk petugas yang
        // sudah mengosongkan jadwalnya.
        $hidup = (int) $this->db->query(
            "SELECT COUNT(*) c FROM `forum_janji_temu`
             WHERE `status` IN ('diajukan','ditawarkan','disetujui')")->row()->c;
        if ($hidup > 0) {
            throw new RuntimeException(
                "{$hidup} janji temu masih berjalan (diajukan/ditawarkan/disetujui). "
                . 'Rollback dibatalkan - selesaikan atau batalkan dulu lewat Janji Temu Konsultasi.');
        }

        $this->dbforge->drop_table('forum_janji_temu', TRUE);
    }
}
