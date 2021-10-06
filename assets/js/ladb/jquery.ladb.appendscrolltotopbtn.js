+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbAppendScrollToTopBtn = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbAppendScrollToTopBtn.DEFAULTS = {
        btn: '<a id="ladb_scrollto_btn_top" class="ladb-scrollto-btn ladb-scrollto-btn-top"><i class="ladb-icon-arrow-up"></i></a>',
        smoothScroll: true
    };

    LadbAppendScrollToTopBtn.prototype.init = function() {
        var that = this;

        // Create and add button
        var $btn = $(this.options.btn)
            .click(function() {
                $('body,html').animate({
                    scrollTop : 0
                }, that.options.smoothScroll ? 500 : 0);
            });
        this.$element.append($btn);

        // Scroll event
        $(window).scroll(function () {
            if ($(this).scrollTop() > 100) {
                $btn.fadeIn();
            } else {
                $btn.fadeOut();
            }
        });

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.appendscrolltotopbtn');
            var options = $.extend({}, LadbAppendScrollToTopBtn.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.appendscrolltotopbtn', (data = new LadbAppendScrollToTopBtn(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbAppendScrollToTopBtn;

    $.fn.ladbAppendScrollToTopBtn             = Plugin;
    $.fn.ladbAppendScrollToTopBtn.Constructor = LadbAppendScrollToTopBtn;


    // NO CONFLICT
    // =================

    $.fn.ladbAppendScrollToTopBtn.noConflict = function () {
        $.fn.ladbAppendScrollToTopBtn = old;
        return this;
    }

}(jQuery);