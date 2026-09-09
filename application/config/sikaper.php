<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Konfigurasi API Sikaper (Disperakim Jateng)
| -------------------------------------------------------------------------
|
| 🔻 BASE URL DIKOREKSI 9 Sep 2026, DAN INI SEBAB UTAMA "401" YANG LAMA.
| Berkas ini dulu menunjuk `https://sikaper.disperakim.jatengprov.go.id/api/`.
| Host itu memang milik dinas dan situs publiknya hidup, tetapi API-nya
| MENOLAK kredensial yang sama dengan `401 Unauthorized` - diuji ulang 9 Sep
| 2026, masih 401. Host yang benar-benar melayani API adalah
| `egov.phicos.co.id/jateng/sikaper_new`, dan dengan kredensial yang sama ia
| membalas 200 dalam ~0,3 detik. Jadi yang salah selama ini BUKAN
| kredensialnya, melainkan alamatnya - dan itu tidak akan pernah ketahuan
| dari pesan 401 yang identik dengan atau tanpa auth.
|
| Perhatikan juga `/api/v2/`, bukan `/api/`. Keenam endpoint hidup di v2.
|
| 🔴 KREDENSIAL TIDAK LAGI DITULIS DI SINI. Versi lama menaruh username dan
| password apa adanya di berkas ini, dan berkas ini ter-commit - jadi
| password itu SUDAH MASUK RIWAYAT GIT dan tidak bisa ditarik kembali dengan
| memindahkannya ke `.env` saja. Lihat §18: yang dibutuhkan ROTASI di sisi
| dinas, bukan sekadar pemindahan. Sampai rotasi itu terjadi, anggap
| kredensial yang beredar sekarang sudah bocor.
|
| Nilai bawaan password SENGAJA kosong. Kalau `.env` tidak menyediakannya,
| pemanggilan API gagal dengan jelas di tempatnya, bukan diam-diam memakai
| kredensial yang tertinggal di kode.
*/

$config['sikaper_api_base_url'] = rtrim(
    getenv('SIKAPER_BASE_URL') ?: 'https://egov.phicos.co.id/jateng/sikaper_new/api/v2',
    '/'
) . '/';

$config['sikaper_api_username'] = trim((string) getenv('SIKAPER_USERNAME'));
$config['sikaper_api_password'] = trim((string) getenv('SIKAPER_PASSWORD'));
