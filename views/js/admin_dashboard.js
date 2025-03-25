/**
 * JavaScript para el panel de control de MegaSincronización
 */

// Variables globales
var activityChart = null;

$(document).ready(function() {
    // Inicializar gráfico al cargar la página si existe el contenedor
    if ($('#activity-chart').length > 0) {
        var activityData = window.activityData || {};
        initActivityChart(activityData);
    }
    
    // Botón de actualizar estadísticas
    $('#refresh-stats-btn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).find('i').addClass('icon-spin');
        
        $.ajax({
            url: window.refreshStatsUrl || (currentIndex + '&ajax=1&action=RefreshStats'),
            dataType: 'json',
            success: function(data) {
                updateDashboardStats(data);
                showSuccessMessage(window.refreshSuccessMsg || 'Estadísticas actualizadas correctamente');
            },
            error: function() {
                showErrorMessage(window.refreshErrorMsg || 'Error al actualizar las estadísticas');
            },
            complete: function() {
                btn.prop('disabled', false).find('i').removeClass('icon-spin');
            }
        });
    });
    
    // Inicializar tooltips
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Botones de acciones rápidas
    $('.quick-action-btn').click(function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var url = btn.data('url');
        var confirmMsg = btn.data('confirm');
        
        // Si hay mensaje de confirmación, mostrar diálogo
        if (confirmMsg && !confirm(confirmMsg)) {
            return false;
        }
        
        // Realizar la acción
        if (url) {
            btn.prop('disabled', true).find('i').addClass('icon-spin');
            
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccessMessage(response.message || 'Acción completada correctamente');
                        
                        // Si hay callback, ejecutarlo
                        if (btn.data('callback')) {
                            window[btn.data('callback')](response);
                        }
                        
                        // Si hay refresco, actualizar la página
                        if (btn.data('refresh') === 'true') {
                            location.reload();
                        }
                    } else {
                        showErrorMessage(response.message || 'Error al ejecutar la acción');
                    }
                },
                error: function() {
                    showErrorMessage('Error de comunicación con el servidor');
                },
                complete: function() {
                    btn.prop('disabled', false).find('i').removeClass('icon-spin');
                }
            });
        }
    });
    
    // Test de conexión para todas las tiendas
    $('#test-all-connections-btn').click(function() {
        testAllConnections();
    });
});

/**
 * Inicializa el gráfico de actividad
 */
function initActivityChart(data) {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }
    
    var ctx = document.getElementById('activity-chart');
    if (!ctx) return;
    
    var labels = [];
    var values = [];
    
    // Preparar datos para el gráfico
    if (typeof data === 'object') {
        Object.keys(data).forEach(function(key) {
            labels.push(key);
            values.push(data[key]);
        });
    }
    
    // Configuración del gráfico
    var config = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Actividad',
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
    
    // Crear el gráfico
    activityChart = new Chart(ctx, config);
}

/**
 * Actualiza las estadísticas del dashboard
 */
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
    if (data.logs && data.logs.daily && activityChart) {
        var labels = [];
        var values = [];
        
        Object.keys(data.logs.daily).forEach(function(key) {
            labels.push(key);
            values.push(data.logs.daily[key]);
        });
        
        activityChart.data.labels = labels;
        activityChart.data.datasets[0].data = values;
        activityChart.update();
    }
}

/**
 * Prueba la conexión con todas las tiendas
 */
function testAllConnections() {
    var btn = $('#test-all-connections-btn');
    if (!btn.length) return;
    
    btn.prop('disabled', true).find('i').addClass('icon-spin');
    
    $.ajax({
        url: window.testAllConnectionsUrl || (currentIndex + '&ajax=1&action=TestAllConnections'),
        dataType: 'json',
        success: function(response) {
            if (response.status === 'completed') {
                // Mostrar resultados
                var results = response.results || {};
                var success = 0;
                var errors = 0;
                
                Object.keys(results).forEach(function(shopId) {
                    var shop = results[shopId];
                    var statusIcon = shop.status === 'success' ? 'check text-success' : 'times text-danger';
                    
                    // Actualizar indicador visual en la tabla
                    $('.shop-connection-status[data-shop-id="' + shopId + '"]')
                        .html('<i class="icon icon-' + statusIcon + '"></i>')
                        .attr('title', shop.message)
                        .attr('data-original-title', shop.message);
                    
                    if (shop.status === 'success') success++;
                    else errors++;
                });
                
                // Mostrar mensaje de resumen
                if (errors === 0) {
                    showSuccessMessage('Todas las tiendas conectadas correctamente (' + success + ')');
                } else {
                    showWarningMessage('Conexión completada: ' + success + ' correctas, ' + errors + ' con errores');
                }
            } else {
                showErrorMessage(response.message || 'Error al probar las conexiones');
            }
        },
        error: function() {
            showErrorMessage('Error de comunicación con el servidor');
        },
        complete: function() {
            btn.prop('disabled', false).find('i').removeClass('icon-spin');
            
            // Reinicializar tooltips
            if (typeof $.fn.tooltip === 'function') {
                $('[data-toggle="tooltip"]').tooltip('dispose').tooltip();
            }
        }
    });
}
