+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbQuestionPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$btnNewAnswer = $('#ladb_answer_btn', this.$element);

        this.$answers = $('.ladb-question-answers', this.$element);
        this.$newAnswerBox = $('#ladb_answer_box', this.$element);
    };

    LadbQuestionPage.DEFAULTS = {
        answerNewPath: null,
        answerEditPath: null
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

        $('.ladb-box', $row).append(data);
        $('.ladb-box-inner', $row).hide();

        // Bind collection
        $("[data-form-widget=collection]", $row).each(function () {
            new window.infinite.Collection(this, $(this).siblings("[data-prototype]"));
        });

        // Bind form
        var $form = $('form', $row).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-answer-row')) {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
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
        $('.ladb-btn-cancel', $row).on('click', function() {
            $('.ladb-box-inner', $row).show();
            $('.ladb-answer-form', $row).remove();
        });
        $('.ladb-btn-submit', $row).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // Focus the first textarea
        $('textarea', $form).first().focus();

    };

    LadbQuestionPage.prototype.bindNewAnswerBox = function(data) {
        var that = this;

        this.$newAnswerBox.empty();
        this.$newAnswerBox.append(data);
        this.$newAnswerBox.show();

        this.$btnNewAnswer.hide();
        this.$btnNewAnswer.button('reset');

        // Bind collection
        $("[data-form-widget=collection]", this.$newAnswerBox).each(function () {
            new window.infinite.Collection(this, $(this).siblings("[data-prototype]"));
        });

        // Bind form
        var $form = $('form', this.$newAnswerBox).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-answer-row')) {
                    that.$answers.append(data);
                    that.$newAnswerBox.remove();
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
        $('.ladb-btn-cancel', this.$newAnswerBox).on('click', function() {
            that.$newAnswerBox.empty();
            that.$newAnswerBox.hide();
            that.$btnNewAnswer.show();
        });
        $('.ladb-btn-submit', this.$newAnswerBox).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // ScrollTo box
        this.$newAnswerBox.ladbScrollTo(null, {
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