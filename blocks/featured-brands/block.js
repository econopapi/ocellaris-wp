(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var PanelBody = components.PanelBody;
  var CheckboxControl = components.CheckboxControl;
  var useSelect = data.useSelect;

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
      autoplaySpeed: {
        type: 'number',
        default: 3000
      }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var selectedBrands = attributes.selectedBrands || [];

      // Fetch product brands
      var brands = useSelect(function (select) {
        return select('core').getEntityRecords('taxonomy', 'product_brand', {
          per_page: -1,
          hide_empty: false
        });
      }, []);

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
                  el(RangeControl, {
                    label: 'Autoplay Speed (ms)',
                    value: attributes.autoplaySpeed,
                    onChange: function (value) {
                      setAttributes({ autoplaySpeed: value });
                    },
                    min: 1000,
                    max: 10000,
                    step: 500,
                    key: 'autoplaySpeed'
                  })
                ]
              ),
              el(
                PanelBody,
                {
                  title: 'Select Brands',
                  initialOpen: true,
                  key: 'brands'
                },
                !brands
                  ? el('p', {}, 'Loading brands...')
                  : brands.length === 0
                  ? el('p', {}, 'No product brands found. Make sure you have the WooCommerce Brands plugin installed.')
                  : brands.map(function (brand) {
                      return el(CheckboxControl, {
                        label: brand.name + ' (ID: ' + brand.id + ')',
                        checked: selectedBrands.indexOf(brand.id) > -1,
                        onChange: function () {
                          toggleBrand(brand.id);
                        },
                        key: brand.id
                      });
                    })
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
                  style: { 
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
                      var brand = brands && brands.find(function (b) { return b.id === brandId; });
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
              }, '↔ Carousel with autoplay (Preview on frontend)')
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