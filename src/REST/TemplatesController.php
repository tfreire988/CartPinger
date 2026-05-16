<?php
/**
 * REST controller for WhatsApp message templates.
 *
 * GET /whatscom/v1/templates — returns the cached list of approved templates
 * for the configured WABA, fetching from the Meta API on a cache miss.
 *
 * Requires the manage_woocommerce capability.
 *
 * @package WhatsCom\REST
 */

declare(strict_types=1);

namespace WhatsCom\REST;

use WhatsCom\Support\CredentialStore;
use WhatsCom\WhatsApp\TemplateManager;

/**
 * Class TemplatesController
 */
final class TemplatesController {

	/** REST namespace and route. */
	private const NAMESPACE = 'whatscom/v1';
	private const ROUTE     = '/templates';

	/**
	 * Register the /templates REST route.
	 */
	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'handleGet' ),
				'permission_callback' => array( self::class, 'checkPermission' ),
			)
		);
	}

	/**
	 * Permission callback — requires manage_woocommerce.
	 */
	public static function checkPermission(): bool {
		return (bool) current_user_can( 'manage_woocommerce' );
	}

	/**
	 * GET /whatscom/v1/templates
	 *
	 * Returns all approved templates for the configured WABA.
	 * Returns HTTP 422 when the plugin is not configured.
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		$manager = self::makeManager();

		if ( null === $manager ) {
			return new \WP_REST_Response( array( 'message' => 'Plugin is not configured.' ), 422 );
		}

		$templates = $manager->getTemplates();

		return new \WP_REST_Response(
			array(
				'templates' => $templates,
				'count'     => count( $templates ),
			),
			200
		);
	}

	/**
	 * Build a TemplateManager from stored credentials.
	 *
	 * Returns null when the WABA ID or access token is missing.
	 *
	 * @return TemplateManager|null
	 */
	private static function makeManager(): ?TemplateManager {
		$waba_id      = (string) get_option( 'whatscom_waba_id', '' );
		$access_token = CredentialStore::load( 'whatscom_access_token' );

		if ( '' === $waba_id || '' === $access_token ) {
			return null;
		}

		return new TemplateManager( $waba_id, $access_token );
	}
}
