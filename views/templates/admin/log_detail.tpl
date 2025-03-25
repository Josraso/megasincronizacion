{*
* Detalle de log
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-search"></i> {l s='Detalle de Log' mod='megasincronizacion'} #{$log.id_megasync_log}
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-info-circle"></i> {l s='Información General' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='ID:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <p class="form-control-static">{$log.id_megasync_log}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Fecha:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <p class="form-control-static">{$log.date_add|date_format:'%d/%m/%Y %H:%M:%S'}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Tipo:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <p class="form-control-static">{$type_badge}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Categoría:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <p class="form-control-static">{$log.category}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Empleado:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <p class="form-control-static">{$log.employee_name|default:'-'}</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Mensaje:' mod='megasincronizacion'}</label>
                            <div class="col-lg-9">
                                <div class="well">
                                    {$log.message|nl2br}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            {if !empty($related_info) || !empty($related_links)}
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-link"></i> {l s='Información Relacionada' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        {if !empty($log.id_related)}
                            <div class="form-group">
                                <label>{l s='ID Relacionado:' mod='megasincronizacion'}</label>
                                <p class="form-control-static">{$log.id_related}</p>
                            </div>
                        {/if}
                        
                        {if !empty($related_info.shop)}
                            <div class="form-group">
                                <label>{l s='Tienda:' mod='megasincronizacion'}</label>
                                <p class="form-control-static">
                                    <a href="{$related_links.shop|escape:'html':'UTF-8'}" class="btn btn-default btn-xs">
                                        <i class="icon icon-shopping-cart"></i> {$related_info.shop.name}
                                    </a>
                                </p>
                            </div>
                        {/if}
                        
                        {if !empty($related_info.order)}
                            <div class="form-group">
                                <label>{l s='Pedido:' mod='megasincronizacion'}</label>
                                <p class="form-control-static">
                                    <a href="{$related_links.order|escape:'html':'UTF-8'}" class="btn btn-default btn-xs">
                                        <i class="icon icon-credit-card"></i> {$related_info.order.reference}
                                    </a>
                                </p>
                            </div>
                        {/if}
                        
                        {if !empty($related_info.product)}
                            <div class="form-group">
                                <label>{l s='Producto:' mod='megasincronizacion'}</label>
                                <p class="form-control-static">
                                    <a href="{$related_links.product|escape:'html':'UTF-8'}" class="btn btn-default btn-xs">
                                        <i class="icon icon-shopping-cart"></i> {$related_info.product.name}
                                    </a>
                                </p>
                            </div>
                        {/if}
                    </div>
                </div>
            {/if}
            
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-filter"></i> {l s='Filtros Relacionados' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="list-group">
                        <a href="{$current_url|escape:'html':'UTF-8'}&type={$log.type}" class="list-group-item">
                            <i class="icon icon-filter"></i> {l s='Ver todos los logs de tipo' mod='megasincronizacion'} <strong>{$log.type}</strong>
                        </a>
                        
                        <a href="{$current_url|escape:'html':'UTF-8'}&category={$log.category}" class="list-group-item">
                            <i class="icon icon-filter"></i> {l s='Ver todos los logs de categoría' mod='megasincronizacion'} <strong>{$log.category}</strong>
                        </a>
                        
                        {if !empty($log.id_related)}
                            <a href="{$current_url|escape:'html':'UTF-8'}&id_related={$log.id_related}" class="list-group-item">
                                <i class="icon icon-filter"></i> {l s='Ver todos los logs relacionados con ID' mod='megasincronizacion'} <strong>{$log.id_related}</strong>
                            </a>
                        {/if}
                        
                        {if !empty($log.employee_id)}
                            <a href="{$current_url|escape:'html':'UTF-8'}&employee_id={$log.employee_id}" class="list-group-item">
                                <i class="icon icon-filter"></i> {l s='Ver todos los logs del empleado' mod='megasincronizacion'} <strong>{$log.employee_name}</strong>
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel-footer">
        <a href="{$current_url|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="icon icon-arrow-left"></i> {l s='Volver a la lista de logs' mod='megasincronizacion'}
        </a>
    </div>
</div>