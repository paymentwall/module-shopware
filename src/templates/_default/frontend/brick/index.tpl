{extends file='frontend/index/index.tpl'}

{* Main content *}
{block name='frontend_index_content'}
    <div id="center" class="grid_13 home">
        <script type="text/javascript">
            $("#left").hide();
        </script>
        <div id="payment-form-container"></div>
        <script src="https://api.paymentwall.com/brick/brick.1.3.js"></script>
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
        .brick-wrapper {
            margin: auto;
        }

        .brick-iw-cvv:before, .brick-iw-exp:before, .brick-iw-cc:before, .brick-iw-email:before {
            margin: 15px 0 0 11px;
        }
    </style>
{/block}

 