import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { SettingsPage } from './settings-page';
import './index.scss';

// WordPress が用意した要素にモックアップを表示する。
domReady( () => {
	const container = document.getElementById( 'rubymaco-mockup-settings' );
	if ( container ) {
		createRoot( container ).render( <SettingsPage /> );
	}
} );
