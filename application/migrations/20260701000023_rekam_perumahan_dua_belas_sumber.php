<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data Perumahan: sepuluh sumber dana menjadi DUA BELAS.
 *
 * Menambahkan `apbd_provinsi` dan `baznas_provinsi`. Keputusan user 30 Jul 2026,
 * menyatukan dua acuan yang bertentangan: Google Form dinas memuat sepuluh
 * sumber tanpa keduanya (lihat STRUKTUR_FORM_SUMBER_REKAM_DATA.md), sedangkan
 * sketsa Menu Utama memuat keduanya tetapi tanpa `apbn_dak` dan
 * `apbn_kemensos`. Tidak ada yang dibuang: menambah nilai ENUM tidak menyentuh
 * baris yang sudah ada, sedangkan membuang nilai bersifat destruktif begitu
 * ada laporan memakainya.
 *
 * `apbd_provinsi` BUKAN nama lain dari `apbn_dak` (uang provinsi vs transfer
 * pusat) dan `baznas_provinsi` BUKAN nama lain dari `apbn_kemensos` (lembaga
 * zakat vs kementerian). Keduanya benar-benar sumber berbeda.
 *
 * KENAPA FK-nya DILEPAS DULU: `rd_perumahan_baris.(laporan_id, sumber_dana)`
 * menunjuk ke `rd_perumahan_bagian.(laporan_id, sumber_dana)`, dan MySQL
 * mensyaratkan tipe kolom perujuk dan yang dirujuk IDENTIK. Meng-ALTER salah
 * satunya lebih dulu membuat keduanya berbeda sesaat dan ditolak (errno 150 /
 * 3780). Jadi: lepas FK → ubah kedua kolom → pasang FK kembali. Indeks yang
 * dibutuhkan FK tetap ada selama itu (`uq_rd_perumahan_baris` berawalan
 * `laporan_id, sumber_dana`), jadi tidak ada yang perlu dibuat ulang.
 */
class Migration_Rekam_perumahan_dua_belas_sumber extends CI_Migration {

    /** Sesudah: 12 nilai. Urutannya mengikuti Rekam_data_model::SUMBER_PERUMAHAN. */
    private const SESUDAH = "'apbd_provinsi','apbd_kabkota','apbn_bsps','apbn_dak'"
        . ",'apbn_kemensos','apbn_dana_desa','apbn_kl_lain','baznas_ri'"
        . ",'baznas_provinsi','baznas_kabkota','csr','dana_lainnya'";

    /** Sebelum: 10 nilai, persis seperti migrasi 20260701000021 menuliskannya. */
    private const SEBELUM = "'apbd_kabkota','apbn_bsps','apbn_dak','apbn_kemensos'"
        . ",'apbn_dana_desa','apbn_kl_lain','baznas_ri','baznas_kabkota','csr','dana_lainnya'";

    private const FK = 'fk_rd_perumahan_baris_bagian';

    public function up()
    {
        $this->ubah(self::SESUDAH);
    }

    /**
     * Turun HANYA aman selama belum ada baris memakai dua sumber baru. Kalau
     * ada, MySQL menolak mengubah ENUM-nya dan migrasi berhenti dengan galat —
     * itu perilaku yang DIINGINKAN. Membiarkannya lewat berarti nilai yang
     * tidak lagi sah berubah jadi string kosong tanpa suara, dan capaian yang
     * pernah dilaporkan hilang dari rekap tanpa ada yang tahu.
     */
    public function down()
    {
        $sisa = (int) $this->db->query(
            "SELECT COUNT(*) AS n FROM `rd_perumahan_bagian`
             WHERE `sumber_dana` IN ('apbd_provinsi','baznas_provinsi')")->row('n');

        if ($sisa > 0) {
            show_error("Migrasi 20260701000023 tidak bisa diturunkan: ada {$sisa} baris "
                . 'rd_perumahan_bagian memakai apbd_provinsi/baznas_provinsi. Hapus atau '
                . 'pindahkan baris itu lebih dulu — menurunkan ENUM sekarang akan '
                . 'mengosongkan nilainya tanpa suara.');
            return;
        }

        $this->ubah(self::SEBELUM);
    }

    private function ubah($daftar)
    {
        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP FOREIGN KEY `' . self::FK . '`');

        $this->db->query("ALTER TABLE `rd_perumahan_bagian`
            MODIFY COLUMN `sumber_dana` ENUM({$daftar}) NOT NULL");
        $this->db->query("ALTER TABLE `rd_perumahan_baris`
            MODIFY COLUMN `sumber_dana` ENUM({$daftar}) NOT NULL");

        $this->db->query('ALTER TABLE `rd_perumahan_baris`
            ADD CONSTRAINT `' . self::FK . '` FOREIGN KEY (`laporan_id`,`sumber_dana`)
            REFERENCES `rd_perumahan_bagian` (`laporan_id`,`sumber_dana`) ON DELETE CASCADE');
    }
}
