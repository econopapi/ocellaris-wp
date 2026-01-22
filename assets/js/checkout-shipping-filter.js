/**
 * Ocellaris Checkout Shipping Filter
 * Filtra las opciones de envío para mostrar solo las permitidas
 * 
 * Desarrollado por Daniel Limón - <dani@dlimon.net>
 */
(function($) {
    'use strict';

    // Variables de control para evitar loops infinitos
    let isProcessing = false;
    let lastProcessedHash = '';

    // Lista de opciones de envío permitidas (nombres parciales para hacer match)
    const allowedShippingOptions = [
        'Estafeta Terrestre ( 1-2 days )',
        'Estafeta Express ( Next day )',
        'DHL Economy Select Domestic ( 1-4 days )',
        'DHL Express Domestic ( Next day )',
        'FedEx Nacional Económico ( 2-4 days )',
        'FedEx Nacional Día Siguiente ( Next day )',
        'Recogida local'
    ];

    // Debug: activar para ver qué opciones se están procesando
    const DEBUG_MODE = true;

    /**
     * Verifica si el checkout está en un estado válido para procesar
     * @returns {boolean}
     */
    function isCheckoutReady() {
        // Verificar si hay campos de dirección requeridos vacíos
        const requiredFields = ['billing_country', 'billing_state', 'billing_city'];
        
        for (const field of requiredFields) {
            const $field = $(`#${field}`);
            if ($field.length > 0 && $field.val() === '') {
                return false;
            }
        }
        
        // Verificar si WooCommerce está calculando envío
        if ($('.blockUI, .processing').length > 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Genera un hash de las opciones de envío actuales
     * @returns {string}
     */
    function getShippingOptionsHash() {
        const options = [];
        $('#shipping_method li').each(function() {
            const $label = $(this).find('label');
            if ($label.length > 0) {
                options.push($label.text().trim());
            }
        });
        return options.join('|');
    }

    /**
     * Verifica si una opción de envío está permitida
     * @param {string} labelText - Texto del label de la opción
     * @returns {boolean}
     */
    function isShippingOptionAllowed(labelText) {
        // Normalizar el texto removiendo precios y espacios extra
        // Remover el precio (formato: $XXX.XX o $X,XXX.XX)
        const normalizedLabel = labelText.replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
        
        if (DEBUG_MODE) {
            console.log('🔍 Checking option:', {
                original: labelText,
                normalized: normalizedLabel,
                allowed: false
            });
        }
        
        const isAllowed = allowedShippingOptions.some(function(allowedOption) {
            // Hacer match exacto después de normalizar
            const matches = normalizedLabel === allowedOption;
            if (DEBUG_MODE && matches) {
                console.log('✅ Match found:', normalizedLabel, '===', allowedOption);
            }
            return matches;
        });
        
        if (DEBUG_MODE) {
            console.log(isAllowed ? '✅ ALLOWED:' : '❌ BLOCKED:', normalizedLabel);
        }
        
        return isAllowed;
    }

    /**
     * Filtra las opciones de envío
     */
    function filterShippingOptions() {
        const $shippingMethods = $('#shipping_method li');
        
        if ($shippingMethods.length === 0) {
            return;
        }

        console.log('🚀 Filtering shipping options...');

        let firstVisibleOption = null;
        let hasCheckedVisible = false;

        $shippingMethods.each(function() {
            const $li = $(this);
            const $label = $li.find('label');
            const $input = $li.find('input.shipping_method');
            
            if ($label.length === 0) {
                return;
            }

            // Obtener solo el texto del label
            const labelText = $label.text().replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
            
            console.log('Checking option:', labelText);

            if (isShippingOptionAllowed(labelText)) {
                console.log('✅ SHOWING:', labelText);
                $li.show();
                
                if (!firstVisibleOption) {
                    firstVisibleOption = $input;
                }
                
                if ($input.is(':checked')) {
                    hasCheckedVisible = true;
                }
            } else {
                console.log('❌ HIDING:', labelText);
                $li.hide();
                
                if ($input.is(':checked')) {
                    $input.prop('checked', false);
                }
            }
        });

        // Auto-seleccionar primera opción si ninguna está seleccionada
        if (!hasCheckedVisible && firstVisibleOption) {
            firstVisibleOption.prop('checked', true).trigger('change');
        }
    }

    /**
     * Inicializa el observer para detectar cambios en las opciones de envío
     */
    function initShippingObserver() {
        console.log('🚀 Initializing shipping observer...');
        
        // Filtrar opciones iniciales
        setTimeout(filterShippingOptions, 1000);
        
        // ENFOQUE SIMPLE: Solo usar el evento más confiable de WooCommerce
        $(document.body).on('updated_checkout', function() {
            console.log('📦 WooCommerce updated_checkout event fired!');
            // Delay generoso para asegurar que las opciones estén cargadas
            setTimeout(function() {
                console.log('⏰ Running filter after updated_checkout...');
                filterShippingOptions();
            }, 2000);
        });
        
        // Backup: Monitorear cada 3 segundos si hay opciones sin filtrar
        setInterval(function() {
            const $methods = $('#shipping_method li');
            if ($methods.length > 0) {
                console.log('🔍 Checking for unfiltered options...');
                filterShippingOptions();
            }
        }, 3000);
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        initShippingObserver();
    });

    // También inicializar cuando WooCommerce termine de cargar
    $(document.body).on('init_checkout', function() {
        initShippingObserver();
    });

})(jQuery);
