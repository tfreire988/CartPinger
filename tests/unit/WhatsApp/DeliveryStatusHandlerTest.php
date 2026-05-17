<?php
/**
 * Unit tests for DeliveryStatusHandler.
 *
 * $wpdb is replaced with an anonymous-class spy so MessageLogRepository and
 * CartRecoveryRepository can be exercised without a real database connection.
 *
 * @package CartPinger\Tests\Unit\WhatsApp
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WhatsApp;

use CartPinger\WhatsApp\DeliveryStatusHandler;
use WP_Mock\Tools\TestCase;

/**
 * Class DeliveryStatusHandlerTest
 */
class DeliveryStatusHandlerTest extends TestCase {

	private const WAMID = 'wamid.HBgNMzQ2MTIzNDU2NzgVAgARGBI';
	private const PHONE = '+34612345678';
	private const TS    = 1_704_067_200; // 2024-01-01 00:00:00 UTC

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
	 * Build the minimal $wpdb spy used by most tests.
	 *
	 * @param object|null          $row         Returned by get_row() (null = not found).
	 * @param array<string,mixed>  $updates     Collects each update() call's $data arg.
	 * @param array<string,mixed>  $queries     Collects each query() call's SQL string.
	 */
	private function makeWpdb(
		?object $row,
		array &$updates,
		array &$queries
	): object {
		return new class( $row, $updates, $queries ) extends \stdClass {
			public string $prefix = 'wp_';

			private ?object $row;
			/** @var array<string,mixed> */
			private array $updates;
			/** @var array<string,mixed> */
			private array $queries;

			/** @param array<string,mixed> $u @param array<string,mixed> $q */
			public function __construct( ?object $row, array &$u, array &$q ) {
				$this->row     = $row;
				$this->updates = &$u;
				$this->queries = &$q;
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

			/** @return int|false */
			public function query( string $sql ): int|false {
				$this->queries[] = $sql;
				return 1;
			}
		};
	}

	/**
	 * Build a standard messages-log row stub.
	 *
	 * @param string $template_name Template name stored on the row.
	 */
	private function makeLogRow( string $template_name = 'order_confirmed' ): object {
		$row                 = new \stdClass();
		$row->id             = 1;
		$row->template_name  = $template_name;
		$row->recipient_phone = self::PHONE;
		return $row;
	}

	/**
	 * Build a minimal entry payload for a given status.
	 *
	 * @param string $status Meta status code.
	 * @param string $wamid  Message ID.
	 */
	private function makeEntry( string $status, string $wamid = self::WAMID ): array {
		return array(
			'id'      => 'WABA_ID',
			'changes' => array(
				array(
					'field' => 'messages',
					'value' => array(
						'messaging_product' => 'whatsapp',
						'statuses'          => array(
							array(
								'id'           => $wamid,
								'status'       => $status,
								'timestamp'    => (string) self::TS,
								'recipient_id' => self::PHONE,
							),
						),
					),
				),
			),
		);
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_hooks_entry_action(): void {
		\WP_Mock::expectActionAdded(
			'cartpinger_webhook_entry',
			array( DeliveryStatusHandler::class, 'handleEntry' )
		);

		DeliveryStatusHandler::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — early exits
	// -------------------------------------------------------------------------

	public function test_ignores_non_array_entry(): void {
		// No $wpdb calls expected.
		DeliveryStatusHandler::handleEntry( 'not-an-array' );

		$this->addToAssertionCount( 1 );
	}

	public function test_ignores_entry_without_changes_key(): void {
		DeliveryStatusHandler::handleEntry( array( 'id' => 'WABA_ID' ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_ignores_change_with_wrong_field(): void {
		$entry = array(
			'changes' => array(
				array(
					'field' => 'account_review',
					'value' => array(),
				),
			),
		);

		DeliveryStatusHandler::handleEntry( $entry );

		$this->addToAssertionCount( 1 );
	}

	public function test_ignores_entry_without_statuses(): void {
		$entry = array(
			'changes' => array(
				array(
					'field' => 'messages',
					'value' => array( 'messages' => array() ), // no 'statuses' key
				),
			),
		);

		DeliveryStatusHandler::handleEntry( $entry );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — unknown status codes
	// -------------------------------------------------------------------------

	public function test_ignores_unknown_status_code(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeLogRow(), $updates, $queries );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'queued' ) );

		// applyDeliveryStatus returns null for unknown codes → no DB update.
		$this->assertEmpty( $updates, 'Unknown status must not trigger a DB update' );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — 'delivered' status
	// -------------------------------------------------------------------------

	public function test_processes_delivered_status_for_non_recovery_template(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeLogRow( 'order_confirmed' ), $updates, $queries );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'delivered' ) );

		$this->assertCount( 1, $updates, 'Exactly one messages_log UPDATE expected' );
		$this->assertSame( 'delivered', $updates[0]['status'] );
		$this->assertArrayHasKey( 'delivered_at', $updates[0] );
		$this->assertEmpty( $queries, 'No recovery UPDATE expected for non-recovery template' );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — 'read' status
	// -------------------------------------------------------------------------

	public function test_processes_read_status(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeLogRow( 'order_completed' ), $updates, $queries );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'read' ) );

		$this->assertCount( 1, $updates );
		$this->assertSame( 'read', $updates[0]['status'] );
		$this->assertArrayHasKey( 'read_at', $updates[0] );
		$this->assertEmpty( $queries );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — recovery template cross-reference
	// -------------------------------------------------------------------------

	public function test_updates_recovery_row_when_template_is_abandoned_cart_recovery(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb(
			$this->makeLogRow( 'abandoned_cart_recovery' ),
			$updates,
			$queries
		);

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'delivered' ) );

		$this->assertCount( 1, $updates, 'messages_log UPDATE expected' );
		$this->assertSame( 'delivered', $updates[0]['status'] );
		$this->assertCount( 1, $queries, 'Recovery UPDATE via markRecoveryDelivery() expected' );
	}

	public function test_updates_recovery_row_to_read_status(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb(
			$this->makeLogRow( 'abandoned_cart_recovery' ),
			$updates,
			$queries
		);

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'read' ) );

		$this->assertCount( 1, $updates );
		$this->assertSame( 'read', $updates[0]['status'] );
		$this->assertCount( 1, $queries, 'Recovery UPDATE expected for read status' );
	}

	public function test_does_not_update_recovery_for_non_recovery_template(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb(
			$this->makeLogRow( 'order_confirmed' ),
			$updates,
			$queries
		);

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'read' ) );

		$this->assertEmpty( $queries, 'No recovery UPDATE for non-recovery template' );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — wamid not found in DB
	// -------------------------------------------------------------------------

	public function test_does_nothing_when_wamid_not_found_in_db(): void {
		$updates = array();
		$queries = array();

		// get_row() returns null → wamid not in messages_log.
		$GLOBALS['wpdb'] = $this->makeWpdb( null, $updates, $queries );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'delivered' ) );

		$this->assertEmpty( $updates );
		$this->assertEmpty( $queries );
	}

	// -------------------------------------------------------------------------
	// handleEntry() — 'failed' status (no timestamp column)
	// -------------------------------------------------------------------------

	public function test_processes_failed_status_without_timestamp_column(): void {
		$updates = array();
		$queries = array();

		$GLOBALS['wpdb'] = $this->makeWpdb( $this->makeLogRow(), $updates, $queries );

		\WP_Mock::userFunction( 'esc_sql' )->andReturnArg( 0 );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		DeliveryStatusHandler::handleEntry( $this->makeEntry( 'failed' ) );

		$this->assertCount( 1, $updates );
		$this->assertSame( 'failed', $updates[0]['status'] );
		// 'failed' has no timestamp column — ensure no stray key is written.
		$this->assertArrayNotHasKey( 'sent_at', $updates[0] );
		$this->assertArrayNotHasKey( 'delivered_at', $updates[0] );
		$this->assertArrayNotHasKey( 'read_at', $updates[0] );
	}
}
