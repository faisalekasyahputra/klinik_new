<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyapu retensi: menghapus informasi yang sudah kedaluwarsa (form keamanan poin 7.3).
 *
 * Sebelum ini "Retensi belum ada penyapunya": snapshot SIMPERUM (terenkripsi, berisi profil RTLH
 * per NIK) punya expires_at tetapi tidak ada pekerjaan yang menghapusnya, sehingga menumpuk selamanya.
 * Kebijakan (hari) ada di config/data_lifecycle.php. Tidak bergantung pada CodeIgniter selain
 * pengambilan koneksi DB bawaan, jadi dapat diuji dengan adaptor DB (tests/data_lifecycle_db_test.php).
 *
 * Yang disapu:
 *   - snapshot SIMPERUM yang sudah lewat expires_at + masa tenggang, KECUALI yang dirujuk penilaian
 *     yang sudah dikirim (bukan draf): itu bukti asal data sebuah arsip;
 *   - penghitung batas laju lama, token surel yang sudah kedaluwarsa, langganan push yang dinonaktifkan;
 *   - log aplikasi lebih tua dari batas, dan jejak audit lebih tua dari batas (5 tahun; sengaja lama);
 *   - cache respons layanan luar di application/cache (*.json yang tak tersegarkan sekian hari, dan bendera
 *     *_gagal.flag yang lewat sehari): namanya ber-md5 URL atau id lokasi, jadi menumpuk tanpa batas.
 *     Cache yang masih dipakai tersegarkan tiap TTL (paling lama 1 hari), jadi yang disapu hanya cadangan
 *     basi yang sudah lama tidak diminta. index.html, .htaccess, dan penanda retensi tidak pernah disentuh.
 *   - salinan foto SIKUMBANG di assets/cache_foto (Index::buka_foto): yang lebih tua dari cache_foto_hari,
 *     lalu yang tertua sampai total folder di bawah cache_foto_maks_mb;
 *   - draf penilaian yang dilepas saat NIK dipindahkan ke pemilik terverifikasi (superseded tanpa
 *     submitted_at, Housing_assessment_model::pindahkan_ikatan_nik) lebih tua dari draf_nik_dipindah_hari,
 *     beserta berkas buktinya (Data_erasure::sapu_berkas_draf).
 * Tidak disapu (sengaja): sesi (dikelola PHP/hosting) dan berkas unggahan (dimiliki akun; dihapus lewat
 * hapus akun).
 *
 * Dijalankan sekali per interval: dipicu permintaan web SESUDAH respons terkirim (MY_Controller), atau
 * `php index.php retensi jalankan [kering]` dari CLI/cron. Mode kering hanya menghitung.
 */
class Penyapu_retensi {

    private $db;
    private $policy;
    private $app;
    private $root;
    private $web;

    /** @param array $params db (adaptor: query(), affected_rows()), policy (isi 'retensi'), app (akar application/ berakhiran pemisah) */
    public function __construct(array $params = [])
    {
        if (isset($params['policy'])) {
            $this->policy = $params['policy'];
        } else {
            $config = [];
            require dirname(__DIR__) . '/config/data_lifecycle.php';
            $this->policy = $config['data_lifecycle']['retensi'];
        }
        $this->db = $params['db'] ?? (function_exists('get_instance') ? get_instance()->db : NULL);
        $this->app = rtrim($params['app'] ?? (defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/'), '/\\') . DIRECTORY_SEPARATOR;
        $this->root = $params['root'] ?? NULL; // akar berkas privat untuk Data_erasure; bawaan private_uploads_root()
        // Akar web (FCPATH) untuk assets/cache_foto. Bawaan: induk application/, jadi uji yang memberi app
        // sementara tidak pernah menyentuh folder cache nyata.
        $this->web = rtrim($params['web'] ?? dirname($this->app), '/\\') . DIRECTORY_SEPARATOR;
    }

    public function interval() { return (int) $this->policy['interval_detik']; }

    /** TRUE bila penanda terakhir jalan sudah lebih tua dari interval (atau belum ada). */
    public static function jatuh_tempo($marker, $interval, $sekarang = NULL)
    {
        $sekarang = $sekarang ?? time();
        clearstatcache(true, $marker);
        return ! is_file($marker) || (int) @filemtime($marker) <= $sekarang - (int) $interval;
    }

    /**
     * @return array {kering:bool, tugas:array<string,array{jumlah:int, galat:?string}>, total:int}
     */
    public function jalankan($kering = FALSE)
    {
        $p = $this->policy;
        $sn = (int) $p['snapshot_simperum_lewat_hari'];
        $tugas = [
            'snapshot_simperum' => [
                "FROM sf_rekaman_simperum WHERE expires_at < (NOW() - INTERVAL {$sn} DAY)
                    AND id NOT IN (SELECT rekaman_simperum_id FROM sf_penilaian_perumahan
                                   WHERE rekaman_simperum_id IS NOT NULL AND status <> 'draft')", 'DELETE'],
            'rate_limit' => ['FROM sys_batas_laju WHERE jendela_mulai_at < (NOW() - INTERVAL ' . (int) $p['rate_limit_hari'] . ' DAY)', 'DELETE'],
            'langganan_push_nonaktif' => ['FROM sys_langganan_notifikasi WHERE aktif = 0 AND updated_at < (NOW() - INTERVAL ' . (int) $p['langganan_push_nonaktif_hari'] . ' DAY)', 'DELETE'],
            'jejak_audit' => ['FROM sys_jejak_audit WHERE created_at < (NOW() - INTERVAL ' . (int) $p['jejak_audit_hari'] . ' DAY)', 'DELETE'],
        ];
        $hasil = [];
        foreach ($tugas as $nama => [$dari, $jenis]) {
            $hasil[$nama] = $this->sql($dari, $kering);
        }
        $tk = (int) $p['token_surel_lewat_hari'];
        $hasil['token_surel'] = $this->sql_ubah(
            "FROM usr_akun WHERE token_email IS NOT NULL AND token_email_kedaluwarsa < (NOW() - INTERVAL {$tk} DAY)",
            'UPDATE usr_akun SET token_email = NULL, token_email_kedaluwarsa = NULL WHERE token_email IS NOT NULL AND token_email_kedaluwarsa < (NOW() - INTERVAL ' . $tk . ' DAY)',
            $kering);
        $hasil['log_aplikasi'] = $this->sapu_log((int) $p['log_aplikasi_hari'], $kering);
        $hasil['cache_hulu'] = $this->sapu_cache((int) $p['cache_hulu_hari'], $kering);
        $hasil['cache_foto'] = $this->sapu_foto((int) ($p['cache_foto_hari'] ?? 30), (int) ($p['cache_foto_maks_mb'] ?? 512), $kering);
        $hasil['draf_nik_dipindah'] = $this->sapu_draf_nik_dipindah((int) ($p['draf_nik_dipindah_hari'] ?? 30), $kering);

        $total = 0;
        foreach ($hasil as $h) { $total += (int) $h['jumlah']; }
        return ['kering' => (bool) $kering, 'tugas' => $hasil, 'total' => $total];
    }

    /** Catat hasil satu putaran (bukan mode kering) di jejak audit; hanya jumlah, tanpa isi data. */
    public function catat(array $hasil, $ip = 'sistem')
    {
        if ($hasil['kering']) { return FALSE; }
        $ringkas = [];
        foreach ($hasil['tugas'] as $nama => $h) { $ringkas[$nama] = $h['galat'] === NULL ? $h['jumlah'] : 'galat'; }
        try {
            return (bool) $this->db->query(
                'INSERT INTO sys_jejak_audit (pelaku_id, pelaku_email, pelaku_peran, aksi, objek_tipe, objek_id, ringkasan, detail_json, ip, created_at)
                 VALUES (NULL, NULL, ?, ?, ?, NULL, ?, ?, ?, NOW())',
                ['sistem', 'retensi_dijalankan', 'retensi', 'Penyapu retensi: ' . $hasil['total'] . ' entri kedaluwarsa dihapus/dibersihkan', json_encode($ringkas), substr((string) $ip, 0, 45)]
            );
        } catch (Throwable $e) {
            if (function_exists('log_message')) { log_message('error', 'Retensi: gagal mencatat audit: ' . $e->getMessage()); }
            return FALSE;
        }
    }

    // ------------------------------------------------------------------
    private function sql($dari, $kering)
    {
        try {
            if ($kering) {
                $r = $this->db->query('SELECT COUNT(*) AS n ' . $dari);
                $baris = $r ? $r->row_array() : NULL;
                return ['jumlah' => (int) ($baris['n'] ?? 0), 'galat' => NULL];
            }
            $ok = $this->db->query('DELETE ' . $dari);
            return ['jumlah' => $ok ? (int) $this->db->affected_rows() : 0, 'galat' => $ok ? NULL : 'kueri gagal'];
        } catch (Throwable $e) {
            return ['jumlah' => 0, 'galat' => get_class($e)];
        }
    }

    private function sql_ubah($dari_hitung, $ubah, $kering)
    {
        try {
            if ($kering) {
                $r = $this->db->query('SELECT COUNT(*) AS n ' . $dari_hitung);
                $baris = $r ? $r->row_array() : NULL;
                return ['jumlah' => (int) ($baris['n'] ?? 0), 'galat' => NULL];
            }
            $ok = $this->db->query($ubah);
            return ['jumlah' => $ok ? (int) $this->db->affected_rows() : 0, 'galat' => $ok ? NULL : 'kueri gagal'];
        } catch (Throwable $e) {
            return ['jumlah' => 0, 'galat' => get_class($e)];
        }
    }

    /** Draf yang dilepas saat NIK dipindahkan: berkas dulu (tidak bisa di-rollback), lalu barisnya. */
    private function sapu_draf_nik_dipindah($hari, $kering)
    {
        $syarat = "status = 'superseded' AND submitted_at IS NULL AND updated_at < (NOW() - INTERVAL {$hari} DAY)";
        if ($kering) { return $this->sql('FROM sf_penilaian_perumahan WHERE ' . $syarat, TRUE); }
        try {
            $r = $this->db->query('SELECT id FROM sf_penilaian_perumahan WHERE ' . $syarat);
            if ( ! $r) { return ['jumlah' => 0, 'galat' => 'kueri gagal']; }
            $ids = array_map('intval', array_column($r->result_array(), 'id'));
            if ( ! $ids) { return ['jumlah' => 0, 'galat' => NULL]; }
            require_once __DIR__ . '/Data_erasure.php';
            (new Data_erasure(['db' => $this->db] + ($this->root !== NULL ? ['root' => $this->root] : [])))->sapu_berkas_draf($ids);
            $ok = $this->db->query('DELETE FROM sf_penilaian_perumahan WHERE id IN (' . implode(',', $ids) . ") AND status = 'superseded' AND submitted_at IS NULL");
            return ['jumlah' => $ok ? (int) $this->db->affected_rows() : 0, 'galat' => $ok ? NULL : 'kueri gagal'];
        } catch (Throwable $e) {
            return ['jumlah' => 0, 'galat' => get_class($e)];
        }
    }

    private function sapu_log($hari, $kering)
    {
        $dir = $this->app . 'logs' . DIRECTORY_SEPARATOR;
        if ( ! is_dir($dir)) { return ['jumlah' => 0, 'galat' => NULL]; }
        $batas = time() - $hari * 86400;
        $n = 0;
        foreach ((array) glob($dir . 'log-*.php') as $f) {
            if ( ! preg_match('/log-\d{4}-\d{2}-\d{2}\.php$/', $f) || (int) @filemtime($f) >= $batas) { continue; }
            if ($kering || @unlink($f)) { $n++; }
        }
        return ['jumlah' => $n, 'galat' => NULL];
    }

    /**
     * Salinan foto SIKUMBANG (Index::buka_foto) di assets/cache_foto: hanya <md5>.jpg. Yang lebih tua
     * dari $hari disapu, lalu yang tertua sampai total folder <= $maks_mb. Foto yang masih dipakai
     * diunduh ulang sekali saat diminta lagi. index.html dan berkas lain tidak disentuh.
     */
    private function sapu_foto($hari, $maks_mb, $kering)
    {
        $dir = $this->web . 'assets' . DIRECTORY_SEPARATOR . 'cache_foto' . DIRECTORY_SEPARATOR;
        if ( ! is_dir($dir)) { return ['jumlah' => 0, 'galat' => NULL]; }
        $berkas = [];
        foreach ((array) glob($dir . '*.jpg') as $f) {
            if (preg_match('/^[0-9a-f]{32}\.jpg$/', basename($f)) && is_file($f)) { $berkas[$f] = [(int) @filemtime($f), (int) @filesize($f)]; }
        }
        uasort($berkas, fn($a, $b) => $a[0] <=> $b[0]); // tertua dulu
        $total = array_sum(array_column($berkas, 1));
        $batas_umur = time() - $hari * 86400;
        $batas_byte = $maks_mb * 1048576;
        $n = 0;
        foreach ($berkas as $f => [$waktu, $ukuran]) {
            if ($waktu >= $batas_umur && $total <= $batas_byte) { break; }
            if ($kering || @unlink($f)) { $n++; $total -= $ukuran; }
        }
        return ['jumlah' => $n, 'galat' => NULL];
    }

    /** Cache hulu (cache_hulu_helper, Sikumbang, Sikaper, Ternak): hanya *.json dan *_gagal.flag di akar application/cache. */
    private function sapu_cache($hari, $kering)
    {
        $dir = $this->app . 'cache' . DIRECTORY_SEPARATOR;
        if ( ! is_dir($dir)) { return ['jumlah' => 0, 'galat' => NULL]; }
        // ponytail: bendera gagal hanya bermakna CACHE_HULU_JEDA_GAGAL detik, jadi batasnya tetap sehari.
        $batas = ['json' => time() - $hari * 86400, 'flag' => time() - 86400];
        $n = 0;
        foreach ($batas as $jenis => $sebelum) {
            foreach ((array) glob($dir . ($jenis === 'json' ? '*.json' : '*_gagal.flag')) as $f) {
                if ( ! is_file($f) || (int) @filemtime($f) >= $sebelum) { continue; }
                if ($kering || @unlink($f)) { $n++; }
            }
        }
        return ['jumlah' => $n, 'galat' => NULL];
    }
}
