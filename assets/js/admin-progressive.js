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

    var PATH_DASHBOARD = /(Admin|akun|Pengaturan|User_Profile)/;

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

    function setSidebarActive(url) {
        var path = new URL(url, window.location.href).pathname;
        document.querySelectorAll('aside a').forEach(function (a) {
            if (a.pathname === path) { a.setAttribute('aria-current', 'page'); }
            else { a.removeAttribute('aria-current'); }
        });
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
                main.innerHTML = '<div class="animate-[fadeIn_0.3s_ease-out]">' + html + '</div>';
                reExecuteScripts(main).then(function () {
                    if (window.Alpine) { Alpine.initTree(main); }
                    if (push) { history.pushState({ adminUrl: url }, '', url); }
                    setSidebarActive(url);
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
        // Hanya controller dashboard; logout/portal biar navigasi penuh.
        if (/Auth\//.test(link.pathname)) return;
        if (!PATH_DASHBOARD.test(link.pathname)) return;
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
