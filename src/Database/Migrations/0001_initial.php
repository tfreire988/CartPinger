<?php
/**
 * Migration 0001 — initial schema.
 *
 * @package WhatsCom\Database\Migrations
 */

declare(strict_types=1);

namespace WhatsCom\Database\Migrations;

use WhatsCom\Database\MigrationInterface;
use WhatsCom\Database\Schema;

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
