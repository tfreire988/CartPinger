<?php
/**
 * WooCommerce integration bootstrapper.
 *
 * @package WhatsCom\WooCommerce
 */

declare(strict_types=1);

namespace WhatsCom\WooCommerce;

/**
 * Class WCBootstrap
 */
final class WCBootstrap {

	/**
	 * Register all WooCommerce integration hooks.
	 */
	public static function register(): void {
		OrderNotifier::register();
		\WhatsCom\WhatsApp\MessageQueue::register();
		// TODO v1.0: ProductButton::register();
		// TODO v1.0: AbandonedCartTracker::register();
		// TODO v1.0: CheckoutFields::register();
		// TODO v1.0: ChatWidget::register();
	}
}
