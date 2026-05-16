<?php
/**
 * REST controller for sending a test WhatsApp message.
 *
 * POST /whatscom/v1/test-message — sends a short text message to a given
 * E.164 phone number using the stored credentials, so the store owner can
 * verify the Cloud API connection from the onboarding wizard.
 *
 * Requires the manage_woocommerce capability.
 *
 * @package WhatsCom\REST
 */

declare(strict_types=1);

namespace WhatsCom\REST;

use WhatsCom\Support\CredentialStore;
use WhatsCom\Support\Sanitizer;
use WhatsCom\WhatsApp\CloudApiClient;

/**
 * Class TestMessageController
 */
final class TestMessageController {

	/** REST namespace and route. */
	private const NAMESPACE = 'whatscom/v1';
	private const ROUTE     = '/test-message';

	/**
	 * Register the /test-message REST route.
	 */
	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'handlePost' ),
				'permission_callback' => array( self::class, 'checkPermission' ),
				'args'                => array(
					'phone' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
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
	 * POST /whatscom/v1/test-message
	 *
	 * Validates the phone number, builds a CloudApiClient from stored
	 * credentials, and sends a short test text message.
	 *
	 * Returns HTTP 422 when the phone is invalid or the plugin is not
	 * configured; HTTP 502 when the Cloud API call itself fails.
	 *
	 * @param \WP_REST_Request $request REST request carrying the phone field.
	 * @return \WP_REST_Response
	 */
	public static function handlePost( \WP_REST_Request $request ): \WP_REST_Response {
		$phone = Sanitizer::phone( (string) ( $request->get_param( 'phone' ) ?? '' ) );

		if ( '' === $phone ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid phone number.' ), 422 );
		}

		$client = self::makeClient();

		if ( null === $client ) {
			return new \WP_REST_Response( array( 'message' => 'Plugin is not configured.' ), 422 );
		}

		$result = $client->sendText( $phone, self::testMessageText() );

		if ( ! $result['success'] ) {
			return new \WP_REST_Response(
				array( 'message' => $result['error'] ?? 'Failed to send test message.' ),
				502
			);
		}

		return new \WP_REST_Response( array( 'message' => 'Test message sent.' ), 200 );
	}

	/**
	 * Build a CloudApiClient from stored credentials.
	 *
	 * Returns null when credentials are missing.
	 *
	 * @return CloudApiClient|null
	 */
	private static function makeClient(): ?CloudApiClient {
		$phone_id     = (string) get_option( 'whatscom_phone_number_id', '' );
		$access_token = CredentialStore::load( 'whatscom_access_token' );

		if ( '' === $phone_id || '' === $access_token ) {
			return null;
		}

		return new CloudApiClient( $access_token, $phone_id );
	}

	/**
	 * Return the test message body text.
	 *
	 * @return string
	 */
	private static function testMessageText(): string {
		$site = (string) get_bloginfo( 'name' );
		/* translators: %s: store name */
		return sprintf( __( '✅ WhatsCom test message from %s. Your WhatsApp integration is working!', 'whatscom' ), $site );
	}
}
