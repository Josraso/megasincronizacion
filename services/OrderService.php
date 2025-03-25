<?php
/**
 * Servicio de gestión de pedidos para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderService
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
     * Obtiene los datos de un pedido por su ID
     *
     * @param int $id_megasync_order ID del pedido
     * @return array|bool Datos del pedido o false si no existe
     */
    public function getOrderById($id_megasync_order)
    {
        return Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `id_megasync_order` = '.(int)$id_megasync_order
        );
    }
    
    /**
     * Obtiene los detalles de un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @return array Detalles del pedido
     */
    public function getOrderDetails($id_megasync_order)
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_order_detail`
            WHERE `id_megasync_order` = '.(int)$id_megasync_order
        );
    }
    
    /**
     * Obtiene el historial de un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @return array Historial del pedido
     */
    public function getOrderHistory($id_megasync_order)
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_order_history`
            WHERE `id_megasync_order` = '.(int)$id_megasync_order
            .' ORDER BY `date_add` DESC'
        );
    }
    
    /**
     * Obtiene los pedidos pendientes de importar
     *
     * @return array Pedidos pendientes
     */
    public function getPendingOrders()
    {
        return Db::getInstance()->executeS('
            SELECT * FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `imported` = 0 AND `status` = "pending"
            ORDER BY `date_add` ASC
        ');
    }
    
    /**
     * Actualiza el estado de importación de un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @param bool $imported Estado de importación
     * @return bool Resultado de la actualización
     */
    public function updateOrderImportedStatus($id_megasync_order, $imported)
    {
        $status = $imported ? 'imported' : 'pending';
        
        $result = Db::getInstance()->update('megasync_orders', [
            'imported' => (int)$imported,
            'status' => pSQL($status),
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_megasync_order = ' . (int)$id_megasync_order);
        
        if ($result) {
            // Añadir registro al historial
            $this->addOrderHistory($id_megasync_order, $status, 'Estado actualizado manualmente');
            
            $this->logService->log(
                'Estado de importación actualizado para pedido #' . $id_megasync_order . ': ' . ($imported ? 'Importado' : 'Pendiente'),
                'info',
                'order',
                $id_megasync_order
            );
        }
        
        return $result;
    }
    
    /**
     * Elimina un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @return bool Resultado de la eliminación
     */
    public function deleteOrder($id_megasync_order)
    {
        // Primero eliminar detalles e historial
        Db::getInstance()->delete('megasync_order_detail', 'id_megasync_order = ' . (int)$id_megasync_order);
        Db::getInstance()->delete('megasync_order_history', 'id_megasync_order = ' . (int)$id_megasync_order);
        
        // Luego eliminar el pedido
        $result = Db::getInstance()->delete('megasync_orders', 'id_megasync_order = ' . (int)$id_megasync_order);
        
        if ($result) {
            $this->logService->log(
                'Pedido #' . $id_megasync_order . ' eliminado',
                'info',
                'order',
                $id_megasync_order
            );
        }
        
        return $result;
    }
    
    /**
     * Añade un registro al historial de un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @param string $status Estado del pedido
     * @param string $comment Comentario
     * @return bool|int ID del registro o false si falla
     */
    public function addOrderHistory($id_megasync_order, $status, $comment = '')
    {
        $result = Db::getInstance()->insert('megasync_order_history', [
            'id_megasync_order' => (int)$id_megasync_order,
            'status' => pSQL($status),
            'comment' => pSQL($comment),
            'date_add' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            return Db::getInstance()->Insert_ID();
        }
        
        return false;
    }
    
    /**
     * Obtiene los detalles de un pedido de una tienda externa
     *
     * @param int $id_shop ID de la tienda
     * @param int $id_order_origin ID del pedido en la tienda origen
     * @return array|false Detalles del pedido o false si falla
     */
    public function getExternalOrderDetails($id_shop, $id_order_origin)
    {
        $shop = $this->shopManager->getShopById($id_shop);
        
        if (!$shop) {
            $this->logService->log(
                'Error al obtener detalles de pedido externo: Tienda no encontrada',
                'error',
                'order'
            );
            return false;
        }
        
        try {
            $result = $this->communicationService->getOrderDetails(
                $shop['url'],
                $shop['api_key'],
                $id_order_origin
            );
            
            if (isset($result['status']) && $result['status'] === 'success') {
                return $result['order'];
            }
            
            $this->logService->log(
                'Error al obtener detalles de pedido externo #' . $id_order_origin . ': ' . 
                (isset($result['message']) ? $result['message'] : 'Error desconocido'),
                'error',
                'order'
            );
            
            return false;
        } catch (Exception $e) {
            $this->logService->log(
                'Excepción al obtener detalles de pedido externo #' . $id_order_origin . ': ' . $e->getMessage(),
                'error',
                'order'
            );
            
            return false;
        }
    }
    
    /**
     * Actualiza el estado de un pedido
     *
     * @param int $id_megasync_order ID del pedido
     * @param string $status Nuevo estado
     * @param string $comment Comentario
     * @return bool Resultado de la actualización
     */
    public function updateOrderStatus($id_megasync_order, $status, $comment = '')
    {
        // Validar estado
        $validStatuses = ['pending', 'processing', 'imported', 'error', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            $this->logService->log(
                'Error al actualizar estado de pedido #' . $id_megasync_order . ': Estado no válido: ' . $status,
                'error',
                'order',
                $id_megasync_order
            );
            return false;
        }
        
        // Actualizar estado
        $result = Db::getInstance()->update('megasync_orders', [
            'status' => pSQL($status),
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_megasync_order = ' . (int)$id_megasync_order);
        
        if ($result) {
            // Añadir registro al historial
            $this->addOrderHistory($id_megasync_order, $status, $comment);
            
            $this->logService->log(
                'Estado actualizado para pedido #' . $id_megasync_order . ': ' . $status,
                'info',
                'order',
                $id_megasync_order
            );
        }
        
        return $result;
    }
    
    /**
     * Refresca el estado de un pedido desde la tienda origen
     *
     * @param int $id_megasync_order ID del pedido
     * @return array Resultado de la operación
     */
    public function refreshOrderStatus($id_megasync_order)
    {
        $order = $this->getOrderById($id_megasync_order);
        
        if (!$order) {
            return [
                'status' => 'error',
                'message' => 'Pedido no encontrado'
            ];
        }
        
        $shop = $this->shopManager->getShopById($order['id_shop']);
        
        if (!$shop) {
            return [
                'status' => 'error',
                'message' => 'Tienda no encontrada'
            ];
        }
        
        try {
            $externalOrder = $this->getExternalOrderDetails($order['id_shop'], $order['id_order_origin']);
            
            if (!$externalOrder) {
                return [
                    'status' => 'error',
                    'message' => 'No se pudo obtener información del pedido de la tienda origen'
                ];
            }
            
            // Actualizar datos del pedido
            $status = $this->mapExternalStatus($externalOrder['status']);
            
            $this->updateOrderStatus(
                $id_megasync_order,
                $status,
                'Estado actualizado desde tienda origen: ' . $externalOrder['status']
            );
            
            return [
                'status' => 'success',
                'message' => 'Estado actualizado correctamente',
                'order_status' => $status,
                'external_status' => $externalOrder['status']
            ];
        } catch (Exception $e) {
            $this->logService->log(
                'Error al refrescar estado de pedido #' . $id_megasync_order . ': ' . $e->getMessage(),
                'error',
                'order',
                $id_megasync_order
            );
            
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Mapea un estado externo a uno interno
     *
     * @param string $externalStatus Estado externo
     * @return string Estado interno
     */
    protected function mapExternalStatus($externalStatus)
    {
        $statusMap = [
            'awaiting_payment' => 'pending',
            'awaiting_fulfillment' => 'pending',
            'awaiting_shipment' => 'pending',
            'awaiting_pickup' => 'pending',
            'partially_shipped' => 'processing',
            'completed' => 'processing',
            'shipped' => 'processing',
            'canceled' => 'cancelled',
            'declined' => 'cancelled',
            'refunded' => 'cancelled',
            'disputed' => 'error',
            'error' => 'error',
            // Añadir más mapeos según sea necesario
        ];
        
        return isset($statusMap[$externalStatus]) ? $statusMap[$externalStatus] : 'pending';
    }
    
    /**
     * Procesa la actualización de estado de un pedido
     *
     * @param array $params Parámetros del hook
     * @return bool Resultado del procesamiento
     */
    public function processOrderStatusUpdate($params)
    {
        if (!isset($params['id_order']) || !isset($params['newOrderStatus'])) {
            return false;
        }
        
        $id_order = $params['id_order'];
        $newStatus = $params['newOrderStatus'];
        
        // Buscar si este pedido es un pedido destino
        $megasyncOrder = Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `id_order_destination` = '.(int)$id_order
        );
        
        if (!$megasyncOrder) {
            // No es un pedido que nos interese
            return false;
        }
        
        // Actualizar estado en tienda origen si corresponde
        if ($newStatus && isset($newStatus->id)) {
            $shop = $this->shopManager->getShopById($megasyncOrder['id_shop']);
            
            if (!$shop) {
                $this->logService->log(
                    'Error al actualizar estado de pedido origen: Tienda no encontrada',
                    'error',
                    'order',
                    $megasyncOrder['id_megasync_order']
                );
                return false;
            }
            
            // Mapear estado de PrestaShop a estado externo
            $externalStatus = $this->mapPrestashopStatus($newStatus->id);
            
            try {
                $result = $this->communicationService->updateOrderStatus(
                    $shop['url'],
                    $shop['api_key'],
                    $megasyncOrder['id_order_origin'],
                    $externalStatus,
                    'Estado actualizado desde tienda destino'
                );
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    $this->logService->log(
                        'Estado de pedido origen #' . $megasyncOrder['id_order_origin'] . ' actualizado a: ' . $externalStatus,
                        'success',
                        'order',
                        $megasyncOrder['id_megasync_order']
                    );
                    return true;
                }
                
                $this->logService->log(
                    'Error al actualizar estado de pedido origen #' . $megasyncOrder['id_order_origin'] . ': ' . 
                    (isset($result['message']) ? $result['message'] : 'Error desconocido'),
                    'error',
                    'order',
                    $megasyncOrder['id_megasync_order']
                );
            } catch (Exception $e) {
                $this->logService->log(
                    'Excepción al actualizar estado de pedido origen #' . $megasyncOrder['id_order_origin'] . ': ' . $e->getMessage(),
                    'error',
                    'order',
                    $megasyncOrder['id_megasync_order']
                );
            }
        }
        
        return false;
    }
    
    /**
     * Mapea un estado de PrestaShop a uno externo
     *
     * @param int $prestashopStatusId ID del estado en PrestaShop
     * @return string Estado externo
     */
    protected function mapPrestashopStatus($prestashopStatusId)
    {
        // Estos IDs pueden variar según la instalación, ajustar según sea necesario
        $statusMap = [
            1 => 'awaiting_payment',          // Esperando pago por transferencia
            2 => 'payment_accepted',          // Pago aceptado
            3 => 'processing',                // Preparación en curso
            4 => 'shipped',                   // Enviado
            5 => 'delivered',                 // Entregado
            6 => 'canceled',                  // Cancelado
            7 => 'refunded',                  // Reembolsado
            8 => 'payment_error',             // Error en pago
            9 => 'awaiting_pickup',           // Pendiente de recogida en tienda
            10 => 'awaiting_bank_wire',       // Esperando pago por transferencia
            11 => 'awaiting_cashondelivery',  // Esperando pago contra reembolso
            12 => 'awaiting_check',           // Esperando pago por cheque
        ];
        
        return isset($statusMap[$prestashopStatusId]) ? $statusMap[$prestashopStatusId] : 'processing';
    }
    
    /**
     * Importa un pedido desde una tienda hija
     *
     * @param int $id_megasync_order ID del pedido a importar
     * @return array Resultado de la importación
     */
    public function importOrder($id_megasync_order)
    {
        // Obtener datos del pedido
        $order = $this->getOrderById($id_megasync_order);
        
        if (!$order) {
            return [
                'status' => 'error',
                'message' => 'Pedido no encontrado'
            ];
        }
        
        // Verificar si ya está importado
        if ($order['imported']) {
            return [
                'status' => 'error',
                'message' => 'Este pedido ya ha sido importado'
            ];
        }
        
        // Obtener tienda
        $shop = $this->shopManager->getShopById($order['id_shop']);
        
        if (!$shop) {
            return [
                'status' => 'error',
                'message' => 'Tienda no encontrada'
            ];
        }
        
        // Obtener detalles del pedido de la tienda origen
        $externalOrder = $this->getExternalOrderDetails($order['id_shop'], $order['id_order_origin']);
        
        if (!$externalOrder) {
            return [
                'status' => 'error',
                'message' => 'No se pudo obtener información del pedido de la tienda origen'
            ];
        }
        
        // Determinar el modo de importación
        $orderMode = (int)$shop['order_mode'];
        
        try {
            // Proceso de importación según el modo
            switch ($orderMode) {
                case 1: // Cliente y direcciones fijas
                    $result = $this->importOrderMode1($order, $externalOrder, $shop);
                    break;
                    
                case 2: // Conservar direcciones originales
                    $result = $this->importOrderMode2($order, $externalOrder, $shop);
                    break;
                    
                case 3: // Mixto - Cliente fijo, dirección envío original
                    $result = $this->importOrderMode3($order, $externalOrder, $shop);
                    break;
                    
                default:
                    return [
                        'status' => 'error',
                        'message' => 'Modo de importación no válido: ' . $orderMode
                    ];
            }
            
            if ($result['status'] === 'success') {
                // Actualizar pedido como importado
                $id_order = $result['id_order'];
                
                Db::getInstance()->update('megasync_orders', [
                    'id_order_destination' => (int)$id_order,
                    'imported' => 1,
                    'status' => 'imported',
                    'date_upd' => date('Y-m-d H:i:s')
                ], 'id_megasync_order = ' . (int)$id_megasync_order);
                
                // Añadir registro al historial
                $this->addOrderHistory(
                    $id_megasync_order,
                    'imported',
                    'Pedido importado correctamente. ID en tienda destino: ' . $id_order
                );
                
                $this->logService->log(
                    'Pedido #' . $id_megasync_order . ' importado correctamente. ID en tienda destino: ' . $id_order,
                    'success',
                    'order',
                    $id_megasync_order
                );
                
                return [
                    'status' => 'success',
                    'message' => 'Pedido importado correctamente',
                    'id_order' => $id_order
                ];
            }
            
            // Si llegamos aquí, es que falló la importación
            $this->updateOrderStatus(
                $id_megasync_order,
                'error',
                'Error al importar pedido: ' . $result['message']
            );
            
            return $result;
        } catch (Exception $e) {
            $this->updateOrderStatus(
                $id_megasync_order,
                'error',
                'Excepción al importar pedido: ' . $e->getMessage()
            );
            
            $this->logService->log(
                'Excepción al importar pedido #' . $id_megasync_order . ': ' . $e->getMessage(),
                'error',
                'order',
                $id_megasync_order
            );
            
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Importa un pedido con modo 1 (Cliente y direcciones fijas)
     *
     * @param array $order Datos del pedido en megasync
     * @param array $externalOrder Datos del pedido externo
     * @param array $shop Datos de la tienda
     * @return array Resultado de la importación
     */
    protected function importOrderMode1($order, $externalOrder, $shop)
    {
        // Verificar cliente fijo
        $id_customer = (int)$shop['fixed_customer_id'];
        
        if (!$id_customer) {
            return [
                'status' => 'error',
                'message' => 'No se ha configurado un cliente fijo para esta tienda'
            ];
        }
        
        $customer = new Customer($id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            return [
                'status' => 'error',
                'message' => 'Cliente fijo no encontrado'
            ];
        }
        
        // Obtener direcciones del cliente
        $addresses = $customer->getAddresses($this->context->language->id);
        
        if (empty($addresses)) {
            return [
                'status' => 'error',
                'message' => 'El cliente fijo no tiene direcciones asociadas'
            ];
        }
        
        // Usar la primera dirección
        $address = new Address($addresses[0]['id_address']);
        
        // Obtener detalles de productos
        $orderDetails = $this->getOrderDetails($order['id_megasync_order']);
        
        if (empty($orderDetails)) {
            return [
                'status' => 'error',
                'message' => 'No hay productos en el pedido'
            ];
        }
        
        // Crear carrito
        $cart = new Cart();
        $cart->id_customer = $id_customer;
        $cart->id_address_delivery = $address->id;
        $cart->id_address_invoice = $address->id;
        $cart->id_currency = $order['currency_id'];
        $cart->id_lang = $this->context->language->id;
        $cart->secure_key = $customer->secure_key;
        
        if (!$cart->add()) {
            return [
                'status' => 'error',
                'message' => 'Error al crear el carrito'
            ];
        }
        
        // Añadir productos al carrito
        foreach ($orderDetails as $detail) {
            $id_product = $detail['product_id'];
            $id_product_attribute = $detail['product_attribute_id'] ?: 0;
            $quantity = $detail['product_quantity'];
            
            // Verificar disponibilidad
            $product = new Product($id_product);
            
            if (!Validate::isLoadedObject($product)) {
                continue; // Omitir productos no encontrados
            }
            
            $cart->updateQty($quantity, $id_product, $id_product_attribute);
        }
        
        // Si el carrito está vacío, cancelar
        if (!$cart->getProducts()) {
            return [
                'status' => 'error',
                'message' => 'El carrito está vacío después de procesar los productos'
            ];
        }
        
        // Seleccionar transportista (usamos el primero disponible)
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, ALL_CARRIERS);
        
        if (empty($carriers)) {
            return [
                'status' => 'error',
                'message' => 'No hay transportistas disponibles'
            ];
        }
        
        $id_carrier = $carriers[0]['id_carrier'];
        $cart->id_carrier = $id_carrier;
        $cart->update();
        
        // Obtener método de pago (usamos transferencia bancaria como fallback)
        $payment_module = 'bankwire';
        
        // Crear pedido
        $payment = $externalOrder['payment_method'] ?: 'Transferencia bancaria';
        $this->context->currency = new Currency($order['currency_id']);
        
        // Importar el pedido
        try {
            $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_PAYMENT'),  // Estado "Pago aceptado"
                (float)$cart->getOrderTotal(true, Cart::BOTH),
                $payment,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );
            
            $id_order = $this->module->currentOrder;
            
            return [
                'status' => 'success',
                'message' => 'Pedido importado correctamente',
                'id_order' => $id_order
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al validar el pedido: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Importa un pedido con modo 2 (Conservar direcciones originales)
     * 
     * @param array $order Datos del pedido en megasync
     * @param array $externalOrder Datos del pedido externo
     * @param array $shop Datos de la tienda
     * @return array Resultado de la importación
     */
    protected function importOrderMode2($order, $externalOrder, $shop)
    {
        // Implementar la lógica para importar pedido conservando direcciones originales
        // Esta es una implementación básica, habría que ampliarla con la lógica completa
        
        // Verificar si tenemos datos del cliente
        if (!isset($externalOrder['customer']) || empty($externalOrder['customer'])) {
            return [
                'status' => 'error',
                'message' => 'No se encontraron datos del cliente en el pedido original'
            ];
        }
        
        $customer_data = $externalOrder['customer'];
        
        // Buscar si el cliente ya existe
        $id_customer = Customer::customerExists($customer_data['email'], true);
        
        if (!$id_customer) {
            // Crear nuevo cliente
            $customer = new Customer();
            $customer->firstname = $customer_data['firstname'];
            $customer->lastname = $customer_data['lastname'];
            $customer->email = $customer_data['email'];
            $customer->passwd = Tools::encrypt(Tools::passwdGen());
            $customer->active = 1;
            
            if (!$customer->add()) {
                return [
                    'status' => 'error',
                    'message' => 'Error al crear el cliente'
                ];
            }
            
            $id_customer = $customer->id;
        } else {
            $customer = new Customer($id_customer);
        }
        
        // Verificar direcciones
        if (!isset($externalOrder['addresses']) || empty($externalOrder['addresses'])) {
            return [
                'status' => 'error',
                'message' => 'No se encontraron direcciones en el pedido original'
            ];
        }
        
        $addresses = $externalOrder['addresses'];
        $id_address_delivery = 0;
        $id_address_invoice = 0;
        
        // Procesar direcciones
        foreach ($addresses as $addr_data) {
            // Buscar si la dirección ya existe
            $addr_exists = Address::getAddressIdByAddress(
                $addr_data['address1'],
                $addr_data['address2'],
                $addr_data['postcode'],
                $addr_data['city'],
                $addr_data['id_country']
            );
            
            if ($addr_exists && Address::getCustomerIdByAddressId($addr_exists) == $id_customer) {
                $address_id = $addr_exists;
            } else {
                // Crear nueva dirección
                $address = new Address();
                $address->id_customer = $id_customer;
                $address->firstname = $customer->firstname;
                $address->lastname = $customer->lastname;
                $address->address1 = $addr_data['address1'];
                $address->address2 = $addr_data['address2'] ?? '';
                $address->postcode = $addr_data['postcode'];
                $address->city = $addr_data['city'];
                $address->id_country = $addr_data['id_country'];
                $address->phone = $addr_data['phone'] ?? '';
                $address->phone_mobile = $addr_data['phone_mobile'] ?? '';
                $address->alias = $addr_data['type'] == 'delivery' ? 'Dirección de envío' : 'Dirección de facturación';
                
                if (!$address->add()) {
                    return [
                        'status' => 'error',
                        'message' => 'Error al crear la dirección'
                    ];
                }
                
                $address_id = $address->id;
            }
            
            // Asignar ID según el tipo
            if ($addr_data['type'] == 'delivery') {
                $id_address_delivery = $address_id;
            } else {
                $id_address_invoice = $address_id;
            }
        }
        
        // Si falta alguna dirección, usar la misma para ambas
        if (!$id_address_delivery) {
            $id_address_delivery = $id_address_invoice;
        }
        if (!$id_address_invoice) {
            $id_address_invoice = $id_address_delivery;
        }
        
        // Obtener detalles de productos
        $orderDetails = $this->getOrderDetails($order['id_megasync_order']);
        
        if (empty($orderDetails)) {
            return [
                'status' => 'error',
                'message' => 'No hay productos en el pedido'
            ];
        }
        
        // Crear carrito
        $cart = new Cart();
        $cart->id_customer = $id_customer;
        $cart->id_address_delivery = $id_address_delivery;
        $cart->id_address_invoice = $id_address_invoice;
        $cart->id_currency = $order['currency_id'];
        $cart->id_lang = $this->context->language->id;
        $cart->secure_key = $customer->secure_key;
        
        if (!$cart->add()) {
            return [
                'status' => 'error',
                'message' => 'Error al crear el carrito'
            ];
        }
        
        // Añadir productos al carrito
        foreach ($orderDetails as $detail) {
            $cart->updateQty(
                $detail['product_quantity'],
                $detail['product_id'],
                $detail['product_attribute_id'] ?: 0
            );
        }
        
        // Resto de la implementación similar al Modo 1
        // ...
        
        // Ejemplo simplificado para completar la función:
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, ALL_CARRIERS);
        $id_carrier = $carriers[0]['id_carrier'];
        $cart->id_carrier = $id_carrier;
        $cart->update();
        
        $payment = $externalOrder['payment_method'] ?: 'Transferencia bancaria';
        $this->context->currency = new Currency($order['currency_id']);
        
        try {
            $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                (float)$cart->getOrderTotal(true, Cart::BOTH),
                $payment,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );
            
            $id_order = $this->module->currentOrder;
            
            return [
                'status' => 'success',
                'message' => 'Pedido importado correctamente (Modo 2)',
                'id_order' => $id_order
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al validar el pedido: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Importa un pedido con modo 3 (Mixto - Cliente fijo, dirección envío original)
     * 
     * @param array $order Datos del pedido en megasync
     * @param array $externalOrder Datos del pedido externo
     * @param array $shop Datos de la tienda
     * @return array Resultado de la importación
     */
    protected function importOrderMode3($order, $externalOrder, $shop)
    {
        // Verificar cliente fijo
        $id_customer = (int)$shop['fixed_customer_id'];
        
        if (!$id_customer) {
            return [
                'status' => 'error',
                'message' => 'No se ha configurado un cliente fijo para esta tienda'
            ];
        }
        
        $customer = new Customer($id_customer);
        
        if (!Validate::isLoadedObject($customer)) {
            return [
                'status' => 'error',
                'message' => 'Cliente fijo no encontrado'
            ];
        }
        
        // Obtener dirección de facturación del cliente
        $addresses = $customer->getAddresses($this->context->language->id);
        
        if (empty($addresses)) {
            return [
                'status' => 'error',
                'message' => 'El cliente fijo no tiene direcciones asociadas'
            ];
        }
        
        $id_address_invoice = $addresses[0]['id_address'];
        
        // Verificar dirección de envío original
        if (!isset($externalOrder['shipping_address']) || empty($externalOrder['shipping_address'])) {
            // Si no hay dirección de envío, usar la de facturación
            $id_address_delivery = $id_address_invoice;
        } else {
            // Crear nueva dirección de envío basada en la original
            $addr_data = $externalOrder['shipping_address'];
            
            $address = new Address();
            $address->id_customer = $id_customer;
            $address->firstname = $addr_data['firstname'] ?? $customer->firstname;
            $address->lastname = $addr_data['lastname'] ?? $customer->lastname;
            $address->address1 = $addr_data['address1'];
            $address->address2 = $addr_data['address2'] ?? '';
            $address->postcode = $addr_data['postcode'];
            $address->city = $addr_data['city'];
            $address->id_country = $addr_data['id_country'];
            $address->phone = $addr_data['phone'] ?? '';
            $address->phone_mobile = $addr_data['phone_mobile'] ?? '';
            $address->alias = 'Dirección de envío (pedido #' . $order['id_order_origin'] . ')';
            
            if (!$address->add()) {
                // Si falla, usar la dirección de facturación
                $id_address_delivery = $id_address_invoice;
            } else {
                $id_address_delivery = $address->id;
            }
        }
        
        // Obtener detalles de productos
        $orderDetails = $this->getOrderDetails($order['id_megasync_order']);
        
        if (empty($orderDetails)) {
            return [
                'status' => 'error',
                'message' => 'No hay productos en el pedido'
            ];
        }
        
        // Crear carrito
        $cart = new Cart();
        $cart->id_customer = $id_customer;
        $cart->id_address_delivery = $id_address_delivery;
        $cart->id_address_invoice = $id_address_invoice;
        $cart->id_currency = $order['currency_id'];
        $cart->id_lang = $this->context->language->id;
        $cart->secure_key = $customer->secure_key;
        
        if (!$cart->add()) {
            return [
                'status' => 'error',
                'message' => 'Error al crear el carrito'
            ];
        }
        
        // Añadir productos al carrito
        foreach ($orderDetails as $detail) {
            $cart->updateQty(
                $detail['product_quantity'],
                $detail['product_id'],
                $detail['product_attribute_id'] ?: 0
            );
        }
        
        // Resto de la implementación similar a los modos anteriores
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, ALL_CARRIERS);
        $id_carrier = $carriers[0]['id_carrier'];
        $cart->id_carrier = $id_carrier;
        $cart->update();
        
        $payment = $externalOrder['payment_method'] ?: 'Transferencia bancaria';
        $this->context->currency = new Currency($order['currency_id']);
        
        try {
            $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('PS_OS_PAYMENT'),
                (float)$cart->getOrderTotal(true, Cart::BOTH),
                $payment,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );
            
            $id_order = $this->module->currentOrder;
            
            return [
                'status' => 'success',
                'message' => 'Pedido importado correctamente (Modo 3)',
                'id_order' => $id_order
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al validar el pedido: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ejecuta la sincronización programada de pedidos
     *
     * @return array Resultado de la sincronización
     */
    public function runScheduledOrderSync()
    {
        // Verificar si está habilitada la sincronización programada
        if (!Configuration::get('MEGASYNC_SCHEDULED_ORDER_SYNC')) {
            return [
                'status' => 'warning',
                'message' => 'Sincronización programada de pedidos no está habilitada'
            ];
        }
        
        // Obtener tiendas según método de conversión
        $automaticShops = $this->shopManager->getShopsByConversionMethod('automatic');
        $cronShops = $this->shopManager->getShopsByConversionMethod('cron');
        
        // Para CRON procesamos solo las tiendas configuradas para CRON
        $shops = $cronShops;
        
        if (empty($shops)) {
            return [
                'status' => 'warning',
                'message' => 'No hay tiendas configuradas para sincronización de pedidos por CRON'
            ];
        }
        
        $results = [];
        $newOrders = 0;
        $errors = 0;
        
        // Procesar cada tienda
        foreach ($shops as $shop) {
            try {
                $shopResult = $this->syncOrdersFromShop($shop);
                
                $results[$shop['id_megasync_shop']] = $shopResult;
                
                if (isset($shopResult['new_orders'])) {
                    $newOrders += $shopResult['new_orders'];
                }
                
                if (isset($shopResult['errors'])) {
                    $errors += $shopResult['errors'];
                }
            } catch (Exception $e) {
                $results[$shop['id_megasync_shop']] = [
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
                
                $errors++;
                
                $this->logService->log(
                    'Error en sincronización programada de pedidos para tienda ' . $shop['name'] . ': ' . $e->getMessage(),
                    'error',
                    'order'
                );
            }
        }
        
        // Actualizar fecha de última sincronización
        Configuration::updateValue('MEGASYNC_LAST_ORDER_SYNC_DATE', date('Y-m-d H:i:s'));
        
        $this->logService->log(
            'Sincronización programada de pedidos completada: ' . $newOrders . ' nuevos pedidos, ' . $errors . ' errores',
            $errors > 0 ? 'warning' : 'success',
            'order'
        );
        
        return [
            'status' => 'completed',
            'message' => 'Sincronización de pedidos completada',
            'new_orders' => $newOrders,
            'errors' => $errors,
            'details' => $results
        ];
    }
    
    /**
     * Sincroniza pedidos desde una tienda específica
     *
     * @param array $shop Datos de la tienda
     * @return array Resultado de la sincronización
     */
    protected function syncOrdersFromShop($shop)
    {
        $id_shop = $shop['id_megasync_shop'];
        $lastSync = null;
        
        // Obtener fecha de última sincronización para esta tienda
        $lastSyncRecord = Db::getInstance()->getRow('
            SELECT MAX(`date_add`) as last_sync
            FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `id_shop` = '.(int)$id_shop
        );
        
        if ($lastSyncRecord && !empty($lastSyncRecord['last_sync'])) {
            $lastSync = $lastSyncRecord['last_sync'];
        }
        
        try {
            // Obtener pedidos pendientes
            $pendingOrders = $this->communicationService->getPendingOrders(
                $shop['url'],
                $shop['api_key'],
                $lastSync
            );
            
            if (!isset($pendingOrders['orders']) || !is_array($pendingOrders['orders'])) {
                return [
                    'status' => 'error',
                    'message' => 'Formato de respuesta inválido al obtener pedidos pendientes'
                ];
            }
            
            $orders = $pendingOrders['orders'];
            
            if (empty($orders)) {
                return [
                    'status' => 'success',
                    'message' => 'No hay nuevos pedidos para sincronizar',
                    'new_orders' => 0
                ];
            }
            
            $newOrders = 0;
            $errors = 0;
            $ordersData = [];
            
            // Verificar si agrupar pedidos
            if ($shop['group_orders'] && $shop['conversion_method'] == 'cron') {
                // Agrupar pedidos en uno solo
                $result = $this->createGroupedOrder($shop, $orders);
                
                if ($result['status'] === 'success') {
                    $newOrders = 1;
                    $ordersData[] = [
                        'id' => $result['id_megasync_order'],
                        'reference' => $result['reference'],
                        'status' => 'success'
                    ];
                } else {
                    $errors = 1;
                    $ordersData[] = [
                        'reference' => 'grouped',
                        'status' => 'error',
                        'message' => $result['message']
                    ];
                }
            } else {
                // Procesar cada pedido individualmente
                foreach ($orders as $orderData) {
                    $result = $this->createOrderFromExternalData($shop, $orderData);
                    
                    if ($result['status'] === 'success') {
                        $newOrders++;
                        $ordersData[] = [
                            'id' => $result['id_megasync_order'],
                            'reference' => $orderData['reference'],
                            'status' => 'success'
                        ];
                    } else {
                        $errors++;
                        $ordersData[] = [
                            'reference' => $orderData['reference'],
                            'status' => 'error',
                            'message' => $result['message']
                        ];
                    }
                }
            }
            
            $this->logService->log(
                'Sincronización de pedidos para tienda ' . $shop['name'] . ' completada: ' . 
                $newOrders . ' nuevos pedidos, ' . $errors . ' errores',
                $errors > 0 ? 'warning' : 'success',
                'order'
            );
            
            return [
                'status' => 'success',
                'message' => 'Sincronización de pedidos completada',
                'new_orders' => $newOrders,
                'errors' => $errors,
                'orders' => $ordersData
            ];
        } catch (Exception $e) {
            $this->logService->log(
                'Error en sincronización de pedidos para tienda ' . $shop['name'] . ': ' . $e->getMessage(),
                'error',
                'order'
            );
            
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crea un pedido agrupado a partir de varios pedidos externos
     *
     * @param array $shop Datos de la tienda
     * @param array $orders Datos de los pedidos externos
     * @return array Resultado de la operación
     */
    protected function createGroupedOrder($shop, $orders)
    {
        if (empty($orders)) {
            return [
                'status' => 'error',
                'message' => 'No hay pedidos para agrupar'
            ];
        }
        
        // Crear registro de pedido agrupado
        $currency = new Currency(Currency::getDefaultCurrency());
        $total = 0;
        
        // Sumar totales
        foreach ($orders as $order) {
            $total += (float)$order['total_paid'];
        }
        
        // Generar referencia única
        $reference = 'GROUPED-' . date('YmdHis') . '-' . $shop['id_megasync_shop'];
        
        // Insertar pedido agrupado
        $orderData = [
            'id_shop' => (int)$shop['id_megasync_shop'],
            'reference' => pSQL($reference),
            'id_order_origin' => 0, // No hay un ID origen específico
            'status' => 'pending',
            'total_paid' => (float)$total,
            'currency_id' => (int)$currency->id,
            'data' => json_encode(['grouped' => true, 'order_count' => count($orders)]),
            'imported' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        $result = Db::getInstance()->insert('megasync_orders', $orderData);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Error al crear el pedido agrupado'
            ];
        }
        
        $id_megasync_order = Db::getInstance()->Insert_ID();
        
        // Insertar detalles de todos los pedidos
        foreach ($orders as $order) {
            $orderDetails = $order['products'];
            
            foreach ($orderDetails as $detail) {
                // Buscar producto por referencia
                $product_id = 0;
                $product_attribute_id = 0;
                
                if (!empty($detail['reference'])) {
                    $product = Product::getIdByReference($detail['reference']);
                    
                    if ($product) {
                        $product_id = $product;
                        
                        // Buscar combinación si es necesario
                        if (!empty($detail['combination_reference'])) {
                            $combination = Db::getInstance()->getRow('
                                SELECT `id_product_attribute`
                                FROM `'._DB_PREFIX_.'product_attribute`
                                WHERE `reference` = "'.pSQL($detail['combination_reference']).'"
                                AND `id_product` = '.(int)$product_id
                            );
                            
                            if ($combination) {
                                $product_attribute_id = $combination['id_product_attribute'];
                            }
                        }
                    }
                }
                
                // Si no encontramos por referencia, intentar por ID
                if (!$product_id && !empty($detail['id_product'])) {
                    $product_id = $detail['id_product'];
                    $product_attribute_id = !empty($detail['id_product_attribute']) ? $detail['id_product_attribute'] : 0;
                }
                
                // Si tenemos un producto válido, añadirlo
                if ($product_id) {
                    $detailData = [
                        'id_megasync_order' => (int)$id_megasync_order,
                        'id_order_detail_origin' => !empty($detail['id_order_detail']) ? (int)$detail['id_order_detail'] : 0,
                        'product_id' => (int)$product_id,
                        'product_attribute_id' => (int)$product_attribute_id,
                        'product_reference' => pSQL($detail['reference']),
                        'product_name' => pSQL($detail['name']),
                        'product_quantity' => (int)$detail['quantity'],
                        'product_price' => (float)$detail['price'],
                        'data' => json_encode($detail)
                    ];
                    
                    Db::getInstance()->insert('megasync_order_detail', $detailData);
                }
            }
        }
        
        // Añadir registro al historial
        $this->addOrderHistory(
            $id_megasync_order,
            'pending',
            'Pedido agrupado creado con ' . count($orders) . ' pedidos'
        );
        
        $this->logService->log(
            'Pedido agrupado #' . $id_megasync_order . ' creado con ' . count($orders) . ' pedidos',
            'success',
            'order',
            $id_megasync_order
        );
        
        return [
            'status' => 'success',
            'message' => 'Pedido agrupado creado correctamente',
            'id_megasync_order' => $id_megasync_order,
            'reference' => $reference
        ];
    }
    
    /**
     * Crea un registro de pedido a partir de datos externos
     *
     * @param array $shop Datos de la tienda
     * @param array $orderData Datos del pedido externo
     * @return array Resultado de la operación
     */
    protected function createOrderFromExternalData($shop, $orderData)
    {
        if (empty($orderData)) {
            return [
                'status' => 'error',
                'message' => 'Datos de pedido vacíos'
            ];
        }
        
        // Verificar si ya existe este pedido
        $existingOrder = Db::getInstance()->getRow('
            SELECT * FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `id_shop` = '.(int)$shop['id_megasync_shop'].'
            AND `id_order_origin` = '.(int)$orderData['id_order']
        );
        
        if ($existingOrder) {
            return [
                'status' => 'warning',
                'message' => 'El pedido ya existe en el sistema',
                'id_megasync_order' => $existingOrder['id_megasync_order']
            ];
        }
        
        // Obtener moneda
        $currency_id = Currency::getDefaultCurrency()->id;
        if (!empty($orderData['id_currency'])) {
            $currency = new Currency($orderData['id_currency']);
            if (Validate::isLoadedObject($currency)) {
                $currency_id = $currency->id;
            }
        }
        
        // Insertar pedido
        $orderInsertData = [
            'id_shop' => (int)$shop['id_megasync_shop'],
            'reference' => pSQL($orderData['reference']),
            'id_order_origin' => (int)$orderData['id_order'],
            'status' => 'pending',
            'total_paid' => (float)$orderData['total_paid'],
            'currency_id' => (int)$currency_id,
            'data' => json_encode($orderData),
            'imported' => 0,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        $result = Db::getInstance()->insert('megasync_orders', $orderInsertData);
        
        if (!$result) {
            return [
                'status' => 'error',
                'message' => 'Error al crear el registro de pedido'
            ];
        }
        
        $id_megasync_order = Db::getInstance()->Insert_ID();
        
        // Insertar detalles del pedido
        if (isset($orderData['products']) && is_array($orderData['products'])) {
            foreach ($orderData['products'] as $detail) {
                // Buscar producto por referencia
                $product_id = 0;
                $product_attribute_id = 0;
                
                if (!empty($detail['reference'])) {
                    $product = Product::getIdByReference($detail['reference']);
                    
                    if ($product) {
                        $product_id = $product;
                        
                        // Buscar combinación si es necesario
                        if (!empty($detail['combination_reference'])) {
                            $combination = Db::getInstance()->getRow('
                                SELECT `id_product_attribute`
                                FROM `'._DB_PREFIX_.'product_attribute`
                                WHERE `reference` = "'.pSQL($detail['combination_reference']).'"
                                AND `id_product` = '.(int)$product_id
                            );
                            
                            if ($combination) {
                                $product_attribute_id = $combination['id_product_attribute'];
                            }
                        }
                    }
                }
                
                // Si no encontramos por referencia, intentar por ID
                if (!$product_id && !empty($detail['id_product'])) {
                    $product_id = $detail['id_product'];
                    $product_attribute_id = !empty($detail['id_product_attribute']) ? $detail['id_product_attribute'] : 0;
                }
                
                // Si tenemos un producto válido o no, lo añadimos de todas formas
                $detailData = [
                    'id_megasync_order' => (int)$id_megasync_order,
                    'id_order_detail_origin' => !empty($detail['id_order_detail']) ? (int)$detail['id_order_detail'] : 0,
                    'product_id' => (int)$product_id,
                    'product_attribute_id' => (int)$product_attribute_id,
                    'product_reference' => pSQL($detail['reference'] ?? ''),
                    'product_name' => pSQL($detail['name'] ?? 'Producto sin nombre'),
                    'product_quantity' => (int)($detail['quantity'] ?? 1),
                    'product_price' => (float)($detail['price'] ?? 0),
                    'data' => json_encode($detail)
                ];
                
                Db::getInstance()->insert('megasync_order_detail', $detailData);
            }
        }
        
        // Añadir registro al historial
        $this->addOrderHistory(
            $id_megasync_order,
            'pending',
            'Pedido creado desde tienda ' . $shop['name']
        );
        
        $this->logService->log(
            'Nuevo pedido #' . $id_megasync_order . ' creado desde tienda ' . $shop['name'] . ' (Origen: ' . $orderData['reference'] . ')',
            'success',
            'order',
            $id_megasync_order
        );
        
        // Importar automáticamente si corresponde
        if ($shop['conversion_method'] == 'automatic') {
            $this->importOrder($id_megasync_order);
        }
        
        return [
            'status' => 'success',
            'message' => 'Pedido creado correctamente',
            'id_megasync_order' => $id_megasync_order
        ];
    }
    
    /**
     * Obtiene estadísticas de pedidos
     *
     * @param int $days Número de días para las estadísticas
     * @return array Estadísticas
     */
    public function getOrderStats($days = 7)
    {
        $stats = [];
        $dateLimit = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
        
        // Total de pedidos
        $total = Db::getInstance()->getValue('
            SELECT COUNT(*) FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `date_add` >= "'.pSQL($dateLimit).'"
        ');
        
        // Pedidos por estado
        $byStatus = Db::getInstance()->executeS('
            SELECT `status`, COUNT(*) as count
            FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `date_add` >= "'.pSQL($dateLimit).'"
            GROUP BY `status`
        ');
        
        // Pedidos por tienda
        $byShop = Db::getInstance()->executeS('
            SELECT o.`id_shop`, s.`name`, COUNT(*) as count
            FROM `'._DB_PREFIX_.'megasync_orders` o
            LEFT JOIN `'._DB_PREFIX_.'megasync_shops` s ON (o.`id_shop` = s.`id_megasync_shop`)
            WHERE o.`date_add` >= "'.pSQL($dateLimit).'"
            GROUP BY o.`id_shop`
        ');
        
        // Estadísticas diarias
        $daily = Db::getInstance()->executeS('
            SELECT DATE(`date_add`) as day, COUNT(*) as count
            FROM `'._DB_PREFIX_.'megasync_orders`
            WHERE `date_add` >= "'.pSQL($dateLimit).'"
            GROUP BY DATE(`date_add`)
            ORDER BY day ASC
        ');
        
        // Formatear resultados
        $stats['total'] = (int)$total;
        
        $stats['by_status'] = [
            'pending' => 0,
            'processing' => 0,
            'imported' => 0,
            'error' => 0,
            'cancelled' => 0
        ];
        
        foreach ($byStatus as $row) {
            $stats['by_status'][$row['status']] = (int)$row['count'];
        }
        
        $stats['by_shop'] = [];
        foreach ($byShop as $row) {
            $stats['by_shop'][] = [
                'id_shop' => $row['id_shop'],
                'name' => $row['name'],
                'count' => (int)$row['count']
            ];
        }
        
        $stats['daily'] = [];
        $currentDate = strtotime('-'.$days.' days');
        $endDate = time();
        
        while ($currentDate <= $endDate) {
            $day = date('Y-m-d', $currentDate);
            $stats['daily'][$day] = 0;
            $currentDate = strtotime('+1 day', $currentDate);
        }
        
        foreach ($daily as $row) {
            $stats['daily'][$row['day']] = (int)$row['count'];
        }
        
        return $stats;
    }
}