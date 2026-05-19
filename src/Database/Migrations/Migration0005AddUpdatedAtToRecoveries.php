<?php
/**
 * Migration 0005 — add updated_at column to cartpinger_recoveries.
 *
 * Required by the Pro follow-up sequence which looks for rows whose
 * sequence_step was changed more than 24h / 48h ago. The original
 * schema only had created_at, causing "Unknown column 'updated_at'"
 * errors in the wp_cartpinger_recoveries queries.
 *
 * @package CartPinger\Database\Migrations
 */

declare(strict_types=1);

namespace CartPinger\Database\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Database\MigrationInterface;
use CartPinger\Database\Schema;

/**
 * Class Migration0005AddUpdatedAtToRecoveries
 */
final class Migration0005AddUpdatedAtToRecoveries implements MigrationInterface {

	public function getVersion(): int {
		return 5;
	}

	/**
	 * Add updated_at column when missing, then re-run dbDelta so the
	 * status_step_updated index gets created.
	 */
	public function up(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
				DB_NAME,
				$table,
				'updated_at'
			)
		);

		if ( (int) $exists === 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				"ALTER TABLE `{$table}` ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
			);
		}

		// dbDelta will reconcile the new status_step_updated index.
		Schema::create();
	}
}
