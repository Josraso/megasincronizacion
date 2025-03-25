<?php
/**
 * 2007-2023 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2023 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Cargar todas las clases y servicios necesarios
require_once _PS_MODULE_DIR_ . 'megasincronizacion/classes/MegasyncShop.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/LogService.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/CommunicationService.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/ShopManager.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/StockService.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/PriceService.php';
require_once _PS_MODULE_DIR_ . 'megasincronizacion/services/OrderService.php';

class Megasincronizacion extends Module
{
    public function __construct()
    {
        $this->name = 'megasincronizacion';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'YourCompany';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mega Sincronización');
        $this->description = $this->l('Módulo para sincronizar productos, precios, stock y pedidos entre tiendas');
        $this->confirmUninstall = $this->l('¿Está seguro de que desea desinstalar este módulo?');
    }

    /**
     * Inicializa los servicios necesarios
     */
    public function initServices()
    {
        // Los servicios ya están cargados al inicio del archivo
        // Este método se mantiene por retrocompatibilidad
    }

    /**
     * Instalar el módulo
     */
    public function install()
    {
        // Instalar SQL y crear tablas
        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionUpdateQuantity') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->installTab();
    }

    /**
     * Desinstalar el módulo
     */
    public function uninstall()
    {
        // Desinstalar SQL y eliminar tablas
        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall() &&
            $this->uninstallTab();
    }

    /**
     * Instala las pestañas de administración
     */
    public function installTab()
    {
        $tabs = [
            [
                'class_name' => 'AdminMegaSincronizacion',
                'visible' => true,
                'name' => 'Mega Sincronización',
                'parent_class_name' => 'SELL',
                'icon' => 'refresh'
            ],
            [
                'class_name' => 'AdminMegaLogs',
                'visible' => true,
                'name' => 'Logs',
                'parent_class_name' => 'AdminMegaSincronizacion'
            ],
            [
                'class_name' => 'AdminMegaShops',
                'visible' => true,
                'name' => 'Tiendas',
                'parent_class_name' => 'AdminMegaSincronizacion'
            ],
            [
                'class_name' => 'AdminMegaOrders',
                'visible' => true,
                'name' => 'Pedidos',
                'parent_class_name' => 'AdminMegaSincronizacion'
            ]
        ];
        
        $languages = Language::getLanguages();
        
        foreach ($tabs as $tabConfig) {
            $tab = new Tab();
            $tab->class_name = $tabConfig['class_name'];
            $tab->module = $this->name;
            $tab->active = $tabConfig['visible'];
            $tab->id_parent = (int)Tab::getIdFromClassName($tabConfig['parent_class_name']);
            
            if (isset($tabConfig['icon'])) {
                $tab->icon = $tabConfig['icon'];
            }
            
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = $tabConfig['name'];
            }
            
            $tab->add();
        }
        
        return true;
    }

    /**
     * Desinstala las pestañas de administración
     */
    public function uninstallTab()
    {
        $tabs = [
            'AdminMegaLogs',
            'AdminMegaShops',
            'AdminMegaOrders',
            'AdminMegaSincronizacion'
        ];
        
        foreach ($tabs as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);
            
            if ($id_tab) {
                $tab = new Tab($id_tab);
                $tab->delete();
            }
        }
        
        return true;
    }

    /**
     * Hook para reaccionar a cambios en productos
     */
    public function hookActionProductUpdate($params)
    {
        if (!Configuration::get('MEGASYNC_LIVE_MODE')) {
            return;
        }
        
        // Obtener el producto actualizado
        $product = $params['product'];
        
        // Sincronizar precios si está habilitado
        if (Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
            $priceService = new PriceService();
            $priceService->syncProductPrice($product->id);
        }
        
        // No sincronizamos stock aquí porque ya hay un hook específico para ello
    }

    /**
     * Hook para reaccionar a cambios en cantidades
     */
    public function hookActionUpdateQuantity($params)
    {
        if (!Configuration::get('MEGASYNC_LIVE_MODE')) {
            return;
        }
        
        // Obtener datos del producto
        $id_product = $params['id_product'];
        $id_product_attribute = $params['id_product_attribute'];
        
        // Sincronizar stock si está habilitado
        if (Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
            $stockService = new StockService();
            $stockService->syncProductStock($id_product, $id_product_attribute);
        }
    }

    /**
     * Hook para reaccionar a cambios en estados de pedidos
     */
    public function hookActionOrderStatusUpdate($params)
    {
        if (!Configuration::get('MEGASYNC_LIVE_MODE')) {
            return;
        }
        
        // Procesar cambio de estado
        $orderService = new OrderService();
        $orderService->processOrderStatusUpdate($params);
    }

    /**
     * Hook para añadir recursos al backoffice
     */
    public function hookDisplayBackOfficeHeader()
    {
        // Añadir recursos JS y CSS globales para el módulo
        $this->context->controller->addJS($this->_path . 'views/js/admin_global.js');
        $this->context->controller->addCSS($this->_path . 'views/css/admin_global.css');
    }

    /**
     * Renderiza la configuración del módulo
     */
    public function getContent()
    {
        $output = '';
        
        // Procesar formulario
        if (Tools::isSubmit('submit' . $this->name)) {
            $live_mode = (bool)Tools::getValue('MEGASYNC_LIVE_MODE');
            $scheduled_stock_sync = (bool)Tools::getValue('MEGASYNC_SCHEDULED_STOCK_SYNC');
            $scheduled_price_sync = (bool)Tools::getValue('MEGASYNC_SCHEDULED_PRICE_SYNC');
            $scheduled_order_sync = (bool)Tools::getValue('MEGASYNC_SCHEDULED_ORDER_SYNC');
            $cron_token = Tools::getValue('MEGASYNC_CRON_TOKEN');
            
            if (empty($cron_token)) {
                $cron_token = md5(uniqid(rand(), true));
            }
            
            Configuration::updateValue('MEGASYNC_LIVE_MODE', $live_mode);
            Configuration::updateValue('MEGASYNC_SCHEDULED_STOCK_SYNC', $scheduled_stock_sync);
            Configuration::updateValue('MEGASYNC_SCHEDULED_PRICE_SYNC', $scheduled_price_sync);
            Configuration::updateValue('MEGASYNC_SCHEDULED_ORDER_SYNC', $scheduled_order_sync);
            Configuration::updateValue('MEGASYNC_CRON_TOKEN', $cron_token);
            
            $output .= $this->displayConfirmation($this->l('Configuración guardada correctamente'));
        }
        
        // Renderizar formulario de configuración
        $output .= $this->renderForm();
        
        return $output;
    }

    /**
     * Renderiza el formulario de configuración
     */
    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Modo Activo'),
                        'name' => 'MEGASYNC_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Activar el módulo en modo producción'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Sincronización Programada de Stock'),
                        'name' => 'MEGASYNC_SCHEDULED_STOCK_SYNC',
                        'is_bool' => true,
                        'desc' => $this->l('Activar la sincronización programada de stock'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Sincronización Programada de Precios'),
                        'name' => 'MEGASYNC_SCHEDULED_PRICE_SYNC',
                        'is_bool' => true,
                        'desc' => $this->l('Activar la sincronización programada de precios'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            ]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Sincronización Programada de Pedidos'),
                        'name' => 'MEGASYNC_SCHEDULED_ORDER_SYNC',
                        'is_bool' => true,
                        'desc' => $this->l('Activar la sincronización programada de pedidos'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Activado')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Desactivado')
                            ]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Token CRON'),
                        'name' => 'MEGASYNC_CRON_TOKEN',
                        'desc' => $this->l('Token para tareas CRON (dejar vacío para generar uno nuevo)'),
                        'size' => 50
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];
        
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];
        
        return $helper->generateForm([$fields_form]);
    }
    
    /**
     * Obtiene los valores actuales para el formulario de configuración
     */
    protected function getConfigFormValues()
    {
        return [
            'MEGASYNC_LIVE_MODE' => Configuration::get('MEGASYNC_LIVE_MODE'),
            'MEGASYNC_SCHEDULED_STOCK_SYNC' => Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC'),
            'MEGASYNC_SCHEDULED_PRICE_SYNC' => Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC'),
            'MEGASYNC_SCHEDULED_ORDER_SYNC' => Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC'),
            'MEGASYNC_CRON_TOKEN' => Configuration::get('MEGASYNC_CRON_TOKEN')
        ];
    }
    
    /**
     * Ejecuta una tarea CRON
     */
    public function runCron($task, $token)
    {
        // Verificar token
        if ($token !== Configuration::get('MEGASYNC_CRON_TOKEN')) {
            return json_encode([
                'status' => 'error',
                'message' => 'Token no válido'
            ]);
        }
        
        // Ejecutar tarea según el tipo
        switch ($task) {
            case 'stock':
                if (!Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
                    return json_encode([
                        'status' => 'error',
                        'message' => 'Sincronización de stock no activada'
                    ]);
                }
                
                $stockService = new StockService();
                return json_encode($stockService->runScheduledStockSync());
                
            case 'price':
                if (!Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
                    return json_encode([
                        'status' => 'error',
                        'message' => 'Sincronización de precios no activada'
                    ]);
                }
                
                $priceService = new PriceService();
                return json_encode($priceService->runScheduledPriceSync());
                
            case 'order':
                if (!Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
                    return json_encode([
                        'status' => 'error',
                        'message' => 'Sincronización de pedidos no activada'
                    ]);
                }
                
                $orderService = new OrderService();
                return json_encode($orderService->runScheduledOrderSync());
                
            case 'all':
                $result = [
                    'status' => 'completed',
                    'tasks' => []
                ];
                
                // Stock
                if (Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
                    $stockService = new StockService();
                    $result['tasks']['stock'] = $stockService->runScheduledStockSync();
                }
                
                // Price
                if (Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
                    $priceService = new PriceService();
                    $result['tasks']['price'] = $priceService->runScheduledPriceSync();
                }
                
                // Order
                if (Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
                    $orderService = new OrderService();
                    $result['tasks']['order'] = $orderService->runScheduledOrderSync();
                }
                
                return json_encode($result);
                
            default:
                return json_encode([
                    'status' => 'error',
                    'message' => 'Tarea no válida'
                ]);
        }
    }
}