import apiFetch from '@wordpress/api-fetch';
import type { PageData, RulePreview, Settings } from './types';

/**
 * 固定例文の変換結果を取得し、記法IDと例文の対応を検証する。
 *
 * @param settings 編集中の設定。
 * @param rules    PHP側の記法定義。
 * @param signal   設定変更時の中止通知。
 * @return 検証済みのプレビュー一覧。
 */
export async function fetchPreview(
	settings: Settings,
	rules: PageData[ 'definitions' ][ 'markup_rules' ],
	signal: AbortSignal
): Promise< RulePreview[] > {
	const controller = new AbortController();
	const abort = () => controller.abort();
	signal.addEventListener( 'abort', abort, { once: true } );
	if ( signal.aborted ) {
		controller.abort();
	}
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- タイマーはfinallyで解除する。
	const timeout = setTimeout( abort, 30000 );
	try {
		const body: unknown = await apiFetch( {
			path: '/rubymaco/v1/settings/preview',
			method: 'POST',
			data: settings,
			signal: controller.signal,
		} );
		if (
			! body ||
			typeof body !== 'object' ||
			! ( 'previews' in body ) ||
			! Array.isArray( body.previews ) ||
			body.previews.length !== rules.length
		) {
			throw new Error( 'Invalid preview response' );
		}
		return body.previews.map( ( preview: unknown, index ): RulePreview => {
			const rule = rules[ index ];
			if (
				! preview ||
				typeof preview !== 'object' ||
				! ( 'id' in preview ) ||
				preview.id !== rule.id ||
				! ( 'examples' in preview ) ||
				! Array.isArray( preview.examples ) ||
				preview.examples.length !== rule.examples.length
			) {
				throw new Error( 'Invalid preview rule' );
			}
			const examples = preview.examples.map(
				( example: unknown, position ) => {
					if (
						! example ||
						typeof example !== 'object' ||
						! ( 'text' in example ) ||
						example.text !== rule.examples[ position ] ||
						! ( 'html' in example ) ||
						typeof example.html !== 'string'
					) {
						throw new Error( 'Invalid preview example' );
					}
					return {
						text: rule.examples[ position ],
						html: example.html,
					};
				}
			);
			return { id: rule.id, examples };
		} );
	} finally {
		clearTimeout( timeout );
		signal.removeEventListener( 'abort', abort );
	}
}
