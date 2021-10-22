+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbYoutookWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbYoutookWidget.DEFAULTS = {
    };

    LadbYoutookWidget.prototype.markLoading = function() {
        this.$element.find('.ladb-input-box-left > i').addClass('ladb-icon-spinner');
    };

    LadbYoutookWidget.prototype.unmarkLoading = function() {
        this.$element.find('.ladb-input-box-left > i').removeClass('ladb-icon-spinner');
    };

    LadbYoutookWidget.prototype.bind = function() {
        var that = this;

        var $form = this.$element.find('form');
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            beforeSubmit: function() {
                that.markLoading();
                that.$element.find('input').prop('disabled', true);
            },
            success: function(data, textStatus, jqXHR) {
                var $data = $(data);
                if ($data.prop("tagName") == 'A') {
                    window.location.href = $data.attr("href");
                } else {
                    $form.replaceWith(data);
                    that.bind();
                }
            },
            error: function() {
                that.unmarkLoading();
                that.$element.find('input').prop('disabled', false);
            }
        });

    };

    LadbYoutookWidget.prototype.init = function() {
        var that = this;

        this.bind();

        // Focus input
        this.$element.find('input').focus();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.youtookwidget');
            var options = $.extend({}, LadbYoutookWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.youtookwidget', (data = new LadbYoutookWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbYoutookWidget;

    $.fn.ladbYoutookWidget             = Plugin;
    $.fn.ladbYoutookWidget.Constructor = LadbYoutookWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbYoutookWidget.noConflict = function () {
        $.fn.ladbYoutookWidget = old;
        return this;
    }

}(jQuery);