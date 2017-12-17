+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbPlanPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$viewer = $('#ladb_3dwarehouse_viewer', this.$element);
        this.$iframe = $('iframe', this.$viewer);
        this.$tipWebGL = $('.ladb-tip-webgl', this.$viewer);
        this.$tipNoWebGL = $('.ladb-tip-no-webgl', this.$viewer);

        this.loadCount = 0;

    };

    LadbPlanPage.DEFAULTS = {
        embedIdentifier: ''
    };

    LadbPlanPage.prototype.bind = function() {
        var that = this;

        // Bind viewer iframe
        that.$iframe.on('load', function(e) {
            that.loadCount++;
            if (that.loadCount > 1) {
                that.$tipWebGL.remove();
            }
        });

    };

    LadbPlanPage.prototype.init = function() {
        var that = this;

        // Check WebGL support
        if (Modernizr.webgl) {
            this.bind();
            this.$tipWebGL.show();
            this.$tipNoWebGL.hide();
        } else {
            this.$tipWebGL.hide();
            this.$tipNoWebGL.show();
        }

        this.$iframe.attr('src', 'https://3dwarehouse.sketchup.com/embed.html?mid=' + that.options.embedIdentifier + '&width=' + Math.ceil(that.$viewer.width()) + '&height=' + Math.ceil(that.$viewer.height()));

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.planpage');
            var options = $.extend({}, LadbPlanPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.planpage', (data = new LadbPlanPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbPlanPage;

    $.fn.ladbPlanPage             = Plugin;
    $.fn.ladbPlanPage.Constructor = LadbPlanPage;


    // NO CONFLICT
    // =================

    $.fn.ladbPlanPage.noConflict = function () {
        $.fn.ladbPlanPage = old;
        return this;
    }

}(jQuery);