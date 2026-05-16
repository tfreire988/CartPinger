<?php
/**
 * Floating WhatsApp chat widget on the storefront.
 *
 * @package WhatsCom\WooCommerce
 */

declare(strict_types=1);

namespace WhatsCom\WooCommerce;

/**
 * Class ChatWidget
 *
 * TODO v1.0: inject floating chat button HTML + wa.me deep-link into wp_footer.
 */
final class ChatWidget {

	/**
	 * Register hooks.
	 */
	public static function register(): void {
		add_action( 'wp_footer', array( self::class, 'renderWidget' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
	}

	/**
	 * Render the chat widget HTML.
	 */
	public static function renderWidget(): void {
		// TODO v1.0: output widget HTML with business phone, greeting message.
	}

	/**
	 * Enqueue frontend CSS/JS for the widget.
	 */
	public static function enqueueAssets(): void {
		// TODO v1.0: enqueue assets/build/frontend.js and frontend.css.
	}
}
