<?php
/**
 * Unit tests for StatsController.
 *
 * $wpdb is replaced with an anonymous-class stub so both repositories can
 * run without a real database connection.
 *
 * @package CartPinger\Tests\Unit\REST
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\REST;

use CartPinger\REST\StatsController;
use WP_Mock\Tools\TestCase;

/**
 * Class StatsControllerTest
 */
class StatsControllerTest extends TestCase {

	/** @var mixed Original $wpdb for restoration. */
	private mixed $original_wpdb = null;

	public function setUp(): void {
		\WP_Mock::setUp();

		global $wpdb;
		$this->original_wpdb = $wpdb;
	}

	public function tearDown(): void {
		global $wpdb;
		$wpdb = $this->original_wpdb;

		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a $wpdb stub whose get_row() returns preset aggregates.
	 *
	 * @param int $total     Total cart recovery rows.
	 * @param int $recovered Rows with status='recovered'.
	 * @param int $delivered Rows delivered (including read).
	 * @param int $read      Rows read.
	 */
	private function makeWpdb( int $total, int $recovered, int $delivered, int $read ): object {
		return new class( $total, $recovered, $delivered, $read ) extends \stdClass {
			public string $prefix = 'wp_';

			private int $total;
			private int $recovered;
			private int $delivered;
			private int $read;

			public function __construct( int $total, int $recovered, int $delivered, int $read ) {
				$this->total     = $total;
				$this->recovered = $recovered;
				$this->delivered = $delivered;
				$this->read      = $read;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return object|null */
			public function get_row( string $sql ): ?object {
				$row = new \stdClass();

				if ( str_contains( $sql, 'cartpinger_recoveries' ) ) {
					$row->total     = (string) $this->total;
					$row->recovered = (string) $this->recovered;
				} else {
					$row->delivered  = (string) $this->delivered;
					$row->read_count = (string) $this->read;
				}

				return $row;
			}
		};
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_hooks_rest_route(): void {
		\WP_Mock::userFunction( 'register_rest_route' )
			->once()
			->withArgs( function ( string $namespace, string $route ) {
				return 'cartpinger/v1' === $namespace && '/stats' === $route;
			} )
			->andReturn( true );

		StatsController::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// checkPermission()
	// -------------------------------------------------------------------------

	public function test_check_permission_returns_false_without_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( false );

		$this->assertFalse( StatsController::checkPermission() );
	}

	public function test_check_permission_returns_true_with_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( true );

		$this->assertTrue( StatsController::checkPermission() );
	}

	// -------------------------------------------------------------------------
	// handleGet() — zero data
	// -------------------------------------------------------------------------

	public function test_returns_zeros_when_tables_are_empty(): void {
		$GLOBALS['wpdb'] = $this->makeWpdb( 0, 0, 0, 0 );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$response = StatsController::handleGet( new \WP_REST_Request() );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 0, $data['total_carts'] );
		$this->assertSame( 0, $data['recovered'] );
		$this->assertSame( 0.0, $data['conversion_rate'] );
		$this->assertSame( 0, $data['delivered'] );
		$this->assertSame( 0, $data['read'] );
	}

	// -------------------------------------------------------------------------
	// handleGet() — with data
	// -------------------------------------------------------------------------

	public function test_returns_correct_aggregates(): void {
		$GLOBALS['wpdb'] = $this->makeWpdb( 100, 25, 80, 30 );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$response = StatsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 100, $data['total_carts'] );
		$this->assertSame( 25, $data['recovered'] );
		$this->assertSame( 25.0, $data['conversion_rate'] );
		$this->assertSame( 80, $data['delivered'] );
		$this->assertSame( 30, $data['read'] );
	}

	public function test_conversion_rate_is_rounded_to_one_decimal(): void {
		$GLOBALS['wpdb'] = $this->makeWpdb( 3, 1, 2, 1 );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$response = StatsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		// 1/3 * 100 = 33.333… → rounded to 33.3
		$this->assertSame( 33.3, $data['conversion_rate'] );
	}

	// -------------------------------------------------------------------------
	// handleGet() — response shape
	// -------------------------------------------------------------------------

	public function test_response_contains_all_required_keys(): void {
		$GLOBALS['wpdb'] = $this->makeWpdb( 10, 2, 8, 3 );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$response = StatsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		foreach ( array( 'total_carts', 'recovered', 'conversion_rate', 'delivered', 'read' ) as $key ) {
			$this->assertArrayHasKey( $key, $data, "Missing key: $key" );
		}
	}
}
