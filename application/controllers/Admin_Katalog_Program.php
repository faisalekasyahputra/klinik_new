<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Katalog Program - P2 lapisan "master & kendali" superadmin.
 *
 * ═══ KATALOGNYA HIDUP DI DUA TEMPAT, DAN KEDUANYA NYATA. ═══
 *
 * Ini bukan kelalaian yang bisa ditambal layar ini; ini pembagian tugas yang
 * sudah terlanjur ada, dan layar ini menyatakannya apa adanya:
 *
 *   `sf_programs` (tabel)          | `Smart_filter::master_programs()` (kode)
 *   -------------------------------|------------------------------------------
 *   `is_active` MENGGERBANGI        | judul & deskripsi di KARTU HASIL DIAGNOSA
 *   pengajuan - Housing_assessment  |
 *   _model:500 menolak kalau != 1   | aturan pencocokan desil -> program
 *   `nama_program` tampil di        |
 *   ANTREAN ADMIN dan /akun         |
 *
 * Akibat yang bisa dilihat warga: ia memilih "Bansos PB (Pembangunan Baru)"
 * di hasil diagnosa, lalu di /akun pengajuannya bernama "Stimulan Pembangunan
 * Baru". Program yang sama, dua nama. Empat dari enam program berselisih
 * seperti ini per 4 Agt 2026 - karena itu layar ini MENAMPILKAN selisihnya,
 * bukan berpura-pura tabelnya satu-satunya sumber.
 *
 * ═══ YANG BISA DIUBAH DARI SINI ═══
 *
 *   ✅ `nama_program`, `deskripsi_singkat` - yang dibaca warga di antrean/akun.
 *   ✅ `is_active` - kendali nyata: mematikannya menolak pengajuan baru.
 *   ❌ `kode_program` - identitas. `Housing_assessment_model:748` mencarinya
 *      lewat kolom ini, dan `Smart_filter` memetakannya sebagai literal di kode.
 *      Menggantinya lewat formulir memutus keduanya sekaligus.
 *   ❌ TAMBAH / HAPUS program - dan ini bukan kemalasan. Program baru di tabel
 *      TIDAK akan pernah dicocokkan ke siapa pun: aturan kelayakannya ditulis
 *      di `Smart_filter::get_eligible_programs()`, yaitu KODE. Tombol "tambah"
 *      di sini hanya akan melahirkan program hantu yang tampak terdaftar dan
 *      tidak pernah muncul untuk satu warga pun.
 */
class Admin_Katalog_Program extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Katalog Program';

        // Judul versi Smart_filter, dikelompokkan per kode. Bukan satu-ke-satu:
        // `oemah_lestari` punya DUA judul (subsidi & non-subsidi).
        $this->load->library('smart_filter');
        $judul_diagnosa = [];
        foreach ($this->smart_filter->master_programs() as $p) {
            $judul_diagnosa[$p['kode']][] = $p['title'];
        }

        $rows = $this->db
            ->select('p.*, k.nama_kategori,'
                . ' (SELECT COUNT(*) FROM sf_housing_queue q WHERE q.program_id = p.id) AS dipakai', FALSE)
            ->from('sf_programs p')
            ->join('sf_program_kategori k', 'k.id = p.id_kategori', 'left')
            ->order_by('k.nama_kategori', 'ASC')->order_by('p.nama_program', 'ASC')
            ->get()->result();

        foreach ($rows as $r) {
            $r->judul_diagnosa = $judul_diagnosa[$r->kode_program] ?? [];
            // Selisih dihitung di sini, bukan dibandingkan mata di layar.
            $r->selisih = $r->judul_diagnosa !== [] && ! in_array($r->nama_program, $r->judul_diagnosa, TRUE);
            $r->tanpa_aturan = $r->judul_diagnosa === [];
        }

        $data['rows'] = $rows;
        $data['jml_selisih'] = count(array_filter($rows, static function ($r) { return $r->selisih; }));
        $data['jml_tanpa_aturan'] = count(array_filter($rows, static function ($r) { return $r->tanpa_aturan; }));

        // Kode di Smart_filter yang TIDAK punya baris tabel - arah sebaliknya,
        // dan akibatnya lebih buruk: kartunya muncul di hasil diagnosa, lalu
        // pengajuannya gagal karena `program_id`-nya tidak ada.
        $kode_tabel = array_column($rows, 'kode_program');
        $data['tanpa_baris'] = array_values(array_diff(array_keys($judul_diagnosa), $kode_tabel));

        $this->render_admin('admin/katalog/index', $data);
    }

    /**
     * Ubah nama/deskripsi/status satu program. `kode_program` TIDAK ikut.
     *
     * Mematikan program yang SEDANG DIPAKAI antrean tetap boleh - baris yang
     * sudah masuk antrean tidak dibatalkan, yang ditolak hanya pengajuan BARU
     * (Housing_assessment_model:500). Itu memang gunanya sakelar ini, jadi
     * jumlah pemakainya ditampilkan di layar supaya keputusannya sadar.
     */
    public function ubah()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $id = (int) $this->input->post('id');
        $lama = $this->db->get_where('sf_programs', ['id' => $id])->row();
        if ( ! $lama) {
            $this->session->set_flashdata('error', 'Program tidak ditemukan.');
            redirect('Admin_Katalog_Program');
            return;
        }

        $nama   = trim((string) $this->input->post('nama_program', TRUE));
        $desk   = trim((string) $this->input->post('deskripsi_singkat', TRUE));
        $badge  = trim((string) $this->input->post('badge', TRUE));
        $syarat = trim((string) $this->input->post('syarat_utama', TRUE));
        $urutan = (int) $this->input->post('urutan');
        if ($nama === '' || mb_strlen($nama) > 255) {
            $this->session->set_flashdata('error', 'Nama program wajib diisi, maksimal 255 karakter.');
            redirect('Admin_Katalog_Program');
            return;
        }
        if (mb_strlen($badge) > 60 || mb_strlen($syarat) > 300) {
            $this->session->set_flashdata('error', 'Badge maksimal 60 karakter, syarat utama maksimal 300.');
            redirect('Admin_Katalog_Program');
            return;
        }
        // Dijepit, bukan ditolak: urutan salah ketik bukan alasan membuang
        // seluruh suntingan yang sudah diketik orang.
        $urutan = max(1, min(99, $urutan ?: 99));

        // Checkbox yang tidak dicentang TIDAK terkirim sama sekali - itu bukan
        // "nilai lama", itu 0. Membacanya sebagai "biarkan" membuat sakelar
        // mati yang tidak pernah bisa dimatikan.
        $aktif  = $this->input->post('is_active') ? 1 : 0;
        $korsel = $this->input->post('tampil_korsel') ? 1 : 0;

        $set = [
            'nama_program'      => $nama,
            'deskripsi_singkat' => $desk,
            'is_active'         => $aktif,
            'badge'             => $badge !== '' ? $badge : NULL,
            'syarat_utama'      => $syarat !== '' ? $syarat : NULL,
            'urutan'            => $urutan,
            'tampil_korsel'     => $korsel,
        ];

        // Gambar hanya disentuh kalau memang ada berkas dikirim. Tanpa ini,
        // menyimpan perubahan teks saja akan menghapus fotonya.
        $gambar_baru = NULL;
        if ( ! empty($_FILES['gambar']['name'])) {
            $galat = NULL;
            $gambar_baru = $this->simpan_gambar_program($lama->kode_program, $galat);
            if ($gambar_baru === NULL) {
                $this->session->set_flashdata('error', $galat ?: 'Gambar gagal disimpan.');
                redirect('Admin_Katalog_Program');
                return;
            }
            $set['gambar'] = $gambar_baru;
        }

        $this->db->where('id', $id)->update('sf_programs', $set);

        /* Berkas lama dibuang HANYA kalau ia hasil unggahan. Berkas bawaan di
           `assets/img/program/` ikut repo dan dipakai sebagai nilai awal migrasi
           036 - menghapusnya berarti deploy berikutnya menghidupkannya lagi
           sementara DB sudah menunjuk ke tempat lain. */
        if ($gambar_baru !== NULL && strpos((string) $lama->gambar, self::DIR_UNGGAHAN) === 0) {
            @unlink(FCPATH . $lama->gambar);
        }

        $berubah = [];
        if ($lama->nama_program !== $nama)              { $berubah[] = 'nama'; }
        if ($lama->deskripsi_singkat !== $desk)         { $berubah[] = 'deskripsi'; }
        if ((string) $lama->badge !== $badge)           { $berubah[] = 'badge'; }
        if ((string) $lama->syarat_utama !== $syarat)   { $berubah[] = 'syarat utama'; }
        if ((int) $lama->urutan !== $urutan)            { $berubah[] = 'urutan'; }
        if ((int) $lama->tampil_korsel !== $korsel)     { $berubah[] = $korsel ? 'ditampilkan di beranda' : 'DISEMBUNYIKAN dari beranda'; }
        if ($gambar_baru !== NULL)                      { $berubah[] = 'gambar'; }
        if ((int) $lama->is_active !== $aktif)          { $berubah[] = $aktif ? 'diaktifkan' : 'DINONAKTIFKAN'; }

        if ($berubah) {
            $this->catat_audit('program_diubah',
                'Program ' . $lama->kode_program . ': ' . implode(', ', $berubah),
                'sf_programs', $id,
                ['dari' => ['nama' => $lama->nama_program, 'aktif' => (int) $lama->is_active],
                 'ke'   => ['nama' => $nama, 'aktif' => $aktif]]);
        }

        $this->session->set_flashdata('success', $berubah ? 'Program diperbarui.' : 'Tidak ada perubahan.');
        redirect('Admin_Katalog_Program');
    }

    /** Foto unggahan dipisah dari berkas bawaan yang ikut repo. */
    const DIR_UNGGAHAN = 'assets/img/program/unggahan/';

    /**
     * Simpan foto program. Balikkan path relatif, atau NULL + $galat terisi.
     *
     * Gambar ini TAYANG PUBLIK di beranda, jadi ia tidak boleh diperlakukan
     * seperti lampiran privat: yang berbahaya di sini bukan siapa yang bisa
     * membacanya, melainkan APA yang ikut terbawa.
     *
     *   1. Jenis ditentukan `finfo` dari ISI berkas, bukan dari ekstensi
     *      maupun `$_FILES['type']` - keduanya dikirim peramban dan bisa dikarang.
     *   2. `getimagesize()` sebagai lapis kedua: berkas yang lolos finfo tapi
     *      bukan gambar sungguhan ditolak di sini.
     *   3. Metadata dilucuti. Foto lapangan dari ponsel membawa koordinat GPS,
     *      dan menayangkannya di beranda publik berarti mengumumkan lokasi
     *      pengambilannya. Pelucutnya milik induk - sama dengan yang dipakai
     *      bukti warga, satu implementasi untuk keduanya.
     *   4. Nama berkas DIBUANG dan diganti acak. Nama kiriman tidak pernah
     *      menyentuh disk, jadi tidak ada jalan menembus direktori maupun
     *      menanam ekstensi ganda.
     */
    private function simpan_gambar_program($kode, &$galat = NULL)
    {
        $f = $_FILES['gambar'];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) {
            $galat = 'Berkas gagal diunggah. Coba lagi.';
            return NULL;
        }
        if ($f['size'] > 3 * 1024 * 1024) {
            $galat = 'Ukuran gambar maksimal 3 MB.';
            return NULL;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? NULL;
        if ($ext === NULL) {
            // WEBP/AVIF sengaja belum diterima: pelucut metadata yang ada hanya
            // paham JPEG & PNG, dan menerima format yang tidak bisa dibersihkan
            // sama saja menayangkan metadatanya.
            $galat = 'Gambar harus JPG atau PNG.';
            return NULL;
        }
        if (@getimagesize($f['tmp_name']) === FALSE) {
            $galat = 'Berkas itu bukan gambar yang sah.';
            return NULL;
        }

        $dir = FCPATH . self::DIR_UNGGAHAN;
        if ( ! is_dir($dir) && ! @mkdir($dir, 0755, TRUE)) {
            $galat = 'Direktori unggahan tidak bisa dibuat.';
            return NULL;
        }

        $nama = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $kode))
              . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
        if ( ! @move_uploaded_file($f['tmp_name'], $dir . $nama)) {
            $galat = 'Gambar gagal disimpan ke disk.';
            return NULL;
        }
        if ( ! $this->strip_image_metadata($dir . $nama, $mime)) {
            @unlink($dir . $nama);
            $galat = 'Metadata gambar gagal dibersihkan; unggahan dibatalkan.';
            return NULL;
        }
        return self::DIR_UNGGAHAN . $nama;
    }
}
