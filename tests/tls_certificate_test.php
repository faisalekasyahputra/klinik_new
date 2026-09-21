<?php
/** Verifies that the public Klinik PKP endpoint presents a trusted, valid TLS certificate. */
$host = 'floralwhite-lion-710022.hostingersite.com';
$context = stream_context_create(['ssl' => [
    'verify_peer' => true,
    'verify_peer_name' => true,
    'peer_name' => $host,
    'capture_peer_cert' => true,
    'SNI_enabled' => true,
    'SNI_server_name' => $host,
]]);
$socket = @stream_socket_client('ssl://' . $host . ':443', $errno, $error, 15, STREAM_CLIENT_CONNECT, $context);
if (!$socket) {
    fwrite(STDERR, "TLS connection failed: {$error} ({$errno})\n");
    exit(1);
}
$params = stream_context_get_params($socket);
fclose($socket);
$certificate = $params['options']['ssl']['peer_certificate'] ?? null;
$details = $certificate ? openssl_x509_parse($certificate) : false;
if (!$details || empty($details['validTo_time_t']) || $details['validTo_time_t'] <= time()) {
    fwrite(STDERR, "Missing or expired TLS certificate.\n");
    exit(1);
}
echo 'Trusted TLS certificate valid until ' . gmdate(DATE_ATOM, $details['validTo_time_t']) . ".\n";
