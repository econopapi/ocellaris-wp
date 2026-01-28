(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var PanelBody = components.PanelBody;
  var CheckboxControl = components.CheckboxControl;
  var SelectControl = components.SelectControl;
  var ToggleControl = components.ToggleControl;
  var Button = components.Button;
  var useState = element.useState;
  var useEffect = element.useEffect;
  var useSelect = data.useSelect;
  var apiFetch = wp.apiFetch;

  registerBlockType('ocellaris/featured-products', {
    title: 'Ocellaris Featured Products',
    icon: 'products',
    category: 'widgets',
    keywords: ['products', 'featured', 'woocommerce', 'ocellaris'],
    attributes: {
      title: {
        type: 'string',
        default: 'FEATURED PRODUCTS'
      },
      productsToShow: {
        type: 'number',
        default: 4
      },
      filterType: {
        type: 'string',
        default: 'manual' // manual, tags, sale, featured
      },
      selectedProducts: {
        type: 'array',
        default: []
      },
      selectedTags: {
        type: 'array',
        default: []
      },
      showOnSale: {
        type: 'boolean',
        default: false
      },
      showFeatured: {
        type: 'boolean',
        default: false
      },
      randomizeProducts: {
        type: 'boolean',
        default: false
      }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      
      // Estados locales
      var _useState = useState([]),
          products = _useState[0],
          setProducts = _useState[1];
      
      var _useState2 = useState([]),
          tags = _useState2[0],
          setTags = _useState2[1];
      
      var _useState3 = useState(false),
          isLoading = _useState3[0],
          setIsLoading = _useState3[1];

      var _useState4 = useState(''),
          searchTerm = _useState4[0],
          setSearchTerm = _useState4[1];

      // Cargar productos al montar el componente
      useEffect(function () {
        loadProducts();
        loadTags();
      }, [attributes.filterType, attributes.selectedTags, attributes.showOnSale, attributes.showFeatured]);

      function loadProducts() {
        setIsLoading(true);
        var allProducts = [];
        var page = 1;
        var perPage = 100;
        var params = {
          per_page: perPage,
          status: 'publish'
        };

        // Aplicar filtros según el tipo seleccionado
        if (attributes.filterType === 'sale') {
          params.on_sale = true;
        } else if (attributes.filterType === 'featured') {
          params.featured = true;
        } else if (attributes.filterType === 'tags' && attributes.selectedTags.length > 0) {
          params.tag = attributes.selectedTags.join(',');
        }

        function fetchPage(pageNum) {
          // Clonar params para evitar mutaciones entre llamadas
          var pageParams = Object.assign({}, params, { page: pageNum });
          return apiFetch({
            path: wp.url.addQueryArgs('/wc/v3/products', pageParams)
          });
        }

        function fetchAllProducts() {
          fetchPage(page).then(function (data) {
            allProducts = allProducts.concat(data);
            if (data.length === perPage) {
              page++;
              fetchAllProducts();
            } else {
              setProducts(allProducts);
              setIsLoading(false);
            }
          }).catch(function (error) {
            console.error('Error loading products:', error);
            setIsLoading(false);
          });
        }

        fetchAllProducts();
      }

      function loadTags() {
        apiFetch({
          path: '/wc/v3/products/tags?per_page=100'
        }).then(function (data) {
          setTags(data);
        }).catch(function (error) {
          console.error('Error loading tags:', error);
        });
      }

      function toggleProductSelection(productId) {
        var selectedProducts = attributes.selectedProducts || [];
        var index = selectedProducts.indexOf(productId);
        
        if (index === -1) {
          // Agregar producto
          if (selectedProducts.length < attributes.productsToShow) {
            setAttributes({
              selectedProducts: [...selectedProducts, productId]
            });
          }
        } else {
          // Remover producto
          setAttributes({
            selectedProducts: selectedProducts.filter(function(id) {
              return id !== productId;
            })
          });
        }
      }

      function toggleTagSelection(tagId) {
        var selectedTags = attributes.selectedTags || [];
        var index = selectedTags.indexOf(tagId);
        
        if (index === -1) {
          setAttributes({
            selectedTags: [...selectedTags, tagId]
          });
        } else {
          setAttributes({
            selectedTags: selectedTags.filter(function(id) {
              return id !== tagId;
            })
          });
        }
      }

      // Filtrar productos por búsqueda
      var filteredProducts = products.filter(function(product) {
        var term = searchTerm.toLowerCase();
        return (
          (product.name && product.name.toLowerCase().includes(term)) ||
          (product.sku && product.sku.toLowerCase().includes(term)) ||
          (product.description && product.description.toLowerCase().includes(term)) ||
          (product.short_description && product.short_description.toLowerCase().includes(term))
        );
      });

      // Obtener productos seleccionados para mostrar
      var displayProducts = [];
      if (attributes.filterType === 'manual') {
        displayProducts = products.filter(function(product) {
          return attributes.selectedProducts.includes(product.id);
        });
      } else {
        displayProducts = filteredProducts.slice(0, attributes.productsToShow);
      }

      return el('div', { className: 'ocellaris-featured-products-editor' }, [
        el(InspectorControls, {}, [
          el(PanelBody, { title: 'Configuración General', initialOpen: true }, [
            el(TextControl, {
              label: 'Título',
              value: attributes.title,
              onChange: function(value) {
                setAttributes({ title: value });
              }
            }),
            el(RangeControl, {
              label: 'Productos a mostrar',
              value: attributes.productsToShow,
              onChange: function(value) {
                setAttributes({ productsToShow: value });
              },
              min: 1,
              max: 12
            }),
            el(ToggleControl, {
              label: 'Mostrar productos aleatorios',
              help: 'Los productos se mostrarán en orden aleatorio cada vez que se cargue la página',
              checked: attributes.randomizeProducts,
              onChange: function(value) {
                setAttributes({ randomizeProducts: value });
              }
            })
          ]),
          
          el(PanelBody, { title: 'Filtros de Producto', initialOpen: true }, [
            el(SelectControl, {
              label: 'Tipo de filtro',
              value: attributes.filterType,
              options: [
                { label: 'Selección manual', value: 'manual' },
                { label: 'Por etiquetas', value: 'tags' },
                { label: 'Productos en oferta', value: 'sale' },
                { label: 'Productos destacados', value: 'featured' }
              ],
              onChange: function(value) {
                setAttributes({ 
                  filterType: value,
                  selectedProducts: [],
                  selectedTags: []
                });
              }
            }),

            // Selección manual de productos
            attributes.filterType === 'manual' && el('div', {}, [
              el('h4', {}, 'Seleccionar Productos'),
              el(TextControl, {
                label: 'Buscar productos',
                value: searchTerm,
                onChange: setSearchTerm,
                placeholder: 'Escribe para buscar...'
              }),
              el('div', { className: 'products-grid' }, 
                filteredProducts.map(function(product) {
                  var isSelected = attributes.selectedProducts.includes(product.id);
                  return el('div', {
                    key: product.id,
                    className: 'product-item ' + (isSelected ? 'selected' : ''),
                    onClick: function() { toggleProductSelection(product.id); }
                  }, [
                    product.images[0] && el('img', {
                      src: product.images[0].src,
                      alt: product.name,
                      style: { width: '50px', height: '50px', objectFit: 'cover' }
                    }),
                    el('div', {}, [
                      el('strong', {}, product.name),
                      el('div', {}, '$' + product.price)
                    ])
                  ]);
                })
              )
            ]),

            // Selección por etiquetas
            attributes.filterType === 'tags' && el('div', {}, [
              el('h4', {}, 'Seleccionar Etiquetas'),
              tags.map(function(tag) {
                var isSelected = attributes.selectedTags.includes(tag.id);
                return el(CheckboxControl, {
                  key: tag.id,
                  label: tag.name,
                  checked: isSelected,
                  onChange: function() {
                    toggleTagSelection(tag.id);
                  }
                });
              })
            ])
          ])
        ]),

        // Vista previa del bloque
        el('div', { className: 'ocellaris-featured-products-preview' }, [
          el('h2', { 
            style: { 
              textAlign: 'center', 
              color: '#FF1654', 
              fontSize: '24px',
              fontWeight: 'bold',
              marginBottom: '30px'
            } 
          }, attributes.title),

          isLoading && el('p', {}, 'Cargando productos...'),
          
          !isLoading && displayProducts.length === 0 && el('p', {}, 'No hay productos para mostrar.'),
          
          !isLoading && displayProducts.length > 0 && el('div', {
            style: {
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
              gap: '20px',
              maxWidth: '1200px',
              margin: '0 auto'
            }
          }, displayProducts.slice(0, attributes.productsToShow).map(function(product) {
            return el('div', {
              key: product.id,
              style: {
                border: '1px solid #ddd',
                borderRadius: '8px',
                padding: '15px',
                textAlign: 'center',
                backgroundColor: '#fff'
              }
            }, [
              product.images[0] && el('img', {
                src: product.images[0].src,
                alt: product.name,
                style: {
                  width: '100%',
                  height: '200px',
                  objectFit: 'cover',
                  marginBottom: '15px'
                }
              }),
              el('h3', {
                style: { fontSize: '16px', margin: '10px 0' }
              }, product.name),
              el('div', {
                style: { 
                  color: '#FF1654', 
                  fontSize: '18px', 
                  fontWeight: 'bold',
                  marginBottom: '10px'
                }
              }, '$' + product.price),
              el('div', {
                style: {
                  backgroundColor: '#007cba',
                  color: 'white',
                  padding: '8px 16px',
                  borderRadius: '4px',
                  cursor: 'pointer'
                }
              }, 'ADD TO CART')
            ]);
          }))
        ])
      ]);
    },

    save: function () {
      // El contenido se renderiza en PHP
      return null;
    }
  });

})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.data);