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
        $master_programs = $this->master_programs();

        // LOGIKA KELAYAKAN UTAMA BERDASARKAN DESIL

        /* Status kepemilikan MENYARING DI ATAS desil — tidak menggantikannya
         * (keputusan user 5 Agt 2026, revisi dinas butir A7). Desil tetap
         * menentukan program mana yang berhak; kepemilikan menentukan BENTUK
         * intervensinya. Menggantikan desil akan mengubah sasaran subsidi.
         *
         * Pemetaannya BUKAN tebakan — diambil dari dokumen proyek sendiri,
         * docs/meetings/22_juni_2026/ANALISA_PROGRAM_PPT_UN_HABITAT.md §2:
         *
         *   PB Backlog — "Warga yang menumpang di rumah orangtua/saudara
         *   (1 rumah dihuni >1 KK), ATAU warga yang saat ini masih sewa/kontrak
         *   rumah." Intervensinya: "Pembangunan rumah baru di lahan milik
         *   sendiri yang sah."
         *
         *   RTLH — "perbaikan atau renovasi rumah bagi masyarakat miskin yang
         *   rumahnya masuk kategori tidak layak."
         *
         * Jadi `pb` untuk yang BELUM punya rumah layak huni, `rtlh` untuk yang
         * punya rumah tapi tidak layak. Gerbang `rtlh` sudah ada sejak awal;
         * yang bolong justru pasangannya di `pb` — itu sebabnya warga berumah
         * layak pun tetap ditawari bantuan pembangunan baru.
         *
         * Program PEMBIAYAAN (FLPP, Oemah Lestari) sengaja TIDAK disaring:
         * membeli rumah terbuka apa pun status huniannya sekarang. Begitu juga
         * `omah_sekeng` — dokumen sumbernya tidak menyebut ia perbaikan atau
         * pembangunan, dan menyaring atas dugaan bisa menutup bantuan yang
         * seharusnya terbuka. Kalau dinas kelak memastikan jenisnya, tambahkan
         * di sini, bukan di pemanggil.
         */
        $punya_rumah_tak_layak = ($status_kepemilikan === 'Punya Rumah Tidak Layak');
        $belum_punya_rumah     = in_array($status_kepemilikan,
            ['Sewa/Kontrak', 'Numpang/Keluarga', 'Punya Lahan Belum Bangun'], TRUE);
        // Tanpa keterangan kepemilikan, JANGAN menyaring: lebih baik menawarkan
        // terlalu banyak daripada diam-diam menutup bantuan yang berhak.
        $tak_diketahui = empty($status_kepemilikan);

        // Desil 1, 2, 3
        if (in_array($desil, [1, 2, 3])) {
            if ($tak_diketahui || $belum_punya_rumah) {
                $eligible[] = $master_programs['pb'];
            }
            if ($tak_diketahui || $punya_rumah_tak_layak) {
                $eligible[] = $master_programs['rtlh'];
            }
        }
        // Desil 4
        elseif ($desil == 4) {
            $eligible[] = $master_programs['omah_sekeng'];
            if ($tak_diketahui || $belum_punya_rumah) {
                $eligible[] = $master_programs['pb'];
            }
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

    /**
     * Definisi master program sesuai UN HABITAT (mapping `id` ke INT di sf_programs).
     *
     * DIANGKAT JADI PUBLIC 4 Agt 2026, tanpa mengubah isinya sama sekali: layar
     * Katalog Program (`Admin_Katalog_Program`) perlu membandingkan judul di
     * sini dengan `sf_programs.nama_program`, dan keduanya MEMANG SUDAH
     * BERSELISIH untuk empat dari enam program. Selisih itu tidak bisa
     * ditunjukkan kalau daftarnya terkubur sebagai variabel lokal.
     *
     * ⚠️ Daftar ini TETAP jadi acuan pencocokan. `sf_programs` mengatur
     * `is_active` (yang menggerbangi pengajuan) dan `nama_program` (yang tampil
     * di antrean & /akun); yang di sini mengatur apa yang tampil di kartu hasil
     * diagnosa. Menambah program ke tabel TIDAK membuatnya bisa dicocokkan —
     * aturannya ada di `get_eligible_programs()`, dan itu kode.
     *
     * Perhatikan `oemah_lestari` muncul DUA KALI (subsidi & non-subsidi) dengan
     * `kode` yang sama; pemetaan kode -> judul memang bukan satu-ke-satu.
     */
    public function master_programs() {
        return [
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
                'id' => 6, // Fresh DB mendapat baris ini dari migrasi 000017.
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
    }

}
