<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Srp2 extends Admin_Controller {

    /** Butir 7 putaran 2. Harus sama persis dengan ENUM migrasi 040. */
    const STATUS_SERTIFIKASI = ['belum_mendaftar', 'mendaftar', 'masih_proses', 'bersertifikat'];

    /**
     * Keadaan masa berlaku - DITURUNKAN, tidak pernah disimpan.
     *
     * Dinas minta penanda aktif/non-aktif dari masa berlaku sertifikat.
     * Menyimpannya berarti ada yang harus memperbaruinya tiap hari, dan yang
     * tidak diperbarui akan berkata "aktif" untuk sertifikat yang kedaluwarsa
     * kemarin - tanpa satu pun galat. Diturunkan berarti selalu benar.
     */
    public static function keadaan_berlaku($status, $berakhir) {
        if ($status !== 'bersertifikat')        { return ['belum', 'Belum bersertifikat']; }
        if (empty($berakhir))                   { return ['tak_tercatat', 'Masa berlaku belum tercatat']; }
        return $berakhir >= date('Y-m-d')
            ? ['aktif', 'Aktif']
            : ['kedaluwarsa', 'Non-aktif - masa berlaku habis'];
    }

    public function __construct() {
        parent::__construct();
        // upsert_direktori_publik() dipakai proses() - satu fungsi yang sama
        // dengan yang dipanggil saat pemohon mengubah data perusahaannya.
        $this->load->model('auth_model');
    }

    public function index() {
        $data['title'] = 'Direktori Pengembang Bersertifikat';

        // Pola tabel server-side B8, sama dengan pending() di berkas ini -
        // sebelumnya satu-satunya tabel admin SRP2 yang masih mengirim SELURUH
        // baris ke browser sekaligus (67 dan terus bertambah tiap approve).
        $table = $this->table_state(['nama_perusahaan', 'created_at', 'status_aktif'], 'nama_perusahaan');
        $data['base_url'] = 'Admin_Srp2';

        // from() di depan, lalu count_all_results('', FALSE) - kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->from('srp2_certified_developers');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama_perusahaan', $table['q'])->or_like('alamat_kantor', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;
        $data['kabupaten'] = $this->db->select('id, nama')->order_by('nama', 'ASC')
            ->get('kabupaten')->result();

        /* NPWP dibuka HANYA di layar admin ini, dan hanya untuk baris yang
           sedang ditampilkan - bukan seluruh direktori. Nilainya diisikan ke
           formulir supaya "Simpan" tidak menghapusnya diam-diam: isian yang
           selalu terkirim tetapi dibiarkan kosong akan menge-NULL-kan kolomnya,
           dan itu persis bug `sosmed_lainnya` yang baru kami perbaiki 10 Agt.

           Gagal buka TIDAK disembunyikan jadi string kosong. Kosong berarti
           "belum diisi", dan kalau kunci enkripsinya berganti sementara layar
           berkata kosong, admin akan mengetik ulang NPWP ke atas data yang
           sebenarnya masih ada. */
        $this->load->library('encryption_lib');
        foreach ($data['rows'] as $r) {
            $r->npwp_plain = NULL;
            $r->npwp_rusak = FALSE;
            if (empty($r->npwp_ciphertext)) { continue; }
            $buka = $this->encryption_lib->decrypt($r->npwp_ciphertext);
            if ($buka === FALSE || $buka === NULL || $buka === '') { $r->npwp_rusak = TRUE; continue; }
            $r->npwp_plain = $buka;
        }

        $this->render_admin('admin/srp2/index', $data);
    }

    /**
     * Halaman TERSENDIRI untuk menambah pengembang baru - dulu form inline
     * di puncak index() (permintaan user 14 Agt 2026: form sepanjang itu
     * selalu mendorong tabel ke bawah, padahal "tambah manual" jarang
     * dipakai dibanding "cari/edit yang sudah ada" - direktori ini isinya
     * 66 dari 67 baris data historis, cuma 1 hasil alur pengajuan online).
     *
     * Field & nama input DIPINDAH APA ADANYA, bukan ditulis ulang -
     * save() (dituju form ini) tidak berubah sama sekali: ia sudah
     * menerima id=0/kosong sebagai INSERT sejak awal, jadi satu-satunya
     * yang pindah adalah tempat form-nya dirender.
     */
    public function tambah() {
        $data['title'] = 'Tambah Pengembang';
        $data['kabupaten'] = $this->db->select('id, nama')->order_by('nama', 'ASC')
            ->get('kabupaten')->result();
        $this->render_admin('admin/srp2/tambah', $data);
    }

    /**
     * Daftar pengajuan SRP2 yang menunggu keputusan - menutup gap dari
     * docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md Fase 1 (sebelumnya tidak
     * ada alur admin sama sekali untuk srp2_registrations, lihat
     * docs/engineering/AUDIT_ROLE_PENGEMBANG.md Temuan #1).
     */
    public function pending() {
        $data['title'] = 'Verifikasi SRP2';

        // Cari + urut + paginasi semuanya server-side (B8).
        $table = $this->table_state(['updated_at', 'nama_perusahaan', 'email'], 'updated_at');
        $data['base_url'] = 'Admin_Srp2/pending';

        // Status = FILTER dengan nilai default, BUKAN klausa WHERE mati. Dulu
        // where('status_verifikasi','Pending') dipaku di sini, sehingga setiap
        // pengajuan yang sudah diputuskan lenyap dari jangkauan admin: tidak ada
        // cara memantau siapa yang diminta perbaikan tapi tak kunjung mengirim
        // ulang, dan satu-satunya jalan membukanya lagi adalah menebak URL
        // detail/<id>. Roadmap T1b butir 1.
        //
        // Nilai defaultnya dibaca dari registry (pending_where) supaya "apa arti
        // belum diproses" tetap satu deklarasi - dipakai badge sidebar sekaligus
        // query ini, tidak lagi ditulis ulang literalnya di dua tempat.
        $modul = $this->config->item('dashboard_modules')['srp2_verifikasi'] ?? [];
        $status_default = $modul['pending_where']['status_verifikasi'] ?? 'Pending';

        $status_pilihan = ['Pending', 'Draft', 'Diterima', 'Ditolak'];
        $status_filter  = (string) $this->input->get('status');
        if ( ! in_array($status_filter, $status_pilihan, TRUE) && $status_filter !== 'semua') {
            $status_filter = $status_default;
        }
        $data['status_filter']  = $status_filter;
        $data['status_pilihan'] = $status_pilihan;

        // from() di depan, lalu count_all_results('', FALSE) - kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->from('srp2_registrations');
        if ($status_filter !== 'semua') { $this->db->where('status_verifikasi', $status_filter); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama_perusahaan', $table['q'])->or_like('email', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_admin('admin/srp2/pending', $data);
    }

    /**
     * Detail satu pengajuan + status unggah 14 dokumen. Dokumen dibuka lewat
     * lihat_dokumen() (endpoint ber-guard), tidak pernah lewat path publik.
     */
    public function detail($id = NULL) {
        if ( ! is_numeric($id)) { show_404(); }
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id])->row();
        if ( ! $data['pendaftar']) { show_404(); }

        $this->load->helper('srp2');
        $data['dokumen_list'] = srp2_dokumen_persyaratan();

        $data['uploaded'] = [];
        foreach ($this->db->where('registration_id', (int) $id)->get('srp2_documents')->result() as $doc) {
            $data['uploaded'][$doc->document_key] = $doc;
        }

        $data['title'] = 'Detail Pengajuan SRP2';
        $this->render_admin('admin/srp2/detail', $data);
    }

    /**
     * Terima/tolak satu pengajuan - satu endpoint dengan field 'status',
     * pola yang sama persis dengan Admin_Kemitraan::proses() (bukan dua
     * method terima()/tolak() terpisah seperti draft awal PRD) supaya
     * komponen admin/components/review_form.php benar-benar dipakai ulang
     * tanpa modifikasi bentuk endpoint.
     *
     * Terima otomatis meng-upsert srp2_certified_developers berbasis
     * certified_developer_id (bukan insert baru tiap kali) - idempotent
     * terhadap approve berulang (PRD FR-10). Tolak wajib catatan_admin,
     * divalidasi SERVER (PRD FR-09), bukan cuma atribut required di HTML.
     */
    public function proses($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        // 'Draft' = aksi "Minta Perbaikan": membuka kembali pengajuan supaya
        // pemohon bisa memperbaiki dokumennya, TANPA mencap "Ditolak" di
        // riwayatnya. Memakai status yang sudah ada (Draft = bisa diedit, belum
        // dikirim) sehingga tidak perlu status/migrasi baru.
        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak', 'Draft'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $reg = $this->db->get_where('srp2_registrations', ['id' => (int) $id])->row();
        if ( ! $reg) { show_404(); }

        // Transisi status ditegakkan di SERVER, bukan di view. Sebelumnya proses()
        // membaca $reg lalu tidak pernah menguji status lamanya: satu POST rakitan
        // bisa menerbitkan Draft berisi 0 dokumen ke direktori publik lengkap
        // dengan reviewed_by. Satu-satunya penjaga adalah kondisi if di detail.php
        // - itu UI, bukan otorisasi. Roadmap T1a butir 6.
        $transisi_sah = [
            'Pending'  => ['Diterima', 'Ditolak', 'Draft'],
            'Diterima' => ['Draft', 'Ditolak'],
            'Ditolak'  => ['Draft'],
            'Draft'    => [],
        ];
        $asal = (string) $reg->status_verifikasi;
        if ( ! in_array($status, $transisi_sah[$asal] ?? [], TRUE)) {
            $this->session->set_flashdata('error', 'Pengajuan berstatus "' . $asal . '" tidak bisa diubah menjadi "' . $status . '".');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        // Catatan wajib untuk kedua keputusan yang mengembalikan pekerjaan ke
        // pemohon - tanpa alasan, dia tidak tahu apa yang harus diperbaiki.
        $catatan = trim((string) $this->input->post('catatan_admin', TRUE));
        if (in_array($status, ['Ditolak', 'Draft'], TRUE) && $catatan === '') {
            $pesan = $status === 'Ditolak'
                ? 'Catatan wajib diisi saat menolak pengajuan.'
                : 'Catatan wajib diisi - jelaskan apa yang harus diperbaiki pemohon.';
            $this->session->set_flashdata('error', $pesan);
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $update = [
            'status_verifikasi' => $status,
            'reviewed_by'       => $this->get_user_id(),
            'reviewed_at'       => date('Y-m-d H:i:s'),
        ];
        // catatan_admin hanya ditulis untuk keputusan yang MEMBAWA catatan.
        // Saat Diterima, kolomnya sengaja TIDAK disentuh: dulu di-NULL-kan,
        // sehingga alasan "dulu diminta perbaikan karena X" hilang permanen -
        // dan tidak ada tabel log lain yang menyimpannya. Roadmap T1b butir 4.
        if ($status !== 'Diterima') { $update['catatan_admin'] = $catatan; }

        // SATU TRANSAKSI untuk seluruh keputusan. Sebelumnya tiga penulisan
        // berjalan lepas tanpa satu pun nilai balik diperiksa, sementara flash
        // sukses tetap disetel: nama bentrok UNIQUE di direktori membuat insert
        // gagal, insert_id() jadi 0, UPDATE registrasi ditolak FK, status tetap
        // Pending - dan admin membaca "Pengajuan diterima". Di production
        // db_debug mati sehingga seluruh rantai itu senyap. Melanggar §0d.
        // Pola trans_start/trans_complete/trans_status mengikuti User_model.php:36.
        $this->db->trans_start();

        if ($status === 'Diterima') {
            // Direktori publik srp2_certified_developers tetap tabel terpisah
            // (opsi b, docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) -
            // link berbasis ID, bukan pencocokan nama string seperti sebelumnya.
            //
            // Lewat SATU fungsi upsert yang sama dengan yang dipakai saat pemohon
            // mengubah data perusahaannya - bukan dua salinan payload yang harus
            // diingat untuk diubah berbarengan.
            $cid = $this->auth_model->upsert_direktori_publik($reg);
            if ($cid) { $update['certified_developer_id'] = $cid; }
        } elseif ($status === 'Ditolak' && $reg->certified_developer_id) {
            // Ditolak setelah pernah Diterima: cabut dari direktori publik.
            // Dulu blok direktori hanya jalan untuk 'Diterima', sehingga
            // pengecualian yang SENGAJA dibuat untuk "Minta Perbaikan" ikut
            // menutupi cabang ini - perusahaan yang resmi ditolak tetap tampil
            // "Bersertifikat" di halaman publik. Dipisah eksplisit di kode,
            // bukan diandalkan pada komentar. Roadmap T1a butir 7.
            $this->db->where('id', $reg->certified_developer_id)
                ->update('srp2_certified_developers', ['status_aktif' => 0]);
        }
        // 'Draft' (Minta Perbaikan) sengaja TIDAK menyentuh direktori: pengembangnya
        // tetap tersertifikasi, yang diperbaiki cuma kelengkapan dokumen. Pencabutan
        // dari daftar publik tetap keputusan terpisah lewat halaman Direktori SRP2.

        // Status asal ikut di WHERE: dengan begitu affected_rows() === 0 TIDAK
        // ambigu lagi (asal selalu != tujuan karena transisi sudah divalidasi),
        // jadi 0 baris pasti berarti gagal atau kalah balapan dengan admin lain.
        $this->db->where('id', (int) $id)
            ->where('status_verifikasi', $asal)
            ->update('srp2_registrations', $update);
        $baris_terubah = $this->db->affected_rows();

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE || $baris_terubah === 0) {
            log_message('error', 'Admin_Srp2::proses gagal - id=' . (int) $id . ' ' . $asal . '->' . $status . ' baris=' . $baris_terubah);
            $this->session->set_flashdata('error', 'Keputusan GAGAL disimpan dan sudah dibatalkan seluruhnya - tidak ada perubahan yang tersimpan. '
                . ($status === 'Diterima'
                    ? 'Penyebab paling sering: nama perusahaan "' . $reg->nama_perusahaan . '" sudah dipakai baris lain di direktori bersertifikat.'
                    : 'Coba lagi; bila terus gagal, laporkan beserta ID pengajuan ' . (int) $id . '.'));
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $pesan_sukses = [
            'Diterima' => 'Pengajuan diterima - pengembang masuk direktori publik.',
            'Ditolak'  => 'Pengajuan ditolak, catatan sudah dikirim ke pengembang.'
                . ($reg->certified_developer_id ? ' Pengembang juga dicabut dari direktori publik.' : ''),
            'Draft'    => 'Pengajuan dibuka kembali untuk diperbaiki. Pengembang melihat catatan Anda di dashboardnya.',
        ];
        $this->session->set_flashdata('success', $pesan_sukses[$status]);
        redirect('Admin_Srp2/pending');
    }

    /**
     * Sajikan satu dokumen SRP2 ke admin. Endpoint ber-guard (Admin_Controller),
     * berkas dibaca dari penyimpanan privat - tidak pernah lewat path publik.
     * Menutup PRD FR-11.
     */
    public function lihat_dokumen($id = NULL, $document_key = NULL) {
        if ( ! is_numeric($id) || empty($document_key)) { show_404(); }

        $doc = $this->db->where(['registration_id' => (int) $id, 'document_key' => $document_key])
            ->get('srp2_documents')->row();
        if ( ! $doc) { show_404(); }

        // Lewat serve_private_file() supaya lokasi akar & pengamanan nama file
        // (basename) ikut satu jalur dengan endpoint berkas lainnya.
        $this->serve_private_file('srp2', (int) $id, $doc->stored_name, $doc->mime_type);
    }

    public function save() {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $id = (int) $this->input->post('id');
        $name = strtoupper(trim((string) $this->input->post('nama_perusahaan', TRUE)));
        if ($name === '' || strlen($name) > 180) { $this->session->set_flashdata('error', 'Nama perusahaan wajib diisi.'); redirect('Admin_Srp2'); return; }
        /* HANYA medan yang BENAR-BENAR DIKIRIM yang masuk payload.
         *
         * Sampai 5 Agt 2026 ketiga URL selalu dirakit tanpa syarat, padahal
         * form BARIS di tabel (views/admin/srp2/index.php) tidak punya input
         * `sosmed_lainnya` - hanya form "Tambah pengembang" yang punya. Akibatnya
         * setiap "Simpan" pada sebuah baris menge-NULL-kan kolom itu diam-diam.
         * Bukan dugaan: nol dari 67 baris direktori punya nilai di sana.
         *
         * Dua kolom tanggal masa berlaku (migrasi 037) akan bernasib persis sama
         * tanpa perbaikan ini - dan tanggal sertifikat yang hilang sendiri jauh
         * lebih mahal daripada tautan media sosial.
         *
         * Bedanya jelas dan disengaja:
         *   - medan TIDAK dikirim  -> kolomnya tidak disentuh sama sekali,
         *   - medan dikirim KOSONG -> kolomnya dikosongkan (itu memang maunya).
         *
         * `status_aktif` SENGAJA di luar aturan ini: ia checkbox, dan checkbox
         * yang tidak dicentang memang tidak terkirim. Membacanya sebagai
         * "biarkan" membuat sakelar yang tidak pernah bisa dimatikan.
         */
        $payload = [
            'nama_perusahaan' => $name,
            'status_aktif'    => $this->input->post('status_aktif') ? 1 : 0,
        ];

        if ($this->input->post('alamat_kantor') !== NULL) {
            $payload['alamat_kantor'] = trim((string) $this->input->post('alamat_kantor', TRUE));
        }

        foreach (['website', 'instagram', 'sosmed_lainnya'] as $field) {
            if ($this->input->post($field) === NULL) { continue; }
            $value = trim((string) $this->input->post($field, TRUE));
            if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], TRUE))) { $this->session->set_flashdata('error', 'Link ' . $field . ' harus menggunakan URL http/https yang valid.'); redirect('Admin_Srp2'); return; }
            $payload[$field] = $value ?: null;
        }

        /* Masa berlaku (butir B1). Kosong -> NULL, bukan '' - MariaDB lokal
           berjalan TANPA STRICT mode, jadi '' pada kolom DATE mendarat sebagai
           '0000-00-00' tanpa satu pun galat, dan tanggal itu lolos ke layar. */
        $tanggal = [];
        foreach (['sertifikat_terbit', 'sertifikat_berakhir'] as $field) {
            if ($this->input->post($field) === NULL) { continue; }
            $v = trim((string) $this->input->post($field, TRUE));
            if ($v === '') { $payload[$field] = NULL; $tanggal[$field] = NULL; continue; }
            $d = DateTime::createFromFormat('!Y-m-d', $v);
            if ( ! $d || $d->format('Y-m-d') !== $v) {
                $this->session->set_flashdata('error', 'Tanggal sertifikat harus berformat YYYY-MM-DD.');
                redirect('Admin_Srp2'); return;
            }
            $payload[$field] = $v;
            $tanggal[$field] = $v;
        }
        if ( ! empty($tanggal['sertifikat_terbit']) && ! empty($tanggal['sertifikat_berakhir'])
            && $tanggal['sertifikat_terbit'] > $tanggal['sertifikat_berakhir']) {
            $this->session->set_flashdata('error', 'Tanggal terbit tidak boleh melewati tanggal akhir masa berlaku.');
            redirect('Admin_Srp2'); return;
        }

        /* ── Butir 7: status bertingkat ─────────────────────────────────────
           Divalidasi ke daftar tertutup. Nilai di luar daftar TIDAK diam-diam
           diabaikan - ia ditolak dengan pesan, karena status yang meleset
           mengubah arti seluruh baris di mata dinas. */
        if ($this->input->post('status_sertifikasi') !== NULL) {
            $s = (string) $this->input->post('status_sertifikasi', TRUE);
            if ( ! in_array($s, self::STATUS_SERTIFIKASI, TRUE)) {
                $this->session->set_flashdata('error', 'Status sertifikasi tidak dikenal.');
                redirect('Admin_Srp2'); return;
            }
            $payload['status_sertifikasi'] = $s;
        }

        /* ── Butir 7: kabupaten ─────────────────────────────────────────────
           Divalidasi ke TABEL, bukan sekadar dicek angka. Id yang tidak ada
           akan lolos `is_numeric` lalu menghasilkan baris berwilayah hantu. */
        if ($this->input->post('kabupaten_id') !== NULL) {
            $kab = (int) $this->input->post('kabupaten_id');
            if ($kab === 0) {
                $payload['kabupaten_id'] = NULL;
            } elseif ($this->db->where('id', $kab)->count_all_results('kabupaten')) {
                $payload['kabupaten_id'] = $kab;
            } else {
                $this->session->set_flashdata('error', 'Kabupaten/kota tidak dikenal.');
                redirect('Admin_Srp2'); return;
            }
        }

        /* ── Butir 12: asosiasi ─────────────────────────────────────────────
           Ketik bebas sampai dinas mengirim daftar resminya. Pertanyaannya
           sudah disampaikan; sampai dijawab, mengarang daftar sendiri berarti
           memaksa pengembang memilih asosiasi yang mungkin bukan miliknya. */
        if ($this->input->post('asosiasi') !== NULL) {
            $a = mb_substr(trim((string) $this->input->post('asosiasi', TRUE)), 0, 100);
            $payload['asosiasi'] = $a === '' ? NULL : $a;
        }

        /* ── Butir 8: NPWP sebagai kunci pengembang ─────────────────────────
           Diperlakukan PERSIS seperti NIK warga: nilai aslinya dienkripsi, dan
           yang dipakai mencari/menegakkan keunikan adalah sidik deterministik.
           Polanya tidak dibuat baru - `Encryption_lib` sudah menyediakannya.

           Angka saja yang disimpan. NPWP ditulis orang dengan titik dan strip
           yang berbeda-beda; menyimpan apa adanya membuat dua tulisan NPWP yang
           sama menghasilkan dua sidik berbeda, dan UNIQUE-nya jadi tidak
           menjaga apa pun. */
        if ($this->input->post('npwp') !== NULL) {
            $npwp_mentah = preg_replace('/\D+/', '', (string) $this->input->post('npwp', TRUE));
            if ($npwp_mentah === '') {
                $payload['npwp_ciphertext']  = NULL;
                $payload['npwp_lookup_hash'] = NULL;
            } elseif (strlen($npwp_mentah) < 15 || strlen($npwp_mentah) > 16) {
                $this->session->set_flashdata('error', 'NPWP harus 15 atau 16 digit angka.');
                redirect('Admin_Srp2'); return;
            } else {
                $this->load->library('encryption_lib');
                $payload['npwp_ciphertext']  = $this->encryption_lib->encrypt($npwp_mentah);
                $payload['npwp_lookup_hash'] = $this->encryption_lib->deterministic_hash($npwp_mentah);
            }
        }

        // Hasil query DIPERIKSA, bukan diasumsikan - pola sukses karangan yang
        // sama seperti proses() sebelum T1a. Kolom nama ber-UNIQUE, jadi bentrok
        // adalah kegagalan yang paling mungkin terjadi; di production db_debug
        // mati sehingga insert/update gagal hanya mengembalikan FALSE dan alur
        // berjalan terus sampai menyetel "Daftar pengembang diperbarui".
        $ok = $id
            ? $this->db->where('id', $id)->update('srp2_certified_developers', $payload)
            : $this->db->insert('srp2_certified_developers', $payload);

        if ($ok === FALSE) {
            log_message('error', 'Admin_Srp2::save gagal - id=' . $id . ' nama=' . $name);
            $this->session->set_flashdata('error', 'Gagal menyimpan: nama perusahaan "' . $name . '" kemungkinan sudah dipakai baris lain di direktori. Tidak ada perubahan yang tersimpan.');
            redirect('Admin_Srp2'); return;
        }
        $this->session->set_flashdata('success', 'Daftar pengembang diperbarui.'); redirect('Admin_Srp2');
    }

    public function delete($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || !is_numeric($id)) { show_404(); }
        // DELETE yang tidak cocok baris mana pun tetap mengembalikan TRUE, jadi
        // `affected_rows()` yang membedakan "terhapus" dari "id-nya memang tidak ada".
        if ( ! $this->db->where('id', (int) $id)->delete('srp2_certified_developers')
            || $this->db->affected_rows() !== 1) {
            $this->session->set_flashdata('error', 'Pengembang tidak ditemukan atau sudah dihapus.');
            redirect('Admin_Srp2'); return;
        }
        $this->session->set_flashdata('success', 'Pengembang dihapus dari daftar.'); redirect('Admin_Srp2');
    }
}
