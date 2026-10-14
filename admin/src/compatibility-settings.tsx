import { SwitchControl, Text } from '@wordpress/ui';
type Props = {
	value: string;
	messages: Record< string, string >;
	disabled: boolean;
	onChange: ( value: string ) => void;
};
// 保存値は従来どおり維持し、互換方式の有効・無効として表示する。
export default function CompatibilitySettings( {
	value,
	messages,
	disabled,
	onChange,
}: Props ) {
	return (
		<section
			className="rubymaco-section"
			aria-labelledby="rubymaco-compatibility"
		>
			<Text
				variant="heading-lg"
				render={ <h2 id="rubymaco-compatibility" /> }
			>
				{ messages.bouten_rendering_method }
			</Text>
			<SwitchControl
				label={ messages.custom_rendering_label }
				description={ messages.custom_rendering_description }
				checked={ value === 'custom' }
				disabled={ disabled }
				onCheckedChange={ ( checked ) =>
					onChange( checked ? 'custom' : 'text_emphasis' )
				}
			/>
		</section>
	);
}
