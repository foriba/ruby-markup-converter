import { useEffect, useRef, useState } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { Link, Tabs } from '@wordpress/ui';
import type { PageData, Settings } from './types';
import { saveSettings } from './settings-api';
import ConversionScope from './conversion-scope';
import CompatibilitySettings from './compatibility-settings';
import MarkupRules from './markup-rules';
import useSettingsPreview from './use-settings-preview';
import SaveFeedback from './save-feedback';
/**
 * 設定を編集し、認証付きのREST APIを通じて保存する。
 *
 * @param props      コンポーネントのプロパティ。
 * @param props.data 初期設定とPHP側の表示定義。
 */
export default function SettingsForm( { data }: { data: PageData } ) {
	const [ settings, setSettings ] = useState< Settings >( data.settings );
	const [ savedSettings, setSavedSettings ] = useState< Settings >(
		data.settings
	);
	const [ saveStatus, setSaveStatus ] = useState<
		'idle' | 'saving' | 'saved' | 'failed'
	>( 'idle' );
	const savePending = useRef( false );
	const { messages, definitions } = data;
	const preview = useSettingsPreview( settings, definitions );
	const dirty =
		settings.apply_mode !== savedSettings.apply_mode ||
		settings.bouten_style !== savedSettings.bouten_style ||
		settings.bouten_rendering_method !==
			savedSettings.bouten_rendering_method ||
		settings.enabled_rule_ids.length !==
			savedSettings.enabled_rule_ids.length ||
		settings.enabled_rule_ids.some(
			( id ) => ! savedSettings.enabled_rule_ids.includes( id )
		);
	const saving = saveStatus === 'saving';
	// 保存成功の表示を5秒間維持する。再編集・画面終了時はタイマーを解除する。
	useEffect( () => {
		if ( saveStatus !== 'saved' || dirty ) {
			return;
		}
		const timer = setTimeout( () => {
			setSaveStatus( ( current ) =>
				current === 'saved' ? 'idle' : current
			);
		}, 5000 );
		return () => clearTimeout( timer );
	}, [ saveStatus, dirty ] );
	const save = async () => {
		// 次回描画での入力無効化に加え、即座にフラグを確認して二重送信を防ぐ。
		if ( savePending.current ) {
			return;
		}
		savePending.current = true;
		setSaveStatus( 'saving' );
		try {
			const result = await saveSettings( settings, definitions );
			setSettings( result );
			setSavedSettings( result );
			setSaveStatus( 'saved' );
		} catch {
			// 通信失敗時もサーバーでは一部保存済みの可能性がある。編集内容は保持する。
			setSaveStatus( 'failed' );
		} finally {
			savePending.current = false;
		}
	};

	// 保存完了後の表示は、次の編集で解除する。
	const change = ( update: Partial< Settings > ) => {
		if ( savePending.current ) {
			return;
		}
		setSettings( ( current ) => ( { ...current, ...update } ) );
		setSaveStatus( 'idle' );
	};
	return (
		<div className="rubymaco-settings">
			<div className="rubymaco-guide">
				<Link href={ data.links.user_guide } openInNewTab>
					{ messages.user_guide }
				</Link>
			</div>
			<Tabs.Root defaultValue="basic">
				<Tabs.List aria-label={ messages.page_title }>
					<Tabs.Tab value="basic">{ messages.basic_tab }</Tabs.Tab>
					<Tabs.Tab value="compatibility">
						{ messages.compatibility_tab }
					</Tabs.Tab>
				</Tabs.List>
				<Tabs.Panel value="basic">
					<ConversionScope
						choices={ definitions.apply_modes }
						value={ settings.apply_mode }
						label={ messages.conversion_scope }
						disabled={ saving }
						onChange={ ( value ) =>
							change( { apply_mode: value } )
						}
					/>
					<p role="status">
						{ preview.loading && (
							<>
								<Spinner />
								{ messages.preview_loading }
							</>
						) }
					</p>
					{ preview.failed && (
						<Notice status="error" isDismissible={ false }>
							<p role="alert">{ messages.preview_failed }</p>
							<Button
								variant="secondary"
								onClick={ preview.retry }
							>
								{ messages.retry }
							</Button>
						</Notice>
					) }
					<MarkupRules
						definitions={ definitions }
						settings={ settings }
						messages={ messages }
						previews={ preview.previews }
						failed={ preview.failed }
						disabled={ saving }
						onStyleChange={ ( value ) =>
							change( { bouten_style: value } )
						}
						onRuleChange={ ( id, checked ) =>
							change( {
								enabled_rule_ids: definitions.markup_rules
									.filter( ( rule ) =>
										rule.id === id
											? checked
											: settings.enabled_rule_ids.includes(
													rule.id
											  )
									)
									.map( ( rule ) => rule.id ),
							} )
						}
					/>
				</Tabs.Panel>
				<Tabs.Panel value="compatibility">
					<CompatibilitySettings
						value={ settings.bouten_rendering_method }
						messages={ messages }
						disabled={ saving }
						onChange={ ( value ) =>
							change( { bouten_rendering_method: value } )
						}
					/>
				</Tabs.Panel>
			</Tabs.Root>
			<SaveFeedback
				status={ saveStatus }
				dirty={ dirty }
				messages={ messages }
				onSave={ save }
			/>
		</div>
	);
}
