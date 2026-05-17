<?php
/**
 * Unit tests for OnboardingWizard::handleComplete().
 *
 * @package CartPinger\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Admin;

use CartPinger\Admin\OnboardingWizard;
use WP_Mock\Tools\TestCase;

/**
 * Class OnboardingWizardTest
 */
class OnboardingWizardTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		$_GET = array();
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// handleComplete() — early exit when param absent
	// -------------------------------------------------------------------------

	public function test_handle_complete_does_nothing_when_param_absent(): void {
		// $_GET is empty — no WP functions should be called.
		$_GET = array();

		OnboardingWizard::handleComplete();

		// WP_Mock tearDown would flag unexpected calls.
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// handleComplete() — nonce failure
	// -------------------------------------------------------------------------

	public function test_handle_complete_dies_on_bad_nonce(): void {
		$_GET = array(
			'cartpinger_complete' => '1',
			'_wpnonce'          => 'bad-nonce',
		);

		\WP_Mock::userFunction( 'sanitize_key' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_verify_nonce' )
			->with( 'bad-nonce', 'cartpinger_complete_onboarding' )
			->andReturn( false );

		\WP_Mock::userFunction( 'esc_html__' )
			->andReturnUsing( fn( $text ) => $text );

		\WP_Mock::userFunction( 'wp_die' )
			->once()
			->andReturnUsing(
				function (): never {
					throw new \RuntimeException( 'wp_die called' );
				}
			);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die called' );

		OnboardingWizard::handleComplete();
	}

	// -------------------------------------------------------------------------
	// handleComplete() — success path
	// -------------------------------------------------------------------------

	public function test_handle_complete_saves_option_and_redirects(): void {
		$_GET = array(
			'cartpinger_complete' => '1',
			'_wpnonce'          => 'valid-nonce',
		);

		\WP_Mock::userFunction( 'sanitize_key' )
			->andReturnUsing( fn( $val ) => (string) $val );

		\WP_Mock::userFunction( 'wp_verify_nonce' )
			->with( 'valid-nonce', 'cartpinger_complete_onboarding' )
			->andReturn( 1 );

		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_onboarding_completed', true, false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'admin_url' )
			->with( 'admin.php?page=cartpinger' )
			->andReturn( 'http://localhost/wp-admin/admin.php?page=cartpinger' );

		// wp_safe_redirect throws so we never reach exit() in the source.
		// WP_Mock tearDown still verifies all ->once() expectations.
		\WP_Mock::userFunction( 'wp_safe_redirect' )
			->with( 'http://localhost/wp-admin/admin.php?page=cartpinger' )
			->once()
			->andReturnUsing(
				function (): never {
					throw new \RuntimeException( 'redirect_called' );
				}
			);

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'redirect_called' );

		OnboardingWizard::handleComplete();
	}
}
