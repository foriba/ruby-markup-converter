import { Notice, RadioGroupControl, Stack, Text } from '@wordpress/ui';
import type { Rule } from './sample-data';
import { MarkupRuleItem } from './markup-rule-item';
type Props = {
	rules: Rule[];
	enabled: string[];
	style: string;
	onRuleChange: ( id: string, checked: boolean ) => void;
	onStyleChange: ( value: string ) => void;
};
// ルビ・傍点の記法と傍点の種類をまとめて表示する。
export function MarkupRules( {
	rules,
	enabled,
	style,
	onRuleChange,
	onStyleChange,
}: Props ) {
	return (
		<section className="mockup-section" aria-labelledby="mockup-rules">
			<Text variant="heading-xl" render={ <h2 /> }>
				記法ルール
			</Text>
			<Notice.Root intent="warning">
				<Notice.Title>注意事項</Notice.Title>
				<Notice.Description>
					記法の入れ子は想定していません。ある記法の内部に別の記法を含めると、意図しない変換結果になる場合があります。表示が意図と異なる場合は、記述内容と記法の組み合わせを確認してください。
				</Notice.Description>
			</Notice.Root>
			{ [ 'ruby', 'bouten' ].map( ( type ) => (
				<section
					className="mockup-rule-group"
					key={ type }
					aria-labelledby={ 'mockup-' + type }
				>
					<Text variant="heading-lg" render={ <h3 /> }>
						{ type === 'ruby' ? 'ルビ' : '傍点' }
					</Text>
					<Stack direction="column">
						{ rules
							.filter( ( rule ) => rule.type === type )
							.map( ( rule ) => (
								<MarkupRuleItem
									key={ rule.id }
									rule={ rule }
									enabled={ enabled.includes( rule.id ) }
									style={ style }
									onChange={ ( checked ) =>
										onRuleChange( rule.id, checked )
									}
								/>
							) ) }
					</Stack>
					{ type === 'bouten' && (
						<div className="mockup-bouten-style">
							<RadioGroupControl
								label="傍点の種類"
								description="傍点記法で使用するマークを選択します。"
								items={ [
									{
										label: '点',
										value: 'dot',
									},
									{
										label: 'ゴマ点',
										value: 'sesame',
									},
								] }
								value={ style }
								onValueChange={ ( value ) =>
									onStyleChange( String( value ) )
								}
							/>
						</div>
					) }
				</section>
			) ) }
		</section>
	);
}
