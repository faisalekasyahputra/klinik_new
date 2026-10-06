<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * robots.txt dan sitemap.xml (SEO 6 Okt 2026). Rute, bukan berkas fisik: .htaccess menolak berkas di
 * akar selain index.php, dan alamat situs (base_url) berbeda antara lokal, production, dan domain
 * dinas nanti. Isinya dari config/seo.php, jadi daftar halaman dan noindex hanya ada di satu tempat.
 */
class Seo extends MY_Controller {

    public function robots() {
        $cfg = seo_cfg();
        $akar = rtrim((string) parse_url(base_url(), PHP_URL_PATH), '/') . '/';
        $baris = ['User-agent: *', 'Allow: ' . $akar];
        // Layar staf dan endpoint data: selalu di balik login atau bukan halaman.
        // Jalur robots.txt peka huruf, sedangkan rute CI menerima /umum/forum maupun /Umum/forum: keduanya ditulis.
        $jalur = [];
        foreach (array_merge(['Admin', 'Rekam_', 'Kemitraan_Bidang', 'Push/', 'Chat/', 'ajax_', 'load_more', 'cari_wil'],
            (array) ($cfg['noindex'] ?? [])) as $p) {
            $jalur[$p] = TRUE;
            $jalur[strtolower($p)] = TRUE;
        }
        foreach (array_keys($jalur) as $p) { $baris[] = 'Disallow: ' . $akar . $p; }
        $baris[] = '';
        $baris[] = 'Sitemap: ' . base_url('sitemap.xml');
        $this->output->set_content_type('text/plain', 'utf-8')
            ->set_header('Cache-Control: public, max-age=3600')
            ->set_output(implode("\n", $baris) . "\n");
    }

    public function sitemap() {
        $url = [];
        foreach ((array) (seo_cfg()['halaman'] ?? []) as $kunci => $isi) {
            if (($isi['peta'] ?? TRUE) !== FALSE && ! seo_noindex(strtolower((string) $kunci))) { $url[] = base_url((string) $kunci); }
        }
        $this->load->model('Program_model');
        foreach ($this->Program_model->daftar_publik() as $p) { $url[] = base_url('program-pemerintah/' . str_replace('_', '-', $p['kode_program'])); }
        // Halaman detail dari data hulu yang sudah di-cache; hulu yang gagal cukup dilewati.
        try {
            require_once APPPATH . 'controllers/Kawasan_kumuh.php'; // tahun bawaan halaman daftarnya
            $this->load->library('sikaper_api');
            $hasil = $this->sikaper_api->get_data_kawasan((string) Kawasan_kumuh::TAHUN_BAWAAN);
            foreach ((array) ($hasil['data']['data'] ?? []) as $b) {
                if ( ! empty($b['id'])) { $url[] = base_url('kawasan_kumuh/detail/' . rawurlencode((string) $b['id'])); }
            }
        } catch (Throwable $e) { log_message('error', 'Seo::sitemap kawasan: ' . $e->getMessage()); }
        try {
            $this->load->library('ternak_api');
            foreach ((array) $this->ternak_api->get_public_house_designs() as $d) {
                if (isset($d['id']) && ctype_digit((string) $d['id'])) { $url[] = base_url('panduan_desain/' . $d['id']); }
            }
        } catch (Throwable $e) { log_message('error', 'Seo::sitemap desain: ' . $e->getMessage()); }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (array_unique($url) as $u) { $xml .= '  <url><loc>' . htmlspecialchars($u, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n"; }
        $this->output->set_content_type('application/xml', 'utf-8')
            ->set_header('Cache-Control: public, max-age=3600')
            ->set_output($xml . "</urlset>\n");
    }
}
