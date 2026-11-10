import { store, getContext } from '@wordpress/interactivity';

store( 'plugin-template', {
	actions: {
		toggle() {
			const context = getContext();
			context.isOpen = ! context.isOpen;
		},
	},
} );
