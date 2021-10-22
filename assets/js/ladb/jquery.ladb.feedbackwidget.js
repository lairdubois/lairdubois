+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbFeedbackWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$feedbacks = $('.ladb-feedbacks', this.$element);

        this.$btnNewFeedback = $('#ladb_feedback_btn', this.$element);

        this.$hiddenRow = null;
        this.$feedbackForm = null;

    };

    LadbFeedbackWidget.DEFAULTS = {
    };

    LadbFeedbackWidget.prototype.removeFeedbackForm = function() {
        if (this.$feedbackForm) {
            this.$feedbackForm.remove();
            this.$feedbackForm = null;
        }
    };

    LadbFeedbackWidget.prototype.revealHiddenRow = function() {
        if (this.$hiddenRow) {
            this.$hiddenRow.show();
            this.$hiddenRow = null;
        }
    };

    LadbFeedbackWidget.prototype.bindFeedbackRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-btn-edit', $row).on('click', function(e) {
            e.preventDefault();
            $(this).blur();
            $(this).button('loading');

            var $btn = $(this);
            var editPath = $(this).attr('href');

            // Load edit feedback form
            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindEditFeedbackBox($row, data);
                    $btn.button('reset');
                },
                error: function () {
                    console.log('error');
                }
            });

        });

        LADBCommon.setupTooltips();

    };

    LadbFeedbackWidget.prototype.bindEditFeedbackBox = function($row, data) {
        var that = this;

        this.removeFeedbackForm();
        this.revealHiddenRow();
        this.$btnNewFeedback.show();

        this.$feedbackForm = $(data);

        $row.hide();
        $row.after(this.$feedbackForm);
        this.$hiddenRow = $row;

        // Bind collection
        $("[data-form-widget=collection]", this.$feedbackForm).each(function () {
            new window.infinite.Collection(this, $('[data-prototype]', $(this).next()));
        });

        // Bind form
        var $form = $('form', this.$feedbackForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                that.removeFeedbackForm();
                if ($(data).hasClass('ladb-feedback-row')) {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.$hiddenRow = null;
                    that.bindFeedbackRow($newRow);
                } else {
                    that.bindEditFeedbackBox($row, data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$feedbackForm).on('click', function() {
            that.removeFeedbackForm();
            that.revealHiddenRow();
            return false;
        });
        $('.ladb-btn-submit', this.$feedbackForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        LADBCommon.setupTextareas();

        // Focus the first textarea
        $('input', $form).first().focus();

    };

    LadbFeedbackWidget.prototype.bindNewFeedbackBox = function(data) {
        var that = this;

        this.removeFeedbackForm();
        this.revealHiddenRow();

        this.$feedbackForm = $(data);

        this.$btnNewFeedback.hide();
        this.$btnNewFeedback.button('reset');
        $('.ladb-feedbacks-footer').append(this.$feedbackForm);

        // Bind collection
        $("[data-form-widget=collection]", this.$feedbackForm).each(function () {
            new window.infinite.Collection(this, $('[data-prototype]', $(this).next()));
        });

        // Bind form
        var $form = $('form', this.$feedbackForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-success')) {
                    var $row = $('.ladb-feedback-row', data);
                    var $header = $('.ladb-feedbacks-header', data);
                    var $footer = $('.ladb-feedbacks-footer', data);
                    that.$feedbacks.append($row);
                    $('.ladb-feedbacks-header', that.$element).replaceWith($header);
                    $('.ladb-feedbacks-footer', that.$element).replaceWith($footer);
                    that.removeFeedbackForm();
                    that.bindFeedbackRow($row);
                } else {
                    that.bindNewFeedbackBox(data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$feedbackForm).on('click', function() {
            that.removeFeedbackForm();
            that.$btnNewFeedback.show();
            return false;
        });
        $('.ladb-btn-submit', this.$feedbackForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // ScrollTo form
        this.$feedbackForm.ladbScrollTo(null, {
            onAfter: function() {

                LADBCommon.setupTextareas();

                // Focus the first textarea
                $('input', $form).first().focus();

            }
        });

    };

    LadbFeedbackWidget.prototype.bindRows = function() {
        var that = this;

        // Bind rows
        $('.ladb-feedback-row', this.$element).each(function(index, value) {
            that.bindFeedbackRow($(value));
        });

    };

    LadbFeedbackWidget.prototype.bind = function() {
        var that = this;

        this.bindRows();

        // Bind buttons
        this.$btnNewFeedback.on('click', function(e) {
            e.preventDefault();
            $(this).blur();
            $(this).button('loading');

            var newPath = $(this).attr('href');

            // Load new feedback form
            $.ajax(newPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindNewFeedbackBox(data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

    };

    LadbFeedbackWidget.prototype.init = function() {
        var that = this;

        this.bind();
        LADBCommon.setupTextareas();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.feedbackwidget');
            var options = $.extend({}, LadbFeedbackWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.feedbackwidget', (data = new LadbFeedbackWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbFeedbackWidget;

    $.fn.ladbFeedbackWidget             = Plugin;
    $.fn.ladbFeedbackWidget.Constructor = LadbFeedbackWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbFeedbackWidget.noConflict = function () {
        $.fn.ladbFeedbackWidget = old;
        return this;
    }

}(jQuery);