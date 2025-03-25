<?php
/**
 * Clase para gestionar tiendas remotas
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class RemoteShop extends ObjectModel
{
    /**
     * @var int ID único de la tienda remota
     */
    public $id_shop_remote;
    
    /**
     * @var string Nombre de la tienda remota
     */
    public $name;
    
    /**
     * @var string URL de la tienda remota
     */
    public $url;
    
    /**
     * @var string Clave API para autenticación
     */
    public $api_key;
    
    /**
     * @var bool Estado de activación de la tienda
     */
    public $active;
    
    /**
     * @var bool Sincronización de stock activada
     */
    public $sync_stock;
    
    /**
     * @var bool Sincronización de precios activada
     */
    public $sync_price;
    
    /**
     * @var float Porcentaje de incremento de precios
     */
    public $price_increase;
    
    /**
     * @var string Modo de importación de pedidos (fixed, original, mixed)
     */
    public $import_mode;
    
    /**
     * @var int ID del cliente por defecto para pedidos importados
     */
    public $id_customer;
    
    /**
     * @var int ID de la dirección por defecto para pedidos importados
     */
    public $id_address;
    
    /**
     * @var string Modo de conversión de pedidos (auto, manual, cron)
     */
    public $conversion_mode;
    
    /**
     * @var string Fecha de creación de la tienda
     */
    public $date_add;
    
    /**
     * @var string Fecha de última actualización de la tienda
     */
    public $date_upd;

    /**
     * Definición de la estructura del modelo
     *
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'megasync_shop',
        'primary' => 'id_shop_remote',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true, 'size' => 255],
            'api_key' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'sync_stock' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => '0'],
            'sync_price' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => '0'],
            'price_increase' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false, 'default' => '0'],
            'import_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'values' => ['fixed', 'original', 'mixed'], 'default' => 'fixed'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => '0'],
            'id_address' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false, 'default' => '0'],
            'conversion_mode' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'values' => ['auto', 'manual', 'cron'], 'default' => 'manual'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    /**
     * Constructor
     *
     * @param int|null $id_shop_remote ID de la tienda remota
     * @param int|null $id_lang ID del idioma
     * @param int|null $id_shop ID de la tienda actual
     */
    public function __construct($id_shop_remote = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_shop_remote, $id_lang, $id_shop);
    }

    /**
     * Obtener tienda por API key
     *
     * @param string $api_key Clave API
     * @return RemoteShop|false Objeto RemoteShop o false si no se encuentra
     */
    public static function getByApiKey($api_key)
    {
        $query = new DbQuery();
        $query->select('id_shop_remote');
        $query->from('megasync_shop');
        $query->where('api_key = "'.pSQL($api_key).'"');
        $query->where('active = 1');

        $id_shop_remote = Db::getInstance()->getValue($query);
        
        if ($id_shop_remote) {
            return new RemoteShop($id_shop_remote);
        }
        
        return false;
    }

    /**
     * Verificar si una API key corresponde a una tienda desactivada
     *
     * @param string $api_key Clave API
     * @return bool True si la API key corresponde a una tienda desactivada
     */
    public static function isApiKeyForInactiveShop($api_key)
    {
        $query = new DbQuery();
        $query->select('id_shop_remote');
        $query->from('megasync_shop');
        $query->where('api_key = "'.pSQL($api_key).'"');
        $query->where('active = 0');
        
        $id_shop_remote = Db::getInstance()->getValue($query);
        
        return (bool)$id_shop_remote;
    }

    /**
     * Obtener todas las tiendas activas
     *
     * @return array Lista de tiendas activas
     */
    public static function getActiveShops()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('megasync_shop');
        $query->where('active = 1');
        $query->orderBy('name ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Obtener tiendas con sincronización de stock activada
     *
     * @return array Lista de tiendas con sincronización de stock
     */
    public static function getStockSyncShops()
    {
        $query = new DbQuery();
        $query->select('s.*, ss.sync_mode, ss.last_sync');
        $query->from('megasync_shop', 's');
        $query->innerJoin('megasync_stock_sync', 'ss', 's.id_shop_remote = ss.id_shop_remote');
        $query->where('s.active = 1');
        $query->where('ss.active = 1');
        $query->orderBy('s.name ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Obtener tiendas con sincronización de precios activada
     *
     * @return array Lista de tiendas con sincronización de precios
     */
    public static function getPriceSyncShops()
    {
        $query = new DbQuery();
        $query->select('s.*, ps.increase_percentage, ps.sync_mode, ps.last_sync');
        $query->from('megasync_shop', 's');
        $query->innerJoin('megasync_price_sync', 'ps', 's.id_shop_remote = ps.id_shop_remote');
        $query->where('s.active = 1');
        $query->where('ps.active = 1');
        $query->orderBy('s.name ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Obtener tiendas con conversión automática activada
     *
     * @return array Lista de tiendas con conversión automática
     */
    public static function getAutoConvertShops()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('megasync_shop');
        $query->where('active = 1');
        $query->where('conversion_mode = "auto"');
        $query->orderBy('name ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Obtener tiendas con conversión por CRON activada
     *
     * @return array Lista de tiendas con conversión CRON
     */
    public static function getCronConvertShops()
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('megasync_shop');
        $query->where('active = 1');
        $query->where('conversion_mode = "cron"');
        $query->orderBy('name ASC');

        return Db::getInstance()->executeS($query);
    }

    /**
     * Generar una clave API aleatoria
     *
     * @return string Clave API generada
     */
    public static function generateApiKey()
    {
        return Tools::strtolower(md5(uniqid(mt_rand(), true)));
    }

    /**
     * Sobrescribir método add para establecer fechas automáticamente
     *
     * @param bool $auto_date
     * @param bool $null_values
     * @return bool
     */
    public function add($auto_date = true, $null_values = false)
    {
        $this->date_add = date('Y-m-d H:i:s');
        $this->date_upd = date('Y-m-d H:i:s');
        
        $result = parent::add($auto_date, $null_values);
        
        // Si se crea correctamente, añadir registros en las tablas de configuración de sync
        if ($result) {
            // Añadir registro en tabla de sincronización de stock
            Db::getInstance()->insert('megasync_stock_sync', [
                'id_shop_remote' => (int)$this->id,
                'active' => (int)$this->sync_stock,
                'sync_mode' => 'realtime',
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);
            
            // Añadir registro en tabla de sincronización de precios
            Db::getInstance()->insert('megasync_price_sync', [
                'id_shop_remote' => (int)$this->id,
                'active' => (int)$this->sync_price,
                'increase_percentage' => (float)$this->price_increase,
                'sync_mode' => 'realtime',
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $result;
    }

    /**
     * Sobrescribir método update para actualizar fecha
     *
     * @param bool $null_values
     * @return bool
     */
    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        
        $result = parent::update($null_values);
        
        // Si se actualiza correctamente, actualizar registros en las tablas de configuración de sync
        if ($result) {
            // Obtener el ID de la configuración de stock existente
            $stockSyncId = Db::getInstance()->getValue('
                SELECT id_stock_sync 
                FROM `'._DB_PREFIX_.'megasync_stock_sync` 
                WHERE id_shop_remote = '.(int)$this->id
            );
            
            // Actualizar o crear configuración de stock
            if ($stockSyncId) {
                Db::getInstance()->update('megasync_stock_sync', [
                    'active' => (int)$this->sync_stock,
                    'date_upd' => date('Y-m-d H:i:s')
                ], 'id_stock_sync = '.(int)$stockSyncId);
            } else {
                Db::getInstance()->insert('megasync_stock_sync', [
                    'id_shop_remote' => (int)$this->id,
                    'active' => (int)$this->sync_stock,
                    'sync_mode' => 'realtime',
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Obtener el ID de la configuración de precios existente
            $priceSyncId = Db::getInstance()->getValue('
                SELECT id_price_sync 
                FROM `'._DB_PREFIX_.'megasync_price_sync` 
                WHERE id_shop_remote = '.(int)$this->id
            );
            
            // Actualizar o crear configuración de precios
            if ($priceSyncId) {
                Db::getInstance()->update('megasync_price_sync', [
                    'active' => (int)$this->sync_price,
                    'increase_percentage' => (float)$this->price_increase,
                    'date_upd' => date('Y-m-d H:i:s')
                ], 'id_price_sync = '.(int)$priceSyncId);
            } else {
                Db::getInstance()->insert('megasync_price_sync', [
                    'id_shop_remote' => (int)$this->id,
                    'active' => (int)$this->sync_price,
                    'increase_percentage' => (float)$this->price_increase,
                    'sync_mode' => 'realtime',
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        return $result;
    }

    /**
     * Sobrescribir método delete para eliminar registros relacionados
     * 
     * @return bool
     */
    public function delete()
    {
        // Eliminar registros relacionados
        Db::getInstance()->delete('megasync_stock_sync', 'id_shop_remote = '.(int)$this->id);
        Db::getInstance()->delete('megasync_price_sync', 'id_shop_remote = '.(int)$this->id);
        
        // Eliminar logs
        Db::getInstance()->delete('megasync_log', 'id_shop_remote = '.(int)$this->id);
        Db::getInstance()->delete('megasync_stock_log', 'id_shop_remote = '.(int)$this->id);
        Db::getInstance()->delete('megasync_price_log', 'id_shop_remote = '.(int)$this->id);
        
        return parent::delete();
    }

    /**
     * Obtiene estadísticas de pedidos para una tienda
     * 
     * @return array Estadísticas básicas de pedidos
     */
    public function getOrderStats()
    {
        // Total de pedidos
        $total_orders = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `'._DB_PREFIX_.'megasync_order` 
            WHERE id_shop_remote = '.(int)$this->id
        );
        
        // Pedidos pendientes
        $pending_orders = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `'._DB_PREFIX_.'megasync_order` 
            WHERE id_shop_remote = '.(int)$this->id.' 
            AND status = "pending"'
        );
        
        // Pedidos procesados
        $processed_orders = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM `'._DB_PREFIX_.'megasync_order` 
            WHERE id_shop_remote = '.(int)$this->id.' 
            AND status = "processed"'
        );
        
        // Suma total de pedidos
        $total_amount = (float)Db::getInstance()->getValue('
            SELECT SUM(total_amount) 
            FROM `'._DB_PREFIX_.'megasync_order` 
            WHERE id_shop_remote = '.(int)$this->id
        );
        
        return [
            'total_orders' => $total_orders,
            'pending_orders' => $pending_orders,
            'processed_orders' => $processed_orders,
            'total_amount' => $total_amount
        ];
    }

    /**
     * Comprueba la conexión con la tienda remota
     * 
     * @return array Resultado de la prueba de conexión
     */
    public function testConnection()
    {
        try {
            if (!$this->active) {
                return [
                    'success' => false,
                    'message' => 'La tienda está desactivada',
                    'status' => 'inactive'
                ];
            }
            
            // Crear servicio de comunicación
            require_once(_PS_MODULE_DIR_.'megasincronizacion/services/CommunicationService.php');
            $communication = new CommunicationService();
            
            // Datos para la prueba
            $data = [
                'shop_name' => Configuration::get('MEGASYNC_SHOP_NAME'),
                'shop_url' => Context::getContext()->shop->getBaseURL(true),
                'test' => true,
                'timestamp' => time()
            ];
            
            // URL de endpoint de prueba
            $url = rtrim($this->url, '/') . '/modules/megasincronizacion/api/testConnection.php';
            
            // Enviar solicitud
            $result = $communication->sendRequest(
                $data, 
                $url, 
                Configuration::get('MEGASYNC_VERIFY_SSL', true),
                $this->api_key
            );
            
            // Registrar actividad
            Db::getInstance()->insert('megasync_log', [
                'id_shop_remote' => (int)$this->id,
                'action' => 'test_connection',
                'status' => 1,
                'message' => 'Prueba de conexión exitosa',
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => 'Conexión establecida correctamente',
                'response' => $result
            ];
            
        } catch (Exception $e) {
            // Registrar error en logs
            Db::getInstance()->insert('megasync_log', [
                'id_shop_remote' => (int)$this->id,
                'action' => 'test_connection_error',
                'status' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar pedido de prueba a la tienda remota
     * 
     * @return array Resultado del envío del pedido de prueba
     */
    public function sendTestOrder()
    {
        try {
            if (!$this->active) {
                return [
                    'success' => false,
                    'message' => 'La tienda está desactivada',
                    'status' => 'inactive'
                ];
            }
            
            // Crear servicio de comunicación
            require_once(_PS_MODULE_DIR_.'megasincronizacion/services/CommunicationService.php');
            $communication = new CommunicationService();
            
            // URL del endpoint
            $url = rtrim($this->url, '/') . '/modules/megasincronizacion/api/testOrder.php';
            
            // Datos para el pedido de prueba
            $test_id = time();
            $data = [
                'shop_name' => Configuration::get('MEGASYNC_SHOP_NAME'),
                'shop_url' => Context::getContext()->shop->getBaseURL(true),
                'test_id' => $test_id,
                'test' => true
            ];
            
            // Enviar solicitud
            $result = $communication->sendRequest(
                $data, 
                $url, 
                Configuration::get('MEGASYNC_VERIFY_SSL', true),
                $this->api_key
            );
            
            // Registrar actividad
            Db::getInstance()->insert('megasync_log', [
                'id_shop_remote' => (int)$this->id,
                'action' => 'test_order',
                'status' => 1,
                'message' => 'Pedido de prueba enviado correctamente',
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => 'Pedido de prueba enviado correctamente',
                'response' => $result
            ];
            
        } catch (Exception $e) {
            // Registrar error en logs
            Db::getInstance()->insert('megasync_log', [
                'id_shop_remote' => (int)$this->id,
                'action' => 'test_order_error',
                'status' => 0,
                'message' => 'Error: ' . $e->getMessage(),
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}