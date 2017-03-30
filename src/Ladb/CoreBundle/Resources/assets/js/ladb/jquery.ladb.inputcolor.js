+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbInputColor = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$input = null;
        this.$previewAddOn = null;
        this.$preview = null;
    };

    LadbInputColor.DEFAULTS = {
        colors: ['#ee0701', '#f58220', '#fbca04', '#0e8a16', '#006b75', '#1d76db', '#5319e7'],
        colorsPerLine: 8,
        includeMargins: false
    };

    LadbInputColor.prototype.updatePreview = function() {
        var color = this.$input.val();
        if (color) {
            this.$preview.css('background', color);
        }
    };

    LadbInputColor.prototype.init = function() {
        var that = this;

        var $body = $('body');

        // Decorate input

        var $inputGroup = $('<div class="ladb-input-color input-group"></div>');

        this.$previewAddOn = $('<div class="ladb-input-color-preview-addon input-group-addon"></div>');
        this.$preview = $('<div class="ladb-input-color-preview"></div>');
        this.$input = this.$element.clone();

        this.$previewAddOn.append(this.$preview);
        $inputGroup.append(this.$previewAddOn);
        $inputGroup.append(this.$input);

        this.$element.replaceWith($inputGroup);

        // Create the color box

        var colorsMarkup = '';
        var prefix = this.$input.attr('id').replace(/-/g, '') + '_';

        for (var i = 0; i < this.options.colors.length; i++) {

            var color = this.options.colors[i];

            var breakLine = '';
            if (i % this.options.colorsPerLine == 0) {
                breakLine = 'clear: both; ';
            }

            if (i > 0 && breakLine && $.browser && $.browser.msie && $.browser.version <= 7) {
                breakLine = '';
                colorsMarkup += '<li style="float: none; clear: both; overflow: hidden; background-color: #fff; display: block; height: 1px; line-height: 1px; font-size: 1px; margin-bottom: -2px;"></li>';
            }

            colorsMarkup += '<li data-ladb-color-index="' + i + '" class="ladb-color-box" style="' + breakLine + 'background-color: ' + color + '" title="' + color + '"></li>';
        }

        var $box = $('<div class="ladb-input-color-picker" style="position: absolute; left: 0; top: 0;"><ul>' + colorsMarkup + '</ul><div style="clear: both;"></div></div>');
        $body.append($box);
        $box.hide();

        $box.find('li.ladb-color-box').click(function() {
            if (that.$input.is('input')) {
                that.$input.val(that.options.colors[$(this).data('ladb-color-index')]);
                that.$input.blur();
            }
            that.updatePreview();
            $box.hide();
        });

        $body.on('click', function() {
            $box.hide();
        });

        $box.click(function (event) {
            event.stopPropagation();
        });

        var positionAndShowBox = function(box) {
            var pos = that.$previewAddOn.offset();
            box.css({ left: pos.left, top: (pos.top + that.$input.outerHeight(that.options.includeMargins)) });
            box.show();
        };

        this.$previewAddOn.on('click', function(event) {
            event.stopPropagation();
            positionAndShowBox($box);
        });

        this.$input.on('click', function(event) {
            event.stopPropagation();
        });
        this.$input.on('focus', function() {
            positionAndShowBox($box);
        });
        this.$input.on('change', function() {
            that.updatePreview();
        });
        this.$input.on('keyup', function() {
            that.updatePreview();
        });

        this.updatePreview();
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.inputColor');
            var options = $.extend({}, LadbInputColor.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.inputColor', (data = new LadbInputColor(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.LadbInputColor;

    $.fn.ladbInputColor             = Plugin;
    $.fn.ladbInputColor.Constructor = LadbInputColor;


    // NO CONFLICT
    // =================

    $.fn.ladbInputColor.noConflict = function () {
        $.fn.ladbInputColor = old;
        return this;
    }

}(jQuery);