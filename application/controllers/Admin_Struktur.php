<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Struktur & Cakupan - master bidang dan wilayah, plus siapa yang menanganinya.
 *
 * P1 lapisan "master & kendali" superadmin. Menjawab tiga pertanyaan yang hari
 * ini tidak punya satu pun layar: bidang apa saja yang ada, wilayah mana yang
 * BELUM punya petugas, dan apakah data acuannya masih utuh.
 *
 * ═══ SATU-SATUNYA YANG BISA DIUBAH DARI SINI ADALAH `nama`. ═══
 *
 * `bidang.kode` dan `kabupaten.id` TIDAK, dan itu bukan kehati-hatian berlebih.
 * Keduanya dirujuk dari banyak tempat. Sejak migrasi 069 (2 Okt 2026) semua
 * rujukan itu ber-FK: kkn_magang_bidang/pendaftaran/posisi.bidang_kode,
 * kkn_magang_slot.bidang_kode (lewat kkn_magang_bidang), rd_laporan,
 * sf_antrean_pengajuan, sf_penilaian_perumahan, psu_serah_terima, sf_data_simperum,
 * srp2_direktori_pengembang .kabupaten_id, serta usr_akun.bidang_kode,
 * usr_akun.kabupaten_id, aduan.bidang_kode (yang terakhir tiga ini baru di 069).
 *
 * Mengubah `kode` lewat formulir berarti DB menolaknya; mengganti kunci adalah
 * migrasi data, bukan isian formulir. Hitungan yatim di layar ini dipertahankan
 * sebagai pemeriksaan silang (nol = keadaan benar).
 */
class Admin_Struktur extends Admin_Controller {

    /** Apa yang boleh diganti namanya, dan kolom kuncinya. */
    private const MASTER = [
        'bidang'    => ['tabel' => 'bidang',    'kunci' => 'kode', 'label' => 'Bidang'],
        'kabupaten' => ['tabel' => 'kabupaten', 'kunci' => 'id',   'label' => 'Kabupaten/Kota'],
    ];

    public function index()
    {
        $data['title'] = 'Struktur & Cakupan';

        $data['bidang'] = $this->db
            ->select('b.kode, b.nama,'
                . ' (SELECT COUNT(*) FROM usr_akun u WHERE u.peran = "admin_bidang" AND u.bidang_kode = b.kode) AS petugas,'
                . ' (SELECT COUNT(*) FROM aduan a WHERE a.bidang_kode = b.kode AND a.status != "Selesai") AS aduan_aktif', FALSE)
            ->from('bidang b')->order_by('b.nama', 'ASC')->get()->result();

        $data['wilayah'] = $this->db
            ->select('k.id, k.nama,'
                . ' (SELECT COUNT(*) FROM usr_akun u WHERE u.peran = "admin_kabkota" AND u.kabupaten_id = k.id) AS petugas,'
                . ' (SELECT COUNT(*) FROM rd_laporan l WHERE l.kabupaten_id = k.id) AS laporan', FALSE)
            ->from('kabupaten k')->order_by('k.nama', 'ASC')->get()->result();

        // Integritas rujukan yang TIDAK dijaga foreign key. Nol adalah keadaan
        // yang benar; angka apa pun di atas nol berarti ada kunci yang berubah
        // atau baris master yang hilang, dan tidak ada galat yang menyertainya.
        $data['yatim'] = [
            'Petugas bidang menunjuk bidang yang tidak ada' => $this->hitung_yatim(
                'usr_akun u', 'u.bidang_kode', 'bidang b', 'b.kode'),
            'Petugas wilayah menunjuk kabupaten yang tidak ada' => $this->hitung_yatim(
                'usr_akun u', 'u.kabupaten_id', 'kabupaten k', 'k.id'),
            'Aduan menunjuk bidang yang tidak ada' => $this->hitung_yatim(
                'aduan a', 'a.bidang_kode', 'bidang b', 'b.kode'),
            'Slot magang menunjuk bidang yang tidak ada' => $this->hitung_yatim(
                'kkn_magang_slot s', 's.bidang_kode', 'bidang b', 'b.kode'),
        ];

        $this->render_admin('admin/struktur/index', $data);
    }

    /**
     * Ganti nama tampilan satu baris master. Kuncinya TIDAK ikut.
     *
     * Nama boleh berubah karena ia label; kunci tidak boleh karena ia identitas
     * yang sudah tersebar ke sembilan tempat (lihat docblock kelas).
     */
    public function ubah_nama()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $jenis = (string) $this->input->post('jenis', TRUE);
        if ( ! isset(self::MASTER[$jenis])) {
            $this->session->set_flashdata('error', 'Jenis data tidak dikenal.');
            redirect('Admin_Struktur');
            return;
        }
        $master = self::MASTER[$jenis];

        $kunci = trim((string) $this->input->post('kunci', TRUE));
        $nama  = trim((string) $this->input->post('nama', TRUE));
        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->session->set_flashdata('error', 'Nama wajib diisi, maksimal 100 karakter.');
            redirect('Admin_Struktur');
            return;
        }

        // Keberadaan barisnya dicek terpisah, bukan lewat affected_rows():
        // MySQL juga membalas 0 saat namanya diketik sama persis dengan yang
        // tersimpan, dan itu bukan kegagalan.
        $lama = $this->db->select($master['kunci'] . ', nama')
            ->get_where($master['tabel'], [$master['kunci'] => $kunci])->row();
        if ( ! $lama) {
            $this->session->set_flashdata('error', 'Baris tidak ditemukan.');
            redirect('Admin_Struktur');
            return;
        }
        if ($lama->nama === $nama) {
            $this->session->set_flashdata('success', 'Nama tidak berubah.');
            redirect('Admin_Struktur');
            return;
        }

        $this->db->where($master['kunci'], $kunci)->update($master['tabel'], ['nama' => $nama]);

        $this->catat_audit('master_nama_diubah',
            $master['label'] . ' ' . $kunci . ': "' . $lama->nama . '" menjadi "' . $nama . '"',
            $master['tabel'], $kunci, ['dari' => $lama->nama, 'ke' => $nama]);

        $this->session->set_flashdata('success', 'Nama diperbarui.');
        redirect('Admin_Struktur');
    }

    /**
     * Berapa baris yang menunjuk master yang sudah tidak ada. LEFT JOIN, bukan
     * NOT IN: `NOT IN` dengan subquery yang memuat NULL memulangkan nol baris
     * untuk keadaan apa pun - hijau palsu yang sangat meyakinkan.
     */
    private function hitung_yatim($dari, $kolom_anak, $master, $kolom_master)
    {
        return (int) $this->db->from($dari)
            ->join($master, $kolom_master . ' = ' . $kolom_anak, 'left')
            ->where($kolom_anak . ' IS NOT NULL', NULL, FALSE)
            ->where($kolom_master . ' IS NULL', NULL, FALSE)
            ->count_all_results();
    }
}
