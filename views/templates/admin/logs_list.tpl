{*
* Lista de logs
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-list"></i> {l s='Registros de Actividad' mod='megasincronizacion'}
        
        <div class="panel-heading-action">
            <a href="{$stats_link|escape:'html':'UTF-8'}" class="btn btn-info">
                <i class="icon icon-bar-chart"></i> {l s='Ver Estadísticas' mod='megasincronizacion'}
            </a>
            <a href="{$export_link|escape:'html':'UTF-8'}" class="btn btn-default" target="_blank">
                <i class="icon icon-download"></i> {l s='Exportar CSV' mod='megasincronizacion'}
            </a>
            <button id="clear-logs-btn" class="btn btn-danger">
                <i class="icon icon-eraser"></i> {l s='Limpiar Logs' mod='megasincronizacion'}
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <form id="filter-form" class="form-inline" action="{$current_url|escape:'html':'UTF-8'}" method="get">
                <input type="hidden" name="configure" value="{$module_name|escape:'html':'UTF-8'}">
                <input type="hidden" name="tab" value="logs">
                
                <div class="panel">
                    <div class="panel-heading">
                        <i class="icon icon-filter"></i> {l s='Filtros' mod='megasincronizacion'}
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>{l s='Tipo:' mod='megasincronizacion'}</label>
                                <select name="type" class="form-control">
                                    <option value="">{l s='-- Todos los tipos --' mod='megasincronizacion'}</option>
                                    {foreach from=$log_types item=type}
                                        <option value="{$type}" {if isset($filters.type) && $filters.type == $type}selected{/if}>{$type}</option>
                                    {/foreach}
                                </select>
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='Categoría:' mod='megasincronizacion'}</label>
                                <select name="category" class="form-control">
                                    <option value="">{l s='-- Todas las categorías --' mod='megasincronizacion'}</option>
                                    {foreach from=$log_categories item=category}
                                        <option value="{$category}" {if isset($filters.category) && $filters.category == $category}selected{/if}>{$category}</option>
                                    {/foreach}
                                </select>
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='ID Relacionado:' mod='megasincronizacion'}</label>
                                <input type="text" name="id_related" class="form-control" value="{if isset($filters.id_related)}{$filters.id_related}{/if}">
                            </div>
                            
                            <div class="col-md-3 form-group">
                                <label>{l s='Mensaje:' mod='megasincronizacion'}</label>
                                <input type="text" name="message" class="form-control" value="{if isset($filters.search)}{$filters.search}{/if}">
                            </div>
                        </div>
                        
                        <div class="row" style="margin-top: 15px;">
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
    
    {if empty($logs)}
        <div class="alert alert-info">
            <p>{l s='No hay logs que coincidan con los criterios de búsqueda.' mod='megasincronizacion'}</p>
        </div>
    {else}
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{l s='ID' mod='megasincronizacion'}</th>
                        <th>{l s='Fecha' mod='megasincronizacion'}</th>
                        <th>{l s='Tipo' mod='megasincronizacion'}</th>
                        <th>{l s='Categoría' mod='megasincronizacion'}</th>
                        <th>{l s='Mensaje' mod='megasincronizacion'}</th>
                        <th>{l s='ID Relacionado' mod='megasincronizacion'}</th>
                        <th>{l s='Empleado' mod='megasincronizacion'}</th>
                        <th class="text-right">{l s='Acciones' mod='megasincronizacion'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$logs item=log}
                        <tr>
                            <td>{$log.id_megasync_log}</td>
                            <td>{$log.date_add|date_format:'%d/%m/%Y %H:%M:%S'}</td>
                            <td>
                                {if $log.type == 'success'}
                                    <span class="label label-success">{l s='Éxito' mod='megasincronizacion'}</span>
                                {elseif $log.type == 'error'}
                                    <span class="label label-danger">{l s='Error' mod='megasincronizacion'}</span>
                                {elseif $log.type == 'warning'}
                                    <span class="label label-warning">{l s='Advertencia' mod='megasincronizacion'}</span>
                                {else}
                                    <span class="label label-info">{l s='Info' mod='megasincronizacion'}</span>
                                {/if}
                            </td>
                            <td>{$log.category}</td>
                            <td class="log-message">{$log.message|truncate:100:"..."}</td>
                            <td>{$log.id_related}</td>
                            <td>{$log.employee_name|default:'-'}</td>
                            <td class="text-right">
                                <a href="{$log_link|escape:'html':'UTF-8'}{$log.id_megasync_log}" class="btn btn-default btn-xs" title="{l s='Ver detalles' mod='megasincronizacion'}">
                                    <i class="icon icon-search"></i>
                                </a>
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
    
    // Limpiar logs
    $('#clear-logs-btn').click(function() {
        if (confirm('{l s='¿Está seguro de que desea eliminar todos los logs? Esta acción no se puede deshacer.' js=1 mod='megasincronizacion'}')) {
            $.ajax({
                url: '{$current_url|escape:'javascript':'UTF-8'}&ajax=1&action=ClearLogs',
                data: { confirm: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccessMessage(response.message || '{l s='Logs eliminados correctamente' js=1 mod='megasincronizacion'}');
                        // Recargar la página para actualizar la lista
                        location.reload();
                    } else {
                        showErrorMessage(response.message || '{l s='Error al eliminar los logs' js=1 mod='megasincronizacion'}');
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

<style type="text/css">
.log-message {
    max-width: 350px;
    word-break: break-word;
}
</style>