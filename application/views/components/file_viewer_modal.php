<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Penampil berkas privat dalam modal — dipakai portal DAN shell dashboard.
 *
 * Dulu "Lihat berkas" = target="_blank" (tab baru putih sampai file termuat)
 * atau navigasi penuh ke biner — halaman "mati sebentar", dan loader
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
<dialog id="kpkp-file-dialog" style="width:min(92vw,960px);height:min(88vh,900px);padding:0;border:0;border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 25px 60px rgba(0,0,0,.35)">
    <div style="display:flex;flex-direction:column;height:100%">
        <div style="display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-bottom:1px solid rgba(10,26,31,.1);background:#f6fafb">
            <strong id="kpkp-file-title" style="flex:1;font-size:.85rem;color:#0a1a1f;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Berkas</strong>
            <a id="kpkp-file-newtab" href="#" target="_blank" rel="noopener" style="font-size:.75rem;font-weight:700;color:#00545f;text-decoration:none">Buka di tab baru ↗</a>
            <button type="button" id="kpkp-file-close" aria-label="Tutup" style="border:0;background:rgba(10,26,31,.06);border-radius:8px;width:28px;height:28px;cursor:pointer;font-size:1rem;line-height:1">✕</button>
        </div>
        <p id="kpkp-file-loading" style="margin:auto;font-size:.85rem;color:#5b7a80">Memuat berkas…</p>
        <div id="kpkp-file-message" role="status" style="display:none;margin:auto;max-width:34rem;padding:1.5rem;text-align:center">
            <div style="width:44px;height:44px;margin:0 auto .9rem;border-radius:999px;background:rgba(220,38,38,.1);color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.35rem;font-weight:900">!</div>
            <p id="kpkp-file-message-text" style="margin:0;font-size:.9rem;line-height:1.6;color:#0a1a1f"></p>
            <button type="button" id="kpkp-file-message-close" style="margin-top:1.1rem;border:0;border-radius:10px;padding:.5rem 1.1rem;font-size:.8rem;font-weight:700;cursor:pointer;background:#0a1a1f;color:#fff">Mengerti</button>
        </div>
        <iframe id="kpkp-file-frame" title="Pratinjau berkas" style="flex:1;border:0;width:100%;display:none"></iframe>
    </div>
</dialog>
<script>
(function () {
    'use strict';
    var dlg = document.getElementById('kpkp-file-dialog');
    var frame = document.getElementById('kpkp-file-frame');
    var ttl = document.getElementById('kpkp-file-title');
    var newtab = document.getElementById('kpkp-file-newtab');
    var loading = document.getElementById('kpkp-file-loading');
    var msgBox = document.getElementById('kpkp-file-message');
    var msgText = document.getElementById('kpkp-file-message-text');
    var blobUrl = null;
    if (!dlg || !dlg.showModal) return; // browser purba: biarkan href asli bekerja

    function tutup() {
        frame.src = 'about:blank';
        frame.style.display = 'none';
        loading.style.display = '';
        msgBox.style.display = 'none';
        newtab.style.display = '';
        if (blobUrl) { URL.revokeObjectURL(blobUrl); blobUrl = null; }
    }

    /**
     * Tampilkan kondisi DI DALAM modal dan biarkan bertahan sampai pengguna
     * menutupnya sendiri. Dulu jalur ini menutup modal lalu navigasi penuh —
     * pengulangan masalah "halaman mati sedikit" dalam bentuk kecil.
     */
    function tampilkanPesan(teks) {
        loading.style.display = 'none';
        frame.style.display = 'none';
        newtab.style.display = 'none'; // berkasnya tidak ada; tab baru tak menolong
        msgText.textContent = teks;
        msgBox.style.display = '';
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
    // event 'close' — event antrean itu terbukti bisa tidak tiba di sebagian
    // lingkungan. tutup() idempoten, dobel panggil aman.
    dlg.addEventListener('close', tutup); // jalur ESC
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
        dlg.showModal();
        fetch(a.href, { credentials: 'same-origin' })
            .then(function (r) {
                var ct = (r.headers.get('content-type') || '');
                // text/html = bukan berkas, melainkan halaman (mis. redirect
                // "berkas hilang" + flash). Pesannya dipanen dari HTML itu dan
                // ditampilkan DI DALAM modal — modal tidak menutup sendiri.
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
                if (!b) return;
                if (blobUrl) { URL.revokeObjectURL(blobUrl); } // jaga-jaga tutup() terlewat
                blobUrl = URL.createObjectURL(b);
                frame.src = blobUrl;
                loading.style.display = 'none';
                frame.style.display = '';
            })
            .catch(function () {
                tampilkanPesan('Berkas gagal dimuat. Periksa koneksi Anda lalu coba lagi.');
            });
    });
})();
</script>
