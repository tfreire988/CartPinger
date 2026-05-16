<?php
/**
 * Unit tests for the Encryptor helper.
 *
 * @package WhatsCom\Tests\Unit\Support
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\Support;

use WhatsCom\Support\Encryptor;
use WP_Mock\Tools\TestCase;

/**
 * Class EncryptorTest
 */
class EncryptorTest extends TestCase {

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
	 * Mock wp_salt() for both salt types used by Encryptor::deriveKey().
	 * Call this once per test; WP_Mock allows multiple calls per mock by default.
	 */
	private function mockWpSalt(): void {
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( str_repeat( 'a', 64 ) );

		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( str_repeat( 'b', 64 ) );
	}

	// -------------------------------------------------------------------------
	// encrypt()
	// -------------------------------------------------------------------------

	public function test_encrypt_returns_non_empty_base64_string(): void {
		$this->mockWpSalt();

		$encoded = Encryptor::encrypt( 'hello' );

		$this->assertNotEmpty( $encoded );
		$this->assertNotFalse( base64_decode( $encoded, true ) );
	}

	public function test_encrypt_produces_different_output_each_call(): void {
		$this->mockWpSalt();

		$first  = Encryptor::encrypt( 'same plaintext' );
		$second = Encryptor::encrypt( 'same plaintext' );

		$this->assertNotSame( $first, $second, 'Each encryption must use a fresh random nonce.' );
	}

	public function test_encrypt_blob_is_long_enough_for_nonce_and_tag(): void {
		$this->mockWpSalt();

		$encoded = Encryptor::encrypt( 'x' );
		$raw     = base64_decode( $encoded, true );

		// nonce(12) + at_least_1_byte_ciphertext + tag(16) = at least 29 bytes.
		$this->assertNotFalse( $raw );
		$this->assertGreaterThanOrEqual( 29, strlen( (string) $raw ) );
	}

	// -------------------------------------------------------------------------
	// decrypt()
	// -------------------------------------------------------------------------

	public function test_round_trip_restores_original_plaintext(): void {
		$this->mockWpSalt();

		$plaintext = 'EAABsbCS4iJsBAK7wtoken';
		$encoded   = Encryptor::encrypt( $plaintext );
		$decoded   = Encryptor::decrypt( $encoded );

		$this->assertSame( $plaintext, $decoded );
	}

	public function test_round_trip_with_empty_string(): void {
		$this->mockWpSalt();

		$encoded = Encryptor::encrypt( '' );
		$decoded = Encryptor::decrypt( $encoded );

		$this->assertSame( '', $decoded );
	}

	public function test_round_trip_with_unicode_plaintext(): void {
		$this->mockWpSalt();

		$plaintext = 'Ação de configuração — café ☕';
		$decoded   = Encryptor::decrypt( Encryptor::encrypt( $plaintext ) );

		$this->assertSame( $plaintext, $decoded );
	}

	// -------------------------------------------------------------------------
	// decrypt() — error paths
	// -------------------------------------------------------------------------

	public function test_decrypt_throws_on_invalid_base64(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/invalid base64/' );

		// WP_Mock not needed — exception thrown before deriveKey() is called.
		Encryptor::decrypt( '!!!not-base64!!!' );
	}

	public function test_decrypt_throws_on_too_short_blob(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/too short/' );

		// 27 bytes — less than nonce(12) + tag(16) = 28.
		Encryptor::decrypt( base64_encode( str_repeat( "\x00", 27 ) ) );
	}

	public function test_decrypt_throws_on_tampered_ciphertext(): void {
		$this->mockWpSalt();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/authentication tag mismatch/' );

		$encoded = Encryptor::encrypt( 'secret value' );
		$raw     = base64_decode( $encoded, true );

		// Flip a byte in the ciphertext region (byte 13, after the 12-byte nonce).
		$tampered      = (string) $raw;
		$tampered[13]  = chr( ord( $tampered[13] ) ^ 0xFF );
		$tampered_blob = base64_encode( $tampered );

		Encryptor::decrypt( $tampered_blob );
	}

	public function test_decrypt_throws_on_tampered_tag(): void {
		$this->mockWpSalt();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/authentication tag mismatch/' );

		$encoded = Encryptor::encrypt( 'another secret' );
		$raw     = (string) base64_decode( $encoded, true );

		// Flip the last byte (part of the 16-byte GCM tag).
		$raw[ strlen( $raw ) - 1 ] = chr( ord( $raw[ strlen( $raw ) - 1 ] ) ^ 0xFF );

		Encryptor::decrypt( base64_encode( $raw ) );
	}

	public function test_decrypt_throws_on_tampered_nonce(): void {
		$this->mockWpSalt();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessageMatches( '/authentication tag mismatch/' );

		$encoded = Encryptor::encrypt( 'yet another secret' );
		$raw     = (string) base64_decode( $encoded, true );

		// Flip a byte inside the 12-byte nonce region.
		$raw[5] = chr( ord( $raw[5] ) ^ 0xFF );

		Encryptor::decrypt( base64_encode( $raw ) );
	}

	// -------------------------------------------------------------------------
	// Key isolation
	// -------------------------------------------------------------------------

	public function test_different_salts_produce_different_ciphertext(): void {
		// First key.
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( str_repeat( 'a', 64 ) );
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( str_repeat( 'b', 64 ) );

		$blob_a = Encryptor::encrypt( 'data' );

		\WP_Mock::tearDown();
		\WP_Mock::setUp();

		// Second key (different salts).
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( str_repeat( 'x', 64 ) );
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( str_repeat( 'y', 64 ) );

		$blob_b = Encryptor::encrypt( 'data' );

		$this->assertNotSame( $blob_a, $blob_b );
	}
}
