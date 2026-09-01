<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Push_subscription_model extends CI_Model {

    const TABLE = 'sys_push_subscriptions';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption_lib');
    }

    public function simpan($user_id, array $subscription, $user_agent = NULL)
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $keys = isset($subscription['keys']) && is_array($subscription['keys']) ? $subscription['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));
        if ($endpoint === '' || strlen($endpoint) > 4096 || ! preg_match('#^https://#i', $endpoint)
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

    /** Gabungkan langganan dari beberapa role/scope tanpa menduplikasi perangkat. */
    public function untuk_audiens(array $audiences)
    {
        $rows = [];
        foreach ($audiences as $audience) {
            if (empty($audience['role'])) { continue; }
            $this->db->select('sys_push_subscriptions.*')->from(self::TABLE)
                ->join('usr_users', 'usr_users.id = sys_push_subscriptions.user_id')
                ->where('sys_push_subscriptions.aktif', 1)
                ->where('usr_users.role', $audience['role'])
                ->where("LOWER(TRIM(COALESCE(usr_users.status,''))) !=", 'nonaktif');
            if (isset($audience['kabupaten_id'])) {
                $this->db->where('usr_users.kabupaten_id', (int) $audience['kabupaten_id']);
            }
            if (isset($audience['bidang_kode'])) {
                $this->db->where('usr_users.bidang_kode', (string) $audience['bidang_kode']);
            }
            foreach ($this->db->get()->result_array() as $row) {
                try {
                    $row['endpoint'] = $this->encryption_lib->decrypt($row['endpoint']);
                    $row['public_key'] = $this->encryption_lib->decrypt($row['public_key']);
                    $row['auth_token'] = $this->encryption_lib->decrypt($row['auth_token']);
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
                'gagal_berturut' => 0, 'last_success_at' => $now, 'updated_at' => $now,
            ]);
        }
        $this->db->set('gagal_berturut', 'LEAST(gagal_berturut + 1, 255)', FALSE)
            ->set('last_failure_at', $now)->set('updated_at', $now);
        if ($expired) { $this->db->set('aktif', 0); }
        return $this->db->where('id', (int) $id)->update(self::TABLE);
    }
}
