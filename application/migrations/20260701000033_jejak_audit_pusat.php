<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jejak audit pusat - siapa mengubah apa, kapan.
 *
 * Sebelum ini yang tersimpan hanya "peninjau TERAKHIR" per baris data
 * (`reviewed_by`, `reviewed_by_bidang`, `sf_riwayat_keputusan_antrean` untuk
 * satu domain saja). Pertanyaan yang tidak bisa dijawab sama sekali: siapa
 * mengangkat seseorang jadi admin kab/kota, siapa menonaktifkan akun, siapa
 * mengubah slot magang - padahal justru itu yang ditanyakan ketika ada yang
 * keliru, dan justru itu yang tidak punya "baris data" untuk ditempeli.
 *
 * DUA KEPUTUSAN DESAIN yang layak disebut karena keduanya menentang naluri
 * normalisasi, dan keduanya disengaja:
 *
 * 1. `actor_email` DISIMPAN APA ADANYA, menduplikasi usr_users. FK-nya
 *    ON DELETE SET NULL, jadi menghapus akun akan mengosongkan `actor_id` -
 *    dan jejak audit yang kehilangan "siapa" begitu akunnya dihapus bukan
 *    jejak audit, ia cuma daftar waktu. Justru akun yang dihapus yang paling
 *    sering perlu ditelusuri.
 *
 * 2. `ringkasan` berbahasa manusia disimpan SAAT KEJADIAN, bukan disusun ulang
 *    saat ditampilkan. Menyusunnya belakangan berarti tampilan bergantung pada
 *    data yang mungkin sudah berubah atau lenyap - "role diubah menjadi
 *    admin_kabkota (Semarang)" harus tetap terbaca meski kabupaten itu kelak
 *    diganti namanya.
 *
 * TIDAK ADA migrasi data lama: jejak sebelum tabel ini memang tidak pernah
 * terekam, dan mengarang-ngarangnya dari `reviewed_at` akan menghasilkan
 * riwayat yang terlihat lengkap padahal separuhnya tebakan.
 */
class Migration_Jejak_audit_pusat extends CI_Migration {

    /**
     * Collation diambil dari kolom yang akan direferensikan, bukan ditulis
     * sebagai tebakan. Pelajaran migrasi 031/032: FK antar kolom yang
     * collation-nya beda ditolak errno 150, dan di lokal bisa lolos sementara
     * production patah diam-diam.
     */
    private function bentuk_teks($tabel, $kolom)
    {
        $row = $this->db->query(
            "SELECT CHARACTER_SET_NAME cs, COLLATION_NAME co FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$tabel, $kolom]
        )->row();
        return $row && $row->co ? ['cs' => $row->cs, 'co' => $row->co] : NULL;
    }

    /** Tipe kolom apa adanya, supaya FK tidak ditolak karena signed/unsigned. */
    private function tipe_kolom($tabel, $kolom)
    {
        $row = $this->db->query(
            "SELECT COLUMN_TYPE t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$tabel, $kolom]
        )->row();
        return $row ? $row->t : 'INT(11)';
    }

    public function up()
    {
        if ($this->db->table_exists('sys_jejak_audit')) { return; }

        /**
         * CHARSET dan COLLATION dibaca BERPASANGAN, dan tipe kolomnya disalin
         * apa adanya. Percobaan pertama migrasi ini gagal dua kali di baris yang
         * sama - komentar di atas memperingatkan "jangan menebak collation",
         * lalu tubuhnya menulis `DEFAULT CHARSET=utf8mb4` sebagai tebakan baru.
         * `usr_users` ternyata `utf8` (utf8mb3), jadi utf8mb4 + utf8_general_ci
         * ditolak errno 1253; dan `usr_users.id` ternyata `int(11)` SIGNED,
         * jadi `INT UNSIGNED` akan ditolak errno 150 sesudahnya.
         *
         * Pelajarannya bukan "hati-hati collation" - itu sudah tertulis. Yang
         * kurang: menuliskan nilainya sebagai literal SELALU tebakan, sekalipun
         * tebakan itu benar di dua server yang kebetulan sama.
         */
        $teks_bentuk = $this->bentuk_teks('usr_users', 'email');
        $kolasi = $teks_bentuk['co'] ?? NULL;
        $charset = $teks_bentuk['cs'] ?? 'utf8mb4';
        $teks   = $kolasi ? ' COLLATE ' . $kolasi : '';
        $tipe_actor = $this->tipe_kolom('usr_users', 'id');

        $sql = "CREATE TABLE `sys_jejak_audit` (
            `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            `actor_id`    {$tipe_actor} NULL,
            `actor_email` VARCHAR(100){$teks} NULL,
            `actor_role`  VARCHAR(30){$teks} NULL,

            -- Kata kerja pendek & stabil ('role_diubah', 'akun_dinonaktifkan').
            -- Dipakai untuk MENYARING; yang dibaca manusia ada di `ringkasan`.
            `aksi`        VARCHAR(40){$teks} NOT NULL,

            -- Objek boleh apa saja, termasuk yang kuncinya bukan angka
            -- (bidang_kode). Karena itu VARCHAR, bukan INT.
            `objek_tipe`  VARCHAR(40){$teks} NULL,
            `objek_id`    VARCHAR(60){$teks} NULL,

            `ringkasan`   VARCHAR(255){$teks} NOT NULL,
            `detail_json` TEXT{$teks} NULL,
            `ip`          VARCHAR(45){$teks} NULL,
            `created_at`  DATETIME NOT NULL,

            PRIMARY KEY (`id`),
            KEY `idx_audit_waktu` (`created_at`),
            KEY `idx_audit_aksi` (`aksi`, `created_at`),
            KEY `idx_audit_pelaku` (`actor_id`, `created_at`),
            KEY `idx_audit_objek` (`objek_tipe`, `objek_id`),
            CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`)
                REFERENCES `usr_users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset}" . ($kolasi ? " COLLATE={$kolasi}" : '');

        $this->db->query($sql);
    }

    public function down()
    {
        // Menolak menghapus jejak yang sudah terisi. Tabel audit yang bisa
        // dilenyapkan satu perintah tidak melindungi siapa pun - dan rollback
        // paling sering dijalankan justru ketika ada yang sedang salah.
        if ($this->db->table_exists('sys_jejak_audit')) {
            $n = (int) $this->db->count_all('sys_jejak_audit');
            if ($n > 0) {
                throw new RuntimeException(
                    "sys_jejak_audit berisi {$n} baris. Rollback dibatalkan - "
                    . 'ekspor dulu isinya kalau memang hendak dibuang.');
            }
            $this->dbforge->drop_table('sys_jejak_audit', TRUE);
        }
    }
}
