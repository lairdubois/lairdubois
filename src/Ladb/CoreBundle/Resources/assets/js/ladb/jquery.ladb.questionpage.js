+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbQuestionPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$answers = $('.ladb-question-answers', this.$element);

        this.$btnNewAnswer = $('#ladb_answer_btn', this.$element);

        this.$hiddenRow = null;
        this.$answerForm = null;

    };

    LadbQuestionPage.DEFAULTS = {
        answerNewPath: null,
        answerEditPath: null
    };

    LadbQuestionPage.prototype.removeAnswerForm = function() {
        if (this.$answerForm) {
            this.$answerForm.remove();
            this.$answerForm = null;
        }
    };

    LadbQuestionPage.prototype.revealHiddenRow = function() {
        if (this.$hiddenRow) {
            this.$hiddenRow.show();
            this.$hiddenRow = null;
        }
    };

    LadbQuestionPage.prototype.bindAnswerRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-btn-edit', $row).on('click', function(e) {
            e.preventDefault();
            $(this).blur();

            var editPath = $(this).attr('href');

            // Load edit answer form
            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindEditAnswerBox($row, data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

    };

    LadbQuestionPage.prototype.bindEditAnswerBox = function($row, data) {
        var that = this;

        this.removeAnswerForm();
        this.revealHiddenRow();
        this.$btnNewAnswer.show();

        this.$answerForm = $(data);

        $row.hide();
        $row.after(this.$answerForm);
        this.$hiddenRow = $row;

        // Bind collection
        $("[data-form-widget=collection]", this.$answerForm).each(function () {
            new window.infinite.Collection(this, $(this).siblings("[data-prototype]"));
        });

        // Bind form
        var $form = $('form', this.$answerForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                that.removeAnswerForm();
                if ($(data).hasClass('ladb-answer-row')) {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.$hiddenRow = null;
                    that.bindAnswerRow($newRow);
                } else {
                    that.bindEditAnswerBox($row, data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$answerForm).on('click', function() {
            that.removeAnswerForm();
            that.revealHiddenRow();
        });
        $('.ladb-btn-submit', this.$answerForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // Focus the first textarea
        $('textarea', $form).first().focus();

    };

    LadbQuestionPage.prototype.bindNewAnswerBox = function(data) {
        var that = this;

        this.removeAnswerForm();
        this.revealHiddenRow();

        this.$answerForm = $(data);

        this.$btnNewAnswer.hide();
        this.$btnNewAnswer.button('reset');
        this.$btnNewAnswer.parent().after(this.$answerForm);

        // Bind collection
        $("[data-form-widget=collection]", this.$answerForm).each(function () {
            new window.infinite.Collection(this, $(this).siblings("[data-prototype]"));
        });

        // Bind form
        var $form = $('form', this.$answerForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-answer-row')) {
                    that.$answers.append(data);
                    that.removeAnswerForm();
                    that.bindAnswerRow($(data));
                } else {
                    that.bindNewAnswerBox(data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$answerForm).on('click', function() {
            that.removeAnswerForm();
            that.$btnNewAnswer.show();
        });
        $('.ladb-btn-submit', this.$answerForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // ScrollTo form
        this.$answerForm.ladbScrollTo(null, {
            onAfter: function() {

                // Focus the first textarea
                $('textarea', $form).first().focus();

            }
        });

    };

    LadbQuestionPage.prototype.bind = function() {
        var that = this;

        // Bind rows
        $('.ladb-answer-row', this.$element).each(function(index, value) {
            that.bindAnswerRow($(value));
        });

        // Bind buttons
        this.$btnNewAnswer.on('click', function() {
            $(this).button('loading');

            // Load new answer form
            $.ajax(that.options.answerNewPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindNewAnswerBox(data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

    };

    LadbQuestionPage.prototype.init = function() {
        var that = this;

        this.bind();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.questionpage');
            var options = $.extend({}, LadbQuestionPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.questionpage', (data = new LadbQuestionPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbQuestionPage;

    $.fn.ladbQuestionPage             = Plugin;
    $.fn.ladbQuestionPage.Constructor = LadbQuestionPage;


    // NO CONFLICT
    // =================

    $.fn.ladbQuestionPage.noConflict = function () {
        $.fn.ladbQuestionPage = old;
        return this;
    }

}(jQuery);