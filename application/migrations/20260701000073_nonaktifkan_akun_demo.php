<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Akun demo dinonaktifkan (keputusan pemilik produk 3 Okt 2026, sebelum pentest). Panel
 * "Kredensial Demo" di halaman masuk dicabut pada rilis yang sama; sandi semua akun ini adalah
 * `password` dan pernah dipajang di halaman publik, jadi akunnya sendiri ikut ditutup.
 *
 * Sasaran (DEMO): sebelas akun yang dipajang panel lama, ditambah dev1@example.com (akun demo
 * pengembang yang dulu tercatat di AKUN_LOGIN.md dengan sandi `password`). Semua @example.com
 * (domain cadangan, tidak bisa menerima surel) kecuali akun super admin demo. Akun nyata dan akun
 * uji agen lokal (*@agen.test) tidak disentuh.
 *
 * Per akun: status `nonaktif`, kata_sandi NULL, sesi_aktif_* dikosongkan. google_id tidak disentuh.
 * Baris tidak dihapus (riwayat data yang merujuknya tetap utuh).
 *
 * PRA-CEK: menolak (hanya hitungan) bila sesudahnya tidak ada satu pun Super Admin di luar daftar
 * ini yang aktif DAN bisa masuk (punya sandi atau sudah tertaut Google). Akun buatan
 * `php index.php akun buat_superadmin <email>` baru terhitung sesudah pemiliknya masuk dengan Google.
 * Jadi production tidak pernah tertinggal tanpa Super Admin.
 *
 * down(): HANYA status yang dipulihkan, dari cuplikan status sebelumnya yang disimpan up() di jejak
 * audit (aksi akun_demo_dinonaktifkan). Sandi TIDAK bisa dipulihkan (tidak disimpan dalam bentuk
 * apa pun): akun demo yang dipulihkan tetap tanpa sandi sampai Super Admin mereset sandinya.
 */
class Migration_Nonaktifkan_akun_demo extends CI_Migration {

    const DEMO = [
        'admin@klinikpkp.jatengprov.go.id',
        'warga@example.com',
        'pengembang@example.com',
        'universitas@example.com',
        'mahasiswa@example.com',
        'adminkabkota@example.com',
        'adminbidang@example.com',
        'adminbidang.kawasan@example.com',
        'adminbidang.pertanahan@example.com',
        'adminbidang.perencanaan@example.com',
        'adminbidang.sekretariat@example.com',
        'dev1@example.com',
    ];

    /** Super Admin di luar akun demo yang aktif dan bisa masuk (sandi atau Google). Dipakai juga Migrate::status. */
    public static function sql_admin_siap()
    {
        return "SELECT COUNT(*) n FROM usr_akun WHERE peran = 'admin' AND LOWER(TRIM(COALESCE(status, ''))) <> 'nonaktif'
            AND LOWER(email) NOT IN ('" . implode("','", self::DEMO) . "')
            AND (COALESCE(kata_sandi, '') <> '' OR COALESCE(google_id, '') <> '')";
    }

    public function up()
    {
        $this->tanpa_debug(function () {
            if ($this->n(self::sql_admin_siap()) < 1) {
                throw new RuntimeException('Migrasi 073 ditolak: 0 Super Admin aktif yang bisa masuk di luar akun demo. '
                    . 'Buat dulu dengan `php index.php akun buat_superadmin <email>`, masuk dengan Google, buat sandi, lalu ulangi.');
            }
            $daftar = "LOWER(email) IN ('" . implode("','", self::DEMO) . "')";
            $q = $this->db->query("SELECT id, status FROM usr_akun WHERE $daftar");
            if ($q === FALSE) { throw new RuntimeException('Migrasi 073 gagal membaca usr_akun: ' . $this->db->error()['message']); }
            $sebelum = array_column($q->result_array(), 'status', 'id');
            $this->db->trans_begin();
            $this->wajib("UPDATE usr_akun SET status = 'nonaktif', kata_sandi = NULL,
                sesi_aktif_hash = NULL, sesi_aktif_id_hash = NULL, sesi_aktif_at = NULL WHERE $daftar");
            $this->wajib('INSERT INTO sys_jejak_audit (aksi, objek_tipe, ringkasan, detail_json, created_at) VALUES ('
                . $this->db->escape('akun_demo_dinonaktifkan') . ", 'usr_akun', "
                . $this->db->escape(count($sebelum) . ' akun demo dinonaktifkan dan sandinya dihapus (migrasi 073)') . ', '
                . $this->db->escape(json_encode(['status_sebelum' => $sebelum])) . ', NOW())');
            $this->db->trans_commit();
        });
    }

    public function down()
    {
        $this->tanpa_debug(function () {
            $q = $this->db->query("SELECT detail_json FROM sys_jejak_audit WHERE aksi = 'akun_demo_dinonaktifkan' ORDER BY id DESC LIMIT 1");
            $sebelum = $q ? (json_decode((string) $q->row('detail_json'), TRUE)['status_sebelum'] ?? []) : [];
            $this->db->trans_begin();
            foreach ($sebelum as $id => $status) {
                if ( ! in_array($status, ['restricted', 'active', 'nonaktif'], TRUE)) { continue; }
                $this->wajib('UPDATE usr_akun SET status = ' . $this->db->escape($status) . ' WHERE id = ' . (int) $id
                    . " AND status = 'nonaktif' AND LOWER(email) IN ('" . implode("','", self::DEMO) . "')");
            }
            $this->wajib('INSERT INTO sys_jejak_audit (aksi, objek_tipe, ringkasan, created_at) VALUES ('
                . $this->db->escape('akun_demo_dipulihkan') . ", 'usr_akun', "
                . $this->db->escape(count($sebelum) . ' status akun demo dipulihkan (migrasi 073 turun); sandi tetap kosong') . ', NOW())');
            $this->db->trans_commit();
        });
    }

    /** db_debug mati supaya galat jadi pengecualian berpesan hitungan, dipulihkan walau gagal (pola 069/071). */
    private function tanpa_debug(callable $kerja)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try { $kerja(); }
        catch (Throwable $e) { $this->db->trans_rollback(); throw $e; } // tanpa transaksi terbuka: no-op
        finally { $this->db->db_debug = $debug; }
    }

    private function n($sql)
    {
        $q = $this->db->query($sql);
        if ($q === FALSE) { throw new RuntimeException('Migrasi 073 gagal membaca: ' . $this->db->error()['message']); }
        return (int) $q->row('n');
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 073 gagal: ' . preg_replace('/\s+/', ' ', $sql) . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
