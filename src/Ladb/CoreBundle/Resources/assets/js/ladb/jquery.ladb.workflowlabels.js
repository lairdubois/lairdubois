+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbWorkflowLabels = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.changed = false;

        this.$table = $('.ladb-label-table', this.$element);
        this.$newBtn = $('.ladb-label-new-btn', this.$element);
    };

    LadbWorkflowLabels.DEFAULTS = {
    };

    LadbWorkflowLabels.prototype.bindRowForm = function($rowForm, $row) {
        var that = this;

        var $form = $('form', $rowForm);
        var $loadingPanel = $('.ladb-loading-panel', $rowForm);
        var $cancelBtn = $('.ladb-label-cancel-btn', $rowForm);
        var $saveBtn = $('.ladb-label-save-btn', $rowForm);
        var $deleteBtn = $('.ladb-label-delete-btn', $rowForm);
        var $inputColor = $('.ladb-input-color', $form);

        // Bind form
        $form.ajaxForm({
            cache: false,
            dataType: "html",
            context: document.body,
            clearForm: true,
            success: function(data, textStatus, jqXHR) {

                $cancelBtn.unbind('click');
                $saveBtn.unbind('click');
                $deleteBtn.unbind('click');
                if ($row) { $row.unbind('click'); }

                var $data = $(data);
                $rowForm.replaceWith($data);

                if ($data.hasClass('ladb-workflow-label-row')) {
                    if ($row) { $row.remove(); }
                    that.bindRows($data);
                    that.changed = true;
                } else {
                    that.bindRowForm($data, $row);
                }

            },
            error: function() {
                $loadingPanel.hide();
            }
        });

        // Bind buttons
        $cancelBtn.on('click', function(e) {
            if ($row) { $row.show(); }
            $cancelBtn.unbind('click');
            $saveBtn.unbind('click');
            $deleteBtn.unbind('click');
            $rowForm.remove();
        });
        $saveBtn.on('click', function(e) {
            $loadingPanel.show();
            $form.submit();
        });
        $deleteBtn.on('click', function(e) {
            $loadingPanel.show();
            $.ajax($(this).data('href'), {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function (data, textStatus, jqXHR) {
                    $row.remove();
                    $cancelBtn.unbind('click');
                    $saveBtn.unbind('click');
                    $deleteBtn.unbind('click');
                    $rowForm.remove();
                },
                error: function () {
                    $loadingPanel.hide();
                }
            });

        });

        // Bind input color
        $inputColor.ladbInputColor();

    };

    LadbWorkflowLabels.prototype.bindRows = function($rows) {
        var that = this;

        $rows.on('click', function(e) {

            // Hide previously edited row
            $('.ladb-workflow-label-row').show();
            $('.ladb-workflow-label-row-form').remove();

            var $row = $(this);
            var url = $row.data('href');

            $.ajax(url, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {

                    var $rowForm = $(data);

                    that.bindRowForm($rowForm, $row);

                    // Hide old row
                    $row.hide();

                    // Append row form
                    $row.after($rowForm);

                },
                error: function () {
                }
            });

        });
    };

    LadbWorkflowLabels.prototype.bind = function() {
        var that = this;

        // Bind modal
        var onHiddenModal = function(e) {
            that.destroy();
            that.$element.off('hidden.bs.modal', onHiddenModal);

            if (that.changed) {

                // Labels have changed -> do something
                // TODO

            }
        };
        this.$element.on('hidden.bs.modal', onHiddenModal);

        // Bind New buttons
        this.$newBtn.on('click', function(e) {
            e.preventDefault();

            // Hide previously edited row
            $('.ladb-workflow-label-row').show();
            $('.ladb-workflow-label-row-form').remove();

            var $newBtn = $(this);
            var url = $newBtn.attr('href');

            // Loading button
            $newBtn.button('loading');

            $.ajax(url, {
                cache: false,
                dataType: "html",
                context: document.body,
                success: function(data, textStatus, jqXHR) {

                    var $tbody = $('tbody', that.$table);
                    var $rowForm = $(data);

                    that.bindRowForm($rowForm, null);

                    // Reset loading
                    $newBtn.button('reset');

                    // Append row form
                    $tbody.append($rowForm);

                },
                error: function () {
                    // Reset loading
                    $newBtn.button('reset');
                }
            });

        });

        // Bind Rows
        this.bindRows($('.ladb-workflow-label-row', this.$element));

    };

    LadbWorkflowLabels.prototype.init = function() {

        this.bind();

    };

    LadbWorkflowLabels.prototype.destroy = function() {
        this.$element.removeData('ladb.workflowLabels');
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.workflowLabels');
            var options = $.extend({}, LadbWorkflowLabels.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.workflowLabels', (data = new LadbWorkflowLabels(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.LadbWorkflowLabels;

    $.fn.ladbWorkflowLabels             = Plugin;
    $.fn.ladbWorkflowLabels.Constructor = LadbWorkflowLabels;


    // NO CONFLICT
    // =================

    $.fn.ladbWorkflowLabels.noConflict = function () {
        $.fn.ladbWorkflowLabels = old;
        return this;
    }

}(jQuery);