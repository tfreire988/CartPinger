<?php
/**
 * Tracks abandoned WooCommerce carts for WhatsApp recovery.
 *
 * Hooks into checkout field updates to snapshot the cart while the customer
 * is on the checkout page. When a phone number and GDPR consent are present
 * the cart is persisted to cartpinger_recoveries so the cron job can send
 * a recovery message after the configured delay.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

use CartPinger\Database\Repositories\CartRecoveryRepository;
use CartPinger\Database\Repositories\MessageLogRepository;
use CartPinger\Support\CredentialStore;
use CartPinger\Support\Sanitizer;
use CartPinger\WhatsApp\CloudApiClient;
use CartPinger\WhatsApp\MessageQueue;

/**
 * Class AbandonedCartTracker
 */
final class AbandonedCartTracker {

	public const CRON_HOOK = 'cartpinger_send_recovery';

	/** Delay in seconds before a pending cart is treated as abandoned. */
	private const ABANDON_DELAY = 3600;

	/** Maps WP locales to Meta template language codes. Unlisted locales fall back to en_US. */
	private const LANGUAGE_MAP = array(
		'es_ES' => 'es_ES',
		'es_MX' => 'es_MX',
		'pt_BR' => 'pt_BR',
	);

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_checkout_update_order_review', array( self::class, 'onCheckoutUpdate' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( self::class, 'onOrderComplete' ), 10, 1 );
		add_action( self::CRON_HOOK, array( self::class, 'processPending' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Snapshot the cart when checkout fields are updated.
	 *
	 * Fires on woocommerce_checkout_update_order_review (AJAX), receives the
	 * serialised POST string WooCommerce sends from the checkout form.
	 *
	 * GDPR: consent is evaluated on every AJAX update. If the customer unchecks
	 * the box any previously stored pending record is immediately revoked so it
	 * can never trigger a recovery message.
	 *
	 * @param string $post_data URL-encoded checkout field data.
	 */
	public static function onCheckoutUpdate( string $post_data ): void {
		parse_str( $post_data, $fields );

		$raw_phone = $fields['billing_phone'] ?? '';
		$phone     = Sanitizer::phone( is_array( $raw_phone ) ? '' : (string) $raw_phone );
		if ( '' === $phone ) {
			return;
		}

		$consent = ! empty( $fields['cartpinger_whatsapp_consent'] );

		// Persist consent decision to WC session so it survives shipping/coupon refreshes.
		$session = WC()->session;
		if ( $session ) {
			$session->set( 'cartpinger_whatsapp_consent', $consent ? '1' : '0' );
		}

		if ( ! $consent ) {
			// Immediately revoke any pending recovery record — strict GDPR compliance.
			( new CartRecoveryRepository() )->revokeConsent( $phone );
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return;
		}

		$contents = (string) wp_json_encode( $cart->get_cart_contents() );
		$raw_name = $fields['billing_first_name'] ?? '';
		$name     = sanitize_text_field( is_array( $raw_name ) ? '' : (string) $raw_name );
		$token    = bin2hex( random_bytes( 32 ) );

		( new CartRecoveryRepository() )->upsert( $phone, $name, $contents, $token, true );
	}

	/**
	 * Mark the customer's pending cart as recovered when an order is placed.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function onOrderComplete( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$phone = Sanitizer::phone( (string) $order->get_billing_phone() );
		if ( '' === $phone ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'status' => 'recovered' ),
			array(
				'customer_phone' => $phone,
				'status'         => 'pending',
			)
		);
	}

	/**
	 * Resolve the Meta template language code from the current WP locale.
	 *
	 * Exact locale match wins; Spanish variants fall back to es_ES, Portuguese
	 * variants to pt_BR, everything else to en_US.
	 */
	private static function resolveLanguageCode(): string {
		$locale = get_locale();

		if ( isset( self::LANGUAGE_MAP[ $locale ] ) ) {
			return self::LANGUAGE_MAP[ $locale ];
		}

		if ( str_starts_with( $locale, 'es_' ) ) {
			return 'es_ES';
		}

		if ( str_starts_with( $locale, 'pt_' ) ) {
			return 'pt_BR';
		}

		return 'en_US';
	}

	/**
	 * Cron callback — send WhatsApp recovery messages for abandoned carts.
	 *
	 * A cart is considered abandoned when it has been in 'pending' state for
	 * longer than ABANDON_DELAY seconds and the customer has given GDPR consent.
	 */
	public static function processPending(): void {
		$repo   = new CartRecoveryRepository();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::ABANDON_DELAY );
		$rows   = $repo->getPending( $cutoff );

		if ( empty( $rows ) ) {
			return;
		}

		$access_token    = CredentialStore::load( 'cartpinger_access_token' );
		$phone_number_id = (string) get_option( 'cartpinger_phone_number_id', '' );
		$client          = new CloudApiClient( $access_token, $phone_number_id );
		$queue           = new MessageQueue( new MessageLogRepository(), $client );
		$language_code   = self::resolveLanguageCode();

		foreach ( $rows as $row ) {
			if ( ! isset( $row->id, $row->customer_phone, $row->recovery_token ) ) {
				continue;
			}

			/** @phpstan-var object{id: int, customer_phone: string, recovery_token: string, customer_name?: string, gdpr_consent: int} $row */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
			if ( ! $row->gdpr_consent ) {
				$repo->markStatus( (int) $row->id, 'expired' );
				continue;
			}

			$customer_name = isset( $row->customer_name ) ? (string) $row->customer_name : '';
			$recovery_url  = add_query_arg(
				'cartpinger_recover',
				(string) $row->recovery_token,
				wc_get_cart_url()
			);

			$components = array(
				array(
					'type'       => 'body',
					'parameters' => array(
						array(
							'type' => 'text',
							'text' => '' !== $customer_name ? $customer_name : __( 'there', 'cartpinger' ),
						),
						array(
							'type' => 'text',
							'text' => $recovery_url,
						),
					),
				),
			);

			$queue->enqueue(
				(string) $row->customer_phone,
				'abandoned_cart_recovery',
				$language_code,
				$components
			);

			$repo->markStatus( (int) $row->id, 'sent' );
		}
	}
}
