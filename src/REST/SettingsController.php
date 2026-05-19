<?php
/**
 * REST controller for plugin credential settings.
 *
 * GET  /cartpinger/v1/settings — returns current settings with sensitive
 *      values masked; indicates whether the plugin is fully configured.
 * POST /cartpinger/v1/settings — validates, sanitizes, and persists credentials.
 *
 * Both endpoints require the manage_woocommerce capability.
 *
 * @package CartPinger\REST
 */

declare(strict_types=1);

namespace CartPinger\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Support\CredentialStore;
use CartPinger\Support\Sanitizer;

/**
 * Class SettingsController
 */
final class SettingsController {

	/** REST namespace and route. */
	private const NAMESPACE = 'cartpinger/v1';
	private const ROUTE     = '/settings';

	/** WP option keys. */
	private const OPT_PHONE_ID       = 'cartpinger_phone_number_id';
	private const OPT_WABA_ID        = 'cartpinger_waba_id';
	private const OPT_VERIFY_TOKEN   = 'cartpinger_webhook_verify_token';
	private const OPT_ACCESS_TOKEN   = 'cartpinger_access_token';
	private const OPT_APP_SECRET     = 'cartpinger_app_secret';
	private const OPT_DELETE_ON_UNI  = 'cartpinger_delete_data_on_uninstall';
	private const OPT_WIDGET_ENABLED = 'cartpinger_widget_enabled';
	private const OPT_SUPPORT_PHONE  = 'cartpinger_support_phone';
	private const OPT_WIDGET_MESSAGE = 'cartpinger_widget_message';
	private const OPT_LS_WH_SECRET   = 'cartpinger_ls_webhook_secret';

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
						'phone_number_id'          => array(
							'type'     => 'string',
							'required' => true,
						),
						'waba_id'                  => array(
							'type'     => 'string',
							'required' => true,
						),
						'verify_token'             => array(
							'type'     => 'string',
							'required' => true,
						),
						'access_token'             => array(
							'type'     => 'string',
							'required' => false,
							'default'  => '',
						),
						'app_secret'               => array(
							'type'     => 'string',
							'required' => false,
							'default'  => '',
						),
						'delete_data_on_uninstall' => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => false,
						),
						'widget_enabled'           => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => false,
						),
						'support_phone'            => array(
							'type'     => 'string',
							'required' => false,
							'default'  => '',
						),
						'widget_message'           => array(
							'type'     => 'string',
							'required' => false,
							'default'  => '',
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
	 * GET /cartpinger/v1/settings
	 *
	 * Returns current settings. Sensitive fields (access_token, app_secret)
	 * are replaced with "***" so secrets never leave the server.
	 *
	 * @param \WP_REST_Request $request REST request (unused, satisfies callback signature).
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		$phone_id     = (string) get_option( self::OPT_PHONE_ID, '' );
		$waba_id      = (string) get_option( self::OPT_WABA_ID, '' );
		$verify_token = (string) get_option( self::OPT_VERIFY_TOKEN, '' );
		$has_token    = '' !== CredentialStore::load( self::OPT_ACCESS_TOKEN );
		$has_secret   = '' !== CredentialStore::load( self::OPT_APP_SECRET );

		$data = array(
			'phone_number_id'          => $phone_id,
			'waba_id'                  => $waba_id,
			'verify_token'             => $verify_token,
			'access_token'             => $has_token ? '***' : '',
			'app_secret'               => $has_secret ? '***' : '',
			'delete_data_on_uninstall' => (bool) get_option( self::OPT_DELETE_ON_UNI, false ),
			'is_configured'            => '' !== $phone_id && '' !== $waba_id && '' !== $verify_token && $has_token && $has_secret,
			'widget_enabled'           => (bool) get_option( self::OPT_WIDGET_ENABLED, false ),
			'support_phone'            => (string) get_option( self::OPT_SUPPORT_PHONE, '' ),
			'widget_message'           => (string) get_option( self::OPT_WIDGET_MESSAGE, '' ),
			'ls_webhook_secret'        => '' !== (string) get_option( self::OPT_LS_WH_SECRET, '' ) ? '***' : '',
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * POST /cartpinger/v1/settings
	 *
	 * Validates and persists the four required credential fields.
	 * Returns HTTP 422 with a descriptive message when any field fails validation.
	 *
	 * @param \WP_REST_Request $request REST request carrying the credential fields.
	 * @return \WP_REST_Response
	 */
	public static function handlePost( \WP_REST_Request $request ): \WP_REST_Response {
		$phone_id     = Sanitizer::metaNumericId( (string) ( $request->get_param( 'phone_number_id' ) ?? '' ) );
		$waba_id      = Sanitizer::metaNumericId( (string) ( $request->get_param( 'waba_id' ) ?? '' ) );
		$verify_token = Sanitizer::verifyToken( (string) ( $request->get_param( 'verify_token' ) ?? '' ) );
		$raw_access_token = (string) ( $request->get_param( 'access_token' ) ?? '' );
		$raw_app_secret   = (string) ( $request->get_param( 'app_secret' ) ?? '' );
		$access_token     = ( '' !== $raw_access_token && '***' !== $raw_access_token )
			? Sanitizer::accessToken( $raw_access_token )
			: '';
		$app_secret       = ( '' !== $raw_app_secret && '***' !== $raw_app_secret )
			? Sanitizer::appSecret( $raw_app_secret )
			: '';

		if ( '' === $phone_id ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid phone_number_id.' ), 422 );
		}
		if ( '' === $waba_id ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid waba_id.' ), 422 );
		}
		if ( '' === $verify_token ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid verify_token.' ), 422 );
		}

		// Validate only when a new value is being set.
		if ( '' !== $raw_access_token && '***' !== $raw_access_token && '' === $access_token ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid access_token.' ), 422 );
		}
		if ( '' !== $raw_app_secret && '***' !== $raw_app_secret && '' === $app_secret ) {
			return new \WP_REST_Response( array( 'message' => 'Invalid app_secret.' ), 422 );
		}

		$delete_on_uninstall = (bool) $request->get_param( 'delete_data_on_uninstall' );
		$widget_enabled      = (bool) $request->get_param( 'widget_enabled' );
		$support_phone       = Sanitizer::phone( (string) ( $request->get_param( 'support_phone' ) ?? '' ) );
		$widget_message      = sanitize_text_field( (string) ( $request->get_param( 'widget_message' ) ?? '' ) );

		update_option( self::OPT_PHONE_ID, $phone_id, false );
		update_option( self::OPT_WABA_ID, $waba_id, false );
		update_option( self::OPT_VERIFY_TOKEN, $verify_token, false );
		update_option( self::OPT_DELETE_ON_UNI, $delete_on_uninstall, false );
		update_option( self::OPT_WIDGET_ENABLED, $widget_enabled, false );
		update_option( self::OPT_SUPPORT_PHONE, $support_phone, false );
		update_option( self::OPT_WIDGET_MESSAGE, $widget_message, false );
		if ( '' !== $access_token ) {
			CredentialStore::save( self::OPT_ACCESS_TOKEN, $access_token );
		}
		if ( '' !== $app_secret ) {
			CredentialStore::save( self::OPT_APP_SECRET, $app_secret );
		}

		$ls_secret = sanitize_text_field( (string) ( $request->get_param( 'ls_webhook_secret' ) ?? '' ) );
		if ( '' !== $ls_secret && '***' !== $ls_secret ) {
			update_option( self::OPT_LS_WH_SECRET, $ls_secret, false );
		}

		return new \WP_REST_Response( array( 'message' => 'Settings saved.' ), 200 );
	}
}
