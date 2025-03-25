{*
* Plantilla para la vista detallada de un pedido
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-credit-card"></i> {l s='Detalle del Pedido' mod='megasincronizacion'} #{$order.id_megasync_order} - {$order.reference}
        <span class="badge badge-{if $order.status == 'imported'}success{elseif $order.status == 'error'}danger{elseif $order.status == 'pending'}info{else}warning{/if}">
            {$status_text}
        </span>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-info-circle"></i> {l s='Información General' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{l s='Tienda de Origen:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">
                                    <a href="{$shop_link}" target="_blank">
                                        {$shop.name} <i class="icon icon-external-link"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Referencia:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$order.reference}</div>
                            </div>
                            <div class="form-group">
                                <label>{l s='ID en Origen:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$order.id_order_origin}</div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Fecha:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$order.date_add|date_format:'%d/%m/%Y %H:%M:%S'}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{l s='Estado:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">
                                    <span class="badge badge-{if $order.status == 'imported'}success{elseif $order.status == 'error'}danger{elseif $order.status == 'pending'}info{else}warning{/if}">
                                        {$status_text}
                                    </span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Importado:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">
                                    {if $order.imported}
                                        <span class="label label-success">{l s='Sí' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-danger">{l s='No' mod='megasincronizacion'}</span>
                                    {/if}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{l s='ID en Tienda Destino:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">
                                    {if $order.id_order_destination}
                                        <a href="{$order_link}" target="_blank">
                                            {$order.id_order_destination} <i class="icon icon-external-link"></i>
                                        </a>
                                    {else}
                                        {l s='No importado aún' mod='megasincronizacion'}
                                    {/if}
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Total:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">
                                    <strong>{displayPrice price=$order.total_paid currency=$order.currency_id}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button id="refresh-order-btn" class="btn btn-default" data-order-id="{$order.id_megasync_order}">
                                <i class="icon icon-refresh"></i> {l s='Refrescar Estado' mod='megasincronizacion'}
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            {if !$order.imported}
                                <a href="{$link->getAdminLink('AdminMegaOrders')}&import&id_megasync_order={$order.id_megasync_order}" class="btn btn-success">
                                    <i class="icon icon-download"></i> {l s='Importar Pedido' mod='megasincronizacion'}
                                </a>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-shopping-cart"></i> {l s='Productos' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{l s='Referencia' mod='megasincronizacion'}</th>
                                    <th>{l s='Producto' mod='megasincronizacion'}</th>
                                    <th class="text-center">{l s='Cantidad' mod='megasincronizacion'}</th>
                                    <th class="text-right">{l s='Precio Unitario' mod='megasincronizacion'}</th>
                                    <th class="text-right">{l s='Total' mod='megasincronizacion'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$order_details item=detail}
                                    <tr>
                                        <td>{$detail.product_reference}</td>
                                        <td>
                                            {$detail.product_name}
                                            {if $detail.product_id}
                                                <a href="{$link->getAdminLink('AdminProducts')}&updateproduct&id_product={$detail.product_id}" target="_blank">
                                                    <i class="icon icon-external-link"></i>
                                                </a>
                                            {/if}
                                        </td>
                                        <td class="text-center">{$detail.product_quantity}</td>
                                        <td class="text-right">{displayPrice price=$detail.product_price currency=$order.currency_id}</td>
                                        <td class="text-right">{displayPrice price=$detail.product_price*$detail.product_quantity currency=$order.currency_id}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>{l s='Total:' mod='megasincronizacion'}</strong></td>
                                    <td class="text-right"><strong>{displayPrice price=$order.total_paid currency=$order.currency_id}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-history"></i> {l s='Historial' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{l s='Fecha' mod='megasincronizacion'}</th>
                                    <th>{l s='Estado' mod='megasincronizacion'}</th>
                                    <th>{l s='Comentario' mod='megasincronizacion'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$order_history item=history}
                                    <tr>
                                        <td>{$history.date_add|date_format:'%d/%m/%Y %H:%M:%S'}</td>
                                        <td>
                                            <span class="badge badge-{if $history.status == 'imported'}success{elseif $history.status == 'error'}danger{elseif $history.status == 'pending'}info{else}warning{/if}">
                                                {$history.status}
                                            </span>
                                        </td>
                                        <td>{$history.comment}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            {if $origin_order}
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-truck"></i> {l s='Información de Envío' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        {if isset($origin_order.shipping_address)}
                            <address>
                                <strong>{$origin_order.shipping_address.firstname} {$origin_order.shipping_address.lastname}</strong><br>
                                {$origin_order.shipping_address.address1}<br>
                                {if $origin_order.shipping_address.address2}{$origin_order.shipping_address.address2}<br>{/if}
                                {$origin_order.shipping_address.postcode} {$origin_order.shipping_address.city}<br>
                                {$origin_order.shipping_address.country}<br>
                                {if $origin_order.shipping_address.phone}
                                    <abbr title="{l s='Teléfono' mod='megasincronizacion'}">T:</abbr> {$origin_order.shipping_address.phone}
                                {/if}
                                {if $origin_order.shipping_address.phone_mobile}
                                    <abbr title="{l s='Móvil' mod='megasincronizacion'}">M:</abbr> {$origin_order.shipping_address.phone_mobile}
                                {/if}
                            </address>
                        {else}
                            <div class="alert alert-warning">
                                {l s='No hay información de envío disponible' mod='megasincronizacion'}
                            </div>
                        {/if}
                        
                        {if isset($origin_order.carrier)}
                            <div class="form-group">
                                <label>{l s='Transportista:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$origin_order.carrier.name}</div>
                            </div>
                            {if isset($origin_order.tracking_number) && $origin_order.tracking_number}
                                <div class="form-group">
                                    <label>{l s='Número de Seguimiento:' mod='megasincronizacion'}</label>
                                    <div class="form-control-static">{$origin_order.tracking_number}</div>
                                </div>
                            {/if}
                        {/if}
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-credit-card"></i> {l s='Información de Facturación' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        {if isset($origin_order.billing_address)}
                            <address>
                                <strong>{$origin_order.billing_address.firstname} {$origin_order.billing_address.lastname}</strong><br>
                                {$origin_order.billing_address.address1}<br>
                                {if $origin_order.billing_address.address2}{$origin_order.billing_address.address2}<br>{/if}
                                {$origin_order.billing_address.postcode} {$origin_order.billing_address.city}<br>
                                {$origin_order.billing_address.country}<br>
                                {if $origin_order.billing_address.phone}
                                    <abbr title="{l s='Teléfono' mod='megasincronizacion'}">T:</abbr> {$origin_order.billing_address.phone}
                                {/if}
                            </address>
                        {else}
                            <div class="alert alert-warning">
                                {l s='No hay información de facturación disponible' mod='megasincronizacion'}
                            </div>
                        {/if}
                        
                        {if isset($origin_order.payment_method) && $origin_order.payment_method}
                            <div class="form-group">
                                <label>{l s='Método de Pago:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$origin_order.payment_method}</div>
                            </div>
                        {/if}
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-user"></i> {l s='Información del Cliente' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        {if isset($origin_order.customer)}
                            <div class="form-group">
                                <label>{l s='Nombre:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$origin_order.customer.firstname} {$origin_order.customer.lastname}</div>
                            </div>
                            <div class="form-group">
                                <label>{l s='Email:' mod='megasincronizacion'}</label>
                                <div class="form-control-static">{$origin_order.customer.email}</div>
                            </div>
                        {else}
                            <div class="alert alert-warning">
                                {l s='No hay información del cliente disponible' mod='megasincronizacion'}
                            </div>
                        {/if}
                    </div>
                </div>
            {else}
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-info-circle"></i> {l s='Información Adicional' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-warning">
                            {l s='No se pudo obtener información detallada del pedido en la tienda origen.' mod='megasincronizacion'}
                        </div>
                        
                        <p class="text-center">
                            <button id="fetch-origin-details-btn" class="btn btn-default" data-order-id="{$order.id_megasync_order}">
                                <i class="icon icon-refresh"></i> {l s='Intentar Obtener Información' mod='megasincronizacion'}
                            </button>
                        </p>
                    </div>
                </div>
            {/if}
            
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-list"></i> {l s='Logs Relacionados' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    {if $logs && count($logs) > 0}
                        <div class="logs-container">
                            {foreach from=$logs item=log}
                                <div class="log-entry log-{$log.type}">
                                    <div class="log-date">{$log.date_add|date_format:'%d/%m/%Y %H:%M'}</div>
                                    <div class="log-type">
                                        <span class="label label-{if $log.type == 'success'}success{elseif $log.type == 'error'}danger{elseif $log.type == 'warning'}warning{else}info{/if}">
                                            {$log.type}
                                        </span>
                                    </div>
                                    <div class="log-message">{$log.message}</div>
                                </div>
                            {/foreach}
                        </div>
                    {else}
                        <div class="alert alert-info">
                            {l s='No hay logs relacionados con este pedido.' mod='megasincronizacion'}
                        </div>
                    {/if}
                    
                    <div class="text-center">
                        <a href="{$link->getAdminLink('AdminMegaLogs')}&id_related={$order.id_megasync_order}&category=order" class="btn btn-default btn-sm">
                            <i class="icon icon-search"></i> {l s='Ver todos los logs relacionados' mod='megasincronizacion'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Refrescar estado del pedido
    $('#refresh-order-btn').click(function() {
        var orderId = $(this).data('order-id');
        var btn = $(this);
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$link->getAdminLink('AdminMegaOrders')}&ajax=1&action=RefreshOrderStatus',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessMessage('{l s='Estado actualizado correctamente' js=1 mod='megasincronizacion'}');
                    // Recargar la página para mostrar la información actualizada
                    location.reload();
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function() {
                showErrorMessage('{l s='Error al actualizar el estado' js=1 mod='megasincronizacion'}');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
    
    // Obtener detalles de la tienda origen
    $('#fetch-origin-details-btn').click(function() {
        var orderId = $(this).data('order-id');
        var btn = $(this);
        
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: '{$link->getAdminLink('AdminMegaOrders')}&ajax=1&action=RefreshOrderDetails',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showSuccessMessage('{l s='Información actualizada correctamente' js=1 mod='megasincronizacion'}');
                    // Recargar la página para mostrar la información actualizada
                    location.reload();
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function() {
                showErrorMessage('{l s='Error al obtener la información' js=1 mod='megasincronizacion'}');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
});
</script>

<style type="text/css">
.badge-success {
    background-color: #72C279;
}
.badge-warning {
    background-color: #FBBB22;
}
.badge-danger {
    background-color: #E08F95;
}
.badge-info {
    background-color: #25B9D7;
}

.log-entry {
    margin-bottom: 10px;
    padding: 8px;
    border-radius: 3px;
    border-left: 3px solid #ddd;
}
.log-success {
    background-color: #f3f9f4;
    border-left-color: #72C279;
}
.log-error {
    background-color: #FFF2F2;
    border-left-color: #E08F95;
}
.log-warning {
    background-color: #FFF9EC;
    border-left-color: #FBBB22;
}
.log-info {
    background-color: #F5FAFD;
    border-left-color: #25B9D7;
}
.log-date {
    font-size: 0.9em;
    color: #777;
    margin-bottom: 3px;
}
.log-message {
    margin-top: 5px;
    word-break: break-word;
}
.logs-container {
    max-height: 300px;
    overflow-y: auto;
}
</style>
