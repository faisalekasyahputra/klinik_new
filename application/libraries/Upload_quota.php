<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kuota unggahan per pengguna: jumlah berkas dan total ukuran (form keamanan poin 11.1).
 *
 * Tidak bergantung pada CodeIgniter dan tidak butuh tabel baru (tanpa migrasi). Buku kuota
 * berupa "penanda" kosong per berkas di {akar}/_pemilik/{pengguna}/ dengan nama
 *   {domain}~{pemilik}~{nama_tersimpan}~{ukuran_dilaporkan}
 * yang menunjuk ke berkas sungguhan di {akar}/{domain}/{pemilik}/{nama_tersimpan}.
 *
 * Pemakaian dihitung dari KEADAAN DISK, bukan dari penghitung yang bisa melenceng: penanda
 * yang berkasnya sudah tidak ada (dihapus admin, diganti, akun dihapus, dan puluhan titik
 * hapus lain di aplikasi) dibuang saat dihitung, jadi tidak perlu kait penghapusan di mana pun.
 * Pemesanan (penanda dibuat sebelum berkas dipindah) dilindungi flock supaya dua unggahan
 * bersamaan tidak sama-sama lolos melewati batas.
 */
class Upload_quota {

    private $root;
    private $ledger;
    private $quota;
    private $ttl;

    /** @param array $params root (akar private_uploads, wajib diakhiri pemisah), policy (isi 'upload_policy') */
    public function __construct(array $params = [])
    {
        $policy = $params['policy'] ?? NULL;
        if ($policy === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/upload_policy.php';
            $policy = $config['upload_policy'];
        }
        $root = $params['root'] ?? NULL;
        if ($root === NULL) {
            require_once dirname(__DIR__) . '/helpers/private_upload_helper.php';
            $root = private_uploads_root();
        }
        $this->root = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;
        $this->ledger = $this->root . $policy['ledger_dir'] . DIRECTORY_SEPARATOR;
        $this->quota = $policy['quota'];
        $this->ttl = (int) $policy['reservation_ttl'];
    }

    /**
     * Identitas pengguna untuk buku kuota dan batasnya.
     * @return array {actor: string, limits: array, label: string}
     */
    public function identify($user_id, $role, $ip)
    {
        $user_id = (int) $user_id;
        if ($user_id > 0) {
            $limits = $this->quota[(string) $role] ?? $this->quota['_default'];
            return ['actor' => 'u' . $user_id, 'limits' => $limits, 'label' => (string) $role];
        }
        return ['actor' => 'ip_' . substr(hash('sha256', (string) $ip), 0, 20), 'limits' => $this->quota['anon'], 'label' => 'anon'];
    }

    /** @return array {files:int, bytes:int} */
    public function usage($actor, array $limits = [])
    {
        return $this->hitung($this->dir($actor), $limits);
    }

    /**
     * Pesan satu berkas baru. Panggil SEBELUM memindahkan berkas; bila berkas gagal dipindah,
     * panggil release(). @return bool FALSE (dan $error terisi) bila kuota terlampaui.
     */
    public function reserve($actor, array $limits, $domain, $owner_id, $stored_name, $size, &$error = NULL)
    {
        $dir = $this->dir($actor);
        if ( ! is_dir($dir) && ! @mkdir($dir, 0700, TRUE) && ! is_dir($dir)) {
            $error = 'Gagal menyiapkan pencatatan kuota unggahan.';
            return FALSE;
        }
        $kunci = @fopen($dir . '.kunci', 'c');
        if ( ! $kunci) { $error = 'Gagal menyiapkan pencatatan kuota unggahan.'; return FALSE; }
        try {
            flock($kunci, LOCK_EX);
            $pakai = $this->hitung($dir, $limits);
            if ($pakai['files'] + 1 > (int) $limits['files']) {
                $error = 'Kuota unggahan penuh: maksimal ' . (int) $limits['files'] . ' berkas. Hapus berkas yang tidak diperlukan atau hubungi admin.';
                return FALSE;
            }
            if ($pakai['bytes'] + (int) $size > (int) $limits['bytes']) {
                $error = 'Kuota unggahan penuh: total maksimal ' . round($limits['bytes'] / 1048576) . ' MB. Hapus berkas yang tidak diperlukan atau hubungi admin.';
                return FALSE;
            }
            $ok = @file_put_contents($dir . $this->nama_penanda($domain, $owner_id, $stored_name, $size), '') !== FALSE;
            if ( ! $ok) { $error = 'Gagal mencatat kuota unggahan.'; }
            return $ok;
        } finally {
            flock($kunci, LOCK_UN);
            fclose($kunci);
        }
    }

    public function release($actor, $domain, $owner_id, $stored_name)
    {
        $dir = $this->dir($actor);
        foreach ((array) glob($dir . $this->bersih($domain) . '~' . $this->bersih($owner_id) . '~' . $this->bersih_nama($stored_name) . '~*') as $p) { @unlink($p); }
    }

    /** Hapus seluruh buku kuota satu pengguna (mis. saat akun dihapus). */
    public function forget($actor)
    {
        $dir = $this->dir($actor);
        if ( ! is_dir($dir)) { return; }
        foreach ((array) scandir($dir) as $f) {
            if ($f !== '.' && $f !== '..') { @unlink($dir . $f); }
        }
        @rmdir($dir);
    }

    // ------------------------------------------------------------------
    private function hitung($dir, array $limits)
    {
        $hasil = ['files' => 0, 'bytes' => 0];
        if ( ! is_dir($dir)) { return $hasil; }
        $window = (int) ($limits['window'] ?? 0);
        $sekarang = time();
        foreach ((array) scandir($dir) as $f) {
            if ($f === '.' || $f === '..' || $f[0] === '.') { continue; }
            $p = $dir . $f;
            $mtime = (int) @filemtime($p);
            $bagian = explode('~', $f);
            if (count($bagian) !== 4) { @unlink($p); continue; }
            [$domain, $owner, $stored, $lapor] = $bagian;
            if ($window > 0 && $mtime < $sekarang - $window) { @unlink($p); continue; }
            $nyata = $this->root . $domain . DIRECTORY_SEPARATOR . $owner . DIRECTORY_SEPARATOR . $stored;
            if (is_file($nyata)) { $ukuran = (int) filesize($nyata); }
            elseif ($mtime >= $sekarang - $this->ttl) { $ukuran = (int) $lapor; }   // pemesanan yang belum disusul berkasnya
            else { @unlink($p); continue; }                                          // berkas sudah tidak ada: penanda basi
            $hasil['files']++;
            $hasil['bytes'] += $ukuran;
        }
        return $hasil;
    }

    private function dir($actor)
    {
        return $this->ledger . $this->bersih($actor) . DIRECTORY_SEPARATOR;
    }

    private function nama_penanda($domain, $owner_id, $stored_name, $size)
    {
        return $this->bersih($domain) . '~' . $this->bersih($owner_id) . '~' . $this->bersih_nama($stored_name) . '~' . (int) $size;
    }

    /** Domain, pemilik, dan pengguna: sama ketatnya dengan private_uploads_dir() (tanpa titik). */
    private function bersih($s)
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', (string) $s);
    }

    /** Nama berkas tersimpan (acak heksadesimal + ekstensi): titik diizinkan, tetapi tidak berupa titik saja. */
    private function bersih_nama($s)
    {
        return basename(preg_replace('/[^A-Za-z0-9_.]/', '', (string) $s));
    }
}
