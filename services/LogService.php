<?php
/**
 * Servicio de registro de logs para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class LogService
{
    /**
     * Tipos de log válidos
     */
    const LOG_TYPES = [
        'success',
        'error',
        'warning',
        'info'
    ];

    /**
     * Categorías de log
     */
    const LOG_CATEGORIES = [
        'general',
        'shop',
        'connection',
        'stock',
        'stock-batch',
        'price',
        'order',
        'customer',
        'address',
        'configuration'
    ];

    /**
     * Número máximo de logs a mantener (0 = sin límite)
     */
    const MAX_LOGS = 10000;

    /**
     * Registra un mensaje en el log
     *
     * @param string $message Mensaje a registrar
     * @param string $type Tipo de log (success, error, warning, info)
     * @param string $category Categoría del log
     * @param int|null $id_related ID relacionado (producto, pedido, etc.)
     * @return bool Resultado de la operación
     */
    public function log($message, $type = 'info', $category = 'general', $id_related = null)
    {
        // Validar tipo de log
        if (!in_array($type, self::LOG_TYPES)) {
            $type = 'info';
        }

        // Validar categoría
        if (!in_array($category, self::LOG_CATEGORIES)) {
            $category = 'general';
        }

        // Insertar en la base de datos
        $result = Db::getInstance()->insert('megasync_log', [
            'message' => pSQL($message),
            'type' => pSQL($type),
            'category' => pSQL($category),
            'id_related' => $id_related ? (int)$id_related : null,
            'employee_id' => Context::getContext()->employee ? (int)Context::getContext()->employee->id : null,
            'employee_name' => Context::getContext()->employee ? pSQL(Context::getContext()->employee->firstname . ' ' . Context::getContext()->employee->lastname) : null,
            'date_add' => date('Y-m-d H:i:s')
        ]);

        // Purgar logs antiguos si es necesario
        if (self::MAX_LOGS > 0) {
            $this->purgeLogs();
        }

        return $result;
    }

    /**
     * Obtiene los logs según criterios de filtrado
     *
     * @param int $limit Número de logs a obtener
     * @param int $offset Desplazamiento para paginación
     * @param array $filters Filtros adicionales
     * @return array Logs obtenidos
     */
    public function getLogs($limit = 50, $offset = 0, $filters = [])
    {
        // Construir la consulta SQL base
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'megasync_log` WHERE 1 ';
        $sqlCount = 'SELECT COUNT(*) as total FROM `'._DB_PREFIX_.'megasync_log` WHERE 1 ';
        
        // Aplicar filtros
        $sqlFilters = '';
        if (!empty($filters)) {
            if (isset($filters['type']) && in_array($filters['type'], self::LOG_TYPES)) {
                $sqlFilters .= ' AND `type` = "'.pSQL($filters['type']).'"';
            }
            
            if (isset($filters['category']) && in_array($filters['category'], self::LOG_CATEGORIES)) {
                $sqlFilters .= ' AND `category` = "'.pSQL($filters['category']).'"';
            }
            
            if (isset($filters['id_related']) && $filters['id_related'] > 0) {
                $sqlFilters .= ' AND `id_related` = '.(int)$filters['id_related'];
            }
            
            if (isset($filters['employee_id']) && $filters['employee_id'] > 0) {
                $sqlFilters .= ' AND `employee_id` = '.(int)$filters['employee_id'];
            }
            
            if (isset($filters['date_from']) && Validate::isDate($filters['date_from'])) {
                $sqlFilters .= ' AND `date_add` >= "'.pSQL($filters['date_from']).' 00:00:00"';
            }
            
            if (isset($filters['date_to']) && Validate::isDate($filters['date_to'])) {
                $sqlFilters .= ' AND `date_add` <= "'.pSQL($filters['date_to']).' 23:59:59"';
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sqlFilters .= ' AND `message` LIKE "%'.pSQL($filters['search']).'%"';
            }
        }
        
        // Añadir filtros a las consultas
        $sql .= $sqlFilters;
        $sqlCount .= $sqlFilters;
        
        // Ordenar y limitar resultados
        $sql .= ' ORDER BY `date_add` DESC LIMIT '.(int)$offset.', '.(int)$limit;
        
        // Ejecutar consultas
        $logs = Db::getInstance()->executeS($sql);
        $count = Db::getInstance()->getRow($sqlCount);
        
        return [
            'logs' => $logs,
            'total' => $count['total'],
            'limit' => $limit,
            'offset' => $offset,
            'pages' => ceil($count['total'] / $limit)
        ];
    }

    /**
     * Elimina los logs más antiguos para mantener el límite configurado
     *
     * @return bool Resultado de la operación
     */
    protected function purgeLogs()
    {
        // Contar logs actuales
        $countSql = 'SELECT COUNT(*) as total FROM `'._DB_PREFIX_.'megasync_log`';
        $count = Db::getInstance()->getRow($countSql);
        
        // Si no excedemos el límite, no hacer nada
        if ($count['total'] <= self::MAX_LOGS) {
            return true;
        }
        
        // Calcular cuántos logs eliminar
        $toDelete = $count['total'] - self::MAX_LOGS;
        
        // Eliminar los logs más antiguos
        $deleteSql = 'DELETE FROM `'._DB_PREFIX_.'megasync_log` 
                     ORDER BY `date_add` ASC 
                     LIMIT '.(int)$toDelete;
        
        return Db::getInstance()->execute($deleteSql);
    }

    /**
     * Elimina todos los logs
     *
     * @return bool Resultado de la operación
     */
    public function clearAllLogs()
    {
        return Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.'megasync_log`');
    }

    /**
     * Elimina logs según criterios de filtrado
     *
     * @param array $filters Filtros para los logs a eliminar
     * @return bool Resultado de la operación
     */
    public function deleteLogs($filters = [])
    {
        // Construir la consulta SQL base
        $sql = 'DELETE FROM `'._DB_PREFIX_.'megasync_log` WHERE 1 ';
        
        // Aplicar filtros
        if (!empty($filters)) {
            if (isset($filters['type']) && in_array($filters['type'], self::LOG_TYPES)) {
                $sql .= ' AND `type` = "'.pSQL($filters['type']).'"';
            }
            
            if (isset($filters['category']) && in_array($filters['category'], self::LOG_CATEGORIES)) {
                $sql .= ' AND `category` = "'.pSQL($filters['category']).'"';
            }
            
            if (isset($filters['id_related']) && $filters['id_related'] > 0) {
                $sql .= ' AND `id_related` = '.(int)$filters['id_related'];
            }
            
            if (isset($filters['employee_id']) && $filters['employee_id'] > 0) {
                $sql .= ' AND `employee_id` = '.(int)$filters['employee_id'];
            }
            
            if (isset($filters['date_from']) && Validate::isDate($filters['date_from'])) {
                $sql .= ' AND `date_add` >= "'.pSQL($filters['date_from']).' 00:00:00"';
            }
            
            if (isset($filters['date_to']) && Validate::isDate($filters['date_to'])) {
                $sql .= ' AND `date_add` <= "'.pSQL($filters['date_to']).' 23:59:59"';
            }
        }
        
        return Db::getInstance()->execute($sql);
    }

    /**
     * Obtiene estadísticas de los logs
     *
     * @param int $days Número de días para estadísticas (0 = todos)
     * @return array Estadísticas de logs
     */
    public function getLogStats($days = 30)
    {
        $stats = [];
        
        // Estadísticas por tipo
        $sqlByType = 'SELECT `type`, COUNT(*) as count 
                      FROM `'._DB_PREFIX_.'megasync_log` ';
        
        // Estadísticas por categoría
        $sqlByCategory = 'SELECT `category`, COUNT(*) as count 
                          FROM `'._DB_PREFIX_.'megasync_log` ';
        
        // Filtro de días si es necesario
        if ($days > 0) {
            $dateLimit = date('Y-m-d H:i:s', strtotime('-'.(int)$days.' days'));
            $sqlByType .= 'WHERE `date_add` >= "'.pSQL($dateLimit).'" ';
            $sqlByCategory .= 'WHERE `date_add` >= "'.pSQL($dateLimit).'" ';
        }
        
        // Completar consultas
        $sqlByType .= 'GROUP BY `type`';
        $sqlByCategory .= 'GROUP BY `category`';
        
        // Ejecutar consultas
        $statsByType = Db::getInstance()->executeS($sqlByType);
        $statsByCategory = Db::getInstance()->executeS($sqlByCategory);
        
        // Formatear resultados
        $stats['by_type'] = [];
        foreach (self::LOG_TYPES as $type) {
            $stats['by_type'][$type] = 0;
        }
        foreach ($statsByType as $row) {
            $stats['by_type'][$row['type']] = (int)$row['count'];
        }
        
        $stats['by_category'] = [];
        foreach (self::LOG_CATEGORIES as $category) {
            $stats['by_category'][$category] = 0;
        }
        foreach ($statsByCategory as $row) {
            $stats['by_category'][$row['category']] = (int)$row['count'];
        }
        
        // Estadísticas diarias para el período especificado
        if ($days > 0) {
            $sqlDaily = 'SELECT DATE(`date_add`) as day, COUNT(*) as count 
                         FROM `'._DB_PREFIX_.'megasync_log` 
                         WHERE `date_add` >= "'.pSQL($dateLimit).'" 
                         GROUP BY DATE(`date_add`) 
                         ORDER BY day ASC';
            
            $statsDaily = Db::getInstance()->executeS($sqlDaily);
            
            $stats['daily'] = [];
            // Inicializar array con todos los días del período
            $currentDate = strtotime('-'.(int)$days.' days');
            $endDate = time();
            while ($currentDate <= $endDate) {
                $day = date('Y-m-d', $currentDate);
                $stats['daily'][$day] = 0;
                $currentDate = strtotime('+1 day', $currentDate);
            }
            
            // Llenar con datos reales
            foreach ($statsDaily as $row) {
                $stats['daily'][$row['day']] = (int)$row['count'];
            }
        }
        
        return $stats;
    }

    /**
     * Exporta logs a CSV
     *
     * @param array $filters Filtros para los logs a exportar
     * @return string Contenido del CSV
     */
    public function exportLogsToCSV($filters = [])
    {
        // Obtener logs sin límite
        $logs = $this->getLogs(0, 0, $filters)['logs'];
        
        if (empty($logs)) {
            return 'No hay logs para exportar';
        }
        
        // Crear archivo CSV
        $output = fopen('php://temp', 'r+');
        
        // Cabeceras
        fputcsv($output, [
            'ID', 
            'Fecha', 
            'Tipo', 
            'Categoría', 
            'Mensaje', 
            'ID Relacionado', 
            'Empleado ID', 
            'Empleado'
        ]);
        
        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id_megasync_log'],
                $log['date_add'],
                $log['type'],
                $log['category'],
                $log['message'],
                $log['id_related'],
                $log['employee_id'],
                $log['employee_name']
            ]);
        }
        
        // Obtener contenido
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);
        
        return $csvContent;
    }
}
