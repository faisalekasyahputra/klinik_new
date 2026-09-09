<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$kpkp_notifications = [];
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $message = $this->session->flashdata($type);
    if (is_scalar($message) && trim((string) $message) !== '') {
        $kpkp_notifications[] = ['type' => $type, 'message' => strip_tags((string) $message)];
    }
}
$kpkp_notifications_json = json_encode(
    $kpkp_notifications,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<div class="kpkp-notification-region" data-kpkp-notification-region aria-label="Notifikasi" aria-live="polite" aria-relevant="additions text"></div>
<script type="application/json" data-kpkp-flash-notifications><?= $kpkp_notifications_json ?: '[]' ?></script>
