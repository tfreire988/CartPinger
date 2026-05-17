<?php
/**
 * Transparent encrypt/decrypt wrapper for sensitive plugin credentials.
 *
 * All values stored via this class are AES-256-GCM encrypted before being
 * written to the WordPress options table.  Callers always work with plaintext;
 * this class handles the cipher boundary.
 *
 * @package CartPinger\Support
 */

declare(strict_types=1);

namespace CartPinger\Support;

/**
 * Class CredentialStore
 */
final class CredentialStore {

	/**
	 * Encrypt $plaintext and persist it under $option_key.
	 *
	 * Returns false when encryption fails (e.g. OpenSSL not available) or
	 * when the value is empty (empty credentials must not be stored).
	 *
	 * @param string $option_key WP option name.
	 * @param string $plaintext  Credential value in plaintext.
	 * @return bool True on success.
	 */
	public static function save( string $option_key, string $plaintext ): bool {
		if ( '' === $plaintext ) {
			return false;
		}

		try {
			$encrypted = Encryptor::encrypt( $plaintext );
		} catch ( \RuntimeException $e ) {
			return false;
		}

		return (bool) update_option( $option_key, $encrypted, false );
	}

	/**
	 * Load and decrypt the credential stored under $option_key.
	 *
	 * Returns an empty string when the option does not exist, is empty,
	 * or cannot be decrypted (e.g. key material changed after re-salting).
	 *
	 * @param string $option_key WP option name.
	 * @return string Plaintext credential, or empty string on any failure.
	 */
	public static function load( string $option_key ): string {
		$encrypted = (string) get_option( $option_key, '' );

		if ( '' === $encrypted ) {
			return '';
		}

		try {
			return Encryptor::decrypt( $encrypted );
		} catch ( \RuntimeException $e ) {
			return '';
		}
	}
}
