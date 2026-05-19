<?php
/**
 * Unit tests for DashboardPage and SettingsPage.
 *
 * @package CartPinger\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Admin;

use CartPinger\Admin\DashboardPage;
use CartPinger\Admin\SettingsPage;
use WP_Mock\Tools\TestCase;

/**
 * Class DashboardPageTest
 */
class DashboardPageTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// DashboardPage::render() — capability guard
	// -------------------------------------------------------------------------

	public function test_dashboard_render_dies_without_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_die' )
			->once()
			->andThrow( new \RuntimeException( 'wp_die called' ) );

		\WP_Mock::userFunction( 'esc_html__' )
			->andReturnArg( 0 );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'wp_die called' );

		DashboardPage::render();
	}

	public function test_dashboard_render_outputs_mount_point(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( true );

		\WP_Mock::userFunction( 'wp_localize_script' )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'rest_url' )
			->andReturn( 'https://example.com/wp-json/' );

		\WP_Mock::userFunction( 'esc_url_raw' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( 'wp_create_nonce' )
			->andReturn( 'testnonce' );

		\WP_Mock::userFunction( 'esc_html_e' )
			->andReturn( null );

		ob_start();
		DashboardPage::render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cartpinger-dashboard-app', $output );
		$this->assertStringContainsString( 'class="wrap"', $output );
	}

	// -------------------------------------------------------------------------
	// SettingsPage::render() — capability guard
	// -------------------------------------------------------------------------

	public function test_settings_render_dies_without_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_die' )
			->once()
			->andThrow( new \RuntimeException( 'wp_die called' ) );

		\WP_Mock::userFunction( 'esc_html__' )
			->andReturnArg( 0 );

		$this->expectException( \RuntimeException::class );

		SettingsPage::render();
	}

	public function test_settings_render_outputs_mount_point(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( true );

		\WP_Mock::userFunction( 'wp_localize_script' )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'rest_url' )
			->andReturn( 'https://example.com/wp-json/' );

		\WP_Mock::userFunction( 'esc_url_raw' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( 'wp_create_nonce' )
			->andReturn( 'testnonce' );

		\WP_Mock::userFunction( 'esc_html_e' )
			->andReturn( null );

		ob_start();
		SettingsPage::render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cartpinger-settings-app', $output );
		$this->assertStringContainsString( 'class="wrap"', $output );
	}

	// -------------------------------------------------------------------------
	// AdminBootstrap::registerMenu() — capability
	// -------------------------------------------------------------------------

	public function test_register_menu_uses_manage_woocommerce_capability(): void {
		\WP_Mock::userFunction( 'add_menu_page' )
			->once()
			->withArgs( function ( string $page_title, string $menu_title, string $capability ) {
				return 'manage_woocommerce' === $capability;
			} )
			->andReturn( 'cartpinger' );

		\WP_Mock::userFunction( 'add_submenu_page' )
			->times( 4 )
			->andReturn( 'cartpinger' );

		\WP_Mock::userFunction( 'esc_html__' )
			->andReturnArg( 0 );

		\CartPinger\Admin\AdminBootstrap::registerMenu();

		$this->addToAssertionCount( 1 );
	}
}
