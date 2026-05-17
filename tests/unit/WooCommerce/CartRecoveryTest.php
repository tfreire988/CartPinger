<?php
/**
 * Unit tests for CartRecovery.
 *
 * @package CartPinger\Tests\Unit\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WooCommerce;

use CartPinger\WooCommerce\CartRecovery;
use WP_Mock\Tools\TestCase;

/**
 * Class CartRecoveryTest
 */
class CartRecoveryTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_adds_init_action(): void {
		\WP_Mock::expectActionAdded(
			'init',
			array( CartRecovery::class, 'handleRecoveryRequest' )
		);

		CartRecovery::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// handleRecoveryRequest() — early exits (no token in $_GET)
	// -------------------------------------------------------------------------

	public function test_does_nothing_when_no_query_param(): void {
		// $_GET is empty — no WordPress functions should be called.
		unset( $_GET['cartpinger_recover'] );

		CartRecovery::handleRecoveryRequest();

		$this->addToAssertionCount( 1 );
	}

	public function test_does_nothing_when_token_is_wrong_length(): void {
		$_GET['cartpinger_recover'] = 'short';

		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( 'wp_unslash' )
			->andReturnArg( 0 );

		CartRecovery::handleRecoveryRequest();

		$this->addToAssertionCount( 1 );

		unset( $_GET['cartpinger_recover'] );
	}
}
