/**
 * Ocellaris All Brands - Frontend JavaScript
 * Handles alphabet filtering and animations
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        initializeAllBrandsBlocks();
    });

    function initializeAllBrandsBlocks() {
        $('.ocellaris-all-brands').each(function() {
            var $container = $(this);
            initializeBlock($container);
        });
    }

    function initializeBlock($container) {
        var $filterButtons = $container.find('.filter-button');
        var $brandItems = $container.find('.brand-item');
        var $brandsGrid = $container.find('.brands-grid');
        var $counter = $container.find('#brands-counter');
        var $brandsText = $container.find('#brands-text');
        var $loading = $container.find('#brands-loading');
        var $empty = $container.find('#brands-empty');

        // Bind filter button events
        $filterButtons.on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var filter = $button.data('filter');

            // Update active state
            $filterButtons.removeClass('active');
            $button.addClass('active');

            // Filter brands
            filterBrands(filter, $brandItems, $counter, $brandsText, $loading, $empty, $brandsGrid);
        });

        // Add smooth scroll to top when filter changes
        $filterButtons.on('click', function() {
            var offset = $container.offset().top - 100;
            $('html, body').animate({
                scrollTop: offset
            }, 600, 'easeInOutQuart');
        });

        // Initialize with all brands visible
        $brandItems.addClass('visible');
    }

    function filterBrands(filter, $brandItems, $counter, $brandsText, $loading, $empty, $brandsGrid) {
        // Show loading state
        $loading.show();
        $empty.hide();
        $brandsGrid.css('opacity', '0.5');

        // Simulate filtering delay for better UX
        setTimeout(function() {
            var $visibleItems;
            
            if (filter === 'all') {
                $visibleItems = $brandItems;
            } else {
                $visibleItems = $brandItems.filter(function() {
                    var itemLetter = $(this).data('letter');
                    if (filter === '#') {
                        return itemLetter === 'other';
                    }
                    return itemLetter === filter;
                });
            }

            // Hide all items first
            $brandItems.removeClass('visible').hide();

            // Show filtered items
            if ($visibleItems.length > 0) {
                $visibleItems.each(function(index) {
                    var $item = $(this);
                    setTimeout(function() {
                        $item.show().addClass('visible');
                    }, index * 50); // Stagger animation
                });

                // Update counter
                if ($counter.length) {
                    animateCounter($counter, $visibleItems.length);
                }

                // Update text
                if ($brandsText.length) {
                    $brandsText.text($visibleItems.length === 1 ? 'marca' : 'marcas');
                }

                // Hide empty message
                $empty.hide();
            } else {
                // Show empty state
                $empty.show();
                
                if ($counter.length) {
                    $counter.text('0');
                }
                if ($brandsText.length) {
                    $brandsText.text('marcas');
                }
            }

            // Hide loading and restore opacity
            $loading.hide();
            $brandsGrid.css('opacity', '1');

        }, 300);
    }

    function animateCounter($counter, targetValue) {
        var currentValue = parseInt($counter.text()) || 0;
        
        if (currentValue === targetValue) return;

        var increment = targetValue > currentValue ? 1 : -1;
        var duration = Math.abs(targetValue - currentValue) * 30;
        
        $counter.addClass('counting');
        
        var counter = setInterval(function() {
            currentValue += increment;
            $counter.text(currentValue);
            
            if (currentValue === targetValue) {
                clearInterval(counter);
                $counter.removeClass('counting');
            }
        }, duration / Math.abs(targetValue - currentValue));
    }

    // Add smooth easing function if not available
    if (!$.easing.easeInOutQuart) {
        $.easing.easeInOutQuart = function (x, t, b, c, d) {
            if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
            return -c/2 * ((t-=2)*t*t*t - 2) + b;
        };
    }

    // Advanced search functionality (future enhancement)
    function initializeSearchBox($container) {
        var $searchInput = $container.find('.brands-search-input');
        var $brandItems = $container.find('.brand-item');
        var searchTimeout;

        if (!$searchInput.length) return;

        $searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            var searchTerm = $(this).val().toLowerCase().trim();

            searchTimeout = setTimeout(function() {
                if (searchTerm === '') {
                    $brandItems.show().addClass('visible');
                } else {
                    $brandItems.each(function() {
                        var $item = $(this);
                        var brandName = $item.data('name');
                        
                        if (brandName.includes(searchTerm)) {
                            $item.show().addClass('visible');
                        } else {
                            $item.hide().removeClass('visible');
                        }
                    });
                }
            }, 300);
        });
    }

    // Handle window resize for responsive behavior
    var resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            $('.ocellaris-all-brands').each(function() {
                var $container = $(this);
                var $brandItems = $container.find('.brand-item.visible');
                
                // Trigger a refresh of the layout
                $brandItems.each(function(index) {
                    var $item = $(this);
                    $item.css('animation-delay', (index * 0.05) + 's');
                });
            });
        }, 250);
    });

    // Add keyboard navigation support
    $(document).on('keydown', '.filter-button', function(e) {
        var $button = $(this);
        var $allButtons = $button.parent().find('.filter-button');
        var currentIndex = $allButtons.index($button);
        var $target;

        switch(e.which) {
            case 37: // Left arrow
                e.preventDefault();
                $target = currentIndex > 0 ? $allButtons.eq(currentIndex - 1) : $allButtons.last();
                break;
            case 39: // Right arrow
                e.preventDefault();
                $target = currentIndex < $allButtons.length - 1 ? $allButtons.eq(currentIndex + 1) : $allButtons.first();
                break;
            case 13: // Enter
            case 32: // Space
                e.preventDefault();
                $button.click();
                return;
        }

        if ($target && $target.length) {
            $target.focus();
        }
    });

    // Performance optimization: Intersection Observer for animations
    if ('IntersectionObserver' in window) {
        var observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        var brandObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('in-viewport');
                }
            });
        }, observerOptions);

        // Observe brand items when they're added to the DOM
        $(document).on('DOMNodeInserted', '.brand-item', function() {
            brandObserver.observe(this);
        });

        // Initial observation
        $('.brand-item').each(function() {
            brandObserver.observe(this);
        });
    }

    // Export for external use
    window.OcellarisAllBrands = {
        initialize: initializeAllBrandsBlocks,
        filterBrands: filterBrands
    };

})(jQuery);