import { createRoot, useEffect, useState } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { Page } from '@wordpress/admin-ui';
import apiFetch from '@wordpress/api-fetch';
import type { AdminConfig, PageData } from './types';
import { SETTINGS_PATH } from './settings-api';
import SettingsForm from './settings-form';
import './index.scss';

/**
 * 設定画面の初期データを取得し、フォームへ渡す。
 *
 * @param props        コンポーネントのプロパティ。
 * @param props.config PHPから渡された読み込み中・失敗時の画面文言。
 */
function App( { config }: { config: AdminConfig } ) {
	const [ data, setData ] = useState< PageData | null >( null );
	const [ failed, setFailed ] = useState( false );
	const [ attempt, setAttempt ] = useState( 0 );

	useEffect( () => {
		const controller = new AbortController();
		setFailed( false );
		apiFetch< PageData >( {
			path: SETTINGS_PATH,
			signal: controller.signal,
		} )
			.then( ( result ) => {
				if (
					! result ||
					! result.settings ||
					! result.definitions ||
					! result.messages
				) {
					throw new Error( 'Invalid settings response' );
				}
				setData( result );
			} )
			.catch( ( error ) => {
				if ( error.name !== 'AbortError' ) {
					setFailed( true );
				}
			} );
		return () => controller.abort();
	}, [ attempt ] );

	const messages = data ? data.messages : config.messages;
	return (
		<Page
			hasPadding
			showSidebarToggle={ false }
			title={ messages.page_title }
		>
			{ failed && (
				<Notice status="error" isDismissible={ false }>
					<p role="alert">{ messages.load_failed }</p>
					<p>
						<Button
							variant="secondary"
							onClick={ () => setAttempt( attempt + 1 ) }
						>
							{ messages.retry }
						</Button>
					</p>
				</Notice>
			) }
			{ ! failed && ! data && (
				<p role="status">
					<Spinner />
					{ messages.loading }
				</p>
			) }
			{ ! failed && data && <SettingsForm data={ data } /> }
		</Page>
	);
}

const container = document.getElementById( 'rubymaco-admin-root' );
if ( container && window.rubymacoAdmin ) {
	createRoot( container ).render( <App config={ window.rubymacoAdmin } /> );
}
