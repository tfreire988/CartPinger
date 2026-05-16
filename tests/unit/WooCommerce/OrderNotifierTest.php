<?php
/**
 * Unit tests for OrderNotifier.
 *
 * Tests run through the full stack (OrderNotifier → CloudApiClient → WP HTTP)
 * by mocking the WordPress HTTP functions used by CloudApiClient internally.
 *
 * @package WhatsCom\Tests\Unit\WooCommerce
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\WooCommerce;

use WhatsCom\Support\Encryptor;
use WhatsCom\WooCommerce\OrderNotifier;
use WP_Mock\Tools\TestCase;

/**
 * Class OrderNotifierTest
 */
class OrderNotifierTest extends TestCase {

	private const PHONE_ID       = '1234567890';
	private const ACCESS_TOKEN   = 'EAAtest_access_token_abc';
	private const CUSTOMER_PHONE = '+34612345678';
	private const SALT_AUTH      = 'auth-salt-abcdef';
	private const SALT_SECURE    = 'secure-auth-salt-ghijkl';

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Stub wp_salt() for both keys used by Encryptor::deriveKey().
	 */
	private function mockSalts(): void {
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( self::SALT_AUTH );

		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( self::SALT_SECURE );
	}

	/**
	 * Mock get_option for phone_id and encrypted access_token.
	 * Must be called after mockSalts().
	 */
	private function mockConfigured(): void {
		$encrypted = Encryptor::encrypt( self::ACCESS_TOKEN );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( self::PHONE_ID );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( $encrypted );
	}

	/**
	 * Mock all WP HTTP functions so CloudApiClient's post() returns success.
	 *
	 * Returns the captured decoded request body so assertions can check the
	 * template name and recipient phone.
	 *
	 * @return array<string, mixed>|null Reference to captured body (filled after the call).
	 */
	private function mockHttpSuccess(): mixed {
		$captured = null;

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url, array $args ) use ( &$captured ): array {
					$captured = json_decode( $args['body'], true );
					return array( 'response' => array( 'code' => 200 ) );
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.test123"}]}' );

		return $captured;
	}

	/**
	 * Create a WC_Order stub with a billing phone.
	 *
	 * @param string $phone E.164 or raw phone string.
	 * @return \WC_Order
	 */
	private function makeOrder( string $phone = self::CUSTOMER_PHONE ): \WC_Order {
		$order = new \WC_Order();
		$order->set_billing_phone( $phone );
		return $order;
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_hooks_status_changed_action(): void {
		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( OrderNotifier::class, 'onStatusChanged' ),
			10,
			4
		);

		OrderNotifier::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// onStatusChanged() — early exits
	// -------------------------------------------------------------------------

	public function test_does_nothing_for_unlisted_status(): void {
		// 'pending' is not in STATUS_TEMPLATES — no get_option or HTTP calls expected.
		OrderNotifier::onStatusChanged( 1, 'pending', 'on-hold', $this->makeOrder() );

		$this->addToAssertionCount( 1 );
	}

	public function test_does_nothing_when_phone_id_missing(): void {
		$this->mockSalts();

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( '' );

		OrderNotifier::onStatusChanged( 1, 'pending', 'processing', $this->makeOrder() );

		$this->addToAssertionCount( 1 );
	}

	public function test_does_nothing_when_customer_phone_is_invalid(): void {
		$this->mockSalts();
		$this->mockConfigured();

		// Billing phone is empty — Sanitizer::phone() returns ''.
		OrderNotifier::onStatusChanged( 1, 'pending', 'processing', $this->makeOrder( '' ) );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// onStatusChanged() — successful sends
	// -------------------------------------------------------------------------

	public function test_sends_order_confirmed_template_on_processing(): void {
		$this->mockSalts();
		$this->mockConfigured();
		$captured = null;

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url, array $args ) use ( &$captured ): array {
					$captured = json_decode( $args['body'], true );
					return array();
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.abc"}]}' );

		OrderNotifier::onStatusChanged( 1, 'pending', 'processing', $this->makeOrder() );

		$this->assertIsArray( $captured );
		$this->assertSame( self::CUSTOMER_PHONE, $captured['to'] );
		$this->assertSame( 'order_confirmed', $captured['template']['name'] );
	}

	public function test_sends_order_completed_template_on_completed(): void {
		$this->mockSalts();
		$this->mockConfigured();
		$captured = null;

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url, array $args ) use ( &$captured ): array {
					$captured = json_decode( $args['body'], true );
					return array();
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.abc"}]}' );

		OrderNotifier::onStatusChanged( 1, 'processing', 'completed', $this->makeOrder() );

		$this->assertIsArray( $captured );
		$this->assertSame( 'order_completed', $captured['template']['name'] );
	}

	public function test_sends_order_cancelled_template_on_cancelled(): void {
		$this->mockSalts();
		$this->mockConfigured();
		$captured = null;

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url, array $args ) use ( &$captured ): array {
					$captured = json_decode( $args['body'], true );
					return array();
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.abc"}]}' );

		OrderNotifier::onStatusChanged( 1, 'processing', 'cancelled', $this->makeOrder() );

		$this->assertIsArray( $captured );
		$this->assertSame( 'order_cancelled', $captured['template']['name'] );
	}
}
