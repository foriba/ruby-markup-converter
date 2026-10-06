import { RadioControl } from '@wordpress/components';
import type { Choice } from './types';

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
export default function SettingChoices( {
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
		<RadioControl
			className="rubymaco-field"
			id={ name }
			label={ label }
			selected={ value }
			options={ choices }
			onChange={ onChange }
		/>
	);
}
