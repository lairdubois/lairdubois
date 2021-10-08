+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbVoteWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.$upBtn = $('.ladb-vote-up.ladb-enabled', this.$element);
        this.$downBtn = $('.ladb-vote-down.ladb-enabled', this.$element);
        this.$newModal = $('#new_vote_modal');
    };

    LadbVoteWidget.DEFAULTS = {
        upPath: null,
        upIsNew: false,
        downPath: null,
        downIsNew: false,
        mentionQueryPath: null
    };

    LadbVoteWidget.prototype.replaceWidgetWith = function(data) {
        this.$element.replaceWith(data);
        LADBCommon.setupTooltips();
        setupPopovers();
        $(document).trigger('updated.ladb');
    };

    LadbVoteWidget.prototype.replaceModalContentWith = function(data) {
        var $modalContent = this.$newModal.find('.modal-content');
        $modalContent.children().remove();
        $modalContent.append(data);
    };

    LadbVoteWidget.prototype.directBtnAction = function($btn, path, way) {
        var that = this;

        $btn.find('i').removeClass('ladb-icon-arrow-' + way).addClass('ladb-icon-spinner');
        $.ajax(path, {
            cache: false,
            dataType: 'html',
            context: document.body,
            success: function (data, textStatus, jqXHR) {
                that.replaceWidgetWith(data);
            },
            error: function () {
            }
        });
    };

    LadbVoteWidget.prototype.modalBtnAction = function(path, way) {
        var that = this;

        this.$newModal
            .modal({
                remote: path
            })
            .on('loaded.bs.modal', function (e) {
                that.bindModalContent();
            })
            .on('hidden.bs.modal', function (e) {
                var $modal = $(e.target);
                $modal
                    .removeData('bs.modal')
                    .off('loaded.bs.modal')
                    .off('hidden.bs.modal')
                ;
                that.replaceModalContentWith("<div class='modal-header'>Chargement...</div>");
            })
        ;
    };

    LadbVoteWidget.prototype.bindModalContent = function() {
        var that = this;

        var $form = $('form', this.$newModal);
        var $validateBtn = $('a.ladb-vote-btn', this.$newModal);

        // Bind form
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                if ($(data).hasClass('ladb-vote-widget')) {
                    that.replaceWidgetWith(data);
                    that.$newModal.modal('hide');
                } else {
                    that.replaceModalContentWith(data);
                    that.bindModalContent();
                }
            },
            error: function() {
                console.log('error');
                that.$newModal.modal('hide');
            }
        });

        // Bind textarea
        $('textarea', this.$newModal)
            .markdown({ autofocus:true })
            .ladbTextcompletify({ maxCount: 5, mentionQueryPath: this.options.mentionQueryPath });
        autosize($('textarea', this.$newModal));

        // Bind button
        $validateBtn.on('click', function(e) {
            e.preventDefault();
            $form.submit();
        });

    };

    LadbVoteWidget.prototype.bind = function() {
        var that = this;

        this.$upBtn.on('click', function(event) {
            event.preventDefault();
            $('[data-tooltip=tooltip]').tooltip('hide');
            $(this).blur();
            if (that.options.upIsNew) {
                that.modalBtnAction(that.options.upPath, 'up');
            } else {
                that.directBtnAction($(this), that.options.upPath, 'up');
            }
        });

        this.$downBtn.on('click', function(event) {
            event.preventDefault();
            $('[data-tooltip=tooltip]').tooltip('hide');
            $(this).blur();
            if (that.options.downIsNew) {
                that.modalBtnAction(that.options.downPath, 'down');
            } else {
                that.directBtnAction($(this), that.options.downPath, 'down')
            }
        });

    };

    LadbVoteWidget.prototype.init = function() {
        var that = this;

        this.bind();

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.votewidget');
            var options = $.extend({}, LadbVoteWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.votewidget', (data = new LadbVoteWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option]();
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbVoteWidget;

    $.fn.ladbVoteWidget             = Plugin;
    $.fn.ladbVoteWidget.Constructor = LadbVoteWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbVoteWidget.noConflict = function () {
        $.fn.ladbVoteWidget = old;
        return this;
    }

}(jQuery);