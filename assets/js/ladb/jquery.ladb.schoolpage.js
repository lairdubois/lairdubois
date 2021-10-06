+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbSchoolPage = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$testimonials = $('.ladb-knowledge-school-testimonials', this.$element);

        this.$btnNewTestimonial = $('#ladb_testimonial_btn', this.$element);

        this.$hiddenRow = null;
        this.$testimonialForm = null;

    };

    LadbSchoolPage.DEFAULTS = {
        testimonialNewPath: null,
        testimonialEditPath: null
    };

    LadbSchoolPage.prototype.removeTestimonialForm = function() {
        if (this.$testimonialForm) {
            this.$testimonialForm.remove();
            this.$testimonialForm = null;
        }
    };

    LadbSchoolPage.prototype.revealHiddenRow = function() {
        if (this.$hiddenRow) {
            this.$hiddenRow.show();
            this.$hiddenRow = null;
        }
    };

    LadbSchoolPage.prototype.bindTestimonialRow = function($row) {
        var that = this;

        // Bind buttons
        $('.ladb-btn-edit', $row).on('click', function(e) {
            e.preventDefault();
            $(this).blur();

            var editPath = $(this).attr('href');

            // Load edit testimonial form
            $.ajax(editPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindEditTestimonialBox($row, data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

        setupTooltips();

    };

    LadbSchoolPage.prototype.bindEditTestimonialBox = function($row, data) {
        var that = this;

        this.removeTestimonialForm();
        this.revealHiddenRow();
        this.$btnNewTestimonial.show();

        this.$testimonialForm = $(data);

        $row.hide();
        $row.after(this.$testimonialForm);
        this.$hiddenRow = $row;

        // Bind form
        var $form = $('form', this.$testimonialForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                that.removeTestimonialForm();
                if ($(data).hasClass('ladb-testimonial-row')) {
                    var $newRow = $(data);
                    $row.replaceWith($newRow);
                    that.$hiddenRow = null;
                    that.bindTestimonialRow($newRow);
                } else {
                    that.bindEditTestimonialBox($row, data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$testimonialForm).on('click', function() {
            that.removeTestimonialForm();
            that.revealHiddenRow();
            return false;
        });
        $('.ladb-btn-submit', this.$testimonialForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        setupTextareas();

        // Focus the first textarea
        $('textarea', $form).first().focus();

    };

    LadbSchoolPage.prototype.bindNewTestimonialBox = function(data) {
        var that = this;

        this.removeTestimonialForm();
        this.revealHiddenRow();

        this.$testimonialForm = $(data);

        this.$btnNewTestimonial.hide();
        this.$btnNewTestimonial.button('reset');
        $('.ladb-testimonials-footer').append(this.$testimonialForm);

        // Bind form
        var $form = $('form', this.$testimonialForm).first();
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-success')) {
                    var $row = $('.ladb-testimonial-row', data);
                    var $header = $('.ladb-testimonials-header', data);
                    var $footer = $('.ladb-testimonials-footer', data);
                    that.$testimonials.append($row);
                    $('.ladb-testimonials-header', that.$element).replaceWith($header);
                    $('.ladb-testimonials-footer', that.$element).replaceWith($footer);
                    that.removeTestimonialForm();
                    that.bindTestimonialRow($row);
                } else {
                    that.bindNewTestimonialBox(data);
                }
            },
            error: function() {
                console.log('error');
            }
        });

        // Bind buttons
        $('.ladb-btn-cancel', this.$testimonialForm).on('click', function() {
            that.removeTestimonialForm();
            that.$btnNewTestimonial.show();
            return false;
        });
        $('.ladb-btn-submit', this.$testimonialForm).on('click', function() {
            $(this).button('loading');
            $form.submit();
        });

        // ScrollTo form
        this.$testimonialForm.ladbScrollTo(null, {
            onAfter: function() {

                setupTextareas();

                // Focus the first textarea
                $('textarea', $form).first().focus();

            }
        });

    };

    LadbSchoolPage.prototype.bindRows = function() {
        var that = this;

        // Bind rows
        $('.ladb-testimonial-row', this.$element).each(function(index, value) {
            that.bindTestimonialRow($(value));
        });

    };

    LadbSchoolPage.prototype.bind = function() {
        var that = this;

        this.bindRows();

        // Bind buttons
        this.$btnNewTestimonial.on('click', function() {
            $(this).button('loading');

            // Load new testimonial form
            $.ajax(that.options.testimonialNewPath, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {
                    that.bindNewTestimonialBox(data);
                },
                error: function () {
                    console.log('error');
                }
            });

        });

    };

    LadbSchoolPage.prototype.init = function() {
        var that = this;

        this.bind();
        setupTextareas();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.schoolpage');
            var options = $.extend({}, LadbSchoolPage.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.schoolpage', (data = new LadbSchoolPage(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbSchoolPage;

    $.fn.ladbSchoolPage             = Plugin;
    $.fn.ladbSchoolPage.Constructor = LadbSchoolPage;


    // NO CONFLICT
    // =================

    $.fn.ladbSchoolPage.noConflict = function () {
        $.fn.ladbSchoolPage = old;
        return this;
    }

}(jQuery);