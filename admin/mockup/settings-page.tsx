import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { Link, Tabs } from '@wordpress/ui';
import { Page } from '@wordpress/admin-ui';
import { rules } from './sample-data';
import { ConversionScope } from './conversion-scope';
import { MarkupRules } from './markup-rules';
import { CompatibilitySettings } from './compatibility-settings';

/** 保存せずに、設定画面の配置と選択状態を試す。 */
export function SettingsPage() {
	const [ scope, setScope ] = useState( 'shortcode' );
	const [ style, setStyle ] = useState( 'sesame' );
	const [ method, setMethod ] = useState( 'text_emphasis' );
	const [ enabled, setEnabled ] = useState(
		rules.filter( ( rule ) => rule.enabled ).map( ( rule ) => rule.id )
	);
	const [ saved, setSaved ] = useState( false );
	const change = ( update: () => void ) => {
		update();
		setSaved( false );
	};
	return (
		<Page
			hasPadding
			showSidebarToggle={ false }
			title="Ruby Markup Converter"
		>
			<div className="mockup-settings">
				<div className="mockup-guide">
					<Link
						href="https://github.com/foriba/ruby-markup-converter/blob/main/docs/ja-JP/user-guide.md"
						openInNewTab
					>
						ユーザーガイド
					</Link>
				</div>
				<Tabs.Root defaultValue="basic">
					<Tabs.List aria-label="設定カテゴリー">
						<Tabs.Tab value="basic">基本設定</Tabs.Tab>
						<Tabs.Tab value="compatibility">互換設定</Tabs.Tab>
					</Tabs.List>
					<Tabs.Panel value="basic">
						<ConversionScope
							scope={ scope }
							onChange={ ( value ) =>
								change( () => setScope( value ) )
							}
						/>
						<MarkupRules
							rules={ rules }
							enabled={ enabled }
							style={ style }
							onStyleChange={ ( value ) =>
								change( () => setStyle( value ) )
							}
							onRuleChange={ ( id, checked ) =>
								change( () =>
									setEnabled( ( current ) =>
										checked
											? [ ...current, id ]
											: current.filter(
													( value ) => value !== id
											  )
									)
								)
							}
						/>
					</Tabs.Panel>
					<Tabs.Panel value="compatibility">
						<CompatibilitySettings
							method={ method }
							onChange={ ( value ) =>
								change( () => setMethod( value ) )
							}
						/>
					</Tabs.Panel>
				</Tabs.Root>
				<footer className="mockup-actions">
					<Button
						variant="primary"
						onClick={ () => setSaved( true ) }
					>
						設定を保存
					</Button>
					<span role="status">
						{ saved
							? 'モックアップのため、実際の設定は保存されません。'
							: '' }
					</span>
				</footer>
			</div>
		</Page>
	);
}
