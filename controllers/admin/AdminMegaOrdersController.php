<?php
/**
 * Controlador para la gestión de pedidos del módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMegaOrdersController extends ModuleAdminController
{
    /**
     * @var OrderService
     */
    protected $orderService;
    
    /**
     * @var ShopManager
     */
    protected $shopManager;
    
    /**
     * @var LogService
     */
    protected $logService;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'megasync_orders';
        $this->className = 'MegasyncOrder';
        $this->identifier = 'id_megasync_order';
        $this->lang = false;
        
        $this->addRowAction('view');
        $this->addRowAction('import');
        $this->addRowAction('delete');
        
        parent::__construct();
        
        // Inicializar servicios
        $this->orderService = new OrderService();
        $this->shopManager = new ShopManager();
        $this->logService = new LogService();
        
        // Configurar lista
        $this->fields_list = [
            'id_megasync_order' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'id_shop' => [
                'title' => $this->l('Tienda'),
                'align' => 'center',
                'callback' => 'getShopName',
                'filter_key' => 'a!id_shop'
            ],
            'reference' => [
                'title' => $this->l('Referencia'),
                'filter_key' => 'a!reference'
            ],
            'id_order_origin' => [
                'title' => $this->l('ID Origen'),
                'align' => 'center',
                'filter_key' => 'a!id_order_origin'
            ],
            'id_order_destination' => [
                'title' => $this->l('ID Destino'),
                'align' => 'center',
                'filter_key' => 'a!id_order_destination'
            ],
            'status' => [
                'title' => $this->l('Estado'),
                'align' => 'center',
                'callback' => 'getOrderStatusText',
                'filter_key' => 'a!status'
            ],
            'total_paid' => [
                'title' => $this->l('Total'),
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
                'filter_key' => 'a!total_paid'
            ],
            'date_add' => [
                'title' => $this->l('Fecha'),
                'align' => 'center',
                'type' => 'datetime',
                'filter_key' => 'a!date_add'
            ],
            'imported' => [
                'title' => $this->l('Importado'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'imported',
                'class' => 'fixed-width-sm'
            ]
        ];
        
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?'),
                'icon' => 'icon-trash'
            ],
            'import' => [
                'text' => $this->l('Importar seleccionados'),
                'icon' => 'icon-download'
            ]
        ];
    }

    /**
     * Obtiene el nombre de la tienda
     */
    public function getShopName($id_shop)
    {
        $shop = $this->shopManager->getShopById($id_shop);
        return $shop ? $shop['name'] : $this->l('Desconocida');
    }

    /**
     * Obtiene el texto del estado del pedido
     */
    public function getOrderStatusText($status)
    {
        $statuses = [
            'pending' => $this->l('Pendiente'),
            'processing' => $this->l('Procesando'),
            'imported' => $this->l('Importado'),
            'error' => $this->l('Error'),
            'cancelled' => $this->l('Cancelado')
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $this->l('Desconocido');
    }

    /**
     * Renderiza la vista de un pedido
     */
    public function renderView()
    {
        $id_megasync_order = (int)Tools::getValue('id_megasync_order');
        
        if (!$id_megasync_order) {
            $this->errors[] = $this->l('ID de pedido no válido');
            return;
        }
        
        $order = $this->orderService->getOrderById($id_megasync_order);
        if (!$order) {
            $this->errors[] = $this->l('Pedido no encontrado');
            return;
        }
        
        // Obtener detalles adicionales
        $orderDetails = $this->orderService->getOrderDetails($id_megasync_order);
        $orderHistory = $this->orderService->getOrderHistory($id_megasync_order);
        $shop = $this->shopManager->getShopById($order['id_shop']);
        
        // Obtener pedido origen y destino si existen
        $originOrder = null;
        $destinationOrder = null;
        
        if (!empty($order['id_order_origin'])) {
            $originOrder = $this->orderService->getExternalOrderDetails(
                $order['id_shop'], 
                $order['id_order_origin']
            );
        }
        
        if (!empty($order['id_order_destination'])) {
            try {
                $destinationOrder = new Order($order['id_order_destination']);
                if (!Validate::isLoadedObject($destinationOrder)) {
                    $destinationOrder = null;
                }
            } catch (Exception $e) {
                $destinationOrder = null;
            }
        }
        
        // Preparar datos para la vista
        $this->tpl_view_vars = [
            'order' => $order,
            'order_details' => $orderDetails,
            'order_history' => $orderHistory,
            'shop' => $shop,
            'origin_order' => $originOrder,
            'destination_order' => $destinationOrder,
            'status_text' => $this->getOrderStatusText($order['status']),
            'order_link' => $order['id_order_destination'] ? 
                $this->context->link->getAdminLink('AdminOrders') . '&vieworder&id_order=' . $order['id_order_destination'] : 
                false,
            'shop_link' => $this->context->link->getAdminLink('AdminMegaShops') . '&viewmegasync_shops&id_megasync_shop=' . $order['id_shop'],
            'logs' => $this->logService->getLogs(20, 0, [
                'category' => 'order',
                'id_related' => $id_megasync_order
            ])['logs']
        ];
        
        return parent::renderView();
    }

    /**
     * Procesa la acción de importar pedido
     */
    public function processImport()
    {
        $id_megasync_order = (int)Tools::getValue('id_megasync_order');
        
        if (!$id_megasync_order) {
            $this->errors[] = $this->l('ID de pedido no válido');
            return;
        }
        
        $order = $this->orderService->getOrderById($id_megasync_order);
        if (!$order) {
            $this->errors[] = $this->l('Pedido no encontrado');
            return;
        }
        
        // Verificar si ya está importado
        if ($order['imported']) {
            $this->errors[] = $this->l('Este pedido ya ha sido importado');
            return;
        }
        
        // Importar pedido
        $result = $this->orderService->importOrder($id_megasync_order);
        
        if ($result['status'] === 'success') {
            $this->confirmations[] = $this->l('Pedido importado correctamente');
            
            // Redireccionar a la vista del pedido
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders') . '&viewmegasync_orders&id_megasync_order=' . $id_megasync_order);
        } else {
            $this->errors[] = $this->l('Error al importar el pedido') . ': ' . $result['message'];
            
            // Redireccionar a la lista
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
        }
    }

    /**
     * Procesa la acción de eliminar pedido
     */
    public function processDelete()
    {
        $id_megasync_order = (int)Tools::getValue('id_megasync_order');
        
        if (!$id_megasync_order) {
            $this->errors[] = $this->l('ID de pedido no válido');
            return;
        }
        
        $order = $this->orderService->getOrderById($id_megasync_order);
        if (!$order) {
            $this->errors[] = $this->l('Pedido no encontrado');
            return;
        }
        
        // Eliminar pedido
        $result = $this->orderService->deleteOrder($id_megasync_order);
        
        if ($result) {
            $this->confirmations[] = $this->l('Pedido eliminado correctamente');
        } else {
            $this->errors[] = $this->l('Error al eliminar el pedido');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
    }

    /**
     * Procesa la acción de marcar como importado/no importado
     */
    public function processImported()
    {
        $id_megasync_order = (int)Tools::getValue('id_megasync_order');
        $status = (int)Tools::getValue('status');
        
        if (!$id_megasync_order) {
            $this->errors[] = $this->l('ID de pedido no válido');
            return;
        }
        
        $order = $this->orderService->getOrderById($id_megasync_order);
        if (!$order) {
            $this->errors[] = $this->l('Pedido no encontrado');
            return;
        }
        
        // Actualizar estado de importación
        $result = $this->orderService->updateOrderImportedStatus($id_megasync_order, $status);
        
        if ($result) {
            $this->confirmations[] = $status ? 
                $this->l('Pedido marcado como importado') : 
                $this->l('Pedido marcado como no importado');
        } else {
            $this->errors[] = $this->l('Error al actualizar el estado del pedido');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
    }

    /**
     * Procesa las acciones por lotes
     */
    public function processBulkImport()
    {
        $orders = $this->getBulkOrders();
        $importedCount = 0;
        $errorCount = 0;
        
        foreach ($orders as $id_megasync_order) {
            $order = $this->orderService->getOrderById($id_megasync_order);
            
            // Verificar si ya está importado
            if ($order && !$order['imported']) {
                $result = $this->orderService->importOrder($id_megasync_order);
                
                if ($result['status'] === 'success') {
                    $importedCount++;
                } else {
                    $errorCount++;
                    $this->errors[] = $this->l('Error al importar el pedido') . ' #' . $id_megasync_order . ': ' . $result['message'];
                }
            }
        }
        
        if ($importedCount > 0) {
            $this->confirmations[] = $importedCount . ' ' . $this->l('pedidos importados correctamente');
        }
        
        if ($errorCount > 0) {
            $this->warnings[] = $errorCount . ' ' . $this->l('pedidos no pudieron ser importados');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
    }

    /**
     * Obtiene los pedidos seleccionados para acciones por lotes
     */
    protected function getBulkOrders()
    {
        $orders = [];
        $orderBox = Tools::getValue('megasync_ordersBox');
        
        if (is_array($orderBox) && !empty($orderBox)) {
            foreach ($orderBox as $id_megasync_order) {
                $orders[] = (int)$id_megasync_order;
            }
        }
        
        return $orders;
    }

    /**
     * Añade botones personalizados a la barra de herramientas
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['sync_now'] = [
                'href' => self::$currentIndex . '&syncNow&token=' . $this->token,
                'desc' => $this->l('Sincronizar Ahora'),
                'icon' => 'process-icon-refresh'
            ];
            
            $this->page_header_toolbar_btn['import_all'] = [
                'href' => self::$currentIndex . '&importAll&token=' . $this->token,
                'desc' => $this->l('Importar Todos'),
                'icon' => 'process-icon-download'
            ];
        }
        
        parent::initPageHeaderToolbar();
    }

    /**
     * Procesa la acción de sincronizar ahora
     */
    public function processSyncNow()
    {
        // Ejecutar sincronización manual
        $result = $this->orderService->runScheduledOrderSync();
        
        if (isset($result['status']) && $result['status'] === 'completed') {
            $this->confirmations[] = $this->l('Sincronización completada') . ': ' . 
                (isset($result['new_orders']) ? $result['new_orders'] : 0) . ' ' . $this->l('nuevos pedidos');
        } else {
            $this->errors[] = $this->l('Error en la sincronización') . ': ' . 
                (isset($result['message']) ? $result['message'] : $this->l('Error desconocido'));
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
    }

    /**
     * Procesa la acción de importar todos los pedidos pendientes
     */
    public function processImportAll()
    {
        // Obtener todos los pedidos pendientes
        $pendingOrders = $this->orderService->getPendingOrders();
        
        if (empty($pendingOrders)) {
            $this->warnings[] = $this->l('No hay pedidos pendientes para importar');
            
            // Redireccionar a la lista
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
            return;
        }
        
        $importedCount = 0;
        $errorCount = 0;
        
        foreach ($pendingOrders as $order) {
            $result = $this->orderService->importOrder($order['id_megasync_order']);
            
            if ($result['status'] === 'success') {
                $importedCount++;
            } else {
                $errorCount++;
                $this->errors[] = $this->l('Error al importar el pedido') . ' #' . $order['id_megasync_order'] . ': ' . $result['message'];
            }
        }
        
        if ($importedCount > 0) {
            $this->confirmations[] = $importedCount . ' ' . $this->l('pedidos importados correctamente');
        }
        
        if ($errorCount > 0) {
            $this->warnings[] = $errorCount . ' ' . $this->l('pedidos no pudieron ser importados');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaOrders'));
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
                $this->module->getPathUri() . 'views/js/admin_orders.js'
            ]);
        } elseif ($this->display == 'view') {
            // En la vista de detalles
            $this->addJS([
                $this->module->getPathUri() . 'views/js/admin_order_detail.js'
            ]);
            
            $this->addCSS([
                $this->module->getPathUri() . 'views/css/admin_order_detail.css'
            ]);
        }
    }

    /**
     * Procesa las acciones AJAX
     */
    public function ajaxProcessRefreshOrderStatus()
    {
        $id_megasync_order = (int)Tools::getValue('order_id');
        
        if (!$id_megasync_order) {
            die(json_encode([
                'status' => 'error',
                'message' => $this->l('ID de pedido no válido')
            ]));
        }
        
        $order = $this->orderService->getOrderById($id_megasync_order);
        if (!$order) {
            die(json_encode([
                'status' => 'error',
                'message' => $this->l('Pedido no encontrado')
            ]));
        }
        
        // Obtener datos actualizados de la tienda de origen
        $result = $this->orderService->refreshOrderStatus($id_megasync_order);
        
        die(json_encode($result));
    }

    /**
     * Añade filtros específicos a la consulta
     */
    protected function _buildQuery()
    {
        // Filtrar por estado si se especifica
        $status = Tools::getValue('order_status');
        
        if ($status && in_array($status, ['pending', 'processing', 'imported', 'error', 'cancelled'])) {
            $this->_where .= ' AND a.`status` = "'.pSQL($status).'"';
        }
        
        // Filtrar por tienda si se especifica
        $id_shop = (int)Tools::getValue('id_shop');
        
        if ($id_shop) {
            $this->_where .= ' AND a.`id_shop` = '.(int)$id_shop;
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
