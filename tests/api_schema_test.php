<?php
/**
 * Penjaga regresi validasi skema API dan kontrol anti-otomatisasi API (form keamanan 12.5 dan 12.7).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/api_schema_test.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
require $app . '/helpers/anti_automation_helper.php';
require $app . '/libraries/Api_schema.php';
$config = []; require $app . '/config/api_schemas.php'; $SKEMA = $config['api_schemas']; $EXEMPT = $config['api_schema_exempt']; $COMMON = $config['api_schema_common'];
$config = []; require $app . '/config/rate_limits.php'; $POL = $config['rate_limit_policies'];
$config = []; require $app . '/config/input_validation.php'; $BOLEH = array_flip($config['input_allowed_fields']);

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function sumber($rel) {
    global $app;
    $t = @file_get_contents($app . '/' . $rel);
    if ($t === FALSE) { throw new RuntimeException("Tidak bisa membaca application/$rel"); }
    return $t;
}
$S = new Api_schema();
/** Jalankan validasi untuk "kelas/metode" dengan permintaan ringkas. */
function v($rute, array $req) {
    global $S;
    [$c, $m] = explode('/', $rute);
    $skema = $S->find($c, $m);
    if ($skema === NULL) { throw new RuntimeException("Skema $rute tidak ada"); }
    return $S->validate($skema, $req + ['method' => 'POST', 'get' => [], 'post' => [], 'files' => [], 'segments' => [], 'ajax' => TRUE, 'content_type' => 'application/x-www-form-urlencoded', 'json' => NULL]);
}
function ok($h) { return ! empty($h['ok']); }

// ------------------------------------------------------------------ 1. Mesin aturan (tipe dan rentang)
$r = function ($rule, $nilai) use ($S) { return $S->nilai($rule, $nilai); };
check($r(['type' => 'int', 'min' => 1, 'max' => 10], '5') === NULL, 'int dalam rentang lolos');
check($r(['type' => 'int', 'min' => 1, 'max' => 10], '0') !== NULL && $r(['type' => 'int', 'max' => 10], '11') !== NULL, 'int di luar rentang ditolak');
check($r(['type' => 'int'], '5.5') !== NULL && $r(['type' => 'int'], '5e3') !== NULL && $r(['type' => 'int'], ' 5') !== NULL && $r(['type' => 'int'], '5abc') !== NULL, 'int hanya menerima bilangan bulat murni');
check($r(['type' => 'int'], "5\n") !== NULL, 'int menolak baris baru di akhir (bukan sekadar $)');
check($r(['type' => 'float', 'min' => 0, 'max' => 1], '0,5') === NULL && $r(['type' => 'float'], 'abc') !== NULL, 'float menerima koma desimal dan menolak huruf');
check($r(['type' => 'bool'], 'on') === NULL && $r(['type' => 'bool'], 'mungkin') !== NULL, 'bool hanya nilai boolean yang dikenal');
check($r(['type' => 'enum', 'values' => ['a', 'b']], 'a') === NULL && $r(['type' => 'enum', 'values' => ['a', 'b']], 'c') !== NULL, 'enum dibatasi pada nilai yang diizinkan');
check($r(['type' => 'enum', 'values' => ['0', '1']], '00') !== NULL, 'enum membandingkan string persis');
check($r(['type' => 'date'], '2026-02-28') === NULL && $r(['type' => 'date'], '2026-02-31') !== NULL && $r(['type' => 'date'], '28-02-2026') !== NULL, 'date harus tanggal kalender yang sah');
check($r(['type' => 'url', 'https_only' => TRUE], 'https://push.example/abc') === NULL && $r(['type' => 'url', 'https_only' => TRUE], 'http://push.example/abc') !== NULL && $r(['type' => 'url'], 'javascript:alert(1)') !== NULL, 'url: https_only dan skema berbahaya ditolak');
check($r(['type' => 'string', 'min_len' => 2, 'max_len' => 4], 'abc') === NULL && $r(['type' => 'string', 'max_len' => 4], 'abcde') !== NULL && $r(['type' => 'string', 'min_len' => 2], 'a') !== NULL, 'string: panjang minimum dan maksimum');
check($r(['type' => 'string', 'max_len' => 3], 'ééé') === NULL && $r(['type' => 'string', 'max_len' => 3], 'éééé') !== NULL, 'panjang dihitung dalam karakter, bukan byte');
check($r(['type' => 'string', 'pattern' => '/^\d{16}$/D'], "1234567890123456\n") !== NULL, 'pola dengan /D menolak baris baru di akhir');
check($r(['type' => 'string'], "\xff\xfe") !== NULL, 'string non-UTF-8 ditolak');
check($r(['type' => 'string'], ['a']) !== NULL && $r(['type' => 'int'], ['1']) !== NULL, 'larik di tempat skalar ditolak (kebingungan tipe)');
$struktur = FALSE; $S->nilai(['type' => 'string'], ['a'], $struktur);
check($struktur === TRUE, 'larik di tempat skalar ditandai struktural');
$struktur = FALSE; $S->nilai(['type' => 'int'], 'abc', $struktur);
check($struktur === FALSE, 'salah isi biasa TIDAK ditandai struktural (tidak memicu peringatan keamanan)');

// Pesan galat tidak boleh memantulkan nilai yang dikirim, untuk SETIAP jenis aturan.
foreach ([['type' => 'int', 'min' => 1], ['type' => 'float'], ['type' => 'bool'], ['type' => 'enum', 'values' => ['a']], ['type' => 'date'],
          ['type' => 'url', 'https_only' => TRUE], ['type' => 'string', 'max_len' => 3], ['type' => 'string', 'pattern' => '/^\d+$/D'],
          ['type' => 'object', 'json' => TRUE, 'fields' => ['a' => ['type' => 'int']]]] as $i => $aturan) {
    foreach (['SECRETXYZ', '{"a":"SECRETXYZ"}'] as $nilai) {
        $pesan = $r($aturan, $nilai);
        check($pesan === NULL || strpos($pesan, 'SECRET') === FALSE, "Pesan galat aturan #$i memantulkan nilai yang dikirim: $pesan");
    }
}

// Objek JSON bersarang.
$sub = ['type' => 'object', 'json' => TRUE, 'unknown' => 'reject', 'fields' => ['a' => ['type' => 'int', 'required' => TRUE], 'b' => ['type' => 'object', 'fields' => ['c' => ['type' => 'string', 'max_len' => 3]]]]];
check($r($sub, '{"a":1,"b":{"c":"xyz"}}') === NULL, 'objek bersarang sah lolos');
check($r($sub, '{"b":{"c":"xyz"}}') !== NULL, 'field wajib di objek bersarang ditegakkan');
check($r($sub, '{"a":1,"b":{"c":"terlalu panjang"}}') !== NULL, 'aturan di dalam objek bersarang ditegakkan');
check($r($sub, '{"a":1,"x":2}') !== NULL, 'field asing di objek bersarang ditolak bila unknown=reject');
check($r($sub, 'bukan json') !== NULL && $r($sub, '[1,2]') !== NULL && $r($sub, '"teks"') !== NULL, 'JSON rusak, larik, atau skalar ditolak sebagai objek');
check($r($sub, str_repeat('{"a":', 10) . '1' . str_repeat('}', 10)) !== NULL, 'JSON yang terlalu dalam ditolak');
check($r(['type' => 'object', 'json' => TRUE, 'max_bytes' => 20], '{"a":"' . str_repeat('x', 50) . '"}') !== NULL, 'JSON melebihi max_bytes ditolak');
check($r(['type' => 'array', 'max_items' => 2, 'items' => ['type' => 'int']], ['1', '2']) === NULL && $r(['type' => 'array', 'max_items' => 2, 'items' => ['type' => 'int']], ['1', '2', '3']) !== NULL && $r(['type' => 'array', 'items' => ['type' => 'int']], ['1', 'x']) !== NULL, 'larik: batas elemen dan aturan elemen');

// ------------------------------------------------------------------ 2. validate(): metode, XHR, Content-Type, field, segmen, berkas
$sim = ['nik' => '3374010101900001', 'tgl_lahir' => '1990-01-01'];
check(ok(v('program/api_cek_simperum', ['post' => $sim])), 'Permintaan SIMPERUM sah lolos');
$h = v('program/api_cek_simperum', ['method' => 'GET']);
check( ! ok($h) && $h['status'] === 405 && $h['allow'] === ['POST'], 'Metode salah = 405 dengan daftar Allow');
$h = v('program/api_cek_simperum', ['post' => $sim, 'ajax' => FALSE]);
check($h['status'] === 400 && $h['code'] === 'ajax_required', 'Endpoint XHR-saja menolak permintaan non-XHR');
$h = v('program/api_cek_simperum', ['post' => $sim, 'content_type' => 'application/json']);
check($h['status'] === 415, 'Badan JSON ke endpoint formulir = 415');
$h = v('program/api_cek_simperum', ['post' => ['nik' => '3374010101900001']]);
check($h['status'] === 422 && isset($h['errors']['tgl_lahir']) && $h['structural'] === FALSE, 'Field wajib hilang = 422 non-struktural');
$h = v('program/api_cek_simperum', ['post' => $sim + ['keyword' => 'x']]);
check($h['status'] === 422 && isset($h['errors']['keyword']) && $h['structural'] === TRUE, 'Field yang tidak dideklarasikan ditolak dan struktural');
$h = v('program/api_cek_simperum', ['post' => ['nik' => ['3374010101900001']] + $sim]);
check($h['structural'] === TRUE && isset($h['errors']['nik']), 'NIK berbentuk larik ditolak dan struktural');
check(ok(v('program/api_cek_simperum', ['post' => $sim + ['csrf_kpkp_token' => 'abc']])), 'Token CSRF selalu boleh ada');
$h = v('program/api_cek_simperum', ['post' => ['nik' => 'SECRET-NIK-9999', 'tgl_lahir' => 'SECRET-TGL']]);
check( ! ok($h) && strpos(json_encode($h), 'SECRET') === FALSE, 'Pesan galat TIDAK memantulkan nilai yang dikirim');
$h = v('program/api_cek_simperum', ['post' => $sim, 'files' => ['form_1']]);
check($h['structural'] === TRUE && isset($h['errors']['_berkas']), 'Endpoint tanpa dukungan berkas menolak unggahan');

$kal = ['penghasilan' => '2500000', 'pekerjaan' => 'Wiraswasta', 'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'Butuh rumah layak', 'kabupaten_id' => '3374', 'kode_program_target' => 'umum', 'simpan_hasil' => '0'];
check(ok(v('program/api_kalkulasi_program', ['post' => $kal])), 'Kalkulasi program sah lolos');
foreach ([['penghasilan', '-1'], ['penghasilan', '100000001'], ['penghasilan', 'banyak'], ['pekerjaan', 'Presiden'], ['status_kepemilikan', 'Punya Istana'], ['alasan_pengajuan', ''], ['alasan_pengajuan', str_repeat('x', 5001)], ['kode_program_target', 'a b;'], ['simpan_hasil', '2']] as [$f, $nilai]) {
    check( ! ok(v('program/api_kalkulasi_program', ['post' => [$f => $nilai] + $kal])), "Kalkulasi: $f=$nilai harus ditolak");
}
check( ! ok(v('program/api_kalkulasi_program', ['post' => array_diff_key($kal, ['pekerjaan' => 1])])), 'Kalkulasi tanpa pekerjaan ditolak');

$sub_ok = json_encode(['endpoint' => 'https://push.example/abc', 'keys' => ['p256dh' => 'BPa_-Z+/=9', 'auth' => 'aB-_9=']]);
check(ok(v('push/subscribe', ['post' => ['subscription' => $sub_ok]])), 'Langganan Web Push sah (JSON bersarang) lolos');
foreach ([
    json_encode(['endpoint' => 'http://push.example/abc', 'keys' => ['p256dh' => 'a', 'auth' => 'b']]),
    json_encode(['endpoint' => 'https://push.example/abc']),
    json_encode(['endpoint' => 'https://push.example/abc', 'keys' => ['p256dh' => '<script>', 'auth' => 'b']]),
    json_encode(['endpoint' => 'https://push.example/' . str_repeat('a', 5000), 'keys' => ['p256dh' => 'a', 'auth' => 'b']]),
    '[]', 'bukan json',
] as $i => $salah) { check( ! ok(v('push/subscribe', ['post' => ['subscription' => $salah]])), "Langganan Web Push cacat #$i harus ditolak"); }
check(ok(v('push/unsubscribe', ['post' => ['endpoint' => 'https://push.example/abc']])) && ! ok(v('push/unsubscribe', ['post' => ['endpoint' => 'ftp://x']])), 'unsubscribe: endpoint https wajib');
check(ok(v('push/config', ['method' => 'GET'])) && v('push/config', ['method' => 'POST'])['status'] === 405, 'push/config hanya GET');

check(ok(v('umum/toggle_like', ['post' => ['type' => 'diskusi', 'id' => '12']])) && ! ok(v('umum/toggle_like', ['post' => ['type' => 'komentar', 'id' => '0']])) && ! ok(v('umum/toggle_like', ['post' => ['type' => 'x', 'id' => '1']])), 'toggle_like: type enum dan id positif');
check(ok(v('umum/report_komentar', ['post' => ['id' => '5']])) && ! ok(v('umum/report_komentar', ['post' => ['id' => '5; DROP']])), 'report_komentar: id integer');

$login = ['email' => 'a@b.id', 'password' => 'x', 'bot_token' => 'tok.en', 'situs_web' => '', 'redirect_to' => '/akun'];
check(ok(v('auth/do_login', ['post' => $login, 'ajax' => FALSE])), 'Login sah (tanpa XHR) lolos');
check( ! ok(v('auth/do_login', ['post' => $login + ['nik' => '1'], 'ajax' => FALSE])) && ! ok(v('auth/do_login', ['post' => ['email' => 'a@b.id'], 'ajax' => FALSE])), 'Login: field asing dan sandi hilang ditolak');
check(ok(v('auth/do_register', ['post' => ['email' => 'a@b.id', 'password' => 'x', 'password_confirm' => 'x', 'tos_agree' => 'on', 'srp2_pengembang' => '1', 'nama_perusahaan' => 'PT A', 'bot_token' => 't', 'situs_web' => ''], 'ajax' => FALSE])), 'Registrasi sah lolos');

check(ok(v('pengembang/simpan_dokumen', ['segments' => ['7'], 'files' => ['form_1', 'form_6b'], 'post' => ['return_to' => 'dashboard']])), 'Unggah dokumen SRP2 sah lolos');
foreach ([['segments' => ['abc']], ['segments' => ['0']], ['segments' => []], ['segments' => ['7', 'x']], ['segments' => ['7'], 'files' => ['file_ktp']], ['segments' => ['7'], 'files' => ['../x']], ['segments' => ['7'], 'post' => ['return_to' => 'evil']]] as $i => $req) {
    check( ! ok(v('pengembang/simpan_dokumen', $req)), "Unggah dokumen SRP2 cacat #$i harus ditolak");
}
check(count($SKEMA['pengembang/simpan_dokumen']['methods']['POST']['files']) === 2 && $SKEMA['pengembang/simpan_dokumen']['methods']['POST']['files']['max'] >= 14, 'Batas kolom berkas mencakup 14 dokumen SRP2');

check(ok(v('kemitraanportal/cek_sertifikat_kkn', ['post' => ['nim' => 'A12345'], 'ajax' => FALSE])) && ! ok(v('kemitraanportal/cek_sertifikat_kkn', ['post' => ['nim' => 'A12/45'], 'ajax' => FALSE])), 'Pencarian sertifikat: NIM alfanumerik');
check(ok(v('kemitraanportal/cek_sertifikat_kkn', ['method' => 'GET', 'ajax' => FALSE])), 'GET ke pencarian sertifikat tetap sampai ke handler (dialihkan ke formulir)');
check(isset($SKEMA['kemitraanportal/cek_sertifikat_kkn']['invalid']['redirect']), 'Formulir peramban punya pengalihan sendiri saat isian ditolak');

$cari = ['kodeWilayah' => '3374', 'keyword' => 'griya', 'searchBy' => 'nama-perumahan', 'sort' => 'terbaru', 'status_rumah' => 'subsidi', 'page' => '2', 'limit' => '12', '_' => '1758000000000'];
check(ok(v('index/cari_wil', ['method' => 'GET', 'get' => $cari, 'ajax' => FALSE])), 'Pencarian wilayah sah (parameter persis milik klien) lolos');
foreach ([['kodeWilayah' => '33;DROP'], ['page' => '-1'], ['limit' => '9999'], ['keyword' => str_repeat('k', 101)], ['sort' => 'a b'], ['foo' => 'bar']] as $i => $salah) {
    check( ! ok(v('index/cari_wil', ['method' => 'GET', 'get' => $salah + $cari, 'ajax' => FALSE])), "Pencarian wilayah cacat #$i harus ditolak");
}
check(ok(v('admin/update_status', ['post' => ['queue_id' => '5', 'from_status' => 'Baru', 'status' => 'Diterima', 'catatan_admin' => 'ok'], 'ajax' => FALSE])) && ! ok(v('admin/update_status', ['post' => ['queue_id' => 'x', 'status' => 'Diterima'], 'ajax' => FALSE])), 'Keputusan antrean admin: queue_id integer dan status wajib');

// ------------------------------------------------------------------ 3. Registri: konsisten dengan sisa aplikasi
$dilihat = 0;
foreach ($SKEMA as $rute => $skema) {
    [$c, $m] = explode('/', $rute);
    check($rute === strtolower($rute), "Kunci skema $rute harus huruf kecil");
    $berkas = null;
    foreach (glob($app . '/controllers/*.php') as $f) { if (strtolower(basename($f, '.php')) === $c) { $berkas = $f; } }
    check($berkas !== null, "Skema $rute menunjuk controller yang tidak ada");
    check(preg_match('/function\s+' . preg_quote($m, '/') . '\s*\(/i', file_get_contents($berkas)) === 1, "Skema $rute menunjuk metode yang tidak ada");
    check( ! empty($skema['methods']) && is_array($skema['methods']), "Skema $rute wajib mendeklarasikan metode");
    check( ! empty($skema['class']), "Skema $rute wajib menyebut kelas batas laju");
    check(isset($POL['kelas_' . $skema['class'] . '_ip'], $POL['kelas_' . $skema['class'] . '_akun']), "Kelas {$skema['class']} pada $rute harus punya kebijakan _ip dan _akun");
    check(in_array($skema['class'], anti_automation_route_classes($c, $m), TRUE), "Endpoint $rute harus otomatis masuk kelas laju {$skema['class']}");
    check(anti_automation_route_is_json($c, $m) === ! empty($skema['json']), "Penanda JSON $rute harus konsisten dengan helper");
    foreach ($skema['methods'] as $metode => $spec) {
        check(in_array($metode, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], TRUE), "Metode $metode pada $rute tidak dikenal");
        foreach (array_keys($spec['fields'] ?? []) as $f) {
            check(isset($BOLEH[$f]) || in_array($f, $COMMON, TRUE) || $f === '_', "Field '$f' di skema $rute belum ada di allowlist Input_guard (akan ditolak 400 sebelum skema)");
        }
        if (($spec['unknown'] ?? 'reject') === 'reject' && (empty($spec['fields']) && ! isset($spec['files'])) && $metode !== 'GET') {
            // tanpa field dan reject: memang endpoint tanpa masukan (do_verify_email); tidak apa-apa
        }
    }
    $dilihat++;
}
check($dilihat >= 15, 'Jumlah skema terlalu sedikit');
check(anti_automation_route_classes('Index', 'cari_wil') === ['cari'] && anti_automation_route_classes('Auth', 'login') === [], 'Kelas laju lama tidak berubah oleh registri skema');

// Setiap metode publik yang menghasilkan JSON terdaftar (atau dikecualikan dengan alasan).
$json_ditemukan = [];
foreach (glob($app . '/controllers/*.php') as $f) {
    $src = file_get_contents($f); $kelas = strtolower(basename($f, '.php'));
    if ( ! preg_match_all('/\R\s*(public|protected|private)?\s*function\s+(\w+)\s*\(/', $src, $mm, PREG_OFFSET_CAPTURE)) { continue; }
    foreach ($mm[2] as $i => [$nama, $pos]) {
        $vis = $mm[1][$i][0];
        if ($vis === 'private' || $vis === 'protected' || $nama[0] === '_' || $nama === '__construct') { continue; }
        $body = substr($src, $pos, ($mm[2][$i + 1][1] ?? strlen($src)) - $pos);
        if (preg_match('/application\/json|json_encode\(|\$this->json\(/', $body)) { $json_ditemukan[$kelas . '/' . strtolower($nama)] = TRUE; }
    }
}
check(count($json_ditemukan) >= 15, 'Detektor endpoint JSON menemukan terlalu sedikit (' . count($json_ditemukan) . ')');
$belum = array_values(array_filter(array_keys($json_ditemukan), function ($k) use ($SKEMA, $EXEMPT) { return ! isset($SKEMA[$k]) && ! isset($EXEMPT[$k]); }));
check($belum === [], 'Endpoint yang menghasilkan JSON tapi tanpa skema dan tanpa pengecualian beralasan: ' . implode(', ', $belum));
foreach ($EXEMPT as $k => $alasan) {
    check(strlen(trim($alasan)) > 20, "Pengecualian $k wajib beralasan");
    check( ! isset($SKEMA[$k]), "$k tidak boleh ada di skema DAN pengecualian sekaligus");
}

// ------------------------------------------------------------------ 4. Pemasangan
$my = sumber('core/MY_Controller.php');
check(preg_match('/function __construct\(\)\s*\{(.*?)\R    \}/s', $my, $ktor) === 1
    && strpos($ktor[1], '$this->enforce_anti_automation();') !== FALSE
    && strpos($ktor[1], '$this->enforce_api_schema();') !== FALSE
    && strpos($ktor[1], '$this->enforce_anti_automation();') < strpos($ktor[1], '$this->enforce_api_schema();')
    && strpos($ktor[1], '$this->enforce_api_schema();') < strpos($ktor[1], '$this->usir_kalau_nonaktif();'),
    'Konstruktor MY_Controller harus memanggil enforce_api_schema() sesudah anti-otomatisasi dan sebelum logika sesi/akun');
check(preg_match('/function enforce_api_schema\(\).*?catch \(Throwable.*?schema_error/s', $my) === 1, 'Galat validator skema harus menolak permintaan (fail-closed)');
check(strpos($my, "'skema_tidak_valid'") !== FALSE && strpos($my, "'structural'") !== FALSE, 'Pelanggaran struktural harus menjadi peringatan keamanan');
check(strpos($my, 'anti_automation_route_is_json(') !== FALSE, 'Penolakan laju endpoint API terdaftar harus berbentuk JSON walau bukan XHR');
check(strpos($my, "set_header('Allow: '") !== FALSE, '405 harus menyertakan header Allow');

echo "api_schema_test: OK ($total pemeriksaan, " . count($SKEMA) . " skema endpoint, " . count($json_ditemukan) . " metode JSON terdeteksi semuanya tercakup)\n";
