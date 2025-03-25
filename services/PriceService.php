<?php
/**
 * Servicio de sincronización de precios para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PriceService
{
    /**
     * @var LogService
     */
    protected $logService;

    /**
     * @var CommunicationService
     */
    protected $communicationService;

    /**
     * @var ShopManager
     */
    protected $shopManager;

    public function __construct()
    {
        $this->logService = new LogService();
        $this->communicationService = new CommunicationService();
        $this->shopManager = new ShopManager();
    }

    /**
     * Sincroniza el precio de un producto específico con todas las tiendas configuradas
     *
     * @param int $id_product ID del producto
     * @param int|null $id_product_attribute ID del atributo del producto (opcional)
     * @return array Resultados de la sincronización
     */
    public function syncProductPrice($id_product, $id_product_attribute = null)
    {
        // Obtener tiendas con sincronización de precios activada
        $shops = $this->shopManager->getPriceSyncShops();
        
        if (empty($shops)) {
            return ['status' => 'warning', 'message' => 'No hay tiendas configuradas para sincronización de precios'];
        }

        // Obtener datos de precio del producto
        $priceData = $this->getProductPriceData($id_product, $id_product_attribute);
        
        if (!$priceData) {
            return ['status' => 'error', 'message' => 'No se pudo obtener la información de precio del producto'];
        }

        $results = [];
        
        // Enviar datos a cada tienda aplicando el porcentaje configurado
        foreach ($shops as $shop) {
            try {
                // Aplicar porcentaje de aumento si está configurado
                $shopPriceData = $this->applyPricePercentage($priceData, $shop);
                
                // Determinar si enviar solo el precio base
                if ($shop['sync_base_price_only']) {
                    $shopPriceData = $this->filterBasePrice($shopPriceData);
                }
                
                $syncResult = $this->sendPriceToShop($shop, $shopPriceData);
                $results[$shop['id_megasync_shop']] = $syncResult;
                
                // Registrar el resultado
                $logType = ($syncResult['status'] === 'success') ? 'success' : 'error';
                $this->logService->log(
                    'Sincronización de precio para producto #' . $id_product . 
                    ' en tienda ' . $shop['name'] . ': ' . $syncResult['message'],
                    $logType,
                    'price',
                    $id_product
                );
            } catch (Exception $e) {
                $results[$shop['id_megasync_shop']] = [
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
                
                $this->logService->log(
                    'Error en sincronización de precio para producto #' . $id_product . 
                    ' en tienda ' . $shop['name'] . ': ' . $e->getMessage(),
                    'error',
                    'price',
                    $id_product
                );
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Sincronización de precio completada',
            'details' => $results
        ];
    }

    /**
     * Obtiene los datos de precio de un producto
     *
     * @param int $id_product ID del producto
     * @param int|null $id_product_attribute ID del atributo del producto (opcional)
     * @return array|false Datos de precio o false si no se encuentra
     */
    protected function getProductPriceData($id_product, $id_product_attribute = null)
    {
        $product = new Product($id_product);
        
        if (!Validate::isLoadedObject($product)) {
            $this->logService->log('Producto no encontrado: #' . $id_product, 'error', 'price', $id_product);
            return false;
        }

        $priceData = [];
        
        // Si tenemos un atributo específico
        if ($id_product_attribute !== null) {
            $reference = $product->reference;
            
            // Obtener referencia específica del atributo si existe
            $combination = new Combination($id_product_attribute);
            if (Validate::isLoadedObject($combination) && !empty($combination->reference)) {
                $reference = $combination->reference;
            }
            
            // Obtener precios
            $price = $product->getPrice(false, $id_product_attribute);
            $priceWithTax = $product->getPrice(true, $id_product_attribute);
            $wholesalePrice = $product->wholesale_price;
            
            $priceData[] = [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'reference' => $reference,
                'price' => $price,
                'price_with_tax' => $priceWithTax,
                'wholesale_price' => $wholesalePrice,
                'tax_rate' => $product->getTaxesRate(),
                'ean13' => $combination->ean13 ?: $product->ean13,
                'upc' => $combination->upc ?: $product->upc,
                'isbn' => $combination->isbn ?: $product->isbn,
            ];
        } else {
            // Producto sin atributos o todos los atributos
            $combinations = $product->getAttributeCombinations();
            
            if (empty($combinations)) {
                // Producto sin combinaciones
                $priceData[] = [
                    'id_product' => $id_product,
                    'id_product_attribute' => 0,
                    'reference' => $product->reference,
                    'price' => $product->getPrice(false),
                    'price_with_tax' => $product->getPrice(true),
                    'wholesale_price' => $product->wholesale_price,
                    'tax_rate' => $product->getTaxesRate(),
                    'ean13' => $product->ean13,
                    'upc' => $product->upc,
                    'isbn' => $product->isbn,
                ];
            } else {
                // Producto con combinaciones
                foreach ($combinations as $combination) {
                    $id_product_attribute = $combination['id_product_attribute'];
                    $priceData[] = [
                        'id_product' => $id_product,
                        'id_product_attribute' => $id_product_attribute,
                        'reference' => !empty($combination['reference']) ? $combination['reference'] : $product->reference,
                        'price' => $product->getPrice(false, $id_product_attribute),
                        'price_with_tax' => $product->getPrice(true, $id_product_attribute),
                        'wholesale_price' => $product->wholesale_price,
                        'tax_rate' => $product->getTaxesRate(),
                        'ean13' => !empty($combination['ean13']) ? $combination['ean13'] : $product->ean13,
                        'upc' => !empty($combination['upc']) ? $combination['upc'] : $product->upc,
                        'isbn' => !empty($combination['isbn']) ? $combination['isbn'] : $product->isbn,
                    ];
                }
            }
        }
        
        return $priceData;
    }

    /**
     * Aplica el porcentaje de aumento configurado para la tienda
     *
     * @param array $priceData Datos de precio originales
     * @param array $shop Datos de la tienda
     * @return array Datos de precio con el porcentaje aplicado
     */
    protected function applyPricePercentage($priceData, $shop)
    {
        $percentage = (float)$shop['price_percentage'];
        
        if ($percentage == 0) {
            return $priceData;
        }
        
        $modifiedPriceData = [];
        
        foreach ($priceData as $item) {
            // Calcular nuevos precios con el porcentaje
            $priceIncrease = $item['price'] * ($percentage / 100);
            $priceWithTaxIncrease = $item['price_with_tax'] * ($percentage / 100);
            
            $newItem = $item;
            $newItem['price'] = $item['price'] + $priceIncrease;
            $newItem['price_with_tax'] = $item['price_with_tax'] + $priceWithTaxIncrease;
            $newItem['original_price'] = $item['price'];
            $newItem['original_price_with_tax'] = $item['price_with_tax'];
            $newItem['percentage_applied'] = $percentage;
            
            $modifiedPriceData[] = $newItem;
        }
        
        return $modifiedPriceData;
    }

    /**
     * Filtra los datos para incluir solo el precio base
     *
     * @param array $priceData Datos de precio completos
     * @return array Datos de precio filtrados
     */
    protected function filterBasePrice($priceData)
    {
        $filteredData = [];
        
        foreach ($priceData as $item) {
            $filteredItem = [
                'id_product' => $item['id_product'],
                'id_product_attribute' => $item['id_product_attribute'],
                'reference' => $item['reference'],
                'price' => $item['price'],
                'price_with_tax' => $item['price_with_tax']
            ];
            
            // Mantener los identificadores de producto
            if (isset($item['ean13'])) $filteredItem['ean13'] = $item['ean13'];
            if (isset($item['upc'])) $filteredItem['upc'] = $item['upc'];
            if (isset($item['isbn'])) $filteredItem['isbn'] = $item['isbn'];
            
            $filteredData[] = $filteredItem;
        }
        
        return $filteredData;
    }

    /**
     * Envía los datos de precio a una tienda específica
     *
     * @param array $shop Datos de la tienda
     * @param array $priceData Datos de precio a enviar
     * @return array Resultado de la operación
     */
    protected function sendPriceToShop($shop, $priceData)
    {
        $data = [
            'api_key' => $shop['api_key'],
            'price_data' => $priceData
        ];

        try {
            $result = $this->communicationService->sendRequest(
                $shop['url'],
                'updatePrice',
                $data
            );

            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Precio actualizado correctamente en tienda ' . $shop['name'],
                    'details' => $result
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al actualizar precio en tienda ' . $shop['name'] . ': ' . 
                    (isset($result['message']) ? $result['message'] : 'Respuesta no válida'),
                'details' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error de comunicación con tienda ' . $shop['name'] . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ejecuta la sincronización programada de precios
     *
     * @return array Resultados de la sincronización
     */
    public function runScheduledPriceSync()
    {
        // Verificar si está habilitada la sincronización programada
        if (!Configuration::get('MEGASYNC_SCHEDULED_PRICE_SYNC')) {
            return [
                'status' => 'warning',
                'message' => 'Sincronización programada de precios no está habilitada'
            ];
        }

        // Obtener productos para sincronizar
        $lastSyncDate = Configuration::get('MEGASYNC_LAST_PRICE_SYNC_DATE');
        $productsToSync = [];

        if ($lastSyncDate) {
            // Obtener productos con precios actualizados desde la última sincronización
            $productsToSync = $this->getProductsWithPriceUpdatedSince($lastSyncDate);
        } else {
            // Primera sincronización, obtener todos los productos activos
            $productsToSync = $this->getAllActiveProductIds();
        }

        // Actualizar fecha de última sincronización
        Configuration::updateValue('MEGASYNC_LAST_PRICE_SYNC_DATE', date('Y-m-d H:i:s'));

        // Si no hay productos para sincronizar, terminar
        if (empty($productsToSync)) {
            return [
                'status' => 'success',
                'message' => 'No hay productos con precios actualizados para sincronizar'
            ];
        }

        // Sincronizar cada producto
        $results = [];
        $totalProducts = count($productsToSync);
        $successfulUpdates = 0;
        $failedUpdates = 0;

        foreach ($productsToSync as $productInfo) {
            $id_product = $productInfo['id_product'];
            $syncResult = $this->syncProductPrice($id_product);
            
            $results[$id_product] = $syncResult;
            
            if (isset($syncResult['status']) && $syncResult['status'] === 'completed') {
                $successfulUpdates++;
            } else {
                $failedUpdates++;
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Sincronización programada de precios completada',
            'total_products' => $totalProducts,
            'successful_updates' => $successfulUpdates,
            'failed_updates' => $failedUpdates,
            'details' => $results
        ];
    }

    /**
     * Obtiene los IDs de productos con precios actualizados desde una fecha determinada
     *
     * @param string $date Fecha desde la que buscar actualizaciones
     * @return array IDs de productos
     */
    protected function getProductsWithPriceUpdatedSince($date)
    {
        return Db::getInstance()->executeS('
            SELECT p.`id_product`
            FROM `'._DB_PREFIX_.'product` p
            WHERE p.`active` = 1
            AND (
                p.`date_upd` > "'.pSQL($date).'"
                OR p.`price` != p.`price`  -- Forzar a incluir todos los productos en este ejemplo
            )
            ORDER BY p.`id_product` ASC
        ');
    }

    /**
     * Sincroniza el precio de varios productos específicos
     *
     * @param array $productIds Lista de IDs de productos a sincronizar
     * @return array Resultados de la sincronización
     */
    public function syncMultipleProductsPrices($productIds)
    {
        if (empty($productIds)) {
            return [
                'status' => 'warning',
                'message' => 'No hay productos para sincronizar'
            ];
        }

        $results = [];
        $totalProducts = count($productIds);
        $successfulUpdates = 0;
        $failedUpdates = 0;

        foreach ($productIds as $id_product) {
            $syncResult = $this->syncProductPrice($id_product);
            
            $results[$id_product] = $syncResult;
            
            if (isset($syncResult['status']) && $syncResult['status'] === 'completed') {
                $successfulUpdates++;
            } else {
                $failedUpdates++;
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Sincronización de precios múltiple completada',
            'total_products' => $totalProducts,
            'successful_updates' => $successfulUpdates,
            'failed_updates' => $failedUpdates,
            'details' => $results
        ];
    }
    
    /**
     * Obtiene los IDs de todos los productos activos
     *
     * @return array IDs de productos
     */
    protected function getAllActiveProductIds()
    {
        return Db::getInstance()->executeS('
            SELECT p.`id_product`
            FROM `'._DB_PREFIX_.'product` p
            WHERE p.`active` = 1
            ORDER BY p.`id_product` ASC
        ');
    }
}