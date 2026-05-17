<?php
/**
 * Adds WhatsApp phone field to block-based checkout.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

/**
 * Class CheckoutFields
 *
 * TODO v1.0: register an additional checkout field (block-based API, WC 9.0+)
 *            to capture a WhatsApp-specific phone number when different from billing.
 */
final class CheckoutFields {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_init', array( self::class, 'registerFields' ) );
	}

	/**
	 * Register custom block-based checkout fields.
	 */
	public static function registerFields(): void {
		/*
		 * TODO v1.0: use \Automattic\WooCommerce\Blocks\Package::container()
		 * and BlockCheckoutFieldsRegistry to add the WhatsApp opt-in field.
		 */
	}
}
