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
    $("textarea.ladb-autosize").autosize();
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
