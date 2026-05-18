<?php
/**
 * REST controller for Pro license management.
 *
 * GET  /cartpinger/v1/license — returns current license status and masked key.
 * POST /cartpinger/v1/license — activate a license key.
 * DELETE /cartpinger/v1/license — deactivate the current license.
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
 * Class LicenseController
 */
final class LicenseController {

	private const NAMESPACE = 'cartpinger/v1';
	private const ROUTE     = '/license';

	/**
	 * Register the /license REST route.
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
						'license_key' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( self::class, 'handleDelete' ),
					'permission_callback' => array( self::class, 'checkPermission' ),
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
	 * GET /cartpinger/v1/license
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'is_pro'      => LicenseManager::isPro(),
				'license_key' => LicenseManager::getMaskedKey(),
			),
			200
		);
	}

	/**
	 * POST /cartpinger/v1/license
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function handlePost( \WP_REST_Request $request ): \WP_REST_Response {
		$key    = (string) ( $request->get_param( 'license_key' ) ?? '' );
		$result = LicenseManager::activate( $key );

		return new \WP_REST_Response( $result, $result['success'] ? 200 : 422 );
	}

	/**
	 * DELETE /cartpinger/v1/license
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function handleDelete( \WP_REST_Request $request ): \WP_REST_Response {
		$result = LicenseManager::deactivate();

		return new \WP_REST_Response( $result, 200 );
	}
}
