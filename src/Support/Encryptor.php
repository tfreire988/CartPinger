<?php
/**
 * AES-256-GCM encryption/decryption for sensitive plugin data.
 *
 * The encryption key is derived from WordPress site salts via HKDF-SHA-256.
 * No static secrets are baked into the plugin; the key is unique per
 * WordPress installation.
 *
 * Wire format (before base64):  nonce[12] | ciphertext[n] | tag[16]
 *
 * @package CartPinger\Support
 */

declare(strict_types=1);

namespace CartPinger\Support;

/**
 * Class Encryptor
 */
final class Encryptor {

	private const CIPHER      = 'aes-256-gcm';
	private const NONCE_LEN   = 12;
	private const TAG_LEN     = 16;
	private const HKDF_INFO   = 'cartpinger-encryptor-v1';
	private const HKDF_LENGTH = 32;

	/**
	 * Encrypt a plaintext string and return a base64-encoded blob.
	 *
	 * @param string $plaintext The string to encrypt.
	 * @return string Base64-encoded ciphertext blob.
	 * @throws \RuntimeException If OpenSSL encryption fails.
	 */
	public static function encrypt( string $plaintext ): string {
		$key   = self::deriveKey();
		$nonce = random_bytes( self::NONCE_LEN );
		$tag   = '';

		$ciphertext = openssl_encrypt(
			$plaintext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag,
			'',
			self::TAG_LEN
		);

		if ( false === $ciphertext ) {
			throw new \RuntimeException( 'Encryption failed.' );
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $nonce . $ciphertext . $tag );
	}

	/**
	 * Decrypt a base64-encoded blob produced by encrypt().
	 *
	 * Fails loudly on any tampering or encoding error — callers must not
	 * silently swallow the exception.
	 *
	 * @param string $encoded Base64-encoded ciphertext blob.
	 * @return string Original plaintext.
	 * @throws \RuntimeException If decryption fails for any reason.
	 */
	public static function decrypt( string $encoded ): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( $encoded, true );

		if ( false === $raw ) {
			throw new \RuntimeException( 'Decryption failed: invalid base64 encoding.' );
		}

		$min_len = self::NONCE_LEN + self::TAG_LEN;

		if ( strlen( $raw ) < $min_len ) {
			throw new \RuntimeException( 'Decryption failed: ciphertext blob is too short.' );
		}

		$nonce      = substr( $raw, 0, self::NONCE_LEN );
		$tag        = substr( $raw, -self::TAG_LEN );
		$ciphertext = substr( $raw, self::NONCE_LEN, -self::TAG_LEN );
		$key        = self::deriveKey();

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag
		);

		if ( false === $plaintext ) {
			throw new \RuntimeException( 'Decryption failed: authentication tag mismatch.' );
		}

		return $plaintext;
	}

	/**
	 * Derive a 256-bit AES key from WordPress installation-unique salts.
	 *
	 * Uses HKDF-SHA-256 so the raw salt bytes are stretched into a
	 * cryptographically strong fixed-length key regardless of salt length.
	 *
	 * @return string 32-byte raw key.
	 */
	private static function deriveKey(): string {
		$material = wp_salt( 'auth' ) . wp_salt( 'secure_auth' );

		return hash_hkdf( 'sha256', $material, self::HKDF_LENGTH, self::HKDF_INFO );
	}
}
