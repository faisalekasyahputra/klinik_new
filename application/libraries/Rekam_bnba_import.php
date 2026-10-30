<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Baca BNBA (daftar penerima) dari Excel yang diunggah admin kabupaten/kota,
 * format dari dinas 27 Okt 2026:
 *   NIK | NO KK | NAMA | ALAMAT | KABUPATEN | KECAMATAN | DESA/KELURAHAN |
 *   KDDESA (rumus VLOOKUP di templat, wajib terisi) | JUMLAH MATERIAL | JUMLAH PADAT KARYA
 *
 * Pola yang sama dengan Kkn_peserta_import: header dicocokkan lewat NAMA kolom,
 * satu baris cacat MENOLAK SELURUH berkas dengan nomor barisnya, dan library
 * ini tidak menyentuh DB. Hasilnya hanya tiga angka; NIK/nama/alamat tidak
 * dikembalikan supaya tidak ada salinan kedua di memori pemanggil.
 */
class Rekam_bnba_import
{
    const BATAS_BARIS = 5000;

    /**
     * Judul kolom (huruf kecil, spasi dirapatkan) → kunci. KDDESA (permintaan dinas 28 Okt 2026) kini
     * DIBACA: di templat terisi rumus VLOOKUP dari lembar KODE WILAYAH, dan baris yang KDDESA-nya
     * kosong/asing ditolak. Judulnya diawali "kddesa" dan boleh diikuti keterangan apa pun.
     */
    const KOLOM = [
        'nik' => 'nik', 'no kk' => 'kk', 'nama' => 'nama', 'alamat' => 'alamat',
        'kabupaten' => 'kab', 'kecamatan' => 'kec', 'desa/kelurahan' => 'desa',
        'jumlah material' => 'material', 'jumlah padat karya' => 'padat',
    ];
    const WAJIB = ['nik', 'nama', 'alamat', 'kab', 'kec', 'desa', 'kddesa', 'material', 'padat'];

    /**
     * @param string $path           berkas sementara
     * @param string $nama_kabupaten kabupaten pelapor; baris kabupaten lain ditolak
     * @param array  $kode_desa      kode desa SAH milik kabupaten pelapor (tabel `desa`), [kode => TRUE]
     * @return array ['success','message','jumlah_penerima','total_material','total_padat_karya']
     */
    /**
     * Buka berkas, ganti sel berumus dengan nilai tersimpannya, lalu petakan judul kolom.
     * @return array|string [$kol, $baris_isi] atau pesan galat
     */
    private function muat($path)
    {
        try {
            // Nilai MENTAH (formatData FALSE): sel uang berformat "#,##0.00" kalau dibaca sebagai teks
            // terformat jadi "17.500.000,00" dan membuang pemisahnya mengalikan angka 100 kali.
            // Rumus TIDAK dihitung di server (calculateFormulas FALSE): sel berumus (KDDESA) memakai
            // nilai hasil yang disimpan Excel. Lebih cepat (VLOOKUP ke ribuan desa per baris) dan
            // tidak menjalankan rumus kiriman pengunggah.
            $sheet = IOFactory::load($path)->getActiveSheet();
            $baris = $sheet->toArray(NULL, FALSE, FALSE, FALSE);
            foreach ($baris as $i => $r) {
                foreach ($r as $c => $v) {
                    if (is_string($v) && strpos($v, '=') === 0) {
                        $baris[$i][$c] = $sheet->getCell([$c + 1, $i + 1])->getOldCalculatedValue();
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Rekam_bnba_import: gagal membaca berkas - ' . $e->getMessage());
            return 'Berkas tidak dapat dibaca. Pastikan benar-benar Excel (XLSX/XLS), bukan berkas yang sekadar diganti namanya.';
        }
        if (count($baris) > self::BATAS_BARIS + 1) {
            return 'Berkas terlalu panjang. Maksimal ' . self::BATAS_BARIS . ' baris penerima.';
        }

        // Judul bisa berisi "(KOLOM TERISI OTOMATIS)" sesudah baris baru: dibuang ke satu spasi.
        $kol = [];
        foreach ((array) array_shift($baris) as $idx => $judul) {
            $judul = strtolower(trim(preg_replace('/\s+/', ' ', (string) $judul)));
            if (isset(self::KOLOM[$judul])) { $kol[self::KOLOM[$judul]] = $idx; }
            elseif (strpos($judul, 'kddesa') === 0) { $kol['kddesa'] = $idx; }
        }
        $hilang = array_diff(self::WAJIB, array_keys($kol));
        if ($hilang) {
            return 'Kolom tidak ditemukan pada baris pertama: ' . implode(', ', array_map('strtoupper', $hilang))
                . '. Gunakan templat dari tombol "Unduh templat BNBA".';
        }
        return [$kol, $baris];
    }

    /**
     * Baris penerima dari berkas yang SUDAH lolos baca() saat diunggah (untuk BNBA gabungan).
     * Tidak memvalidasi ulang; baris kosong dilewati. NULL bila berkas tak terbaca.
     * @return array|null [['nik','kk','nama','alamat','kab','kec','desa','kddesa','material','padat'], ...]
     */
    public function penerima($path)
    {
        $muat = $this->muat($path);
        if (is_string($muat)) { return NULL; }
        [$kol, $baris] = $muat;
        $out = [];
        foreach ($baris as $r) {
            if (implode('', array_map([self::class, 'teks'], $r)) === '') { continue; }
            $v = static fn($k) => self::teks($r[$kol[$k] ?? -1] ?? '');
            $out[] = ['nik' => $v('nik'), 'kk' => isset($kol['kk']) ? $v('kk') : '', 'nama' => $v('nama'),
                'alamat' => $v('alamat'), 'kab' => $v('kab'), 'kec' => $v('kec'), 'desa' => $v('desa'),
                'kddesa' => $v('kddesa'), 'material' => self::rupiah($r[$kol['material']] ?? '') ?? 0,
                'padat' => self::rupiah($r[$kol['padat']] ?? '') ?? 0];
        }
        return $out;
    }

    public function baca($path, $nama_kabupaten, array $kode_desa)
    {
        $muat = $this->muat($path);
        if (is_string($muat)) { return $this->gagal($muat); }
        [$kol, $baris] = $muat;

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
            // KDDESA kosong = rumus tidak menemukan gabungan kabupaten/kecamatan/desa: hampir selalu
            // salah ketik nama desa atau kecamatan. Ditolak, bukan ditebak.
            $kd = $v('kddesa');
            if ($kd === '' || $kd === '#N/A') {
                $cacat[] = "$no: KDDESA kosong. Periksa ejaan KECAMATAN dan DESA/KELURAHAN agar sama dengan lembar KODE WILAYAH"; continue;
            }
            if ( ! preg_match('/^\d{10}$/', $kd) || ! isset($kode_desa[$kd])) {
                $cacat[] = "$no: KDDESA $kd bukan kode desa di {$nama_kabupaten}"; continue;
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
