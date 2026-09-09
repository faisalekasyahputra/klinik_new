<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Visibilitas superadmin atas SELURUH aduan lintas bidang - menutup
 * AUDIT_ROLE_ADMIN_SCOPED.md temuan #5: sebelumnya tabel `aduan` hanya
 * disentuh Umum (kirim), Admin_Bidang (kelola, ter-scope), dan Pengaturan
 * (baca milik sendiri). Kalau sebuah bidang belum punya admin_bidang
 * ter-assign, aduannya tidak terlihat SIAPA PUN di sisi admin.
 *
 * SATU-SATUNYA endpoint tulis di sini adalah triase() - MERUTEKAN aduan ke
 * bidang, bukan MEMUTUSKAN nasibnya. Bedanya penting dan sengaja dijaga:
 * `status` dan `catatan_admin` tetap hanya bisa disentuh Admin_Bidang, jadi
 * tidak ada jalur kedua yang bisa menimpa keputusan admin bidang tanpa jejak
 * (bandingkan masalah dua-jalur-tulis di sf_housing_queue, temuan #3).
 *
 * Triase lahir 3 Agt 2026 karena pelapor tidak lagi memilih bidang sendiri -
 * warga tidak tahu urusannya masuk Bidang Perumahan atau Bidang Kawasan
 * Permukiman. Aduan baru lahir dengan `bidang` NULL dan, karena NULL tidak
 * cocok dengan WHERE mana pun, tidak muncul di meja bidang mana pun sampai
 * dirutekan dari sini. Itu berarti layar ini sekarang jadi HAMBATAN: aduan yang
 * tidak ditriase tidak ditangani siapa pun - karena itu ada callout jumlahnya
 * di atas tabel.
 */
class Admin_Aduan extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Semua Aduan';

        $bidang_filter = $this->input->get('bidang', TRUE);
        $status_sah = ['Baru', 'Diproses', 'Selesai'];
        $status_filter = $this->input->get('status', TRUE);
        $status_filter = in_array($status_filter, $status_sah, TRUE) ? $status_filter : NULL;
        $daftar_bidang = $this->db->order_by('nama', 'ASC')->get('bidang')->result();
        $kode_valid = array_column($daftar_bidang, 'kode');
        // Filter dari query string divalidasi ke tabel bidang - bukan langsung
        // dimasukkan ke WHERE. Nilai semu 'belum' dipakai untuk antrean triase
        // (bidang IS NULL); ia bukan kode bidang dan tidak boleh masuk WHERE
        // sebagai nilai.
        $triase_saja = ($bidang_filter === 'belum');
        $bidang_filter = $triase_saja ? 'belum'
            : (in_array($bidang_filter, $kode_valid, TRUE) ? $bidang_filter : NULL);

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state(
            ['aduan.created_at', 'aduan.nama', 'aduan.judul', 'aduan.bidang', 'aduan.status'],
            'aduan.created_at'
        );
        $data['base_url'] = 'Admin_Aduan';

        $this->db->from('aduan')->join('usr_users', 'usr_users.id = aduan.reviewed_by', 'left');
        if ($triase_saja) { $this->db->where('aduan.bidang IS NULL', NULL, FALSE); }
        elseif ($bidang_filter) { $this->db->where('aduan.bidang', $bidang_filter); }
        if ($status_filter) { $this->db->where('aduan.status', $status_filter); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('aduan.nama', $table['q'])->or_like('aduan.email', $table['q'])
                ->or_like('aduan.judul', $table['q'])->or_like('aduan.pesan', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->select('aduan.*, usr_users.name AS nama_petugas')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        // Bidang tanpa admin ter-assign: aduannya tidak akan tertangani siapa pun.
        $data['bidang_tanpa_admin'] = [];
        foreach ($daftar_bidang as $b) {
            $ada_admin = $this->db->where(['role' => 'admin_bidang', 'bidang_kode' => $b->kode])
                ->count_all_results('usr_users');
            if ($ada_admin === 0) { $data['bidang_tanpa_admin'][] = $b->nama; }
        }

        // Antrean triase: aduan yang belum dirutekan tidak muncul di meja bidang
        // mana pun. Angkanya ditunjukkan walau filternya sedang tidak aktif -
        // hambatan yang cuma terlihat kalau sedang dicari bukan hambatan yang
        // terlihat.
        $data['jml_triase'] = (int) $this->db->where('bidang IS NULL', NULL, FALSE)
            ->count_all_results('aduan');

        $data['daftar_bidang'] = $daftar_bidang;
        $data['bidang_filter'] = $bidang_filter;
        $data['status_sah'] = $status_sah;
        $data['status_filter'] = $status_filter;
        $this->render_admin('admin/aduan/index', $data);
    }

    /**
     * Rutekan satu aduan ke bidang penanganan.
     *
     * TIGA pembatas, masing-masing menutup hal berbeda:
     *
     * 1. POST saja. GET yang menulis bisa dipicu lewat <img src> di halaman mana
     *    pun; rute aduan bukan sesuatu yang boleh berubah karena seseorang
     *    membuka tautan.
     * 2. Bidang dicocokkan ke TABEL `bidang`, bukan ke daftar yang ditulis ulang
     *    di sini. Kode ngawur berarti aduan hilang dari semua meja sekaligus -
     *    tidak cocok dengan WHERE admin bidang mana pun, dan tidak lagi NULL
     *    sehingga hilang juga dari antrean triase. Kegagalan senyap sempurna.
     * 3. WHERE `status='Baru'`. Setelah admin bidang mulai memproses, memindah
     *    rutenya berarti menarik berkas dari tangan orang yang sedang
     *    mengerjakannya - riwayat keputusannya tetap di baris itu tapi bidangnya
     *    sudah bukan bidang yang memutuskan. Selama masih 'Baru', salah rute
     *    justru MASIH BOLEH diperbaiki: itu tujuannya.
     *
     * Keberadaan barisnya dicek terpisah, bukan lewat affected_rows(): MySQL
     * membalas 0 juga ketika nilai barunya sama persis dengan yang tersimpan
     * (meneruskan ulang ke bidang yang sama), dan dulu itu salah dilaporkan
     * sebagai "tidak ditemukan" di endpoint sebelah - AUDIT_ROLE_ADMIN_SCOPED #6.
     */
    /**
     * Detail satu aduan - butir 16 putaran 2.
     *
     * Sebelum ini meja aduan hanya punya daftar, penerusan ke bidang, dan lihat
     * lampiran. Isi aduannya sendiri terpotong di daftar, sehingga admin
     * menriase berdasarkan judul saja - menebak bidang tujuan dari satu baris
     * kalimat, lalu salah teruskan, lalu warga menunggu di meja yang keliru.
     *
     * READ-ONLY. Keputusan status dan penerusan tetap lewat jalurnya sendiri
     * (`triase()`), supaya tidak ada dua pintu tulis untuk satu hal.
     */
    public function detail($id = NULL)
    {
        $id = (int) $id;
        $row = $this->db->select('a.*, b.nama AS nama_bidang, u.name AS nama_peninjau')
            ->from('aduan a')
            ->join('bidang b', 'b.kode = a.bidang', 'left')
            ->join('usr_users u', 'u.id = a.reviewed_by', 'left')
            ->where('a.id', $id)
            ->get()->row();

        if ( ! $row) { show_404(); return; }

        $this->render_admin('admin/aduan/detail', [
            'title'    => 'Detail Aduan',
            'aduan'    => $row,
            'back_url' => 'Admin_Aduan',
        ]);
    }

    public function triase($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $id = (int) $id;

        $bidang = (string) $this->input->post('bidang', TRUE);
        $sah = array_column($this->db->get('bidang')->result(), 'nama', 'kode');
        if ( ! isset($sah[$bidang])) {
            $this->session->set_flashdata('error', 'Bidang tujuan tidak dikenal.');
            redirect('Admin_Aduan');
            return;
        }

        $row = $this->db->select('judul, bidang, status')->get_where('aduan', ['id' => $id])->row();
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Aduan tidak ditemukan.');
            redirect('Admin_Aduan');
            return;
        }
        if ($row->status !== 'Baru') {
            $this->catat_audit('aduan_triase_ditolak',
                'Triase ditolak: aduan #' . $id . ' sudah berstatus ' . $row->status,
                'aduan', $id, ['status' => $row->status, 'bidang_diminta' => $bidang]);
            $this->session->set_flashdata('error',
                'Aduan ini sudah ditangani (' . $row->status . ') - rutenya tidak bisa diubah lagi.');
            redirect('Admin_Aduan');
            return;
        }

        $this->db->where('id', $id)->where('status', 'Baru')
            ->update('aduan', ['bidang' => $bidang]);

        $asal = $row->bidang ? ($sah[$row->bidang] ?? $row->bidang) : 'antrean triase';
        $this->catat_audit('aduan_ditriase',
            'Aduan #' . $id . ' diteruskan dari ' . $asal . ' ke ' . $sah[$bidang],
            'aduan', $id, ['dari' => $row->bidang, 'ke' => $bidang, 'judul' => $row->judul]);

        $this->notify_admin_push([
            ['role' => 'admin_bidang', 'bidang_kode' => $bidang],
        ], 'Aduan baru untuk bidang Anda',
            'Ada aduan hasil triase yang menunggu tindak lanjut.',
            'Admin_Bidang?status=Baru', 'aduan-bidang-' . $id);
        $this->session->set_flashdata('success', 'Aduan diteruskan ke ' . $sah[$bidang] . '.');
        redirect('Admin_Aduan');
    }

    /**
     * Sajikan lampiran aduan ke superadmin (lintas bidang, sesuai kewenangan).
     * Ber-guard lewat Admin_Controller; berkas dibaca dari private_uploads/,
     * bukan URL publik. Bandingkan Admin_Bidang::lihat_lampiran() yang
     * WHERE-nya ganda karena memang ter-scope.
     */
    public function lihat_lampiran($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('lampiran')->get_where('aduan', ['id' => (int) $id])->row();
        if ( ! $row || empty($row->lampiran)) { show_404(); }

        $ext = strtolower(pathinfo($row->lampiran, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('aduan', (int) $id, $row->lampiran, $mime);
    }
}
