import type { PageData } from './types';

/**
 * 記法の選択欄・説明・例文を表示する。編集値は保持せず、変更を親へ通知する。
 *
 * @param props                コンポーネントのプロパティ。
 * @param props.rules          表示対象の記法。
 * @param props.enabledRuleIds 選択中の記法ID。
 * @param props.exampleLabel   例文一覧のラベル。
 * @param props.onChange       選択変更時の通知。
 */
export default function MarkupRuleFields( {
	rules,
	enabledRuleIds,
	exampleLabel,
	onChange,
}: {
	rules: PageData[ 'definitions' ][ 'markup_rules' ];
	enabledRuleIds: string[];
	exampleLabel: string;
	onChange: ( id: string, checked: boolean ) => void;
} ) {
	return (
		<>
			{ rules.map( ( rule ) => (
				<div className="rubymaco-rule" key={ rule.id }>
					<label htmlFor={ 'rubymaco-rule-' + rule.id }>
						<input
							id={ 'rubymaco-rule-' + rule.id }
							type="checkbox"
							checked={ enabledRuleIds.includes( rule.id ) }
							aria-describedby={ 'rubymaco-rule-help-' + rule.id }
							onChange={ ( event ) =>
								onChange( rule.id, event.target.checked )
							}
						/>
						{ rule.titles.join( ' / ' ) }
					</label>
					<p
						className="description"
						id={ 'rubymaco-rule-help-' + rule.id }
					>
						{ rule.description }
					</p>
					<ul aria-label={ exampleLabel }>
						{ rule.examples.map( ( example, index ) => (
							<li key={ index }>
								<code>{ example }</code>
							</li>
						) ) }
					</ul>
				</div>
			) ) }
		</>
	);
}
