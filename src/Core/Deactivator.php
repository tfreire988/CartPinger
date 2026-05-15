<?php
/**
 * Plugin deactivation handler.
 *
 * @package WhatsCom\Core
 */

declare(strict_types=1);

namespace WhatsCom\Core;

/**
 * Class Deactivator
 */
final class Deactivator {

	/**
	 * Runs on plugin deactivation. Clears scheduled events — does NOT delete data.
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'whatscom_process_message_queue' );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, 'whatscom_process_message_queue' );
		}

		wp_clear_scheduled_hook( 'whatscom_process_message_queue' );

		flush_rewrite_rules();
	}
}
