<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Validasi skema per-endpoint untuk API dan layanan web (form keamanan poin 12.5).
 *
 * Tidak bergantung pada CodeIgniter (dapat diuji offline). Skemanya deklaratif di
 * config/api_schemas.php dan dipaksakan MY_Controller::enforce_api_schema() SEBELUM metode
 * controller berjalan. Input_guard (allowlist nama field dan format berdasarkan nama) tetap
 * berjalan lebih dulu; lapisan ini menambah yang tidak bisa dilakukan allowlist global:
 *   - metode HTTP yang diizinkan per endpoint (405 + Allow), XHR bila diwajibkan,
 *   - Content-Type yang didukung (415),
 *   - field yang WAJIB, tipe, rentang, panjang, pola, dan nilai enum PER ENDPOINT,
 *   - field yang tidak dideklarasikan ditolak (bukan sekadar "dikenal secara global"),
 *   - larik/objek di tempat yang seharusnya skalar ditolak (kebingungan tipe),
 *   - objek JSON bersarang (mis. langganan Web Push) dengan skema turunannya,
 *   - segmen URI (mis. id di /Pengembang/simpan_dokumen/{id}) dan nama kolom berkas.
 * Pesan galat menyebut nama field dan aturannya, tidak pernah nilai yang dikirim.
 */
class Api_schema {

    private $schemas;
    private $common;

    /** @param array $params schemas (array skema), common (nama field tambahan yang selalu boleh) */
    public function __construct(array $params = [])
    {
        if (isset($params['schemas'])) {
            $this->schemas = $params['schemas'];
            $this->common = $params['common'] ?? [];
            return;
        }
        $config = [];
        require dirname(__DIR__) . '/config/api_schemas.php';
        $this->schemas = $config['api_schemas'];
        $this->common = $config['api_schema_common'];
    }

    public function schemas() { return $this->schemas; }

    /** @return array|NULL skema untuk "controller/metode" (huruf kecil) */
    public function find($controller, $method)
    {
        return $this->schemas[strtolower((string) $controller) . '/' . strtolower((string) $method)] ?? NULL;
    }

    /**
     * @param array $schema skema endpoint
     * @param array $req    method, get, post, files (nama kolom), segments, ajax, content_type, json (badan JSON terurai, atau NULL)
     * @return array {ok:bool, status:int, code:string, errors:array<string,string>, structural:bool, allow?:string[]}
     *         structural = pelanggaran yang menandakan kiriman dirakit (bukan salah ketik pengguna)
     */
    public function validate(array $schema, array $req)
    {
        $method = strtoupper((string) ($req['method'] ?? 'GET'));
        $spec = $schema['methods'][$method] ?? NULL;
        if ($spec === NULL) {
            return $this->gagal(405, 'method_not_allowed', ['_metode' => 'Metode HTTP tidak diizinkan untuk endpoint ini.'], TRUE) + ['allow' => array_keys($schema['methods'])];
        }
        if ( ! empty($schema['ajax']) && empty($req['ajax'])) {
            return $this->gagal(400, 'ajax_required', ['_ajax' => 'Endpoint ini hanya melayani permintaan XHR.'], TRUE);
        }

        $sumber = $spec['source'] ?? 'form';
        $ctype = strtolower(trim(explode(';', (string) ($req['content_type'] ?? ''))[0]));
        if ($sumber === 'json') {
            if ($ctype !== 'application/json') {
                return $this->gagal(415, 'unsupported_media_type', ['_content_type' => 'Content-Type harus application/json.'], TRUE);
            }
            $data = $req['json'] ?? NULL;
            if ( ! is_array($data) || ($data !== [] && array_keys($data) === range(0, count($data) - 1))) {
                return $this->gagal(422, 'schema_invalid', ['_badan' => 'Badan JSON harus berupa objek.'], TRUE);
            }
        } else {
            if ($ctype === 'application/json') {
                return $this->gagal(415, 'unsupported_media_type', ['_content_type' => 'Endpoint ini tidak menerima badan JSON.'], TRUE);
            }
            $data = ($sumber === 'query') ? ($req['get'] ?? []) : (($method === 'GET') ? ($req['get'] ?? []) : ($req['post'] ?? []));
        }

        $errors = []; $struktur = FALSE;

        // Segmen URI.
        $segmen = array_values((array) ($req['segments'] ?? []));
        $aturan_segmen = $schema['segments'] ?? [];
        if (count($segmen) > count($aturan_segmen)) {
            $errors['_segmen'] = 'Jumlah segmen URI melebihi yang diizinkan.'; $struktur = TRUE;
        }
        foreach ($aturan_segmen as $i => $rule) {
            if (($segmen[$i] ?? '') === '') {
                if ( ! empty($rule['required'])) { $errors['segmen_' . $i] = 'wajib diisi'; }
                continue;
            }
            $e = $this->nilai($rule, $segmen[$i]);
            if ($e !== NULL) { $errors['segmen_' . $i] = $e; }
        }

        // Field.
        $fields = $spec['fields'] ?? [];
        $unknown = $spec['unknown'] ?? 'reject';
        if ($unknown === 'reject') {
            foreach (array_keys($data) as $nama) {
                if ( ! isset($fields[$nama]) && ! in_array($nama, $this->common, TRUE)) {
                    $errors[(string) $nama] = 'field tidak dikenal untuk endpoint ini'; $struktur = TRUE;
                }
            }
        }
        foreach ($fields as $nama => $rule) {
            $ada = array_key_exists($nama, $data) && $data[$nama] !== NULL;
            if ( ! $ada || $data[$nama] === '') {
                if ( ! empty($rule['required'])) { $errors[$nama] = 'wajib diisi'; }
                continue;
            }
            $e = $this->nilai($rule, $data[$nama], $struktur);
            if ($e !== NULL) { $errors[$nama] = $e; }
        }

        // Kolom berkas.
        if (isset($spec['files'])) {
            $kolom = array_values((array) ($req['files'] ?? []));
            if (count($kolom) > (int) ($spec['files']['max'] ?? 1)) { $errors['_berkas'] = 'jumlah berkas melebihi batas'; $struktur = TRUE; }
            foreach ($kolom as $k) {
                if ( ! preg_match($spec['files']['pattern'], (string) $k)) { $errors['berkas'] = 'nama kolom berkas tidak dikenal'; $struktur = TRUE; break; }
            }
        } elseif ( ! empty($req['files']) && $unknown === 'reject') {
            $errors['_berkas'] = 'endpoint ini tidak menerima berkas'; $struktur = TRUE;
        }

        return $errors ? $this->gagal(422, 'schema_invalid', $errors, $struktur) : ['ok' => TRUE, 'status' => 200, 'code' => NULL, 'errors' => [], 'structural' => FALSE];
    }

    // ------------------------------------------------------------------ satu nilai
    /** @return string|NULL pesan galat tanpa nilai yang dikirim */
    public function nilai(array $rule, $value, &$struktur = FALSE)
    {
        $type = $rule['type'] ?? 'string';
        if (is_array($value) && $type !== 'object' && $type !== 'array') {
            $struktur = TRUE;
            return 'harus berupa nilai tunggal, bukan larik atau objek';
        }
        if ($type === 'object') { return $this->objek($rule, $value, $struktur); }
        if ($type === 'array') { return $this->larik($rule, $value, $struktur); }
        if ( ! is_scalar($value)) { $struktur = TRUE; return 'tipe nilai tidak didukung'; }
        $s = (string) $value;

        switch ($type) {
            case 'int':
                if ( ! preg_match('/^-?\d{1,18}$/D', $s)) { return 'harus bilangan bulat'; }
                if (isset($rule['min']) && (int) $s < $rule['min']) { return 'di bawah batas minimum ' . $rule['min']; }
                if (isset($rule['max']) && (int) $s > $rule['max']) { return 'di atas batas maksimum ' . $rule['max']; }
                return NULL;
            case 'float':
                if ( ! preg_match('/^-?\d{1,15}(?:[.,]\d{1,8})?$/D', $s)) { return 'harus bilangan desimal'; }
                $f = (float) str_replace(',', '.', $s);
                if (isset($rule['min']) && $f < $rule['min']) { return 'di bawah batas minimum ' . $rule['min']; }
                if (isset($rule['max']) && $f > $rule['max']) { return 'di atas batas maksimum ' . $rule['max']; }
                return NULL;
            case 'bool':
                return in_array(strtolower($s), ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no', 'ya', 'tidak'], TRUE) ? NULL : 'harus nilai boolean';
            case 'enum':
                return in_array($s, array_map('strval', (array) ($rule['values'] ?? [])), TRUE) ? NULL : 'bukan salah satu nilai yang diizinkan';
            case 'date':
                if ( ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $s, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) { return 'harus tanggal YYYY-MM-DD yang sah'; }
                return NULL;
            case 'url':
                $skema = ! empty($rule['https_only']) ? '#^https://#i' : '#^https?://#i';
                if ( ! preg_match($skema, $s) || ! filter_var($s, FILTER_VALIDATE_URL)) { return 'harus URL ' . ( ! empty($rule['https_only']) ? 'https ' : '') . 'yang sah'; }
                return $this->panjang($rule, $s);
            case 'string':
            default:
                if ( ! preg_match('//u', $s)) { return 'bukan UTF-8 yang sah'; }
                if (($e = $this->panjang($rule, $s)) !== NULL) { return $e; }
                if (isset($rule['pattern']) && ! preg_match($rule['pattern'], $s)) { return 'format tidak sesuai'; }
                return NULL;
        }
    }

    private function panjang(array $rule, $s)
    {
        $n = function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
        if (isset($rule['min_len']) && $n < $rule['min_len']) { return 'terlalu pendek (minimal ' . $rule['min_len'] . ' karakter)'; }
        if (isset($rule['max_len']) && $n > $rule['max_len']) { return 'terlalu panjang (maksimal ' . $rule['max_len'] . ' karakter)'; }
        return NULL;
    }

    /** Objek bersarang: array asosiatif, atau string JSON bila 'json' => TRUE. */
    private function objek(array $rule, $value, &$struktur)
    {
        if ( ! empty($rule['json'])) {
            if ( ! is_string($value)) { $struktur = TRUE; return 'harus string JSON'; }
            if (strlen($value) > (int) ($rule['max_bytes'] ?? 8192)) { return 'JSON terlalu besar'; }
            $value = json_decode($value, TRUE, (int) ($rule['max_depth'] ?? 4));
            if (json_last_error() !== JSON_ERROR_NONE) { return 'JSON tidak valid atau terlalu dalam'; }
        }
        if ( ! is_array($value) || ($value !== [] && array_keys($value) === range(0, count($value) - 1))) { $struktur = TRUE; return 'harus berupa objek'; }
        $fields = $rule['fields'] ?? [];
        $sub = [];
        if (($rule['unknown'] ?? 'ignore') === 'reject') {
            foreach (array_keys($value) as $k) { if ( ! isset($fields[$k])) { $sub[$k] = 'field tidak dikenal'; $struktur = TRUE; } }
        }
        foreach ($fields as $nama => $r) {
            $ada = array_key_exists($nama, $value) && $value[$nama] !== NULL && $value[$nama] !== '';
            if ( ! $ada) { if ( ! empty($r['required'])) { $sub[$nama] = 'wajib diisi'; } continue; }
            $e = $this->nilai($r, $value[$nama], $struktur);
            if ($e !== NULL) { $sub[$nama] = $e; }
        }
        if ( ! $sub) { return NULL; }
        $bagian = [];
        foreach ($sub as $k => $v) { $bagian[] = $k . ': ' . $v; }
        return 'objek tidak sesuai skema (' . implode('; ', $bagian) . ')';
    }

    private function larik(array $rule, $value, &$struktur)
    {
        if ( ! is_array($value) || ($value !== [] && array_keys($value) !== range(0, count($value) - 1))) { $struktur = TRUE; return 'harus berupa larik'; }
        if (count($value) > (int) ($rule['max_items'] ?? 50)) { return 'terlalu banyak elemen'; }
        foreach ($value as $v) {
            $e = $this->nilai($rule['items'] ?? ['type' => 'string', 'max_len' => 200], $v, $struktur);
            if ($e !== NULL) { return 'elemen tidak valid: ' . $e; }
        }
        return NULL;
    }

    private function gagal($status, $code, array $errors, $struktur)
    {
        return ['ok' => FALSE, 'status' => $status, 'code' => $code, 'errors' => $errors, 'structural' => (bool) $struktur];
    }
}
