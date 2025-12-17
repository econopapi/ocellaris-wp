/**
 * Ocellaris Custom Header JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const menuToggle = $('.ocellaris-menu-toggle');
        const sidebarMenu = $('.ocellaris-sidebar-menu');
        const sidebarOverlay = $('.ocellaris-sidebar-overlay');
        const sidebarClose = $('.sidebar-close');
        const submenuPanel = $('.ocellaris-submenu-panel');
        const submenuContent = $('.submenu-panel-content');

        // Datos de submenús (acá podés agregar más categorías)
        const submenuData = {
            'aquariums': {
                title: 'Aquariums & Stands',
                items: [
                    { title: 'All Aquariums', link: '#' },
                    { title: 'Rimless Aquariums', link: '#' },
                    { title: 'Peninsula Aquariums', link: '#' },
                    { title: 'Cube Aquariums', link: '#' },
                    { title: 'Standard Aquariums', link: '#' },
                    { title: 'Frag Tanks', link: '#' },
                    { title: 'Stands & Canopies', link: '#' }
                ]
            },
            'lighting': {
                title: 'Lighting',
                groups: [
                    {
                        title: 'LED',
                        items: [
                            { title: 'LED Fixtures', link: '#' },
                            { title: 'LED Strips', link: '#' },
                            { title: 'Mounting Arms', link: '#' }
                        ]
                    },
                    {
                        title: 'Traditional',
                        items: [
                            { title: 'Hybrid', link: '#' },
                            { title: 'T5 Fluorescent', link: '#' },
                            { title: 'Ballasts', link: '#' },
                            { title: 'Bulbs', link: '#' },
                            { title: 'Fixtures', link: '#' }
                        ]
                    },
                    {
                        title: 'Other',
                        items: [
                            { title: 'Refugium Lighting', link: '#' },
                            { title: 'Par Meters', link: '#' }
                        ]
                    }
                ]
            },
            'pumps': {
                title: 'Pumps & Powerheads',
                items: [
                    { title: 'All Pumps', link: '#' },
                    { title: 'Return Pumps', link: '#' },
                    { title: 'Feed Pumps', link: '#' },
                    { title: 'Wavemakers', link: '#' },
                    { title: 'Dosing Pumps', link: '#' }
                ]
            },
            'plumbing': {
                title: 'Plumbing',
                items: [
                    { title: 'Pipes & Fittings', link: '#' },
                    { title: 'Bulkheads', link: '#' },
                    { title: 'Valves', link: '#' },
                    { title: 'Unions', link: '#' },
                    { title: 'Check Valves', link: '#' }
                ]
            },
            'controllers': {
                title: 'Controllers & Testing',
                items: [
                    { title: 'Aquarium Controllers', link: '#' },
                    { title: 'Test Kits', link: '#' },
                    { title: 'Probes & Sensors', link: '#' },
                    { title: 'Monitors', link: '#' }
                ]
            },
            'additives': {
                title: 'Additives',
                items: [
                    { title: 'All Additives', link: '#' },
                    { title: 'Calcium', link: '#' },
                    { title: 'Alkalinity', link: '#' },
                    { title: 'Magnesium', link: '#' },
                    { title: 'Trace Elements', link: '#' }
                ]
            },
            'reverse-osmosis': {
                title: 'Reverse Osmosis',
                items: [
                    { title: 'RO/DI Systems', link: '#' },
                    { title: 'Replacement Filters', link: '#' },
                    { title: 'Accessories', link: '#' }
                ]
            },
            'salt': {
                title: 'Salt & Maintenance',
                items: [
                    { title: 'Salt Mix', link: '#' },
                    { title: 'Water Changes', link: '#' },
                    { title: 'Cleaners', link: '#' },
                    { title: 'Maintenance Tools', link: '#' }
                ]
            }
        };

        // Abrir sidebar
        menuToggle.on('click', function() {
            sidebarMenu.addClass('active');
            sidebarOverlay.addClass('active');
            $('body').css('overflow', 'hidden');
        });

        // Cerrar sidebar
        function closeSidebar() {
            sidebarMenu.removeClass('active');
            sidebarOverlay.removeClass('active');
            submenuPanel.removeClass('active');
            $('.sidebar-menu-list .menu-item').removeClass('active');
            $('body').css('overflow', '');
            
            // Pequeño delay para que la animación se vea mejor
            setTimeout(function() {
                submenuContent.html('');
            }, 300);
        }

        sidebarClose.on('click', closeSidebar);
        sidebarOverlay.on('click', closeSidebar);

        // Cerrar sidebar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.hasClass('active')) {
                closeSidebar();
            }
        });

        // Manejar click en categorías con submenú
        $('.sidebar-menu-list .menu-item-has-children > a').on('click', function(e) {
            e.preventDefault();
            
            const $menuItem = $(this).parent();
            const category = $(this).data('category');
            const data = submenuData[category];

            // Si clickean la misma categoría, cerrar el panel
            if ($menuItem.hasClass('active')) {
                $menuItem.removeClass('active');
                submenuPanel.removeClass('active');
                return;
            }

            // Activar categoría
            $('.sidebar-menu-list .menu-item').removeClass('active');
            $menuItem.addClass('active');

            // Generar contenido del submenú
            if (data) {
                let html = '<h4>' + data.title + '</h4>';
                
                if (data.groups) {
                    // Si tiene grupos (como Lighting)
                    data.groups.forEach(group => {
                        html += '<div class="submenu-group">';
                        html += '<h5>' + group.title + '</h5>';
                        html += '<ul>';
                        group.items.forEach(item => {
                            html += '<li><a href="' + item.link + '">' + item.title + '</a></li>';
                        });
                        html += '</ul>';
                        html += '</div>';
                    });
                } else {
                    // Si es lista simple
                    html += '<ul>';
                    data.items.forEach(item => {
                        html += '<li><a href="' + item.link + '">' + item.title + '</a></li>';
                    });
                    html += '</ul>';
                }

                submenuContent.html(html);
                submenuPanel.addClass('active');
            }
        });

        // Cerrar panel de submenú si clickean afuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.sidebar-menu-list, .ocellaris-submenu-panel').length) {
                if (submenuPanel.hasClass('active')) {
                    submenuPanel.removeClass('active');
                    $('.sidebar-menu-list .menu-item').removeClass('active');
                }
            }
        });

        // Search field enhancement
        const searchField = $('.search-field');
        searchField.on('focus', function() {
            $(this).parent().addClass('search-focused');
        });
        searchField.on('blur', function() {
            $(this).parent().removeClass('search-focused');
        });

        // Prevenir búsqueda vacía
        $('.search-form').on('submit', function(e) {
            if (searchField.val().trim() === '') {
                e.preventDefault();
                searchField.focus();
            }
        });

        // Animación de scroll del header
        let lastScroll = 0;
        $(window).on('scroll', function() {
            const currentScroll = $(this).scrollTop();
            
            if (currentScroll > lastScroll && currentScroll > 100) {
                $('.ocellaris-header').css('transform', 'translateY(-100%)');
            } else {
                $('.ocellaris-header').css('transform', 'translateY(0)');
            }
            
            lastScroll = currentScroll;
        });
    });
})(jQuery);