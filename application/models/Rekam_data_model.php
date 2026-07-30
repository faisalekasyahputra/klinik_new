<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — pelaporan capaian Kabupaten/Kota (tahap D1).
 *
 * Satu-satunya pintu tulis modul ini. Tiga aturan ditegakkan DI SINI, bukan di
 * view atau controller, supaya jalur mana pun tunduk pada aturan yang sama:
 *
 *   1. Status hanya berubah lewat transisi(); UPDATE selalu menyertakan status
 *      asal di WHERE, jadi dua permintaan bersamaan tidak bisa dua-duanya lolos.
 *   2. Laporan `terkirim` TERKUNCI untuk kabupaten. Hanya peninjau provinsi yang
 *      membukanya kembali lewat `perlu_perbaikan`.
 *   3. Tiap operasi ber-scope kabupaten. NULL = lingkup provinsi
 *      (admin / admin_bidang); angka kabupaten = admin_kabkota, wajib diisi.
 *
 * "Diterima" bukan status tersendiri di skema — ia adalah laporan `terkirim`
 * yang `reviewed_at`-nya sudah terisi (lihat terima()). Tidak ambigu karena
 * "minta perbaikan" memindahkan statusnya, dan kirim ulang membersihkan jejak
 * peninjauan sebelumnya.
 *
 * Acuan: docs/product/ROADMAP_REKAM_DATA.md (D1),
 *        docs/architecture/SKEMA_DATA_REKAM_DATA.md
 */
class Rekam_data_model extends CI_Model {

    private const DOMAINS = ['perumahan', 'kawasan'];

    /** Transisi sah. Selain ini ditolak model, bukan disembunyikan view. */
    private const TRANSISI = [
        'draft'           => ['terkirim'],
        'terkirim'        => ['perlu_perbaikan'],
        'perlu_perbaikan' => ['terkirim'],
    ];

    /** Status yang masih boleh ditulis. `terkirim` sengaja tidak masuk. */
    private const STATUS_TERBUKA = ['draft', 'perlu_perbaikan'];

    /**
     * DUA BELAS sumber, bukan sepuluh. Google Form dinas (lihat
     * docs/engineering/STRUKTUR_FORM_SUMBER_REKAM_DATA.md) memuat sepuluh dan
     * TIDAK memuat `apbd_provinsi` maupun `baznas_provinsi`; sketsa Menu Utama
     * user memuat keduanya tetapi tidak memuat `apbn_dak` dan `apbn_kemensos`.
     * Keduanya digabung atas keputusan user 30 Jul 2026.
     *
     * Alasannya teknis, bukan kompromi: menambah nilai ENUM adalah ALTER yang
     * tidak menyentuh baris yang sudah ada, sedangkan MEMBUANG `apbn_dak`/
     * `apbn_kemensos` bersifat destruktif begitu ada laporan memakainya — dan
     * kalau dinas masih memakai Google Form itu, dua sumber tersebut memang
     * harus bisa dilaporkan. `apbd_provinsi` bukan nama lain dari `apbn_dak`
     * (uang provinsi vs transfer pusat) dan `baznas_provinsi` bukan nama lain
     * dari `apbn_kemensos` (lembaga zakat vs kementerian).
     *
     * Urutan di sini menentukan urutan tampil. Dua yang baru diselipkan pada
     * kelompoknya masing-masing, bukan ditempel di ujung.
     */
    public const SUMBER_PERUMAHAN = [
        'apbd_provinsi', 'apbd_kabkota', 'apbn_bsps', 'apbn_dak', 'apbn_kemensos',
        'apbn_dana_desa', 'apbn_kl_lain', 'baznas_ri', 'baznas_provinsi',
        'baznas_kabkota', 'csr', 'dana_lainnya',
    ];

    /** Hanya tiga sumber ini yang punya field keterangan di form aslinya. */
    private const SUMBER_BERKETERANGAN = ['apbn_kl_lain', 'csr', 'dana_lainnya'];

    public const PROGRAM = [
        'pk_rtlh', 'pb_rtlh', 'pb_backlog', 'pk_bencana', 'pb_bencana', 'pb_relokasi',
    ];

    public const INDIKATOR = [
        'bangunan_gedung', 'jalan_lingkungan', 'air_minum', 'drainase',
        'air_limbah', 'persampahan', 'proteksi_kebakaran',
    ];

    public const SUMBER_KAWASAN = [
        'apbn', 'apbd_provinsi', 'apbd_kabkota', 'dana_desa', 'csr', 'baznas', 'dana_lainnya',
    ];

    /**
     * Satuan diturunkan dari indikator, tidak disimpan — supaya tidak bisa
     * bertentangan dengannya. `proteksi_kebakaran` memang tanpa satuan di form
     * dinas; itu pertanyaan terbuka nomor 4, jangan dikarang.
     */
    private const SATUAN = [
        'bangunan_gedung'    => 'unit',
        'jalan_lingkungan'   => "m'",
        'air_minum'          => 'KK',
        'drainase'           => "m'",
        'air_limbah'         => 'KK',
        'persampahan'        => 'KK',
        'proteksi_kebakaran' => '',
    ];

    public static function satuan($indikator)
    {
        return self::SATUAN[$indikator] ?? '';
    }

    // ---------------------------------------------------------------- periode

    /**
     * Idempoten: periode yang sama tidak pernah menghasilkan dua laporan.
     * Draft baru mewarisi laporan `terkirim` terakhir pada tahun yang sama —
     * wajib, karena angkanya kumulatif dan mengetik ulang dari nol membuat
     * capaian menyusut diam-diam (roadmap §5).
     */
    public function ambil_atau_buat_draft($domain, $kabupaten_id, $tahun, $bulan)
    {
        $domain       = (string) $domain;
        $kabupaten_id = (int) $kabupaten_id;
        $tahun        = (int) $tahun;
        $bulan        = (int) $bulan;

        if ( ! in_array($domain, self::DOMAINS, TRUE)) {
            return $this->gagal('domain_invalid', 'Domain laporan tidak dikenal.');
        }
        if ($kabupaten_id < 1 || $bulan < 1 || $bulan > 12 || $tahun < 2020 || $tahun > 2100) {
            return $this->gagal('periode_invalid', 'Kabupaten atau periode tidak valid.');
        }

        $kunci = [
            'domain' => $domain, 'kabupaten_id' => $kabupaten_id,
            'tahun' => $tahun, 'bulan' => $bulan,
        ];

        $ada = $this->db->get_where('rd_laporan', $kunci)->row_array();
        if ($ada) {
            return ['success' => TRUE, 'laporan' => $ada, 'baru' => FALSE, 'diwarisi' => 0];
        }

        $this->db->trans_begin();
        $ok = $this->db->insert('rd_laporan', $kunci + ['status' => 'draft']);
        $id = $ok ? (int) $this->db->insert_id() : 0;
        if ( ! $ok || ! $id || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            // Kalah balapan dengan permintaan lain untuk periode yang sama:
            // barisnya sudah ada, itu hasil yang sah — bukan kegagalan.
            $ada = $this->db->get_where('rd_laporan', $kunci)->row_array();
            return $ada
                ? ['success' => TRUE, 'laporan' => $ada, 'baru' => FALSE, 'diwarisi' => 0]
                : $this->gagal('write_failed', 'Draft laporan belum dapat dibuat.');
        }

        $diwarisi = $this->warisi($domain, $id, $kabupaten_id, $tahun, $bulan);
        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Draft laporan belum dapat dibuat.');
        }
        $this->db->trans_commit();

        return [
            'success' => TRUE, 'baru' => TRUE, 'diwarisi' => $diwarisi,
            'laporan' => $this->db->get_where('rd_laporan', ['id' => $id])->row_array(),
        ];
    }

    /** Sumber warisan: bulan TERBESAR yang lebih kecil, berstatus `terkirim`, tahun sama. */
    private function warisi($domain, $laporan_baru, $kabupaten_id, $tahun, $bulan)
    {
        $sumber = $this->db->select('id')
            ->where([
                'domain' => $domain, 'kabupaten_id' => $kabupaten_id,
                'tahun' => $tahun, 'status' => 'terkirim',
            ])
            ->where('bulan <', $bulan)
            ->order_by('bulan', 'DESC')->limit(1)
            ->get('rd_laporan')->row_array();
        if ( ! $sumber) {
            return 0;
        }
        $lama = (int) $sumber['id'];

        if ($domain === 'perumahan') {
            $this->db->query(
                'INSERT INTO rd_perumahan_bagian (laporan_id, sumber_dana, ada)
                 SELECT ?, sumber_dana, ada FROM rd_perumahan_bagian WHERE laporan_id = ?',
                [$laporan_baru, $lama]);
            $this->db->query(
                'INSERT INTO rd_perumahan_baris (laporan_id, sumber_dana, program, unit, anggaran, keterangan)
                 SELECT ?, sumber_dana, program, unit, anggaran, keterangan
                 FROM rd_perumahan_baris WHERE laporan_id = ?',
                [$laporan_baru, $lama]);
            return (int) $this->db->affected_rows();
        }

        $this->db->query(
            'INSERT INTO rd_kawasan_ringkasan (laporan_id, ada_penanganan, ada_progres, catatan_progres, total_luas_ha)
             SELECT ?, ada_penanganan, ada_progres, catatan_progres, total_luas_ha
             FROM rd_kawasan_ringkasan WHERE laporan_id = ?',
            [$laporan_baru, $lama]);
        $this->db->query(
            'INSERT INTO rd_kawasan_intervensi (laporan_id, urutan, indikator, nama_kegiatan, lokasi_teks,
                 sumber_anggaran, keterangan_sumber, volume, nilai_anggaran, nilai_padat_karya)
             SELECT ?, urutan, indikator, nama_kegiatan, lokasi_teks,
                 sumber_anggaran, keterangan_sumber, volume, nilai_anggaran, nilai_padat_karya
             FROM rd_kawasan_intervensi WHERE laporan_id = ?',
            [$laporan_baru, $lama]);
        return (int) $this->db->affected_rows();
    }

    /**
     * Cari laporan satu periode TANPA membuatnya. Pasangan baca-saja dari
     * `ambil_atau_buat_draft()` — sengaja terpisah, bukan parameter tambahan.
     *
     * Layar Capaian kini membuka tabel lebih dulu (sesuai sketsa Menu Utama),
     * dan sekadar MELIHAT tidak boleh melahirkan baris di `rd_laporan`. Kalau
     * layar baca memakai `ambil_atau_buat_draft()`, setiap admin yang cuma
     * menengok akan membuat draft untuk bulan itu, dan riwayat pelaporan penuh
     * periode kosong yang tidak pernah diniatkan siapa pun. Draft lahir hanya
     * saat tombol Input Capaian ditekan.
     *
     * @return array|null Baris rd_laporan, atau NULL bila periode itu belum ada.
     */
    public function laporan_periode($domain, $kabupaten_id, $tahun, $bulan)
    {
        if ( ! in_array($domain, self::DOMAINS, TRUE)) {
            return NULL;
        }
        return $this->db->get_where('rd_laporan', [
            'domain'       => $domain,
            'kabupaten_id' => (int) $kabupaten_id,
            'tahun'        => (int) $tahun,
            'bulan'        => (int) $bulan,
        ])->row_array() ?: NULL;
    }

    // -------------------------------------------------------------- perumahan

    /**
     * Jawaban gerbang "Ada / Tidak Ada". Mengubah jawaban menjadi berbeda dari
     * sebelumnya menghapus baris bagian lalu menulisnya ulang — angkanya ikut
     * tersapu lewat CASCADE, bukan lewat JavaScript. Jawaban yang sama = no-op,
     * jadi menyimpan dua kali tidak menghilangkan angka yang sudah diisi.
     */
    public function simpan_bagian($laporan_id, $sumber_dana, $ada, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'perumahan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        if ( ! in_array($sumber_dana, self::SUMBER_PERUMAHAN, TRUE)) {
            return $this->gagal('sumber_invalid', 'Sumber dana tidak dikenal.');
        }
        $laporan_id = (int) $gerbang['laporan']['id'];
        $ada        = $ada ? 1 : 0;

        $sekarang = $this->db->select('ada')
            ->get_where('rd_perumahan_bagian', ['laporan_id' => $laporan_id, 'sumber_dana' => $sumber_dana])
            ->row_array();
        if ($sekarang !== NULL && (int) $sekarang['ada'] === $ada) {
            return ['success' => TRUE, 'laporan_id' => $laporan_id, 'berubah' => FALSE];
        }

        $this->db->trans_begin();
        $this->db->delete('rd_perumahan_bagian', ['laporan_id' => $laporan_id, 'sumber_dana' => $sumber_dana]);
        $ok = $this->db->insert('rd_perumahan_bagian', [
            'laporan_id' => $laporan_id, 'sumber_dana' => $sumber_dana, 'ada' => $ada,
        ]);
        if ( ! $ok || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Jawaban sumber dana belum tersimpan.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'laporan_id' => $laporan_id, 'berubah' => TRUE];
    }

    /**
     * Angka per program untuk satu sumber dana.
     * $baris = ['pk_rtlh' => ['unit' => 12, 'anggaran' => 300000000, 'keterangan' => '...'], ...]
     *
     * Sumber dananya wajib sudah dinyatakan "Ada" — dijamin FK gabungan di DB,
     * tapi diperiksa di sini juga supaya pesannya bisa dibaca manusia.
     */
    public function simpan_baris($laporan_id, $sumber_dana, array $baris, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'perumahan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        if ( ! in_array($sumber_dana, self::SUMBER_PERUMAHAN, TRUE)) {
            return $this->gagal('sumber_invalid', 'Sumber dana tidak dikenal.');
        }
        $laporan_id = (int) $gerbang['laporan']['id'];

        $bagian = $this->db->select('ada')
            ->get_where('rd_perumahan_bagian', ['laporan_id' => $laporan_id, 'sumber_dana' => $sumber_dana])
            ->row_array();
        if ( ! $bagian || (int) $bagian['ada'] !== 1) {
            return $this->gagal('bagian_belum_ada', 'Sumber dana ini belum dinyatakan "Ada".');
        }

        $bersih = [];
        foreach ($baris as $program => $nilai) {
            if ( ! in_array($program, self::PROGRAM, TRUE)) {
                return $this->gagal('program_invalid', 'Program tidak dikenal.');
            }
            $unit     = $this->angka_bulat($nilai['unit'] ?? 0);
            $anggaran = $this->angka_bulat($nilai['anggaran'] ?? 0);
            if ($unit === NULL || $anggaran === NULL) {
                return $this->gagal('angka_invalid', 'Unit dan anggaran harus bilangan bulat tidak negatif.');
            }
            // Anggaran tanpa unit = uang keluar tanpa rumah tertangani. Hampir selalu
            // salah ketik, dan kalau dibiarkan ia mencemari rekap provinsi.
            if ($anggaran > 0 && $unit === 0) {
                return $this->gagal('unit_kosong', 'Anggaran terisi tetapi unitnya 0. Isi unitnya, atau nolkan anggarannya.');
            }
            // Keterangan hanya bermakna di tiga sumber; di sumber lain dikosongkan,
            // bukan ditolak — supaya payload lama tidak membuat form gagal.
            $keterangan = in_array($sumber_dana, self::SUMBER_BERKETERANGAN, TRUE)
                ? mb_substr(trim((string) ($nilai['keterangan'] ?? '')), 0, 150)
                : '';
            $bersih[$program] = [$unit, $anggaran, $keterangan];
        }
        if ( ! $bersih) {
            return $this->gagal('kosong', 'Tidak ada angka untuk disimpan.');
        }

        $this->db->trans_begin();
        foreach ($bersih as $program => [$unit, $anggaran, $keterangan]) {
            // Simpan berkali-kali tidak menggandakan baris: kuncinya
            // uq_rd_perumahan_baris (laporan_id, sumber_dana, program).
            $this->db->query(
                'INSERT INTO rd_perumahan_baris (laporan_id, sumber_dana, program, unit, anggaran, keterangan)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE unit = VALUES(unit), anggaran = VALUES(anggaran),
                     keterangan = VALUES(keterangan)',
                [$laporan_id, $sumber_dana, $program, $unit, $anggaran, $keterangan]);
        }
        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Angka belum tersimpan.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'laporan_id' => $laporan_id, 'baris' => count($bersih)];
    }

    /** Sumber dana yang belum dijawab Ada/Tidak Ada — penentu boleh-tidaknya Kirim. */
    public function sumber_belum_dijawab($laporan_id)
    {
        $terjawab = $this->db->select('sumber_dana')
            ->get_where('rd_perumahan_bagian', ['laporan_id' => (int) $laporan_id])
            ->result_array();
        return array_values(array_diff(self::SUMBER_PERUMAHAN, array_column($terjawab, 'sumber_dana')));
    }

    /**
     * BNBA — satu berkas per laporan (`uq_rd_bnba_laporan`). Unggah ulang
     * MENGGANTI. Berkas lama dikembalikan supaya pemanggil meng-`unlink()`-nya;
     * model tidak menyentuh disk, controller yang punya berkas.
     */
    public function simpan_bnba($laporan_id, array $meta, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'perumahan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        $laporan_id = (int) $gerbang['laporan']['id'];
        $lama = $this->db->get_where('rd_perumahan_bnba', ['laporan_id' => $laporan_id])->row_array();

        $this->db->trans_begin();
        $this->db->delete('rd_perumahan_bnba', ['laporan_id' => $laporan_id]);
        $ok = $this->db->insert('rd_perumahan_bnba', [
            'laporan_id'   => $laporan_id,
            'nama_asli'    => mb_substr((string) $meta['nama_asli'], 0, 255),
            'private_path' => (string) $meta['private_path'],
            'mime_type'    => mb_substr((string) $meta['mime_type'], 0, 100),
            'ukuran'       => (int) $meta['ukuran'],
            'uploaded_by'  => (int) $meta['uploaded_by'] ?: NULL,
        ]);
        if ( ! $ok || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Berkas BNBA belum tercatat.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'path_lama' => $lama['private_path'] ?? NULL];
    }

    /** Baca BNBA ter-scope. NULL kalau laporannya di luar wilayah pemanggil. */
    public function bnba($laporan_id, $scope_kabupaten_id = NULL)
    {
        $laporan = $this->laporan($laporan_id, $scope_kabupaten_id);
        if ( ! $laporan) {
            return NULL;
        }
        return $this->db->get_where('rd_perumahan_bnba',
            ['laporan_id' => (int) $laporan['id']])->row_array() ?: NULL;
    }

    /**
     * Kirim laporan perumahan. Gerbangnya di sini, bukan di view: sepuluh sumber
     * dana WAJIB sudah dijawab Ada/Tidak Ada. "Belum dijawab" berbeda dari
     * "nihil", dan mengirim laporan setengah terisi membuat rekap provinsi
     * membaca kekosongan sebagai nol.
     */
    public function kirim($laporan_id, $aktor_id, $scope_kabupaten_id = NULL)
    {
        $laporan = $this->laporan($laporan_id, $scope_kabupaten_id);
        if ( ! $laporan) {
            return $this->gagal('luar_scope', 'Laporan tidak ditemukan di wilayah Anda.');
        }
        $id = (int) $laporan['id'];

        if ($laporan['domain'] === 'perumahan') {
            $belum = $this->sumber_belum_dijawab($id);
            if ($belum) {
                return $this->gagal('belum_lengkap',
                    count($belum) . ' sumber dana belum dijawab Ada/Tidak Ada.');
            }
        } else {
            $ringkasan = $this->db->get_where('rd_kawasan_ringkasan', ['laporan_id' => $id])->row_array();
            if ( ! $ringkasan) {
                return $this->gagal('belum_lengkap',
                    'Jawab dulu apakah ada penanganan kawasan permukiman kumuh.');
            }
            // "Tidak ada penanganan" dan "tidak ada progres" SAH dikirim tanpa
            // Total Luas — itu salah satu cacat form dinas yang tidak dibawa ke
            // sini. Yang dijaga cuma satu: mengaku ada progres tapi nol intervensi
            // membuat rekap membaca kekosongan sebagai nol rupiah.
            if ((int) $ringkasan['ada_penanganan'] === 1 && (int) $ringkasan['ada_progres'] === 1
                && $this->total_kawasan($id)['jumlah_intervensi'] === 0) {
                return $this->gagal('belum_lengkap',
                    'Ada progres realisasi, tetapi belum ada satu pun intervensi dicatat.');
            }
        }

        // Transisi tetap lewat pintu yang sama, termasuk penguncian status asal.
        return $this->transisi($id, $laporan['status'], 'terkirim', $aktor_id, $scope_kabupaten_id);
    }

    // ---------------------------------------------------------------- kawasan

    public function simpan_ringkasan($laporan_id, array $data, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'kawasan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        $laporan_id     = (int) $gerbang['laporan']['id'];
        $ada_penanganan = empty($data['ada_penanganan']) ? 0 : 1;
        $ada_progres    = empty($data['ada_progres']) ? 0 : 1;
        $catatan        = trim((string) ($data['catatan_progres'] ?? ''));
        $luas           = $this->angka_desimal($data['total_luas_ha'] ?? 0);

        if ($luas === NULL) {
            return $this->gagal('luas_invalid', 'Total luas harus angka tidak negatif.');
        }
        if ( ! $ada_penanganan) {
            // Tidak ada penanganan sama sekali: sisanya dinolkan, bukan disimpan
            // setengah-setengah.
            $ada_progres = 0;
            $luas        = 0;
        } elseif ( ! $ada_progres && $catatan === '') {
            return $this->gagal('catatan_wajib', 'Jelaskan progres kegiatan saat ini.');
        }
        // Total Luas SENGAJA tidak diwajibkan saat tidak ada progres — itu salah
        // satu cacat form dinas yang tidak dibawa ke sini (roadmap §4).

        $ok = $this->db->query(
            'INSERT INTO rd_kawasan_ringkasan (laporan_id, ada_penanganan, ada_progres, catatan_progres, total_luas_ha)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE ada_penanganan = VALUES(ada_penanganan),
                 ada_progres = VALUES(ada_progres), catatan_progres = VALUES(catatan_progres),
                 total_luas_ha = VALUES(total_luas_ha)',
            [$laporan_id, $ada_penanganan, $ada_progres, ($catatan === '' ? NULL : $catatan), $luas]);

        return $ok
            ? ['success' => TRUE, 'laporan_id' => $laporan_id]
            : $this->gagal('write_failed', 'Ringkasan belum tersimpan.');
    }

    /** $intervensi_id NULL = tambah baru (urutan otomatis di ekor daftar). */
    public function simpan_intervensi($laporan_id, array $data, $intervensi_id = NULL, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'kawasan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        $laporan_id = (int) $gerbang['laporan']['id'];

        if ( ! in_array($data['indikator'] ?? '', self::INDIKATOR, TRUE)) {
            return $this->gagal('indikator_invalid', 'Indikator penanganan tidak dikenal.');
        }
        if ( ! in_array($data['sumber_anggaran'] ?? '', self::SUMBER_KAWASAN, TRUE)) {
            return $this->gagal('sumber_invalid', 'Sumber anggaran tidak dikenal.');
        }
        $nama   = trim((string) ($data['nama_kegiatan'] ?? ''));
        $lokasi = trim((string) ($data['lokasi_teks'] ?? ''));
        if ($nama === '' || $lokasi === '') {
            return $this->gagal('teks_wajib', 'Nama kegiatan dan lokasi wajib diisi.');
        }
        $volume      = $this->angka_desimal($data['volume'] ?? 0);
        $anggaran    = $this->angka_bulat($data['nilai_anggaran'] ?? 0);
        $padat_karya = $this->angka_bulat($data['nilai_padat_karya'] ?? 0);
        if ($volume === NULL || $anggaran === NULL || $padat_karya === NULL) {
            return $this->gagal('angka_invalid', 'Volume dan nilai harus angka tidak negatif.');
        }

        $payload = [
            'indikator'         => $data['indikator'],
            'nama_kegiatan'     => $nama,
            'lokasi_teks'       => $lokasi,
            'sumber_anggaran'   => $data['sumber_anggaran'],
            'keterangan_sumber' => mb_substr(trim((string) ($data['keterangan_sumber'] ?? '')), 0, 150),
            'volume'            => $volume,
            'nilai_anggaran'    => $anggaran,
            'nilai_padat_karya' => $padat_karya,
        ];

        if ($intervensi_id !== NULL) {
            $this->db->where(['id' => (int) $intervensi_id, 'laporan_id' => $laporan_id]);
            $ok = $this->db->update('rd_kawasan_intervensi', $payload);
            return ($ok && $this->db->affected_rows() >= 0)
                ? ['success' => TRUE, 'intervensi_id' => (int) $intervensi_id]
                : $this->gagal('write_failed', 'Intervensi belum tersimpan.');
        }

        $this->db->trans_begin();
        $ekor = (int) $this->db->select_max('urutan', 'urutan')
            ->get_where('rd_kawasan_intervensi', ['laporan_id' => $laporan_id])
            ->row()->urutan;
        $ok = $this->db->insert('rd_kawasan_intervensi',
            $payload + ['laporan_id' => $laporan_id, 'urutan' => $ekor + 1]);
        $id = $ok ? (int) $this->db->insert_id() : 0;
        if ( ! $ok || ! $id || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Intervensi belum tersimpan.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'intervensi_id' => $id, 'urutan' => $ekor + 1];
    }

    /** Hapus lalu rapatkan `urutan` supaya tidak bolong. */
    public function hapus_intervensi($laporan_id, $intervensi_id, $scope_kabupaten_id = NULL)
    {
        $gerbang = $this->laporan_untuk_tulis($laporan_id, 'kawasan', $scope_kabupaten_id);
        if (empty($gerbang['success'])) {
            return $gerbang;
        }
        $laporan_id = (int) $gerbang['laporan']['id'];

        $this->db->trans_begin();
        $this->db->delete('rd_kawasan_intervensi',
            ['id' => (int) $intervensi_id, 'laporan_id' => $laporan_id]);
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return $this->gagal('tidak_ditemukan', 'Intervensi tidak ditemukan pada laporan ini.');
        }
        // Menaik dari 1: nomor tujuan selalu <= nomor sekarang, jadi tidak pernah
        // menabrak uq_rd_kawasan_intervensi_urutan di tengah jalan.
        $sisa = $this->db->select('id')->where('laporan_id', $laporan_id)
            ->order_by('urutan', 'ASC')->get('rd_kawasan_intervensi')->result_array();
        $nomor = 0;
        foreach ($sisa as $row) {
            $nomor++;
            $this->db->where('id', (int) $row['id'])
                ->update('rd_kawasan_intervensi', ['urutan' => $nomor]);
        }
        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->gagal('write_failed', 'Intervensi belum terhapus.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'sisa' => $nomor];
    }

    // ------------------------------------------------------------ siklus status

    /**
     * Satu-satunya pintu ubah status.
     *
     * $scope_kabupaten_id: angka = admin_kabkota (hanya wilayahnya sendiri),
     * NULL = peninjau provinsi. `perlu_perbaikan` wajib beralasan.
     */
    public function transisi($laporan_id, $dari, $ke, $aktor_id, $scope_kabupaten_id = NULL, $catatan = '')
    {
        $laporan_id = (int) $laporan_id;
        $aktor_id   = (int) $aktor_id;
        $catatan    = trim((string) $catatan);

        if ( ! isset(self::TRANSISI[$dari]) || ! in_array($ke, self::TRANSISI[$dari], TRUE)) {
            return $this->gagal('transisi_invalid', 'Perubahan status tidak valid.');
        }
        if ($ke === 'perlu_perbaikan' && $catatan === '') {
            return $this->gagal('catatan_wajib', 'Catatan perbaikan wajib diisi.');
        }

        $waktu = date('Y-m-d H:i:s');
        if ($ke === 'terkirim') {
            // Kirim / kirim ulang: bersihkan jejak peninjauan sebelumnya supaya
            // laporan yang baru dikirim tidak terbaca seolah sudah diterima.
            $payload = [
                'status' => 'terkirim', 'submitted_at' => $waktu, 'submitted_by' => $aktor_id,
                'reviewed_at' => NULL, 'reviewed_by' => NULL, 'catatan_admin' => NULL,
            ];
        } else {
            $payload = [
                'status' => $ke, 'reviewed_at' => $waktu, 'reviewed_by' => $aktor_id,
                'catatan_admin' => $catatan,
            ];
        }

        // WHERE menyertakan status asal: dua permintaan bersamaan, hanya satu lolos.
        $this->db->where(['id' => $laporan_id, 'status' => $dari]);
        if ($scope_kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $scope_kabupaten_id);
        }
        $ok = $this->db->update('rd_laporan', $payload);
        if ( ! $ok || $this->db->affected_rows() !== 1) {
            return $this->gagal('basi_atau_luar_scope', 'Laporan sudah berubah atau di luar wilayah Anda.');
        }
        return ['success' => TRUE, 'laporan_id' => $laporan_id, 'status' => $ke];
    }

    /**
     * Terima laporan. Skema tidak punya status `diterima` tersendiri; yang
     * menandainya adalah `reviewed_at` terisi pada laporan yang masih `terkirim`.
     *
     * $domain adalah gerbang bidang: Admin Bidang Perumahan tidak boleh
     * menyentuh laporan kawasan, dan sebaliknya.
     */
    public function terima($laporan_id, $reviewer_id, $domain = NULL)
    {
        $this->db->where(['id' => (int) $laporan_id, 'status' => 'terkirim', 'reviewed_at' => NULL]);
        if ($domain !== NULL) {
            $this->db->where('domain', $domain);
        }
        $ok = $this->db->update('rd_laporan', [
            'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) $reviewer_id,
            'catatan_admin' => NULL,
        ]);
        return ($ok && $this->db->affected_rows() === 1)
            ? ['success' => TRUE, 'laporan_id' => (int) $laporan_id]
            : $this->gagal('basi_atau_sudah_ditinjau', 'Laporan sudah ditinjau atau statusnya berubah.');
    }

    /**
     * Kembalikan laporan ke kabupaten dengan catatan WAJIB. Catatan itu yang
     * dibaca petugas kabupaten di layar riwayat dan input, jadi ia bagian dari
     * kontrak, bukan hiasan.
     */
    public function minta_perbaikan($laporan_id, $reviewer_id, $catatan, $domain = NULL)
    {
        $laporan = $this->db->get_where('rd_laporan', ['id' => (int) $laporan_id])->row_array();
        if ( ! $laporan || ($domain !== NULL && $laporan['domain'] !== $domain)) {
            return $this->gagal('luar_bidang', 'Laporan tidak ditemukan di bidang Anda.');
        }
        // Transisi tetap lewat pintu yang sama: status asal ikut di WHERE, dan
        // catatan kosong ditolak di sana.
        return $this->transisi((int) $laporan['id'], $laporan['status'], 'perlu_perbaikan',
            $reviewer_id, NULL, $catatan);
    }

    /**
     * Daftar laporan yang menunggu/pernah ditinjau provinsi, satu domain saja.
     * Draft tidak pernah muncul di sini — yang belum dikirim bukan urusan
     * peninjau.
     */
    public function daftar_tinjauan($domain, $tahun, $bulan = NULL)
    {
        if ( ! in_array($domain, self::DOMAINS, TRUE)) {
            return [];
        }
        $this->db
            ->select('l.id, l.kabupaten_id, l.bulan, l.status, l.submitted_at, l.reviewed_at,'
                . ' l.catatan_admin, k.nama AS kabupaten')
            ->from('rd_laporan l')
            ->join('kabupaten k', 'k.id = l.kabupaten_id', 'left')
            ->where(['l.domain' => $domain, 'l.tahun' => (int) $tahun])
            ->where_in('l.status', ['terkirim', 'perlu_perbaikan']);
        if ($bulan !== NULL) {
            $this->db->where('l.bulan', (int) $bulan);
        }
        return $this->db->order_by('l.bulan', 'DESC')->order_by('k.nama', 'ASC')
            ->get()->result_array();
    }

    /** Detail satu laporan untuk peninjau bidang. NULL kalau beda domain. */
    public function laporan_bidang($laporan_id, $domain)
    {
        $laporan = $this->db->get_where('rd_laporan',
            ['id' => (int) $laporan_id, 'domain' => $domain])->row_array();
        if ( ! $laporan) {
            return NULL;
        }
        // Scope kabupaten sengaja NULL: peninjau provinsi memang lintas wilayah.
        // Yang menggerbanginya adalah domain, bukan kabupaten.
        return $this->isi_laporan((int) $laporan['id']);
    }

    // ------------------------------------------------------------------ baca

    /** Baca satu laporan; $scope_kabupaten_id NULL = lingkup provinsi. */
    public function laporan($laporan_id, $scope_kabupaten_id = NULL)
    {
        $this->db->where('id', (int) $laporan_id);
        if ($scope_kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $scope_kabupaten_id);
        }
        return $this->db->get('rd_laporan')->row_array() ?: NULL;
    }

    /** Isi lengkap satu laporan, sudah ter-scope. NULL kalau di luar wilayah. */
    public function isi_laporan($laporan_id, $scope_kabupaten_id = NULL)
    {
        $laporan = $this->laporan($laporan_id, $scope_kabupaten_id);
        if ( ! $laporan) {
            return NULL;
        }
        $id = (int) $laporan['id'];

        if ($laporan['domain'] === 'perumahan') {
            return [
                'laporan' => $laporan,
                'bagian'  => $this->db->order_by('sumber_dana', 'ASC')
                    ->get_where('rd_perumahan_bagian', ['laporan_id' => $id])->result_array(),
                'baris'   => $this->db->order_by('sumber_dana', 'ASC')->order_by('program', 'ASC')
                    ->get_where('rd_perumahan_baris', ['laporan_id' => $id])->result_array(),
                'bnba'    => $this->db->get_where('rd_perumahan_bnba', ['laporan_id' => $id])->row_array() ?: NULL,
                'belum_dijawab' => $this->sumber_belum_dijawab($id),
            ];
        }
        return [
            'laporan'    => $laporan,
            'ringkasan'  => $this->db->get_where('rd_kawasan_ringkasan', ['laporan_id' => $id])->row_array() ?: NULL,
            'intervensi' => $this->db->order_by('urutan', 'ASC')
                ->get_where('rd_kawasan_intervensi', ['laporan_id' => $id])->result_array(),
            'total'      => $this->total_kawasan($id),
        ];
    }

    /** Nilai turunan, tidak pernah disimpan (§skema 5). */
    public function total_kawasan($laporan_id)
    {
        $row = $this->db->select('COUNT(*) AS jml, COALESCE(SUM(nilai_anggaran),0) AS anggaran,'
                . ' COALESCE(SUM(nilai_padat_karya),0) AS padat_karya', FALSE)
            ->get_where('rd_kawasan_intervensi', ['laporan_id' => (int) $laporan_id])->row_array();
        return [
            'jumlah_intervensi' => (int) ($row['jml'] ?? 0),
            'total_anggaran'    => (int) ($row['anggaran'] ?? 0),
            'total_padat_karya' => (int) ($row['padat_karya'] ?? 0),
        ];
    }

    /**
     * Rekap satu periode. **Tidak ada SUM() antar bulan** — angkanya sudah
     * kumulatif, menjumlahkannya melipatgandakan capaian (§skema 5, jebakan
     * terbesar modul ini). Satu-satunya penjumlahan yang sah adalah antar
     * kabupaten pada bulan yang sama, dan itu dilakukan pemanggil.
     */
    public function rekap($domain, $tahun, $bulan, $kabupaten_id = NULL)
    {
        if ( ! in_array($domain, self::DOMAINS, TRUE)) {
            return [];
        }
        $this->db->where([
            'l.domain' => $domain, 'l.tahun' => (int) $tahun,
            'l.bulan' => (int) $bulan, 'l.status' => 'terkirim',
        ]);
        if ($kabupaten_id !== NULL) {
            $this->db->where('l.kabupaten_id', (int) $kabupaten_id);
        }

        if ($domain === 'perumahan') {
            return $this->db
                ->select('l.kabupaten_id, b.sumber_dana, b.program, b.unit, b.anggaran, b.keterangan')
                ->from('rd_laporan l')
                ->join('rd_perumahan_baris b', 'b.laporan_id = l.id')
                ->order_by('l.kabupaten_id', 'ASC')->order_by('b.sumber_dana', 'ASC')
                ->get()->result_array();
        }
        return $this->db
            ->select('l.kabupaten_id, l.id AS laporan_id, r.ada_penanganan, r.ada_progres, r.total_luas_ha,'
                . ' COUNT(i.id) AS jumlah_intervensi, COALESCE(SUM(i.nilai_anggaran),0) AS total_anggaran,'
                . ' COALESCE(SUM(i.nilai_padat_karya),0) AS total_padat_karya', FALSE)
            ->from('rd_laporan l')
            ->join('rd_kawasan_ringkasan r', 'r.laporan_id = l.id')
            ->join('rd_kawasan_intervensi i', 'i.laporan_id = l.id', 'left')
            ->group_by('l.id')->order_by('l.kabupaten_id', 'ASC')
            ->get()->result_array();
    }

    /**
     * Daftar periode satu tahun, baca-saja. Bukan rekap angka — hanya jejak
     * status supaya petugas tahu bulan mana yang sudah dikirim dan mana yang
     * dikembalikan untuk diperbaiki.
     */
    public function riwayat($domain, $kabupaten_id, $tahun, $scope_kabupaten_id = NULL)
    {
        if ( ! in_array($domain, self::DOMAINS, TRUE)) {
            return [];
        }
        // Scope wilayah tetap ditegakkan walau ini jalur baca.
        if ($scope_kabupaten_id !== NULL && (int) $scope_kabupaten_id !== (int) $kabupaten_id) {
            return [];
        }
        return $this->db
            ->select('id, bulan, status, submitted_at, reviewed_at, catatan_admin, updated_at')
            ->where(['domain' => $domain, 'kabupaten_id' => (int) $kabupaten_id, 'tahun' => (int) $tahun])
            ->order_by('bulan', 'DESC')
            ->get('rd_laporan')->result_array();
    }

    // ---------------------------------------------------------------- internal

    /**
     * Gerbang tunggal seluruh jalur tulis: wilayah + domain + kunci `terkirim`.
     * Semua penulis lewat sini, jadi guard-nya satu tempat — bukan diulang di
     * tiap method dan bukan di controller.
     */
    private function laporan_untuk_tulis($laporan_id, $domain, $scope_kabupaten_id)
    {
        $this->db->where(['id' => (int) $laporan_id, 'domain' => $domain]);
        if ($scope_kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $scope_kabupaten_id);
        }
        $laporan = $this->db->get('rd_laporan')->row_array();
        if ( ! $laporan) {
            return $this->gagal('luar_scope', 'Laporan tidak ditemukan di wilayah Anda.');
        }
        if ( ! in_array($laporan['status'], self::STATUS_TERBUKA, TRUE)) {
            return $this->gagal('terkunci', 'Laporan sudah terkirim dan terkunci.');
        }
        return ['success' => TRUE, 'laporan' => $laporan];
    }

    /** NULL kalau bukan bilangan bulat tidak negatif. */
    private function angka_bulat($nilai)
    {
        if (is_string($nilai)) {
            $nilai = trim($nilai);
        }
        if ($nilai === '' || $nilai === NULL || ! is_numeric($nilai)) {
            return NULL;
        }
        $angka = (float) $nilai;
        if ($angka < 0 || floor($angka) !== $angka) {
            return NULL;
        }
        return (int) $angka;
    }

    /** NULL kalau bukan angka tidak negatif. Pecahan diizinkan (Ha, m'). */
    private function angka_desimal($nilai)
    {
        if (is_string($nilai)) {
            $nilai = trim($nilai);
        }
        if ($nilai === '' || $nilai === NULL || ! is_numeric($nilai) || (float) $nilai < 0) {
            return NULL;
        }
        return round((float) $nilai, 2);
    }

    private function gagal($kode, $pesan)
    {
        return ['success' => FALSE, 'error' => $kode, 'message' => $pesan];
    }
}
