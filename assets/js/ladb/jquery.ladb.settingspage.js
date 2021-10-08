+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbSettingsPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.webpush = null;

        this.$activeSpan = $('#ladb_webpush_active_span');
        this.$inactiveSpan = $('#ladb_webpush_inactive_span');
        this.$deniedSpan = $('#ladb_webpush_denied_span');
        this.$disabledSpan = $('#ladb_webpush_disabled_span');
        this.$helpSpan = $('#ladb_webpush_help_span');

        this.$subscribeBtn = $('#ladb_webpush_subscribe_btn');
        this.$revokeBtn = $('#ladb_webpush_revoke_btn');
    };

    LadbSettingsPage.DEFAULTS = {
        accountTypeFormFieldId: null,
        accountTypeBrandValue: null,
        swPath: '',
        serverKey: '',
        subscriptionUrl: ''
    };

    LadbSettingsPage.prototype.refreshWebpushState = function() {
        var that = this;

        if (this.webpush) {
            this.webpush.getNotificationPermissionState()
                .then(function(state) {
                    switch (state) {

                        case 'prompt':
                        case 'granted':
                            that.webpush.hasSubscription().then(function(result) {
                                that.$subscribeBtn.button('reset');
                                that.$revokeBtn.button('reset');
                                that.$deniedSpan.hide();
                                that.$disabledSpan.hide();
                                if (result) {
                                    that.$subscribeBtn.hide();
                                    that.$revokeBtn.show();
                                    that.$activeSpan.show();
                                    that.$inactiveSpan.hide();
                                } else {
                                    that.$subscribeBtn.show();
                                    that.$revokeBtn.hide();
                                    that.$activeSpan.hide();
                                    that.$inactiveSpan.show();
                                }
                            });
                            break;

                        case 'denied':
                            that.$subscribeBtn.hide();
                            that.$revokeBtn.hide();
                            that.$activeSpan.hide();
                            that.$inactiveSpan.hide();
                            that.$deniedSpan.show();
                            that.$disabledSpan.hide();
                            break;

                    }
                });
        } else {
            that.$subscribeBtn.hide();
            that.$revokeBtn.hide();
            that.$activeSpan.hide();
            that.$inactiveSpan.hide();
            that.$deniedSpan.hide();
            that.$disabledSpan.show();
            that.$helpSpan.hide();
        }

    };

    LadbSettingsPage.prototype.bind = function() {
        var that = this;

        // Bind buttons
        this.$subscribeBtn.on('click', function(e) {
            e.preventDefault();
            that.$subscribeBtn.button('loading');
            that.webpush.subscribe();
        });
        this.$revokeBtn.on('click', function(e) {
            e.preventDefault();
            that.$revokeBtn.button('loading');
            that.webpush.revoke();
        });

        // Bind form fields
        $('#' + this.options.accountTypeFormFieldId).on('change', function(e) {
            if ($('#' + that.options.accountTypeFormFieldId + ' input:checked').val() == that.options.accountTypeBrandValue) {
                $('#ladb_account_type_brand_warning_alert').show();
            } else {
                $('#ladb_account_type_brand_warning_alert').hide();
            }
        })

    };

    LadbSettingsPage.prototype.init = function() {
        var that = this;

        this.webpush = new LADBWebPush.client({
            swPath: this.options.swPath,
            serverKey: this.options.serverKey,
            url: this.options.subscriptionUrl,
            promptIfNotSubscribed: false,
            onRegistered: function() {
                that.refreshWebpushState();
            },
            onUnavailable: function() {
                that.refreshWebpushState();
            },
            onSubscribe: function(subscription) {
                that.refreshWebpushState();
            },
            onUnsubscribe: function(subscription) {
                that.refreshWebpushState();
            }
        });

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