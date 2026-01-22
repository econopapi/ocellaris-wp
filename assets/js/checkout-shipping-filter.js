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
    const DEBUG_MODE = false;

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
        // Ejecutar filtro inmediatamente
        setTimeout(filterShippingOptions, 500);
        
        // Observer para detectar cuando el contenedor de envío cambia
        const targetNode = document.querySelector('.woocommerce-checkout');
        
        if (targetNode) {
            const observer = new MutationObserver(function(mutationsList) {
                let shouldFilter = false;
                
                for (const mutation of mutationsList) {
                    // Detectar cualquier cambio en el DOM que pueda incluir opciones de envío
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        // Verificar si el cambio está relacionado con envío
                        const target = mutation.target;
                        const targetParent = target.parentElement;
                        
                        if (target.id === 'shipping_method' ||
                            (target.classList && target.classList.contains('shipping_method')) ||
                            (target.querySelector && target.querySelector('#shipping_method')) ||
                            (targetParent && targetParent.id === 'shipping_method') ||
                            (target.closest && target.closest('#shipping_method')) ||
                            (target.closest && target.closest('.woocommerce-shipping-fields'))) {
                            shouldFilter = true;
                            break;
                        }
                        
                        // También detectar si se agregaron/removieron elementos li
                        Array.from(mutation.addedNodes).forEach(function(node) {
                            if (node.nodeType === Node.ELEMENT_NODE && 
                                (node.tagName === 'LI' || node.querySelector && node.querySelector('li'))) {
                                shouldFilter = true;
                            }
                        });
                    }
                    
                    // Detectar cambios de atributos que puedan indicar nuevas opciones
                    if (mutation.type === 'attributes' && 
                        (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                        if (mutation.target.closest && mutation.target.closest('#shipping_method')) {
                            shouldFilter = true;
                        }
                    }
                }

                if (shouldFilter) {
                    console.log('🔄 DOM change detected, filtering...');
                    setTimeout(filterShippingOptions, 100);
                }
            });
            
            observer.observe(targetNode, {
                childList: true,
                subtree: true,
                attributes: true,
                characterData: true
            });
        }

        // Escuchar TODOS los eventos de WooCommerce
        $(document.body).on('updated_checkout updated_shipping_method checkout_error', function(e) {
            console.log('🔄 WC Event:', e.type);
            setTimeout(filterShippingOptions, 150);
        });
        
        // Escuchar cambios en campos de dirección
        $(document).on('change', '#billing_country, #billing_state, #billing_city, #billing_postcode, #shipping_country, #shipping_state, #shipping_city, #shipping_postcode', function() {
            console.log('🔄 Address changed');
            // Delay más largo porque WooCommerce necesita tiempo para cargar opciones
            setTimeout(filterShippingOptions, 1000);
        });
        
        // Monitor más agresivo para detectar cuando aparecen opciones de envío
        const aggressiveMonitor = setInterval(function() {
            const $currentMethods = $('#shipping_method li');
            if ($currentMethods.length > 0) {
                // Solo ejecutar si encontramos opciones que no están filtradas correctamente
                const hasUnfilteredOptions = $currentMethods.filter(':visible').filter(function() {
                    const labelText = $(this).find('label').text().replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
                    return !isShippingOptionAllowed(labelText);
                }).length > 0;
                
                if (hasUnfilteredOptions) {
                    console.log('🔄 Unfiltered options detected, filtering...');
                    filterShippingOptions();
                }
            }
        }, 1000);
        
        // Limpiar monitor después de 2 minutos
        setTimeout(() => clearInterval(aggressiveMonitor), 120000);
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
