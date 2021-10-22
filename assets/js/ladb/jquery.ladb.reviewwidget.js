+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbReviewWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$reviews = $('.ladb-knowledge-book-reviews', this.$element);

        this.$btnNewReview = $('#ladb_review_btn', this.$element);

        this.$hiddenRow = null;
        this.$reviewForm = null;

    };

    LadbReviewWidget.DEFAULTS = {
    };

    LadbReviewWidget.prototype.removeReviewForm = function() {
        if (this.$reviewForm) {
            this.$reviewForm.remove();
            this.$reviewForm = null;
        }
    };

    LadbReviewWidget.prototype.revealHiddenRow = function() {
        if (this.$hiddenRow) {
            this.$hiddenRow.show();
            this.$hiddenRow = null;
        }
    };

    LadbReviewWidget.prototype.bindReviewRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-btn-edit', $row).on('click', function(e) {
            e.preventDefault();
            $(this).blur();
            $(this).button('loading');

            var $btn = $(this);
            var editPath = $(this).attr('href');

            // Load edit review form
            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindEditReviewBox($row, data);
                    $btn.button('reset');
                },
                error: function () {
                    console.log('error');
                }
            });

        });

        LADBCommon.setupTooltips();

    };

    LadbReviewWidget.prototype.bindEditReviewBox = function($row, data) {
        var that = this;

        this.removeReviewForm();
        this.revealHiddenRow();
        this.$btnNewReview.show();

        this.$reviewForm = $(data);

        $row.hide();
        $row.after(this.$reviewForm);
        this.$hiddenRow = $row;

        // Bind form
        var $form = $('form', this.$reviewForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                that.removeReviewForm();
                if ($(data).hasClass('ladb-review-row')) {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.$hiddenRow = null;
                    that.bindReviewRow($newRow);
                } else {
                    that.bindEditReviewBox($row, data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$reviewForm).on('click', function() {
            that.removeReviewForm();
            that.revealHiddenRow();
            return false;
        });
        $('.ladb-btn-submit', this.$reviewForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        LADBCommon.setupTextareas();

        // Focus the first textarea
        $('input', $form).first().focus();

    };

    LadbReviewWidget.prototype.bindNewReviewBox = function(data) {
        var that = this;

        this.removeReviewForm();
        this.revealHiddenRow();

        this.$reviewForm = $(data);

        this.$btnNewReview.hide();
        this.$btnNewReview.button('reset');
        $('.ladb-reviews-footer').append(this.$reviewForm);

        // Bind form
        var $form = $('form', this.$reviewForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-success')) {
                    var $row = $('.ladb-review-row', data);
                    var $header = $('.ladb-reviews-header', data);
                    var $footer = $('.ladb-reviews-footer', data);
                    that.$reviews.append($row);
                    $('.ladb-reviews-header', that.$element).replaceWith($header);
                    $('.ladb-reviews-footer', that.$element).replaceWith($footer);
                    that.removeReviewForm();
                    that.bindReviewRow($row);
                } else {
                    that.bindNewReviewBox(data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$reviewForm).on('click', function() {
            that.removeReviewForm();
            that.$btnNewReview.show();
            return false;
        });
        $('.ladb-btn-submit', this.$reviewForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // ScrollTo form
        this.$reviewForm.ladbScrollTo(null, {
            onAfter: function() {

                LADBCommon.setupTextareas();

                // Focus the first textarea
                $('input', $form).first().focus();

            }
        });

    };

    LadbReviewWidget.prototype.bindRows = function() {
        var that = this;

        // Bind rows
        $('.ladb-review-row', this.$element).each(function(index, value) {
            that.bindReviewRow($(value));
        });

    };

    LadbReviewWidget.prototype.bind = function() {
        var that = this;

        this.bindRows();

        // Bind buttons
        this.$btnNewReview.on('click', function(e) {
            e.preventDefault();
            $(this).blur();
            $(this).button('loading');

            var newPath = $(this).attr('href');

            // Load new review form
            $.ajax(newPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindNewReviewBox(data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

    };

    LadbReviewWidget.prototype.init = function() {
        var that = this;

        this.bind();
        LADBCommon.setupTextareas();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.reviewwidget');
            var options = $.extend({}, LadbReviewWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.reviewwidget', (data = new LadbReviewWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbReviewWidget;

    $.fn.ladbReviewWidget             = Plugin;
    $.fn.ladbReviewWidget.Constructor = LadbReviewWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbReviewWidget.noConflict = function () {
        $.fn.ladbReviewWidget = old;
        return this;
    }

}(jQuery);