{*
* Lista de tiendas
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-shopping-cart"></i> {l s='Tiendas Configuradas' mod='megasincronizacion'}
        <span class="badge">{count($shops)}</span>
        <div class="panel-heading-action">
            <a href="{$shop_link|escape:'html':'UTF-8'}add" class="btn btn-default">
                <i class="icon icon-plus"></i> {l s='Añadir Nueva Tienda' mod='megasincronizacion'}
            </a>
        </div>
    </div>
    
    {if empty($shops)}
        <div class="alert alert-info">
            <p>{l s='No hay tiendas configuradas. Añada una nueva tienda para comenzar.' mod='megasincronizacion'}</p>
        </div>
    {else}
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{l s='ID' mod='megasincronizacion'}</th>
                        <th>{l s='Nombre' mod='megasincronizacion'}</th>
                        <th>{l s='URL' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Stock' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Precios' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Activa' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Conexión' mod='megasincronizacion'}</th>
                        <th class="text-right">{l s='Acciones' mod='megasincronizacion'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$shops item=shop}
                        <tr>
                            <td>{$shop.id_megasync_shop}</td>
                            <td>{$shop.name}</td>
                            <td>
                                <a href="{$shop.url}" target="_blank" title="{l s='Abrir tienda' mod='megasincronizacion'}">
                                    {$shop.url} <i class="icon icon-external-link"></i>
                                </a>
                            </td>
                            <td class="text-center">
                                {if $shop.sync_stock}
                                    <span class="label label-success">{l s='Sí' mod='megasincronizacion'}</span>
                                {else}
                                    <span class="label label-default">{l s='No' mod='megasincronizacion'}</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $shop.sync_price}
                                    <span class="label label-success">{l s='Sí' mod='megasincronizacion'}</span>
                                    {if $shop.price_percentage > 0}
                                        <span class="label label-info">+{$shop.price_percentage}%</span>
                                    {/if}
                                {else}
                                    <span class="label label-default">{l s='No' mod='megasincronizacion'}</span>
                                {/if}
                            </td>
                            <td class="text-center">
                                <a href="#" class="toggle-shop-status" data-shop-id="{$shop.id_megasync_shop}" data-status="{if $shop.active}0{else}1{/if}">
                                    {if $shop.active}
                                        <i class="icon icon-check text-success" title="{l s='Activa' mod='megasincronizacion'}"></i>
                                    {else}
                                        <i class="icon icon-times text-danger" title="{l s='Inactiva' mod='megasincronizacion'}"></i>
                                    {/if}
                                </a>
                            </td>
                            <td class="text-center shop-connection-status" data-shop-id="{$shop.id_megasync_shop}">
                                <i class="icon icon-refresh text-muted" title="{l s='Probar conexión' mod='megasincronizacion'}"></i>
                            </td>
                            <td class="text-right">
                                <div class="btn-group">
                                    <a href="{$shop_link|escape:'html':'UTF-8'}view&id_shop={$shop.id_megasync_shop}" class="btn btn-default btn-xs" title="{l s='Ver detalles' mod='megasincronizacion'}">
                                        <i class="icon icon-search"></i>
                                    </a>
                                    <a href="{$shop_link|escape:'html':'UTF-8'}edit&id_shop={$shop.id_megasync_shop}" class="btn btn-default btn-xs" title="{l s='Editar' mod='megasincronizacion'}">
                                        <i class="icon icon-pencil"></i>
                                    </a>
                                    <a href="#" class="btn btn-default btn-xs test-connection" data-shop-id="{$shop.id_megasync_shop}" title="{l s='Probar conexión' mod='megasincronizacion'}">
                                        <i class="icon icon-exchange"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-xs delete-shop" data-shop-id="{$shop.id_megasync_shop}" data-shop-name="{$shop.name}" title="{l s='Eliminar' mod='megasincronizacion'}">
                                        <i class="icon icon-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        
        <div class="panel-footer">
            <div class="row">
                <div class="col-md-6">
                    <a href="{$shop_link|escape:'html':'UTF-8'}add" class="btn btn-default">
                        <i class="icon icon-plus"></i> {l s='Añadir Nueva Tienda' mod='megasincronizacion'}
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <button id="test-all-connections-btn" class="btn btn-default">
                        <i class="icon icon-refresh"></i> {l s='Probar Todas las Conexiones' mod='megasincronizacion'}
                    </button>
                </div>
            </div>
        </div>
    {/if}
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Probar conexión individual
    $('.test-connection').click(function(e) {
        e.preventDefault();
        var shopId = $(this).data('shop-id');
        var statusCell = $('.shop-connection-status[data-shop-id="' + shopId + '"]');
        
        statusCell.html('<i class="icon icon-spinner icon-spin"></i>');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=TestConnection',
            data: { shop_id: shopId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    statusCell.html('<i class="icon icon-check text-success" title="' + response.message + '"></i>');
                } else {
                    statusCell.html('<i class="icon icon-times text-danger" title="' + response.message + '"></i>');
                }
            },
            error: function() {
                statusCell.html('<i class="icon icon-times text-danger" title="{l s='Error de comunicación' js=1 mod='megasincronizacion'}"></i>');
            }
        });
    });
    
    // Probar todas las conexiones
    $('#test-all-connections-btn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        // Mostrar indicadores de carga en todas las celdas de estado
        $('.shop-connection-status').html('<i class="icon icon-spinner icon-spin"></i>');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=TestAllConnections',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'completed') {
                    var results = response.results || {};
                    var success = 0;
                    var errors = 0;
                    
                    Object.keys(results).forEach(function(shopId) {
                        var shop = results[shopId];
                        var statusCell = $('.shop-connection-status[data-shop-id="' + shopId + '"]');
                        
                        if (shop.status === 'success') {
                            statusCell.html('<i class="icon icon-check text-success" title="' + shop.message + '"></i>');
                            success++;
                        } else {
                            statusCell.html('<i class="icon icon-times text-danger" title="' + shop.message + '"></i>');
                            errors++;
                        }
                    });
                    
                    if (errors === 0) {
                        showSuccessMessage('{l s='Todas las tiendas conectadas correctamente' js=1 mod='megasincronizacion'} (' + success + ')');
                    } else {
                        showWarningMessage('{l s='Conexión completada' js=1 mod='megasincronizacion'}: ' + success + ' {l s='correctas' js=1 mod='megasincronizacion'}, ' + errors + ' {l s='con errores' js=1 mod='megasincronizacion'}');
                    }
                } else {
                    showErrorMessage(response.message || '{l s='Error al probar las conexiones' js=1 mod='megasincronizacion'}');
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
    
    // Cambiar estado de tienda
    $('.toggle-shop-status').click(function(e) {
        e.preventDefault();
        var link = $(this);
        var shopId = link.data('shop-id');
        var status = link.data('status');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=ToggleShop',
            data: { id_shop: shopId, active: status },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    showErrorMessage(response.message || '{l s='Error al cambiar el estado' js=1 mod='megasincronizacion'}');
                }
            },
            error: function() {
                showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
            }
        });
    });
    
    // Eliminar tienda
    $('.delete-shop').click(function(e) {
        e.preventDefault();
        var link = $(this);
        var shopId = link.data('shop-id');
        var shopName = link.data('shop-name');
        
        if (confirm('{l s='¿Está seguro de que desea eliminar la tienda' js=1 mod='megasincronizacion'} "' + shopName + '"? {l s='Esta acción no se puede deshacer.' js=1 mod='megasincronizacion'}')) {
            $.ajax({
                url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=DeleteShop',
                data: { id_shop: shopId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        showErrorMessage(response.message || '{l s='Error al eliminar la tienda' js=1 mod='megasincronizacion'}');
                    }
                },
                error: function() {
                    showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
                }
            });
        }
    });
});
</script>