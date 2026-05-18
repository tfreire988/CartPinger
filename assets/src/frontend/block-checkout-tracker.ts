/**
 * CartPinger block checkout tracker.
 *
 * Subscribes to WooCommerce block checkout stores and sends cart data to the
 * CartPinger REST endpoint as the customer fills in their phone and opts in —
 * without waiting for "Place Order".
 */
import { subscribe, select } from '@wordpress/data';

declare const cartpingerTracker: {
	apiUrl: string;
	nonce: string;
};

const CUSTOMER_STORE = 'wc/store/customer';
const CHECKOUT_STORE = 'wc/store/checkout';

let debounceTimer: ReturnType< typeof setTimeout > | null = null;
let lastTrackedKey = '';

const getBillingField = ( field: string ): string => {
	const store = select( CUSTOMER_STORE );
	if ( ! store ) return '';

	if ( typeof store.getBillingAddress === 'function' ) {
		const billing = store.getBillingAddress() as Record< string, string > | null;
		return billing?.[ field ] ?? '';
	}

	if ( typeof store.getCustomerData === 'function' ) {
		const data = store.getCustomerData() as {
			billing_address?: Record< string, string >;
		} | null;
		return data?.billing_address?.[ field ] ?? '';
	}

	return '';
};

const getConsent = (): boolean => {
	const store = select( CHECKOUT_STORE );
	if ( ! store || typeof store.getAdditionalFields !== 'function' ) return false;

	const fields = store.getAdditionalFields() as Record< string, unknown > | null;
	return !! fields?.[ 'cartpinger/whatsapp_consent' ];
};

const trackCart = async (): Promise< void > => {
	const phone = getBillingField( 'phone' );
	const consent = getConsent();

	const key = `${ phone }:${ String( consent ) }`;
	if ( key === lastTrackedKey || ! phone ) return;

	try {
		await fetch( cartpingerTracker.apiUrl + 'track-cart', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cartpingerTracker.nonce,
			},
			body: JSON.stringify( {
				phone,
				name: getBillingField( 'first_name' ),
				consent,
			} ),
		} );
		lastTrackedKey = key;
	} catch {
		// Silently fail — tracking is best-effort.
	}
};

const scheduleTrack = (): void => {
	if ( debounceTimer ) clearTimeout( debounceTimer );
	debounceTimer = setTimeout( () => {
		void trackCart();
	}, 1500 );
};

subscribe( scheduleTrack, CUSTOMER_STORE );
subscribe( scheduleTrack, CHECKOUT_STORE );
