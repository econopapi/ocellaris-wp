/**
 * Ocellaris Checkout Shipping Filter
 * Filtra las opciones de envío para mostrar solo las permitidas.
 * 
 * Usa un polling agresivo (setInterval) para garantizar que los métodos
 * de envío se filtren incluso después de recargas AJAX del checkout
 * (e.g. cuando Envia.com recotiza envíos tras cambiar el código postal).
 * 
 * Desarrollado por Daniel Limón - <dani@dlimon.net>
 * 
 * @package Ocellaris Custom Astra
 */

(function($) {
    'use strict';

    // Lista de opciones de envío permitidas
    var allowedShippingOptions = [
        'Estafeta Terrestre ( 1-2 days )',
        'Estafeta Express ( Next day )',
        'DHL Economy Select Domestic ( 1-4 days )',
        'DHL Express Domestic ( Next day )',
        'FedEx Nacional Económico ( 2-4 days )',
        'FedEx Nacional Día Siguiente ( Next day )',
        'Recolección en tienda'
    ];

    function isShippingOptionAllowed(labelText) {
        var normalizedLabel = labelText.replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
        return allowedShippingOptions.indexOf(normalizedLabel) !== -1;
    }

    function filterShippingOptions() {
        var $shippingMethods = $('#shipping_method li');
        
        if ($shippingMethods.length === 0) {
            return;
        }

        $shippingMethods.each(function() {
            var $li = $(this);
            var $label = $li.find('label');
            
            if ($label.length === 0) return;

            var labelText = $label.text().replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();

            if (isShippingOptionAllowed(labelText)) {
                $li.show();
            } else {
                $li.hide();
            }
        });
    }

    $(document).ready(function() {
        filterShippingOptions();
        
        // Polling agresivo cada 2 segundos para capturar recargas AJAX
        setInterval(filterShippingOptions, 2000);

        // También reaccionar inmediatamente a eventos de WooCommerce
        $(document.body).on('updated_checkout', function() {
            setTimeout(filterShippingOptions, 300);
            setTimeout(filterShippingOptions, 800);
        });
    });

    // Backup: ejecutar después de 1 segundo por si jQuery no disparó ready
    setTimeout(filterShippingOptions, 1000);

})(jQuery);
