<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Versi aturan rekomendasi (migrasi 084 dan 085): versi terbit dipakai mesin, satu draf disunting Super Admin,
 * lalu diterbitkan. Nilai yang boleh disimpan selalu dari daftar tetap di mesin (Warga_ruleset::JENIS_CEK,
 * Matriks_program_ruleset::PILIHAN), jadi layar tidak bisa menyimpan kategori yang tidak dikenal mesin.
 */
class Aturan_model extends CI_Model {

    /** Kode versi berstatus terbit terbaru; NULL bila belum ada (migrasi 085 belum jalan). */
    public function kode_aktif()
    {
        $r = $this->db->select('kode')->where('status', 'terbit')
            ->order_by('diterbitkan_pada', 'DESC')->order_by('id', 'DESC')->limit(1)->get('aturan_versi')->row_array();
        return $r['kode'] ?? NULL;
    }

    public function versi($kode)
    {
        return $this->db->get_where('aturan_versi', ['kode' => (string) $kode])->row_array();
    }

    public function draf()
    {
        return $this->db->order_by('id', 'DESC')->get_where('aturan_versi', ['status' => 'draf'], 1)->row_array();
    }

    public function riwayat($batas = 10)
    {
        return $this->db->where_in('status', ['terbit', 'arsip'])->order_by('diterbitkan_pada', 'DESC')->order_by('id', 'DESC')
            ->limit($batas)->get('aturan_versi')->result_array();
    }

    /** Baris aturan_program satu versi, digabung dengan katalog (urut). */
    public function program($kode)
    {
        return $this->db->select('a.*, p.kode_program, p.nama_program, p.aktif')
            ->from('aturan_program a')->join('sf_program p', 'p.id = a.program_id')
            ->where('a.versi', (string) $kode)->order_by('a.urutan')->order_by('a.id')->get()->result_array();
    }

    public function matriks($kode)
    {
        return $this->db->order_by('urutan')->get_where('aturan_matriks_baris', ['versi' => (string) $kode])->result_array();
    }

    /** Draf yang ada, atau draf baru salinan versi aktif. */
    public function buat_draf($user_id)
    {
        if ($d = $this->draf()) { return $d; }
        $aktif = $this->kode_aktif();
        if ($aktif === NULL) { return NULL; }
        $kode = 'ATURAN-' . date('Ymd-His');
        $this->db->trans_start();
        $this->db->insert('aturan_versi', ['kode' => $kode, 'status' => 'draf', 'dibuat_oleh' => (int) $user_id ?: NULL,
            'catatan' => 'Salinan dari ' . $aktif]);
        $this->db->query('INSERT INTO aturan_program (versi, program_id, urutan, desil_sasaran, kandidat_tanpa_desil, jenis_cek, kode_alasan)
            SELECT ?, program_id, urutan, desil_sasaran, kandidat_tanpa_desil, jenis_cek, kode_alasan FROM aturan_program WHERE versi = ?', [$kode, $aktif]);
        $this->db->query('INSERT INTO aturan_matriks_baris (versi, urutan, rentang_penghasilan, desil, status_dtks, lahan, rumah, lingkungan, finansial, usia, keluarga, nama_skema)
            SELECT ?, urutan, rentang_penghasilan, desil, status_dtks, lahan, rumah, lingkungan, finansial, usia, keluarga, nama_skema
            FROM aturan_matriks_baris WHERE versi = ?', [$kode, $aktif]);
        $this->db->trans_complete();
        return $this->db->trans_status() ? $this->versi($kode) : NULL;
    }

    public function buang_draf()
    {
        $d = $this->draf();
        if ($d) { $this->db->delete('aturan_versi', ['id' => $d['id'], 'status' => 'draf']); } // baris aturan ikut lewat FK CASCADE
        return $d;
    }

    /**
     * Ganti seluruh aturan program di draf dari isian layar. $isian[program_id] = [ikut, desil[], tanpa_desil, cek, urutan].
     * Mengembalikan daftar galat (kosong = tersimpan). Program yang tidak dicentang "ikut" tidak ditawarkan ke warga.
     */
    public function simpan_program_draf(array $isian)
    {
        $d = $this->draf();
        if ( ! $d) { return ['Belum ada draf.']; }
        $lama = array_column($this->program($d['kode']), NULL, 'program_id');
        $katalog = array_column($this->db->select('id, kode_program, nama_program')->get('sf_program')->result_array(), NULL, 'id');
        $baris = []; $galat = [];
        foreach ($isian as $pid => $v) {
            $pid = (int) $pid;
            if ( ! isset($katalog[$pid]) || ! is_array($v) || empty($v['ikut'])) { continue; }
            $nama = $katalog[$pid]['nama_program'];
            $desil = array_values(array_unique(array_map('intval', (array) ($v['desil'] ?? []))));
            sort($desil);
            $cek = (string) ($v['cek'] ?? '');
            if ( ! isset(Warga_ruleset::JENIS_CEK[$cek])) { $galat[] = "$nama: jenis cek tidak dikenal."; continue; }
            if ( ! $desil || array_diff($desil, range(1, 10))) { $galat[] = "$nama: pilih minimal satu desil antara 1 dan 10."; continue; }
            // Kode alasan lama dipertahankan bila jenis ceknya sama (label warga tetap), selain itu kode umum jenis itu.
            $alasan = ($lama[$pid]['jenis_cek'] ?? NULL) === $cek ? $lama[$pid]['kode_alasan'] : Warga_ruleset::JENIS_CEK[$cek]['alasan'];
            $baris[] = ['versi' => $d['kode'], 'program_id' => $pid, 'urutan' => max(1, min(99, (int) ($v['urutan'] ?? 99))),
                'desil_sasaran' => implode(',', $desil), 'kandidat_tanpa_desil' => empty($v['tanpa_desil']) ? 0 : 1,
                'jenis_cek' => $cek, 'kode_alasan' => $alasan];
        }
        if ( ! $galat && ! $baris) { $galat[] = 'Minimal satu program harus ditawarkan.'; }
        if ($galat) { return $galat; }
        $this->db->trans_start();
        $this->db->delete('aturan_program', ['versi' => $d['kode']]);
        $this->db->insert_batch('aturan_program', $baris);
        $this->db->trans_complete();
        return $this->db->trans_status() ? [] : ['Aturan program gagal disimpan.'];
    }

    /** Ganti seluruh baris matriks draf. $isian = daftar baris [kolom => nilai, hapus]; urutan = urutan kiriman. */
    public function simpan_matriks_draf(array $isian)
    {
        $d = $this->draf();
        if ( ! $d) { return ['Belum ada draf.']; }
        $P = Matriks_program_ruleset::PILIHAN;
        $baris = []; $galat = [];
        foreach (array_values($isian) as $i => $v) {
            if ( ! is_array($v) || ! empty($v['hapus'])) { continue; }
            $no = $i + 1;
            $r = ['versi' => $d['kode'], 'urutan' => count($baris) + 1];
            if ( ! isset($P['rentang_penghasilan'][$v['rentang_penghasilan'] ?? ''])) { $galat[] = "Baris $no: rentang penghasilan wajib dipilih."; continue; }
            $r['rentang_penghasilan'] = $v['rentang_penghasilan'];
            foreach (['lahan', 'rumah', 'lingkungan', 'finansial', 'usia', 'keluarga'] as $k) {
                $nilai = (string) ($v[$k] ?? '');
                if ($nilai !== '' && ! isset($P[$k][$nilai])) { $galat[] = "Baris $no: nilai $k tidak dikenal."; continue 2; }
                $r[$k] = $nilai === '' ? NULL : $nilai;
            }
            $desil = array_values(array_unique(array_map('intval', (array) ($v['desil'] ?? []))));
            sort($desil);
            if (array_diff($desil, range(1, 10))) { $galat[] = "Baris $no: desil di luar 1 sampai 10."; continue; }
            $r['desil'] = $desil ? implode(',', $desil) : NULL;
            $r['status_dtks'] = NULL; // kolom C tidak dicocokkan lagi (UAT dinas warga #10), tidak disunting
            $nama = trim((string) ($v['nama_skema'] ?? ''));
            if ($nama === '' || mb_strlen($nama) > 100) { $galat[] = "Baris $no: nama program di matriks wajib diisi, maksimal 100 karakter."; continue; }
            $r['nama_skema'] = $nama;
            $baris[] = $r;
        }
        if ( ! $galat && ! $baris) { $galat[] = 'Matriks minimal satu baris.'; }
        if ($galat) { return $galat; }
        $this->db->trans_start();
        $this->db->delete('aturan_matriks_baris', ['versi' => $d['kode']]);
        $this->db->insert_batch('aturan_matriks_baris', $baris);
        $this->db->trans_complete();
        return $this->db->trans_status() ? [] : ['Matriks gagal disimpan.'];
    }

    /** Terbitkan draf: draf jadi terbit, terbit sebelumnya jadi arsip. Mengembalikan [kode baru, kode lama] atau NULL. */
    public function terbitkan($user_id)
    {
        $d = $this->draf();
        if ( ! $d || ! $this->program($d['kode']) || ! $this->matriks($d['kode'])) { return NULL; }
        $lama = $this->kode_aktif();
        $this->db->trans_start();
        $this->db->where('status', 'terbit')->update('aturan_versi', ['status' => 'arsip']);
        $this->db->where('id', $d['id'])->update('aturan_versi', ['status' => 'terbit',
            'diterbitkan_oleh' => (int) $user_id ?: NULL, 'diterbitkan_pada' => date('Y-m-d H:i:s')]);
        $this->db->trans_complete();
        return $this->db->trans_status() ? [$d['kode'], $lama] : NULL;
    }

    /**
     * Uji coba: contoh warga sintetis dinilai dengan versi aktif dan draf. Hasil: daftar contoh dengan program yang
     * ditawarkan (eligible/potential) di kedua versi, dan jumlah contoh yang berubah.
     */
    public function uji_coba()
    {
        $d = $this->draf();
        $aktif = $this->kode_aktif();
        if ( ! $d || ! $aktif) { return NULL; }
        $mesin = function ($kode) {
            return [new Warga_ruleset(NULL, $kode), new Matriks_program_ruleset(NULL, $kode)];
        };
        [$wa, $ma] = $mesin($aktif);
        [$wd, $md] = $mesin($d['kode']);
        $nama = array_column($this->db->select('kode_program, nama_program')->get('sf_program')->result_array(), 'nama_program', 'kode_program');
        $hasil = []; $berubah = 0;
        foreach (self::contoh_warga() as [$label, $draft, $profil]) {
            $tawar = function ($w) use ($draft, $profil, $nama) {
                $out = [];
                foreach ($w->route_candidates($profil['desil_kesejahteraan']) as $c) {
                    $e = $w->evaluate($c, $draft, $profil);
                    if (in_array($e['status_kelayakan'], ['eligible', 'potential'], TRUE)) { $out[] = $nama[$c] ?? $c; }
                }
                return $out;
            };
            $awal = fn($m) => array_values(array_unique(array_column($m->preliminary($draft, $profil, date('Y-m-d'))['items'], 'program_name')));
            $a = [$tawar($wa), $awal($ma)]; $b = [$tawar($wd), $awal($md)];
            $beda = $a !== $b;
            $berubah += $beda ? 1 : 0;
            $hasil[] = ['label' => $label, 'aktif' => $a, 'draf' => $b, 'berubah' => $beda];
        }
        return ['aktif' => $aktif, 'draf' => $d['kode'], 'contoh' => $hasil, 'berubah' => $berubah];
    }

    /** Contoh warga sintetis untuk uji coba: satu per desil, rumah milik rusak vs bukan pemilik. */
    public static function contoh_warga()
    {
        $penghasilan = [1 => 900000, 2 => 1800000, 3 => 2000000, 4 => 2500000, 5 => 3500000, 6 => 4500000, 7 => 6000000, 8 => 8000000, 9 => 9000000, 10 => 15000000];
        $contoh = [];
        foreach ($penghasilan as $desil => $rp) {
            $profil = ['desil_kesejahteraan' => $desil, 'penghasilan_bulanan' => $rp, 'stabilitas_pekerjaan' => 'permanent',
                'status_perkawinan' => 'married', 'birth_date' => '1990-05-01', 'mampu_swadaya' => 'capable', 'mode_sumber' => 'api'];
            $contoh[] = ["Desil $desil, Rp" . number_format($rp, 0, ',', '.') . ', sewa, menikah', [
                'jalur_penilaian' => 'candidate_land', 'kepemilikan_rumah' => 'rent', 'rumah_lain' => '0', 'tanah_lain' => '0',
                'kawasan_perumahan' => 'good', 'jml_kk' => 1], $profil];
            $contoh[] = ["Desil $desil, Rp" . number_format($rp, 0, ',', '.') . ', rumah sendiri rusak berat', [
                'jalur_penilaian' => 'existing_house', 'kepemilikan_rumah' => 'owned', 'rumah_lain' => '0', 'tanah_lain' => '0',
                'kawasan_perumahan' => 'slum', 'jml_kk' => 1, 'kondisi_atap' => 'severe_damage_or_absent',
                'kondisi_kolom' => 'severe_damage_or_absent', 'sumber_air' => 'pdam', 'jenis_kloset' => 'swan_neck'], $profil];
        }
        return $contoh;
    }
}
