/**
 * タブの表示とキーボード操作を担当する。選択状態は親で管理する。
 *
 * @param props          コンポーネントのプロパティ。
 * @param props.messages 画面文言。
 * @param props.tab      選択中のタブ。
 * @param props.onChange タブ変更時の通知。
 */
export default function SettingsTabs( {
	messages,
	tab,
	onChange,
}: {
	messages: Record< string, string >;
	tab: string;
	onChange: ( tab: string ) => void;
} ) {
	const tabs = [
		{ id: 'basic', label: messages.basic_tab },
		{ id: 'compatibility', label: messages.compatibility_tab },
	];

	return (
		<div
			className="nav-tab-wrapper rubymaco-tabs"
			role="tablist"
			aria-label={ messages.page_title }
		>
			{ tabs.map( ( item, index ) => (
				<button
					type="button"
					role="tab"
					key={ item.id }
					id={ 'rubymaco-tab-' + item.id }
					className={
						'nav-tab' + ( tab === item.id ? ' nav-tab-active' : '' )
					}
					aria-selected={ tab === item.id }
					aria-controls={ 'rubymaco-panel-' + item.id }
					tabIndex={ tab === item.id ? 0 : -1 }
					onClick={ () => onChange( item.id ) }
					onKeyDown={ ( event ) => {
						let next = index;
						if (
							event.key === 'ArrowRight' ||
							event.key === 'ArrowLeft'
						) {
							next = ( index + 1 ) % tabs.length;
						} else if ( event.key === 'Home' ) {
							next = 0;
						} else if ( event.key === 'End' ) {
							next = tabs.length - 1;
						} else {
							return;
						}
						event.preventDefault();
						onChange( tabs[ next ].id );
						document
							.getElementById( 'rubymaco-tab-' + tabs[ next ].id )
							?.focus();
					} }
				>
					{ item.label }
				</button>
			) ) }
		</div>
	);
}
