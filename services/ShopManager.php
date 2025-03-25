<?php
/**
 * Servicio de gestión de tiendas para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShopManager
{
    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var CommunicationService
     */
    protected $communicationService;

    public function __construct()
    {
        $this->logService = new LogService();
        $this->communicationService = new CommunicationService();
    }

    /**
     * Obtiene todas las tiendas registradas
     *
     * @return array Listado de tiendas
     */
    public function getAllShops()
    {
        $shops = Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            ORDER BY `name` ASC
        ');

        if (!$shops) {
            return [];
        }

        return $shops;
    }

    /**
     * Obtiene una tienda por su ID
     *
     * @param int $id_shop ID de la tienda
     * @return array|false Datos de la tienda o false si no existe
     */
    public function getShopById($id_shop)
    {
        return Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `id_megasync_shop` = '.(int)$id_shop
        );
    }

    /**
     * Añade una nueva tienda
     *
     * @param array $shopData Datos de la tienda
     * @return bool|int ID de la tienda insertada o false si falla
     */
    public function addShop($shopData)
    {
        // Validación de los datos
        if (empty($shopData['name']) || empty($shopData['url']) || empty($shopData['api_key'])) {
            return false;
        }

        // Verificar conexión con la tienda
        $testConnection = $this->testConnection($shopData['url'], $shopData['api_key']);
        if (!$testConnection) {
            return false;
        }

        // Inserción en la base de datos
        $result = Db::getInstance()->insert('megasync_shops', [
            'name' => pSQL($shopData['name']),
            'url' => pSQL($shopData['url']),
            'api_key' => pSQL($shopData['api_key']),
            'sync_stock' => isset($shopData['sync_stock']) ? (int)$shopData['sync_stock'] : 0,
            'sync_stock_batch' => isset($shopData['sync_stock_batch']) ? (int)$shopData['sync_stock_batch'] : 0,
            'sync_price' => isset($shopData['sync_price']) ? (int)$shopData['sync_price'] : 0,
            'price_percentage' => isset($shopData['price_percentage']) ? (float)$shopData['price_percentage'] : 0,
            'sync_base_price_only' => isset($shopData['sync_base_price_only']) ? (int)$shopData['sync_base_price_only'] : 0,
            'order_mode' => isset($shopData['order_mode']) ? (int)$shopData['order_mode'] : 1,
            'fixed_customer_id' => isset($shopData['fixed_customer_id']) ? (int)$shopData['fixed_customer_id'] : 0,
            'conversion_method' => isset($shopData['conversion_method']) ? pSQL($shopData['conversion_method']) : 'automatic',
            'group_orders' => isset($shopData['group_orders']) ? (int)$shopData['group_orders'] : 0,
            'active' => isset($shopData['active']) ? (int)$shopData['active'] : 1,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $id_shop = Db::getInstance()->Insert_ID();
            $this->logService->log('Nueva tienda añadida: ' . $shopData['name'], 'success', 'shop', $id_shop);
            return $id_shop;
        }

        return false;
    }

    /**
     * Actualiza los datos de una tienda existente
     *
     * @param int $id_shop ID de la tienda
     * @param array $shopData Nuevos datos de la tienda
     * @return bool Resultado de la actualización
     */
    public function updateShop($id_shop, $shopData)
    {
        // Validación de los datos
        if (empty($shopData['name']) || empty($shopData['url']) || empty($shopData['api_key'])) {
            return false;
        }

        // Verificar que la tienda existe
        $shop = $this->getShopById($id_shop);
        if (!$shop) {
            return false;
        }

        // Actualizar en la base de datos
        $result = Db::getInstance()->update('megasync_shops', [
            'name' => pSQL($shopData['name']),
            'url' => pSQL($shopData['url']),
            'api_key' => pSQL($shopData['api_key']),
            'sync_stock' => isset($shopData['sync_stock']) ? (int)$shopData['sync_stock'] : 0,
            'sync_stock_batch' => isset($shopData['sync_stock_batch']) ? (int)$shopData['sync_stock_batch'] : 0,
            'sync_price' => isset($shopData['sync_price']) ? (int)$shopData['sync_price'] : 0,
            'price_percentage' => isset($shopData['price_percentage']) ? (float)$shopData['price_percentage'] : 0,
            'sync_base_price_only' => isset($shopData['sync_base_price_only']) ? (int)$shopData['sync_base_price_only'] : 0,
            'order_mode' => isset($shopData['order_mode']) ? (int)$shopData['order_mode'] : 1,
            'fixed_customer_id' => isset($shopData['fixed_customer_id']) ? (int)$shopData['fixed_customer_id'] : 0,
            'conversion_method' => isset($shopData['conversion_method']) ? pSQL($shopData['conversion_method']) : 'automatic',
            'group_orders' => isset($shopData['group_orders']) ? (int)$shopData['group_orders'] : 0,
            'active' => isset($shopData['active']) ? (int)$shopData['active'] : 1,
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_megasync_shop = ' . (int)$id_shop);

        if ($result) {
            $this->logService->log('Tienda actualizada: ' . $shopData['name'], 'success', 'shop', $id_shop);
            return true;
        }

        return false;
    }

    /**
     * Elimina una tienda
     *
     * @param int $id_shop ID de la tienda a eliminar
     * @return bool Resultado de la eliminación
     */
    public function deleteShop($id_shop)
    {
        // Verificar que la tienda existe
        $shop = $this->getShopById($id_shop);
        if (!$shop) {
            return false;
        }

        // Eliminar de la base de datos
        $result = Db::getInstance()->delete('megasync_shops', 'id_megasync_shop = ' . (int)$id_shop);

        if ($result) {
            $this->logService->log('Tienda eliminada: ' . $shop['name'], 'success', 'shop', $id_shop);
            return true;
        }

        return false;
    }

    /**
     * Activa o desactiva una tienda
     *
     * @param int $id_shop ID de la tienda
     * @param bool $active Estado de activación
     * @return bool Resultado de la actualización
     */
    public function toggleShopActive($id_shop, $active)
    {
        // Verificar que la tienda existe
        $shop = $this->getShopById($id_shop);
        if (!$shop) {
            return false;
        }

        // Actualizar estado
        $result = Db::getInstance()->update('megasync_shops', [
            'active' => (int)$active,
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_megasync_shop = ' . (int)$id_shop);

        if ($result) {
            $status = $active ? 'activada' : 'desactivada';
            $this->logService->log('Tienda ' . $status . ': ' . $shop['name'], 'success', 'shop', $id_shop);
            return true;
        }

        return false;
    }

    /**
     * Prueba la conexión con una tienda
     *
     * @param string $url URL de la tienda
     * @param string $api_key Clave API para la conexión
     * @return bool Resultado de la prueba
     */
    public function testConnection($url, $api_key)
    {
        try {
            $result = $this->communicationService->sendRequest(
                $url,
                'test',
                ['api_key' => $api_key]
            );

            if (isset($result['status']) && $result['status'] === 'success') {
                $this->logService->log('Conexión exitosa con tienda: ' . $url, 'success', 'connection');
                return true;
            }

            $this->logService->log('Error de conexión con tienda: ' . $url, 'error', 'connection');
            return false;
        } catch (Exception $e) {
            $this->logService->log('Excepción en conexión con tienda: ' . $e->getMessage(), 'error', 'connection');
            return false;
        }
    }

    /**
     * Obtiene todas las tiendas activas
     *
     * @return array Listado de tiendas activas
     */
    public function getActiveShops()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `active` = 1
            ORDER BY `name` ASC
        ');
    }

    /**
     * Obtiene las tiendas con sincronización de stock activada
     *
     * @return array Listado de tiendas
     */
    public function getStockSyncShops()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `active` = 1 AND `sync_stock` = 1
            ORDER BY `name` ASC
        ');
    }

/**
     * Obtiene las tiendas con sincronización de precios activada
     *
     * @return array Listado de tiendas
     */
    public function getPriceSyncShops()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `active` = 1 AND `sync_price` = 1
            ORDER BY `name` ASC
        ');
    }

    /**
     * Obtiene las tiendas según su método de conversión
     *
     * @param string $method Método de conversión (automatic, manual, cron)
     * @return array Listado de tiendas
     */
    public function getShopsByConversionMethod($method)
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `active` = 1 AND `conversion_method` = "'.pSQL($method).'"
            ORDER BY `name` ASC
        ');
    }

    /**
     * Obtiene las tiendas que tienen habilitado el agrupamiento de pedidos
     *
     * @return array Listado de tiendas
     */
    public function getGroupOrdersShops()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_shops`
            WHERE `active` = 1 AND `group_orders` = 1
            ORDER BY `name` ASC
        ');
    }
}
