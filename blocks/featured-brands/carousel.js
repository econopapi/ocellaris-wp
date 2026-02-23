/**
 * Ocellaris Featured Brands Carousel
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    $('.ocellaris-featured-brands.mode-carousel, .ocellaris-featured-brands:not([class*="mode-"])').each(function() {
      var $carousel = $(this);
      var $container = $carousel.find('.brands-carousel');
      var $items = $container.find('.brand-item');
      var $prevBtn = $carousel.find('.carousel-prev');
      var $nextBtn = $carousel.find('.carousel-next');
      
      var autoplaySpeed = parseInt($carousel.data('autoplay-speed')) || 3000;
      var currentIndex = 0;
      var itemsToShow = 5; // Number of brands visible at once
      var totalItems = $items.length;
      var isAnimating = false;
      var autoplayInterval;
      var itemWidth = 100 / itemsToShow;

      function getItemsToShow() {
        var windowWidth = $(window).width();
        if (windowWidth < 480) {
          return 2;
        }
        if (windowWidth < 768) {
          return 3;
        }
        if (windowWidth < 1024) {
          return 4;
        }
        return 5;
      }

      function rebuildCarousel() {
        $container.find('.brand-item.cloned').remove();

        if (totalItems > 0) {
          var cloneCount = Math.min(itemsToShow, totalItems);
          $items.slice(0, cloneCount).clone().addClass('cloned').appendTo($container);
          $items.slice(-cloneCount).clone().addClass('cloned').prependTo($container);
        }

        var $allItems = $container.find('.brand-item');
        itemWidth = 100 / itemsToShow;
        $allItems.css('width', itemWidth + '%');

        currentIndex = totalItems > 0 ? itemsToShow : 0;
        updateCarousel(false);
      }

      // Set responsive layout and build initial loop structure
      itemsToShow = getItemsToShow();
      rebuildCarousel();

      // Responsive adjustments
      function updateItemsToShow() {
        var nextItemsToShow = getItemsToShow();

        if (nextItemsToShow !== itemsToShow) {
          itemsToShow = nextItemsToShow;
          rebuildCarousel();
        } else {
          itemWidth = 100 / itemsToShow;
          $container.find('.brand-item').css('width', itemWidth + '%');
          updateCarousel(false);
        }
      }

      function updateCarousel(animate) {
        var offset = -currentIndex * itemWidth;
        
        if (animate) {
          $container.css({
            'transition': 'transform 0.5s ease-in-out',
            'transform': 'translateX(' + offset + '%)'
          });
        } else {
          $container.css({
            'transition': 'none',
            'transform': 'translateX(' + offset + '%)'
          });
        }
      }

      function nextSlide() {
        if (isAnimating) return;
        isAnimating = true;
        
        currentIndex++;
        updateCarousel(true);
        
        setTimeout(function() {
          if (currentIndex >= totalItems + itemsToShow) {
            currentIndex = itemsToShow;
            updateCarousel(false);
          }
          isAnimating = false;
        }, 500);
      }

      function prevSlide() {
        if (isAnimating) return;
        isAnimating = true;
        
        currentIndex--;
        updateCarousel(true);
        
        setTimeout(function() {
          if (currentIndex < itemsToShow) {
            currentIndex = totalItems + itemsToShow - 1;
            updateCarousel(false);
          }
          isAnimating = false;
        }, 500);
      }

      function startAutoplay() {
        autoplayInterval = setInterval(nextSlide, autoplaySpeed);
      }

      function stopAutoplay() {
        clearInterval(autoplayInterval);
      }

      // Navigation buttons
      $nextBtn.on('click', function() {
        stopAutoplay();
        nextSlide();
        startAutoplay();
      });

      $prevBtn.on('click', function() {
        stopAutoplay();
        prevSlide();
        startAutoplay();
      });

      // Pause on hover
      $carousel.on('mouseenter', stopAutoplay);
      $carousel.on('mouseleave', startAutoplay);

      // Handle window resize
      var resizeTimer;
      $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          stopAutoplay();
          updateItemsToShow();
          startAutoplay();
        }, 250);
      });

      // Start autoplay
      startAutoplay();
    });
  });

})(jQuery);