<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PDF sertifikat KKN, dipindah dari KemitraanPortal::sertifikat_kkn_pdf (7 Okt 2026) supaya admin bisa
 * melihat sertifikat peserta mana pun dari halaman Peserta KKN (Admin_Kemitraan::pratinjau_sertifikat)
 * dengan isi yang PERSIS sama dengan yang dicetak peserta. Pemanggil yang memeriksa hak cetak.
 *
 * $data: nama_peserta, nim, instansi_asal, tanggal_sertifikat (NULL = hari ini, untuk pratinjau sebelum terbit).
 * $nomor: dari MY_Controller::nomor_sertifikat_kkn. @return bool FALSE bila template hilang.
 */
class Cetak_sertifikat_kkn {

    public function kirim($data, $nomor)
    {
        /* require_once LANGSUNG, bukan mengandalkan classmap composer -
           setasign/fpdf 1.9.0 MENYATAKAN classmap "fpdf.php" di
           composer.json-nya sendiri, tapi entah kenapa tidak pernah
           mendarat di vendor/composer/autoload_classmap.php walau sudah
           composer dump-autoload berkali-kali di lingkungan ini (diverifikasi
           langsung: entri "FPDF" nihil di classmap yang dihasilkan).
           require_once pada berkas tunggal ini AMAN dipakai berulang -
           gaya pakai FPDF yang paling umum di luar Composer justru begini,
           dan idempoten (PHP tidak mendefinisikan ulang kelas yang sudah ada). */
        require_once FCPATH . 'vendor/setasign/fpdf/fpdf.php';

        $template = FCPATH . 'assets/img/template_sertifikat_kkn.jpg';
        if ( ! is_file($template)) {
            // Template WAJIB ada - bukan sesuatu yang boleh diam-diam
            // jatuh ke rancangan lama, karena rancangan lama itu sudah
            // dilepas sepenuhnya (bukan cuma tidak dipakai).
            log_message('error', 'Cetak_sertifikat_kkn: template hilang di ' . $template);
            return FALSE;
        }

        // FPDF (font bawaan Arial/Times) TIDAK mengenal UTF-8 - dikonversi
        // ke Windows-1252 sekali di sini, bukan menulis konversi berulang
        // di tiap Cell(). //TRANSLIT menjatuhkan karakter yang benar-benar
        // tidak punya padanan (mis. emoji) alih-alih gagal total.
        $t = function ($s) {
            $s = (string) ($s ?? '');
            $hasil = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
            return $hasil !== FALSE ? $hasil : $s;
        };

        $pdf = new FPDF('L', 'mm', 'A4'); // 297 x 210mm - rasio PERSIS template
        $pdf->SetTitle('Sertifikat KKN Kemitraan - ' . $data->nama_peserta);
        $pdf->SetAutoPageBreak(FALSE);

        /* Font skrip untuk <<Nama Lengkap>> - permintaan user 22 Agt 2026,
           mendekati kaligrafi biru navy templatenya jauh lebih dekat
           daripada Times italic bawaan FPDF. Berkasnya
           (BrittanySignature.json + .z, dibangkitkan SEKALI dari .ttf lewat
           vendor/setasign/fpdf/makefont/makefont.php - lihat catatan di
           application/fonts/) DIBACA DARI application/fonts/, bukan
           vendor/ - font pihak ketiga yang diunggah user, bukan bagian
           paket FPDF itu sendiri. */
        $pdf->AddFont('BrittanySignature', '', 'BrittanySignature.json', APPPATH . 'fonts/');

        /* Font utk kalimat "Atas partisipasinya... dalam kegiatan :" -
           permintaan user 23 Agt 2026, dipilih user sendiri untuk mendekati
           Cormorant Garamond yang dipakai versi HTML+print (Versi B) yang
           dibandingkan sebelum ini. Dipakai instans STATIS "Regular" dari
           paket variable-font yang dilampirkan user
           (Cormorant_Garamond/static/CormorantGaramond-Regular.ttf, BUKAN
           file variable-font utamanya) - makefont.php FPDF tidak
           mendukung sumbu variabel; instans statis satu berat inilah yang
           sungguh dipakai untuk merender, bukan cuma nama filenya yang mirip. */
        $pdf->AddFont('CormorantGaramond', '', 'CormorantGaramond-Regular.json', APPPATH . 'fonts/');

        $pdf->AddPage();
        $pdf->Image($template, 0, 0, 297, 210, 'JPG');

        /* GANTI TEMPLATE 23 Agt 2026 - user memberi file sumber aslinya
           (PIAGAM PENGHARGAAN KKN KEMITRAAN 2025 COBA.pdf, lihat folder
           Downloads/SLAMRET1/). Dibongkar dengan smalot/pdfparser: file
           itu ternyata punya LAPISAN TERPISAH - satu gambar latar yang
           SUNGGUH KOSONG (tanpa "<<ID>>"/"<<Nama Lengkap>>"/kalimat
           Universitas ter-bake sama sekali, diekstrak jadi
           assets/img/template_sertifikat_kkn.jpg yang sekarang, 2000x1414px)
           plus lapisan teks vektor terpisah untuk keempat field itu di
           atasnya. File JPG LAMA (semua placeholder ter-bake jadi piksel,
           sumber segala kotak-penutup-warna-sampel di bawah sebelumnya)
           disimpan sebagai template_sertifikat_kkn_lama_baked.jpg untuk
           rujukan/rollback, tidak lagi dipakai kode ini.

           Konsekuensinya: TIDAK ADA LAGI kotak penutup warna-sampel sama
           sekali - background di sini sudah benar-benar kosong di keempat
           area, jadi teks tinggal ditulis langsung tanpa perlu menutupi
           apa pun (akar masalah "kelihatan ditempel" dari kotak-warna-
           sampel sebelumnya otomatis tidak relevan lagi). Koordinat di
           bawah BUKAN hasil ukur visual/pixel-ruler lagi, tapi dihitung
           dari matriks transformasi PDF asli (rangkaian q/cm/Tm di
           content stream file sumbernya) - lihat riwayat sesi untuk
           skrip penelusurannya. */

        // "Nomor : " + nomor - baris ini SELURUHNYA tidak ada di background
        // baru (dulu ter-bake penuh termasuk "<<ID>>"), jadi labelnya ikut
        // ditulis di sini. Nomornya dari pemanggil (awalan per KKN + urut
        // peserta, MY_Controller::nomor_sertifikat_kkn), bukan NIM 10-16
        // digit yang terlihat aneh sebagai "nomor surat". Text() FPDF
        // memakai (x,y) SEBAGAI TITIK DASAR/BASELINE teks - persis makna
        // Tm di PDF, jadi angka hasil penelusuran matriks bisa dipakai
        // langsung tanpa konversi tambahan.
        $pdf->SetFont('Times', '', 13);
        $pdf->SetTextColor(10, 10, 10);
        $pdf->Text(121.34, 62.95, $t('Nomor : ' . $nomor));

        // <<Nama Lengkap>> - font BrittanySignature (diunggah user 22 Agt
        // 2026), mendekati skrip/kursif biru navy templatenya. Dipusatkan
        // manual (GetStringWidth) terhadap TITIK TENGAH KERTAS SUNGGUHAN
        // (148.5mm, dari 297mm lebar A4 landscape) - bukan titik tengah
        // kotak 50-207mm yang dipakai sebelumnya, yang pusatnya di 128.5mm
        // dan membuat nama terlihat bergeser ~20mm ke kiri dibanding garis
        // titik-titik di bawahnya (keluhan user 23 Agt 2026, dan diverifikasi
        // dengan mengukur ulang garis titik-titik di background baru: pusat
        // sungguhannya 148.43mm, cocok dengan titik tengah halaman, bukan
        // 128.5mm). Baseline asli dari file sumber (x=93.85mm) TETAP tidak
        // dipakai untuk pemusatan - itu cuma valid untuk teks placeholder
        // "<<Nama Lengkap>>" itu sendiri, bukan patokan nama sungguhan yang
        // panjangnya bervariasi per mahasiswa. Ukuran huruf MENGECIL
        // OTOMATIS kalau nama kepanjangan - satu-satunya cara nama yang
        // sangat panjang tidak meluber ke luar kertas.
        $nama = $t($data->nama_peserta);
        $namaUkuran = 44;
        $pdf->SetFont('BrittanySignature', '', $namaUkuran);
        while ($pdf->GetStringWidth($nama) > 155 && $namaUkuran > 20) {
            $namaUkuran -= 0.5;
            $pdf->SetFont('BrittanySignature', '', $namaUkuran);
        }
        $pdf->SetTextColor(19, 61, 103);
        $namaX = 148.5 - $pdf->GetStringWidth($nama) / 2;
        $pdf->Text($namaX, 95.31, $nama);

        /* Kalimat "Atas partisipasinya ... dan <<Universitas>> dalam
           kegiatan :" - dulu diperlakukan sebagai satu kotak sempit 35mm
           untuk nama universitas SAJA karena sisa kalimatnya ter-bake di
           background lama. Sekarang SELURUH kalimat itu tidak ada di
           background baru, jadi ditulis penuh di sini sebagai satu
           paragraf rata-tengah (MultiCell) dengan nama universitas
           disisipkan di tengah. Rentang x=37.6-259.4mm diambil dari
           baseline dua baris kalimat ini di file sumber (37.6mm & 97.6mm
           dari margin kiri).

           WAJIB MUAT 2 BARIS, tidak boleh lebih - diukur langsung di
           background baru: jarak dari garis titik-titik (~104.7mm) ke
           judul kegiatan tetap "Verifikasi dan Validasi..." (~127mm)
           cuma cukup untuk 2 baris di ukuran wajar; baris ke-3 akan
           bertabrakan dengan judul itu (terbukti lewat preview GD saat
           nama universitas panjang dipaksa 16pt tetap - lihat riwayat
           sesi). Makanya ukuran font MENGECIL OTOMATIS (bukan wrap bebas
           seperti MultiCell biasa) sampai hasil lipatannya <=2 baris,
           persis pola yang sama dipakai untuk Nama Lengkap di atas. */
        $kalimat = 'Atas partisipasinya sebagai peserta Kuliah Kerja Nyata (KKN) Kemitraan Disperakim Provinsi Jawa Tengah dan '
            . $t($data->instansi_asal) . ' dalam kegiatan :';

        $hitung_baris = function ($teks, $lebar) use ($pdf) {
            $kata = explode(' ', $teks);
            $baris = 1;
            $baris_ini = '';
            foreach ($kata as $k) {
                $coba = $baris_ini === '' ? $k : $baris_ini . ' ' . $k;
                if ($pdf->GetStringWidth($coba) > $lebar - 2 && $baris_ini !== '') {
                    $baris++;
                    $baris_ini = $k;
                } else {
                    $baris_ini = $coba;
                }
            }
            return $baris;
        };

        $kalimatBoxW = 221.8;
        // Ukuran dasar 16pt: file sumber memakai 21.333 unit teks x skala
        // CTM 0.75 = 16pt EFEKTIF - dan sesi lain yang membongkar file yang
        // SAMA lewat PyMuPDF (page.get_text('dict')) menemukan angka
        // PERSIS sama, "Cormorant Garamond Regular, 16pt", jadi ini bukan
        // kebetulan dua taksiran beda ketemu sama, tapi dua metode
        // independen mengukur font placeholder aslinya. FPDF di sini
        // bekerja langsung dalam mm/pt tanpa CTM tersembunyi, jadi 16pt
        // dipakai apa adanya sebagai titik awal sebelum pengecekan
        // wajib-2-baris di bawah.
        $kalimatUkuran = 16;
        $pdf->SetFont('CormorantGaramond', '', $kalimatUkuran);
        while ($hitung_baris($kalimat, $kalimatBoxW) > 2 && $kalimatUkuran > 9) {
            $kalimatUkuran -= 0.5;
            $pdf->SetFont('CormorantGaramond', '', $kalimatUkuran);
        }
        $kalimatLineH = $kalimatUkuran * 0.42375; // rasio sama seperti 16pt->6.78mm

        $pdf->SetTextColor(10, 10, 10);
        $pdf->SetXY(37.6, 106.5);
        $pdf->MultiCell($kalimatBoxW, $kalimatLineH, $kalimat, 0, 'C');

        // Nama kegiatan ("Verifikasi dan Validasi Rumah Tidak Layak Huni")
        // - permintaan user 23 Agt 2026: BIARKAN APA ADANYA, tidak diganti
        // dinamis dari data->divisi_atau_tema. Kalimat ini sekarang bagian
        // TETAP dari gambar background (sudah ter-bake di file sumber),
        // jadi sengaja TIDAK ADA kode yang menulis/menutup area ini lagi.

        /* Tanggal "31 Desember 2025" - permintaan user 23 Agt 2026: ganti
           jadi tanggal SEKARANG (tanggal cetak), bukan tanggal tetap dari
           template. BEDA dari field lain di atas: baris ini TIDAK genuinely
           kosong di background baru - "Semarang, 31 Desember 2025" masih
           ter-bake sebagai satu kesatuan gambar, jadi field ini balik
           memakai pola tutup-lalu-tulis (kotak warna kertas) seperti versi
           lama, BUKAN karena regresi, tapi karena field ini memang tidak
           ikut dipisah jadi lapisan teks di file sumber PDF aslinya (cuma
           <<ID>>, <<Nama Lengkap>>, dan kalimat Universitas yang punya
           lapisan teks terpisah - lihat catatan di atas method ini).

           Cuma "31 Desember 2025" yang ditutup, BUKAN "Semarang," di
           depannya - stempel resmi Kepala Dinas tumpang tindih tepat di
           bawah kata "Semarang," (dikonfirmasi lewat crop zoom), jadi
           kotak penutup yang lebih lebar akan ikut memakan sebagian
           stempel asli itu. Koordinat 140-181mm/146-151mm diukur presisi
           dari background baru (column-darkness word-segmentation,
           bukan taksiran visual): "31" mulai 141.7mm, "2025" berakhir
           179.24mm, stempel berhenti sebelum 140mm. Warna sampul
           (247,246,241) disampel LANGSUNG dari kertas kosong di sisi
           kanan kotak, bukan warna rata sembarang. */
        // Tanggal terbit ditetapkan admin (migrasi 062), bukan hari pencetakan.
        $tanggalCetak = $t(tgl_id(((array) $data)['tanggal_sertifikat'] ?? date('Y-m-d')));
        $pdf->SetFillColor(247, 246, 241);
        $pdf->Rect(139, 145.7, 43, 5.6, 'F');
        $pdf->SetFont('Times', '', 13);
        $pdf->SetTextColor(10, 10, 10);
        $tglX = 139 + (43 - $pdf->GetStringWidth($tanggalCetak)) / 2;
        $pdf->Text($tglX, 150.3, $tanggalCetak);

        $pdf->Output('I', 'sertifikat-kkn-' . preg_replace('/[^A-Za-z0-9_-]/', '', $data->nim) . '.pdf');
        return TRUE;
    }
}
