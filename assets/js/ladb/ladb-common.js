import autosize from 'autosize';

var LADBCommon = (function () {

    var $lazy = null;
    var lazyTimer = null;

    var notifyError = function(error) {
        $.UIkit.notify("<i class='ladb-icon-warning'></i> " + error, {
            status:'danger',
            timeout: 7000
        });
    };

    var notifyFileError = function(fileName, error) {
        notifyError("Erreur sur le fichier <strong>" + fileName + "</strong><br><em>" + error + "</em>");
    };

    var setupTooltips = function() {
        if (!Modernizr.touchevents) {   // No tootltip on if touch screen
            $("[data-tooltip=tooltip]").tooltip({container: 'body'});
        }
    };

    var setupPopovers = function() {
        $("[data-popover=popover]").popover();
    };

    var setupTextareas = function() {
        autosize($("textarea.ladb-autosize"));
    };

    var formatFileSize = function(fileSizeInBytes) {
        var i = -1;
        var byteUnits = [' ko', ' Mo', ' Go', ' To', 'Po', 'Eo', 'Zo', 'Yo'];
        do {
            fileSizeInBytes = fileSizeInBytes / 1024;
            i++;
        } while (fileSizeInBytes > 1024);
        return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
    };

    var lazyLoadReset = function($container) {
        $lazy = $('[data-src]', $container).Lazy({
            effect: 'show',
            effectTime: 0,
            threshold: 0,
            chainable: false
        });
    };

    var lazyLoadUpdate = function() {
        if (!$lazy) {
            lazyLoadReset();
        }
        if (lazyTimer) {
            clearTimeout(lazyTimer);   // clear any previous pending timer
        }
        lazyTimer = setTimeout(function() {
            $lazy.update();
        }, 100);   // set new timer
    };

    return {
        notifyError: notifyError,
        notifyFileError: notifyFileError,
        setupTooltips: setupTooltips,
        setupPopovers: setupPopovers,
        setupTextareas: setupTextareas,
        formatFileSize: formatFileSize,
        lazyLoadReset: lazyLoadReset,
        lazyLoadUpdate: lazyLoadUpdate
    };

})();

export default LADBCommon;