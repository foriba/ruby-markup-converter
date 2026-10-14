import { RadioGroupControl, Text } from '@wordpress/ui';
import type { Choice } from './types';
type Props = {
	choices: Choice[];
	value: string;
	label: string;
	disabled: boolean;
	onChange: ( value: string ) => void;
};
// 適用範囲の表示定義と現在値を受け取り、変更を親へ通知する。
export default function ConversionScope( {
	choices,
	value,
	label,
	disabled,
	onChange,
}: Props ) {
	return (
		<section className="rubymaco-section" aria-labelledby="rubymaco-scope">
			<Text variant="heading-xl" render={ <h2 id="rubymaco-scope" /> }>
				{ label }
			</Text>
			<RadioGroupControl
				label={ label }
				hideLabelFromVision
				items={ choices }
				value={ value }
				disabled={ disabled }
				onValueChange={ ( next ) => onChange( String( next ) ) }
			/>
		</section>
	);
}
