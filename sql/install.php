<?php
/**
 * SQL para la instalación del módulo Megasincronización
 */

$sql = array();

// Tabla de tiendas
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'megasync_shops` (
    `id_megasync_shop` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `url` varchar(255) NOT NULL,
    `api_key` varchar(255) NOT NULL,
    `sync_stock` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `sync_stock_batch` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `sync_price` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `price_percentage` decimal(20,6) NOT NULL DEFAULT 0,
    `sync_base_price_only` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `order_mode` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `fixed_customer_id` int(10) unsigned NOT NULL DEFAULT 0,
    `conversion_method` varchar(32) NOT NULL DEFAULT "automatic",
    `group_orders` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_megasync_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Tabla de pedidos
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'megasync_orders` (
    `id_megasync_order` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_shop` int(10) unsigned NOT NULL,
    `reference` varchar(64) NOT NULL,
    `id_order_origin` int(10) unsigned NOT NULL,
    `id_order_destination` int(10) unsigned DEFAULT NULL,
    `status` varchar(32) NOT NULL DEFAULT "pending",
    `total_paid` decimal(20,6) NOT NULL DEFAULT 0,
    `currency_id` int(10) unsigned NOT NULL,
    `data` text,
    `imported` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `date_add` datetime NOT NULL,
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_megasync_order`),
    KEY `id_shop` (`id_shop`),
    KEY `id_order_origin` (`id_order_origin`),
    KEY `id_order_destination` (`id_order_destination`),
    KEY `status` (`status`),
    KEY `imported` (`imported`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Tabla de detalles de pedidos
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'megasync_order_detail` (
    `id_megasync_order_detail` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_megasync_order` int(10) unsigned NOT NULL,
    `id_order_detail_origin` int(10) unsigned NOT NULL,
    `id_order_detail_destination` int(10) unsigned DEFAULT NULL,
    `product_id` int(10) unsigned NOT NULL,
    `product_attribute_id` int(10) unsigned DEFAULT NULL,
    `product_reference` varchar(64) DEFAULT NULL,
    `product_name` varchar(255) NOT NULL,
    `product_quantity` int(10) unsigned NOT NULL DEFAULT 0,
    `product_price` decimal(20,6) NOT NULL DEFAULT 0,
    `data` text,
    PRIMARY KEY (`id_megasync_order_detail`),
    KEY `id_megasync_order` (`id_megasync_order`),
    KEY `product_id` (`product_id`, `product_attribute_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Tabla de logs
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'megasync_log` (
    `id_megasync_log` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `message` text NOT NULL,
    `type` varchar(32) NOT NULL DEFAULT "info",
    `category` varchar(32) NOT NULL DEFAULT "general",
    `id_related` int(10) unsigned DEFAULT NULL,
    `employee_id` int(10) unsigned DEFAULT NULL,
    `employee_name` varchar(255) DEFAULT NULL,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_megasync_log`),
    KEY `type` (`type`),
    KEY `category` (`category`),
    KEY `id_related` (`id_related`),
    KEY `employee_id` (`employee_id`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Tabla de historial de pedidos
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'megasync_order_history` (
    `id_megasync_order_history` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `id_megasync_order` int(10) unsigned NOT NULL,
    `status` varchar(32) NOT NULL,
    `comment` text,
    `date_add` datetime NOT NULL,
    PRIMARY KEY (`id_megasync_order_history`),
    KEY `id_megasync_order` (`id_megasync_order`),
    KEY `date_add` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Configuraciones por defecto
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'configuration` (`name`, `value`, `date_add`, `date_upd`) VALUES
    ("MEGASYNC_LIVE_MODE", "0", NOW(), NOW()),
    ("MEGASYNC_CRON_TOKEN", "' . md5(uniqid(rand(), true)) . '", NOW(), NOW()),
    ("MEGASYNC_SCHEDULED_STOCK_SYNC", "0", NOW(), NOW()),
    ("MEGASYNC_SCHEDULED_PRICE_SYNC", "0", NOW(), NOW()),
    ("MEGASYNC_SCHEDULED_ORDER_SYNC", "0", NOW(), NOW())
    ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `date_upd` = VALUES(`date_upd`)';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
