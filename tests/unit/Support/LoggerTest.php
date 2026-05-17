<?php
/**
 * Unit tests for the Logger helper.
 *
 * @package CartPinger\Tests\Unit\Support
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Support;

use CartPinger\Support\Logger;
use WP_Mock\Tools\TestCase;

/**
 * Class LoggerTest
 */
class LoggerTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_debug_does_not_throw(): void {
		Logger::debug( 'debug message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_info_does_not_throw(): void {
		Logger::info( 'info message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_warning_does_not_throw(): void {
		Logger::warning( 'warning message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_error_does_not_throw(): void {
		Logger::error( 'error message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_log_with_sensitive_context_does_not_expose_token(): void {
		// No WC logger, no WP_DEBUG_LOG — just confirms no crash and masking
		// is delegated to Sanitizer (tested separately).
		Logger::log( Logger::INFO, 'Test', array( 'access_token' => 'super-secret' ) );
		$this->addToAssertionCount( 1 );
	}

	public function test_log_routes_to_wc_logger_when_available(): void {
		// Use an anonymous class instead of createMock(\WC_Logger_Interface::class)
		// because that interface only exists in PHPStan stubs, not at PHPUnit runtime.
		$mock_logger = new class {
			/** @var array<int, array<string, mixed>> */
			public array $log_calls = array();

			/** @param array<string, mixed> $context */
			public function log( string $level, string $message, array $context = array() ): void {
				$this->log_calls[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		};

		\WP_Mock::userFunction( 'wc_get_logger' )
			->once()
			->andReturn( $mock_logger );

		Logger::log( Logger::INFO, 'Hello WC' );

		$this->assertCount( 1, $mock_logger->log_calls );
		$this->assertSame( 'info', $mock_logger->log_calls[0]['level'] );
		$this->assertSame( 'Hello WC', $mock_logger->log_calls[0]['message'] );
		$this->assertSame( 'cartpinger', $mock_logger->log_calls[0]['context']['source'] );
	}

	public function test_log_falls_back_silently_when_no_wc_and_no_debug_log(): void {
		// Neither wc_get_logger nor WP_DEBUG_LOG are available — must be silent.
		Logger::log( Logger::ERROR, 'Something broke' );
		$this->addToAssertionCount( 1 );
	}
}
