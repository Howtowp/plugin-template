import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit() {
		return (
			<p { ...useBlockProps() }>
				{ __( 'Plugin Template sample (interactive on the front end).', 'plugin-template' ) }
			</p>
		);
	},
	save: () => null, // Dynamic block: rendered by render.php.
} );
