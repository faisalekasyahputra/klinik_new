<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Penampil berkas privat dalam modal - dipakai portal DAN shell dashboard.
 *
 * Dulu "Lihat berkas" = target="_blank" (tab baru putih sampai file termuat)
 * atau navigasi penuh ke biner - halaman "mati sebentar", dan loader
 * progresif malah bekerja dua kali (fetch lalu tetap navigasi penuh).
 *
 * Cara pakai: beri atribut data-file-view (+ opsional data-file-title) pada
 * <a> menuju endpoint berkas privat. Fallback tanpa-JS tetap jalan lewat
 * href/target aslinya. Respons text/html (mis. berkas hilang -> redirect +
 * flash) sengaja DIJATUHKAN ke navigasi penuh supaya pesan jujurnya tampil,
 * bukan halaman tersarang di dalam iframe.
 *
 * Script ini harus termuat SEBELUM loader progresif (keduanya mengecek
 * e.defaultPrevented, jadi urutan pendaftaran listener menentukan siapa
 * yang menang).
 */
?>
<?php /* Warna lewat token CSS, bukan atribut style: terang bawaan, gelap saat html.dark (panel admin).
   Gambar tampil sebagai <img> di tengah (object-fit contain); dulu gambar di dalam iframe menempel di
   kiri atas dengan sisa putih (4 Okt 2026). PDF tetap iframe. */ ?>
<style>
    #kpkp-file-dialog { --fv-latar: #fff; --fv-kepala: #f6fafb; --fv-garis: rgba(10,26,31,.1); --fv-teks: #0a1a1f;
        --fv-redup: #5b7a80; --fv-tautan: #00545f; --fv-tombol: rgba(10,26,31,.06); --fv-panggung: #eef3f4;
        width: min(92vw, 960px); height: min(88vh, 900px); padding: 0; border: 1px solid var(--fv-garis); border-radius: 16px;
        overflow: hidden; background: var(--fv-latar); color: var(--fv-teks); box-shadow: 0 25px 60px rgba(0,0,0,.35); }
    html.dark #kpkp-file-dialog { --fv-latar: #0f2933; --fv-kepala: #0a1a1f; --fv-garis: rgba(255,255,255,.08); --fv-teks: #fff;
        --fv-redup: #8aacb0; --fv-tautan: #d6fb00; --fv-tombol: rgba(255,255,255,.08); --fv-panggung: #0a1a1f; }
    #kpkp-file-dialog::backdrop { background: rgba(0,0,0,.55); }
    .fv-kepala { display: flex; align-items: center; gap: .75rem; padding: .65rem .9rem; border-bottom: 1px solid var(--fv-garis); background: var(--fv-kepala); }
    .fv-judul { flex: 1; font-size: .85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #kpkp-file-newtab { font-size: .75rem; font-weight: 700; color: var(--fv-tautan); text-decoration: none; }
    #kpkp-file-close { border: 0; background: var(--fv-tombol); color: var(--fv-teks); border-radius: 8px; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; line-height: 1; }
    .fv-panggung { flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; background: var(--fv-panggung); }
    #kpkp-file-gambar { max-width: 100%; max-height: 100%; object-fit: contain; display: none; }
    #kpkp-file-frame { flex: 1; align-self: stretch; border: 0; width: 100%; display: none; background: #fff; }
    #kpkp-file-loading { font-size: .85rem; color: var(--fv-redup); }
    #kpkp-file-message { display: none; max-width: 34rem; padding: 1.5rem; text-align: center; }
    #kpkp-file-message-text { margin: 0; font-size: .9rem; line-height: 1.6; color: var(--fv-teks); }
    #kpkp-file-message-close { margin-top: 1.1rem; border: 0; border-radius: 10px; padding: .5rem 1.1rem; font-size: .8rem; font-weight: 700; cursor: pointer; background: var(--fv-teks); color: var(--fv-latar); }
</style>
<dialog id="kpkp-file-dialog">
    <div style="display:flex;flex-direction:column;height:100%">
        <div class="fv-kepala">
            <strong id="kpkp-file-title" class="fv-judul">Berkas</strong>
            <a id="kpkp-file-newtab" href="#" target="_blank" rel="noopener">Buka di tab baru ↗</a>
            <button type="button" id="kpkp-file-close" aria-label="Tutup">✕</button>
        </div>
        <div class="fv-panggung">
            <p id="kpkp-file-loading">Memuat berkas…</p>
            <div id="kpkp-file-message" role="status">
                <div style="width:44px;height:44px;margin:0 auto .9rem;border-radius:999px;background:rgba(220,38,38,.1);color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.35rem;font-weight:900">!</div>
                <p id="kpkp-file-message-text"></p>
                <button type="button" id="kpkp-file-message-close">Mengerti</button>
            </div>
            <img id="kpkp-file-gambar" alt="">
            <iframe id="kpkp-file-frame" title="Pratinjau berkas"></iframe>
        </div>
    </div>
</dialog>
<script>
(function () {
    'use strict';
    var dlg = document.getElementById('kpkp-file-dialog');
    var frame = document.getElementById('kpkp-file-frame');
    var gambar = document.getElementById('kpkp-file-gambar');
    var ttl = document.getElementById('kpkp-file-title');
    var newtab = document.getElementById('kpkp-file-newtab');
    var loading = document.getElementById('kpkp-file-loading');
    var msgBox = document.getElementById('kpkp-file-message');
    var msgText = document.getElementById('kpkp-file-message-text');
    var blobUrl = null;
    var urutan = 0; // nomor pembukaan; balasan fetch milik pembukaan lama diabaikan
    if (!dlg || !dlg.showModal) return; // browser purba: biarkan href asli bekerja

    function tutup() {
        frame.src = 'about:blank';
        frame.style.display = 'none';
        gambar.removeAttribute('src');
        gambar.style.display = 'none';
        loading.style.display = '';
        msgBox.style.display = 'none';
        newtab.style.display = '';
        if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; }
    }

    /**
     * Tampilkan kondisi DI DALAM modal dan biarkan bertahan sampai pengguna
     * menutupnya sendiri. Dulu jalur ini menutup modal lalu navigasi penuh -
     * pengulangan masalah "halaman mati sedikit" dalam bentuk kecil.
     */
    function tampilkanPesan(teks) {
        loading.style.display = 'none';
        frame.style.display = 'none';
        gambar.style.display = 'none';
        newtab.style.display = 'none'; // berkasnya tidak ada; tab baru tak menolong
        msgText.textContent = teks;
        msgBox.style.display = 'block';
    }

    /** Ambil pesan flash dari HTML balasan (blob JSON milik pusat notifikasi). */
    function pesanDariHtml(html) {
        try {
            var doc = new DOMParser().parseFromString(html, 'text/html'); // tidak mengeksekusi script
            var blob = doc.querySelector('script[data-kpkp-flash-notifications]');
            var list = blob ? JSON.parse(blob.textContent || '[]') : [];
            for (var i = 0; i < list.length; i++) {
                if (list[i] && list[i].message) return String(list[i].message);
            }
        } catch (err) { /* jatuh ke pesan umum di bawah */ }
        return '';
    }
    // tutup() dipanggil EKSPLISIT di tiap jalur penutupan, bukan hanya lewat
    // event 'close' - event antrean itu terbukti bisa tidak tiba di sebagian
    // lingkungan. tutup() idempoten, dobel panggil aman.
    // Jalur ESC. Event 'close' bisa tiba SESUDAH modal dibuka lagi (buka-tutup-buka cepat): tanpa cek
    // dlg.open ia menghapus berkas yang baru tampil dan menyisakan "Memuat berkas" (4 Okt 2026).
    dlg.addEventListener('close', function () { if (!dlg.open) { tutup(); } });
    document.getElementById('kpkp-file-close').addEventListener('click', function () { dlg.close(); tutup(); });
    document.getElementById('kpkp-file-message-close').addEventListener('click', function () { dlg.close(); tutup(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) { dlg.close(); tutup(); } }); // klik backdrop

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-file-view]');
        if (!a || e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        ttl.textContent = a.getAttribute('data-file-title') || (a.textContent || 'Berkas').trim();
        newtab.href = a.href;
        var nomor = ++urutan;
        tutup(); // keadaan bersih untuk pembukaan ini
        dlg.showModal();
        fetch(a.href, { credentials: 'same-origin' })
            .then(function (r) {
                if (nomor !== urutan) { return null; } // modal sudah dibuka untuk berkas lain
                var ct = (r.headers.get('content-type') || '');
                // text/html = bukan berkas, melainkan halaman (mis. redirect
                // "berkas hilang" + flash). Pesannya dipanen dari HTML itu dan
                // ditampilkan DI DALAM modal - modal tidak menutup sendiri.
                if (!r.ok || r.redirected || ct.indexOf('text/html') !== -1) {
                    return r.text().then(function (html) {
                        tampilkanPesan(pesanDariHtml(html)
                            || 'Berkas ini belum dapat ditampilkan. Silakan coba lagi atau hubungi admin.');
                        return null;
                    });
                }
                return r.blob();
            })
            .then(function (b) {
                if (!b || nomor !== urutan || !dlg.open) return;
                if (blobUrl) { URL.revokeObjectURL(blobUrl); } // jaga-jaga tutup() terlewat
                blobUrl = URL.createObjectURL(b);
                loading.style.display = 'none';
                if ((b.type || '').indexOf('image/') === 0) { // gambar: di tengah, diperkecil agar muat
                    gambar.alt = ttl.textContent;
                    gambar.src = blobUrl;
                    gambar.style.display = 'block';
                } else {
                    frame.src = blobUrl;
                    frame.style.display = 'block'; // bukan '': CSS #kpkp-file-frame bawaannya display:none
                }
            })
            .catch(function () {
                if (nomor !== urutan) { return; }
                tampilkanPesan('Berkas gagal dimuat. Periksa koneksi Anda lalu coba lagi.');
            });
    });
})();
</script>
