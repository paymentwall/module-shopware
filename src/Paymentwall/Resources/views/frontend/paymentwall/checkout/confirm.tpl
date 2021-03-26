{extends file='frontend/checkout/confirm.tpl'}

{block name='frontend_checkout_confirm_left_payment_method'}
    <div class="pw-payment-method-wrapper">
        {if !empty($localPaymentSelected) }
            <p class="pw-payment--method-info">
                <strong class="payment--title">{s name="ConfirmInfoPaymentMethod" namespace="frontend/checkout/confirm"}{/s}</strong>
                <span class="payment--description">{$localPaymentSelected}</span>
            </p>
        {/if}
        {$smarty.block.parent}
    </div>
{/block}