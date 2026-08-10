<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Slot magang: dari array hardcode menjadi data yang bisa dikelola.
 *
 * Sebelum ini ketersediaan slot hidup sebagai array literal di
 * `KemitraanPortal::magang()`, dan view-nya sendiri mengaku "Data dummy untuk
 * rancangan awal". Akibatnya dua hal: tidak ada seorang pun yang bisa
 * mengubahnya tanpa deploy, dan formulir pendaftaran tidak pernah tunduk
 * padanya - `divisi_atau_tema` adalah teks bebas, jadi mahasiswa bisa
 * mengetik divisi yang di layar berwarna merah.
 *
 * DUA tabel, bukan satu. Daftar divisi harus bisa berdiri sendiri: kalau nama
 * divisi disimpan sebagai teks di baris slot, maka "daftar divisi" cuma bisa
 * diturunkan lewat SELECT DISTINCT - dan divisi yang belum punya slot sama
 * sekali menjadi mustahil ditampilkan.
 *
 * `kkn_magang_slot` sengaja TIDAK punya kolom `tersedia`. Baris yang ada
 * berarti buka; tidak ada baris berarti tutup. Dengan kolom boolean, "tidak
 * ada baris" dan "ada baris tapi tersedia=0" menjadi dua cara mengatakan hal
 * yang sama, dan cepat atau lambat keduanya berselisih. Menutup slot berarti
 * menghapus barisnya.
 *
 * `tahun` ada sejak awal meski tampilan lama hanya mengenal nama bulan. Tanpa
 * itu tabel ini basi setiap Januari dan tidak ada cara membedakan Juni tahun
 * ini dari Juni tahun lalu.
 *
 * FK `divisi_id` INT SIGNED mengikuti `kkn_magang_divisi.id` yang dibuat di
 * berkas ini juga - lihat AGENTS.md §0e soal errno 150 kalau tipe rujukan
 * tidak sama persis.
 */
class Migration_Kemitraan_slot_magang extends CI_Migration {

    /** Divisi yang selama ini hardcode, beserta bulan yang berwarna hijau. */
    private $seed = [
        'Administrasi Pemerintahan'           => ['Juni'],
        'Infrastruktur dan Teknologi Digital' => ['Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        'Komunikasi Publik dan Media'         => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        'Komunikasi Publik dan Media (PPID)'  => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        'Pengelolaan Data Statistik'          => ['Oktober', 'November', 'Desember'],
        'Penyediaan dan Pengadaan Barang'     => ['Januari', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        'Sekretariat'                         => ['Maret', 'April', 'Mei', 'Juni', 'Oktober', 'November', 'Desember'],
    ];

    private $nomor_bulan = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `kkn_magang_divisi` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `nama` VARCHAR(150) NOT NULL,
            `aktif` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Nonaktif = tidak ditawarkan pendaftaran baru',
            `urutan` SMALLINT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_divisi_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS `kkn_magang_slot` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `divisi_id` INT NOT NULL,
            `tahun` SMALLINT UNSIGNED NOT NULL,
            `bulan` TINYINT UNSIGNED NOT NULL COMMENT '1..12',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_slot_divisi_periode` (`divisi_id`,`tahun`,`bulan`),
            KEY `idx_slot_periode` (`tahun`,`bulan`),
            CONSTRAINT `fk_slot_divisi` FOREIGN KEY (`divisi_id`)
                REFERENCES `kkn_magang_divisi` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Seed dipatok ke tahun berjalan supaya yang tampil di layar hari ini
        // persis sama dengan sebelum migrasi - perpindahan penyimpanan tidak
        // boleh sekalian mengubah isinya. INSERT IGNORE: migrasi ini idempoten,
        // menjalankannya dua kali tidak menggandakan apa pun.
        $tahun = (int) date('Y');
        $urutan = 0;

        foreach ($this->seed as $nama => $bulan_terbuka) {
            $urutan += 10;
            $this->db->query("INSERT IGNORE INTO `kkn_magang_divisi` (`nama`, `urutan`) VALUES (?, ?)",
                [$nama, $urutan]);

            $divisi = $this->db->get_where('kkn_magang_divisi', ['nama' => $nama])->row();
            if ( ! $divisi) { continue; }

            foreach ($bulan_terbuka as $nama_bulan) {
                if ( ! isset($this->nomor_bulan[$nama_bulan])) { continue; }
                $this->db->query("INSERT IGNORE INTO `kkn_magang_slot` (`divisi_id`, `tahun`, `bulan`) VALUES (?, ?, ?)",
                    [(int) $divisi->id, $tahun, $this->nomor_bulan[$nama_bulan]]);
            }
        }
    }

    public function down()
    {
        // Slot lebih dulu: ia yang memegang FK ke divisi.
        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_slot`");
        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_divisi`");
    }
}
