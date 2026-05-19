<?php
/**
 * Runs versioned database migrations in order.
 *
 * @package CartPinger\Database
 */

declare(strict_types=1);

namespace CartPinger\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Database\Migrations\Migration0001Initial;
use CartPinger\Database\Migrations\Migration0002AddWamidUnique;
use CartPinger\Database\Migrations\Migration0003AddComponentsColumn;
use CartPinger\Database\Migrations\Migration0004AddRecoveriesTable;
use CartPinger\Database\Migrations\Migration0005AddUpdatedAtToRecoveries;

/**
 * Class MigrationRunner
 */
final class MigrationRunner {

	/** Highest migration version number shipped with this release. */
	public const CURRENT_VERSION = 5;

	private const OPTION_KEY = 'cartpinger_db_version';

	/**
	 * True when the database is behind the current version.
	 */
	public static function needsUpdate(): bool {
		return self::getCurrentVersion() < self::CURRENT_VERSION;
	}

	/**
	 * Run all pending migrations in ascending version order.
	 * Safe to call on every boot — already-applied migrations are skipped.
	 *
	 * @param MigrationInterface[]|null $migrations Injectable list for testing; uses production set when null.
	 */
	public static function run( ?array $migrations = null ): void {
		$current    = self::getCurrentVersion();
		$migrations = $migrations ?? self::getProductionMigrations();

		usort( $migrations, static fn( MigrationInterface $a, MigrationInterface $b ) => $a->getVersion() <=> $b->getVersion() );

		foreach ( $migrations as $migration ) {
			if ( $migration->getVersion() <= $current ) {
				continue;
			}

			$migration->up();
			update_option( self::OPTION_KEY, $migration->getVersion(), true );
			$current = $migration->getVersion();
		}
	}

	/**
	 * Return the current installed DB version as an integer.
	 *
	 * Handles the legacy semver string ('0.1.0') written by the pre-runner
	 * Schema::create() call — that equals migration version 1.
	 */
	public static function getCurrentVersion(): int {
		$stored = get_option( self::OPTION_KEY, 0 );

		if ( is_string( $stored ) && str_contains( $stored, '.' ) ) {
			return 1;
		}

		return (int) $stored;
	}

	/**
	 * Return the ordered list of production migrations.
	 *
	 * @return MigrationInterface[]
	 */
	private static function getProductionMigrations(): array {
		return array(
			new Migration0001Initial(),
			new Migration0002AddWamidUnique(),
			new Migration0003AddComponentsColumn(),
			new Migration0004AddRecoveriesTable(),
			new Migration0005AddUpdatedAtToRecoveries(),
		);
	}
}
