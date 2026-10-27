import apiFetch from '@wordpress/api-fetch';
import type { PageData, Settings } from './types';

/** 設定の取得・保存 API のパス。URL・nonce・ユーザー言語は apiFetch に任せる。 */
export const SETTINGS_PATH = '/rubymaco/v1/settings';

/**
 * 4項目の設定を保存する。成功応答の形式が不正な場合も失敗として扱う。
 *
 * @param settings    編集中の設定。
 * @param definitions PHPから取得した選択肢の定義。
 * @return サーバーで保存を確認した設定。
 */
export async function saveSettings(
	settings: Settings,
	definitions: PageData[ 'definitions' ]
): Promise< Settings > {
	const controller = new AbortController();
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return -- タイマーは必ずfinallyで解除する。
	const timeout = setTimeout( () => controller.abort(), 30000 );
	try {
		const body: unknown = await apiFetch( {
			path: SETTINGS_PATH,
			method: 'POST',
			data: settings,
			signal: controller.signal,
		} );
		if ( ! body || typeof body !== 'object' || ! ( 'settings' in body ) ) {
			throw new Error( 'Invalid save response' );
		}
		const result = body.settings;
		if (
			! result ||
			typeof result !== 'object' ||
			! ( 'apply_mode' in result ) ||
			! definitions.apply_modes.some(
				( choice ) => choice.value === result.apply_mode
			) ||
			! ( 'bouten_style' in result ) ||
			! definitions.bouten_styles.some(
				( choice ) => choice.value === result.bouten_style
			) ||
			! ( 'bouten_rendering_method' in result ) ||
			! definitions.bouten_rendering_methods.some(
				( choice ) => choice.value === result.bouten_rendering_method
			) ||
			! ( 'enabled_rule_ids' in result ) ||
			! Array.isArray( result.enabled_rule_ids ) ||
			! result.enabled_rule_ids.every(
				( id: unknown ) =>
					typeof id === 'string' &&
					definitions.markup_rules.some( ( rule ) => rule.id === id )
			)
		) {
			throw new Error( 'Invalid saved settings' );
		}
		return result as Settings;
	} finally {
		clearTimeout( timeout );
	}
}
