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
    

        // open sidebar
        menuToggle.on('click', function() {
            sidebarMenu.addClass('active');
            sidebarOverlay.addClass('active');
            $('body').css('overflow', 'hidden');
        });


        // close sidebar
        function closeSidebar() {
            sidebarMenu.removeClass('active');
            sidebarOverlay.removeClass('active');
            $('body').css('overflow', '');
        }

        sidebarClose.on('click', closeSidebar);
        sidebarOverlay.on('click', closeSidebar);

        // close sidebar on ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.hasClass('active')) {
                closeSidebar();
            }
        });

        // submenu toggle for mobile
        $('.sidebar-menu-list .meun-item-has-children > a').on('click', function(e) {
            if ($(window).width() <= 790) {
                e.preventDefault();
                $(this).parent().toggleClass('submenu-open');
                $(this).next('.sub-menu').slideToggle(200);
            }
        })

        // search field enhancement
        const searchField = $('.search-field');
        searchField.on('focus', function() {
            $(this).parent().addClass('search-focused');
        });
        searchField.on('blur', function() {
            $(this).parent().removeClass('search-focused');
        });

        // prevent empty search
        $('.search-form').on('submit', function(e) {
            if (searchField.val().trim() === '') {
                e.preventDefault();
                searchField.focus();
            }
        });
    });
})(jQuery);