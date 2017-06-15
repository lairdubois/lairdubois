+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbQuestionPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$btnAnswer = $('#ladb_answer_btn', this.$element);

        this.$modalAnswer = $('#answer_modal', this.$element);

    };

    LadbQuestionPage.DEFAULTS = {
        answerNewPath: null
    };

    LadbQuestionPage.prototype.bind = function() {
        var that = this;

        // Bind buttons
        this.$btnAnswer.on('click', function() {
            that.$modalAnswer.modal({ remote:that.options.answerNewPath });
        });

        // Bind modal
        that.$modalAnswer
            .on('hidden.bs.modal', function () {
                console.log('hidden');
            })
            .on('loaded.bs.modal', function () {

                // Bind collections
                $("[data-form-widget=collection]", that.$modalAnswer).each(function () {
                    new window.infinite.Collection(this, $(this).siblings("[data-prototype]"));
                });

                // Bin form
                var $form = $('form', this);
                $form.ajaxForm({
                    cache: false,
                    dataType: "html",
                    context: document.body,
                    clearForm: true,
                    beforeSubmit: function() {
                        that.$element.find('input').prop('disabled', true);
                    },
                    success: function(data, textStatus, jqXHR) {
                        console.log('success');
                    },
                    error: function() {
                        console.log('error');
                    }
                });


                // Bind buttons
                $('.ladb-submit', this).on('click', function() {
                    $form.submit();
                });


            })
        ;

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