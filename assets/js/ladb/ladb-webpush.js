var LADBWebPush = (function () {

    var LadbWebPushClient = function LadbWebPushClient(options) {

        return {

            options: {},
            worker: null,
            registration: null,
            pushSubscription: null,

            init: function init(options) {
                this.options = options || {};

                if (!options.url) {
                    throw Error('Url has not been defined.');
                }

                return this.initServiceWorker();
            },

            initServiceWorker: function() {
                var that = this;

                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    if ('function' === typeof that.options.onUnavailable) {
                        that.options.onUnavailable();
                    }
                    return;
                }

                navigator.serviceWorker.register(this.options.swPath)
                    .then(function(serviceWorkerRegistration) {
                        that.worker = serviceWorkerRegistration.active || serviceWorkerRegistration.installing;
                        return serviceWorkerRegistration;
                    })
                    .then(function(serviceWorkerRegistration) {
                        that.serviceWorkerRegistration = serviceWorkerRegistration;
                        if ('function' === typeof that.options.onRegistered) {
                            that.options.onRegistered();
                        }
                    });
                return this;
            },

            getNotificationPermissionState: function() {
                if (navigator.permissions) {
                    return navigator.permissions.query({ name: 'notifications' }).then(function(result) {
                        return result.state;
                    });
                }
                return new Promise(function(resolve) {
                    resolve(Notification.permission);
                });
            },

            hasSubscription: function() {
                return this.serviceWorkerRegistration.pushManager.getSubscription().then(function(pushSubscription) {
                    return pushSubscription !== null;
                });
            },

            subscribe: function() {
                var that = this;
                return this.serviceWorkerRegistration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: that.encodeServerKey(that.options.serverKey)
                }).then(function(pushSubscription) {
                    that.pushSubscription = pushSubscription;
                    return that.savePushSubscription(pushSubscription).then(function(pushSubscription) {
                        if ('function' === typeof that.options.onSubscribe) {
                            return that.options.onSubscribe(pushSubscription);
                        }
                    });
                }).catch(function(error) {
                    console.log('ERROR', error);
                });
            },

            revoke: function() {
                var that = this;
                return this.serviceWorkerRegistration.pushManager.getSubscription().then(function(pushSubscription) {
                    pushSubscription.unsubscribe().then(function(unsubscribed) {
                        that.deletePushSubscription(pushSubscription);
                        if ('function' === typeof that.options.onUnsubscribe) {
                            return that.options.onUnsubscribe(pushSubscription);
                        }
                    });
                });
            },

            /////

            savePushSubscription: function(pushSubscription) {
                return fetch(this.options.url, {
                    method: 'POST',
                    mode: 'cors',
                    credentials: 'include',
                    cache: 'default',
                    headers: new Headers({
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }),
                    body: JSON.stringify(pushSubscription)
                }).then(function () {
                    return pushSubscription;
                });
            },

            deletePushSubscription: function(pushSubscription) {
                return fetch(this.options.url, {
                    method: 'DELETE',
                    mode: 'cors',
                    credentials: 'include',
                    cache: 'default',
                    headers: new Headers({
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }),
                    body: JSON.stringify(pushSubscription)
                });
            },

            encodeServerKey: function(serverKey) {
                var padding = '='.repeat((4 - serverKey.length % 4) % 4);
                var base64 = (serverKey + padding).replace(/\-/g, '+').replace(/_/g, '/');

                var rawData = window.atob(base64);
                var outputArray = new Uint8Array(rawData.length);

                for (var i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

        }.init(options);
    };

    return {
        LadbWebPushClient: LadbWebPushClient
    }

});

export default LADBWebPush;