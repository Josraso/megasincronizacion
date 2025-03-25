<?php
/**
 * Servicio de sincronización de stock para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class StockService
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

    /**
     * Tamaño del lote para procesamiento por defecto
     */
    const DEFAULT_BATCH_SIZE = 50;

    public function __construct()
    {
        $this->logService = new LogService();
        $this->communicationService = new CommunicationService();
        $this->shopManager = new ShopManager();
    }

    /**
     * Sincroniza el stock de un producto específico con todas las tiendas configuradas
     *
     * @param int $id_product ID del producto
     * @param int|null $id_product_attribute ID del atributo del producto (opcional)
     * @return array Resultados de la sincronización
     */
    public function syncProductStock($id_product, $id_product_attribute = null)
    {
        // Obtener tiendas con sincronización de stock activada
        $shops = $this->shopManager->getStockSyncShops();
        
        if (empty($shops)) {
            return ['status' => 'warning', 'message' => 'No hay tiendas configuradas para sincronización de stock'];
        }

        // Obtener datos de stock del producto
        $stockData = $this->getProductStockData($id_product, $id_product_attribute);
        
        if (!$stockData) {
            return ['status' => 'error', 'message' => 'No se pudo obtener la información de stock del producto'];
        }

        $results = [];
        
        // Enviar datos a cada tienda
        foreach ($shops as $shop) {
            try {
                $syncResult = $this->sendStockToShop($shop, $stockData);
                $results[$shop['id_megasync_shop']] = $syncResult;
                
                // Registrar el resultado
                $logType = ($syncResult['status'] === 'success') ? 'success' : 'error';
                $this->logService->log(
                    'Sincronización de stock para producto #' . $id_product . 
                    ' en tienda ' . $shop['name'] . ': ' . $syncResult['message'],
                    $logType,
                    'stock',
                    $id_product
                );
            } catch (Exception $e) {
                $results[$shop['id_megasync_shop']] = [
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
                
                $this->logService->log(
                    'Error en sincronización de stock para producto #' . $id_product . 
                    ' en tienda ' . $shop['name'] . ': ' . $e->getMessage(),
                    'error',
                    'stock',
                    $id_product
                );
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Sincronización de stock completada',
            'details' => $results
        ];
    }

    /**
     * Obtiene los datos de stock de un producto
     *
     * @param int $id_product ID del producto
     * @param int|null $id_product_attribute ID del atributo del producto (opcional)
     * @return array|false Datos de stock o false si no se encuentra
     */
    protected function getProductStockData($id_product, $id_product_attribute = null)
    {
        $product = new Product($id_product);
        
        if (!Validate::isLoadedObject($product)) {
            $this->logService->log('Producto no encontrado: #' . $id_product, 'error', 'stock', $id_product);
            return false;
        }

        $stockData = [];
        
        // Si tenemos un atributo específico
        if ($id_product_attribute !== null) {
            $quantity = StockAvailable::getQuantityAvailableByProduct($id_product, $id_product_attribute);
            $reference = $product->reference;
            
            // Obtener referencia específica del atributo si existe
            $combination = new Combination($id_product_attribute);
            if (Validate::isLoadedObject($combination) && !empty($combination->reference)) {
                $reference = $combination->reference;
            }
            
            $stockData[] = [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'reference' => $reference,
                'quantity' => $quantity,
                'ean13' => $combination->ean13 ?: $product->ean13,
                'upc' => $combination->upc ?: $product->upc,
                'isbn' => $combination->isbn ?: $product->isbn,
            ];
        } else {
            // Producto sin atributos o todos los atributos
            $combinations = $product->getAttributeCombinations();
            
            if (empty($combinations)) {
                // Producto sin combinaciones
                $stockData[] = [
                    'id_product' => $id_product,
                    'id_product_attribute' => 0,
                    'reference' => $product->reference,
                    'quantity' => StockAvailable::getQuantityAvailableByProduct($id_product, 0),
                    'ean13' => $product->ean13,
                    'upc' => $product->upc,
                    'isbn' => $product->isbn,
                ];
            } else {
                // Producto con combinaciones
                foreach ($combinations as $combination) {
                    $stockData[] = [
                        'id_product' => $id_product,
                        'id_product_attribute' => $combination['id_product_attribute'],
                        'reference' => !empty($combination['reference']) ? $combination['reference'] : $product->reference,
                        'quantity' => StockAvailable::getQuantityAvailableByProduct(
                            $id_product, 
                            $combination['id_product_attribute']
                        ),
                        'ean13' => !empty($combination['ean13']) ? $combination['ean13'] : $product->ean13,
                        'upc' => !empty($combination['upc']) ? $combination['upc'] : $product->upc,
                        'isbn' => !empty($combination['isbn']) ? $combination['isbn'] : $product->isbn,
                    ];
                }
            }
        }
        
        return $stockData;
    }

    /**
     * Envía los datos de stock a una tienda específica
     *
     * @param array $shop Datos de la tienda
     * @param array $stockData Datos de stock a enviar
     * @return array Resultado de la operación
     */
    protected function sendStockToShop($shop, $stockData)
    {
        $data = [
            'api_key' => $shop['api_key'],
            'stock_data' => $stockData
        ];

        try {
            $result = $this->communicationService->sendRequest(
                $shop['url'],
                'updateStock',
                $data
            );

            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Stock actualizado correctamente en tienda ' . $shop['name'],
                    'details' => $result
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al actualizar stock en tienda ' . $shop['name'] . ': ' . 
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
     * Sincroniza el stock de varios productos por lotes
     *
     * @param array $productIds IDs de los productos a sincronizar
     * @param int $batchSize Tamaño del lote (opcional)
     * @return array Resultados de la sincronización
     */
    public function syncProductsStockBatch($productIds, $batchSize = null)
    {
        // Obtener tiendas con sincronización de stock activada
        $shops = $this->shopManager->getStockSyncShops();
        
        if (empty($shops)) {
            return ['status' => 'warning', 'message' => 'No hay tiendas configuradas para sincronización de stock'];
        }

        if (empty($productIds)) {
            return ['status' => 'warning', 'message' => 'No hay productos para sincronizar'];
        }

        $results = [];
        $totalProducts = count($productIds);
        $processedProducts = 0;
        $successfulUpdates = 0;
        $failedUpdates = 0;

        // Determinar el tamaño del lote a utilizar
        if ($batchSize === null) {
            $batchSize = self::DEFAULT_BATCH_SIZE;
        }

        // Procesar productos en lotes
        $productBatches = array_chunk($productIds, $batchSize);

        foreach ($productBatches as $batch) {
            $batchData = [];
            
            // Recopilar datos de stock para todos los productos del lote
            foreach ($batch as $id_product) {
                $stockData = $this->getProductStockData($id_product);
                if ($stockData) {
                    $batchData = array_merge($batchData, $stockData);
                    $processedProducts++;
                }
            }
            
            // Enviar lote a cada tienda
            foreach ($shops as $shop) {
                try {
                    // Si la tienda tiene configuración específica de lotes, usar esa
                    $useBatch = $shop['sync_stock_batch'] == 1;
                    
                    if ($useBatch) {
                        $syncResult = $this->sendStockBatchToShop($shop, $batchData);
                        
                        if ($syncResult['status'] === 'success') {
                            $successfulUpdates += count($batch);
                        } else {
                            $failedUpdates += count($batch);
                        }
                        
                        $shopId = $shop['id_megasync_shop'];
                        if (!isset($results[$shopId])) {
                            $results[$shopId] = [
                                'shop_name' => $shop['name'],
                                'successful' => 0,
                                'failed' => 0,
                                'details' => []
                            ];
                        }
                        
                        $results[$shopId]['successful'] += ($syncResult['status'] === 'success') ? count($batch) : 0;
                        $results[$shopId]['failed'] += ($syncResult['status'] === 'error') ? count($batch) : 0;
                        $results[$shopId]['details'][] = $syncResult;
                        
                        // Registrar el resultado del lote
                        $logType = ($syncResult['status'] === 'success') ? 'success' : 'error';
                        $this->logService->log(
                            'Sincronización de stock por lotes (' . count($batch) . ' productos) en tienda ' . 
                            $shop['name'] . ': ' . $syncResult['message'],
                            $logType,
                            'stock-batch'
                        );
                    } else {
                        // Si no usa lotes, procesar uno por uno
                        foreach ($batch as $id_product) {
                            $singleResult = $this->syncProductStock($id_product);
                            // Los resultados ya se guardan en el log dentro de syncProductStock
                            
                            if (isset($singleResult['status']) && $singleResult['status'] === 'completed') {
                                $successfulUpdates++;
                            } else {
                                $failedUpdates++;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $shopId = $shop['id_megasync_shop'];
                    if (!isset($results[$shopId])) {
                        $results[$shopId] = [
                            'shop_name' => $shop['name'],
                            'successful' => 0,
                            'failed' => count($batch),
                            'details' => []
                        ];
                    } else {
                        $results[$shopId]['failed'] += count($batch);
                    }
                    
                    $results[$shopId]['details'][] = [
                        'status' => 'error',
                        'message' => 'Error en procesamiento de lote: ' . $e->getMessage()
                    ];
                    
                    $this->logService->log(
                        'Error en sincronización de stock por lotes en tienda ' . 
                        $shop['name'] . ': ' . $e->getMessage(),
                        'error',
                        'stock-batch'
                    );
                    
                    $failedUpdates += count($batch);
                }
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Sincronización de stock por lotes completada',
            'total_products' => $totalProducts,
            'processed_products' => $processedProducts,
            'successful_updates' => $successfulUpdates,
            'failed_updates' => $failedUpdates,
            'details' => $results
        ];
    }

    /**
     * Envía un lote de datos de stock a una tienda
     *
     * @param array $shop Datos de la tienda
     * @param array $batchData Datos de stock a enviar en lote
     * @return array Resultado de la operación
     */
    protected function sendStockBatchToShop($shop, $batchData)
    {
        $data = [
            'api_key' => $shop['api_key'],
            'stock_data' => $batchData,
            'batch' => true
        ];

        try {
            $result = $this->communicationService->sendRequest(
                $shop['url'],
                'updateStockBatch',
                $data
            );

            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Lote de stock actualizado correctamente en tienda ' . $shop['name'],
                    'details' => $result
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Error al actualizar lote de stock en tienda ' . $shop['name'] . ': ' . 
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
     * Ejecuta la sincronización programada de stock
     *
     * @return array Resultados de la sincronización
     */
    public function runScheduledStockSync()
    {
        // Verificar si está habilitada la sincronización programada
        if (!Configuration::get('MEGASYNC_SCHEDULED_STOCK_SYNC')) {
            return [
                'status' => 'warning',
                'message' => 'Sincronización programada de stock no está habilitada'
            ];
        }

        // Obtener productos para sincronizar
        $lastSyncDate = Configuration::get('MEGASYNC_LAST_STOCK_SYNC_DATE');
        $productsToSync = [];

        if ($lastSyncDate) {
            // Obtener productos actualizados desde la última sincronización
            $productsToSync = $this->getProductsUpdatedSince($lastSyncDate);
        } else {
            // Primera sincronización, obtener todos los productos activos
            $productsToSync = $this->getAllActiveProductIds();
        }

        // Actualizar fecha de última sincronización
        Configuration::updateValue('MEGASYNC_LAST_STOCK_SYNC_DATE', date('Y-m-d H:i:s'));

        // Si no hay productos para sincronizar, terminar
        if (empty($productsToSync)) {
            return [
                'status' => 'success',
                'message' => 'No hay productos para sincronizar'
            ];
        }

        // Sincronizar por lotes
        return $this->syncProductsStockBatch($productsToSync);
    }

    /**
     * Obtiene los IDs de productos actualizados desde una fecha determinada
     *
     * @param string $date Fecha desde la que buscar actualizaciones
     * @return array IDs de productos
     */
    protected function getProductsUpdatedSince($date)
    {
        return Db::getInstance()->executeS('
            SELECT p.`id_product`
            FROM `'._DB_PREFIX_.'product` p
            WHERE p.`active` = 1
            AND (
                p.`date_upd` > "'.pSQL($date).'"
                OR EXISTS (
                    SELECT 1 FROM `'._DB_PREFIX_.'stock_available` sa
                    WHERE sa.`id_product` = p.`id_product`
                    AND sa.`date_upd` > "'.pSQL($date).'"
                )
            )
            ORDER BY p.`id_product` ASC
        ');
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
    