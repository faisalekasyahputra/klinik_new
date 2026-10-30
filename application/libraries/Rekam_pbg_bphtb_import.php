<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Rekam_bnba_import.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Baca Excel PBG dan BPHTB (format dinas 30 Okt 2026) yang diunggah admin kabupaten/kota.
 *
 * Pola Rekam_bnba_import: kolom dicocokkan lewat NAMA judul, satu baris cacat MENOLAK
 * SELURUH berkas dengan nomor barisnya, sel dibaca mentah, rumus memakai nilai tersimpan
 * Excel, dan library ini tidak menyentuh DB. Yang dikembalikan hanya jumlah baris; NIK
 * dan nama tidak ikut keluar.
 *
 * PBG: dua lembar, PENGEMBANG dan PERORANGAN (dua tabel di contoh dinas). BPHTB: satu
 * lembar lebar (34 kolom dinas). Baris "Bulan" harus sama dengan bulan yang dipilih
 * saat mengunggah, supaya berkas Februari tidak tercatat sebagai Januari.
 */
class Rekam_pbg_bphtb_import
{
    const BATAS_BARIS = 5000;

    const BULAN = [1 => 'januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli',
        'agustus', 'september', 'oktober', 'november', 'desember'];

    /** Judul kolom lembar PENGEMBANG (sesudah dinormalkan, keterangan dalam kurung dibuang). */
    const KOLOM_PENGEMBANG = ['nama perumahan' => 'perumahan', 'nama pengembang' => 'pengembang',
        'alamat perumahan' => 'alamat', 'bentuk rumah' => 'bentuk', 'tipe' => 'tipe',
        'status data' => 'bulan', 'bulan' => 'bulan'];

    const KOLOM_PERORANGAN = ['nik' => 'nik', 'nama' => 'nama', 'alamat' => 'alamat',
        'kecamatan' => 'kec', 'kelurahan/desa' => 'desa', 'kode kelurahan/desa' => 'kode', 'bulan' => 'bulan'];

    /**
     * Judul BPHTB yang DIPERIKSA. Kolom dinas lain (nomor akta, notaris, jenis hak, NOP, ...)
     * boleh ada dan boleh kosong; isinya tidak dinilai di sini.
     */
    const KOLOM_BPHTB = ['nama kab/kota' => 'kab', 'tahun' => 'tahun', 'nama kecamatan' => 'kec',
        'nama kelurahan' => 'desa', 'pihak penerima' => 'penerima', 'nik penerima' => 'nik',
        'nik pihak pengalih pemberi' => 'nik_pengalih', 'penghasilan' => 'penghasilan',
        'luas tanah' => 'luas_tanah', 'luas bangunan' => 'luas_bangunan',
        'harga perolehan pengalihan hak' => 'harga', 'nilai njop' => 'njop', 'nilai sspd' => 'sspd',
        'nilai sspd bphtb' => 'sspd_bphtb', 'bulan bphtb' => 'bulan', 'apakah mbr atau bukan' => 'mbr'];
    const WAJIB_BPHTB = ['kab', 'tahun', 'kec', 'desa', 'penerima', 'nik', 'bulan', 'mbr'];
    const ANGKA_BPHTB = ['penghasilan' => 'Penghasilan', 'luas_tanah' => 'luas_tanah', 'luas_bangunan' => 'luas_bangunan',
        'harga' => 'harga_perolehan_pengalihan_hak', 'njop' => 'nilai_njop', 'sspd' => 'nilai_sspd', 'sspd_bphtb' => 'nilai_sspd_bphtb'];

    /**
     * @param array $kode_desa  [kode 10 angka => TRUE] desa milik kabupaten pelapor
     * @return array ['success','message','jumlah_baris','ringkasan']
     */
    public function baca_pbg($path, $bulan, array $kode_desa)
    {
        $buku = $this->muat($path);
        if (is_string($buku)) { return $this->gagal($buku); }
        $lembar = [];
        foreach (['PENGEMBANG', 'PERORANGAN'] as $nama) {
            $s = $buku->getSheetByName($nama);
            if ( ! $s) { return $this->gagal("Lembar $nama tidak ditemukan. Gunakan templat dari tombol \"Unduh templat PBG\"."); }
            $lembar[$nama] = $this->baris($s);
            if (count($lembar[$nama]) > self::BATAS_BARIS + 1) { return $this->gagal("Lembar $nama terlalu panjang. Maksimal " . self::BATAS_BARIS . ' baris.'); }
        }
        $cacat = [];
        $n_peng = $this->periksa_pengembang($lembar['PENGEMBANG'], $bulan, $cacat);
        $n_oran = $this->periksa_perorangan($lembar['PERORANGAN'], $bulan, $kode_desa, $cacat);
        if (is_string($n_peng)) { return $this->gagal($n_peng); }
        if (is_string($n_oran)) { return $this->gagal($n_oran); }
        if ($cacat) { return $this->gagal_baris($cacat); }
        if ($n_peng + $n_oran === 0) { return $this->gagal('Tidak ada baris yang bisa dibaca dari kedua lembar.'); }
        return ['success' => TRUE, 'message' => '', 'jumlah_baris' => $n_peng + $n_oran,
            'ringkasan' => "Pengembang $n_peng, perorangan $n_oran"];
    }

    /**
     * @param array $desa_nama [KECAMATAN_DESA huruf besar => TRUE] desa milik kabupaten pelapor
     */
    public function baca_bphtb($path, $tahun, $bulan, $nama_kabupaten, array $desa_nama)
    {
        $buku = $this->muat($path);
        if (is_string($buku)) { return $this->gagal($buku); }
        $baris = $this->baris($buku->getSheet(0));
        if (count($baris) > self::BATAS_BARIS + 1) { return $this->gagal('Berkas terlalu panjang. Maksimal ' . self::BATAS_BARIS . ' baris.'); }
        $kol = $this->judul(array_shift($baris), self::KOLOM_BPHTB);
        $hilang = array_diff(self::WAJIB_BPHTB, array_keys($kol));
        if ($hilang) {
            $nama = array_flip(self::KOLOM_BPHTB);
            return $this->gagal('Kolom tidak ditemukan pada baris pertama: ' . implode(', ', array_map(fn($k) => $nama[$k], $hilang))
                . '. Gunakan templat dari tombol "Unduh templat BPHTB".');
        }

        $cacat = []; $n = 0;
        foreach ($baris as $i => $r) {
            $no = $i + 2;
            $v = static fn($k) => Rekam_bnba_import::teks($r[$kol[$k] ?? -1] ?? '');
            if (self::kosong($r)) { continue; }
            // Baris contoh dan baris penomoran kolom di lembar contoh dinas dilewati, bukan ditolak.
            if (strtolower(Rekam_bnba_import::teks($r[0] ?? '')) === 'contoh' || preg_match('/^\d+$/', $v('kab'))) { continue; }

            if ( ! Rekam_bnba_import::kab_cocok($v('kab'), $nama_kabupaten)) { $cacat[] = "$no: Nama Kab/Kota bukan wilayah Anda ({$nama_kabupaten})"; continue; }
            if ($v('tahun') !== (string) $tahun) { $cacat[] = "$no: Tahun harus $tahun"; continue; }
            if ( ! isset($desa_nama[self::kunci_desa($v('kec'), $v('desa'))])) {
                $cacat[] = "$no: Nama Kecamatan/Nama Kelurahan tidak ditemukan di {$nama_kabupaten}. Samakan ejaannya dengan lembar KODE WILAYAH"; continue;
            }
            if ($v('penerima') === '') { $cacat[] = "$no: Pihak Penerima wajib diisi"; continue; }
            if ( ! preg_match('/^\d{16}$/', $v('nik'))) { $cacat[] = "$no: NIK Penerima harus 16 angka (kolom diformat Teks)"; continue; }
            if ($v('nik_pengalih') !== '' && ! preg_match('/^\d{16}$/', $v('nik_pengalih'))) {
                $cacat[] = "$no: NIK Pihak Pengalih Pemberi harus 16 angka atau dikosongkan"; continue;
            }
            foreach (self::ANGKA_BPHTB as $k => $label) {
                if ($v($k) !== '' && Rekam_bnba_import::rupiah($r[$kol[$k] ?? -1] ?? '') === NULL && ! is_numeric($v($k))) {
                    $cacat[] = "$no: $label harus angka"; continue 2;
                }
            }
            if (self::bulan_angka($v('bulan')) !== (int) $bulan) { $cacat[] = "$no: bulan_bphtb harus " . ucfirst(self::BULAN[$bulan]); continue; }
            if ( ! in_array(strtolower($v('mbr')), ['mbr', 'bukan mbr', 'non mbr', 'bukan'], TRUE)) {
                $cacat[] = "$no: Apakah MBR atau bukan diisi MBR atau Bukan MBR"; continue;
            }
            $n++;
        }
        if ($cacat) { return $this->gagal_baris($cacat); }
        if ($n === 0) { return $this->gagal('Tidak ada baris BPHTB yang bisa dibaca dari berkas ini.'); }
        return ['success' => TRUE, 'message' => '', 'jumlah_baris' => $n, 'ringkasan' => "$n transaksi"];
    }

    /** @return int|string jumlah baris sah, atau pesan bila judul kolom tidak lengkap */
    private function periksa_pengembang(array $baris, $bulan, array &$cacat)
    {
        $kol = $this->judul(array_shift($baris), self::KOLOM_PENGEMBANG);
        $hilang = array_diff(array_unique(self::KOLOM_PENGEMBANG), array_keys($kol));
        if ($hilang) { return 'Lembar PENGEMBANG: kolom ' . implode(', ', array_map('strtoupper', $hilang)) . ' tidak ditemukan pada baris pertama.'; }
        $n = 0;
        foreach ($baris as $i => $r) {
            $no = 'PENGEMBANG ' . ($i + 2);
            $v = static fn($k) => Rekam_bnba_import::teks($r[$kol[$k]] ?? '');
            if (self::kosong($r, [0])) { continue; }
            foreach (['perumahan' => 'Nama Perumahan', 'pengembang' => 'Nama Pengembang', 'alamat' => 'Alamat Perumahan', 'tipe' => 'Tipe'] as $k => $label) {
                if ($v($k) === '') { $cacat[] = "$no: $label wajib diisi"; continue 2; }
            }
            if ( ! in_array(strtolower($v('bentuk')), ['tapak', 'susun'], TRUE)) { $cacat[] = "$no: Bentuk Rumah diisi tapak atau susun"; continue; }
            if (self::bulan_angka($v('bulan')) !== (int) $bulan) { $cacat[] = "$no: Status Data (Bulan) harus " . ucfirst(self::BULAN[$bulan]); continue; }
            $n++;
        }
        return $n;
    }

    private function periksa_perorangan(array $baris, $bulan, array $kode_desa, array &$cacat)
    {
        $kol = $this->judul(array_shift($baris), self::KOLOM_PERORANGAN);
        $hilang = array_diff(self::KOLOM_PERORANGAN, array_keys($kol));
        if ($hilang) { return 'Lembar PERORANGAN: kolom ' . implode(', ', array_map('strtoupper', $hilang)) . ' tidak ditemukan pada baris pertama.'; }
        $n = 0; $nik_ada = [];
        foreach ($baris as $i => $r) {
            $no = 'PERORANGAN ' . ($i + 2);
            $v = static fn($k) => Rekam_bnba_import::teks($r[$kol[$k]] ?? '');
            // Kolom No dan kolom kode (berumus, berisi "" di baris kosong) tidak membuat baris terisi.
            if (self::kosong($r, [0, $kol['kode']])) { continue; }
            $nik = $v('nik');
            if ( ! preg_match('/^\d{16}$/', $nik)) { $cacat[] = "$no: NIK harus 16 angka (kolom NIK diformat Teks)"; continue; }
            if (isset($nik_ada[$nik])) { $cacat[] = "$no: NIK sama dengan baris {$nik_ada[$nik]}"; continue; }
            foreach (['nama' => 'NAMA', 'alamat' => 'ALAMAT', 'kec' => 'KECAMATAN', 'desa' => 'KELURAHAN/DESA'] as $k => $label) {
                if ($v($k) === '') { $cacat[] = "$no: $label wajib diisi"; continue 2; }
            }
            $kd = $v('kode');
            if ($kd === '' || $kd === '#N/A') { $cacat[] = "$no: KODE KELURAHAN/DESA kosong. Periksa ejaan KECAMATAN dan KELURAHAN/DESA agar sama dengan lembar KODE WILAYAH"; continue; }
            if ( ! isset($kode_desa[$kd])) { $cacat[] = "$no: KODE KELURAHAN/DESA $kd bukan kode desa di wilayah Anda"; continue; }
            if (self::bulan_angka($v('bulan')) !== (int) $bulan) { $cacat[] = "$no: Bulan harus " . ucfirst(self::BULAN[$bulan]); continue; }
            $nik_ada[$nik] = $i + 2;
            $n++;
        }
        return $n;
    }

    /** "Januari", "januari 2026", "1", 1 → 1. NULL bila tidak dikenali. */
    public static function bulan_angka($s)
    {
        $s = strtolower(trim((string) $s));
        if (preg_match('/^\d{1,2}$/', $s)) { $b = (int) $s; return ($b >= 1 && $b <= 12) ? $b : NULL; }
        foreach (self::BULAN as $b => $nama) {
            if (strpos($s, $nama) === 0) { return $b; }
        }
        return NULL;
    }

    /** Kunci pencocokan kecamatan + desa, sama dengan kolom gabungan lembar KODE WILAYAH. */
    public static function kunci_desa($kec, $desa)
    {
        return strtoupper(trim((string) $kec)) . '_' . strtoupper(trim((string) $desa));
    }

    /** Judul → [kunci => indeks]. Huruf kecil, garis bawah jadi spasi, keterangan "(...)" dibuang. */
    private function judul($baris, array $peta)
    {
        $kol = [];
        foreach ((array) $baris as $idx => $judul) {
            $j = strtolower((string) $judul);
            $j = preg_replace('/\(.*$/s', '', $j);
            $j = preg_replace('/\s*\/\s*/', '/', str_replace('_', ' ', $j));
            $j = trim(preg_replace('/\s+/', ' ', $j));
            if (isset($peta[$j]) && ! isset($kol[$peta[$j]])) { $kol[$peta[$j]] = $idx; }
        }
        return $kol;
    }

    /** @return \PhpOffice\PhpSpreadsheet\Spreadsheet|string */
    private function muat($path)
    {
        try {
            return IOFactory::load($path);
        } catch (\Throwable $e) {
            log_message('error', 'Rekam_pbg_bphtb_import: gagal membaca berkas - ' . $e->getMessage());
            return 'Berkas tidak dapat dibaca. Pastikan benar-benar Excel (XLSX/XLS), bukan berkas yang sekadar diganti namanya.';
        }
    }

    /** Sel mentah; sel berumus memakai nilai yang disimpan Excel (rumus kiriman tidak dijalankan). */
    private function baris($sheet)
    {
        $baris = $sheet->toArray(NULL, FALSE, FALSE, FALSE);
        foreach ($baris as $i => $r) {
            foreach ($r as $c => $v) {
                if (is_string($v) && strpos($v, '=') === 0) {
                    $baris[$i][$c] = $sheet->getCell([$c + 1, $i + 1])->getOldCalculatedValue();
                }
            }
        }
        return $baris;
    }

    private static function kosong(array $r, array $abaikan = [])
    {
        foreach ($r as $c => $v) {
            if ( ! in_array($c, $abaikan, TRUE) && Rekam_bnba_import::teks($v) !== '') { return FALSE; }
        }
        return TRUE;
    }

    private function gagal_baris(array $cacat)
    {
        $tampil = array_slice($cacat, 0, 10);
        $sisa = count($cacat) - count($tampil);
        return $this->gagal('Baris ' . implode('; Baris ', $tampil)
            . ($sisa > 0 ? " (dan $sisa baris lain)" : '') . '. Perbaiki lalu unggah ulang seluruh berkas.');
    }

    private function gagal($pesan)
    {
        return ['success' => FALSE, 'message' => $pesan, 'jumlah_baris' => 0, 'ringkasan' => NULL];
    }
}
