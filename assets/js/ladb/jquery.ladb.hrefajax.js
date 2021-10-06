;(function ( $ ) {

    $.fn.ladbHrefAjax = function(event, options) {

        var settings = $.extend({
            loading: true,
            targetSelector: null,
            replaceTargetInnerOnly: false,
            onSuccess: null
        }, options );

        var btn = this;
        var url = btn.attr("href");

        // Hide tooltips
        $("[data-tooltip=tooltip]").tooltip('hide');

        // Ignore default behavior
        if (event != null) {
            event.preventDefault();
        }

        // Change button state
        if (settings.loading) {
            btn.button('loading');
        }

        // Ajax Request
        $.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                if (settings.targetSelector != null) {
                    $(settings.targetSelector).each(function(index) {
                        if (settings.replaceTargetInnerOnly) {
                            $(this).html(data);
                        } else {
                            $(this).replaceWith(data);
                        }
                    });
                }
                if (typeof settings.onSuccess == 'function') {
                    settings.onSuccess();
                }
            },
            error: function () {
                if (settings.loading) {
                    btn.button('reset');
                }
            }
        });

    };

} ( jQuery ));