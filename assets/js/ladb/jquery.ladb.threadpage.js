+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbThreadPage = function(element, options) {
        this.options = options;
        this.$element = $(element);
    };

    LadbThreadPage.DEFAULTS = {
    };

    // Edit /////

    LadbThreadPage.prototype.cancelEdit = function() {
        $(".ladb-message-row .ladb-body", this.$element).show();
        $(".ladb-message-row .ladb-edit", this.$element).remove();
    };

    LadbThreadPage.prototype.bindEdit = function($row) {
        var that = this;

        var $edit = $('.ladb-edit', $row);

        // Bind buttons
        $('.ladb-message-cancel-edit', $edit).on('click', function() {
            that.cancelEdit();
            return false;
        });

        // Bind form
        $('form', $row).ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                if ($(data).attr("class") === "ladb-edit") {
                    $edit.replaceWith(data);
                    that.bindEdit($row);
                } else {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.bindRow($newRow.first());
                    setupTooltips();
                    LADBCommon.lazyLoadReset($newRow);
                }
            },
            error:function() {
                that.cancelEdit();
            }
        });

        // Setup textearea
        LADBCommon.setupTextareas();

    };

    // New /////

    LadbThreadPage.prototype.bindNew = function($new) {
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
                    LADBPictures.pictureGalleryRemoveAllPictures($new.attr('id'));
                    var $row = $(data);
                    var $rowNew = $new.closest('.ladb-message-row');
                    $row.insertBefore($rowNew);
                    $('ul.alert-danger', $new).remove();
                    $('.ladb-form-gallery-section', $new).collapse('hide');
                    setupTooltips();
                    LADBCommon.lazyLoadReset($row);
                    $new.ladbScrollTo();
                    $('textarea', $new).focus();
                    that.bindRow($row);
                }
                $('[type=submit]', $new).button('reset');
            },
            error:function() {
                $('[type=submit]', $new).button('reset');
            }
        });

        // Setup textearea
        LADBCommon.setupTextareas();

    };

    // Row /////

    LadbThreadPage.prototype.bindRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-message-edit', $row).on('click', function () {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var editPath = $(this).attr('href');

            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function (data, textStatus, jqXHR) {
                    that.cancelEdit();
                    $(".ladb-body", $row).hide();
                    $(".ladb-content", $row).append(data);
                    that.bindEdit($row);
                    $btn.button('reset');
                },
                error: function () {
                    that.cancelEdit();
                    $btn.button('reset');
                }
            });

            return false;
        });

    };

    // Internal /////

    LadbThreadPage.prototype.bind = function() {
        var that = this;

        // Bind new button
        $('.ladb-fake-new button', this.$element).on('click', function() {

            var $btn = $(this);
            $btn.blur();
            $btn.button('loading');

            var newPath = $btn.data('ladb-new-path');
            var $newContainer =  $btn.closest('.ladb-message');
            var $new = $newContainer.children('.ladb-new').last();
            var $fakeNew = $newContainer.children('.ladb-fake-new').last();

            $.ajax(newPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    $newContainer.append(data);
                    $new = $('.ladb-new', $newContainer).last();
                    that.bindNew($new);
                    $new.ladbScrollTo();
                    $('textarea', $new).focus();
                    $fakeNew.remove();
                },
                error: function () {
                }
            });

        });

        // Bind rows
        $('.ladb-message-row', this.$element).each(function (index, value) {
            that.bindRow($(value));
        });

    };

    LadbThreadPage.prototype.init = function() {

        this.bind();

        // Setup tooltips
        setupTooltips();

        // Setup textearea
        LADBCommon.setupTextareas();

        // Intercept hash that starts with #_ scrollto behavior
        if (window.location.hash && window.location.hash.startsWith('#_')) {
            setTimeout(function() {
                var selector = window.location.hash.replace('#', '#ladb');   // Change to a valid element ID like #ladb_XXX
                var $target = $(selector);
                $target.ladbScrollTo(null, {
                    onAfterHighlight: true
                });
            }, 1000);
        } else {
            $('.ladb-message-row:last').ladbScrollTo(null, {
                smoothScroll: false
            });
        }

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.threadPage');
            var options = $.extend({}, LadbThreadPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.threadPage', (data = new LadbThreadPage(this, options)));
                data.init();
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            }
        })
    }

    var old = $.fn.ladbThreadPage;

    $.fn.ladbThreadPage             = Plugin;
    $.fn.ladbThreadPage.Constructor = LadbThreadPage;


    // NO CONFLICT
    // =================

    $.fn.ladbThreadPage.noConflict = function () {
        $.fn.ladbThreadPage = old;
        return this;
    }

}(jQuery);