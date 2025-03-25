<?php
/**
 * Servicio de comunicación para el módulo MegaSincronización
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CommunicationService
{
    /**
     * Tiempo máximo de espera para las solicitudes en segundos
     */
    const REQUEST_TIMEOUT = 30;
    
    /**
     * Número máximo de reintentos para las solicitudes
     */
    const MAX_RETRIES = 3;
    
    /**
     * Espera entre reintentos en segundos
     */
    const RETRY_WAIT = 2;
    
    /**
     * Ruta base de la API en las tiendas destino
     */
    const API_BASE_PATH = '/modules/megasincronizacion/api/';
    
    /**
     * @var LogService
     */
    protected $logService;
    
    public function __construct()
    {
        $this->logService = new LogService();
    }
    
    /**
     * Envía una solicitud a la API de una tienda remota
     *
     * @param string $url URL base de la tienda
     * @param string $endpoint Punto final de la API
     * @param array $data Datos a enviar
     * @param string $method Método HTTP (POST, GET, etc)
     * @param int $retryCount Contador de reintentos
     * @return array Respuesta de la API
     * @throws Exception Si hay errores de comunicación
     */
    public function sendRequest($url, $endpoint, $data = [], $method = 'POST', $retryCount = 0)
    {
        // Normalizar URL
        $url = rtrim($url, '/');
        $apiUrl = $url . self::API_BASE_PATH . $endpoint;
        
        // Inicializar cURL
        $ch = curl_init();
        
        // Configurar opciones comunes
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Considerar cambiar en producción
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Considerar cambiar en producción
        
        // Añadir cabeceras
        $headers = [
            'Accept: application/json',
            'Cache-Control: no-cache',
            'X-Megasync-Version: 1.0.0',
            'X-Megasync-Source: ' . $this->getShopDomain()
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Configurar según el método
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($data)) {
                    $queryString = http_build_query($data);
                    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . $queryString);
                }
                break;
                
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                break;
                
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
                break;
                
            default:
                throw new Exception('Método HTTP no soportado: ' . $method);
        }
        
        // Registrar inicio de la solicitud
        $requestId = uniqid('req_');
        $requestData = json_encode($data);
        $startTime = microtime(true);
        $this->logService->log(
            'Iniciando solicitud ' . $method . ' a ' . $apiUrl . ' [' . $requestId . ']',
            'info',
            'connection'
        );
        
        // Ejecutar la solicitud
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $info = curl_getinfo($ch);
        $httpCode = $info['http_code'];
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);
        
        // Calcular tiempo de respuesta
        $requestTime = round(($endTime - $startTime) * 1000); // en ms
        
        // Verificar errores de cURL
        if ($errno) {
            $this->logService->log(
                'Error cURL en solicitud ' . $method . ' a ' . $apiUrl . ' [' . $requestId . ']: ' . $error . ' (errno: ' . $errno . ')',
                'error',
                'connection'
            );
            
            // Reintentar si no hemos superado el límite
            if ($retryCount < self::MAX_RETRIES) {
                $this->logService->log(
                    'Reintentando solicitud [' . $requestId . '] (' . ($retryCount + 1) . '/' . self::MAX_RETRIES . ')',
                    'warning',
                    'connection'
                );
                
                // Esperar antes de reintentar
                sleep(self::RETRY_WAIT);
                
                return $this->sendRequest($url, $endpoint, $data, $method, $retryCount + 1);
            }
            
            throw new Exception('Error de comunicación: ' . $error . ' (errno: ' . $errno . ')');
        }
        
        // Verificar respuesta HTTP
        if ($httpCode >= 400) {
            $this->logService->log(
                'Error HTTP ' . $httpCode . ' en solicitud ' . $method . ' a ' . $apiUrl . ' [' . $requestId . ']: ' . $response,
                'error',
                'connection'
            );
            
            // Reintentar si es un error de servidor (5xx) y no hemos superado el límite
            if ($httpCode >= 500 && $retryCount < self::MAX_RETRIES) {
                $this->logService->log(
                    'Reintentando solicitud [' . $requestId . '] (' . ($retryCount + 1) . '/' . self::MAX_RETRIES . ')',
                    'warning',
                    'connection'
                );
                
                // Esperar antes de reintentar
                sleep(self::RETRY_WAIT);
                
                return $this->sendRequest($url, $endpoint, $data, $method, $retryCount + 1);
            }
            
            throw new Exception('Error HTTP ' . $httpCode . ': ' . $response);
        }
        
        // Intentar decodificar la respuesta JSON
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logService->log(
                'Error al decodificar respuesta JSON de ' . $apiUrl . ' [' . $requestId . ']: ' . json_last_error_msg(),
                'error',
                'connection'
            );
            
            throw new Exception('Error al decodificar respuesta JSON: ' . json_last_error_msg());
        }
        
        // Registrar finalización exitosa
        $this->logService->log(
            'Solicitud ' . $method . ' a ' . $apiUrl . ' completada [' . $requestId . '] en ' . $requestTime . 'ms',
            'success',
            'connection'
        );
        
        return $responseData;
    }
    
    /**
     * Obtiene el dominio de la tienda actual
     *
     * @return string Dominio de la tienda
     */
    protected function getShopDomain()
    {
        $context = Context::getContext();
        $shop = $context->shop;
        
        if (Validate::isLoadedObject($shop)) {
            return $shop->domain;
        }
        
        return $_SERVER['HTTP_HOST'] ?? 'unknown';
    }
    
    /**
     * Verifica si una URL es válida y accesible
     *
     * @param string $url URL a verificar
     * @return bool Resultado de la verificación
     */
    public function validateUrl($url)
    {
        if (!Validate::isUrl($url)) {
            return false;
        }
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode >= 200 && $httpCode < 400;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Realiza una solicitud de test para verificar la conectividad
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @return array Resultado del test
     */
    public function testConnection($url, $apiKey)
    {
        try {
            // Verificar formato de URL
            if (!$this->validateUrl($url)) {
                return [
                    'status' => 'error',
                    'message' => 'URL no válida o no accesible'
                ];
            }
            
            // Enviar solicitud de test
            $result = $this->sendRequest(
                $url,
                'test',
                ['api_key' => $apiKey]
            );
            
            // Verificar respuesta
            if (isset($result['status']) && $result['status'] === 'success') {
                return [
                    'status' => 'success',
                    'message' => 'Conexión establecida correctamente',
                    'details' => $result
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'Error en la respuesta de la API',
                'details' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía una solicitud de sincronización de stock
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @param array $stockData Datos de stock a sincronizar
     * @return array Resultado de la sincronización
     */
    public function syncStock($url, $apiKey, $stockData)
    {
        try {
            $result = $this->sendRequest(
                $url,
                'syncStock',
                [
                    'api_key' => $apiKey,
                    'stock_data' => $stockData
                ]
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error en sincronización de stock: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Envía una solicitud de sincronización de precios
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @param array $priceData Datos de precios a sincronizar
     * @return array Resultado de la sincronización
     */
    public function syncPrices($url, $apiKey, $priceData)
    {
        try {
            $result = $this->sendRequest(
                $url,
                'syncPrices',
                [
                    'api_key' => $apiKey,
                    'price_data' => $priceData
                ]
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error en sincronización de precios: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene pedidos pendientes de una tienda
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @param string $lastSync Fecha de última sincronización
     * @return array Pedidos pendientes
     */
    public function getPendingOrders($url, $apiKey, $lastSync = null)
    {
        try {
            $data = [
                'api_key' => $apiKey
            ];
            
            if ($lastSync) {
                $data['last_sync'] = $lastSync;
            }
            
            $result = $this->sendRequest(
                $url,
                'getPendingOrders',
                $data
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al obtener pedidos pendientes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene los detalles de un pedido específico
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @param int $orderId ID del pedido en la tienda origen
     * @return array Detalles del pedido
     */
    public function getOrderDetails($url, $apiKey, $orderId)
    {
        try {
            $result = $this->sendRequest(
                $url,
                'getOrderDetails',
                [
                    'api_key' => $apiKey,
                    'order_id' => $orderId
                ]
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al obtener detalles del pedido: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza el estado de un pedido en la tienda origen
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @param int $orderId ID del pedido en la tienda origen
     * @param string $status Nuevo estado del pedido
     * @param string $comment Comentario adicional
     * @return array Resultado de la actualización
     */
    public function updateOrderStatus($url, $apiKey, $orderId, $status, $comment = '')
    {
        try {
            $result = $this->sendRequest(
                $url,
                'updateOrderStatus',
                [
                    'api_key' => $apiKey,
                    'order_id' => $orderId,
                    'status' => $status,
                    'comment' => $comment
                ]
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error al actualizar estado del pedido: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Realiza una comprobación de salud de la API de una tienda
     *
     * @param string $url URL base de la tienda
     * @param string $apiKey Clave API para autenticación
     * @return array Resultado de la comprobación
     */
    public function checkApiHealth($url, $apiKey)
    {
        try {
            $result = $this->sendRequest(
                $url,
                'health',
                ['api_key' => $apiKey]
            );
            
            return $result;
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error en comprobación de salud de la API: ' . $e->getMessage()
            ];
        }
    }
}
