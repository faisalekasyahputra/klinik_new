<?php
/**
 * Pemberitahuan SIMPERUM belum aktif - muncul untuk penguji setiap halaman
 * yang bergantung pada SIMPERUM dibuka.
 *
 * KENAPA MODAL, BUKAN SPANDUK SAJA. Spanduk `is_simulation` sudah ada di
 * halaman pendataan sejak lama, dan penguji tetap melaporkan hasil simulasi
 * sebagai kalau itu data sungguhan - spanduk terbaca sebagai hiasan. Yang
 * dipertaruhkan bukan kenyamanan: NIK warga sungguhan selalu dijawab "tidak
 * terdaftar" oleh fixture, dan penguji yang tidak tahu itu akan melaporkannya
 * sebagai kerusakan pencarian. Modal menghentikan langkah sebentar; itu memang
 * maksudnya.
 *
 * MUNCUL SEKALI TIAP HALAMAN DIBUKA - keputusan user 10 Agt 2026. Sengaja
 * TIDAK diingat di sessionStorage: penguji berpindah antar layar dan tiap layar
 * punya ketergantungan SIMPERUM sendiri, jadi "sudah pernah lihat" bukan alasan
 * untuk diam di layar berikutnya.
 *
 * Dipasang HANYA saat mode simulasi. Begitu `SIMPERUM_MODE=api` dan kuncinya
 * terisi, berkas ini tidak merender apa pun - tanpa perlu dicabut dari view.
 *
 * Memakai <dialog> bawaan HTML: backdrop, tombol ESC, dan jebakan fokus sudah
 * ditangani peramban. Tidak ada pustaka modal yang perlu dimuat.
 */

$CI =& get_instance();
$CI->config->load('simperum', TRUE, TRUE);
if ($CI->config->item('simperum_mode', 'simperum') !== 'simulation') { return; }
?>
<dialog id="modal-simperum" class="rounded-2xl border p-0"
        style="max-width:32rem;width:calc(100% - 2rem);background:var(--portal-bg-card);border-color:var(--portal-border);color:var(--portal-text)"
        aria-labelledby="modal-simperum-judul">
    <div class="p-5 sm:p-6">
        <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:#b45309">Pemberitahuan untuk penguji</p>
        <h2 id="modal-simperum-judul" class="mt-1 text-lg font-black sm:text-xl">SIMPERUM belum diaktifkan</h2>

        <p class="mt-3 text-sm leading-relaxed" style="color:var(--portal-text-muted)">
            Sambungan ke data SIMPERUM <strong>belum disetujui</strong>, sehingga belum dapat dinyalakan.
            Selama itu, halaman ini berjalan memakai <strong>data contoh</strong>.
        </p>

        <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
             style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
            <p class="font-bold">Yang perlu diketahui saat menguji:</p>
            <ul class="mt-1.5 list-outside list-disc space-y-1 pl-4">
                <li><strong>NIK sungguhan akan selalu dijawab &ldquo;tidak terdaftar&rdquo;.</strong> Itu perilaku yang benar untuk keadaan sekarang, bukan kerusakan pencarian.</li>
                <?php
                /* Kata "desil" dan "penghasilan" SENGAJA TIDAK dipakai di sini.
                   Modal ini juga dipasang di layar Cek Data Rumah, yang sengaja
                   hanya menjawab "terdaftar / tidak terdaftar" dan tidak pernah
                   menyinggung kesejahteraan - `uji_cek_rtlh.php` menjaga itu dan
                   langsung merah waktu kalimat ini menyebutnya. Penjaganya benar:
                   menaruh istilah kesejahteraan di layar itu menembus batas yang
                   dibangun dengan sengaja, meski cuma sebagai keterangan. */
                ?>
                <li>Seluruh data dan rekomendasi yang muncul adalah <strong>contoh</strong> - bukan data warga yang bersangkutan.</li>
                <li>Hasil di alur ini <strong>bukan keputusan bantuan</strong> dan tidak boleh dipakai sebagai dasar apa pun.</li>
            </ul>
        </div>

        <p class="mt-3 text-xs" style="color:var(--portal-text-muted)">
            Begitu sambungan disetujui dan dinyalakan, pemberitahuan ini hilang dengan sendirinya
            dan seluruh halaman langsung memakai data sungguhan.
        </p>

        <div class="mt-5 flex justify-end">
            <button type="button" id="modal-simperum-tutup"
                    class="rounded-xl px-4 py-2 text-sm font-bold"
                    style="background:var(--portal-brand);color:#0a1a1f">Saya mengerti</button>
        </div>
    </div>
</dialog>
<script>
(function () {
    var d = document.getElementById('modal-simperum');
    if (!d) { return; }

    /* `showModal` baru ada sejak <dialog> didukung penuh. Kalau peramban terlalu
       tua, JANGAN memaksa: dialog tanpa showModal() tampil sebagai blok biasa
       di tengah halaman dan justru merusak tata letak. Lebih baik tidak muncul
       daripada muncul rusak - spanduk simulasi di halaman tetap menjadi
       jaring pengamannya. */
    if (typeof d.showModal !== 'function') { return; }

    var buka = function () { if (!d.open) { d.showModal(); } };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buka);
    } else {
        buka();
    }

    document.getElementById('modal-simperum-tutup').addEventListener('click', function () { d.close(); });
})();
</script>
