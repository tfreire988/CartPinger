<?php
/**
 * Unit tests for WebhookController.
 *
 * @package CartPinger\Tests\Unit\REST
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\REST;

use CartPinger\REST\WebhookController;
use CartPinger\Support\Encryptor;
use WP_Mock\Tools\TestCase;

/**
 * Class WebhookControllerTest
 */
class WebhookControllerTest extends TestCase {

	private const VERIFY_TOKEN    = 'test-verify-token';
	private const APP_SECRET      = 'deadbeef1234567890abcdef12345678';
	private const SALT_AUTH       = 'auth-salt-abcdef';
	private const SALT_SECURE_AUTH = 'secure-auth-salt-ghijkl';

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
			->andReturn( self::SALT_SECURE_AUTH );
	}

	/**
	 * Mock get_option for the two credential keys used by makeHandler().
	 *
	 * app_secret is stored encrypted; this helper encrypts it so
	 * CredentialStore::load() can decrypt it back to the plaintext secret.
	 * Must be called after mockSalts().
	 *
	 * @param string $verify_token Plaintext verify token.
	 * @param string $app_secret   Plaintext app secret (will be encrypted).
	 */
	private function mockCredentials(
		string $verify_token = self::VERIFY_TOKEN,
		string $app_secret = self::APP_SECRET
	): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_webhook_verify_token', '' )
			->andReturn( $verify_token );

		$encrypted_secret = '' !== $app_secret ? Encryptor::encrypt( $app_secret ) : '';

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_app_secret', '' )
			->andReturn( $encrypted_secret );
	}

	/**
	 * Build a signed X-Hub-Signature-256 header value.
	 */
	private function sign( string $body, string $secret = self::APP_SECRET ): string {
		return 'sha256=' . hash_hmac( 'sha256', $body, $secret );
	}

	// -------------------------------------------------------------------------
	// handleVerification()
	// -------------------------------------------------------------------------

	public function test_verification_returns_challenge_on_valid_token(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$request = new \WP_REST_Request();
		$request->set_param( 'hub.mode', 'subscribe' );
		$request->set_param( 'hub.verify_token', self::VERIFY_TOKEN );
		$request->set_param( 'hub.challenge', 'abc123' );

		$response = WebhookController::handleVerification( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'abc123', $response->get_data() );
	}

	public function test_verification_returns_403_for_wrong_token(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$request = new \WP_REST_Request();
		$request->set_param( 'hub.mode', 'subscribe' );
		$request->set_param( 'hub.verify_token', 'wrong-token' );
		$request->set_param( 'hub.challenge', 'abc123' );

		$response = WebhookController::handleVerification( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_verification_returns_403_for_wrong_mode(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$request = new \WP_REST_Request();
		$request->set_param( 'hub.mode', 'unsubscribe' );
		$request->set_param( 'hub.verify_token', self::VERIFY_TOKEN );
		$request->set_param( 'hub.challenge', 'abc123' );

		$response = WebhookController::handleVerification( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// handleEvent()
	// -------------------------------------------------------------------------

	public function test_event_always_returns_200(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$body    = '{"object":"whatsapp_business_account","entry":[]}';
		$request = new \WP_REST_Request();
		$request->set_body( $body );
		$request->set_header( 'x-hub-signature-256', $this->sign( $body ) );

		$response = WebhookController::handleEvent( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_event_returns_200_even_for_invalid_signature(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$body    = '{"object":"whatsapp_business_account","entry":[]}';
		$request = new \WP_REST_Request();
		$request->set_body( $body );
		$request->set_header( 'x-hub-signature-256', 'sha256=badhash' );

		$response = WebhookController::handleEvent( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_event_dispatches_action_for_valid_payload(): void {
		$this->mockSalts();
		$this->mockCredentials();

		$entry   = array( 'id' => 'phone_1', 'changes' => array() );
		$payload = array(
			'object' => 'whatsapp_business_account',
			'entry'  => array( $entry ),
		);
		$body    = (string) json_encode( $payload );

		$request = new \WP_REST_Request();
		$request->set_body( $body );
		$request->set_header( 'x-hub-signature-256', $this->sign( $body ) );

		\WP_Mock::expectAction( 'cartpinger_webhook_entry', $entry );

		$response = WebhookController::handleEvent( $request );

		$this->assertSame( 200, $response->get_status() );
	}
}
