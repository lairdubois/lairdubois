+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbFundingWidget = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.amount = this.options.defaultAmount;

        this.$fundingWidgetButton = $('#ladb_donate_btn', this.$element);
        this.$amountInput = $('#ladb_donate_amount_input', this.$element);

        this.$paymentModal = $('#payment_modal', this.$element);
        this.$loadingPanel = $('.ladb-loading-panel', this.$paymentModal);
        this.$cancelButton = $('#ladb_donate_cancel_btn', this.$paymentModal);
        this.$payButton = $('#ladb_donate_pay_btn', this.$paymentModal);
        this.$ccNumberInput = $('input.cc-number', this.$paymentModal);
        this.$ccExpInput = $('input.cc-exp', this.$paymentModal);
        this.$ccCvcInput = $('input.cc-cvc', this.$paymentModal);

        this.$paymentSuccessModal = $('#payment_success_modal', this.$element);

    };

    LadbFundingWidget.DEFAULTS = {
        stripePublishableKey: null,
        autoShow: false,
        dashboardUrl: null,
        chargeUrl: null
    };

    LadbFundingWidget.prototype.retrieveAmount = function() {
        this.amount = this.$amountInput.val() * 100;
    };

    LadbFundingWidget.prototype.displayAmount = function() {
        var euroAmount = this.amount / 100;
        var euroFee = euroAmount * 0.014 + 0.25;
        this.$amountInput.val(euroAmount);
        $('.ladb-donate-amount', this.$paymentModal).html(euroAmount);
        $('.ladb-donate-fee', this.$paymentModal).html(euroFee.toFixed(2));
    };

    LadbFundingWidget.prototype.toggleShakePaymentModal = function(shake) {
        var that = this;

        $('.modal-content', this.$paymentModal).toggleClass('ladb-animation-shake', shake);
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
        $('.alert', this.$paymentModal).remove();
    };

    LadbFundingWidget.prototype.bind = function() {
        var that = this;

        // Bind payment modal
        this.$paymentModal.on('hide.bs.modal', function(e) {

            // Remove errors and shake
            that.toggleShakePaymentModal(false);
            that.clearErrorMessage();
            $(this).find('.form-group').removeClass('has-error');

            // Empty input fields
            that.$ccNumberInput.val('');
            that.$ccExpInput.val('');
            that.$ccCvcInput.val('');

            // Reset Loading on button
            that.$payButton.button('reset');

        });

        // Bind payment success modal
        this.$paymentSuccessModal.on('hide.bs.modal', function(e) {

            // Reload dashboard
            window.location.href = that.options.dashboardUrl;

        });

        // Bind "FundingWidget" button
        this.$fundingWidgetButton.on('click', function(e) {
            e.preventDefault();

            // Update amount
            that.retrieveAmount();
            that.displayAmount();

            // Open modal
            that.$paymentModal.modal();

        });

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
                            url: that.options.chargeUrl,
                            data: {
                                token: token,
                                amount: that.amount
                            },
                            success: function(response) {
                                if (response.success) {

                                    that.$paymentModal.modal('hide');
                                    that.$paymentSuccessModal
                                        .find('.modal-body p')
                                        .html(response.message);
                                    that.$paymentSuccessModal.modal('show');

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

    LadbFundingWidget.prototype.init = function() {

        this.bind();

        // Initialize payment form
        this.$ccNumberInput.payment('formatCardNumber');
        this.$ccExpInput.payment('formatCardExpiry');
        this.$ccCvcInput.payment('formatCardCVC');

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