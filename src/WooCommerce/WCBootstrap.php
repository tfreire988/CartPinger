<?php
/**
 * WooCommerce integration bootstrapper.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WCBootstrap
 */
final class WCBootstrap {

	/**
	 * Register all WooCommerce integration hooks.
	 */
	public static function register(): void {
		OrderNotifier::register();
		\CartPinger\WhatsApp\MessageQueue::register();
		AbandonedCartTracker::register();
		CartRecovery::register();
		CheckoutConsent::register();
		CheckoutFields::register();
		ChatWidget::register();
	}
}
