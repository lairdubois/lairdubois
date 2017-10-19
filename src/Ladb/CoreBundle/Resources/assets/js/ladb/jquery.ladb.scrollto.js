;(function ( $ ) {

    $.fn.ladbScrollTo = function(event, options) {

        var settings = $.extend({
            smoothScroll: true,
            offset: 100,
            onAfter: null
        }, options );

        var target = this;

        // Ignore default behavior
        if (event != null) {
            event.preventDefault();
        }

        var inView = function($target){
            var $win = $(window);
            var scrollPosition = $win.scrollTop();
            var visibleArea = $win.scrollTop() + $win.height();
            var objEndPos = (target.offset().top + target.outerHeight());
            return (visibleArea >= objEndPos && scrollPosition <= objEndPos) ? true : false
        };

        var targetTop = target.offset().top;
        var scrollTop = $(window).scrollTop();
        var scrollMax = $(document).height() - $(window).height();
        if ((scrollTop < scrollMax || targetTop - settings.offset <= scrollTop) && !inView($(target))) {
            $(window).scrollTo(target, {
                duration: settings.smoothScroll ? 500 : 0,
                offset: { top: (targetTop - settings.offset) < scrollMax ? -settings.offset : 0 },
                onAfter: function() {
                    if (typeof(settings.onAfter) == 'function') {
                        settings.onAfter();
                    }
                }
            });
        }

    }

} ( jQuery ))