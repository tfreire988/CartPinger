<?php
/**
 * Unit tests for SettingsController.
 *
 * @package WhatsCom\Tests\Unit\REST
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\REST;

use WhatsCom\REST\SettingsController;
use WP_Mock\Tools\TestCase;

/**
 * Class SettingsControllerTest
 */
class SettingsControllerTest extends TestCase {

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
	 * Mock all four get_option credential calls.
	 *
	 * @param string $phone_id     Phone number ID option value.
	 * @param string $verify_token Verify token option value.
	 * @param string $access_token Access token option value.
	 * @param string $app_secret   App secret option value.
	 */
	private function mockGetOptions(
		string $phone_id = '1234567890',
		string $verify_token = 'my-token',
		string $access_token = 'EAAabc123',
		string $app_secret = 'deadbeef'
	): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( $phone_id );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_webhook_verify_token', '' )
			->andReturn( $verify_token );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( $access_token );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_app_secret', '' )
			->andReturn( $app_secret );
	}

	// -------------------------------------------------------------------------
	// handleGet()
	// -------------------------------------------------------------------------

	public function test_get_masks_sensitive_fields_when_set(): void {
		$this->mockGetOptions();

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertSame( '1234567890', $data['phone_number_id'] );
		$this->assertSame( 'my-token', $data['verify_token'] );
		$this->assertSame( '***', $data['access_token'] );
		$this->assertSame( '***', $data['app_secret'] );
		$this->assertTrue( $data['is_configured'] );
	}

	public function test_get_returns_empty_strings_when_not_configured(): void {
		$this->mockGetOptions( '', '', '', '' );

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertSame( '', $data['access_token'] );
		$this->assertSame( '', $data['app_secret'] );
		$this->assertFalse( $data['is_configured'] );
	}

	public function test_get_is_configured_false_when_any_field_missing(): void {
		$this->mockGetOptions( '1234567890', 'token', '', 'secret' );

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertFalse( $data['is_configured'] );
	}

	// -------------------------------------------------------------------------
	// handlePost()
	// -------------------------------------------------------------------------

	public function test_post_saves_valid_credentials(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'verify_token', 'valid-token-abc' );
		$request->set_param( 'access_token', 'EAAvalidtoken123456' );
		$request->set_param( 'app_secret', 'abcdef1234567890abcdef1234567890' );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_phone_number_id', \WP_Mock\Functions::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_webhook_verify_token', \WP_Mock\Functions::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_access_token', \WP_Mock\Functions::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_app_secret', \WP_Mock\Functions::type( 'string' ), false )
			->once()
			->andReturn( true );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'Settings saved.', $data['message'] );
	}

	public function test_post_returns_422_for_invalid_phone_id(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', 'not-a-number' );
		$request->set_param( 'verify_token', 'valid-token' );
		$request->set_param( 'access_token', 'EAAtoken' );
		$request->set_param( 'app_secret', 'abcdef1234567890' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'phone_number_id', $data['message'] );
	}

	public function test_post_returns_422_for_empty_verify_token(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'verify_token', '' );
		$request->set_param( 'access_token', 'EAAtoken' );
		$request->set_param( 'app_secret', 'abcdef1234567890' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'verify_token', $data['message'] );
	}

	public function test_post_returns_422_for_empty_access_token(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'verify_token', 'valid-token' );
		$request->set_param( 'access_token', '' );
		$request->set_param( 'app_secret', 'abcdef1234567890' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'access_token', $data['message'] );
	}

	public function test_post_returns_422_for_empty_app_secret(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'verify_token', 'valid-token' );
		$request->set_param( 'access_token', 'EAAtoken' );
		$request->set_param( 'app_secret', '' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'app_secret', $data['message'] );
	}
}
