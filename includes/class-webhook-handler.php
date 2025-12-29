<?php
/**
 * Manejador de Webhooks iPos para actualizar inventario en ventas
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_Webhook_Handler {
    
    private $ipos_api;
    private $webhook_id;
    private $location_id = '1'; // Locación de Ocellaris en iPos
    
    public function __construct() {
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $this->ipos_api = new Ocellaris_IPos_API();
        $this->webhook_id = get_option('ocellaris_ipos_webhook_id');
        
        // Registrar endpoint REST API interno
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
        
        // Hook para cuando una orden pase a "processing" (pago confirmado)
        add_action('woocommerce_order_status_processing', array($this, 'handle_order_paid'), 10, 1);
        
        // Hook alternativo para cuando se complete el pago
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'), 10, 1);
    }
    
    /**
     * Registrar endpoint REST API para recibir webhooks
     */
    public function register_webhook_endpoint() {
        register_rest_route('ocellaris/v1', '/ipos-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_webhook'),
            'permission_callback' => array($this, 'validate_webhook_signature')
        ));
    }
    
    /**
     * Validar firma del webhook (seguridad)
     */
    public function validate_webhook_signature($request) {
        // WooCommerce envía un header X-WC-Webhook-Signature
        $signature = $request->get_header('X-WC-Webhook-Signature');
        $webhook_secret = get_option('ocellaris_webhook_secret');
        
        if (empty($webhook_secret)) {
            // Si no hay secreto configurado, permitir (modo desarrollo)
            return true;
        }
        
        $payload = $request->get_body();
        $expected_signature = base64_encode(hash_hmac('sha256', $payload, $webhook_secret, true));
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Procesar webhook recibido (método REST API)
     */
    public function process_webhook($request) {
        $data = $request->get_json_params();
        
        error_log('[iPos Webhook] Recibido: ' . json_encode($data));
        
        // Validar que sea una orden
        if (!isset($data['id'])) {
            return new WP_Error('invalid_webhook', 'ID de orden no encontrado', array('status' => 400));
        }
        
        $order_id = $data['id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', 'Orden no encontrada', array('status' => 404));
        }
        
        // Solo procesar si el estado es "processing" o "completed"
        $status = $order->get_status();
        if (!in_array($status, array('processing', 'completed'))) {
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Orden en estado ' . $status . ', no se actualiza inventario'
            ));
        }
        
        // Procesar la orden
        $result = $this->process_order_inventory($order);
        
        if ($result['success']) {
            return rest_ensure_response($result);
        } else {
            return new WP_Error('sync_failed', $result['message'], array('status' => 500));
        }
    }
    
    /**
     * MÉTODO PRINCIPAL: Manejar orden cuando pasa a "processing" (pago confirmado)
     * Este es el hook directo, más confiable que el webhook
     */
    public function handle_order_paid($order_id) {
        error_log('[iPos Stock] Orden #' . $order_id . ' pagada, actualizando inventario en iPos');
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            error_log('[iPos Stock] ERROR: Orden no encontrada');
            return;
        }
        
        // Verificar que no se haya procesado antes
        $already_synced = $order->get_meta('_ipos_inventory_synced');
        if ($already_synced) {
            error_log('[iPos Stock] Orden ya sincronizada previamente, omitiendo');
            return;
        }
        
        // Procesar inventario
        $result = $this->process_order_inventory($order);
        
        if ($result['success']) {
            // Marcar como sincronizada
            $order->update_meta_data("_ipos_inventory_synced", true);
            $order->update_meta_data("_ipos_sync_date", current_time("mysql"));
            $order->update_meta_data("_ipos_sync_details", json_encode($result["details"]));
            $order->save();
            
            // Agregar nota a la orden
            $order->add_order_note(
                '✅ Inventario actualizado en iPos: ' . $result['total_items'] . ' productos procesados'
            );
            
            error_log('[iPos Stock] ✅ Orden #' . $order_id . ' sincronizada exitosamente');
        } else {
            // Agregar nota de error
            $order->add_order_note(
                '❌ Error al actualizar inventario en iPos: ' . $result['message']
            );
            
            error_log('[iPos Stock] ❌ Error en orden #' . $order_id . ': ' . $result['message']);
        }
    }
    
    /**
     * Método alternativo: cuando se completa el pago
     */
    public function handle_payment_complete($order_id) {
        // Este se ejecuta justo después de confirmar el pago
        // Podés usarlo si preferís este momento en lugar de "processing"
        error_log('[iPos Stock] Pago completado para orden #' . $order_id);
        
        // Llamar al mismo procesador
        $this->handle_order_paid($order_id);
    }
    
    /**
     * Procesar inventario de una orden
     */
    private function process_order_inventory($order) {
        $order_id = $order->get_id();
        $items = $order->get_items();
        
        if (empty($items)) {
            return array(
                'success' => false,
                'message' => 'Orden sin productos'
            );
        }
        
        $processed_items = array();
        $errors = array();
        
        foreach ($items as $item_id => $item) {
            $product = $item->get_product();
            
            if (!$product) {
                $errors[] = 'Item #' . $item_id . ': Producto no encontrado';
                continue;
            }
            
            $product_id = $product->get_id();
            $quantity = $item->get_quantity();
            
            $ipos_product_id = get_post_meta($product_id, '_ipos_product_id', true);
            $ipos_variation_id = get_post_meta($product_id, '_ipos_variation_id', true);
            
            if (empty($ipos_product_id) || empty($ipos_variation_id)) {
                $errors[] = 'Producto #' . $product_id . ': No tiene mapeo con iPos';
                continue;
            }
            
            // Usar método de Ipos_API
            $result = $this->ipos_api->update_inventory(
                $ipos_product_id,
                $ipos_variation_id,
                $quantity,
                'Venta WooCommerce - Orden #' . $order_id,
                $this->location_id
            );
            
            if ($result['success']) {
                $processed_items[] = array(
                    'product_id' => $product_id,
                    'ipos_product_id' => $ipos_product_id,
                    'ipos_variation_id' => $ipos_variation_id,
                    'quantity' => $quantity,
                    'new_stock' => $result['new_stock']
                );
                
                error_log(sprintf(
                    '[iPos Stock] ✅ Producto #%d: -%d unidades (nuevo stock: %d)',
                    $product_id,
                    $quantity,
                    $result['new_stock']
                ));
            } else {
                $errors[] = 'Producto #' . $product_id . ': ' . $result['error'];
                
                error_log(sprintf(
                    '[iPos Stock] ❌ Error producto #%d: %s',
                    $product_id,
                    $result['error']
                ));
            }
        }
        
        $success = !empty($processed_items);
        
        return array(
            'success' => $success,
            'message' => $success ? 'Inventario actualizado' : 'Error al actualizar inventario',
            'order_id' => $order_id,
            'total_items' => count($items),
            'processed' => count($processed_items),
            'errors' => $errors,
            'details' => $processed_items
        );
    }
    
    /**
     * Crear webhook programáticamente
     */
    public static function create_webhook() {
        if (!class_exists('WC_Webhook')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce no está activo'
            );
        }
        
        // Verificar si ya existe
        $existing_id = get_option('ocellaris_ipos_webhook_id');
        if ($existing_id) {
            $existing_webhook = wc_get_webhook($existing_id);
            if ($existing_webhook && $existing_webhook->get_status() === 'active') {
                return array(
                    'success' => true,
                    'message' => 'Webhook ya existe y está activo',
                    'webhook_id' => $existing_id
                );
            }
        }
        
        try {
            $webhook = new WC_Webhook();
            $webhook->set_name('iPos Stock Update - Ventas');
            $webhook->set_status('active');
            $webhook->set_topic('order.updated');
            $webhook->set_delivery_url(rest_url('ocellaris/v1/ipos-webhook'));
            
            // Generar secreto
            $secret = 'ipos_' . wp_generate_password(32, false);
            $webhook->set_secret($secret);
            
            $webhook->save();
            
            // Guardar datos
            update_option('ocellaris_ipos_webhook_id', $webhook->get_id());
            update_option('ocellaris_webhook_secret', $secret);
            
            return array(
                'success' => true,
                'message' => 'Webhook creado exitosamente',
                'webhook_id' => $webhook->get_id(),
                'delivery_url' => rest_url('ocellaris/v1/ipos-webhook')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error al crear webhook: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Eliminar webhook
     */
    public static function delete_webhook() {
        $webhook_id = get_option('ocellaris_ipos_webhook_id');
        
        if (!$webhook_id) {
            return array(
                'success' => false,
                'message' => 'No hay webhook configurado'
            );
        }
        
        $webhook = wc_get_webhook($webhook_id);
        
        if ($webhook) {
            $webhook->delete(true);
        }
        
        delete_option('ocellaris_ipos_webhook_id');
        delete_option('ocellaris_webhook_secret');
        
        return array(
            'success' => true,
            'message' => 'Webhook eliminado'
        );
    }
    
    /**
     * Verificar estado del webhook
     */
    // public static function get_webhook_status() {
    //     $webhook_id = get_option('ocellaris_ipos_webhook_id');
        
    //     if (!$webhook_id) {
    //         return array(
    //             'active' => false,
    //             'message' => 'No configurado'
    //         );
    //     }
        
    //     $webhook = wc_get_webhook($webhook_id);
        
    //     if (!$webhook) {
    //         return array(
    //             'active' => false,
    //             'message' => 'Webhook no encontrado'
    //         );
    //     }
        
    //     return array(
    //         'active' => $webhook->get_status() === 'active',
    //         'status' => $webhook->get_status(),
    //         'delivery_url' => $webhook->get_delivery_url(),
    //         'webhook_id' => $webhook_id
    //     );
    // }
    public static function get_webhook_status() {
        $webhook_id = get_option('ocellaris_ipos_webhook_id');
        
        if (!$webhook_id) {
            return array(
                'webhook_id' => null,
                'active' => false,
                'status' => 'not_configured',
                'message' => 'No configurado'
            );
        }
        
        $webhook = wc_get_webhook($webhook_id);
        
        if (!$webhook) {
            // El webhook se registró pero no existe en WC
            return array(
                'webhook_id' => $webhook_id,
                'active' => false,
                'status' => 'deleted',
                'message' => 'Webhook fue eliminado',
                'delivery_url' => null
            );
        }
        
        $status = $webhook->get_status();
        
        return array(
            'webhook_id' => $webhook_id,
            'active' => $status === 'active',
            'status' => $status,
            'message' => $status === 'active' ? 'Activo' : 'Inactivo',
            'delivery_url' => $webhook->get_delivery_url(),
            'can_reactivate' => in_array($status, array('disabled', 'paused', 'inactive'))
        );
    }
    
    /**
     * Reactivar webhook desactivado
     */
    public static function reactivate_webhook() {
        $webhook_id = get_option('ocellaris_ipos_webhook_id');
        
        if (!$webhook_id) {
            return array(
                'success' => false,
                'message' => 'No hay webhook configurado'
            );
        }
        
        $webhook = wc_get_webhook($webhook_id);
        
        if (!$webhook) {
            // Si fue eliminado, recrearlo
            return self::create_webhook();
        }
        
        try {
            $webhook->set_status('active');
            $webhook->save();
            
            return array(
                'success' => true,
                'message' => 'Webhook reactivado exitosamente',
                'webhook_id' => $webhook_id,
                'status' => 'active',
                'delivery_url' => $webhook->get_delivery_url()
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Error al reactivar webhook: ' . $e->getMessage()
            );
        }
    }    
}

// Inicializar
new Ocellaris_Webhook_Handler();