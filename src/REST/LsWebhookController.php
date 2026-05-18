<?php
/**
 * REST controller for Lemon Squeezy webhook events.
 *
 * Handles POST /cartpinger/v1/ls-webhook. Verifies the X-Signature header
 * using HMAC-SHA256 and the stored signing secret, then dispatches events.
 *
 * Supported events:
 *   - order_refunded: deactivates the Pro license for the site.
 *
 * @package CartPinger\REST
 */

declare(strict_types=1);

namespace CartPinger\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Support\LicenseManager;

/**
 * Class LsWebhookController
 */
final class LsWebhookController {

	private const NAMESPACE  = 'cartpinger/v1';
	private const ROUTE      = '/ls-webhook';
	private const OPT_SECRET = 'cartpinger_ls_webhook_secret';

	/**
	 * Register the /ls-webhook REST route.
	 */
	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle POST /cartpinger/v1/ls-webhook.
	 *
	 * Always returns 200 so Lemon Squeezy does not retry.
	 * Payloads with invalid signatures are silently discarded.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response
	 */
	public static function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$raw_body  = $request->get_body();
		$signature = (string) ( $request->get_header( 'x-signature' ) ?? '' );

		if ( ! self::verifySignature( $raw_body, $signature ) ) {
			return new \WP_REST_Response( null, 200 );
		}

		$payload = json_decode( $raw_body, true );
		$meta    = is_array( $payload ) && isset( $payload['meta'] ) ? $payload['meta'] : array();
		$event   = isset( $meta['event_name'] ) ? (string) $meta['event_name'] : '';

		if ( 'order_refunded' === $event ) {
			LicenseManager::deactivate();
		}

		return new \WP_REST_Response( null, 200 );
	}

	/**
	 * Verify the HMAC-SHA256 signature from Lemon Squeezy.
	 *
	 * @param string $body      Raw request body.
	 * @param string $signature Value of the X-Signature header.
	 * @return bool
	 */
	private static function verifySignature( string $body, string $signature ): bool {
		$secret = (string) get_option( self::OPT_SECRET, '' );

		if ( '' === $secret || '' === $signature ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $body, $secret );

		return hash_equals( $expected, $signature );
	}
}
