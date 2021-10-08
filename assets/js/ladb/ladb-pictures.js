var LADBPictures = (function () {

    var pictureGalleryUpdateForm = function(formSection) {
        var fileupload = $("#" + formSection + "_fileupload");
        var fieldId = fileupload.data("ladb-field-id");
        var maxPictureCount = fileupload.data("ladb-max-picture-count");
        var idsValue = "";
        var idsCount = 0;
        $("#" + formSection + "_thumbnails li.ladb-picture").each(function(index, value) {
            if (idsValue.length > 0) {
                idsValue += ",";
            }
            idsValue += value.id.substring(8); // 8 = "picture_" length
            idsCount++;
        });
        $("#" + fieldId).attr("value", idsValue).trigger('change');
        if (idsCount >= maxPictureCount) {
            fileupload.hide();
        } else {
            fileupload.show();
        }
    };

    var pictureGalleryRemovePicture = function(formSection, id) {
        $("#picture_" + id).remove();
        pictureGalleryUpdateForm(formSection);
    };

    var pictureGalleryRemoveAllPictures = function(formSection) {
        $("#" + formSection + "_thumbnails li").remove(".ladb-picture");
        pictureGalleryUpdateForm(formSection);
    };

    var pictureGalleryEditPicture = function(formSection, id, url) {
        jQuery.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                $("body").append(data);
                $("#edit_" + formSection + "_" + id + "_modal").modal();
            },
            error: function() {
            }
        });
    };

    var pictureGalleryCancelEditPicture = function(formSection, id) {
        var modal = $("#edit_" + formSection + "_" + id + "_modal");
        modal.modal("hide");
        modal.remove();
    };

    var pictureGalleryRotatePreview = function(preview, input, angle) {
        var rotation = parseInt(input.val());
        preview.removeClass("ladb-rotate" + rotation);
        if (angle < 0 && rotation == 0) {
            rotation = 360;
        }
        rotation = (rotation + angle) % 360;
        preview.addClass("ladb-rotate" + rotation);
        input.val(rotation);
    };

    var pictureGalleryInit = function(options) {
        var fileupload = $("#" + options.formSection + "_fileupload");
        fileupload.data("ladb-field-id", options.fieldId);
        fileupload.data("ladb-max-picture-count", options.maxPictureCount);
        fileupload.find("[type=file]").fileupload({
            dataType: "json",
            loadImageMaxFileSize: options.maxFileSize,
            disableImageResize: true, // /Android(?!.*Chrome)|Opera/.test(window.navigator && navigator.userAgent),
            acceptFileTypes: options.acceptedFileTypes,
            maxFileSize: options.maxFileSize,
            sequentialUploads: true,
            dropZone: "#" + options.formSection + "_dropzone",
            messages: {
                acceptFileTypes: "Type de fichier non accepté (JPEG et PNG seulement)",
                maxFileSize: "Le fichier est trop volumineux (max " + formatFileSize(options.maxFileSize) + ")"
            },
            processstart: function(e) {
                fileupload.find(".progress").first().show();
            },
            processfail: function (e, data) {
                var file = data.files[data.index];
                notifyFileError(file.name, file.error);
            },
            send: function (e, data) {
                if ($("#" + options.formSection + "_thumbnails li.ladb-picture").length >= options.maxPictureCount) {
                    return false;
                }
            },
            progressall: function (e, data) {
                var progress = parseInt(data.loaded / data.total * 100, 10);
                fileupload.find(".progress-bar").first().css("width", progress + "%");
            },
            done: function (e, data) {
                $.each(data.result.files, function (index, file) {
                    if (file.error) {
                        notifyFileError(file.name, file.error);
                    } else {
                        $(options.thumnailTemplate
                            .replace(new RegExp("000", 'g'), file.id)
                            .replace(new RegExp("SRC", 'g'), file.name))
                            .appendTo("#" + options.formSection + "_thumbnails");
                        $("#" + options.formSection + "_dropzone").trigger("uploaded");
                    }
                });
            },
            fail: function (e, data) {
                if (data.textStatus != undefined) {
                    notifyError(data.textStatus);
                }
            },
            stop: function (e) {
                fileupload.find(".progress").first().hide();
                fileupload.find(".progress-bar").first().css("width", 0);
                pictureGalleryUpdateForm(options.formSection);
                if (options.sortable) {
                    $("#" + options.formSection + "_thumbnails").sortable("refresh").sortable("refreshPositions");
                }
                setupTooltips();
            }
        })
            .prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled')
        if (options.sortable) {
            $("#" + options.formSection + "_thumbnails").sortable({
                tolerance: "pointer",
                handle: '.ladb-sortable-handle',
                update: function(e, ui) {
                    pictureGalleryUpdateForm(options.formSection);
                }
            });
        }
    };

    return {
        pictureGalleryUpdateForm: pictureGalleryUpdateForm,
        pictureGalleryRemovePicture: pictureGalleryRemovePicture,
        pictureGalleryRemoveAllPictures: pictureGalleryRemoveAllPictures,
        pictureGalleryEditPicture: pictureGalleryEditPicture,
        pictureGalleryCancelEditPicture: pictureGalleryCancelEditPicture,
        pictureGalleryRotatePreview: pictureGalleryRotatePreview,
        pictureGalleryInit: pictureGalleryInit
    };
})();

export default LADBPictures;