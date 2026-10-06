<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji penamaan Bahasa Indonesia (migrasi 072):
 *
 *   php docs/engineering/uji_penamaan_indonesia.php
 *
 * - Skema: 14 tabel dan 181 kolom bernama baru, nama lama tidak ada lagi, setiap tabel punya
 *   COMMENT, CHECK 071 pada tabel riwayat menyebut kolom baru, Migrate::status melaporkan 072.
 * - Pemindai kode application/ (tanpa migrations/, cache/, logs/, dan helpers/kunci_tersimpan_helper.php
 *   yang memang menyimpan peta kunci lama):
 *     * nama tabel lama dan nama kolom lama yang KHAS (bukan kata umum) merah di mana pun;
 *     * nama kolom lama yang UMUM (name, role, type, message, ...) merah bila muncul sebagai
 *       argumen pertama query builder, di dalam string SQL mentah, atau sebagai properti baris
 *       `->nama`. Kunci sesi (`userdata('role')`), nama isian formulir (`post('name')`), dan
 *       kunci respons JSON sengaja tetap; itu bukan kolom.
 * - Peta kunci JSON tersimpan (kunci_tersimpan_helper) bijektif dan sama dengan peta 072.
 * - Swauji: pemindai dijalankan pada potongan kode sintetis yang memuat nama lama dan HARUS
 *   menangkapnya, jadi suite ini terbukti bisa merah.
 *
 * Tidak menulis data.
 */
define('BASEPATH', 'uji');
$AKAR = dirname(__DIR__, 2);
$env = [];
foreach (file(env_berkas_path($AKAR), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$total = 0; $gagal = 0;
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };

if ( ! class_exists('CI_Migration')) { class CI_Migration {} }
require $AKAR . '/application/migrations/20260701000071_status_tertutup_tabel_mati.php';
require $AKAR . '/application/migrations/20260701000072_penamaan_indonesia.php';
require $AKAR . '/application/helpers/kunci_tersimpan_helper.php';
$M = 'Migration_Penamaan_indonesia';

/** Nama kolom lama yang juga kata umum: hanya diperiksa di konteks SQL/query builder/properti baris. */
const UMUM = ['name', 'username', 'password', 'avatar', 'role', 'phone', 'type', 'allowed', 'sender', 'message',
    'note', 'badge', 'is_active', 'ip_address', 'file_name', 'file_path', 'file_size', 'original_name',
    'error_code', 'bidang', 'is_deleted', 'id_diskusi', 'id_komentar', 'key_name', 'key_value', 'limit_key'];
/** Metode query builder CI yang argumen pertamanya nama kolom (atau daftar kolom). */
const QB = 'where|or_where|where_in|or_where_in|where_not_in|or_where_not_in|like|or_like|not_like|or_not_like|'
    . 'order_by|group_by|having|or_having|select|select_max|select_min|select_sum|select_avg|set|distinct';

/**
 * Pindai satu teks. $khas: regex nama khas (kata utuh di mana pun). $umum: daftar nama umum.
 * Mengembalikan daftar "baris: potongan" yang melanggar.
 */
function pindai($teks, $khas, array $umum)
{
    $temuan = [];
    $u = implode('|', array_map('preg_quote', $umum));
    foreach (explode("\n", $teks) as $i => $baris) {
        $no = $i + 1;
        if (preg_match($khas, $baris, $m)) { $temuan[] = $no . ': ' . $m[0]; continue; }
        // ->kolom pada baris hasil query (bukan pemanggilan metode, bukan properti kelas $this->x).
        if (preg_match('/(?<!\$this)->(' . $u . ')\b(?!\s*\()/', $baris, $m)) { $temuan[] = $no . ': ' . $m[0]; continue; }
        // Argumen pertama query builder: ->where('role', ...), ->select('id, name'), ->order_by('u.name').
        if (preg_match_all('/->(?:' . QB . ')\(\s*([\'"])(.*?)\1/', $baris, $mm)) {
            foreach ($mm[2] as $arg) {
                if (preg_match('/(?<![\w$.])(?:\w+\.)?(' . $u . ')\b(?![.(]|\s*\()/', $arg, $m)) { $temuan[] = $no . ': ' . $m[0]; continue 2; }
            }
        }
        // get_where/insert/update dengan array kolom literal pada baris yang sama.
        if (preg_match('/->(?:get_where|insert|update|replace|delete)\(\s*\'[a-z0-9_]+\'\s*,\s*\[([^\]]*)\]/', $baris, $mm)
            && preg_match('/[\'"](' . $u . ')[\'"]\s*=>/', $mm[1], $m)) { $temuan[] = $no . ': ' . $m[0]; continue; }
        // Potongan WHERE di literal: 'bidang IS NULL', "u.role = 'x'", 'is_active >= 1'. Atribut HTML
        // (x-show="role == ...") dan perbandingan JS (==, ===) bukan SQL.
        if (preg_match('/(?<!=)[\'"](?:\w+\.)?(' . $u . ')\s*(?:IS\s+(?:NOT\s+)?NULL|=(?![=>])|<>|!=(?!=)|>=|<=|IN\s*\(|LIKE\b)/i', $baris, $m)) {
            $temuan[] = $no . ': ' . $m[0]; continue;
        }
        // String SQL mentah: literal yang memuat kata kunci SQL.
        if (preg_match_all('/([\'"])((?:(?!\1).)*\b(?:SELECT|FROM|WHERE|JOIN|INSERT INTO|UPDATE|ORDER BY|GROUP BY)\b(?:(?!\1).)*)\1/', $baris, $mm)) {
            foreach ($mm[2] as $sql) {
                // Nama tabel sesudah FROM/JOIN/INTO/UPDATE (mis. tabel `bidang`) bukan kolom.
                if (preg_match('/(?<![\w$])(?<!FROM )(?<!JOIN )(?<!INTO )(?<!UPDATE )(?<!TABLE )(?:[a-z]\w*\.)?(' . $u . ')\b(?![.(]|\s*\()/i', $sql, $m)) { $temuan[] = $no . ': ' . $m[0]; continue 2; }
            }
        }
    }
    return $temuan;
}

try {
    echo "=== UJI PENAMAAN BAHASA INDONESIA (MIGRASI 072) ===\n";

    echo "\n-- Skema --\n";
    preg_match("/migration_version'\] = (\d+);/", file_get_contents($AKAR . '/application/config/migration.php'), $vm);
    $cek(($vm[1] ?? '') >= '20260701000072' && (string) $satu('SELECT version FROM migrations') === $vm[1], 'Config dan DB di versi yang sama, paling rendah 20260701000072');
    $cek(count($M::TABEL) === 14 && array_sum(array_map('count', $M::KOLOM)) === 181 && count($M::KOMENTAR) === 45,
        'Peta migrasi: 14 tabel, 181 kolom, 45 komentar');
    $tabel = array_column($db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetch_all(), 0);
    $kolom = [];
    foreach ($db->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()")->fetch_all() as [$t, $k]) { $kolom[$t][$k] = TRUE; }
    $lama_ada = array_values(array_intersect(array_keys($M::TABEL), $tabel));
    $baru_hilang = array_values(array_diff(array_values($M::TABEL), $tabel));
    $cek(! $lama_ada && ! $baru_hilang, '14 tabel bernama baru, nol nama tabel lama' . ($lama_ada || $baru_hilang ? ' - ' . implode(', ', array_merge($lama_ada, $baru_hilang)) : ''));
    $salah = [];
    foreach ($M::KOLOM as $t_lama => $peta) {
        $t = $M::tabel($t_lama);
        foreach ($peta as $a => $b) {
            if (empty($kolom[$t][$b])) { $salah[] = "$t.$b hilang"; }
            if ($a !== $b && ! empty($kolom[$t][$a])) { $salah[] = "$t.$a masih ada"; }
        }
    }
    $cek(! $salah, '181 kolom bernama baru, nol nama kolom lama' . ($salah ? ' - ' . implode(', ', array_slice($salah, 0, 8)) : ''));
    $tanpa = array_column($db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' AND TABLE_COMMENT=''")->fetch_all(), 0);
    // Tabel yang lahir SESUDAH 072 (nama Indonesia sejak awal, COMMENT ditulis di migrasinya sendiri).
    // Tambah di sini setiap kali migrasi baru membuat tabel; 45 tabel 072 tetap dijaga persis.
    $sesudah_072 = ['seo_halaman' => '075'];
    $cek(count($tabel) === 45 + count($sesudah_072) && ! $tanpa, count($tabel) . ' tabel, semuanya ber-COMMENT' . ($tanpa ? ' - tanpa: ' . implode(', ', $tanpa) : ''));
    $tabel_072 = array_values(array_diff($tabel, array_keys($sesudah_072)));
    $cek(array_diff($tabel_072, array_keys($M::KOMENTAR)) === [] && array_diff(array_keys($M::KOMENTAR), $tabel_072) === []
        && ! array_diff(array_keys($sesudah_072), $tabel), 'Daftar KOMENTAR migrasi 072 = tabel di DB, ditambah tabel sesudah 072 yang terdaftar');
    $prefiks = TRUE;
    foreach ($M::KOMENTAR as $t => $k) {
        if (preg_match('/^(sf|rd|srp2|usr|sys|kkn|forum|chat|psu)_/', $t, $p)) { $prefiks = $prefiks && strpos($k, $p[1] . '_ = ') === 0; }
        $prefiks = $prefiks && strpos($k, "\u{2014}") === FALSE && strpos($k, "\u{2013}") === FALSE;
    }
    $cek($prefiks, 'Komentar tabel berprefiks diawali arti prefiksnya (mis. "sf_ = warga dan perumahan"), tanpa em/en dash');
    $klausa = [];
    foreach ($db->query("SELECT CONSTRAINT_NAME, CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()
        AND TABLE_NAME='sf_riwayat_keputusan_antrean'")->fetch_all() as [$n, $c]) { $klausa[$n] = $c; }
    $cek(strpos($klausa['ck_riwayat_antrean_dari'] ?? '', '`status_awal`') !== FALSE && strpos($klausa['ck_riwayat_antrean_ke'] ?? '', '`status_akhir`') !== FALSE,
        'CHECK 071 di tabel riwayat menyebut status_awal/status_akhir');
    $cek((int) $satu("SELECT COUNT(*) FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()") === 8, 'Delapan CHECK 071 tetap terpasang');
    $status = (string) shell_exec('php ' . escapeshellarg($AKAR . '/index.php') . ' migrate status 2>&1');
    $cek(strpos($status, 'nama Bahasa Indonesia (migrasi 072): TERPASANG (14 tabel dan 181 kolom berganti nama, 45 tabel ber-COMMENT)') !== FALSE,
        'Migrate::status melaporkan 072 TERPASANG');
    $merah_status = array_values(array_filter(explode("\n", $status), fn($b) => preg_match('/(migrasi 0[3-7]\d).*(HILANG|BELUM|TIDAK ADA -|BEDA)/', $b)));
    $cek(! $merah_status, 'Baris status migrasi lama tetap ADA/TERPASANG sesudah 072' . ($merah_status ? ' - ' . implode(' | ', array_slice($merah_status, 0, 3)) : ''));

    echo "\n-- Peta kunci JSON tersimpan --\n";
    $peta = kunci_tersimpan_peta();
    $semua = [];
    foreach ($M::KOLOM as $p) { foreach ($p as $a => $b) { $semua[$a][$b] = TRUE; } }
    $luar = array_filter(array_keys($peta), fn($a) => empty($semua[$a][$peta[$a]]));
    $cek(! $luar && count(array_unique($peta)) === count($peta), count($peta) . ' kunci, tiap pasangan ada di peta 072 dan bijektif' . ($luar ? ' - ' . implode(', ', $luar) : ''));
    $contoh = ['response_status' => 'found', 'identity' => ['gender_code' => 'male', 'full_name' => 'X'],
        'housing' => ['housing_status_code' => 'owned'], 'missing_fields' => ['welfare_decile', 'nik'],
        'source' => ['raw_record' => ['Nama' => 'X', 'source_mode' => 'tetap']]];
    $baru = kunci_tersimpan_ke_baru($contoh);
    $cek(isset($baru['status_respons'], $baru['identity']['jenis_kelamin'], $baru['housing']['kepemilikan_rumah'])
        && $baru['missing_fields'] === ['desil_kesejahteraan', 'nik'] && $baru['source']['raw_record'] === $contoh['source']['raw_record']
        && kunci_tersimpan_ke_lama($baru) === $contoh, 'Terjemah lama->baru->lama utuh; missing_fields ikut, raw_record SIMPERUM tidak disentuh');
    $cek(kunci_tersimpan_tabel() === $M::TABEL && kunci_tersimpan_objek('usr_users') === 'usr_akun' && kunci_tersimpan_objek('usr_akun') === 'usr_akun',
        'Peta nama tabel untuk objek_tipe jejak audit lama = peta tabel 072');

    echo "\n-- Pemindai kode application/ --\n";
    $khas_tabel = array_keys($M::TABEL);
    $khas_kolom = [];
    foreach ($M::KOLOM as $p) { foreach ($p as $a => $b) { if ($a !== $b && ! in_array($a, UMUM, TRUE)) { $khas_kolom[$a] = TRUE; } } }
    $khas = '/(?<![\w$])(' . implode('|', array_map('preg_quote', array_merge($khas_tabel, array_keys($khas_kolom)))) . ')\b/';
    $cek(count(array_unique(array_merge(array_keys($khas_kolom), UMUM))) >= 150, count($khas_kolom) . ' nama kolom khas + ' . count(UMUM) . ' nama umum dipantau');

    // Swauji: pemindai HARUS menangkap setiap pola ini.
    $sintetis = [
        "\$this->db->get('usr_users')",
        "->where('role', 'warga')",
        "->select('id, name, email')",
        "\$user->phone",
        "\$q = 'SELECT u.username FROM x u'",
        "->get_where('usr_akun', ['id' => 1, 'role' => 'warga'])",
        "\$row['housing_status_code']",
        "->order_by('d.id_diskusi')",
        "['Belum diteruskan', 'bidang IS NULL', 'x']",
    ];
    $tangkap = array_filter($sintetis, fn($s) => pindai($s, $khas, UMUM) !== []);
    $cek(count($tangkap) === count($sintetis), 'Swauji: ' . count($tangkap) . ' dari ' . count($sintetis) . ' pola nama lama sintetis tertangkap');
    $bersih = ["\$this->session->userdata('role')", "\$this->input->post('name', TRUE)", "'message' => 'OK'", "->where('peran', \$this->session->userdata('role'))",
        "\$this->input->ip_address()", "\$this->slot->bidang()", "\$this->allowed", "->select('bidang.kode, bidang.nama')"];
    $palsu = array_filter($bersih, fn($s) => pindai($s, $khas, UMUM) !== []);
    $cek(! $palsu, 'Swauji: kunci sesi, isian formulir, respons JSON, dan metode tidak dianggap kolom' . ($palsu ? ' - ' . implode(' | ', $palsu) : ''));

    $temuan = [];
    $n_berkas = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($AKAR . '/application', FilesystemIterator::SKIP_DOTS)) as $f) {
        $p = str_replace('\\', '/', $f->getPathname());
        if ( ! preg_match('/\.(php|js|json)$/', $p) || preg_match('#/application/(migrations|cache|logs)/#', $p)
            || substr($p, -strlen('helpers/kunci_tersimpan_helper.php')) === 'helpers/kunci_tersimpan_helper.php') { continue; }
        $n_berkas++;
        foreach (pindai(file_get_contents($p), $khas, UMUM) as $t) { $temuan[] = substr($p, strlen($AKAR) + 1) . ':' . $t; }
    }
    $cek($n_berkas > 250, "Memindai {$n_berkas} berkas application/");
    $cek(! $temuan, 'Nol nama tabel/kolom lama di konteks SQL, query builder, atau properti baris' . ($temuan ? ":\n      " . implode("\n      ", array_slice($temuan, 0, 25)) . (count($temuan) > 25 ? "\n      ... dan " . (count($temuan) - 25) . ' lagi' : '') : ''));
} catch (Throwable $e) {
    $cek(FALSE, 'Pengecualian: ' . $e->getMessage());
}

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
