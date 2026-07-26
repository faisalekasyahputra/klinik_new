<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Overview superadmin. SEMUA angka di sini hasil query nyata — dulu
     * halaman ini mencampur data asli dengan angka hardcode (Publikasi Aktif
     * = 24), chart berisi data dummy, dan feed "Aktivitas Terkini" berupa HTML
     * statis bernama orang yang tidak ada. Lihat ANCHOR_DASHBOARD_TERPADU.md
     * temuan B2. Kalau suatu metrik belum bisa dihitung, JANGAN diisi angka
     * karangan — hilangkan kartunya atau tampilkan apa adanya.
     */
    public function index() {
        $data['title'] = 'Overview Dashboard';

        // Kartu per domain dibangun dari registry (entri ber-'ringkas'),
        // jadi menambah modul pengajuan baru otomatis muncul di sini tanpa
        // menyentuh controller/view ini.
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $data['kartu_domain'] = [];
        foreach ($this->config->item('dashboard_modules') ?: [] as $key => $modul) {
            if (empty($modul['ringkas']) || empty($modul['table'])) { continue; }
            if (array_key_exists('enabled', $modul) && $modul['enabled'] === FALSE) { continue; }
            $data['kartu_domain'][] = [
                'label'   => $modul['ringkas'],
                'icon'    => $modul['icon'],
                'url'     => $modul['url'],
                'pending' => $this->count_pending_modul($modul),
                'total'   => (int) $this->db->count_all_results($modul['table']),
            ];
        }

        $data['total_users'] = (int) $this->db->count_all('usr_users');
        $data['total_diskusi'] = $this->db->table_exists('forum_diskusi')
            ? (int) $this->db->count_all('forum_diskusi') : 0;

        // Antrean tanpa kabupaten = tidak terlihat admin wilayah manapun.
        // Sengaja ditonjolkan supaya tidak menumpuk diam-diam (Fase 3).
        $data['antrean_tanpa_wilayah'] = (int) $this->db
            ->where('kabupaten_id IS NULL', NULL, FALSE)
            ->count_all_results('sf_housing_queue');

        $data['tren'] = $this->tren_pengajuan_harian();
        $data['aktivitas'] = $this->aktivitas_terkini();

        $this->render_admin('admin/dashboard', $data);
    }

    /**
     * Jumlah pengajuan antrean per hari, 7 hari terakhir (termasuk hari yang
     * nol — supaya sumbu X tidak bolong dan tidak menyesatkan).
     */
    private function tren_pengajuan_harian() {
        $rows = $this->db->select("DATE(created_at) AS tanggal, COUNT(*) AS jumlah", FALSE)
            ->where('created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)', NULL, FALSE)
            ->group_by('DATE(created_at)')
            ->get('sf_housing_queue')->result();

        $per_tanggal = [];
        foreach ($rows as $r) { $per_tanggal[$r->tanggal] = (int) $r->jumlah; }

        $tren = ['label' => [], 'data' => []];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-{$i} day"));
            $tren['label'][] = date('d M', strtotime($tanggal));
            $tren['data'][]  = $per_tanggal[$tanggal] ?? 0;
        }
        return $tren;
    }

    /**
     * Aktivitas nyata lintas domain, 6 terbaru — pola agregator yang sama
     * dengan Pengaturan::index() milik user, tapi lingkupnya seluruh sistem.
     */
    private function aktivitas_terkini() {
        $items = [];

        foreach ($this->db->select('nama_lengkap, status_antrean, created_at')
            ->order_by('created_at', 'DESC')->limit(5)->get('sf_housing_queue')->result() as $r) {
            $items[] = [
                'icon' => 'ph-ticket', 'jenis' => 'Antrean Perumahan',
                'judul' => $r->nama_lengkap, 'status' => $r->status_antrean, 'waktu' => $r->created_at,
            ];
        }
        foreach ($this->db->select('judul, status, created_at')
            ->order_by('created_at', 'DESC')->limit(5)->get('aduan')->result() as $r) {
            $items[] = [
                'icon' => 'ph-chat-centered-text', 'jenis' => 'Aduan',
                'judul' => $r->judul, 'status' => $r->status, 'waktu' => $r->created_at,
            ];
        }
        foreach ($this->db->select('nama_perusahaan, status_verifikasi, created_at')
            ->order_by('created_at', 'DESC')->limit(5)->get('srp2_registrations')->result() as $r) {
            $items[] = [
                'icon' => 'ph-seal-check', 'jenis' => 'Sertifikasi SRP2',
                'judul' => $r->nama_perusahaan, 'status' => $r->status_verifikasi, 'waktu' => $r->created_at,
            ];
        }

        usort($items, fn($a, $b) => strtotime($b['waktu']) <=> strtotime($a['waktu']));
        return array_slice($items, 0, 6);
    }
}
