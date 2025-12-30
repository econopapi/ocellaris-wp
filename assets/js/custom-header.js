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
        const submenuClose = $('.submenu-sidebar-close');
        const submenuPanel = $('.ocellaris-submenu-panel');
        const submenuContent = $('.submenu-panel-content');

        // Open sidebar
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

        // Cerrar submenu sidebar
        function closeSubmenu() {
            submenuPanel.removeClass('active');
        }

        sidebarClose.on('click', closeSidebar);
        sidebarOverlay.on('click', closeSidebar);
        submenuClose.on('click', closeSubmenu);

        // Cerrar sidebar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.hasClass('active')) {
                closeSidebar();
            }
        });

        // Handle click on sidebar categories
        $('.sidebar-menu-list').on('click', '.menu-item > a', function(e) {
            const $link = $(this);
            const $menuItem = $link.parent();
            const catId = $link.data('cat-id');
            const href = $link.attr('href');
            const $subMenu = $menuItem.children('.sub-menu');
            const hasSubMenu = $subMenu.length > 0;

            // Si no es categoría ni tiene hijos, comportamiento normal
            if (!catId && !hasSubMenu) return;

            e.preventDefault();

            // Toggle activo
            if ($menuItem.hasClass('active')) {
                $menuItem.removeClass('active');
                $('.sidebar-menu-list .menu-item').removeClass('active');
                $('.ocellaris-submenu-panel').removeClass('active');
                return;
            }
            $('.sidebar-menu-list .menu-item').removeClass('active');
            $menuItem.addClass('active');

            const title = ($link.text() || '').trim();

            // Caso 1: el usuario creó hijos en Menús (curado manualmente)
            if (hasSubMenu) {
                const $children = $subMenu.children('li.menu-item');

                // Si no hay hijos reales, navegar a la categoría
                if (!$children.length) {
                    window.location.href = href;
                    return;
                }

                // Construir panel con hijos
                let html = '<h4>' + title + '</h4>';
                $children.each(function() {
                    const $child = $(this);
                    const $a = $child.children('a');
                    const childTitle = ($a.text() || '').trim();
                    const $grand = $child.children('.sub-menu');

                    if ($grand.length) {
                        html += '<div class="submenu-group"><h5>' + childTitle + '</h5><ul>';
                        $grand.children('li.menu-item').each(function() {
                            const $ga = $(this).children('a');
                            html += '<li><a href="' + $ga.attr('href') + '">' + ($ga.text() || '').trim() + '</a></li>';
                        });
                        html += '</ul></div>';
                    } else {
                        html += '<ul><li><a href="' + $a.attr('href') + '">' + childTitle + '</a></li></ul>';
                    }
                });

                $('.submenu-panel-content').html(html);
                $('.ocellaris-submenu-panel').addClass('active');
                return;
            }

            // Caso 2: sin hijos en Menús -> consultar por AJAX y abrir solo si hay subcategorías
            if (typeof OcellarisHeader === 'undefined') {
                // Fallback: navegar si no hay AJAX disponible
                window.location.href = href;
                return;
            }

            $.ajax({
                url: OcellarisHeader.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ocellaris_get_subcategories',
                    nonce: OcellarisHeader.nonce,
                    catId: catId
                }
            }).done(function(resp) {
                // En error o sin datos, navegar
                if (!resp || !resp.success || !resp.data) {
                    window.location.href = href;
                    return;
                }

                const data = resp.data;
                const groups = Array.isArray(data.groups) ? data.groups : [];
                const hasItems = groups.some(function(g) {
                    return Array.isArray(g.items) && g.items.length > 0;
                });

                // Si no hay subcategorías, navegar
                if (!hasItems) {
                    window.location.href = href;
                    return;
                }

                // Construir y abrir el panel
                let out = '<h4>' + (data.title || title) + '</h4>';
                groups.forEach(function(group) {
                    const hasGroupTitle = group.title && group.title.length;
                    out += '<div class="submenu-group">';
                    if (hasGroupTitle) out += '<h5>' + group.title + '</h5>';
                    out += '<ul>';
                    (group.items || []).forEach(function(item) {
                        out += '<li><a href="' + item.link + '">' + item.title + '</a></li>';
                    });
                    out += '</ul></div>';
                });

                $('.submenu-panel-content').html(out);
                $('.ocellaris-submenu-panel').addClass('active');
            }).fail(function() {
                // Fallback ante fallo de red
                window.location.href = href;
            });
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
    });
})(jQuery);