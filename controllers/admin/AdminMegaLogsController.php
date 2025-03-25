<?php
/**
 * Controlador para la gestión de logs del módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMegaLogsController extends ModuleAdminController
{
    /**
     * @var LogService
     */
    protected $logService;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'megasync_log';
        $this->className = 'MegasyncLog';
        $this->identifier = 'id_megasync_log';
        $this->lang = false;
        
        parent::__construct();
        
        // Inicializar servicios
        $this->logService = new LogService();
        
        // No necesitamos acciones de edición
        $this->actions = ['view', 'delete'];
        
        // Configurar lista
        $this->fields_list = [
            'id_megasync_log' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'date_add' => [
                'title' => $this->l('Fecha'),
                'align' => 'center',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ],
            'type' => [
                'title' => $this->l('Tipo'),
                'align' => 'center',
                'callback' => 'getLogTypeWithBadge',
                'filter_key' => 'a!type'
            ],
            'category' => [
                'title' => $this->l('Categoría'),
                'align' => 'center',
                'filter_key' => 'a!category'
            ],
            'message' => [
                'title' => $this->l('Mensaje'),
                'filter_key' => 'a!message'
            ],
            'employee_name' => [
                'title' => $this->l('Empleado'),
                'filter_key' => 'a!employee_name'
            ],
            'id_related' => [
                'title' => $this->l('ID Relacionado'),
                'align' => 'center',
                'filter_key' => 'a!id_related'
            ]
        ];
        
        // Acciones por lotes
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar los logs seleccionados?'),
                'icon' => 'icon-trash'
            ]
        ];
    }

    /**
     * Formatea el tipo de log con badge de color
     */
    public function getLogTypeWithBadge($type)
    {
        $badges = [
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info'
        ];
        
        $badgeClass = isset($badges[$type]) ? $badges[$type] : 'default';
        
        return '<span class="badge badge-' . $badgeClass . '">' . $type . '</span>';
    }

    /**
     * Renderiza la vista de detalles de un log
     */
    public function renderView()
    {
        $id_log = (int)Tools::getValue('id_megasync_log');
        
        if (!$id_log) {
            $this->errors[] = $this->l('ID de log no válido');
            return;
        }
        
        // Obtener detalles del log
        $log = Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'megasync_log`
            WHERE `id_megasync_log` = '.(int)$id_log
        );
        
        if (!$log) {
            $this->errors[] = $this->l('Log no encontrado');
            return;
        }
        
        // Obtener información relacionada según la categoría
        $relatedInfo = $this->getRelatedInfo($log);
        
        // Preparar datos para la vista
        $this->tpl_view_vars = [
            'log' => $log,
            'related_info' => $relatedInfo,
            'type_badge' => $this->getLogTypeWithBadge($log['type']),
            'related_links' => $this->getRelatedLinks($log)
        ];
        
        return parent::renderView();
    }

    /**
     * Obtiene información relacionada según la categoría del log
     */
    protected function getRelatedInfo($log)
    {
        $info = [];
        
        if (!empty($log['id_related'])) {
            switch ($log['category']) {
                case 'shop':
                    $shopManager = new ShopManager();
                    $shop = $shopManager->getShopById($log['id_related']);
                    
                    if ($shop) {
                        $info['shop'] = $shop;
                    }
                    break;
                
                case 'order':
                    $orderService = new OrderService();
                    $order = $orderService->getOrderById($log['id_related']);
                    
                    if ($order) {
                        $info['order'] = $order;
                    }
                    break;
                
                case 'product':
                case 'stock':
                case 'price':
                    $product = new Product($log['id_related']);
                    
                    if (Validate::isLoadedObject($product)) {
                        $info['product'] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'reference' => $product->reference,
                            'active' => $product->active
                        ];
                    }
                    break;
            }
        }
        
        return $info;
    }

    /**
     * Obtiene enlaces relacionados con el log
     */
    protected function getRelatedLinks($log)
    {
        $links = [];
        
        if (!empty($log['id_related'])) {
            switch ($log['category']) {
                case 'shop':
                    $links['shop'] = $this->context->link->getAdminLink('AdminMegaShops') . 
                        '&viewmegasync_shops&id_megasync_shop=' . $log['id_related'];
                    break;
                
                case 'order':
                    $links['order'] = $this->context->link->getAdminLink('AdminMegaOrders') . 
                        '&viewmegasync_orders&id_megasync_order=' . $log['id_related'];
                    break;
                
                case 'product':
                case 'stock':
                case 'price':
                    $links['product'] = $this->context->link->getAdminLink('AdminProducts') . 
                        '&updateproduct&id_product=' . $log['id_related'];
                    break;
            }
        }
        
        return $links;
    }

    /**
     * Añade botones personalizados a la barra de herramientas
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['export_csv'] = [
                'href' => self::$currentIndex . '&export=1&token=' . $this->token,
                'desc' => $this->l('Exportar a CSV'),
                'icon' => 'process-icon-export'
            ];
            
            $this->page_header_toolbar_btn['clear_logs'] = [
                'href' => self::$currentIndex . '&clearLogs=1&token=' . $this->token,
                'desc' => $this->l('Limpiar Logs'),
                'icon' => 'process-icon-eraser'
            ];
            
            $this->page_header_toolbar_btn['stats'] = [
                'href' => self::$currentIndex . '&stats=1&token=' . $this->token,
                'desc' => $this->l('Estadísticas'),
                'icon' => 'process-icon-stats'
            ];
        }
        
        parent::initPageHeaderToolbar();
    }

    /**
     * Procesa la acción de exportar a CSV
     */
    public function processExport()
    {
        // Crear filtros a partir de los parámetros de la URL
        $filters = [];
        
        $type = Tools::getValue('type');
        if ($type && in_array($type, LogService::LOG_TYPES)) {
            $filters['type'] = $type;
        }
        
        $category = Tools::getValue('category');
        if ($category && in_array($category, LogService::LOG_CATEGORIES)) {
            $filters['category'] = $category;
        }
        
        $id_related = (int)Tools::getValue('id_related');
        if ($id_related) {
            $filters['id_related'] = $id_related;
        }
        
        $date_from = Tools::getValue('date_from');
        if (Validate::isDate($date_from)) {
            $filters['date_from'] = $date_from;
        }
        
        $date_to = Tools::getValue('date_to');
        if (Validate::isDate($date_to)) {
            $filters['date_to'] = $date_to;
        }
        
        $search = Tools::getValue('message');
        if ($search) {
            $filters['search'] = $search;
        }
        
        // Exportar a CSV
        $csvContent = $this->logService->exportLogsToCSV($filters);
        
        // Enviar archivo al navegador
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=megasync_logs_' . date('Y-m-d') . '.csv');
        
        echo $csvContent;
        exit;
    }

    /**
     * Procesa la acción de limpiar logs
     */
    public function processClearLogs()
    {
        // Solicitar confirmación
        if (!Tools::getValue('confirm')) {
            $this->context->smarty->assign([
                'action' => 'clearLogs',
                'token' => $this->token,
                'message' => $this->l('¿Está seguro de que desea eliminar todos los logs? Esta acción no se puede deshacer.')
            ]);
            
            // Mostrar template de confirmación
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'megasincronizacion/views/templates/admin/confirm.tpl');
        }
        
        // Ejecutar limpieza
        $result = $this->logService->clearAllLogs();
        
        if ($result) {
            $this->confirmations[] = $this->l('Todos los logs han sido eliminados');
        } else {
            $this->errors[] = $this->l('Error al eliminar los logs');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaLogs'));
    }

    /**
     * Procesa la acción de ver estadísticas
     */
    public function processStats()
    {
        $days = (int)Tools::getValue('days', 30);
        if ($days <= 0) {
            $days = 30;
        }
        
        // Obtener estadísticas
        $stats = $this->logService->getLogStats($days);
        
        // Preparar datos para la vista
        $this->context->smarty->assign([
            'stats' => $stats,
            'days' => $days,
            'total_logs' => array_sum($stats['by_type']),
            'daily_data' => json_encode($this->prepareDailyData($stats['daily'])),
            'types_data' => json_encode($this->prepareTypesData($stats['by_type'])),
            'categories_data' => json_encode($this->prepareCategoriesData($stats['by_category'])),
            'page_header_toolbar_title' => $this->l('Estadísticas de Logs')
        ]);
        
        // Añadir recursos
        $this->addCSS([
            $this->module->getPathUri() . 'views/css/admin_stats.css'
        ]);
        
        $this->addJS([
            $this->module->getPathUri() . 'views/js/chart.min.js',
            $this->module->getPathUri() . 'views/js/admin_log_stats.js'
        ]);
        
        // Mostrar template de estadísticas
        $content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'megasincronizacion/views/templates/admin/stats.tpl');
        
        return $content;
    }

    /**
     * Prepara los datos diarios para el gráfico
     */
    protected function prepareDailyData($dailyStats)
    {
        $data = [];
        
        foreach ($dailyStats as $date => $count) {
            $data[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        
        return $data;
    }

    /**
     * Prepara los datos de tipos para el gráfico
     */
    protected function prepareTypesData($typesStats)
    {
        $data = [];
        $colors = [
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8'
        ];
        
        foreach ($typesStats as $type => $count) {
            $data[] = [
                'label' => $type,
                'value' => $count,
                'color' => isset($colors[$type]) ? $colors[$type] : '#6c757d'
            ];
        }
        
        return $data;
    }

    /**
     * Prepara los datos de categorías para el gráfico
     */
    protected function prepareCategoriesData($categoriesStats)
    {
        $data = [];
        
        foreach ($categoriesStats as $category => $count) {
            if ($count > 0) {
                $data[] = [
                    'label' => $category,
                    'value' => $count
                ];
            }
        }
        
        return $data;
    }

    /**
     * Inicializa el contenido antes de renderizar
     */
    public function initContent()
    {
        parent::initContent();
        
        // Añadir recursos JS y CSS
        if (empty($this->display)) {
            // En la lista
            $this->addJS([
                $this->module->getPathUri() . 'views/js/admin_logs.js'
            ]);
        } elseif ($this->display == 'view') {
            // En la vista de detalles
            $this->addCSS([
                $this->module->getPathUri() . 'views/css/admin_log_detail.css'
            ]);
        }
    }

    /**
     * Añade filtros específicos a la consulta
     */
    protected function _buildQuery()
    {
        // Filtrar por tipo si se especifica
        $type = Tools::getValue('type');
        
        if ($type && in_array($type, LogService::LOG_TYPES)) {
            $this->_where .= ' AND a.`type` = "'.pSQL($type).'"';
        }
        
        // Filtrar por categoría si se especifica
        $category = Tools::getValue('category');
        
        if ($category && in_array($category, LogService::LOG_CATEGORIES)) {
            $this->_where .= ' AND a.`category` = "'.pSQL($category).'"';
        }
        
        // Filtrar por fecha si se especifica
        $date_from = Tools::getValue('date_from');
        $date_to = Tools::getValue('date_to');
        
        if (Validate::isDate($date_from)) {
            $this->_where .= ' AND a.`date_add` >= "'.pSQL($date_from).' 00:00:00"';
        }
        
        if (Validate::isDate($date_to)) {
            $this->_where .= ' AND a.`date_add` <= "'.pSQL($date_to).' 23:59:59"';
        }
        
        return parent::_buildQuery();
    }
}
    }