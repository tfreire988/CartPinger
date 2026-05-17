<?php
/**
 * Migration 0003 — add language_code and components columns to messages log.
 *
 * @package WhatsCom\Database\Migrations
 */

declare(strict_types=1);

namespace WhatsCom\Database\Migrations;

use WhatsCom\Database\MigrationInterface;
use WhatsCom\Database\Schema;

/**
 * Class Migration0003AddComponentsColumn
 */
final class Migration0003AddComponentsColumn implements MigrationInterface {

	public function getVersion(): int {
		return 3;
	}

	/**
	 * Add language_code and components columns via dbDelta (idempotent).
	 */
	public function up(): void {
		Schema::create();
	}
}
