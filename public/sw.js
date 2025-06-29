// public/sw.js

const CACHE_NAME = 'visubudget-cache-v11'; // Updated version to trigger a new install

const APP_SHELL_FILES = [
    // We only pre-cache the absolute essentials. The dynamic pages are not pre-cached.
    '/offline.html',
    '/assets/images/logo.png',
    '/assets/css/style.css',
    '/assets/js/main.js'
];

// On install, cache the core app shell.
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Caching app shell');
            return cache.addAll(APP_SHELL_FILES);
        })
    );
    self.skipWaiting();
});

// On activation, clean up old caches.
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate');
    event.waitUntil(
        caches.keys().then((keyList) => {
            return Promise.all(keyList.map((key) => {
                if (key !== CACHE_NAME) {
                    console.log('[ServiceWorker] Removing old cache', key);
                    return caches.delete(key);
                }
            }));
        })
    );
    return self.clients.claim();
});


// On fetch, use our new, smarter caching strategy.
self.addEventListener('fetch', (event) => {
    // We only want to handle GET requests.
    if (event.request.method !== 'GET') {
        return;
    }

    // **THE NEW, STRICTER LOGIC IS HERE**
    // For HTML page requests (like the dashboard), always try the network first.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            // Always try to fetch from the network.
            fetch(event.request)
                .catch(() => {
                    // ONLY if the network fails completely, serve the generic offline page.
                    // We no longer try to serve a stale version of the dashboard.
                    return caches.match('/offline.html');
                })
        );
        return;
    }

    // For all other assets (CSS, JS, images), we can use a "Cache-first" strategy
    // because they don't change very often.
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // If we find it in the cache, return it immediately.
                if (response) {
                    return response;
                }
                // Otherwise, go to the network, cache the result for next time, and return it.
                return fetch(event.request).then((networkResponse) => {
                    // Clone the response because it can only be consumed once.
                    const responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseToCache);
                    });
                    return networkResponse;
                });
            })
    );
});

// --- The Push Event Listener ---
self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');

    let notificationData = {
        title: 'VisuBudget Reminder',
        body: 'You have a new notification.',
        icon: '/assets/images/logo.svg'
    };
    
    // We wrap the data parsing in a try...catch block.
    if (event.data) {
        try {
            // First, try to parse the data as JSON.
            const data = event.data.json();
            notificationData.title = data.title || notificationData.title;
            notificationData.body = data.body || notificationData.body;
            notificationData.icon = data.icon || notificationData.icon;
        } catch (e) {
            // If JSON parsing fails, fall back to treating it as plain text.
            console.log('[Service Worker] Push data is not JSON, treating as text.');
            notificationData.body = event.data.text();
        }
    }
    
    const options = {
        body: notificationData.body,
        icon: notificationData.icon,
        badge: '/assets/images/logo.svg'
    };

    // This is the command that actually shows the notification.
    event.waitUntil(self.registration.showNotification(notificationData.title, options));
});
