( function () {
	'use strict';

	var SUPPORTER_URL  = 'https://cartpinger.com/supporter';

	function api() {
		return {
			url:   ( window.cartpingerAdmin && ( window.cartpingerAdmin.apiUrl || window.cartpingerAdmin.restUrl ) ) || '/wp-json/cartpinger/v1/',
			nonce: ( window.cartpingerAdmin && window.cartpingerAdmin.nonce ) || '',
		};
	}

	function fetchJson( path, opts ) {
		var a = api();
		opts = opts || {};
		opts.headers = Object.assign( { 'X-WP-Nonce': a.nonce }, opts.headers || {} );
		if ( opts.body && typeof opts.body !== 'string' ) {
			opts.headers[ 'Content-Type' ] = 'application/json';
			opts.body = JSON.stringify( opts.body );
		}
		return fetch( a.url + path, opts ).then( function ( r ) { return r.json(); } );
	}

	function el( html ) {
		var div = document.createElement( 'div' );
		div.innerHTML = html.trim();
		return div.firstChild;
	}

	function setStatus( node, msg, kind ) {
		if ( ! node ) { return; }
		node.textContent = msg;
		node.className = 'cp-status-msg ' + ( kind || '' );
	}

	/* =================== DASHBOARD =================== */

	function renderDashboard() {
		var mount = document.getElementById( 'cartpinger-dashboard-app' );
		if ( ! mount ) { return; }

		mount.innerHTML = '<p>Loading dashboard…</p>';

		Promise.all( [
			fetchJson( 'stats' ),
			fetchJson( 'settings' ).catch( function () { return { is_configured: false }; } ),
		] ).then( function ( results ) {
			var stats      = results[0] || {};
			var settings   = results[1] || {};
			var configured = !! settings.is_configured;

			var html = '<div class="cp-wrap">';
			html += '<h1>CartPinger Dashboard</h1>';
			html += '<p class="cp-subtitle">WhatsApp commerce for your WooCommerce store.</p>';

			if ( ! configured ) {
				html += '<div class="cp-info-box warning"><p><strong>Setup incomplete.</strong> ' +
					'<a href="admin.php?page=cartpinger-setup">Complete the setup wizard</a> to start sending WhatsApp messages.</p></div>';
			}

			html += '<div class="cp-kpi-grid">' +
				kpi( 'Abandoned carts tracked', stats.total_carts, '', true ) +
				kpi( 'Recovered', stats.recovered, '', false ) +
				kpi( 'Conversion rate', ( stats.conversion_rate || 0 ) + '%', '', false ) +
				kpi( 'Messages delivered', stats.delivered, '', false ) +
				kpi( 'Messages read', stats.read, '', false ) +
			'</div>';

			html += '<div class="cp-card">' +
				'<h3>Quick actions</h3>' +
				'<p><a class="cp-btn cp-btn-primary" href="admin.php?page=cartpinger-setup">Setup Wizard</a> ' +
				'<a class="cp-btn cp-btn-secondary" href="admin.php?page=cartpinger-templates">View Templates</a> ' +
				'<a class="cp-btn cp-btn-secondary" href="admin.php?page=cartpinger-settings">Settings</a></p>' +
			'</div>';

			html += '</div>';
			mount.innerHTML = html;
		} ).catch( function () {
			mount.innerHTML = '<p>Could not load dashboard.</p>';
		} );
	}

	function kpi( label, value, meta, accent ) {
		return '<div class="cp-kpi ' + ( accent ? 'cp-kpi-accent' : '' ) + '">' +
			'<div class="cp-kpi-label">' + label + '</div>' +
			'<div class="cp-kpi-value">' + ( value != null ? value : 0 ) + '</div>' +
			( meta ? '<div class="cp-kpi-meta">' + meta + '</div>' : '' ) +
		'</div>';
	}

	/* =================== SETTINGS =================== */

	function renderSettings() {
		var mount = document.getElementById( 'cartpinger-settings-app' );
		if ( ! mount ) { return; }

		mount.innerHTML = '<p>Loading settings…</p>';

		Promise.all( [
			fetchJson( 'settings' ),
			fetchJson( 'license' ).catch( function () { return { is_pro: false, license_key: '' }; } ),
		] ).then( function ( results ) {
			var d   = results[0] || {};
			var lic = results[1] || {};

			var html = '<div class="cp-wrap">';
			html += '<h1>Settings</h1>';
			html += '<p class="cp-subtitle">Configure your WhatsApp Cloud API credentials, cart recovery behavior, and chat widget.</p>';

			html += '<div class="cp-tabs">' +
				'<button class="cp-tab active" data-tab="api">WhatsApp API</button>' +
				'<button class="cp-tab" data-tab="recovery">Cart Recovery</button>' +
				'<button class="cp-tab" data-tab="widget">Chat Widget</button>' +
				'<button class="cp-tab" data-tab="license">Supporter</button>' +
				'<button class="cp-tab" data-tab="advanced">Advanced</button>' +
				'</div>';

			// Tab: API
			html += '<div class="cp-tab-panel" data-panel="api">' +
				'<div class="cp-card">' +
					'<h3>WhatsApp Cloud API Credentials</h3>' +
					'<p style="color:#646970;font-size:13px;">Get these values from your Meta for Developers app. <a href="admin.php?page=cartpinger-setup" >Open the Setup Wizard</a> if you need help.</p>' +
					field( 'phone_number_id', 'Phone Number ID', d.phone_number_id, 'text', 'The numeric ID of your WhatsApp Business phone number.' ) +
					field( 'waba_id', 'WhatsApp Business Account ID', d.waba_id, 'text', 'Your WABA (WhatsApp Business Account) numeric ID.' ) +
					field( 'access_token', 'Access Token', d.access_token, 'password', 'Permanent System User token from Meta. Generate a long-lived token for production.' ) +
					field( 'app_secret', 'App Secret', d.app_secret, 'password', 'Found under Meta App → Settings → Basic → App Secret.' ) +
					field( 'verify_token', 'Webhook Verify Token', d.verify_token, 'text', 'Any random string you choose. You will paste it into Meta when configuring the webhook.' ) +
				'</div>' +
			'</div>';

			// Tab: Recovery
			html += '<div class="cp-tab-panel" data-panel="recovery" style="display:none;">' +
				'<div class="cp-card">' +
					'<h3>Abandoned Cart Recovery</h3>' +
					'<p style="color:#646970;font-size:13px;">The first recovery message is always sent 1 hour after a cart is abandoned (if the customer ticked the WhatsApp consent box at checkout). The settings below control optional follow-up messages.</p>' +
					checkbox( 'enable_followups', 'Send follow-up messages at +24h and +48h', d.enable_followups ) +
					checkbox( 'enable_auto_coupon', 'Generate a 10% WooCommerce discount coupon and include it in the +24h follow-up', d.enable_auto_coupon ) +
					'<p style="color:#646970;font-size:12px;margin-top:12px;">Follow-up messages use the templates <code>abandoned_cart_recovery_24h</code> (or <code>abandoned_cart_recovery_24h_no_coupon</code>) and <code>abandoned_cart_recovery_48h</code>. Make sure these are approved in Meta Business Manager — see <a href="admin.php?page=cartpinger-templates">Templates</a>.</p>' +
				'</div>' +
			'</div>';

			// Tab: Widget
			html += '<div class="cp-tab-panel" data-panel="widget" style="display:none;">' +
				'<div class="cp-card">' +
					'<h3>Floating Chat Widget</h3>' +
					'<p style="color:#646970;font-size:13px;">Show a WhatsApp button on every page that opens a chat with your support number.</p>' +
					checkbox( 'widget_enabled', 'Show the WhatsApp chat widget on the storefront', d.widget_enabled ) +
					field( 'support_phone', 'Support phone number', d.support_phone, 'text', 'E.164 format (e.g. +34612345678). This is the number customers will message.' ) +
					field( 'widget_message', 'Pre-filled message', d.widget_message, 'text', 'Optional. Text customers will see pre-filled when they open WhatsApp.' ) +
				'</div>' +
			'</div>';

			// Tab: License
			html += '<div class="cp-tab-panel" data-panel="license" style="display:none;">' +
				'<div class="cp-card">' +
					'<h3>Supporter License' + ( lic.is_pro ? ' <span class="cp-pro-badge">ACTIVE</span>' : '' ) + '</h3>' +
					'<p style="color:#646970;font-size:13px;">CartPinger is free and fully functional. If you want to support the project, you can buy a Supporter license at <a href="' + SUPPORTER_URL + '" target="_blank" rel="noopener">cartpinger.com/supporter</a>. It currently unlocks an optional companion add-on with advanced reporting and priority support — installed separately.</p>' +
					( lic.is_pro
						? '<p>Supporter license active. Key: <code>' + ( lic.license_key || '' ) + '</code></p>' +
						  '<p style="color:#646970;font-size:12px;">' + ( lic.seconds_since_check != null
								? 'Last verified ' + humanAgo( lic.seconds_since_check ) + ' ago.'
								: 'Not yet verified — the daily check will run shortly.' ) + '</p>' +
						  '<p><button class="cp-btn cp-btn-secondary" id="cp-license-validate">Verify now</button> ' +
						  '<button class="cp-btn cp-btn-secondary" id="cp-license-deactivate">Deactivate</button> ' +
						  '<span class="cp-status-msg" id="cp-license-status"></span></p>'
						: ( lic.last_fail_reason
								? '<div class="cp-info-box warning"><p>Your previous license check failed: <em>' + escapeHtml( lic.last_fail_reason ) + '</em></p></div>'
								: '' ) +
						  field( 'license_key_input', 'License key', '', 'text', 'Optional. Paste the license key emailed after purchase at cartpinger.com.' ) +
						  '<p><button class="cp-btn cp-btn-primary" id="cp-license-activate">Activate</button> <span class="cp-status-msg" id="cp-license-status"></span></p>'
					) +
				'</div>' +
			'</div>';

			// Tab: Advanced
			html += '<div class="cp-tab-panel" data-panel="advanced" style="display:none;">' +
				'<div class="cp-card">' +
					'<h3>Advanced Options</h3>' +
					checkbox( 'delete_data_on_uninstall', 'Delete all CartPinger data when the plugin is uninstalled', d.delete_data_on_uninstall ) +
				'</div>' +
			'</div>';

			// Sticky save
			html += '<div style="margin-top:24px;">' +
				'<button class="cp-btn cp-btn-primary" id="cp-save-settings">Save Settings</button>' +
				'<span class="cp-status-msg" id="cp-save-status"></span>' +
			'</div>';

			html += '</div>';
			mount.innerHTML = html;

			// Tab switching
			mount.querySelectorAll( '.cp-tab' ).forEach( function ( tab ) {
				tab.addEventListener( 'click', function () {
					mount.querySelectorAll( '.cp-tab' ).forEach( function ( t ) { t.classList.remove( 'active' ); } );
					mount.querySelectorAll( '.cp-tab-panel' ).forEach( function ( p ) { p.style.display = 'none'; } );
					tab.classList.add( 'active' );
					var panel = mount.querySelector( '.cp-tab-panel[data-panel="' + tab.dataset.tab + '"]' );
					if ( panel ) { panel.style.display = 'block'; }
				} );
			} );

			// Save settings
			document.getElementById( 'cp-save-settings' ).addEventListener( 'click', function () {
				var status = document.getElementById( 'cp-save-status' );
				setStatus( status, 'Saving…', 'loading' );

				fetchJson( 'settings', {
					method: 'POST',
					body: {
						phone_number_id:          fval( 'phone_number_id' ),
						waba_id:                  fval( 'waba_id' ),
						access_token:             fval( 'access_token' ),
						app_secret:               fval( 'app_secret' ),
						verify_token:             fval( 'verify_token' ),
						support_phone:            fval( 'support_phone' ),
						widget_message:           fval( 'widget_message' ),
						widget_enabled:           cval( 'widget_enabled' ),
						enable_followups:         cval( 'enable_followups' ),
						enable_auto_coupon:       cval( 'enable_auto_coupon' ),
						delete_data_on_uninstall: cval( 'delete_data_on_uninstall' ),
					},
				} )
				.then( function ( r ) { setStatus( status, r.message || 'Saved!', 'ok' ); } )
				.catch( function () { setStatus( status, 'Error saving.', 'err' ); } );
			} );

			// License activate / deactivate
			var act = document.getElementById( 'cp-license-activate' );
			if ( act ) {
				act.addEventListener( 'click', function () {
					var status = document.getElementById( 'cp-license-status' );
					setStatus( status, 'Activating…', 'loading' );
					fetchJson( 'license', {
						method: 'POST',
						body: { license_key: fval( 'license_key_input' ) },
					} )
					.then( function ( r ) {
						if ( r.success ) {
							setStatus( status, 'License activated! Reloading…', 'ok' );
							setTimeout( function () { window.location.reload(); }, 800 );
						} else {
							setStatus( status, r.message || 'Activation failed.', 'err' );
						}
					} )
					.catch( function () { setStatus( status, 'Error.', 'err' ); } );
				} );
			}
			var validateBtn = document.getElementById( 'cp-license-validate' );
			if ( validateBtn ) {
				validateBtn.addEventListener( 'click', function () {
					var status = document.getElementById( 'cp-license-status' );
					setStatus( status, 'Verifying…', 'loading' );
					fetchJson( 'license/validate', { method: 'POST' } )
					.then( function ( r ) {
						setStatus( status, r.message || 'Verified.', r.success ? 'ok' : 'err' );
						setTimeout( function () { window.location.reload(); }, 1200 );
					} )
					.catch( function () { setStatus( status, 'Error.', 'err' ); } );
				} );
			}

			var deact = document.getElementById( 'cp-license-deactivate' );
			if ( deact ) {
				deact.addEventListener( 'click', function () {
					if ( ! window.confirm( 'Deactivate the Pro license on this site?' ) ) { return; }
					var status = document.getElementById( 'cp-license-status' );
					setStatus( status, 'Deactivating…', 'loading' );
					fetchJson( 'license', { method: 'DELETE' } )
					.then( function () { window.location.reload(); } )
					.catch( function () { setStatus( status, 'Error.', 'err' ); } );
				} );
			}
		} ).catch( function () {
			mount.innerHTML = '<p>Could not load settings.</p>';
		} );
	}

	function field( name, label, value, type, help ) {
		return '<div class="cp-field">' +
			'<label for="cp-' + name + '">' + label + '</label>' +
			'<input type="' + type + '" id="cp-' + name + '" value="' + escapeHtml( value || '' ) + '" autocomplete="off" />' +
			( help ? '<div class="cp-help">' + help + '</div>' : '' ) +
		'</div>';
	}

	function checkbox( name, label, checked ) {
		return '<div class="cp-field">' +
			'<label><input type="checkbox" id="cp-' + name + '"' + ( checked ? ' checked' : '' ) + ' /> ' + label + '</label>' +
		'</div>';
	}

	function fval( name ) {
		var node = document.getElementById( 'cp-' + name );
		return node ? node.value.trim() : '';
	}
	function cval( name ) {
		var node = document.getElementById( 'cp-' + name );
		return node ? !! node.checked : false;
	}
	function humanAgo( seconds ) {
		seconds = Math.max( 0, parseInt( seconds, 10 ) || 0 );
		if ( seconds < 60 ) { return seconds + 's'; }
		if ( seconds < 3600 ) { return Math.floor( seconds / 60 ) + 'm'; }
		if ( seconds < 86400 ) { return Math.floor( seconds / 3600 ) + 'h'; }
		return Math.floor( seconds / 86400 ) + 'd';
	}
	function escapeHtml( str ) {
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* =================== WIZARD STEP 5 (legacy IDs) =================== */

	function wireWizardButtons() {
		var saveBtn = document.getElementById( 'cartpinger-save-settings' );
		var testBtn = document.getElementById( 'cartpinger-send-test' );
		if ( ! saveBtn && ! testBtn ) { return; }

		if ( saveBtn ) {
			saveBtn.addEventListener( 'click', function () {
				var statusEl = document.getElementById( 'cartpinger-save-status' );
				statusEl.textContent = 'Saving…';
				statusEl.style.color = '#646970';

				fetchJson( 'settings', {
					method: 'POST',
					body: {
						phone_number_id: ( document.getElementById( 'cartpinger-phone-id' ) || {} ).value || '',
						waba_id:         ( document.getElementById( 'cartpinger-waba-id' ) || {} ).value || '',
						verify_token:    ( document.getElementById( 'cartpinger-verify-token' ) || {} ).value || '',
						access_token:    ( document.getElementById( 'cartpinger-access-token' ) || {} ).value || '',
						app_secret:      ( document.getElementById( 'cartpinger-app-secret' ) || {} ).value || '',
					},
				} )
				.then( function ( r ) { statusEl.textContent = r.message || 'Saved!'; statusEl.style.color = '#00a32a'; } )
				.catch( function () { statusEl.textContent = 'Error saving.'; statusEl.style.color = '#d63638'; } );
			} );
		}

		if ( testBtn ) {
			testBtn.addEventListener( 'click', function () {
				var statusEl = document.getElementById( 'cartpinger-test-status' );
				statusEl.textContent = 'Sending…';
				statusEl.style.color = '#646970';

				fetchJson( 'test-message', {
					method: 'POST',
					body: { phone: ( document.getElementById( 'cartpinger-test-phone' ) || {} ).value || '' },
				} )
				.then( function ( r ) {
					var ok = r.message && r.message.indexOf( 'sent' ) !== -1;
					statusEl.textContent = r.message || 'Done.';
					statusEl.style.color = ok ? '#00a32a' : '#d63638';
				} )
				.catch( function () { statusEl.textContent = 'Error.'; statusEl.style.color = '#d63638'; } );
			} );
		}
	}

	/* =================== TEMPLATES COPY BUTTONS =================== */

	function wireTemplateCopy() {
		document.querySelectorAll( '.cp-copy-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var target = btn.dataset.target;
				if ( ! target ) { return; }
				var node = document.getElementById( target );
				if ( ! node ) { return; }
				var text = node.textContent;
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( text ).then( function () {
						btn.classList.add( 'copied' );
						var old = btn.textContent;
						btn.textContent = 'Copied!';
						setTimeout( function () { btn.classList.remove( 'copied' ); btn.textContent = old; }, 1500 );
					} );
				}
			} );
		} );

		document.querySelectorAll( '.cp-lang-tab' ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var card = tab.closest( '.cp-template-card' );
				if ( ! card ) { return; }
				card.querySelectorAll( '.cp-lang-tab' ).forEach( function ( t ) { t.classList.remove( 'active' ); } );
				tab.classList.add( 'active' );
				card.querySelectorAll( '[data-lang-body]' ).forEach( function ( b ) {
					b.style.display = b.dataset.langBody === tab.dataset.lang ? 'block' : 'none';
				} );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		renderDashboard();
		renderSettings();
		wireWizardButtons();
		wireTemplateCopy();
	} );
}() );
