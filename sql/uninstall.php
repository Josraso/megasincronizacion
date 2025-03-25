<?php
/**
 * SQL para la desinstalación del módulo Megasincronización
 */

$sql = array();

// Eliminar tablas
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'megasync_shops`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'megasync_orders`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'megasync_order_detail`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'megasync_log`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'megasync_order_history`';

// Eliminar configuraciones
$sql[] = 'DELETE FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` LIKE "MEGASYNC_%"';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
