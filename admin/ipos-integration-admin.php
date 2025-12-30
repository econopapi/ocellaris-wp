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
        add_action('wp_ajax_sync_ipos_stock', array($this, 'ajax_sync_stock'));
        
        // NUEVO: AJAX handlers para webhooks
        add_action('wp_ajax_create_ipos_webhook', array($this, 'ajax_create_webhook'));
        add_action('wp_ajax_delete_ipos_webhook', array($this, 'ajax_delete_webhook'));
        add_action('wp_ajax_get_webhook_status', array($this, 'ajax_get_webhook_status'));
        add_action('wp_ajax_reactivate_ipos_webhook', array($this, 'ajax_reactivate_webhook'));
        // Nuevo: endpoint para System Health
        add_action('wp_ajax_get_ipos_system_health', array($this, 'ajax_get_system_health'));
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
            <h1 class="ipos-title">
                <span class="ipos-logo" aria-hidden="true">
                    <svg xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 164.7 153" width="36" height="33" style="display:block">
                        <title>Logo iPos Full Color</title>
                        <g fill-rule="evenodd" fill="none" stroke-width="1" stroke="none" id="Page-1">
                            <g fill-rule="nonzero" transform="translate(-379.000000, -268.000000)" id="Artboard">
                                <g transform="translate(379.000000, 268.000000)" id="Logo-iPos-Full-Color">
                                    <path fill="#444F5C" id="Shape" d="M220.27972,43.8672255 L235.564436,43.8672255 L235.564436,53.7500579 L235.789211,53.7500579 C238.036963,49.4824712 241.183816,46.4502385 245.22977,44.6533599 C249.275724,42.8564813 253.658841,41.958042 258.379121,41.958042 C264.110889,41.958042 269.168332,42.9687862 273.439061,44.9902746 C277.70979,47.0117631 281.306194,49.8193859 284.115884,53.3008382 C286.925574,56.7822906 289.060939,60.9375724 290.521978,65.6543787 C291.983017,70.3711851 292.657343,75.4249062 292.657343,80.7032372 C292.657343,85.6446534 291.983017,90.3614597 290.746753,94.9659612 C289.51049,99.5704626 287.5999,103.61344 284.902597,107.207197 C282.317682,110.688649 279.058442,113.496272 275.124875,115.630065 C271.191309,117.763859 266.471029,118.774603 261.188811,118.774603 C258.828671,118.774603 256.468531,118.549993 254.108392,118.100773 C251.748252,117.651554 249.5005,116.977724 247.365135,116.079285 C245.22977,115.180846 243.206793,113.945492 241.408591,112.485528 C239.61039,111.025564 238.036963,109.34099 236.800699,107.319502 L236.463536,107.319502 L236.463536,143.706294 L220.392108,143.706294 L220.392108,43.8672255 L220.27972,43.8672255 Z M276.473526,80.3663224 C276.473526,77.1094799 276.023976,73.8526374 275.237263,70.8204048 C274.338162,67.6758672 273.101898,64.9805493 271.416084,62.6221461 C269.73027,60.2637429 267.594905,58.2422544 265.00999,56.7822906 C262.425075,55.3223267 259.502997,54.6484972 256.243756,54.6484972 C249.5005,54.6484972 244.330669,57.0069004 240.959041,61.7237068 C237.475025,66.4405131 235.789211,72.7295883 235.789211,80.4786273 C235.789211,84.1846895 236.238761,87.5538369 237.137862,90.6983745 C238.036963,93.8429121 239.385614,96.53823 241.183816,98.7843283 C242.982018,101.030427 245.117383,102.827305 247.58991,104.174964 C250.062438,105.522623 252.984515,106.196453 256.243756,106.196453 C259.952547,106.196453 262.987013,105.410318 265.571928,103.950354 C268.156843,102.49039 270.17982,100.468902 271.865634,98.1104988 C273.551449,95.7520956 274.675325,92.9444727 275.34965,89.9122401 C276.136364,86.7677025 276.473526,83.6231649 276.473526,80.3663224 Z"/>
                                    <path fill="#444F5C" id="Shape" d="M338.811189,118.531469 C332.99079,118.531469 327.841975,117.525394 323.252814,115.625032 C318.663653,113.724669 314.858008,111.041805 311.723947,107.688224 C308.589886,104.334643 306.23934,100.310347 304.560379,95.6153336 C302.881417,90.9203206 302.097902,85.7781634 302.097902,80.1888622 C302.097902,74.711347 302.881417,69.5691899 304.560379,64.8741769 C306.23934,60.1791639 308.589886,56.154867 311.723947,52.8012863 C314.858008,49.4477056 318.663653,46.764841 323.252814,44.8644786 C327.841975,42.9641162 332.99079,41.958042 338.811189,41.958042 C344.631588,41.958042 349.780403,42.9641162 354.369563,44.8644786 C358.958724,46.764841 362.76437,49.4477056 365.898431,52.8012863 C369.032492,56.154867 371.383038,60.1791639 373.061999,64.8741769 C374.74096,69.5691899 375.524476,74.711347 375.524476,80.1888622 C375.524476,85.7781634 374.74096,90.9203206 373.061999,95.6153336 C371.383038,100.310347 369.032492,104.334643 365.898431,107.688224 C362.76437,111.041805 358.958724,113.724669 354.369563,115.625032 C349.892333,117.525394 344.631588,118.531469 338.811189,118.531469 Z M338.811189,105.899648 C342.392973,105.899648 345.415103,105.117146 348.101441,103.663927 C350.675849,102.210709 352.914464,100.198561 354.593425,97.739268 C356.272386,95.2799755 357.503624,92.5971109 358.28714,89.5788883 C359.070655,86.5606656 359.518378,83.4306569 359.518378,80.3006483 C359.518378,77.1706396 359.070655,74.1524169 358.28714,71.0224082 C357.503624,67.8923996 356.272386,65.209535 354.593425,62.8620285 C352.914464,60.514522 350.787779,58.5023735 348.101441,57.0491552 C345.527034,55.5959369 342.392973,54.8134347 338.811189,54.8134347 C335.229405,54.8134347 332.207274,55.5959369 329.520936,57.0491552 C326.946529,58.5023735 324.707914,60.514522 323.028953,62.8620285 C321.349991,65.209535 320.118753,68.0041856 319.335238,71.0224082 C318.551723,74.1524169 318.104,77.1706396 318.104,80.3006483 C318.104,83.4306569 318.551723,86.5606656 319.335238,89.5788883 C320.118753,92.5971109 321.349991,95.3917615 323.028953,97.739268 C324.707914,100.198561 326.834598,102.098923 329.520936,103.663927 C332.207274,105.117146 335.341335,105.899648 338.811189,105.899648 Z"/>
                                    <path fill="#444F5C" id="Path" d="M401.195598,93.4935299 C401.646447,98.2091955 403.449843,101.46525 406.605786,103.373972 C409.761729,105.282694 413.481233,106.180916 417.764298,106.180916 C419.229557,106.180916 421.032953,106.068638 422.949061,105.844083 C424.865169,105.619527 426.668565,105.170416 428.359249,104.49675 C430.049933,103.823083 431.40248,102.924861 432.529602,101.577528 C433.656725,100.342473 434.107574,98.6583065 433.994861,96.5250292 C433.882149,94.5040297 433.093163,92.8198634 431.740616,91.4725304 C430.275357,90.1251974 428.471961,89.1146976 426.330429,88.3287534 C424.076184,87.5428091 421.596514,86.8691426 418.778708,86.3077538 C415.960902,85.7463651 413.030384,85.1849763 410.099865,84.5113098 C407.056635,83.8376433 404.126117,83.051699 401.421023,82.153477 C398.603217,81.255255 396.123547,79.907922 393.869302,78.3360335 C391.615057,76.7641449 389.924374,74.7431454 388.571827,72.1607571 C387.21928,69.6906466 386.543006,66.5468696 386.543006,62.9539815 C386.543006,59.0242602 387.557417,55.6559277 389.473525,52.9612616 C391.389633,50.2665956 393.869302,48.1333183 396.799821,46.449152 C399.730339,44.7649857 403.111706,43.6422082 406.718498,42.9685417 C410.32529,42.2948752 413.81937,41.958042 417.088025,41.958042 C420.807529,41.958042 424.414321,42.2948752 427.9084,43.1930972 C431.40248,43.9790415 434.44571,45.3263745 437.263516,47.1228185 C440.081322,48.9192626 442.335567,51.2770953 444.138963,54.0840391 C445.942359,57.0032607 447.182194,60.3715932 447.633043,64.4135923 L430.838918,64.4135923 C430.049933,60.5961487 428.359249,58.0137604 425.541443,56.6664274 C422.723637,55.3190944 419.567694,54.6454279 415.960902,54.6454279 C414.83378,54.6454279 413.481233,54.7577056 411.903261,54.8699834 C410.32529,55.0945389 408.860031,55.4313721 407.507484,55.8804832 C406.154937,56.4418719 405.027814,57.1155384 404.013404,58.1260382 C403.111706,59.1365379 402.548145,60.3715932 402.548145,62.0557595 C402.548145,64.076759 403.224419,65.6486475 404.576965,66.8837028 C405.929512,68.1187581 407.732908,69.1292578 409.987153,69.9152021 C412.241398,70.7011464 414.721067,71.3748129 417.538874,71.9362016 C420.35668,72.4975904 423.287198,73.0589791 426.330429,73.7326457 C429.260947,74.4063122 432.191465,75.1922564 435.009271,76.0904784 C437.827078,76.9887004 440.419459,78.3360335 442.560992,79.907922 C444.815237,81.4798105 446.618633,83.50081 447.97118,85.9709206 C449.323727,88.4410311 450,91.4725304 450,94.9531407 C450,99.2196953 448.98559,102.924861 447.069482,105.95636 C445.040661,108.98786 442.560992,111.45797 439.405049,113.254414 C436.249106,115.163136 432.755027,116.510469 428.810098,117.296413 C424.977882,118.194635 421.145665,118.531469 417.313449,118.531469 C412.692247,118.531469 408.409182,117.97008 404.464253,116.95958 C400.519325,115.94908 397.137957,114.377192 394.320151,112.243914 C391.389633,110.110637 389.135388,107.528249 387.557417,104.384472 C385.866733,101.240695 385.077747,97.535529 384.965035,93.1566967 L401.195598,93.1566967 L401.195598,93.4935299 Z"/>
                                    <path fill="#3B82F6" id="Path" d="M207.692308,13.6363636 C207.692308,20.5925653 202.061097,26.2237762 195.104895,26.2237762 C188.148693,26.2237762 182.517483,20.5925653 182.517483,13.6363636 C182.517483,6.68016194 188.148693,1.04895105 195.104895,1.04895105 C202.061097,1.04895105 207.692308,6.68016194 207.692308,13.6363636 Z"/>
                                    <path fill="#3B82F6" id="Path" d="M205.594406,129.46516 C205.594406,136.79415 200.649351,142.657343 194.58042,142.657343 L194.58042,142.657343 C188.511489,142.657343 183.566434,136.681397 183.566434,129.46516 L183.566434,52.0033712 C183.566434,44.674381 188.511489,38.8111888 194.58042,38.8111888 L194.58042,38.8111888 C200.649351,38.8111888 205.594406,44.7871347 205.594406,52.0033712 L205.594406,129.46516 Z"/>
                                    <g id="Group">
                                        <path fill="#3B82F6" id="Path" d="M108.685633,0.673971698 C101.290528,0.673971698 95.3520343,6.62738836 95.3520343,13.9287484 C95.3520343,15.501349 95.6881755,17.0739497 96.1363636,18.4218931 C96.5845518,19.7698365 97.2568341,21.0054512 98.0411634,22.1287374 C98.3773045,22.4657233 99.0495868,23.0273663 98.4893516,24.1506525 C98.4893516,24.1506525 87.3966942,43.3588459 87.3966942,43.3588459 L93.2231405,46.7287044 L104.315798,27.520511 C105.100127,26.1725676 106.220598,26.9588679 106.780833,27.0711965 C107.453115,27.1835251 107.901303,27.1835251 108.573586,27.1835251 C115.96869,27.1835251 121.907184,21.2301085 121.907184,13.9287484 C121.907184,6.62738836 116.080737,0.673971698 108.685633,0.673971698 Z"/>
                                        <path fill="#3B82F6" id="Path" d="M145.997298,72.002643 C142.972028,65.2629261 135.128735,62.342382 128.405912,65.3752547 C126.949301,66.0492264 125.716783,66.9478553 124.596313,67.9588128 C123.587889,68.9697704 122.691513,69.9807279 122.019231,71.3286713 C121.795137,71.7779858 121.571043,72.6766147 120.338525,72.5642861 C120.338525,72.5642861 105.212174,71.1040141 105.212174,71.1040141 L104.539892,77.8437311 L119.666243,79.3040031 C121.234901,79.4163317 120.89876,80.8766037 121.122854,81.4382468 C121.346949,82.1122185 121.458996,82.4492043 121.795137,83.0108474 C124.820407,89.7505644 132.6637,92.6711084 139.386523,89.6382358 C146.109345,86.6053631 149.022568,78.74236 145.997298,72.002643 Z"/>
                                        <path fill="#3B82F6" id="Path" d="M96.2484107,138.950498 C101.626669,133.895711 101.850763,125.471064 96.8086459,120.079291 C95.8002225,118.956005 94.455658,117.945047 93.2231405,117.271075 C91.990623,116.597104 90.6460585,116.147789 89.1894469,115.923132 C88.7412587,115.810803 87.8448824,115.923132 87.5087413,114.799846 C87.5087413,114.799846 84.1473299,100.758769 84.1473299,100.758769 L77.5365544,102.331369 L80.8979657,116.372446 C81.2341068,117.945047 79.8895423,118.057376 79.3293071,118.394362 C78.7690718,118.731347 78.5449777,119.068333 77.9847425,119.517648 C72.6064844,124.572435 72.3823903,132.997082 77.4245073,138.388855 C82.5786713,143.6683 90.9821996,144.005286 96.2484107,138.950498 Z"/>
                                        <path fill="#3B82F6" id="Path" d="M22.7455499,29.4300975 C20.9527972,36.6191289 25.3226319,43.8081603 32.3815957,45.6054182 C33.8382072,45.9424041 35.5189129,46.0547327 36.9755245,45.9424041 C38.432136,45.8300754 39.7767006,45.4930896 41.1212651,44.9314465 C41.5694533,44.7067893 42.2417355,44.1451462 43.1381119,45.0437751 C43.1381119,45.0437751 51.205499,52.5697924 51.205499,52.5697924 L55.9114749,47.6273333 L47.8440877,40.101316 C46.7236173,38.9780298 47.8440877,38.0794009 48.0681818,37.5177578 C48.2922759,36.9561148 48.4043229,36.5068003 48.51637,35.8328286 C50.3091227,28.6437971 45.939288,21.4547657 38.8803242,19.6575078 C31.8213605,17.86025 24.5383026,22.241066 22.7455499,29.4300975 Z"/>
                                        <path fill="#3B82F6" id="Path" d="M4.48188175,100.983426 C9.86013986,105.925885 18.2636682,105.588899 23.3057851,100.084797 C24.3142085,98.9615109 25.2105849,97.6135675 25.7708201,96.2656241 C26.3310553,94.9176807 26.7792435,93.5697373 26.8912905,92.221794 C26.8912905,91.7724795 26.7792435,90.8738506 27.8997139,90.4245361 C27.8997139,90.4245361 47.1718055,83.5724905 47.1718055,83.5724905 L45.1549587,77.0574308 L25.7708201,83.9094764 C24.3142085,84.3587908 23.9780674,83.0108474 23.6419263,82.561533 C23.1937381,81.9998899 22.8575969,81.7752326 22.4094088,81.3259182 C17.0311507,76.3834591 8.62762238,76.7204449 3.5855054,82.2245471 C-1.45661157,87.7286493 -1.00842339,96.0409669 4.48188175,100.983426 Z"/>
                                        <ellipse ry="23.0273663" rx="22.969644" cy="71.3286713" cx="74.3992371" fill="#93C5FD" id="Oval"/>
                                    </g>
                                </g>
                            </g>
                        </g>
                    </svg>
                </span>
                <span class="ipos-title-text">iPos Sync</span>
            </h1>
            <p>Módulo de integración iPos para WooCommerce. Sincroniza productos e inventarios entre ambas plataformas.</p>
            <?php if (empty($api_key)): ?>
                <div class="notice notice-warning">
                    <p><strong>Atención!</strong> Todavía no configuraste tu API Key de iPos. 
                    <a href="<?php echo admin_url('admin.php?page=ocellaris-ipos-settings'); ?>">
                        Ve a la configuración
                    </a> para empezar.</p>
                </div>
            <?php else: ?>
                
                <div class="ipos-cards-row">
                    <div class="ipos-status-card">
                        <h2>System Health</h2>
                        <div id="system-health">
                            <!-- se completa vía JS -->
                            <div class="health-loading">
                                <span class="sync-spinner"></span> Comprobando estado del sistema...
                            </div>
                        </div>
                    </div>

                    <div class="ipos-stats-card">
                        <h2>Estadísticas</h2>
                        <?php $this->render_stats(); ?>
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
                
                <div class="ipos-sync-card">
                    <h2>Sincronización de Stock</h2>
                    <p>Sincroniza el inventario desde iPos a WooCommerce. Este proceso actualiza el stock de todos los productos sincronizados.</p>
                    <p><strong>Nota:</strong> Asegurate de haber sincronizado los productos primero.</p>
                    
                    <div id="stock-sync-progress" style="display: none;">
      
                    <div class="sync-spinner"></div>
                        <p id="stock-sync-message">Sincronizando stock...</p>
                        <div class="progress-bar">
                            <div class="progress-fill" id="stock-progress-fill" style="width: 0%"></div>
                        </div>
                        <p id="stock-progress-text">0 / 0 procesados</p>
                    </div>
                    
                    <div id="stock-sync-results" style="display: none;"></div>
                    
                    <button type="button" class="button button-primary" id="sync-stock">
                        🔄 Sincronizar Stock Ahora
                    </button>
                </div>

                <div class="ipos-webhook-card">
                    <h2>Webhooks - Sincronización de Ventas</h2>
                    <p>Los webhooks actualizan automáticamente el inventario en iPos cuando se realiza una venta en WooCommerce.</p>
                    
                    <div id="webhook-status-container">
                        <button type="button" class="button button-secondary" id="refresh-webhook-status">
                            🔄 Actualizar Estado
                        </button>
                    </div>
                    
                    <div id="webhook-info" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #0073aa; border-radius: 4px;">
                        <h3 style="margin-top: 0;">Información del Webhook</h3>
                        <table class="webhook-details" style="width: 100%;">
                            <tr>
                                <td style="width: 200px; padding: 8px; font-weight: bold;">Estado:</td>
                                <td style="padding: 8px;"><span id="webhook-status-badge"></span></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; font-weight: bold;">ID del Webhook:</td>
                                <td style="padding: 8px;"><code id="webhook-id"></code></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; font-weight: bold;">URL de Entrega:</td>
                                <td style="padding: 8px;"><code id="webhook-url" style="word-break: break-all;"></code></td>
                            </tr>
                        </table>
                        
                        <div style="margin-top: 20px;">
                            <button type="button" class="button button-danger" id="delete-webhook" style="background-color: #dc3545; border-color: #dc3545; color: white;">
                                🗑️ Eliminar Webhook
                            </button>
                        </div>
                    </div>
                    
                    <div id="webhook-inactive" style="display: none; margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <p><strong>⚠️ Webhook no configurado</strong></p>
                        <p>Para activar la sincronización automática de ventas, necesitas crear un webhook.</p>
                        <button type="button" class="button button-primary" id="create-webhook">
                            Crear Webhook
                        </button>
                    </div>
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
     * Nuevo: helper para formatear fechas de forma consistente
     */
    private function format_datetime($timestamp) {
        if (empty($timestamp)) {
            return 'Nunca';
        }
        // Usa la localización configurada en WP
        return date_i18n('d/m/Y H:i:s', $timestamp);
    }

    /**
     * Renderizar estadísticas (enfocadas en categorías/productos/stock)
     */
    private function render_stats() {
        // Datos base
        $last_categories_sync = get_option('ocellaris_ipos_last_sync');
        $last_product_sync    = get_option('ocellaris_ipos_last_product_sync');
        $last_stock_sync      = get_option('ocellaris_ipos_last_stock_sync');

        // Categorías
        $wc_categories        = wp_count_terms('product_cat');
        $category_map         = get_option('ocellaris_ipos_category_map', array());
        $mapped_categories    = is_array($category_map) ? count($category_map) : 0;
        $mapped_cat_percent   = ($wc_categories > 0) ? round(($mapped_categories / $wc_categories) * 100, 1) : 0;

        // Productos
        $wc_products          = wp_count_posts('product');
        $wc_products_count    = isset($wc_products->publish) ? (int)$wc_products->publish : 0;
        $product_map          = get_option('ocellaris_ipos_product_map', array());
        $mapped_products      = is_array($product_map) ? count($product_map) : 0;
        $mapped_prod_percent  = ($wc_products_count > 0) ? round(($mapped_products / $wc_products_count) * 100, 1) : 0;

        // Métricas de stock y caché
        global $wpdb;
        $out_of_stock = (int)$wpdb->get_var("
            SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
              AND p.post_status = 'publish'
              AND pm.meta_key = '_stock_status'
              AND pm.meta_value = 'outofstock'
        ");

        $managed_stock_count = (int)$wpdb->get_var("
            SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('product')
              AND p.post_status IN ('publish','draft')
              AND pm.meta_key = '_manage_stock'
              AND pm.meta_value = 'yes'
        ");

        ?>
        <table class="widefat stats-table">
            <tbody>
                <tr>
                    <td><strong>Categorías (Woo):</strong></td>
                    <td><?php echo is_numeric($wc_categories) ? $wc_categories : 0; ?></td>
                    <td><strong>Mapeadas (iPos→Woo):</strong></td>
                    <td>
                        <?php echo $mapped_categories; ?>
                        <span class="stat-badge"><?php echo $mapped_cat_percent; ?>%</span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Productos (Woo publicados):</strong></td>
                    <td><?php echo $wc_products_count; ?></td>
                    <td><strong>Productos mapeados (iPos→Woo):</strong></td>
                    <td>
                        <?php echo $mapped_products; ?>
                        <span class="stat-badge"><?php echo $mapped_prod_percent; ?>%</span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Stock administrado:</strong></td>
                    <td><?php echo $managed_stock_count; ?></td>
                    <td><strong>Sin stock (publicados):</strong></td>
                    <td><?php echo $out_of_stock; ?></td>
                </tr>
                <tr>
                    <td><strong>Última sync (categorías):</strong></td>
                    <td><?php echo $this->format_datetime($last_categories_sync); ?></td>
                    <td><strong>Última sync (productos):</strong></td>
                    <td><?php echo $this->format_datetime($last_product_sync); ?></td>
                </tr>
                <tr>
                    <td><strong>Última sync (stock):</strong></td>
                    <td><?php echo $this->format_datetime($last_stock_sync); ?></td>
                    <td></td>
                    <td></td>
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
            wp_send_json_error('No tienes permisos para hacer esta acción.');
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


    /**
     * AJAX: Sincronizar stock
     */
    public function ajax_sync_stock(){
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        if(!current_user_can('manage_options')){
            wp_send_json_error('No tienes permisos para hacer esta acción.');
        }
        $offset = isset($_POST['offset'])? intval($_POST['offset']): 0;
        require_once get_stylesheet_directory().'/includes/class-stock-sync.php';
        $sync = new Ocellaris_Stock_Sync();
        $result = $sync->sync_all_stock($offset);

        // Nuevo: marcar última sincronización de stock cuando finaliza
        if (!empty($result['success']) && !empty($result['completed'])) {
            update_option('ocellaris_ipos_last_stock_sync', time());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Crear webhook
     */
    public function ajax_create_webhook() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para hacer esta acción.');
        }
        
        $result = Ocellaris_Webhook_Handler::create_webhook();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Eliminar webhook
     */
    public function ajax_delete_webhook() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para hacer esta acción.');
        }
        
        $result = Ocellaris_Webhook_Handler::delete_webhook();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Obtener estado del webhook
     */
    public function ajax_get_webhook_status() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para hacer esto.');
        }
        
        $status = Ocellaris_Webhook_Handler::get_webhook_status();
        wp_send_json_success($status);
    }

    /**
     * AJAX: Reactivar webhook
     */
    public function ajax_reactivate_webhook() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tienes permisos para hacer esta acción.');
        }
        
        $result = Ocellaris_Webhook_Handler::reactivate_webhook();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }    
    
    /**
     * AJAX: System Health (solo técnico)
     */
    public function ajax_get_system_health() {
        check_ajax_referer('ipos_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tenés permisos para hacer esto.');
        }

        $api_key = get_option($this->api_key_option);
        global $wpdb;

        // Severidad agregada
        $severity_rank = array('ok' => 1, 'warn' => 2, 'error' => 3);
        $overall = 'ok';
        $items   = array();
        $apply_overall = function($status) use (&$overall, $severity_rank) {
            if (($severity_rank[$status] ?? 2) > ($severity_rank[$overall] ?? 1)) {
                $overall = $status;
            }
        };

        // API Key
        $api_key_status = empty($api_key) ? 'error' : 'ok';
        $items[] = array(
            'key'    => 'api_key',
            'label'  => 'API Key',
            'status' => $api_key_status,
            'detail' => empty($api_key) ? 'Falta configurar' : 'Configurado'
        );
        $apply_overall($api_key_status);

        // Conexión API (ping categorías)
        $api_status = 'warn';
        $api_detail = 'N/A';
        if (!empty($api_key)) {
            $response = wp_remote_get(
                'https://ocellaris.ipos.services/api/v1/categories',
                array(
                    'headers' => array('Authorization' => 'Bearer ' . $api_key),
                    'timeout' => 10
                )
            );
            if (is_wp_error($response)) {
                $api_status = 'error';
                $api_detail = 'Error: ' . $response->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if ($code === 200) {
                    $api_status = 'ok';
                    $api_detail = '200 OK';
                } else {
                    $api_status = 'error';
                    $api_detail = 'HTTP ' . $code;
                }
            }
        } else {
            $api_status = 'error';
            $api_detail = 'Sin API Key';
        }
        $items[] = array(
            'key'    => 'api_connectivity',
            'label'  => 'Conexión API',
            'status' => $api_status,
            'detail' => $api_detail
        );
        $apply_overall($api_status);

        // Webhook estado
        $webhook_status_arr = class_exists('Ocellaris_Webhook_Handler') ? Ocellaris_Webhook_Handler::get_webhook_status() : array();
        $webhook_active = !empty($webhook_status_arr['active']);
        $webhook_id     = !empty($webhook_status_arr['webhook_id']) ? $webhook_status_arr['webhook_id'] : null;

        $webhook_status = $webhook_id ? ($webhook_active ? 'ok' : 'warn') : 'warn';
        $items[] = array(
            'key'    => 'webhook',
            'label'  => 'Webhook ventas',
            'status' => $webhook_status,
            'detail' => $webhook_id ? ($webhook_active ? 'Activo' : strtoupper($webhook_status_arr['status'] ?? 'Inactivo')) : 'No configurado',
            'id'     => $webhook_id
        );
        $apply_overall($webhook_status);

        // Caché de productos (transients)
        // $products_cache_transients = (int)$wpdb->get_var("
        //     SELECT COUNT(*)
        //     FROM {$wpdb->options}
        //     WHERE option_name LIKE '_transient_ocellaris_ipos_products_cache%'
        //        OR option_name LIKE '_transient_timeout_ocellaris_ipos_products_cache%'
        // ");
        // $cache_status = ($products_cache_transients > 500) ? 'error' : (($products_cache_transients > 0) ? 'warn' : 'ok');
        // $items[] = array(
        //     'key'    => 'cache',
        //     'label'  => 'Caché de productos',
        //     'status' => $cache_status,
        //     'detail' => $products_cache_transients . ' transients'
        // );
        // $apply_overall($cache_status);

        // Sesión de sincronización activa
        $session_id = get_transient('ocellaris_sync_session_id');
        $session_status = $session_id ? 'warn' : 'ok';
        $items[] = array(
            'key'    => 'session',
            'label'  => 'Sesión de sincronización',
            'status' => $session_status,
            'detail' => $session_id ? ('Activa · ID: ' . $session_id) : 'Ninguna'
        );
        $apply_overall($session_status);

        // Resumen
        $summary = array(
            'overall' => $overall,
            'message' => $overall === 'ok' ? '✅ Todo en orden'
                        : ($overall === 'warn' ? '⚠️ Hay puntos a revisar' : '❌ Acción requerida')
        );

        wp_send_json_success(array(
            'summary' => $summary,
            'items'   => $items
        ));
    }
}

// Inicializar
new Ocellaris_IPos_Admin();
?>