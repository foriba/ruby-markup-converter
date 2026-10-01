import { useRef, useState } from '@wordpress/element';
import type { AdminConfig, PageData, Settings } from './types';
import { saveSettings } from './settings-api';
import SettingChoices from './setting-choices';
import SettingsTabs from './settings-tabs';
import MarkupRuleFields from './markup-rule-fields';

/**
 * 設定を編集し、認証付きのREST APIを通じて保存する。
 *
 * @param props        コンポーネントのプロパティ。
 * @param props.data   初期設定とPHP側の表示定義。
 * @param props.config REST APIの接続情報。
 */
export default function SettingsForm( {
	data,
	config,
}: {
	data: PageData;
	config: AdminConfig;
} ) {
	const [ settings, setSettings ] = useState< Settings >( data.settings );
	const [ savedSettings, setSavedSettings ] = useState< Settings >(
		data.settings
	);
	const [ saveStatus, setSaveStatus ] = useState<
		'idle' | 'saving' | 'saved' | 'failed'
	>( 'idle' );
	const savePending = useRef( false );
	const [ tab, setTab ] = useState( 'basic' );
	const { messages, definitions } = data;
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
	const save = async () => {
		// 次回描画での入力無効化に加え、即座にフラグを確認して二重送信を防ぐ。
		if ( savePending.current ) {
			return;
		}
		savePending.current = true;
		setSaveStatus( 'saving' );
		try {
			const result = await saveSettings( config, settings, definitions );
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
	return (
		<>
			<p>
				<a
					href={ data.links.user_guide }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ messages.user_guide } { messages.opens_in_new_tab }
				</a>
			</p>
			<fieldset
				className="rubymaco-editor"
				disabled={ saving }
				aria-label={ messages.page_title }
				aria-busy={ saving }
			>
				<SettingsTabs
					messages={ messages }
					tab={ tab }
					onChange={ setTab }
				/>
				<div
					role="tabpanel"
					id="rubymaco-panel-basic"
					aria-labelledby="rubymaco-tab-basic"
					hidden={ tab !== 'basic' }
					tabIndex={ 0 }
				>
					<SettingChoices
						name="apply_mode"
						label={ messages.conversion_scope }
						choices={ definitions.apply_modes }
						value={ settings.apply_mode }
						onChange={ ( value ) =>
							setSettings( ( current ) => ( {
								...current,
								apply_mode: value,
							} ) )
						}
					/>
					<h2>{ messages.markup_rules }</h2>
					{ [ 'ruby', 'bouten' ].map( ( type ) => (
						<section
							key={ type }
							aria-labelledby={ 'rubymaco-heading-' + type }
						>
							<h3 id={ 'rubymaco-heading-' + type }>
								{ messages[ type ] }
							</h3>
							<MarkupRuleFields
								rules={ definitions.markup_rules.filter(
									( rule ) => rule.type === type
								) }
								enabledRuleIds={ settings.enabled_rule_ids }
								exampleLabel={ messages.markup_example }
								onChange={ ( id, checked ) =>
									setSettings( ( current ) => ( {
										...current,
										enabled_rule_ids:
											definitions.markup_rules
												.filter( ( rule ) =>
													rule.id === id
														? checked
														: current.enabled_rule_ids.includes(
																rule.id
														  )
												)
												.map( ( rule ) => rule.id ),
									} ) )
								}
							/>
							{ type === 'bouten' && (
								<SettingChoices
									name="bouten_style"
									label={ messages.bouten_style }
									choices={ definitions.bouten_styles }
									value={ settings.bouten_style }
									onChange={ ( value ) =>
										setSettings( ( current ) => ( {
											...current,
											bouten_style: value,
										} ) )
									}
								/>
							) }
						</section>
					) ) }
				</div>
				<div
					role="tabpanel"
					id="rubymaco-panel-compatibility"
					aria-labelledby="rubymaco-tab-compatibility"
					hidden={ tab !== 'compatibility' }
					tabIndex={ 0 }
				>
					<SettingChoices
						name="bouten_rendering_method"
						label={ messages.bouten_rendering_method }
						choices={ definitions.bouten_rendering_methods }
						value={ settings.bouten_rendering_method }
						onChange={ ( value ) =>
							setSettings( ( current ) => ( {
								...current,
								bouten_rendering_method: value,
							} ) )
						}
					/>
				</div>
			</fieldset>
			{ saveStatus === 'failed' && (
				<div className="notice notice-error">
					<p role="alert">{ messages.save_failed }</p>
				</div>
			) }
			<p role="status" className="rubymaco-edit-status">
				{ dirty ? messages.unsaved_changes : '' }
			</p>
			<p className="rubymaco-save-actions">
				<button
					type="button"
					className="button button-primary"
					disabled={
						saving || ( ! dirty && saveStatus !== 'failed' )
					}
					onClick={ save }
				>
					{ saving ? messages.saving : messages.save }
				</button>
				<span role="status">
					{ saving && messages.saving }
					{ saveStatus === 'saved' && ! dirty && messages.saved }
				</span>
			</p>
		</>
	);
}
