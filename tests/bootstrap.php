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

// WP_Mock 1.x does not ship WP_Error / WP_REST_* stubs — define minimal ones.
if ( ! class_exists( 'WP_REST_Request' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	class WP_REST_Request {
		/** @var array<string,mixed> */
		private array $params = array();
		/** @var array<string,string> */
		private array $headers = array();
		/** @var string */
		private string $body = '';

		/**
		 * Set a request parameter (test helper).
		 *
		 * @param string $key   Parameter name.
		 * @param mixed  $value Parameter value.
		 */
		public function set_param( string $key, mixed $value ): void {
			$this->params[ $key ] = $value;
		}

		/**
		 * Get a request parameter.
		 *
		 * @param string $key Parameter name.
		 * @return mixed
		 */
		public function get_param( string $key ): mixed {
			return $this->params[ $key ] ?? null;
		}

		/**
		 * Set the raw request body (test helper).
		 *
		 * @param string $body Raw body string.
		 */
		public function set_body( string $body ): void {
			$this->body = $body;
		}

		/**
		 * Get the raw request body.
		 *
		 * @return string
		 */
		public function get_body(): string {
			return $this->body;
		}

		/**
		 * Set a request header (test helper).
		 *
		 * @param string $key   Header name (lowercase).
		 * @param string $value Header value.
		 */
		public function set_header( string $key, string $value ): void {
			$this->headers[ strtolower( $key ) ] = $value;
		}

		/**
		 * Get a request header value.
		 *
		 * @param string $key Header name (lowercase).
		 * @return string|null
		 */
		public function get_header( string $key ): ?string {
			return $this->headers[ strtolower( $key ) ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	class WP_REST_Response {
		/** @var mixed */
		private mixed $data;
		/** @var int */
		private int $status;

		/**
		 * Create a REST response.
		 *
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status code.
		 */
		public function __construct( mixed $data, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get response data.
		 *
		 * @return mixed
		 */
		public function get_data(): mixed {
			return $this->data;
		}

		/**
		 * Get HTTP status code.
		 *
		 * @return int
		 */
		public function get_status(): int {
			return $this->status;
		}
	}
}

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
