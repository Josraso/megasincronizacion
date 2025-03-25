{*
* Formulario de edición/creación de tienda
*}

<div class="panel">
    <div class="panel-heading">
        {if isset($shop.id_megasync_shop)}
            <i class="icon icon-pencil"></i> {l s='Editar Tienda' mod='megasincronizacion'}: {$shop.name}
        {else}
            <i class="icon icon-plus"></i> {l s='Añadir Nueva Tienda' mod='megasincronizacion'}
        {/if}
    </div>
    
    <form id="shop_form" class="form-horizontal" action="{$form_action|escape:'html':'UTF-8'}" method="post">
        <input type="hidden" name="ajax" value="1">
        <input type="hidden" name="action" value="SaveShop">
        {if isset($shop.id_megasync_shop)}
            <input type="hidden" name="id_shop" value="{$shop.id_megasync_shop}">
        {/if}
        
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Nombre' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <input type="text" name="name" class="form-control" value="{if isset($shop.name)}{$shop.name}{/if}" required>
                    <p class="help-block">{l s='Nombre descriptivo para identificar la tienda' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='URL' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <input type="url" name="url" class="form-control" value="{if isset($shop.url)}{$shop.url}{/if}" required>
                    <p class="help-block">{l s='URL completa de la tienda (ej: https://tienda.com)' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='API Key' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <input type="text" name="api_key" class="form-control" value="{if isset($shop.api_key)}{$shop.api_key}{/if}" required>
                    <p class="help-block">{l s='Clave de API para autenticación' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Sincronizar Stock' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="sync_stock" id="sync_stock_on" value="1" {if isset($shop.sync_stock) && $shop.sync_stock}checked="checked"{/if}>
                        <label for="sync_stock_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="sync_stock" id="sync_stock_off" value="0" {if !isset($shop.sync_stock) || !$shop.sync_stock}checked="checked"{/if}>
                        <label for="sync_stock_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Activar sincronización de stock para esta tienda' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Procesar Stock por Lotes' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="sync_stock_batch" id="sync_stock_batch_on" value="1" {if isset($shop.sync_stock_batch) && $shop.sync_stock_batch}checked="checked"{/if}>
                        <label for="sync_stock_batch_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="sync_stock_batch" id="sync_stock_batch_off" value="0" {if !isset($shop.sync_stock_batch) || !$shop.sync_stock_batch}checked="checked"{/if}>
                        <label for="sync_stock_batch_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Activar procesamiento por lotes para grandes volúmenes de stock' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Sincronizar Precios' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="sync_price" id="sync_price_on" value="1" {if isset($shop.sync_price) && $shop.sync_price}checked="checked"{/if}>
                        <label for="sync_price_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="sync_price" id="sync_price_off" value="0" {if !isset($shop.sync_price) || !$shop.sync_price}checked="checked"{/if}>
                        <label for="sync_price_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Activar sincronización de precios para esta tienda' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Porcentaje Aumento Precio' mod='megasincronizacion'}
                </label>
                <div class="col-lg-2">
                    <div class="input-group">
                        <input type="number" name="price_percentage" class="form-control" value="{if isset($shop.price_percentage)}{$shop.price_percentage}{else}0{/if}" step="0.01" min="0">
                        <span class="input-group-addon">%</span>
                    </div>
                    <p class="help-block">{l s='Porcentaje de aumento a aplicar al precio (0 para no aplicar)' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Solo Sincronizar Precio Base' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="sync_base_price_only" id="sync_base_price_only_on" value="1" {if isset($shop.sync_base_price_only) && $shop.sync_base_price_only}checked="checked"{/if}>
                        <label for="sync_base_price_only_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="sync_base_price_only" id="sync_base_price_only_off" value="0" {if !isset($shop.sync_base_price_only) || !$shop.sync_base_price_only}checked="checked"{/if}>
                        <label for="sync_base_price_only_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Sincronizar solo el precio base del producto' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Modo Pedido' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <select name="order_mode" class="form-control">
                        <option value="1" {if isset($shop.order_mode) && $shop.order_mode == 1}selected{/if}>{l s='Modo 1: Cliente y direcciones fijas' mod='megasincronizacion'}</option>
                        <option value="2" {if isset($shop.order_mode) && $shop.order_mode == 2}selected{/if}>{l s='Modo 2: Conservar direcciones originales' mod='megasincronizacion'}</option>
                        <option value="3" {if isset($shop.order_mode) && $shop.order_mode == 3}selected{/if}>{l s='Modo 3: Mixto - Cliente fijo, dirección envío original' mod='megasincronizacion'}</option>
                    </select>
                    <p class="help-block">{l s='Modo de importación de pedidos' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Cliente Fijo' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <select name="fixed_customer_id" class="form-control">
                        <option value="0">{l s='-- Seleccionar cliente --' mod='megasincronizacion'}</option>
                        {foreach from=$customers item=customer}
                            <option value="{$customer.id_option}" {if isset($shop.fixed_customer_id) && $shop.fixed_customer_id == $customer.id_option}selected{/if}>{$customer.name}</option>
                        {/foreach}
                    </select>
                    <p class="help-block">{l s='Cliente a usar para pedidos en modo 1 o 3' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Método Conversión' mod='megasincronizacion'}
                </label>
                <div class="col-lg-6">
                    <select name="conversion_method" class="form-control">
                        <option value="automatic" {if isset($shop.conversion_method) && $shop.conversion_method == 'automatic'}selected{/if}>{l s='Automático (tiempo real)' mod='megasincronizacion'}</option>
                        <option value="manual" {if isset($shop.conversion_method) && $shop.conversion_method == 'manual'}selected{/if}>{l s='Manual' mod='megasincronizacion'}</option>
                        <option value="cron" {if isset($shop.conversion_method) && $shop.conversion_method == 'cron'}selected{/if}>{l s='Por CRON' mod='megasincronizacion'}</option>
                    </select>
                    <p class="help-block">{l s='Método de conversión de pedidos' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Agrupar Pedidos (CRON)' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="group_orders" id="group_orders_on" value="1" {if isset($shop.group_orders) && $shop.group_orders}checked="checked"{/if}>
                        <label for="group_orders_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="group_orders" id="group_orders_off" value="0" {if !isset($shop.group_orders) || !$shop.group_orders}checked="checked"{/if}>
                        <label for="group_orders_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Agrupar todos los pedidos en uno solo al procesar por CRON' mod='megasincronizacion'}</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Activo' mod='megasincronizacion'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="active" id="active_on" value="1" {if !isset($shop.active) || $shop.active}checked="checked"{/if}>
                        <label for="active_on">{l s='Sí' mod='megasincronizacion'}</label>
                        <input type="radio" name="active" id="active_off" value="0" {if isset($shop.active) && !$shop.active}checked="checked"{/if}>
                        <label for="active_off">{l s='No' mod='megasincronizacion'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Estado de la tienda' mod='megasincronizacion'}</p>
                </div>
            </div>
        </div>
        
        <div class="panel-footer">
            <a href="{$current_url|escape:'html':'UTF-8'}" class="btn btn-default">
                <i class="icon icon-arrow-left"></i> {l s='Volver a la lista' mod='megasincronizacion'}
            </a>
            <button type="button" id="test_connection_btn" class="btn btn-info">
                <i class="icon icon-exchange"></i> {l s='Probar Conexión' mod='megasincronizacion'}
            </button>
            <button type="submit" name="submit{$module_name|escape:'html':'UTF-8'}Form" class="btn btn-primary pull-right">
                <i class="icon icon-save"></i> {l s='Guardar' mod='megasincronizacion'}
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Probar conexión
    $('#test_connection_btn').click(function() {
        var url = $('input[name="url"]').val();
        var apiKey = $('input[name="api_key"]').val();
        var btn = $(this);
        
        if (!url || !apiKey) {
            showErrorMessage('{l s='URL y API Key son requeridos para probar la conexión' js=1 mod='megasincronizacion'}');
            return;
        }
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=TestConnection',
            data: {
                url: url,
                api_key: apiKey
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessMessage(response.message || '{l s='Conexión exitosa' js=1 mod='megasincronizacion'}');
                } else {
                    showErrorMessage(response.message || '{l s='Error en la conexión' js=1 mod='megasincronizacion'}');
                }
            },
            error: function() {
                showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
    
    // Enviar formulario por AJAX
    $('#shop_form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessMessage(response.message || '{l s='Tienda guardada correctamente' js=1 mod='megasincronizacion'}');
                    
                    // Redireccionar a la lista de tiendas después de un breve retraso
                    setTimeout(function() {
                        window.location.href = '{$current_url|escape:'javascript':'UTF-8'}';
                    }, 1000);
                } else {
                    showErrorMessage(response.message || '{l s='Error al guardar la tienda' js=1 mod='megasincronizacion'}');
                    submitBtn.prop('disabled', false).find('i').removeClass('icon-spin');
                }
            },
            error: function() {
                showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
                submitBtn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
});
</script>