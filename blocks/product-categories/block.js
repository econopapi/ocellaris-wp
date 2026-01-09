(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var PanelBody = components.PanelBody;
  var CheckboxControl = components.CheckboxControl;
  var useSelect = data.useSelect;
  var useState = element.useState;
  var useEffect = element.useEffect;
  var apiFetch = wp.apiFetch;

  registerBlockType('ocellaris/product-categories', {
    title: 'Ocellaris Product Categories',
    icon: 'grid-view',
    category: 'widgets',
    attributes: {
      selectedCategories: {
        type: 'array',
        default: []
      },
      title: {
        type: 'string',
        default: 'TOP CATEGORIES'
      },
      subtitle: {
        type: 'string',
        default: ''
      }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var selectedCategories = attributes.selectedCategories || [];

      // Local state for categories, search and loading
      var _useState = useState(''),
          searchTerm = _useState[0],
          setSearchTerm = _useState[1];

      var _useState2 = useState([]),
          categories = _useState2[0],
          setCategories = _useState2[1];

      var _useState3 = useState(false),
          isLoading = _useState3[0],
          setIsLoading = _useState3[1];

      // Load categories with search and pagination (like featured-brands block)
      useEffect(function() {
        setIsLoading(true);

        var searchParams = {
          per_page: 50,
          _fields: 'id,name',
          parent: 0
        };

        if (searchTerm) {
          searchParams.search = searchTerm;
        }

        var queryString = Object.keys(searchParams).map(function(key) {
          return key + '=' + encodeURIComponent(searchParams[key]);
        }).join('&');

        apiFetch({ path: '/wp/v2/product_cat?' + queryString }).then(function(response) {
          if (searchTerm) {
            setCategories(response);
            setIsLoading(false);
          } else {
            var catIds = response.map(function(c) { return c.id; });
            var missing = selectedCategories.filter(function(id) { return catIds.indexOf(id) === -1; });

            if (missing.length > 0) {
              apiFetch({ path: '/wp/v2/product_cat?include=' + missing.join(',') + '&_fields=id,name' }).then(function(selResp) {
                setCategories(response.concat(selResp));
                setIsLoading(false);
              }).catch(function() {
                setCategories(response);
                setIsLoading(false);
              });
            } else {
              setCategories(response);
              setIsLoading(false);
            }
          }
        }).catch(function(error) {
          console.error('Error loading categories:', error);
          setCategories([]);
          setIsLoading(false);
        });
      }, [searchTerm, selectedCategories]);

      function toggleCategory(catId) {
        var newSelected = [...selectedCategories];
        var index = newSelected.indexOf(catId);
        
        if (index > -1) {
          newSelected.splice(index, 1);
        } else {
          newSelected.push(catId);
        }
        
        setAttributes({ selectedCategories: newSelected });
      }

      function getCategoryImage(category) {
        // WooCommerce stores category images in meta
        if (category.meta && category.meta.thumbnail_id) {
          return category.meta.thumbnail_id;
        }
        return null;
      }

      return el(
        'div',
        { className: 'ocellaris-product-categories-editor' },
        [
          el(
            InspectorControls,
            { key: 'inspector' },
            [
              el(
                PanelBody,
                {
                  title: 'Settings',
                  initialOpen: true,
                  key: 'settings'
                },
                [
                  el(TextControl, {
                    label: 'Title',
                    value: attributes.title,
                    onChange: function (value) {
                      setAttributes({ title: value });
                    },
                    key: 'title'
                  }),
                  el(TextControl, {
                    label: 'Subtitle',
                    value: attributes.subtitle,
                    onChange: function (value) {
                      setAttributes({ subtitle: value });
                    },
                    key: 'subtitle'
                  })
                ]
              ),
              el(
                PanelBody,
                {
                  title: 'Select Categories (' + selectedCategories.length + ' selected)',
                  initialOpen: true,
                  key: 'categories'
                },
                [
                  el('div', {
                    key: 'selected-cats-section',
                    style: {
                      marginBottom: '20px',
                      padding: '12px',
                      background: '#f5f5f5',
                      borderRadius: '4px',
                      border: '1px solid #ddd'
                    }
                  }, [
                    el('p', { key: 'selected-title', style: { margin: '0 0 12px 0', fontSize: '13px', fontWeight: 'bold', color: '#333' } }, 'Categorias Agregadas (' + selectedCategories.length + ')'),
                    selectedCategories.length === 0 ? el('p', { style: { color: '#999', fontSize: '13px', fontStyle: 'italic' } }, 'No hay categorias seleccionadas') : selectedCategories.map(function(catId) {
                      var cat = categories.find(function(c) { return c.id === catId; });
                      var name = cat ? cat.name : 'Categoria ID: ' + catId;
                      return el('div', { key: catId, style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '8px 12px', background: '#e8f5e9', border: '1px solid #4caf50', borderRadius: '4px', marginBottom: '8px', fontSize: '14px' } }, [
                        el('span', { key: 'name', style: { fontWeight: '500', color: '#2e7d32' } }, name),
                        el('button', { key: 'remove-btn', onClick: function() { toggleCategory(catId); }, style: { background: '#ff6b6b', color: 'white', border: 'none', borderRadius: '3px', padding: '4px 10px', cursor: 'pointer', fontSize: '12px', fontWeight: 'bold' } }, '✕ Quitar')
                      ]);
                    })
                  ]),
                  el('hr', { key: 'divider', style: { margin: '15px 0', border: 'none', borderTop: '1px solid #ddd' } }),
                  el(TextControl, {
                    label: 'Search categories',
                    placeholder: 'Type to search...',
                    value: searchTerm,
                    onChange: function(value) { setSearchTerm(value); },
                    key: 'search'
                  }),
                  el('div', { key: 'cats-list', style: { marginTop: '15px', maxHeight: '300px', overflowY: 'auto', border: '1px solid #e0e0e0', borderRadius: '4px', padding: '10px' } }, isLoading ? el('p', {}, '⏳ Cargando categorias...') : (categories.length === 0 ? el('p', {}, searchTerm ? 'No se encontraron categorias con ese termino' : 'No product categories found.') : categories.map(function(category) {
                    return el(CheckboxControl, {
                      label: category.name + ' (ID: ' + category.id + ')',
                      checked: selectedCategories.indexOf(category.id) > -1,
                      onChange: function() { toggleCategory(category.id); },
                      key: category.id
                    });
                  })))
                ]
              )
            ]
          ),
          el(
            'div',
            { className: 'editor-preview', key: 'preview' },
            [
              el('h3', { style: { textAlign: 'center', color: '#FF6347' } }, attributes.title),
              el(
                'div',
                { style: { display: 'flex', gap: '20px', justifyContent: 'center', flexWrap: 'wrap', marginTop: '30px' } },
                selectedCategories.length === 0
                  ? el('p', { style: { textAlign: 'center', color: '#666' } }, 'Select categories from the sidebar →')
                  : selectedCategories.map(function (catId) {
                      var category = categories && categories.find(function (c) { return c.id === catId; });
                      if (!category) return null;
                      
                      return el(
                        'div',
                        {
                          key: catId,
                          style: {
                            textAlign: 'center',
                            width: '150px'
                          }
                        },
                        [
                          el(
                            'div',
                            {
                              style: {
                                width: '120px',
                                height: '120px',
                                borderRadius: '50%',
                                background: '#f0f0f0',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                margin: '0 auto 10px',
                                border: '3px solid #e0e0e0'
                              }
                            },
                            el('span', { style: { fontSize: '12px', color: '#999' } }, '📦')
                          ),
                          el('p', { style: { fontSize: '14px', fontWeight: '600', margin: 0 } }, category.name)
                        ]
                      );
                    })
              ),
              attributes.subtitle && el('h3', { style: { textAlign: 'center', color: '#FF6347', marginTop: '30px' } }, attributes.subtitle)
            ]
          )
        ]
      );
    },

    save: function () {
      return null; // Rendered via PHP
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.data
);