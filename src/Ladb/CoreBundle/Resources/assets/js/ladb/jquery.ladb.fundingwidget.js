+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbFundingWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.amount = this.options.defaultAmount;
        this.amountEur = this.amount / 100;
        this.payed = false;

        this.$amountInput = $('#ladb_donate_amount_input', this.$element);
        this.$fundingWidgetButton = $('#ladb_donate_btn', this.$element);

        this.$newModal = $('#new_modal');
        this.$newModalDefaultHtml = this.$newModal.html();

    };

    LadbFundingWidget.DEFAULTS = {
        stripePublishableKey: null,
        autoShow: false,
        defaultAmount: 500,
        newUrl: null,
        createUrl: null,
        dashboardUrl: null
    };

    /////

    LadbFundingWidget.prototype.retrieveAmount = function() {
        this.amountEur = this.$amountInput.val();
        this.amount = this.amountEur * 100;
    };

    LadbFundingWidget.prototype.toggleShakePaymentModal = function(shake) {
        var that = this;

        $('.modal-content', this.$newModal).toggleClass('ladb-animation-shake', shake);
        if (shake) {
            setTimeout(function() {
                $('.modal-content', that.$paymentModal).removeClass('ladb-animation-shake');
            }, 1000);
        }
    };

    LadbFundingWidget.prototype.togglePaymentLoading = function(loading) {
        if (loading) {
            this.$payButton.button('loading');
            this.$cancelButton.hide();
            this.$loadingPanel.show();
        } else {
            this.$payButton.button('reset');
            this.$cancelButton.show();
            this.$loadingPanel.hide();
        }
    };

    LadbFundingWidget.prototype.displayErrorMessage = function(message) {
        this.clearErrorMessage();
        $('#payment-form').prepend(
            $('<div/>', {
                class: 'alert alert-danger'
            }).text(message)
        );
    };

    LadbFundingWidget.prototype.clearErrorMessage = function() {
        $('.alert', this.$newModal).remove();
    };


    /////

    LadbFundingWidget.prototype.bindNewModal = function() {
        var that = this;

        // Bind "ccNumber" inpout
        this.$ccNumberInput.on('keyup', function(e) {
            var cardType = $.payment.cardType(that.$ccNumberInput.val());
        });

        // Bind "Pay" button
        this.$payButton.on('click', function(e) {

            // Hide previous error
            that.clearErrorMessage();

            // Activate Loading
            that.togglePaymentLoading(true);

            // Prevalidate fileds
            var cardType = $.payment.cardType(that.$ccNumberInput.val());
            that.$ccNumberInput.toggleInputError(!$.payment.validateCardNumber(that.$ccNumberInput.val()));
            that.$ccExpInput.toggleInputError(!$.payment.validateCardExpiry(that.$ccExpInput.payment('cardExpiryVal')));
            that.$ccCvcInput.toggleInputError(!$.payment.validateCardCVC(that.$ccCvcInput.val(), cardType));

            if ($('.has-error', that.$paymentModal).length) {

                // Shake modal
                that.toggleShakePaymentModal(true);

                // Reset Loading
                that.togglePaymentLoading(false);

            } else {

                var $form = $('#payment-form');

                // Request a token from Stripe:
                Stripe.card.createToken($form, function(status, response) {
                    if (response.error) { // Problem!

                        // Show the errors on the form
                        that.displayErrorMessage(response.error.message);

                        // Shake modal
                        that.toggleShakePaymentModal(true);

                        // Reset Loading
                        that.togglePaymentLoading(false);

                    } else { // Token was created!

                        // Get the token ID:
                        var token = response.id;

                        // Charging the customer
                        $.ajax({
                            type: "POST",
                            url: that.options.createUrl,
                            data: {
                                token: token,
                                amount: that.amount
                            },
                            success: function(response) {
                                if (response.success) {

                                    // Display success message
                                    that.$newModal
                                        .find('.modal-content')
                                        .html(response.content);

                                    // Flag as payed
                                    that.payed = true;

                                } else {

                                    // Shake modal
                                    that.toggleShakePaymentModal(true);

                                    // Reset Loading
                                    that.togglePaymentLoading(false);

                                    // Show the errors on the form
                                    that.displayErrorMessage(response.message );

                                    switch (response.error_code) {

                                        case 'invalid_number':
                                        case 'incorrect_number':
                                        case 'card_declined':
                                            that.$ccNumberInput
                                                .toggleInputError(true)
                                                .focus();
                                            break;

                                        case 'invalid_expiry_month':
                                        case 'invalid_expiry_year':
                                        case 'expired_card':
                                            that.$ccExpInput
                                                .toggleInputError(true)
                                                .focus();
                                            break;

                                        case 'invalid_cvc':
                                        case 'incorrect_cvc':
                                        case 'incorrect_zip':
                                            that.$ccCvcInput
                                                .toggleInputError(true)
                                                .focus();
                                            break;

                                    }

                                }
                            },
                            error: function(response) {

                                // Shake modal
                                that.toggleShakePaymentModal(true);

                                // Reset Loading
                                that.togglePaymentLoading(false);

                            }
                        });

                    }
                });

            }

        });

    };

    LadbFundingWidget.prototype.bind = function() {
        var that = this;

        // Bind modal
        this.$newModal
            .on('loaded.bs.modal', function(e) {

                that.$loadingPanel = $('.ladb-loading-panel', that.$newModal);
                that.$cancelButton = $('#ladb_donate_cancel_btn', that.$newModal);
                that.$payButton = $('#ladb_donate_pay_btn', that.$newModal);
                that.$ccNumberInput = $('input.cc-number', that.$newModal);
                that.$ccExpInput = $('input.cc-exp', that.$newModal);
                that.$ccCvcInput = $('input.cc-cvc', that.$newModal);

                // Initialize payment form
                that.$ccNumberInput.payment('formatCardNumber');
                that.$ccExpInput.payment('formatCardExpiry');
                that.$ccCvcInput.payment('formatCardCVC');

                // Bind newModal
                that.bindNewModal();

            })
            .on('hidden.bs.modal', function(e) {
                that.$newModal
                    .removeData('bs.modal')
                    .find(".modal-body").remove();
                that.$newModal
                    .find('.modal-footer').remove();
                that.$newModal
                    .html(that.$newModalDefaultHtml);

                // If payed, reload the dashboard page
                if (that.payed) {
                    that.payed = false;
                    window.location = that.options.dashboardUrl;
                }

            });

        // Bind "FundingWidget" button
        this.$fundingWidgetButton.on('click', function(e) {
            e.preventDefault();

            // Retrieve amount
            that.retrieveAmount();

            // Load modal content
            that.$newModal
                .modal({ remote: that.options.newUrl + '?amount_eur=' + that.amountEur });
        });

    };

    LadbFundingWidget.prototype.init = function() {

        this.bind();

        // Restrict amount input to numeric
        this.$amountInput.payment('restrictNumeric');

        $.fn.toggleInputError = function(erred) {
            this.parent('.form-group').toggleClass('has-error', erred);
            return this;
        };

        // Setup Strip API
        Stripe.setPublishableKey(this.options.stripePublishableKey);

        // AutoShow ?
        if (this.options.autoShow) {
            this.$fundingWidgetButton.click();
        }

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.fundingWidget');
            var options = $.extend({}, LadbFundingWidget.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.fundingWidget', (data = new LadbFundingWidget(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbFundingWidget;

    $.fn.ladbFundingWidget             = Plugin;
    $.fn.ladbFundingWidget.Constructor = LadbFundingWidget;


    // NO CONFLICT
    // =================

    $.fn.ladbFundingWidget.noConflict = function () {
        $.fn.ladbFundingWidget = old;
        return this;
    }

}(jQuery);