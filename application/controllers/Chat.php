<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Chat_model');
        
   
    }
    public function register_session() {
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
    
   

    public function api_bot($pesan_warga) {
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
        
        // Pengaman wajib XAMPP Windows agar tidak silent-crash
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