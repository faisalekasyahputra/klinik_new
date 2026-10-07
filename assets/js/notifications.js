(function (global, document) {
    'use strict';

    global.KPKP = global.KPKP || {};
    if (global.KPKP.notify) return;

    var titles = {
        success: 'Berhasil',
        error: 'Terjadi kesalahan',
        warning: 'Perhatian',
        info: 'Informasi'
    };
    // Ikon SVG konstan (bukan dari data), aman dipasang lewat innerHTML.
    var ikonKecil = function (isi) { return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + isi + '</svg>'; };
    var icons = {
        success: ikonKecil('<path d="M20 6 9 17l-5-5"/>'),
        error: ikonKecil('<circle cx="12" cy="12" r="9.5"/><line x1="12" y1="7.5" x2="12" y2="12.5"/><line x1="12" y1="16.2" x2="12.01" y2="16.2"/>'),
        warning: ikonKecil('<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>'),
        info: ikonKecil('<circle cx="12" cy="12" r="9.5"/><line x1="12" y1="11" x2="12" y2="16.5"/><line x1="12" y1="7.6" x2="12.01" y2="7.6"/>')
    };
    var active = Object.create(null);

    function region() {
        var node = document.querySelector('[data-kpkp-notification-region]');
        if (node) return node;
        node = document.createElement('div');
        node.className = 'kpkp-notification-region';
        node.setAttribute('data-kpkp-notification-region', '');
        node.setAttribute('aria-label', 'Notifikasi');
        node.setAttribute('aria-live', 'polite');
        node.setAttribute('aria-relevant', 'additions text');
        document.body.appendChild(node);
        return node;
    }

    /* Modal <dialog> yang dibuka dengan showModal() berada di lapisan teratas (top layer) peramban, di
       atas z-index berapa pun, sehingga toast dulu tertutup modal (umpan balik user 7 Okt 2026). Wadah
       toast ikut naik ke lapisan teratas sebagai popover manual; ditutup lalu dibuka lagi setiap ada
       toast baru supaya berada di atas modal yang dibuka sesudahnya. Peramban tanpa Popover API tetap
       memakai z-index lama. */
    function keAtas(node) {
        if (typeof node.showPopover !== 'function') return;
        try {
            if (!node.hasAttribute('popover')) node.setAttribute('popover', 'manual');
            if (node.matches(':popover-open')) node.hidePopover();
            node.showPopover();
        } catch (e) { /* tetap tampil dengan z-index biasa */ }
    }

    function removeNow(toast) {
        if (!toast) return;
        clearTimeout(toast._kpkpTimer);
        if (toast._kpkpId && active[toast._kpkpId] === toast) delete active[toast._kpkpId];
        toast.remove();
    }

    function close(toast) {
        if (!toast || !toast.isConnected) return;
        clearTimeout(toast._kpkpTimer);
        toast.dataset.state = 'closing';
        global.setTimeout(function () { removeNow(toast); }, 180);
    }

    function show(message, type, options) {
        if (message === undefined || message === null || String(message).trim() === '') return null;
        type = Object.prototype.hasOwnProperty.call(titles, type) ? type : 'info';
        options = options || {};

        var id = options.id ? String(options.id) : '';
        if (id && active[id]) removeNow(active[id]);

        var toast = document.createElement('section');
        toast.className = 'kpkp-notification kpkp-notification--' + type;
        toast.dataset.state = 'entering';
        toast.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');
        toast.setAttribute('aria-atomic', 'true');
        toast._kpkpId = id;

        var icon = document.createElement('span');
        icon.className = 'kpkp-notification__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = icons[type];

        var body = document.createElement('div');
        var title = document.createElement('strong');
        title.className = 'kpkp-notification__title';
        title.textContent = options.title || titles[type];
        var text = document.createElement('p');
        text.className = 'kpkp-notification__message';
        text.textContent = String(message);
        body.appendChild(title);
        body.appendChild(text);

        var dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'kpkp-notification__close';
        dismiss.setAttribute('aria-label', 'Tutup notifikasi');
        dismiss.textContent = '\u00d7';
        dismiss.addEventListener('click', function () { close(toast); });

        toast.appendChild(icon);
        toast.appendChild(body);
        toast.appendChild(dismiss);
        var wadah = region();
        wadah.appendChild(toast);
        keAtas(wadah);
        if (id) active[id] = toast;

        global.requestAnimationFrame(function () {
            global.requestAnimationFrame(function () { toast.dataset.state = 'open'; });
        });

        var defaultDuration = type === 'success' || type === 'info' ? 5000 : 0;
        var duration = options.sticky ? 0 : (options.duration === undefined ? defaultDuration : Number(options.duration));
        function schedule() {
            clearTimeout(toast._kpkpTimer);
            if (duration > 0) toast._kpkpTimer = global.setTimeout(function () { close(toast); }, duration);
        }
        toast.addEventListener('mouseenter', function () { clearTimeout(toast._kpkpTimer); });
        toast.addEventListener('mouseleave', schedule);
        toast.addEventListener('focusin', function () { clearTimeout(toast._kpkpTimer); });
        toast.addEventListener('focusout', schedule);
        schedule();
        return toast;
    }

    // Ikon SVG konstan (bukan dari data), aman dipasang lewat innerHTML.
    var svg = function (isi) { return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + isi + '</svg>'; };
    var ikonDialog = {
        galat: svg('<circle cx="12" cy="12" r="9.5"/><line x1="12" y1="7.5" x2="12" y2="12.5"/><line x1="12" y1="16.2" x2="12.01" y2="16.2"/>'),
        tanya: svg('<circle cx="12" cy="12" r="9.5"/><path d="M9.3 9.2a2.8 2.8 0 0 1 5.4 1c0 1.9-2.7 2.5-2.7 2.5"/><line x1="12" y1="16.6" x2="12.01" y2="16.6"/>'),
        bahaya: svg('<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/><path d="M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7"/>'),
        // Pemberitahuan yang meminta tindakan, bukan galat (5 Okt 2026): ikon "i", aksen teal.
        info: svg('<circle cx="12" cy="12" r="9.5"/><line x1="12" y1="11" x2="12" y2="16.5"/><line x1="12" y1="7.6" x2="12.01" y2="7.6"/>')
    };

    /**
     * Dialog di tengah layar. jenis: 'galat' (bawaan), 'tanya', atau 'bahaya'.
     * aksi: [{label, url?, utama?, nilai?, fokus?}]; tanpa url = tombol yang menutup dialog dengan `nilai`-nya.
     * URL hanya diterima bila se-origin (dibangun server lewat base_url). options.selesai(nilai) dipanggil saat ditutup.
     */
    function dialog(message, options) {
        if (message === undefined || message === null || String(message).trim() === '') return null;
        options = options || {};
        var lama = document.querySelector('dialog.kpkp-dialog');
        if (lama) lama.close();
        var jenis = Object.prototype.hasOwnProperty.call(ikonDialog, options.jenis) ? options.jenis : 'galat';
        var hasil;

        var box = document.createElement('dialog');
        box.className = 'kpkp-dialog kpkp-dialog--' + jenis;
        box.setAttribute('aria-labelledby', 'kpkp-dialog-judul');
        box.setAttribute('aria-describedby', 'kpkp-dialog-pesan');

        var icon = document.createElement('span');
        icon.className = 'kpkp-dialog__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = ikonDialog[jenis];
        var title = document.createElement('h2');
        title.className = 'kpkp-dialog__title';
        title.id = 'kpkp-dialog-judul';
        title.textContent = options.title || titles.error;
        var text = document.createElement('p');
        text.className = 'kpkp-dialog__message';
        text.id = 'kpkp-dialog-pesan';
        text.textContent = String(message);

        var bar = document.createElement('div');
        bar.className = 'kpkp-dialog__actions';
        var aksi = Array.isArray(options.aksi) && options.aksi.length ? options.aksi : [{ label: 'Mengerti', utama: true }];
        var fokus = null;
        aksi.forEach(function (a) {
            if (!a || !a.label) return;
            var el;
            if (a.url) {
                var tujuan;
                try { tujuan = new URL(String(a.url), global.location.href); } catch (e) { return; }
                if (tujuan.origin !== global.location.origin) return;
                el = document.createElement('a');
                el.href = tujuan.href;
            } else {
                el = document.createElement('button');
                el.type = 'button';
                el.addEventListener('click', function () { hasil = a.nilai; box.close(); });
            }
            el.className = 'kpkp-dialog__btn' + (a.utama ? ' kpkp-dialog__btn--utama' : '');
            el.textContent = String(a.label);
            bar.appendChild(el);
            if (a.fokus || (a.utama && !fokus)) fokus = el;
        });

        box.appendChild(icon);
        box.appendChild(title);
        box.appendChild(text);
        box.appendChild(bar);
        box.addEventListener('close', function () {
            box.remove();
            if (typeof options.selesai === 'function') options.selesai(hasil);
        });
        // Klik di latar (di luar kotak) menutup dialog, sama seperti Esc.
        box.addEventListener('click', function (e) { if (e.target === box) box.close(); });
        document.body.appendChild(box);
        if (typeof box.showModal === 'function') { box.showModal(); } else { box.setAttribute('open', ''); }
        if (fokus) fokus.focus();
        return box;
    }

    /**
     * Pengganti confirm() bawaan: Promise<boolean>. options: {judul, label, bahaya}.
     * Tindakan merusak (bahaya) memberi fokus awal ke "Tidak jadi" supaya Enter tidak langsung menghapus.
     */
    function konfirmasi(message, options) {
        options = options || {};
        return new Promise(function (resolve) {
            var dlg = dialog(message, {
                title: options.judul || 'Lanjutkan tindakan ini?',
                jenis: options.bahaya ? 'bahaya' : 'tanya',
                aksi: [
                    { label: options.label || 'Ya, lanjutkan', utama: true, nilai: true },
                    { label: 'Tidak jadi', nilai: false, fokus: !!options.bahaya }
                ],
                selesai: function (nilai) { resolve(nilai === true); }
            });
            if (!dlg) resolve(false);
        });
    }

    // Formulir bertanda data-konfirmasi (judul: data-konfirmasi-judul, tombol: data-konfirmasi-label,
    // merah: data-konfirmasi-bahaya) ditahan sampai dikonfirmasi. Fase capture, jadi berjalan sebelum
    // penangan submit lain (status memuat, Alpine) dan mereka baru jalan sesudah dikonfirmasi.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-konfirmasi') || form._kpkpYakin) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        var pengirim = event.submitter || null;
        konfirmasi(form.getAttribute('data-konfirmasi'), {
            judul: form.getAttribute('data-konfirmasi-judul'),
            label: form.getAttribute('data-konfirmasi-label'),
            bahaya: form.hasAttribute('data-konfirmasi-bahaya')
        }).then(function (ya) {
            if (!ya) return;
            form._kpkpYakin = true;
            try {
                if (typeof form.requestSubmit === 'function') { form.requestSubmit(pengirim || undefined); } else { form.submit(); }
            } finally {
                form._kpkpYakin = false;
            }
        });
    }, true);

    var api = {
        show: show,
        dialog: dialog,
        konfirmasi: konfirmasi,
        success: function (message, options) { return show(message, 'success', options); },
        error: function (message, options) { return show(message, 'error', options); },
        warning: function (message, options) { return show(message, 'warning', options); },
        info: function (message, options) { return show(message, 'info', options); },
        dismiss: function (id) { if (active[String(id)]) close(active[String(id)]); }
    };
    global.KPKP.notify = api;

    document.addEventListener('kpkp:notify', function (event) {
        var detail = event.detail || {};
        show(detail.message, detail.type, detail);
    });

    function showFlashNotifications() {
        document.querySelectorAll('[data-kpkp-flash-notifications]').forEach(function (node) {
            try {
                var items = JSON.parse(node.textContent || '[]');
                if (Array.isArray(items)) {
                    // Halaman masuk/daftar/onboarding: setiap galat menjadi dialog di tengah (keputusan pemilik
                    // produk 3 Okt 2026). Di halaman lain hanya galat yang membawa tombol aksi.
                    var halamanAuth = document.body.classList.contains('auth-page');
                    items.forEach(function (item) {
                        if (item.dialog) { dialog(item.message, { title: item.title || titles[item.type], jenis: 'info', aksi: item.aksi }); }
                        else if (item.type === 'error' && (halamanAuth || Array.isArray(item.aksi))) { dialog(item.message, item); }
                        else { show(item.message, item.type, item); }
                    });
                }
            } catch (error) {
                global.console.error('Notifikasi flash tidak valid.', error);
            }
            node.remove();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showFlashNotifications, { once: true });
    } else {
        showFlashNotifications();
    }
})(window, document);
