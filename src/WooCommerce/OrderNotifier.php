<?php
/**
 * Sends WhatsApp notifications to the store admin on new orders.
 *
 * @package WhatsCom\WooCommerce
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace WhatsCom\WooCommerce;

/**
 * Class OrderNotifier
 *
 * TODO v1.0: hook into woocommerce_new_order and enqueue Cloud API message.
 */
final class OrderNotifier {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_new_order', array( self::class, 'onNewOrder' ), 10, 1 );
	}

	/**
	 * Handle a new order event.
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public static function onNewOrder( int $order_id ): void {
		// TODO v1.0: fetch order details, build template components, enqueue message.
	}
}
