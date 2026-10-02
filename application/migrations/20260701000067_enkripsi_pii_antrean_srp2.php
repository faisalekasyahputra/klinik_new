<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fase 3 normalisasi langkah 1 (keputusan pemilik produk 2 Okt 2026): data pribadi warga
 * dan pemohon SRP2 berhenti tersimpan polos.
 *
 * KEPUTUSAN PER KOLOM sf_housing_queue (dibaca dari kode dan data, 2 Okt 2026):
 *   Baris jalur wizard (ber-assessment_id) SUDAH menulis keempat kolom ini NULL
 *   (Housing_assessment_model::submit_owned_assessment); identitasnya dibaca lewat
 *   assessment_id -> sf_penilaian_perumahan -> sf_profil_warga yang sudah terenkripsi.
 *   Yang mengisi kolom ini hanya jalur diagnosa LAMA (Program_model, sudah mati sejak
 *   27 Sep 2026), dan barisnya TIDAK punya assessment_id (9 dari 11 baris saat dibaca),
 *   jadi tidak ada join yang bisa memulihkan nilainya. Menghapus duplikatnya berarti
 *   menghapus satu-satunya salinan, maka keempatnya DISIMPAN sebagai salinan terenkripsi:
 *   - nik_pengaju        -> nik_pengaju_ciphertext + nik_pengaju_lookup_hash (KEY, bukan
 *                           UNIQUE: satu orang boleh punya beberapa tiket lama)
 *   - nama_lengkap       -> nama_lengkap_ciphertext
 *   - data_simperum_json -> data_simperum_json_ciphertext (MEDIUMTEXT)
 *   - data_survey_json   -> data_survey_json_ciphertext (MEDIUMTEXT)
 * srp2_registrations.nik_ktp -> nik_ktp_ciphertext + nik_ktp_lookup_hash, UNIQUE pada
 * sidiknya (aturan keunikan lama `uq_nik_ktp` dipertahankan), pola sama dengan NPWP (056).
 *
 * Nama kolom `<asal>_ciphertext` disengaja: User_model::_prepare_export_record() membuka
 * kolom berakhiran _ciphertext kembali ke nama asalnya, jadi ekspor data akun tetap utuh.
 *
 * Urutan up() per tabel: tambah kolom -> enkripsi tiap baris yang plaintext-nya terisi ->
 * baca ulang dan cocokkan di PHP (ciphertext sungguhan, dekripsi = asal, sidik = asal) ->
 * selisih harus 0, kalau tidak ROLLBACK dan melempar -> kosongkan plaintext -> commit ->
 * baru DROP kolom polos. Baris yang plaintext-nya sudah NULL tidak disentuh, jadi migrasi
 * yang terputus sesudah commit aman dijalankan ulang (ciphertext-nya tidak tertimpa NULL).
 * Encryption_lib yang tidak siap (kunci/pepper hilang) MELEMPAR sebelum apa pun ditulis.
 * down() kebalikannya dengan pemeriksaan yang sama, lalu memulihkan `uq_nik_ktp`.
 */
class Migration_Enkripsi_pii_antrean_srp2 extends CI_Migration {

    /** asal => [definisi kolom asal, tipe ciphertext, berhash?, kolom sebelum asal] */
    const SPEK = [
        'sf_housing_queue' => [
            'nik_pengaju'        => [['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE], 'TEXT', TRUE, 'program_id'],
            'nama_lengkap'       => [['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE], 'TEXT', FALSE, 'nik_pengaju'],
            'data_simperum_json' => [['type' => 'TEXT', 'null' => TRUE], 'MEDIUMTEXT', FALSE, 'nama_lengkap'],
            'data_survey_json'   => [['type' => 'TEXT', 'null' => TRUE], 'MEDIUMTEXT', FALSE, 'data_simperum_json'],
        ],
        'srp2_registrations' => [
            'nik_ktp' => [['type' => 'VARCHAR', 'constraint' => 16, 'null' => TRUE], 'TEXT', TRUE, 'nama_peserta'],
        ],
    ];
    /** tabel => [nama indeks sidik, UNIQUE?] */
    const INDEKS_HASH = [
        'sf_housing_queue'   => ['idx_sf_queue_nik_lookup', FALSE],
        'srp2_registrations' => ['uq_srp2_registration_nik', TRUE],
    ];

    public function up()
    {
        $enc = $this->enkripsi_siap();
        foreach (self::SPEK as $tabel => $kolom) {
            if ( ! $this->db->table_exists($tabel)) { throw new RuntimeException('Migrasi 067: tabel ' . $tabel . ' tidak ada.'); }

            $tambah = [];
            foreach ($kolom as $asal => [, $tipe, $berhash]) {
                $sebelum = $this->db->field_exists($asal, $tabel) ? $asal : NULL;
                if ( ! $this->db->field_exists($asal . '_ciphertext', $tabel)) {
                    $tambah[$asal . '_ciphertext'] = ['type' => $tipe, 'null' => TRUE, 'comment' => $asal . ' terenkripsi (Encryption_lib)'] + ($sebelum ? ['after' => $sebelum] : []);
                }
                if ($berhash && ! $this->db->field_exists($asal . '_lookup_hash', $tabel)) {
                    $tambah[$asal . '_lookup_hash'] = ['type' => 'CHAR', 'constraint' => 64, 'null' => TRUE, 'comment' => 'Sidik deterministik ' . $asal . ' untuk pencarian/keunikan', 'after' => $asal . '_ciphertext'];
                }
            }
            if ($tambah && ! $this->dbforge->add_column($tabel, $tambah)) {
                throw new RuntimeException('Migrasi 067: kolom terenkripsi ' . $tabel . ' gagal ditambahkan.');
            }

            $polos = array_values(array_filter(array_keys($kolom), function ($k) use ($tabel) { return $this->db->field_exists($k, $tabel); }));
            if ($polos) {
                $this->db->trans_begin();
                foreach ($this->db->select(array_merge(['id'], $polos))->get($tabel)->result_array() as $baris) {
                    $set = [];
                    foreach ($polos as $asal) {
                        if ($baris[$asal] === NULL) { continue; }
                        $set[$asal . '_ciphertext'] = $enc->encrypt($baris[$asal]);
                        if ($kolom[$asal][2]) { $set[$asal . '_lookup_hash'] = $baris[$asal] === '' ? NULL : $enc->deterministic_hash($baris[$asal]); }
                    }
                    if ($set && ! $this->db->where('id', $baris['id'])->update($tabel, $set)) { $this->batal($tabel, 'enkripsi baris gagal ditulis'); }
                }
                $selisih = $this->selisih($enc, $tabel, $kolom, $polos);
                if ($selisih !== 0) { $this->batal($tabel, $selisih . ' nilai tidak kembali utuh sesudah dienkripsi'); }
                $kosong = [];
                foreach ($polos as $asal) { $kosong[$asal] = NULL; }
                if ( ! $this->db->update($tabel, $kosong) || ! $this->db->trans_status()) { $this->batal($tabel, 'plaintext gagal dikosongkan'); }
                $this->db->trans_commit();
            }

            if ($tabel === 'srp2_registrations' && $this->ada_indeks($tabel, 'uq_nik_ktp')) {
                $this->wajib('ALTER TABLE `srp2_registrations` DROP INDEX `uq_nik_ktp`');
            }
            foreach ($polos as $asal) {
                if ( ! $this->dbforge->drop_column($tabel, $asal)) { throw new RuntimeException('Migrasi 067: kolom polos ' . $tabel . '.' . $asal . ' gagal dibuang.'); }
            }
            [$indeks, $unik] = self::INDEKS_HASH[$tabel];
            $hash = array_keys(array_filter($kolom, function ($s) { return $s[2]; }))[0] . '_lookup_hash';
            if ( ! $this->ada_indeks($tabel, $indeks)) {
                $this->wajib('ALTER TABLE `' . $tabel . '` ADD ' . ($unik ? 'UNIQUE ' : '') . 'KEY `' . $indeks . '` (`' . $hash . '`)');
            }
        }
    }

    public function down()
    {
        $enc = $this->enkripsi_siap();
        foreach (self::SPEK as $tabel => $kolom) {
            if ( ! $this->db->table_exists($tabel)) { continue; }

            foreach ($kolom as $asal => [$def, , , $sebelum]) {
                if ( ! $this->db->field_exists($asal, $tabel)
                    && ! $this->dbforge->add_column($tabel, [$asal => $def + ['after' => $sebelum]])) {
                    throw new RuntimeException('Migrasi 067 down: kolom ' . $tabel . '.' . $asal . ' gagal dibuat ulang.');
                }
            }

            $sandi = array_values(array_filter(array_keys($kolom), function ($k) use ($tabel) { return $this->db->field_exists($k . '_ciphertext', $tabel); }));
            if ($sandi) {
                $this->db->trans_begin();
                $pilih = array_map(function ($k) { return $k . '_ciphertext'; }, $sandi);
                foreach ($this->db->select(array_merge(['id'], $pilih))->get($tabel)->result_array() as $baris) {
                    $set = [];
                    foreach ($sandi as $asal) {
                        $c = $baris[$asal . '_ciphertext'];
                        if ($c === NULL) { continue; }
                        $p = $c === '' ? '' : ($enc->is_encrypted($c) ? $enc->decrypt($c) : FALSE);
                        if ($p === FALSE) { $this->batal($tabel, 'ciphertext gagal dibuka'); }
                        $set[$asal] = $p;
                    }
                    if ($set && ! $this->db->where('id', $baris['id'])->update($tabel, $set)) { $this->batal($tabel, 'dekripsi baris gagal ditulis'); }
                }
                $selisih = $this->selisih($enc, $tabel, $kolom, $sandi);
                if ($selisih !== 0 || ! $this->db->trans_status()) { $this->batal($tabel, $selisih . ' nilai tidak kembali utuh sesudah didekripsi'); }
                $this->db->trans_commit();
            }

            [$indeks] = self::INDEKS_HASH[$tabel];
            if ($this->ada_indeks($tabel, $indeks)) { $this->wajib('ALTER TABLE `' . $tabel . '` DROP INDEX `' . $indeks . '`'); }
            foreach ($kolom as $asal => [, , $berhash]) {
                foreach ($berhash ? ['_ciphertext', '_lookup_hash'] : ['_ciphertext'] as $akhiran) {
                    if ($this->db->field_exists($asal . $akhiran, $tabel)) { $this->dbforge->drop_column($tabel, $asal . $akhiran); }
                }
            }
            if ($tabel === 'srp2_registrations' && ! $this->ada_indeks($tabel, 'uq_nik_ktp')) {
                $this->wajib('ALTER TABLE `srp2_registrations` ADD UNIQUE KEY `uq_nik_ktp` (`nik_ktp`)');
            }
        }
    }

    /**
     * Jumlah nilai yang TIDAK cocok antara kolom polos dan pasangan terenkripsinya, untuk
     * baris yang polosnya terisi. Dekripsi Encryption_lib mengembalikan teks non-ciphertext
     * apa adanya, jadi is_encrypted() wajib diperiksa: tanpa itu salinan polos "lolos".
     */
    private function selisih($enc, $tabel, array $kolom, array $nama)
    {
        $pilih = ['id'];
        foreach ($nama as $asal) {
            $pilih[] = $asal; $pilih[] = $asal . '_ciphertext';
            if ($kolom[$asal][2]) { $pilih[] = $asal . '_lookup_hash'; }
        }
        $n = 0;
        foreach ($this->db->select($pilih)->get($tabel)->result_array() as $baris) {
            foreach ($nama as $asal) {
                $p = $baris[$asal];
                if ($p === NULL) { continue; }
                $c = $baris[$asal . '_ciphertext'];
                $cocok = $p === '' ? $c === '' : ($enc->is_encrypted($c) && $enc->decrypt($c) === $p);
                if ($kolom[$asal][2]) {
                    $cocok = $cocok && $baris[$asal . '_lookup_hash'] === ($p === '' ? NULL : $enc->deterministic_hash($p));
                }
                if ( ! $cocok) { $n++; }
            }
        }
        return $n;
    }

    /** Encryption_lib wajib benar-benar bekerja; tidak ada jalur yang menulis plaintext. */
    private function enkripsi_siap()
    {
        $this->load->library('encryption_lib');
        $enc = get_instance()->encryption_lib ?? NULL;
        $uji = $enc ? $enc->encrypt('uji-067') : NULL;
        if ( ! $enc || ! $enc->is_encrypted($uji) || $enc->decrypt($uji) !== 'uji-067'
            || strlen((string) $enc->deterministic_hash('uji-067')) !== 64) {
            throw new RuntimeException('Migrasi 067: Encryption_lib tidak siap (kunci/pepper); tidak ada yang ditulis.');
        }
        return $enc;
    }

    private function batal($tabel, $sebab)
    {
        $this->db->trans_rollback();
        throw new RuntimeException('Migrasi 067 dibatalkan pada ' . $tabel . ': ' . $sebab . '. Data polos tidak diubah.');
    }

    private function ada_indeks($tabel, $nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$tabel, $nama])->row('n') > 0;
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 067 gagal: ' . $sql);
        }
    }
}
