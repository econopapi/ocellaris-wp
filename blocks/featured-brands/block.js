(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var PanelBody = components.PanelBody;
  var CheckboxControl = components.CheckboxControl;
  var SelectControl = components.SelectControl;
  var useState = element.useState;
  var useEffect = element.useEffect;
  var useSelect = data.useSelect;
  var apiFetch = wp.apiFetch;

  registerBlockType('ocellaris/featured-brands', {
    title: 'Ocellaris Featured Brands',
    icon: 'star-filled',
    category: 'widgets',
    attributes: {
      selectedBrands: {
        type: 'array',
        default: []
      },
      title: {
        type: 'string',
        default: 'FEATURED BRANDS'
      },
      displayMode: {
        type: 'string',
        default: 'carousel'
      },
      autoplaySpeed: {
        type: 'number',
        default: 3000
      }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var selectedBrands = attributes.selectedBrands || [];
      
      // Estados locales
      var _useState = useState(''),
          searchTerm = _useState[0],
          setSearchTerm = _useState[1];
      
      var _useState2 = useState([]),
          brands = _useState2[0],
          setBrands = _useState2[1];
      
      var _useState3 = useState(false),
          isLoading = _useState3[0],
          setIsLoading = _useState3[1];

      // Cargar marcas con búsqueda y paginación
      useEffect(function() {
        setIsLoading(true);
        
        var searchParams = {
          per_page: 50,
          _fields: 'id,name'
        };
        
        if (searchTerm) {
          searchParams.search = searchTerm;
        }

        var queryString = Object.keys(searchParams)
          .map(function(key) { return key + '=' + encodeURIComponent(searchParams[key]); })
          .join('&');

        apiFetch({ 
          path: '/wp/v2/product_brand?' + queryString 
        })
          .then(function(response) {
            // Si hay búsqueda, solo mostrar resultados
            if (searchTerm) {
              setBrands(response);
            } else {
              // Si NO hay búsqueda, combinar resultados + marcas seleccionadas
              var brandIds = response.map(function(b) { return b.id; });
              var missingBrands = selectedBrands.filter(function(id) {
                return brandIds.indexOf(id) === -1;
              });

              // Si hay marcas seleccionadas faltantes, cargarlas
              if (missingBrands.length > 0) {
                apiFetch({
                  path: '/wp/v2/product_brand?include=' + missingBrands.join(',') + '&_fields=id,name'
                })
                  .then(function(selectedResponse) {
                    setBrands(response.concat(selectedResponse));
                    setIsLoading(false);
                  })
                  .catch(function() {
                    setBrands(response);
                    setIsLoading(false);
                  });
              } else {
                setBrands(response);
                setIsLoading(false);
              }
            }
            if (searchTerm) {
              setIsLoading(false);
            }
          })
          .catch(function(error) {
            console.error('Error loading brands:', error);
            setBrands([]);
            setIsLoading(false);
          });
      }, [searchTerm, selectedBrands]);

      function toggleBrand(brandId) {
        var newSelected = [...selectedBrands];
        var index = newSelected.indexOf(brandId);
        
        if (index > -1) {
          newSelected.splice(index, 1);
        } else {
          newSelected.push(brandId);
        }
        
        setAttributes({ selectedBrands: newSelected });
      }

      // Generar lista de marcas
      var brandsList;
      if (isLoading) {
        brandsList = el('p', {}, '⏳ Cargando marcas...');
      } else if (brands.length === 0) {
        brandsList = el('p', {}, searchTerm ? 'No se encontraron marcas con ese término' : 'No product brands found.');
      } else {
        brandsList = brands.map(function(brand) {
          return el(CheckboxControl, {
            label: brand.name + ' (ID: ' + brand.id + ')',
            checked: selectedBrands.indexOf(brand.id) > -1,
            onChange: function() {
              toggleBrand(brand.id);
            },
            key: brand.id
          });
        });
      }

      // Generar lista de marcas seleccionadas
      var selectedBrandsList = selectedBrands.length > 0 
        ? selectedBrands.map(function(brandId) {
            var brand = brands.find(function(b) { return b.id === brandId; });
            var brandName = brand ? brand.name : 'Marca ID: ' + brandId;
            
            return el(
              'div',
              {
                key: brandId,
                style: {
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  padding: '8px 12px',
                  background: '#e8f5e9',
                  border: '1px solid #4caf50',
                  borderRadius: '4px',
                  marginBottom: '8px',
                  fontSize: '14px'
                }
              },
              [
                el('span', { key: 'name', style: { fontWeight: '500', color: '#2e7d32' } }, brandName),
                el(
                  'button',
                  {
                    key: 'remove-btn',
                    onClick: function() {
                      toggleBrand(brandId);
                    },
                    style: {
                      background: '#ff6b6b',
                      color: 'white',
                      border: 'none',
                      borderRadius: '3px',
                      padding: '4px 10px',
                      cursor: 'pointer',
                      fontSize: '12px',
                      fontWeight: 'bold'
                    }
                  },
                  '✕ Quitar'
                )
              ]
            );
          })
        : el('p', { style: { color: '#999', fontSize: '13px', fontStyle: 'italic' } }, 'No hay marcas seleccionadas');

      return el(
        'div',
        { className: 'ocellaris-featured-brands-editor' },
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
                  el(SelectControl, {
                    label: 'Display Mode',
                    value: attributes.displayMode,
                    options: [
                      { label: 'Carousel', value: 'carousel' },
                      { label: 'Grid', value: 'grid' }
                    ],
                    onChange: function (value) {
                      setAttributes({ displayMode: value });
                    },
                    key: 'displayMode'
                  }),
                  attributes.displayMode === 'carousel' ? el(RangeControl, {
                    label: 'Autoplay Speed (ms)',
                    value: attributes.autoplaySpeed,
                    onChange: function (value) {
                      setAttributes({ autoplaySpeed: value });
                    },
                    min: 1000,
                    max: 10000,
                    step: 500,
                    key: 'autoplaySpeed'
                  }) : null
                ]
              ),
              el(
                PanelBody,
                {
                  title: 'Select Brands (' + selectedBrands.length + ' selected)',
                  initialOpen: true,
                  key: 'brands'
                },
                [
                  el('div', { 
                    key: 'selected-brands-section',
                    style: {
                      marginBottom: '20px',
                      padding: '12px',
                      background: '#f5f5f5',
                      borderRadius: '4px',
                      border: '1px solid #ddd'
                    }
                  }, [
                    el('p', { 
                      key: 'selected-title',
                      style: { 
                        margin: '0 0 12px 0',
                        fontSize: '13px',
                        fontWeight: 'bold',
                        color: '#333'
                      } 
                    }, 'Marcas Agregadas (' + selectedBrands.length + ')'),
                    selectedBrandsList
                  ]),
                  el('hr', { key: 'divider', style: { margin: '15px 0', border: 'none', borderTop: '1px solid #ddd' } }),
                  el(TextControl, {
                    label: 'Search brands',
                    placeholder: 'Type to search...',
                    value: searchTerm,
                    onChange: function(value) {
                      setSearchTerm(value);
                    },
                    key: 'search'
                  }),
                  el('div', { 
                    key: 'brands-list',
                    style: {
                      marginTop: '15px',
                      maxHeight: '300px',
                      overflowY: 'auto',
                      border: '1px solid #e0e0e0',
                      borderRadius: '4px',
                      padding: '10px'
                    }
                  }, brandsList)
                ]
              )
            ]
          ),
          el(
            'div',
            { className: 'editor-preview', key: 'preview' },
            [
              el('h3', { style: { textAlign: 'center', color: '#FF6347', marginBottom: '30px' } }, attributes.title),
              el(
                'div',
                { 
                  style: attributes.displayMode === 'grid' ? {
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
                    gap: '20px',
                    padding: '20px',
                    background: '#f9f9f9',
                    borderRadius: '8px'
                  } : { 
                    display: 'flex', 
                    gap: '30px', 
                    justifyContent: 'center', 
                    flexWrap: 'wrap',
                    alignItems: 'center',
                    padding: '20px',
                    background: '#f9f9f9',
                    borderRadius: '8px'
                  } 
                },
                selectedBrands.length === 0
                  ? el('p', { style: { textAlign: 'center', color: '#666' } }, 'Select brands from the sidebar →')
                  : selectedBrands.map(function (brandId) {
                      var brand = brands.find(function (b) { return b.id === brandId; });
                      if (!brand) return null;
                      
                      return el(
                        'div',
                        {
                          key: brandId,
                          style: {
                            width: '150px',
                            height: '100px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            background: '#fff',
                            border: '1px solid #e0e0e0',
                            borderRadius: '4px',
                            padding: '15px'
                          }
                        },
                        el('span', { 
                          style: { 
                            fontSize: '14px', 
                            fontWeight: '600',
                            color: '#333',
                            textAlign: 'center'
                          } 
                        }, brand.name)
                      );
                    })
              ),
              el('p', { 
                style: { 
                  textAlign: 'center', 
                  color: '#666', 
                  fontSize: '12px',
                  marginTop: '15px' 
                } 
              }, attributes.displayMode === 'carousel' ? '↔ Carousel with autoplay (Preview on frontend)' : '⊞ Grid layout (Preview on frontend)')
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