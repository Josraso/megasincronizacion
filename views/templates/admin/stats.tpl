{*
* Estadísticas de logs
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon icon-bar-chart"></i> {l s='Estadísticas de Logs' mod='megasincronizacion'}
        
        <div class="panel-heading-action">
            <a href="{$current_url|escape:'html':'UTF-8'}" class="btn btn-default">
                <i class="icon icon-list"></i> {l s='Volver a Logs' mod='megasincronizacion'}
            </a>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel-heading">
            <i class="icon icon-filter"></i> {l s='Período' mod='megasincronizacion'}
        </div>
        <div class="panel-body">
            <form id="stats-form" class="form-inline" action="{$current_url|escape:'html':'UTF-8'}" method="get">
                <input type="hidden" name="configure" value="{$module_name|escape:'html':'UTF-8'}">
                <input type="hidden" name="tab" value="logs">
                <input type="hidden" name="logAction" value="stats">
                
                <div class="form-group">
                    <label>{l s='Mostrar estadísticas de los últimos' mod='megasincronizacion'}</label>
                    <select name="days" class="form-control">
                        <option value="7" {if $days == 7}selected{/if}>{l s='7 días' mod='megasincronizacion'}</option>
                        <option value="15" {if $days == 15}selected{/if}>{l s='15 días' mod='megasincronizacion'}</option>
                        <option value="30" {if $days == 30}selected{/if}>{l s='30 días' mod='megasincronizacion'}</option>
                        <option value="60" {if $days == 60}selected{/if}>{l s='60 días' mod='megasincronizacion'}</option>
                        <option value="90" {if $days == 90}selected{/if}>{l s='90 días' mod='megasincronizacion'}</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon icon-refresh"></i> {l s='Actualizar' mod='megasincronizacion'}
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-pie-chart"></i> {l s='Distribución por Tipo' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="stats-summary">
                        <div class="row">
                            <div class="col-xs-12 text-center">
                                <span class="stats-value">{$total_logs}</span>
                                <p>{l s='Logs Totales' mod='megasincronizacion'}</p>
                            </div>
                        </div>
                        <div class="row stats-details">
                            <div class="col-xs-3 text-center">
                                <span class="label label-success">{$stats.by_type.success}</span>
                                <p class="small">{l s='Éxito' mod='megasincronizacion'}</p>
                            </div>
                            <div class="col-xs-3 text-center">
                                <span class="label label-info">{$stats.by_type.info}</span>
                                <p class="small">{l s='Info' mod='megasincronizacion'}</p>
                            </div>
                            <div class="col-xs-3 text-center">
                                <span class="label label-warning">{$stats.by_type.warning}</span>
                                <p class="small">{l s='Advertencia' mod='megasincronizacion'}</p>
                            </div>
                            <div class="col-xs-3 text-center">
                                <span class="label label-danger">{$stats.by_type.error}</span>
                                <p class="small">{l s='Error' mod='megasincronizacion'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="type-chart" style="height: 250px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-area-chart"></i> {l s='Actividad Diaria' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div id="daily-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-pie-chart"></i> {l s='Distribución por Categoría' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div id="category-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon icon-table"></i> {l s='Resumen por Categoría' mod='megasincronizacion'}
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{l s='Categoría' mod='megasincronizacion'}</th>
                                    <th class="text-center">{l s='Logs' mod='megasincronizacion'}</th>
                                    <th class="text-right">{l s='Porcentaje' mod='megasincronizacion'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach from=$stats.by_category key=category item=count}
                                    <tr>
                                        <td>{$category}</td>
                                        <td class="text-center">{$count}</td>
                                        <td class="text-right">
                                            {if $total_logs > 0}
                                                {math equation="round((count/total)*100, 1)" count=$count total=$total_logs}%
                                            {else}
                                                0%
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Datos para los gráficos
    var dailyData = {$daily_data};
    var typesData = {$types_data};
    var categoriesData = {$categories_data};
    
    // Inicializar gráficos
    initDailyChart(dailyData);
    initTypeChart(typesData);
    initCategoryChart(categoriesData);
});

function initDailyChart(data) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    var ctx = document.getElementById('daily-chart');
    if (!ctx) return;
    
    var labels = data.map(function(item) { return item.date; });
    var values = data.map(function(item) { return item.count; });
    
    var config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '{l s='Actividad Diaria' js=1 mod='megasincronizacion'}',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                data: values,
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
    };
    
    new Chart(ctx, config);
}

function initTypeChart(data) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    var ctx = document.getElementById('type-chart');
    if (!ctx) return;
    
    var labels = data.map(function(item) { return item.label; });
    var values = data.map(function(item) { return item.value; });
    var colors = data.map(function(item) { return item.color; });
    
    var config = {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

function initCategoryChart(data) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    var ctx = document.getElementById('category-chart');
    if (!ctx) return;
    
    var labels = data.map(function(item) { return item.label; });
    var values = data.map(function(item) { return item.value; });
    
    // Generar colores aleatorios
    var colors = [];
    for (var i = 0; i < data.length; i++) {
        var hue = (i * 137) % 360; // Distribuir colores uniformemente
        colors.push('hsl(' + hue + ', 70%, 60%)');
    }
    
    var config = {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    };
    
    new Chart(ctx, config);
}
</script>

<style type="text/css">
.stats-value {
    font-size: 32px;
    font-weight: 700;
    color: #25B9D7;
}

.stats-details {
    margin-top: 15px;
    margin-bottom: 15px;
}

.stats-details .label {
    font-size: 14px;
    padding: 5px 10px;
    display: inline-block;
}
</style>