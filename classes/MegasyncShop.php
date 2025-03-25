<?php
/**
 * Clase MegasyncShop para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class MegasyncShop extends ObjectModel
{
    /** @var int ID de la tienda */
    public $id_megasync_shop;
    
    /** @var string Nombre de la tienda */
    public $name;
    
    /** @var string URL de la tienda */
    public $url;
    
    /** @var string Clave API para la tienda */
    public $api_key;
    
    /** @var bool Sincronización de stock activada */
    public $sync_stock;
    
    /** @var bool Procesar stock en lotes */
    public $sync_stock_batch;
    
    /** @var bool Sincronización de precios activada */
    public $sync_price;
    
    /** @var float Porcentaje de aumento de precio */
    public $price_percentage;
    
    /** @var bool Sincronizar solo precio base */
    public $sync_base_price_only;
    
    /** @var int Modo de pedido (1=fijo, 2=original, 3=mixto) */
    public $order_mode;
    
    /** @var int ID del cliente fijo para pedidos */
    public $fixed_customer_id;
    
    /** @var string Método de conversión (automatic, manual, cron) */
    public $conversion_method;
    
    /** @var bool Agrupar pedidos en CRON */
    public $group_orders;
    
    /** @var bool Tienda activa */
    public $active;
    
    /** @var string Fecha de creación */
    public $date_add;
    
    /** @var string Fecha de actualización */
    public $date_upd;
    
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'megasync_shops',
        'primary' => 'id_megasync_shop',
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING, 
                'validate' => 'isGenericName', 
                'required' => true, 
                'size' => 255
            ],
            'url' => [
                'type' => self::TYPE_STRING, 
                'validate' => 'isUrl', 
                'required' => true, 
                'size' => 255
            ],
            'api_key' => [
                'type' => self::TYPE_STRING, 
                'validate' => 'isString', 
                'required' => true, 
                'size' => 255
            ],
            'sync_stock' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'sync_stock_batch' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'sync_price' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'price_percentage' => [
                'type' => self::TYPE_FLOAT, 
                'validate' => 'isFloat'
            ],
            'sync_base_price_only' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'order_mode' => [
                'type' => self::TYPE_INT, 
                'validate' => 'isUnsignedInt'
            ],
            'fixed_customer_id' => [
                'type' => self::TYPE_INT, 
                'validate' => 'isUnsignedInt'
            ],
            'conversion_method' => [
                'type' => self::TYPE_STRING, 
                'validate' => 'isString', 
                'size' => 32
            ],
            'group_orders' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'active' => [
                'type' => self::TYPE_BOOL, 
                'validate' => 'isBool'
            ],
            'date_add' => [
                'type' => self::TYPE_DATE, 
                'validate' => 'isDate'
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE, 
                'validate' => 'isDate'
            ]
        ]
    ];
    
    /**
     * Constructor
     *
     * @param int|null $id ID de la tienda
     * @param int|null $id_lang ID del idioma (no se usa)
     * @param int|null $id_shop ID de la tienda en PrestaShop (no se usa)
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }
    
    /**
     * Asigna valores por defecto a una nueva tienda
     */
    public function getFieldsForAdd()
    {
        $fields = parent::getFieldsForAdd();
        
        if (!isset($fields['date_add']) || empty($fields['date_add'])) {
            $fields['date_add'] = date('Y-m-d H:i:s');
        }
        
        if (!isset($fields['date_upd']) || empty($fields['date_upd'])) {
            $fields['date_upd'] = date('Y-m-d H:i:s');
        }
        
        return $fields;
    }
    
    /**
     * Actualiza la fecha de modificación antes de guardar
     */
    public function update($null_values = false)
    {
        $this->date_upd = date('Y-m-d H:i:s');
        return parent::update($null_values);
    }
}