<?php
/**
 * Unit tests for CartRecoveryRepository.
 *
 * Uses WP_Mock to stub $wpdb and WordPress database functions.
 *
 * @package CartPinger\Tests\Unit\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WooCommerce;

use CartPinger\Database\Repositories\CartRecoveryRepository;
use WP_Mock\Tools\TestCase;

/**
 * Class CartRecoveryRepositoryTest
 */
class CartRecoveryRepositoryTest extends TestCase {

	/** @var object Partial $wpdb stub. */
	private object $wpdb;

	public function setUp(): void {
		\WP_Mock::setUp();

		$this->wpdb = new class() {
			public string $prefix      = 'wp_';
			public int    $insert_id   = 0;
			public string $last_error  = '';

			/** @var string|null */
			public ?string $last_prepare = null;

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return vsprintf( str_replace( array( '%s', '%d' ), array( "'%s'", '%d' ), $query ), $args );
			}

			/** @return false|int */
			public function query( string $sql ): false|int {
				return 1;
			}

			/** @return string|null */
			public function get_var( string $sql ): ?string {
				return null;
			}

			/** @return object|null */
			public function get_row( string $sql ): ?object {
				return null;
			}

			/** @return object[]|null */
			public function get_results( string $sql ): ?array {
				return array();
			}

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int|false {
				return 1;
			}
		};

		// Inject $wpdb into global scope.
		$GLOBALS['wpdb'] = $this->wpdb;
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		unset( $GLOBALS['wpdb'] );
	}

	// -------------------------------------------------------------------------
	// upsert()
	// -------------------------------------------------------------------------

	public function test_upsert_returns_insert_id_on_insert(): void {
		$this->wpdb->insert_id = 7;

		$repo = new CartRecoveryRepository();
		$id   = $repo->upsert( '+34612345678', 'María', '[]', str_repeat( 'a', 64 ), true );

		$this->assertSame( 7, $id );
	}

	public function test_upsert_returns_null_on_query_failure(): void {
		$wpdb = new class() extends \stdClass {
			public string $prefix     = 'wp_';
			public int    $insert_id  = 0;
			public string $last_error = 'DB error';

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return false|int */
			public function query( string $sql ): false|int {
				return false;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repo = new CartRecoveryRepository();
		$id   = $repo->upsert( '+34612345678', 'Test', '[]', str_repeat( 'b', 64 ), false );

		$this->assertNull( $id );
	}

	// -------------------------------------------------------------------------
	// findByToken()
	// -------------------------------------------------------------------------

	public function test_find_by_token_returns_null_when_not_found(): void {
		$repo = new CartRecoveryRepository();
		$row  = $repo->findByToken( str_repeat( 'c', 64 ) );

		$this->assertNull( $row );
	}

	public function test_find_by_token_returns_row(): void {
		$expected          = new \stdClass();
		$expected->id      = 3;
		$expected->status  = 'pending';

		$wpdb = new class( $expected ) extends \stdClass {
			public string $prefix = 'wp_';

			private \stdClass $row;

			public function __construct( \stdClass $row ) {
				$this->row = $row;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return object|null */
			public function get_row( string $sql ): ?object {
				return $this->row;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repo = new CartRecoveryRepository();
		$row  = $repo->findByToken( str_repeat( 'd', 64 ) );

		$this->assertInstanceOf( \stdClass::class, $row );
		$this->assertSame( 3, $row->id );
	}

	// -------------------------------------------------------------------------
	// getPending()
	// -------------------------------------------------------------------------

	public function test_get_pending_returns_empty_array_when_none(): void {
		$repo = new CartRecoveryRepository();
		$rows = $repo->getPending( '2025-01-01 00:00:00' );

		$this->assertSame( array(), $rows );
	}

	// -------------------------------------------------------------------------
	// revokeConsent()
	// -------------------------------------------------------------------------

	public function test_revoke_consent_calls_wpdb_update_with_expired_status(): void {
		$update_args = null;

		$wpdb = new class( $update_args ) extends \stdClass {
			public string $prefix = 'wp_';
			/** @var array<string,mixed>|null */
			private mixed $args;

			/** @param array<string,mixed>|null $args */
			public function __construct( mixed &$args ) {
				$this->args = &$args;
			}

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int {
				$this->args = array(
					'data'  => $data,
					'where' => $where,
				);
				return 1;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repo = new CartRecoveryRepository();
		$repo->revokeConsent( '+34612345678' );

		$this->assertIsArray( $update_args );
		$this->assertSame( 'expired', $update_args['data']['status'] );
		$this->assertSame( 0, $update_args['data']['gdpr_consent'] );
		$this->assertSame( '+34612345678', $update_args['where']['customer_phone'] );
		$this->assertSame( 'pending', $update_args['where']['status'] );
	}

	// -------------------------------------------------------------------------
	// markRecoveryDelivery()
	// -------------------------------------------------------------------------

	public function test_mark_recovery_delivery_ignores_sent_status(): void {
		$query_called = false;

		$GLOBALS['wpdb'] = new class( $query_called ) extends \stdClass {
			public string $prefix = 'wp_';
			private bool &$called;

			public function __construct( bool &$called ) {
				$this->called = &$called;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->called = true;
				return 1;
			}
		};

		$repo = new CartRecoveryRepository();
		$repo->markRecoveryDelivery( '+34612345678', 'sent' );

		$this->assertFalse( $query_called, 'No query for "sent" status — only delivered/read are valid' );
	}

	public function test_mark_recovery_delivery_ignores_failed_status(): void {
		$query_called = false;

		$GLOBALS['wpdb'] = new class( $query_called ) extends \stdClass {
			public string $prefix = 'wp_';
			private bool &$called;

			public function __construct( bool &$called ) {
				$this->called = &$called;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->called = true;
				return 1;
			}
		};

		$repo = new CartRecoveryRepository();
		$repo->markRecoveryDelivery( '+34612345678', 'failed' );

		$this->assertFalse( $query_called );
	}

	public function test_mark_recovery_delivery_executes_query_for_delivered(): void {
		$captured_sql = '';

		$GLOBALS['wpdb'] = new class( $captured_sql ) extends \stdClass {
			public string $prefix = 'wp_';
			private string &$captured;

			public function __construct( string &$captured ) {
				$this->captured = &$captured;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->captured = $sql;
				return 1;
			}
		};

		$repo = new CartRecoveryRepository();
		$repo->markRecoveryDelivery( '+34612345678', 'delivered' );

		$this->assertNotSame( '', $captured_sql, 'A SQL query must be executed for "delivered"' );
	}

	public function test_mark_recovery_delivery_executes_query_for_read(): void {
		$captured_sql = '';

		$GLOBALS['wpdb'] = new class( $captured_sql ) extends \stdClass {
			public string $prefix = 'wp_';
			private string &$captured;

			public function __construct( string &$captured ) {
				$this->captured = &$captured;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->captured = $sql;
				return 1;
			}
		};

		$repo = new CartRecoveryRepository();
		$repo->markRecoveryDelivery( '+34612345678', 'read' );

		$this->assertNotSame( '', $captured_sql, 'A SQL query must be executed for "read"' );
	}

	public function test_mark_recovery_delivery_ignores_empty_phone(): void {
		$query_called = false;

		$GLOBALS['wpdb'] = new class( $query_called ) extends \stdClass {
			public string $prefix = 'wp_';
			private bool &$called;

			public function __construct( bool &$called ) {
				$this->called = &$called;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->called = true;
				return 1;
			}
		};

		$repo = new CartRecoveryRepository();
		$repo->markRecoveryDelivery( '', 'delivered' );

		$this->assertFalse( $query_called, 'Empty phone must short-circuit before any DB call' );
	}

	// -------------------------------------------------------------------------
	// markStatus()
	// -------------------------------------------------------------------------

	public function test_mark_status_calls_wpdb_update(): void {
		$called = false;

		$wpdb = new class( $called ) extends \stdClass {
			public string $prefix = 'wp_';
			private bool &$called;

			public function __construct( bool &$called ) {
				$this->called = &$called;
			}

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int {
				$this->called = true;
				return 1;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repo = new CartRecoveryRepository();
		$repo->markStatus( 5, 'recovered' );

		$this->assertTrue( $called );
	}
}
