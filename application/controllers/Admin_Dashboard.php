<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Meja kerja superadmin. Semua angka di sini hasil query nyata; kalau suatu
     * metrik belum bisa dihitung, jangan diisi angka karangan.
     */
    public function index() {
        $data['title'] = 'Meja Kerja Super Admin';

        // Kartu kerja dibangun dari registry yang sama dengan sidebar, tetapi
        // hanya untuk role aktif agar modul scoped tidak bocor ke overview.
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $data['kartu_domain'] = [];
        $role = $this->current_role();
        foreach ($this->config->item('dashboard_modules') ?: [] as $key => $modul) {
            if (empty($modul['ringkas']) || empty($modul['table'])) { continue; }
            if (array_key_exists('enabled', $modul) && $modul['enabled'] === FALSE) { continue; }
            if (empty($modul['roles']) || ! in_array($role, $modul['roles'], TRUE)) { continue; }
            if ( ! empty($modul['scope']) && empty($this->session->userdata($modul['scope']))) { continue; }
            $data['kartu_domain'][] = [
                'label'   => $modul['ringkas'],
                'icon'    => $modul['icon'],
                'url'     => $modul['overview_url'] ?? $modul['url'],
                'pending' => $this->count_pending_modul($modul),
            ];
        }

        $data['total_users'] = (int) $this->db->count_all('usr_users');
        $data['total_diskusi'] = $this->db->table_exists('forum_diskusi')
            ? (int) $this->db->count_all('forum_diskusi') : 0;

        // Hanya backlog aktif: riwayat yang sudah diputus bukan pekerjaan hari ini.
        $data['antrean_tanpa_wilayah'] = (int) $this->db
            ->where('kabupaten_id IS NULL', NULL, FALSE)
            ->where('status_antrean', 'pending')
            ->count_all_results('sf_housing_queue');

        $data['aktivitas'] = $this->aktivitas_terkini();

        $this->render_admin('admin/dashboard', $data);
    }

    /**
     * Pengajuan baru lintas seluruh domain kerja superadmin. Setiap baris
     * membawa tujuan daftar yang sudah terfilter, bukan sekadar informasi mati.
     */
    private function aktivitas_terkini() {
        $items = [];

        foreach ($this->db->select('nama_lengkap, status_antrean, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('sf_housing_queue')->result() as $r) {
            $items[] = [
                'icon' => 'ph-ticket', 'jenis' => 'Antrean Perumahan',
                'judul' => trim((string) $r->nama_lengkap) !== '' ? $r->nama_lengkap : 'Pengajuan warga',
                'status' => $r->status_antrean, 'waktu' => $r->created_at,
                'url' => 'Admin?status=' . rawurlencode($r->status_antrean),
            ];
        }
        foreach ($this->db->select('judul, status, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('aduan')->result() as $r) {
            $items[] = [
                'icon' => 'ph-chat-centered-text', 'jenis' => 'Aduan',
                'judul' => $r->judul ?: 'Aduan warga', 'status' => $r->status, 'waktu' => $r->created_at,
                'url' => 'Admin_Aduan?status=' . rawurlencode($r->status),
            ];
        }
        foreach ($this->db->select('nama_perusahaan, status_verifikasi, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('srp2_registrations')->result() as $r) {
            $items[] = [
                'icon' => 'ph-seal-check', 'jenis' => 'Sertifikasi SRP2',
                'judul' => $r->nama_perusahaan ?: 'Pengajuan SRP2',
                'status' => $r->status_verifikasi, 'waktu' => $r->created_at,
                'url' => 'Admin_Srp2/pending?status=' . rawurlencode($r->status_verifikasi),
            ];
        }
        foreach ($this->db->select('instansi_asal, status, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('kkn_magang_pendaftaran')->result() as $r) {
            $items[] = [
                'icon' => 'ph-graduation-cap', 'jenis' => 'KKN/Magang',
                'judul' => $r->instansi_asal ?: 'Pengajuan KKN/Magang',
                'status' => $r->status, 'waktu' => $r->created_at,
                'url' => 'Admin_Kemitraan?status=' . rawurlencode($r->status),
            ];
        }

        usort($items, fn($a, $b) => strtotime($b['waktu']) <=> strtotime($a['waktu']));
        return array_slice($items, 0, 6);
    }
}
