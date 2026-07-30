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

    // Kartu tabel admin (toolbar + tabel + paginasi) yang menandai dirinya bisa
    // ditukar sendirian. Lihat `lingkupTabel()`.
    var SEL_TABEL = '[data-tabel-admin]';

    // Panel yang menyatakan POST-nya boleh dijalankan progresif. Opt-in, karena
    // POST tidak bisa dibatalkan lalu diulang sebagai navigasi penuh. Syaratnya:
    // seluruh aksinya memakai PRG (POST lalu redirect ke halaman GET).
    var SEL_PANEL = '[data-panel-progresif]';

    // URL yang isinya sedang tampil. Bukan `location.href`: pada popstate,
    // alamat sudah berubah SEBELUM handler jalan, jadi membandingkan tujuan
    // dengan `location` di sana selalu menghasilkan "sama" dan Back dari
    // halaman lain akan salah dianggap ganti tab.
    var urlSekarang = window.location.href;

    /**
     * Apakah perpindahan ini cuma mengganti isi tabel, bukan pindah halaman?
     *
     * Tab status, kotak cari, tombol urut, dan paginasi semuanya dibangun
     * `admin_table_url()`: path-nya tetap, yang berubah cuma query string. Jadi
     * path yang sama = masih tabel yang sama, cukup tukar kartunya. Tautan
     * "Detail"/"Tinjau" di dalam baris tabel path-nya berbeda, jadi ia tetap
     * berpindah halaman seperti biasa.
     *
     * Keputusan ini masih bisa dibatalkan setelah balasan datang: kalau kartu
     * bertanda tidak ada di balasan, loadPage() kembali menukar seluruh konten.
     */
    function lingkupTabel(url) {
        if ( ! document.querySelector(SEL_TABEL)) return false;
        try {
            return new URL(url, location.href).pathname
                === new URL(urlSekarang, location.href).pathname;
        } catch (e) { return false; }
    }

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

    /**
     * @param body FormData opsional. Kalau ada, permintaan dikirim sebagai POST
     *             dan redirect JUSTRU yang ditunggu (pola PRG) — bukan tanda
     *             sesi habis seperti pada GET.
     */
    function loadPage(url, push, tabelSaja, body) {
        var main = document.getElementById('main-content');
        if (!main) { window.location.href = url; return; }
        var myToken = ++loadToken;

        // Alamat yang akan masuk history. Untuk POST ini BUKAN `url`: yang harus
        // tercatat adalah tujuan redirect-nya (mis. .../input?langkah=isian),
        // bukan endpoint aksinya (.../simpan_program) — kalau endpoint aksi yang
        // masuk history, tombol Back mengarah ke URL yang hanya menerima POST.
        var urlAkhir = url;

        // Ganti tab TIDAK memasang skeleton dan TIDAK menggulir ke atas: judul,
        // toolbar, dan posisi baca pengguna harus tetap di tempatnya. Kartunya
        // cuma diredupkan supaya terlihat sedang bekerja.
        var wadah = tabelSaja ? document.querySelector(SEL_TABEL) : null;
        if (wadah) {
            wadah.setAttribute('aria-busy', 'true');
            wadah.style.opacity = '0.55';
        } else {
            main.innerHTML = SKELETON;
            main.scrollTop = 0;
        }

        // `X-Shell: admin` menandai SIAPA yang meminta, bukan sekadar "ini AJAX".
        // Tanpa ini `render_user_dashboard()` melepas shell admin untuk permintaan
        // AJAX apa pun — termasuk dari loader portal PUBLIK di layouts/footer.php,
        // yang lalu menyuntikkan markup admin ke dalam panel publik. Hasilnya
        // halaman admin tampil tanpa sidebar, tanpa tailwind-admin.css, dan tanpa
        // ikon Phosphor (portal memakai FontAwesome): judul kartu tak terbaca dan
        // ikon jadi kotak kosong. Terjadi nyata pada /Rekam_Data.
        var opsi = { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Shell': 'admin' }, credentials: 'same-origin' };
        if (body) { opsi.method = 'POST'; opsi.body = body; }

        fetch(url, opsi)
            .then(function (res) {
                if (myToken !== loadToken) return null;
                // Redirect pada GET = sesi habis -> navigasi penuh. Pada POST,
                // redirect adalah hasil yang BENAR: seluruh aksi wizard memakai
                // PRG, jadi menolak redirect di sini berarti menolak semuanya.
                if (!res.ok || (res.redirected && ! body)) { window.location.href = url; return null; }
                if (((res.headers.get('content-type')) || '').indexOf('text/html') === -1) {
                    window.location.href = res.url || url; return null;
                }
                urlAkhir = res.url || url;
                var title = res.headers.get('X-Page-Title');
                if (title) {
                    try { document.title = decodeURIComponent(title) + ' | Klinik PKP'; } catch (e) {}
                }
                return res.text();
            })
            .then(function (html) {
                if (html === null || html === undefined || myToken !== loadToken) return;
                if (/^\s*(<!doctype|<html)/i.test(html)) { window.location.href = urlAkhir; return; }

                // Menu dipisahkan SEBELUM konten disuntik: `<template>` di ekor
                // balasan tidak boleh ikut mendarat di area konten.
                var potong = html.indexOf('<template id="sidebar-nav-baru">');

                // Penanda menu = tanda tangan partial DASHBOARD. Halaman portal
                // publik juga punya cabang AJAX dan juga membalas potongan
                // text/html, jadi ia lolos ketiga penjaga di atas — tanpa syarat
                // ini, tautan dari dashboard ke portal menyuntikkan markup portal
                // ke dalam shell admin (kebalikan persis dari bug X-Shell di
                // komentar fetch). Arah gagalnya aman: partial yang tidak
                // menyertakan penanda dimuat sebagai halaman penuh.
                if (potong === -1) { window.location.href = urlAkhir; return; }

                var menuHtml = html.slice(potong);
                html = html.slice(0, potong);

                if (wadah) {
                    wadah.style.opacity = '';
                    wadah.removeAttribute('aria-busy');
                    var kotak = document.createElement('div');
                    kotak.innerHTML = html;
                    var kartuBaru = kotak.querySelector(SEL_TABEL);
                    if (kartuBaru) {
                        wadah.innerHTML = kartuBaru.innerHTML;
                        // Wadahnya tetap, jadi komponen Alpine di LUAR kartu
                        // (mis. modal proses antrean) tidak ikut dibuat ulang —
                        // hanya isi barunya yang perlu dipasangi.
                        if (window.Alpine) { Alpine.initTree(wadah); }
                        // Sidebar sengaja TIDAK ditukar: path tidak berubah, jadi
                        // sorotan dan cabang terbukanya sudah benar. Menukarnya
                        // hanya akan menutup sub-menu yang sedang dibuka pengguna.
                        urlSekarang = urlAkhir;
                        if (push) { history.pushState({ adminUrl: urlAkhir }, '', urlAkhir); }
                        return;
                    }
                    // Balasan tanpa kartu bertanda — perlakukan sebagai pindah
                    // halaman biasa, jangan tinggalkan layar setengah tertukar.
                }

                main.innerHTML = '<div class="animate-[fadeIn_0.3s_ease-out]">' + html + '</div>';
                reExecuteScripts(main).then(function () {
                    if (window.Alpine) { Alpine.initTree(main); }
                    urlSekarang = urlAkhir;
                    if (push) { history.pushState({ adminUrl: urlAkhir }, '', urlAkhir); }
                    if (menuHtml) { tukarSidebar(menuHtml); }
                }).catch(function () { /* konten sudah tampil; jangan kunci UI */ });
            })
            .catch(function () {
                if (myToken === loadToken) window.location.href = urlAkhir;
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
        loadPage(link.href, true, !!link.closest(SEL_TABEL) && lingkupTabel(link.href));
    });

    // Kotak cari toolbar adalah <form method="get">, bukan tautan — tanpa ini ia
    // tetap memuat ulang seluruh konten padahal hasilnya cuma mengganti isi
    // tabel yang sama, persis seperti tab status di sebelahnya.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if ( ! form || e.defaultPrevented) return;
        var metode = (form.getAttribute('method') || 'get').toLowerCase();

        if (metode === 'get' && form.closest(SEL_TABEL)) {
            var url = (form.getAttribute('action') || window.location.pathname)
                + '?' + new URLSearchParams(new FormData(form)).toString();
            if ( ! lingkupTabel(url)) return;
            e.preventDefault();
            loadPage(url, true, true);
            return;
        }

        // POST di dalam panel bertanda — wizard Rekam Data. Setiap langkahnya
        // POST lalu redirect (PRG), jadi tanpa ini seluruh wizard memuat ulang
        // halaman penuh di SETIAP tombol: kedip putih, shell dibangun ulang,
        // gulir lompat — padahal yang berubah cuma isi panelnya.
        //
        // OPT-IN, bukan berlaku umum. Menangkap semua POST secara diam-diam
        // berbahaya: endpoint yang membalas JSON atau tidak redirect (login,
        // aksi Alpine) akan rusak, dan POST tidak bisa "dibatalkan lalu diulang
        // sebagai navigasi penuh" — kirimannya sudah terjadi. Panel harus
        // menyatakan diri siap.
        if (metode !== 'post' || ! form.closest(SEL_PANEL)) return;

        // Tombol submit bernama ikut menentukan hasil (mis. <button name="langkah"
        // value="program"> vs value="bnba"). FormData saja TIDAK memuatnya, jadi
        // nilainya harus diambil dari `e.submitter`. Kalau peramban tidak
        // menyediakannya, JANGAN ditangkap: mengirim tanpa nilai itu bukan
        // "sedikit kurang bagus", tapi memindahkan pengguna ke langkah yang
        // salah. Biarkan submit biasa jalan — halaman memuat penuh, tapi benar.
        if ( ! ('submitter' in e)) return;

        var data = new FormData(form);
        if (e.submitter && e.submitter.name) {
            data.append(e.submitter.name, e.submitter.value);
        }
        e.preventDefault();
        loadPage(form.getAttribute('action') || window.location.href, true, false, data);
    });

    // S1 — entry AWAL direkam sebelum pushState pertama, supaya Back dari
    // halaman pertama tidak menghasilkan popstate ber-state null (URL berubah,
    // isi layar tetap).
    if (!history.state || !history.state.adminUrl) {
        history.replaceState({ adminUrl: window.location.href }, '', window.location.href);
    }

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.adminUrl) {
            loadPage(e.state.adminUrl, false, lingkupTabel(e.state.adminUrl));
            return;
        }
        // State kosong = entry yang tidak kita rekam. Muat URL aktif supaya isi
        // layar selalu cocok dengan alamatnya.
        loadPage(window.location.href, false, lingkupTabel(window.location.href));
    });
})();
