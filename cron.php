<?php
/**
 * CRON para el módulo MegaSincronización
 */

// Incluir configuración de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Verificar token de seguridad
$token = Tools::getValue('token');
$configToken = Configuration::get('MEGASYNC_CRON_TOKEN');

if (empty($token) || $token !== $configToken) {
    die('Acceso denegado');
}

// Cargar módulo
$module = Module::getInstanceByName('megasincronizacion');

if (!$module) {
    die('Módulo no disponible');
}

// Inicializar servicios
$module->initServices();

// Determinar acción a ejecutar
$action = Tools::getValue('action', 'all');

// Log de inicio
$module->logService->log('Ejecución CRON iniciada: ' . $action, 'info', 'general');

$results = [];

try {
    switch ($action) {
        case 'stock':
            // Sincronizar stock
            if (Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
                $results['stock'] = $module->stockService->runScheduledStockSync();
            } else {
                $results['stock'] = ['status' => 'warning', 'message' => 'Sincronización de stock desactivada'];
            }
            break;
            
        case 'price':
            // Sincronizar precios
            if (Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
                $results['price'] = $module->priceService->runScheduledPriceSync();
            } else {
                $results['price'] = ['status' => 'warning', 'message' => 'Sincronización de precios desactivada'];
            }
            break;
            
        case 'order':
            // Sincronizar pedidos
            if (Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
                $results['order'] = $module->orderService->runScheduledOrderSync();
            } else {
                $results['order'] = ['status' => 'warning', 'message' => 'Sincronización de pedidos desactivada'];
            }
            break;
            
        case 'all':
        default:
            // Ejecutar todas las sincronizaciones
            if (Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
                $results['stock'] = $module->stockService->runScheduledStockSync();
            }
            
            if (Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
                $results['price'] = $module->priceService->runScheduledPriceSync();
            }
            
            if (Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
                $results['order'] = $module->orderService->runScheduledOrderSync();
            }
            break;
    }
    
    // Log de resultados
    $module->logService->log('Ejecución CRON completada: ' . json_encode($results), 'success', 'general');
    
    echo 'CRON ejecutado correctamente';
    echo json_encode($results);
} catch (Exception $e) {
    // Log de error
    $module->logService->log('Error en ejecución CRON: ' . $e->getMessage(), 'error', 'general');
    
    echo 'Error en la ejecución del CRON: ' . $e->getMessage();
}