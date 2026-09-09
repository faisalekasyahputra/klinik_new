<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class Web_push_service {

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Push_subscription_model', 'push_subscriptions');
    }

    public function public_key()
    {
        return trim((string) getenv('WEB_PUSH_VAPID_PUBLIC_KEY'));
    }

    public function configured()
    {
        return $this->public_key() !== '' && trim((string) getenv('WEB_PUSH_VAPID_PRIVATE_KEY')) !== ''
            && class_exists(WebPush::class);
    }

    /** Payload wajib generik: jangan masukkan PII ke title/body/url. */
    public function notify(array $audiences, $title, $body, $url, $tag)
    {
        if ( ! $this->configured()) { return ['sent' => 0, 'failed' => 0, 'skipped' => TRUE]; }
        $subscriptions = $this->CI->push_subscriptions->untuk_audiens($audiences);
        if (empty($subscriptions)) { return ['sent' => 0, 'failed' => 0]; }

        $auth = ['VAPID' => [
            'subject'    => trim((string) (getenv('WEB_PUSH_VAPID_SUBJECT') ?: 'mailto:admin@localhost')),
            'publicKey'  => $this->public_key(),
            'privateKey' => trim((string) getenv('WEB_PUSH_VAPID_PRIVATE_KEY')),
        ]];
        try {
            $webPush = new WebPush($auth, ['TTL' => 3600, 'urgency' => 'normal'], 20, ['timeout' => 8, 'connect_timeout' => 4]);
            $webPush->setReuseVAPIDHeaders(TRUE);
            $ids = [];
            $payload = json_encode([
                'title' => mb_substr((string) $title, 0, 100),
                'body'  => mb_substr((string) $body, 0, 220),
                'url'   => base_url(ltrim((string) $url, '/')),
                'tag'   => preg_replace('/[^a-z0-9_-]/i', '-', (string) $tag),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            foreach ($subscriptions as $row) {
                $subscription = Subscription::create([
                    'endpoint' => $row['endpoint'],
                    'publicKey' => $row['public_key'],
                    'authToken' => $row['auth_token'],
                    'contentEncoding' => $row['content_encoding'],
                ]);
                $webPush->queueNotification($subscription, $payload);
                $ids[$row['endpoint']] = (int) $row['id'];
            }
            $sent = $failed = 0;
            foreach ($webPush->flush() as $report) {
                $endpoint = (string) $report->getRequest()->getUri();
                $id = $ids[$endpoint] ?? NULL;
                if ($report->isSuccess()) { $sent++; } else { $failed++; }
                if ($id) { $this->CI->push_subscriptions->catat_hasil($id, $report->isSuccess(), $report->isSubscriptionExpired()); }
                if ( ! $report->isSuccess()) { log_message('error', 'Web Push gagal: ' . $report->getReason()); }
            }
            return ['sent' => $sent, 'failed' => $failed];
        } catch (Throwable $e) {
            log_message('error', 'Web Push exception: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => count($subscriptions)];
        }
    }
}
