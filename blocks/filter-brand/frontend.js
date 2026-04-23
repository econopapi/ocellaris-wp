(function() {
  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList && e.target.classList.contains('oc-filter-brand-select')) {
      var v = e.target.value;
      var url = new URL(window.location.href);
      if (v && v !== '') url.searchParams.set('filter_brand', v); else url.searchParams.delete('filter_brand');
      url.searchParams.delete('paged');
      window.location.href = url.toString();
    }
  });
})();
