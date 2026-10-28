<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Baca BNBA (daftar penerima) dari Excel yang diunggah admin kabupaten/kota,
 * format dari dinas 27 Okt 2026:
 *   NIK | NO KK | NAMA | ALAMAT | KABUPATEN | KECAMATAN | DESA/KELURAHAN |
 *   KDDESA (terisi otomatis, diabaikan) | JUMLAH MATERIAL | JUMLAH PADAT KARYA
 *
 * Pola yang sama dengan Kkn_peserta_import: header dicocokkan lewat NAMA kolom,
 * satu baris cacat MENOLAK SELURUH berkas dengan nomor barisnya, dan library
 * ini tidak menyentuh DB. Hasilnya hanya tiga angka; NIK/nama/alamat tidak
 * dikembalikan supaya tidak ada salinan kedua di memori pemanggil.
 */
class Rekam_bnba_import
{
    const BATAS_BARIS = 5000;

    /** Judul kolom (huruf kecil) → kunci. KDDESA sengaja tidak ada: terisi otomatis, tak dibaca. */
    const KOLOM = [
        'nik' => 'nik', 'no kk' => 'kk', 'nama' => 'nama', 'alamat' => 'alamat',
        'kabupaten' => 'kab', 'kecamatan' => 'kec', 'desa/kelurahan' => 'desa',
        'jumlah material' => 'material', 'jumlah padat karya' => 'padat',
    ];
    const WAJIB = ['nik', 'nama', 'alamat', 'kab', 'kec', 'desa', 'material', 'padat'];

    /**
     * @param string $path          berkas sementara
     * @param string $nama_kabupaten kabupaten pelapor; baris kabupaten lain ditolak
     * @return array ['success','message','jumlah_penerima','total_material','total_padat_karya']
     */
    public function baca($path, $nama_kabupaten)
    {
        try {
            // Nilai MENTAH (formatData FALSE): sel uang berformat "#,##0.00" kalau dibaca sebagai teks
            // terformat jadi "17.500.000,00" dan membuang pemisahnya mengalikan angka 100 kali.
            $baris = IOFactory::load($path)->getActiveSheet()->toArray(NULL, TRUE, FALSE, FALSE);
        } catch (\Throwable $e) {
            log_message('error', 'Rekam_bnba_import: gagal membaca berkas - ' . $e->getMessage());
            return $this->gagal('Berkas tidak dapat dibaca. Pastikan benar-benar Excel (XLSX/XLS), bukan berkas yang sekadar diganti namanya.');
        }
        if (count($baris) > self::BATAS_BARIS + 1) {
            return $this->gagal('Berkas terlalu panjang. Maksimal ' . self::BATAS_BARIS . ' baris penerima.');
        }

        // Judul bisa berisi "(KOLOM TERISI OTOMATIS)" sesudah baris baru: dibuang ke satu spasi.
        $kol = [];
        foreach ((array) array_shift($baris) as $idx => $judul) {
            $judul = strtolower(trim(preg_replace('/\s+/', ' ', (string) $judul)));
            if (isset(self::KOLOM[$judul])) { $kol[self::KOLOM[$judul]] = $idx; }
        }
        $hilang = array_diff(self::WAJIB, array_keys($kol));
        if ($hilang) {
            return $this->gagal('Kolom tidak ditemukan pada baris pertama: ' . implode(', ', array_map('strtoupper', $hilang))
                . '. Gunakan templat dari tombol "Unduh templat BNBA".');
        }

        $cacat = []; $nik_ada = []; $n = 0; $material = 0; $padat = 0;
        foreach ($baris as $i => $r) {
            $no = $i + 2;
            $v = static fn($k) => self::teks($r[$kol[$k] ?? -1] ?? '');
            if (implode('', array_map([self::class, 'teks'], $r)) === '') { continue; }

            $nik = $v('nik');
            if ( ! preg_match('/^\d{16}$/', $nik)) { $cacat[] = "$no: NIK harus 16 angka (kolom NIK diformat Teks)"; continue; }
            if (isset($nik_ada[$nik])) { $cacat[] = "$no: NIK sama dengan baris {$nik_ada[$nik]}"; continue; }
            if ($v('kk') !== '' && ! preg_match('/^\d{16}$/', $v('kk'))) { $cacat[] = "$no: NO KK harus 16 angka atau dikosongkan"; continue; }
            foreach (['nama' => 'NAMA', 'alamat' => 'ALAMAT', 'kec' => 'KECAMATAN', 'desa' => 'DESA/KELURAHAN'] as $k => $label) {
                if ($v($k) === '') { $cacat[] = "$no: $label wajib diisi"; continue 2; }
            }
            if ( ! self::kab_cocok($v('kab'), $nama_kabupaten)) {
                $cacat[] = "$no: KABUPATEN bukan wilayah Anda ({$nama_kabupaten})"; continue;
            }
            $uang = [];
            foreach (['material' => 'JUMLAH MATERIAL', 'padat' => 'JUMLAH PADAT KARYA'] as $k => $label) {
                $uang[$k] = self::rupiah($r[$kol[$k]] ?? '');
                if ($uang[$k] === NULL) { $cacat[] = "$no: $label harus angka bulat (rupiah penuh, tanpa desimal)"; continue 2; }
            }
            $nik_ada[$nik] = $no;
            $n++;
            $material += $uang['material'];
            $padat    += $uang['padat'];
        }

        if ($cacat) {
            $tampil = array_slice($cacat, 0, 10);
            $sisa = count($cacat) - count($tampil);
            return $this->gagal('Baris ' . implode('; Baris ', $tampil)
                . ($sisa > 0 ? " (dan $sisa baris lain)" : '') . '. Perbaiki lalu unggah ulang seluruh berkas.');
        }
        if ($n === 0) { return $this->gagal('Tidak ada baris penerima yang bisa dibaca dari berkas ini.'); }

        return ['success' => TRUE, 'message' => '', 'jumlah_penerima' => $n,
            'total_material' => $material, 'total_padat_karya' => $padat];
    }

    /** "Kabupaten Kebumen", "KEBUMEN", "Kota Magelang" → bentuk yang bisa dibandingkan. */
    public static function normal_kab($nama)
    {
        return trim(preg_replace('/^(kabupaten|kab\.?|kota)\s+/i', '', strtolower(trim((string) $nama))));
    }

    /**
     * Nama tanpa awalan ("Kebumen", seperti contoh dinas) cukup cocok namanya. Bila baris MENULIS
     * awalannya, jenisnya harus sama: Jateng punya pasangan Kab./Kota bernama sama (Magelang,
     * Pekalongan, Semarang, Tegal), dan "Kota Semarang" tidak boleh lolos untuk Kabupaten Semarang.
     */
    public static function kab_cocok($baris, $pelapor)
    {
        if (self::normal_kab($baris) !== self::normal_kab($pelapor)) { return FALSE; }
        $jenis = static fn($s) => preg_match('/^\s*(kabupaten|kab\.?|kota)\s/i', (string) $s, $m)
            ? (stripos($m[1], 'kota') === 0 ? 'kota' : 'kab') : NULL;
        return $jenis($baris) === NULL || $jenis($baris) === $jenis($pelapor);
    }

    /** Sel apa pun → teks. Angka bulat (NIK yang tidak diformat Teks) ditulis utuh, bukan notasi ilmiah. */
    public static function teks($c)
    {
        if (is_float($c) && floor($c) === $c && abs($c) < 1e16) { return sprintf('%.0f', $c); }
        return trim((string) $c);
    }

    /** Rupiah penuh: angka bulat >= 0, atau teks "17500000" / "17.500.000" / "17,500,000". NULL bila bukan. */
    public static function rupiah($c)
    {
        if (is_int($c)) { return $c >= 0 ? $c : NULL; }
        if (is_float($c)) { return ($c >= 0 && floor($c) === $c) ? (int) $c : NULL; }
        $s = str_replace(' ', '', (string) $c);
        if (preg_match('/^\d+$/', $s) || preg_match('/^\d{1,3}([.,]\d{3})+$/', $s)) { return (int) str_replace(['.', ','], '', $s); }
        return NULL;
    }

    private function gagal($pesan)
    {
        return ['success' => FALSE, 'message' => $pesan, 'jumlah_penerima' => 0,
            'total_material' => 0, 'total_padat_karya' => 0];
    }
}
