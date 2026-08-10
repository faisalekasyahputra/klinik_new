<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peninggalan mockup kemitraan - SEKARANG HANYA PENGALIH.
 *
 * Sampai 3 Agt 2026 controller ini me-`load->view()` empat berkas mockup di
 * `views/pages/kemitraan/*` secara langsung: HTML utuh ber-`<!doctype>`, form
 * tanpa `action`, tautan ke `kemitraan.html`. Tidak ditaut dari portal mana pun,
 * tapi tetap bisa dibuka siapa saja yang menebak /Kemitraan/kkn.
 *
 * Kenapa itu masalah, bukan sekadar berantakan: mockup-nya masih menulis
 * "KKN Tematik" - nomenklatur yang dicabut dinas - dan memuat teks persyaratan
 * KEDUA yang berbeda dari yang resmi ("mahasiswa aktif minimal semester 6",
 * "maksimal 10 orang per lokasi") yang tidak pernah dikonfirmasi siapa pun.
 * Mengganti label di portal baru sambil membiarkan halaman ini hidup berarti
 * dua versi kebenaran tetap bisa dibaca publik, dan yang salah justru yang
 * tidak pernah diperbarui lagi.
 *
 * Dialihkan, bukan dihapus: tautan/bookmark lama tetap mendarat di tempat yang
 * benar alih-alih 404. Berkas view-nya dibiarkan di tempatnya sebagai arsip -
 * tidak ada lagi yang merendernya.
 */
class Kemitraan extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		date_default_timezone_set('Asia/Jakarta');
	}

	public function index()
	{
		redirect('KemitraanPortal');
	}

	public function kkn()
	{
		redirect('KemitraanPortal/kkn');
	}

	public function magang()
	{
		redirect('KemitraanPortal/magang');
	}

	public function ketentuan()
	{
		// Tidak ada padanan tunggal: ketentuan lama menggabung KKN + Magang.
		// Portal memisahkannya, jadi pengunjung dikembalikan ke pintu depannya.
		redirect('KemitraanPortal');
	}
}
