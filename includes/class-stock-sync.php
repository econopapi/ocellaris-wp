<?php
/**
 * Sincronización de stock iPos <-> WooCommerce
 * 
 * @package Ocellaris_Child
 */

if(!defined('ABSPATH')){
    exit;
}

class Ocellaris_Stock_Sync {
    private $ipos_api;
    private $category_map = array();
    private $product_map = array();
    private $batch_sizer = 20;

    private $max_execution_time = 999999;
    private $start_time;

    // logging
    private $logs = array();
    private $current_session_id;
    private $logs_cache_key = 'ocellaris_ipos_stock_sync_logs';

    // configuración específica de iPos
    private $ipos_location_id = '1'; // "Tienda Ocellaris" por defecto para consultar stock
    // esto puede variar en otras instancias de iPos

    public function __construct(){
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $this->ipos_api = new Ocellaris_IPos_API();
        $this->load_product_map();
        $this->start_time = time();
        @ini_set('max_execution_time', $this->max_execution_time);
        @ini_set('memory_limit', '1024M');
        
        $this->log('Inicializando Ocellaris_Stock_Sync', 'info');
    }

    /**
     * sistema de logging verboso
     */
    private function log($message, $level = 'info', $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $elapsed = time() - $this->start_time;
        $memory = round(memory_get_usage()/1024/1024,2);
        $log_entry = array(
            'timestamp' => $timestamp,
            'elapsed' => $elapsed . 's',
            'memory' => $memory . 'MB',
            'level' => $level,
            'message' => $message);
        if($data!==null){
            $log_entry['data'] = $data;
        }
        $this->logs[] = $log_entry;
        $this->persist_logs_to_cache();
        error_log(sprintf(
            '[IPOS-STOCK-SYNC][%s][%ds][%sMB] %s',
            strtoupper($level),
            $elapsed,
            $memory,
            $message));
    }


    /**
     * logs a transient para persistencia entre peticiones ajax
     */
    private function persist_logs_to_cache() {
        $recent_logs = array_slice($this->logs, -500);
        set_transient($this->logs_cache_key.'_'.$this->current_session_id,
                      $recent_logs, HOUR_IN_SECONDS);
    }


    /**
     * recuperar logs desde el caché
     */
    private function load_logs_from_cache(){
        $cached_logs = get_transient($this->logs_cache_key.'_'.$this->current_session_id);
        if($cached_logs&&is_array($cached_logs)){
            $existing_count = count($this->logs);
            if ($existing_count<=1){
                $this->logs = $cached_logs;
            }
        }
    }


    /**
     * logs formateados para frontend
     */
    public function get_logs(){
        $this->load_logs_from_cache();
        return array_map(function($log){
            $icon = '📋';
            $class = 'info';
            switch($log['level']){
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
                case 'stock':
                    $icon = '📦';
                    $class = 'stock';
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
     * obtener o crear session id
     */
    private function get_or_create_session_id(){
        $session_id = get_transient('ocellaris_stock_sync_session_id');
        if(!$session_id){
            $session_id = uniqid('stock_sync_', true);
            set_transient('ocellaris_stock_sync_session_id', $session_id, HOUR_IN_SECONDS);
            $this->log('🆕 Nueva sesión de sincronización de stock creada: '.$session_id, 'info');
        } else {
            $this->log('♻️ Reanudando sesión existente: '.$session_id, 'info');
        }
        return $session_id;
    }


    /**
     * sincronización de stock de TODOS los productos
     */
    public function sync_all_stock($offset = 0){
        $this->log('📊 Iniciando sync_all_stock', 'info', array('offset' => $offset));
        // obtener productos de woocommerce con mapeo en ipos
        $wc_products = $this->get_wc_products_with_ipos_mapping($offset);
        if(empty($wc_products)){
            $this->log('⚠️ No se encontraron productos mapeados en iPos', 'warning');
            delete_transient('ocellaris_stock_sync_session_id');
            return array(
                'success' => false,
                'message' => 'No se encontraron productos mapeados en iPos',
                'logs' => $this->get_logs()
            );
        }
        $total = $this->count_wc_products_with_ipos_mapping();
        $batch = array_slice($wc_products, 0, $this->batch_sizer);
        $batch_count = count($batch);
        $this->log('📦 Lote preparado', 'info', array(
            'offset' => $offset,
            'batch_size' => $this->batch_sizer,
            'batch_count' => $batch_count,
            'total' => $total,
            'remaining' => $total-$offset
        ));
        $updated = 0;
        $failed = 0;
        $errors = array();
        $this->log('🔄 Procesando lote de '.$batch_count.'productos...', 'info');
        foreach($batch as $index => $product_data){
            $product_number = $offset+$index+1;
            $wc_product_id = $product_data['ID'];
            $ipos_product_id = $product_data['ipos_product_id'];
            $ipos_variation_id = $product_data['ipos_variation_id'];
            $sku = $product_data['post_excerpt'];
            $this->log("[{$product_number}/{$total}] WooCommerce ID: {$wc_product_id}, iPos Var ID: {$ipos_variation_id}", 'info');
            // verificación de timeout
            $elapsed = time()-$this->start_time;
            if($elapsed>($this->max_execution_time-30)){
                $this->log('⚠️ Timeout alcanzado', 'warning', array(
                    'elapsed' => $elapsed.'s',
                    'max' => $this->max_execution_time.'s',
                    'next_offset' => $offset+$index
                ));
                $next_offset = $offset+$index;
                return array(
                    'success' => true,
                    'completed' => false,
                    'total' => $total,
                    'processed' => $next_offset,
                    'updated' => $updated,
                    'failed' => $failed,
                    'errors' => $errors,
                    'next_offset' => $next_offset,
                    'progress_percentage' => round(($next_offset/$total)*100,1),
                    'message' => 'Timeout preventivo - continuando con siguiente lote',
                    'logs' => $this->get_logs()
                );
            }
            $sync_result = $this->sync_product_stock($wc_product_id, $ipos_product_id, $ipos_variation_id);
            if ($sync_result['success']){
                $updated++;
                $this->log("✅ Stock sincronizado: {$sync_result['wc_stock']} unidades", 'success');
            } else {
                $failed++;
                $errors[] =  "Producto {$wc_product_id}: {$sync_result['error']}";
                $this->log("❌ Error: {$sync_result['error']}", 'error');
            }
        }
        $this->log('Lote completado', 'success', array(
            'updated' => $updated,
            'failed' => $failed,
            'created' => 0,
            'skipped' => 0
        ));
        $next_offset = $offset+$batch_count;
        $has_more = $next_offset<$total;
        if(!$has_more){
            $this->log('✨ Sincronización de stock completada', 'success');
            delete_transient('ocellaris_stock_sync_session_id');
        }
        return array(
            'success' => true,
            'completed' => !$has_more,
            'total' => $total,
            'processed' => $next_offset,
            'updated' => $updated,
            'failed' => $failed,
            'created' => 0,
            'skipped' => 0,
            'errors' => $errors,
            'next_offset' => $has_more?$next_offset:null,
            'progress_percentage' => round(($next_offset/$total)*100,1),
            'message' => sprintf(
                'Lote compĺetado: %d actualizados, %d fallidos (%.1f%% - %d%d)',
                $updated,
                $failed,
                round(($next_offset/$total)*100,1),
                $next_offset,
                $total
            ),
            'logs' => $this->get_logs()
        );
    }


    /**
     * sincronización de stock de un solo producto
     */
    private function sync_product_stock($wc_product_id, $ipos_product_id, $ipos_variation_id){
        $this->log("Obteniendo stock de iPos para variation {$ipos_variation_id}...", 'stock');
        $ipos_stock = $this->get_ipos_stock($ipos_product_id, $ipos_variation_id);
        if($ipos_stock===false){
            return array('success'=>false,
                         'error'=>'No se pudo obtener stock de iPos.');
        }
        $this->log("📦 Stock en iPos: {$ipos_stock['quantity']} unidades", 'stock');
        // actualizar stock en woocommerce
        $update_result = $this->update_wc_product_stock($wc_product_id, $ipos_stock['quantity']);
        if(!$update_result){
            return array('success'=>false,
                         'error'=>'Error al actualizar stock en WooCommerce');
        }
        $this->log("✅ Stock en WooCommerce actualizado a {$ipos_stock['quantity']}", 'stock');
        return array(
            'success' => true,
            'ipos_stock' => $ipos_stock['quantity'],
            'wc_stock' => $ipos_stock['quantity'],
            'variation_id' => $ipos_variation_id
        );
    }

    /**
     * obtener stock de producto en iPos
     * POST /api/v1/stock/check
     */
    private function get_ipos_stock($product_id, $variation_id) {
        $this->log("🌐 Llamando a iPos API para stock (Product: {$product_id}, Variation: {$variation_id})", 'api');
        $request_body = array(
            'ProductID' => (string) $product_id,
            'VariationID' => (string) $variation_id,
            'LocationID' => $this->ipos_location_id,
            'SalesChannelID' => ''
        );
        
        // petición POST a API iPos
        $response = wp_remote_post(
            'https://ocellaris.ipos.services/api/v1/stock/check',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . get_option('ocellaris_ipos_api_key'),
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($request_body),
                'timeout' => 45,
                'sslverify' => false
            ));
        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            $this->log("❌ Error en request a iPos API: {$error}", 'error');
            return false;
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $this->log("⚠️ HTTP {$code} - Response: " . substr($body, 0, 200), 'warning');
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("❌ Error parseando JSON: " . json_last_error_msg(), 'error');
            return false;
        }
        
        // serialización de respuesta
        if (!isset($data['Data']) || !is_array($data['Data']) || empty($data['Data'])) {
            $this->log("⚠️ No hay datos de stock en la respuesta", 'warning');
            return array('quantity' => 0);
        }
        $stock_data = $data['Data'][0];
        $quantity = isset($stock_data['Quantity']) ? (int) $stock_data['Quantity'] : 0;
        $this->log("✅ Stock obtenido correctamente: {$quantity} unidades", 'success');  
        return array(
            'quantity' => $quantity,
            'product_id' => $stock_data['ProductID'] ?? $product_id,
            'variation_id' => $stock_data['VariationID'] ?? $variation_id,
            'location_id' => $stock_data['LocationID'] ?? $this->ipos_location_id
        );
    }


    /**
     * actualización de stock en woocommerce
     */
    private function update_wc_product_stock($product_id, $quantity) {
        $this->log("🔄 Actualizando stock en WooCommerce (ID: {$product_id}, Qty: {$quantity})", 'stock');
        try {
            $product = wc_get_product($product_id); 
            if (!$product) {
                $this->log("❌ Producto no encontrado en WooCommerce", 'error');
                return false;
            }
            // habilitar gestión de stock en woocommerce
            $product->set_manage_stock(true);
            // establecer cantidad
            $product->set_stock_quantity($quantity);
            // establecer stock status
            if ($quantity > 0) {
                $product->set_stock_status('instock');
            } else {
                $product->set_stock_status('outofstock');
            }
            // guardar cambios
            $result = $product->save();
            if (!$result) {
                $this->log("❌ Error al guardar producto", 'error');
                return false;
            }
            $this->log("✅ Stock actualizado correctamente", 'success');
            return true;
        } catch (Exception $e) {
            $this->log("❌ Excepción: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    
    /**
     * obtener productos de wc con mapeo en ipos
     */
    private function get_wc_products_with_ipos_mapping($offset = 0, $limit = 500) {
        global $wpdb;
        $this->log("📥 Obteniendo productos con mapeo en iPos (offset: {$offset}, limit: {$limit})", 'info');
        // Query para obtener productos que tienen meta iPos
        $query = "
            SELECT DISTINCT p.ID, p.post_excerpt, pm1.meta_value as ipos_product_id, pm2.meta_value as ipos_variation_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_ipos_product_id'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_ipos_variation_id'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            ORDER BY p.ID ASC
            LIMIT %d OFFSET %d
        ";
        $results = $wpdb->get_results($wpdb->prepare($query, $limit, $offset), ARRAY_A);
        $this->log("✅ Query ejecutada: " . count($results) . " productos encontrados", 'info');
        return $results;
    }


    /**
     * conteo de productos con mapeo en iPos
     */
    private function count_wc_products_with_ipos_mapping() {
        global $wpdb;
        $count = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_ipos_product_id'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_ipos_variation_id'
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
        ");
        return (int) $count;
    }


    /**
     * cargar mapa de productos
     */
    private function load_product_map() {
        $saved_map = get_option('ocellaris_ipos_product_map', array());
        if (is_array($saved_map)) {
            $this->product_map = $saved_map;
        }
    }
/** END Ocellaris_Stock_Sync */
}