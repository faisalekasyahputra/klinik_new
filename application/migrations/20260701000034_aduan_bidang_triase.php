<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * `aduan.bidang` boleh NULL - antrean triase.
 *
 * Revisi dinas 3 Agt 2026: pelapor TIDAK LAGI memilih bidang tujuan. Alasannya
 * sederhana dan benar - warga tidak tahu urusan rumahnya masuk Bidang Perumahan
 * atau Bidang Kawasan Permukiman, dan menyuruh mereka menebak berarti sebagian
 * aduan mendarat di meja yang salah lalu berhenti di sana. Superadmin yang
 * meneruskan.
 *
 * NULL, bukan sentinel 'umum'. Dua alasan:
 *
 * 1. DEFAULT 'umum' yang ada sekarang berbohong dua kali: 'umum' BUKAN bidang
 *    (tabel `bidang` berisi lima, dan itu bukan salah satunya, dikonfirmasi
 *    dinas 1 Agt 2026), dan komentar kolomnya menjanjikan "dideteksi otomatis"
 *    untuk deteksi yang tidak pernah ada. Baris ber-'umum' terlihat SUDAH
 *    dirutekan padahal tidak - dan `Admin_Bidang` yang mem-filter per kode
 *    membuatnya tak terlihat siapa pun. Persis kegagalan senyap yang dulu
 *    melahirkan callout "bidang tanpa admin".
 * 2. NULL tidak cocok dengan WHERE mana pun, jadi aduan yang belum ditriase
 *    OTOMATIS tidak muncul di meja bidang mana pun tanpa satu baris kode baru.
 *
 * Migrasi ini dijalankan SEBELUM kodenya berubah dan aman untuk kode lama:
 * kolom yang menerima NULL tetap menerima nilai lama, dan DEFAULT hanya
 * terpakai kalau kolomnya tidak disebut di INSERT - `Umum::simpan_aduan()`
 * selalu menyebutnya.
 */
class Migration_Aduan_bidang_triase extends CI_Migration {

    /**
     * Bentuk kolom apa adanya. Disalin dari migrasi 033 dan SENGAJA tidak
     * diangkat jadi base class bersama: migrasi adalah catatan sejarah, dan
     * helper bersama yang disunting nanti diam-diam mengubah arti migrasi lama.
     *
     * MODIFY COLUMN menulis ulang definisi kolom SELURUHNYA. Apa pun yang tidak
     * disebutkan ulang akan hilang atau jatuh ke default tabel - termasuk
     * charset dan collation. Di sini kebetulan sama dengan tabelnya
     * (utf8mb4_general_ci), dan "kebetulan sama" persis alasan kenapa dibaca,
     * bukan ditulis sebagai literal: pelajaran errno 1253 dari migrasi 033.
     */
    private function bentuk($tabel, $kolom)
    {
        return $this->db->query(
            "SELECT COLUMN_TYPE t, IS_NULLABLE n, CHARACTER_SET_NAME cs, COLLATION_NAME co
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$tabel, $kolom]
        )->row();
    }

    private function teks($b)
    {
        return ($b->cs ? ' CHARACTER SET ' . $b->cs : '') . ($b->co ? ' COLLATE ' . $b->co : '');
    }

    public function up()
    {
        $b = $this->bentuk('aduan', 'bidang');
        if ( ! $b || $b->n === 'YES') { return; }   // sudah nullable - idempoten

        $this->db->query(
            "ALTER TABLE `aduan` MODIFY COLUMN `bidang` {$b->t}{$this->teks($b)}
             NULL DEFAULT NULL
             COMMENT 'FK longgar ke bidang.kode. NULL = belum ditriase superadmin'"
        );

        /**
         * Backfill: bidang yang tidak ada di tabel `bidang` -> NULL, yaitu masuk
         * antrean triase. Termasuk 'umum' dan 'pengembang' dari daftar lama.
         *
         * Dicocokkan ke TABEL, bukan ke daftar kode yang ditulis ulang di sini -
         * daftar kembar akan menyimpang, dan yang menyimpang di sini artinya
         * aduan sah ikut dilempar ke antrean triase.
         */
        $this->db->query(
            "UPDATE `aduan` SET `bidang` = NULL
             WHERE `bidang` IS NOT NULL
               AND (TRIM(`bidang`) = '' OR `bidang` NOT IN (SELECT `kode` FROM `bidang`))"
        );
    }

    public function down()
    {
        $b = $this->bentuk('aduan', 'bidang');
        if ( ! $b || $b->n === 'NO') { return; }

        /**
         * Menolak turun selama masih ada aduan yang belum ditriase. NOT NULL
         * tidak bisa dipulihkan tanpa MEMBERI NILAI pada baris NULL, dan nilai
         * apa pun yang dipilih di sini adalah rute karangan: aduannya akan
         * muncul di meja satu bidang yang tidak pernah memilihnya, terlihat
         * seperti aduan yang sudah dirutekan manusia.
         */
        $sisa = (int) $this->db->query(
            'SELECT COUNT(*) c FROM `aduan` WHERE `bidang` IS NULL')->row()->c;
        if ($sisa > 0) {
            throw new RuntimeException(
                "{$sisa} aduan masih menunggu triase (bidang NULL). Rollback dibatalkan - "
                . 'triase dulu lewat Pantau Aduan, atau tetapkan bidangnya manual.');
        }

        $this->db->query(
            "ALTER TABLE `aduan` MODIFY COLUMN `bidang` {$b->t}{$this->teks($b)}
             NOT NULL DEFAULT 'umum'"
        );
    }
}
