<?php
/**
 * Unit tests for Uninstaller.
 *
 * @package WhatsCom\Tests\Unit\Core
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\Core;

use WhatsCom\Core\Uninstaller;
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
			->with( 'whatscom_delete_data_on_uninstall', false )
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
			->with( 'whatscom_delete_data_on_uninstall', false )
			->andReturn( true );

		// Expect delete_option for every option key the plugin owns.
		$expected_keys = array(
			'whatscom_phone_number_id',
			'whatscom_waba_id',
			'whatscom_webhook_verify_token',
			'whatscom_access_token',
			'whatscom_app_secret',
			'whatscom_version',
			'whatscom_db_version',
			'whatscom_activated_at',
			'whatscom_onboarding_completed',
			'whatscom_delete_data_on_uninstall',
		);

		foreach ( $expected_keys as $key ) {
			\WP_Mock::userFunction( 'delete_option' )
				->with( $key )
				->once()
				->andReturn( true );
		}

		\WP_Mock::userFunction( 'delete_transient' )
			->with( 'whatscom_templates_cache' )
			->once()
			->andReturn( true );

		Uninstaller::uninstall();

		$this->addToAssertionCount( 1 );
	}
}
