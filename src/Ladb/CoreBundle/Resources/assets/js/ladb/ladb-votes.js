function bindAjaxVoteButton(id, direction, url) {
    console.log('bind');
    $("#ladb_vote_widget_" + id + " .ladb-vote-" + direction + ".ladb-enabled").on("click", function(event) {
        console.log('click');
        event.preventDefault();
        $('[data-tooltip=tooltip]').tooltip('hide');
        $(this).find("i").removeClass("ladb-icon-thumb-" + direction).addClass("ladb-icon-spinner");
        jQuery.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function (data, textStatus, jqXHR) {
                $("#ladb_vote_widget_" + id).replaceWith(data);
                setupTooltips();
                setupPopovers();
                $(document).trigger("updated.ladb");
            },
            error: function () {
            }
        });
    });
}