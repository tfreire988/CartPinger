<?php
/**
 * Contract for versioned database migrations.
 *
 * @package WhatsCom\Database
 */

declare(strict_types=1);

namespace WhatsCom\Database;

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
