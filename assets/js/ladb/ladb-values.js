var LADBValues = (function () {
    var bindNewValueProposalAjaxForm = function () {
        $("#ladb_new_value_proposal form").ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                var i = 0;
                while (i < $(data).length) {
                    if ($(data)[i].tagName == "FORM") {
                        $("#ladb_new_value_proposal form").replaceWith($(data)[i]);
                        bindNewValueProposalAjaxForm();
                    } else if ($(data)[i].tagName == "DIV") {
                        $(".ladb-page").replaceWith($(data)[i]);
                        $('.ladb-comment-widget').ladbCommentWidget();
                        LADBCommon.setupTooltips();
                        setupPopovers();
                        $(document).trigger("updated.ladb");
                        UIkit.notify("Proposition ajoutée !", {
                            status: 'success',
                            pos:'bottom-center',
                            timeout:2000
                        });
                    }
                    i++;
                }
            },
            error: function() {
            }
        });
        LADBCommon.setupTextareas();
    };

    var bindEditValueProposalAjaxForm = function (id) {
        $("#ladb_value_proposal_" + id + " .ladb-content-box").find('form').ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                if ($(data)[0].tagName == "FORM") {
                    $("#ladb_value_proposal_" + id + " .ladb-content-box").find("form").replaceWith(data);
                    bindEditValueProposalAjaxForm(id);
                } else {
                    $("#ladb_value_proposal_" + id).replaceWith(data);
                    $('.ladb-comment-widget').ladbCommentWidget();
                    LADBCommon.setupTooltips();
                    setupPopovers();
                    $(document).trigger("updated.ladb");
                }
            },
            error: function() {
                cancelEditValueProposal();
            }
        });
        LADBCommon.setupTextareas();
    };

    var editValueProposal = function (id, url) {
        jQuery.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                cancelEditValueProposal();
                $("#ladb_value_proposal_" + id + " .ladb-content-box .ladb-editable").hide();
                $("#ladb_value_proposal_" + id + " .ladb-content-box").append(data);
                bindEditValueProposalAjaxForm(id);
            },
            error:function () {
                cancelEditValueProposal();
            }
        });
    };

    var deleteValueProposal = function (id, url) {
        jQuery.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                $("#delete_value_proposal_" + id +"_modal").modal("hide");
                $(".ladb-page").replaceWith($(data));
                $('.ladb-comment-widget').ladbCommentWidget();
                //bindNewCommentAjaxForm();
                LADBCommon.setupTooltips();
                setupPopovers();
                $(document).trigger("updated.ladb");
                UIkit.notify("Proposition supprimée !", {
                    status: 'success',
                    pos:'bottom-center',
                    timeout:2000
                });
            },
            error:function () {
            }
        });
    };

    var moveValueProposal = function (id, url) {
        jQuery.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                $(".ladb-page").replaceWith($(data));
                $('.ladb-comment-widget').ladbCommentWidget();
                LADBCommon.setupTooltips();
                setupPopovers();
                $(document).trigger("updated.ladb");
                UIkit.notify("Proposition déplacée !", {
                    status: 'success',
                    pos:'bottom-center',
                    timeout:2000
                });
            },
            error:function () {
            }
        });
    };

    var cancelEditValueProposal = function () {
        $(".ladb-page .ladb-value-proposal .ladb-content-box .ladb-editable").show();
        $(".ladb-page .ladb-value-proposal .ladb-content-box form").remove();
    };

    return {
        bindNewValueProposalAjaxForm: bindNewValueProposalAjaxForm,
        bindEditValueProposalAjaxForm: bindEditValueProposalAjaxForm,
        editValueProposal: editValueProposal,
        deleteValueProposal: deleteValueProposal,
        moveValueProposal: moveValueProposal,
        cancelEditValueProposal: cancelEditValueProposal
    };
})();

export default LADBValues;
