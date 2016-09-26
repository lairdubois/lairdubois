+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbFundingDashboard = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.balanceRatio = this.$element.data('ladb-balance-ratio');

        this.$loadingPanel = $('.ladb-loading-panel', this.$element);
        this.$navPrev = $('.ladb-prev', this.$element);
        this.$navNext = $('.ladb-next', this.$element);
        this.$chargeBalanceInfosBtn = $('#ladb_charge_balance_infos_btn', this.$element);
        this.$transactionFeeBalanceInfosBtn = $('#ladb_transaction_fee_balance_infos_btn', this.$element);
        this.$carriedForwardBalanceInfosBtn = $('#ladb_carried_forward_balance_infos_btn', this.$element);
        this.$donationBalanceInfosBtn = $('#ladb_donation_balance_infos_btn', this.$element);
        this.$infosModal = $('#infos_modal', this.$element);
    };

    LadbFundingDashboard.DEFAULTS = {
        ratio: 0.2
    };

    LadbFundingDashboard.prototype.load = function(url) {
        var that = this;

        this.$loadingPanel.show();

        $.ajax(url, {
            cache: false,
            dataType: "html",
            context: document.body,
            success: function(data, textStatus, jqXHR) {
                $(data)
                    .insertBefore(that.$element)
                    .ladbFundingDashboard();
                that.$element.remove();
            },
            error: function () {
                that.$loadingPanel.hide();
            }
        });

    };

    LadbFundingDashboard.prototype.bind = function() {
        var that = this;

        // Bind navigation
        this.$navPrev.on('click', function(event) {
            event.preventDefault();
            that.load($(this).attr("href"));
        });
        this.$navNext.on('click', function(event) {
            event.preventDefault();
            that.load($(this).attr("href"));
        });

        // Bind infos modal
        this.$infosModal.on('hidden.bs.modal', function(event) {
            that.$infosModal
                .removeData('bs.modal')
                .find('.modal-content').empty().append('<div class="modal-body">Chargement...</div>');
        });

        // Bind infos
        this.$chargeBalanceInfosBtn.on('click', function(event) {
            event.preventDefault();
            that.$infosModal.modal({ remote: $(this).attr("href") });
        });
        this.$transactionFeeBalanceInfosBtn.on('click', function(event) {
            event.preventDefault();
            that.$infosModal.modal({ remote: $(this).attr("href") });
        });
        this.$carriedForwardBalanceInfosBtn.on('click', function(event) {
            event.preventDefault();
            that.$infosModal.modal({ remote: $(this).attr("href") });
        });
        this.$donationBalanceInfosBtn.on('click', function(event) {
            event.preventDefault();
            that.$infosModal.modal({ remote: $(this).attr("href") });
        });

    };

    LadbFundingDashboard.prototype.init = function() {
        var that = this;

        this.bind();

        // Circle /////

        $('#circle').circleProgress({
            value: that.balanceRatio,
            size: 200,
            thickness: 10,
            startAngle: -Math.PI / 2,
            fill: {
                color: ["#5cb85c"] //"#f77f00"]
            }
        }).on('circle-animation-progress', function(event, progress) {
            $(this).find('span:first').html(parseInt( that.balanceRatio * 100 * progress));
        });

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.fundingDashboard');
            var options = $.extend({}, LadbFundingDashboard.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.fundingDashboard', (data = new LadbFundingDashboard(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbFundingDashboard;

    $.fn.ladbFundingDashboard             = Plugin;
    $.fn.ladbFundingDashboard.Constructor = LadbFundingDashboard;


    // NO CONFLICT
    // =================

    $.fn.ladbFundingDashboard.noConflict = function () {
        $.fn.ladbFundingDashboard = old;
        return this;
    }

}(jQuery);