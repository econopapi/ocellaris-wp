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
        const normalizedLabel = labelText.trim();
        
        return allowedShippingOptions.some(function(allowedOption) {
            // Hacer match con el inicio del texto (antes del precio)
            return normalizedLabel.indexOf(allowedOption) !== -1;
        });
    }

    /**
     * Filtra las opciones de envío
     */
    function filterShippingOptions() {
        // Prevenir ejecución múltiple simultánea
        if (isProcessing) {
            return;
        }

        const $shippingMethods = $('#shipping_method li');
        
        if ($shippingMethods.length === 0) {
            return;
        }

        // Verificar si el checkout está listo
        if (!isCheckoutReady()) {
            return;
        }

        // Verificar si las opciones han cambiado
        const currentHash = getShippingOptionsHash();
        if (currentHash === lastProcessedHash) {
            return;
        }

        isProcessing = true;
        lastProcessedHash = currentHash;

        let firstVisibleOption = null;
        let hasCheckedVisible = false;
        let visibleOptions = 0;

        $shippingMethods.each(function() {
            const $li = $(this);
            const $label = $li.find('label');
            const $input = $li.find('input.shipping_method');
            
            if ($label.length === 0) {
                return;
            }

            // Obtener solo el texto del label (sin el HTML del precio)
            const labelText = $label.text();

            if (isShippingOptionAllowed(labelText)) {
                $li.show();
                visibleOptions++;
                
                // Guardar la primera opción visible
                if (!firstVisibleOption) {
                    firstVisibleOption = $input;
                }
                
                // Verificar si esta opción visible está seleccionada
                if ($input.is(':checked')) {
                    hasCheckedVisible = true;
                }
            } else {
                $li.hide();
                
                // Si la opción oculta estaba seleccionada, deseleccionarla
                if ($input.is(':checked')) {
                    $input.prop('checked', false);
                }
            }
        });

        // Solo auto-seleccionar si hay opciones visibles y ninguna está seleccionada
        // Y solo si no es la carga inicial del checkout
        if (!hasCheckedVisible && firstVisibleOption && visibleOptions > 0) {
            // Usar setTimeout para evitar conflictos con otros eventos
            setTimeout(function() {
                if (!$('#shipping_method input:checked').length) {
                    firstVisibleOption.prop('checked', true);
                    // No disparar 'change' inmediatamente para evitar loops
                }
                isProcessing = false;
            }, 50);
        } else {
            isProcessing = false;
        }
    }

    /**
     * Inicializa el observer para detectar cambios en las opciones de envío
     */
    function initShippingObserver() {
        // Observer para detectar cuando el contenedor de envío cambia
        const targetNode = document.querySelector('.woocommerce-checkout');
        
        if (!targetNode) {
            return;
        }

        const config = {
            childList: true,
            subtree: true,
            attributes: false,
            characterData: false
        };

        const callback = function(mutationsList) {
            // Evitar procesamiento durante bloqueo de UI
            if ($('.blockUI, .processing').length > 0) {
                return;
            }

            let shouldProcess = false;
            
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    // Verificar si se agregaron nodos relacionados con envío
                    const hasShippingChanges = Array.from(mutation.addedNodes).some(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return node.classList && 
                                   (node.classList.contains('shipping_method') ||
                                    node.id === 'shipping_method' ||
                                    node.querySelector && node.querySelector('#shipping_method'));
                        }
                        return false;
                    });

                    if (hasShippingChanges || mutation.target.id === 'shipping_method' || 
                        (mutation.target.closest && mutation.target.closest('#shipping_method'))) {
                        shouldProcess = true;
                        break;
                    }
                }
            }

            if (shouldProcess) {
                // Delay más largo para asegurar que el DOM y WooCommerce estén listos
                setTimeout(filterShippingOptions, 200);
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);

        // También escuchar el evento de WooCommerce cuando se actualiza el checkout
        let updateTimeout;
        $(document.body).on('updated_checkout', function() {
            // Limpiar timeout anterior para evitar múltiples ejecuciones
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(function() {
                // Solo procesar si no hay bloqueos de UI activos
                if ($('.blockUI, .processing').length === 0) {
                    filterShippingOptions();
                }
            }, 300);
        });

        // Escuchar cuando se completa el cálculo de envío
        $(document.body).on('updated_shipping_method', function() {
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(filterShippingOptions, 200);
        });

        // Filtrar opciones iniciales si ya existen y el checkout está listo
        setTimeout(function() {
            if (isCheckoutReady()) {
                filterShippingOptions();
            }
        }, 500);
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
