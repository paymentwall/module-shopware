{extends file="frontend/index/index.tpl"}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_top_bar_container'}{/block}
{block name='frontend_index_navigation_categories_top'}{/block}
{block name='frontend_index_shop_navigation'}{/block}
{block name='frontend_index_breadcrumb'}{/block}

{block name='frontend_index_logo_trusted_shops'}
    {$smarty.block.parent}
    {s name="FinishButtonBackToShop" namespace="frontend/checkout/finish" assign="snippetFinishButtonBackToShop"}{/s}
    <a href="{url controller='index'}"
       class="btn is--small btn--back-top-shop is--icon-left"
       title="{$snippetFinishButtonBackToShop|escape}"
       xmlns="http://www.w3.org/1999/html">
        <i class="icon--arrow-left"></i>
        {s name="FinishButtonBackToShop" namespace="frontend/checkout/finish"}{/s}
    </a>
{/block}

{block name="frontend_index_content"}
    <div id="payment"   style='margin-top: 25px'>
        <iframe src="{$gatewayUrl}"
                scrolling="yes"
                style="x-overflow: none;"
                frameborder="0"
        width = '100%',
        height = '800'>
        </iframe>
    </div>
{/block}
