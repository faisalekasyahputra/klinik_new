<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kolom etalase untuk `sf_programs` — supaya 5 program unggulan bisa diurus
 * dari layar admin, bukan dari kode.
 *
 * LATAR: info program hidup di TIGA tempat yang saling melenceng —
 *   1. tabel `sf_programs` (kode, nama, deskripsi_singkat, is_active),
 *   2. `Smart_filter::master_programs()` (judul, badge, ikon, warna — hardcode),
 *   3. korsel beranda (judul, badge, deskripsi, syarat, gambar — hardcode di JS).
 * Migrasi ini memindahkan yang DITAMPILKAN ke tabel, sehingga korsel punya satu
 * sumber dan admin bisa mengubahnya. Aturan KELAYAKAN tetap di Smart_filter —
 * itu logika, bukan tampilan, dan tidak boleh bisa diubah lewat formulir.
 *
 * WARNA SENGAJA TIDAK IKUT. Palet pastelnya baru disetel 5 Agt dan kontrasnya
 * diukur piksel per piksel; membiarkannya diedit bebas membatalkan itu dalam
 * satu klik. Admin mengatur kata dan foto — yang memang miliknya.
 *
 * ADITIF MURNI: hanya ADD COLUMN + UPDATE nilai awal. Tidak ada kolom yang
 * dibuang, tidak ada tipe yang diubah, jadi kode LAMA tetap jalan sesudah
 * migrasi ini — urutan aman "migrasi dulu, kode menyusul" (AGENTS.md §51).
 */
class Migration_Program_etalase extends CI_Migration {

    /** Nilai awal diambil APA ADANYA dari korsel yang sedang tayang, supaya
     *  migrasi ini nol perubahan visual. Yang berpindah tempatnya, bukan isinya. */
    private $awal = [
        'flpp' => [
            'badge' => 'MBR Fixed Income',
            'deskripsi' => 'Skema pembiayaan rumah subsidi dengan bunga tetap dan tenor panjang.',
            'syarat' => 'Penghasilan maksimal Rp8 juta per bulan dan memenuhi ketentuan MBR.',
            'gambar' => 'assets/img/program/01_subsidif_lpp.avif',
            'urutan' => 1,
        ],
        'oemah_lestari' => [
            'badge' => 'MBR & Umum',
            'deskripsi' => 'Pembiayaan rumah terjangkau yang berpihak pada hunian ramah lingkungan.',
            'syarat' => 'Skema bunga ringan melalui kolaborasi BPR-BRK.',
            'gambar' => 'assets/img/program/02_oemahletari.avif',
            'urutan' => 2,
        ],
        'rtlh' => [
            'badge' => 'Miskin & Ekstrem',
            'deskripsi' => 'Bantuan perbaikan rumah bagi masyarakat dengan hunian tidak layak.',
            'syarat' => 'Terdaftar DTKS dan rumah memenuhi kriteria kerusakan.',
            'gambar' => 'assets/img/program/03_rtlh.avif',
            'urutan' => 3,
        ],
        'pb' => [
            'badge' => 'Bencana & Relokasi',
            'deskripsi' => 'Bantuan stimulan material untuk pembangunan rumah baru yang layak.',
            'syarat' => 'Tersedia untuk skema backlog, relokasi, atau pascabencana.',
            'gambar' => 'assets/img/program/04_Bantuan.avif',
            'urutan' => 4,
        ],
        'rumah_apung' => [
            'badge' => 'Kawasan Pesisir',
            'deskripsi' => 'Solusi hunian adaptif untuk masyarakat pesisir terdampak rob.',
            'syarat' => 'Prioritas kawasan pesisir yang mengalami genangan permanen.',
            'gambar' => 'assets/img/program/05_areakumuh.avif',
            'urutan' => 5,
        ],
    ];

    public function up()
    {
        if ( ! $this->db->table_exists('sf_programs')) {
            log_message('error', 'Migrasi 036: tabel sf_programs tidak ada.');
            return;
        }

        /* Ditambahkan satu per satu dan hanya kalau BELUM ada. `add_column()`
           yang menabrak kolom eksisting akan gagal, dan dengan `db_debug` mati
           di production kegagalan itu TIDAK bersuara — migrasinya tetap tercatat
           sukses (riwayat 031). Memeriksa lebih dulu membuat migrasi ini aman
           dijalankan ulang. */
        $kolom = [
            'badge' => [
                'type' => 'VARCHAR', 'constraint' => 60, 'null' => TRUE,
                'comment' => 'Label kecil di kartu etalase, mis. "MBR Fixed Income"',
            ],
            'syarat_utama' => [
                'type' => 'VARCHAR', 'constraint' => 300, 'null' => TRUE,
                'comment' => 'Satu kalimat syarat yang tampil di slide',
            ],
            'gambar' => [
                'type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE,
                'comment' => 'Path relatif dari root situs; NULL = pakai bawaan',
            ],
            'urutan' => [
                'type' => 'TINYINT', 'constraint' => 3, 'unsigned' => TRUE,
                'null' => FALSE, 'default' => 99,
            ],
            'tampil_korsel' => [
                'type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 0,
                'comment' => '1 = ikut tampil di korsel beranda',
            ],
        ];
        foreach ($kolom as $nama => $spec) {
            if ( ! $this->db->field_exists($nama, 'sf_programs')) {
                $this->dbforge->add_column('sf_programs', [$nama => $spec]);
            }
        }

        /* Nilai awal. `deskripsi_singkat` SUDAH ADA dan NOT NULL — hanya ditimpa
           kalau isinya kosong, supaya teks yang mungkin sudah disunting orang
           tidak tersapu migrasi. */
        foreach ($this->awal as $kode => $v) {
            $baris = $this->db->select('id, deskripsi_singkat')
                ->get_where('sf_programs', ['kode_program' => $kode])->row_array();
            if ( ! $baris) {
                log_message('error', "Migrasi 036: program '{$kode}' tidak ada di sf_programs.");
                continue;
            }
            $set = [
                'badge'         => $v['badge'],
                'syarat_utama'  => $v['syarat'],
                'gambar'        => $v['gambar'],
                'urutan'        => $v['urutan'],
                'tampil_korsel' => 1,
            ];
            if (trim((string) $baris['deskripsi_singkat']) === '') {
                $set['deskripsi_singkat'] = $v['deskripsi'];
            }
            $this->db->where('id', (int) $baris['id'])->update('sf_programs', $set);
        }
    }

    public function down()
    {
        foreach (['badge', 'syarat_utama', 'gambar', 'urutan', 'tampil_korsel'] as $k) {
            if ($this->db->field_exists($k, 'sf_programs')) {
                $this->dbforge->drop_column('sf_programs', $k);
            }
        }
    }
}
