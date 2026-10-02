<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tantangan bot untuk formulir publik tanpa akun (login dan registrasi), form keamanan
 * poin 10.4. Berdiri sendiri, tidak memakai layanan pihak ketiga.
 *
 * Kenapa perlu: verifikasi Google reCAPTCHA di Auth dilewati seluruhnya bila kunci situs
 * kosong, dan di production per 21 Sep 2026 kuncinya kosong. Jadi halaman login dan
 * registrasi tidak punya tantangan bot sama sekali; hanya batas laju.
 *
 * Dua tanda yang murah tetapi memilah skrip dari manusia:
 *  1. HONEYPOT: kolom `situs_web` yang disembunyikan dengan CSS. Manusia tidak pernah
 *     melihat atau mengisinya; pengisi formulir otomatis mengisi semua kolom teks.
 *  2. TOKEN WAKTU bertanda tangan HMAC yang disematkan saat halaman DIRENDER. Skrip yang
 *     menembak endpoint langsung tidak membawanya, dan skrip yang membaca halaman lalu
 *     langsung mengirim (dalam ~0 detik) terlalu cepat untuk manusia.
 *
 * Penegakan token (wajib ada, batas usia, dan waktu minimum) HANYA di production. Di lingkungan
 * lain kolom honeypot tetap ditegakkan tetapi token tidak wajib, karena 49 suite uji proyek
 * ini masuk lewat HTTP dengan mengirim formulir seketika setelah memuatnya. Perilaku
 * production dibuktikan lewat parameter $production di tests/anti_automation_test.php.
 */
class Bot_guard {

    private $cfg;
    private $secret;

    public function __construct()
    {
        require_once dirname(__DIR__) . '/helpers/anti_automation_helper.php';
        $this->cfg = anti_automation_config('bot_guard', []);
        $kunci = (string) getenv('KPKP_DATA_KEY');
        if ($kunci === '' && function_exists('get_instance') && ($ci = get_instance())) {
            $kunci = (string) $ci->config->item('encryption_key');
        }
        // Token ini bukan batas keamanan yang rahasianya harus dijaga ketat (ia hanya membuktikan
        // "halaman ini dimuat lalu ditunggu"); tanpa kunci pun tetap berfungsi sebagai penanda waktu.
        $this->secret = 'bot-guard:v1:' . ($kunci !== '' ? $kunci : __FILE__);
    }

    public function token($form, $waktu = NULL)
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['f' => (string) $form, 't' => (int) ($waktu ?? time())])), '+/', '-_'), '=');
        return $payload . '.' . substr(hash_hmac('sha256', $payload, $this->secret), 0, 32);
    }

    /** HTML kolom honeypot tersembunyi dan token; sisipkan di dalam <form>. */
    public function fields($form)
    {
        $h = htmlspecialchars((string) ($this->cfg['honeypot_field'] ?? 'situs_web'), ENT_QUOTES, 'UTF-8');
        $t = htmlspecialchars((string) ($this->cfg['token_field'] ?? 'bot_token'), ENT_QUOTES, 'UTF-8');
        return '<div aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">'
            . '<label>Jangan diisi<input type="text" name="' . $h . '" value="" tabindex="-1" autocomplete="off"></label></div>'
            . '<input type="hidden" name="' . $t . '" value="' . htmlspecialchars($this->token($form), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * @param array $post kiriman formulir (bawaan $_POST)
     * @return array {ok: bool, reason: string|null}
     *         reason: honeypot | token_hilang | token_rusak | form_salah | terlalu_cepat | kedaluwarsa
     */
    public function check($form, array $post = NULL, $sekarang = NULL, $production = NULL)
    {
        $post = $post ?? $_POST;
        $sekarang = (int) ($sekarang ?? time());
        $production = $production ?? (defined('ENVIRONMENT') && ENVIRONMENT === 'production');
        $hp = (string) ($this->cfg['honeypot_field'] ?? 'situs_web');
        $tk = (string) ($this->cfg['token_field'] ?? 'bot_token');

        if (isset($post[$hp]) && trim((string) $post[$hp]) !== '') {
            return ['ok' => FALSE, 'reason' => 'honeypot'];
        }
        $token = isset($post[$tk]) ? (string) $post[$tk] : '';
        if ($token === '') {
            return $production ? ['ok' => FALSE, 'reason' => 'token_hilang'] : ['ok' => TRUE, 'reason' => NULL];
        }
        $bagian = explode('.', $token);
        if (count($bagian) !== 2 || ! hash_equals(substr(hash_hmac('sha256', $bagian[0], $this->secret), 0, 32), $bagian[1])) {
            return ['ok' => FALSE, 'reason' => 'token_rusak'];
        }
        $isi = json_decode((string) base64_decode(strtr($bagian[0], '-_', '+/')), TRUE);
        if ( ! is_array($isi) || ! isset($isi['f'], $isi['t'])) {
            return ['ok' => FALSE, 'reason' => 'token_rusak'];
        }
        if ($isi['f'] !== (string) $form) {
            return ['ok' => FALSE, 'reason' => 'form_salah'];
        }
        if ( ! $production) {
            return ['ok' => TRUE, 'reason' => NULL];
        }
        $umur = $sekarang - (int) $isi['t'];
        if ($umur > (int) ($this->cfg['token_max_age'] ?? 21600)) {
            return ['ok' => FALSE, 'reason' => 'kedaluwarsa'];
        }
        if ($umur < (int) ($this->cfg['min_fill_seconds'] ?? 1)) {
            return ['ok' => FALSE, 'reason' => 'terlalu_cepat'];
        }
        return ['ok' => TRUE, 'reason' => NULL];
    }
}
