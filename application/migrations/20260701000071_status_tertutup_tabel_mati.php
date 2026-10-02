<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fase 3 normalisasi langkah 5: buang tabel mati, kunci kosakata status.
 *
 * A. TABEL MATI. sys_menu, sys_multi, data_sosmed_perumahan: nol rujukan di application/,
 *    assets, config, dan suite uji (yang tersisa hanya skrip refaktor lama di dev-scripts/,
 *    dump skema baseline, dan daftar penyelarasan huruf migrasi 051/068). Nol FK dari/ke ketiganya,
 *    nol view/trigger. Lokal (= salinan production) ketiganya KOSONG. up() MENOLAK bila
 *    salah satu berisi, jadi tidak ada baris yang perlu dibawa down().
 *
 * B. KOSAKATA STATUS. Inventaris 2 Okt 2026: nol nilai campur huruf, nol konsep kembar dalam
 *    satu kolom, nol nilai mati, nol nilai yang ditulis kode tapi ditolak ENUM. Yang dipasang
 *    di sini: CHECK pada kolom VARCHAR yang himpunannya sudah tertutup di kode (daftar di
 *    CEK, disalin dari konstanta/whitelist penulisnya). CHECK, bukan ENUM: koneksi aplikasi
 *    berjalan TANPA strict mode (stricton FALSE di config/database.php membuang STRICT_*),
 *    dan di mode itu nilai di luar ENUM tersimpan sebagai '' dengan peringatan saja,
 *    sedangkan CHECK selalu menolak. BINARY supaya beda huruf besar-kecil ikut ditolak (perbandingan kolom _ci).
 *    Tidak dikunci (sengaja): chat_rooms.status (fitur dikarantina, keputusan #7),
 *    response_status SIMPERUM (keadaan dari gateway luar), eligibility_status (bergantung
 *    versi ruleset), kode isian warga (*_code, matrix_dtks_status).
 *
 * PRA-CEK menolak sebelum DDL bila ada baris di tabel mati atau nilai status di luar himpunan;
 * pesannya hanya hitungan. down() melepas CHECK dan membuat ulang ketiga tabel dengan
 * definisi SHOW CREATE TABLE lokal 2 Okt 2026 (sesudah 068). Idempoten.
 */
class Migration_Status_tertutup_tabel_mati extends CI_Migration {

    /** Tabel mati => CREATE TABLE asal (untuk down()). */
    const TABEL_MATI = [
        'sys_menu' => "CREATE TABLE IF NOT EXISTS `sys_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `default` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        'sys_multi' => "CREATE TABLE IF NOT EXISTS `sys_multi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `id_menu` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC",
        'data_sosmed_perumahan' => "CREATE TABLE IF NOT EXISTS `data_sosmed_perumahan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_perumahan` varchar(255) DEFAULT NULL,
  `pengembang` varchar(255) DEFAULT NULL,
  `kabupaten_kota` varchar(100) DEFAULT NULL,
  `asosiasi` varchar(100) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    /** Nama CHECK => [tabel, kolom, himpunan nilai, penulis yang menjadi sumber himpunannya]. */
    const CEK = [
        'ck_aduan_status' => ['aduan', 'status', ['Baru', 'Diproses', 'Selesai'], 'Admin_Bidang::update_status, Admin_Aduan'],
        'ck_kkn_pendaftaran_status' => ['kkn_magang_pendaftaran', 'status',
            ['Diajukan', 'Ditinjau Bidang', 'Diterima', 'Ditolak', 'Dibatalkan'], 'Admin_Kemitraan, Kemitraan_Bidang, KemitraanPortal'],
        'ck_janji_temu_status' => ['forum_janji_temu', 'status',
            ['diajukan', 'ditawarkan', 'disetujui', 'selesai', 'dibatalkan', 'ditolak'], 'Janji_temu_model::ALUR'],
        'ck_srp2_status_verifikasi' => ['srp2_registrations', 'status_verifikasi',
            ['Draft', 'Pending', 'Diterima', 'Ditolak'], 'Admin_Srp2::proses, Pengembang, Auth_model'],
        'ck_usr_users_status' => ['usr_users', 'status', ['restricted', 'active', 'nonaktif'], 'Auth_model, Admin_Users, Kemitraan_Bidang'],
        'ck_penilaian_status' => ['sf_penilaian_perumahan', 'status', ['draft', 'submitted', 'superseded'], 'Housing_assessment_model'],
        'ck_riwayat_antrean_dari' => ['sf_riwayat_keputusan_antrean', 'from_status',
            ['pending', 'needs_revision', 'approved', 'rejected'], 'Housing_assessment_model (= ENUM status_antrean)'],
        'ck_riwayat_antrean_ke' => ['sf_riwayat_keputusan_antrean', 'to_status',
            ['pending', 'needs_revision', 'approved', 'rejected'], 'Housing_assessment_model (= ENUM status_antrean)'],
    ];

    public function up()
    {
        $this->tanpa_debug(function () {
            $this->pra_cek();
            foreach (self::CEK as $nama => [$tabel, $kolom, $nilai]) {
                if ( ! $this->ada_cek($tabel, $nama)) {
                    $this->wajib("ALTER TABLE `$tabel` ADD CONSTRAINT `$nama` CHECK (" . self::ekspresi($kolom, $nilai) . ')');
                }
            }
            foreach (array_keys(self::TABEL_MATI) as $t) { $this->wajib("DROP TABLE IF EXISTS `$t`"); }
        });
    }

    public function down()
    {
        $this->tanpa_debug(function () {
            foreach (self::TABEL_MATI as $sql) { $this->wajib($sql); }
            foreach (self::CEK as $nama => [$tabel]) {
                if ($this->ada_cek($tabel, $nama)) { $this->wajib("ALTER TABLE `$tabel` DROP CONSTRAINT `$nama`"); }
            }
        });
    }

    /** Ekspresi CHECK satu kolom; dipakai juga oleh uji untuk membandingkan dengan information_schema. */
    public static function ekspresi($kolom, array $nilai)
    {
        return "BINARY `$kolom` IN ('" . implode("','", $nilai) . "')";
    }

    /** Hitungan yang wajib nol sebelum DDL; hanya angka yang disebut di pesan galat. */
    private function pra_cek()
    {
        $masalah = [];
        foreach (array_keys(self::TABEL_MATI) as $t) {
            if ($this->db->table_exists($t) && ($n = $this->n("SELECT COUNT(*) n FROM `$t`"))) { $masalah[] = "$t berisi $n baris"; }
        }
        foreach (self::CEK as [$tabel, $kolom, $nilai]) {
            $n = $this->n("SELECT COUNT(*) n FROM `$tabel` WHERE `$kolom` IS NOT NULL AND NOT (" . self::ekspresi($kolom, $nilai) . ')');
            if ($n) { $masalah[] = "$tabel.$kolom: $n baris di luar himpunan"; }
        }
        if ($masalah) {
            throw new RuntimeException('Migrasi 071 ditolak, putuskan datanya dulu: ' . implode('; ', $masalah));
        }
    }

    private function ada_cek($tabel, $nama)
    {
        return $this->n("SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = " . $this->db->escape($tabel) . " AND CONSTRAINT_NAME = " . $this->db->escape($nama)
            . " AND CONSTRAINT_TYPE = 'CHECK'") > 0;
    }

    /** db_debug mati supaya galat jadi pengecualian berpesan hitungan, dipulihkan walau gagal (pola 069). */
    private function tanpa_debug(callable $kerja)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try { $kerja(); } finally { $this->db->db_debug = $debug; }
    }

    private function n($sql)
    {
        $q = $this->db->query($sql);
        if ($q === FALSE) { throw new RuntimeException('Migrasi 071 gagal membaca: ' . $this->db->error()['message']); }
        return (int) $q->row('n');
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 071 gagal: ' . preg_replace('/\s+/', ' ', $sql) . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
