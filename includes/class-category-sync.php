<?php
/**
 * Sincronización de categorías iPos <-> WooCommerce
 * 
 * @package Ocellaris_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_Category_Sync {
    
    private $ipos_api;
    private $category_map = array(); // iPos ID => WC ID
    
    public function __construct() {
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $this->ipos_api = new Ocellaris_IPos_API();
        $this->load_category_map();
    }
    

    /**
     * Sincronizar todas las categorías
     */
    public function sync_all_categories() {
        $result = $this->ipos_api->get_categories();
        
        if (!$result['success']) {
            return array(
                'success' => false,
                'message' => 'Error al obtener categorías de iPos: ' . $result['error']
            );
        }
        
        // Validar que $result['data'] es un array
        $categories = isset($result['data']) ? $result['data'] : array();
        
        // Si no es array, probablemente sea un error
        if (!is_array($categories)) {
            return array(
                'success' => false,
                'message' => 'Respuesta inválida del API de iPos: ' . gettype($categories)
            );
        }
        
        if (empty($categories)) {
            return array(
                'success' => false,
                'message' => 'No se encontraron categorías en iPos'
            );
        }
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        // Primero procesamos las categorías sin padre (top-level)
        foreach ($categories as $category) {
            // Validar que $category es un array
            if (!is_array($category)) {
                $errors[] = 'Categoría inválida recibida del API';
                continue;
            }
            
            if (empty($category['Parent'])) {
                $sync_result = $this->sync_category($category);
                $this->process_sync_result($sync_result, $created, $updated, $skipped, $errors);
            }
        }
        
        // Luego procesamos las categorías con padre
        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }
            
            if (!empty($category['Parent'])) {
                $sync_result = $this->sync_category($category);
                $this->process_sync_result($sync_result, $created, $updated, $skipped, $errors);
            }
        }
        
        // Guardar el mapeo actualizado
        $this->save_category_map();
        
        return array(
            'success' => true,
            'total' => count($categories),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => sprintf(
                '✅ Sincronización completa! Creadas: %d | Actualizadas: %d | Omitidas: %d',
                $created,
                $updated,
                $skipped
            )
        );
    }
    
    /**
     * Sincronizar una categoría individual
     */
    private function sync_category($ipos_category) {
        $ipos_id = $ipos_category['ID'];
        $name = sanitize_text_field($ipos_category['Name']);
        
        // Verificar si ya existe en WC
        $wc_cat_id = $this->get_wc_category_id($ipos_id);
        
        $category_data = array(
            'name' => $name,
            'description' => 'Sincronizado desde iPos'
        );
        
        // Procesar categoría padre si existe
        if (!empty($ipos_category['Parent'])) {
            $parent_wc_id = $this->get_wc_category_id($ipos_category['Parent']);
            if ($parent_wc_id) {
                $category_data['parent'] = $parent_wc_id;
            }
        }
        
        if ($wc_cat_id) {
            // Actualizar categoría existente
            $term = wp_update_term($wc_cat_id, 'product_cat', $category_data);
            
            if (is_wp_error($term)) {
                return array(
                    'success' => false,
                    'type' => 'update',
                    'name' => $name,
                    'error' => $term->get_error_message()
                );
            }
            
            return array(
                'success' => true,
                'type' => 'updated',
                'name' => $name,
                'wc_id' => $term['term_id']
            );
            
        } else {
            // Crear nueva categoría
            $term = wp_insert_term($name, 'product_cat', $category_data);
            
            if (is_wp_error($term)) {
                // Si el error es que ya existe, obtener el ID
                if ($term->get_error_code() === 'term_exists') {
                    $existing_term = get_term_by('name', $name, 'product_cat');
                    if ($existing_term) {
                        $this->category_map[$ipos_id] = $existing_term->term_id;
                        return array(
                            'success' => true,
                            'type' => 'skipped',
                            'name' => $name,
                            'wc_id' => $existing_term->term_id
                        );
                    }
                }
                
                return array(
                    'success' => false,
                    'type' => 'create',
                    'name' => $name,
                    'error' => $term->get_error_message()
                );
            }
            
            $wc_cat_id = $term['term_id'];
            $this->category_map[$ipos_id] = $wc_cat_id;
            
            // Guardar el iPos ID como meta
            update_term_meta($wc_cat_id, 'ipos_category_id', $ipos_id);
            
            return array(
                'success' => true,
                'type' => 'created',
                'name' => $name,
                'wc_id' => $wc_cat_id
            );
        }
    }
    
    /**
     * Obtener el ID de WooCommerce para una categoría de iPos
     */
    private function get_wc_category_id($ipos_id) {
        // Primero buscar en el mapa en memoria
        if (isset($this->category_map[$ipos_id])) {
            $term = get_term($this->category_map[$ipos_id], 'product_cat');
            if (!is_wp_error($term) && $term) {
                return $this->category_map[$ipos_id];
            } else {
                unset($this->category_map[$ipos_id]);
            }
        }
        
        // Verificar que la taxonomía existe
        if (!taxonomy_exists('product_cat')) {
            return false;
        }
        
        // Buscar en la base de datos con validación
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
            'suppress_filter' => true // Agregar esto
        ));
        
        if (is_wp_error($terms)) {
            error_log('Error en get_terms: ' . $terms->get_error_message());
            return false;
        }
        
        if (!empty($terms)) {
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
     * Cargar el mapa de categorías guardado
     */
    private function load_category_map() {
        $saved_map = get_option('ocellaris_ipos_category_map', array());
        if (is_array($saved_map)) {
            $this->category_map = $saved_map;
        }
    }
    
    /**
     * Guardar el mapa de categorías
     */
    private function save_category_map() {
        update_option('ocellaris_ipos_category_map', $this->category_map);
    }
}