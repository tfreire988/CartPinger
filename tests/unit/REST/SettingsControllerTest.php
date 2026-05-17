<?php
/**
 * Unit tests for SettingsController.
 *
 * @package CartPinger\Tests\Unit\REST
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\REST;

use CartPinger\REST\SettingsController;
use CartPinger\Support\Encryptor;
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
			->with( 'cartpinger_phone_number_id', '' )
			->andReturn( '1234567890' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_waba_id', '' )
			->andReturn( '9876543210' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_webhook_verify_token', '' )
			->andReturn( 'my-token' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_access_token', '' )
			->andReturn( $encrypted_token );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_app_secret', '' )
			->andReturn( $encrypted_secret );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_delete_data_on_uninstall', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_message', '' )
			->andReturn( '' );

		$response = SettingsController::handleGet( new \WP_REST_Request() );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertSame( '1234567890', $data['phone_number_id'] );
		$this->assertSame( '9876543210', $data['waba_id'] );
		$this->assertSame( 'my-token', $data['verify_token'] );
		$this->assertSame( '***', $data['access_token'] );
		$this->assertSame( '***', $data['app_secret'] );
		$this->assertTrue( $data['is_configured'] );
	}

	public function test_get_returns_empty_strings_when_not_configured(): void {
		// get_option returns '' for all fields — CredentialStore::load short-circuits.
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_phone_number_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_waba_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_webhook_verify_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_access_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_app_secret', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_delete_data_on_uninstall', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_message', '' )
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
			->with( 'cartpinger_phone_number_id', '' )
			->andReturn( '1234567890' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_waba_id', '' )
			->andReturn( '9876543210' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_webhook_verify_token', '' )
			->andReturn( 'my-token' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_access_token', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_app_secret', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_delete_data_on_uninstall', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_message', '' )
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
		$this->mockSalts();

		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'waba_id', '9876543210' );
		$request->set_param( 'verify_token', 'valid-token-abc' );
		$request->set_param( 'access_token', 'EAAvalidtoken123456' );
		$request->set_param( 'app_secret', 'abcdef1234567890abcdef1234567890' );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_phone_number_id', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_waba_id', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_webhook_verify_token', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_access_token', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_app_secret', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_delete_data_on_uninstall', \Mockery::type( 'bool' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_widget_enabled', \Mockery::type( 'bool' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_support_phone', \Mockery::type( 'string' ), false )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->with( 'cartpinger_widget_message', \Mockery::type( 'string' ), false )
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
		$request->set_param( 'waba_id', '9876543210' );
		$request->set_param( 'verify_token', 'valid-token' );
		$request->set_param( 'access_token', 'EAAtoken' );
		$request->set_param( 'app_secret', 'abcdef1234567890' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'phone_number_id', $data['message'] );
	}

	public function test_post_returns_422_for_invalid_waba_id(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'waba_id', 'not-a-number' );
		$request->set_param( 'verify_token', 'valid-token' );
		$request->set_param( 'access_token', 'EAAtoken' );
		$request->set_param( 'app_secret', 'abcdef1234567890' );

		$response = SettingsController::handlePost( $request );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'waba_id', $data['message'] );
	}

	public function test_post_returns_422_for_empty_verify_token(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'phone_number_id', '1234567890' );
		$request->set_param( 'waba_id', '9876543210' );
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
		$request->set_param( 'waba_id', '9876543210' );
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
		$request->set_param( 'waba_id', '9876543210' );
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
