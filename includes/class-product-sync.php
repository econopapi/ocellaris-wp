<?php
/**
 * Sincronización de productos iPos <-> WooCommerce
 * 
 * @package Ocellaris_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_Product_Sync {
    
    private $ipos_api;
    private $category_map = array();
    private $product_map = array(); // iPos Product ID => WC Product ID
    private $batch_size = 50; // Procesar de a 50 productos
    
    // Límites de tiempo y memoria
    private $max_execution_time = 300; // 5 minutos
    private $start_time;
    
    public function __construct() {
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $this->ipos_api = new Ocellaris_IPos_API();
        $this->load_category_map();
        $this->load_product_map();
        $this->start_time = time();
        
        // Aumentar límites si es posible
        @ini_set('max_execution_time', $this->max_execution_time);
        @ini_set('memory_limit', '512M');
    }
    
    /**
     * Sincronizar todos los productos (con procesamiento por lotes)
     */
    public function sync_all_products($offset = 0) {
        $result = $this->ipos_api->get_products();
        
        if (!$result['success']) {
            return array(
                'success' => false,
                'message' => 'Error al obtener productos de iPos: ' . $result['error']
            );
        }
        
        $all_products = isset($result['data']['Products']) ? $result['data']['Products'] : array();
        
        if (empty($all_products)) {
            return array(
                'success' => false,
                'message' => 'No se encontraron productos en iPos'
            );
        }
        
        $total = count($all_products);
        
        // Filtrar productos ACTIVE
        $active_products = array_filter($all_products, function($product) {
            return isset($product['Status']) && $product['Status'] === 'ACTIVE';
        });
        
        $active_count = count($active_products);
        
        // Obtener el lote actual
        $batch = array_slice($active_products, $offset, $this->batch_size);
        
        if (empty($batch)) {
            // Ya terminamos todos los lotes
            return array(
                'success' => true,
                'completed' => true,
                'total' => $total,
                'active' => $active_count,
                'processed' => $offset,
                'message' => '✅ Sincronización completa!'
            );
        }
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($batch as $product) {
            // Verificar timeout
            if ((time() - $this->start_time) > ($this->max_execution_time - 30)) {
                // Guardamos y salimos
                $this->save_product_map();
                return array(
                    'success' => true,
                    'completed' => false,
                    'total' => $total,
                    'active' => $active_count,
                    'processed' => $offset + $created + $updated + $skipped,
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'next_offset' => $offset + $this->batch_size,
                    'message' => 'Procesando... timeout preventivo'
                );
            }
            
            $sync_result = $this->sync_product($product);
            $this->process_sync_result($sync_result, $created, $updated, $skipped, $errors);
        }
        
        // Guardar el mapeo
        $this->save_product_map();
        
        $next_offset = $offset + $this->batch_size;
        $has_more = $next_offset < $active_count;
        
        return array(
            'success' => true,
            'completed' => !$has_more,
            'total' => $total,
            'active' => $active_count,
            'processed' => min($next_offset, $active_count),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'next_offset' => $has_more ? $next_offset : null,
            'message' => sprintf(
                'Lote procesado: %d creados, %d actualizados, %d omitidos',
                $created,
                $updated,
                $skipped
            )
        );
    }
    
    /**
     * Sincronizar un producto individual
     */
    private function sync_product($ipos_product) {
        $ipos_id = $ipos_product['ID'];
        $name = sanitize_text_field($ipos_product['Name']);
        
        // Los productos en iPos pueden tener múltiples variaciones
        $variations = isset($ipos_product['ProductVariations']) ? $ipos_product['ProductVariations'] : array();
        
        if (empty($variations)) {
            return array(
                'success' => false,
                'type' => 'skip',
                'name' => $name,
                'error' => 'Sin variaciones'
            );
        }
        
        // Por ahora solo manejamos productos SIMPLE (1 variación)
        if (count($variations) > 1) {
            // TODO: Implementar productos variables en el futuro
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
            return array(
                'success' => false,
                'type' => 'skip',
                'name' => $name,
                'error' => 'Sin SKU'
            );
        }
        
        // Buscar si ya existe en WC por SKU
        $wc_product_id = $this->get_wc_product_by_sku($sku);
        
        // Preparar datos del producto
        $product_data = $this->prepare_product_data($ipos_product, $variation);
        
        if ($wc_product_id) {
            // Actualizar producto existente
            $result = $this->update_wc_product($wc_product_id, $product_data);
            
            if ($result) {
                $this->product_map[$ipos_id] = $wc_product_id;
                return array(
                    'success' => true,
                    'type' => 'updated',
                    'name' => $name,
                    'wc_id' => $wc_product_id,
                    'sku' => $sku
                );
            } else {
                return array(
                    'success' => false,
                    'type' => 'update',
                    'name' => $name,
                    'error' => 'Error al actualizar en WC'
                );
            }
        } else {
            // Crear nuevo producto
            $result = $this->create_wc_product($product_data);
            
            if ($result && isset($result['id'])) {
                $wc_product_id = $result['id'];
                $this->product_map[$ipos_id] = $wc_product_id;
                
                // Guardar el iPos ID como meta
                update_post_meta($wc_product_id, '_ipos_product_id', $ipos_id);
                update_post_meta($wc_product_id, '_ipos_variation_id', $variation['ID']);
                
                return array(
                    'success' => true,
                    'type' => 'created',
                    'name' => $name,
                    'wc_id' => $wc_product_id,
                    'sku' => $sku
                );
            } else {
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
        
        // Agregar categorías
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
            }
        }
        
        // Agregar imágenes
        if (!empty($ipos_product['Pictures'])) {
            $images = array();
            foreach ($ipos_product['Pictures'] as $picture) {
                if (!empty($picture['PictureUrl'])) {
                    $images[] = array('src' => $picture['PictureUrl']);
                }
            }
            if (!empty($images)) {
                $data['images'] = $images;
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
        
        // Shipping
        $data['shipping_required'] = isset($ipos_product['RequiresShipping']) && $ipos_product['RequiresShipping'] === 'YES';
        
        // Brand como meta data
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
     * Crear producto en WooCommerce (usando API interna)
     */
    private function create_wc_product($data) {
        $post_data = array(
            'post_title'    => $data['name'],
            'post_content'  => $data['description'],
            'post_excerpt'  => $data['short_description'],
            'post_type'     => 'product',
            'post_status'   => $data['status']
        );
        
        $product_id = wp_insert_post($post_data);
        
        if (is_wp_error($product_id)) {
            return false;
        }
        
        // Establecer tipo de producto
        wp_set_object_terms($product_id, 'simple', 'product_type');
        
        // Agregar metadatos básicos
        update_post_meta($product_id, '_sku', $data['sku']);
        update_post_meta($product_id, '_regular_price', $data['regular_price']);
        update_post_meta($product_id, '_price', $data['regular_price']);
        update_post_meta($product_id, '_manage_stock', 'no');
        update_post_meta($product_id, '_sold_individually', 'no');
        
        // Dimensiones
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
        
        // Peso
        if (isset($data['weight']) && !empty($data['weight'])) {
            update_post_meta($product_id, '_weight', $data['weight']);
        }
        
        // Shipping
        update_post_meta($product_id, '_requires_shipping', $data['shipping_required'] ? 'yes' : 'no');
        
        // Categorías
        if (isset($data['categories']) && !empty($data['categories'])) {
            wp_set_object_terms($product_id, $data['categories'], 'product_cat');
        }
        
        // Imágenes
        if (isset($data['images']) && !empty($data['images'])) {
            $this->attach_product_images($product_id, $data['images']);
        }
        
        // Meta data adicional (Brand, Provider, etc)
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                update_post_meta($product_id, $meta['key'], $meta['value']);
            }
        }
        
        return array('id' => $product_id);
    }
    
    /**
     * Actualizar producto en WooCommerce (usando API interna)
     */
    private function update_wc_product($product_id, $data) {
        $post_data = array(
            'ID'           => $product_id,
            'post_title'   => $data['name'],
            'post_content' => $data['description'],
            'post_excerpt' => $data['short_description']
        );
        
        $result = wp_update_post($post_data);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Actualizar metadatos
        update_post_meta($product_id, '_sku', $data['sku']);
        update_post_meta($product_id, '_regular_price', $data['regular_price']);
        update_post_meta($product_id, '_price', $data['regular_price']);
        
        // Dimensiones
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
        
        // Peso
        if (isset($data['weight']) && !empty($data['weight'])) {
            update_post_meta($product_id, '_weight', $data['weight']);
        }
        
        // Shipping
        update_post_meta($product_id, '_requires_shipping', $data['shipping_required'] ? 'yes' : 'no');
        
        // Categorías
        if (isset($data['categories']) && !empty($data['categories'])) {
            wp_set_object_terms($product_id, $data['categories'], 'product_cat');
        }
        
        // Imágenes (reemplazar)
        if (isset($data['images']) && !empty($data['images'])) {
            delete_post_meta($product_id, '_product_image_gallery');
            $this->attach_product_images($product_id, $data['images']);
        }
        
        // Meta data adicional
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                update_post_meta($product_id, $meta['key'], $meta['value']);
            }
        }
        
        return true;
    }
    
    /**
     * Adjuntar imágenes al producto
     */
    private function attach_product_images($product_id, $images) {
        $gallery_ids = array();
        
        foreach ($images as $index => $image) {
            if (empty($image['src'])) {
                continue;
            }
            
            $image_id = $this->download_image($image['src'], $product_id);
            
            if ($image_id) {
                if ($index === 0) {
                    set_post_thumbnail($product_id, $image_id);
                } else {
                    $gallery_ids[] = $image_id;
                }
            }
        }
        
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }
    
    /**
     * Descargar imagen desde URL
     */
    private function download_image($url, $product_id) {
        $timeout = 10;
        $response = wp_remote_get($url, array('timeout' => $timeout));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return false;
        }
        
        $filename = basename(parse_url($url, PHP_URL_PATH));
        if (empty($filename)) {
            $filename = 'image-' . md5($url) . '.jpg';
        }
        
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . $filename;
        
        if (!file_exists($upload_path)) {
            file_put_contents($upload_path, $image_data);
        }
        
        $attachment = array(
            'post_mime_type' => 'image/jpeg',
            'post_title'     => sanitize_text_field(basename($filename, '.jpg')),
            'post_content'   => '',
            'post_status'    => 'publish'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload_path, $product_id);
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload_path);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
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