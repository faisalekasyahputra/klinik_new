'use strict';

self.addEventListener('push', function (event) {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (error) { data = {}; }
    var title = data.title || 'Klinik PKP';
    var options = {
        body: data.body || 'Ada pekerjaan admin baru.',
        icon: './assets/img/logo-jateng.png',
        badge: './assets/img/logo-jateng.png',
        tag: data.tag || 'klinik-pkp-admin',
        renotify: true,
        data: { url: data.url || './Admin' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var fallback = new URL('./Admin', self.registration.scope).href;
    var target;
    try {
        target = new URL(event.notification.data && event.notification.data.url ? event.notification.data.url : fallback, self.registration.scope);
        if (target.origin !== self.location.origin) target = new URL(fallback);
    } catch (error) {
        target = new URL(fallback);
    }

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windows) {
        for (var i = 0; i < windows.length; i++) {
            if (new URL(windows[i].url).origin === target.origin) {
                return windows[i].focus().then(function (client) { return client.navigate(target.href); });
            }
        }
        return clients.openWindow(target.href);
    }));
});
