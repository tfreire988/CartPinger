<?php
/**
 * Unit tests for Uninstaller.
 *
 * @package CartPinger\Tests\Unit\Core
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Core;

use CartPinger\Core\Uninstaller;
use WP_Mock\Tools\TestCase;

/**
 * Class UninstallerTest
 */
class UninstallerTest extends TestCase {

	/** @var mixed Saved $wpdb. */
	private mixed $original_wpdb = null;

	public function setUp(): void {
		\WP_Mock::setUp();

		global $wpdb;
		$this->original_wpdb = $wpdb;
		$wpdb                = new class {
			/** @var string */
			public string $prefix = 'wp_';

			/**
			 * Stub for wpdb::query().
			 *
			 * @param string $sql SQL to execute.
			 * @return int|false
			 */
			public function query( string $sql ): int|false {
				return 1;
			}
		};
	}

	public function tearDown(): void {
		global $wpdb;
		$wpdb = $this->original_wpdb;
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// uninstall() — opt-out (default)
	// -------------------------------------------------------------------------

	public function test_uninstall_does_nothing_when_opt_in_is_false(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_delete_data_on_uninstall', false )
			->andReturn( false );

		// delete_option and delete_transient must NOT be called.
		Uninstaller::uninstall();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// uninstall() — opt-in
	// -------------------------------------------------------------------------

	public function test_uninstall_deletes_all_options_when_opted_in(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_delete_data_on_uninstall', false )
			->andReturn( true );

		// Expect delete_option for every option key the plugin owns.
		$expected_keys = array(
			'cartpinger_phone_number_id',
			'cartpinger_waba_id',
			'cartpinger_webhook_verify_token',
			'cartpinger_access_token',
			'cartpinger_app_secret',
			'cartpinger_version',
			'cartpinger_db_version',
			'cartpinger_activated_at',
			'cartpinger_onboarding_completed',
			'cartpinger_delete_data_on_uninstall',
			'cartpinger_widget_enabled',
			'cartpinger_support_phone',
			'cartpinger_widget_message',
			'cartpinger_license_key',
			'cartpinger_license_status',
		);

		foreach ( $expected_keys as $key ) {
			\WP_Mock::userFunction( 'delete_option' )
				->with( $key )
				->once()
				->andReturn( true );
		}

		\WP_Mock::userFunction( 'delete_transient' )
			->with( 'cartpinger_templates_cache' )
			->once()
			->andReturn( true );

		Uninstaller::uninstall();

		$this->addToAssertionCount( 1 );
	}
}
