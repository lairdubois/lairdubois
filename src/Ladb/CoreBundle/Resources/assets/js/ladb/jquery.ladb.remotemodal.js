+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbRemoteModal = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.defaultHtml = this.$element.html();

        this.hiddable = true;
        this.onHidden = null;

    };

    LadbRemoteModal.DEFAULTS = {
    };

    LadbRemoteModal.prototype.setHiddable = function(hiddable) {
        this.hiddable = hiddable;
    };

    LadbRemoteModal.prototype.setContent = function(data) {
        return $('.modal-content', this.$element).html(data);
    };

    LadbRemoteModal.prototype.show = function() {
        this.$element.modal('show');
    };

    LadbRemoteModal.prototype.hide = function() {
        this.$element.modal('hide');
    };

    LadbRemoteModal.prototype.loadContent = function(options) {
        var that = this;

        options = $.extend({}, {
            url: '',
            data: null,
            onSuccess: null,
            onError: null,
            onHidden: null
        }, options);

        if (options.url) {

            this.onHidden = options.onHidden;

            // Show modal
            this.show();

            // Ajax call
            $.ajax(options.url, {
                cache: false,
                dataType: "html",
                context: document.body,
                data: options.data ? options.data : null,
                success: function (data, textStatus, jqXHR) {
                    var $content = that.setContent(data);
                    if (options.onSuccess) {
                        options.onSuccess($content);
                    }
                },
                error: function () {
                    if (options.onError) {
                        options.onError();
                    }
                }
            });
        }

    };

    LadbRemoteModal.prototype.bind = function() {
        var that = this;

        this.$element
            .on('hidden.bs.modal', function(e) {
                that.$element
                    .removeData('bs.modal');
                that.$element
                    .html(that.defaultHtml);
                if (that.onHidden) {
                    that.onHidden(that.$element);
                    that.onHidden = null;
                }
            })
            .on('hide.bs.modal', function() {
                return that.hiddable;
            });

    };

    LadbRemoteModal.prototype.init = function() {

        // Check element
        if (!this.$element.hasClass('modal') || $('.modal-dialog > .modal-content', this.$element).length == 0) {
            throw 'The given element is not a valid Bootstrap modal';
        }

        this.bind();
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.remotemodal');
            var options = $.extend({}, LadbRemoteModal.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.remotemodal', (data = new LadbRemoteModal(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbRemoteModal;

    $.fn.ladbRemoteModal             = Plugin;
    $.fn.ladbRemoteModal.Constructor = LadbRemoteModal;


    // NO CONFLICT
    // =================

    $.fn.ladbRemoteModal.noConflict = function () {
        $.fn.ladbRemoteModal = old;
        return this;
    }

}(jQuery);