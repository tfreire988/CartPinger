<?php
/**
 * Unit tests for CredentialStore.
 *
 * @package CartPinger\Tests\Unit\Support
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Support;

use CartPinger\Support\CredentialStore;
use WP_Mock\Tools\TestCase;

/**
 * Class CredentialStoreTest
 */
class CredentialStoreTest extends TestCase {

	private const OPTION_KEY = 'cartpinger_access_token';
	private const PLAINTEXT  = 'EAAexampleAccessToken12345';

	/** Stable salts for Encryptor key derivation. */
	private const SALT_AUTH        = 'auth-salt-value-abcdef';
	private const SALT_SECURE_AUTH = 'secure-auth-salt-value-ghijkl';

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
	 * Mock wp_salt() for both auth keys used by Encryptor::deriveKey().
	 */
	private function mockSalts(): void {
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( self::SALT_AUTH );

		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( self::SALT_SECURE_AUTH );
	}

	// -------------------------------------------------------------------------
	// save()
	// -------------------------------------------------------------------------

	public function test_save_stores_encrypted_value(): void {
		$this->mockSalts();

		// Capture whatever encrypted blob is passed to update_option.
		$stored = null;

		\WP_Mock::userFunction( 'update_option' )
			->once()
			->andReturnUsing(
				function ( string $key, string $value, bool $autoload ) use ( &$stored ): bool {
					$stored = $value;
					return true;
				}
			);

		$result = CredentialStore::save( self::OPTION_KEY, self::PLAINTEXT );

		$this->assertTrue( $result );
		$this->assertNotNull( $stored );
		// Stored value must differ from plaintext (it is base64-encoded ciphertext).
		$this->assertNotSame( self::PLAINTEXT, $stored );
	}

	public function test_save_returns_false_for_empty_plaintext(): void {
		// No WP functions should be called — early return.
		CredentialStore::save( self::OPTION_KEY, '' );

		// WP_Mock tearDown would flag unexpected update_option calls.
		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// load()
	// -------------------------------------------------------------------------

	public function test_load_returns_original_plaintext_after_save(): void {
		$this->mockSalts();

		// Step 1: encrypt via save(), capture the encrypted blob.
		$encrypted = null;

		\WP_Mock::userFunction( 'update_option' )
			->once()
			->andReturnUsing(
				function ( string $key, string $value, bool $autoload ) use ( &$encrypted ): bool {
					$encrypted = $value;
					return true;
				}
			);

		CredentialStore::save( self::OPTION_KEY, self::PLAINTEXT );

		// Step 2: decrypt via load() using the captured blob.
		\WP_Mock::userFunction( 'get_option' )
			->with( self::OPTION_KEY, '' )
			->andReturn( (string) $encrypted );

		$result = CredentialStore::load( self::OPTION_KEY );

		$this->assertSame( self::PLAINTEXT, $result );
	}

	public function test_load_returns_empty_string_when_option_missing(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( self::OPTION_KEY, '' )
			->andReturn( '' );

		$result = CredentialStore::load( self::OPTION_KEY );

		$this->assertSame( '', $result );
	}

	public function test_load_returns_empty_string_for_corrupted_data(): void {
		$this->mockSalts();

		\WP_Mock::userFunction( 'get_option' )
			->with( self::OPTION_KEY, '' )
			->andReturn( 'not-valid-base64!!!' );

		$result = CredentialStore::load( self::OPTION_KEY );

		$this->assertSame( '', $result );
	}
}
