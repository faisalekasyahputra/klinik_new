<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fase 3 normalisasi langkah 4 (keputusan pemilik produk 2 Okt 2026): satu sumber data
 * perusahaan. Kolom perusahaan di usr_users (nama_perusahaan, alamat_kantor, telp_kantor)
 * DIBUANG. Sumbernya kini:
 * - akun yang tertaut baris direktori (srp2_certified_developers.user_id): baris direktori;
 * - akun pengembang yang belum tertaut: pengajuan SRP2 terbarunya (srp2_registrations,
 *   dokumen yang dinilai admin; saat diterima upsert_direktori_publik() menyalinnya ke
 *   direktori). Akun tanpa pengajuan yang punya data perusahaan dibuatkan draft, persis
 *   yang dilakukan Auth_model::ensure_srp2_draft() saat akun itu membuka /akun.
 * telp_kantor tidak punya tujuan (direktori dan pengajuan tidak punya kolom telepon kantor,
 * dan onboarding tidak lagi memintanya); pra-cek memastikan setiap isinya SAMA dengan
 * usr_users.phone, jadi tidak ada nilai yang hilang.
 *
 * Isi hanya medan tujuan yang KOSONG, tidak pernah menimpa. Pra-cek MENOLAK (melempar, sebelum
 * penulisan apa pun) bila ada konflik: medan usr_users dan tujuannya sama-sama terisi dengan
 * nilai berbeda, data perusahaan di akun bukan pengembang yang tidak tertaut, telp_kantor yang
 * beda dari phone, atau surel terlalu panjang untuk draft. Pesan galat hanya berisi hitungan.
 *
 * Ikut dibereskan: Pengembang::profil dulu melengkapi medan kosong direktori dari pengajuan
 * Diterima saat dibaca. Jalur itu dicabut, jadi up() mengisi medan direktori yang kosong dari
 * pengajuan Diterima yang menunjuknya (aturan isi-yang-kosong yang sama).
 *
 * PRA-CEK 2 Okt 2026 (DB lokal = salinan production, hanya hitungan): 6 akun pengembang nyata;
 * 1 tertaut direktori dengan medan perusahaan usr_users kosong; 5 tidak tertaut: 4 nama dan
 * 1 alamat di usr_users, 0 konflik dengan pengajuan, 1 akun tanpa pengajuan (dibuatkan draft),
 * 1 telp_kantor (= phone). Medan direktori yang diisi dari pengajuan Diterima: 0.
 *
 * down() memasang lagi ketiga kolom di posisi semula dan mengisinya dari sumber tunggal:
 * akun tertaut dari baris direktori, akun pengembang lain dari pengajuan terbarunya.
 * telp_kantor kembali NULL (isinya identik dengan phone, lihat di atas). Draft yang dibuat
 * up() dan isian yang dipindah ke pengajuan/direktori tetap ada (tidak bisa dibedakan dari
 * isian pemohon, dan memang data yang sah). Idempoten: aman dijalankan ulang.
 */
class Migration_Sumber_tunggal_perusahaan extends CI_Migration {

    /** Kolom usr_users yang dibuang: nama => definisi untuk down() (urutan = posisi asal). */
    const KOLOM = [
        'nama_perusahaan' => 'VARCHAR(150) DEFAULT NULL AFTER `alamat`',
        'alamat_kantor'   => 'TEXT DEFAULT NULL AFTER `nama_perusahaan`',
        'telp_kantor'     => 'VARCHAR(20) DEFAULT NULL AFTER `alamat_kantor`',
    ];

    /** Medan yang dipindah (ada di usr_users, direktori, dan pengajuan). */
    const MEDAN = ['nama_perusahaan', 'alamat_kantor'];

    /** Medan direktori yang dulu dilengkapi Pengembang::profil dari pengajuan Diterima. */
    const MEDAN_PROFIL = ['asosiasi', 'no_keanggotaan', 'alamat_kantor', 'instagram', 'website', 'sosmed_lainnya'];

    /** Pengajuan terbaru tiap akun (urutan yang sama dengan ensure_srp2_draft). */
    const TERBARU = '(SELECT user_id, MAX(id) id FROM srp2_registrations WHERE user_id IS NOT NULL GROUP BY user_id)';

    /** Akun tanpa baris direktori. */
    const TAK_TERTAUT = 'NOT EXISTS (SELECT 1 FROM srp2_certified_developers d0 WHERE d0.user_id = u.id)';

    public function up()
    {
        if ( ! $this->db->field_exists('nama_perusahaan', 'usr_users')) { return; }
        $this->tanpa_debug(function () { $this->naik(); });
    }

    public function down()
    {
        $this->tanpa_debug(function () { $this->turun(); });
    }

    private function naik()
    {
        $this->pra_cek();

        $this->wajib('START TRANSACTION');
        try {
            foreach (self::MEDAN as $k) {
                // Akun tertaut -> baris direktori.
                $this->wajib("UPDATE srp2_certified_developers d JOIN usr_users u ON u.id = d.user_id
                    SET d.`$k` = u.`$k` WHERE IFNULL(d.`$k`, '') = '' AND IFNULL(u.`$k`, '') <> ''");
                // Akun pengembang tak tertaut -> pengajuan terbarunya.
                $this->wajib("UPDATE srp2_registrations r JOIN " . self::TERBARU . " t ON t.id = r.id
                    JOIN usr_users u ON u.id = r.user_id
                    SET r.`$k` = u.`$k` WHERE IFNULL(r.`$k`, '') = '' AND IFNULL(u.`$k`, '') <> '' AND " . self::TAK_TERTAUT);
            }
            $this->wajib("INSERT INTO srp2_registrations (user_id, email, nama_perusahaan, alamat_kantor, status_verifikasi)
                SELECT u.id, u.email, NULLIF(u.nama_perusahaan, ''), NULLIF(u.alamat_kantor, ''), 'Draft' FROM usr_users u
                WHERE u.role = 'pengembang' AND (IFNULL(u.nama_perusahaan, '') <> '' OR IFNULL(u.alamat_kantor, '') <> '')
                  AND " . self::TAK_TERTAUT . " AND NOT EXISTS (SELECT 1 FROM srp2_registrations r0 WHERE r0.user_id = u.id)");
            foreach (self::MEDAN_PROFIL as $k) {
                $this->wajib("UPDATE srp2_certified_developers d JOIN srp2_registrations r
                    ON r.certified_developer_id = d.id AND r.status_verifikasi = 'Diterima'
                    SET d.`$k` = r.`$k` WHERE IFNULL(d.`$k`, '') = '' AND IFNULL(r.`$k`, '') <> ''");
            }
            // Bukti sebelum DROP: setiap isian usr_users kini identik di tujuannya.
            $sisa = $this->hitung_tak_terpindah();
            if ($sisa) { throw new RuntimeException('Migrasi 070 dibatalkan: ' . $sisa . ' isian belum identik di tujuannya.'); }
            $this->wajib('COMMIT');
        } catch (Throwable $e) {
            $this->db->query('ROLLBACK');
            throw $e;
        }

        $this->wajib('ALTER TABLE usr_users DROP COLUMN `' . implode('`, DROP COLUMN `', array_keys(self::KOLOM)) . '`');
    }

    private function turun()
    {
        foreach (self::KOLOM as $k => $def) {
            if ( ! $this->db->field_exists($k, 'usr_users')) { $this->wajib("ALTER TABLE usr_users ADD COLUMN `$k` $def"); }
        }
        foreach (self::MEDAN as $k) {
            $this->wajib("UPDATE usr_users u JOIN srp2_certified_developers d ON d.user_id = u.id
                SET u.`$k` = NULLIF(d.`$k`, '') WHERE u.`$k` IS NULL");
            $this->wajib("UPDATE usr_users u JOIN " . self::TERBARU . " t ON t.user_id = u.id JOIN srp2_registrations r ON r.id = t.id
                SET u.`$k` = NULLIF(r.`$k`, '') WHERE u.`$k` IS NULL AND u.role = 'pengembang' AND " . self::TAK_TERTAUT);
        }
    }

    /** Hitungan yang wajib nol sebelum penulisan; hanya angka yang disebut di pesan galat. */
    private function pra_cek()
    {
        $masalah = [];
        foreach (self::MEDAN as $k) {
            $n = $this->n("SELECT COUNT(*) n FROM usr_users u JOIN srp2_certified_developers d ON d.user_id = u.id
                WHERE IFNULL(u.`$k`, '') <> '' AND IFNULL(d.`$k`, '') <> '' AND BINARY u.`$k` <> BINARY d.`$k`");
            if ($n) { $masalah[] = "$k: $n akun tertaut berbeda dengan direktori"; }
            $n = $this->n("SELECT COUNT(*) n FROM usr_users u JOIN " . self::TERBARU . " t ON t.user_id = u.id
                JOIN srp2_registrations r ON r.id = t.id
                WHERE IFNULL(u.`$k`, '') <> '' AND IFNULL(r.`$k`, '') <> '' AND BINARY u.`$k` <> BINARY r.`$k` AND " . self::TAK_TERTAUT);
            if ($n) { $masalah[] = "$k: $n akun tak tertaut berbeda dengan pengajuan terbarunya"; }
        }
        $n = $this->n("SELECT COUNT(*) n FROM usr_users u WHERE IFNULL(u.role, '') <> 'pengembang'
            AND (IFNULL(u.nama_perusahaan, '') <> '' OR IFNULL(u.alamat_kantor, '') <> '') AND " . self::TAK_TERTAUT);
        if ($n) { $masalah[] = "$n akun bukan pengembang tak tertaut berisi data perusahaan"; }
        $n = $this->n("SELECT COUNT(*) n FROM usr_users WHERE IFNULL(telp_kantor, '') <> '' AND BINARY telp_kantor <> BINARY IFNULL(phone, '')");
        if ($n) { $masalah[] = "telp_kantor: $n akun berbeda dari phone"; }
        $n = $this->n("SELECT COUNT(*) n FROM usr_users u WHERE u.role = 'pengembang' AND CHAR_LENGTH(u.email) > 100
            AND (IFNULL(u.nama_perusahaan, '') <> '' OR IFNULL(u.alamat_kantor, '') <> '')
            AND NOT EXISTS (SELECT 1 FROM srp2_registrations r0 WHERE r0.user_id = u.id) AND " . self::TAK_TERTAUT);
        if ($n) { $masalah[] = "$n akun tanpa pengajuan bersurel lebih dari 100 karakter"; }
        if ($masalah) {
            throw new RuntimeException('Migrasi 070 ditolak, putuskan datanya dulu: ' . implode('; ', $masalah));
        }
    }

    /** Isian usr_users yang tidak identik di tujuannya (direktori bila tertaut, pengajuan terbaru bila tidak). */
    private function hitung_tak_terpindah()
    {
        $n = 0;
        foreach (self::MEDAN as $k) {
            $n += $this->n("SELECT COUNT(*) n FROM usr_users u
                LEFT JOIN srp2_certified_developers d ON d.user_id = u.id
                LEFT JOIN " . self::TERBARU . " t ON t.user_id = u.id LEFT JOIN srp2_registrations r ON r.id = t.id
                WHERE IFNULL(u.`$k`, '') <> ''
                  AND NOT (BINARY u.`$k` <=> BINARY IF(d.id IS NOT NULL, d.`$k`, r.`$k`))");
        }
        return $n;
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
        if ($q === FALSE) { throw new RuntimeException('Migrasi 070 gagal membaca: ' . $this->db->error()['message']); }
        return (int) $q->row('n');
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 070 gagal: ' . preg_replace('/\s+/', ' ', $sql) . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
