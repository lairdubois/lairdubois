self.processMessage = payload => {

    try {
        const jsonData = JSON.parse(payload);
        const promises = [];
        for (key in jsonData) {
            if ('notification' === key) {
                promises.push(self.registration.showNotification(jsonData.notification.title, jsonData.notification));
            }
        }
        return Promise.race(promises);
    } catch (e) {
        return self.registration.showNotification('Notification', {
            body: payload
        });
    }

};

// Register event listener for the 'push' event.
self.addEventListener('push', event => {
    const pushMessageData = event.data;
    const payload = pushMessageData ? pushMessageData.text() : undefined;
    event.waitUntil(self.processMessage(payload));
});

self.addEventListener('notificationclick', event => {

    event.notification.close();
    const url = event.notification.data.link;

    if (url.length > 0) {
        event.waitUntil(
            clients.matchAll({
                type: 'window'
            })
                .then(windowClients => {
                    for (const client of windowClients) {
                        if (client.url === url && 'focus' in client) {
                            return client.focus();
                        }
                    }

                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
        );
    }
});

