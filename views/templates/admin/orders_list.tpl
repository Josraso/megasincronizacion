{*
* Lista de pedidos
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-credit-card"></i> {l s='Pedidos' mod='megasincronizacion'}
        <span class="badge">{count($orders)}</span>
        
        <div class="panel-heading-action">
            <button id="sync-orders-btn" class="btn btn-default">
                <i class="icon icon-refresh"></i> {l s='Sincronizar Ahora' mod='megasincronizacion'}
            </button>
            <button id="import-all-btn" class="btn btn-success">
                <i class="icon icon-download"></i> {l s='Importar Todos' mod='megasincronizacion'}
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <form id="filter-form" class="form-inline" action="{$current_url|escape:'html':'UTF-8'}" method="get">
                <input type="hidden" name="configure" value="{$module_name|escape:'html':'UTF-8'}">
                <input type="hidden" name="tab" value="orders">
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-filter"></i> {l s='Filtros' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>{l s='Tienda:' mod='megasincronizacion'}</label>
                                <select name="id_shop" class="form-control">
                                    <option value="0">{l s='-- Todas las tiendas --' mod='megasincronizacion'}</option>
                                    {foreach from=$shops item=shop}
                                        <option value="{$shop.id_megasync_shop}" {if isset($filters.id_shop) && $filters.id_shop == $shop.id_megasync_shop}selected{/if}>{$shop.name}</option>
                                    {/foreach}
                                </select>
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='Estado:' mod='megasincronizacion'}</label>
                                <select name="order_status" class="form-control">
                                    <option value="">{l s='-- Todos los estados --' mod='megasincronizacion'}</option>
                                    <option value="pending" {if isset($filters.status) && $filters.status == 'pending'}selected{/if}>{l s='Pendiente' mod='megasincronizacion'}</option>
                                    <option value="processing" {if isset($filters.status) && $filters.status == 'processing'}selected{/if}>{l s='Procesando' mod='megasincronizacion'}</option>
                                    <option value="imported" {if isset($filters.status) && $filters.status == 'imported'}selected{/if}>{l s='Importado' mod='megasincronizacion'}</option>
                                    <option value="error" {if isset($filters.status) && $filters.status == 'error'}selected{/if}>{l s='Error' mod='megasincronizacion'}</option>
                                    <option value="cancelled" {if isset($filters.status) && $filters.status == 'cancelled'}selected{/if}>{l s='Cancelado' mod='megasincronizacion'}</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='Desde:' mod='megasincronizacion'}</label>
                                <div class="input-group">
                                    <input type="text" name="date_from" class="form-control datepicker" value="{if isset($filters.date_from)}{$filters.date_from}{/if}">
                                    <span class="input-group-addon"><i class="icon icon-calendar"></i></span>
                                </div>
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='Hasta:' mod='megasincronizacion'}</label>
                                <div class="input-group">
                                    <input type="text" name="date_to" class="form-control datepicker" value="{if isset($filters.date_to)}{$filters.date_to}{/if}">
                                    <span class="input-group-addon"><i class="icon icon-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="button" id="reset-filter-btn" class="btn btn-default">
                            <i class="icon icon-eraser"></i> {l s='Limpiar' mod='megasincronizacion'}
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="icon icon-search"></i> {l s='Filtrar' mod='megasincronizacion'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    {if empty($orders)}
        <div class="alert alert-info">
            <p>{l s='No hay pedidos que coincidan con los criterios de búsqueda.' mod='megasincronizacion'}</p>
        </div>
    {else}
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{l s='ID' mod='megasincronizacion'}</th>
                        <th>{l s='Tienda' mod='megasincronizacion'}</th>
                        <th>{l s='Referencia' mod='megasincronizacion'}</th>
                        <th>{l s='ID Origen' mod='megasincronizacion'}</th>
                        <th>{l s='ID Destino' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Estado' mod='megasincronizacion'}</th>
                        <th class="text-right">{l s='Total' mod='megasincronizacion'}</th>
                        <th>{l s='Fecha' mod='megasincronizacion'}</th>
                        <th class="text-center">{l s='Importado' mod='megasincronizacion'}</th>
                        <th class="text-right">{l s='Acciones' mod='megasincronizacion'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$orders item=order}
                        <tr>
                            <td>{$order.id_megasync_order}</td>
                            <td>{$order.shop_name}</td>
                            <td>{$order.reference}</td>
                            <td>{$order.id_order_origin}</td>
                            <td>
                                {if $order.id_order_destination}
                                    <a href="{$link->getAdminLink('AdminOrders')|escape:'html':'UTF-8'}&vieworder&id_order={$order.id_order_destination}" target="_blank">
                                        {$order.id_order_destination} <i class="icon icon-external-link"></i>
                                    </a>
                                {else}
                                    -
                                {/if}
                            </td>
                            <td class="text-center">
                                {if $order.status == 'pending'}
                                    <span class="label label-info">{l s='Pendiente' mod='megasincronizacion'}</span>
                                {elseif $order.status == 'processing'}
                                    <span class="label label-primary">{l s='Procesando' mod='megasincronizacion'}</span>
                                {elseif $order.status == 'imported'}
                                    <span class="label label-success">{l s='Importado' mod='megasincronizacion'}</span>
                                {elseif $order.status == 'error'}
                                    <span class="label label-danger">{l s='Error' mod='megasincronizacion'}</span>
                                {elseif $order.status == 'cancelled'}
                                    <span class="label label-default">{l s='Cancelado' mod='megasincronizacion'}</span>
                                {else}
                                    <span class="label label-default">{$order.status}</span>
                                {/if}
                            </td>
                            <td class="text-right">{displayPrice price=$order.total_paid currency=$order.currency_id}</td>
                            <td>{$order.date_add|date_format:'%d/%m/%Y %H:%M'}</td>
                            <td class="text-center">
                                {if $order.imported}
                                    <i class="icon icon-check text-success" title="{l s='Importado' mod='megasincronizacion'}"></i>
                                {else}
                                    <i class="icon icon-times text-danger" title="{l s='No importado' mod='megasincronizacion'}"></i>
                                {/if}
                            </td>
                            <td class="text-right">
                                <div class="btn-group">
                                    <a href="{$order_link|escape:'html':'UTF-8'}{$order.id_megasync_order}" class="btn btn-default btn-xs" title="{l s='Ver detalles' mod='megasincronizacion'}">
                                        <i class="icon icon-search"></i>
                                    </a>
                                    
                                    {if !$order.imported}
                                        <a href="#" class="btn btn-success btn-xs import-order" data-order-id="{$order.id_megasync_order}" title="{l s='Importar' mod='megasincronizacion'}">
                                            <i class="icon icon-download"></i>
                                        </a>
                                    {/if}
                                    
                                    <a href="#" class="btn btn-default btn-xs refresh-order" data-order-id="{$order.id_megasync_order}" title="{l s='Refrescar estado' mod='megasincronizacion'}">
                                        <i class="icon icon-refresh"></i>
                                    </a>
                                    
                                    <a href="#" class="btn btn-danger btn-xs delete-order" data-order-id="{$order.id_megasync_order}" title="{l s='Eliminar' mod='megasincronizacion'}">
                                        <i class="icon icon-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        
        {* Paginación *}
        {if $pagination.total_pages > 1}
            <div class="text-center">
                <ul class="pagination">
                    {if $pagination.current_page > 1}
                        <li>
                            <a href="{$pagination.url|escape:'html':'UTF-8'}&page=1" title="{l s='Primera página' mod='megasincronizacion'}">
                                <i class="icon icon-angle-double-left"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{$pagination.url|escape:'html':'UTF-8'}&page={$pagination.current_page-1}" title="{l s='Página anterior' mod='megasincronizacion'}">
                                <i class="icon icon-angle-left"></i>
                            </a>
                        </li>
                    {/if}
                    
                    {assign var=start_page value=max(1, $pagination.current_page-2)}
                    {assign var=end_page value=min($pagination.total_pages, $pagination.current_page+2)}
                    
                    {if $start_page > 1}
                        <li class="disabled"><span>...</span></li>
                    {/if}
                    
                    {for $page=$start_page to $end_page}
                        <li {if $page == $pagination.current_page}class="active"{/if}>
                            <a href="{$pagination.url|escape:'html':'UTF-8'}&page={$page}">{$page}</a>
                        </li>
                    {/for}
                    
                    {if $end_page < $pagination.total_pages}
                        <li class="disabled"><span>...</span></li>
                    {/if}
                    
                    {if $pagination.current_page < $pagination.total_pages}
                        <li>
                            <a href="{$pagination.url|escape:'html':'UTF-8'}&page={$pagination.current_page+1}" title="{l s='Página siguiente' mod='megasincronizacion'}">
                                <i class="icon icon-angle-right"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{$pagination.url|escape:'html':'UTF-8'}&page={$pagination.total_pages}" title="{l s='Última página' mod='megasincronizacion'}">
                                <i class="icon icon-angle-double-right"></i>
                            </a>
                        </li>
                    {/if}
                </ul>
            </div>
        {/if}
    {/if}
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Inicializar datepickers
    $('.datepicker').datepicker({
        dateFormat: 'yy-mm-dd'
    });
    
    // Resetear filtros
    $('#reset-filter-btn').click(function() {
        window.location.href = '{$current_url|escape:'javascript':'UTF-8'}';
    });
    
    // Sincronizar pedidos
    $('#sync-orders-btn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=RunManualSync',
            data: { sync_type: 'order' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'completed') {
                    showSuccessMessage('{l s='Sincronización completada' js=1 mod='megasincronizacion'}: ' + response.new_orders + ' {l s='nuevos pedidos' js=1 mod='megasincronizacion'}');
                    // Recargar la página para mostrar los nuevos pedidos
                    location.reload();
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
    
    // Importar todos los pedidos
    $('#import-all-btn').click(function() {
        if (confirm('{l s='¿Está seguro de que desea importar todos los pedidos pendientes?' js=1 mod='megasincronizacion'}')) {
            var btn = $(this);
            btn.prop('disabled', true).find('i').addClass('icon-spin');
            
            $.ajax({
                url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=ImportAllOrders',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccessMessage(response.message || '{l s='Todos los pedidos importados correctamente' js=1 mod='megasincronizacion'}');
                        // Recargar la página para mostrar los cambios
                        location.reload();
                    } else {
                        showErrorMessage(response.message || '{l s='Error al importar los pedidos' js=1 mod='megasincronizacion'}');
                    }
                },
                error: function() {
                    showErrorMessage('{l s='Error de comunicación con el servidor' js=1 mod='megasincronizacion'}');
                },
                complete: function() {
                    btn.prop('disabled', false).find('i').removeClass('icon-spin');
                }
            });
        }
    });
    
    // Importar un pedido
    $('.import-order').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var orderId = btn.data('order-id');
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=ImportOrder',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessMessage(response.message || '{l s='Pedido importado correctamente' js=1 mod='megasincronizacion'}');
                    // Recargar la página para mostrar los cambios
                    location.reload();
                } else {
                    showErrorMessage(response.message || '{l s='Error al importar el pedido' js=1 mod='megasincronizacion'}');
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
    
    // Refrescar estado de un pedido
    $('.refresh-order').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        var orderId = btn.data('order-id')