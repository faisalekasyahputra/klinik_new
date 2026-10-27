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
            'catatan' => 'Salinan dari ' . $aktif, 'matriks_menentukan' => (int) ($this->versi($aktif)['matriks_menentukan'] ?? 0)]);
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
     * Baris matriks milik satu program: nama di matriks dipetakan ke kode katalog (kode_katalog, mis. "PK RTLH" -> rtlh),
     * atau diawali nama katalog program itu (program baru dari layar ini memakai namanya sendiri).
     */
    public function milik_program(array $baris, array $program)
    {
        $kode = get_instance()->matriks_program_ruleset->kode_katalog($baris['nama_skema']);
        return $kode !== NULL ? $kode === $program['kode_program'] : stripos($baris['nama_skema'], $program['nama_program']) === 0;
    }

    /**
     * Simpan aturan SATU program di draf: $v = [ikut, desil[], tanpa_desil, cek, urutan] dan $isian_baris = baris matriks
     * milik program itu [kolom => nilai, hapus]. Baris program lain tidak disentuh; baris program ini menempati posisi
     * prioritas baris lamanya (baris baru di akhir). Mengembalikan daftar galat (kosong = tersimpan).
     */
    public function simpan_program($program_id, array $v, array $isian_baris)
    {
        $d = $this->draf();
        if ( ! $d) { return ['Belum ada draf.']; }
        $p = $this->db->select('id, kode_program, nama_program')->get_where('sf_program', ['id' => (int) $program_id])->row_array();
        if ( ! $p) { return ['Program tidak ditemukan.']; }
        $lama = array_column($this->program($d['kode']), NULL, 'program_id');
        $galat = [];
        $aturan = NULL;
        if ( ! empty($v['ikut'])) {
            $desil = array_values(array_unique(array_map('intval', (array) ($v['desil'] ?? []))));
            sort($desil);
            $cek = (string) ($v['cek'] ?? '');
            if ( ! isset(Warga_ruleset::JENIS_CEK[$cek])) { $galat[] = 'Jenis cek data tidak dikenal.'; }
            if ( ! $desil || array_diff($desil, range(1, 10))) { $galat[] = 'Pilih minimal satu desil antara 1 dan 10.'; }
            // Kode alasan lama dipertahankan bila jenis ceknya sama (label warga tetap), selain itu kode umum jenis itu.
            $alasan = ($lama[$p['id']]['jenis_cek'] ?? NULL) === $cek ? $lama[$p['id']]['kode_alasan'] : (Warga_ruleset::JENIS_CEK[$cek]['alasan'] ?? NULL);
            $aturan = ['versi' => $d['kode'], 'program_id' => (int) $p['id'], 'urutan' => max(1, min(99, (int) ($v['urutan'] ?? 99))),
                'desil_sasaran' => implode(',', $desil), 'kandidat_tanpa_desil' => empty($v['tanpa_desil']) ? 0 : 1,
                'jenis_cek' => $cek, 'kode_alasan' => $alasan];
        } elseif (count($lama) === 1 && isset($lama[$p['id']])) {
            $galat[] = 'Minimal satu program harus ditawarkan.';
        }
        $P = Matriks_program_ruleset::PILIHAN;
        $baru = [];
        foreach (array_values($isian_baris) as $i => $b) {
            if ( ! is_array($b) || ! empty($b['hapus'])) { continue; }
            $no = $i + 1;
            if ( ! isset($P['rentang_penghasilan'][$b['rentang_penghasilan'] ?? ''])) { $galat[] = "Baris $no: rentang penghasilan wajib dipilih."; continue; }
            $r = ['versi' => $d['kode'], 'rentang_penghasilan' => $b['rentang_penghasilan'], 'status_dtks' => NULL];
            foreach (['lahan', 'rumah', 'lingkungan', 'finansial', 'usia', 'keluarga'] as $k) {
                $nilai = (string) ($b[$k] ?? '');
                if ($nilai !== '' && ! isset($P[$k][$nilai])) { $galat[] = "Baris $no: nilai $k tidak dikenal."; continue 2; }
                $r[$k] = $nilai === '' ? NULL : $nilai;
            }
            $desil = array_values(array_unique(array_map('intval', (array) ($b['desil'] ?? []))));
            sort($desil);
            if (array_diff($desil, range(1, 10))) { $galat[] = "Baris $no: desil di luar 1 sampai 10."; continue; }
            $r['desil'] = $desil ? implode(',', $desil) : NULL; // kolom B tidak dicocokkan preliminary(), dibawa apa adanya
            $nama = trim((string) ($b['nama_skema'] ?? ''));
            if ($nama === '' || mb_strlen($nama) > 100) { $galat[] = "Baris $no: nama di matriks wajib diisi, maksimal 100 karakter."; continue; }
            if ( ! $this->milik_program(['nama_skema' => $nama], $p)) { $galat[] = "Baris $no: nama di matriks harus diawali \"{$p['nama_program']}\"."; continue; }
            $r['nama_skema'] = $nama;
            $baru[] = $r;
        }
        if ($galat) { return $galat; }

        // Susun ulang seluruh matriks draf: baris program ini menggantikan baris lamanya di posisi pertamanya.
        $semua = []; $sisip = FALSE;
        foreach ($this->matriks($d['kode']) as $r) {
            if ($this->milik_program($r, $p)) {
                if ( ! $sisip) { array_push($semua, ...$baru); $sisip = TRUE; }
                continue;
            }
            unset($r['id']);
            $semua[] = $r;
        }
        if ( ! $sisip) { array_push($semua, ...$baru); }
        if ( ! $semua) { return ['Matriks minimal satu baris.']; }
        foreach ($semua as $i => &$r) { $r['urutan'] = $i + 1; }
        unset($r);

        $this->db->trans_start();
        $this->db->delete('aturan_program', ['versi' => $d['kode'], 'program_id' => (int) $p['id']]);
        if ($aturan) { $this->db->insert('aturan_program', $aturan); }
        $this->db->delete('aturan_matriks_baris', ['versi' => $d['kode']]);
        $this->db->insert_batch('aturan_matriks_baris', $semua);
        $this->db->trans_complete();
        return $this->db->trans_status() ? [] : ['Aturan gagal disimpan.'];
    }

    /** Sakelar pilihan B di draf: matriks ikut menentukan program yang bisa dipilih. */
    public function atur_matriks_menentukan($nyala)
    {
        $d = $this->draf();
        if ( ! $d) { return FALSE; }
        return (bool) $this->db->where('id', $d['id'])->update('aturan_versi', ['matriks_menentukan' => $nyala ? 1 : 0]);
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
        $katalog = array_column($this->db->select('kode_program, nama_program')->get('sf_program')->result_array(), 'nama_program', 'kode_program');
        [$wa, $ma] = $mesin($aktif);
        [$wd, $md] = $mesin($d['kode']);
        $nama = array_column($this->db->select('kode_program, nama_program')->get('sf_program')->result_array(), 'nama_program', 'kode_program');
        $hasil = []; $berubah = 0;
        foreach (self::contoh_warga() as [$label, $draft, $profil]) {
            // Sama dengan Warga::step: hasil desil, lalu disaring matriks bila versi itu memakai pilihan B.
            $tawar = function ($w, $m) use ($draft, $profil, $nama, $katalog) {
                $hasil = [];
                foreach ($w->route_candidates($profil['desil_kesejahteraan']) as $c) { $hasil[] = $w->evaluate($c, $draft, $profil) + ['program_code' => $c]; }
                if ($w->matriks_menentukan) { $hasil = $m->saring($hasil, $m->preliminary($draft, $profil, date('Y-m-d'))['items'], $katalog); }
                $out = [];
                foreach ($hasil as $e) { if (in_array($e['status_kelayakan'], ['eligible', 'potential'], TRUE)) { $out[] = $nama[$e['program_code']] ?? $e['program_code']; } }
                return $out;
            };
            $awal = fn($m) => array_values(array_unique(array_column($m->preliminary($draft, $profil, date('Y-m-d'))['items'], 'program_name')));
            $a = [$tawar($wa, $ma), $awal($ma)]; $b = [$tawar($wd, $md), $awal($md)];
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
            if (in_array($desil, [5, 6], TRUE)) {
                $contoh[] = ["Desil $desil, Rp" . number_format($rp, 0, ',', '.') . ', sewa, menikah, usia 65', [
                    'jalur_penilaian' => 'candidate_land', 'kepemilikan_rumah' => 'rent', 'rumah_lain' => '0', 'tanah_lain' => '0',
                    'kawasan_perumahan' => 'good', 'jml_kk' => 1], ['birth_date' => '1961-03-10'] + $profil];
            }
            $contoh[] = ["Desil $desil, Rp" . number_format($rp, 0, ',', '.') . ', rumah sendiri rusak berat', [
                'jalur_penilaian' => 'existing_house', 'kepemilikan_rumah' => 'owned', 'rumah_lain' => '0', 'tanah_lain' => '0',
                'kawasan_perumahan' => 'slum', 'jml_kk' => 1, 'kondisi_atap' => 'severe_damage_or_absent',
                'kondisi_kolom' => 'severe_damage_or_absent', 'sumber_air' => 'pdam', 'jenis_kloset' => 'swan_neck'], $profil];
        }
        return $contoh;
    }
}
