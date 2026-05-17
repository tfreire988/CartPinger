<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package CartPinger\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define plugin constants for the test environment.
define( 'CARTPINGER_VERSION', '0.1.0' );
define( 'CARTPINGER_PLUGIN_FILE', dirname( __DIR__ ) . '/cartpinger.php' );
define( 'CARTPINGER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'CARTPINGER_PLUGIN_URL', 'http://localhost/wp-content/plugins/cartpinger/' );
define( 'CARTPINGER_PLUGIN_BASENAME', 'cartpinger/cartpinger.php' );

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

if ( ! class_exists( 'WC_Order' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	class WC_Order {
		/** @var string */
		private string $billing_phone = '';
		/** @var string */
		private string $billing_first_name = 'Test';
		/** @var int */
		private int $order_number = 1001;
		/** @var string */
		private string $total = '99.00';
		/** @var string */
		private string $currency = 'EUR';

		/**
		 * Set the billing phone (test helper).
		 *
		 * @param string $phone Phone number.
		 */
		public function set_billing_phone( string $phone ): void {
			$this->billing_phone = $phone;
		}

		/**
		 * Get the billing phone number.
		 *
		 * @return string
		 */
		public function get_billing_phone(): string {
			return $this->billing_phone;
		}

		/**
		 * Set the billing first name (test helper).
		 *
		 * @param string $name First name.
		 */
		public function set_billing_first_name( string $name ): void {
			$this->billing_first_name = $name;
		}

		/**
		 * Get the customer billing first name.
		 *
		 * @return string
		 */
		public function get_billing_first_name(): string {
			return $this->billing_first_name;
		}

		/**
		 * Get the order number (same as ID in stub).
		 *
		 * @return int
		 */
		public function get_order_number(): int {
			return $this->order_number;
		}

		/**
		 * Get the order total as a numeric string.
		 *
		 * @return string
		 */
		public function get_total(): string {
			return $this->total;
		}

		/**
		 * Get the order currency code.
		 *
		 * @return string
		 */
		public function get_currency(): string {
			return $this->currency;
		}

		/**
		 * Get the order ID.
		 *
		 * @return int
		 */
		public function get_id(): int {
			return 1;
		}
	}
}
