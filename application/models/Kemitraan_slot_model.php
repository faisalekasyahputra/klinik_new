<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Divisi magang dan ketersediaan slotnya (migrasi 20260701000026).
 *
 * Ada sebagai model, bukan query yang ditulis langsung di controller seperti
 * tetangganya, karena dibaca dari DUA sisi: `KemitraanPortal` (papan slot,
 * pilihan divisi di formulir, penjagaan saat menyimpan) dan `Admin_Kemitraan`
 * (matriks pengelolaan). Aturan "buka atau tutup" yang ditulis dua kali adalah
 * aturan yang cepat atau lambat berselisih dengan dirinya sendiri.
 *
 * Ingat bentuk penyimpanannya: BARIS ADA berarti buka, tidak ada baris berarti
 * tutup. Tidak ada kolom `tersedia` yang bisa berbohong.
 */
class Kemitraan_slot_model extends CI_Model
{
    const TABEL_DIVISI = 'kkn_magang_divisi';
    const TABEL_SLOT   = 'kkn_magang_slot';

    /**
     * Batas panjang satu periode, dalam hari (400 ~ 13 bulan).
     *
     * Bukan aturan kosmetik: hitungan kehadiran menelusuri hari demi hari, jadi
     * satu baris berperiode 2020-2099 akan membuat setiap render halaman
     * menghitung puluhan ribu hari. Batas ini ditegakkan di validasi controller
     * lewat periode_terlalu_panjang(), dan diulang sebagai jaring terakhir di
     * hari_dalam_periode() supaya baris lama yang telanjur aneh tetap tidak
     * bisa menggantung halaman.
     */
    const BATAS_HARI = 400;

    /** Nama bulan Indonesia, indeks 1..12 — dipakai tampilan publik & admin. */
    public static function nama_bulan()
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    /**
     * @param bool $hanya_aktif FALSE hanya untuk layar admin, yang memang perlu
     *                          melihat divisi nonaktif supaya bisa menyalakannya
     *                          kembali. Sisi publik selalu TRUE.
     */
    public function divisi($hanya_aktif = TRUE)
    {
        if ($hanya_aktif) { $this->db->where('aktif', 1); }
        return $this->db->order_by('urutan', 'ASC')->order_by('nama', 'ASC')
            ->get(self::TABEL_DIVISI)->result();
    }

    public function divisi_by_id($id)
    {
        return $this->db->get_where(self::TABEL_DIVISI, ['id' => (int) $id])->row();
    }

    /**
     * Pendaftaran menyimpan NAMA divisi di kolom teks `divisi_atau_tema`, jadi
     * pencocokan saat menyimpan berjalan lewat nama, bukan id. Nama itu UNIQUE
     * di tabel divisi, jadi tetap satu baris yang bisa ditunjuk.
     */
    public function divisi_by_nama($nama)
    {
        return $this->db->get_where(self::TABEL_DIVISI, ['nama' => trim((string) $nama)])->row();
    }

    /**
     * Slot satu tahun sebagai peta [divisi_id][bulan] => baris slot.
     *
     * Nilainya barisnya sendiri, bukan sekadar TRUE, karena sejak migrasi
     * 20260701000028 tiap slot membawa rentang tanggalnya. `isset()` tetap
     * berarti "bulan ini dibuka", jadi pemanggil lama tidak berubah.
     */
    public function peta_slot($tahun)
    {
        $peta = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, ['tahun' => (int) $tahun])->result() as $baris) {
            $peta[(int) $baris->divisi_id][(int) $baris->bulan] = $baris;
        }
        return $peta;
    }

    /** Slot satu divisi satu tahun: [bulan] => baris. Untuk layar detail. */
    public function slot_divisi($divisi_id, $tahun)
    {
        $peta = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, [
            'divisi_id' => (int) $divisi_id, 'tahun' => (int) $tahun,
        ])->result() as $baris) {
            $peta[(int) $baris->bulan] = $baris;
        }
        return $peta;
    }

    /**
     * Label rentang satu slot: 'Juni' kalau sebulan penuh, 'Juni 1–15' kalau tidak.
     *
     * Sebulan penuh sengaja TIDAK ditulis "1–30": angka yang tidak membedakan
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
            . '–' . (int) date('j', strtotime($slot->tgl_selesai));
    }

    /** Tahun yang punya slot, untuk selektor tahun di layar admin. */
    public function tahun_tersedia()
    {
        $tahun = array_map(
            static function ($b) { return (int) $b->tahun; },
            $this->db->distinct()->select('tahun')->order_by('tahun', 'DESC')->get(self::TABEL_SLOT)->result()
        );
        // Tahun berjalan selalu ikut, meski belum punya satu slot pun —
        // kalau tidak, tahun yang masih kosong justru mustahil dibuka.
        $sekarang = (int) date('Y');
        if ( ! in_array($sekarang, $tahun, TRUE)) { $tahun[] = $sekarang; }
        rsort($tahun);
        return $tahun;
    }

    /**
     * Tulis ulang slot SATU divisi untuk satu tahun.
     *
     * Hapus-lalu-sisipkan, bukan selisih per bulan: layar detail mengirim
     * keadaan LENGKAP dua belas bulan, jadi bulan yang tidak dikirim memang
     * berarti tutup. Dibungkus transaksi supaya tidak pernah ada momen di mana
     * divisi itu tampak kosong bagi pengunjung yang kebetulan memuat halaman.
     *
     * Rentang DIJEPIT ke batas bulannya, bukan ditolak. Admin yang mengetik
     * 20 Juni – 5 Juli pada baris Juni jelas bermaksud "sampai akhir Juni";
     * memulangkannya dengan pesan galat untuk maksud yang sudah terbaca hanya
     * menambah satu putaran. Bulan Juli punya barisnya sendiri.
     *
     * @param array $bulan_data [bulan => ['buka' => '1', 'mulai' => 'Y-m-d', 'selesai' => 'Y-m-d']]
     */
    public function tulis_ulang_divisi($divisi_id, $tahun, array $bulan_data)
    {
        $divisi_id = (int) $divisi_id;
        $tahun     = (int) $tahun;
        if ( ! $this->divisi_by_id($divisi_id)) { return FALSE; }

        $this->db->trans_start();
        $this->db->delete(self::TABEL_SLOT, ['divisi_id' => $divisi_id, 'tahun' => $tahun]);

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
                'divisi_id'   => $divisi_id,
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

    /**
     * Daftar bulan "tahun-bulan" yang disinggung satu periode.
     *
     * Dipatok ke tanggal 1 supaya penambahan bulan tidak melompati Februari
     * ketika tanggal mulainya 29-31.
     */
    private function bulan_dalam_periode($mulai, $selesai)
    {
        $awal  = date_create((string) $mulai);
        $akhir = date_create((string) $selesai);
        if ( ! $awal || ! $akhir || $akhir < $awal) { return []; }

        $kunci  = [];
        $kursor = date_create($awal->format('Y-m-01'));
        $batas  = date_create($akhir->format('Y-m-01'));
        while ($kursor <= $batas) {
            $kunci[] = (int) $kursor->format('Y') . '-' . (int) $kursor->format('n');
            $kursor->modify('+1 month');
        }
        return $kunci;
    }

    /**
     * Berapa mahasiswa hadir BERSAMAAN pada tiap hari: [nama divisi]['Y-m-d'] => n.
     *
     * Harian, bukan bulanan, dan itu inti seluruh hitungan ini. Versi pertama
     * menghitung "berapa pendaftaran menyentuh bulan ini", sehingga dua orang
     * yang tidak pernah bertemu satu hari pun bisa saling menghalangi: A pulang
     * 15 Juli, B datang 16 Juli, dan Juli tercatat terisi dua. Yang benar-benar
     * terbatas adalah meja dan pembimbing — dan itu diukur per hari.
     *
     * DITURUNKAN, tidak pernah disimpan. Kolom "terisi" berarti dua sumber
     * kebenaran yang harus disinkronkan pada setiap pendaftaran, penolakan, dan
     * penyuntingan admin — dan yang satu pasti menyimpang dari yang lain.
     *
     * `Diajukan` ikut dihitung, bukan cuma `Diterima`: kuota berkurang sejak
     * mahasiswa mendaftar, bukan sejak admin menyetujui. `Ditolak` tidak
     * menghitung — ia melepaskan tempatnya kembali.
     *
     * Dicocokkan lewat NAMA karena itulah yang tersimpan di `divisi_atau_tema`.
     *
     * @param int|null $abaikan_id Pendaftaran yang tidak ikut dihitung. Dipakai
     *   saat admin menyunting satu baris: baris itu tidak boleh menghalangi
     *   dirinya sendiri.
     */
    private function peta_harian($abaikan_id = NULL)
    {
        $this->db->select('id, divisi_atau_tema, periode_mulai, periode_selesai')
            ->where('jenis', 'magang')
            ->where_in('status', ['Diajukan', 'Diterima'])
            ->where('divisi_atau_tema IS NOT NULL')
            ->where('periode_mulai IS NOT NULL')
            ->where('periode_selesai IS NOT NULL');
        if ($abaikan_id !== NULL) { $this->db->where('id !=', (int) $abaikan_id); }

        $harian = [];
        foreach ($this->db->get('kkn_magang_pendaftaran')->result() as $baris) {
            $nama = (string) $baris->divisi_atau_tema;
            foreach ($this->hari_dalam_periode($baris->periode_mulai, $baris->periode_selesai) as $tanggal) {
                $harian[$nama][$tanggal] = ($harian[$nama][$tanggal] ?? 0) + 1;
            }
        }
        return $harian;
    }

    /**
     * Tanggal 'Y-m-d' sepanjang satu periode.
     *
     * ponytail: dibatasi BATAS_HARI. Periode selama itu tidak mungkin magang
     * sungguhan, dan tanpa batas satu baris berperiode 2020-2099 membuat
     * halaman menghitung sepuluh ribu hari. Batas sebenarnya ditegakkan lebih
     * awal di validasi controller; yang di sini jaring terakhir supaya baris
     * lama yang aneh tidak pernah bisa menggantung halaman.
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
     * Puncak kehadiran bersamaan per divisi per bulan: [nama]['Y-n'] => n.
     *
     * Dipakai TAMPILAN saja (papan publik dan matriks admin) — satu angka per
     * sel. Penjagaan pendaftaran TIDAK boleh memakai angka ini: puncak sebulan
     * bisa terjadi pada hari-hari yang tidak disentuh pendaftar baru. Untuk itu
     * ada bulan_terhalang(), yang memeriksa hari demi hari.
     */
    public function peta_terisi($abaikan_id = NULL)
    {
        $peta = [];
        foreach ($this->peta_harian($abaikan_id) as $nama => $hari) {
            foreach ($hari as $tanggal => $jumlah) {
                [$tahun, $bulan] = array_map('intval', explode('-', $tanggal));
                $kunci = $tahun . '-' . $bulan;
                if ($jumlah > ($peta[$nama][$kunci] ?? 0)) { $peta[$nama][$kunci] = $jumlah; }
            }
        }
        return $peta;
    }

    /**
     * Alasan satu periode tidak bisa diambil pada satu divisi.
     *
     * Kosong berarti boleh. Isinya kalimat siap tempel, mis.
     * "Juli 2027 (tidak dibuka)" atau "Juni 2027 (penuh, 2 dari 2)".
     *
     * Diperiksa SETIAP bulan yang disinggung, bukan cuma bulan mulai: magang
     * Juni-Agustus dengan Juli tertutup berarti papan slotnya berbohong kepada
     * orang yang membacanya.
     */
    public function bulan_terhalang($divisi, $mulai, $selesai, $abaikan_id = NULL)
    {
        $bulan_periode = $this->bulan_dalam_periode($mulai, $selesai);
        if ( ! $bulan_periode) { return []; }

        $slot = [];
        foreach ($this->db->get_where(self::TABEL_SLOT, ['divisi_id' => (int) $divisi->id])->result() as $b) {
            $slot[(int) $b->tahun . '-' . (int) $b->bulan] = $b;
        }
        $harian = $this->peta_harian($abaikan_id)[$divisi->nama] ?? [];
        $nama   = self::nama_bulan();
        $kuota  = (int) $divisi->kuota;

        // Diperiksa HARI DEMI HARI, hanya pada hari yang benar-benar disentuh
        // pemohon. Memakai puncak sebulan akan menolak orang karena keramaian
        // yang terjadi di tanggal yang tidak ia lalui: A di 1-10 Juni, pemohon
        // di 20-30 Juni, kuota 1 — puncak Juni memang 1, tapi mereka tidak
        // pernah bertemu.
        $tutup = $diluar = $penuh = [];
        foreach ($this->hari_dalam_periode($mulai, $selesai) as $tanggal) {
            [$tahun, $bulan] = array_map('intval', explode('-', $tanggal));
            $kunci = $tahun . '-' . $bulan;

            if ( ! isset($slot[$kunci])) { $tutup[$kunci] = TRUE; continue; }

            // Bulan boleh terbuka tapi harinya di luar jendela — "tidak dibuka"
            // akan menyesatkan di situ, karena bulannya memang tersedia. Yang
            // perlu diketahui pemohon adalah tanggal berapa saja.
            $s = $slot[$kunci];
            if ($tanggal < $s->tgl_mulai || $tanggal > $s->tgl_selesai) { $diluar[$kunci] = $s; continue; }

            $isi = (int) ($harian[$tanggal] ?? 0);
            if ($isi >= $kuota && $isi > ($penuh[$kunci] ?? -1)) { $penuh[$kunci] = $isi; }
        }

        // Diurutkan menurut kalender, bukan menurut urutan penemuan — pesan
        // kesalahan yang melompat-lompat bulannya lebih sulit dibaca daripada
        // yang berurutan.
        $halangan = [];
        foreach ($bulan_periode as $kunci) {
            [$tahun, $bulan] = array_map('intval', explode('-', $kunci));
            $label = $nama[$bulan] . ' ' . $tahun;

            if (isset($tutup[$kunci])) {
                $halangan[] = $label . ' (tidak dibuka)';
            } elseif (isset($diluar[$kunci])) {
                $s = $diluar[$kunci];
                $halangan[] = $label . ' (hanya dibuka tanggal '
                    . (int) date('j', strtotime($s->tgl_mulai)) . '–'
                    . (int) date('j', strtotime($s->tgl_selesai)) . ')';
            } elseif (isset($penuh[$kunci])) {
                $halangan[] = $label . ' (penuh, ' . $penuh[$kunci] . ' dari ' . $kuota . ')';
            }
        }
        return $halangan;
    }

    /**
     * Siapa saja yang mengisi tiap bulan pada satu divisi: [bulan] => [baris, ...].
     *
     * Ada supaya angka "2 dari 2" di layar admin tidak muncul entah dari mana.
     * Hitungan yang tidak bisa ditelusuri ke orangnya adalah hitungan yang
     * tidak dipercaya, dan admin akan kembali menghitung manual di sebelahnya.
     *
     * Hanya yang MEMAKAN kuota (Diajukan dan Diterima) — sama persis dengan
     * yang dihitung peta_harian(), supaya daftar ini selalu menjelaskan angka
     * yang sedang ditampilkan, bukan angka lain.
     */
    public function pendaftar_divisi($nama_divisi, $tahun)
    {
        $baris = $this->db->select('kkn_magang_pendaftaran.id, kkn_magang_pendaftaran.periode_mulai,
                kkn_magang_pendaftaran.periode_selesai, kkn_magang_pendaftaran.status,
                kkn_magang_pendaftaran.instansi_asal, usr_users.name AS nama_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.jenis', 'magang')
            ->where('kkn_magang_pendaftaran.divisi_atau_tema', $nama_divisi)
            ->where_in('kkn_magang_pendaftaran.status', ['Diajukan', 'Diterima'])
            ->where('kkn_magang_pendaftaran.periode_mulai IS NOT NULL')
            ->where('kkn_magang_pendaftaran.periode_selesai IS NOT NULL')
            ->order_by('kkn_magang_pendaftaran.periode_mulai', 'ASC')
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

    public function set_kuota($id, $kuota)
    {
        $kuota = (int) $kuota;
        if ($kuota < 0)   { $kuota = 0; }
        if ($kuota > 255) { $kuota = 255; }   // batas TINYINT UNSIGNED
        return $this->db->where('id', (int) $id)
            ->update(self::TABEL_DIVISI, ['kuota' => $kuota]);
    }

    public function tambah_divisi($nama)
    {
        $nama = trim((string) $nama);
        if ($nama === '') { return FALSE; }
        if ($this->db->get_where(self::TABEL_DIVISI, ['nama' => $nama])->row()) { return FALSE; }

        $terakhir = $this->db->select_max('urutan')->get(self::TABEL_DIVISI)->row();
        return $this->db->insert(self::TABEL_DIVISI, [
            'nama'   => $nama,
            'urutan' => (int) ($terakhir->urutan ?? 0) + 10,
        ]);
    }

    public function ubah_status_divisi($id, $aktif)
    {
        return $this->db->where('id', (int) $id)
            ->update(self::TABEL_DIVISI, ['aktif' => $aktif ? 1 : 0]);
    }

    /**
     * Ganti nama divisi — DAN nama yang tersimpan di pendaftarannya.
     *
     * Pendaftaran menyimpan nama divisi sebagai teks, bukan id. Mengganti nama
     * tanpa ikut memperbarui baris-baris itu akan memutus mereka dari divisinya:
     * hitungan kehadiran berhenti mengenalinya, dan papan slot berhenti
     * menampilkan beban yang sebenarnya. Keduanya dalam satu transaksi.
     */
    public function ganti_nama_divisi($id, $nama_baru)
    {
        $nama_baru = trim((string) $nama_baru);
        $divisi    = $this->divisi_by_id($id);
        if ( ! $divisi || $nama_baru === '') { return FALSE; }
        if ($nama_baru === $divisi->nama)    { return TRUE; }

        $bentrok = $this->divisi_by_nama($nama_baru);
        if ($bentrok) { return FALSE; }

        $this->db->trans_start();
        $this->db->where('id', (int) $divisi->id)->update(self::TABEL_DIVISI, ['nama' => $nama_baru]);
        $this->db->where('jenis', 'magang')->where('divisi_atau_tema', $divisi->nama)
            ->update('kkn_magang_pendaftaran', ['divisi_atau_tema' => $nama_baru]);
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /** Berapa pendaftaran magang yang menunjuk ke divisi ini, apa pun statusnya. */
    public function jumlah_pendaftaran_divisi($nama_divisi)
    {
        return (int) $this->db->where('jenis', 'magang')
            ->where('divisi_atau_tema', $nama_divisi)
            ->count_all_results('kkn_magang_pendaftaran');
    }

    /**
     * Hapus divisi — HANYA kalau belum pernah dipakai pendaftaran mana pun.
     *
     * Yang sudah dipakai cukup dinonaktifkan. Menghapusnya menyeret slotnya
     * lewat ON DELETE CASCADE, sementara pendaftaran lama menyimpan NAMA divisi
     * sebagai teks dan akan menunjuk ke sesuatu yang tidak ada lagi — riwayat
     * yang menunjuk ke ruang kosong lebih buruk daripada divisi mati yang masih
     * bisa dibaca.
     *
     * @return bool|string TRUE kalau terhapus, atau pesan alasan penolakan.
     */
    public function hapus_divisi($id)
    {
        $divisi = $this->divisi_by_id($id);
        if ( ! $divisi) { return 'Divisi tidak ditemukan.'; }

        $dipakai = $this->jumlah_pendaftaran_divisi($divisi->nama);
        if ($dipakai > 0) {
            return 'Divisi ini sudah dipakai ' . $dipakai . ' pendaftaran, jadi tidak bisa dihapus. '
                . 'Nonaktifkan saja — riwayatnya tetap terbaca dan ia berhenti ditawarkan ke pendaftar baru.';
        }

        $this->db->delete(self::TABEL_DIVISI, ['id' => (int) $divisi->id]);
        return TRUE;
    }
}
