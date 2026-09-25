<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bank Data untuk superadmin (daftar revisi dinas 23 Sep 2026): unggah PDF Buku Data dan PDF
 * Statistika yang tampil sebagai kartu di tab Bank Data publik (migrasi 063).
 *
 * PDF-nya dokumen PUBLIK, jadi disimpan di webroot (assets/dokumen/unggahan/, di-gitignore
 * supaya bertahan di setiap deploy) dengan nama acak, setelah lolos pindai isi (poin 11.4:
 * JavaScript/aksi otomatis/enkripsi di PDF ditolak). Nama berkas kiriman tidak pernah
 * menyentuh disk.
 */
class Admin_Bank_Data extends Admin_Controller {

    const TABEL = 'sf_bank_data_dokumen';
    const DIR = 'assets/dokumen/unggahan/';
    const JENIS = ['buku_data' => 'Buku Data', 'statistika' => 'Statistika'];
    const MAKS_BYTE = 20971520; // 20 MB; buku data bisa tebal.

    public function index()
    {
        $data['title'] = 'Bank Data';
        $data['jenis'] = self::JENIS;
        $data['rows'] = $this->db->order_by('jenis', 'ASC')->order_by('urutan', 'ASC')->order_by('id', 'DESC')
            ->get(self::TABEL)->result();
        $this->render_admin('admin/bank_data/index', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $jenis = (string) $this->input->post('jenis', TRUE);
        $judul = trim((string) $this->input->post('judul', TRUE));
        $deskripsi = trim((string) $this->input->post('deskripsi', TRUE));
        $urutan = (int) $this->input->post('urutan');
        if ( ! isset(self::JENIS[$jenis])) { return $this->gagal('Jenis dokumen tidak dikenal.'); }
        if ($judul === '' || mb_strlen($judul) > 150) { return $this->gagal('Judul wajib diisi, maksimal 150 karakter.'); }
        if (mb_strlen($deskripsi) > 255) { return $this->gagal('Deskripsi maksimal 255 karakter.'); }

        $f = $_FILES['berkas_pdf'] ?? NULL;
        if ( ! $f || (int) $f['error'] !== UPLOAD_ERR_OK) { return $this->gagal('Pilih berkas PDF yang akan diunggah.'); }
        if ((int) $f['size'] <= 0 || (int) $f['size'] > self::MAKS_BYTE) { return $this->gagal('Ukuran PDF harus lebih dari 0 dan maksimal 20 MB.'); }
        if ((new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) !== 'application/pdf') { return $this->gagal('Berkas harus PDF.'); }
        $galat = NULL;
        if ( ! $this->scan_uploaded_file($f['tmp_name'], 'pdf', $galat, 'bank_data')) { return $this->gagal($galat); }

        $dir = FCPATH . self::DIR;
        if ( ! is_dir($dir) && ! @mkdir($dir, 0755, TRUE)) { return $this->gagal('Direktori unggahan tidak bisa dibuat.'); }
        $nama = $jenis . '-' . bin2hex(random_bytes(8)) . '.pdf';
        if ( ! @move_uploaded_file($f['tmp_name'], $dir . $nama)) { return $this->gagal('PDF gagal disimpan ke disk.'); }

        $now = date('Y-m-d H:i:s');
        $row = ['jenis' => $jenis, 'judul' => $judul, 'deskripsi' => $deskripsi === '' ? NULL : $deskripsi,
                'berkas' => self::DIR . $nama, 'ukuran' => (int) $f['size'], 'aktif' => 1, 'urutan' => max(0, min(999, $urutan)),
                'diunggah_oleh' => (int) $this->get_user_id(), 'created_at' => $now, 'updated_at' => $now];
        if ( ! $this->db->insert(self::TABEL, $row)) {
            @unlink($dir . $nama);
            return $this->gagal('Dokumen belum tersimpan. Coba lagi.');
        }
        $id = (string) $this->db->insert_id();
        $this->catat_audit('bank_data_diunggah', 'Mengunggah ' . self::JENIS[$jenis] . ': ' . $judul, self::TABEL, $id, ['jenis' => $jenis]);
        $this->session->set_flashdata('success', 'Dokumen diunggah dan langsung tampil di Bank Data.');
        redirect('Admin_Bank_Data');
    }

    public function ubah_status($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where(self::TABEL, ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }
        $aktif = $row->aktif ? 0 : 1;
        $this->db->where('id', (int) $id)->update(self::TABEL, ['aktif' => $aktif, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->catat_audit('bank_data_status', ($aktif ? 'Menampilkan ' : 'Menyembunyikan ') . $row->judul, self::TABEL, (string) $row->id, ['aktif' => $aktif]);
        $this->session->set_flashdata('success', $aktif ? 'Dokumen ditampilkan lagi di Bank Data.' : 'Dokumen disembunyikan dari Bank Data.');
        redirect('Admin_Bank_Data');
    }

    public function hapus($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where(self::TABEL, ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }
        $this->db->where('id', (int) $id)->delete(self::TABEL);
        // Hanya berkas di folder unggahan yang boleh dihapus, jadi baris yang dimanipulasi tidak
        // bisa menghapus berkas lain di webroot.
        if (strpos($row->berkas, self::DIR) === 0 && basename($row->berkas) === substr($row->berkas, strlen(self::DIR))) {
            @unlink(FCPATH . $row->berkas);
        }
        $this->catat_audit('bank_data_dihapus', 'Menghapus ' . (self::JENIS[$row->jenis] ?? $row->jenis) . ': ' . $row->judul, self::TABEL, (string) $row->id, ['berkas' => basename($row->berkas)]);
        $this->session->set_flashdata('success', 'Dokumen dihapus.');
        redirect('Admin_Bank_Data');
    }

    private function gagal($pesan)
    {
        $this->session->set_flashdata('error', $pesan);
        redirect('Admin_Bank_Data');
    }
}
