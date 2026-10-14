import { Notice, RadioGroupControl, Stack, Text } from '@wordpress/ui';
import type { PageData, Settings, RulePreview } from './types';
import MarkupRuleItem from './markup-rule-item';
type Props = {
	definitions: PageData[ 'definitions' ];
	settings: Settings;
	messages: Record< string, string >;
	previews: RulePreview[];
	failed: boolean;
	disabled: boolean;
	onRuleChange: ( id: string, checked: boolean ) => void;
	onStyleChange: ( value: string ) => void;
};
// 記法はPHPの定義順で表示し、例文の変換は選択状態に依存させない。
export default function MarkupRules( {
	definitions,
	settings,
	messages,
	previews,
	failed,
	disabled,
	onRuleChange,
	onStyleChange,
}: Props ) {
	return (
		<section className="rubymaco-section" aria-labelledby="rubymaco-rules">
			<Text variant="heading-xl" render={ <h2 id="rubymaco-rules" /> }>
				{ messages.markup_rules }
			</Text>
			<Notice.Root intent="warning">
				<Notice.Title>{ messages.markup_warning_title }</Notice.Title>
				<Notice.Description>
					{ messages.markup_warning }
				</Notice.Description>
			</Notice.Root>
			{ [ 'ruby', 'bouten' ].map( ( type ) => (
				<section
					className="rubymaco-rule-group"
					key={ type }
					aria-labelledby={ 'rubymaco-' + type }
				>
					<Text
						variant="heading-lg"
						render={ <h3 id={ 'rubymaco-' + type } /> }
					>
						{ messages[ type ] }
					</Text>
					<Stack direction="column">
						{ definitions.markup_rules
							.filter( ( rule ) => rule.type === type )
							.map( ( rule ) => (
								<MarkupRuleItem
									key={ rule.id }
									rule={ rule }
									enabled={ settings.enabled_rule_ids.includes(
										rule.id
									) }
									disabled={ disabled }
									preview={ previews.find(
										( item ) => item.id === rule.id
									) }
									messages={ messages }
									failed={ failed }
									onChange={ ( checked ) =>
										onRuleChange( rule.id, checked )
									}
								/>
							) ) }
					</Stack>
					{ type === 'bouten' && (
						<div className="rubymaco-bouten-style">
							<RadioGroupControl
								label={ messages.bouten_style }
								description={
									messages.bouten_style_description
								}
								items={ definitions.bouten_styles }
								value={ settings.bouten_style }
								disabled={ disabled }
								onValueChange={ ( next ) =>
									onStyleChange( String( next ) )
								}
							/>
						</div>
					) }
				</section>
			) ) }
		</section>
	);
}
