/**
 * Ocellaris Checkout Shipping Filter
 * Filtra las opciones de envío para mostrar solo las permitidas
 * 
 * Desarrollado por Daniel Limón - <dani@dlimon.net>
 */
(function($) {
    'use strict';

    // Lista de opciones de envío permitidas (nombres parciales para hacer match)
    const allowedShippingOptions = [
        'Estafeta Terrestre ( 1-2 days )',
        'Estafeta Express ( Next day )',
        'DHL Economy Select Domestic ( 1-4 days )',
        'DHL Express Domestic ( Next day )',
        'FedEx Nacional Económico ( 2-4 days )',
        'FedEx Nacional Día Siguiente ( Next day )'
    ];

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
        const $shippingMethods = $('#shipping_method li');
        
        if ($shippingMethods.length === 0) {
            return;
        }

        let firstVisibleOption = null;
        let hasCheckedVisible = false;

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

        // Si ninguna opción visible está seleccionada, seleccionar la primera visible
        if (!hasCheckedVisible && firstVisibleOption) {
            firstVisibleOption.prop('checked', true).trigger('change');
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
                        // Pequeño delay para asegurar que el DOM esté completamente actualizado
                        setTimeout(filterShippingOptions, 100);
                    }
                }
            }
        };

        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);

        // También escuchar el evento de WooCommerce cuando se actualiza el checkout
        $(document.body).on('updated_checkout', function() {
            setTimeout(filterShippingOptions, 100);
        });

        // Filtrar opciones iniciales si ya existen
        filterShippingOptions();
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
