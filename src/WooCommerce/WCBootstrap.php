<?php
/**
 * WooCommerce integration bootstrapper.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

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
		// TODO v1.0: ProductButton::register();
		// TODO v1.0: AbandonedCartTracker::register();
		// TODO v1.0: CheckoutFields::register();
		// TODO v1.0: ChatWidget::register();
	}
}
