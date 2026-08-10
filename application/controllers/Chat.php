<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DIKARANTINA 29 Jul 2026 - butir B2.
 *
 * Seluruh endpoint eksternal controller ini membalas 404 sampai keputusan #7
 * (chat dicabut atau dibangun) turun. Ini containment, BUKAN penghapusan:
 * kode dan model dibiarkan utuh supaya kedua pilihan tetap terbuka.
 *
 * Kenapa perlu, padahal fiturnya memang sudah rusak: `api_bot()` berstatus
 * public sehingga routable lewat GET /Chat/api_bot/<pesan>, dan setiap
 * panggilan menembak Gemini memakai kunci API dinas - kuota bisa dikuras
 * anonim tanpa alat khusus. Menjadikan `api_bot()` private saja TIDAK cukup:
 * `kirim_pesan_lanjutan()` juga public dan memanggilnya, jadi jalur ke Gemini
 * tetap terbuka. Karena itu ketiga endpoint eksternal ditutup sekaligus.
 *
 * CSRF bukan penutup di sini (B12): `csrf_regenerate` FALSE + double-submit
 * berarti token bisa diambil anonim lewat satu GET.
 */
class Chat extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Chat_model');


    }

    /** Satu pintu penolakan untuk seluruh endpoint yang dikarantina. */
    private function dikarantina() {
        show_404();
    }

    public function register_session() {
        $this->dikarantina();
        return;
    }

    private function register_session_dikarantina() {
        $session_id = $this->input->post('session_id', true);
        $nama       = $this->input->post('nama', true);
        $email      = $this->input->post('email', true);
        $hp         = $this->input->post('hp', true);
        $pesan_awal = $this->input->post('pesan_awal', true);

        // 1. Masukkan pesan pembuka tersebut ke tabel log chat sebagai pesan pertama warga
        $data_pesan = [
            'session_id' => $session_id,
            'pengirim'   => 'warga',
            'pesan'      => $pesan_awal,
            'nama_warga' => $nama,
            'email_warga'=> $email,
            'hp_warga'  => $hp

        ];
        $insert = $this->db->insert('tb_chat', $data_pesan);

        // 2. (Opsional) Jika Anda ingin menyimpan data identitas warga (nama, hp, email) ke tabel terpisah 
        // seperti `tb_identitas_warga`, Anda bisa melakukan insert datanya di baris ini.

        if($insert) {
            $output = ['status' => 'success', 'message' => 'Sesi obrolan berhasil didaftarkan'];
        } else {
            $output = ['status' => 'error', 'message' => 'Gagal menyimpan ke server'];
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }
    public function kirim_pesan_lanjutan() {
        $this->dikarantina();
        return;
    }

    private function kirim_pesan_lanjutan_dikarantina() {
        $session_id = $this->input->post('session_id', true);
        $pesan_warga = $this->input->post('pesan', true);

        if (!$session_id || !$pesan_warga) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error']));
            return;
        }

        // 1. SIMPAN PESAN WARGA KE DATABASE
        $data_warga = [
            'session_id' => $session_id,
            'pengirim'   => 'warga',
            'pesan'      => $pesan_warga
        ];
        $this->db->insert('tb_chat', $data_warga);

        // 2. HIT API AI UNTUK MENDAPATKAN JAWABAN OTOMATIS
        $jawaban_ai = $this->api_bot($pesan_warga);

        // 3. SIMPAN JAWABAN AI KE DATABASE SEBAGAI 'BOT'
        $data_bot = [
            'session_id' => $session_id,
            'pengirim'   => 'bot',
            'pesan'      => $jawaban_ai
        ];
        $this->db->insert('tb_chat', $data_bot);

        // 4. KIRIM RESPON SUKSES KE AJAX
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => 'success']));
    }
    
   

    // PRIVATE sejak 29 Jul 2026 (B2). Sebelumnya public, sehingga routable
    // lewat GET /Chat/api_bot/<pesan> dan setiap hit anonim menembak Gemini
    // memakai kunci API dinas.
    private function api_bot($pesan_warga) {
    // Gunakan API Key murni dari Google AI Studio Anda
   //$pesan_warga = 'Apa Syarat menjadi pengembang';
    $api_key = getenv('GEMINI_API_KEY');
    
    // KUNCI PERUBAHAN: Gunakan /v1/ dan nama model murni tanpa embel-embel '-latest'
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $api_key;
    $system_prompt = "[INSTRUKSI SISTEM MUTLAK]\n"
                   . "Anda adalah 'Asisten Pintar AI' resmi dari platform Klinik PKP Disperakim Provinsi Jawa Tengah.\n"
                   . "Patuhi peraturan ini saat merespons:\n"
                   . "1. Batasan Topik: Anda HANYA boleh menjawab pertanyaan terkait program Rumah Tidak Layak Huni (RTLH), perumahan subsidi, PSU, sengketa kawasan permukiman,klinik Perumahan Kawasan pemukiman (PKP), alamat dan nama pengembang perumahan dan regulasi perumahan di Jawa Tengah.\n"
                   . "2. Penolakan Luar Topik: Jika warga bertanya hal di luar topik tersebut (seperti tips koding, matematika, resep makanan, dll), Anda WAJIB menolak dengan sopan menggunakan kalimat: 'Maaf, sebagai Asisten Resmi Klinik PKP Disperakim Jateng, saya hanya dapat membantu memberikan informasi seputar urusan perumahan dan kawasan permukiman di Jawa Tengah. Ada hal terkait permukiman yang bisa saya bantu?'\n"
                   . "3. Gaya Bahasa: Ramah, profesional, ringkas, dan menggunakan Bahasa Indonesia yang baik.\n"
                   . "-------\n\n";
    $teks_lengkap = $system_prompt . "Pertanyaan Warga: " . $pesan_warga;
    
    $payload = [
        "contents" => [
            [
                "parts" => [
                    [
                        "text" => $teks_lengkap
                    ]
                ]
            ]
        ],
        "generationConfig" => ["maxOutputTokens" => 1000,"temperature" => 0.2]
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        
        // B4 - SENGAJA belum diperbaiki. `api_bot()` kini private dan seluruh
        // endpoint Chat dikarantina 404 (B2), jadi baris ini tidak pernah
        // dieksekusi. Nasibnya mengikuti keputusan #7: bila chat dicabut, titik
        // ini hilang bersama berkasnya; bila dibangun, TLS wajib dinyalakan
        // sebelum route dibuka. Komentar lama "pengaman wajib XAMPP Windows"
        // keliru - mematikan verifikasi sertifikat bukan pengaman, dan
        // Simperum_gateway.php membuktikan verifikasi menyala baik-baik saja
        // di lingkungan yang sama.
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15 
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        echo "Aduh, cURL Error pada XAMPP: " . $error_msg;
        return;
    }

    curl_close($ch);

    $result = json_decode($response, true);
    //echo json_encode($result);
    //die();
    // TAMPILKAN HASIL AKHIR
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return nl2br(htmlspecialchars($result['candidates'][0]['content']['parts'][0]['text']));
    } else {
        return "Maaf, sistem asisten AI kami sedang sibuk saat ini. Mohon coba sampaikan pertanyaan Anda beberapa saat lagi.";
    }
}
public function ambil_pesan() {
    $this->dikarantina();
    return;
}

/**
 * Dikarantina. Catatan untuk keputusan #7 kalau chat jadi dibangun:
 * `result_array()` di bawah TANPA `select()` mengembalikan SELURUH kolom,
 * termasuk nama/email/HP warga - dan kuncinya `session_id` buatan browser
 * (`Math.random()` di footer), jadi siapa pun yang menebaknya bisa membaca
 * riwayat orang lain (B7). Wajib diperbaiki sebelum route dibuka lagi.
 */
private function ambil_pesan_dikarantina() {
    // Tangkap token session_id dari request AJAX
    $session_id = $this->input->post('session_id', true);

    if (!$session_id) {
        $this->output->set_status_header(400);
        return;
    }

    // Ambil data chat dari tabel berdasarkan session_id, urutkan dari pesan terlama ke terbaru
    $daftar_pesan = $this->db->order_by('created_at', 'ASC')
                             ->get_where('tb_chat', ['session_id' => $session_id])
                             ->result_array();

    // Kembalikan respons dalam format JSON murni
    $response = [
        'status' => 'success',
        'data'   => $daftar_pesan
    ];

    $this->output
         ->set_content_type('application/json')
         ->set_output(json_encode($response));
}
}
