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
    var icons = { success: '\u2713', error: '!', warning: '!', info: 'i' };
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
        icon.textContent = icons[type];

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
        region().appendChild(toast);
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

    var api = {
        show: show,
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
                    items.forEach(function (item) { show(item.message, item.type, item); });
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
