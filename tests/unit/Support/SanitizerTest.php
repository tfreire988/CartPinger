<?php
/**
 * Unit tests for the Sanitizer helper.
 *
 * @package CartPinger\Tests\Unit\Support
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Support;

use CartPinger\Support\Sanitizer;
use WP_Mock\Tools\TestCase;

/**
 * Class SanitizerTest
 */
class SanitizerTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// phone()
	// -------------------------------------------------------------------------

	/**
	 * @dataProvider provideValidPhones
	 */
	public function test_phone_valid( string $input, string $expected ): void {
		$this->assertSame( $expected, Sanitizer::phone( $input ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provideValidPhones(): array {
		return array(
			'e164 with plus'  => array( '+34612345678', '+34612345678' ),
			'digits only'     => array( '34612345678', '+34612345678' ),
			'spaces stripped' => array( '+34 612 345 678', '+34612345678' ),
			'dashes stripped' => array( '+34-612-345-678', '+34612345678' ),
		);
	}

	public function test_phone_invalid_returns_empty(): void {
		$this->assertSame( '', Sanitizer::phone( 'not-a-phone' ) );
	}

	public function test_phone_too_short_returns_empty(): void {
		$this->assertSame( '', Sanitizer::phone( '+123' ) );
	}

	public function test_phone_sql_injection_returns_empty(): void {
		$this->assertSame( '', Sanitizer::phone( "'; DROP TABLE wp_users; --" ) );
	}

	// -------------------------------------------------------------------------
	// metaNumericId()
	// -------------------------------------------------------------------------

	public function test_meta_numeric_id_strips_non_digits(): void {
		$this->assertSame( '123456789012345', Sanitizer::metaNumericId( '123456789012345' ) );
	}

	public function test_meta_numeric_id_with_spaces(): void {
		$this->assertSame( '123', Sanitizer::metaNumericId( ' 123 ' ) );
	}

	public function test_meta_numeric_id_too_long_returns_empty(): void {
		$this->assertSame( '', Sanitizer::metaNumericId( str_repeat( '1', 21 ) ) );
	}

	public function test_meta_numeric_id_empty_returns_empty(): void {
		$this->assertSame( '', Sanitizer::metaNumericId( '' ) );
	}

	public function test_meta_numeric_id_letters_only_returns_empty(): void {
		$this->assertSame( '', Sanitizer::metaNumericId( 'abcdef' ) );
	}

	// -------------------------------------------------------------------------
	// verifyToken()
	// -------------------------------------------------------------------------

	public function test_verify_token_allows_alphanumeric_and_hyphens(): void {
		$this->assertSame( 'my-token_123', Sanitizer::verifyToken( 'my-token_123' ) );
	}

	public function test_verify_token_strips_special_chars(): void {
		$this->assertSame( 'token', Sanitizer::verifyToken( 'token!@#$%' ) );
	}

	public function test_verify_token_truncates_at_80(): void {
		$long = str_repeat( 'a', 100 );
		$this->assertSame( 80, strlen( Sanitizer::verifyToken( $long ) ) );
	}

	// -------------------------------------------------------------------------
	// appSecret()
	// -------------------------------------------------------------------------

	public function test_app_secret_valid_hex(): void {
		$hex = str_repeat( 'a1', 16 );
		$this->assertSame( $hex, Sanitizer::appSecret( $hex ) );
	}

	public function test_app_secret_uppercased_normalized(): void {
		$this->assertSame( 'abcdef', Sanitizer::appSecret( 'ABCDEF' ) );
	}

	public function test_app_secret_non_hex_stripped(): void {
		$this->assertSame( 'abc123', Sanitizer::appSecret( 'abc123!@#xyz' ) );
	}

	// -------------------------------------------------------------------------
	// templateName()
	// -------------------------------------------------------------------------

	public function test_template_name_lowercases_and_strips(): void {
		$this->assertSame( 'order_confirmed', Sanitizer::templateName( 'Order_Confirmed!' ) );
	}

	public function test_template_name_sql_injection_stripped(): void {
		// Sanitizer strips dangerous chars (' ; - space) but keeps all a-z chars.
		// "order'; DROP TABLE--" → "orderdroptable" after removing non-[a-z0-9_].
		$this->assertSame( 'orderdroptable', Sanitizer::templateName( "order'; DROP TABLE--" ) );
	}

	// -------------------------------------------------------------------------
	// maskSensitive()
	// -------------------------------------------------------------------------

	public function test_mask_sensitive_replaces_token_key(): void {
		$result = Sanitizer::maskSensitive( array( 'access_token' => 'real-secret' ) );
		$this->assertSame( '***', $result['access_token'] );
	}

	public function test_mask_sensitive_replaces_password_key(): void {
		$result = Sanitizer::maskSensitive( array( 'password' => 'hunter2' ) );
		$this->assertSame( '***', $result['password'] );
	}

	public function test_mask_sensitive_preserves_safe_keys(): void {
		$result = Sanitizer::maskSensitive( array( 'order_id' => 42, 'status' => 'sent' ) );
		$this->assertSame( 42, $result['order_id'] );
		$this->assertSame( 'sent', $result['status'] );
	}

	public function test_mask_sensitive_detects_partial_key_match(): void {
		$result = Sanitizer::maskSensitive( array( 'app_secret_key' => 'hidden' ) );
		$this->assertSame( '***', $result['app_secret_key'] );
	}

	public function test_mask_sensitive_empty_context_returns_empty(): void {
		$this->assertSame( array(), Sanitizer::maskSensitive( array() ) );
	}
}
