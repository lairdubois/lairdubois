function bindNewValueProposalAjaxForm() {
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
                    setupTooltips();
                    setupPopovers();
                    $(document).trigger("updated.ladb");
                }
                i++;
            }
        },
        error: function() {
        }
    });
}
function bindEditValueProposalAjaxForm(id) {
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
                setupTooltips();
                setupPopovers();
                $(document).trigger("updated.ladb");
            }
        },
        error: function() {
            cancelEditValueProposal();
        }
    });
}
function editValueProposal(id, url) {
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
}
function deleteValueProposal(id, url) {
    jQuery.ajax(url, {
        cache: false,
        dataType: "html",
        context: document.body,
        success: function(data, textStatus, jqXHR) {
            $("#delete_value_proposal_" + id +"_modal").modal("hide");
            $(".ladb-page").replaceWith($(data));
            $('.ladb-comment-widget').ladbCommentWidget();
            //bindNewCommentAjaxForm();
            setupTooltips();
            setupPopovers();
            $(document).trigger("updated.ladb");
        },
        error:function () {
        }
    });
}
function moveValueProposal(id, url) {
    jQuery.ajax(url, {
        cache: false,
        dataType: "html",
        context: document.body,
        success: function(data, textStatus, jqXHR) {
            $(".ladb-page").replaceWith($(data));
            $('.ladb-comment-widget').ladbCommentWidget();
            setupTooltips();
            setupPopovers();
            $(document).trigger("updated.ladb");
        },
        error:function () {
        }
    });
}
function cancelEditValueProposal() {
    $(".ladb-page .ladb-value-proposal .ladb-content-box .ladb-editable").show();
    $(".ladb-page .ladb-value-proposal .ladb-content-box form").remove();
}
