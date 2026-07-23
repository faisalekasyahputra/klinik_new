<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KemitraanPortal extends Public_Controller
{
    public function index()
    {
        $this->render('pages/kemitraan_portal/index', ['judul' => 'KKN dan Magang']);
    }

    public function kkn()
    {
        $this->render('pages/kemitraan_portal/kkn', ['judul' => 'KKN Tematik']);
    }

    public function magang()
    {
        $this->render('pages/kemitraan_portal/magang', [
            'judul' => 'Magang dan Kerja Praktik',
            'slot_magang' => [
                ['divisi' => 'Administrasi Pemerintahan', 'tersedia' => ['Juni']],
                ['divisi' => 'Infrastruktur dan Teknologi Digital', 'tersedia' => ['Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Komunikasi Publik dan Media', 'tersedia' => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Komunikasi Publik dan Media (PPID)', 'tersedia' => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Pengelolaan Data Statistik', 'tersedia' => ['Oktober', 'November', 'Desember']],
                ['divisi' => 'Penyediaan dan Pengadaan Barang', 'tersedia' => ['Januari', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Sekretariat', 'tersedia' => ['Maret', 'April', 'Mei', 'Juni', 'Oktober', 'November', 'Desember']],
            ],
        ]);
    }
}
