<?php
/**
 * REST controller for the Meta webhook endpoint.
 *
 * Handles both the GET subscription-challenge verification (hub.mode=subscribe)
 * and the POST payload processing, delegating all business logic to
 * WebhookHandler.
 *
 * @package WhatsCom\REST
 */

declare(strict_types=1);

namespace WhatsCom\REST;

use WhatsCom\WhatsApp\WebhookHandler;

/**
 * Class WebhookController
 */
final class WebhookController {

	/** REST namespace and route. */
	private const NAMESPACE = 'whatscom/v1';
	private const ROUTE     = '/webhook';

	/**
	 * Register the /webhook REST route.
	 */
	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( self::class, 'handleVerification' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'hub.mode'         => array( 'type' => 'string', 'default' => '' ),
						'hub.verify_token' => array( 'type' => 'string', 'default' => '' ),
						'hub.challenge'    => array( 'type' => 'string', 'default' => '' ),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( self::class, 'handleEvent' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Handle the Meta webhook subscription-challenge GET request.
	 *
	 * Returns the hub.challenge string with HTTP 200 on success, or HTTP 403
	 * when the verify_token does not match the stored token.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response
	 */
	public static function handleVerification( \WP_REST_Request $request ): \WP_REST_Response {
		$handler = self::makeHandler();

		$challenge = $handler->verifySubscription(
			(string) ( $request->get_param( 'hub.mode' ) ?? '' ),
			(string) ( $request->get_param( 'hub.verify_token' ) ?? '' ),
			(string) ( $request->get_param( 'hub.challenge' ) ?? '' )
		);

		if ( null === $challenge ) {
			return new \WP_REST_Response( null, 403 );
		}

		return new \WP_REST_Response( $challenge, 200 );
	}

	/**
	 * Handle an inbound webhook POST event from Meta.
	 *
	 * Always returns HTTP 200 so Meta does not retry the delivery.
	 * Payloads with invalid signatures are silently discarded by WebhookHandler.
	 *
	 * @param \WP_REST_Request $request REST request object.
	 * @return \WP_REST_Response
	 */
	public static function handleEvent( \WP_REST_Request $request ): \WP_REST_Response {
		$handler   = self::makeHandler();
		$raw_body  = $request->get_body();
		$signature = (string) ( $request->get_header( 'x-hub-signature-256' ) ?? '' );

		$handler->process( $raw_body, $signature );

		return new \WP_REST_Response( null, 200 );
	}

	/**
	 * Instantiate a WebhookHandler using credentials stored in WP options.
	 */
	private static function makeHandler(): WebhookHandler {
		$verify_token = (string) get_option( 'whatscom_webhook_verify_token', '' );
		$app_secret   = (string) get_option( 'whatscom_app_secret', '' );

		return new WebhookHandler( $verify_token, $app_secret );
	}
}
