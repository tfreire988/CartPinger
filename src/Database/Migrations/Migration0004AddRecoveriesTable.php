<?php
/**
 * Migration 0004 — create cartpinger_recoveries table.
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
 * Class Migration0004AddRecoveriesTable
 */
final class Migration0004AddRecoveriesTable implements MigrationInterface {

	public function getVersion(): int {
		return 4;
	}

	/**
	 * Create cartpinger_recoveries table via dbDelta (idempotent).
	 */
	public function up(): void {
		Schema::create();
	}
}
