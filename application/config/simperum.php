<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$mode = getenv('SIMPERUM_MODE') ?: 'simulation';
$config['simperum_mode'] = in_array($mode, ['simulation', 'api'], TRUE) ? $mode : 'simulation';
// Poin 13.3: mode simulasi (data fiktif dari application/fixtures/simperum) adalah fitur SAMPEL untuk
// pengembangan dan uji. Di production ia TIDAK boleh aktif, apa pun isi .env: env yang hilang atau salah
// ketik tidak boleh diam-diam menyajikan data fiktif sebagai hasil pencarian resmi.
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    $config['simperum_mode'] = 'api';
}
$config['simperum_fixture_path'] = APPPATH . 'fixtures/simperum';
$config['simperum_base_url'] = rtrim(getenv('SIMPERUM_BASE_URL') ?: 'https://simperum.disperakim.jatengprov.go.id/api/pub/', '/') . '/';
$config['simperum_public_key'] = trim((string) getenv('SIMPERUM_PUBLIC_KEY'));
$config['simperum_private_key'] = trim((string) getenv('SIMPERUM_PRIVATE_KEY'));
$config['simperum_connect_timeout'] = max(1, min(10, (int) (getenv('SIMPERUM_CONNECT_TIMEOUT') ?: 5)));
$config['simperum_timeout'] = max(2, min(30, (int) (getenv('SIMPERUM_TIMEOUT') ?: 12)));
