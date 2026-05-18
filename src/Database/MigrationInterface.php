<?php
/**
 * Contract for versioned database migrations.
 *
 * @package CartPinger\Database
 */

declare(strict_types=1);

namespace CartPinger\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface MigrationInterface
 */
interface MigrationInterface {

	/**
	 * Monotonically increasing integer version this migration brings the DB to.
	 */
	public function getVersion(): int;

	/**
	 * Apply the migration. Must be idempotent (safe to call multiple times).
	 */
	public function up(): void;
}
