import { createRoot, useEffect, useState } from '@wordpress/element';
import type { AdminConfig, PageData } from './types';
import './index.scss';

/**
 * Read-only startup screen. Saving and preview controls follow in the next step.
 *
 * @param props        Component properties.
 * @param props.config PHP-provided connection settings and messages.
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
				<div className="notice notice-error">
					<p role="alert">{ messages.load_failed }</p>
					<p>
						<button
							className="button"
							onClick={ () => setAttempt( attempt + 1 ) }
						>
							{ messages.retry }
						</button>
					</p>
				</div>
			) }
			{ ! failed && ! data && <p role="status">{ messages.loading }</p> }
			{ ! failed && data && (
				<dl className="rubymaco-admin-summary">
					{ (
						[
							[ 'apply_mode', 'apply_modes', 'conversion_scope' ],
							[ 'bouten_style', 'bouten_styles', 'bouten_style' ],
							[
								'bouten_rendering_method',
								'bouten_rendering_methods',
								'bouten_rendering_method',
							],
						] as const
					 ).map( ( [ setting, definition, label ] ) => (
						<div key={ setting }>
							<dt>{ messages[ label ] }</dt>
							<dd>
								{
									data.definitions[ definition ].find(
										( choice ) =>
											choice.value ===
											data.settings[ setting ]
									)?.label
								}
							</dd>
						</div>
					) ) }
				</dl>
			) }
		</>
	);
}

const container = document.getElementById( 'rubymaco-admin-root' );
if ( container && window.rubymacoAdmin ) {
	createRoot( container ).render( <App config={ window.rubymacoAdmin } /> );
}
