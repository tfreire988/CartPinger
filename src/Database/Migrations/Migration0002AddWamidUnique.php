<?php
/**
 * Migration 0002 — add UNIQUE index on meta_message_id for webhook deduplication.
 *
 * @package CartPinger\Database\Migrations
 */

declare(strict_types=1);

namespace CartPinger\Database\Migrations;

use CartPinger\Database\MigrationInterface;

/**
 * Class Migration0002AddWamidUnique
 */
final class Migration0002AddWamidUnique implements MigrationInterface {

	public function getVersion(): int {
		return 2;
	}

	/**
	 * Add a UNIQUE index on meta_message_id if it does not already exist.
	 * Safe to run multiple times.
	 */
	public function up(): void {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

		$existing = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = %s AND index_name = %s',
				$table,
				'meta_message_id_unique'
			)
		);

		if ( (int) $existing > 0 ) {
			return;
		}

		$wpdb->query( "ALTER TABLE `{$table}` ADD UNIQUE INDEX `meta_message_id_unique` (`meta_message_id`)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
