{extends file='frontend/index/index.tpl'}

{* Main content *}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_content'}
    <div id="center" class="grid_13 home">

        {$iframe}
        <script type="text/javascript">
            var xhttp = new XMLHttpRequest();
            setInterval(function () {
                xhttp.onreadystatechange = function () {
                    if (xhttp.readyState == 4 && xhttp.status == 200) {
                        if (0 != xhttp.responseText) {
                            window.location.href = 'checkout/finish';
                        }
                    }
                };
                xhttp.open("GET", "paymentwall/redirect/?orderId={$orderId}", true);
                xhttp.send();
            }, 10000);
        </script>
    </div>
{/block}

 