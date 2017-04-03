+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbInputDuration = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.inputs = [];
        this.labels = [];

        this.disabled = this.$element.hasClass('disabled') || this.$element.attr('disabled') === 'disabled';

        this.days = 0;
        this.hours = 0;
        this.minutes = 0;
        this.seconds = 0;
    };

    LadbInputDuration.DEFAULTS = {
        showSeconds: false,
        lang: 'fr',
        i18n: {
            fr: {
                day: 'jour',
                hour: 'heure',
                minute: 'minute',
                second: 'seconde',
                days: 'jours',
                hours: 'heures',
                minutes: 'minutes',
                seconds: 'secondes'
            }
        },
        onChanged: null
    };

    LadbInputDuration.prototype.updateField = function(id, value) {

        // Upate label
        var key = value > 1 ? id : id.substring(0, id.length - 1);
        this.labels[id].text(this.options.i18n[this.options.lang][key]);

        // Update input
        this.inputs[id].val(value);

    };

    LadbInputDuration.prototype.updateFileds = function() {

        var total = this.seconds + this.minutes * 60 + this.hours * 60 * 60 + this.days * 24 * 60 * 60;
        this.$element.val(total);
        this.$element.change();

        this.updateField('days', this.days);
        this.updateField('hours', this.hours);
        this.updateField('minutes', this.minutes);
        this.updateField('seconds', this.seconds);

        if (typeof this.options.onChanged === "function") {
            this.options.onChanged(this.$element.val());
        }

    };

    LadbInputDuration.prototype.buildFieldBlock = function(id, hidden, max) {
        var that = this;

        var input = $('<input class="form-control">', {
            type: 'number',
            min: 0,
            value: 0,
            disabled: this.disabled
        }).change(function() {
            that.days = Math.max(0, parseInt(that.inputs['days'].val(), 10)) || 0;
            that.hours = Math.max(0, parseInt(that.inputs['hours'].val(), 10)) || 0;
            that.minutes = Math.max(0, parseInt(that.inputs['minutes'].val(), 10)) || 0;
            that.seconds = Math.max(0, parseInt(that.inputs['seconds'].val(), 10)) || 0;
            that.updateFileds();
        });
        if (max) {
            input.attr('max', max);
        }
        this.inputs[id] = input;

        var label = $('<div>', {
            text: this.options.i18n[this.options.lang][id]
        });
        this.labels[id] = label;

        return $('<div>', {
            class: 'ladb-input-duration-field-block ' + (hidden ? 'hidden' : ''),
            html: [input, label]
        });
    };

    LadbInputDuration.prototype.init = function() {

        // Replace input

        var inputReplacer = $('<div class="bdp-input">', {
            html: [
                this.buildFieldBlock('days', false),
                this.buildFieldBlock('hours', false, 23),
                this.buildFieldBlock('minutes', false, 59),
                this.buildFieldBlock('seconds', !this.options.showSeconds, 59)
            ]
        });

        this.$element.after(inputReplacer).hide().data('ladb-input-duration', '1');

        // Init values

        if (this.$element.val() === '') {
            this.$element.val(0);
        }

        var total = parseInt(this.$element.val(), 10);
        this.seconds = total % 60;
        total = Math.floor(total / 60);
        this.minutes = total % 60;
        total = Math.floor(total / 60);
        this.hours = total % 24;
        this.days = Math.floor(total / 24);

        this.updateFileds();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.inputduration');
            var options = $.extend({}, LadbInputDuration.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.inputduration', (data = new LadbInputDuration(this, options)));
            }
            data.init();
        })
    }

    var old = $.fn.LadbInputDuration;

    $.fn.ladbInputDuration             = Plugin;
    $.fn.ladbInputDuration.Constructor = LadbInputDuration;


    // NO CONFLICT
    // =================

    $.fn.ladbInputDuration.noConflict = function () {
        $.fn.ladbInputDuration = old;
        return this;
    }

}(jQuery);