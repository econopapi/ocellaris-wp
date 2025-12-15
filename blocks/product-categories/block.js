(function (blocks, element, blockEditor, components, data) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var PanelBody = components.PanelBody;
  var CheckboxControl = components.CheckboxControl;
  var useSelect = data.useSelect;

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

      // Fetch product categories
      var categories = useSelect(function (select) {
        return select('core').getEntityRecords('taxonomy', 'product_cat', {
          per_page: -1,
          hide_empty: false
        });
      }, []);

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
                  title: 'Select Categories',
                  initialOpen: true,
                  key: 'categories'
                },
                !categories
                  ? el('p', {}, 'Loading categories...')
                  : categories.length === 0
                  ? el('p', {}, 'No product categories found.')
                  : categories.map(function (category) {
                      return el(CheckboxControl, {
                        label: category.name + ' (ID: ' + category.id + ')',
                        checked: selectedCategories.indexOf(category.id) > -1,
                        onChange: function () {
                          toggleCategory(category.id);
                        },
                        key: category.id
                      });
                    })
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