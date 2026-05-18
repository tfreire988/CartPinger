<?php
/**
 * REST controller for admin dashboard statistics.
 *
 * GET /cartpinger/v1/stats — returns aggregated KPIs for the admin dashboard.
 * Requires manage_woocommerce capability.
 *
 * @package CartPinger\REST
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\REST;

use CartPinger\Database\Repositories\CartRecoveryRepository;
use CartPinger\Database\Repositories\MessageLogRepository;

/**
 * Class StatsController
 */
final class StatsController {

	/** REST namespace and route. */
	private const NAMESPACE = 'cartpinger/v1';
	private const ROUTE     = '/stats';

	/**
	 * Register the /stats REST route.
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
	 * GET /cartpinger/v1/stats
	 *
	 * @param \WP_REST_Request $request REST request (unused).
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		$recovery_stats = ( new CartRecoveryRepository() )->getStats();
		$delivery_stats = ( new MessageLogRepository() )->getDeliveryStats();

		$total     = $recovery_stats['total'];
		$recovered = $recovery_stats['recovered'];

		$data = array(
			'total_carts'     => $total,
			'recovered'       => $recovered,
			'conversion_rate' => $total > 0 ? round( ( $recovered / $total ) * 100, 1 ) : 0.0,
			'delivered'       => $delivery_stats['delivered'],
			'read'            => $delivery_stats['read'],
		);

		return new \WP_REST_Response( $data, 200 );
	}
}
