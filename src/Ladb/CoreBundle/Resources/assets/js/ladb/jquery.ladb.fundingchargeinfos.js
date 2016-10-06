+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbFundingChargeInfos = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.changed = false;

        this.$table = $('.ladb-charge-table', this.$element);
        this.$newBtn = $('.ladb-charge-new-btn', this.$element);
    };

    LadbFundingChargeInfos.DEFAULTS = {
    };

    LadbFundingChargeInfos.prototype.bindRowForm = function($rowForm, $row) {
        var that = this;

        var $form = $('form', $rowForm);
        var $loadingPanel = $('.ladb-loading-panel', $rowForm);
        var $cancelBtn = $('.ladb-charge-cancel-btn', $rowForm);
        var $saveBtn = $('.ladb-charge-save-btn', $rowForm);
        var $deleteBtn = $('.ladb-charge-delete-btn', $rowForm);

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

                if ($data.hasClass('ladb-charge-row')) {
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

        // bind buttons
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
    };

    LadbFundingChargeInfos.prototype.bindRows = function($rows) {
        var that = this;

        $rows.on('click', function(e) {

            // Hide previously edited row
            $('.ladb-charge-row').show();
            $('.ladb-charge-row-form').remove();

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

    LadbFundingChargeInfos.prototype.bind = function() {
        var that = this;

        // Bind modal
        var onHiddenModal = function(e) {
            that.destroy();
            that.$element.off('hidden.bs.modal', onHiddenModal);

            if (that.changed) {

                // CHarges have changed -> reload the dashboard
                $('#ladb_funding_dashboard').ladbFundingDashboard('reload');

            }
        };
        this.$element.on('hidden.bs.modal', onHiddenModal);

        // Bind New buttons
        this.$newBtn.on('click', function(e) {
            e.preventDefault();

            // Hide previously edited row
            $('.ladb-charge-row').show();
            $('.ladb-charge-row-form').remove();

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
        this.bindRows($('.ladb-charge-row', this.$element));

    };

    LadbFundingChargeInfos.prototype.init = function() {

        this.bind();

    };

    LadbFundingChargeInfos.prototype.destroy = function() {
        this.$element.removeData('ladb.fundingChargeInfos');
    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.fundingChargeInfos');
            var options = $.extend({}, LadbFundingChargeInfos.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.fundingChargeInfos', (data = new LadbFundingChargeInfos(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbFundingChargeInfos;

    $.fn.ladbFundingChargeInfos             = Plugin;
    $.fn.ladbFundingChargeInfos.Constructor = LadbFundingChargeInfos;


    // NO CONFLICT
    // =================

    $.fn.ladbFundingChargeInfos.noConflict = function () {
        $.fn.ladbFundingChargeInfos = old;
        return this;
    }

}(jQuery);