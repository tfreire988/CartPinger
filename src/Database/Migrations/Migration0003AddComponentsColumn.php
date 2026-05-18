<?php
/**
 * Migration 0003 — add language_code and components columns to messages log.
 *
 * @package CartPinger\Database\Migrations
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\Database\Migrations;

use CartPinger\Database\MigrationInterface;
use CartPinger\Database\Schema;

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
