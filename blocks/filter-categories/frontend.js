(function() {
  function updateFilter() {
    var checkboxes = document.querySelectorAll('.oc-filter-cat-checkbox');
    if (!checkboxes) return;
    var slugs = [];
    checkboxes.forEach(function(cb) { if(cb.checked) slugs.push(cb.value); });
    var value = slugs.join(',');
    var url = new URL(window.location.href);
    if (value) url.searchParams.set('filter_cat', value); else url.searchParams.delete('filter_cat');
    url.searchParams.delete('paged');
    window.location.href = url.toString();
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('oc-filter-cat-checkbox')) {
      updateFilter();
    }
  });
})();
