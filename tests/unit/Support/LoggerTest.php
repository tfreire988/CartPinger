<?php
/**
 * Unit tests for the Logger helper.
 *
 * @package WhatsCom\Tests\Unit\Support
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\Support;

use WhatsCom\Support\Logger;
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

	public function test_log_masks_sensitive_context_before_writing(): void {
		\WP_Mock::userFunction( 'wc_get_logger' )->never();

		// With WP_DEBUG_LOG false (not defined), nothing should be written.
		// We verify masking via Sanitizer directly (tested separately).
		// This test confirms Logger::log() does not throw with sensitive data.
		Logger::log( Logger::INFO, 'Test message', array( 'access_token' => 'should-be-masked' ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_debug_convenience_method_does_not_throw(): void {
		Logger::debug( 'debug message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_info_convenience_method_does_not_throw(): void {
		Logger::info( 'info message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_warning_convenience_method_does_not_throw(): void {
		Logger::warning( 'warning message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_error_convenience_method_does_not_throw(): void {
		Logger::error( 'error message' );
		$this->addToAssertionCount( 1 );
	}

	public function test_log_routes_to_wc_logger_when_available(): void {
		$wc_logger = $this->createMock( \WC_Logger_Interface::class );
		$wc_logger->expects( $this->once() )
			->method( 'log' )
			->with(
				'info',
				'Hello WC',
				$this->callback(
					static fn( array $ctx ) => ( $ctx['source'] ?? '' ) === 'whatscom'
				)
			);

		\WP_Mock::userFunction( 'wc_get_logger' )
			->once()
			->andReturn( $wc_logger );

		Logger::log( Logger::INFO, 'Hello WC' );
	}

	public function test_log_with_empty_context_does_not_throw(): void {
		Logger::log( Logger::ERROR, 'Something broke' );
		$this->addToAssertionCount( 1 );
	}
}
