+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbTopbarTranslucent = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.isActive = false;
        this.isOn = false;
    };

    LadbTopbarTranslucent.DEFAULTS = {
    };

    LadbTopbarTranslucent.prototype.activate = function() {
        this.isActive = true;
        this.tryToTurnOn();
    };

    LadbTopbarTranslucent.prototype.desactivate = function() {
        this.isActive = false;
        this.turnOff();
    };

    LadbTopbarTranslucent.prototype.tryToTurnOn = function() {
        if (this.isActive) {
            if ($(window).scrollTop() > 1) {
                this.turnOff();
            } else if (!this.isOn) {
                this.$element.addClass('ladb-topbar-translucent');
                this.isOn = true;
            }
        }
    };

    LadbTopbarTranslucent.prototype.turnOff = function() {
        if (!this.isOn) {
            return;
        }
        this.$element.removeClass('ladb-topbar-translucent');
        this.isOn = false;
    };

    LadbTopbarTranslucent.prototype.init = function() {
        var that = this;

        $(window).scroll(function() {
            that.tryToTurnOn();
        });

        this.activate();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.topbartranslucent');
            var options = $.extend({}, LadbTopbarTranslucent.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.topbartranslucent', (data = new LadbTopbarTranslucent(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbTopbarTranslucent;

    $.fn.ladbTopbarTranslucent             = Plugin;
    $.fn.ladbTopbarTranslucent.Constructor = LadbTopbarTranslucent;


    // NO CONFLICT
    // =================

    $.fn.ladbTopbarTranslucent.noConflict = function () {
        $.fn.ladbTopbarTranslucent = old;
        return this;
    }

}(jQuery);