<?php
/**
 * REST controller for CSV export of cart recoveries.
 *
 * GET /cartpinger/v1/export — streams a CSV of all recovery records.
 * Requires the manage_woocommerce capability.
 *
 * @package CartPinger\REST
 */

declare(strict_types=1);

namespace CartPinger\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Database\Repositories\CartRecoveryRepository;

/**
 * Class ExportController
 */
final class ExportController {

	private const NAMESPACE = 'cartpinger/v1';
	private const ROUTE     = '/export';

	/**
	 * Register the /export REST route.
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
	 * GET /cartpinger/v1/export
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function handleGet( \WP_REST_Request $request ): \WP_REST_Response {
		$rows = ( new CartRecoveryRepository() )->getAll();

		$csv = "id,customer_phone,customer_name,status,sequence_step,created_at\n";

		foreach ( $rows as $row ) {
			/** @phpstan-var object{id: int, customer_phone: string, customer_name: string, status: string, sequence_step: int, created_at: string} $row */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
			$csv .= implode(
				',',
				array(
					(int) $row->id,
					'"' . str_replace( '"', '""', (string) $row->customer_phone ) . '"',
					'"' . str_replace( '"', '""', (string) $row->customer_name ) . '"',
					'"' . str_replace( '"', '""', (string) $row->status ) . '"',
					(int) $row->sequence_step,
					'"' . str_replace( '"', '""', (string) $row->created_at ) . '"',
				)
			) . "\n";
		}

		$response = new \WP_REST_Response( $csv, 200 );
		$response->header( 'Content-Type', 'text/csv' );
		$response->header( 'Content-Disposition', 'attachment; filename="cartpinger-recoveries.csv"' );

		return $response;
	}
}
