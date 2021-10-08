var LADBReports = (function () {

    var ladbReportPrepareModal = function(entityType, entityId) {
        $("#report_modal_form").each(function(){
            this.reset();
        });
        $("#report_modal_submit").attr("disabled", true);
        $("#report_modal_entity_type").val(entityType);
        $("#report_modal_entity_id").val(entityId);
    };

    return {
        ladbReportPrepareModal: ladbReportPrepareModal
    };

})();

export default LADBReports;