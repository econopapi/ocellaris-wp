(function (blocks, element, blockEditor, components) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var PanelBody = components.PanelBody;
  var ToggleControl = components.ToggleControl;
  var SelectControl = components.SelectControl;

  registerBlockType('ocellaris/all-brands', {
    title: 'Ocellaris All Brands',
    icon: 'grid-view',
    category: 'widgets',
    attributes: {
      title: {
        type: 'string',
        default: 'TODAS NUESTRAS MARCAS'
      },
      showAlphabetFilter: {
        type: 'boolean',
        default: true
      },
      columns: {
        type: 'number',
        default: 4
      },
      displayStyle: {
        type: 'string',
        default: 'grid' // 'grid' or 'list'
      },
      showBrandCount: {
        type: 'boolean',
        default: true
      },
      brandImageSize: {
        type: 'string',
        default: 'medium' // 'small', 'medium', 'large'
      }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      return el(
        'div',
        { className: 'ocellaris-all-brands-editor' },
        [
          el(
            InspectorControls,
            { key: 'inspector' },
            [
              el(
                PanelBody,
                {
                  title: 'Configuración General',
                  initialOpen: true,
                  key: 'general'
                },
                [
                  el(TextControl, {
                    label: 'Título',
                    value: attributes.title,
                    onChange: function (value) {
                      setAttributes({ title: value });
                    },
                    key: 'title'
                  }),
                  el(ToggleControl, {
                    label: 'Mostrar filtro alfabético',
                    checked: attributes.showAlphabetFilter,
                    onChange: function (value) {
                      setAttributes({ showAlphabetFilter: value });
                    },
                    key: 'showAlphabetFilter'
                  }),
                  el(ToggleControl, {
                    label: 'Mostrar contador de marcas',
                    checked: attributes.showBrandCount,
                    onChange: function (value) {
                      setAttributes({ showBrandCount: value });
                    },
                    key: 'showBrandCount'
                  })
                ]
              ),
              el(
                PanelBody,
                {
                  title: 'Diseño y Presentación',
                  initialOpen: true,
                  key: 'design'
                },
                [
                  el(SelectControl, {
                    label: 'Estilo de visualización',
                    value: attributes.displayStyle,
                    onChange: function (value) {
                      setAttributes({ displayStyle: value });
                    },
                    options: [
                      { label: 'Grid (rejilla)', value: 'grid' },
                      { label: 'Lista vertical', value: 'list' }
                    ],
                    key: 'displayStyle'
                  }),
                  el(RangeControl, {
                    label: 'Columnas (modo grid)',
                    value: attributes.columns,
                    onChange: function (value) {
                      setAttributes({ columns: value });
                    },
                    min: 2,
                    max: 6,
                    step: 1,
                    key: 'columns'
                  }),
                  el(SelectControl, {
                    label: 'Tamaño de imagen de marca',
                    value: attributes.brandImageSize,
                    onChange: function (value) {
                      setAttributes({ brandImageSize: value });
                    },
                    options: [
                      { label: 'Pequeño', value: 'small' },
                      { label: 'Mediano', value: 'medium' },
                      { label: 'Grande', value: 'large' }
                    ],
                    key: 'brandImageSize'
                  })
                ]
              )
            ]
          ),
          el(
            'div',
            { className: 'editor-preview', key: 'preview' },
            [
              el('h3', { 
                style: { 
                  textAlign: 'center', 
                  color: '#FF6347', 
                  marginBottom: '30px',
                  textTransform: 'uppercase',
                  letterSpacing: '1px'
                } 
              }, attributes.title),
              
              // Preview del filtro alfabético
              attributes.showAlphabetFilter && el(
                'div',
                {
                  style: {
                    display: 'flex',
                    justifyContent: 'center',
                    flexWrap: 'wrap',
                    gap: '8px',
                    marginBottom: '30px',
                    padding: '15px',
                    background: '#f8f9fa',
                    borderRadius: '8px'
                  }
                },
                ['TODAS', 'A', 'B', 'C', 'D', 'E', 'F'].map(function(letter, index) {
                  return el(
                    'button',
                    {
                      key: letter,
                      style: {
                        padding: '8px 12px',
                        background: letter === 'TODAS' ? '#007cba' : '#e0e0e0',
                        color: letter === 'TODAS' ? '#fff' : '#333',
                        border: 'none',
                        borderRadius: '4px',
                        fontSize: '12px',
                        fontWeight: 'bold',
                        cursor: 'pointer'
                      }
                    },
                    letter
                  );
                })
              ),

              // Preview del contador
              attributes.showBrandCount && el(
                'p',
                {
                  style: {
                    textAlign: 'center',
                    color: '#666',
                    fontSize: '14px',
                    marginBottom: '20px'
                  }
                },
                'Mostrando todas las marcas (XX resultados)'
              ),

              // Preview del grid/lista
              el(
                'div',
                {
                  style: {
                    display: attributes.displayStyle === 'grid' ? 'grid' : 'block',
                    gridTemplateColumns: attributes.displayStyle === 'grid' ? 
                      'repeat(' + attributes.columns + ', 1fr)' : 'none',
                    gap: '20px',
                    padding: '20px',
                    background: '#f9f9f9',
                    borderRadius: '8px'
                  }
                },
                [1, 2, 3, 4, 5, 6].slice(0, attributes.columns * 2).map(function(i) {
                  return el(
                    'div',
                    {
                      key: i,
                      style: {
                        background: '#fff',
                        border: '1px solid #e0e0e0',
                        borderRadius: '8px',
                        padding: '20px',
                        textAlign: 'center',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                        height: attributes.brandImageSize === 'small' ? '80px' : 
                                attributes.brandImageSize === 'medium' ? '100px' : '120px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                      }
                    },
                    el('span', { 
                      style: { 
                        fontSize: '12px', 
                        color: '#666',
                        fontWeight: '500'
                      } 
                    }, 'Marca ' + i)
                  );
                })
              ),

              el('p', { 
                style: { 
                  textAlign: 'center', 
                  color: '#666', 
                  fontSize: '12px',
                  marginTop: '20px',
                  fontStyle: 'italic'
                } 
              }, 'Vista previa - Las marcas se cargarán automáticamente desde WooCommerce')
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
  window.wp.components
);