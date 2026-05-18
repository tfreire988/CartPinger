<?php
/**
 * Sends WhatsApp notifications to customers on key order status changes.
 *
 * Hooks into woocommerce_order_status_changed and dispatches a Cloud API
 * template message for each configured status transition.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\WooCommerce;

use CartPinger\Support\CredentialStore;
use CartPinger\Support\Sanitizer;
use CartPinger\WhatsApp\CloudApiClient;

/**
 * Class OrderNotifier
 */
final class OrderNotifier {

	/**
	 * Map of WooCommerce order status slugs to WhatsApp template names.
	 *
	 * Only statuses listed here will trigger a notification.
	 *
	 * @var array<string, string>
	 */
	private const STATUS_TEMPLATES = array(
		'processing' => 'order_confirmed',
		'completed'  => 'order_completed',
		'cancelled'  => 'order_cancelled',
	);

	/**
	 * Register the order-status hook.
	 */
	public static function register(): void {
		add_action( 'woocommerce_order_status_changed', array( self::class, 'onStatusChanged' ), 10, 4 );
	}

	/**
	 * Handle a WooCommerce order status transition.
	 *
	 * Silently no-ops when:
	 *   - The new status is not in STATUS_TEMPLATES.
	 *   - The plugin credentials are not fully configured.
	 *   - The order has no valid billing phone number.
	 *
	 * @param int       $order_id WooCommerce order ID.
	 * @param string    $from     Previous status slug (without "wc-" prefix).
	 * @param string    $to       New status slug (without "wc-" prefix).
	 * @param \WC_Order $order    WooCommerce order object.
	 */
	public static function onStatusChanged( int $order_id, string $from, string $to, \WC_Order $order ): void {
		if ( ! array_key_exists( $to, self::STATUS_TEMPLATES ) ) {
			return;
		}

		$client = self::makeClient();
		if ( null === $client ) {
			return;
		}

		$phone = Sanitizer::phone( (string) $order->get_billing_phone() );
		if ( '' === $phone ) {
			return;
		}

		$total      = number_format( (float) $order->get_total(), 2 ) . ' ' . (string) $order->get_currency();
		$components = array(
			array(
				'type'       => 'body',
				'parameters' => array(
					array(
						'type' => 'text',
						'text' => (string) $order->get_billing_first_name(),
					),
					array(
						'type' => 'text',
						'text' => (string) $order->get_order_number(),
					),
					array(
						'type' => 'text',
						'text' => $total,
					),
				),
			),
		);

		$client->sendTemplate( $phone, self::STATUS_TEMPLATES[ $to ], 'en_US', $components );
	}

	/**
	 * Build a CloudApiClient from stored (decrypted) credentials.
	 *
	 * Returns null when the plugin is not fully configured so callers can
	 * bail out without additional credential checks.
	 *
	 * @return CloudApiClient|null Client ready to send, or null if not configured.
	 */
	private static function makeClient(): ?CloudApiClient {
		$phone_id     = (string) get_option( 'cartpinger_phone_number_id', '' );
		$access_token = CredentialStore::load( 'cartpinger_access_token' );

		if ( '' === $phone_id || '' === $access_token ) {
			return null;
		}

		return new CloudApiClient( $access_token, $phone_id );
	}
}
