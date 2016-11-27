;(function ( $ ) {

    $.fn.ladbBoxLinkClick = function(event, options) {

        var settings = $.extend({
            location: null
        }, options );

        // If click event is targeted on A tag or A tag parent, keep the default behavior
        if (event.target.tagName == 'A' || $(event.target).parent().prop('tagName') == 'A') {
            return;
        }

        var box = this;

        if (settings.location == null) {
            return;
        }

        // Emulate an "open in a new tab" kind of click by default.
        if (event.ctrlKey || event.shiftKey || event.metaKey || (event.button && event.button == 1)) {
            var $anchor = $('<a>').hide().attr({
                href: settings.location,
                target: '_blank',
                class: 'ladb-box-transient-link'
            });
            $anchor.appendTo('body');
            var $link = $('.' + $anchor.attr('class'));
            $link[0].click();
            $link.remove();
            event.preventDefault();

        } else {
            document.location = settings.location;
        }

    };

} ( jQuery ));