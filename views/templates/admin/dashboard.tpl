{*
* Plantilla principal para el panel de control del módulo Megasincronización
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-refresh"></i> {l s='Mega Sincronización - Panel de Control' mod='megasincronizacion'}
    </div>
    
    <div class="row dashboard-stats">
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-shopping-cart"></i> {l s='Tiendas' mod='megasincronizacion'}
                </div>
                <div class="panel-body text-center">
                    <span class="stat-value">{$dashboardStats.activeShops}</span>
                    <p>{l s='Tiendas Activas' mod='megasincronizacion'}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-exchange"></i> {l s='Logs' mod='megasincronizacion'}
                </div>
                <div class="panel-body text-center">
                    <span class="stat-value">{$dashboardStats.logs.total}</span>
                    <p>{l s='Registros Recientes' mod='megasincronizacion'}</p>
                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-xs-4 text-center">
                            <span class="label label-success">{$dashboardStats.logs.success}</span>
                            <p class="small">{l s='Éxitos' mod='megasincronizacion'}</p>
                        </div>
                        <div class="col-xs-4 text-center">
                            <span class="label label-warning">{$dashboardStats.logs.warnings}</span>
                            <p class="small">{l s='Advertencias' mod='megasincronizacion'}</p>
                        </div>
                        <div class="col-xs-4 text-center">
                            <span class="label label-danger">{$dashboardStats.logs.errors}</span>
                            <p class="small">{l s='Errores' mod='megasincronizacion'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-credit-card"></i> {l s='Pedidos' mod='megasincronizacion'}
                </div>
                <div class="panel-body text-center">
                    <span class="stat-value">{$dashboardStats.orders.total}</span>
                    <p>{l s='Pedidos Recientes' mod='megasincronizacion'}</p>
                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-xs-4 text-center">
                            <span class="label label-primary">{$dashboardStats.orders.by_status.pending}</span>
                            <p class="small">{l s='Pendientes' mod='megasincronizacion'}</p>
                        </div>
                        <div class="col-xs-4 text-center">
                            <span class="label label-info">{$dashboardStats.orders.by_status.processing}</span>
                            <p class="small">{l s='Procesando' mod='megasincronizacion'}</p>
                        </div>
                        <div class="col-xs-4 text-center">
                            <span class="label label-success">{$dashboardStats.orders.by_status.imported}</span>
                            <p class="small">{l s='Importados' mod='megasincronizacion'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-cogs"></i> {l s='Estado' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <ul class="list-unstyled status-list">
                        <li>
                            <i class="icon {if $configurationStatus.generalConfig}icon-check text-success{else}icon-times text-danger{/if}"></i>
                            {l s='Configuración General' mod='megasincronizacion'}
                        </li>
                        <li>
                            <i class="icon {if $configurationStatus.shopsConfigured}icon-check text-success{else}icon-times text-danger{/if}"></i>
                            {l s='Tiendas Configuradas' mod='megasincronizacion'}
                        </li>
                        <li>
                            <i class="icon {if $configurationStatus.stockConfigured}icon-check text-success{else}icon-times text-warning{/if}"></i>
                            {l s='Sincronización Stock' mod='megasincronizacion'}
                        </li>
                        <li>
						<i class="icon {if $configurationStatus.priceConfigured}icon-check text-success{else}icon-times text-warning{/if}"></i>
                            {l s='Sincronización Precios' mod='megasincronizacion'}
                        </li>
                        <li>
                            <i class="icon {if $configurationStatus.cronConfigured}icon-check text-success{else}icon-times text-warning{/if}"></i>
                            {l s='CRON Configurado' mod='megasincronizacion'}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-bar-chart"></i> {l s='Actividad Reciente' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div id="activity-chart" style="height: 250px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-warning"></i> {l s='Tareas Pendientes' mod='megasincronizacion'}
                    <span class="badge">{count($pendingTasks)}</span>
                </div>
                <div class="panel-body">
                    {if count($pendingTasks) > 0}
                        <div class="list-group">
                            {foreach from=$pendingTasks item=task}
                                <a href="{if isset($task.link)}{$task.link|escape:'html':'UTF-8'}{else}#{/if}" class="list-group-item">
                                    {if $task.priority == 'high'}
                                        <span class="label label-danger">{l s='Alta' mod='megasincronizacion'}</span>
                                    {elseif $task.priority == 'medium'}
                                        <span class="label label-warning">{l s='Media' mod='megasincronizacion'}</span>
                                    {else}
                                        <span class="label label-info">{l s='Baja' mod='megasincronizacion'}</span>
                                    {/if}
                                    &nbsp;{$task.message}
                                </a>
                            {/foreach}
                        </div>
                    {else}
                        <div class="alert alert-success">
                            <p>{l s='¡Enhorabuena! No hay tareas pendientes.' mod='megasincronizacion'}</p>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-list"></i> {l s='Últimas Entradas de Log' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{l s='Fecha' mod='megasincronizacion'}</th>
                                    <th>{l s='Tipo' mod='megasincronizacion'}</th>
                                    <th>{l s='Mensaje' mod='megasincronizacion'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$recentActivities item=log}
                                    <tr>
                                        <td>{$log.date_add|date_format:'%d/%m/%Y %H:%M'}</td>
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
                                        <td>{$log.message}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer text-right">
                        <a href="index.php?controller=AdminMegaLogs&token={Tools::getAdminTokenLite('AdminMegaLogs')}" class="btn btn-default btn-sm">
                            <i class="icon icon-list"></i> {l s='Ver todos los logs' mod='megasincronizacion'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="icon icon-wrench"></i> {l s='Estado del Sistema' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{l s='Comprobación' mod='megasincronizacion'}</th>
                                    <th>{l s='Estado' mod='megasincronizacion'}</th>
                                    <th>{l s='Detalles' mod='megasincronizacion'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$systemHealth item=check}
                                    <tr>
                                        <td>{$check.name}</td>
                                        <td>
                                            {if $check.status == 'ok'}
                                                <span class="label label-success">{l s='OK' mod='megasincronizacion'}</span>
                                            {elseif $check.status == 'warning'}
                                                <span class="label label-warning">{l s='Advertencia' mod='megasincronizacion'}</span>
                                            {else}
                                                <span class="label label-danger">{l s='Error' mod='megasincronizacion'}</span>
                                            {/if}
                                        </td>
                                        <td>{$check.message}</td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="panel-footer">
        <div class="row">
            <div class="col-md-6">
                <a href="index.php?controller=AdminMegaShops&token={Tools::getAdminTokenLite('AdminMegaShops')}" class="btn btn-default">
                    <i class="icon icon-shopping-cart"></i> {l s='Gestionar Tiendas' mod='megasincronizacion'}
                </a>
                <a href="index.php?controller=AdminMegaOrders&token={Tools::getAdminTokenLite('AdminMegaOrders')}" class="btn btn-default">
                    <i class="icon icon-credit-card"></i> {l s='Gestionar Pedidos' mod='megasincronizacion'}
                </a>
                <a href="index.php?controller=AdminMegaLogs&token={Tools::getAdminTokenLite('AdminMegaLogs')}" class="btn btn-default">
                    <i class="icon icon-list"></i> {l s='Ver Logs' mod='megasincronizacion'}
                </a>
            </div>
            <div class="col-md-6 text-right">
                <button id="refresh-stats-btn" class="btn btn-primary">
                    <i class="icon icon-refresh"></i> {l s='Actualizar Estadísticas' mod='megasincronizacion'}
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Inicializar gráfico de actividad
    var activityData = {$dashboardStats.logs.daily|json_encode nofilter};
    initActivityChart(activityData);
    
    // Botón de actualizar estadísticas
    $('#refresh-stats-btn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: 'index.php?controller=AdminMegaSincronizacion&ajax=1&action=RefreshStats&token={$smarty.get.token}',
            dataType: 'json',
            success: function(data) {
                // Actualizar estadísticas
                updateDashboardStats(data);
                showSuccessMessage('{l s='Estadísticas actualizadas correctamente' js=1 mod='megasincronizacion'}');
            },
            error: function() {
                showErrorMessage('{l s='Error al actualizar las estadísticas' js=1 mod='megasincronizacion'}');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
});

function initActivityChart(data) {
    // Implementar gráfico con la librería Chart.js
    if (typeof Chart !== 'undefined' && document.getElementById('activity-chart')) {
        var ctx = document.getElementById('activity-chart').getContext('2d');
        var labels = [];
        var values = [];
        
        // Preparar datos para el gráfico
        if (typeof data === 'object') {
            Object.keys(data).forEach(function(key) {
                labels.push(key);
                values.push(data[key]);
            });
        }
        
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '{l s='Actividad' js=1 mod='megasincronizacion'}',
                    data: values,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
}

function updateDashboardStats(data) {
    // Actualizar contadores
    if (data.activeShops !== undefined) {
        $('.dashboard-stats .stat-value').eq(0).text(data.activeShops);
    }
    
    if (data.logs && data.logs.total !== undefined) {
        $('.dashboard-stats .stat-value').eq(1).text(data.logs.total);
        $('.dashboard-stats .label-success').eq(0).text(data.logs.success);
        $('.dashboard-stats .label-warning').eq(0).text(data.logs.warnings);
        $('.dashboard-stats .label-danger').eq(0).text(data.logs.errors);
    }
    
    if (data.orders && data.orders.total !== undefined) {
        $('.dashboard-stats .stat-value').eq(2).text(data.orders.total);
        $('.dashboard-stats .label-primary').eq(0).text(data.orders.by_status.pending);
        $('.dashboard-stats .label-info').eq(0).text(data.orders.by_status.processing);
        $('.dashboard-stats .label-success').eq(1).text(data.orders.by_status.imported);
    }
    
    // Actualizar gráfico si existe
    if (data.logs && data.logs.daily && typeof Chart !== 'undefined') {
        var chartInstance = Chart.getChart(document.getElementById('activity-chart'));
        if (chartInstance) {
            var labels = [];
            var values = [];
            
            Object.keys(data.logs.daily).forEach(function(key) {
                labels.push(key);
                values.push(data.logs.daily[key]);
            });
            
            chartInstance.data.labels = labels;
            chartInstance.data.datasets[0].data = values;
            chartInstance.update();
        }
    }
}

// Función para mostrar mensajes de éxito
function showSuccessMessage(message) {
    if (typeof $.growl !== 'undefined') {
        $.growl.notice({
            title: '',
            message: message,
            duration: 5000
        });
    } else {
        alert(message);
    }
}

// Función para mostrar mensajes de error
function showErrorMessage(message) {
    if (typeof $.growl !== 'undefined') {
        $.growl.error({
            title: '',
            message: message,
            duration: 5000
        });
    } else {
        alert(message);
    }
}
</script>