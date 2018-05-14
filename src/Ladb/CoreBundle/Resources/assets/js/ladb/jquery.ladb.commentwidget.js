+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbCommentWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$showActivities = $('#ladb_comment_settings_show_activities', this.$element);
        this.$hideActivities = $('#ladb_comment_settings_hide_activities', this.$element);

        this.activitiesHidden = this.$element.data('ladb-activities-hidden');

    };

    LadbCommentWidget.DEFAULTS = {
    };

    // Edit /////

    LadbCommentWidget.prototype.cancelEditComment = function() {
        $(".ladb-comment-row .ladb-body", this.$element).show();
        $(".ladb-comment-row .ladb-edit", this.$element).remove();
    };

    LadbCommentWidget.prototype.bindEditComment = function($row) {
        var that = this;

        var $edit = $('.ladb-edit', $row);

        // Bind buttons
        $('.ladb-comment-cancel-edit', $edit).on('click', function() {
           that.cancelEditComment();
           return false;
        });

        // Bind form
        $('form', $row).ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                if ($(data).attr("class") == "ladb-edit") {
                    $edit.replaceWith(data);
                    that.bindEditComment($row);
                } else {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.bindRow($newRow.first());
                    setupTooltips();
                }
            },
            error:function() {
                that.cancelEditComment();
            }
        });

    };

    // Reply /////

    LadbCommentWidget.prototype.replyTo = function($btn, newContainerSelector, mention, newPath) {
        var that = this;

        var $newContainer = $(newContainerSelector);
        var $new = $('.ladb-new', $newContainer).last();
        var $fakeNew = $('.ladb-fake-new', $newContainer).last();
        var isCollapse = $newContainer.hasClass('collapse');

        if ($new.length > 0) {
            if (isCollapse) {
                $newContainer.collapse('show');
            }
            $new.ladbScrollTo();
            $('textarea', $new)
                .val(mention)
                .focus();
            if ($btn) {
                $btn.button('reset');
            }
        } else {
            $.ajax(newPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    if (isCollapse) {
                        $newContainer.collapse('show');
                    }
                    $newContainer.append(data);
                    $new = $('.ladb-new', $newContainer).last();
                    that.bindNew($new);
                    $new.ladbScrollTo();
                    $('textarea', $new)
                        .val(mention);
                    if ($btn) {
                        $btn.button('reset');
                    }
                    $fakeNew.remove();
                },
                error: function () {
                    if ($btn) {
                        $btn.button('reset');
                    }
                }
            });
        }

    };

    // Rows /////

    LadbCommentWidget.prototype.bindRows = function() {
        var that = this;

        $('.ladb-comment-row', this.$element).each(function (index, value) {
            that.bindRow($(value));
        });

        // Bind children fake new textearea
        $('.ladb-fake-new button', this.$element).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var newContainerSelector = $(this).data('ladb-new-container-selector');
            var newPath = $(this).data('ladb-new-path');

            that.replyTo($btn, newContainerSelector, null, newPath);
        });
    };

    LadbCommentWidget.prototype.bindRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-comment-edit', $row).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var editPath = $(this).attr('href');

            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.cancelEditComment();
                    $(".ladb-body", $row).hide();
                    $(".ladb-box", $row).append(data);
                    that.bindEditComment($row);
                    $btn.button('reset');
                },
                error:function () {
                    that.cancelEditComment();
                    $btn.button('reset');
                }
            });

            return false;
        });
        $('.ladb-comment-delete', $row).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var deletePath = $(this).attr('href');

            $.ajax(deletePath, {
                cache:false,
                dataType:"html",
                context: document.body,
                success:function(data, textStatus, jqXHR) {
                    $row.remove();
                    $btn.button('reset');
                },
                error:function () {
                    $btn.button('reset');
                }
            });

            return false;
        });
        $('.ladb-comment-reply', $row).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var newContainerSelector = $(this).data('ladb-new-container-selector');
            var mention = $(this).data('ladb-mention');
            var newPath = $(this).data('ladb-new-path');

            that.replyTo($btn, newContainerSelector, mention, newPath);

            return false;
        });
    };

    // New /////

    LadbCommentWidget.prototype.bindNew = function($new) {
        var that = this;

        $('form', $new).ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass("ladb-new")) {
                    var $newNew = $(data);
                    $new.replaceWith($newNew);
                    that.bindNew($newNew);
                } else {
                    pictureGalleryRemoveAllPictures($new.attr('id'));
                    var $row = $(data);
                    $row.insertBefore($new);
                    $('ul.alert-danger', $new).remove();
                    $('.ladb-form-gallery-section', $new).collapse('hide');
                    setupTooltips();
                    that.bindRow($row.first());
                }
                $('[type=submit]', $new).button('reset');
            },
            error:function() {
            }
        });

    };

    // Activities /////

    LadbCommentWidget.prototype.layoutActivities = function() {
        if (this.activitiesHidden) {
            $('.ladb-activity-row', this.$element).hide();
            this.$showActivities.show();
            this.$hideActivities.hide();
        } else {
            $('.ladb-activity-row', this.$element).show();
            this.$showActivities.hide();
            this.$hideActivities.show();
        }
    };

    LadbCommentWidget.prototype.toggleActivities = function() {
        this.activitiesHidden = !this.activitiesHidden;
        this.layoutActivities();
    };

    // Internal /////

    LadbCommentWidget.prototype.init = function() {
        var that = this;

        // Bind
        this.$showActivities.on('click', function() {
            that.toggleActivities();
        });
        this.$hideActivities.on('click', function() {
            that.toggleActivities();
        });
        $('.ladb-comment-new', this.$element).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var newContainerSelector = $(this).data('ladb-new-container-selector');
            var newPath = $(this).data('ladb-new-path');

            that.replyTo($btn, newContainerSelector, null, newPath);

        });
        $('.collapse').on('shown.bs.collapse', function () {
            $('img[data-src]', $(this)).lazyLoadXT();
        });
        this.layoutActivities();

        this.bindNew($('.ladb-new', this.$element).last());
        this.bindRows();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.commentWidget');
            var options = $.extend({}, LadbCommentWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.commentWidget', (data = new LadbCommentWidget(this, options)));
                data.init();
            }
            if (typeof option == 'string') {
                data[option]();
            }
        })
    }

    var old = $.fn.ladbCommentWidget;

    $.fn.ladbCommentWidget             = Plugin;
    $.fn.ladbCommentWidget.Constructor = LadbCommentWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbCommentWidget.noConflict = function () {
        $.fn.ladbCommentWidget = old;
        return this;
    }

}(jQuery);