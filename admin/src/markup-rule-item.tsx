import { CheckboxControl } from '@wordpress/ui';
import type { PageData, RulePreview } from './types';
import RuleExample from './rule-example';
type Props = {
	rule: PageData[ 'definitions' ][ 'markup_rules' ][ number ];
	enabled: boolean;
	disabled: boolean;
	preview?: RulePreview;
	messages: Record< string, string >;
	failed: boolean;
	onChange: ( checked: boolean ) => void;
};
// 一つの記法の選択、説明、入力例と変換後の表示をまとめる。
export default function MarkupRuleItem( {
	rule,
	enabled,
	disabled,
	preview,
	messages,
	failed,
	onChange,
}: Props ) {
	return (
		<article className="rubymaco-rule">
			<CheckboxControl
				label={ rule.titles.join( '\n' ) }
				description={ rule.description }
				checked={ enabled }
				disabled={ disabled }
				onCheckedChange={ onChange }
			/>
			<div className="rubymaco-examples">
				<div className="rubymaco-example-head" aria-hidden="true">
					<span>{ messages.markup_example }</span>
					<span>{ messages.output_result }</span>
				</div>
				{ rule.examples.map( ( example, index ) => (
					<div className="rubymaco-example" key={ index }>
						<div>
							<span className="rubymaco-example-label">
								{ messages.markup_example }
							</span>
							<code>{ example }</code>
						</div>
						<div className="rubymaco-output">
							<span className="rubymaco-example-label">
								{ messages.output_result }
							</span>
							<RuleExample
								html={ preview?.examples[ index ]?.html }
								messages={ messages }
								failed={ failed }
							/>
						</div>
					</div>
				) ) }
			</div>
		</article>
	);
}
