import { RadioGroupControl, Text } from '@wordpress/ui';
type Props = { scope: string; onChange: ( value: string ) => void };
// 適用範囲を表示し、選択結果を親ページへ通知する。
export function ConversionScope( { scope, onChange }: Props ) {
	return (
		<section className="mockup-section" aria-labelledby="mockup-scope">
			<Text variant="heading-xl" render={ <h2 /> }>
				適用範囲
			</Text>
			<RadioGroupControl
				label="適用範囲"
				hideLabelFromVision
				items={ [
					{
						value: 'shortcode',
						label: '指定した範囲内のみ適用',
						description:
							'Ruby Markup Converter ブロック内の記法を変換します。手動で記述した [rubymaco]…[/rubymaco] ショートコード内も対象です。',
					},
					{
						value: 'all',
						label: '投稿本文全体に適用',
						description:
							'ブロックやショートコード内も含め、投稿本文全体を変換します。意図しないテキストが変換されたり、長い投稿では処理に時間がかかったりする場合があります。',
					},
				] }
				value={ scope }
				onValueChange={ ( value ) => onChange( String( value ) ) }
			/>
		</section>
	);
}
