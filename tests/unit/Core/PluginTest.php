<?php
/**
 * Unit tests for Plugin singleton.
 *
 * @package CartPinger\Tests\Unit\Core
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Core;

use CartPinger\Core\Plugin;
use WP_Mock\Tools\TestCase;

/**
 * Class PluginTest
 */
class PluginTest extends TestCase {

	/**
	 * Set up WP_Mock expectations before each test.
	 */
	public function setUp(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Tear down WP_Mock after each test.
	 */
	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	/**
	 * Plugin::instance() returns the same object on repeated calls.
	 */
	public function test_instance_returns_singleton(): void {
		$a = Plugin::instance();
		$b = Plugin::instance();

		$this->assertSame( $a, $b );
	}

	/**
	 * Plugin::instance() returns a Plugin instance.
	 */
	public function test_instance_is_correct_type(): void {
		$this->assertInstanceOf( Plugin::class, Plugin::instance() );
	}
}
