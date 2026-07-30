/**
 * Loader progresif shell dashboard (admin/kabkota/bidang/akun).
 *
 * Versi ringkas dari loader portal (layouts/footer.php): klik link internal
 * ke controller dashboard di-fetch sebagai partial (cabang AJAX
 * render_user_dashboard) lalu di-swap ke #main-content — sidebar/topbar tidak
 * dimuat ulang. Kelayakan diputuskan dari RESPONS: redirect (sesi habis),
 * bukan text/html (berkas bukti), atau dokumen utuh <!doctype (halaman tanpa
 * cabang partial) semuanya jatuh ke navigasi penuh.
 */
(function () {
    'use strict';

    function reExecuteScripts(wrapper) {
        var scripts = Array.prototype.slice.call(wrapper.querySelectorAll('script'));
        return scripts.reduce(function (chain, oldScript) {
            return chain.then(function () {
                return new Promise(function (resolve) {
                    var s = document.createElement('script');
                    for (var i = 0; i < oldScript.attributes.length; i++) {
                        s.setAttribute(oldScript.attributes[i].name, oldScript.attributes[i].value);
                    }
                    s.textContent = oldScript.textContent;
                    if (s.src) {
                        s.onload = resolve;
                        s.onerror = resolve;
                        oldScript.replaceWith(s);
                    } else {
                        oldScript.replaceWith(s);
                        resolve();
                    }
                });
            });
        }, Promise.resolve());
    }

    var SKELETON = '<div class="animate-pulse space-y-4" aria-hidden="true">'
        + '<div style="height:1.75rem;width:16rem;border-radius:.5rem;background:rgba(138,172,176,.18)"></div>'
        + '<div style="height:6rem;border-radius:1rem;background:rgba(138,172,176,.12)"></div>'
        + '<div style="height:2.5rem;border-radius:.75rem;background:rgba(138,172,176,.12)"></div>'
        + '<div style="height:2.5rem;border-radius:.75rem;background:rgba(138,172,176,.12)"></div>'
        + '<div style="height:2.5rem;border-radius:.75rem;background:rgba(138,172,176,.12)"></div>'
        + '</div>';

    var loadToken = 0;

    /**
     * Tukar SELURUH menu sidebar dengan versi yang dikirim server.
     *
     * Dulu fungsi ini menempelkan `aria-current` pada tautan yang path-nya
     * COCOK PERSIS. Tiga akibatnya, dan ketiganya terlihat oleh pengguna:
     *
     *   1. Sorotan lama tidak pernah dilepas. Sorotan itu kelas Tailwind dari
     *      render server, bukan aria-current, jadi dua item menyala bersamaan.
     *   2. /Rekam_Perumahan/input tidak cocok persis dengan tautan
     *      /Rekam_Perumahan, jadi selama di wizard tidak ada yang menyala.
     *   3. Sub-menu dirender server; JS tidak pernah menyentuhnya, jadi cabang
     *      lama tetap terbuka di halaman yang tidak ada hubungannya.
     *
     * Akarnya satu: aturan aktif dan cabang terbuka diputuskan
     * MY_Controller::dashboard_menu(), lalu SEBAGIAN disalin ulang di sini.
     * Dua implementasi untuk satu aturan pasti berbeda suatu saat. Sekarang
     * server mengirim menunya utuh dan JS hanya menukar — satu aturan, satu
     * tempat.
     */
    function tukarSidebar(html) {
        var nav = document.getElementById('sidebar-nav');
        if (!nav) return;
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var baru = tmp.querySelector('#sidebar-nav-baru');
        if (!baru) return;
        nav.innerHTML = baru.innerHTML;
        // Menu memakai Alpine (x-show, tombol lipat) — tanpa initTree, sub-menu
        // yang baru disuntik tidak akan pernah menanggapi klik.
        if (window.Alpine) { Alpine.initTree(nav); }
    }

    function loadPage(url, push) {
        var main = document.getElementById('main-content');
        if (!main) { window.location.href = url; return; }
        var myToken = ++loadToken;
        main.innerHTML = SKELETON;
        main.scrollTop = 0;

        // `X-Shell: admin` menandai SIAPA yang meminta, bukan sekadar "ini AJAX".
        // Tanpa ini `render_user_dashboard()` melepas shell admin untuk permintaan
        // AJAX apa pun — termasuk dari loader portal PUBLIK di layouts/footer.php,
        // yang lalu menyuntikkan markup admin ke dalam panel publik. Hasilnya
        // halaman admin tampil tanpa sidebar, tanpa tailwind-admin.css, dan tanpa
        // ikon Phosphor (portal memakai FontAwesome): judul kartu tak terbaca dan
        // ikon jadi kotak kosong. Terjadi nyata pada /Rekam_Data.
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Shell': 'admin' }, credentials: 'same-origin' })
            .then(function (res) {
                if (myToken !== loadToken) return null;
                if (!res.ok || res.redirected) { window.location.href = url; return null; }
                if (((res.headers.get('content-type')) || '').indexOf('text/html') === -1) {
                    window.location.href = url; return null;
                }
                var title = res.headers.get('X-Page-Title');
                if (title) {
                    try { document.title = decodeURIComponent(title) + ' | Klinik PKP'; } catch (e) {}
                }
                return res.text();
            })
            .then(function (html) {
                if (html === null || html === undefined || myToken !== loadToken) return;
                if (/^\s*(<!doctype|<html)/i.test(html)) { window.location.href = url; return; }

                // Menu dipisahkan SEBELUM konten disuntik: `<template>` di ekor
                // balasan tidak boleh ikut mendarat di area konten.
                var potong = html.indexOf('<template id="sidebar-nav-baru">');
                var menuHtml = potong === -1 ? '' : html.slice(potong);
                if (potong !== -1) { html = html.slice(0, potong); }

                main.innerHTML = '<div class="animate-[fadeIn_0.3s_ease-out]">' + html + '</div>';
                reExecuteScripts(main).then(function () {
                    if (window.Alpine) { Alpine.initTree(main); }
                    if (push) { history.pushState({ adminUrl: url }, '', url); }
                    if (menuHtml) { tukarSidebar(menuHtml); }
                }).catch(function () { /* konten sudah tampil; jangan kunci UI */ });
            })
            .catch(function () {
                if (myToken === loadToken) window.location.href = url;
            });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link || !link.href || e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        var href = link.getAttribute('href') || '';
        if (href === '' || href.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(href)) return;
        if (link.hostname !== window.location.hostname) return;
        // Logout dan alur auth SELALU navigasi penuh: keduanya mengganti sesi,
        // dan menukar konten sambil sesi berpindah meninggalkan shell milik
        // peran lama.
        if (/Auth\//.test(link.pathname)) return;

        // SEMUA tautan internal lain lewat jalur progresif. Kelayakannya
        // diputuskan dari RESPONS, bukan dari nama jalurnya — loadPage() sudah
        // menjatuhkan diri ke navigasi penuh pada redirect, pada balasan bukan
        // text/html, dan pada dokumen utuh.
        //
        // Sebelumnya di sini ada allowlist /(Admin|akun|Pengaturan|User_Profile)/.
        // Seluruh modul Rekam Data tidak memuat satu pun kata itu, jadi
        // Rekam_Data, Rekam_Perumahan, Rekam_Kawasan, dan Rekam_Tinjauan
        // diam-diam memuat ulang seluruh halaman. Ini kelas kesalahan yang sama
        // dengan daftar jalur di layouts/footer.php yang diperbaiki lebih dulu
        // hari ini — daftar yang menyebut nama satu per satu memang selalu bocor
        // pada nama yang belum ada saat ia ditulis. Halaman baru kini otomatis
        // ikut, tanpa perlu diingat siapa pun.
        // S2 — opt-out dihormati SEBELUM fetch. Tautan yang sengaja ditandai
        // tidak boleh difetch dulu lalu baru jatuh ke navigasi penuh: itu dua
        // GET untuk satu klik, dan endpoint yang menghitung kunjungan jadi
        // naik dua kali.
        if (link.hasAttribute('data-no-page-transition')
            || link.closest('[data-no-page-transition]')) return;
        e.preventDefault();
        loadPage(link.href, true);
    });

    // S1 — entry AWAL direkam sebelum pushState pertama, supaya Back dari
    // halaman pertama tidak menghasilkan popstate ber-state null (URL berubah,
    // isi layar tetap).
    if (!history.state || !history.state.adminUrl) {
        history.replaceState({ adminUrl: window.location.href }, '', window.location.href);
    }

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.adminUrl) { loadPage(e.state.adminUrl, false); return; }
        // State kosong = entry yang tidak kita rekam. Muat URL aktif supaya isi
        // layar selalu cocok dengan alamatnya.
        loadPage(window.location.href, false);
    });
})();
