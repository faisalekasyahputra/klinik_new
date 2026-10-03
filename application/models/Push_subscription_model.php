<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Push_subscription_model extends CI_Model {

    const TABLE = 'sys_langganan_notifikasi';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption_lib');
    }

    /**
     * Endpoint hanya boleh milik layanan Web Push peramban: FCM (Chrome, Edge berbasis Chromium, Android),
     * Mozilla autopush (Firefox), Apple (Safari), dan WNS (Windows). Https, tanpa kredensial di URL, port
     * bawaan, host berupa nama (bukan IP). Tanpa ini endpoint bebas membuat server menembak alamat
     * pilihan pengguna setiap kali notifikasi dikirim.
     */
    public static function endpoint_sah($url)
    {
        $p = parse_url((string) $url);
        if ( ! is_array($p) || strtolower($p['scheme'] ?? '') !== 'https' || empty($p['host'])
            || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && (int) $p['port'] !== 443)) {
            return FALSE;
        }
        $host = rtrim(strtolower($p['host']), '.');
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)) { return FALSE; }
        if (in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'], TRUE)) {
            return TRUE;
        }
        return (bool) preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.(push\.apple\.com|notify\.windows\.com)$/', $host);
    }

    public function simpan($user_id, array $subscription, $user_agent = NULL)
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $keys = isset($subscription['keys']) && is_array($subscription['keys']) ? $subscription['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));
        if ($endpoint === '' || strlen($endpoint) > 4096 || ! self::endpoint_sah($endpoint)
            || $p256dh === '' || strlen($p256dh) > 255 || $auth === '' || strlen($auth) > 255) {
            return ['success' => FALSE, 'message' => 'Data langganan perangkat tidak valid.'];
        }

        $hash = hash('sha256', $endpoint);
        $now = date('Y-m-d H:i:s');
        try {
            // Ketiganya membentuk kredensial langganan. Hash endpoint tetap
            // tersedia untuk lookup tanpa membuka ciphertext.
            $encrypted_endpoint = $this->encryption_lib->encrypt($endpoint);
            $encrypted_public_key = $this->encryption_lib->encrypt($p256dh);
            $encrypted_auth = $this->encryption_lib->encrypt($auth);
        } catch (Throwable $e) {
            log_message('error', 'Kredensial Web Push gagal dienkripsi: ' . $e->getMessage());
            return ['success' => FALSE, 'message' => 'Perangkat belum dapat didaftarkan dengan aman.'];
        }
        $data = [
            'user_id'          => (int) $user_id,
            'endpoint'         => $encrypted_endpoint,
            'public_key'       => $encrypted_public_key,
            'auth_token'       => $encrypted_auth,
            'content_encoding' => in_array(($subscription['contentEncoding'] ?? ''), ['aes128gcm', 'aesgcm'], TRUE)
                ? $subscription['contentEncoding'] : 'aes128gcm',
            'user_agent'       => mb_substr((string) $user_agent, 0, 255),
            'aktif'            => 1,
            'gagal_berturut'   => 0,
            'updated_at'       => $now,
        ];
        $existing = $this->db->select('id')->get_where(self::TABLE, ['endpoint_hash' => $hash])->row();
        if ($existing) {
            $ok = $this->db->where('id', $existing->id)->update(self::TABLE, $data);
        } else {
            $data['endpoint_hash'] = $hash;
            $data['created_at'] = $now;
            $ok = $this->db->insert(self::TABLE, $data);
        }
        return ['success' => (bool) $ok, 'message' => $ok ? 'Perangkat berhasil didaftarkan.' : 'Perangkat belum dapat didaftarkan.'];
    }

    public function nonaktifkan_milik($user_id, $endpoint)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->where('endpoint_hash', hash('sha256', trim((string) $endpoint)))
            ->update(self::TABLE, ['aktif' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Gabungkan langganan dari beberapa role/scope tanpa menduplikasi perangkat.
     * Satu audiens = role (opsional disaring kabupaten_id/bidang_kode) ATAU satu akun lewat user_id.
     */
    public function untuk_audiens(array $audiences)
    {
        $rows = [];
        foreach ($audiences as $audience) {
            if (empty($audience['role']) && empty($audience['user_id'])) { continue; }
            $this->db->select('sys_langganan_notifikasi.*')->from(self::TABLE)
                ->join('usr_akun', 'usr_akun.id = sys_langganan_notifikasi.user_id')
                ->where('sys_langganan_notifikasi.aktif', 1)
                ->where("LOWER(TRIM(COALESCE(usr_akun.status,''))) !=", 'nonaktif');
            if ( ! empty($audience['role'])) {
                $this->db->where('usr_akun.peran', $audience['role']);
            }
            if ( ! empty($audience['user_id'])) {
                $this->db->where('usr_akun.id', (int) $audience['user_id']);
            }
            if (isset($audience['kabupaten_id'])) {
                $this->db->where('usr_akun.kabupaten_id', (int) $audience['kabupaten_id']);
            }
            if (isset($audience['bidang_kode'])) {
                $this->db->where('usr_akun.bidang_kode', (string) $audience['bidang_kode']);
            }
            foreach ($this->db->get()->result_array() as $row) {
                try {
                    $row['endpoint'] = $this->encryption_lib->decrypt($row['endpoint']);
                    $row['public_key'] = $this->encryption_lib->decrypt($row['public_key']);
                    $row['auth_token'] = $this->encryption_lib->decrypt($row['auth_token']);
                    // Langganan lama dari sebelum daftar izin host tidak pernah ditembak.
                    if ( ! self::endpoint_sah($row['endpoint'])) { continue; }
                    $rows[(int) $row['id']] = $row;
                } catch (Throwable $e) {
                    log_message('error', 'Langganan Web Push #' . (int) $row['id']
                        . ' gagal dibuka dan dilewati: ' . $e->getMessage());
                }
            }
        }
        return array_values($rows);
    }

    public function catat_hasil($id, $success, $expired = FALSE)
    {
        $now = date('Y-m-d H:i:s');
        if ($success) {
            return $this->db->where('id', (int) $id)->update(self::TABLE, [
                'gagal_berturut' => 0, 'terakhir_berhasil_at' => $now, 'updated_at' => $now,
            ]);
        }
        $this->db->set('gagal_berturut', 'LEAST(gagal_berturut + 1, 255)', FALSE)
            ->set('terakhir_gagal_at', $now)->set('updated_at', $now);
        if ($expired) { $this->db->set('aktif', 0); }
        return $this->db->where('id', (int) $id)->update(self::TABLE);
    }
}
