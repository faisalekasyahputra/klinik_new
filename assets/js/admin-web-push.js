(function () {
    'use strict';

    var button = document.querySelector('[data-web-push-toggle]');
    if (!button) return;

    var label = button.querySelector('[data-web-push-label]');
    var icon = button.querySelector('i');
    var currentSubscription = null;
    var registration = null;

    function notify(message, type) {
        if (window.KPKP && window.KPKP.notify && window.KPKP.notify[type || 'info']) {
            window.KPKP.notify[type || 'info'](message);
        }
    }

    function setState(state, text) {
        button.dataset.state = state;
        button.disabled = state === 'loading' || state === 'disabled';
        label.textContent = text;
        button.setAttribute('aria-label', text);
        button.title = text;
        icon.className = state === 'active' ? 'ph ph-bell-ringing text-xl' : 'ph ph-bell text-xl';
        button.classList.toggle('text-green-600', state === 'active');
        button.classList.toggle('dark:text-brand-primary', state === 'active');
    }

    function base64ToUint8Array(value) {
        var padding = '='.repeat((4 - value.length % 4) % 4);
        var raw = atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, function (char) { return char.charCodeAt(0); });
    }

    function post(url, values) {
        var body = new URLSearchParams();
        body.set(button.dataset.csrfName, button.dataset.csrfHash);
        Object.keys(values).forEach(function (key) { body.set(key, values[key]); });
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
            body: body.toString()
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok || !data.success) throw new Error(data.message || 'Permintaan notifikasi gagal.');
                return data;
            });
        });
    }

    function init() {
        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            var isiOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
            setState('disabled', isiOS ? 'Pasang web ke Layar Utama untuk notifikasi' : 'Web Push tidak didukung browser ini');
            return;
        }

        Promise.all([
            navigator.serviceWorker.register(button.dataset.swUrl),
            fetch(button.dataset.configUrl, { credentials: 'same-origin' }).then(function (response) { return response.json(); })
        ]).then(function (results) {
            registration = results[0];
            var config = results[1];
            if (!config.enabled || !config.publicKey) {
                setState('disabled', 'Web Push belum dikonfigurasi');
                return;
            }
            button.dataset.publicKey = config.publicKey;
            return registration.pushManager.getSubscription();
        }).then(function (subscription) {
            if (subscription === undefined) return;
            currentSubscription = subscription;
            setState(subscription ? 'active' : 'inactive', subscription ? 'Notifikasi HP aktif' : 'Aktifkan notifikasi HP');
        }).catch(function (error) {
            console.error(error);
            setState('disabled', 'Web Push belum tersedia');
        });
    }

    button.addEventListener('click', function () {
        if (!registration || !button.dataset.publicKey) return;
        setState('loading', 'Memproses notifikasi...');
        if (currentSubscription) {
            var endpoint = currentSubscription.endpoint;
            currentSubscription.unsubscribe().then(function () {
                return post(button.dataset.unsubscribeUrl, { endpoint: endpoint });
            }).then(function () {
                currentSubscription = null;
                setState('inactive', 'Aktifkan notifikasi HP');
                notify('Notifikasi pada perangkat ini sudah dinonaktifkan.', 'success');
            }).catch(function (error) {
                setState('active', 'Notifikasi HP aktif');
                notify(error.message, 'error');
            });
            return;
        }

        Notification.requestPermission().then(function (permission) {
            if (permission !== 'granted') throw new Error('Izin notifikasi tidak diberikan. Ubah izin situs di pengaturan browser bila ingin mengaktifkannya.');
            return registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64ToUint8Array(button.dataset.publicKey)
            });
        }).then(function (subscription) {
            currentSubscription = subscription;
            return post(button.dataset.subscribeUrl, { subscription: JSON.stringify(subscription.toJSON()) });
        }).then(function () {
            setState('active', 'Notifikasi HP aktif');
            notify('Perangkat ini akan menerima notifikasi pekerjaan admin baru.', 'success');
        }).catch(function (error) {
            if (currentSubscription) {
                currentSubscription.unsubscribe();
                currentSubscription = null;
            }
            setState('inactive', 'Aktifkan notifikasi HP');
            notify(error.message, 'error');
        });
    });

    setState('loading', 'Memeriksa notifikasi...');
    init();
})();
