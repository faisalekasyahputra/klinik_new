<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fase 3 normalisasi langkah 2 (keputusan pemilik produk 2 Okt 2026): satu charset dan satu
 * collation eksplisit di semua tabel, supaya lokal (MariaDB 10.4) dan production (11.8)
 * berhenti melenceng dan karakter 4-byte (emoji) bisa disimpan.
 *
 * TARGET utf8mb4_unicode_ci. Alasannya: (a) dikenal 10.4 DAN 11.8, sedangkan
 * utf8mb4_uca1400_ai_ci (bawaan 11.8 untuk CREATE TABLE tanpa COLLATE, asal 14 tabel
 * menyimpang di production) tidak dikenal 10.4, jadi dump production tidak bisa dimuat ke
 * lokal tanpa sunting; (b) sudah menjadi default DATABASE production; (c) urutan/pencocokan
 * Unicode (UCA 4.0) lebih benar daripada general_ci, dan keduanya PAD SPACE serta tidak peka
 * huruf besar, jadi perilaku pembanding yang dikenal kode tidak berubah. Pra-cek 2 Okt 2026
 * (lokal dan production, hanya hitungan): 27 indeks UNIQUE/PRIMARY bertipe string, nol grup
 * kembar di bawah collation target; indeks terpanjang sesudah utf8mb4 = 1020 byte (batas
 * 3072, semua tabel ROW_FORMAT=DYNAMIC); 5 pasangan FK bertipe string semuanya berakhir di
 * collation yang sama.
 *
 * CARA: per tabel satu ALTER = DEFAULT CHARACTER SET + MODIFY per kolom string, BUKAN
 * `CONVERT TO`. CONVERT TO menaikkan 10 kolom TEXT utf8mb3 menjadi MEDIUMTEXT diam-diam
 * (perubahan skema yang tidak diminta, dan down() lalu harus menebak tipenya). Definisi
 * kolom diambil apa adanya dari SHOW CREATE TABLE; yang diganti hanya klausa
 * CHARACTER SET/COLLATE tepat sesudah tipe, jadi NULL/DEFAULT/COMMENT/tipe tetap.
 * TEXT utf8mb3 -> TEXT utf8mb4 aman untuk data lama: batas TEXT dihitung byte, dan byte
 * karakter BMP tidak berubah.
 *
 * PENGECUALIAN DISENGAJA: kolom ber-charset `ascii` (ascii_bin, 5 kolom dari migrasi 052/053:
 * kunci push base64url dan module_key) TIDAK disentuh ke dua arah. Isinya peka huruf besar dan
 * byte-exact; mengubahnya ke unicode_ci membuat UNIQUE/PRIMARY-nya tidak peka huruf besar.
 *
 * PENGAMAN: 5 FK bertipe string dilepas lalu dipasang ulang (lihat FK_STRING; pra-cek: nol baris
 * yatim di bawah collation target), FOREIGN_KEY_CHECKS=0 selama ALTER, dan sql_mode ditambah
 * STRICT_ALL_TABLES (konversi yang kehilangan karakter GAGAL, bukan diam-diam jadi '?');
 * nilai sesi keduanya dipulihkan di finally walau gagal.
 * ALTER bersifat DDL (tidak transaksional); migrasi yang terputus aman dijalankan ulang
 * karena kolom yang sudah di target dilewati.
 *
 * down() memulihkan peta collation per tabel/kolom PRODUCTION pada versi 067 (dibaca dari
 * information_schema production 2 Okt 2026, di bawah). Collation yang tidak dikenal server
 * diterjemahkan: utf8mb3_* -> utf8_* (nama lama 10.4), utf8mb4_uca1400_ai_ci ->
 * utf8mb4_unicode_ci (keadaan lokal 10.4 sebelum 068 memang begitu). Default DATABASE tidak
 * dikembalikan: production sudah utf8mb4_unicode_ci sebelum 068 (lokal tadinya general_ci).
 * down() GAGAL (strict) bila sudah ada karakter 4-byte di tabel yang kembali ke utf8mb3.
 */
class Migration_Collation_seragam_utf8mb4 extends CI_Migration {

    const TARGET = 'utf8mb4_unicode_ci';

    /** Peta production versi 067: collation default tabel => daftar tabel. */
    const PETA_TABEL = [
        'utf8mb3_general_ci' => ['chat_messages', 'chat_rooms', 'data_sosmed_perumahan', 'forum_diskusi', 'forum_janji_temu', 'forum_komentar', 'migrations', 'sys_jejak_audit', 'usr_documents', 'usr_users'],
        'utf8mb4_general_ci' => ['aduan', 'bidang', 'forum_likes', 'kabupaten', 'kkn_magang_bidang', 'kkn_magang_pendaftaran', 'kkn_magang_posisi', 'kkn_peserta', 'psu_serah_terima', 'sf_berkas_penilaian', 'sf_penilaian_perumahan', 'sf_profil_warga', 'sf_rekaman_simperum', 'sf_rekomendasi_penilaian', 'srp2_asosiasi', 'srp2_registrations', 'sys_menu', 'sys_multi'],
        'utf8mb4_uca1400_ai_ci' => ['forum_laporan_komentar', 'kkn_magang_slot', 'rd_kawasan_intervensi', 'rd_kawasan_ringkasan', 'rd_laporan', 'rd_perumahan_baris', 'rd_perumahan_bnba', 'rd_perumahan_program', 'sf_housing_queue', 'sf_programs', 'sf_program_kategori', 'sf_riwayat_keputusan_antrean', 'sys_rate_limits', 'sys_settings'],
        'utf8mb4_unicode_ci' => ['sf_bank_data_dokumen', 'sf_data_simperum', 'srp2_certified_developers', 'srp2_documents', 'sys_push_subscriptions', 'usr_admin_module_privileges'],
    ];
    /** Kolom string (non-ascii) yang collation-nya beda dari default tabelnya di production 067. */
    const PETA_KOLOM = [
        'kkn_magang_slot.bidang_kode'        => 'utf8mb4_general_ci',
        'srp2_certified_developers.asosiasi' => 'utf8mb4_general_ci',
    ];

    /**
     * FK bertipe string (identik di production dan lokal 067). MariaDB 10.4 menolak mengganti
     * collation kolom FK walau FOREIGN_KEY_CHECKS=0 (galat 1833), jadi FK ini dilepas sebelum
     * ALTER dan dipasang ulang sesudahnya DENGAN pemeriksaan FK aktif (pra-cek: nol baris yatim
     * di bawah collation target). Terputus di tengah? Jalankan ulang: yang belum ada dipasang.
     */
    const FK_STRING = [
        'fk_magang_bidang'              => ['kkn_magang_bidang', 'FOREIGN KEY (`bidang_kode`) REFERENCES `bidang` (`kode`) ON DELETE CASCADE'],
        'fk_kkn_bidang'                 => ['kkn_magang_pendaftaran', 'FOREIGN KEY (`bidang_kode`) REFERENCES `bidang` (`kode`) ON DELETE SET NULL'],
        'fk_magang_posisi_bidang'       => ['kkn_magang_posisi', 'FOREIGN KEY (`bidang_kode`) REFERENCES `bidang` (`kode`) ON DELETE CASCADE ON UPDATE CASCADE'],
        'fk_slot_bidang'                => ['kkn_magang_slot', 'FOREIGN KEY (`bidang_kode`) REFERENCES `kkn_magang_bidang` (`bidang_kode`) ON DELETE CASCADE'],
        'fk_rd_perumahan_baris_program' => ['rd_perumahan_baris', 'FOREIGN KEY (`laporan_id`, `program`) REFERENCES `rd_perumahan_program` (`laporan_id`, `program`) ON DELETE CASCADE'],
    ];

    public function up()
    {
        $this->jalankan(function ($tabel) { return self::TARGET; }, function ($tabel, $kolom) { return self::TARGET; }, TRUE);
    }

    public function down()
    {
        $tabel_ke = [];
        foreach (self::PETA_TABEL as $c => $daftar) { foreach ($daftar as $t) { $tabel_ke[$t] = $c; } }
        $this->jalankan(
            function ($tabel) use ($tabel_ke) { return $tabel_ke[$tabel] ?? NULL; },
            function ($tabel, $kolom) use ($tabel_ke) { return self::PETA_KOLOM[$tabel . '.' . $kolom] ?? $tabel_ke[$tabel]; },
            FALSE);
    }

    /**
     * @param callable $tujuan_tabel fn(tabel) => collation, NULL = tabel dilewati
     * @param callable $tujuan_kolom fn(tabel, kolom) => collation
     */
    private function jalankan(callable $tujuan_tabel, callable $tujuan_kolom, $ubah_database)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE; // galat dilempar wajib(), supaya finally sempat memulihkan sesi
        $this->wajib('SET @kpkp_fk_068 = @@FOREIGN_KEY_CHECKS, @kpkp_mode_068 = @@SESSION.sql_mode');
        try {
            $this->wajib("SET FOREIGN_KEY_CHECKS = 0, SESSION sql_mode = CONCAT_WS(',', NULLIF(@@SESSION.sql_mode, ''), 'STRICT_ALL_TABLES')");
            foreach (self::FK_STRING as $fk => [$anak]) {
                if ($this->ada_fk($anak, $fk)) { $this->wajib('ALTER TABLE `' . $anak . '` DROP FOREIGN KEY `' . $fk . '`'); }
            }
            $tabel_db = $this->db->query("SELECT TABLE_NAME t, TABLE_COLLATION c FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->result_array();
            foreach ($tabel_db as $baris) {
                $tabel = $baris['t'];
                $mau = $tujuan_tabel($tabel);
                if ($mau === NULL) { continue; }
                $mau = $this->collation_server($mau);
                $ubah = $baris['c'] !== $mau ? ['DEFAULT CHARACTER SET ' . $this->charset_dari($mau) . ' COLLATE ' . $mau] : [];

                $kolom_db = $this->db->query("SELECT COLUMN_NAME k, COLLATION_NAME c FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLLATION_NAME IS NOT NULL
                      AND CHARACTER_SET_NAME <> 'ascii' ORDER BY ORDINAL_POSITION", [$tabel])->result_array();
                $definisi = NULL;
                foreach ($kolom_db as $k) {
                    $c = $this->collation_server($tujuan_kolom($tabel, $k['k']));
                    if ($k['c'] === $c) { continue; }
                    $definisi = $definisi ?? $this->definisi_kolom($tabel);
                    if ( ! isset($definisi[$k['k']])) { throw new RuntimeException('Migrasi 068: definisi ' . $tabel . '.' . $k['k'] . ' tidak terbaca.'); }
                    $ubah[] = 'MODIFY ' . $this->dengan_collation($definisi[$k['k']], $c);
                }
                if ($ubah) { $this->wajib('ALTER TABLE `' . $tabel . '` ' . implode(', ', $ubah)); }
            }
            $this->wajib('SET FOREIGN_KEY_CHECKS = 1');
            foreach (self::FK_STRING as $fk => [$anak, $definisi_fk]) {
                if ( ! $this->ada_fk($anak, $fk)) { $this->wajib('ALTER TABLE `' . $anak . '` ADD CONSTRAINT `' . $fk . '` ' . $definisi_fk); }
            }
            if ($ubah_database) {
                // Hak ALTER DATABASE tidak dijamin di hosting: dicoba, gagalnya hanya dicatat.
                if ($this->db->query('ALTER DATABASE CHARACTER SET utf8mb4 COLLATE ' . self::TARGET) === FALSE) {
                    log_message('error', 'Migrasi 068: ALTER DATABASE ditolak (' . $this->db->error()['message'] . '); default database tidak diubah, tabel tetap seragam.');
                }
            }
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = @kpkp_fk_068, SESSION sql_mode = @kpkp_mode_068');
            $this->db->db_debug = $debug;
        }
    }

    /** kolom => definisi lengkap dari SHOW CREATE TABLE (tanpa koma penutup). */
    private function definisi_kolom($tabel)
    {
        $sql = $this->db->query('SHOW CREATE TABLE `' . $tabel . '`')->row_array()['Create Table'] ?? '';
        $hasil = [];
        foreach (explode("\n", $sql) as $baris) {
            if (preg_match('/^\s+`([^`]+)` /', $baris, $m)) { $hasil[$m[1]] = rtrim(trim($baris), ','); }
        }
        return $hasil;
    }

    /** Ganti klausa CHARACTER SET/COLLATE tepat sesudah tipe kolom; sisa definisi tidak disentuh. */
    private function dengan_collation($definisi, $collation)
    {
        // Lewati `nama` lalu tipe: berhenti di spasi pertama di luar kurung dan kutip (enum('a b')).
        $i = strpos($definisi, '` ') + 2;
        $kurung = 0; $kutip = FALSE;
        for ($n = strlen($definisi); $i < $n; $i++) {
            $ch = $definisi[$i];
            if ($kutip) { if ($ch === "'") { $kutip = FALSE; } continue; } // '' berurutan = buka-tutup lagi, tetap benar
            if ($ch === "'") { $kutip = TRUE; } elseif ($ch === '(') { $kurung++; } elseif ($ch === ')') { $kurung--; } elseif ($ch === ' ' && $kurung === 0) { break; }
        }
        $sisa = preg_replace('/^( CHARACTER SET \w+)?( COLLATE \w+)?/', '', substr($definisi, $i));
        return substr($definisi, 0, $i) . ' CHARACTER SET ' . $this->charset_dari($collation) . ' COLLATE ' . $collation . $sisa;
    }

    /** Terjemahkan nama collation ke yang dikenal server ini (10.4 tidak kenal utf8mb3_* maupun uca1400). */
    private function collation_server($c)
    {
        foreach ([$c, str_replace('utf8mb3_', 'utf8_', $c), str_replace('uca1400_ai_ci', 'unicode_ci', $c)] as $calon) {
            if ($this->charset_dari($calon) !== NULL) { return $calon; }
        }
        throw new RuntimeException('Migrasi 068: collation ' . $c . ' tidak dikenal server ini.');
    }

    private function charset_dari($collation)
    {
        static $peta = NULL;
        if ($peta === NULL) {
            $peta = [];
            // 11.x menulis uca1400 di COLLATIONS tanpa charset ('uca1400_ai_ci'); nama lengkapnya
            // hanya ada di FULL_COLLATION_NAME tabel ini. 10.4 belum punya kolom itu.
            foreach ($this->db->query('SELECT * FROM information_schema.COLLATION_CHARACTER_SET_APPLICABILITY')->result_array() as $r) {
                $peta[$r['FULL_COLLATION_NAME'] ?? $r['COLLATION_NAME']] = $r['CHARACTER_SET_NAME'];
            }
        }
        return $peta[$collation] ?? NULL;
    }

    private function ada_fk($tabel, $nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$tabel, $nama])->row('n') > 0;
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 068 gagal: ' . $sql . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
