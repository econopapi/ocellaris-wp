<?php
/**
 * Sincronización de productos iPos <-> WooCommerce
 * OPTIMIZADO con caché y logging verboso
 * 
 * @package Ocellaris_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_Product_Sync {
    
    private $ipos_api;
    private $category_map = array();
    private $product_map = array();
    private $batch_size = 20; 
    
    private $max_execution_time = 99999; 
    private $start_time;
    
    // Sistema de logging
    private $logs = array();
    private $current_session_id;
    private $logs_cache_key = 'ocellaris_ipos_sync_logs';
    
    // Caché de productos
    private $products_cache_key = 'ocellaris_ipos_products_cache';
    private $cache_duration = 3600; // 1 hora
    
    public function __construct() {
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $this->ipos_api = new Ocellaris_IPos_API();
        $this->load_category_map();
        $this->load_product_map();
        $this->start_time = time();
        $this->current_session_id = $this->get_or_create_session_id();
        
        @ini_set('max_execution_time', $this->max_execution_time);
        @ini_set('memory_limit', '512M');
        
        $this->log('Inicializado Ocellaris_Product_Sync', 'info');
    }
    
    /**
     * Sistema de logging verboso CON persistencia
     */
    private function log($message, $level = 'info', $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $elapsed = time() - $this->start_time;
        $memory = round(memory_get_usage() / 1024 / 1024, 2);
        
        $log_entry = array(
            'timestamp' => $timestamp,
            'elapsed' => $elapsed . 's',
            'memory' => $memory . 'MB',
            'level' => $level,
            'message' => $message
        );
        
        if ($data !== null) {
            $log_entry['data'] = $data;
        }
        
        $this->logs[] = $log_entry;
        
        // NUEVO: Guardar en transient para persistencia entre requests
        $this->persist_logs_to_cache();
        
        // También guardar en error_log para debugging
        error_log(sprintf(
            '[IPOS-SYNC][%s][%ds][%sMB] %s',
            strtoupper($level),
            $elapsed,
            $memory,
            $message
        ));
    }
    
    /**
     * NUEVO: Guardar logs en transient para que sobrevivan entre requests AJAX
     */
    private function persist_logs_to_cache() {
        // Guardar solo los últimos 500 logs para no sobrecargar
        $recent_logs = array_slice($this->logs, -500);
        set_transient($this->logs_cache_key . '_' . $this->current_session_id, $recent_logs, HOUR_IN_SECONDS);
    }
    
    /**
     * NUEVO: Recuperar logs persistidos de requests anteriores
     */
    private function load_logs_from_cache() {
        $cached_logs = get_transient($this->logs_cache_key . '_' . $this->current_session_id);
        
        if ($cached_logs && is_array($cached_logs)) {
            // Combinar logs cacheados con los nuevos (evitando duplicados)
            $existing_count = count($this->logs);
            $cached_count = count($cached_logs);
            
            // Si tenemos logs nuevos, mantenerlos. Si no, usar los cacheados
            if ($existing_count <= 1) { // Solo tiene el log inicial
                $this->logs = $cached_logs;
            }
        }
    }
    
    /**
     * Obtener logs formateados para el frontend
     */
    public function get_logs() {
        // Cargar logs persistidos al inicio
        $this->load_logs_from_cache();
        
        return array_map(function($log) {
            $icon = '📋';
            $class = 'info';
            
            switch($log['level']) {
                case 'success':
                    $icon = '✅';
                    $class = 'success';
                    break;
                case 'error':
                    $icon = '❌';
                    $class = 'error';
                    break;
                case 'warning':
                    $icon = '⚠️';
                    $class = 'warning';
                    break;
                case 'cache':
                    $icon = '💾';
                    $class = 'cache';
                    break;
                case 'api':
                    $icon = '🌐';
                    $class = 'api';
                    break;
                case 'image':
                    $icon = '🖼️';
                    $class = 'image';
                    break;
            }
            
            return array(
                'message' => sprintf(
                    '<span class="log-icon">%s</span> <span class="log-time">%s</span> <span class="log-message">%s</span> <span class="log-meta">[%s | %s]</span>',
                    $icon,
                    $log['timestamp'],
                    $log['message'],
                    $log['elapsed'],
                    $log['memory']
                ),
                'class' => $class,
                'raw' => $log
            );
        }, $this->logs);
    }
    
    /**
     * Obtener o crear session ID para esta sincronización
     */
    private function get_or_create_session_id() {
        $session_id = get_transient('ocellaris_sync_session_id');
        
        if (!$session_id) {
            $session_id = uniqid('sync_', true);
            set_transient('ocellaris_sync_session_id', $session_id, $this->cache_duration);
            $this->log('🆕 Nueva sesión de sincronización creada: ' . $session_id, 'info');
        } else {
            $this->log('♻️ Reanudando sesión existente: ' . $session_id, 'info');
        }
        
        return $session_id;
    }
    
    /**
     * Obtener productos de la API con caché inteligente
     */
    private function get_all_products_cached() {
        $cache_key = $this->products_cache_key . '_' . $this->current_session_id;
        $cached_products = get_transient($cache_key);
        
        if ($cached_products !== false && is_array($cached_products) && count($cached_products) > 0) {
            $this->log('💾 Productos obtenidos desde caché', 'cache', array(
                'total' => count($cached_products),
                'cache_key' => $cache_key
            ));
            return $cached_products;
        }
        
        $this->log('🌐 Llamando a la API de iPos para obtener productos...', 'api');
        $api_start = microtime(true);

        // Obtener productos paginados
        $all_products = array();
        $page = 0;
        $has_more = true;
        $total_downloaded = 0;

        while ($has_more) {
            $this->log("📄 Solicitando página {$page}...", 'api');
            
            // Hacer la llamada con parámetro de página
            $result = $this->ipos_api->get_products($page);
            
            if (!$result['success']) {
                $this->log('❌ Error al obtener productos de iPos en página ' . $page . ': ' . $result['error'], 'error');
                
                // Si ya tenemos productos, continuar con los que tenemos
                if (!empty($all_products)) {
                    $this->log('⚠️ Continuando con productos ya obtenidos', 'warning');
                    break;
                }
                
                return false;
            }
            
            $page_products = isset($result['data']['Products']) ? $result['data']['Products'] : array();
            $page_count = count($page_products);
            $total_downloaded += $page_count;
            
            $this->log("✅ Página {$page} descargada: {$page_count} productos", 'api');
            
            if (empty($page_products)) {
                $has_more = false;
                $this->log("🏁 Fin de paginación - página {$page} vacía", 'info');
            } else {
                $all_products = array_merge($all_products, $page_products);
                
                // Determinar si hay más páginas
                // Si la página tiene menos de 100 productos (o algún límite), asumimos que es la última
                // O puedes agregar lógica basada en headers de paginación si la API los proporciona
                if ($page_count < 100) { // Ajusta este número según tu API
                    $has_more = false;
                    $this->log("🏁 Última página detectada (menos de 100 productos)", 'info');
                } else {
                    $page++;
                    
                    // Pequeña pausa para no sobrecargar la API
                    if ($page % 10 == 0) {
                        sleep(1);
                    }
                }
            }
            
            // Seguridad: límite de páginas para evitar loops infinitos
            if ($page > 100) {
                $this->log('⚠️ Límite de páginas alcanzado (100)', 'warning');
                break;
            }            
        }
        
        // $result = $this->ipos_api->get_products();
        
        $api_duration = round(microtime(true) - $api_start, 2);
        
        $this->log('✅ Productos descargados de la API', 'success', array(
            'total' => count($all_products),
            'pages' => $page + 1,
            'duration' => $api_duration . 's',
            'size_mb' => round(strlen(json_encode($all_products)) / 1024 / 1024, 2)
        ));
        
        if (empty($all_products)) {
            $this->log('⚠️ No se obtuvieron productos de la API', 'warning');
            return false;
        }
        
        // Guardar en caché
        set_transient($cache_key, $all_products, $this->cache_duration);
        $this->log('💾 Productos guardados en caché', 'cache', array(
            'key' => $cache_key,
            'count' => count($all_products)
        ));
        
        return $all_products;
    }
    
    /**
     * Sincronizar todos los productos (con procesamiento por lotes)
     */
    public function sync_all_products($offset = 0) {
        $this->log('📊 Iniciando sync_all_products', 'info', array('offset' => $offset));
        
        // Obtener productos (cachéados)
        $all_products = $this->get_all_products_cached();
        
        if ($all_products === false) {
            return array(
                'success' => false,
                'message' => 'Error al obtener productos de iPos',
                'logs' => $this->get_logs()
            );
        }
        
        if (empty($all_products)) {
            $this->log('⚠️ No se encontraron productos en iPos', 'warning');
            return array(
                'success' => false,
                'message' => 'No se encontraron productos en iPos',
                'logs' => $this->get_logs()
            );
        }
        
        $total = count($all_products);
        
        // Filtrar productos ACTIVE y reindexear correctamente
        $this->log('🔍 Filtrando productos activos...', 'info');
        $filtered = array_filter($all_products, function($product) {
            return isset($product['Status']) && $product['Status'] === 'ACTIVE';
        });
        
        // IMPORTANTE: Reindexear array después de filter
        $active_products = array_values($filtered);
        $active_count = count($active_products);
        
        $this->log('✅ Filtrado completado', 'success', array(
            'total' => $total,
            'active' => $active_count,
            'inactive' => $total - $active_count
        ));
        
        // Validar que el offset sea válido
        if ($offset >= $active_count) {
            $this->log('🎉 ¡Sincronización completada! Todos los productos han sido procesados', 'success', array(
                'total_processed' => $active_count,
                'offset' => $offset
            ));
            
            // LIMPIAR SESIÓN SOLO CUANDO TERMINA
            delete_transient('ocellaris_sync_session_id');
            
            return array(
                'success' => true,
                'completed' => true,
                'total' => $total,
                'active' => $active_count,
                'processed' => $offset,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => array(),
                'message' => '✨ ¡Sincronización 100% completada!',
                'logs' => $this->get_logs()
            );
        }
        
        // Obtener el lote actual - VERIFICAR QUE EXISTAN PRODUCTOS
        $batch = array_slice($active_products, $offset, $this->batch_size);
        $batch_count = count($batch);
        
        $this->log('📦 Lote actual preparado', 'info', array(
            'offset' => $offset,
            'batch_size' => $this->batch_size,
            'batch_count' => $batch_count,
            'remaining' => $active_count - $offset
        ));
        
        if (empty($batch)) {
            $this->log('⚠️ Lote vacío - probablemente fin de datos', 'warning');
            delete_transient('ocellaris_sync_session_id');
            
            return array(
                'success' => true,
                'completed' => true,
                'total' => $total,
                'active' => $active_count,
                'processed' => $offset,
                'message' => '✨ ¡Sincronización completada!',
                'logs' => $this->get_logs()
            );
        }
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        $this->log('🔄 Procesando lote de ' . $batch_count . ' productos...', 'info');
        
        foreach ($batch as $index => $product) {
            $product_number = $offset + $index + 1;
            $product_name = isset($product['Name']) ? $product['Name'] : 'Sin nombre';
            
            $this->log("🔸 [{$product_number}/{$active_count}] {$product_name}", 'info');
            
            // Verificar timeout - DEJAR MÁS MARGEN
            $elapsed = time() - $this->start_time;
            if ($elapsed > ($this->max_execution_time - 45)) {
                $this->log('⏰ Timeout preventivo alcanzado', 'warning', array(
                    'elapsed' => $elapsed . 's',
                    'max' => $this->max_execution_time . 's',
                    'next_offset' => $offset + $index
                ));
                $this->save_product_map();
                
                // NO LIMPIAR SESIÓN - MANTENER PARA PRÓXIMO LOTE
                $next_offset = $offset + $index;
                
                return array(
                    'success' => true,
                    'completed' => false,
                    'total' => $total,
                    'active' => $active_count,
                    'processed' => $next_offset,
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'next_offset' => $next_offset,
                    'progress_percentage' => round(($next_offset / $active_count) * 100, 1),
                    'message' => 'Timeout preventivo - continuando con siguiente lote',
                    'logs' => $this->get_logs()
                );
            }
            
            $sync_start = microtime(true);
            $sync_result = $this->sync_product($product);
            $sync_duration = round(microtime(true) - $sync_start, 3);
            
            $this->log("  ⏱️ Procesado en {$sync_duration}s", 'info');
            $this->process_sync_result($sync_result, $created, $updated, $skipped, $errors);
        }
        
        // Guardar el mapeo
        $this->save_product_map();
        $this->log('💾 Mapeo de productos guardado', 'cache');
        
        // IMPORTANTE: Calcular el próximo offset CORRECTAMENTE
        $next_offset = $offset + $batch_count;
        $has_more = $next_offset < $active_count;
        
        $this->log('📊 Lote completado', 'success', array(
            'current_offset' => $offset,
            'batch_processed' => $batch_count,
            'next_offset' => $next_offset,
            'has_more' => $has_more,
            'remaining' => $active_count - $next_offset,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped
        ));
        
        // NO LIMPIAR SESIÓN si hay más productos
        if (!$has_more) {
            $this->log('✨ Todos los productos sincronizados', 'success');
            delete_transient('ocellaris_sync_session_id');
        }
        
        return array(
            'success' => true,
            'completed' => !$has_more,
            'total' => $total,
            'active' => $active_count,
            'processed' => $next_offset,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'next_offset' => $has_more ? $next_offset : null,
            'progress_percentage' => round(($next_offset / $active_count) * 100, 1),
            'message' => sprintf(
                'Lote completado: %d creados, %d actualizados, %d omitidos (%.1f%% - %d/%d)',
                $created,
                $updated,
                $skipped,
                round(($next_offset / $active_count) * 100, 1),
                $next_offset,
                $active_count
            ),
            'logs' => $this->get_logs()
        );
    }
    
    /**
     * Sincronizar un producto individual
     */
    private function sync_product($ipos_product) {
        $ipos_id = $ipos_product['ID'];
        $name = sanitize_text_field($ipos_product['Name']);
        
        $this->log("  🔍 Analizando producto ID:{$ipos_id}", 'info');
        
        $variations = isset($ipos_product['ProductVariations']) ? $ipos_product['ProductVariations'] : array();
        
        if (empty($variations)) {
            $this->log("  ⚠️ Sin variaciones, omitiendo", 'warning');
            return array(
                'success' => false,
                'type' => 'skip',
                'name' => $name,
                'error' => 'Sin variaciones'
            );
        }
        
        if (count($variations) > 1) {
            $this->log("  ⏭️ Producto variable detectado, omitiendo (pendiente)", 'info');
            return array(
                'success' => true,
                'type' => 'skipped',
                'name' => $name,
                'reason' => 'Producto variable (pendiente)'
            );
        }
        
        $variation = $variations[0];
        $sku = isset($variation['SKU']) ? sanitize_text_field($variation['SKU']) : '';
        
        if (empty($sku)) {
            $this->log("  ⚠️ Sin SKU, omitiendo", 'warning');
            return array(
                'success' => false,
                'type' => 'skip',
                'name' => $name,
                'error' => 'Sin SKU'
            );
        }
        
        $this->log("  📝 SKU: {$sku}", 'info');
        
        // Buscar si ya existe en WC por SKU
        $wc_product_id = $this->get_wc_product_by_sku($sku);
        
        if ($wc_product_id) {
            $this->log("  ♻️ Producto existente encontrado (WC ID: {$wc_product_id})", 'info');
        } else {
            $this->log("  🆕 Producto nuevo, creando...", 'info');
        }
        
        // Preparar datos del producto
        $prep_start = microtime(true);
        $product_data = $this->prepare_product_data($ipos_product, $variation);
        $prep_duration = round(microtime(true) - $prep_start, 3);
        $this->log("  ⚙️ Datos preparados en {$prep_duration}s", 'info');
        
        if ($wc_product_id) {
            // Actualizar producto existente
            $update_start = microtime(true);
            $result = $this->update_wc_product($wc_product_id, $product_data);
            $update_duration = round(microtime(true) - $update_start, 3);
            
            if ($result) {
                $this->product_map[$ipos_id] = $wc_product_id;
                $this->log("  ✅ Producto actualizado en {$update_duration}s", 'success');
                return array(
                    'success' => true,
                    'type' => 'updated',
                    'name' => $name,
                    'wc_id' => $wc_product_id,
                    'sku' => $sku
                );
            } else {
                $this->log("  ❌ Error al actualizar producto", 'error');
                return array(
                    'success' => false,
                    'type' => 'update',
                    'name' => $name,
                    'error' => 'Error al actualizar en WC'
                );
            }
        } else {
            // Crear nuevo producto
            $create_start = microtime(true);
            $result = $this->create_wc_product($product_data);
            $create_duration = round(microtime(true) - $create_start, 3);
            
            if ($result && isset($result['id'])) {
                $wc_product_id = $result['id'];
                $this->product_map[$ipos_id] = $wc_product_id;
                
                update_post_meta($wc_product_id, '_ipos_product_id', $ipos_id);
                update_post_meta($wc_product_id, '_ipos_variation_id', $variation['ID']);
                
                $this->log("  ✅ Producto creado en {$create_duration}s (WC ID: {$wc_product_id})", 'success');
                return array(
                    'success' => true,
                    'type' => 'created',
                    'name' => $name,
                    'wc_id' => $wc_product_id,
                    'sku' => $sku
                );
            } else {
                $this->log("  ❌ Error al crear producto", 'error');
                return array(
                    'success' => false,
                    'type' => 'create',
                    'name' => $name,
                    'error' => 'Error al crear en WC'
                );
            }
        }
    }
    
    /**
     * Preparar datos del producto para WooCommerce
     */
    private function prepare_product_data($ipos_product, $variation) {
        $this->log("    📋 Preparando datos del producto...", 'info');
        
        $data = array(
            'name' => sanitize_text_field($ipos_product['Name']),
            'type' => 'simple',
            'status' => 'publish',
            'sku' => sanitize_text_field($variation['SKU']),
            'regular_price' => (string) $variation['Price'],
            'manage_stock' => false,
            'description' => wp_kses_post($ipos_product['Description']),
            'short_description' => wp_kses_post($ipos_product['MoreDescription']),
        );
        
        // Categorías
        if (!empty($ipos_product['Categories'])) {
            $category_ids = array();
            foreach ($ipos_product['Categories'] as $cat) {
                $wc_cat_id = $this->get_wc_category_id($cat['ID']);
                if ($wc_cat_id) {
                    $category_ids[] = $wc_cat_id;
                }
            }
            if (!empty($category_ids)) {
                $data['categories'] = $category_ids;
                $this->log("    📁 Categorías asignadas: " . count($category_ids), 'info');
            }
        }
        
        // Imágenes
        if (!empty($ipos_product['Pictures'])) {
            $images = array();
            foreach ($ipos_product['Pictures'] as $picture) {
                if (!empty($picture['PictureUrl'])) {
                    $images[] = array('src' => $picture['PictureUrl']);
                }
            }
            if (!empty($images)) {
                $data['images'] = $images;
                $this->log("    🖼️ Imágenes encontradas: " . count($images), 'info');
            }
        }
        
        // Dimensiones y peso
        if (!empty($variation['Weight'])) {
            $data['weight'] = (string) $variation['Weight'];
        }
        
        if (!empty($variation['Length']) || !empty($variation['Width']) || !empty($variation['Height'])) {
            $data['dimensions'] = array(
                'length' => (string) ($variation['Length'] ?? ''),
                'width' => (string) ($variation['Width'] ?? ''),
                'height' => (string) ($variation['Height'] ?? '')
            );
        }
        
        $data['shipping_required'] = isset($ipos_product['RequiresShipping']) && $ipos_product['RequiresShipping'] === 'YES';
        
        if (!empty($ipos_product['Brand'])) {
            $data['meta_data'] = array(
                array('key' => '_ipos_brand', 'value' => $ipos_product['Brand']),
                array('key' => '_ipos_provider', 'value' => $ipos_product['Provider'] ?? '')
            );
        }
        
        return $data;
    }
    
    /**
     * Buscar producto en WooCommerce por SKU
     */
    private function get_wc_product_by_sku($sku) {
        global $wpdb;
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
                $sku
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }
    
    /**
     * Crear producto en WooCommerce
     */
    private function create_wc_product($data) {
        $this->log("    ➕ Creando nuevo producto en WooCommerce...", 'info');
        
        $post_data = array(
            'post_title'    => $data['name'],
            'post_content'  => $data['description'],
            'post_excerpt'  => $data['short_description'],
            'post_type'     => 'product',
            'post_status'   => $data['status']
        );
        
        $product_id = wp_insert_post($post_data);
        
        if (is_wp_error($product_id)) {
            $this->log("    ❌ Error wp_insert_post: " . $product_id->get_error_message(), 'error');
            return false;
        }
        
        $this->log("    ✅ Post creado (ID: {$product_id})", 'info');
        
        wp_set_object_terms($product_id, 'simple', 'product_type');
        
        update_post_meta($product_id, '_sku', $data['sku']);
        update_post_meta($product_id, '_regular_price', $data['regular_price']);
        update_post_meta($product_id, '_price', $data['regular_price']);
        update_post_meta($product_id, '_manage_stock', 'no');
        update_post_meta($product_id, '_sold_individually', 'no');
        
        if (isset($data['dimensions']) && is_array($data['dimensions'])) {
            if (!empty($data['dimensions']['length'])) {
                update_post_meta($product_id, '_length', $data['dimensions']['length']);
            }
            if (!empty($data['dimensions']['width'])) {
                update_post_meta($product_id, '_width', $data['dimensions']['width']);
            }
            if (!empty($data['dimensions']['height'])) {
                update_post_meta($product_id, '_height', $data['dimensions']['height']);
            }
        }
        
        if (isset($data['weight']) && !empty($data['weight'])) {
            update_post_meta($product_id, '_weight', $data['weight']);
        }
        
        update_post_meta($product_id, '_requires_shipping', $data['shipping_required'] ? 'yes' : 'no');
        
        if (isset($data['categories']) && !empty($data['categories'])) {
            wp_set_object_terms($product_id, $data['categories'], 'product_cat');
        }
        
        // Imágenes (con logging detallado)
        if (isset($data['images']) && !empty($data['images'])) {
            $this->attach_product_images($product_id, $data['images']);
        }
        
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                update_post_meta($product_id, $meta['key'], $meta['value']);
            }
        }
        
        return array('id' => $product_id);
    }
    
    /**
     * Actualizar producto en WooCommerce
     */
    private function update_wc_product($product_id, $data) {
        $this->log("    🔄 Actualizando producto existente (ID: {$product_id})...", 'info');
        
        $post_data = array(
            'ID'           => $product_id,
            'post_title'   => $data['name'],
            'post_content' => $data['description'],
            'post_excerpt' => $data['short_description']
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            $this->log("    ❌ Error wp_update_post: " . $result->get_error_message(), 'error');
            return false;
        }
        
        update_post_meta($product_id, '_sku', $data['sku']);
        update_post_meta($product_id, '_regular_price', $data['regular_price']);
        update_post_meta($product_id, '_price', $data['regular_price']);
        
        if (isset($data['dimensions']) && is_array($data['dimensions'])) {
            if (!empty($data['dimensions']['length'])) {
                update_post_meta($product_id, '_length', $data['dimensions']['length']);
            }
            if (!empty($data['dimensions']['width'])) {
                update_post_meta($product_id, '_width', $data['dimensions']['width']);
            }
            if (!empty($data['dimensions']['height'])) {
                update_post_meta($product_id, '_height', $data['dimensions']['height']);
            }
        }
        
        if (isset($data['weight']) && !empty($data['weight'])) {
            update_post_meta($product_id, '_weight', $data['weight']);
        }
        
        update_post_meta($product_id, '_requires_shipping', $data['shipping_required'] ? 'yes' : 'no');
        
        if (isset($data['categories']) && !empty($data['categories'])) {
            wp_set_object_terms($product_id, $data['categories'], 'product_cat');
        }
        
        if (isset($data['images']) && !empty($data['images'])) {
            delete_post_meta($product_id, '_product_image_gallery');
            $this->attach_product_images($product_id, $data['images']);
        }
        
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                update_post_meta($product_id, $meta['key'], $meta['value']);
            }
        }
        
        return true;
    }
    
    /**
     * Adjuntar imágenes al producto (con logging detallado)
     */
    private function attach_product_images($product_id, $images) {
        $this->log("    🖼️ Procesando " . count($images) . " imágenes...", 'image');
        
        $gallery_ids = array();
        
        foreach ($images as $index => $image) {
            if (empty($image['src'])) {
                continue;
            }
            
            $image_start = microtime(true);
            $this->log("      ⬇️ Descargando imagen " . ($index + 1) . "...", 'image');
            
            $image_id = $this->download_image($image['src'], $product_id);
            
            $image_duration = round(microtime(true) - $image_start, 3);
            
            if ($image_id) {
                $this->log("      ✅ Imagen descargada en {$image_duration}s (ID: {$image_id})", 'image');
                
                if ($index === 0) {
                    set_post_thumbnail($product_id, $image_id);
                    $this->log("      🎨 Imagen principal asignada", 'image');
                } else {
                    $gallery_ids[] = $image_id;
                }
            } else {
                $this->log("      ⚠️ Error descargando imagen en {$image_duration}s", 'warning');
            }
        }
        
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
            $this->log("    🖼️ Galería configurada con " . count($gallery_ids) . " imágenes", 'image');
        }
    }
    
/**
     * Descargar imagen desde URL (optimizado con caché)
     */
    private function download_image($url, $product_id) {
        // Verificar si ya existe la imagen por URL
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_source_url' AND meta_value = %s LIMIT 1",
            $url
        ));
        
        if ($existing) {
            $this->log("        ♻️ Imagen ya existe en media library (ID: {$existing})", 'cache');
            return (int) $existing;
        }
        
        $timeout = 15;
        $response = wp_remote_get($url, array(
            'timeout' => $timeout,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            $this->log("        ❌ Error descargando imagen: " . $response->get_error_message(), 'error');
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $this->log("        ⚠️ HTTP {$response_code} al descargar imagen", 'warning');
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            $this->log("        ⚠️ Imagen vacía", 'warning');
            return false;
        }
        
        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'image-' . md5($url) . '.jpg';
        }
        
        // Sanitizar nombre de archivo
        $filename = sanitize_file_name($filename);
        
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . $filename;
        
        // Si el archivo ya existe físicamente, usarlo
        if (file_exists($upload_path)) {
            $this->log("        ♻️ Archivo físico ya existe: {$filename}", 'cache');
        } else {
            file_put_contents($upload_path, $image_data);
            $this->log("        💾 Archivo guardado: {$filename}", 'image');
        }
        
        // Detectar tipo MIME
        $filetype = wp_check_filetype($filename, null);
        $mime_type = $filetype['type'];
        
        $attachment = array(
            'post_mime_type' => $mime_type,
            'post_title'     => sanitize_text_field(basename($filename, '.' . $filetype['ext'])),
            'post_content'   => '',
            'post_status'    => 'publish'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload_path, $product_id);
        
        if (is_wp_error($attachment_id)) {
            $this->log("        ❌ Error al insertar attachment: " . $attachment_id->get_error_message(), 'error');
            return false;
        }
        
        // Guardar URL de origen para evitar duplicados
        update_post_meta($attachment_id, '_source_url', $url);
        
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        $this->log("        ✅ Attachment creado (ID: {$attachment_id})", 'success');
        
        return $attachment_id;
    }
    
    /**
     * Obtener ID de categoría de WooCommerce
     */
    private function get_wc_category_id($ipos_id) {
        if (isset($this->category_map[$ipos_id])) {
            return $this->category_map[$ipos_id];
        }
        
        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'ipos_category_id',
                    'value' => $ipos_id,
                    'compare' => '='
                )
            ),
            'suppress_filter' => true
        ));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            $term_id = $terms[0]->term_id;
            $this->category_map[$ipos_id] = $term_id;
            return $term_id;
        }
        
        return false;
    }
    
    /**
     * Procesar resultado de sincronización
     */
    private function process_sync_result($result, &$created, &$updated, &$skipped, &$errors) {
        if ($result['success']) {
            switch ($result['type']) {
                case 'created':
                    $created++;
                    break;
                case 'updated':
                    $updated++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
            }
        } else {
            $errors[] = $result['name'] . ': ' . $result['error'];
        }
    }
    
    /**
     * Cargar mapa de categorías
     */
    private function load_category_map() {
        $saved_map = get_option('ocellaris_ipos_category_map', array());
        if (is_array($saved_map)) {
            $this->category_map = $saved_map;
        }
    }
    
    /**
     * Cargar mapa de productos
     */
    private function load_product_map() {
        $saved_map = get_option('ocellaris_ipos_product_map', array());
        if (is_array($saved_map)) {
            $this->product_map = $saved_map;
        }
    }
    
    /**
     * Guardar mapa de productos
     */
    private function save_product_map() {
        update_option('ocellaris_ipos_product_map', $this->product_map);
    }
}