function notifyError(error) {
    $.UIkit.notify("<i class='ladb-icon-warning'></i> " + error, {
        status:'danger',
        timeout: 7000
    });
}
function notifyFileError(fileName, error) {
    notifyError("Erreur sur le fichier <strong>" + fileName + "</strong><br><em>" + error + "</em>");
}
function setupTooltips() {
    if (!Modernizr.touchevents) {   // No tootltip on if touch screen
        $("[data-tooltip=tooltip]").tooltip({container: 'body'});
    }
}
function setupPopovers() {
    $("[data-popover=popover]").popover();
}
function setupTextareas() {
    autosize($("textarea.ladb-autosize"));
}
function formatFileSize(fileSizeInBytes) {
    var i = -1;
    var byteUnits = [' ko', ' Mo', ' Go', ' To', 'Po', 'Eo', 'Zo', 'Yo'];
    do {
        fileSizeInBytes = fileSizeInBytes / 1024;
        i++;
    } while (fileSizeInBytes > 1024);
    return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
}
var $lazy = null;
function lazyLoadReset() {
    $lazy = $('[data-src]').Lazy({
        effect: 'show',
        effectTime: 0,
        threshold: 0,
        chainable: false
    });
}
var lazyTimer = null;
function lazyLoadUpdate() {
    if (!$lazy) {
        lazyLoadReset();
    }
    if (lazyTimer) {
        clearTimeout(lazyTimer);   // clear any previous pending timer
    }
    lazyTimer = setTimeout(function() {
        $lazy.update();
    }, 100);   // set new timer
}
