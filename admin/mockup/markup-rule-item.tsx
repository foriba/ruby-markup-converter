import { CheckboxControl } from '@wordpress/ui';
import type { Rule } from './sample-data';
import { RuleExample } from './rule-example';

/**
 * 一つの記法の選択、説明、入力例と表示例をまとめる。
 * @param root0
 * @param root0.rule
 * @param root0.enabled
 * @param root0.onChange
 * @param root0.style
 */
export function MarkupRuleItem( {
	rule,
	enabled,
	onChange,
	style,
}: {
	rule: Rule;
	enabled: boolean;
	onChange: ( checked: boolean ) => void;
	style: string;
} ) {
	return (
		<article className="mockup-rule">
			<CheckboxControl
				label={ rule.title }
				description={ rule.description }
				checked={ enabled }
				onCheckedChange={ onChange }
			/>
			<div className="mockup-examples">
				<div className="mockup-example-head" aria-hidden="true">
					<span>記法例</span>
					<span>表示例</span>
				</div>
				{ rule.examples.map( ( example, index ) => (
					<div className="mockup-example" key={ example }>
						<div>
							<span className="screen-reader-text">記法例：</span>
							<code>{ example }</code>
						</div>
						<div className="mockup-output">
							<span className="screen-reader-text">表示例：</span>
							<RuleExample
								rule={ rule }
								index={ index }
								style={ style }
							/>
						</div>
					</div>
				) ) }
			</div>
		</article>
	);
}
