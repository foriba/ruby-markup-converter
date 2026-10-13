import type { Rule } from './sample-data';

/**
 * 固定例の表示のみを担当し、本体の変換処理には接続しない。
 * @param root0
 * @param root0.rule
 * @param root0.index
 * @param root0.style
 */
export function RuleExample( {
	rule,
	index,
	style,
}: {
	rule: Rule;
	index: number;
	style: string;
} ) {
	if ( rule.type === 'bouten' ) {
		return (
			<>
				この部分が
				<span
					style={ {
						textEmphasisStyle:
							style === 'sesame' ? 'filled sesame' : 'filled dot',
					} }
				>
					強調
				</span>
				されます。
			</>
		);
	}
	if ( rule.id === 'ruby_parenthesis' || index === 1 ) {
		return (
			<>
				それが
				<ruby>
					真実<rt>しんじつ</rt>
				</ruby>
				の
				<ruby>
					愛<rt>あい</rt>
				</ruby>
				です。
			</>
		);
	}
	return (
		<>
			それが
			<ruby>
				真実の愛<rt>トゥルーラブ</rt>
			</ruby>
			です。
		</>
	);
}
