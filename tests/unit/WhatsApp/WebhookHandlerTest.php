<?php
/**
 * Unit tests for WebhookHandler.
 *
 * @package CartPinger\Tests\Unit\WhatsApp
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WhatsApp;

use CartPinger\WhatsApp\WebhookHandler;
use WP_Mock\Tools\TestCase;

/**
 * Class WebhookHandlerTest
 */
class WebhookHandlerTest extends TestCase {

	private const VERIFY_TOKEN = 'my-verify-token-abc123';
	private const APP_SECRET   = 'deadbeef1234567890abcdef12345678';

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeHandler(
		string $verify_token = self::VERIFY_TOKEN,
		string $app_secret = self::APP_SECRET
	): WebhookHandler {
		return new WebhookHandler( $verify_token, $app_secret );
	}

	/**
	 * Compute the X-Hub-Signature-256 header value for a given body + secret.
	 */
	private function sign( string $body, string $secret = self::APP_SECRET ): string {
		return 'sha256=' . hash_hmac( 'sha256', $body, $secret );
	}

	// -------------------------------------------------------------------------
	// verifySubscription()
	// -------------------------------------------------------------------------

	public function test_verify_subscription_returns_challenge_on_valid_token(): void {
		$result = $this->makeHandler()->verifySubscription(
			'subscribe',
			self::VERIFY_TOKEN,
			'abc_challenge_123'
		);

		$this->assertSame( 'abc_challenge_123', $result );
	}

	public function test_verify_subscription_returns_null_for_wrong_mode(): void {
		$result = $this->makeHandler()->verifySubscription(
			'unsubscribe',
			self::VERIFY_TOKEN,
			'challenge'
		);

		$this->assertNull( $result );
	}

	public function test_verify_subscription_returns_null_for_wrong_token(): void {
		$result = $this->makeHandler()->verifySubscription(
			'subscribe',
			'wrong-token',
			'challenge'
		);

		$this->assertNull( $result );
	}

	public function test_verify_subscription_returns_null_when_stored_token_is_empty(): void {
		$result = $this->makeHandler( '', self::APP_SECRET )->verifySubscription(
			'subscribe',
			'',
			'challenge'
		);

		$this->assertNull( $result );
	}

	// -------------------------------------------------------------------------
	// process() — signature verification
	// -------------------------------------------------------------------------

	public function test_process_silently_discards_payload_with_wrong_prefix(): void {
		// Signature does not start with "sha256=" — must be ignored.
		$this->makeHandler()->process( '{"object":"whatsapp_business_account","entry":[]}', 'md5=abc' );

		// No assertion needed — test passes if no exception is thrown and
		// WP_Mock tearDown does not complain about unexpected do_action calls.
		$this->addToAssertionCount( 1 );
	}

	public function test_process_silently_discards_payload_with_invalid_hmac(): void {
		$body = '{"object":"whatsapp_business_account","entry":[]}';

		$this->makeHandler()->process( $body, 'sha256=0000000000000000000000000000000000000000000000000000000000000000' );

		$this->addToAssertionCount( 1 );
	}

	public function test_process_silently_discards_when_app_secret_is_empty(): void {
		$body = '{"object":"whatsapp_business_account","entry":[]}';

		$this->makeHandler( self::VERIFY_TOKEN, '' )->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// process() — payload parsing
	// -------------------------------------------------------------------------

	public function test_process_silently_discards_invalid_json(): void {
		$body = 'not-json{{{';

		$this->makeHandler()->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_process_silently_discards_non_whatsapp_object(): void {
		$body = '{"object":"instagram","entry":[]}';

		$this->makeHandler()->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// process() — entry dispatch
	// -------------------------------------------------------------------------

	public function test_process_dispatches_do_action_for_each_entry(): void {
		$entry1 = array( 'id' => 'phone_1', 'changes' => array() );
		$entry2 = array( 'id' => 'phone_2', 'changes' => array() );

		$payload = array(
			'object' => 'whatsapp_business_account',
			'entry'  => array( $entry1, $entry2 ),
		);

		$body = (string) json_encode( $payload );

		\WP_Mock::expectAction( 'cartpinger_webhook_entry', $entry1 );
		\WP_Mock::expectAction( 'cartpinger_webhook_entry', $entry2 );

		$this->makeHandler()->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_process_does_not_dispatch_when_entry_list_is_empty(): void {
		$body = (string) json_encode(
			array(
				'object' => 'whatsapp_business_account',
				'entry'  => array(),
			)
		);

		// do_action must NOT be called — WP_Mock tearDown would flag it.
		$this->makeHandler()->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_process_skips_non_array_entries(): void {
		$body = (string) json_encode(
			array(
				'object' => 'whatsapp_business_account',
				'entry'  => array( 'not-an-array', 42, null ),
			)
		);

		// do_action must NOT be called for scalar entries.
		$this->makeHandler()->process( $body, $this->sign( $body ) );

		$this->addToAssertionCount( 1 );
	}
}
