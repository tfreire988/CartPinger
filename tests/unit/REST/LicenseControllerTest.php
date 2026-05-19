<?php
/**
 * Unit tests for LicenseController.
 *
 * @package CartPinger\Tests\Unit\REST
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\REST;

use CartPinger\REST\LicenseController;
use WP_Mock\Tools\TestCase;

/**
 * Class LicenseControllerTest
 */
class LicenseControllerTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_check_permission_returns_false_without_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( false );

		$this->assertFalse( LicenseController::checkPermission() );
	}

	public function test_check_permission_returns_true_with_capability(): void {
		\WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_woocommerce' )
			->andReturn( true );

		$this->assertTrue( LicenseController::checkPermission() );
	}

	public function test_handle_get_returns_not_pro_when_no_license(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_status', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_key', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_last_check', 0 )
			->andReturn( 0 );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_last_fail_reason', '' )
			->andReturn( '' );

		$request  = new \WP_REST_Request();
		$response = LicenseController::handleGet( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertFalse( $data['is_pro'] );
		$this->assertSame( '', $data['license_key'] );
	}

	public function test_handle_get_returns_pro_when_active(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_status', '' )
			->andReturn( 'active' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_key', '' )
			->andReturn( 'ABCD1234EFGH5678' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_last_check', 0 )
			->andReturn( 0 );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_last_fail_reason', '' )
			->andReturn( '' );

		$request  = new \WP_REST_Request();
		$response = LicenseController::handleGet( $request );

		$data = $response->get_data();
		$this->assertTrue( $data['is_pro'] );
		$this->assertStringContainsString( '****', $data['license_key'] );
	}

	public function test_handle_delete_deactivates_license(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_license_key', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_license_key', '', false )
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_license_status', '', false )
			->andReturn( true );

		$request  = new \WP_REST_Request();
		$response = LicenseController::handleDelete( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
	}
}
