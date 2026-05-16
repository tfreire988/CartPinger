<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package WhatsCom\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define plugin constants for the test environment.
define( 'WHATSCOM_VERSION', '0.1.0' );
define( 'WHATSCOM_PLUGIN_FILE', dirname( __DIR__ ) . '/whatscom.php' );
define( 'WHATSCOM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WHATSCOM_PLUGIN_URL', 'http://localhost/wp-content/plugins/whatscom/' );
define( 'WHATSCOM_PLUGIN_BASENAME', 'whatscom/whatscom.php' );

// Bootstrap WP_Mock for unit tests.
\WP_Mock::bootstrap();

// WP_Mock 1.x does not ship a WP_Error stub — define a minimal one so
// tests can create WP_Error instances without loading all of WordPress.
if ( ! class_exists( 'WP_Error' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	class WP_Error {
		/** @var string */
		private string $code;
		/** @var string */
		private string $message;

		/** @param mixed $data Unused in stub. */
		public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}

		/** @return string */
		public function get_error_message( string $code = '' ): string {
			return $this->message;
		}

		/** @return string */
		public function get_error_code(): string {
			return $this->code;
		}
	}
}
