<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Baca roster peserta KKN (NIM + Nama) dari berkas Excel yang diunggah
 * universitas - permintaan user 21 Agt 2026, dashboard KKN.
 *
 * SATU TANGGUNG JAWAB: mem-parse berkas jadi array bersih atau menolak
 * dengan alasan jelas. TIDAK menyentuh DB sama sekali - KemitraanPortal
 * yang memutuskan apa yang terjadi dengan hasilnya (ganti roster lama,
 * dsb.), sama seperti Simperum_gateway yang hanya mengambil data tanpa
 * pernah memutuskan penyimpanannya sendiri.
 *
 * TOLAK SELURUH BERKAS kalau ADA satu baris cacat (NIM tanpa nama, atau
 * sebaliknya), bukan melewatkan baris itu diam-diam. Roster yang diam-diam
 * kehilangan satu nama adalah roster yang tidak bisa dipercaya sama sekali
 * - lebih baik universitas diberi tahu PERSIS baris mana yang salah dan
 * mengunggah ulang, daripada admin menghitung peserta dari angka yang
 * ternyata kurang satu.
 */
class Kkn_peserta_import
{
    const BATAS_BARIS = 2000;

    /**
     * @param string $path lokasi berkas sementara (mis. $_FILES[...]['tmp_name'])
     * @return array ['success'=>bool, 'message'=>string, 'peserta'=>[['nim'=>...,'nama'=>...], ...]]
     */
    public function baca($path)
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            // Pesan asli PhpSpreadsheet (jalur berkas, dsb.) tidak untuk
            // pemakai akhir - dicatat, bukan ditampilkan.
            log_message('error', 'Kkn_peserta_import: gagal membaca berkas - ' . $e->getMessage());
            return $this->gagal('Berkas tidak dapat dibaca. Pastikan formatnya benar-benar Excel (XLS/XLSX), bukan berkas yang sekadar diganti namanya.');
        }

        $baris = $spreadsheet->getActiveSheet()->toArray(NULL, TRUE, TRUE, FALSE);
        if (empty($baris)) {
            return $this->gagal('Berkas kosong.');
        }
        if (count($baris) > self::BATAS_BARIS + 1) {
            return $this->gagal('Berkas terlalu panjang. Maksimal ' . self::BATAS_BARIS . ' baris peserta.');
        }

        // Header dicari di BARIS PERTAMA, dicocokkan lewat NAMA kolom
        // (case-insensitive), bukan posisi tetap (kolom A/B) - universitas
        // yang menyusun sendiri templatnya kemungkinan besar tidak
        // mengurutkan NIM lebih dulu dari Nama.
        $header = array_shift($baris);
        $kol_nim = NULL;
        $kol_nama = NULL;
        foreach ($header as $idx => $judul) {
            $judul = strtolower(trim((string) $judul));
            if ($judul === 'nim') { $kol_nim = $idx; }
            if ($judul === 'nama') { $kol_nama = $idx; }
        }
        if ($kol_nim === NULL || $kol_nama === NULL) {
            return $this->gagal('Kolom "NIM" dan "Nama" tidak ditemukan pada baris pertama. '
                . 'Pastikan baris pertama berkas berisi judul kolom NIM dan Nama.');
        }

        $peserta = [];
        $baris_cacat = [];
        $nim_di_baris = [];
        foreach ($baris as $i => $r) {
            $nomor_baris = $i + 2; // +1 offset toArray 0-based, +1 lagi karena header sudah dibuang
            $nim_asli = trim((string) ($r[$kol_nim] ?? ''));
            $nama = trim((string) ($r[$kol_nama] ?? ''));

            // Baris benar-benar kosong (sisa baris kosong di ekor sheet,
            // lazim pada Excel) dilewati diam-diam - itu bukan data cacat,
            // cuma jejak sheet yang pernah lebih panjang.
            if ($nim_asli === '' && $nama === '') { continue; }

            // Tiap baris cacat membawa alasannya sendiri: pesan tunggal "tidak lengkap"
            // dulu dipakai juga untuk NIM/Nama yang kepanjangan (temuan UAT U4).
            if ($nim_asli === '' || $nama === '') {
                $baris_cacat[] = $nomor_baris . ': NIM dan Nama harus terisi keduanya';
                continue;
            }
            $nim = self::normalkan_nim($nim_asli);
            if (mb_strlen($nim) > 30) {
                $baris_cacat[] = $nomor_baris . ': NIM lebih dari 30 karakter';
                continue;
            }
            if ( ! self::nim_sah($nim)) {
                $baris_cacat[] = $nomor_baris . ': NIM hanya boleh berisi huruf dan angka';
                continue;
            }
            if (mb_strlen($nama) > 150) {
                $baris_cacat[] = $nomor_baris . ': Nama lebih dari 150 karakter';
                continue;
            }
            // Satu mahasiswa satu baris: NIM ganda menggelembungkan jumlah peserta dan
            // membuat pencarian sertifikat memilih salah satu barisnya secara acak.
            if (isset($nim_di_baris[strtoupper($nim)])) {
                $baris_cacat[] = $nomor_baris . ': NIM sama dengan baris ' . $nim_di_baris[strtoupper($nim)];
                continue;
            }
            $nim_di_baris[strtoupper($nim)] = $nomor_baris;

            $peserta[] = ['nim' => $nim, 'nama' => $nama];
        }

        if ($baris_cacat) {
            $tampil = array_slice($baris_cacat, 0, 10);
            $sisa = count($baris_cacat) - count($tampil);
            return $this->gagal('Baris ' . implode('; Baris ', $tampil)
                . ($sisa > 0 ? ' (dan ' . $sisa . ' baris lain)' : '')
                . '. Perbaiki lalu unggah ulang seluruh berkas.');
        }
        if ( ! $peserta) {
            return $this->gagal('Tidak ada baris peserta yang bisa dibaca dari berkas ini.');
        }

        return ['success' => TRUE, 'message' => '', 'peserta' => $peserta];
    }

    /**
     * SATU aturan format NIM untuk dua pintu: unggah roster (di atas) dan pencarian
     * sertifikat (KemitraanPortal::cek_sertifikat_kkn). Pemisah yang lazim di NIM
     * (titik, tanda hubung, spasi, mis. 21.11.1234) dibuang, sehingga NIM yang ditulis
     * dengan atau tanpa pemisah menunjuk mahasiswa yang sama. Sebelumnya importer
     * menerima apa saja sementara pencarian hanya alfanumerik, jadi peserta ber-NIM
     * bertitik tidak pernah bisa menemukan sertifikatnya (temuan UAT U4/U5).
     */
    public static function normalkan_nim($nim)
    {
        return preg_replace('/[.\-\s]+/u', '', trim((string) $nim));
    }

    /** NIM yang sudah dinormalkan: 1-30 huruf/angka. */
    public static function nim_sah($nim)
    {
        return (bool) preg_match('/^[A-Za-z0-9]{1,30}$/', (string) $nim);
    }

    private function gagal($pesan)
    {
        return ['success' => FALSE, 'message' => $pesan, 'peserta' => []];
    }
}
