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
  var Spinner = components.Spinner;
  var useState = element.useState;
  var useEffect = element.useEffect;
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
      
        var _useState3 = useState(true),
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
        setProducts([]);
        var perPage = 100;
        var params = {
          per_page: perPage,
          status: 'publish',
          stock_status: 'instock'
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

          // Intentar primero con la API REST de WooCommerce (requiere permisos)
          return apiFetch({
            path: wp.url.addQueryArgs('/wc/v3/products', pageParams)
          }).catch(function (error) {
            // Fallback a la Store API pública cuando la v3 no está disponible (p.ej. usuarios sin permisos)
            console.warn('Falling back to Store API for products:', error);
            var storeParams = Object.assign({}, pageParams);
            delete storeParams.status; // La Store API no acepta "status"
            return apiFetch({
              path: wp.url.addQueryArgs('/wc/store/v1/products', storeParams)
            });
          });
        }

        function isProductInStock(product) {
          if (!product || typeof product !== 'object') {
            return false;
          }

          if (typeof product.is_in_stock === 'boolean') {
            return product.is_in_stock;
          }

          if (typeof product.stock_status === 'string') {
            return product.stock_status.toLowerCase() === 'instock';
          }

          return true;
        }

        function fetchAllProducts(pageNum, accumulatedProducts) {
          return fetchPage(pageNum)
            .then(function (data) {
              // Respuesta inesperada: detener carga para no dejar spinner infinito
              if (!Array.isArray(data)) {
                throw new Error('Unexpected products response');
              }

              var mergedProducts = accumulatedProducts.concat(data);

              if (data.length === perPage) {
                return fetchAllProducts(pageNum + 1, mergedProducts);
              }

              return mergedProducts;
            })
        }

        fetchAllProducts(1, [])
          .then(function (allProducts) {
            setProducts(allProducts.filter(isProductInStock));
          })
          .catch(function (error) {
            console.error('Error loading products:', error);
          })
          .finally(function () {
            setIsLoading(false);
          });
      }

      function loadTags() {
        apiFetch({
          path: '/wc/v3/products/tags?per_page=100'
        }).then(function (data) {
          setTags(data);
        }).catch(function (error) {
          // Reintentar con la Store API si la v3 falla (roles sin permisos)
          console.warn('Falling back to Store API for tags:', error);
          apiFetch({
            path: '/wc/store/v1/products/tags?per_page=100'
          }).then(function (data) {
            setTags(data);
          }).catch(function (fallbackError) {
            console.error('Error loading tags:', fallbackError);
          });
        });
      }

      function toggleProductSelection(productId) {
        var selectedProducts = attributes.selectedProducts || [];
        var index = selectedProducts.indexOf(productId);
        
        if (index === -1) {
          setAttributes({
            selectedProducts: [].concat(selectedProducts, [productId])
          });
        } else {
          // Remover producto
          setAttributes({
            selectedProducts: selectedProducts.filter(function(id) {
              return id !== productId;
            })
          });
        }
      }

      function removeSelectedProduct(productId) {
        setAttributes({
          selectedProducts: (attributes.selectedProducts || []).filter(function(id) {
            return id !== productId;
          })
        });
      }

      function getProductById(productId) {
        for (var i = 0; i < products.length; i++) {
          if (products[i].id === productId) {
            return products[i];
          }
        }
        return null;
      }

      function getProductImage(product) {
        if (!product || !Array.isArray(product.images) || product.images.length === 0) {
          return '';
        }
        return product.images[0].src || '';
      }

      function getProductPrice(product) {
        if (!product) {
          return '';
        }

        function normalizePriceText(value) {
          if (typeof value !== 'string' || value.length === 0) {
            return '';
          }

          // Convertir HTML de WooCommerce a texto legible para el editor.
          return value
            .replace(/<[^>]*>/g, ' ')
            .replace(/&#0*36;|&dollar;/gi, '$')
            .replace(/&nbsp;/gi, ' ')
            .replace(/&amp;/gi, '&')
            .replace(/\s+/g, ' ')
            .trim();
        }

        if (typeof product.price_html === 'string' && product.price_html.length > 0) {
          return normalizePriceText(product.price_html);
        }

        if (typeof product.price === 'string' && product.price.length > 0) {
          return '$' + product.price;
        }

        if (typeof product.prices === 'object' && product.prices && typeof product.prices.price === 'string') {
          return '$' + product.prices.price;
        }

        return '';
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

      var selectedProductItems = (attributes.selectedProducts || []).map(function(productId) {
        return {
          id: productId,
          product: getProductById(productId)
        };
      });

      // Obtener productos seleccionados para mostrar
      var displayProducts = [];
      if (attributes.filterType === 'manual') {
        displayProducts = selectedProductItems.map(function(item) { return item.product; }).filter(function(product) { return !!product; });
      } else {
        displayProducts = products.slice(0, attributes.productsToShow);
      }

      var isManualCarouselActive = attributes.filterType === 'manual' && (attributes.selectedProducts || []).length > attributes.productsToShow;
      var previewProducts = displayProducts.slice(0, attributes.productsToShow);

      // Skeletons para feedback de carga en la selección manual
      var skeletonItems = Array.from({ length: 6 }).map(function (_, idx) {
        return el('div', {
          key: 'skeleton-' + idx,
          className: 'product-item skeleton'
        }, [
          el('div', { className: 'skeleton-thumb' }),
          el('div', { className: 'skeleton-lines' }, [
            el('div', { className: 'line short' }),
            el('div', { className: 'line long' })
          ])
        ]);
      });

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
              label: 'Tarjetas visibles (layout)',
              help: 'Define cuantas tarjetas se muestran a la vez. Si hay mas productos, se activa carrusel en frontend.',
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
              el('p', { className: 'selection-summary' }, 'Seleccionados: ' + (attributes.selectedProducts || []).length + ' | Visibles por vista: ' + attributes.productsToShow),

              el('div', { className: 'selected-products-list' }, [
                el('h4', {}, 'Productos seleccionados'),
                selectedProductItems.length === 0
                  ? el('p', { className: 'selected-products-empty' }, 'Todavia no has seleccionado productos.')
                  : selectedProductItems.map(function(item) {
                      var selectedImage = item.product ? getProductImage(item.product) : '';
                      return el('div', {
                        key: 'selected-' + item.id,
                        className: 'selected-product-item'
                      }, [
                        selectedImage && el('img', {
                          src: selectedImage,
                          alt: item.product ? item.product.name : ('Producto ' + item.id),
                          className: 'selected-product-thumb'
                        }),
                        el('div', { className: 'selected-product-meta' }, [
                          el('strong', {}, item.product ? item.product.name : ('Producto ID #' + item.id)),
                          el('span', {}, item.product ? getProductPrice(item.product) : 'Cargando...')
                        ]),
                        el(Button, {
                          isSecondary: true,
                          isSmall: true,
                          onClick: function() {
                            removeSelectedProduct(item.id);
                          }
                        }, 'Quitar')
                      ]);
                    })
              ]),

              isLoading && el('div', { className: 'loading-products-state' }, [
                el(Spinner, {}),
                el('span', {}, 'Cargando productos disponibles...')
              ]),
              el(TextControl, {
                label: 'Buscar productos',
                value: searchTerm,
                onChange: setSearchTerm,
                placeholder: 'Escribe para buscar...',
                disabled: isLoading
              }),
              el('div', { className: 'products-grid' }, 
                isLoading
                  ? skeletonItems
                  : (filteredProducts.length === 0
                    ? [
                        el('p', { className: 'empty-hint' },
                          products.length === 0
                            ? 'No hay productos en stock disponibles.'
                            : 'No se encontraron productos con esa búsqueda.'
                        )
                      ]
                    : filteredProducts.map(function(product) {
                        var isSelected = attributes.selectedProducts.includes(product.id);
                        var productImage = getProductImage(product);
                        return el('div', {
                          key: product.id,
                          className: 'product-item ' + (isSelected ? 'selected' : ''),
                          onClick: function() { toggleProductSelection(product.id); }
                        }, [
                          productImage && el('img', {
                            src: productImage,
                            alt: product.name,
                            style: { width: '50px', height: '50px', objectFit: 'cover' }
                          }),
                          el('div', {}, [
                            el('strong', {}, product.name),
                            el('div', {}, getProductPrice(product))
                          ])
                        ]);
                      })
                  )
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

          !isLoading && isManualCarouselActive && el('p', { className: 'carousel-preview-hint' }, 'Carrusel activo en frontend: hay mas productos seleccionados que tarjetas visibles.'),
          
          !isLoading && previewProducts.length > 0 && el('div', {
            style: {
              display: 'grid',
              gridTemplateColumns: 'repeat(' + attributes.productsToShow + ', minmax(0, 1fr))',
              gap: '20px',
              maxWidth: '1200px',
              margin: '0 auto'
            }
          }, previewProducts.map(function(product) {
            var previewImage = getProductImage(product);
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
              previewImage && el('img', {
                src: previewImage,
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
              }, getProductPrice(product)),
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