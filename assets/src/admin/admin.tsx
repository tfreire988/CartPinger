/**
 * CartPinger admin entry point.
 *
 * Mounts StatsView on #cartpinger-dashboard-app (Dashboard page) and
 * SettingsView on #cartpinger-settings-app (Settings page).
 */
import { render, useState, useEffect } from '@wordpress/element';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

declare const cartpingerAdmin: {
	apiUrl: string;
	nonce: string;
	view: 'dashboard' | 'settings';
};

interface StatsData {
	total_carts: number;
	recovered: number;
	conversion_rate: number;
	delivered: number;
	read: number;
}

interface SettingsData {
	phone_number_id: string;
	waba_id: string;
	verify_token: string;
	access_token: string;
	app_secret: string;
	delete_data_on_uninstall: boolean;
	widget_enabled: boolean;
	support_phone: string;
	widget_message: string;
	is_configured: boolean;
}

// ---------------------------------------------------------------------------
// Fetch helpers
// ---------------------------------------------------------------------------

async function apiFetch<T>( path: string, options?: RequestInit ): Promise<T> {
	const res = await fetch( cartpingerAdmin.apiUrl + path, {
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': cartpingerAdmin.nonce,
		},
		...options,
	} );

	const body = await res.json() as T & { message?: string };

	if ( ! res.ok ) {
		throw new Error(
			( body as { message?: string } ).message ?? `HTTP ${ res.status }`
		);
	}

	return body;
}

// ---------------------------------------------------------------------------
// StatsView
// ---------------------------------------------------------------------------

function KpiCard( { label, value }: { label: string; value: string } ) {
	return (
		<div className="cartpinger-kpi-card">
			<span className="cartpinger-kpi-card__value">{ value }</span>
			<span className="cartpinger-kpi-card__label">{ label }</span>
		</div>
	);
}

function StatsView() {
	const [ stats, setStats ] = useState<StatsData | null>( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );

	const load = async () => {
		setLoading( true );
		setError( '' );
		try {
			const data = await apiFetch<StatsData>( 'stats' );
			setStats( data );
		} catch ( e ) {
			setError( e instanceof Error ? e.message : 'Error loading stats.' );
		} finally {
			setLoading( false );
		}
	};

	useEffect( () => { load(); }, [] );

	return (
		<div className="cartpinger-stats">
			<div className="cartpinger-stats__header">
				<h2 className="cartpinger-stats__title">Estadísticas de Recuperación</h2>
				<button
					className="button cartpinger-stats__refresh"
					onClick={ load }
					disabled={ loading }
				>
					{ loading ? 'Cargando…' : 'Actualizar' }
				</button>
			</div>

			{ error && (
				<div className="cartpinger-notice cartpinger-notice--error">{ error }</div>
			) }

			{ ! loading && stats && (
				<div className="cartpinger-kpi-grid">
					<KpiCard
						label="Carritos Detectados"
						value={ stats.total_carts.toLocaleString() }
					/>
					<KpiCard
						label="Tasa de Conversión"
						value={ `${ stats.conversion_rate }%` }
					/>
					<KpiCard
						label="Entregados / Leídos"
						value={ `${ stats.delivered.toLocaleString() } / ${ stats.read.toLocaleString() }` }
					/>
				</div>
			) }
		</div>
	);
}

// ---------------------------------------------------------------------------
// SettingsView
// ---------------------------------------------------------------------------

const EMPTY_SETTINGS: SettingsData = {
	phone_number_id: '',
	waba_id: '',
	verify_token: '',
	access_token: '',
	app_secret: '',
	delete_data_on_uninstall: false,
	widget_enabled: false,
	support_phone: '',
	widget_message: '',
	is_configured: false,
};

function SettingsView() {
	const [ form, setForm ] = useState<SettingsData>( EMPTY_SETTINGS );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState<{ type: 'success' | 'error'; text: string } | null>( null );

	useEffect( () => {
		apiFetch<SettingsData>( 'settings' )
			.then( ( data ) => setForm( data ) )
			.catch( () => setNotice( { type: 'error', text: 'No se pudieron cargar los ajustes.' } ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const set = ( key: keyof SettingsData, value: string | boolean ) =>
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );

	const save = async () => {
		setSaving( true );
		setNotice( null );
		try {
			await apiFetch( 'settings', {
				method: 'POST',
				body: JSON.stringify( form ),
			} );
			setNotice( { type: 'success', text: 'Ajustes guardados correctamente.' } );
		} catch ( e ) {
			setNotice( { type: 'error', text: e instanceof Error ? e.message : 'Error al guardar.' } );
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return <div className="cartpinger-settings-loading">Cargando ajustes…</div>;
	}

	return (
		<div className="cartpinger-settings-form">
			{ notice && (
				<div className={ `cartpinger-notice cartpinger-notice--${ notice.type }` }>
					{ notice.text }
				</div>
			) }

			<div className="cartpinger-settings-section">
				<h3 className="cartpinger-settings-section__title">Credenciales Meta API</h3>

				<Field label="Phone Number ID">
					<input
						type="text"
						className="regular-text"
						value={ form.phone_number_id }
						onChange={ ( e ) => set( 'phone_number_id', e.target.value ) }
						disabled={ saving }
						autoComplete="off"
					/>
				</Field>

				<Field label="WABA ID">
					<input
						type="text"
						className="regular-text"
						value={ form.waba_id }
						onChange={ ( e ) => set( 'waba_id', e.target.value ) }
						disabled={ saving }
						autoComplete="off"
					/>
				</Field>

				<Field label="Access Token">
					<input
						type="password"
						className="regular-text"
						value={ form.access_token === '***' ? '' : form.access_token }
						placeholder={ form.access_token === '***' ? '••••••••••••' : '' }
						onChange={ ( e ) => set( 'access_token', e.target.value ) }
						disabled={ saving }
						autoComplete="new-password"
					/>
				</Field>

				<Field label="App Secret">
					<input
						type="password"
						className="regular-text"
						value={ form.app_secret === '***' ? '' : form.app_secret }
						placeholder={ form.app_secret === '***' ? '••••••••••••' : '' }
						onChange={ ( e ) => set( 'app_secret', e.target.value ) }
						disabled={ saving }
						autoComplete="new-password"
					/>
				</Field>
			</div>

			<div className="cartpinger-settings-section">
				<h3 className="cartpinger-settings-section__title">Webhook</h3>

				<Field label="Verify Token">
					<input
						type="text"
						className="regular-text"
						value={ form.verify_token }
						onChange={ ( e ) => set( 'verify_token', e.target.value ) }
						disabled={ saving }
						autoComplete="off"
					/>
				</Field>
			</div>

			<div className="cartpinger-settings-section">
				<h3 className="cartpinger-settings-section__title">Chat Widget</h3>

				<Field label="Activar widget flotante">
					<label className="cartpinger-toggle">
						<input
							type="checkbox"
							checked={ form.widget_enabled }
							onChange={ ( e ) => set( 'widget_enabled', e.target.checked ) }
							disabled={ saving }
						/>
						<span className="cartpinger-toggle__track" />
					</label>
				</Field>

				<Field label="Teléfono de soporte (E.164)">
					<input
						type="tel"
						className="regular-text"
						value={ form.support_phone }
						placeholder="+34612345678"
						onChange={ ( e ) => set( 'support_phone', e.target.value ) }
						disabled={ saving }
					/>
				</Field>

				<Field label="Mensaje pre-rellenado">
					<input
						type="text"
						className="regular-text"
						value={ form.widget_message }
						placeholder="Hola, necesito ayuda con mi pedido"
						onChange={ ( e ) => set( 'widget_message', e.target.value ) }
						disabled={ saving }
					/>
				</Field>
			</div>

			<div className="cartpinger-settings-section">
				<h3 className="cartpinger-settings-section__title">Datos</h3>

				<Field label="Eliminar datos al desinstalar">
					<label className="cartpinger-toggle">
						<input
							type="checkbox"
							checked={ form.delete_data_on_uninstall }
							onChange={ ( e ) => set( 'delete_data_on_uninstall', e.target.checked ) }
							disabled={ saving }
						/>
						<span className="cartpinger-toggle__track" />
					</label>
				</Field>
			</div>

			<div className="cartpinger-settings-actions">
				<button
					className="button button-primary cartpinger-settings-actions__save"
					onClick={ save }
					disabled={ saving || loading }
				>
					{ saving ? 'Guardando…' : 'Guardar ajustes' }
				</button>

				{ form.is_configured && (
					<span className="cartpinger-badge cartpinger-badge--ok">
						&#10003; Conectado
					</span>
				) }
			</div>
		</div>
	);
}

function Field( { label, children }: { label: string; children: React.ReactNode } ) {
	return (
		<div className="cartpinger-field">
			<label className="cartpinger-field__label">{ label }</label>
			<div className="cartpinger-field__control">{ children }</div>
		</div>
	);
}

// ---------------------------------------------------------------------------
// Mount
// ---------------------------------------------------------------------------

const dashboardRoot = document.getElementById( 'cartpinger-dashboard-app' );
if ( dashboardRoot ) {
	render( <StatsView />, dashboardRoot );
}

const settingsRoot = document.getElementById( 'cartpinger-settings-app' );
if ( settingsRoot ) {
	render( <SettingsView />, settingsRoot );
}
