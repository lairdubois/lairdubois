+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbSettingsPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.webpush = null;

        this.$webpushSubscribeBtn = $('#ladb_webpush_subscribe_btn');
        this.$webpushRevokeBtn = $('#ladb_webpush_revoke_btn');
    };

    LadbSettingsPage.DEFAULTS = {
        serverKey: '',
        subscriptionUrl: ''
    };

    LadbSettingsPage.prototype.refreshWebpushButtonsState = function() {
        var that = this;

        this.webpush.getNotificationPermissionState().then(function(result) {
            console.log(result);
            switch (result) {

                case 'granted':
                    that.$webpushSubscribeBtn.hide();
                    that.$webpushRevokeBtn.show();
                    break;

                case 'prompt':
                    break;

                case 'denied':
                    that.$webpushSubscribeBtn.hide();
                    that.$webpushRevokeBtn.hide();
                    break;

            }
        });

    };

    LadbSettingsPage.prototype.bind = function() {
        var that = this;

        // Bind buttons
        this.$webpushSubscribeBtn.on('click', function(e) {
            e.preventDefault();
            that.$webpushSubscribeBtn.button('loading');
            that.webpush.subscribe().then(function() {
                that.$webpushSubscribeBtn.button('reset');
                that.refreshWebpushButtonsState();
            });
        });
        this.$webpushRevokeBtn.on('click', function(e) {
            e.preventDefault();
            that.$webpushRevokeBtn.button('loading');
            that.webpush.revoke().then(function() {
                that.$webpushRevokeBtn.button('reset');
                that.refreshWebpushButtonsState();
            });
        });

    };

    LadbSettingsPage.prototype.init = function() {
        var that = this;

        this.webpush = new BenToolsWebPushClient({
            serverKey: this.options.serverKey,
            url: this.options.subscriptionUrl,
            promptIfNotSubscribed: false // Defaults true - setting this to false will disable automatic prompt
        });

        this.refreshWebpushButtonsState();

        this.bind();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.settingspage');
            var options = $.extend({}, LadbSettingsPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.settingspage', (data = new LadbSettingsPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbSettingsPage;

    $.fn.ladbSettingsPage             = Plugin;
    $.fn.ladbSettingsPage.Constructor = LadbSettingsPage;


    // NO CONFLICT
    // =================

    $.fn.ladbSettingsPage.noConflict = function () {
        $.fn.ladbSettingsPage = old;
        return this;
    }

}(jQuery);