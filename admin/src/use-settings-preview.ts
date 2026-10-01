import { useEffect, useState } from '@wordpress/element';
import type { AdminConfig, PageData, RulePreview, Settings } from './types';
import { fetchPreview } from './preview-api';

/**
 * 編集中の設定に対応するプレビューだけを返す。保存は行わない。
 *
 * @param config   接続情報。
 * @param settings 編集中の設定。
 * @param rules    PHP側の記法定義。
 * @return プレビュー状態と再試行処理。
 */
export default function useSettingsPreview(
	config: AdminConfig,
	settings: Settings,
	rules: PageData[ 'definitions' ][ 'markup_rules' ]
) {
	const key = JSON.stringify( settings );
	const [ attempt, setAttempt ] = useState( 0 );
	const [ result, setResult ] = useState< {
		key: string;
		attempt: number;
		previews: RulePreview[];
		failed: boolean;
	} | null >( null );
	useEffect( () => {
		const controller = new AbortController();
		let active = true;
		// 連続操作をまとめ、通信済みの古い結果も採用しない。
		const timer = setTimeout( () => {
			fetchPreview(
				config,
				JSON.parse( key ) as Settings,
				rules,
				controller.signal
			)
				.then( ( previews ) => {
					if ( active ) {
						setResult( { key, attempt, previews, failed: false } );
					}
				} )
				.catch( () => {
					if ( active ) {
						setResult( {
							key,
							attempt,
							previews: [],
							failed: true,
						} );
					}
				} );
		}, 300 );
		return () => {
			active = false;
			clearTimeout( timer );
			controller.abort();
		};
	}, [ config, key, rules, attempt ] );
	const current =
		result?.key === key && result.attempt === attempt ? result : null;
	return {
		previews: current?.previews ?? [],
		loading: current === null,
		failed: current?.failed ?? false,
		retry: () => setAttempt( ( value ) => value + 1 ),
	};
}
