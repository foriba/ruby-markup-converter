import { useEffect, useState } from '@wordpress/element';
import type { PageData, RulePreview, Settings } from './types';
import { fetchPreview } from './preview-api';

type Variant = { key: string; previews: RulePreview[]; failed: boolean };

/**
 * 描画方式ごとの例文を初回に取得し、選択時は画面内のキャッシュを切り替える。
 *
 * @param settings    編集中の設定。
 * @param definitions PHP側の記法・描画設定の定義。
 * @return プレビュー状態と再試行処理。
 */
export default function useSettingsPreview(
	settings: Settings,
	definitions: PageData[ 'definitions' ]
) {
	const [ attempt, setAttempt ] = useState( 0 );
	const [ result, setResult ] = useState< {
		definitions: PageData[ 'definitions' ];
		attempt: number;
		variants: Variant[];
	} | null >( null );
	useEffect( () => {
		const controller = new AbortController();
		let active = true;
		const rules = definitions.markup_rules;
		// 選択状態に依存しない全組み合わせを先読みする。保存APIは呼び出さない。
		const requests = definitions.bouten_rendering_methods.flatMap(
			( method ) =>
				definitions.bouten_styles.map(
					async ( style ): Promise< Variant > => {
						const key = JSON.stringify( [
							style.value,
							method.value,
						] );
						try {
							const previews = await fetchPreview(
								{
									apply_mode: 'shortcode',
									enabled_rule_ids: rules.map(
										( rule ) => rule.id
									),
									bouten_style: style.value,
									bouten_rendering_method: method.value,
								},
								rules,
								controller.signal
							);
							return { key, previews, failed: false };
						} catch {
							return { key, previews: [], failed: true };
						}
					}
				)
		);
		void Promise.all( requests ).then( ( variants ) => {
			if ( ! active ) {
				return;
			}
			// ルビはすべての組み合わせで共通。同じオブジェクトを再利用する。
			const common =
				variants.find( ( variant ) => ! variant.failed )?.previews ??
				[];
			const ruby = common.filter( ( preview ) =>
				rules.some(
					( rule ) => rule.id === preview.id && rule.type === 'ruby'
				)
			);
			setResult( {
				definitions,
				attempt,
				variants: variants.map( ( variant ) => ( {
					...variant,
					previews: rules.flatMap( ( rule ) => {
						const preview = (
							rule.type === 'ruby' ? ruby : variant.previews
						).find( ( item ) => item.id === rule.id );
						return preview ? [ preview ] : [];
					} ),
				} ) ),
			} );
		} );
		return () => {
			active = false;
			controller.abort();
		};
	}, [ definitions, attempt ] );

	const current =
		result?.definitions === definitions && result.attempt === attempt
			? result
			: null;
	const selected = current?.variants.find(
		( variant ) =>
			variant.key ===
			JSON.stringify( [
				settings.bouten_style,
				settings.bouten_rendering_method,
			] )
	);
	return {
		previews: selected?.previews ?? [],
		loading: current === null,
		failed: current !== null && ( ! selected || selected.failed ),
		retry: () => setAttempt( ( value ) => value + 1 ),
	};
}
