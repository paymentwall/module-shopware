{extends file='frontend/index/index.tpl'}

{* Main content *}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_content'}
    <div id="center" class="grid_13 home">
        <div id="payment-form-container"></div>
        <script src="https://api.paymentwall.com/brick/brick.1.4.js"></script>
        <script>
            var brick = new Brick({
                public_key: "{$public_key}",
                amount: "{$order['amount']}",
                currency: "{$order['currency']}",
                container: 'payment-form-container',
                action: "brick/pay",
                form: {
                    merchant: "{$merchant_name}",
                    product: "Order #{$order['orderNumber']}",
                    pay_button: 'Pay',
                    zip: true
                }
            });

            brick.showPaymentForm(function (data) {
                        if (data.success != 1) {
                            $("#err-container").html(data.error.message);
                        } else {
                            $("#err-container").css("color", "#6B9B20");
                            $("#err-container").html("Order has been paid successfully !");
                            setTimeout(function () {
                                window.location.href = 'checkout/finish';
                            }, 2000);
                        }
                        $("#err-container").show();
                    }
                    ,
                    function (errors) {
                        // handle errors
                    }
            )
            ;
        </script>
    </div>
    <style>
        .brick-input--cc-number, .brick-input--email {
            width: 220px !important;
            padding: 0px 10px 0px 35px !important;
        }
        .brick-input--cc-exp, .brick-input--cc-cvv {
            width: 110px !important;
             padding: 0px 10px 0px 35px !important;
        }
        section.block-group {
            min-height: 500px !important;;
        }
        #center {
            margin-top: 60px;
        }
    </style>
{/block}
