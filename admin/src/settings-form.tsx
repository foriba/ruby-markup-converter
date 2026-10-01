import { useRef, useState } from '@wordpress/element';
import type { AdminConfig, Choice, PageData, Settings } from './types';
import { saveSettings } from './settings-api';

/**
 * PHPから取得した選択肢をラジオボタンで表示する。
 *
 * @param props          コンポーネントのプロパティ。
 * @param props.name     入力項目名。
 * @param props.label    選択肢グループの見出し。
 * @param props.choices  選択肢の一覧。
 * @param props.value    現在の選択値。
 * @param props.onChange 選択変更時のコールバック。
 */
function Choices( {
	name,
	label,
	choices,
	value,
	onChange,
}: {
	name: string;
	label: string;
	choices: Choice[];
	value: string;
	onChange: ( value: string ) => void;
} ) {
	return (
		<fieldset className="rubymaco-field">
			<legend>{ label }</legend>
			{ choices.map( ( choice ) => (
				<div className="rubymaco-choice" key={ choice.value }>
					<label htmlFor={ name + '-' + choice.value }>
						<input
							id={ name + '-' + choice.value }
							type="radio"
							name={ name }
							value={ choice.value }
							checked={ value === choice.value }
							onChange={ () => onChange( choice.value ) }
							aria-describedby={
								choice.description
									? name + '-' + choice.value + '-help'
									: undefined
							}
						/>
						{ choice.label }
					</label>
					{ choice.description && (
						<p
							className="description"
							id={ name + '-' + choice.value + '-help' }
						>
							{ choice.description }
						</p>
					) }
				</div>
			) ) }
		</fieldset>
	);
}

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
	const tabs = [
		{ id: 'basic', label: messages.basic_tab },
		{ id: 'compatibility', label: messages.compatibility_tab },
	];
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
								'nav-tab' +
								( tab === item.id ? ' nav-tab-active' : '' )
							}
							aria-selected={ tab === item.id }
							aria-controls={ 'rubymaco-panel-' + item.id }
							tabIndex={ tab === item.id ? 0 : -1 }
							onClick={ () => setTab( item.id ) }
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
								setTab( tabs[ next ].id );
								document
									.getElementById(
										'rubymaco-tab-' + tabs[ next ].id
									)
									?.focus();
							} }
						>
							{ item.label }
						</button>
					) ) }
				</div>
				<div
					role="tabpanel"
					id="rubymaco-panel-basic"
					aria-labelledby="rubymaco-tab-basic"
					hidden={ tab !== 'basic' }
					tabIndex={ 0 }
				>
					<Choices
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
							{ definitions.markup_rules
								.filter( ( rule ) => rule.type === type )
								.map( ( rule ) => (
									<div
										className="rubymaco-rule"
										key={ rule.id }
									>
										<label
											htmlFor={
												'rubymaco-rule-' + rule.id
											}
										>
											<input
												id={
													'rubymaco-rule-' + rule.id
												}
												type="checkbox"
												checked={ settings.enabled_rule_ids.includes(
													rule.id
												) }
												aria-describedby={
													'rubymaco-rule-help-' +
													rule.id
												}
												onChange={ ( event ) => {
													const checked =
														event.target.checked;
													setSettings(
														( current ) => ( {
															...current,
															enabled_rule_ids:
																definitions.markup_rules
																	.filter(
																		(
																			candidate
																		) =>
																			candidate.id ===
																			rule.id
																				? checked
																				: current.enabled_rule_ids.includes(
																						candidate.id
																				  )
																	)
																	.map(
																		(
																			candidate
																		) =>
																			candidate.id
																	),
														} )
													);
												} }
											/>
											{ rule.titles.join( ' / ' ) }
										</label>
										<p
											className="description"
											id={
												'rubymaco-rule-help-' + rule.id
											}
										>
											{ rule.description }
										</p>
										<ul
											aria-label={
												messages.markup_example
											}
										>
											{ rule.examples.map(
												( example, index ) => (
													<li key={ index }>
														<code>{ example }</code>
													</li>
												)
											) }
										</ul>
									</div>
								) ) }
							{ type === 'bouten' && (
								<Choices
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
					<Choices
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
