/**
 * Ocellaris Featured Products Carousel
 */

(function($) {
  'use strict';

  $(document).ready(function() {
    $('.featured-products-carousel-wrapper').each(function() {
      var $wrapper = $(this);
      var $track = $wrapper.find('.featured-products-carousel-track');
      var $items = $track.find('.featured-product-item');
      var $prev = $wrapper.find('.carousel-prev');
      var $next = $wrapper.find('.carousel-next');
      var $dots = $wrapper.find('.featured-products-carousel-dots');
      var requestedVisible = parseInt($wrapper.data('visible-items'), 10) || 4;

      if ($items.length <= 1) {
        $prev.prop('disabled', true);
        $next.prop('disabled', true);
        return;
      }

      var currentIndex = 0;
      var visibleItems = getVisibleItems();
      var maxIndex = Math.max(0, $items.length - visibleItems);
      var $viewport = $wrapper.find('.featured-products-carousel-viewport');

      var touchStartX = 0;
      var touchStartY = 0;
      var touchEndX = 0;
      var touchEndY = 0;
      var isSwiping = false;
      var swipeThreshold = 36;

      function getVisibleItems() {
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;

        if (viewportWidth < 480) {
          return 1;
        }

        if (viewportWidth < 768) {
          return Math.min(2, requestedVisible);
        }

        return Math.max(1, requestedVisible);
      }

      function buildDots() {
        $dots.empty();

        for (var i = 0; i <= maxIndex; i++) {
          var $dot = $('<button>', {
            type: 'button',
            class: 'featured-products-carousel-dot' + (i === currentIndex ? ' is-active' : ''),
            'aria-label': 'Ir a la posicion ' + (i + 1)
          });

          (function(index) {
            $dot.on('click', function() {
              currentIndex = index;
              update();
            });
          })(i);

          $dots.append($dot);
        }
      }

      function update() {
        var viewportWidth = $viewport[0] ? $viewport[0].getBoundingClientRect().width : $viewport.width();
        var itemWidth = viewportWidth / visibleItems;
        var offset = -(currentIndex * itemWidth);

        $track.css('--products-per-view', visibleItems);
        $items.css('width', itemWidth + 'px');
        $track.css('transform', 'translateX(' + offset + 'px)');

        if (maxIndex <= 0) {
          $prev.prop('disabled', true);
          $next.prop('disabled', true);
        } else {
          $prev.prop('disabled', false);
          $next.prop('disabled', false);
        }

        $dots.find('.featured-products-carousel-dot').removeClass('is-active').eq(currentIndex).addClass('is-active');
      }

      function recalculate() {
        visibleItems = getVisibleItems();
        maxIndex = Math.max(0, $items.length - visibleItems);

        if (currentIndex > maxIndex) {
          currentIndex = maxIndex;
        }

        buildDots();
        update();
      }

      $next.on('click', function() {
        if (maxIndex <= 0) {
          return;
        }

        currentIndex = (currentIndex + 1) % (maxIndex + 1);
        update();
      });

      $prev.on('click', function() {
        if (maxIndex <= 0) {
          return;
        }

        currentIndex = currentIndex <= 0 ? maxIndex : currentIndex - 1;
        update();
      });

      function handleSwipeGesture() {
        if (maxIndex <= 0) {
          return;
        }

        var deltaX = touchEndX - touchStartX;
        var deltaY = touchEndY - touchStartY;

        // Solo actuar cuando el gesto es predominantemente horizontal.
        if (Math.abs(deltaX) < swipeThreshold || Math.abs(deltaX) <= Math.abs(deltaY)) {
          return;
        }

        if (deltaX < 0) {
          currentIndex = (currentIndex + 1) % (maxIndex + 1);
          update();
        } else {
          currentIndex = currentIndex <= 0 ? maxIndex : currentIndex - 1;
          update();
        }
      }

      $viewport.on('touchstart', function(event) {
        var touch = event.originalEvent.touches && event.originalEvent.touches[0];
        if (!touch) {
          return;
        }

        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        touchEndX = touch.clientX;
        touchEndY = touch.clientY;
        isSwiping = true;
      });

      $viewport.on('touchmove', function(event) {
        if (!isSwiping) {
          return;
        }

        var touch = event.originalEvent.touches && event.originalEvent.touches[0];
        if (!touch) {
          return;
        }

        touchEndX = touch.clientX;
        touchEndY = touch.clientY;
      });

      $viewport.on('touchend touchcancel', function() {
        if (!isSwiping) {
          return;
        }

        isSwiping = false;
        handleSwipeGesture();
      });

      var resizeTimer;
      $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(recalculate, 150);
      });

      $(window).on('load', recalculate);

      recalculate();
    });
  });
})(jQuery);
