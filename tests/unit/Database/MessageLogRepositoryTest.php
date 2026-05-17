<?php
/**
 * Unit tests for MessageLogRepository::applyDeliveryStatus().
 *
 * @package CartPinger\Tests\Unit\Database
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Database;

use CartPinger\Database\Repositories\MessageLogRepository;
use WP_Mock\Tools\TestCase;

/**
 * Class MessageLogRepositoryTest
 */
class MessageLogRepositoryTest extends TestCase {

	private const WAMID = 'wamid.test_abc123';
	private const TS    = 1_704_067_200; // 2024-01-01 00:00:00 UTC

	/** @var mixed Original $wpdb. */
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
	 * Build a $wpdb stub where get_row() returns $row and update() records data.
	 *
	 * @param object|null         $row     Value returned by get_row().
	 * @param array<string,mixed> $updates Collects data arrays passed to update().
	 */
	private function makeWpdb( ?object $row, array &$updates ): object {
		return new class( $row, $updates ) extends \stdClass {
			public string $prefix = 'wp_';

			private ?object $row;
			/** @var array<string,mixed> */
			private array $updates;

			/** @param array<string,mixed> $u */
			public function __construct( ?object $row, array &$u ) {
				$this->row     = $row;
				$this->updates = &$u;
			}

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return object|null */
			public function get_row( string $sql ): ?object {
				return $this->row;
			}

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int {
				$this->updates[] = $data;
				return 1;
			}
		};
	}

	private function makeRow( int $id = 1 ): object {
		$row                  = new \stdClass();
		$row->id              = $id;
		$row->template_name   = 'order_confirmed';
		$row->recipient_phone = '+34612345678';
		return $row;
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — unknown status
	// -------------------------------------------------------------------------

	public function test_returns_null_for_unknown_status(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeRow(), $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'queued', self::TS );

		$this->assertNull( $result );
		$this->assertEmpty( $updates, 'No UPDATE must be issued for unknown status' );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — wamid not found
	// -------------------------------------------------------------------------

	public function test_returns_null_when_wamid_not_found(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( null, $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( 'unknown-wamid', 'delivered', self::TS );

		$this->assertNull( $result );
		$this->assertEmpty( $updates );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — 'delivered'
	// -------------------------------------------------------------------------

	public function test_updates_delivered_at_for_delivered_status(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeRow(), $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'delivered', self::TS );

		$this->assertInstanceOf( \stdClass::class, $result );
		$this->assertSame( 'delivered', $result->status );

		$this->assertCount( 1, $updates );
		$this->assertSame( 'delivered', $updates[0]['status'] );
		$this->assertArrayHasKey( 'delivered_at', $updates[0] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::TS ), $updates[0]['delivered_at'] );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — 'read'
	// -------------------------------------------------------------------------

	public function test_updates_read_at_for_read_status(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeRow(), $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'read', self::TS );

		$this->assertInstanceOf( \stdClass::class, $result );
		$this->assertSame( 'read', $result->status );
		$this->assertArrayHasKey( 'read_at', $updates[0] );
		$this->assertSame( gmdate( 'Y-m-d H:i:s', self::TS ), $updates[0]['read_at'] );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — 'sent'
	// -------------------------------------------------------------------------

	public function test_updates_sent_at_for_sent_status(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeRow(), $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'sent', self::TS );

		$this->assertInstanceOf( \stdClass::class, $result );
		$this->assertSame( 'sent', $result->status );
		$this->assertArrayHasKey( 'sent_at', $updates[0] );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — 'failed' (no timestamp column)
	// -------------------------------------------------------------------------

	public function test_updates_status_without_timestamp_for_failed(): void {
		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeRow(), $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'failed', self::TS );

		$this->assertInstanceOf( \stdClass::class, $result );
		$this->assertSame( 'failed', $result->status );
		$this->assertCount( 1, $updates );
		$this->assertSame( 'failed', $updates[0]['status'] );
		$this->assertArrayNotHasKey( 'sent_at', $updates[0] );
		$this->assertArrayNotHasKey( 'delivered_at', $updates[0] );
		$this->assertArrayNotHasKey( 'read_at', $updates[0] );
	}

	// -------------------------------------------------------------------------
	// applyDeliveryStatus() — returned row carries correct fields
	// -------------------------------------------------------------------------

	public function test_returned_row_carries_template_name_and_phone(): void {
		$row               = $this->makeRow();
		$row->template_name   = 'abandoned_cart_recovery';
		$row->recipient_phone = '+34699999999';

		$updates         = array();
		$GLOBALS['wpdb'] = $this->makeWpdb( $row, $updates );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$repo   = new MessageLogRepository();
		$result = $repo->applyDeliveryStatus( self::WAMID, 'read', self::TS );

		$this->assertSame( 'abandoned_cart_recovery', $result->template_name );
		$this->assertSame( '+34699999999', $result->recipient_phone );
	}
}
