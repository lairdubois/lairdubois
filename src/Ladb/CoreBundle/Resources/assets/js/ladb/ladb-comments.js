$(document).ready(function(){
    bindNewCommentAjaxForm();
});
function bindNewCommentAjaxForm() {
    $(".ladb-comment-widget").each(function (index, value) {
        $(value).find(".ladb-new").find("form").ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).attr("class") == "ladb-new") {
                    $(value).find(".ladb-new").replaceWith(data);
                    bindNewCommentAjaxForm();
                } else {
                    pictureGalleryRemoveAllPictures($(value).find(".ladb-new").attr('id'));
                    $(value).find(".ladb-comment-list").append(data);
                    $(value).find("ul.alert-danger").remove();
                    $(value).find(".ladb-form-gallery-section").collapse('hide');
                    setupTooltips();
                }
                $(value).find(".ladb-new").find("[type=submit]").button('reset');
            },
            error:function() {
            }
        });
    });
}
function bindEditCommentAjaxForm(id) {
    $("#ladb_comment_" + id).find('form').ajaxForm({
        cache: false,
        dataType: "html",
        context: document.body,
        success: function(data, textStatus, jqXHR) {
            if ($(data).attr("class") == "ladb-edit") {
                $("#ladb_comment_" + id).find(".ladb-edit").replaceWith(data);
                bindEditCommentAjaxForm(id);
            } else {
                $("#ladb_comment_" + id).replaceWith(data);
                setupTooltips();
            }
        },
        error:function() {
            cancelEditComment();
        }
    });
}
function editComment(id, url) {
    jQuery.ajax(url, {
        cache: false,
        dataType: "html",
        context: document.body,
        success: function(data, textStatus, jqXHR) {
            cancelEditComment();
            $("#ladb_comment_" + id).find(".ladb-body").hide();
            $("#ladb_comment_" + id).find(".ladb-box").append(data);
            bindEditCommentAjaxForm(id);
        },
        error:function () {
            cancelEditComment();
        }
    });
}
function deleteComment(id, url) {
    jQuery.ajax(url, {
        cache:false,
        dataType:"html",
        context: document.body,
        success:function(data, textStatus, jqXHR) {
            $("#ladb_comment_" + id).remove();
        },
        error:function () {
            alert('error');
        }
    });
}
function cancelEditComment() {
    var widget = $(".ladb-comment-widget");
    widget.find(".ladb-body").show();
    widget.find(".ladb-edit").remove();
}