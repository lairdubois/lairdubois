+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbEventsPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$calendar = $('#calendar', this.$element);

        this.clndr = null;
    };

    LadbEventsPage.DEFAULTS = {
        listPath: null
    };

    LadbEventsPage.prototype.loadMonth = function(month) {
        var that = this;

        $.ajax(this.options.listPath, {
            cache: false,
            dataType: "json",
            context: document.body,
            success: function (data, textStatus, jqXHR) {
                if (data.events) {
                    that.clndr.setEvents(data.events);
                }
                setupTooltips();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    };

    LadbEventsPage.prototype.bind = function() {
        var that = this;

        this.clndr = this.$calendar.clndr({
            template: $('#template-calendar').html(),
            multiDayEvents: {
                startDate: 'startDate',
                endDate: 'endDate'
            },
            clickEvents: {
                click: function(target) {
                    console.log(target);
                    console.log(that.clndr);
                },
                onMonthChange: function(month) {
                    that.loadMonth(month);
                }
            },
        });
        this.loadMonth(this.clndr.month);

    };

    LadbEventsPage.prototype.init = function() {
        var that = this;

        this.bind();
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.eventspage');
            var options = $.extend({}, LadbEventsPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.eventspage', (data = new LadbEventsPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbEventsPage;

    $.fn.ladbEventsPage             = Plugin;
    $.fn.ladbEventsPage.Constructor = LadbEventsPage;


    // NO CONFLICT
    // =================

    $.fn.ladbEventsPage.noConflict = function () {
        $.fn.ladbEventsPage = old;
        return this;
    }

}(jQuery);