<?php
/**
 * Unit tests for TestMessageController.
 *
 * @package WhatsCom\Tests\Unit\REST
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\REST;

use WhatsCom\REST\TestMessageController;
use WhatsCom\Support\Encryptor;
use WP_Mock\Tools\TestCase;

/**
 * Class TestMessageControllerTest
 */
class TestMessageControllerTest extends TestCase {

	private const PHONE_ID     = '1234567890';
	private const ACCESS_TOKEN = 'EAAtest_token_abc';
	private const SALT_AUTH    = 'auth-salt-abcdef';
	private const SALT_SECURE  = 'secure-auth-salt-ghijkl';

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
	 * Mock all WP HTTP functions to simulate a successful Cloud API response.
	 */
	private function mockHttpSuccess(): void {
		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturn( array( 'response' => array( 'code' => 200 ) ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.test123"}]}' );
	}

	// -------------------------------------------------------------------------
	// handlePost() — validation
	// -------------------------------------------------------------------------

	public function test_returns_422_for_invalid_phone(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone', 'not-a-phone' );

		$response = TestMessageController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'phone', $data['message'] );
	}

	public function test_returns_422_when_not_configured(): void {
		$this->mockSalts();

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( '' );

		$request = new \WP_REST_Request();
		$request->set_param( 'phone', '+34612345678' );

		$response = TestMessageController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'configured', $data['message'] );
	}

	// -------------------------------------------------------------------------
	// handlePost() — success
	// -------------------------------------------------------------------------

	public function test_returns_200_and_sends_text_message(): void {
		$this->mockSalts();
		$this->mockConfigured();
		$this->mockHttpSuccess();

		\WP_Mock::userFunction( 'get_bloginfo' )
			->with( 'name' )
			->andReturn( 'My Test Store' );

		\WP_Mock::userFunction( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$request = new \WP_REST_Request();
		$request->set_param( 'phone', '+34612345678' );

		$response = TestMessageController::handlePost( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'Test message sent.', $data['message'] );
	}

	// -------------------------------------------------------------------------
	// handlePost() — Cloud API error
	// -------------------------------------------------------------------------

	public function test_returns_502_when_cloud_api_fails(): void {
		$this->mockSalts();
		$this->mockConfigured();

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( fn( $data ) => (string) json_encode( $data ) );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturn( array( 'response' => array( 'code' => 500 ) ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 500 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"error":{"message":"Internal error"}}' );

		\WP_Mock::userFunction( 'get_bloginfo' )
			->with( 'name' )
			->andReturn( 'My Test Store' );

		\WP_Mock::userFunction( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$request = new \WP_REST_Request();
		$request->set_param( 'phone', '+34612345678' );

		$response = TestMessageController::handlePost( $request );

		$this->assertSame( 502, $response->get_status() );
	}
}
