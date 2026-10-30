<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data - PBG (pembebasan retribusi PBG), permintaan dinas 30 Okt 2026.
 * Rekam_Bphtb memakai kelas ini dengan $jenis lain; dua controller supaya tiap menu
 * punya awalan URL sendiri dan ikut hak modulnya sendiri (dashboard_modules.php).
 *
 * "Langsung di model kayak upload BNBA": satu berkas Excel per kabupaten per BULAN
 * (format dinas mencatat bulan di setiap baris), dibaca Rekam_pbg_bphtb_import, satu
 * baris cacat menolak seluruh berkas. Tanpa kirim/tinjau; unggah ulang menggantikan.
 * Kabupaten SELALU dari sesi; superadmin lewat $izinkan_bertindak.
 */
class Rekam_Pbg extends Admin_Kabkota_Controller {

    protected $izinkan_bertindak = TRUE;
    protected $jenis = 'pbg';
    protected $label = 'PBG';

    const DOMAIN = 'rekam_pbg_bphtb';

    public function index()
    {
        $this->load->library('rekam_pbg_bphtb_import'); // daftar nama bulan di tampilan
        $tahun = $this->tahun_dari($this->input->get('tahun'));
        $data = [
            'title'       => 'Rekam Data ' . $this->label,
            'jenis'       => $this->jenis,
            'label'       => $this->label,
            'kelas'       => get_class($this),
            'scope_label' => $this->nama_wilayah(),
            'tahun'       => $tahun,
            'bulan_pilih' => $this->bulan_dari($this->input->get('bulan')),
            'unggahan'    => array_column($this->db->where(['jenis' => $this->jenis,
                'kabupaten_id' => (int) $this->my_kabupaten_id, 'tahun' => $tahun])
                ->get('rd_pbg_bphtb')->result_array(), NULL, 'bulan'),
            'angka'       => array_column($this->db->where(['jenis' => $this->jenis,
                'kabupaten_id' => (int) $this->my_kabupaten_id, 'tahun' => $tahun])
                ->get('rd_pbg_bphtb_angka')->result_array(), NULL, 'bulan'),
            'label_jumlah' => $this->jenis === 'pbg' ? 'Jumlah unit dibebaskan retribusi PBG' : 'Jumlah penerima dibebaskan BPHTB',
            'label_nilai'  => $this->jenis === 'pbg' ? 'Nilai retribusi PBG dibebaskan (Rp)' : 'Nilai BPHTB dibebaskan (Rp)',
        ];
        $this->render_scoped_admin('admin/rekam/pbg_bphtb', $data);
    }

    public function unggah()
    {
        // Penjaga di metode, bukan post_only: Rekam_Bphtb mewarisi metode ini dan daftar post_only per kelas.
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $tahun = $this->tahun_dari($this->input->post('tahun'));
        $bulan = $this->bulan_dari($this->input->post('bulan'));
        $kembali = get_class($this) . '?tahun=' . $tahun . '&bulan=' . $bulan;
        if (empty($_FILES['berkas']['name'])) {
            $this->session->set_flashdata('error', 'Pilih berkas Excel lebih dulu.');
            redirect($kembali);
        }

        $kab = (int) $this->my_kabupaten_id;
        $galat = NULL;
        $tersimpan = $this->store_private_upload('berkas', self::DOMAIN, $kab, $galat, 5242880, [
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
        ]);
        if ($tersimpan === FALSE) {
            $this->session->set_flashdata('error', $galat ?: 'Berkas gagal diunggah.');
            redirect($kembali);
        }
        $path = $this->private_upload_dir(self::DOMAIN, $kab) . $tersimpan;

        $this->load->library('rekam_pbg_bphtb_import');
        $desa = $this->desa_wilayah();
        $baca = $this->jenis === 'pbg'
            ? $this->rekam_pbg_bphtb_import->baca_pbg($path, $bulan, array_fill_keys(array_column($desa, 'kode'), TRUE))
            : $this->rekam_pbg_bphtb_import->baca_bphtb($path, $tahun, $bulan, $this->nama_wilayah(),
                array_fill_keys(array_map(fn($d) => Rekam_pbg_bphtb_import::kunci_desa($d['kec'], $d['desa']), $desa), TRUE));
        if (empty($baca['success'])) {
            @unlink($path);
            $this->session->set_flashdata('error', $baca['message']);
            redirect($kembali);
        }

        $kunci = ['jenis' => $this->jenis, 'kabupaten_id' => $kab, 'tahun' => $tahun, 'bulan' => $bulan];
        $lama = $this->db->where($kunci)->get('rd_pbg_bphtb')->row_array();
        $isi = [
            'nama_asli'    => mb_substr((string) $_FILES['berkas']['name'], 0, 255),
            'path_privat'  => $tersimpan,
            'mime_type'    => (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream',
            'ukuran'       => filesize($path),
            'jumlah_baris' => $baca['jumlah_baris'],
            'ringkasan'    => $baca['ringkasan'],
            'uploaded_at'  => date('Y-m-d H:i:s'),
            'uploaded_by'  => $this->get_user_id() ?: NULL,
        ];
        $ok = $lama ? $this->db->where('id', (int) $lama['id'])->update('rd_pbg_bphtb', $isi)
                    : $this->db->insert('rd_pbg_bphtb', $kunci + $isi);
        if ( ! $ok) {
            @unlink($path);
            $this->session->set_flashdata('error', 'Berkas terbaca, tetapi gagal dicatat. Coba lagi.');
            redirect($kembali);
        }
        if ($lama) { @unlink($this->private_upload_dir(self::DOMAIN, $kab) . basename($lama['path_privat'])); }

        $periode = $this->nama_bulan($bulan) . ' ' . $tahun;
        $this->catat_audit('rekam_' . $this->jenis . '_diunggah', $this->label . ' ' . $periode . ' diunggah ('
            . $this->nama_wilayah() . ', ' . $baca['ringkasan'] . ')', 'rd_pbg_bphtb');
        $this->session->set_flashdata('success', $this->label . ' ' . $periode . ' tersimpan: ' . $baca['ringkasan'] . '.');
        redirect($kembali);
    }

    /** Ringkasan angka satu bulan, boleh tanpa berkas Excel. Simpan lagi menggantikan. */
    public function simpan_angka()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $tahun = $this->tahun_dari($this->input->post('tahun'));
        $bulan = $this->bulan_dari($this->input->post('bulan'));
        $kembali = get_class($this) . '?tahun=' . $tahun . '&bulan=' . $bulan;
        $jumlah = trim((string) $this->input->post('jumlah'));
        $nilai = str_replace('.', '', trim((string) $this->input->post('nilai_rp')));
        if ( ! ctype_digit($jumlah) || ! ctype_digit($nilai) || strlen($jumlah) > 9 || strlen($nilai) > 15) {
            $this->session->set_flashdata('error', 'Jumlah dan nilai harus angka bulat, tanpa minus.');
            redirect($kembali);
        }
        $kunci = ['jenis' => $this->jenis, 'kabupaten_id' => (int) $this->my_kabupaten_id, 'tahun' => $tahun, 'bulan' => $bulan];
        $isi = ['jumlah' => (int) $jumlah, 'nilai_rp' => $nilai,
            'catatan' => mb_substr(trim((string) $this->input->post('catatan')), 0, 500) ?: NULL,
            'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $this->get_user_id() ?: NULL];
        $lama = $this->db->where($kunci)->get('rd_pbg_bphtb_angka')->row_array();
        $ok = $lama ? $this->db->where('id', (int) $lama['id'])->update('rd_pbg_bphtb_angka', $isi)
                    : $this->db->insert('rd_pbg_bphtb_angka', $kunci + $isi);
        $periode = $this->nama_bulan($bulan) . ' ' . $tahun;
        if ( ! $ok) {
            $this->session->set_flashdata('error', 'Ringkasan gagal disimpan. Coba lagi.');
            redirect($kembali);
        }
        $this->catat_audit('rekam_' . $this->jenis . '_angka', 'Ringkasan ' . $this->label . ' ' . $periode . ' disimpan ('
            . $this->nama_wilayah() . ', ' . $jumlah . ', Rp ' . number_format((float) $nilai, 0, ',', '.') . ')', 'rd_pbg_bphtb_angka');
        $this->session->set_flashdata('success', 'Ringkasan ' . $this->label . ' ' . $periode . ' tersimpan.');
        redirect($kembali);
    }

    public function unduh($id = 0)
    {
        $row = $this->db->where(['id' => (int) $id, 'jenis' => $this->jenis, 'kabupaten_id' => (int) $this->my_kabupaten_id])
            ->get('rd_pbg_bphtb')->row_array();
        if ( ! $row) {
            show_404();
            return;
        }
        $this->serve_private_file(self::DOMAIN, (int) $row['kabupaten_id'], $row['path_privat'], $row['mime_type']);
    }

    /**
     * Templat XLSX asli, kolomnya persis format dinas. Kolom NIK berformat Teks; kode desa
     * PBG perorangan berumus VLOOKUP ke lembar KODE WILAYAH (desa kabupaten pengunduh saja).
     */
    public function templat()
    {
        $this->catat_audit('rekap_diunduh', 'Templat ' . $this->label . ' diunduh (' . $this->nama_wilayah() . ')', 'rd_pbg_bphtb');
        $isi = 1000;
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $teks = static function ($s, $kolom) use ($isi) {
            foreach ($kolom as $k) { $s->getStyle("{$k}2:$k" . ($isi + 1))->getNumberFormat()->setFormatCode('@'); }
        };
        $judul = static function ($s, array $kolom) {
            $s->fromArray($kolom);
            $s->getStyle('A1:' . $s->getHighestColumn() . '1')->getFont()->setBold(TRUE);
            $s->getStyle('A1:' . $s->getHighestColumn() . '1')->getAlignment()->setWrapText(TRUE);
            foreach (range(1, count($kolom)) as $c) { $s->getColumnDimensionByColumn($c)->setWidth(20); }
            $s->freezePane('A2');
        };

        if ($this->jenis === 'pbg') {
            $a = $ss->getActiveSheet()->setTitle('PENGEMBANG');
            $judul($a, ['No', 'Nama Perumahan', 'Nama Pengembang', 'Alamat Perumahan', 'Bentuk Rumah (tapak/susun)', 'Tipe (Lb/Lt)', 'Status Data (Bulan)']);
            $b = $ss->createSheet()->setTitle('PERORANGAN');
            $judul($b, ['No', 'NIK', 'NAMA', 'ALAMAT', 'KECAMATAN', 'KELURAHAN/DESA', 'KODE KELURAHAN/DESA (TERISI OTOMATIS)', 'Bulan']);
            $teks($b, ['B']);
            for ($i = 2; $i <= $isi + 1; $i++) {
                $b->setCellValue("G$i", "=IF(F$i=\"\",\"\",IFERROR(VLOOKUP(UPPER(TRIM(E$i))&\"_\"&UPPER(TRIM(F$i)),'KODE WILAYAH'!\$C:\$D,2,FALSE),\"\"))");
            }
        } else {
            $a = $ss->getActiveSheet()->setTitle('BPHTB');
            $judul($a, ['Nomor', 'Nama Kab/Kota', 'Tahun', 'Nama Kecamatan', 'Nama Kelurahan', 'Nomor Akta', 'Tanggal Akta',
                'Nama Notaris', 'Bentuk Perbuatan Hukum', 'Nama Pihak Pengalih Pemberi', 'Direktur/ General Manager',
                'NIK Pihak Pengalih Pemberi', 'Alamat Pihak Pengalih Pemberi', 'Pihak Penerima', 'NIK Penerima', 'Alamat Penerima',
                'Status (Kawin/ Belum Kawin)', 'Penghasilan', 'Jenis Hak', 'Nomor Hak', 'Alamat Tanah Bangunan', 'luas_tanah_',
                'luas_bangunan_', 'harga_perolehan_pengalihan_hak', 'nop_sppt_pbb', 'tahun_sppt', 'nilai_njop', 'nomor sspd',
                'nilai_sspd', 'tanggal_sspd_bphtb', 'nilai_sspd_bphtb', 'bulan_bphtb', 'keterangan', 'Apakah MBR atau bukan']);
            $teks($a, ['L', 'O', 'Y']);
        }

        $kw = $ss->createSheet()->setTitle('KODE WILAYAH');
        $kw->fromArray(['kecamatan', 'deskel', 'concatenate', 'kode_dagri']);
        $r = 2;
        foreach ($this->desa_wilayah() as $d) {
            $kw->fromArray([$d['kec'], $d['desa'], Rekam_pbg_bphtb_import::kunci_desa($d['kec'], $d['desa'])], NULL, "A$r");
            $kw->setCellValueExplicit("D$r", $d['kode'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        foreach (['A' => 22, 'B' => 24, 'C' => 44, 'D' => 14] as $k => $w) { $kw->getColumnDimension($k)->setWidth($w); }
        $ss->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->setPreCalculateFormulas(FALSE);
        $aman = preg_replace('/[^A-Za-z0-9 _.-]/', '', 'Templat ' . $this->label . ' - ' . $this->nama_wilayah());
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $aman . '.xlsx"');
        header('Cache-Control: private, no-store');
        $writer->save('php://output');
        exit;
    }

    private function tahun_dari($t)
    {
        $t = (int) $t;
        return ($t >= 2020 && $t <= 2100) ? $t : (int) date('Y');
    }

    private function bulan_dari($b)
    {
        $b = (int) $b;
        return ($b >= 1 && $b <= 12) ? $b : (int) date('n');
    }

    private function nama_bulan($b)
    {
        $this->load->library('rekam_pbg_bphtb_import');
        return ucfirst(Rekam_pbg_bphtb_import::BULAN[$b]);
    }

    /** Desa kabupaten pelapor, huruf besar seperti lembar KODE WILAYAH. [['kec','desa','kode'], ...] */
    private function desa_wilayah()
    {
        $this->load->library('rekam_pbg_bphtb_import');
        $out = [];
        foreach ($this->db->select('d.id AS kode, d.nama AS desa, k.nama AS kec')
            ->from('desa d')->join('kecamatan k', 'k.id = d.kecamatan_id')
            ->where('k.kabupaten_id', (int) $this->my_kabupaten_id)
            ->order_by('k.nama')->order_by('d.nama')->get()->result_array() as $d) {
            $out[] = ['kec' => strtoupper($d['kec']), 'desa' => strtoupper($d['desa']), 'kode' => (string) $d['kode']];
        }
        return $out;
    }

    private function nama_wilayah()
    {
        return $this->db->where('id', $this->my_kabupaten_id)->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
    }
}
