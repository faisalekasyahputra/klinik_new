<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    /** Foto unggahan admin (Katalog Program); dipisah dari berkas bawaan yang ikut repo. */
    const DIR_UNGGAHAN = 'assets/img/program/unggahan/';

    /* Foto hero pilihan user (Foto Program.rar, 18 Agt 2026), dulu dipetakan langsung di
       program_showcase_carousel.php dan MENANG atas kolom `gambar`: foto yang diunggah admin
       untuk kelima program ini tersimpan tapi tidak pernah tampil di beranda. */
    const FOTO_HERO = [
        'flpp'          => 'assets/img/program/hero-2026/flpp.png',
        'oemah_lestari' => 'assets/img/program/hero-2026/oemah-lestari.webp',
        'rtlh'          => 'assets/img/program/hero-2026/rtlh.png',
        'pb'            => 'assets/img/program/hero-2026/pb.jpeg',
        'rumah_apung'   => 'assets/img/program/hero-2026/rumah-apung.png',
    ];

    /**
     * Gambar yang BENAR-BENAR tampil untuk satu program, dipakai korsel beranda dan layar
     * ubah katalog (thumbnail) supaya keduanya tidak pernah berbeda. Urutan: unggahan admin,
     * foto hero, kolom `gambar` bawaan, gambar cadangan.
     */
    public function gambar_tampil(array $p) {
        $g = (string) ($p['gambar'] ?? '');
        if (strpos($g, self::DIR_UNGGAHAN) === 0) { return $g; }
        return self::FOTO_HERO[$p['kode_program'] ?? ''] ?? ($g !== '' ? $g : 'assets/img/program/01_subsidif_lpp.avif');
    }

    public function __construct() {
        parent::__construct();
        $this->load->helper('housing_queue');
    }

    /**
     * Program yang tampil di korsel etalase beranda - SATU-SATUNYA sumbernya.
     *
     * Sampai 5 Agt 2026 daftar ini hardcode di dalam JS komponen korsel, dan
     * `sf_program` cuma dipakai untuk kelayakan. Dua tempat itu sudah melenceng
     * (judul berbeda antara katalog admin dan korsel), dan itulah yang membuat
     * layar Katalog Program lahir sebagai pembanding. Migrasi 036 memindahkan
     * yang DITAMPILKAN ke tabel; sejak itu korsel membaca dari sini.
     *
     * `aktif` IKUT MENGGERBANG. Program yang dinonaktifkan di Katalog
     * Program tidak boleh terus dipromosikan di beranda - kalau tidak, warga
     * mengeklik sesuatu yang pengajuannya sudah ditutup.
     */
    public function etalase() {
        if ( ! $this->db->table_exists('sf_program')
            || ! $this->db->field_exists('tampil_korsel', 'sf_program')) {
            // Migrasi 036 belum jalan. Bukan alasan menampilkan data lain -
            // dua sumber justru masalah yang sedang dibereskan.
            log_message('error', 'Program_model::etalase() - kolom etalase belum ada; jalankan migrasi 036.');
            return [];
        }
        $rows = $this->db->select('id, kode_program, nama_program, deskripsi_singkat,
                                   lencana, syarat_utama, gambar')
            ->where(['tampil_korsel' => 1, 'aktif' => 1])
            ->order_by('urutan', 'ASC')->order_by('id', 'ASC')
            ->get('sf_program')->result_array();

        // `db_debug` mati di production: query gagal mengembalikan array kosong
        // tanpa suara. Dicatat supaya kekosongan bisa ditelusuri, bukan ditebak.
        if ( ! $rows) {
            log_message('error', 'Program_model::etalase() - nol program etalase aktif.');
        }
        return $rows;
    }

    /** Program aktif beserta nama kategorinya, untuk halaman publik Program Pemerintah (SEO 6 Okt 2026). */
    public function daftar_publik($kode = NULL) {
        $this->db->select('p.id, p.kode_program, p.nama_program, p.deskripsi_singkat, p.lencana, p.syarat_utama,
                           p.batas_penghasilan_maks, p.gambar, k.nama_kategori', FALSE)
            ->from('sf_program p')->join('sf_program_kategori k', 'k.id = p.kategori_id', 'left')
            ->where('p.aktif', 1)->order_by('p.urutan', 'ASC')->order_by('p.id', 'ASC');
        if ($kode !== NULL) { $this->db->where('p.kode_program', (string) $kode); }
        return $this->db->get()->result_array();
    }

    public function get_program_by_code($kode_program) {
        $this->db->where('kode_program', $kode_program);
        $this->db->where('aktif', 1);
        return $this->db->get('sf_program')->row_array();
    }

    /**
     * Tentukan kabupaten_id yang boleh disimpan ke sf_antrean_pengajuan.
     *
     * URUTAN KEPERCAYAAN (jangan dibalik):
     *   1. Domisili user yang login (usr_akun.kabupaten_id) - data terverifikasi,
     *      tidak bisa dipalsukan pemohon lewat form.
     *   2. Pilihan user di form, TAPI wajib cocok dengan baris nyata di tabel
     *      kabupaten - menutup nilai sembarang/ngawur.
     *   3. NULL kalau dua-duanya tidak tersedia (tamu tanpa pilihan valid).
     *
     * Kenapa penting: kolom ini yang jadi dasar WHERE di dashboard Admin_Kabkota.
     * Sebelum ini nilainya diambil mentah dari $_POST, jadi pemohon bisa
     * mengarahkan pengajuannya ke admin wilayah manapun, atau mengirim nilai
     * kosong/ngawur supaya barisnya tidak muncul di dashboard siapa pun.
     * Lihat docs/engineering/AUDIT_ROLE_ADMIN_SCOPED.md temuan #1 (tingkat Tinggi).
     *
     * ASUMSI PRODUK: untuk pemohon yang sudah login dan profilnya punya
     * kabupaten, domisili profil MENANG atas pilihan dropdown. Kalau nanti
     * kebijakannya "warga boleh mengajukan ke kabupaten lain", ubah di sini -
     * satu tempat, bukan tersebar di controller.
     */
    public function resolve_kabupaten_id($user_id = NULL, $requested_id = NULL) {
        if ( ! empty($user_id)) {
            $profil = $this->db->select('kabupaten_id')
                ->get_where('usr_akun', ['id' => (int) $user_id])->row();
            if ($profil && ! empty($profil->kabupaten_id)) {
                return (int) $profil->kabupaten_id;
            }
        }

        $requested_id = (int) $requested_id;
        if ($requested_id > 0 && $this->db->where('id', $requested_id)->count_all_results('kabupaten') > 0) {
            return $requested_id;
        }

        return NULL;
    }

    /* insert_housing_queue() dan create_housing_submission() DIHAPUS 2 Okt 2026 (migrasi 067):
       keduanya penulis NIK/nama/JSON polos ke sf_antrean_pengajuan untuk jalur diagnosa lama yang
       tidak lagi dipanggil siapa pun sejak 27 Sep 2026 (lihat Program.php). Satu-satunya
       penulis antrean sekarang Housing_assessment_model::submit_owned_assessment(). */

    public function transition_housing_queue($antrean_id, $status, $reviewer_id, $kabupaten_id = NULL, $catatan = '') {
        $antrean_id = (int) $antrean_id;
        $catatan = trim((string) $catatan);

        $this->db->where('id', $antrean_id);
        if ($kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $kabupaten_id);
        }
        $row = $this->db->get('sf_antrean_pengajuan')->row();

        if ( ! $row) {
            return ['success' => FALSE, 'code' => 'not_found', 'message' => 'Data pengajuan tidak ditemukan dalam kewenangan Anda.'];
        }
        if ( ! housing_queue_can_transition($row->status_antrean, $status)) {
            return ['success' => FALSE, 'code' => 'invalid_transition', 'message' => 'Perubahan status tersebut tidak diizinkan. Muat ulang halaman untuk melihat status terbaru.'];
        }
        if ($status === 'rejected' && $catatan === '') {
            return ['success' => FALSE, 'code' => 'note_required', 'message' => 'Catatan alasan penolakan wajib diisi.'];
        }

        $this->db->where('id', $antrean_id)
            ->where('status_antrean', $row->status_antrean);
        if ($kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $kabupaten_id);
        }
        $updated = $this->db->update('sf_antrean_pengajuan', [
            'status_antrean' => $status,
            'catatan_admin'  => $status === 'rejected' ? $catatan : NULL,
            'reviewed_by'    => (int) $reviewer_id,
            'reviewed_at'    => date('Y-m-d H:i:s'),
        ]);

        if ( ! $updated || $this->db->affected_rows() !== 1) {
            return ['success' => FALSE, 'code' => 'write_failed', 'message' => 'Keputusan tidak tersimpan. Data mungkin sudah berubah; silakan muat ulang halaman.'];
        }

        return ['success' => TRUE, 'from' => $row->status_antrean, 'to' => $status];
    }

    public function generate_ticket_code() {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'PKP-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = $this->db->where('kode_tiket', $code)->count_all_results('sf_antrean_pengajuan') > 0;
        } while ($exists);

        return $code;
    }

    /* get_housing_queue_by_ticket() DIHAPUS 16 Agt 2026 - permintaan user
       "Cek status pengajuan dari frontend dihapus aja". Method ini
       satu-satunya pemanggilnya dulu (Program::cek_tiket()) adalah
       pencarian tiket+4-digit-NIK tanpa login yang jadi permukaan
       penelusuran (10.000 kemungkinan NIK, rate limit doang penahannya) -
       lihat komentar panjang di Program::cek_tiket(). Dihapus dari model,
       bukan cuma tidak dipanggil, supaya tidak tergoda dipakai lagi
       lewat jalur lain nanti. */

}
