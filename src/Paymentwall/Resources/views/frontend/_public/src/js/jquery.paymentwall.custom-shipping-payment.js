;(function($) {
    'use strict';
    var defaults = {
        paymentSelectionSelector: '.paymentwall-ps-selection',
        paymentMethodSelector: '.paymentwall-ps',
        restylePaymentSelectionAttribute: 'data-restylePaymentSelection'
    };

    $.overridePlugin('swShippingPayment', {
        registerEvents: function() {
            var me = this;
            me.$el.on('click', defaults.paymentMethodSelector, $.proxy(me.onClick, me));
            me.$el.on('change', me.opts.radioSelector, $.proxy(me.onInputChanged, me));

            $.publish('plugin/swShippingPayment/onRegisterEvents', [me]);
        },

        onClick: function(event) {
            var me = this,
                target = $(event.currentTarget),
                radio = target.find('input.pw-ps'),
                url = window.controller.home + 'PaymentwallPaymentSystem/savePaymentsystem',
                data = {
                    'psId': radio.data('ps-id'),
                    'psName': radio.data('ps-name')
                };
            let paymentId = radio.val();
            if (target.hasClass(defaults.activeCls) || target.hasClass(defaults.staticActiveCls)) {
                return;
            }

            $('#payment_mean' + paymentId).prop('checked', true).trigger('change');
            $.subscribe('plugin/swShippingPayment/onInputChanged', function(event, shippingPayment) {
                var paymentMethodSelected = shippingPayment.$el.find('input[name="payment"]:checked').val();
                if (paymentMethodSelected == window.paymentwallId) {
                    $.ajax({
                        type: 'POST',
                        url: url,
                        data: data,
                        success: function(res) {
                            $('#payment_method_' + res).prop('checked', true);

                            $.publish('plugin/pwShippingPaymentCustom/onSelectedPs', [ me ]);
                        }
                    });
                }
            });
        }
    });
})(jQuery);
