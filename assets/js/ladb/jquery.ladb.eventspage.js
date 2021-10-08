+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbEventsPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$calendar = $('#ladb_calendar', this.$element);
        this.$searchWidget = $('.ladb-search-widget', this.$element);

        this.clndr = null;
        this.currentQuery = null;
        this.currentYear = null;
        this.currentMonth = null;
    };

    LadbEventsPage.DEFAULTS = {
        query: null,
        jsonListPath: null,
        listPath: null
    };

    LadbEventsPage.prototype.setCurrentQuery = function(query) {
        this.currentQuery = query;

        // Try to extract searched day
        if (query && query.length > 0) {

            var re = /@day:([0-9]{4})-([0-9]{2})-[0-9]{2}/;
            var m;

            if ((m = re.exec(query)) !== null) {
                this.setCurrentMonth(parseInt(m[1]), parseInt(m[2]));
            }

        }

    };

    LadbEventsPage.prototype.setCurrentMonth = function(year, month) {
        this.currentYear = year;
        this.currentMonth = month;
        if (this.clndr) {
            this.clndr.setYear(this.currentYear);
            this.clndr.setMonth(this.currentMonth - 1);
        }
    };

    LadbEventsPage.prototype.loadEvents = function() {
        var that = this;

        var month = this.clndr.month;
        $.ajax(this.options.jsonListPath + '?q=@active @month:' + month.format('YYYY-MM') + (this.currentQuery ? ' ' + this.currentQuery : ''), {
            cache: false,
            dataType: "json",
            context: document.body,
            success: function (data, textStatus, jqXHR) {
                if (data.events) {
                    that.clndr.setEvents(data.events);
                }
                LADBCommon.setupTooltips();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus);
            }
        });
    };

    LadbEventsPage.prototype.bind = function() {
        var that = this;

        // Bind clndr
        this.clndr = this.$calendar.clndr({
            template: $('#template-calendar').html(),
            multiDayEvents: {
                startDate: 'startDate',
                endDate: 'endDate'
            },
            startWithMonth: this.currentYear && this.currentMonth ? this.currentYear + '-' + this.currentMonth : moment(),
            showAdjacentMonths: false,
            clickEvents: {
                click: function(target) {
                    if (target && target.events) {
                        if (target.events.length === 1) {
                            window.location.href = target.events[0].showPath;
                        } else if (target.events.length > 1) {
                            window.location.href = that.options.listPath + '?q=@day:' + target.date.format('YYYY-MM-DD') + (this.currentQuery ? ' ' + this.currentQuery : '');
                        }
                    }
                },
                onMonthChange: function(month) {
                    that.loadEvents();
                }
            },
        });
        this.loadEvents();

        // Bind searchWidget
        this.$searchWidget.on('search.ladb.success', function(event, query) {
            that.setCurrentQuery(query);
            that.loadEvents();
        });

    };

    LadbEventsPage.prototype.init = function() {
        var that = this;

        // Set starts query
        this.setCurrentQuery(this.options.query);

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