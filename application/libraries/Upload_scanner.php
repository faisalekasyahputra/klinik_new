<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pemindai isi berkas unggahan dari sumber tak tepercaya (form keamanan poin 11.4).
 *
 * Tidak bergantung pada CodeIgniter (dapat diuji offline). Dua lapis:
 *  1. Pemindai bawaan (selalu jalan): tanda tangan kode/skrip tertanam, string uji antivirus
 *     EICAR, dan pemeriksaan struktur per jenis berkas: PDF (konten aktif, lampiran tertanam,
 *     enkripsi), XLSX/XLS (makro, objek tertanam, tautan luar, entitas XML, bom zip), gambar
 *     (tipe sebenarnya, bom dekompresi).
 *  2. ClamAV lewat soket clamd (protokol INSTREAM) BILA env CLAMD_ADDRESS diisi, mis.
 *     unix:///var/run/clamav/clamd.ctl atau tcp://127.0.0.1:3310. Bila diisi tetapi tidak
 *     terjangkau, berkas DITOLAK (fail-closed), bukan diloloskan diam-diam.
 *
 * BATAS YANG DIAKUI: lapis 1 adalah pemeriksaan pola dan struktur, BUKAN antivirus berbasis
 * tanda tangan yang menyeluruh. Ia menangkap kelas serangan yang relevan bagi aplikasi ini
 * (kode PHP/skrip yang diselundupkan, PDF/Excel aktif, bom kompresi), bukan semua malware.
 * Hosting saat ini tidak menyediakan ClamAV, sehingga lapis 2 tidak aktif di production.
 */
class Upload_scanner {

    const POLA = [
        // `<?=` hanya 3 byte: tanpa konteks ia muncul acak di berkas biner besar (gambar/PDF) dan menolak berkas sah.
        // Karena itu dibatasi pada bentuk yang benar-benar dapat dieksekusi (diikuti variabel, backtick, atau fungsi eksekusi).
        'kode_php'      => '/<\?php|<\?=\s*(?:\$|`|\b(?:system|exec|passthru|shell_exec|eval|assert|include|require)\b)/i',
        'skrip_html'    => '/<script[\s>\/]|<iframe[\s>\/]|<html[\s>]|<object[\s>]|<embed[\s>]/i',
        'kode_dinamis'  => '/\b(?:eval|assert|system|passthru|shell_exec)\s*\(\s*(?:\$_(?:GET|POST|REQUEST|COOKIE)|base64_decode|gzinflate|str_rot13)/i',
        'eicar'         => '/EICAR-STANDARD-ANTIVIRUS-TEST-FILE/',
    ];

    const PDF_AKTIF = '#/(JavaScript|JS|Launch|EmbeddedFiles?|RichMedia|XFA|SubmitForm|ImportData|GoToE|Movie|Sound)(?![A-Za-z0-9])#';

    const PESAN = [
        'pdf_terenkripsi'         => 'PDF yang dikunci atau berkata sandi tidak dapat dipindai. Simpan ulang tanpa kata sandi lalu unggah kembali.',
        'pdf_aktif'               => 'PDF berisi konten aktif (skrip atau lampiran tertanam) yang tidak diizinkan. Cetak ulang menjadi PDF biasa.',
        'pdf_tak_dapat_dipindai'  => 'Isi PDF tidak dapat dipindai. Cetak ulang menjadi PDF biasa lalu unggah kembali.',
        'excel_berbahaya'         => 'Berkas Excel berisi makro, objek tertanam, atau tautan luar yang tidak diizinkan. Simpan sebagai XLSX tanpa makro.',
        'gambar_terlalu_besar'    => 'Dimensi gambar terlalu besar.',
        'pemindai_tak_tersedia'   => 'Pemindai berkas sedang tidak tersedia, sehingga unggahan ditolak demi keamanan. Coba lagi nanti.',
        'kosong'                  => 'Berkas kosong.',
        'terlalu_besar'           => 'Ukuran berkas melebihi batas pemindaian.',
        'tipe_tak_sesuai'         => 'Isi berkas tidak sesuai dengan jenis yang dipilih.',
    ];
    const PESAN_UMUM = 'Berkas ditolak karena terdeteksi mengandung konten berbahaya atau tidak dapat dipastikan aman.';

    private $cfg;
    private $clamd;

    /** @param array $params policy (isi 'scan'), clamd (alamat; NULL = ambil dari env, '' = nonaktif) */
    public function __construct(array $params = [])
    {
        $scan = $params['policy']['scan'] ?? NULL;
        if ($scan === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/upload_policy.php';
            $scan = $config['upload_policy']['scan'];
        }
        $this->cfg = $scan;
        $this->clamd = array_key_exists('clamd', $params) ? (string) $params['clamd'] : (string) getenv('CLAMD_ADDRESS');
    }

    /**
     * @param string $path berkas yang akan dipindai (mis. tmp_name unggahan)
     * @param string $ext  ekstensi yang diklaim pengunggah, tanpa titik
     * @return array {ok, code, message, detail, sha256, size}
     */
    public function scan($path, $ext)
    {
        $ext = strtolower((string) $ext);
        if ( ! is_file($path)) { return $this->gagal('kosong', 'berkas tidak ada'); }
        $size = (int) filesize($path);
        if ($size <= 0) { return $this->gagal('kosong', 'ukuran 0'); }
        $sha = (string) hash_file('sha256', $path);
        $tambah = ['sha256' => $sha, 'size' => $size];
        if ($size > (int) $this->cfg['max_bytes']) { return $this->gagal('terlalu_besar', "$size byte") + $tambah; }

        try {
            if ($ext === 'pdf') { $r = $this->scan_pdf($path, $size); }
            elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], TRUE)) { $r = $this->scan_gambar($path, $ext); }
            elseif (in_array($ext, ['xlsx', 'xls'], TRUE)) { $r = $this->scan_excel($path); }
            else { $r = ['ok' => TRUE]; }
            if ( ! empty($r['ok'])) { $r = $this->scan_pola_berkas($path); }
            if ( ! empty($r['ok']) && $this->clamd !== '') { $r = $this->scan_clamd($path); }
        } catch (Throwable $e) {
            // Pemindai yang gagal bukan alasan meloloskan berkas.
            $r = $this->gagal('pemindai_tak_tersedia', 'galat: ' . get_class($e));
        }
        return $r + $tambah;
    }

    // ------------------------------------------------------------------ pola teks/biner umum
    /** Pindai string (mis. isi entri zip). @return string|NULL nama pola yang cocok */
    public function cocok_pola($data)
    {
        foreach (self::POLA as $nama => $re) {
            if (preg_match($re, $data)) { return $nama; }
        }
        return NULL;
    }

    private function scan_pola_berkas($path)
    {
        $fh = fopen($path, 'rb');
        if ( ! $fh) { return $this->gagal('pemindai_tak_tersedia', 'berkas tak terbaca'); }
        $ekor = '';
        try {
            while ( ! feof($fh)) {
                $potong = fread($fh, 1048576);
                if ($potong === FALSE || $potong === '') { break; }
                $buf = $ekor . $potong;
                if (($nama = $this->cocok_pola($buf)) !== NULL) { return $this->gagal($nama, 'pola ' . $nama . ' di isi berkas'); }
                $ekor = substr($buf, -256);
            }
        } finally { fclose($fh); }
        // Shebang hanya bermakna di awal berkas.
        $awal = (string) file_get_contents($path, FALSE, NULL, 0, 64);
        if (preg_match('#^\#!\s*/(usr/)?(bin|env)#', $awal)) { return $this->gagal('shebang', 'skrip shell di awal berkas'); }
        return ['ok' => TRUE];
    }

    // ------------------------------------------------------------------ gambar
    private function scan_gambar($path, $ext)
    {
        $info = @getimagesize($path);
        if ($info === FALSE) { return $this->gagal('tipe_tak_sesuai', 'bukan gambar sah'); }
        $tipe = [IMAGETYPE_JPEG => ['jpg', 'jpeg'], IMAGETYPE_PNG => ['png'], IMAGETYPE_GIF => ['gif'], IMAGETYPE_WEBP => ['webp']][$info[2]] ?? [];
        if ( ! in_array($ext, $tipe, TRUE)) { return $this->gagal('tipe_tak_sesuai', 'tipe gambar tidak cocok dengan ekstensi'); }
        if ((int) $info[0] < 1 || (int) $info[1] < 1 || (int) $info[0] * (int) $info[1] > (int) $this->cfg['image_max_pixels']) {
            return $this->gagal('gambar_terlalu_besar', $info[0] . 'x' . $info[1]);
        }
        return ['ok' => TRUE];
    }

    // ------------------------------------------------------------------ PDF
    private function scan_pdf($path, $size)
    {
        $d = (string) file_get_contents($path);
        if (strpos(substr($d, 0, 1024), '%PDF-') === FALSE) { return $this->gagal('tipe_tak_sesuai', 'tanpa penanda %PDF-'); }

        [$luar, $objstm, $tak_terbaca] = $this->pisah_pdf($d);
        if ($tak_terbaca) { return $this->gagal('pdf_tak_dapat_dipindai', 'ObjStm tidak dapat didekompresi'); }
        $teks = $luar . "\n" . implode("\n", $objstm);
        // Nama PDF boleh disamarkan dengan #XX (mis. /J#61vaScript); normalkan sebelum dicocokkan.
        $teks = (string) preg_replace_callback('/#([0-9A-Fa-f]{2})/', function ($m) { return chr(hexdec($m[1])); }, $teks);

        if (preg_match('#/Encrypt(?![A-Za-z])#', $teks)) { return $this->gagal('pdf_terenkripsi', '/Encrypt'); }
        if (preg_match(self::PDF_AKTIF, $teks, $m)) { return $this->gagal('pdf_aktif', '/' . $m[1]); }
        if (preg_match('#/AA\s*<<#', $teks)) { return $this->gagal('pdf_aktif', '/AA'); }
        foreach ($objstm as $isi) {
            if (($nama = $this->cocok_pola($isi)) !== NULL) { return $this->gagal($nama, 'pola ' . $nama . ' di ObjStm'); }
        }
        return ['ok' => TRUE];
    }

    /**
     * Pisahkan bagian PDF di LUAR isi stream (kamus objek tempat nama seperti /JS berada) dari
     * isi stream yang biner (gambar, font, konten halaman: memindainya menghasilkan positif palsu).
     * Stream bertipe /ObjStm (objek terkompresi, tempat nama itu bisa bersembunyi) didekompresi
     * dengan batas ukuran. @return array [string luar, string[] isi ObjStm, bool ada ObjStm tak terbaca]
     */
    private function pisah_pdf($d)
    {
        $luar = ''; $objstm = []; $tak = FALSE; $pos = 0; $len = strlen($d); $hitung = 0;
        while (TRUE) {
            $s = strpos($d, 'stream', $pos);
            if ($s === FALSE) { $luar .= substr($d, $pos); break; }
            if ($s >= 3 && substr($d, $s - 3, 3) === 'end') { $luar .= substr($d, $pos, $s + 6 - $pos); $pos = $s + 6; continue; }
            $luar .= substr($d, $pos, $s + 6 - $pos);
            $b = $s + 6;
            if (substr($d, $b, 2) === "\r\n") { $b += 2; }
            elseif (substr($d, $b, 1) === "\n" || substr($d, $b, 1) === "\r") { $b += 1; }
            $e = strpos($d, 'endstream', $b);
            if ($e === FALSE) { $e = $len; }

            $kamus = substr($d, max(0, $s - 600), min(600, $s));
            $k = strrpos($kamus, 'obj');
            if ($k !== FALSE) { $kamus = substr($kamus, $k); }
            if (preg_match('#/Type\s*/ObjStm#', $kamus)) {
                if (++$hitung > (int) $this->cfg['pdf_objstm_count'] || ! preg_match('#/FlateDecode#', $kamus)) { $tak = TRUE; }
                else {
                    $mentah = rtrim(substr($d, $b, $e - $b), "\r\n");
                    $isi = @zlib_decode($mentah, (int) $this->cfg['pdf_objstm_max']);
                    if ($isi === FALSE) { $tak = TRUE; } else { $objstm[] = $isi; }
                }
            }
            $pos = $e;
        }
        return [$luar, $objstm, $tak];
    }

    // ------------------------------------------------------------------ Excel (XLSX zip / XLS OLE)
    private function scan_excel($path)
    {
        $awal = (string) file_get_contents($path, FALSE, NULL, 0, 8);
        if (substr($awal, 0, 4) === hex2bin('504b0304')) { return $this->scan_zip($path); }
        if ($awal === hex2bin('d0cf11e0a1b11ae1')) {
            $d = (string) file_get_contents($path);
            // Makro VBA hidup di penyimpanan OLE bernama VBA / _VBA_PROJECT_CUR (UTF-16LE).
            if (strpos($d, "V\0B\0A\0") !== FALSE || strpos($d, "M\0a\0c\0r\0o\0s\0") !== FALSE) {
                return $this->gagal('excel_berbahaya', 'penyimpanan makro VBA di berkas XLS');
            }
            return ['ok' => TRUE];
        }
        return $this->gagal('tipe_tak_sesuai', 'bukan berkas Excel');
    }

    private function scan_zip($path)
    {
        $z = new ZipArchive();
        if ($z->open($path, ZipArchive::RDONLY) !== TRUE) { return $this->gagal('tipe_tak_sesuai', 'zip rusak'); }
        try {
            $n = $z->numFiles;
            if ($n < 1 || $n > (int) $this->cfg['zip_max_entries']) { return $this->gagal('excel_berbahaya', "jumlah entri $n"); }
            $total = 0; $nama_ada = [];
            for ($i = 0; $i < $n; $i++) {
                $st = $z->statIndex($i);
                if ($st === FALSE) { return $this->gagal('excel_berbahaya', 'entri tak terbaca'); }
                $nama = (string) $st['name'];
                $nama_ada[$nama] = TRUE;
                if (preg_match('#(^|/)\.\.(/|$)|^/|\\\\#', $nama)) { return $this->gagal('excel_berbahaya', 'jalur entri berbahaya'); }
                if (preg_match('#vbaProject|/embeddings/|/externalLinks/|/activeX/|/macrosheets/|/customUI/|\.(exe|dll|js|vbs|php|bat|cmd|ps1|jar|hta|scr|lnk)$#i', $nama)) {
                    return $this->gagal('excel_berbahaya', 'entri terlarang: ' . $nama);
                }
                $ukuran = (int) $st['size'];
                $total += $ukuran;
                if ($total > (int) $this->cfg['zip_max_uncompressed']) { return $this->gagal('excel_berbahaya', 'bom zip: total terlalu besar'); }
                if ($ukuran > 1048576 && $ukuran / max(1, (int) $st['comp_size']) > (int) $this->cfg['zip_max_ratio']) {
                    return $this->gagal('excel_berbahaya', 'bom zip: rasio kompresi ' . $nama);
                }
                if ($ukuran > (int) $this->cfg['zip_entry_scan_max']) { continue; }
                $isi = $z->getFromIndex($i);
                if ($isi === FALSE) { return $this->gagal('excel_berbahaya', 'entri tak terbaca: ' . $nama); }
                if (preg_match('/\.(xml|rels)$/i', $nama)) {
                    if (preg_match('/<!(DOCTYPE|ENTITY)/i', $isi)) { return $this->gagal('excel_berbahaya', 'deklarasi XML DOCTYPE/ENTITY'); }
                    if (preg_match_all('/<Relationship\b[^>]*>/i', $isi, $rel)) {
                        foreach ($rel[0] as $tag) {
                            if (stripos($tag, 'TargetMode="External"') !== FALSE
                                && preg_match('#Type="[^"]*/(oleObject|externalLink[A-Za-z]*|attachedTemplate|frame|package|vbaProject)"#i', $tag)) {
                                return $this->gagal('excel_berbahaya', 'relasi eksternal berbahaya');
                            }
                        }
                    }
                }
                if (($pola = $this->cocok_pola($isi)) !== NULL) { return $this->gagal($pola, "pola $pola di entri $nama"); }
            }
            if (empty($nama_ada['[Content_Types].xml']) || empty($nama_ada['xl/workbook.xml'])) {
                return $this->gagal('tipe_tak_sesuai', 'bukan berkas XLSX');
            }
            return ['ok' => TRUE];
        } finally { $z->close(); }
    }

    // ------------------------------------------------------------------ ClamAV (clamd, INSTREAM)
    private function scan_clamd($path)
    {
        $alamat = $this->clamd;
        if ( ! preg_match('#^(unix:///|tcp://)#', $alamat)) { return $this->gagal('pemindai_tak_tersedia', 'CLAMD_ADDRESS tidak valid'); }
        $timeout = (int) $this->cfg['clamd_timeout'];
        $sock = @stream_socket_client($alamat, $errno, $errstr, min(5, $timeout));
        if ( ! $sock) { return $this->gagal('pemindai_tak_tersedia', 'clamd tidak terjangkau'); }
        stream_set_timeout($sock, $timeout);
        $fh = fopen($path, 'rb');
        if ( ! $fh) { fclose($sock); return $this->gagal('pemindai_tak_tersedia', 'berkas tak terbaca'); }
        try {
            fwrite($sock, "zINSTREAM\0");
            while ( ! feof($fh)) {
                $potong = fread($fh, 65536);
                if ($potong === FALSE || $potong === '') { break; }
                if (fwrite($sock, pack('N', strlen($potong)) . $potong) === FALSE) { return $this->gagal('pemindai_tak_tersedia', 'clamd menutup koneksi'); }
            }
            fwrite($sock, pack('N', 0));
            $balas = '';
            while ( ! feof($sock)) {
                $b = fread($sock, 4096);
                if ($b === FALSE || $b === '') { break; }
                $balas .= $b;
                if (strpos($balas, "\0") !== FALSE) { break; }
            }
        } finally { fclose($fh); fclose($sock); }
        $balas = rtrim($balas, "\0\r\n ");
        if (preg_match('/\bOK$/', $balas)) { return ['ok' => TRUE]; }
        if (preg_match('/:\s*(.+?)\s+FOUND$/', $balas, $m)) { return $this->gagal('antivirus', 'ClamAV: ' . $m[1]); }
        return $this->gagal('pemindai_tak_tersedia', 'balasan clamd tak dikenali');
    }

    private function gagal($code, $detail = '')
    {
        return ['ok' => FALSE, 'code' => $code, 'message' => self::PESAN[$code] ?? self::PESAN_UMUM, 'detail' => $detail];
    }
}
