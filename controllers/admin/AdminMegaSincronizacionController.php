<?php
/**
 * Controlador principal para la administración del módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMegaSincronizacionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        $this->meta_title = 'Mega Sincronización';
        
        parent::__construct();
        
        // No mostrar las acciones estándar
        $this->actions = [];
        $this->list_no_link = true;
        
        // Inicializar servicios
        $this->module->initServices();
    }

    /**
     * Renderiza el panel principal
     */
    public function renderView()
    {
        $this->tpl_view_vars = [
            'module_path' => $this->module->getPathUri(),
            'dashboardStats' => $this->getDashboardStats(),
            'configurationStatus' => $this->getConfigurationStatus(),
            'recentActivities' => $this->getRecentActivities(),
            'pendingTasks' => $this->getPendingTasks(),
            'systemHealth' => $this->getSystemHealthChecks(),
        ];
        
        // MODIFICADO: Generar las URLs para los enlaces a otros controladores usando la ruta absoluta
        $token_logs = Tools::getAdminTokenLite('AdminMegaLogs');
        $token_shops = Tools::getAdminTokenLite('AdminMegaShops');
        $token_orders = Tools::getAdminTokenLite('AdminMegaOrders');

        $this->tpl_view_vars['admin_megaLogs_link'] = 'index.php?controller=AdminMegaLogs&token=' . $token_logs;
        $this->tpl_view_vars['admin_megaShops_link'] = 'index.php?controller=AdminMegaShops&token=' . $token_shops;
        $this->tpl_view_vars['admin_megaOrders_link'] = 'index.php?controller=AdminMegaOrders&token=' . $token_orders;
        
        // AÑADIDO: URL para la actualización de estadísticas por AJAX
        $this->tpl_view_vars['refresh_stats_url'] = 'index.php?controller=AdminMegaSincronizacion&ajax=1&action=RefreshStats&token=' . $this->token;
        
        return parent::renderView();
    }

    /**
     * Obtiene estadísticas para el dashboard
     *
     * @return array Estadísticas
     */
    protected function getDashboardStats()
    {
        $logService = new LogService();
        $shopManager = new ShopManager();
        $orderService = new OrderService();
        
        $stats = [];
        
        // Obtener tiendas activas
        $activeShops = $shopManager->getActiveShops();
        $stats['activeShops'] = count($activeShops);
        
        // Obtener estadísticas de logs
        $logStats = $logService->getLogStats(7); // Últimos 7 días
        $stats['logs'] = [
            'total' => array_sum($logStats['by_type']),
            'errors' => $logStats['by_type']['error'],
            'warnings' => $logStats['by_type']['warning'],
            'success' => $logStats['by_type']['success'],
            'daily' => $logStats['daily']
        ];
        
        // Estadísticas de pedidos sincronizados
        // Nota: Asumimos que OrderService tiene un método para obtener estadísticas de pedidos
        $orderStats = $orderService->getOrderStats(7);
        $stats['orders'] = $orderStats;
        
        return $stats;
    }

    /**
     * Obtiene el estado de configuración del módulo
     *
     * @return array Estado de configuración
     */
    protected function getConfigurationStatus()
    {
        $configStatus = [];
        
        // Verificar configuración general
        $configStatus['generalConfig'] = Configuration::get('MEGASYNC_LIVE_MODE') !== false;
        
        // Verificar tiendas configuradas
        $shopManager = new ShopManager();
        $shops = $shopManager->getAllShops();
        $configStatus['shopsConfigured'] = !empty($shops);
        
        // Verificar configuración de stock
        $stockShops = $shopManager->getStockSyncShops();
        $configStatus['stockConfigured'] = !empty($stockShops);
        
        // Verificar configuración de precios
        $priceShops = $shopManager->getPriceSyncShops();
        $configStatus['priceConfigured'] = !empty($priceShops);
        
        // Verificar configuración CRON
        $configStatus['cronConfigured'] = Configuration::get('MEGASYNC_CRON_TOKEN') !== false;
        
        return $configStatus;
    }

    /**
     * Obtiene las actividades recientes
     *
     * @return array Actividades recientes
     */
    protected function getRecentActivities()
    {
        $logService = new LogService();
        $logs = $logService->getLogs(10, 0); // Últimos 10 logs
        
        return $logs['logs'];
    }

    /**
     * Obtiene las tareas pendientes
     *
     * @return array Tareas pendientes
     */
    protected function getPendingTasks()
    {
        $pendingTasks = [];
        
        // Verificar tiendas no conectadas
        $shopManager = new ShopManager();
        $shops = $shopManager->getAllShops();
        
        foreach ($shops as $shop) {
            $isConnected = $shopManager->testConnection($shop['url'], $shop['api_key']);
            
            if (!$isConnected) {
                $pendingTasks[] = [
                    'type' => 'connection',
                    'message' => 'La tienda ' . $shop['name'] . ' no está conectada correctamente',
                    'id' => $shop['id_megasync_shop'],
                    'priority' => 'high'
                ];
            }
        }
        
        // Verificar configuración incompleta
        $configStatus = $this->getConfigurationStatus();
        
        if (!$configStatus['generalConfig']) {
            $pendingTasks[] = [
                'type' => 'config',
                'message' => 'La configuración general del módulo está incompleta',
                'link' => 'index.php?controller=AdminModules&configure=megasincronizacion&token=' . Tools::getAdminTokenLite('AdminModules'),
                'priority' => 'medium'
            ];
        }
        
        if (!$configStatus['shopsConfigured']) {
            $pendingTasks[] = [
                'type' => 'config',
                'message' => 'No hay tiendas configuradas',
                'link' => 'index.php?controller=AdminMegaShops&token=' . Tools::getAdminTokenLite('AdminMegaShops'),
                'priority' => 'high'
            ];
        }
        
        if (!$configStatus['cronConfigured']) {
            $pendingTasks[] = [
                'type' => 'config',
                'message' => 'La configuración CRON no está establecida',
                'link' => 'index.php?controller=AdminModules&configure=megasincronizacion&token=' . Tools::getAdminTokenLite('AdminModules'),
                'priority' => 'medium'
            ];
        }
        
        return $pendingTasks;
    }

    /**
     * Verifica el estado de salud del sistema
     *
     * @return array Resultados de las comprobaciones
     */
    protected function getSystemHealthChecks()
    {
        $healthChecks = [];
        
        // Verificar permisos de directorio
        $cacheWritable = is_writable(_PS_CACHE_DIR_);
        $healthChecks[] = [
            'name' => 'Permisos de escritura en caché',
            'status' => $cacheWritable ? 'ok' : 'error',
            'message' => $cacheWritable ? 'El directorio de caché tiene permisos correctos' : 'El directorio de caché no tiene permisos de escritura'
        ];
        
        // Verificar conexión a la base de datos
        $dbConnected = Db::getInstance()->connect();
        $healthChecks[] = [
            'name' => 'Conexión a la base de datos',
            'status' => $dbConnected ? 'ok' : 'error',
            'message' => $dbConnected ? 'Conexión a la base de datos establecida correctamente' : 'No se puede conectar a la base de datos'
        ];
        
        // Verificar limite de tiempo de ejecución PHP
        $timeLimit = ini_get('max_execution_time');
        $timeLimitOk = $timeLimit == 0 || $timeLimit >= 120;
        $healthChecks[] = [
            'name' => 'Límite de tiempo de ejecución PHP',
            'status' => $timeLimitOk ? 'ok' : 'warning',
            'message' => $timeLimitOk ? 'Límite de tiempo adecuado: ' . $timeLimit . 's' : 'El límite de tiempo es bajo (' . $timeLimit . 's), podría causar problemas con sincronizaciones grandes'
        ];
        
        // Verificar limite de memoria PHP
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->returnBytes($memoryLimit);
        $memoryLimitOk = $memoryLimitBytes == -1 || $memoryLimitBytes >= 128 * 1024 * 1024;
        $healthChecks[] = [
            'name' => 'Límite de memoria PHP',
            'status' => $memoryLimitOk ? 'ok' : 'warning',
            'message' => $memoryLimitOk ? 'Límite de memoria adecuado: ' . $memoryLimit : 'El límite de memoria es bajo (' . $memoryLimit . '), podría causar problemas con grandes volúmenes de datos'
        ];
        
        // Verificar extensiones PHP necesarias
        $requiredExtensions = ['curl', 'json', 'mbstring', 'xml'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        $extensionsOk = empty($missingExtensions);
        $healthChecks[] = [
            'name' => 'Extensiones PHP requeridas',
            'status' => $extensionsOk ? 'ok' : 'error',
            'message' => $extensionsOk ? 'Todas las extensiones requeridas están instaladas' : 'Faltan las siguientes extensiones: ' . implode(', ', $missingExtensions)
        ];
        
        return $healthChecks;
    }

    /**
     * Convierte un valor de string de memoria (como '128M') a bytes
     *
     * @param string $val Valor a convertir
     * @return int Valor en bytes
     */
    protected function returnBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }

    /**
     * Inicializa el contenido antes de renderizar
     */
    public function initContent()
    {
        parent::initContent();
        
        // Añadir recursos JS y CSS
        $this->addJS([
            $this->module->getPathUri() . 'views/js/chart.min.js',
            $this->module->getPathUri() . 'views/js/admin_dashboard.js'
        ]);
        
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/admin_dashboard.css'
        ]);
    }

    /**
     * Procesa las acciones AJAX
     */
    public function ajaxProcessRefreshStats()
    {
        $stats = $this->getDashboardStats();
        die(json_encode($stats));
    }

    /**
     * Procesa la acción de test de conexión
     */
    public function ajaxProcessTestConnection()
    {
        $shopId = (int)Tools::getValue('shop_id');
        
        if (!$shopId) {
            die(json_encode([
                'status' => 'error',
                'message' => 'ID de tienda no válido'
            ]));
        }
        
        $shopManager = new ShopManager();
        $shop = $shopManager->getShopById($shopId);
        
        if (!$shop) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Tienda no encontrada'
            ]));
        }
        
        $testResult = $shopManager->testConnection($shop['url'], $shop['api_key']);
        
        die(json_encode([
            'status' => $testResult ? 'success' : 'error',
            'message' => $testResult ? 'Conexión exitosa con la tienda ' . $shop['name'] : 'Error al conectar con la tienda ' . $shop['name']
        ]));
    }

    /**
     * Procesa la acción de ejecutar sincronización manual
     */
    public function ajaxProcessRunManualSync()
    {
        $syncType = Tools::getValue('sync_type');
        $shopId = (int)Tools::getValue('shop_id');
        
        // Validar tipo de sincronización
        if (!in_array($syncType, ['stock', 'price', 'order'])) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Tipo de sincronización no válido'
            ]));
        }
        
        // Validar tienda si se especificó
        if ($shopId) {
            $shopManager = new ShopManager();
            $shop = $shopManager->getShopById($shopId);
            
            if (!$shop) {
                die(json_encode([
                    'status' => 'error',
                    'message' => 'Tienda no encontrada'
                ]));
            }
        }
        
        // Ejecutar sincronización según el tipo
        $result = [];
        
        switch ($syncType) {
            case 'stock':
                $stockService = new StockService();
                $result = $stockService->runScheduledStockSync();
                break;
            
            case 'price':
                $priceService = new PriceService();
                $result = $priceService->runScheduledPriceSync();
                break;
            
            case 'order':
                $orderService = new OrderService();
                $result = $orderService->runScheduledOrderSync();
                break;
        }
        
        die(json_encode($result));
    }
}