/**
 * WhatsCom admin entry point.
 *
 * TODO v1.0: implement React-based settings/dashboard SPA.
 */
import { render } from '@wordpress/element';

const root = document.getElementById( 'whatscom-admin-root' );

if ( root ) {
	render(
		<div className="whatscom-admin">
			<p>{ 'WhatsCom admin UI — coming in v1.0.' }</p>
		</div>,
		root
	);
}
