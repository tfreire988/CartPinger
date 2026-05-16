<?php
/**
 * REST controller for plugin credential settings.
 *
 * GET  /whatscom/v1/settings — returns current settings with sensitive
 *      values masked; indicates whether the plugin is fully configured.
 * POST /whatscom/v1/settings — validates, sanitizes, and persists credentials.
 *
 * Both endpoints require the manage_woocommerce capability.
 *
 * @package WhatsCom\REST
 */

declare(strict_types=1);

namespace WhatsCom\REST;

use WhatsCom\Support\Sanitizer;

/**
 * Class SettingsController
 */
final class SettingsController {

	/** REST namespace and route. */
	private const NAMESPACE = 'whatscom/v1';
	private const ROUTE     = '/settings';

	/** WP option keys. */
	private const OPT_PHONE_ID     = 'whatscom_phone_number_id';
	private const OPT_VERIFY_TOKEN = 'whatscom_webhook_verify_token';
	private const OPT_ACCESS_TOKEN = 'whatscom_access_token';
	private const OPT_APP_SECRET   = 'whatscom_app_secret';

	/**
	 * Register the /settings REST route.
	 */
	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( self::class, 'handleGet' ),
					'permission_callback' => array( self::class, 'checkPermission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( self::class, 'handlePost' ),
					'permission_callback' => array( self::class, 'checkPermission' ),
					'args'                => array(
						'phone_number_id' => array(
							'type'     => 'string',
							'required' => true,
						),
						'verify_token'    => array(
							'type'     => 'string',
							'required' => true,
						),
						'access_token'    => array(
							'type'     => 'string',
							'required' => true,
						),
						'app_secret'      => array(
							'type'     => 'string',
							'required' => true,
						),
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
	 * GET /whatscom/v1/settings
	 *
	 * Returns current settings. Sensitive fields (access_token, app_secret)
	 * are replaced with "***" so secrets never leave the server.
	 *
	 * @param \WP_REST_Request $request REST request (unused, satisfies callback signature).
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		$phone_id     = (string) get_option( self::OPT_PHONE_ID, '' );
		$verify_token = (string) get_option( self::OPT_VERIFY_TOKEN, '' );
		$has_token    = '' !== (string) get_option( self::OPT_ACCESS_TOKEN, '' );
		$has_secret   = '' !== (string) get_option( self::OPT_APP_SECRET, '' );

		$data = array(
			'phone_number_id' => $phone_id,
			'verify_token'    => $verify_token,
			'access_token'    => $has_token ? '***' : '',
			'app_secret'      => $has_secret ? '***' : '',
			'is_configured'   => '' !== $phone_id && '' !== $verify_token && $has_token && $has_secret,
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * POST /whatscom/v1/settings
	 *
	 * Validates and persists the four required credential fields.
	 * Returns HTTP 422 with a descriptive message when any field fails validation.
	 *
	 * @param \WP_REST_Request $request REST request carrying the credential fields.
	 * @return \WP_REST_Response
	 */
	public static function handlePost( \WP_REST_Request $request ): \WP_REST_Response {
		$phone_id     = Sanitizer::metaNumericId( (string) ( $request->get_param( 'phone_number_id' ) ?? '' ) );
		$verify_token = Sanitizer::verifyToken( (string) ( $request->get_param( 'verify_token' ) ?? '' ) );
		$access_token = Sanitizer::accessToken( (string) ( $request->get_param( 'access_token' ) ?? '' ) );
		$app_secret   = Sanitizer::appSecret( (string) ( $request->get_param( 'app_secret' ) ?? '' ) );

		if ( '' === $phone_id ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid phone_number_id.' ), 422 );
		}
		if ( '' === $verify_token ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid verify_token.' ), 422 );
		}
		if ( '' === $access_token ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid access_token.' ), 422 );
		}
		if ( '' === $app_secret ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid app_secret.' ), 422 );
		}

		update_option( self::OPT_PHONE_ID, $phone_id, false );
		update_option( self::OPT_VERIFY_TOKEN, $verify_token, false );
		update_option( self::OPT_ACCESS_TOKEN, $access_token, false );
		update_option( self::OPT_APP_SECRET, $app_secret, false );

		return new \WP_REST_Response( array( 'message' => 'Settings saved.' ), 200 );
	}
}
