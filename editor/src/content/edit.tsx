import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<div className="rubymaco-editor-label">
				{ __( 'Ruby Markup Converter', 'ruby-markup-converter' ) }
			</div>
			<InnerBlocks />
		</div>
	);
}
