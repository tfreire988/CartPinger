<?php
/**
 * Logging helper with severity levels.
 *
 * Routes entries to WooCommerce logs (if available) or WP_DEBUG_LOG.
 * Sensitive context keys are always masked before writing.
 *
 * @package WhatsCom\Support
 */

declare(strict_types=1);

namespace WhatsCom\Support;

/**
 * Class Logger
 */
final class Logger {

	public const DEBUG   = 'debug';
	public const INFO    = 'info';
	public const WARNING = 'warning';
	public const ERROR   = 'error';

	private const SOURCE = 'whatscom';

	/** Maps our level strings to WooCommerce log level constants. */
	private const WC_LEVELS = array(
		self::DEBUG   => 'debug',
		self::INFO    => 'info',
		self::WARNING => 'warning',
		self::ERROR   => 'error',
	);

	/**
	 * Log at DEBUG level.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Log at INFO level.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log at WARNING level.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log at ERROR level.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Write a log entry, masking sensitive context keys first.
	 *
	 * @param string               $level   Severity level constant.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		$safe_context = Sanitizer::maskSensitive( $context );

		if ( self::writeToWc( $level, $message, $safe_context ) ) {
			return;
		}

		self::writeToErrorLog( $level, $message, $safe_context );
	}

	/**
	 * Attempt to write via WooCommerce logger. Returns true on success.
	 *
	 * @param string               $level   Level string.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Masked context.
	 */
	private static function writeToWc( string $level, string $message, array $context ): bool {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return false;
		}

		$logger   = wc_get_logger();
		$wc_level = self::WC_LEVELS[ $level ] ?? self::INFO;

		$logger->log(
			$wc_level,
			$message,
			array_merge( $context, array( 'source' => self::SOURCE ) )
		);

		return true;
	}

	/**
	 * Write to PHP error_log when WP_DEBUG_LOG is active.
	 *
	 * @param string               $level   Level string.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Masked context.
	 */
	private static function writeToErrorLog( string $level, string $message, array $context ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		$entry = sprintf(
			'[WhatsCom][%s] %s%s',
			strtoupper( $level ),
			$message,
			empty( $context ) ? '' : ' ' . wp_json_encode( $context )
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $entry );
	}
}
