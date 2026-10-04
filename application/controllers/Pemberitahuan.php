<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pusat Pemberitahuan (4 Okt 2026): baris yang membentuk angka merah di sidebar, per modul.
 *
 * Satu halaman untuk tiga peran staf, jadi tidak memakai base controller per peran: guard-nya di
 * constructor (login wajib, peran non-staf 404). Daftar modul = modul_untuk_peran() (saringan yang
 * sama dengan sidebar: hak modul, roles, scope, scope_values), dan barisnya dari pending_modul_baris()
 * (definisi yang sama dengan angka badge). Halaman ini HANYA membaca dan tidak menampilkan data
 * pribadi: penanda baris dari 'tindakan' registry (kode tiket/nomor), bukan nama, NIK, atau isi aduan.
 * Tindakan sesungguhnya tetap di modul masing-masing dengan guard dan scope-nya sendiri.
 */
class Pemberitahuan extends MY_Controller {

    /** Peran yang punya antrean ber-badge. */
    private const PERAN_STAF = ['admin', 'admin_kabkota', 'admin_bidang'];

    /** ponytail: 50 terbaru per modul; sisanya lewat tautan "lihat semua di modul". */
    private const BATAS = 50;

    public function __construct() {
        parent::__construct();
        if ( ! $this->session->userdata('is_logged')) { $this->gerbang_login(); }
        if ( ! in_array($this->current_role(), self::PERAN_STAF, TRUE)) { show_404(); }
    }

    public function index() {
        $modul = [];
        foreach ($this->modul_untuk_peran() as $key => $m) {
            if (empty($m['badge'])) { continue; }
            $hasil = $this->pending_modul_baris($m, self::BATAS);
            if ($hasil['total'] === 0) { continue; }
            $modul[] = $hasil + ['key' => $key, 'modul' => $m];
        }
        $this->render_user_dashboard('admin/pemberitahuan/index', [
            'title' => 'Pusat Pemberitahuan',
            'modul_pemberitahuan' => $modul,
            'batas' => self::BATAS,
        ]);
    }
}
