<?php
/**
 * Handles WhatsApp cart-recovery link clicks.
 *
 * When a customer clicks the recovery link in their WhatsApp message the URL
 * contains ?cartpinger_recover={token}. This class validates the token,
 * restores the saved cart contents, marks the recovery as recovered, and
 * redirects the customer to the checkout page.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

use CartPinger\Database\Repositories\CartRecoveryRepository;

/**
 * Class CartRecovery
 */
final class CartRecovery {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handleRecoveryRequest' ) );
	}

	/**
	 * Check for the recovery query-string parameter and restore the cart.
	 *
	 * Must run before headers are sent (hence on 'init').
	 */
	public static function handleRecoveryRequest(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = isset( $_GET['cartpinger_recover'] ) ? sanitize_text_field( wp_unslash( $_GET['cartpinger_recover'] ) ) : '';

		if ( '' === $token || strlen( $token ) !== 64 ) {
			return;
		}

		$repo = new CartRecoveryRepository();
		$row  = $repo->findByToken( $token );

		if ( null === $row || 'pending' !== $row->status ) {
			return;
		}

		$cart_contents = json_decode( $row->cart_contents, true );
		if ( ! is_array( $cart_contents ) ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart ) {
			return;
		}

		$cart->empty_cart();

		foreach ( $cart_contents as $item ) {
			$product_id   = (int) ( $item['product_id'] ?? 0 );
			$quantity     = (int) ( $item['quantity'] ?? 1 );
			$variation_id = (int) ( $item['variation_id'] ?? 0 );
			$variation    = is_array( $item['variation'] ?? null ) ? $item['variation'] : array();

			if ( $product_id > 0 ) {
				$cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
			}
		}

		$repo->markStatus( (int) $row->id, 'recovered' );

		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}
}
