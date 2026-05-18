<?php
/**
 * Adds a GDPR-compliant WhatsApp marketing consent checkbox to the checkout.
 *
 * The checkbox renders after the order notes field. Its value is transmitted on
 * every WooCommerce AJAX update_order_review call and handled by
 * AbandonedCartTracker::onCheckoutUpdate(), which writes the decision to the WC
 * session so it survives shipping/coupon refreshes and full page reloads.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\WooCommerce;

/**
 * Class CheckoutConsent
 */
final class CheckoutConsent {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_after_order_notes', array( self::class, 'renderCheckbox' ) );
		add_filter( 'woocommerce_checkout_get_value', array( self::class, 'restoreFromSession' ), 10, 2 );
	}

	/**
	 * Output the consent checkbox HTML inside the checkout form.
	 *
	 * @param \WC_Checkout $checkout WooCommerce checkout object.
	 */
	public static function renderCheckbox( \WC_Checkout $checkout ): void {
		woocommerce_form_field(
			'cartpinger_whatsapp_consent',
			array(
				'type'  => 'checkbox',
				'class' => array( 'form-row-wide' ),
				'label' => esc_html__(
					'I agree to receive WhatsApp messages about my order and abandoned cart recovery.',
					'cartpinger'
				),
			),
			$checkout->get_value( 'cartpinger_whatsapp_consent' )
		);
	}

	/**
	 * Restore the checkbox value from the WC session on page load.
	 *
	 * Hooked to woocommerce_checkout_get_value. Returns the session value when
	 * the key matches our field so that a page refresh (e.g. after a coupon is
	 * applied) does not lose the customer's consent choice.
	 *
	 * @param mixed  $value Current field value (null when not yet set).
	 * @param string $key   Checkout field key being resolved.
	 * @return mixed        Session value when available, otherwise $value unchanged.
	 */
	public static function restoreFromSession( mixed $value, string $key ): mixed {
		if ( 'cartpinger_whatsapp_consent' !== $key ) {
			return $value;
		}

		$session = WC()->session;
		if ( ! $session ) {
			return $value;
		}

		$stored = $session->get( 'cartpinger_whatsapp_consent' );
		return null !== $stored ? $stored : $value;
	}
}
