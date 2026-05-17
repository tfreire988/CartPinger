<?php
/**
 * Unit tests for MigrationRunner.
 *
 * @package CartPinger\Tests\Unit\Database
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Database;

use CartPinger\Database\MigrationInterface;
use CartPinger\Database\MigrationRunner;
use WP_Mock\Tools\TestCase;

/**
 * Class MigrationRunnerTest
 */
class MigrationRunnerTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// getCurrentVersion()
	// -------------------------------------------------------------------------

	public function test_get_current_version_returns_zero_when_no_option(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 0 );

		$this->assertSame( 0, MigrationRunner::getCurrentVersion() );
	}

	public function test_get_current_version_returns_int_from_option(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 2 );

		$this->assertSame( 2, MigrationRunner::getCurrentVersion() );
	}

	public function test_get_current_version_converts_legacy_semver_to_one(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( '0.1.0' );

		$this->assertSame( 1, MigrationRunner::getCurrentVersion() );
	}

	public function test_get_current_version_converts_any_semver_to_one(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( '0.2.5' );

		$this->assertSame( 1, MigrationRunner::getCurrentVersion() );
	}

	// -------------------------------------------------------------------------
	// needsUpdate()
	// -------------------------------------------------------------------------

	public function test_needs_update_true_when_version_below_current(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 0 );

		$this->assertTrue( MigrationRunner::needsUpdate() );
	}

	public function test_needs_update_false_when_at_current_version(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( MigrationRunner::CURRENT_VERSION );

		$this->assertFalse( MigrationRunner::needsUpdate() );
	}

	// -------------------------------------------------------------------------
	// run() with injectable migrations
	// -------------------------------------------------------------------------

	public function test_run_applies_pending_migrations_in_order(): void {
		$applied = array();

		$m1 = $this->makeMigration( 1, static function () use ( &$applied ): void { $applied[] = 1; } );
		$m2 = $this->makeMigration( 2, static function () use ( &$applied ): void { $applied[] = 2; } );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 0 );

		\WP_Mock::userFunction( 'update_option' )
			->times( 2 );

		MigrationRunner::run( array( $m2, $m1 ) ); // intentionally out of order.

		$this->assertSame( array( 1, 2 ), $applied );
	}

	public function test_run_skips_already_applied_migrations(): void {
		$called = false;
		$m1     = $this->makeMigration( 1, static function () use ( &$called ): void { $called = true; } );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 1 ); // version 1 already applied.

		MigrationRunner::run( array( $m1 ) );

		$this->assertFalse( $called );
	}

	public function test_run_applies_only_new_migrations_after_partial_install(): void {
		$applied = array();

		$m1 = $this->makeMigration( 1, static function () use ( &$applied ): void { $applied[] = 1; } );
		$m2 = $this->makeMigration( 2, static function () use ( &$applied ): void { $applied[] = 2; } );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( 1 ); // migration 1 already done.

		\WP_Mock::userFunction( 'update_option' )
			->once();

		MigrationRunner::run( array( $m1, $m2 ) );

		$this->assertSame( array( 2 ), $applied );
	}

	public function test_run_does_nothing_when_db_is_current(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_db_version', 0 )
			->andReturn( MigrationRunner::CURRENT_VERSION );

		// update_option intentionally not mocked — WP_Mock will error if called.
		MigrationRunner::run( array() );

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeMigration( int $version, \Closure $up ): MigrationInterface {
		return new class( $version, $up ) implements MigrationInterface {
			public function __construct(
				private readonly int $v,
				private readonly \Closure $up
			) {}

			public function getVersion(): int {
				return $this->v;
			}

			public function up(): void {
				( $this->up )();
			}
		};
	}
}
