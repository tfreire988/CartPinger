<?php
/**
 * Registers the WhatsApp consent field for block-based checkout.
 *
 * Uses the WooCommerce additional checkout fields API (WC 8.6+).
 * Falls back gracefully on older versions — classic checkout consent
 * is handled by CheckoutConsent instead.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

/**
 * Class CheckoutFields
 */
final class CheckoutFields {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_init', array( self::class, 'registerFields' ) );
	}

	/**
	 * Register the WhatsApp consent checkbox in the block checkout order section.
	 *
	 * Only runs when the WooCommerce additional checkout fields API is available
	 * (introduced in WC 8.6). Silently skips on older installs.
	 */
	public static function registerFields(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'       => 'cartpinger/whatsapp_consent',
				'label'    => __( 'I agree to receive WhatsApp messages about my order and abandoned cart recovery.', 'cartpinger' ),
				'location' => 'order',
				'type'     => 'checkbox',
				'required' => false,
			)
		);
	}
}
