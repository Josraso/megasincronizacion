{*
* Vista detallada de una tienda
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-shopping-cart"></i> {l s='Detalles de Tienda' mod='megasincronizacion'}: {$shop.name}
        
        <div class="panel-heading-action">
            <a href="{$current_url|escape:'html':'UTF-8'}&shopAction=edit&id_shop={$shop.id_megasync_shop}" class="btn btn-default">
                <i class="icon icon-pencil"></i> {l s='Editar Tienda' mod='megasincronizacion'}
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-info-circle"></i> {l s='Información General' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='ID:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">{$shop.id_megasync_shop}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='Nombre:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">{$shop.name}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='URL:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">
                                    <a href="{$shop.url}" target="_blank">
                                        {$shop.url} <i class="icon icon-external-link"></i>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='Estado:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">
                                    {if $shop.active}
                                        <span class="label label-success">{l s='Activa' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-danger">{l s='Inactiva' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='Fecha Creación:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">{$shop.date_add|date_format:'%d/%m/%Y %H:%M'}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='Última Actualización:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static">{$shop.date_upd|date_format:'%d/%m/%Y %H:%M'}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-4">{l s='Estado Conexión:' mod='megasincronizacion'}</label>
                            <div class="col-lg-8">
                                <p class="form-control-static" id="connection-status">
                                    {if $connection_status}
                                        <span class="label label-success">{l s='Conectada' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-danger">{l s='Desconectada' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <button id="test-connection-btn" class="btn btn-default" data-shop-id="{$shop.id_megasync_shop}">
                        <i class="icon icon-refresh"></i> {l s='Probar Conexión' mod='megasincronizacion'}
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-cogs"></i> {l s='Configuración' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Sincronización Stock:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    {if $shop.sync_stock}
                                        <span class="label label-success">{l s='Activado' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-default">{l s='Desactivado' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Procesamiento por Lotes:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    {if $shop.sync_stock_batch}
                                        <span class="label label-success">{l s='Activado' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-default">{l s='Desactivado' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Sincronización Precios:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    {if $shop.sync_price}
                                        <span class="label label-success">{l s='Activado' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-default">{l s='Desactivado' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                        
                        {if $shop.sync_price}
                            <div class="form-group">
                                <label class="control-label col-lg-5">{l s='Porcentaje Aumento:' mod='megasincronizacion'}</label>
                                <div class="col-lg-7">
                                    <p class="form-control-static">
                                        {if $shop.price_percentage > 0}
                                            <span class="label label-info">+{$shop.price_percentage}%</span>
                                        {else}
                                            <span class="label label-default">0%</span>
                                        {/if}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label col-lg-5">{l s='Solo Precio Base:' mod='megasincronizacion'}</label>
                                <div class="col-lg-7">
                                    <p class="form-control-static">
                                        {if $shop.sync_base_price_only}
                                            <span class="label label-success">{l s='Sí' mod='megasincronizacion'}</span>
                                        {else}
                                            <span class="label label-default">{l s='No' mod='megasincronizacion'}</span>
                                        {/if}
                                    </p>
                                </div>
                            </div>
                        {/if}
                        
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Modo Pedido:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    <span class="label label-primary">{$order_mode_text}</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Método Conversión:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    <span class="label label-primary">{$conversion_method_text}</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-5">{l s='Agrupar Pedidos:' mod='megasincronizacion'}</label>
                            <div class="col-lg-7">
                                <p class="form-control-static">
                                    {if $shop.group_orders}
                                        <span class="label label-success">{l s='Sí' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-default">{l s='No' mod='megasincronizacion'}</span>
                                    {/if}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon icon-refresh"></i> {l s='Sincronización Manual' mod='megasincronizacion'}
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="well">
                        <h4>{l s='Sincronizar Stock' mod='megasincronizacion'}</h4>
                        <p>{l s='Envía el stock de todos los productos a esta tienda' mod='megasincronizacion'}</p>
                        <button class="btn btn-primary sync-button" data-url="{$sync_links.stock|escape:'html':'UTF-8'}" {if !$shop.sync_stock}disabled{/if}>
                            <i class="icon icon-refresh"></i> {l s='Sincronizar Stock' mod='megasincronizacion'}
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="well">
                        <h4>{l s='Sincronizar Precios' mod='megasincronizacion'}</h4>
                        <p>{l s='Envía los precios de todos los productos a esta tienda' mod='megasincronizacion'}</p>
                        <button class="btn btn-primary sync-button" data-url="{$sync_links.price|escape:'html':'UTF-8'}" {if !$shop.sync_price}disabled{/if}>
                            <i class="icon icon-refresh"></i> {l s='Sincronizar Precios' mod='megasincronizacion'}
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4 text-center">
                    <div class="well">
                        <h4>{l s='Sincronizar Pedidos' mod='megasincronizacion'}</h4>
                        <p>{l s='Obtiene e importa pedidos pendientes de esta tienda' mod='megasincronizacion'}</p>
                        <button class="btn btn-primary sync-button" data-url="{$sync_links.order|escape:'html':'UTF-8'}">
                            <i class="icon icon-refresh"></i> {l s='Sincronizar Pedidos' mod='megasincronizacion'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel-footer">
        <a href="{$current_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon icon-arrow-left"></i> {l s='Volver a la lista de tiendas' mod='megasincronizacion'}
        </a>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Probar conexión
    $('#test-connection-btn').click(function() {
        var btn = $(this);
        var shopId = btn.data('shop-id');
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=TestConnection',
            data: { shop_id: shopId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#connection-status').html('<span class="label label-success">{l s='Conectada' js=1 mod='megasincronizacion'}</span>');
                    showSuccessMessage(response.message || '{l s='Conexión exitosa' js=1 mod='megasincronizacion'}');
                } else {
                    $('#connection-status').html('<span class="label label-danger">{l s='Desconectada' js=1 mod='megasincronizacion'}</span>');
                    showErrorMessage(response.message || '{l s='Error en la conexión' js=1 mod='megasincronizacion'}');
                }
            },
            error: function() {
                $('#connection-status').html('<span class="label label-danger">{l s='Error' js=1 mod='megasincronizacion'}</span>');
                showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
    
    // Sincronización manual
    $('.sync-button').click(function() {
        var btn = $(this);
        var url = btn.data('url');
        
        if (!url) return;
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: url,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'completed' || response.status === 'success') {
                    showSuccessMessage(response.message || '{l s='Sincronización completada correctamente' js=1 mod='megasincronizacion'}');
                } else {
                    showErrorMessage(response.message || '{l s='Error en la sincronización' js=1 mod='megasincronizacion'}');
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
});
</script>