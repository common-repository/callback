var el = wp.element.createElement,
	registerBlockType	= wp.blocks.registerBlockType;

registerBlockType( 'callback/block', {
	title: 'Callback Form',
	description: 'All labels and options are managed in the plugin settings',
	icon: 'email-alt',
	category: 'widgets',
	edit: function( props ) {
		return [
			el('button', {
				className: props.className,
				},
				'Callback Form' // Button label
			),
		];
	},

	save: function() {
		return null;
	},
} );