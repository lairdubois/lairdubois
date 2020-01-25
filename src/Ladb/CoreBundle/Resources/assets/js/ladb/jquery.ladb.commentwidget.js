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
                    lazyLoad();
                }
            },
            error:function() {
                that.cancelEditComment();
            }
        });

    };

    // Reply /////

    LadbCommentWidget.prototype.writeComment = function(msg) {
        var entityType = this.$element.data('ladb-entity-type');
        var entityId = this.$element.data('ladb-entity-id');
        var newContainerSelector = '#ladb_comment_' + entityType + '_' + entityId + '_group';
        this.writeCommentIn(msg, newContainerSelector);
    };

    LadbCommentWidget.prototype.writeCommentIn = function(msg, newContainerSelector, callback) {
        var that = this;

        var $newContainer = $(newContainerSelector);
        var newPath = $newContainer.data('ladb-new-path');
        var $new = $newContainer.children('.ladb-new').last();
        var $fakeNew = $newContainer.children('.ladb-fake-new').last();
        var isCollapse = $newContainer.hasClass('collapse');

        if ($new.length > 0) {
            if (isCollapse) {
                $newContainer.collapse('show');
            }
            $new.ladbScrollTo();
            $('textarea', $new)
                .val(msg)
                .focus();
            if (typeof callback == 'function') {
                callback();
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
                        .val(msg);
                    if (typeof callback == 'function') {
                        callback();
                    }
                    $fakeNew.remove();
                },
                error: function () {
                    if (typeof callback == 'function') {
                        callback();
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

            that.writeCommentIn(null, newContainerSelector, function() {
                $btn.button('reset')
            });
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
                    $(".ladb-content", $row).append(data);
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
            $('.tooltip').tooltip('hide');

            if (!confirm('Confirmez votre action.')) {
                return false;
            }

            var deletePath = $(this).attr('href');

            $.ajax(deletePath, {
                cache:false,
                dataType:"html",
                context: document.body,
                success:function(data, textStatus, jqXHR) {
                    $row.remove();
                    $('#'+ $row.attr('id') + '_group').remove();    // Remove childrens too
                    $btn.button('reset');
                },
                error:function () {
                    $btn.button('reset');
                }
            });

            return false;
        });
        $('.ladb-admin-tool', $row).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');
            $('.tooltip').tooltip('hide');
            $('.dropdown.open .dropdown-toggle').dropdown('toggle');

            if (!confirm('Confirmez votre action.')) {
                return false;
            }

            return true;
        });
        $('.ladb-admin-tool-moveup', $row).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');
            $('.tooltip').tooltip('hide');
            $('.dropdown.open .dropdown-toggle').dropdown('toggle');

            if (!confirm('Confirmez votre action.')) {
                return false;
            }

            var moveupPath = $(this).attr('href');

            $.ajax(moveupPath, {
                cache:false,
                dataType:"html",
                context: document.body,
                success:function(data, textStatus, jqXHR) {
                    $row.insertAfter($row.parent());
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

            that.writeCommentIn(mention, newContainerSelector, function() {
                $btn.button('reset')
            });

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
                    that.bindRow($row.first());
                    setupTooltips();
                    lazyLoad();
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

            that.writeCommentIn(null, newContainerSelector, function() {
                $btn.button('reset')
            });

        });
        $('.collapse').on('shown.bs.collapse', function () {
            lazyLoad();
        });
        this.layoutActivities();

        this.bindNew($('.ladb-new', this.$element).last());
        this.bindRows();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.commentWidget');
            var options = $.extend({}, LadbCommentWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.commentWidget', (data = new LadbCommentWidget(this, options)));
                data.init();
            }
            if (typeof option == 'string') {
                data[option](_parameter);
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