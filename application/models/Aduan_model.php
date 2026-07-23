<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Aduan_model extends CI_Model {

    /**
     * Kata kunci per bidang — dicocokkan ke judul+pesan aduan supaya
     * bisa diarahkan otomatis. Bidangnya mengikuti taksonomi yang sudah
     * dipakai tab navbar utama (Perumahan, Kawasan, Pertanahan, Pengembang).
     */
    private $bidang_keywords = [
        'perumahan' => ['rumah', 'kpr', 'subsidi', 'flpp', 'cicilan', 'uang muka', ' dp ', 'desain rumah', 'bank desain', 'beli rumah', 'cari rumah', 'hunian', 'kredit rumah'],
        'kawasan'   => ['kawasan', 'permukiman', 'kumuh', 'psu', 'sanitasi', 'drainase', 'rtlh', 'tidak layak huni', 'rusun', 'rusunawa', 'sdgs', 'lingkungan warga', 'jalan lingkungan'],
        'pertanahan'=> ['tanah', 'sertifikat tanah', 'sengketa', 'lahan', 'girik', 'akta tanah', 'batas tanah', 'bank tanah', 'izin lokasi', 'status tanah'],
        'pengembang'=> ['pengembang', 'developer', 'srp2', 'sertifikasi pengembang', 'izin pengembang', 'asosiasi pengembang', 'proyek perumahan', 'nib', 'nama perusahaan'],
    ];

    public function bidang_label($bidang) {
        $labels = [
            'perumahan'  => 'Bidang Perumahan',
            'kawasan'    => 'Bidang Kawasan Permukiman',
            'pertanahan' => 'Bidang Pertanahan',
            'pengembang' => 'Bidang Pengembang',
            'umum'       => 'Admin Umum',
        ];
        return $labels[$bidang] ?? $labels['umum'];
    }

    /**
     * Deteksi bidang tujuan dari judul+pesan berdasarkan kecocokan kata kunci
     * terbanyak. Kalau tidak ada kata kunci yang cocok sama sekali, jatuh ke
     * 'umum' (diteruskan ke admin umum).
     */
    public function detect_bidang($teks) {
        $teks = ' ' . mb_strtolower($teks) . ' ';
        $skor_terbaik = 0;
        $bidang_terbaik = 'umum';

        foreach ($this->bidang_keywords as $bidang => $keywords) {
            $skor = 0;
            foreach ($keywords as $kw) {
                if (mb_strpos($teks, $kw) !== FALSE) {
                    $skor++;
                }
            }
            if ($skor > $skor_terbaik) {
                $skor_terbaik = $skor;
                $bidang_terbaik = $bidang;
            }
        }

        return $bidang_terbaik;
    }

    public function create($data) {
        $this->db->insert('aduan', $data);
        return $this->db->insert_id();
    }
}
