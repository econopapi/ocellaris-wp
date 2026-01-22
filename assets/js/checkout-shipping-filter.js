/**
 * Ocellaris Checkout Shipping Filter
 * Filtra las opciones de envío para mostrar solo las permitidas
 * 
 * Desarrollado por Daniel Limón - <dani@dlimon.net>
 */

console.log('🔥 SCRIPT LOADED!');

(function($) {
    'use strict';

    console.log('🔥 IIFE STARTED!');

    // Lista de opciones de envío permitidas
    const allowedShippingOptions = [
        'Estafeta Terrestre ( 1-2 days )',
        'Estafeta Express ( Next day )',
        'DHL Economy Select Domestic ( 1-4 days )',
        'DHL Express Domestic ( Next day )',
        'FedEx Nacional Económico ( 2-4 days )',
        'FedEx Nacional Día Siguiente ( Next day )',
        'Recogida local'
    ];

    function isShippingOptionAllowed(labelText) {
        const normalizedLabel = labelText.replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
        return allowedShippingOptions.includes(normalizedLabel);
    }

    function filterShippingOptions() {
        console.log('🚀 FILTER FUNCTION CALLED!');
        
        const $shippingMethods = $('#shipping_method li');
        console.log('📦 Found', $shippingMethods.length, 'shipping methods');
        
        if ($shippingMethods.length === 0) {
            console.log('❌ No shipping methods found');
            return;
        }

        $shippingMethods.each(function() {
            const $li = $(this);
            const $label = $li.find('label');
            
            if ($label.length === 0) return;

            const labelText = $label.text().replace(/:\s*\$[\d,]+\.\d{2}\s*$/, '').trim();
            console.log('🔍 Checking:', labelText);

            if (isShippingOptionAllowed(labelText)) {
                console.log('✅ SHOWING:', labelText);
                $li.show();
            } else {
                console.log('❌ HIDING:', labelText);
                $li.hide();
            }
        });
    }

    // EJECUTAR INMEDIATAMENTE CUANDO CARGUE
    console.log('⏰ Setting up immediate execution...');
    
    $(document).ready(function() {
        console.log('📄 DOM Ready!');
        filterShippingOptions();
        
        // Ejecutar cada 2 segundos
        setInterval(function() {
            console.log('⏰ Interval check...');
            filterShippingOptions();
        }, 2000);
    });

    // Backup si jQuery no está listo
    setTimeout(function() {
        console.log('⏰ Timeout execution...');
        filterShippingOptions();
    }, 1000);

})(jQuery);

console.log('🔥 SCRIPT FINISHED!');
