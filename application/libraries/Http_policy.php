<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan metode HTTP dan kebersihan URI (form keamanan poin 12.2 dan 12.4).
 *
 * Tidak bergantung pada CodeIgniter (dapat diuji offline). Dipaksakan MY_Controller::
 * enforce_http_policy() sebelum controller berjalan:
 *   - hanya GET, HEAD, POST yang dilayani; PUT/DELETE/PATCH/TRACE dst. = 405;
 *   - penerowongan metode (X-HTTP-Method-Override, _method) = 400;
 *   - endpoint yang mengubah keadaan hanya POST (GET/HEAD = 405 + Allow: POST);
 *   - OPTIONS dijawab 204 dengan Allow milik rute itu saja;
 *   - query string tidak boleh memuat data pribadi/rahasia, dan jalur URI tidak boleh memuat NIK/surel.
 * Header Allow hanya dikeluarkan untuk rute yang sungguh ada (controller dan metodenya sudah
 * teresolusi sebelum pemeriksaan ini), tidak pernah untuk alamat asal-asalan.
 */
class Http_policy {

    private $policy;
    private $schemas;

    /** @param array $params policy (isi 'http_policy'), schemas (registri skema API; NULL = baca config) */
    public function __construct(array $params = [])
    {
        if (isset($params['policy'])) {
            $this->policy = $params['policy'];
        } else {
            $config = [];
            require dirname(__DIR__) . '/config/http_policy.php';
            $this->policy = $config['http_policy'];
        }
        if (array_key_exists('schemas', $params)) {
            $this->schemas = (array) $params['schemas'];
        } else {
            $config = [];
            require dirname(__DIR__) . '/config/api_schemas.php';
            $this->schemas = $config['api_schemas'];
        }
    }

    /** Metode yang dilayani rute "controller/metode" (huruf kecil), tanpa OPTIONS. */
    public function allow_for($route)
    {
        $route = strtolower((string) $route);
        if (isset($this->schemas[$route]['methods'])) {
            $m = array_keys($this->schemas[$route]['methods']);
            if (in_array('GET', $m, TRUE) && ! in_array('HEAD', $m, TRUE)) { $m[] = 'HEAD'; }
            return array_values(array_intersect($this->policy['allowed_methods'], $m));
        }
        if (isset($this->policy['post_only'][$route])) { return ['POST']; }
        return $this->policy['allowed_methods'];
    }

    /**
     * @param array $req route, method, server (kunci $_SERVER; untuk header penimpa), get, post (array)
     * @return array {ok:bool, status:int, code:string|NULL, message:string, allow:string[], structural:bool}
     *         status 204 + code 'options' untuk permintaan OPTIONS yang sah.
     */
    public function check(array $req)
    {
        $route = strtolower((string) ($req['route'] ?? ''));
        $method = strtoupper((string) ($req['method'] ?? 'GET'));
        $allow = $this->allow_for($route);

        // 1. Penerowongan metode.
        foreach ($this->policy['method_override_headers'] as $h) {
            if ( ! empty($req['server'][$h])) { return $this->tolak(400, 'method_override_forbidden', 'Penimpaan metode HTTP tidak diizinkan.', $allow, TRUE); }
        }
        foreach ($this->policy['method_override_params'] as $p) {
            if (isset($req['get'][$p]) || isset($req['post'][$p])) { return $this->tolak(400, 'method_override_forbidden', 'Penimpaan metode HTTP tidak diizinkan.', $allow, TRUE); }
        }

        // 2. OPTIONS: hanya menyebut metode rute ini.
        if ($method === 'OPTIONS') {
            return ['ok' => FALSE, 'status' => 204, 'code' => 'options', 'message' => '', 'allow' => array_merge($allow, ['OPTIONS']), 'structural' => FALSE];
        }

        // 3. Metode yang tidak pernah dilayani.
        if ( ! in_array($method, $this->policy['allowed_methods'], TRUE)) {
            return $this->tolak(405, 'method_not_allowed', 'Metode HTTP tidak diizinkan.', $allow, TRUE);
        }

        // 4. Endpoint yang mengubah keadaan: hanya POST.
        if (isset($this->policy['post_only'][$route]) && $method !== 'POST') {
            return $this->tolak(405, 'method_not_allowed', 'Metode HTTP tidak diizinkan untuk endpoint ini.', $allow, FALSE);
        }

        // 5. Data sensitif di URI.
        $peka = array_flip($this->policy['sensitive_query']);
        foreach (array_keys((array) ($req['get'] ?? [])) as $k) {
            if (isset($peka[strtolower((string) $k)])) {
                return $this->tolak(400, 'sensitive_in_uri', 'Data sensitif tidak boleh dikirim lewat URI; gunakan badan POST.', $allow, TRUE);
            }
        }
        foreach ((array) ($req['segments'] ?? []) as $seg) {
            $seg = rawurldecode((string) $seg);
            foreach ($this->policy['sensitive_path'] as $re) {
                if (preg_match($re, $seg)) { return $this->tolak(400, 'sensitive_in_uri', 'Data sensitif tidak boleh berada di jalur URI.', $allow, TRUE); }
            }
        }
        return ['ok' => TRUE, 'status' => 200, 'code' => NULL, 'message' => '', 'allow' => $allow, 'structural' => FALSE];
    }

    private function tolak($status, $code, $pesan, array $allow, $struktural)
    {
        return ['ok' => FALSE, 'status' => $status, 'code' => $code, 'message' => $pesan, 'allow' => $allow, 'structural' => (bool) $struktural];
    }
}
