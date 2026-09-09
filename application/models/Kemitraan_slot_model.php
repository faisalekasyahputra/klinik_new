<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ketersediaan slot magang per BIDANG (migrasi 20260701000031).
 *
 * Versi sebelumnya bekerja pada "divisi" - tujuh nama yang ternyata berasal
 * dari mockup, bukan dari struktur dinas. Dikonfirmasi 1 Agt 2026: satuan
 * terkecil organisasinya adalah bidang, dan tidak ada satuan di bawahnya.
 *
 * Ada sebagai model, bukan query yang ditulis langsung di controller seperti
 * tetangganya, karena dibaca dari TIGA sisi: `KemitraanPortal` (papan slot,
 * pilihan bidang, penjagaan saat menyimpan), `Admin_Kemitraan` (pengelolaan),
 * dan `Kemitraan_Bidang` (meja tinjauan). Aturan "buka atau tutup" yang ditulis
 * tiga kali adalah aturan yang cepat atau lambat berselisih dengan dirinya.
 *
 * Ingat bentuk penyimpanannya: BARIS SLOT ADA berarti bulan itu dibuka, tidak
 * ada baris berarti tutup. Tidak ada kolom `tersedia` yang bisa berbohong.
 */
class Kemitraan_slot_model extends CI_Model
{
    const TABEL_BIDANG = 'kkn_magang_bidang';
    const TABEL_SLOT   = 'kkn_magang_slot';
    const TABEL_DAFTAR = 'kkn_magang_pendaftaran';

    /**
     * Status yang MEMAKAN tempat pada hitungan kehadiran.
     *
     * Didefinisikan SEKALI karena dibaca dari dua tempat - dan daftar yang
     * ditulis dua kali akan berselisih begitu ada status baru. 'Ditinjau
     * Bidang' ikut memakan tempat: ia belum selesai, dan orangnya masih
     * mengantre untuk periode itu. 'Ditolak' dan 'Dibatalkan' melepaskannya.
     */
    const STATUS_MEMAKAI_KUOTA = ['Diajukan', 'Ditinjau Bidang', 'Diterima'];

    /**
     * Batas panjang satu periode, dalam hari (400 ~ 13 bulan).
     *
     * Bukan aturan kosmetik: hitungan kehadiran menelusuri hari demi hari, jadi
     * satu baris berperiode 2020-2099 akan membuat setiap render halaman
     * menghitung puluhan ribu hari. Ditegakkan di validasi controller lewat
     * periode_terlalu_panjang(), dan diulang sebagai jaring terakhir di
     * hari_dalam_periode() supaya baris lama yang telanjur aneh tetap tidak
     * bisa menggantung halaman.
     */
    const BATAS_HARI = 400;

    /** Nama bulan Indonesia, indeks 1..12 - dipakai tampilan publik & admin. */
    public static function nama_bulan()
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    /**
     * Bidang beserta setelan magangnya: kode, nama, kuota, aktif.
     *
     * @param bool $hanya_aktif FALSE hanya untuk layar admin, yang memang perlu
     *                          melihat bidang nonaktif supaya bisa menyalakannya
     *                          kembali. Sisi publik selalu TRUE.
     */
    public function bidang($hanya_aktif = TRUE)
    {
        $this->db->select('bidang.kode, bidang.nama, ' . self::TABEL_BIDANG . '.kuota, ' . self::TABEL_BIDANG . '.aktif')
            ->from(self::TABEL_BIDANG)
            ->join('bidang', 'bidang.kode = ' . self::TABEL_BIDANG . '.bidang_kode', 'inner')
            ->order_by('bidang.nama', 'ASC');
        if ($hanya_aktif) { $this->db->where(self::TABEL_BIDANG . '.aktif', 1); }
        return $this->db->get()->result();
    }

    public function bidang_by_kode($kode)
    {
        return $this->db->select('bidang.kode, bidang.nama, ' . self::TABEL_BIDANG . '.kuota, ' . self::TABEL_BIDANG . '.aktif')
            ->from(self::TABEL_BIDANG)
            ->join('bidang', 'bidang.kode = ' . self::TABEL_BIDANG . '.bidang_kode', 'inner')
            ->where(self::TABEL_BIDANG . '.bidang_kode', (string) $kode)
            ->get()->row();
    }

    /**
     * Slot satu tahun sebagai peta [bidang_kode][bulan] => baris slot.
     *
     * Nilainya barisnya sendiri, bukan sekadar TRUE, karena tiap slot membawa
     * rentang tanggalnya. `isset()` tetap berarti "bulan ini dibuka".
     */
    public function peta_slot($tahun)
    {
        $peta = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, ['tahun' => (int) $tahun])->result() as $baris) {
            $peta[$baris->bidang_kode][(int) $baris->bulan] = $baris;
        }
        return $peta;
    }

    /** Slot satu bidang satu tahun: [bulan] => baris. Untuk layar detail. */
    public function slot_bidang($kode, $tahun)
    {
        $peta = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, [
            'bidang_kode' => (string) $kode, 'tahun' => (int) $tahun,
        ])->result() as $baris) {
            $peta[(int) $baris->bulan] = $baris;
        }
        return $peta;
    }

    /**
     * Label rentang satu slot: 'Juni' kalau sebulan penuh, 'Juni 1-15' kalau tidak.
     *
     * Sebulan penuh sengaja TIDAK ditulis "1-30": angka yang tidak membedakan
     * apa pun hanya menambah yang harus dibaca.
     */
    public function label_rentang($slot, $pendek = FALSE)
    {
        $nama  = self::nama_bulan()[(int) $slot->bulan];
        $label = $pendek ? substr($nama, 0, 3) : $nama;

        $awal_bulan  = date('Y-m-01', strtotime($slot->tgl_mulai));
        $akhir_bulan = date('Y-m-t', strtotime($slot->tgl_mulai));
        if ($slot->tgl_mulai === $awal_bulan && $slot->tgl_selesai === $akhir_bulan) {
            return $label;
        }
        return $label . ' ' . (int) date('j', strtotime($slot->tgl_mulai))
            . '-' . (int) date('j', strtotime($slot->tgl_selesai));
    }

    /**
     * Tahun yang layak ditampilkan lebih dulu di papan publik.
     *
     * Tahun berjalan kalau ia memang punya slot; kalau tidak, tahun TERDEKAT
     * berikutnya yang punya. Papan yang dipatok ke `date('Y')` membuat seluruh
     * konfigurasi tahun depan tak terlihat: dibuka Agustus 2026 untuk periode
     * 2027, pengunjung hanya melihat dua belas kotak merah dan menyimpulkan
     * tidak ada penerimaan sama sekali. Kena 2 Agt 2026.
     */
    public function tahun_papan()
    {
        $sekarang = (int) date('Y');
        $punya = array_map(
            static function ($b) { return (int) $b->tahun; },
            $this->db->distinct()->select('tahun')->order_by('tahun', 'ASC')->get(self::TABEL_SLOT)->result()
        );
        if (in_array($sekarang, $punya, TRUE)) { return $sekarang; }

        foreach ($punya as $t) {
            if ($t > $sekarang) { return $t; }
        }
        return $sekarang;
    }

    /** Tahun yang punya slot, untuk selektor tahun di layar admin. */
    public function tahun_tersedia()
    {
        $tahun = array_map(
            static function ($b) { return (int) $b->tahun; },
            $this->db->distinct()->select('tahun')->order_by('tahun', 'DESC')->get(self::TABEL_SLOT)->result()
        );
        // Tahun berjalan selalu ikut, meski belum punya satu slot pun -
        // kalau tidak, tahun yang masih kosong justru mustahil dibuka.
        $sekarang = (int) date('Y');
        if ( ! in_array($sekarang, $tahun, TRUE)) { $tahun[] = $sekarang; }
        rsort($tahun);
        return $tahun;
    }

    /**
     * Tulis ulang slot SATU bidang untuk satu tahun.
     *
     * Hapus-lalu-sisipkan, bukan selisih per bulan: layar detail mengirim
     * keadaan LENGKAP dua belas bulan, jadi bulan yang tidak dikirim memang
     * berarti tutup. Dibungkus transaksi supaya tidak pernah ada momen di mana
     * bidang itu tampak kosong bagi pengunjung yang kebetulan memuat halaman.
     *
     * Rentang DIJEPIT ke batas bulannya, bukan ditolak. Admin yang mengetik
     * 20 Juni - 5 Juli pada baris Juni jelas bermaksud "sampai akhir Juni";
     * memulangkannya dengan galat untuk maksud yang sudah terbaca hanya
     * menambah satu putaran. Bulan Juli punya barisnya sendiri.
     *
     * @param array $bulan_data [bulan => ['buka' => '1', 'mulai' => 'Y-m-d', 'selesai' => 'Y-m-d']]
     */
    public function tulis_ulang_bidang($kode, $tahun, array $bulan_data)
    {
        $kode  = (string) $kode;
        $tahun = (int) $tahun;
        if ( ! $this->bidang_by_kode($kode)) { return FALSE; }

        $this->db->trans_start();
        $this->db->delete(self::TABEL_SLOT, ['bidang_kode' => $kode, 'tahun' => $tahun]);

        $baris = [];
        foreach ($bulan_data as $bulan => $isian) {
            $bulan = (int) $bulan;
            if ($bulan < 1 || $bulan > 12) { continue; }
            if (empty($isian['buka'])) { continue; }

            $awal  = sprintf('%04d-%02d-01', $tahun, $bulan);
            $akhir = date('Y-m-t', strtotime($awal));

            $mulai   = $this->jepit_tanggal($isian['mulai']   ?? '', $awal, $akhir, $awal);
            $selesai = $this->jepit_tanggal($isian['selesai'] ?? '', $awal, $akhir, $akhir);
            if ($selesai < $mulai) { $selesai = $akhir; }

            $baris[] = [
                'bidang_kode' => $kode,
                'tahun'       => $tahun,
                'bulan'       => $bulan,
                'tgl_mulai'   => $mulai,
                'tgl_selesai' => $selesai,
            ];
        }
        if ($baris) { $this->db->insert_batch(self::TABEL_SLOT, $baris); }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Tanggal dijepit ke [$min, $max]; kosong atau tidak sah jatuh ke $bawaan. */
    private function jepit_tanggal($nilai, $min, $max, $bawaan)
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) { return $bawaan; }
        if ($nilai < $min) { return $min; }
        if ($nilai > $max) { return $max; }
        return $nilai;
    }

    /** Daftar bulan 'Y-n' yang disinggung satu periode. */
    private function bulan_dalam_periode($mulai, $selesai)
    {
        $awal  = date_create((string) $mulai);
        $akhir = date_create((string) $selesai);
        if ( ! $awal || ! $akhir || $akhir < $awal) { return []; }

        $kunci  = [];
        // Dipatok ke tanggal 1 supaya penambahan bulan tidak melompati Februari
        // ketika tanggal mulainya 29-31.
        $kursor = date_create($awal->format('Y-m-01'));
        $batas  = date_create($akhir->format('Y-m-01'));
        while ($kursor <= $batas) {
            $kunci[] = (int) $kursor->format('Y') . '-' . (int) $kursor->format('n');
            $kursor->modify('+1 month');
        }
        return $kunci;
    }

    /**
     * Tanggal 'Y-m-d' sepanjang satu periode.
     *
     * ponytail: dibatasi BATAS_HARI. Periode selama itu tidak mungkin magang
     * sungguhan, dan tanpa batas satu baris berperiode 2020-2099 membuat
     * halaman menghitung sepuluh ribu hari.
     */
    private function hari_dalam_periode($mulai, $selesai)
    {
        $awal  = date_create((string) $mulai);
        $akhir = date_create((string) $selesai);
        if ( ! $awal || ! $akhir || $akhir < $awal) { return []; }

        $tanggal = [];
        $kursor  = date_create($awal->format('Y-m-d'));
        while ($kursor <= $akhir && count($tanggal) < self::BATAS_HARI) {
            $tanggal[] = $kursor->format('Y-m-d');
            $kursor->modify('+1 day');
        }
        return $tanggal;
    }

    /**
     * Berapa mahasiswa hadir BERSAMAAN pada tiap hari: [bidang_kode]['Y-m-d'] => n.
     *
     * Harian, bukan bulanan, dan itu inti seluruh hitungan ini. Versi pertama
     * menghitung "berapa pendaftaran menyentuh bulan ini", sehingga dua orang
     * yang tidak pernah bertemu satu hari pun bisa saling menghalangi: A pulang
     * 15 Juli, B datang 16 Juli, dan Juli tercatat terisi dua. Yang benar-benar
     * terbatas adalah meja dan pembimbing - dan itu diukur per hari.
     *
     * DITURUNKAN, tidak pernah disimpan. Kolom "terisi" berarti dua sumber
     * kebenaran yang harus disinkronkan pada setiap pendaftaran, penolakan, dan
     * penyuntingan admin - dan yang satu pasti menyimpang dari yang lain.
     *
     * Dikelompokkan lewat `bidang_kode`, kolom sungguhan sejak migrasi 031.
     * Sebelumnya pencocokannya lewat NAMA divisi, dan satu ganti nama memutus
     * seluruh pendaftaran dari hitungannya.
     *
     * @param int|null $abaikan_id Pendaftaran yang tidak ikut dihitung. Dipakai
     *   saat satu baris disunting: ia tidak boleh menghalangi dirinya sendiri.
     */
    private function peta_harian($abaikan_id = NULL)
    {
        $this->db->select('id, bidang_kode, periode_mulai, periode_selesai')
            ->where('jenis', 'magang')
            ->where_in('status', self::STATUS_MEMAKAI_KUOTA)
            ->where('bidang_kode IS NOT NULL', NULL, FALSE)
            ->where('periode_mulai IS NOT NULL', NULL, FALSE)
            ->where('periode_selesai IS NOT NULL', NULL, FALSE);
        if ($abaikan_id !== NULL) { $this->db->where('id !=', (int) $abaikan_id); }

        $harian = [];
        foreach ($this->db->get(self::TABEL_DAFTAR)->result() as $baris) {
            foreach ($this->hari_dalam_periode($baris->periode_mulai, $baris->periode_selesai) as $tanggal) {
                $harian[$baris->bidang_kode][$tanggal] = ($harian[$baris->bidang_kode][$tanggal] ?? 0) + 1;
            }
        }
        return $harian;
    }

    /**
     * Puncak kehadiran bersamaan per bidang per bulan: [bidang_kode]['Y-n'] => n.
     *
     * Dipakai TAMPILAN saja - satu angka per sel. Penjagaan pendaftaran TIDAK
     * boleh memakai angka ini: puncak sebulan bisa terjadi pada hari-hari yang
     * tidak disentuh pemohon baru. Untuk itu ada bulan_terhalang().
     */
    public function peta_terisi($abaikan_id = NULL)
    {
        $peta = [];
        foreach ($this->peta_harian($abaikan_id) as $kode => $hari) {
            foreach ($hari as $tanggal => $jumlah) {
                [$tahun, $bulan] = array_map('intval', explode('-', $tanggal));
                $kunci = $tahun . '-' . $bulan;
                if ($jumlah > ($peta[$kode][$kunci] ?? 0)) { $peta[$kode][$kunci] = $jumlah; }
            }
        }
        return $peta;
    }

    /**
     * Alasan satu periode tidak bisa diambil pada satu bidang.
     *
     * Kosong berarti boleh. Isinya kalimat siap tempel, mis.
     * "Juli 2027 (tidak dibuka)" atau "Juni 2027 (penuh, 2 dari 2)".
     *
     * Diperiksa SETIAP HARI yang disinggung, hanya pada hari yang benar-benar
     * dilalui pemohon. Memakai puncak sebulan akan menolak orang karena
     * keramaian di tanggal yang tidak ia lewati: A di 1-10 Juni, pemohon di
     * 20-30 Juni, kuota 1 - puncak Juni memang 1, tapi mereka tidak bertemu.
     */
    public function bulan_terhalang($bidang, $mulai, $selesai, $abaikan_id = NULL)
    {
        $bulan_periode = $this->bulan_dalam_periode($mulai, $selesai);
        if ( ! $bulan_periode) { return []; }

        $slot = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, ['bidang_kode' => $bidang->kode])->result() as $b) {
            $slot[(int) $b->tahun . '-' . (int) $b->bulan] = $b;
        }
        $harian = $this->peta_harian($abaikan_id)[$bidang->kode] ?? [];
        $nama   = self::nama_bulan();
        $kuota  = (int) $bidang->kuota;

        $tutup = $diluar = $penuh = [];
        foreach ($this->hari_dalam_periode($mulai, $selesai) as $tanggal) {
            [$tahun, $bulan] = array_map('intval', explode('-', $tanggal));
            $kunci = $tahun . '-' . $bulan;

            if ( ! isset($slot[$kunci])) { $tutup[$kunci] = TRUE; continue; }

            // Bulan boleh terbuka tapi harinya di luar jendela - "tidak dibuka"
            // menyesatkan di situ, karena bulannya memang tersedia. Yang perlu
            // diketahui pemohon adalah tanggal berapa saja.
            $s = $slot[$kunci];
            if ($tanggal < $s->tgl_mulai || $tanggal > $s->tgl_selesai) { $diluar[$kunci] = $s; continue; }

            $isi = (int) ($harian[$tanggal] ?? 0);
            if ($isi >= $kuota && $isi > ($penuh[$kunci] ?? -1)) { $penuh[$kunci] = $isi; }
        }

        // Diurutkan menurut kalender, bukan menurut urutan penemuan - pesan
        // kesalahan yang melompat-lompat bulannya lebih sulit dibaca.
        $halangan = [];
        foreach ($bulan_periode as $kunci) {
            [$tahun, $bulan] = array_map('intval', explode('-', $kunci));
            $label = $nama[$bulan] . ' ' . $tahun;

            if (isset($tutup[$kunci])) {
                $halangan[] = $label . ' (tidak dibuka)';
            } elseif (isset($diluar[$kunci])) {
                $s = $diluar[$kunci];
                $halangan[] = $label . ' (hanya dibuka tanggal '
                    . (int) date('j', strtotime($s->tgl_mulai)) . '-'
                    . (int) date('j', strtotime($s->tgl_selesai)) . ')';
            } elseif (isset($penuh[$kunci])) {
                $halangan[] = $label . ' (penuh, ' . $penuh[$kunci] . ' dari ' . $kuota . ')';
            }
        }
        return $halangan;
    }

    /**
     * Siapa saja yang mengisi tiap bulan pada satu bidang: [bulan] => [baris, ...].
     *
     * Ada supaya angka "2 dari 2" di layar admin tidak muncul entah dari mana.
     * Hitungan yang tidak bisa ditelusuri ke orangnya adalah hitungan yang tidak
     * dipercaya, dan admin akan kembali menghitung manual di sebelahnya.
     */
    public function pendaftar_bidang($kode, $tahun)
    {
        $baris = $this->db->select(self::TABEL_DAFTAR . '.id, ' . self::TABEL_DAFTAR . '.periode_mulai,
                ' . self::TABEL_DAFTAR . '.periode_selesai, ' . self::TABEL_DAFTAR . '.status,
                ' . self::TABEL_DAFTAR . '.instansi_asal, usr_users.name AS nama_mahasiswa')
            ->from(self::TABEL_DAFTAR)
            ->join('usr_users', 'usr_users.id = ' . self::TABEL_DAFTAR . '.user_id', 'left')
            ->where(self::TABEL_DAFTAR . '.jenis', 'magang')
            ->where(self::TABEL_DAFTAR . '.bidang_kode', (string) $kode)
            ->where_in(self::TABEL_DAFTAR . '.status', self::STATUS_MEMAKAI_KUOTA)
            ->where(self::TABEL_DAFTAR . '.periode_mulai IS NOT NULL', NULL, FALSE)
            ->where(self::TABEL_DAFTAR . '.periode_selesai IS NOT NULL', NULL, FALSE)
            ->order_by(self::TABEL_DAFTAR . '.periode_mulai', 'ASC')
            ->get()->result();

        $per_bulan = [];
        foreach ($baris as $b) {
            foreach ($this->bulan_dalam_periode($b->periode_mulai, $b->periode_selesai) as $kunci) {
                [$t, $bl] = array_map('intval', explode('-', $kunci));
                if ($t !== (int) $tahun) { continue; }
                $per_bulan[$bl][] = $b;
            }
        }
        return $per_bulan;
    }

    /** Dipakai KemitraanPortal::simpan() dan Admin_Kemitraan::simpan_ubah(). */
    public function periode_terlalu_panjang($mulai, $selesai)
    {
        $awal  = date_create((string) $mulai);
        $akhir = date_create((string) $selesai);
        if ( ! $awal || ! $akhir || $akhir < $awal) { return FALSE; }

        return ((int) $awal->diff($akhir)->days + 1) > self::BATAS_HARI;
    }

    public function set_kuota($kode, $kuota)
    {
        $kuota = (int) $kuota;
        if ($kuota < 0)   { $kuota = 0; }
        if ($kuota > 255) { $kuota = 255; }   // batas TINYINT UNSIGNED
        return $this->db->where('bidang_kode', (string) $kode)
            ->update(self::TABEL_BIDANG, ['kuota' => $kuota]);
    }

    /**
     * Bidang tidak pernah dihapus dari sini - daftarnya adalah struktur
     * organisasi dinas, bukan sesuatu yang dikelola modul magang. Yang bisa
     * diatur cuma apakah ia menerima mahasiswa atau tidak.
     */
    public function set_aktif($kode, $aktif)
    {
        return $this->db->where('bidang_kode', (string) $kode)
            ->update(self::TABEL_BIDANG, ['aktif' => $aktif ? 1 : 0]);
    }
}
