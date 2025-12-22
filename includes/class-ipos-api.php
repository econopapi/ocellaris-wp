<?php
/**
 * Cliente API de iPos
 * 
 * @package Ocellaris_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_IPos_API {
    
    private $base_url = 'https://ocellaris.ipos.services/api/v1';
    private $api_key;
    
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
     * Hacer petición a la API
     */
    private function make_request($endpoint, $method = 'GET', $body = null) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'error' => 'API Key no configurada'
            );
        }
        
        $url = $this->base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if ($body && $method !== 'GET') {
            $args['body'] = json_encode($body);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code < 200 || $code >= 300) {
            return array(
                'success' => false,
                'error' => 'HTTP Error ' . $code,
                'response' => $body
            );
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Error al parsear JSON: ' . json_last_error_msg()
            );
        }
        
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
}