<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Backfill `srp2_registrations.certified_developer_id` untuk pengajuan yang
 * SUDAH berstatus Diterima sebelum kolom itu ada (migrasi 20260701000014).
 *
 * Gejalanya: dashboard pemohon bilang "Diterima" tapi dia tidak muncul di
 * direktori publik, dan tombol "Lihat Profil Publik" tidak punya tujuan yang
 * benar. Migrasi 14 menambahkan kolomnya tapi tidak mengisi baris yang sudah
 * ada - menambah kolom tanpa mengisi data lama meninggalkan separuh sistem
 * memakai jalur baru dan separuh lagi jalur lama.
 *
 * WAJIB DIJALANKAN SEBELUM tautan profil publik dipindah ke certified_developer_id
 * (roadmap T4 butir 1). Kalau urutannya dibalik, badge dan tombol milik
 * pengembang yang SAH justru hilang karena kolomnya masih NULL.
 *
 * IDEMPOTEN - `main` beku, jadi migrasi ini akan duduk lama di branch fitur dan
 * mungkin dijalankan berulang. Dijalankan 2x berturut-turut menghasilkan DB
 * yang identik.
 *
 * ⚠️ COLLATION: `srp2_registrations.nama_perusahaan` utf8mb4_general_ci,
 * `srp2_certified_developers.nama_perusahaan` utf8mb4_unicode_ci. Join nama
 * tanpa COLLATE eksplisit gagal dengan error 1267. Ini satu-satunya tempat
 * pencocokan nama masih boleh dipakai - justru untuk MENGHAPUS ketergantungan
 * padanya; sesudah ini semua tautan lewat FK.
 */
class Migration_Backfill_certified_developer_link extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->table_exists('srp2_registrations') || ! $this->db->table_exists('srp2_certified_developers')) { return; }

        $diterima = $this->db->where('status_verifikasi', 'Diterima')
            ->where('certified_developer_id IS NULL', NULL, FALSE)
            ->get('srp2_registrations')->result();

        foreach ($diterima as $reg) {
            $nama = trim((string) $reg->nama_perusahaan);
            // Tanpa nama perusahaan tidak ada yang bisa diterbitkan ke direktori
            // (kolomnya NOT NULL). Dilewati, bukan dikarang - barisnya memang
            // tidak seharusnya pernah lolos ke status Diterima; gerbang untuk
            // itu sudah dipasang di T1a.
            if ($nama === '') { continue; }

            $baris = $this->db->query(
                'SELECT id FROM srp2_certified_developers
                 WHERE nama_perusahaan COLLATE utf8mb4_general_ci = ? LIMIT 1', [$nama]
            )->row();

            if ($baris) {
                $cid = (int) $baris->id;
            } else {
                $this->db->insert('srp2_certified_developers', [
                    'nama_perusahaan' => $nama,
                    'alamat_kantor'   => $reg->alamat_kantor,
                    'website'         => $reg->website,
                    'instagram'       => $reg->instagram,
                    'sosmed_lainnya'  => $reg->sosmed_lainnya,
                    'status_aktif'    => 1,
                ]);
                $cid = (int) $this->db->insert_id();
                if ( ! $cid) { continue; }
            }

            $this->db->where('id', $reg->id)->update('srp2_registrations', ['certified_developer_id' => $cid]);
        }
    }

    public function down()
    {
        // Sengaja TIDAK mengosongkan certified_developer_id maupun menghapus
        // baris direktori: rollback yang menghapus penerbitan publik jauh lebih
        // berbahaya daripada meninggalkan tautan yang benar. Migrasi ini murni
        // mengisi data yang seharusnya sudah ada.
    }
}
