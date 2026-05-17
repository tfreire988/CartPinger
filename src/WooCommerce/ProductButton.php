<?php
/**
 * "Buy via WhatsApp" button on product pages.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

/**
 * Class ProductButton
 *
 * TODO v1.0: add button after "Add to cart", build wa.me deep-link with product name + URL.
 */
final class ProductButton {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_after_add_to_cart_button', array( self::class, 'renderButton' ) );
	}

	/**
	 * Render the WhatsApp buy button on single product pages.
	 */
	public static function renderButton(): void {
		// TODO v1.0: build wa.me link and render button HTML.
	}
}
