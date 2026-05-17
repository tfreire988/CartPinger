<?php
/**
 * Unit tests for AbandonedCartTracker.
 *
 * @package CartPinger\Tests\Unit\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WooCommerce;

use CartPinger\WooCommerce\AbandonedCartTracker;
use WP_Mock\Tools\TestCase;

/**
 * Class AbandonedCartTrackerTest
 */
class AbandonedCartTrackerTest extends TestCase {

	/** @var mixed Original $wpdb value before any test overrides it. */
	private mixed $original_wpdb = null;

	public function setUp(): void {
		\WP_Mock::setUp();

		global $wpdb;
		$this->original_wpdb = $wpdb;

		// Always start with an empty cart so tests are isolated.
		WC()->cart->empty_cart();
	}

	public function tearDown(): void {
		global $wpdb;
		$wpdb = $this->original_wpdb;

		WC()->cart->empty_cart();

		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_adds_checkout_update_action(): void {
		\WP_Mock::expectActionAdded(
			'woocommerce_checkout_update_order_review',
			array( AbandonedCartTracker::class, 'onCheckoutUpdate' ),
			10,
			1
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_thankyou',
			array( AbandonedCartTracker::class, 'onOrderComplete' ),
			10,
			1
		);

		\WP_Mock::expectActionAdded(
			AbandonedCartTracker::CRON_HOOK,
			array( AbandonedCartTracker::class, 'processPending' )
		);

		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( AbandonedCartTracker::CRON_HOOK )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_schedule_event' )
			->once();

		AbandonedCartTracker::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// onCheckoutUpdate() — phone absent → early exit
	// -------------------------------------------------------------------------

	public function test_does_nothing_when_phone_is_empty(): void {
		// No $wpdb or WP functions expected.
		AbandonedCartTracker::onCheckoutUpdate( 'billing_phone=&cartpinger_whatsapp_consent=1' );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// onCheckoutUpdate() — consent = 0 → revokeConsent() called
	// -------------------------------------------------------------------------

	public function test_revokes_pending_record_when_consent_is_withdrawn(): void {
		$update_called = false;

		// Spy: revokeConsent() calls $wpdb->update().
		$GLOBALS['wpdb'] = new class( $update_called ) extends \stdClass {
			public string $prefix = 'wp_';
			/** @var bool */
			private bool $called;

			public function __construct( bool &$called ) {
				$this->called = &$called;
			}

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int {
				$this->called = true;
				return 1;
			}
		};

		// POST data: valid phone, consent field absent (unchecked checkbox).
		AbandonedCartTracker::onCheckoutUpdate( 'billing_phone=%2B34612345678' );

		$this->assertTrue( $update_called, 'revokeConsent() must call $wpdb->update() when consent is absent' );
	}

	public function test_session_stores_zero_when_consent_is_withdrawn(): void {
		$GLOBALS['wpdb'] = new class() extends \stdClass {
			public string $prefix = 'wp_';

			/** @param array<string,mixed> $data @param array<string,mixed> $where */
			public function update( string $table, array $data, array $where ): int {
				return 1;
			}
		};

		AbandonedCartTracker::onCheckoutUpdate( 'billing_phone=%2B34612345678' );

		$this->assertSame( '0', WC()->session->get( 'cartpinger_whatsapp_consent' ) );
	}

	// -------------------------------------------------------------------------
	// onCheckoutUpdate() — consent = 1 + non-empty cart → upsert called
	// -------------------------------------------------------------------------

	public function test_upserts_cart_when_consent_is_given(): void {
		// Pre-load cart with one product so is_empty() returns false.
		WC()->cart->add_to_cart( 1, 2 );

		$query_called = false;

		// Spy: CartRecoveryRepository::upsert() calls $wpdb->query() (INSERT … ON DUPLICATE KEY).
		$GLOBALS['wpdb'] = new class( $query_called ) extends \stdClass {
			public string $prefix    = 'wp_';
			public int    $insert_id = 5;
			/** @var bool */
			private bool $called;

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

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnArg( 0 );

		AbandonedCartTracker::onCheckoutUpdate(
			'billing_phone=%2B34612345678&billing_first_name=Mar%C3%ADa&cartpinger_whatsapp_consent=1'
		);

		$this->assertTrue( $query_called, 'upsert() must call $wpdb->query() when consent is given and cart is non-empty' );
	}

	public function test_session_stores_one_when_consent_is_given(): void {
		WC()->cart->add_to_cart( 1, 1 );

		$GLOBALS['wpdb'] = new class() extends \stdClass {
			public string $prefix    = 'wp_';
			public int    $insert_id = 1;

			/** @param mixed ...$args */
			public function prepare( string $query, mixed ...$args ): string {
				return $query;
			}

			/** @return int|false */
			public function query( string $sql ): int|false {
				return 1;
			}
		};

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnArg( 0 );

		AbandonedCartTracker::onCheckoutUpdate(
			'billing_phone=%2B34612345678&cartpinger_whatsapp_consent=1'
		);

		$this->assertSame( '1', WC()->session->get( 'cartpinger_whatsapp_consent' ) );
	}

	public function test_does_not_upsert_when_consent_given_but_cart_is_empty(): void {
		// Cart is empty (setUp cleared it). upsert must NOT be called.
		// $wpdb->query() is NOT expected to be called — we use a spy to verify.
		$query_called = false;

		$GLOBALS['wpdb'] = new class( $query_called ) extends \stdClass {
			public string $prefix = 'wp_';
			/** @var bool */
			private bool $called;

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

		AbandonedCartTracker::onCheckoutUpdate(
			'billing_phone=%2B34612345678&cartpinger_whatsapp_consent=1'
		);

		$this->assertFalse( $query_called, 'upsert() must NOT be called when the cart is empty' );
	}

	// -------------------------------------------------------------------------
	// onOrderComplete() — no-op when order not found
	// -------------------------------------------------------------------------

	public function test_on_order_complete_does_nothing_when_wc_get_order_returns_false(): void {
		\WP_Mock::userFunction( 'wc_get_order' )
			->with( 42 )
			->andReturn( false );

		AbandonedCartTracker::onOrderComplete( 42 );

		$this->addToAssertionCount( 1 );
	}
}
