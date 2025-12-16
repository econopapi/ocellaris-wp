/**
 * Ocellaris Featured Brands Carousel
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    $('.ocellaris-featured-brands').each(function() {
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

      // Clone items for infinite loop
      $items.slice(0, itemsToShow).clone().addClass('cloned').appendTo($container);
      $items.slice(-itemsToShow).clone().addClass('cloned').prependTo($container);
      
      var $allItems = $container.find('.brand-item');
      var itemWidth = 100 / itemsToShow;
      
      // Set initial position
      $allItems.css('width', itemWidth + '%');
      currentIndex = itemsToShow;
      updateCarousel(false);

      // Responsive adjustments
      function updateItemsToShow() {
        var windowWidth = $(window).width();
        if (windowWidth < 480) {
          itemsToShow = 2;
        } else if (windowWidth < 768) {
          itemsToShow = 3;
        } else if (windowWidth < 1024) {
          itemsToShow = 4;
        } else {
          itemsToShow = 5;
        }
        itemWidth = 100 / itemsToShow;
        $allItems.css('width', itemWidth + '%');
        updateCarousel(false);
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