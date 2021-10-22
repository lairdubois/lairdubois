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
        confirmedUrl: null,
        dashboardUrl: null
    };

    /////

    LadbFundingWidget.prototype.retrieveAmount = function() {
        this.amountEur = parseInt(this.$amountInput.val());
        if (isNaN(this.amountEur)) {
            this.amountEur = this.options.defaultAmount / 100;
        }
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
        if (this.$paymentForm) {
            this.$paymentForm.prepend(
                $('<div class="alert alert-danger"/>').text(message)
            );
        }
    };

    LadbFundingWidget.prototype.clearErrorMessage = function() {
        $('.alert', this.$newModal).remove();
    };


    /////

     LadbFundingWidget.prototype.bind = function() {
        var that = this;

        // Bin amount input
        this.$amountInput.on('focus', function(event) {
            this.setSelectionRange(0, this.value.length);
        });

        // Bind modal
        this.$newModal
            .on('loaded.bs.modal', function(e) {

                that.$loadingPanel = $('.ladb-loading-panel', that.$newModal);
                that.$cancelButton = $('#ladb_donate_cancel_btn', that.$newModal);
                that.$payButton = $('#ladb_donate_pay_btn', that.$newModal);
                that.$paymentForm = $('#stripe_payment_form', that.$newModal);

                if (that.$paymentForm.length > 0) {

                    var style = {
                        base: {
                            iconColor: '#999',
                            color: '#000',
                            fontWeight: 500,
                            fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif',
                            fontSize: '16px',
                            fontSmoothing: 'antialiased',
                            ':-webkit-autofill': {
                                color: '#999',
                            },
                            '::placeholder': {
                                color: '#999',
                            },
                        }
                    };

                    // Setup Stripe
                    var stripe = Stripe(that.options.stripePublishableKey);

                    // Create elements
                    var elements = stripe.elements();
                    var cardNumberElement = elements.create('cardNumber', {
                        style: style
                    });
                    var cardExpiryElement = elements.create('cardExpiry', {
                        style: style
                    });
                    var cardCvcElement = elements.create('cardCvc', {
                        style: style
                    });
                    cardNumberElement.mount('#stripe_card_number_element');
                    cardExpiryElement.mount('#stripe_card_expiry_element');
                    cardCvcElement.mount('#stripe_card_cvc_element');

                    // Bind "Pay" button
                    that.$payButton.on('click', function (e) {
                        e.preventDefault();

                        // Hide previous error
                        that.clearErrorMessage();

                        // Activate Loading
                        that.togglePaymentLoading(true);

                        var secret = $(this).data('secret');
                        var customerName = $(this).data('customer-name');

                        stripe.confirmCardPayment(secret, {
                            payment_method: {
                                card: cardNumberElement,
                                billing_details: {
                                    name: customerName
                                }
                            }
                        }).then(function (result) {
                            if (result.error) {

                                // Show the errors on the form
                                that.displayErrorMessage(result.error.message);

                                // Shake modal
                                that.toggleShakePaymentModal(true);

                                // Reset Loading
                                that.togglePaymentLoading(false);

                            } else {

                                // The payment has been processed!
                                if (result.paymentIntent.status === 'succeeded') {
                                    // Show a success message to your customer
                                    // There's a risk of the customer closing the window before callback
                                    // execution. Set up a webhook or plugin to listen for the
                                    // payment_intent.succeeded event that handles any business critical
                                    // post-payment actions.

                                    $.ajax({
                                        type: "POST",
                                        url: that.options.confirmedUrl,
                                        data: {
                                            intent_id: result.paymentIntent.id,
                                        },
                                        success: function(response) {
                                            if (response.success) {

                                                // Display success message
                                                that.$newModal
                                                    .find('.modal-content')
                                                    .html(response.content);

                                                // Flag as payed
                                                that.payed = true;

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

                            }
                        });

                    });

                }

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

        $.fn.toggleInputError = function(erred) {
            this.parent('.form-group').toggleClass('has-error', erred);
            return this;
        };

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