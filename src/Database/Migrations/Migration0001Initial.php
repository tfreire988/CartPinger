<?php
/**
 * Migration 0001 — initial schema.
 *
 * @package CartPinger\Database\Migrations
 */

declare(strict_types=1);

namespace CartPinger\Database\Migrations;

use CartPinger\Database\MigrationInterface;
use CartPinger\Database\Schema;

/**
 * Class Migration0001Initial
 */
final class Migration0001Initial implements MigrationInterface {

	public function getVersion(): int {
		return 1;
	}

	/**
	 * Create all plugin tables via dbDelta (idempotent).
	 */
	public function up(): void {
		Schema::create();
	}
}
