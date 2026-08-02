<?php
/**
 * Uji perjalanan penuh SRP2 — roadmap T6, item TERPENTING.
 *
 * PHP CLI murni + curl, TANPA framework dan TANPA fixture library — repo ini
 * tidak punya direktori tests/ sama sekali (dicek sebelum menulis ini), jadi
 * skrip ini sengaja tidak mengandaikan harness apa pun. assert() longgar (fungsi
 * cek()/wajib() di bawah) dipakai alih-alih assert() bawaan PHP karena assert()
 * bawaan bisa dimatikan lewat php.ini (zend.assertions) tanpa peringatan.
 *
 * CARA JALANKAN:
 *   php docs/engineering/uji_perjalanan_srp2.php
 *
 * PRASYARAT WAJIB — DB KOSONG HASIL MIGRASI, bukan DB lokal yang sudah
 * ditambal tangan (pelajaran omah_sekeng, AGENTS.md §0e: "kalau DB lokal
 * jalan, itu bukan bukti kode benar"). Siapkan DB terpisah:
 *   1. mysql -u root -e "CREATE DATABASE uji_srp2 CHARACTER SET utf8mb4;"
 *   2. mysql -u root uji_srp2 < docs/engineering/schema_klinikpkp.sql
 *   3. Arahkan .env DB_NAME sementara ke DB itu, lalu: php index.php migrate
 *   4. Seed SATU akun admin (baseline schema TIDAK menyertakan akun apa pun):
 *      INSERT INTO usr_users (email, password, name, username, role, status,
 *        profile_completed, created_at) VALUES ('admin_uji@example.test',
 *        '<hash bcrypt>', 'Admin Uji', 'admin_uji', 'admin', 'active', 1, NOW());
 *   5. Set UJI_ADMIN_EMAIL / UJI_ADMIN_PASSWORD di bawah (atau env var),
 *      jalankan skrip, lalu kembalikan .env DB_NAME ke semula.
 *
 * Alur POSITIF: daftar cepat -> isi nama perusahaan (sudah termasuk saat
 * daftar cepat) -> unggah 14 dokumen -> kirim -> admin approve -> nama
 * muncul di direktori publik -> profil publik menampilkan perusahaan yang
 * BENAR (bukan perusahaan lain, T4 butir 2).
 *
 * Tiga uji NEGATIF (justru intinya, bukan pelengkap):
 *   N1. Transisi ilegal (Draft -> Diterima langsung) ditolak SERVER.
 *   N2. Gerbang hulu (kirim_pengajuan dengan nama bentrok direktori) mencegah
 *       baris lahir jadi Pending sama sekali.
 *   N3. Penulisan ke tabel kedua yang PASTI gagal (bentrok UNIQUE) membuat
 *       SELURUH keputusan admin batal, bukan tersimpan sebagian.
 *
 * DEFINISI SELESAI sesungguhnya BUKAN skrip ini hijau sekali (itu cuma
 * prasyarat) — tapi skrip ini MERAH kalau satu perbaikan T1a sengaja
 * dibalikkan (buang trans_status() dari Admin_Srp2::proses()). Skrip yang
 * tidak pernah gagal tidak membuktikan apa pun.
 */

// ==========================================================================
// KONFIGURASI
// ==========================================================================
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'admin_uji_t6@example.test');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'UjiAdmin123!');
define('AKUN_PASSWORD', 'Passw0rd!23');

$GLOBALS['UJI_GAGAL'] = 0;
$GLOBALS['UJI_TOTAL'] = 0;

function cek($kondisi, $label) {
    $GLOBALS['UJI_TOTAL']++;
    if ($kondisi) { echo "  OK    $label\n"; return true; }
    echo "  GAGAL $label\n";
    $GLOBALS['UJI_GAGAL']++;
    return false;
}

/** Prasyarat yang kalau gagal membuat sisa skrip tidak bermakna — berhenti total. */
function wajib($kondisi, $label) {
    if (cek($kondisi, $label)) { return; }
    fwrite(STDERR, "\nBerhenti: prasyarat di atas gagal, sisa uji tidak bisa dipercaya.\n");
    exit(1);
}

/**
 * Loader .env sendiri — BUKAN parse_ini_file(). File .env proyek ini memakai
 * baris komentar '#' di awal, dan parse_ini_file() gagal total di baris itu
 * ("syntax error, unexpected '='"), diverifikasi sebelum menulis fungsi ini.
 */
function load_env($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) { continue; }
        [$k, $v] = explode('=', $line, 2);
        $out[trim($k)] = trim($v);
    }
    return $out;
}

// ==========================================================================
// AKSES DB LANGSUNG — untuk VERIFIKASI, bukan pengganti HTTP. Satu-satunya
// tempat skrip ini menulis langsung ke DB ada di blok N3, dan itu SENGAJA
// mensimulasikan skenario yang seharusnya sudah dicegah gerbang hulu (N2) —
// tujuannya mengisolasi transaksi Admin_Srp2::proses() itu sendiri.
// ==========================================================================
class Db {
    private mysqli $m;
    public function __construct(array $env) {
        $this->m = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
        if ($this->m->connect_error) {
            fwrite(STDERR, "Koneksi DB gagal: {$this->m->connect_error}\n");
            exit(1);
        }
    }
    public function baris($sql, array $params = []): ?array {
        $stmt = $this->siapkan($sql, $params);
        $hasil = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $hasil ?: null;
    }
    public function skalar($sql, array $params = []) {
        $baris = $this->baris($sql, $params);
        return $baris ? reset($baris) : null;
    }
    public function jalankan($sql, array $params = []): void {
        $this->siapkan($sql, $params)->close();
    }
    private function siapkan($sql, array $params) {
        $stmt = $this->m->prepare($sql);
        if (!$stmt) { fwrite(STDERR, "Query gagal disiapkan: {$this->m->error}\n"); exit(1); }
        if ($params) {
            $tipe = str_repeat('s', count($params));
            $stmt->bind_param($tipe, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }
}

// ==========================================================================
// KLIEN HTTP KECIL BERBASIS CURL — satu cookie jar per "sesi" (= satu akun
// login). csrf_regenerate=FALSE di config proyek ini, jadi satu token bisa
// dipakai ulang sepanjang sesi asal cookie-nya sama.
// ==========================================================================
class Sesi {
    private string $cookieFile;
    public ?string $csrfToken = null;

    public function __construct() {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'uji_srp2_');
    }
    public function __destruct() {
        @unlink($this->cookieFile);
    }

    private function panggil(string $path, array $curlOpts): array {
        $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, $curlOpts + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            fwrite(STDERR, "curl error ke $path: " . curl_error($ch) . "\n");
            exit(1);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        $body = substr($raw, $info['header_size']);
        $this->tangkapCsrf($body);
        return ['status' => $info['http_code'], 'body' => $body];
    }

    public function get(string $path): array {
        return $this->panggil($path, [CURLOPT_HTTPGET => true]);
    }

    public function postForm(string $path, array $fields, bool $ajax = true): array {
        $fields[$this->namaCsrf()] = $this->csrfToken;
        $opts = [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields)];
        if ($ajax) { $opts[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
        return $this->panggil($path, $opts);
    }

    public function postFile(string $path, string $fieldName, string $filePath, string $mime): array {
        $fields = [
            $fieldName => new CURLFile($filePath, $mime, basename($filePath)),
            $this->namaCsrf() => $this->csrfToken,
        ];
        return $this->panggil($path, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        ]);
    }

    private function namaCsrf(): string { return 'csrf_kpkp_token'; }

    private function tangkapCsrf(string $body): void {
        if (preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/', $body, $m)) {
            $this->csrfToken = $m[1];
        }
    }
}

function json_body(array $resp): ?array {
    // Beberapa respons JSON diawali BOM UTF-8 (peninggalan encoding berkas PHP
    // di suatu tempat pipeline) -- json_decode() gagal SENYAP (return NULL,
    // bukan exception) kalau BOM tidak dibuang dulu. Ditemukan saat menulis
    // skrip ini: login admin selalu "gagal" walau body-nya valid.
    $body = ltrim($resp['body'], "\xEF\xBB\xBF");
    return json_decode($body, true);
}

// ==========================================================================
// SETUP
// ==========================================================================
if (!is_file(ENV_PATH)) { fwrite(STDERR, ".env tidak ditemukan di " . ENV_PATH . "\n"); exit(1); }
$env = load_env(ENV_PATH);
$db  = new Db($env);

$stamp = time();
$akunUji = fn(string $sufiks) => "uji_t6_{$stamp}_{$sufiks}@example.test";

$dokumenKeys = ['form_1', 'form_2a', 'form_2b', 'form_3', 'form_4', 'form_5', 'form_6',
    'form_6b', 'form_7', 'form_8', 'form_9', 'form_10', 'form_11', 'form_13'];

// PDF minimal valid — finfo mendeteksinya sebagai application/pdf sungguhan,
// bukan cuma nama berkas .pdf (yang justru ditolak validasi MIME di server).
$pdfPath = sys_get_temp_dir() . '/uji_srp2_dokumen.pdf';
file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
    . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
    . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 3 3]>>endobj\n"
    . "trailer<</Size 4/Root 1 0 R>>\n%%EOF");

function daftar_cepat_srp2(Sesi $s, string $email, string $nama): array {
    $s->get('Auth/login');
    $r = $s->postForm('Auth/do_register', [
        'email' => $email, 'password' => AKUN_PASSWORD, 'password_confirm' => AKUN_PASSWORD,
        'srp2_pengembang' => '1', 'nama_perusahaan' => $nama,
    ]);
    return [$r, json_body($r)];
}

function unggah_semua_dokumen(Sesi $s, int $regId, string $pdfPath, array $keys): bool {
    foreach ($keys as $key) {
        $r = $s->postFile("Pengembang/simpan_dokumen/$regId", $key, $pdfPath, 'application/pdf');
        $data = json_body($r);
        if ($r['status'] !== 200 || ($data['status'] ?? '') !== 'success') { return false; }
    }
    return true;
}

$sesiUjiAktif = [];
$daftarHapusAkhir = [];

echo "=== UJI PERJALANAN SRP2 (roadmap T6) ===\n";
echo "Target: " . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

// --------------------------------------------------------------------------
echo "-- ADMIN: login akun uji admin\n";
$sesiAdmin = new Sesi();
$sesiAdmin->get('Auth/login');
$rLoginAdmin = $sesiAdmin->postForm('Auth/do_login', ['email' => ADMIN_EMAIL, 'password' => ADMIN_PASSWORD]);
$dataLoginAdmin = json_body($rLoginAdmin);
wajib(($dataLoginAdmin['status'] ?? '') === 'success' && ($dataLoginAdmin['role'] ?? '') === 'admin',
    'Login admin uji berhasil (lihat header berkas ini untuk cara seed akunnya)');

// ==========================================================================
// [POSITIF] Perjalanan penuh
// ==========================================================================
echo "\n-- [POSITIF] daftar -> 14 dokumen -> kirim -> approve -> direktori publik -> profil publik BENAR\n";
$namaA  = 'PT UJI PERJALANAN ' . $stamp;
$emailA = $akunUji('a');
$sesiA  = new Sesi();
$sesiUjiAktif[] = $sesiA; $daftarHapusAkhir[] = $sesiA;

[$rA, $dataA] = daftar_cepat_srp2($sesiA, $emailA, $namaA);
wajib(($dataA['status'] ?? '') === 'success', 'Daftar cepat SRP2 (akun A) berhasil');
$regIdA = (int) $dataA['registration_id'];
wajib($regIdA > 0, 'registration_id valid diterima dari respons daftar');

$rowUser = $db->baris('SELECT name, username FROM usr_users WHERE email = ?', [$emailA]);
cek(!empty($rowUser['name']) && !empty($rowUser['username']),
    'name & username otomatis terisi setelah daftar cepat (T5 S12-a) — bukan NULL');
cek(($dataA['name'] ?? '') !== '', 'Respons do_register menyertakan name (T6 R2-sisa)');

wajib(unggah_semua_dokumen($sesiA, $regIdA, $pdfPath, $dokumenKeys), 'Ke-14 dokumen berhasil diunggah (akun A)');
$jumlahDok = (int) $db->skalar('SELECT COUNT(*) FROM srp2_documents WHERE registration_id = ?', [$regIdA]);
cek($jumlahDok === 14, "Tepat 14 baris srp2_documents tercatat (dapat: $jumlahDok)");

$rKirimA = $sesiA->postForm("Pengembang/kirim_pengajuan/$regIdA", []);
$dataKirimA = json_body($rKirimA);
cek(($dataKirimA['status'] ?? '') === 'success', 'Kirim pengajuan (akun A) diterima server');
$statusA1 = $db->skalar('SELECT status_verifikasi FROM srp2_registrations WHERE id = ?', [$regIdA]);
wajib($statusA1 === 'Pending', "Status berubah jadi Pending di DB sebelum admin memutuskan (dapat: $statusA1)");

$sesiAdmin->get("Admin_Srp2/detail/$regIdA");
$sesiAdmin->postForm("Admin_Srp2/proses/$regIdA", ['status' => 'Diterima'], false);
$rowA = $db->baris('SELECT status_verifikasi, certified_developer_id, reviewed_by FROM srp2_registrations WHERE id = ?', [$regIdA]);
wajib(($rowA['status_verifikasi'] ?? '') === 'Diterima', 'Status berubah jadi Diterima di DB setelah admin approve');
wajib(!empty($rowA['certified_developer_id']), 'certified_developer_id terisi setelah approve (T4)');
cek(!empty($rowA['reviewed_by']), 'reviewed_by terisi (bukan NULL) setelah keputusan');
$cidA = (int) $rowA['certified_developer_id'];

$rDirektori = $sesiA->get('Pengembang/sertifikasi');
cek(strpos($rDirektori['body'], $namaA) !== false, 'Nama perusahaan A muncul di direktori publik');

$rProfilAkun = $sesiA->get('akun/profil');
preg_match('#Pengembang/profil/(\d+)#', $rProfilAkun['body'], $mCid);
cek(isset($mCid[1]) && (int) $mCid[1] === $cidA,
    'Tombol "Lihat Profil Publik" menunjuk certified_developer_id yang BENAR (T4 butir 2)');

$rProfilPublik = $sesiA->get("Pengembang/profil/$cidA");
cek($rProfilPublik['status'] === 200 && strpos($rProfilPublik['body'], $namaA) !== false,
    'Profil publik menampilkan nama perusahaan A yang BENAR (bukan perusahaan lain)');

// UI admin mengikuti status baris yang benar, bukan label "Menunggu" tetap.
$rVerifikasiDiterima = $sesiAdmin->get('Admin_Srp2/pending?status=Diterima');
cek($rVerifikasiDiterima['status'] === 200
    && preg_match('#' . preg_quote($namaA, '#') . '.*?<span\\b[^>]*>\\s*Diterima\\s*</span>#si', $rVerifikasiDiterima['body']) === 1,
    'Daftar verifikasi menampilkan label Diterima pada baris yang difilter Diterima');

$rDirektoriUrut = $sesiAdmin->get('Admin_Srp2?sort=status_aktif&dir=asc');
cek($rDirektoriUrut['status'] === 200
    && strpos($rDirektoriUrut['body'], 'sort=nama_perusahaan') !== false
    && strpos($rDirektoriUrut['body'], 'sort=status_aktif') !== false
    && strpos($rDirektoriUrut['body'], 'ph-sort-ascending text-brand-primary') !== false,
    'Direktori SRP2 menyediakan header sort server-side yang aktif');

// ==========================================================================
// [N1] Transisi ilegal: Draft -> Diterima langsung, tanpa pernah dikirim
// ==========================================================================
echo "\n-- [NEGATIF 1] Transisi ilegal: approve pengajuan yang MASIH Draft harus ditolak SERVER\n";
$emailB = $akunUji('b');
$sesiB  = new Sesi();
$daftarHapusAkhir[] = $sesiB;
[$rB, $dataB] = daftar_cepat_srp2($sesiB, $emailB, 'PT UJI TRANSISI ILEGAL ' . $stamp);
wajib(($dataB['status'] ?? '') === 'success', 'Daftar akun B berhasil (tetap Draft, sengaja tidak dikirim)');
$regIdB = (int) $dataB['registration_id'];

$sesiAdmin->postForm("Admin_Srp2/proses/$regIdB", ['status' => 'Diterima'], false);
$rowB = $db->baris('SELECT status_verifikasi, reviewed_by FROM srp2_registrations WHERE id = ?', [$regIdB]);
cek(($rowB['status_verifikasi'] ?? '') === 'Draft', "Transisi Draft->Diterima ditolak, status tetap Draft (dapat: {$rowB['status_verifikasi']})");
cek(empty($rowB['reviewed_by']), 'reviewed_by tetap NULL — tidak ada keputusan yang tercatat untuk transisi ilegal');

// ==========================================================================
// [N2] Gerbang hulu: nama bentrok direktori dicegah SEBELUM lahir jadi Pending
// ==========================================================================
echo "\n-- [NEGATIF 2] Gerbang hulu: kirim pengajuan dengan nama BENTROK direktori (nama A) harus dicegah\n";
$emailC = $akunUji('c');
$sesiC  = new Sesi();
$daftarHapusAkhir[] = $sesiC;
[$rC, $dataC] = daftar_cepat_srp2($sesiC, $emailC, $namaA); // sengaja sama persis dengan A yang sudah Diterima
$regIdC = (int) $dataC['registration_id'];
wajib(unggah_semua_dokumen($sesiC, $regIdC, $pdfPath, $dokumenKeys), '14 dokumen (akun C) berhasil diunggah');

$rKirimC = $sesiC->postForm("Pengembang/kirim_pengajuan/$regIdC", []);
$dataKirimC = json_body($rKirimC);
cek(($dataKirimC['status'] ?? '') === 'error' && ($dataKirimC['code'] ?? '') === 'nama_perusahaan_bentrok',
    'Kirim pengajuan DITOLAK server karena nama bentrok direktori (bukan cuma tombol disembunyikan)');
$statusC = $db->skalar('SELECT status_verifikasi FROM srp2_registrations WHERE id = ?', [$regIdC]);
cek($statusC === 'Draft', "Baris TIDAK PERNAH lahir jadi Pending, tetap Draft (dapat: $statusC)");

// ==========================================================================
// [N3] Penulisan tabel kedua yang PASTI gagal -> transaksi batal SELURUHNYA
// ==========================================================================
echo "\n-- [NEGATIF 3] proses() gagal di tabel kedua -> SELURUH keputusan batal, bukan tersimpan sebagian\n";
$emailD = $akunUji('d');
$sesiD  = new Sesi();
$daftarHapusAkhir[] = $sesiD;
// Nama UNIK dulu supaya lolos gerbang hulu (N2) -- blok ini menguji transaksi
// proses() itu SENDIRI, bukan mengulang N2.
[$rD, $dataD] = daftar_cepat_srp2($sesiD, $emailD, 'PT UJI SEMENTARA ' . $stamp);
$regIdD = (int) $dataD['registration_id'];
wajib(unggah_semua_dokumen($sesiD, $regIdD, $pdfPath, $dokumenKeys), '14 dokumen (akun D) berhasil diunggah');
$sesiD->postForm("Pengembang/kirim_pengajuan/$regIdD", []);
$statusD0 = $db->skalar('SELECT status_verifikasi FROM srp2_registrations WHERE id = ?', [$regIdD]);
wajib($statusD0 === 'Pending', 'Registrasi D mencapai Pending dengan nama unik (lolos gerbang hulu secara sah)');

// SATU-SATUNYA tempat skrip ini menulis langsung ke DB: menyuntik nama supaya
// bentrok UNIQUE srp2_certified_developers, mensimulasikan skenario yang
// SEHARUSNYA sudah dicegah N2 -- di sini sengaja dilewati untuk mengisolasi
// transaksi Admin_Srp2::proses() itu sendiri (roadmap T1a butir 5, ⏳ di
// roadmap, dibuktikan ulang di sini).
$db->jalankan('UPDATE srp2_registrations SET nama_perusahaan = ? WHERE id = ?', [$namaA, (string) $regIdD]);

$sesiAdmin->postForm("Admin_Srp2/proses/$regIdD", ['status' => 'Diterima'], false);
$rowD = $db->baris('SELECT status_verifikasi, reviewed_by, certified_developer_id FROM srp2_registrations WHERE id = ?', [$regIdD]);
cek(($rowD['status_verifikasi'] ?? '') === 'Pending', "Status TETAP Pending, tidak berubah sebagian (dapat: {$rowD['status_verifikasi']})");
cek(empty($rowD['reviewed_by']), 'reviewed_by tetap NULL — transaksi dibatalkan seluruhnya, bukan sukses karangan');
cek(empty($rowD['certified_developer_id']), 'certified_developer_id tetap NULL — tidak ada baris parsial di direktori');

// ==========================================================================
// BERSIH-BERSIH — akun uji dihapus lewat jalur nyata (delete_account, bukan
// DELETE manual), supaya sekalian membuktikan FK CASCADE + unlink berkas
// masih bekerja setelah semua perubahan T0-T6.
// ==========================================================================
echo "\n-- Membersihkan akun uji lewat jalur nyata\n";
foreach ($daftarHapusAkhir as $s) {
    $s->get('akun/profil');
    $s->postForm('akun/delete', ['current_password' => AKUN_PASSWORD], false);
}
@unlink($pdfPath);

// ==========================================================================
echo "\n=== RINGKASAN ===\n";
echo "{$GLOBALS['UJI_TOTAL']} pemeriksaan, {$GLOBALS['UJI_GAGAL']} gagal.\n";
if ($GLOBALS['UJI_GAGAL'] > 0) {
    echo "MERAH.\n";
    exit(1);
}
echo "HIJAU.\n";
exit(0);
