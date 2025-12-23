<?php
/**
 * Página de administración para Integración iPos
 * 
 * @package Ocellaris_Child
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Ocellaris_IPos_Admin {
    
    private $api_key_option = 'ocellaris_ipos_api_key';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_test_ipos_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_sync_ipos_categories', array($this, 'ajax_sync_categories'));
        add_action('wp_ajax_clear_ipos_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_sync_ipos_products', array($this, 'ajax_sync_products'));
    }
    
    /**
     * Agregar menú en el admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'Integración iPos',
            'iPos Sync',
            'manage_options',
            'ocellaris-ipos',
            array($this, 'render_admin_page'),
            'dashicons-update',
            30
        );
        
        add_submenu_page(
            'ocellaris-ipos',
            'Configuración',
            'Configuración',
            'manage_options',
            'ocellaris-ipos-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registrar settings
     */
    public function register_settings() {
        register_setting('ocellaris_ipos_settings', $this->api_key_option);
    }
    
    /**
     * Cargar scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ocellaris-ipos') === false) {
            return;
        }
        
        wp_enqueue_style(
            'ocellaris-ipos-admin',
            get_stylesheet_directory_uri() . '/admin/css/ipos-admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'ocellaris-ipos-admin',
            get_stylesheet_directory_uri() . '/admin/js/ipos-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('ocellaris-ipos-admin', 'iposAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ipos_sync_nonce')
        ));
    }
    
    /**
     * Renderizar página principal
     */
    public function render_admin_page() {
        $api_key = get_option($this->api_key_option);
        ?>
        <div class="wrap ocellaris-ipos-wrap">
            <h1>🐠 Integración iPos - Ocellaris</h1>
            
            <?php if (empty($api_key)): ?>
                <div class="notice notice-warning">
                    <p><strong>Atención!</strong> Todavía no configuraste tu API Key de iPos. 
                    <a href="<?php echo admin_url('admin.php?page=ocellaris-ipos-settings'); ?>">
                        Ve a la configuración
                    </a> para empezar.</p>
                </div>
            <?php else: ?>
                
                <div class="ipos-status-card">
                    <h2>Estado de Conexión</h2>
                    <div id="connection-status">
                        <button type="button" class="button button-secondary" id="test-connection">
                            Probar Conexión
                        </button>
                    </div>
                </div>
                
                <div class="ipos-sync-card">
                    <h2>Sincronización de Categorías</h2>
                    <p>Sincroniza las categorías de productos desde iPos a WooCommerce.</p>
                    
                    <div id="sync-progress" style="display: none;">
                        <div class="sync-spinner"></div>
                        <p id="sync-message">Sincronizando...</p>
                    </div>
                    
                    <div id="sync-results" style="display: none;"></div>
                    
                    <button type="button" class="button button-primary" id="sync-categories">
                        🔄 Sincronizar Categorías Ahora
                    </button>
                    
                    <button type="button" class="button button-secondary" id="clear-cache" style="margin-left: 10px;">
                        🗑️ Limpiar Caché
                    </button>
                </div>
                
                <div class="ipos-sync-card">
                    <h2>Sincronización de Productos</h2>
                    <p>Sincroniza los productos desde iPos a WooCommerce. Este proceso puede tardar varios minutos.</p>
                    <p><strong>Nota:</strong> Asegurate de haber sincronizado las categorías primero.</p>
                    
                    <div id="product-sync-progress" style="display: none;">
                        <div class="sync-spinner"></div>
                        <p id="product-sync-message">Sincronizando productos...</p>
                        <div class="progress-bar">
                            <div class="progress-fill" id="product-progress-fill" style="width: 0%"></div>
                        </div>
                        <p id="product-progress-text">0 / 0 procesados</p>
                    </div>
                    
                    <div id="product-sync-results" style="display: none;"></div>
                    
                    <button type="button" class="button button-primary" id="sync-products">
                        🔄 Sincronizar Productos Ahora
                    </button>
                </div>
                
                <div class="ipos-stats-card">
                    <h2>Estadísticas</h2>
                    <?php $this->render_stats(); ?>
                </div>
                
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderizar página de configuración
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configuración iPos</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ocellaris_ipos_settings');
                do_settings_sections('ocellaris_ipos_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo $this->api_key_option; ?>">
                                API Key de iPos
                            </label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="<?php echo $this->api_key_option; ?>" 
                                   name="<?php echo $this->api_key_option; ?>" 
                                   value="<?php echo esc_attr(get_option($this->api_key_option)); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Pegá acá tu Bearer token de iPos. Lo encontrás en tu panel de iPos.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Guardar Configuración'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderizar estadísticas
     */
    private function render_stats() {
        $ipos_categories = get_transient('ocellaris_ipos_categories_count');
        $wc_categories = wp_count_terms('product_cat');
        $last_sync = get_option('ocellaris_ipos_last_sync');
        
        $wc_products = wp_count_posts('product');
        $wc_products_count = isset($wc_products->publish) ? $wc_products->publish : 0;
        $last_product_sync = get_option('ocellaris_ipos_last_product_sync');
        
        ?>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>Categorías en iPos:</strong></td>
                    <td><?php echo $ipos_categories ? $ipos_categories : 'N/A'; ?></td>
                </tr>
                <tr>
                    <td><strong>Categorías en WooCommerce:</strong></td>
                    <td><?php echo is_numeric($wc_categories) ? $wc_categories : 0; ?></td>
                </tr>
                <tr>
                    <td><strong>Última sincronización (categorías):</strong></td>
                    <td><?php echo $last_sync ? date('d/m/Y H:i:s', $last_sync) : 'Nunca'; ?></td>
                </tr>
                <tr>
                    <td><strong>Productos en WooCommerce:</strong></td>
                    <td><?php echo $wc_products_count; ?></td>
                </tr>
                <tr>
                    <td><strong>Última sincronización (productos):</strong></td>
                    <td><?php echo $last_product_sync ? date('d/m/Y H:i:s', $last_product_sync) : 'Nunca'; ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * AJAX: Probar conexión
     */
    public function ajax_test_connection() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tenés permisos para hacer esto.');
        }
        
        $api_key = get_option($this->api_key_option);
        
        if (empty($api_key)) {
            wp_send_json_error('Falta configurar el API Key.');
        }
        
        $response = wp_remote_get(
            'https://ocellaris.ipos.services/api/v1/categories',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key
                ),
                'timeout' => 15
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error('Error de conexión: ' . $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            wp_send_json_success('✅ Conexión exitosa con iPos!');
        } else {
            wp_send_json_error('Error HTTP ' . $code . '. Verificá tu API Key.');
        }
    }
    
    /**
     * AJAX: Sincronizar categorías
     */
    public function ajax_sync_categories() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tenés permisos para hacer esto.');
        }
        
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        require_once get_stylesheet_directory() . '/includes/class-category-sync.php';
        
        $sync = new Ocellaris_Category_Sync();
        $result = $sync->sync_all_categories();
        
        if ($result['success']) {
            update_option('ocellaris_ipos_last_sync', time());
            set_transient('ocellaris_ipos_categories_count', $result['total'], HOUR_IN_SECONDS);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Limpiar caché y mapeo
     */
    // public function ajax_clear_cache() {
    //     check_ajax_referer('ipos_sync_nonce', 'nonce');
        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('No tenés permisos para hacer esto.');
    //     }
        
    //     // Eliminar opciones guardadas
    //     delete_option('ocellaris_ipos_category_map');
    //     delete_option('ocellaris_ipos_product_map');
    //     delete_transient('ocellaris_ipos_categories_count');
        
    //     // Limpiar sesión y caché de productos
    //     delete_transient('ocellaris_sync_session_id');
        
    //     // Limpiar todas las sesiones de caché de productos
    //     global $wpdb;
    //     $wpdb->query(
    //         "DELETE FROM {$wpdb->options} 
    //         WHERE option_name LIKE '_transient_ocellaris_ipos_products_cache_%' 
    //         OR option_name LIKE '_transient_timeout_ocellaris_ipos_products_cache_%'"
    //     );
        
    //     wp_send_json_success('✅ Caché limpiado correctamente. Puedes iniciar una nueva sincronización.');
    // }

    public function ajax_clear_cache() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tenés permisos para hacer esto.');
        }
        
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        $api = new Ocellaris_IPos_API();
        
        // Eliminar opciones guardadas
        delete_option('ocellaris_ipos_category_map');
        delete_option('ocellaris_ipos_product_map');
        delete_transient('ocellaris_ipos_categories_count');
        
        // Limpiar caché de productos usando la API
        $api->clear_products_cache();
        
        // Limpiar sesión
        delete_transient('ocellaris_sync_session_id');
        
        // Limpiar todas las sesiones de caché de productos
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_ocellaris_ipos_products_cache%' 
            OR option_name LIKE '_transient_timeout_ocellaris_ipos_products_cache%'"
        );
        
        wp_send_json_success('✅ Caché limpiado correctamente. Puedes iniciar una nueva sincronización.');
    }
    
    /**
     * AJAX: Sincronizar productos
     */
    public function ajax_sync_products() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tenés permisos para hacer esto.');
        }
        
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        require_once get_stylesheet_directory() . '/includes/class-ipos-api.php';
        require_once get_stylesheet_directory() . '/includes/class-product-sync.php';
        
        $sync = new Ocellaris_Product_Sync();
        $result = $sync->sync_all_products($offset);
        
        if ($result['success'] && $result['completed']) {
            update_option('ocellaris_ipos_last_product_sync', time());
        }
        
        wp_send_json_success($result);
    }
}

// Inicializar
new Ocellaris_IPos_Admin();
?>