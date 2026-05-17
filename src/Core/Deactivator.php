<?php
/**
 * Plugin deactivation handler.
 *
 * Clears all scheduled plugin events and cached data.
 * Does NOT delete user data — that only happens on uninstall when the
 * user has explicitly opted in via the cartpinger_delete_data_on_uninstall option.
 *
 * @package CartPinger\Core
 */

declare(strict_types=1);

namespace CartPinger\Core;

use CartPinger\WhatsApp\MessageQueue;
use CartPinger\WooCommerce\AbandonedCartTracker;

/**
 * Class Deactivator
 */
final class Deactivator {

	/**
	 * Runs on plugin deactivation.
	 *
	 * Clears cron events and the templates transient; does NOT delete
	 * credentials or custom DB tables.
	 */
	public static function deactivate(): void {
		// Clear the message-queue cron event.
		$timestamp = wp_next_scheduled( MessageQueue::CRON_HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, MessageQueue::CRON_HOOK );
		}
		wp_clear_scheduled_hook( MessageQueue::CRON_HOOK );

		// Clear the recovery cron event.
		$recovery_ts = wp_next_scheduled( AbandonedCartTracker::CRON_HOOK );
		if ( false !== $recovery_ts ) {
			wp_unschedule_event( $recovery_ts, AbandonedCartTracker::CRON_HOOK );
		}
		wp_clear_scheduled_hook( AbandonedCartTracker::CRON_HOOK );

		// Clear the templates transient so stale data is not served on re-activation.
		delete_transient( 'cartpinger_templates_cache' );

		flush_rewrite_rules();
	}
}
