import { RadioControl } from '@wordpress/components';
import { wordpress } from './wordpress';

const { useSelect, useDispatch } = wordpress.data;
const { PluginDocumentSettingPanel } = wordpress.editor;
const config = window.rubymacoPostSettings;
// 全体・カテゴリーの設定に従う状態。保存済みのメタがない場合もこの状態として扱う。
const INHERIT = 'inherit';

// 投稿メタの編集状態を購読し、保存や取り消しも標準エディタに任せる。
function PostSettings() {
	const { postType, meta, saving } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return {
			postType: editor.getCurrentPostType(),
			meta: editor.getEditedPostAttribute( 'meta' ),
			saving: editor.isSavingPost(),
		};
	}, [] );
	const { editPost } = useDispatch( 'core/editor' );
	if ( ! config || ! [ 'post', 'page' ].includes( postType ) || ! meta ) {
		return null;
	}
	const current = meta[ config.metaKey ];
	const selected = config.options.some(
		( option ) => option.value === current
	)
		? String( current )
		: INHERIT;
	return (
		<PluginDocumentSettingPanel
			name="rubymaco-conversion"
			title="Ruby Markup Converter"
		>
			<RadioControl
				label={ config.label }
				help={ config.help }
				selected={ selected }
				options={ config.options }
				disabled={ saving }
				onChange={ ( value ) => {
					if (
						! saving &&
						config.options.some(
							( option ) => option.value === value
						)
					) {
						// 継承に戻すときは null を送り、REST API にメタを削除させる.
						editPost( {
							meta: {
								[ config.metaKey ]:
									value === INHERIT ? null : value,
							},
						} );
					}
				} }
			/>
		</PluginDocumentSettingPanel>
	);
}

if ( config ) {
	wordpress.plugins.registerPlugin( 'rubymaco-post-settings', {
		render: PostSettings,
		icon: null,
	} );
}
