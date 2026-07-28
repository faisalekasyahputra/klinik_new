<?php
/**
 * Contract check offline untuk autentikasi dan normalisasi GetDataRTLH.
 *
 * Tidak memakai jaringan, kredensial nyata, DB, atau data warga.
 * Jalankan:
 *   php docs/engineering/uji_simperum_api_contract.php
 */

define('BASEPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
require dirname(__DIR__, 2) . '/application/libraries/Simperum_gateway.php';

$class = new ReflectionClass('Simperum_gateway');
$gateway = $class->newInstanceWithoutConstructor();
$set = static function ($name, $value) use ($class, $gateway) {
    $property = $class->getProperty($name);
    $property->setAccessible(TRUE);
    $property->setValue($gateway, $value);
};
$call = static function ($name, ...$arguments) use ($class, $gateway) {
    $method = $class->getMethod($name);
    $method->setAccessible(TRUE);
    return $method->invoke($gateway, ...$arguments);
};

$set('public_key', 'public-test');
$set('private_key', 'private-test');

$total = 0;
$failed = 0;
$check = static function ($condition, $label) use (&$total, &$failed) {
    $total++;
    echo ($condition ? 'OK    ' : 'GAGAL ') . $label . PHP_EOL;
    if ( ! $condition) {
        $failed++;
    }
};

$command = 'GetDataRTLH?NIK=3374010101900001';
$check(
    $call('authorization', $command) === '283ff9b50564188661403e1bc785dce9.public-test',
    'Token MD5 mencakup perintah dan query NIK persis'
);

$fixture_path = dirname(__DIR__, 2) . '/application/fixtures/simperum/api_contract_get_rtlh.json';
$fixture_json = file_get_contents($fixture_path);
$mapped = $call('map_api_response', '3374010101900001', [
    'http_status' => 200,
    'body' => $fixture_json,
    'curl_errno' => 0,
]);

$check(($mapped['response_status'] ?? '') === 'found', 'Respons sukses menjadi found');
$check(($mapped['source_record_key'] ?? '') === 'SYN-001', 'IDBDT menjadi source record key');
$without_source_id = json_decode($fixture_json, TRUE);
unset($without_source_id['Data'][0]['IDBDT']);
$without_source_id = $call('map_api_response', '3374010101900001', [
    'http_status' => 200,
    'body' => json_encode($without_source_id),
    'curl_errno' => 0,
]);
$check(
    array_key_exists('source_record_key', $without_source_id)
        && $without_source_id['source_record_key'] === NULL,
    'NIK tidak menjadi source record key plaintext saat IDBDT kosong'
);
$check(($mapped['identity']['full_name'] ?? '') === 'WARGA KONTRAK SINTETIS', 'Identitas dipetakan');
$check(($mapped['identity']['gender_code'] ?? '') === 'male', 'Jenis kelamin dipetakan');
$check(($mapped['identity']['education_code'] ?? '') === 'bachelor', 'Pendidikan dipetakan');
$check(($mapped['socioeconomic']['occupation_code'] ?? '') === 'educator', 'Pekerjaan dipetakan');
$check(($mapped['socioeconomic']['income_band_code'] ?? '') === 'gt_4_2', 'Penghasilan dipetakan tanpa mempersempit rentang');
$check(($mapped['socioeconomic']['welfare_decile'] ?? NULL) === NULL, 'Desil tidak dikarang');
$check(($mapped['housing']['land_title_code'] ?? '') === 'certificate_unspecified', 'Jenis sertifikat yang tidak rinci dipertahankan');
$check(($mapped['housing']['assistance_source_code'] ?? '') === 'apbd_prov', 'Sumber bantuan dipetakan');
$check(($mapped['housing']['area_condition_code'] ?? '') === 'good', 'Kawasan dipetakan');
$check(($mapped['structure']['roof_material_code'] ?? '') === 'clay_tile', 'Bahan atap dipetakan');
$check(($mapped['structure']['foundation_condition_code'] ?? '') === 'severe_damage_or_absent', 'Pondasi tidak ada dipetakan konservatif');
$check(($mapped['sanitation']['water_source_code'] ?? '') === 'piped', 'Ledeng tidak ditebak menjadi PDAM');
$check(($mapped['location']['kabupaten_id'] ?? 0) === 3374, 'KodeDagri menjadi scope kabupaten/kota');
$invalid_location = json_decode($fixture_json, TRUE);
$invalid_location['Data'][0]['GeoLat'] = '999';
$check(
    $call('map_api_response', '3374010101900001', ['http_status' => 200, 'body' => json_encode($invalid_location), 'curl_errno' => 0])['location']['location_lat'] === NULL,
    'Koordinat di luar rentang tidak dipakai'
);
$check($call('birth_date_matches', '3374010101900001', '1990-01-01', $mapped), 'Tanggal lahir cocok dengan NIK dan tahun sumber');
$check( ! $call('birth_date_matches', '3374010101900001', '1990-01-02', $mapped), 'Tanggal lahir salah ditolak');

$empty = json_encode(['Success' => TRUE, 'Data' => [], 'Message' => '', 'Type' => 'array']);
$check(
    $call('map_api_response', '3374010101900001', ['http_status' => 200, 'body' => $empty, 'curl_errno' => 0])['response_status'] === 'not_found',
    'Data kosong menjadi not_found'
);
$mismatch = json_decode($fixture_json, TRUE);
$mismatch['Data'][0]['NIK'] = '3374010101909999';
$check(
    ($call('map_api_response', '3374010101900001', ['http_status' => 200, 'body' => json_encode($mismatch), 'curl_errno' => 0])['error_code'] ?? '') === 'api_nik_mismatch',
    'NIK respons berbeda ditolak'
);
$check(
    ($call('map_api_response', '3374010101900001', ['http_status' => 401, 'body' => '{}', 'curl_errno' => 0])['error_code'] ?? '') === 'api_auth_failed',
    '401 menjadi kegagalan autentikasi'
);
$check(
    ($call('map_api_response', '3374010101900001', ['http_status' => 429, 'body' => '{}', 'curl_errno' => 0])['error_code'] ?? '') === 'api_rate_limited',
    '429 menjadi pembatasan sumber'
);
$check(
    ($call('map_api_response', '3374010101900001', ['http_status' => 200, 'body' => '{', 'curl_errno' => 0])['error_code'] ?? '') === 'api_invalid_json',
    'JSON rusak ditolak'
);
$check(
    ($call('map_api_response', '3374010101900001', ['http_status' => 0, 'body' => NULL, 'curl_errno' => 28])['error_code'] ?? '') === 'api_transport_error',
    'Timeout transport menjadi error aman'
);
$check(strpos(json_encode($mapped), 'private-test') === FALSE, 'Payload tidak memuat private key');

echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
