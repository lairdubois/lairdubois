+function ($) {
    'use strict';

    // CLASS DEFINITION
    // ======================

    var LadbDonate = function(element, options) {
        this.options = options;
        this.$element = $(element);

        this.amount = this.options.defaultAmount;

        this.$modal = $('#donate_modal', this.$element);
        this.$donateButton = $('#ladb_donate_btn', this.$element);
        this.$payButton = $('#ladb_donate_pay_btn', this.$element);
        this.$amountInput = $('#ladb_donate_amount_input', this.$element);
    };

    LadbDonate.DEFAULTS = {
        defaultAmount: 500,
        publishableKey: null,
        dashboardUrl: null,
        chargeUrl: null
    };

    LadbDonate.prototype.retrieveAmount = function() {
        this.amount = this.$amountInput.val() * 100;
    };

    LadbDonate.prototype.displayAmount = function() {
        var euroAmount = this.amount / 100;
        this.$amountInput.val(euroAmount);
        $('.ladb-donate-amount', this.$modal).html(euroAmount);
    };

    LadbDonate.prototype.toggleShakeModal = function(shake) {
        $('.modal-content', this.$modal).toggleClass('shake', shake);
    };

    LadbDonate.prototype.bind = function() {
        var that = this;

        // Bind modal
        this.$modal.on('hide.bs.modal', function(e) {

            // Remove errors and shake
            that.toggleShakeModal(false);
            $(this).find('.form-group').removeClass('has-error');

            // Reset Loading on button
            that.$payButton.button('reset');

        });

        // Bind "Donate" button
        this.$donateButton.on('click', function(e) {

            // Update amount
            that.retrieveAmount();
            that.displayAmount();

            // Open modal
            that.$modal.modal();

        });

        // Bind "Pay" button
        this.$payButton.on('click', function(e) {

            // Activate Loading on button
            that.$payButton.button('loading');

            // Prevalidate fileds
            var cardType = $.payment.cardType($('.cc-number').val());
            $('.cc-number').toggleInputError(!$.payment.validateCardNumber($('.cc-number').val()));
            $('.cc-exp').toggleInputError(!$.payment.validateCardExpiry($('.cc-exp').payment('cardExpiryVal')));
            $('.cc-cvc').toggleInputError(!$.payment.validateCardCVC($('.cc-cvc').val(), cardType));
            $('.cc-brand').text(cardType);

            if ($('.has-error').length) {

                // Shake modal
                that.toggleShakeModal(true);

                // Reset Loading on button
                that.$payButton.button('reset');

            } else {

                var $form = $('#payment-form');

                // Request a token from Stripe:
                Stripe.card.createToken($form, function(status, response) {
                    if (response.error) { // Problem!

                        // Show the errors on the form:
                        $form.find('.payment-errors').text(response.error.message);

                        // Reset Loading on button
                        that.$payButton.button('reset');

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

                                // Reload dashboard
                                window.location.href = that.options.dashboardUrl;

                            },
                            error: function(response) {

                                // Reset Loading on button
                                that.$payButton.button('reset');

                            }
                        });

                    }
                });

            }

        });

    };

    LadbDonate.prototype.init = function() {

        this.bind();

        // Init amount fields
        this.displayAmount();

        // Initialize payment form
        $('input.cc-number').payment('formatCardNumber');
        $('input.cc-exp').payment('formatCardExpiry');
        $('input.cc-cvc').payment('formatCardCVC');

        $.fn.toggleInputError = function(erred) {
            this.parent('.form-group').toggleClass('has-error', erred);
            return this;
        };

        // Setup Strip API
        Stripe.setPublishableKey(this.options.publishableKey);

    };


    // PLUGIN DEFINITION
    // =======================

    function Plugin(option, _parameter) {
        return this.each(function () {
            var $this   = $(this);
            var data    = $this.data('ladb.donate');
            var options = $.extend({}, LadbDonate.DEFAULTS, $this.data(), typeof option == 'object' && option);

            if (!data) {
                $this.data('ladb.donate', (data = new LadbDonate(this, options)));
            }
            if (typeof option == 'string') {
                data[option](_parameter);
            } else {
                data.init();
            }
        })
    }

    var old = $.fn.ladbDonate;

    $.fn.ladbDonate             = Plugin;
    $.fn.ladbDonate.Constructor = LadbDonate;


    // NO CONFLICT
    // =================

    $.fn.ladbDonate.noConflict = function () {
        $.fn.ladbDonate = old;
        return this;
    }

}(jQuery);