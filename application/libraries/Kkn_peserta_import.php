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
        foreach ($baris as $i => $r) {
            $nomor_baris = $i + 2; // +1 offset toArray 0-based, +1 lagi karena header sudah dibuang
            $nim  = trim((string) ($r[$kol_nim] ?? ''));
            $nama = trim((string) ($r[$kol_nama] ?? ''));

            // Baris benar-benar kosong (sisa baris kosong di ekor sheet,
            // lazim pada Excel) dilewati diam-diam - itu bukan data cacat,
            // cuma jejak sheet yang pernah lebih panjang.
            if ($nim === '' && $nama === '') { continue; }

            if ($nim === '' || $nama === '') {
                $baris_cacat[] = $nomor_baris;
                continue;
            }
            if (mb_strlen($nim) > 30) {
                $baris_cacat[] = $nomor_baris . ' (NIM lebih dari 30 karakter)';
                continue;
            }
            if (mb_strlen($nama) > 150) {
                $baris_cacat[] = $nomor_baris . ' (Nama lebih dari 150 karakter)';
                continue;
            }

            $peserta[] = ['nim' => $nim, 'nama' => $nama];
        }

        if ($baris_cacat) {
            $tampil = array_slice($baris_cacat, 0, 10);
            $sisa = count($baris_cacat) - count($tampil);
            return $this->gagal('Baris ' . implode(', ', $tampil)
                . ($sisa > 0 ? ' (dan ' . $sisa . ' baris lain)' : '')
                . ' tidak lengkap - NIM dan Nama harus terisi keduanya. '
                . 'Perbaiki lalu unggah ulang seluruh berkas.');
        }
        if ( ! $peserta) {
            return $this->gagal('Tidak ada baris peserta yang bisa dibaca dari berkas ini.');
        }

        return ['success' => TRUE, 'message' => '', 'peserta' => $peserta];
    }

    private function gagal($pesan)
    {
        return ['success' => FALSE, 'message' => $pesan, 'peserta' => []];
    }
}
