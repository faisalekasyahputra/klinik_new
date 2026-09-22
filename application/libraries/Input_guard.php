<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Validasi positif struktur, nama field, format, dan ukuran input. */
class Input_guard {
    private const MAX_FIELDS = 1000;
    private const MAX_DEPTH = 12;
    private const MAX_KEY_LENGTH = 128;
    private const MAX_TEXT_LENGTH = 100000;

    private $CI;
    private $allowed = [];

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('input_validation', TRUE);
        foreach (($this->CI->config->item('input_allowed_fields', 'input_validation') ?: []) as $field) {
            $this->allowed[(string) $field] = TRUE;
        }
    }

    public function validate_request() {
        $error = $this->validate_payload($_GET, 'query')
            ?: $this->validate_payload($_POST, 'form')
            ?: $this->validate_payload($_FILES, 'file');

        $method = strtoupper((string) $this->CI->input->method(TRUE));
        $content_type = strtolower(trim(explode(';', (string) $this->CI->input->server('CONTENT_TYPE'))[0]));
        if ( ! $error && in_array($method, ['POST', 'PUT', 'PATCH'], TRUE)
            && $content_type === 'application/json') {
            $raw = (string) $this->CI->input->raw_input_stream;
            if ($raw !== '') {
                $json = json_decode($raw, TRUE, self::MAX_DEPTH);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
                    $error = 'Payload JSON tidak valid atau terlalu dalam.';
                } else {
                    $error = $this->validate_payload($json, 'json');
                }
            }
        }

        if ($error) {
            log_message('error', 'SECURITY_WARNING invalid_input_rejected reason=' . $error
                . ' route=' . strtolower((string) $this->CI->router->fetch_class()) . '/'
                . strtolower((string) $this->CI->router->fetch_method()));
            return ['valid' => FALSE, 'message' => $error];
        }
        return ['valid' => TRUE];
    }

    private function validate_payload(array $payload, $source) {
        $count = 0;
        return $this->walk($payload, 1, $count, NULL, TRUE);
    }

    private function walk(array $values, $depth, &$count, $top_field, $top_level = FALSE) {
        if ($depth > self::MAX_DEPTH) { return 'Struktur input terlalu dalam.'; }
        foreach ($values as $key => $value) {
            $count++;
            if ($count > self::MAX_FIELDS) { return 'Jumlah parameter melebihi batas aman.'; }
            $key = (string) $key;
            if (strlen($key) > self::MAX_KEY_LENGTH || ! preg_match('/^[A-Za-z0-9_.:-]+$/D', $key)) {
                return 'Nama parameter tidak valid.';
            }
            if ($top_level) {
                if (empty($this->allowed[$key])) { return 'Parameter tidak dikenal: ' . $key; }
                $top_field = $key;
            }
            if (is_array($value)) {
                $error = $this->walk($value, $depth + 1, $count, $top_field, FALSE);
                if ($error) { return $error; }
                continue;
            }
            if ( ! is_scalar($value) && $value !== NULL) { return 'Tipe parameter tidak didukung.'; }
            $error = $this->validate_scalar((string) $top_field, (string) $value);
            if ($error) { return $error; }
        }
        return NULL;
    }

    private function validate_scalar($field, $value) {
        if (strlen($value) > self::MAX_TEXT_LENGTH || ! preg_match('//u', $value)) {
            return 'Nilai ' . $field . ' melebihi batas atau bukan UTF-8 valid.';
        }
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            return 'Nilai ' . $field . ' mengandung karakter kontrol terlarang.';
        }
        if ($value === '') { return NULL; }

        // 'step' dan 'langkah' SENGAJA tidak di sini: keduanya slug teks (find_data, housing_family, bnba, isian),
        // bukan angka. Memasukkannya (21 Sep 2026) membuat seluruh wizard warga dan rekam data dijawab 400.
        if (preg_match('/^(?:id|.*_id|reply_to|lock_version|page|hal|limit|per|urutan|kuota|tahun|semester|triwulan|occupant_count|family_count)$/', $field)
            && ! preg_match('/^\d{1,20}$/D', $value)) {
            return 'Nilai numerik ' . $field . ' tidak valid.';
        }
        if (preg_match('/^(?:nik|nik_identitas|nik_ktp)$/', $field) && ! preg_match('/^\d{16}$/D', $value)) {
            return 'Format NIK tidak valid.';
        }
        if ($field === 'family_card_number' && ! preg_match('/^\d{16}$/D', $value)) {
            return 'Format nomor KK tidak valid.';
        }
        if ($field === 'nim' && ! preg_match('/^[A-Za-z0-9 .\/-]{3,30}$/D', $value)) {
            return 'Format NIM tidak valid.';
        }
        if ($field === 'npwp' && ! preg_match('/^[0-9.\- ]{15,24}$/D', $value)) {
            return 'Format NPWP tidak valid.';
        }
        if (in_array($field, ['email', 'email_user', 'footer_email'], TRUE)
            && ! filter_var($value, FILTER_VALIDATE_EMAIL)
            && ! preg_match('/^[A-Za-z0-9._-]{3,100}$/D', $value)) {
            return 'Format email atau username tidak valid.';
        }
        if (preg_match('/^(?:tanggal_lahir|tgl_lahir|birth_date|tanggal_serah_terima|periode_mulai|periode_selesai|sertifikat_terbit|sertifikat_berakhir)$/', $field)
            && ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return 'Format tanggal ' . $field . ' tidak valid.';
        }
        if ($field === 'jadwal_mulai' && ! preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/D', $value)) {
            return 'Format jadwal tidak valid.';
        }
        if (preg_match('/^(?:hp|no_hp|no_whatsapp|phone|telp_kantor|footer_phone)$/', $field)
            && ! preg_match('/^[0-9+(). \-]{7,30}$/D', $value)) {
            return 'Format nomor telepon tidak valid.';
        }
        if (preg_match('/^(?:monthly_income|penghasilan|nilai_anggaran|nilai_padat_karya|rencana_anggaran|realisasi_anggaran|rencana_unit|realisasi_unit|total_luas_ha|house_area_m2|land_length_m|land_width_m|location_accuracy_m|location_lat|location_lng|volume)$/', $field)
            && ! preg_match('/^-?\d{1,15}(?:[.,]\d{1,8})?$/D', $value)) {
            return 'Format angka ' . $field . ' tidak valid.';
        }
        if (preg_match('/^(?:aktif|is_active|status_aktif|ada_penanganan|ada_progres|manual_entry|has_other_land|has_other_house|owns_candidate_land|tampil_korsel|simpan_hasil|srp2_pengembang|tos_agree|pernyataan)$/', $field)
            && ! in_array(strtolower($value), ['0', '1', 'on', 'yes', 'true', 'false', 'ya', 'tidak'], TRUE)) {
            return 'Nilai pilihan ' . $field . ' tidak valid.';
        }
        if ($field === 'website' && ! filter_var($value, FILTER_VALIDATE_URL)) {
            return 'Format URL website tidak valid.';
        }
        return NULL;
    }
}