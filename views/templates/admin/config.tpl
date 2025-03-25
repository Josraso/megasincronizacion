{*
* Configuraci�n general del m�dulo
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-cogs"></i> {l s='Configuraci�n General' mod='megasincronizacion'}
    </div>
    
    <form id="module_form" class="defaultForm form-horizontal" action="{$current_url|escape:'html':'UTF-8'}" method="post">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon icon-wrench"></i> {l s='Opciones B�sicas' mod='megasincronizacion'}
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Modo Activo' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="MEGASYNC_LIVE_MODE" id="MEGASYNC_LIVE_MODE_on" value="1" {if $MEGASYNC_LIVE_MODE}checked="checked"{/if}>
                            <label for="MEGASYNC_LIVE_MODE_on">{l s='S�' mod='megasincronizacion'}</label>
                            <input type="radio" name="MEGASYNC_LIVE_MODE" id="MEGASYNC_LIVE_MODE_off" value="0" {if !$MEGASYNC_LIVE_MODE}checked="checked"{/if}>
                            <label for="MEGASYNC_LIVE_MODE_off">{l s='No' mod='megasincronizacion'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Activar/desactivar la funcionalidad del m�dulo' mod='megasincronizacion'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-heading">
                <i class="icon icon-refresh"></i> {l s='Sincronizaci�n Programada' mod='megasincronizacion'}
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Sincronizaci�n de Stock' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="MEGASYNC_SCHEDULED_STOCK_SYNC" id="MEGASYNC_SCHEDULED_STOCK_SYNC_on" value="1" {if $MEGASYNC_SCHEDULED_STOCK_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_STOCK_SYNC_on">{l s='S�' mod='megasincronizacion'}</label>
                            <input type="radio" name="MEGASYNC_SCHEDULED_STOCK_SYNC" id="MEGASYNC_SCHEDULED_STOCK_SYNC_off" value="0" {if !$MEGASYNC_SCHEDULED_STOCK_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_STOCK_SYNC_off">{l s='No' mod='megasincronizacion'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Activar sincronizaci�n programada de stock mediante CRON' mod='megasincronizacion'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Sincronizaci�n de Precios' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="MEGASYNC_SCHEDULED_PRICE_SYNC" id="MEGASYNC_SCHEDULED_PRICE_SYNC_on" value="1" {if $MEGASYNC_SCHEDULED_PRICE_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_PRICE_SYNC_on">{l s='S�' mod='megasincronizacion'}</label>
                            <input type="radio" name="MEGASYNC_SCHEDULED_PRICE_SYNC" id="MEGASYNC_SCHEDULED_PRICE_SYNC_off" value="0" {if !$MEGASYNC_SCHEDULED_PRICE_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_PRICE_SYNC_off">{l s='No' mod='megasincronizacion'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Activar sincronizaci�n programada de precios mediante CRON' mod='megasincronizacion'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Sincronizaci�n de Pedidos' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="MEGASYNC_SCHEDULED_ORDER_SYNC" id="MEGASYNC_SCHEDULED_ORDER_SYNC_on" value="1" {if $MEGASYNC_SCHEDULED_ORDER_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_ORDER_SYNC_on">{l s='S�' mod='megasincronizacion'}</label>
                            <input type="radio" name="MEGASYNC_SCHEDULED_ORDER_SYNC" id="MEGASYNC_SCHEDULED_ORDER_SYNC_off" value="0" {if !$MEGASYNC_SCHEDULED_ORDER_SYNC}checked="checked"{/if}>
                            <label for="MEGASYNC_SCHEDULED_ORDER_SYNC_off">{l s='No' mod='megasincronizacion'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Activar sincronizaci�n programada de pedidos mediante CRON' mod='megasincronizacion'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel">
            <div class="panel-heading">
                <i class="icon icon-clock-o"></i> {l s='Configuraci�n CRON' mod='megasincronizacion'}
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='URL CRON' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <div class="input-group">
                            <input type="text" value="{$cron_link|escape:'html':'UTF-8'}" class="form-control" readonly>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="copy-cron-url">
                                    <i class="icon icon-copy"></i> {l s='Copiar' mod='megasincronizacion'}
                                </button>
                            </span>
                        </div>
                        <p class="help-block">{l s='Utilice esta URL para configurar sus tareas CRON programadas' mod='megasincronizacion'}</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Regenerar Token' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="regenerate_cron_token" value="1">
                                {l s='Regenerar token CRON (invalidar� la URL anterior)' mod='megasincronizacion'}
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Ejemplos CRON' mod='megasincronizacion'}
                    </label>
                    <div class="col-lg-9">
                        <div class="well">
                            <p><strong>{l s='Para ejecutar todas las sincronizaciones cada hora:' mod='megasincronizacion'}</strong></p>
                            <pre>0 * * * * wget -q -O /dev/null {$cron_link|escape:'html':'UTF-8'} > /dev/null 2>&1</pre>
                            
                            <p><strong>{l s='Para ejecutar sincronizaci�n de stock diariamente a las 2 AM:' mod='megasincronizacion'}</strong></p>
                            <pre>0 2 * * * wget -q -O /dev/null {$cron_link|escape:'html':'UTF-8'}&action=stock > /dev/null 2>&1</pre>
                            
                            <p><strong>{l s='Para ejecutar sincronizaci�n de precios diariamente a las 3 AM:' mod='megasincronizacion'}</strong></p>
                            <pre>0 3 * * * wget -q -O /dev/null {$cron_link|escape:'html':'UTF-8'}&action=price > /dev/null 2>&1</pre>
                            
                            <p><strong>{l s='Para ejecutar sincronizaci�n de pedidos cada 30 minutos:' mod='megasincronizacion'}</strong></p>
                            <pre>*/30 * * * * wget -q -O /dev/null {$cron_link|escape:'html':'UTF-8'}&action=order > /dev/null 2>&1</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel-footer">
            <button type="submit" name="submit{$module_name|escape:'html':'UTF-8'}Config" class="btn btn-primary pull-right">
                <i class="icon icon-save"></i> {l s='Guardar' mod='megasincronizacion'}
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Funcionalidad de copiar URL de CRON
    $('#copy-cron-url').click(function() {
        var input = $(this).closest('.input-group').find('input');
        input.select();
        document.execCommand('copy');
        showSuccessMessage('{l s='URL copiada al portapapeles' js=1 mod='megasincronizacion'}');
    });
});
</script>