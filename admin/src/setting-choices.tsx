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
