<?php
/**
 * Clase para gestionar pedidos remotos
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class RemoteOrder extends ObjectModel
{
    /**
     * @var int ID único del pedido sincronizado
     */
    public $id_order_sync;
    
    /**
     * @var int ID de la tienda remota
     */
    public $id_shop_remote;
    
    /**
     * @var int ID del pedido en la tienda remota
     */
    public $id_order_remote;
    
    /**
     * @var string Referencia del pedido en la tienda remota
     */
    public $reference_remote;
    
    /**
     * @var string Nombre del cliente en la tienda remota
     */
    public $customer_name;
    
    /**
     * @var string Email del cliente en la tienda remota
     */
    public $customer_email;
    
    /**
     * @var string Dirección de envío en formato JSON
     */
    public $shipping_address;
    
    /**
     * @var string Dirección de facturación en formato JSON
     */
    public $invoice_address;
    
    /**
     * @var string Productos del pedido en formato JSON
     */
    public $products;
    
    /**
     * @var string Descuentos aplicados en formato JSON
     */
    public $discounts;
    
    /**
     * @var bool Indica si el pedido incluye envoltorio para regalo
     */
    public $gift_wrapping;
    
    /**
     * @var string Mensaje de regalo si existe
     */
    public $gift_message;
    
    /**
     * @var float Precio del envoltorio para regalo
     */
    public $gift_price;
    
    /**
     * @var float Coste de envío del pedido
     */
    public $shipping_cost;
    
    /**
     * @var float Tasa aplicada al transportista
     */
    public $carrier_tax_rate;
    
    /**
     * @var float Importe total del pedido
     */
    public $total_amount;
    
    /**
     * @var int Número total de productos
     */
    public $total_products;
    
    /**
     * @var string Fecha de creación del pedido
     */
    public $date_add;
    
    /**
     * @var string Estado del pedido (pending, processed, error, cron_pending)
     */
    public $status;
    
    /**
     * @var int ID del pedido local generado
     */
    public $id_order_local;
    
    /**
     * @var string Fecha de procesamiento del pedido
     */
    public $date_processed;
    
    /**
     * @var string Mensaje de error si ocurrió alguno
     */
    public $error_message;

    /**
     * Definición de la estructura del modelo
     *
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'megasync_order',
        'primary' => 'id_order_sync',
        'fields' => [
            'id_shop_remote' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_order_remote' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'reference_remote' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'required' => true, 'size' => 64],
            'customer_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'customer_email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255],
            'shipping_address' => ['type' => self::TYPE_HTML, 'validate' => 'isJson', 'required' => true],
            'invoice_address' => ['type' => self::TYPE_HTML, 'validate' => 'isJson', 'required' => true],
            'products' => ['type' => self::TYPE_HTML, 'validate' => 'isJson', 'required' => true],
            'discounts' => ['type' => self::TYPE_HTML, 'validate' => 'isJson', 'required' => false],
            'gift_wrapping' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => false, 'default' => '0'],
            'gift_message' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => false],
            'gift_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false, 'default' => '0'],
            'shipping_cost' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false, 'default' => '0'],
            'carrier_tax_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false, 'default' => '0'],
            'total_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'total_products' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'values' => ['pending', 'processed', 'error', 'cron_pending']],
            'id_order_local' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => false],
            'date_processed' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => false],
            'error_message' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => false],
        ],
    ];

    /**
     * Constructor
     *
     * @param int|null $id_order_sync
     * @param int|null $id_lang
     * @param int|null $id_shop
     */
    public function __construct($id_order_sync = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_order_sync, $id_lang, $id_shop);
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
        if (!isset($this->date_add) || empty($this->date_add)) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        
        return parent::add($auto_date, $null_values);
    }

    /**
     * Verificar si un pedido ya existe
     *
     * @param int $id_shop_remote
     * @param int $id_order_remote
     * @return bool
     */
    public static function exists($id_shop_remote, $id_order_remote)
    {
        $query = new DbQuery();
        $query->select('COUNT(*)');
        $query->from('megasync_order');
        $query->where('id_shop_remote = ' . (int)$id_shop_remote);
        $query->where('id_order_remote = ' . (int)$id_order_remote);
        
        return (bool)Db::getInstance()->getValue($query);
    }

    /**
     * Obtener todos los pedidos pendientes
     *
     * @return array
     */
    public static function getPendingOrders()
    {
        $query = new DbQuery();
        $query->select('o.*, s.name as shop_name');
        $query->from('megasync_order', 'o');
        $query->leftJoin('megasync_shop', 's', 's.id_shop_remote = o.id_shop_remote');
        $query->where('o.status = "pending"');
        $query->orderBy('o.date_add ASC');

        return Db::getInstance()->executeS($query);
    }
    
    /**
     * Obtener todos los pedidos pendientes para procesar por CRON
     *
     * @return array
     */
    public static function getCronPendingOrders()
    {
        $query = new DbQuery();
        $query->select('o.*, s.name as shop_name, s.id_customer, s.id_address');
        $query->from('megasync_order', 'o');
        $query->innerJoin('megasync_shop', 's', 's.id_shop_remote = o.id_shop_remote');
        $query->where('o.status = "cron_pending"');
        $query->where('s.conversion_mode = "cron"');
        $query->orderBy('o.id_shop_remote ASC, o.date_add ASC');

        return Db::getInstance()->executeS($query);
    }
    
    /**
     * Obtener pedidos pendientes agrupados por tienda para procesar por CRON
     *
     * @return array
     */
    public static function getCronOrdersByShop()
    {
        $query = new DbQuery();
        $query->select('o.id_shop_remote, s.name as shop_name, s.id_customer, s.id_address, COUNT(o.id_order_sync) as order_count');
        $query->from('megasync_order', 'o');
        $query->innerJoin('megasync_shop', 's', 's.id_shop_remote = o.id_shop_remote');
        $query->where('o.status = "cron_pending"');
        $query->where('s.conversion_mode = "cron"');
        $query->groupBy('o.id_shop_remote');
        
        $shops = Db::getInstance()->executeS($query);
        $result = [];
        
        foreach ($shops as $shop) {
            // Obtener todos los pedidos de esta tienda
            $orders = Db::getInstance()->executeS('
                SELECT * FROM `'._DB_PREFIX_.'megasync_order`
                WHERE id_shop_remote = '.(int)$shop['id_shop_remote'].'
                AND status = "cron_pending"
                ORDER BY date_add ASC
            ');
            
            if ($orders) {
                $shop['orders'] = $orders;
                $result[] = $shop;
            }
        }
        
        return $result;
    }

    /**
     * Marcar un pedido como procesado y actualizar con el ID del pedido local
     * 
     * @param int $id_order_local ID del pedido local creado
     * @return bool
     */
    public function markAsProcessed($id_order_local)
    {
        $this->status = 'processed';
        $this->id_order_local = (int)$id_order_local;
        $this->date_processed = date('Y-m-d H:i:s');
        return $this->update();
    }
    
    /**
     * Marcar un pedido como error y guardar el mensaje de error
     * 
     * @param string $error_message Mensaje de error
     * @return bool
     */
    public function markAsError($error_message)
    {
        $this->status = 'error';
        $this->error_message = $error_message;
        return $this->update();
    }
    
    /**
     * Marcar un pedido para procesamiento por CRON
     * 
     * @return bool
     */
    public function markAsCronPending()
    {
        $this->status = 'cron_pending';
        return $this->update();
    }

    /**
     * Convertir a pedido real según la configuración de la tienda
     *
     * @param int $id_customer ID del cliente (opcional, puede usarse el de configuración de tienda)
     * @return array Resultado de la operación
     */
    public function convertToOrder($id_customer = null)
    {
        try {
            // Verificar si ya está procesado
            if ($this->status == 'processed') {
                throw new Exception('Este pedido ya ha sido procesado');
            }

            // Obtener la tienda remota para determinar el modo de importación
            $shop = new RemoteShop($this->id_shop_remote);
            if (!Validate::isLoadedObject($shop)) {
                throw new Exception('Tienda remota no encontrada');
            }
            
            // Si no se proporciona cliente, usar el configurado en la tienda
            if ($id_customer === null) {
                $id_customer = $shop->id_customer;
            }
            
            // Verificar cliente
            if ($id_customer <= 0) {
                throw new Exception('Se requiere un cliente válido');
            }
            
            $customer = new Customer($id_customer);
            if (!Validate::isLoadedObject($customer)) {
                throw new Exception('Cliente no válido');
            }

            // Decodificar datos
            $shipping_address = json_decode($this->shipping_address, true);
            $invoice_address = json_decode($this->invoice_address, true);
            $products_data = json_decode($this->products, true);
            $discounts = !empty($this->discounts) ? json_decode($this->discounts, true) : [];

            // Verificar direcciones
            if (!is_array($shipping_address) || !isset($shipping_address['firstname'])) {
                throw new Exception('Dirección de envío no válida');
            }
            
            if (!is_array($invoice_address) || !isset($invoice_address['firstname'])) {
                throw new Exception('Dirección de facturación no válida');
            }

            // Verificar productos
            if (!is_array($products_data) || empty($products_data)) {
                throw new Exception('No hay productos válidos');
            }

            // Gestión de direcciones según el modo de importación
            $id_address_delivery = 0;
            $id_address_invoice = 0;
            
            switch ($shop->import_mode) {
                case 'fixed':
                    // Usar direcciones fijas de la configuración de la tienda
                    $id_address_delivery = $shop->id_address;
                    $id_address_invoice = $shop->id_address;
                    break;
                    
                case 'original':
                    // Usar las direcciones originales del pedido
                    $id_address_delivery = $this->getOrCreateAddress($shipping_address, $id_customer);
                    $id_address_invoice = $this->getOrCreateAddress($invoice_address, $id_customer);
                    break;
                    
                case 'mixed':
                    // Usar dirección de envío original y facturación fija
                    $id_address_delivery = $this->getOrCreateAddress($shipping_address, $id_customer);
                    $id_address_invoice = $shop->id_address > 0 ? $shop->id_address : $id_address_delivery;
                    break;
                
                default:
                    throw new Exception('Modo de importación no válido');
            }
            
            // Verificar que las direcciones sean válidas
            if (!$id_address_delivery || !$id_address_invoice) {
                throw new Exception('No se pudieron obtener direcciones válidas');
            }

            // MODIFICACIÓN: Calcular el total basado en los productos que realmente se importaron
            $calculated_total = 0;
            $products_to_import = [];

            // Primera pasada: Identificar productos que existen en la tienda madre
            foreach ($products_data as $product_data) {
                $product_id = $this->findProductByReference($product_data['reference']);
                if ($product_id) {
                    // Verificar si hay combinación
                    $id_product_attribute = 0;
                    if (!empty($product_data['combination_reference'])) {
                        $id_product_attribute = $this->findCombinationByReference(
                            $product_id, 
                            $product_data['combination_reference']
                        );
                    }
                    
                    // Marcar este producto para importar
                    $products_to_import[] = [
                        'product_id' => $product_id,
                        'id_product_attribute' => $id_product_attribute,
                        'quantity' => (int)$product_data['quantity'],
                        'price' => (float)$product_data['price'],
                        'total' => (float)$product_data['total']
                    ];
                    
                    // Sumar al total calculado
                    $calculated_total += (float)$product_data['total'];
                }
            }

            // Añadir el coste de envío
            if ($this->shipping_cost > 0) {
                $calculated_total += $this->shipping_cost;
            }
            
            // Añadir el coste del envoltorio para regalo si existe
            if ($this->gift_wrapping && $this->gift_price > 0) {
                $calculated_total += $this->gift_price;
            }
            
            // Aplicar descuentos si existen
            if (!empty($discounts)) {
                foreach ($discounts as $discount) {
                    if (isset($discount['amount']) && $discount['amount'] > 0) {
                        $calculated_total -= (float)$discount['amount'];
                    }
                }
            }

            // Si no hay productos para importar, no se puede crear el pedido
            if (empty($products_to_import)) {
                throw new Exception('No se encontraron productos coincidentes por referencia');
            }

            // Crear carrito
            $cart = new Cart();
            $cart->id_customer = $id_customer;
            $cart->id_address_delivery = $id_address_delivery;
            $cart->id_address_invoice = $id_address_invoice;
            $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
            $cart->id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            $cart->secure_key = $customer->secure_key;
            
            if (!$cart->add()) {
                throw new Exception('Error al crear el carrito');
            }

            // Seleccionar el transportista por defecto si se ha configurado uno
            $id_carrier = (int)Configuration::get('MEGASYNC_DEFAULT_CARRIER_ID');
            if ($id_carrier > 0) {
                $cart->id_carrier = $id_carrier;
            } else {
                // Si no hay transportista configurado, buscar uno disponible
                $carriers = Carrier::getCarriers($cart->id_lang, true);
                if (!empty($carriers)) {
                    $cart->id_carrier = (int)$carriers[0]['id_carrier'];
                }
            }
            
            // Actualizar el carrito
            $cart->update();

            // Añadir productos al carrito
            $products_added = false;
            $products_added_count = 0;
            $force_add = true; // Forzar añadir productos incluso si están fuera de stock

            foreach ($products_to_import as $product_info) {
                // Verificar que el producto existe
                $product = new Product($product_info['product_id']);
                if (!Validate::isLoadedObject($product)) {
                    continue;
                }
                
                // Verificar que la combinación existe (si aplica)
                if ($product_info['id_product_attribute'] > 0) {
                    $combination = new Combination($product_info['id_product_attribute']);
                    if (!Validate::isLoadedObject($combination)) {
                        continue;
                    }
                }
                
                // Añadir al carrito
                $quantity = (int)$product_info['quantity'];
                if ($quantity <= 0) {
                    $quantity = 1;
                }

                try {
                    // Desactivar temporalmente la verificación de stock si estamos forzando
                    if ($force_add) {
                        $original_check_stock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
                        Configuration::updateGlobalValue('PS_ORDER_OUT_OF_STOCK', 1);
                    }
                    
                    $update_result = $cart->updateQty(
                        $quantity, 
                        $product_info['product_id'], 
                        $product_info['id_product_attribute'], 
                        false, // No usar añadir sino updateQty
                        'up' // Dirección: incrementar
                    );
                    
                    // Restaurar configuración original
                    if ($force_add && isset($original_check_stock)) {
                        Configuration::updateGlobalValue('PS_ORDER_OUT_OF_STOCK', $original_check_stock);
                    }
                    
                    if ($update_result) {
                        $products_added = true;
                        $products_added_count++;
                        
                        // Actualizar precios del producto en el carrito
                        $this->updateCartProductPrice(
                            $cart->id, 
                            $product_info['product_id'], 
                            $product_info['id_product_attribute'], 
                            $product_info['price'], 
                            $product_info['total']
                        );
                    }
                } catch (Exception $e) {
                    // Registrar error pero continuar con otros productos
                    PrestaShopLogger::addLog(
                        'MegaSincronización: Error al añadir producto al carrito: ' . $e->getMessage(),
                        3, // Error
                        null,
                        'Cart',
                        $cart->id,
                        true
                    );
                }
            }
            
            // Verificar que al menos se haya añadido un producto al carrito
            if (!$products_added || $products_added_count <= 0) {
                throw new Exception('No se pudieron añadir productos al carrito. Verifica disponibilidad e inventario.');
            }
            
            // Verificar que el carrito tiene productos
            $product_count = (int)Db::getInstance()->getValue('
                SELECT SUM(quantity) FROM `' . _DB_PREFIX_ . 'cart_product` 
                WHERE id_cart = ' . (int)$cart->id
            );
            
            if ($product_count <= 0) {
                throw new Exception('El carrito está vacío después de intentar añadir productos.');
            }
            
            // Configurar contexto para la creación del pedido
            if (!isset($this->context) || $this->context === null) {
                $this->context = Context::getContext();
            }
            
            $this->context->cart = $cart;
            $this->context->customer = $customer;
            $this->context->currency = new Currency($cart->id_currency);
            $this->context->language = new Language($cart->id_lang);
            
            if (!isset($this->context->link) || $this->context->link === null) {
                $this->context->link = new Link();
            }
            
            // Usar el total calculado para el pedido
            $total = $calculated_total;
            if ($total <= 0) {
                $total = $cart->getOrderTotal(true, Cart::BOTH);
            }

            // Buscar un módulo de pago disponible
            $payment_modules = PaymentModule::getInstalledPaymentModules();
            if (empty($payment_modules)) {
                throw new Exception('No hay módulos de pago disponibles para crear el pedido');
            }
            
            // Usar el primer módulo de pago disponible
            $payment_module_name = $payment_modules[0]['name'];
            $payment_module = Module::getInstanceByName($payment_module_name);
            
            if (!Validate::isLoadedObject($payment_module) || !($payment_module instanceof PaymentModule)) {
                throw new Exception('No se pudo cargar un módulo de pago válido');
            }
            
            // Preparar para envoltorio para regalo si es necesario
            if ($this->gift_wrapping) {
                $cart->gift = 1;
                if (!empty($this->gift_message)) {
                    $cart->gift_message = $this->gift_message;
                }
                $cart->update();
            }
            
            // Crear pedido usando el módulo de pago
            $payment_method = 'Pedido importado de tienda remota: ' . $shop->name;
            $order_status = 2; // Forzar explícitamente el estado "Pago aceptado"
            
            // Verificar que el carrito está listo para procesar
            if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0) {
                throw new Exception('Carrito no válido para crear pedido (faltan datos necesarios)');
            }
            
            // Crear el pedido
            $payment_module->validateOrder(
                $cart->id,
                $order_status,
                $total,
                $payment_method,
                null,
                [],
                null,
                false,
                $customer->secure_key
            );
            
            $id_order = Order::getIdByCartId($cart->id);
            
            if (!$id_order) {
                throw new Exception('Error al crear el pedido');
            }
            
            // Ajustar precios directamente en la tabla de detalles del pedido
            $order = new Order($id_order);
            if (Validate::isLoadedObject($order)) {
                try {
                    $order_details = $order->getOrderDetailList();
                    
                    // Mapa de productos por ID
                    $product_price_map = [];
                    foreach ($products_to_import as $prod) {
                        $key = $prod['product_id'] . '-' . $prod['id_product_attribute'];
                        $product_price_map[$key] = [
                            'unit_price' => $prod['price'],
                            'total_price' => $prod['total']
                        ];
                    }
                    
                    // Ajustar cada línea de detalle del pedido
                    foreach ($order_details as $order_detail) {
                        $key = $order_detail['product_id'] . '-' . $order_detail['product_attribute_id'];
                        
                        if (isset($product_price_map[$key])) {
                            $original_price = $product_price_map[$key]['unit_price'];
                            $original_total = $product_price_map[$key]['total_price'];
                            
                            // Obtener las columnas disponibles en order_detail
                            $order_detail_columns = [];
                            $result = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'order_detail`');
                            if ($result && is_array($result)) {
                                foreach ($result as $column) {
                                    $order_detail_columns[] = $column['Field'];
                                }
                            }
                            
                            // Preparar los datos a actualizar
                            $update_data = [];
                            $tax_rate = 1.21; // Tasa por defecto, podría ser configurable
                            
                            $price_fields = [
                                'unit_price_tax_incl' => $original_price,
                                'unit_price_tax_excl' => $original_price / $tax_rate,
                                'total_price_tax_incl' => $original_total,
                                'total_price_tax_excl' => $original_total / $tax_rate,
                                'product_price' => $original_price / $tax_rate,
                                'product_price_wt' => $original_price
                            ];
                            
                            // Solo incluir campos que existan en la tabla
                            foreach ($price_fields as $field => $value) {
                                if (in_array($field, $order_detail_columns)) {
                                    $update_data[$field] = $value;
                                }
                            }
                            
                            // Actualizar precios en la tabla order_detail
                            if (!empty($update_data)) {
                                Db::getInstance()->update(
                                    'order_detail',
                                    $update_data,
                                    'id_order_detail = ' . (int)$order_detail['id_order_detail']
                                );
                            }
                        }
                    }
                    
                    // Actualizar los totales del pedido
                    $order_columns = [];
                    $columns_result = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'orders`');
                    if ($columns_result && is_array($columns_result)) {
                        foreach ($columns_result as $column) {
                            $order_columns[] = $column['Field'];
                        }
                    }
                    
                    // Calcular subtotal sin impuestos
                    $tax_rate = 1.21;
                    $total_products = 0;
                    foreach ($products_to_import as $prod) {
                        $total_products += $prod['total'];
                    }
                    
                    $update_order = [
                        'total_products' => $total_products / $tax_rate,
                        'total_products_wt' => $total_products,
                    ];
                    
                    // Añadir gastos de envío
                    if ($this->shipping_cost > 0) {
                        $update_order['total_shipping'] = $this->shipping_cost;
                        $update_order['total_shipping_tax_incl'] = $this->shipping_cost;
                        $update_order['total_shipping_tax_excl'] = $this->shipping_cost / (1 + ($this->carrier_tax_rate / 100));
                    }
                    
                    // Añadir gastos de regalo
                    if ($this->gift_wrapping && $this->gift_price > 0) {
                        if (in_array('gift_price', $order_columns)) {
                            $update_order['gift_price'] = $this->gift_price / $tax_rate;
                        }
                        if (in_array('total_wrapping', $order_columns)) {
                            $update_order['total_wrapping'] = $this->gift_price / $tax_rate;
                        }
                        if (in_array('total_wrapping_tax_incl', $order_columns)) {
                            $update_order['total_wrapping_tax_incl'] = $this->gift_price;
                        }
                        if (in_array('total_wrapping_tax_excl', $order_columns)) {
                            $update_order['total_wrapping_tax_excl'] = $this->gift_price / $tax_rate;
                        }
                    }
                    
                    // Actualizar totales finales
                    $update_order['total_paid'] = $calculated_total;
                    $update_order['total_paid_tax_incl'] = $calculated_total;
                    $update_order['total_paid_tax_excl'] = $calculated_total / $tax_rate;
                    
                    // Actualizar el pedido con todos los campos
                    Db::getInstance()->update(
                        'orders',
                        $update_order,
                        'id_order = ' . (int)$id_order
                    );
                    
                } catch (Exception $e) {
                    // Registrar error pero continuar con el proceso
                    PrestaShopLogger::addLog(
                        'MegaSincronización: Error al ajustar precios post-creación: ' . $e->getMessage(),
                        3, // Error
                        null,
                        'Order',
                        $id_order,
                        true
                    );
                }
            }
            
            // Actualizar pedido remoto
            $this->status = 'processed';
            $this->id_order_local = $id_order;
            $this->date_processed = date('Y-m-d H:i:s');
            $this->update();
            
            // Registrar en log del módulo
            $module = Module::getInstanceByName('megasincronizacion');
            if ($module) {
                $module->logAction(
                    $this->id_shop_remote, 
                    $this->id_order_remote, 
                    $id_order,
                    'convert_success', 
                    1, 
                    'Pedido convertido correctamente: '.$id_order
                );
            }
            
            return [
                'success' => true,
                'id_order' => $id_order,
                'message' => 'Pedido creado correctamente'
            ];
            
        } catch (Exception $e) {
            // Registrar error
            $this->error_message = $e->getMessage();
            $this->update();
            
            // Registrar en log del módulo
            $module = Module::getInstanceByName('megasincronizacion');
            if ($module) {
                $module->logAction(
                    $this->id_shop_remote, 
                    $this->id_order_remote, 
                    null,
                    'convert_error', 
                    0, 
                    $e->getMessage()
                );
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesamiento por lotes de pedidos CRON
     * 
     * @param int $id_shop_remote ID de la tienda remota
     * @return array Resultado de la operación
     */
    public static function processCronOrdersByShop($id_shop_remote)
    {
        try {
            // Verificar si la tienda existe
            $shop = new RemoteShop($id_shop_remote);
            if (!Validate::isLoadedObject($shop)) {
                throw new Exception('Tienda remota no encontrada');
            }
            
            // Verificar que la tienda tiene modo CRON
            if ($shop->conversion_mode != 'cron') {
                throw new Exception('La tienda no está configurada para procesamiento CRON');
            }
            
            // Verificar que hay un cliente configurado
            if ($shop->id_customer <= 0) {
                throw new Exception('No hay cliente predeterminado configurado para esta tienda');
            }
            
            // Obtener todos los pedidos pendientes de CRON para esta tienda
            $orders = Db::getInstance()->executeS('
                SELECT * FROM `'._DB_PREFIX_.'megasync_order`
                WHERE id_shop_remote = '.(int)$id_shop_remote.'
                AND status = "cron_pending"
                ORDER BY date_add ASC
            ');
            
            if (!$orders || empty($orders)) {
                return [
                    'success' => true,
                    'message' => 'No hay pedidos pendientes para procesar',
                    'processed' => 0
                ];
            }
            
            // Crear un único pedido con todos los productos
            $all_products = [];
            $total_amount = 0;
            $order_ids = [];
            
            // Recopilar todos los productos de todos los pedidos
            foreach ($orders as $order_data) {
                $products = json_decode($order_data['products'], true);
                if (is_array($products)) {
                    foreach ($products as $product) {
                        // Clave única para identificar producto y combinación
                        $key = $product['reference'] . 
                               (isset($product['combination_reference']) ? '_' . $product['combination_reference'] : '');
                        
                        if (isset($all_products[$key])) {
                            // Si ya existe, sumar cantidades
                            $all_products[$key]['quantity'] += (int)$product['quantity'];
                            $all_products[$key]['total'] += (float)$product['total'];
                        } else {
                            // Si no existe, añadir
                            $all_products[$key] = $product;
                        }
                    }
                }
                
                // Sumar el total
                $total_amount += (float)$order_data['total_amount'];
                
                // Guardar ID para actualizar estado después
                $order_ids[] = (int)$order_data['id_order_sync'];
            }
            
            // Convertir a array indexado
            $products_array = [];
            foreach ($all_products as $product) {
                $products_array[] = $product;
            }
            
            // Crear pedido único
            $combined_order = new RemoteOrder();
            $combined_order->id_shop_remote = $id_shop_remote;
            $combined_order->id_order_remote = time(); // ID temporal
            $combined_order->reference_remote = 'CRON-'.date('YmdHis');
            $combined_order->customer_name = 'Pedido CRON Combinado';
            $combined_order->customer_email = 'cron@example.com';
            
            // Usar la dirección configurada en la tienda
            $address = new Address($shop->id_address);
            $address_data = [];
            
            if (Validate::isLoadedObject($address)) {
                $address_data = [
                    'firstname' => $address->firstname,
                    'lastname' => $address->lastname,
                    'company' => $address->company,
                    'address1' => $address->address1,
                    'address2' => $address->address2,
                    'postcode' => $address->postcode,
                    'city' => $address->city,
                    'id_country' => $address->id_country,
                    'country' => Country::getNameById(Context::getContext()->language->id, $address->id_country),
                    'phone' => $address->phone,
                    'phone_mobile' => $address->phone_mobile
                ];
            } else {
                // Dirección por defecto si no existe
                $address_data = [
                    'firstname' => 'CRON',
                    'lastname' => 'Importado',
                    'address1' => 'Dirección CRON',
                    'postcode' => '00000',
                    'city' => 'Ciudad',
                    'country' => 'España'
                ];
            }
            
            $combined_order->shipping_address = json_encode($address_data);
            $combined_order->invoice_address = json_encode($address_data);
            $combined_order->products = json_encode($products_array);
            $combined_order->total_amount = $total_amount;
            $combined_order->total_products = count($products_array);
            $combined_order->date_add = date('Y-m-d H:i:s');
            $combined_order->status = 'pending';
            
            if (!$combined_order->add()) {
                throw new Exception('Error al crear el pedido combinado');
            }
            
            // Convertir el pedido combinado
            $result = $combined_order->convertToOrder($shop->id_customer);
            
            if ($result['success']) {
                // Actualizar todos los pedidos individuales
                foreach ($order_ids as $id_order_sync) {
                    $order = new RemoteOrder($id_order_sync);
                    if (Validate::isLoadedObject($order)) {
                        $order->status = 'processed';
                        $order->id_order_local = $result['id_order'];
                        $order->date_processed = date('Y-m-d H:i:s');
                        $order->update();
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Procesamiento CRON completado correctamente',
                    'id_order' => $result['id_order'],
                    'processed' => count($order_ids)
                ];
            } else {
                throw new Exception('Error al convertir pedido combinado: ' . $result['message']);
            }
            
        } catch (Exception $e) {
            // Registrar error en log del módulo
            $module = Module::getInstanceByName('megasincronizacion');
            if ($module) {
                $module->logAction(
                    $id_shop_remote, 
                    0, 
                    null,
                    'cron_process_error', 
                    0, 
                    $e->getMessage()
                );
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza los precios de un producto en el carrito
     *
     * @param int $id_cart ID del carrito
     * @param int $id_product ID del producto
     * @param int $id_product_attribute ID de la combinación
     * @param float $unit_price Precio unitario
     * @param float $total_price Precio total
     * @return bool
     */
    protected function updateCartProductPrice($id_cart, $id_product, $id_product_attribute, $unit_price, $total_price)
    {
        try {
            // Verificar qué columnas existen en la tabla cart_product
            $cart_product_columns = [];
            $result = Db::getInstance()->executeS('SHOW COLUMNS FROM `'._DB_PREFIX_.'cart_product`');
            if ($result && is_array($result)) {
                foreach ($result as $column) {
                    $cart_product_columns[] = $column['Field'];
                }
            }
            
            // Construir la consulta SQL solo con las columnas que existen
            $update_fields = [];
            
            // Precios unitarios
            if (in_array('price', $cart_product_columns)) {
                $update_fields[] = 'price = ' . (float)$unit_price;
            }
            if (in_array('price_wt', $cart_product_columns)) {
                $update_fields[] = 'price_wt = ' . (float)$unit_price;
            }
            if (in_array('unit_price', $cart_product_columns)) {
                $update_fields[] = 'unit_price = ' . (float)$unit_price;
            }
            if (in_array('unit_price_tax_incl', $cart_product_columns)) {
                $update_fields[] = 'unit_price_tax_incl = ' . (float)$unit_price;
            }
            if (in_array('unit_price_tax_excl', $cart_product_columns)) {
                // Aproximar precio sin impuestos
                $update_fields[] = 'unit_price_tax_excl = ' . (float)($unit_price / 1.21);
            }
            
            // Precios totales (precio * cantidad)
            if (in_array('total_price', $cart_product_columns)) {
                $update_fields[] = 'total_price = ' . (float)$total_price;
            }
            if (in_array('total_price_tax_incl', $cart_product_columns)) {
                $update_fields[] = 'total_price_tax_incl = ' . (float)$total_price;
            }
            if (in_array('total_price_tax_excl', $cart_product_columns)) {
                // Aproximar precio sin impuestos
                $update_fields[] = 'total_price_tax_excl = ' . (float)($total_price / 1.21);
            }
            
            // Ejecutar la actualización si hay campos para actualizar
            if (!empty($update_fields)) {
                $sql = 'UPDATE `'._DB_PREFIX_.'cart_product` SET ' . implode(', ', $update_fields) . ' 
                       WHERE id_cart = ' . (int)$id_cart . ' 
                       AND id_product = ' . (int)$id_product . ' 
                       AND id_product_attribute = ' . (int)$id_product_attribute;
                
                Db::getInstance()->execute($sql);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            PrestaShopLogger::addLog(
                'MegaSincronización: Error al actualizar precios en carrito: ' . $e->getMessage(),
                3, // Error
                null,
                'Cart',
                (int)$id_cart,
                true
            );
            return false;
        }
    }

    /**
     * Buscar un producto por referencia
     *
     * @param string $reference
     * @return int|false
     */
    protected function findProductByReference($reference)
    {
        if (empty($reference)) {
            return false;
        }
        
        $query = new DbQuery();
        $query->select('id_product');
        $query->from('product');
        $query->where('reference = "'.pSQL($reference).'"');
        
        $id_product = Db::getInstance()->getValue($query);
        
        return $id_product ? (int)$id_product : false;
    }

    /**
     * Buscar una combinación por referencia
     *
     * @param int $id_product
     * @param string $reference
     * @return int|false
     */
    protected function findCombinationByReference($id_product, $reference)
    {
        if (empty($reference) || empty($id_product)) {
            return 0; // Sin combinación
        }
        
        $query = new DbQuery();
        $query->select('id_product_attribute');
        $query->from('product_attribute');
        $query->where('id_product = '.(int)$id_product);
        $query->where('reference = "'.pSQL($reference).'"');
        
        $id_product_attribute = Db::getInstance()->getValue($query);
        
        return $id_product_attribute ? (int)$id_product_attribute : 0;
    }

    /**
     * Crear o recuperar una dirección para el cliente
     *
     * @param array $address_data
     * @param int $id_customer
     * @return int|false
     */
    protected function getOrCreateAddress($address_data, $id_customer)
    {
        // Normalizar datos de dirección
        $normalized = [
            'id_customer' => (int)$id_customer,
            'id_country' => $this->getCountryId(isset($address_data['country_iso']) ? $address_data['country_iso'] : ''),
            'alias' => 'Dirección de pedido remoto',
            'firstname' => $address_data['firstname'],
            'lastname' => $address_data['lastname'],
            'address1' => $address_data['address1'],
            'address2' => isset($address_data['address2']) ? $address_data['address2'] : '',
            'postcode' => $address_data['postcode'],
            'city' => $address_data['city'],
            'phone' => isset($address_data['phone']) ? $address_data['phone'] : '',
            'phone_mobile' => isset($address_data['phone_mobile']) ? $address_data['phone_mobile'] : '',
            'vat_number' => isset($address_data['vat_number']) ? $address_data['vat_number'] : '',
            'dni' => isset($address_data['dni']) ? $address_data['dni'] : (isset($address_data['vat_number']) ? $address_data['vat_number'] : ''),
            'company' => isset($address_data['company']) ? $address_data['company'] : '',
            'id_state' => isset($address_data['id_state']) ? (int)$address_data['id_state'] : 0
        ];
        
        // Asegurar que DNI no está vacío (usar VAT o generar uno de prueba si es necesario)
        if (empty($normalized['dni'])) {
            $customer = new Customer($id_customer);
            if (Validate::isLoadedObject($customer) && !empty($customer->dni)) {
                $normalized['dni'] = $customer->dni;
            } else {
                // Generar un DNI de prueba si la tienda lo requiere
                $normalized['dni'] = 'REMOTO' . mt_rand(10000000, 99999999) . strtoupper(substr(md5(time()), 0, 1));
            }
        }
        
        // Buscar directamente en la base de datos para ser compatible con todas las versiones
        $query = new DbQuery();
        $query->select('a.id_address');
        $query->from('address', 'a');
        $query->where('a.id_customer = ' . (int)$id_customer);
        $query->where('a.deleted = 0');
        
        $addresses = Db::getInstance()->executeS($query);
        
        // Comparar con las direcciones existentes
        foreach ($addresses as $address) {
            $addr = new Address((int)$address['id_address']);
            
            // Comparar campos principales
            if ($addr->firstname == $normalized['firstname'] &&
                $addr->lastname == $normalized['lastname'] &&
                $addr->address1 == $normalized['address1'] &&
                $addr->postcode == $normalized['postcode'] &&
                $addr->city == $normalized['city'] &&
                $addr->id_country == $normalized['id_country']) {
                    return (int)$addr->id;
            }
        }
        
        // Crear nueva dirección
        $address = new Address();
        foreach ($normalized as $key => $value) {
            $address->{$key} = $value;
        }
        
        if ($address->save()) {
            return (int)$address->id;
        }
        
        return false;
    }

    /**
     * Obtener ID del país a partir del código ISO
     *
     * @param string $iso_code
     * @return int
     */
    protected function getCountryId($iso_code)
    {
        if (empty($iso_code)) {
            // Si no hay código ISO, usar país por defecto
            return (int)Configuration::get('PS_COUNTRY_DEFAULT');
        }
        
        // Obtener directamente de la base de datos para mayor compatibilidad
        $query = new DbQuery();
        $query->select('id_country');
        $query->from('country');
        $query->where('iso_code = "' . pSQL($iso_code) . '"');
        
        $id_country = (int)Db::getInstance()->getValue($query);
        
        if (!$id_country) {
            // País por defecto si no se encuentra
            $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        }
        
        return $id_country;
    }
}