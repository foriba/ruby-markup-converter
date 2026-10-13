import { SwitchControl, Text } from '@wordpress/ui';
type Props = { method: string; onChange: ( value: string ) => void };
// 互換用の描画方式を選択する。
export function CompatibilitySettings( { method, onChange }: Props ) {
	return (
		<section
			className="mockup-section"
			aria-labelledby="mockup-compatibility"
		>
			<Text
				variant="heading-lg"
				render={ <h2 id="mockup-compatibility" /> }
			>
				傍点の描画方法
			</Text>
			<SwitchControl
				label="カスタム描画を使用する（非推奨）"
				description="傍点の描画を独自方式でおこないます。通常はオフのまま使用してください。この方式は将来的に廃止する可能性があります。"
				checked={ method === 'custom' }
				onCheckedChange={ ( checked ) =>
					onChange( checked ? 'custom' : 'text_emphasis' )
				}
			/>
		</section>
	);
}
