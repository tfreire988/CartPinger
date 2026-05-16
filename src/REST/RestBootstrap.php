<?php
/**
 * REST API bootstrapper.
 *
 * @package WhatsCom\REST
 */

declare(strict_types=1);

namespace WhatsCom\REST;

/**
 * Class RestBootstrap
 */
final class RestBootstrap {

	/**
	 * Register REST API routes.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'registerRoutes' ) );
	}

	/**
	 * Register all plugin REST routes.
	 */
	public static function registerRoutes(): void {
		// TODO v1.0: register routes for settings, webhook, test-message, etc.
		// Example:
		// register_rest_route( 'whatscom/v1', '/webhook', [...] );
	}
}
