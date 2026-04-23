(function (blocks, element, blockEditor, components) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var InspectorControls = blockEditor.InspectorControls;
  var TextControl = components.TextControl;
  var PanelBody = components.PanelBody;

  registerBlockType('ocellaris/filter-brand', {
    title: 'Ocellaris: Filtrar por Marca',
    icon: 'tag',
    category: 'widgets',
    attributes: {
      title: { type: 'string', default: 'Marca' }
    },

    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;

      return el(
        'div',
        { className: 'ocellaris-filter-brand-editor' },
        [
          el(
            InspectorControls,
            { key: 'inspector' },
            el(
              PanelBody,
              { title: 'Ajustes', initialOpen: true },
              el(TextControl, {
                label: 'Título',
                value: attributes.title,
                onChange: function (value) { setAttributes({ title: value }); },
                key: 'title'
              })
            )
          ),
          el('div', { className: 'editor-preview' }, [
            el('h4', { key: 'title-preview' }, attributes.title),
            el('p', { key: 'desc' }, 'Este bloque muestra un dropdown para filtrar por marca.')
          ])
        ]
      );
    },

    save: function () {
      return null; // server rendered
    }
  });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components);
