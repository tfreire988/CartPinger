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

	/**
	 * Sanitize a phone number to E.164 format (digits + leading +).
	 *
	 * @param string $phone Raw phone input.
	 * @return string Sanitized phone, or empty string if invalid.
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
	 * Sanitize a WhatsApp template name (alphanumeric + underscore, max 512 chars).
	 *
	 * @param string $name Raw template name.
	 * @return string Sanitized template name.
	 */
	public static function templateName( string $name ): string {
		$clean = preg_replace( '/[^a-z0-9_]/', '', strtolower( $name ) );
		return is_string( $clean ) ? substr( $clean, 0, 512 ) : '';
	}

	/**
	 * Sanitize a Meta access token (strip whitespace only).
	 *
	 * @param string $token Raw access token.
	 * @return string Sanitized token.
	 */
	public static function accessToken( string $token ): string {
		return sanitize_text_field( trim( $token ) );
	}
}
