<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar bidang disesuaikan dengan struktur Disperakim yang sebenarnya.
 *
 * Dikonfirmasi langsung ke dinas 1 Agt 2026: bidang ada LIMA — Sekretariat,
 * Perencanaan Teknis, Kawasan, Perumahan, Pertanahan — dan tidak ada satuan
 * di bawahnya ("habis kadinas bawahnya langsung bidang-bidang").
 *
 * Tabel ini berisi `pengembang` dan `umum`, dua-duanya BUKAN bidang. Keduanya
 * lahir dari tebakan awal, bukan dari struktur organisasi, lalu tersalin ke
 * tiga tempat lain: whitelist validasi di Umum::kirim_aduan(), daftar pilihan
 * di formulir aduan, dan peta label di Aduan_model. Migrasi ini membetulkan
 * tabelnya; ketiga salinan itu dicabut di commit yang sama supaya tidak ada
 * yang tertinggal menyebut bidang yang tidak ada.
 *
 * Penghapusan DIJAGA: baris hanya dibuang kalau benar-benar tidak dirujuk
 * usr_users maupun aduan. `aduan.bidang` tidak punya FK, jadi tidak ada yang
 * menahan penghapusan yang keliru selain pemeriksaan ini — dan aduan yang
 * bidangnya lenyap akan hilang dari dashboard siapa pun tanpa jejak.
 */
class Migration_Bidang_sesuai_struktur_dinas extends CI_Migration {

    /** kode => nama, sesuai jawaban dinas. */
    private $benar = [
        'sekretariat'        => 'Sekretariat',
        'perencanaan_teknis' => 'Perencanaan Teknis',
        'kawasan'            => 'Bidang Kawasan Permukiman',
        'perumahan'          => 'Bidang Perumahan',
        'pertanahan'         => 'Bidang Pertanahan',
    ];

    public function up()
    {
        foreach ($this->benar as $kode => $nama) {
            $this->db->query("INSERT INTO `bidang` (`kode`, `nama`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`)", [$kode, $nama]);
        }

        foreach ($this->db->get('bidang')->result() as $baris) {
            if (isset($this->benar[$baris->kode])) { continue; }

            $dipakai = (int) $this->db->where('bidang_kode', $baris->kode)->count_all_results('usr_users')
                     + (int) $this->db->where('bidang', $baris->kode)->count_all_results('aduan');

            // Yang masih dirujuk DIBIARKAN. Menghapusnya membuat aduan atau akun
            // admin menunjuk ke bidang yang tidak ada, dan barisnya menghilang
            // dari dashboard siapa pun tanpa pesan apa-apa.
            if ($dipakai === 0) { $this->db->delete('bidang', ['kode' => $baris->kode]); }
        }
    }

    public function down()
    {
        // Sengaja TIDAK mengembalikan 'pengembang' dan 'umum': keduanya memang
        // tidak pernah ada di struktur dinas, dan menghidupkannya kembali hanya
        // menanam ulang kesalahan yang sama.
        foreach (['sekretariat', 'perencanaan_teknis'] as $kode) {
            $dipakai = (int) $this->db->where('bidang_kode', $kode)->count_all_results('usr_users')
                     + (int) $this->db->where('bidang', $kode)->count_all_results('aduan');
            if ($dipakai === 0) { $this->db->delete('bidang', ['kode' => $kode]); }
        }
    }
}
