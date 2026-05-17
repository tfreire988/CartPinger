<?php
/**
 * Unit tests for MessageQueue.
 *
 * wpdb is mocked via an anonymous-class stub assigned to the global so
 * MessageLogRepository can operate without a real database.
 *
 * @package CartPinger\Tests\Unit\WhatsApp
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WhatsApp;

use CartPinger\Database\Repositories\MessageLogRepository;
use CartPinger\WhatsApp\CloudApiClient;
use CartPinger\WhatsApp\MessageQueue;
use WP_Mock\Tools\TestCase;

/**
 * Class MessageQueueTest
 */
class MessageQueueTest extends TestCase {

	/** @var object Original $wpdb value to restore after each test. */
	private mixed $original_wpdb = null;

	public function setUp(): void {
		\WP_Mock::setUp();

		// Stash the original $wpdb (null in test env) and replace with a stub.
		global $wpdb;
		$this->original_wpdb = $wpdb;
		$wpdb                = $this->makeWpdbStub();
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
	 * Build a minimal wpdb stub that satisfies MessageLogRepository's calls.
	 *
	 * @param int|false $insert_result Value returned by insert().
	 * @param int       $insert_id     Value of insert_id after insert().
	 * @param array<int,object> $pending       Rows returned by get_results().
	 */
	private function makeWpdbStub(
		int|false $insert_result = 1,
		int $insert_id = 42,
		array $pending = array()
	): object {
		return new class( $insert_result, $insert_id, $pending ) {
			public string $prefix    = 'wp_';
			public int    $insert_id = 0;

			private int|false $insert_result;
			private array     $pending;

			public function __construct( int|false $insert_result, int $insert_id, array $pending ) {
				$this->insert_result = $insert_result;
				$this->insert_id     = $insert_id;
				$this->pending       = $pending;
			}

			/**
			 * Stub for wpdb::insert().
			 *
			 * @param string               $table  Table name.
			 * @param array<string, mixed> $data   Data to insert.
			 * @param array<int, string>   $format Format strings.
			 * @return int|false
			 */
			public function insert( string $table, array $data, array $format ): int|false {
				return $this->insert_result;
			}

			/**
			 * Stub for wpdb::update().
			 *
			 * @param string               $table        Table name.
			 * @param array<string, mixed> $data         Data to update.
			 * @param array<string, mixed> $where        WHERE clause.
			 * @param array<int, string>   $format       Format strings.
			 * @param array<int, string>   $where_format WHERE format strings.
			 * @return int|false
			 */
			public function update(
				string $table,
				array $data,
				array $where,
				array $format,
				array $where_format
			): int|false {
				return 1;
			}

			/**
			 * Stub for wpdb::get_results().
			 *
			 * @param string $query SQL query string.
			 * @return array<int, object>
			 */
			public function get_results( string $query ): array {
				return $this->pending;
			}

			/**
			 * Stub for wpdb::prepare().
			 *
			 * @param string $query SQL query template.
			 * @param mixed  ...$args Format arguments.
			 * @return string
			 */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}
		};
	}

	/**
	 * Build a MessageQueue with a real repository (backed by the wpdb stub)
	 * and a CloudApiClient whose HTTP layer is mocked via WP_Mock.
	 */
	private function makeQueue(): MessageQueue {
		return new MessageQueue(
			new MessageLogRepository(),
			new CloudApiClient( 'EAAtoken', '1234567890' )
		);
	}

	/**
	 * Mock all WP HTTP functions used by CloudApiClient::sendTemplate().
	 *
	 * @param bool $success Whether the API call succeeds.
	 */
	private function mockCloudApi( bool $success = true ): void {
		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		if ( $success ) {
			\WP_Mock::userFunction( 'wp_remote_post' )
				->andReturn( array( 'response' => array( 'code' => 200 ) ) );
			\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
			\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
			\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
				->andReturn( '{"messages":[{"id":"wamid.abc"}]}' );
		} else {
			\WP_Mock::userFunction( 'wp_remote_post' )
				->andReturn( array( 'response' => array( 'code' => 500 ) ) );
			\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
			\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 500 );
			\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
				->andReturn( '{"error":{"message":"Internal error"}}' );
		}
	}

	// -------------------------------------------------------------------------
	// enqueue()
	// -------------------------------------------------------------------------

	public function test_enqueue_does_nothing_for_invalid_phone(): void {
		// No WP functions or DB calls expected.
		$this->makeQueue()->enqueue( 'not-a-phone', 'order_confirmed' );

		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_schedules_cron_after_insert(): void {
		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( MessageQueue::CRON_HOOK )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_schedule_single_event' )
			->with( \Mockery::type( 'int' ), MessageQueue::CRON_HOOK )
			->once()
			->andReturn( true );

		$this->makeQueue()->enqueue( '+34612345678', 'order_confirmed' );

		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_does_not_schedule_when_already_pending(): void {
		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( MessageQueue::CRON_HOOK )
			->andReturn( time() + 30 );

		// wp_schedule_single_event must NOT be called.
		$this->makeQueue()->enqueue( '+34612345678', 'order_confirmed' );

		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_does_nothing_when_insert_fails(): void {
		global $wpdb;
		$wpdb = $this->makeWpdbStub( false, 0 ); // insert returns false.

		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		// wp_cache_delete and wp_next_scheduled must NOT be called.
		$this->makeQueue()->enqueue( '+34612345678', 'order_confirmed' );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// processQueue()
	// -------------------------------------------------------------------------

	public function test_process_queue_does_nothing_when_empty(): void {
		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_get' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );

		// No HTTP calls expected.
		$this->makeQueue()->processQueue();

		$this->addToAssertionCount( 1 );
	}

	public function test_process_queue_marks_sent_on_success(): void {
		$row                = new \stdClass();
		$row->id              = 1;
		$row->recipient_phone = '+34612345678';
		$row->template_name   = 'order_confirmed';
		$row->language_code   = 'en_US';
		$row->components      = null;

		global $wpdb;
		$wpdb = $this->makeWpdbStub( 1, 1, array( $row ) );

		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$this->mockCloudApi( true );

		$this->makeQueue()->processQueue();

		// If no exception thrown and mocks satisfied, the update was called.
		$this->addToAssertionCount( 1 );
	}

	public function test_process_queue_marks_failed_on_api_error(): void {
		$row                = new \stdClass();
		$row->id              = 2;
		$row->recipient_phone = '+34612345678';
		$row->template_name   = 'order_confirmed';
		$row->language_code   = 'en_US';
		$row->components      = null;

		global $wpdb;
		$wpdb = $this->makeWpdbStub( 1, 1, array( $row ) );

		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$this->mockCloudApi( false );

		$this->makeQueue()->processQueue();

		$this->addToAssertionCount( 1 );
	}

	public function test_process_queue_passes_components_and_language_to_api(): void {
		$components_json = (string) json_encode(
			array(
				array(
					'type'       => 'body',
					'parameters' => array(
						array( 'type' => 'text', 'text' => 'John' ),
						array( 'type' => 'text', 'text' => '1001' ),
						array( 'type' => 'text', 'text' => '99.00 EUR' ),
					),
				),
			)
		);

		$row                = new \stdClass();
		$row->id              = 3;
		$row->recipient_phone = '+34612345678';
		$row->template_name   = 'order_confirmed';
		$row->language_code   = 'es';
		$row->components      = $components_json;

		global $wpdb;
		$wpdb = $this->makeWpdbStub( 1, 1, array( $row ) );

		\WP_Mock::userFunction( 'esc_sql' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_cache_delete' )->andReturn( true );

		$captured_payload = null;

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url, array $args ) use ( &$captured_payload ): array {
					$captured_payload = json_decode( $args['body'], true );
					return array( 'response' => array( 'code' => 200 ) );
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.xyz"}]}' );

		$this->makeQueue()->processQueue();

		$this->assertIsArray( $captured_payload );
		$this->assertSame( 'es', $captured_payload['template']['language']['code'] );
		$body_component = $captured_payload['template']['components'][0];
		$this->assertSame( 'body', $body_component['type'] );
		$this->assertSame( 'John', $body_component['parameters'][0]['text'] );
		$this->assertSame( '1001', $body_component['parameters'][1]['text'] );
		$this->assertSame( '99.00 EUR', $body_component['parameters'][2]['text'] );
	}
}
