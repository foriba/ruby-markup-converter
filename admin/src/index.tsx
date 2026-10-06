import { createRoot, useEffect, useState } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import type { AdminConfig, PageData } from './types';
import SettingsForm from './settings-form';
import './index.scss';

/**
 * 設定画面の初期データを取得し、接続情報とともにフォームへ渡す。
 *
 * @param props        コンポーネントのプロパティ。
 * @param props.config PHPから渡された接続情報と画面文言。
 */
function App( { config }: { config: AdminConfig } ) {
	const [ data, setData ] = useState< PageData | null >( null );
	const [ failed, setFailed ] = useState( false );
	const [ attempt, setAttempt ] = useState( 0 );

	useEffect( () => {
		const controller = new AbortController();
		setFailed( false );
		fetch( config.url, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': config.nonce },
			signal: controller.signal,
		} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'Request failed' );
				}
				return response.json() as Promise< PageData >;
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
	}, [ config.url, config.nonce, attempt ] );

	const messages = data ? data.messages : config.messages;
	return (
		<>
			<h1>{ messages.page_title }</h1>
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
			{ ! failed && data && (
				<SettingsForm data={ data } config={ config } />
			) }
		</>
	);
}

const container = document.getElementById( 'rubymaco-admin-root' );
if ( container && window.rubymacoAdmin ) {
	createRoot( container ).render( <App config={ window.rubymacoAdmin } /> );
}
