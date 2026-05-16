<?php
/**
 * Unit tests for SettingsController.
 *
 * @package WhatsCom\Tests\Unit\REST
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\REST;

use WhatsCom\REST\SettingsController;
use WhatsCom\Support\Encryptor;
use WP_Mock\Tools\TestCase;

/**
 * Class SettingsControllerTest
 */
class SettingsControllerTest extends TestCase {

	/** Stable salts reused across tests. */
	private const SALT_AUTH        = 'auth-salt-abcdef';
	private const SALT_SECURE_AUTH = 'secure-auth-salt-ghijkl';

	public function setUp(): void {
		\WP_Mock::setUp();

		// Sanitizer::accessToken() calls sanitize_text_field() — stub as passthrough.
		\WP_Mock::userFunction( 'sanitize_text_field' )
			->andReturnUsing( fn( $val ) => (string) $val );
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
			->andReturn( self::SALT_SECURE_AUTH );
	}

	/**
	 * Produce a valid encrypted blob for $plaintext using the test salts.
	 *
	 * Must be called after mockSalts() because Encryptor::encrypt() calls wp_salt().
	 *
	 * @param string $plaintext Plaintext to encrypt.
	 * @return string Base64-encoded ciphertext.
	 */
	private function encrypt( string $plaintext ): string {
		return Encryptor::encrypt( $plaintext );
	}

	// -------------------------------------------------------------------------
	// handleGet()
	// -------------------------------------------------------------------------

	public function test_get_masks_sensitive_fields_when_set(): void {
		$this->mockSalts();

		$encrypted_token  = $this->encrypt( 'EAAabc123' );
		$encrypted_secret = $this->encrypt( 'deadbeef12345678' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( '1234567890' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_webhook_verify_token', '' )
			->andReturn( 'my-token' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( $encrypted_token );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_app_secret', '' )
			->andReturn( $encrypted_secret );

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
		// get_option returns '' for all fields — CredentialStore::load short-circuits,
		// so no salts or Encryptor calls are needed.
		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_webhook_verify_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_app_secret', '' )
			->andReturn( '' );

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertSame( '', $data['access_token'] );
		$this->assertSame( '', $data['app_secret'] );
		$this->assertFalse( $data['is_configured'] );
	}

	public function test_get_is_configured_false_when_access_token_missing(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_phone_number_id', '' )
			->andReturn( '1234567890' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_webhook_verify_token', '' )
			->andReturn( 'my-token' );

		// access_token option is empty — CredentialStore::load returns '' immediately.
		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_access_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'whatscom_app_secret', '' )
			->andReturn( '' );

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertFalse( $data['is_configured'] );
	}

	// -------------------------------------------------------------------------
	// handlePost()
	// -------------------------------------------------------------------------

	public function test_post_saves_valid_credentials(): void {
		// CredentialStore::save() calls Encryptor::encrypt() → needs salts.
		$this->mockSalts();

		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'verify_token', 'valid-token-abc' );
		$request->set_param( 'access_token', 'EAAvalidtoken123456' );
		$request->set_param( 'app_secret', 'abcdef1234567890abcdef1234567890' );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_phone_number_id', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_webhook_verify_token', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		// CredentialStore::save routes through Encryptor — value will be a base64 blob.
		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_access_token', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'whatscom_app_secret', \Mockery::type( 'string' ), false )
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
