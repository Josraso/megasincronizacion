<?php
/**
 * CRON para el m�dulo MegaSincronizaci�n
 */

// Incluir configuraci�n de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Verificar token de seguridad
$token = Tools::getValue('token');
$configToken = Configuration::get('MEGASYNC_CRON_TOKEN');

if (empty($token) || $token !== $configToken) {
    die('Acceso denegado');
}

// Cargar m�dulo
$module = Module::getInstanceByName('megasincronizacion');

if (!$module) {
    die('M�dulo no disponible');
}

// Inicializar servicios
$module->initServices();

// Determinar acci�n a ejecutar
$action = Tools::getValue('action', 'all');

// Log de inicio
$module->logService->log('Ejecuci�n CRON iniciada: ' . $action, 'info', 'general');

$results = [];

try {
    switch ($action) {
        case 'stock':
            // Sincronizar stock
            if (Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
                $results['stock'] = $module->stockService->runScheduledStockSync();
            } else {
                $results['stock'] = ['status' => 'warning', 'message' => 'Sincronizaci�n de stock desactivada'];
            }
            break;
            
        case 'price':
            // Sincronizar precios
            if (Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
                $results['price'] = $module->priceService->runScheduledPriceSync();
            } else {
                $results['price'] = ['status' => 'warning', 'message' => 'Sincronizaci�n de precios desactivada'];
            }
            break;
            
        case 'order':
            // Sincronizar pedidos
            if (Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
                $results['order'] = $module->orderService->runScheduledOrderSync();
            } else {
                $results['order'] = ['status' => 'warning', 'message' => 'Sincronizaci�n de pedidos desactivada'];
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
    $module->logService->log('Ejecuci�n CRON completada: ' . json_encode($results), 'success', 'general');
    
    echo 'CRON ejecutado correctamente';
    echo json_encode($results);
} catch (Exception $e) {
    // Log de error
    $module->logService->log('Error en ejecuci�n CRON: ' . $e->getMessage(), 'error', 'general');
    
    echo 'Error en la ejecuci�n del CRON: ' . $e->getMessage();
}