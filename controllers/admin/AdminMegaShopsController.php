<?php
/**
 * Controlador para la gestión de tiendas del módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMegaShopsController extends ModuleAdminController
{
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
        $this->table = 'megasync_shops';
        $this->className = 'MegasyncShop';
        $this->identifier = 'id_megasync_shop';
        $this->lang = false;
        
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('test');
        $this->addRowAction('view');
        
        parent::__construct();
        
        // Inicializar servicios
        $this->shopManager = new ShopManager();
        $this->logService = new LogService();
        
        // Configurar lista
        $this->fields_list = [
            'id_megasync_shop' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Nombre'),
                'filter_key' => 'a!name'
            ],
            'url' => [
                'title' => $this->l('URL'),
                'filter_key' => 'a!url'
            ],
            'sync_stock' => [
                'title' => $this->l('Sync Stock'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'syncstock',
                'class' => 'fixed-width-sm'
            ],
            'sync_price' => [
                'title' => $this->l('Sync Precio'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'syncprice',
                'class' => 'fixed-width-sm'
            ],
            'order_mode' => [
                'title' => $this->l('Modo Pedido'),
                'align' => 'center',
                'callback' => 'getOrderModeText',
                'class' => 'fixed-width-sm'
            ],
            'conversion_method' => [
                'title' => $this->l('Método Conversión'),
                'align' => 'center',
                'callback' => 'getConversionMethodText',
                'class' => 'fixed-width-sm'
            ],
            'active' => [
                'title' => $this->l('Activo'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'status',
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => $this->l('Fecha Creación'),
                'align' => 'center',
                'type' => 'date',
                'filter_key' => 'a!date_add'
            ],
            'date_upd' => [
                'title' => $this->l('Última Actualización'),
                'align' => 'center',
                'type' => 'datetime',
                'filter_key' => 'a!date_upd'
            ]
        ];
        
        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Eliminar seleccionadas'),
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?'),
                'icon' => 'icon-trash'
            ],
            'enableSelection' => [
                'text' => $this->l('Activar seleccionadas'),
                'icon' => 'icon-power-off text-success'
            ],
            'disableSelection' => [
                'text' => $this->l('Desactivar seleccionadas'),
                'icon' => 'icon-power-off text-danger'
            ]
        ];
    }

    /**
     * Renderiza el formulario de edición/creación
     */
    public function renderForm()
    {
        // Clientes para selector de cliente fijo
        $customers = Customer::getCustomers();
        $customerOptions = [];
        foreach ($customers as $customer) {
            $customerOptions[] = [
                'id_option' => $customer['id_customer'],
                'name' => $customer['firstname'] . ' ' . $customer['lastname'] . ' (' . $customer['email'] . ')'
            ];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Tienda'),
                'icon' => 'icon-shopping-cart'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Nombre'),
                    'name' => 'name',
                    'required' => true,
                    'hint' => $this->l('Nombre descriptivo para identificar la tienda')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('URL'),
                    'name' => 'url',
                    'required' => true,
                    'hint' => $this->l('URL completa de la tienda (ej: https://tienda.com)')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('API Key'),
                    'name' => 'api_key',
                    'required' => true,
                    'hint' => $this->l('Clave de API para autenticación')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Sincronizar Stock'),
                    'name' => 'sync_stock',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Activar sincronización de stock para esta tienda')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Procesar Stock por Lotes'),
                    'name' => 'sync_stock_batch',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Activar procesamiento por lotes para grandes volúmenes de stock')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Sincronizar Precios'),
                    'name' => 'sync_price',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Activar sincronización de precios para esta tienda')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Porcentaje Aumento Precio'),
                    'name' => 'price_percentage',
                    'suffix' => '%',
                    'class' => 'fixed-width-sm',
                    'hint' => $this->l('Porcentaje de aumento a aplicar al precio (0 para no aplicar)')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Solo Sincronizar Precio Base'),
                    'name' => 'sync_base_price_only',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Sincronizar solo el precio base del producto')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Modo Pedido'),
                    'name' => 'order_mode',
                    'options' => [
                        'query' => [
                            ['id_option' => 1, 'name' => $this->l('Modo 1: Cliente y direcciones fijas')],
                            ['id_option' => 2, 'name' => $this->l('Modo 2: Conservar direcciones originales')],
                            ['id_option' => 3, 'name' => $this->l('Modo 3: Mixto - Cliente fijo, dirección envío original')]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'hint' => $this->l('Modo de importación de pedidos')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Cliente Fijo'),
                    'name' => 'fixed_customer_id',
                    'options' => [
                        'query' => $customerOptions,
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'hint' => $this->l('Cliente a usar para pedidos en modo 1 o 3')
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Método Conversión'),
                    'name' => 'conversion_method',
                    'options' => [
                        'query' => [
                            ['id_option' => 'automatic', 'name' => $this->l('Automático (tiempo real)')],
                            ['id_option' => 'manual', 'name' => $this->l('Manual')],
                            ['id_option' => 'cron', 'name' => $this->l('Por CRON')]
                        ],
                        'id' => 'id_option',
                        'name' => 'name'
                    ],
                    'hint' => $this->l('Método de conversión de pedidos')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Agrupar Pedidos (CRON)'),
                    'name' => 'group_orders',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Agrupar todos los pedidos en uno solo al procesar por CRON')
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Activo'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                    'hint' => $this->l('Estado de la tienda')
                ]
            ],
            'submit' => [
                'title' => $this->l('Guardar')
            ],
            'buttons' => [
                'test_connection' => [
                    'title' => $this->l('Probar Conexión'),
                    'icon' => 'process-icon-connect',
                    'class' => 'pull-right',
                    'js' => 'testConnection()'
                ]
            ]
        ];
        
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->l('Tienda'),
                'name' => 'checkBoxShopAsso',
                'disable_shared' => true
            ];
        }
        
        return parent::renderForm();
    }

    /**
     * Procesa el formulario de edición/creación
     */
    public function processSave()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        
        $shopData = [
            'name' => Tools::getValue('name'),
            'url' => Tools::getValue('url'),
            'api_key' => Tools::getValue('api_key'),
            'sync_stock' => (int)Tools::getValue('sync_stock'),
            'sync_stock_batch' => (int)Tools::getValue('sync_stock_batch'),
            'sync_price' => (int)Tools::getValue('sync_price'),
            'price_percentage' => (float)Tools::getValue('price_percentage'),
            'sync_base_price_only' => (int)Tools::getValue('sync_base_price_only'),
            'order_mode' => (int)Tools::getValue('order_mode'),
            'fixed_customer_id' => (int)Tools::getValue('fixed_customer_id'),
            'conversion_method' => Tools::getValue('conversion_method'),
            'group_orders' => (int)Tools::getValue('group_orders'),
            'active' => (int)Tools::getValue('active')
        ];
        
        // Validación básica
        if (empty($shopData['name']) || empty($shopData['url']) || empty($shopData['api_key'])) {
            $this->errors[] = $this->l('Los campos Nombre, URL y API Key son obligatorios');
            return false;
        }
        
        // Actualizar o crear tienda
        if ($id_shop) {
            $result = $this->shopManager->updateShop($id_shop, $shopData);
            if ($result) {
                $this->confirmations[] = $this->l('Tienda actualizada correctamente');
            } else {
                $this->errors[] = $this->l('Error al actualizar la tienda');
            }
        } else {
            $id_shop = $this->shopManager->addShop($shopData);
            if ($id_shop) {
                $this->confirmations[] = $this->l('Tienda añadida correctamente');
                // Redireccionar a la lista
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
            } else {
                $this->errors[] = $this->l('Error al añadir la tienda');
            }
        }
        
        return $id_shop;
    }

    /**
     * Procesa la acción de eliminación
     */
    public function processDelete()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        
        if ($id_shop) {
            $shop = $this->shopManager->getShopById($id_shop);
            
            if ($shop && $this->shopManager->deleteShop($id_shop)) {
                $this->confirmations[] = $this->l('Tienda eliminada correctamente');
            } else {
                $this->errors[] = $this->l('Error al eliminar la tienda');
            }
        } else {
            $this->errors[] = $this->l('ID de tienda no válido');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Obtiene el texto descriptivo para el modo de pedido
     */
    public function getOrderModeText($mode)
    {
        $modes = [
            1 => $this->l('Fijo'),
            2 => $this->l('Original'),
            3 => $this->l('Mixto')
        ];
        
        return isset($modes[$mode]) ? $modes[$mode] : $this->l('Desconocido');
    }

    /**
     * Obtiene el texto descriptivo para el método de conversión
     */
    public function getConversionMethodText($method)
    {
        $methods = [
            'automatic' => $this->l('Auto'),
            'manual' => $this->l('Manual'),
            'cron' => $this->l('CRON')
        ];
        
        return isset($methods[$method]) ? $methods[$method] : $this->l('Desconocido');
    }

    /**
     * Añade botones personalizados a la barra de herramientas
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_shop'] = [
                'href' => self::$currentIndex . '&addmegasync_shops&token=' . $this->token,
                'desc' => $this->l('Añadir Tienda'),
                'icon' => 'process-icon-new'
            ];
            
            $this->page_header_toolbar_btn['test_all'] = [
                'href' => '#',
                'desc' => $this->l('Probar Todas'),
                'icon' => 'process-icon-connect',
                'js' => 'testAllConnections()'
            ];
        }
        
        parent::initPageHeaderToolbar();
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
                $this->module->getPathUri() . 'views/js/admin_shops.js'
            ]);
        } elseif ($this->display == 'edit' || $this->display == 'add') {
            // En el formulario
            $this->addJS([
                $this->module->getPathUri() . 'views/js/admin_shop_form.js'
            ]);
        }
    }

    /**
     * Procesa las acciones AJAX
     */
    public function ajaxProcessTestConnection()
    {
        $shopId = (int)Tools::getValue('shop_id');
        $url = Tools::getValue('url');
        $apiKey = Tools::getValue('api_key');
        
        // Si tenemos ID de tienda, obtener datos de la tienda
        if ($shopId) {
            $shop = $this->shopManager->getShopById($shopId);
            
            if (!$shop) {
                die(json_encode([
                    'status' => 'error',
                    'message' => $this->l('Tienda no encontrada')
                ]));
            }
            
            $url = $shop['url'];
            $apiKey = $shop['api_key'];
        }
        
        // Validar datos
        if (empty($url) || empty($apiKey)) {
            die(json_encode([
                'status' => 'error',
                'message' => $this->l('URL y API Key son obligatorios')
            ]));
        }
        
        // Probar conexión
        $testResult = $this->shopManager->testConnection($url, $apiKey);
        
        die(json_encode([
            'status' => $testResult ? 'success' : 'error',
            'message' => $testResult ? $this->l('Conexión exitosa') : $this->l('Error de conexión')
        ]));
    }

    /**
     * Procesa la acción de prueba de conexión para todas las tiendas
     */
    public function ajaxProcessTestAllConnections()
    {
        $shops = $this->shopManager->getAllShops();
        $results = [];
        
        foreach ($shops as $shop) {
            $testResult = $this->shopManager->testConnection($shop['url'], $shop['api_key']);
            
            $results[$shop['id_megasync_shop']] = [
                'id' => $shop['id_megasync_shop'],
                'name' => $shop['name'],
                'status' => $testResult ? 'success' : 'error',
                'message' => $testResult ? $this->l('Conexión exitosa') : $this->l('Error de conexión')
            ];
        }
        
        die(json_encode([
            'status' => 'completed',
            'results' => $results
        ]));
    }

    /**
     * Procesa la acción de activar/desactivar sincronización de stock
     */
    public function processSyncstock()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        $status = (int)Tools::getValue('status');
        
        if (!$id_shop) {
            $this->errors[] = $this->l('ID de tienda no válido');
            return;
        }
        
        $shop = $this->shopManager->getShopById($id_shop);
        if (!$shop) {
            $this->errors[] = $this->l('Tienda no encontrada');
            return;
        }
        
        // Actualizar tienda
        $shopData = $shop;
        $shopData['sync_stock'] = $status;
        
        if ($this->shopManager->updateShop($id_shop, $shopData)) {
            $this->confirmations[] = $status ? 
                $this->l('Sincronización de stock activada') : 
                $this->l('Sincronización de stock desactivada');
        } else {
            $this->errors[] = $this->l('Error al actualizar la tienda');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Procesa la acción de activar/desactivar sincronización de precios
     */
    public function processSyncprice()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        $status = (int)Tools::getValue('status');
        
        if (!$id_shop) {
            $this->errors[] = $this->l('ID de tienda no válido');
            return;
        }
        
        $shop = $this->shopManager->getShopById($id_shop);
        if (!$shop) {
            $this->errors[] = $this->l('Tienda no encontrada');
            return;
        }
        
        // Actualizar tienda
        $shopData = $shop;
        $shopData['sync_price'] = $status;
        
        if ($this->shopManager->updateShop($id_shop, $shopData)) {
            $this->confirmations[] = $status ? 
                $this->l('Sincronización de precios activada') : 
                $this->l('Sincronización de precios desactivada');
        } else {
            $this->errors[] = $this->l('Error al actualizar la tienda');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Procesa la acción de activar/desactivar tienda
     */
    public function processStatus()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        $status = (int)Tools::getValue('status');
        
        if (!$id_shop) {
            $this->errors[] = $this->l('ID de tienda no válido');
            return;
        }
        
        if ($this->shopManager->toggleShopActive($id_shop, $status)) {
            $this->confirmations[] = $status ? 
                $this->l('Tienda activada correctamente') : 
                $this->l('Tienda desactivada correctamente');
        } else {
            $this->errors[] = $this->l('Error al cambiar el estado de la tienda');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Procesa la acción de test
     */
    public function processTest()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        
        if (!$id_shop) {
            $this->errors[] = $this->l('ID de tienda no válido');
            return;
        }
        
        $shop = $this->shopManager->getShopById($id_shop);
        if (!$shop) {
            $this->errors[] = $this->l('Tienda no encontrada');
            return;
        }
        
        $testResult = $this->shopManager->testConnection($shop['url'], $shop['api_key']);
        
        if ($testResult) {
            $this->confirmations[] = $this->l('Conexión exitosa con la tienda') . ' ' . $shop['name'];
        } else {
            $this->errors[] = $this->l('Error al conectar con la tienda') . ' ' . $shop['name'];
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Procesa la acción de ver
     */
    public function renderView()
    {
        $id_shop = (int)Tools::getValue('id_megasync_shop');
        
        if (!$id_shop) {
            $this->errors[] = $this->l('ID de tienda no válido');
            return;
        }
        
        $shop = $this->shopManager->getShopById($id_shop);
        if (!$shop) {
            $this->errors[] = $this->l('Tienda no encontrada');
            return;
        }
        
        // Obtener logs relacionados con esta tienda
        $logService = new LogService();
        $logs = $logService->getLogs(50, 0, ['search' => $shop['name']]);
        
        // Preparar datos para la vista
        $this->tpl_view_vars = [
            'shop' => $shop,
            'logs' => $logs['logs'],
            'order_mode_text' => $this->getOrderModeText($shop['order_mode']),
            'conversion_method_text' => $this->getConversionMethodText($shop['conversion_method']),
            'connection_status' => $this->shopManager->testConnection($shop['url'], $shop['api_key']),
            'sync_links' => [
                'stock' => $this->context->link->getAdminLink('AdminMegaSincronizacion') . '&ajax=1&action=RunManualSync&sync_type=stock&shop_id=' . $id_shop,
                'price' => $this->context->link->getAdminLink('AdminMegaSincronizacion') . '&ajax=1&action=RunManualSync&sync_type=price&shop_id=' . $id_shop,
                'order' => $this->context->link->getAdminLink('AdminMegaSincronizacion') . '&ajax=1&action=RunManualSync&sync_type=order&shop_id=' . $id_shop,
            ]
        ];
        
        return parent::renderView();
    }

    /**
     * Procesa las acciones por lotes
     */
    public function processBulkEnableSelection()
    {
        $shops = $this->getBulkShops();
        
        foreach ($shops as $id_shop) {
            $this->shopManager->toggleShopActive($id_shop, true);
        }
        
        if (!count($this->errors)) {
            $this->confirmations[] = $this->l('Tiendas seleccionadas activadas correctamente');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Procesa las acciones por lotes
     */
    public function processBulkDisableSelection()
    {
        $shops = $this->getBulkShops();
        
        foreach ($shops as $id_shop) {
            $this->shopManager->toggleShopActive($id_shop, false);
        }
        
        if (!count($this->errors)) {
            $this->confirmations[] = $this->l('Tiendas seleccionadas desactivadas correctamente');
        }
        
        // Redireccionar a la lista
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminMegaShops'));
    }

    /**
     * Obtiene las tiendas seleccionadas para acciones por lotes
     */
    protected function getBulkShops()
    {
        $shops = [];
        $shopBox = Tools::getValue('megasync_shopsBox');
        
        if (is_array($shopBox) && !empty($shopBox)) {
            foreach ($shopBox as $id_shop) {
                $shops[] = (int)$id_shop;
            }
        }
        
        return $shops;
    }
}
