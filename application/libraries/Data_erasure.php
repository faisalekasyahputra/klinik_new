<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penghapusan data pemilik saat akun dihapus (form keamanan poin 7.3), melengkapi
 * User_model::_cleanup_owned_files() (SRP2, surat pengantar KKN, dokumen onboarding).
 *
 * Kekosongan yang ditutup (ditemukan 21 Sep 2026 lewat pemeriksaan skema):
 *   - pendaftaran KKN/Magang ikut terhapus lewat FK CASCADE, tetapi hanya SATU dari lima kolom berkasnya
 *     (surat pengantar) yang dihapus dari disk; proposal, laporan akhir, surat balasan, dan surat SIMPERUM
 *     tertinggal sebagai berkas yatim;
 *   - DRAF penilaian warga (bukan arsip) tetap ada setelah akun dihapus (FK SET NULL), lengkap dengan
 *     data terenkripsi dan foto buktinya;
 *   - surel pelaku tetap terbaca di jejak audit dan surel pengirim tetap di forum_diskusi.email_user.
 * Yang SENGAJA tidak dihapus (arsip layanan/keputusan) ada di config/data_lifecycle.php beserta alasannya.
 *
 * Tidak bergantung pada CodeIgniter: menerima adaptor DB (query(), affected_rows()) dan akar berkas privat.
 */
class Data_erasure {

    private $db;
    private $root;
    private $pepper;

    /** @param array $params db (adaptor), root (akar private_uploads), pepper (kunci pseudonim; bawaan env) */
    public function __construct(array $params = [])
    {
        $this->db = $params['db'] ?? (function_exists('get_instance') ? get_instance()->db : NULL);
        if (isset($params['root'])) {
            $root = $params['root'];
        } else {
            require_once dirname(__DIR__) . '/helpers/private_upload_helper.php';
            $root = private_uploads_root();
        }
        $this->root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        $this->pepper = (string) ($params['pepper'] ?? getenv('KPKP_DATA_PEPPER'));
    }

    /** Pseudonim stabil (tanpa surel terbaca) untuk menautkan jejak audit satu akun yang sudah dihapus. */
    public static function pseudonim_surel($email, $pepper = '')
    {
        $email = strtolower(trim((string) $email));
        return 'akun-dihapus-' . substr($pepper !== '' ? hash_hmac('sha256', $email, $pepper) : hash('sha256', $email), 0, 12);
    }

    /**
     * Hapus berkas dan draf milik akun. Panggil SEBELUM baris akun dihapus (setelah itu tautan
     * ke pemilik hilang). @return array {berkas:int, draf:int}
     */
    public function sapu_berkas($user_id)
    {
        $user_id = (int) $user_id;
        $berkas = 0; $draf = 0;

        // Pendaftaran KKN/Magang: sapu SELURUH direktorinya (kelima kolom berkas, termasuk yang diunggah admin).
        foreach ($this->ids('SELECT id FROM kkn_magang_pendaftaran WHERE user_id = ?', [$user_id]) as $id) {
            $berkas += $this->sapu_direktori('kemitraan', $id);
        }

        // Draf penilaian warga: berkas buktinya dan barisnya (baris berkas dan rekomendasi ikut lewat CASCADE).
        $draf_ids = $this->ids("SELECT id FROM sf_penilaian_perumahan WHERE user_id = ? AND status = 'draft'", [$user_id]);
        foreach ($draf_ids as $id) { $berkas += $this->sapu_direktori('warga_assessment', $id); }
        if ($draf_ids) {
            $this->db->query('DELETE FROM sf_penilaian_perumahan WHERE id IN (' . implode(',', array_map('intval', $draf_ids)) . ')');
            $draf = count($draf_ids);
        }
        return ['berkas' => $berkas, 'draf' => $draf];
    }

    /**
     * Samarkan sisa identitas di jejak audit: surel pelaku diganti pseudonim, dan penyebutan surel itu di
     * ringkasan/rincian baris lain (mis. "Membuka kunci akun x@y" oleh admin) juga diganti.
     * @return int jumlah baris audit yang diubah
     */
    public function samarkan_audit($user_id, $email)
    {
        $user_id = (int) $user_id; $email = trim((string) $email);
        if ($email === '') { return 0; }
        $pseudo = self::pseudonim_surel($email, $this->pepper);
        $n = 0;
        $this->db->query('UPDATE sys_jejak_audit SET actor_email = ? WHERE actor_id = ?', [$pseudo, $user_id]);
        $n += (int) $this->db->affected_rows();
        foreach (['ringkasan', 'detail_json'] as $kolom) {
            $this->db->query("UPDATE sys_jejak_audit SET {$kolom} = REPLACE({$kolom}, ?, ?) WHERE {$kolom} LIKE ?", [$email, $pseudo, '%' . $email . '%']);
            $n += (int) $this->db->affected_rows();
        }
        return $n;
    }

    // ------------------------------------------------------------------
    private function ids($sql, array $binds)
    {
        $r = $this->db->query($sql, $binds);
        $hasil = [];
        if ($r && method_exists($r, 'result_array')) { foreach ($r->result_array() as $baris) { $hasil[] = (int) $baris['id']; } }
        return $hasil;
    }

    /** Hapus semua isi {akar}/{domain}/{id}/ lalu direktorinya; @return int jumlah berkas terhapus */
    private function sapu_direktori($domain, $id)
    {
        $dir = $this->root . preg_replace('/[^A-Za-z0-9_]/', '', (string) $domain) . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9_]/', '', (string) $id) . DIRECTORY_SEPARATOR;
        if ( ! is_dir($dir)) { return 0; }
        $n = 0;
        foreach ((array) scandir($dir) as $f) {
            if ($f === '.' || $f === '..') { continue; }
            if (is_file($dir . $f) && @unlink($dir . $f)) { $n++; }
        }
        @rmdir($dir);
        return $n;
    }
}
