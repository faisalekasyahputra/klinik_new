<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master daftar asosiasi pengembang - layar admin.
 *
 * Permintaan user 14 Agt 2026. Sebelumnya kelima asosiasi ditulis mati di
 * `srp2_daftar_asosiasi()`, jadi menambah satu asosiasi baru berarti mengubah
 * kode - padahal ini murni data milik dinas. Sekarang tabel `srp2_asosiasi`
 * (migrasi 042) yang jadi daftar sahnya, dan layar ini yang mengelolanya.
 *
 * Pola CRUD-nya mengikuti Admin_Magang_Posisi (master data sejenis): satu
 * layar, form tambah di atas, sunting inline per baris, hapus lewat POST,
 * semua perubahan masuk jejak audit.
 *
 * ⚠️ `kode` TIDAK BISA DIUBAH setelah dibuat, dan itu disengaja. Dua tabel
 * lain (`srp2_certified_developers.asosiasi`, `srp2_registrations.asosiasi`)
 * menyimpan STRING kode ini, bukan id - tidak ada FK yang bisa meng-cascade.
 * Mengizinkan kode disunting berarti setiap baris yang memakainya berubah jadi
 * yatim DIAM-DIAM: kolom di direktori publik mendadak menampilkan kode mentah,
 * dan tidak ada satu pun galat yang muncul. Yang dibaca orang adalah `nama`,
 * dan itu bebas diubah kapan saja.
 */
class Admin_Asosiasi extends Admin_Controller {

    const TABEL = 'srp2_asosiasi';

    public function index()
    {
        $data['title'] = 'Asosiasi Pengembang';
        $data['rows']  = $this->db->from(self::TABEL)
            ->order_by('urutan', 'ASC')->order_by('nama', 'ASC')
            ->get()->result();

        /* Berapa pengembang memakai tiap asosiasi - dipakai layarnya untuk
           memberi tahu SEBELUM admin menekan Hapus, bukan cuma menolak
           sesudahnya. Dihitung sekali di sini (dua query GROUP BY), bukan
           satu query per baris di dalam view. */
        $pakai = [];
        foreach ([
            'srp2_certified_developers' => 'direktori',
            'srp2_registrations'        => 'pengajuan',
        ] as $tabel => $sebutan) {
            foreach ($this->db->select('asosiasi, COUNT(*) AS jml')->from($tabel)
                ->where('asosiasi IS NOT NULL')->group_by('asosiasi')->get()->result() as $r) {
                $pakai[$r->asosiasi][$sebutan] = (int) $r->jml;
            }
        }
        $data['pemakaian'] = $pakai;

        $this->render_admin('admin/asosiasi/index', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }

        $id     = (int) $this->input->post('id');
        $nama   = trim((string) $this->input->post('nama', TRUE));
        $urutan = max(0, min(999, (int) $this->input->post('urutan')));
        $aktif  = $this->input->post('aktif') ? 1 : 0;

        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->session->set_flashdata('error', 'Nama asosiasi wajib diisi, maksimal 100 karakter.');
            redirect('Admin_Asosiasi'); return;
        }

        $payload = [
            'nama'       => $nama,
            'aktif'      => $aktif,
            'urutan'     => $urutan,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $lama = $this->db->get_where(self::TABEL, ['id' => $id])->row();
            if ( ! $lama) {
                $this->session->set_flashdata('error', 'Asosiasi tidak ditemukan.');
                redirect('Admin_Asosiasi'); return;
            }
            // `kode` SENGAJA tidak diambil dari input di cabang ini - lihat
            // peringatan di kepala class. Kalaupun seseorang menyisipkannya
            // lewat POST, tidak ada yang membacanya.
            $this->db->where('id', $id)->update(self::TABEL, $payload);
            $this->catat_audit('asosiasi_diubah', 'Asosiasi "' . $lama->kode . '" diperbarui',
                self::TABEL, $id, ['nama' => $nama, 'aktif' => $aktif]);
            $this->session->set_flashdata('success', 'Asosiasi diperbarui.');
            redirect('Admin_Asosiasi'); return;
        }

        /* Kode hanya dibentuk SEKALI, saat baris dibuat. Dinormalkan
           (huruf kecil, spasi jadi garis bawah) supaya bentuk simpannya
           konsisten dengan lima kode yang sudah ada - `REI Jateng` yang
           diketik admin jadi `rei_jateng`, bukan dua bentuk berbeda untuk
           hal yang sama. */
        $kode = strtolower(trim((string) $this->input->post('kode', TRUE)));
        $kode = preg_replace('/[^a-z0-9]+/', '_', $kode);
        $kode = trim((string) $kode, '_');

        if ($kode === '' || mb_strlen($kode) > 30) {
            $this->session->set_flashdata('error', 'Kode wajib diisi (huruf/angka, maksimal 30 karakter).');
            redirect('Admin_Asosiasi'); return;
        }
        if ($this->db->where('kode', $kode)->count_all_results(self::TABEL) > 0) {
            $this->session->set_flashdata('error', 'Kode "' . $kode . '" sudah dipakai asosiasi lain.');
            redirect('Admin_Asosiasi'); return;
        }

        $payload['kode']       = $kode;
        $payload['created_at'] = $payload['updated_at'];
        $this->db->insert(self::TABEL, $payload);
        $this->catat_audit('asosiasi_ditambah', 'Asosiasi "' . $kode . '" ditambahkan',
            self::TABEL, $this->db->insert_id(), ['nama' => $nama]);
        $this->session->set_flashdata('success', 'Asosiasi "' . $nama . '" ditambahkan.');
        redirect('Admin_Asosiasi');
    }

    public function hapus()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }

        $id  = (int) $this->input->post('id');
        $row = $this->db->get_where(self::TABEL, ['id' => $id])->row();
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Asosiasi tidak ditemukan.');
            redirect('Admin_Asosiasi'); return;
        }

        /* DITOLAK kalau masih dipakai. Tidak ada FK yang menjaga ini (kolom
           pemakainya string, bukan id - lihat kepala class), jadi penjagaannya
           HARUS di sini: menghapus yang masih terpakai membuat baris-baris itu
           menampilkan kode mentah di halaman publik tanpa satu pun galat.
           Sarannya menonaktifkan, karena itu memang yang dimaksud admin hampir
           setiap kali - berhenti menawarkan tanpa merusak data lama. */
        $terpakai = 0;
        foreach (['srp2_certified_developers', 'srp2_registrations'] as $tabel) {
            $terpakai += (int) $this->db->where('asosiasi', $row->kode)->count_all_results($tabel);
        }
        if ($terpakai > 0) {
            $this->session->set_flashdata('error',
                'Asosiasi "' . $row->nama . '" masih dipakai ' . $terpakai . ' data dan tidak bisa dihapus. '
                . 'Hilangkan centang "Aktif" untuk berhenti menawarkannya tanpa mengubah data lama.');
            redirect('Admin_Asosiasi'); return;
        }

        $this->db->where('id', $id)->delete(self::TABEL);
        $this->catat_audit('asosiasi_dihapus', 'Asosiasi "' . $row->kode . '" dihapus',
            self::TABEL, $id, ['nama' => $row->nama]);
        $this->session->set_flashdata('success', 'Asosiasi "' . $row->nama . '" dihapus.');
        redirect('Admin_Asosiasi');
    }
}
