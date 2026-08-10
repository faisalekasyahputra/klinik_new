<?php
/**
 * Pemberitahuan butir B2 — identitas warga menunggu keputusan dinas.
 *
 * Muncul di layar antrean setiap halaman dibuka, selama
 * `config/kebijakan_data.php` masih bernilai `menunggu_keputusan`.
 *
 * KENAPA MODAL. Tanpa ini, penguji membuka layar antrean, melihat "Warga
 * Contoh 007", dan menyimpulkan datanya rusak atau belum masuk. Yang perlu
 * mereka tahu bukan bahwa datanya contoh, melainkan KENAPA — dan bahwa
 * keputusan untuk mengembalikannya ada di tangan mereka, bukan di tangan kami.
 *
 * Begitu dinas memutuskan dan sakelarnya diubah ke `tampil`, berkas ini tidak
 * merender apa pun. Tidak perlu dicabut dari view.
 */

$CI =& get_instance();
$CI->config->load('kebijakan_data', TRUE, TRUE);
if ($CI->config->item('identitas_warga_kabkota', 'kebijakan_data') !== 'menunggu_keputusan') { return; }
?>
<dialog id="modal-identitas-b2" class="rounded-2xl border p-0"
        style="max-width:34rem;width:calc(100% - 2rem);background:var(--portal-bg-card,#fff);border-color:var(--portal-border,#e5e7eb);color:var(--portal-text,#111827)"
        aria-labelledby="modal-identitas-judul">
    <div class="p-5 sm:p-6">
        <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:#b45309">Menunggu keputusan dinas</p>
        <h2 id="modal-identitas-judul" class="mt-1 text-lg font-black sm:text-xl">Data warga di layar ini sengaja diganti data contoh</h2>

        <p class="mt-3 text-sm leading-relaxed" style="color:var(--portal-text-muted,#6b7280)">
            Dinas melaporkan dashboard kabupaten/kota memunculkan data warga. Kami sudah
            memeriksanya, dan hasilnya perlu dipisahkan menjadi dua hal.
        </p>

        <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
             style="background:rgba(16,185,129,.09);border-color:rgba(16,185,129,.3);color:#065f46">
            <p class="font-bold">Yang sudah dipastikan aman</p>
            <p class="mt-1">
                <strong>Batas wilayahnya mengikat.</strong> Admin kabupaten A tidak dapat melihat,
                membuka, mengunduh, maupun mengubah pengajuan milik kabupaten B — diuji pada empat
                jalur sekaligus, termasuk lewat kotak pencarian dan lewat alamat yang ditebak langsung.
                Tidak ditemukan kebocoran antarwilayah.
            </p>
        </div>

        <div class="mt-3 rounded-xl border p-3 text-xs leading-relaxed"
             style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
            <p class="font-bold">Yang belum bisa kami putuskan sendiri</p>
            <p class="mt-1">
                Bolehkah admin kabupaten/kota melihat <strong>identitas dan keadaan ekonomi warga
                di wilayahnya sendiri</strong>? Layar ini menampilkan nama, empat digit akhir NIK,
                pekerjaan, penghasilan, kelompok kesejahteraan, dan status kepemilikan rumah.
                Semuanya diperlukan untuk menilai pengajuan &mdash; dan semuanya data pribadi.
            </p>
            <p class="mt-2">
                <strong>Itu keputusan kebijakan, bukan keputusan teknis</strong>, sehingga kami tidak
                mengambilnya sendiri. Selama menunggu, seluruh identitas di layar ini diganti data
                contoh supaya bentuk dan alurnya tetap bisa Bapak/Ibu nilai tanpa satu pun data warga
                sungguhan tersaji.
            </p>
        </div>

        <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
             style="background:rgba(14,165,233,.09);border-color:rgba(14,165,233,.3);color:#075985">
            <p class="font-bold">Mohon keputusannya disampaikan kepada pengembang</p>
            <p class="mt-1">Cukup salah satu dari tiga ini:</p>
            <ol class="mt-1.5 list-outside list-decimal space-y-1 pl-4">
                <li><strong>Tampilkan seluruhnya</strong> &mdash; admin kab/kota berhak melihat identitas dan keadaan ekonomi warga wilayahnya.</li>
                <li><strong>Tampilkan sebagian</strong> &mdash; sebutkan kolom mana yang boleh (misalnya nama dan nomor tiket saja, tanpa penghasilan dan kelompok kesejahteraan).</li>
                <li><strong>Tetap disamarkan</strong> &mdash; penilaian dilakukan tanpa melihat identitas.</li>
            </ol>
            <p class="mt-2">Perubahannya satu baris di satu berkas, jadi bisa langsung tayang begitu keputusannya ada.</p>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" id="modal-identitas-tutup"
                    class="rounded-xl px-4 py-2 text-sm font-bold"
                    style="background:var(--portal-brand,#0e6b7a);color:#fff">Saya mengerti</button>
        </div>
    </div>
</dialog>
<script>
(function () {
    var d = document.getElementById('modal-identitas-b2');
    if (!d || typeof d.showModal !== 'function') { return; }
    var buka = function () { if (!d.open) { d.showModal(); } };
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', buka); } else { buka(); }
    document.getElementById('modal-identitas-tutup').addEventListener('click', function () { d.close(); });
})();
</script>
