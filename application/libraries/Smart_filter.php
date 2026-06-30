<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Smart_filter {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Hitung program yang eligible berdasarkan Desil (1-10) dan parameter lain.
     * Menggunakan matriks dari UN HABITAT.
     */
    public function get_eligible_programs($desil, $status_kepemilikan = '', $kawasan_pesisir = false) {
        $eligible = [];

        // Definisi master program sesuai UN HABITAT (Mapping id dengan INT di tb programs)
        $master_programs = [
            'rtlh' => [
                'id' => 3, // Sesuai ID di database
                'kode' => 'rtlh',
                'title' => 'Peningkatan Kualitas RTLH',
                'badge' => 'Miskin Terbawah',
                'desc' => 'Bantuan perbaikan rumah via Bankeupemdes Rp 20 Juta.',
                'icon' => 'fa-hammer',
                'color' => '#c084fc'
            ],
            'pb' => [
                'id' => 4,
                'kode' => 'pb',
                'title' => 'Bansos PB (Pembangunan Baru)',
                'badge' => 'MBR Non-Fixed Income',
                'desc' => 'Bantuan material Rp 40 Juta untuk lahan sendiri/relokasi.',
                'icon' => 'fa-trowel-bricks',
                'color' => '#d6fb00'
            ],
            'omah_sekeng' => [
                'id' => 6, // Harus ditambahkan ke tabel programs!
                'kode' => 'omah_sekeng',
                'title' => 'Omah Sekeng',
                'badge' => 'Desil 4 Khusus',
                'desc' => 'Bantuan kolaboratif CSR & Pemprov sebesar Rp 45 Juta.',
                'icon' => 'fa-hand-holding-heart',
                'color' => '#f59e0b'
            ],
            'flpp' => [
                'id' => 1,
                'kode' => 'flpp',
                'title' => 'KPR-FLPP Subsidi',
                'badge' => 'MBR Fixed Income',
                'desc' => 'Bunga flat 5% & cicilan ringan hingga 20 tahun.',
                'icon' => 'fa-building-columns',
                'color' => '#00a3b5'
            ],
            'oemah_lestari_subsidi' => [
                'id' => 2,
                'kode' => 'oemah_lestari',
                'title' => 'Oemah Lestari Subsidi',
                'badge' => 'MBR Fixed Income',
                'desc' => 'Pembiayaan rumah bunga ringan 8% flat (Kolaborasi BPR).',
                'icon' => 'fa-leaf',
                'color' => '#10b981'
            ],
            'oemah_lestari_non' => [
                'id' => 2, // Menggunakan ID yang sama (Oemah Lestari)
                'kode' => 'oemah_lestari',
                'title' => 'Oemah Lestari Non-Subsidi',
                'badge' => 'MBM & MBA',
                'desc' => 'Pembiayaan rumah komersial ramah lingkungan.',
                'icon' => 'fa-leaf',
                'color' => '#64748b'
            ],
            'rumah_apung' => [
                'id' => 5,
                'kode' => 'rumah_apung',
                'title' => 'Rumah Apung',
                'badge' => 'Kawasan Pesisir',
                'desc' => 'Inovasi desain rumah adaptif genangan air & rob.',
                'icon' => 'fa-water',
                'color' => '#3b82f6'
            ]
        ];

        // LOGIKA KELAYAKAN UTAMA BERDASARKAN DESIL
        
        // Desil 1, 2, 3
        if (in_array($desil, [1, 2, 3])) {
            $eligible[] = $master_programs['pb'];
            
            // RTLH khusus jika punya rumah tapi tidak layak
            if (empty($status_kepemilikan) || $status_kepemilikan === 'Punya Rumah Tidak Layak') {
                $eligible[] = $master_programs['rtlh'];
            }
        } 
        // Desil 4
        elseif ($desil == 4) {
            $eligible[] = $master_programs['omah_sekeng'];
            $eligible[] = $master_programs['pb'];
        }
        // Desil 5, 6, 7, 8
        elseif (in_array($desil, [5, 6, 7, 8])) {
            $eligible[] = $master_programs['flpp'];
            $eligible[] = $master_programs['oemah_lestari_subsidi'];
        }
        // Desil 9, 10
        elseif (in_array($desil, [9, 10])) {
            $eligible[] = $master_programs['oemah_lestari_non'];
        }

        // Tambahan khusus: Rumah Apung untuk kawasan pesisir (Bypass Desil)
        if ($kawasan_pesisir) {
            $eligible[] = $master_programs['rumah_apung'];
        }

        // Jika kebetulan kosong (misal desil out of range), fallback ke program umum
        if (empty($eligible)) {
            $eligible[] = [
                'id' => 'umum',
                'title' => 'Program Terpadu',
                'badge' => 'Umum',
                'desc' => 'Konsultasi program perumahan reguler.',
                'icon' => 'fa-star',
                'color' => '#ffffff'
            ];
        }

        return $eligible;
    }
}
