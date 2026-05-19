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
				$wpdb->dbname,
				$table,
				'updated_at'
			)
		);

		if ( 0 === (int) $exists ) {
			// Table name is built from $wpdb->prefix and a hard-coded string;
			// MySQL does not support binding table or column identifiers via
			// $wpdb->prepare(). The value of $table is therefore not user input
			// and is safe to interpolate directly into the ALTER statement.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedScript, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( 'ALTER TABLE `' . $table . '` ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at' );
		}

		// dbDelta will reconcile the new status_step_updated index.
		Schema::create();
	}
}
