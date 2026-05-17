<?php
/**
 * Unit tests for Deactivator.
 *
 * @package CartPinger\Tests\Unit\Core
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\Core;

use CartPinger\Core\Deactivator;
use CartPinger\WhatsApp\MessageQueue;
use WP_Mock\Tools\TestCase;

/**
 * Class DeactivatorTest
 */
class DeactivatorTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_deactivate_clears_scheduled_cron_event(): void {
		$timestamp = time() + 60;

		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( MessageQueue::CRON_HOOK )
			->andReturn( $timestamp );

		\WP_Mock::userFunction( 'wp_unschedule_event' )
			->with( $timestamp, MessageQueue::CRON_HOOK )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'wp_clear_scheduled_hook' )
			->with( MessageQueue::CRON_HOOK )
			->once()
			->andReturn( 0 );

		\WP_Mock::userFunction( 'delete_transient' )
			->with( 'cartpinger_templates_cache' )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'flush_rewrite_rules' )
			->once()
			->andReturn( null );

		Deactivator::deactivate();

		$this->addToAssertionCount( 1 );
	}

	public function test_deactivate_skips_unschedule_when_no_event_pending(): void {
		\WP_Mock::userFunction( 'wp_next_scheduled' )
			->with( MessageQueue::CRON_HOOK )
			->andReturn( false );

		// wp_unschedule_event must NOT be called.
		\WP_Mock::userFunction( 'wp_clear_scheduled_hook' )
			->with( MessageQueue::CRON_HOOK )
			->once()
			->andReturn( 0 );

		\WP_Mock::userFunction( 'delete_transient' )
			->with( 'cartpinger_templates_cache' )
			->once()
			->andReturn( true );

		\WP_Mock::userFunction( 'flush_rewrite_rules' )
			->once()
			->andReturn( null );

		Deactivator::deactivate();

		$this->addToAssertionCount( 1 );
	}
}
