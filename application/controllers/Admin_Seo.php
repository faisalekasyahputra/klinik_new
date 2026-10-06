<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO Halaman (grup Master, Super Admin; permintaan user 6 Okt 2026). Menimpa judul, deskripsi, gambar
 * pratinjau (og:image), dan status indeks halaman portal tanpa mengubah kode. Isiannya di tabel
 * seo_halaman (migrasi 075) dan dibaca seo_meta() (helpers/seo_helper.php); isian kosong = ikut bawaan.
 *
 * Halaman yang bisa diatur: yang terdaftar di config/seo.php dan setiap program aktif di Katalog Program.
 * Halaman pribadi (akun, alur login, forum) sengaja tidak masuk daftar: SEO-nya tidak boleh dibuka.
 * Beranda berkunci '' di tabel; di formulir dan URL ditulis '/'.
 */
class Admin_Seo extends Admin_Controller {

    const DIR_UNGGAHAN = 'assets/img/og/unggahan/';
    const MAKS_JUDUL = 60, MAKS_DESKRIPSI = 160;

    public function index()
    {
        $data['title'] = 'SEO Halaman';
        $data['halaman'] = $this->daftar_halaman();
        $this->render_admin('admin/seo/index', $data);
    }

    public function ubah()
    {
        $kunci = $this->kunci_masuk($this->input->get('halaman', TRUE));
        $semua = $this->daftar_halaman();
        if ($kunci === NULL || ! isset($semua[$kunci])) { show_404(); return; }
        $data['title'] = 'Ubah SEO Halaman';
        $data['kunci'] = $kunci;
        $data['h'] = $semua[$kunci];
        $data['maks_judul'] = self::MAKS_JUDUL;
        $data['maks_deskripsi'] = self::MAKS_DESKRIPSI;
        $this->render_admin('admin/seo/ubah', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $kunci = $this->kunci_masuk($this->input->post('kunci', TRUE));
        $semua = $this->daftar_halaman();
        if ($kunci === NULL || ! isset($semua[$kunci])) { show_404(); return; }
        $kembali = 'Admin_Seo/ubah?halaman=' . rawurlencode($kunci === '' ? '/' : $kunci);

        $judul = trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('judul', TRUE)));
        $deskripsi = trim(preg_replace('/\s+/u', ' ', (string) $this->input->post('deskripsi', TRUE)));
        $indeks = (string) $this->input->post('indeks', TRUE);
        if (mb_strlen($judul) > self::MAKS_JUDUL || mb_strlen($deskripsi) > self::MAKS_DESKRIPSI) {
            $this->session->set_flashdata('error', 'Judul maksimal ' . self::MAKS_JUDUL . ' karakter, deskripsi maksimal ' . self::MAKS_DESKRIPSI . ' karakter.');
            redirect($kembali);
            return;
        }
        if ( ! in_array($indeks, ['bawaan', 'ya', 'tidak'], TRUE)) { $indeks = 'bawaan'; }

        $lama = $this->db->get_where('seo_halaman', ['kunci' => $kunci])->row_array();
        $gambar = $lama['gambar'] ?? NULL;
        $buang = [];
        if ($this->input->post('hapus_gambar') && $gambar) { $buang[] = $gambar; $gambar = NULL; }
        if ( ! empty($_FILES['gambar']['name'])) {
            $galat = NULL;
            $baru = $this->simpan_gambar($kunci, $galat);
            if ($baru === NULL) {
                $this->session->set_flashdata('error', $galat ?: 'Gambar gagal disimpan.');
                redirect($kembali);
                return;
            }
            if ($gambar) { $buang[] = $gambar; }
            $gambar = $baru;
        }

        $isi = ['judul' => $judul !== '' ? $judul : NULL, 'deskripsi' => $deskripsi !== '' ? $deskripsi : NULL, 'gambar' => $gambar,
            'noindex' => $indeks === 'bawaan' ? NULL : ($indeks === 'tidak' ? 1 : 0)];
        if ( ! array_filter($isi, fn($v) => $v !== NULL)) {
            // Semua kosong = kembali ke bawaan; baris tidak disimpan.
            $this->db->delete('seo_halaman', ['kunci' => $kunci]);
        } elseif ($lama) {
            $this->db->where('kunci', $kunci)->update('seo_halaman', $isi + ['diubah_oleh' => (int) $this->get_user_id(), 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $this->db->insert('seo_halaman', $isi + ['kunci' => $kunci, 'diubah_oleh' => (int) $this->get_user_id(), 'updated_at' => date('Y-m-d H:i:s')]);
        }
        foreach ($buang as $g) { $this->hapus_berkas($g); }

        $this->catat_audit('seo_diubah', 'SEO halaman /' . $kunci . ' diubah', 'seo_halaman', $kunci === '' ? '/' : $kunci,
            ['judul' => $isi['judul'], 'deskripsi' => $isi['deskripsi'], 'gambar' => $gambar !== ($lama['gambar'] ?? NULL), 'indeks' => $indeks]);
        $this->session->set_flashdata('success', 'SEO halaman /' . $kunci . ' disimpan. Mesin pencari membaca perubahan saat merayapi ulang halaman ini.');
        redirect($kembali);
    }

    public function kembalikan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $kunci = $this->kunci_masuk($this->input->post('kunci', TRUE));
        $lama = $kunci === NULL ? NULL : $this->db->get_where('seo_halaman', ['kunci' => $kunci])->row_array();
        if ($lama) {
            $this->db->delete('seo_halaman', ['kunci' => $kunci]);
            if ($lama['gambar']) { $this->hapus_berkas($lama['gambar']); }
            $this->catat_audit('seo_dikembalikan', 'SEO halaman /' . $kunci . ' dikembalikan ke bawaan', 'seo_halaman', $kunci === '' ? '/' : $kunci);
        }
        $this->session->set_flashdata('success', 'SEO halaman /' . (string) $kunci . ' kembali ke bawaan.');
        redirect('Admin_Seo');
    }

    /** '/' = beranda; selain itu harus berbentuk alamat halaman (tanpa titik ganda, tanpa skema). */
    private function kunci_masuk($nilai)
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '/') { return ''; }
        return preg_match('#^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$#', $nilai) ? $nilai : NULL;
    }

    /**
     * Semua halaman yang SEO-nya boleh diatur: [kunci => kelompok, bawaan{judul, deskripsi, gambar, noindex},
     * timpaan|null, efektif{...}]. Bawaan dihitung dengan aturan yang sama dengan seo_meta().
     */
    private function daftar_halaman()
    {
        $cfg = seo_cfg();
        $kartu = function ($kunci) { $f = 'assets/img/og/' . ($kunci === '' ? 'beranda' : strtolower(str_replace('/', '-', $kunci))) . '.jpg'; return is_file(FCPATH . $f) ? $f : NULL; };
        $baris = [];
        foreach ((array) $cfg['halaman'] as $kunci => $isi) {
            $kunci = (string) $kunci;
            $baris[$kunci] = ['kelompok' => 'Halaman', 'bawaan' => ['judul' => (string) $isi['judul'], 'deskripsi' => (string) $isi['deskripsi'],
                'gambar' => $kartu($kunci) ?? $cfg['gambar'], 'noindex' => seo_noindex(strtolower($kunci))]];
        }
        $this->load->model('Program_model');
        foreach ($this->Program_model->daftar_publik() as $p) {
            $kunci = 'program-pemerintah/' . str_replace('_', '-', $p['kode_program']);
            $b = $this->Program_model->seo_bawaan($p);
            $baris[$kunci] = ['kelompok' => 'Program', 'bawaan' => ['judul' => $b['judul'], 'deskripsi' => seo_potong($b['deskripsi']),
                'gambar' => $kartu($kunci) ?? ($b['gambar'] !== '' ? $b['gambar'] : $cfg['gambar']), 'noindex' => FALSE]];
        }
        $timpaan = [];
        foreach ($this->db->get('seo_halaman')->result_array() as $r) { $timpaan[(string) $r['kunci']] = $r; }
        foreach ($baris as $kunci => &$h) {
            $t = $timpaan[$kunci] ?? NULL;
            $h['timpaan'] = $t;
            $h['efektif'] = [
                'judul' => (string) ($t['judul'] ?? '') !== '' ? $t['judul'] : $h['bawaan']['judul'],
                'deskripsi' => (string) ($t['deskripsi'] ?? '') !== '' ? $t['deskripsi'] : $h['bawaan']['deskripsi'],
                'gambar' => (string) ($t['gambar'] ?? '') !== '' ? $t['gambar'] : $h['bawaan']['gambar'],
                'noindex' => ($t['noindex'] ?? NULL) !== NULL ? (bool) $t['noindex'] : $h['bawaan']['noindex'],
            ];
        }
        unset($h);
        return $baris;
    }

    /**
     * Gambar pratinjau unggahan: JPG/PNG maks 3 MB, diperiksa finfo + getimagesize + pemindai, lalu DIKODEKAN
     * ULANG ke JPG 1200x630 (dipotong di tengah). Kode ulang membuang metadata (GPS) dan apa pun yang
     * menumpang di berkas asal; berkas kiriman sendiri tidak pernah ditulis ke webroot. Nama berkas acak.
     */
    private function simpan_gambar($kunci, &$galat = NULL)
    {
        $f = $_FILES['gambar'];
        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) { $galat = 'Berkas gagal diunggah. Coba lagi.'; return NULL; }
        if ($f['size'] > 3 * 1024 * 1024) { $galat = 'Ukuran gambar maksimal 3 MB.'; return NULL; }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? NULL;
        if ($ext === NULL) { $galat = 'Gambar harus JPG atau PNG.'; return NULL; }
        $ukuran = @getimagesize($f['tmp_name']);
        if ($ukuran === FALSE) { $galat = 'Berkas itu bukan gambar yang sah.'; return NULL; }
        if ($ukuran[0] < 600 || $ukuran[1] < 315) { $galat = 'Gambar terlalu kecil; minimal 600x315 piksel (disarankan 1200x630).'; return NULL; }
        if ( ! $this->scan_uploaded_file($f['tmp_name'], $ext, $galat, 'seo_halaman')) { return NULL; }

        $src = $ext === 'png' ? @imagecreatefrompng($f['tmp_name']) : @imagecreatefromjpeg($f['tmp_name']);
        if ( ! $src) { $galat = 'Gambar tidak bisa dibaca.'; return NULL; }
        $w = 1200; $h = 630; $sw = imagesx($src); $sh = imagesy($src);
        $s = max($w / $sw, $h / $sh); $cw = (int) round($w / $s); $ch = (int) round($h / $s);
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255)); // latar PNG transparan
        imagecopyresampled($im, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);
        imagedestroy($src);

        $dir = FCPATH . self::DIR_UNGGAHAN;
        if ( ! is_dir($dir) && ! @mkdir($dir, 0755, TRUE)) { $galat = 'Direktori unggahan tidak bisa dibuat.'; imagedestroy($im); return NULL; }
        $nama = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($kunci === '' ? 'beranda' : $kunci)), '-') . '-' . bin2hex(random_bytes(6)) . '.jpg';
        $ok = imagejpeg($im, $dir . $nama, 85);
        imagedestroy($im);
        if ( ! $ok) { $galat = 'Gambar gagal disimpan ke disk.'; return NULL; }
        return self::DIR_UNGGAHAN . $nama;
    }

    /** Hanya berkas di folder unggahan SEO yang boleh dihapus; kartu bawaan dan aset lain tidak pernah. */
    private function hapus_berkas($path)
    {
        $path = (string) $path;
        if (strpos($path, self::DIR_UNGGAHAN) === 0 && basename($path) === substr($path, strlen(self::DIR_UNGGAHAN))) {
            @unlink(FCPATH . $path);
        }
    }
}
