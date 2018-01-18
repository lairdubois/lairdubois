;(function ( $ ) {

    $.fn.ladbLogout = function(event, options) {

        var settings = $.extend({
        }, options );

        var btn = this;
        var url = btn.attr("href");

        // Ignore default behavior
        if (event != null) {
            event.preventDefault();
        }

        console.log('logout ?');

    };

} ( jQuery ));