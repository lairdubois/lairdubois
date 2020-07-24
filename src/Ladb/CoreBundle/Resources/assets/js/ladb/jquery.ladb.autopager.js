+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbAutopager = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbAutopager.DEFAULTS = {
        loadingHtml : '<span>Loading...</span>',  // '{{ include('LadbCoreBundle:Common:_loading.part.html.twig', { 'float':true, 'noHidden':true }) }}',
        masonry: false,
        masonryWide : false
    };

    LadbAutopager.prototype.init = function() {
        var that = this;

        this.$element.jscroll({
            loadingHtml: that.options.loadingHtml,
            padding: 20,
            nextSelector: 'link[rel=next]:last',
            callback: function () {
                if (that.options.masonry) {
                    var items = [];
                    var $jScrollInner = $('div.jscroll-inner', that.$element);
                    var $jScrollAdded = $('div.jscroll-added', $jScrollInner);
                    var $masonryInner = $('div.masonry-inner', $jScrollInner);
                    $('.ladb-masonry-item', $jScrollAdded).each(function(index, $item) {
                        items.push($item);
                    });
                    $jScrollAdded.remove();
                    $masonryInner.append(items);
                    $masonryInner.masonry('appended', items);
                }
                lazyLoadReset($masonryInner);
                setupTooltips();
            }
        });
        if (this.options.masonry) {
            var $jScrollInner = $('div.jscroll-inner', this.$element);
            $jScrollInner.append('<div class="masonry-inner" />');
            var $masonryInner = $('.masonry-inner', $jScrollInner);
            $('.ladb-masonry-item', $jScrollInner).each(function(index, $item) {
                $masonryInner.append($item);
            });
            $masonryInner.masonry({
                itemSelector: ".ladb-masonry-item",
                columnWidth: ".ladb-masonry-column" + (this.options.masonryWide ? "-wide" : ""),
                transitionDuration: 0,
                horizontalOrder: true,
                stamp: ".ladb-masonry-stamp"
            });
        }
        lazyLoadReset($masonryInner);

    };
    

    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.autopager');
            var options = $.extend({}, LadbAutopager.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.autopager', (data = new LadbAutopager(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbAutopager;

    $.fn.ladbAutopager             = Plugin;
    $.fn.ladbAutopager.Constructor = LadbAutopager;


    // NO CONFLICT
    // =================

    $.fn.ladbAutopager.noConflict = function () {
        $.fn.ladbAutopager = old;
        return this;
    }

}(jQuery);