{*
* Menú de pestañas para la navegación del módulo
*}

<div class="megasync-tabs">
    <ul class="nav nav-tabs" role="tablist">
        {foreach from=$tabs key=tab_id item=tab_name}
            <li class="{if $active_tab == $tab_id}active{/if}">
                <a href="{$current_url|escape:'html':'UTF-8'}&tab={$tab_id|escape:'html':'UTF-8'}">
                    {if $tab_id == 'dashboard'}<i class="icon icon-dashboard"></i>{/if}
                    {if $tab_id == 'shops'}<i class="icon icon-shopping-cart"></i>{/if}
                    {if $tab_id == 'orders'}<i class="icon icon-credit-card"></i>{/if}
                    {if $tab_id == 'logs'}<i class="icon icon-list"></i>{/if}
                    {if $tab_id == 'config'}<i class="icon icon-cogs"></i>{/if}
                    {$tab_name|escape:'html':'UTF-8'}
                </a>
            </li>
        {/foreach}
    </ul>
</div>

<div class="tab-content panel">
    {if isset($confirmation)}
        <div class="alert alert-success">
            {$confirmation|escape:'html':'UTF-8'}
        </div>
    {/if}
    
    {if isset($error)}
        <div class="alert alert-danger">
            {$error|escape:'html':'UTF-8'}
        </div>
    {/if}
</div>