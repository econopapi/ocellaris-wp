<?php
/**
 * Cliente API de iPos
 * OPTIMIZADO para manejar 45K+ líneas de productos
 * 
 * @package Ocellaris_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_IPos_API {
    
    private $base_url = 'https://ocellaris.ipos.services/api/v1';
    private $api_key;
    private $cache_key = 'ocellaris_ipos_all_products_cache';
    private $cache_duration = 3 * HOUR_IN_SECONDS; // 3 horas
    
    public function __construct() {
        $this->api_key = get_option('ocellaris_ipos_api_key');
    }
    
    /**
     * Obtener todas las categorías
     */
    public function get_categories() {
        return $this->make_request('/categories');
    }
    
    /**
     * Obtener una categoría específica
     */
    public function get_category($category_id) {
        return $this->make_request('/categories/' . $category_id);
    }
    
    /**
     * Obtener TODOS los productos con caché inteligente
     * El endpoint devuelve ~45K líneas de una vez
     * Usamos caché agresivo para evitar descargas repetidas
     */
    public function get_products($page = 0) {
        error_log('[iPos API] get_products() llamado para página ' . $page);
        
        $cache_key = $this->cache_key . '_page_' . $page;
        $cached_products = get_transient($cache_key);
        
        if ($cached_products !== false) {
            error_log('[iPos API] ✅ Página ' . $page . ' obtenida del caché (' . count($cached_products) . ' productos)');
            return array(
                'success' => true,
                'data' => array('Products' => $cached_products),
                'cached' => true,
                'page' => $page
            );
        }
        
        error_log('[iPos API] 🌐 Descargando página ' . $page . ' de la API...');
        
        $start_time = microtime(true);
        
        // Construir URL con parámetros de paginación
        $url = '/products';
        $query_params = array();
        
        if ($page > 0) {
            $query_params['page'] = $page;
        }
        
        // Si la API soporta límite por página, puedes agregarlo aquí
        // $query_params['limit'] = 100; // Ajusta según la API
        
        if (!empty($query_params)) {
            $url .= '?' . http_build_query($query_params);
        }
        
        $result = $this->make_request($url);
        
        if (!$result['success']) {
            error_log('[iPos API] ❌ Error en página ' . $page . ': ' . $result['error']);
            return $result;
        }
        
        $products = isset($result['data']['Products']) ? $result['data']['Products'] : array();
        $product_count = count($products);
        
        $duration = round(microtime(true) - $start_time, 2);
        error_log('[iPos API] ✅ Página ' . $page . ' descargada: ' . $product_count . ' productos en ' . $duration . 's');
        
        // Cachear esta página individualmente
        if (!empty($products)) {
            set_transient($cache_key, $products, $this->cache_duration);
            error_log('[iPos API] 💾 Página ' . $page . ' cacheadapor 3 horas');
        }
        
        return array(
            'success' => true,
            'data' => array('Products' => $products),
            'cached' => false,
            'page' => $page,
            'duration' => $duration . 's'
        );
    }

    /**
     * Obtener TODOS los productos recorriendo todas las páginas
     */
    public function get_all_products() {
        error_log('[iPos API] get_all_products() llamado');
        
        $cache_key = $this->cache_key . '_all';
        $cached_products = get_transient($cache_key);
        
        if ($cached_products !== false) {
            error_log('[iPos API] ✅ Todos los productos obtenidos del caché (' . count($cached_products) . ' productos)');
            return array(
                'success' => true,
                'data' => array('Products' => $cached_products),
                'cached' => true
            );
        }
        
        error_log('[iPos API] 🌐 Descargando TODOS los productos paginados...');
        
        $all_products = array();
        $page = 0;
        $has_more = true;
        $start_time = microtime(true);
        
        while ($has_more) {
            $page_result = $this->get_products($page);
            
            if (!$page_result['success']) {
                error_log('[iPos API] ❌ Error al obtener página ' . $page);
                
                // Si ya tenemos productos, devolver lo que tenemos
                if (!empty($all_products)) {
                    error_log('[iPos API] ⚠️ Devolviendo productos obtenidos hasta ahora');
                    break;
                }
                
                return $page_result;
            }
            
            $page_products = isset($page_result['data']['Products']) ? $page_result['data']['Products'] : array();
            $page_count = count($page_products);
            
            if (empty($page_products)) {
                $has_more = false;
                error_log('[iPos API] 🏁 Página ' . $page . ' vacía - Fin de paginación');
            } else {
                $all_products = array_merge($all_products, $page_products);
                error_log('[iPos API] 📦 Total acumulado: ' . count($all_products) . ' productos');
                
                // Si la página tiene menos de 100 productos, asumimos que es la última
                // Ajusta esta lógica según el comportamiento de tu API
                if ($page_count < 100) {
                    $has_more = false;
                    error_log('[iPos API] 🏁 Última página detectada');
                } else {
                    $page++;
                    
                    // Pequeña pausa entre páginas
                    if ($page % 5 == 0) {
                        sleep(1);
                    }
                }
            }
            
            // Límite de seguridad
            if ($page > 50) {
                error_log('[iPos API] ⚠️ Límite de 50 páginas alcanzado');
                break;
            }
        }
        
        $duration = round(microtime(true) - $start_time, 2);
        $total_count = count($all_products);
        
        error_log('[iPos API] 🎉 Descarga completa de ' . $total_count . ' productos en ' . $duration . 's');
        
        // Cachear todos los productos
        if (!empty($all_products)) {
            set_transient($cache_key, $all_products, $this->cache_duration);
            error_log('[iPos API] 💾 Todos los productos cacheados por 3 horas');
        }
        
        return array(
            'success' => true,
            'data' => array('Products' => $all_products),
            'cached' => false,
            'total_pages' => $page + 1,
            'total_products' => $total_count,
            'duration' => $duration . 's'
        );
    }
        
    
    /**
     * Limpiar caché de productos (útil para testing)
     */
    // public function clear_products_cache() {
    //     delete_transient($this->cache_key);
    //     error_log('[iPos API] 🗑️ Caché de productos limpiado');
    //     return true;
    // }

    /**
     * Limpiar caché de productos (ahora incluye páginas)
     */
    public function clear_products_cache() {
        global $wpdb;
        
        // Eliminar caché principal
        delete_transient($this->cache_key . '_all');
        
        // Eliminar todas las páginas cacheadas
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                '_transient_' . $this->cache_key . '_page_%',
                '_transient_timeout_' . $this->cache_key . '_page_%'
            )
        );
        
        error_log('[iPos API] 🗑️ Caché de productos y páginas limpiado');
        return true;
    }
    
    /**
     * Obtener un producto específico
     */
    public function get_product($product_id) {
        return $this->make_request('/products/' . $product_id);
    }
    
    /**
     * Buscar producto por SKU
     */
    public function get_product_by_sku($sku) {
        return $this->make_request('/products?sku=' . urlencode($sku));
    }
    
    /**
     * Hacer petición a la API con timeout extendido para 45K líneas
     */
    private function make_request($endpoint, $method = 'GET', $body = null) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'API Key no configurada'
            );
        }
        
        $url = $this->base_url . $endpoint;
        $request_id = uniqid('ipos_', true); // id único para petición
        
        // Timeout más largo para solicitudes grandes (45K líneas)
        $timeout = (strpos($endpoint, '/products') === 0) ? 120 : 75;
        if (strpos($endpoint, '/stock/movements') === 0) {
            $timeout = 150;
        }

        $log_prefix = '[iPos API ' . $request_id . ']';
        $start_time = microtime(true);

        error_log($log_prefix.' Iniciando petición: '.$method.' '.$url.' (timeout: '.$timeout.'s)');

        if($body){
            error_log($log_prefix.' Payload: '.print_r($body, true));
        }

        // hook para monitoreo externo
        do_action('ocellaris_ipos_before_request', $endpoint, $method, $body, $request_id);

        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => $timeout,
            'sslverify' => false // Para dev, remover en producción si es necesario
        );
        
        if ($body && $method !== 'GET') {
            $args['body'] = json_encode($body);
        }

        // medición de tiempo de conexión
        $connection_start = microtime(true);
        $response = wp_remote_request($url, $args);
        $connection_time = round(microtime(true) - $connection_start, 3);

        error_log($log_prefix.' Tiempo de conexión: '.$connection_time.'s');
        
        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            error_log('[iPos API] ❌ Error: ' . $error);
            return array(
                'success' => false,
                'error' => $error
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code < 200 || $code >= 300) {
            error_log('[iPos API] ❌ HTTP ' . $code);
            return array(
                'success' => false,
                'error' => 'HTTP Error ' . $code,
                'response' => substr($body, 0, 500) // Primeros 500 chars del error
            );
        }
        
        // Para /products, el body puede ser muy grande (45K líneas)
        $body_size = strlen($body);
        $body_size_mb = round($body_size / 1024 / 1024, 2);
        
        error_log('[iPos API] Response size: ' . $body_size_mb . 'MB');
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Error al parsear JSON: ' . json_last_error_msg();
            error_log('[iPos API] ❌ ' . $error);
            return array(
                'success' => false,
                'error' => $error
            );
        }
        
        error_log('[iPos API] ✅ Response parsed successfully');
        
        return array(
            'success' => true,
            'data' => $data
        );
    }
    
    /**
     * Verificar si la API está configurada correctamente
     */
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    /**
     * Actualizar inventario en iPos (movimiento OUT para ventas)
     */
    public function update_inventory($product_id, $variation_id, $quantity, $notes = '', $location_id = '1') {
        error_log(sprintf(
            '[iPos API] Actualizando inventario: Product=%s, Variation=%s, Qty=-%d',
            $product_id,
            $variation_id,
            $quantity
        ));
        
        $payload = array(
            'ProductID' => (string) $product_id,
            'VariationID' => (string) $variation_id,
            'LocationID' => (string) $location_id,
            'Quantity' => (int) $quantity,
            'Notes' => $notes,
            'Type' => 'OUT',
        );
        
        $result = $this->make_request('/stock/movements', 'POST', $payload);
        
        if (!$result['success']) {
            return array(
                'success' => false,
                'error' => $result['error']
            );
        }
        
        $data = $result['data'];
        
        if (isset($data['Data']) && isset($data['Data']['Quantity'])) {
            return array(
                'success' => true,
                'new_stock' => $data['Data']['Quantity'],
                'response' => $data
            );
        }
        
        return array(
            'success' => false,
            'error' => 'Respuesta inesperada de la API',
            'response' => $data
        );
    }
}