+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbShareButtonsBuilder = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbShareButtonsBuilder.DEFAULTS = {
    };

    LadbShareButtonsBuilder.prototype.bindButton = function(button, withCounters) {
        var that = this;
        var network = button.getAttribute('data-sb-network');
        var url = button.getAttribute('data-sb-url');
        var height = button.getAttribute('data-sb-height');
        var width = button.getAttribute('data-sb-width');
        var top = Math.max(0, (screen.height - height) / 2);
        var left = Math.max(0, (screen.width - width) / 2);
        var specs = 'height=' + height + ',width=' + width + ',top=' + top + ',left=' + left
            + ',status=0,toolbar=0,directories=0,location=0'
            + ',menubar=0,resizable=1,scrollbars=1'
        var windowName = 'sb-window-' + Math.random();

        var sharerUrl = null;

        switch (network){
            case 'facebook':
                sharerUrl = this.buildUrl('https://www.facebook.com/sharer.php',{
                    's': 100,
                    'p[url]': url
                });
                if (withCounters) {
                    $.getJSON('/api/facebook/share.count.json?url=' + url, function(data) {
                        that.appendCounterBadge(button, data.count);
                    });
                }
                break;
            case 'twitter':
                sharerUrl = this.buildUrl('https://twitter.com/intent/tweet',{
                    'text': button.getAttribute('data-sb-text'),
                    'via': button.getAttribute('data-sb-via'),
                    'hashtags': button.getAttribute('data-sb-hashtags')
                });
                break;
            case 'google-plus':
                sharerUrl = this.buildUrl('https://plus.google.com/share',{
                    'url': url
                });
                if (withCounters) {
                    $.getJSON('/api/google-plus/share.count.json?url=' + url, function(data) {
                        that.appendCounterBadge(button, data.count);
                    });
                }
                break;
            case 'pinterest':
                sharerUrl = this.buildUrl('https://www.pinterest.com/pin/create/button/',{
                    'url': url,
                    'media': button.getAttribute('data-sb-media'),
                    'description': button.getAttribute('data-sb-description')
                });
                if (withCounters) {
                    $.getJSON('https://api.pinterest.com/v1/urls/count.json?url=' + url + '&callback=?', function(data) {
                        that.appendCounterBadge(button, data.count);
                    });
                }
                break;
        }
        if (sharerUrl != null) {
            button.onclick = function(){
                window.open(sharerUrl, windowName, specs);
            };
        }
    };

    LadbShareButtonsBuilder.prototype.buildUrl = function(url, parameters) {
        var qs = "";
        for (var key in parameters) {
            var value = parameters[key];
            if (!value) {
                continue
            }
            value = value.toString().split('\"').join('"');
            qs += key + "=" + encodeURIComponent(value) + "&";
        }
        if (qs.length > 0) {
            qs = qs.substring(0, qs.length - 1); //chop off last "&"
            url = url + "?" + qs;
        }
        return url;
    };

    LadbShareButtonsBuilder.prototype.appendCounterBadge = function(button, count) {
        if (count > 0) {
            $(button).append('<span class="badge badge-notification badge-notification-info">' + count + '</span>');
        }
    };

    LadbShareButtonsBuilder.prototype.init = function() {
        var that = this;
        var withCounters = this.$element.data('sb-counters') === true;
        this.$element.find('.ladb-sharebuttons-btn').each(function(index, button) {
            if (!button.hasAttribute('data-sb-isbinded')) {
                button.setAttribute('data-sb-isbinded', 'true');
                that.bindButton(button, withCounters);
            }
        });
    };

    
    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.sharebuttonsbuilder');
            var options = $.extend({}, LadbShareButtonsBuilder.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.sharebuttonsbuilder', (data = new LadbShareButtonsBuilder(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbShareButtonsBuilder;

    $.fn.ladbShareButtonsBuilder             = Plugin;
    $.fn.ladbShareButtonsBuilder.Constructor = LadbShareButtonsBuilder;


    // NO CONFLICT
    // =================

    $.fn.ladbShareButtonsBuilder.noConflict = function () {
        $.fn.ladbShareButtonsBuilder = old;
        return this;
    }

}(jQuery);