<?php
/**
 * Tracks abandoned WooCommerce carts for WhatsApp recovery.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

/**
 * Class AbandonedCartTracker
 *
 * TODO v1.0: snapshot cart on woocommerce_cart_updated, mark recovered on checkout,
 *            send WhatsApp recovery message after configurable delay.
 */
final class AbandonedCartTracker {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_cart_updated', array( self::class, 'onCartUpdated' ) );
		add_action( 'woocommerce_thankyou', array( self::class, 'onOrderComplete' ), 10, 1 );
	}

	/**
	 * Snapshot the current cart state.
	 */
	public static function onCartUpdated(): void {
		// TODO v1.0: serialize cart, upsert row in cartpinger_abandoned_carts.
	}

	/**
	 * Mark cart as recovered when an order is placed.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function onOrderComplete( int $order_id ): void {
		// TODO v1.0: find matching cart row, set status=recovered.
	}
}
