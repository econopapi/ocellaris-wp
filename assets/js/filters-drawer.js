(function() {
  var MOBILE_Q = '(max-width: 768px)';

  function initFiltersDrawer() {
    // Only on mobile
    if (!window.matchMedia(MOBILE_Q).matches) return;

    // Only if we have filters on the page
    var filtersExist = document.querySelector('.ocellaris-filter-categories') || document.querySelector('.ocellaris-filter-brand');
    if (!filtersExist) return;

    // Avoid adding multiple times
    if (document.querySelector('.ocellaris-filters-toggle')) return;

    // Prefer inserting next to the page title (e.g. SHOP) so it appears where you indicated
    var titleEl = document.querySelector('main h1, .woocommerce-products-header__title, .page-title, .entry-title, .archive-header h1');
    var actionsTarget = null;

    if (titleEl && titleEl.parentNode) {
      actionsTarget = titleEl.parentNode;
      // If the parent isn't already flex/grid, make it a simple horizontal container
      var comp = window.getComputedStyle(actionsTarget).display || '';
      if (!/flex|grid/.test(comp)) {
        actionsTarget.style.display = 'flex';
        actionsTarget.style.justifyContent = 'space-between';
        actionsTarget.style.gap = '12px';
      }
      // On mobile, align items to the top so the button lines up with the title
      actionsTarget.style.alignItems = 'flex-start';
    } else {
      actionsTarget = document.querySelector('.ocellaris-header-actions') || document.querySelector('.ocellaris-header-container') || document.body;
    }

      // Create toggle button with funnel SVG and explicit label
      var toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      toggleBtn.className = 'ocellaris-filters-toggle';
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.title = 'Abrir filtros';
      toggleBtn.innerHTML = '<span class="oc-filters-icon" aria-hidden="true">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
        '<path d="M3 5h18v2l-7 8v4l-4 2v-6L3 7V5z" fill="currentColor"/>' +
        '</svg>' +
        '</span><span class="oc-filters-text">Filtros</span>';

      // Basic button inline styles to ensure predictable size
      toggleBtn.style.display = 'inline-flex';
      toggleBtn.style.alignItems = 'center';
      toggleBtn.style.justifyContent = 'center';
      toggleBtn.style.height = '36px';
      toggleBtn.style.lineHeight = '1';
      toggleBtn.style.padding = '8px 12px';
      toggleBtn.style.boxSizing = 'border-box';

    // Insert the button after the title when possible, otherwise at the start of target
    if (titleEl && titleEl.parentNode === actionsTarget) {
      actionsTarget.insertBefore(toggleBtn, titleEl.nextSibling);
    } else {
      actionsTarget.insertBefore(toggleBtn, actionsTarget.firstChild);
    }


      // Function to align the toggle vertically with the title (align top)
      function alignToggle() {
        if (!titleEl || !toggleBtn) return;
        try {
          var titleRect = titleEl.getBoundingClientRect();
          var btnRect = toggleBtn.getBoundingClientRect();
          var diffTop = titleRect.top - btnRect.top;
          // small visual fine-tune offset (adjust if needed)
          var visualOffset = -2;
          toggleBtn.style.transform = 'translateY(' + Math.round(diffTop + visualOffset) + 'px)';
        } catch (err) {
          // ignore
        }
      }

      // Initial align and keep it updated on resize/load
      setTimeout(alignToggle, 60);
      window.addEventListener('resize', alignToggle);
      window.addEventListener('load', alignToggle);

    // Create drawer and backdrop
    var drawer = document.createElement('div');
    drawer.className = 'ocellaris-filters-drawer';
    drawer.setAttribute('aria-hidden', 'true');
    drawer.innerHTML = '<div class="ocellaris-filters-drawer-header"><button class="ocellaris-filters-close" aria-label="Cerrar filtros">×</button><h3>Filtros</h3></div><div class="ocellaris-filters-drawer-content" tabindex="-1"></div>';

    var backdrop = document.createElement('div');
    backdrop.className = 'ocellaris-filters-backdrop';

    document.body.appendChild(drawer);
    document.body.appendChild(backdrop);

    var moved = [];

    function openDrawer() {
      var content = drawer.querySelector('.ocellaris-filters-drawer-content');
      content.innerHTML = '';
      moved = [];

      var nodes = document.querySelectorAll('.ocellaris-filter-categories, .ocellaris-filter-brand');
      nodes.forEach(function(node) {
        // store original position
        moved.push({ node: node, parent: node.parentNode, next: node.nextSibling });
        content.appendChild(node);
      });

      document.documentElement.classList.add('ocellaris-filters-open');
      drawer.setAttribute('aria-hidden', 'false');
      toggleBtn.setAttribute('aria-expanded', 'true');
      // focus drawer for accessibility
      setTimeout(function(){ content.focus(); }, 50);
      // prevent background scroll
      document.documentElement.style.overflow = 'hidden';
    }

    function closeDrawer() {
      // Move nodes back to original parent
      moved.forEach(function(item) {
        try {
          if (!item.parent) return;
          if (item.next && item.next.parentNode === item.parent) {
            item.parent.insertBefore(item.node, item.next);
          } else {
            item.parent.appendChild(item.node);
          }
        } catch (err) {
          // ignore
        }
      });
      moved = [];

      document.documentElement.classList.remove('ocellaris-filters-open');
      drawer.setAttribute('aria-hidden', 'true');
      toggleBtn.setAttribute('aria-expanded', 'false');
      document.documentElement.style.overflow = '';
    }

    toggleBtn.addEventListener('click', function(e){
      if (document.documentElement.classList.contains('ocellaris-filters-open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    backdrop.addEventListener('click', closeDrawer);

    drawer.querySelector('.ocellaris-filters-close').addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && document.documentElement.classList.contains('ocellaris-filters-open')) {
        closeDrawer();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFiltersDrawer);
  } else {
    initFiltersDrawer();
  }

  // Re-init if the viewport crosses the mobile breakpoint
  try {
    var mm = window.matchMedia(MOBILE_Q);
    if (mm && mm.addEventListener) {
      mm.addEventListener('change', function(e){ if (e.matches) initFiltersDrawer(); });
    }
  } catch (err) {
    // ignore
  }

})();
