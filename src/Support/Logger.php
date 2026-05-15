<?php
/**
 * Logging helper with severity levels.
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

	/**
	 * Log at DEBUG level.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function debug( string $message, array $context = array() ): void {
		self::log( self::DEBUG, $message, $context );
	}

	/**
	 * Log at INFO level.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log at WARNING level.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log at ERROR level.
	 *
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Write a log entry.
	 *
	 * @param string $level   Severity level.
	 * @param string $message Log message.
	 * @param array<string, mixed> $context Optional context data.
	 */
	private static function log( string $level, string $message, array $context = array() ): void {
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
