{extends file='frontend/checkout/change_payment.tpl'}

{block name='frontend_checkout_payment_content'}
    <div class="pw-payment-methods panel--body is--wide block-group">
        {foreach $localPaymentMethods as $payment}
            <div class="payment--method block{if $payment@first} method_first {else} method{/if} paymentwall-ps">

                {* Radio Button *}
                {block name='frontend_checkout_payment_fieldset_input_radio_pw'}
                    <div class="method--input">
                        <input type="radio" name="payment" data-ps-id="{$payment.id}"  data-ps-name="{$payment.name}" class="radio pw-ps" value="{$paymentwallId}" id="payment_method_{$payment.id}" {if $payment.id eq $localPaymentSelected} checked="checked"{/if}/>
                    </div>
                {/block}

                {* Method Name *}
                {block name='frontend_checkout_payment_fieldset_input_label_pw'}
                    <div class="method--label is--first">
                        <label class="method--name is--strong">{$payment.name}</label>
                    </div>
                {/block}

                {* Method Logo *}
                {block name='frontend_checkout_payment_fieldset_template_pw'}
                    <div class="payment--method-logo payment_logo_{$payment.name}">
                        <img src="{$payment.img_url}" alt="{$payent.name}">
                    </div>
                {/block}
            </div>
        {/foreach}
    </div>
    <div class="default-payment-method">
        {$smarty.block.parent}
    </div>
    <script id='pw-inline-script' type="text/javascript">
        var paymentwallId = '{$paymentwallId}';
    </script>
{/block}
