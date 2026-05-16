<?php
/**
 * Input sanitization helpers.
 *
 * @package WhatsCom\Support
 */

declare(strict_types=1);

namespace WhatsCom\Support;

/**
 * Class Sanitizer
 */
final class Sanitizer {

	/** Keys whose values are always masked in logs. */
	private const SENSITIVE_KEYS = array( 'token', 'secret', 'password', 'key', 'auth', 'access_token', 'app_secret' );

	/**
	 * Sanitize a phone number to E.164 format.
	 *
	 * @param string $phone Raw phone input.
	 * @return string Sanitized E.164 phone, or empty string if invalid.
	 */
	public static function phone( string $phone ): string {
		$clean = preg_replace( '/[^0-9+]/', '', $phone );

		if ( null === $clean ) {
			return '';
		}

		if ( ! preg_match( '/^\+?[1-9]\d{6,14}$/', $clean ) ) {
			return '';
		}

		if ( ! str_starts_with( $clean, '+' ) ) {
			$clean = '+' . $clean;
		}

		return $clean;
	}

	/**
	 * Sanitize a Meta Phone Number ID or WABA ID (digits only, 1–20 chars).
	 *
	 * @param string $id Raw Meta numeric ID.
	 * @return string Digits-only string, or empty string if invalid.
	 */
	public static function metaNumericId( string $id ): string {
		$clean = preg_replace( '/\D/', '', trim( $id ) );

		if ( null === $clean || '' === $clean || strlen( $clean ) > 20 ) {
			return '';
		}

		return $clean;
	}

	/**
	 * Sanitize a WhatsApp Cloud API access token.
	 *
	 * @param string $token Raw access token.
	 * @return string Sanitized token (printable ASCII, no whitespace).
	 */
	public static function accessToken( string $token ): string {
		return sanitize_text_field( trim( $token ) );
	}

	/**
	 * Sanitize a webhook verify token (alphanumeric + hyphens, max 80 chars).
	 *
	 * @param string $token Raw verify token.
	 * @return string Sanitized token.
	 */
	public static function verifyToken( string $token ): string {
		$clean = preg_replace( '/[^a-zA-Z0-9\-_]/', '', trim( $token ) );
		return is_string( $clean ) ? substr( $clean, 0, 80 ) : '';
	}

	/**
	 * Sanitize a Meta App Secret (hex string, max 64 chars).
	 *
	 * @param string $secret Raw app secret.
	 * @return string Lowercase hex string, or empty string if invalid.
	 */
	public static function appSecret( string $secret ): string {
		$clean = preg_replace( '/[^a-fA-F0-9]/', '', trim( $secret ) );

		if ( null === $clean || strlen( $clean ) > 64 ) {
			return '';
		}

		return strtolower( $clean );
	}

	/**
	 * Sanitize a WhatsApp template name (lowercase alphanumeric + underscore, max 512 chars).
	 *
	 * @param string $name Raw template name.
	 * @return string Sanitized template name.
	 */
	public static function templateName( string $name ): string {
		$clean = preg_replace( '/[^a-z0-9_]/', '', strtolower( $name ) );
		return is_string( $clean ) ? substr( $clean, 0, 512 ) : '';
	}

	/**
	 * Mask sensitive values in a context array before logging.
	 *
	 * @param array<string, mixed> $context Raw context.
	 * @return array<string, mixed> Context with sensitive values replaced by '***'.
	 */
	public static function maskSensitive( array $context ): array {
		$masked = array();

		foreach ( $context as $key => $value ) {
			$key_lower    = strtolower( (string) $key );
			$is_sensitive = false;

			foreach ( self::SENSITIVE_KEYS as $sensitive ) {
				if ( str_contains( $key_lower, $sensitive ) ) {
					$is_sensitive = true;
					break;
				}
			}

			$masked[ $key ] = $is_sensitive ? '***' : $value;
		}

		return $masked;
	}
}
