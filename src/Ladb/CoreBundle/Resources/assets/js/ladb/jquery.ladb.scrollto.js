;(function ( $ ) {

    $.fn.ladbScrollTo = function(event, options) {

        var settings = $.extend({
            smoothScroll: true,
            offset: 100
        }, options );

        var target = this;

        // Ignore default behavior
        if (event != null) {
            event.preventDefault();
        }

        var targetTop = target.offset().top;
        var scrollTop = $(window).scrollTop();
        var scrollMax = $(document).height() - $(window).height();
        if (scrollTop < scrollMax || targetTop - settings.offset <= scrollTop) {
            $(window).scrollTo(target, {
                duration: settings.smoothScroll ? 500 : 0,
                offset: { top: (targetTop - settings.offset) < scrollMax ? -settings.offset : 0 }
            });
        }

    }

} ( jQuery ))