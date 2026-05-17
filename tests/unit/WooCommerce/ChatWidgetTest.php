<?php
/**
 * Unit tests for ChatWidget.
 *
 * @package CartPinger\Tests\Unit\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\WooCommerce;

use CartPinger\WooCommerce\ChatWidget;
use WP_Mock\Tools\TestCase;

/**
 * Class ChatWidgetTest
 */
class ChatWidgetTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// register()
	// -------------------------------------------------------------------------

	public function test_register_adds_footer_action(): void {
		\WP_Mock::expectActionAdded(
			'wp_footer',
			array( ChatWidget::class, 'renderWidget' ),
			100
		);

		ChatWidget::register();

		$this->addToAssertionCount( 1 );
	}

	// -------------------------------------------------------------------------
	// renderWidget() — guards
	// -------------------------------------------------------------------------

	public function test_render_widget_outputs_nothing_when_disabled(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( false );

		ob_start();
		ChatWidget::renderWidget();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_render_widget_outputs_nothing_when_phone_empty(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '' );

		ob_start();
		ChatWidget::renderWidget();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_render_widget_outputs_nothing_when_phone_invalid(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( 'not-a-phone' );

		ob_start();
		ChatWidget::renderWidget();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_render_widget_outputs_html_when_configured(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '+34612345678' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_message', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'esc_url' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( 'esc_attr' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( '__' )
			->andReturnArg( 0 );

		ob_start();
		ChatWidget::renderWidget();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cartpinger-chat-widget', $output );
		$this->assertStringContainsString( 'wa.me/34612345678', $output );
		$this->assertStringContainsString( '<svg', $output );
	}

	public function test_render_widget_includes_pre_filled_message_in_url(): void {
		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_enabled', false )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_support_phone', '' )
			->andReturn( '+34612345678' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_widget_message', '' )
			->andReturn( 'Hello there' );

		\WP_Mock::userFunction( 'esc_url' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( 'esc_attr' )
			->andReturnArg( 0 );

		\WP_Mock::userFunction( '__' )
			->andReturnArg( 0 );

		ob_start();
		ChatWidget::renderWidget();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'text=Hello%20there', $output );
	}

	// -------------------------------------------------------------------------
	// buildUrl()
	// -------------------------------------------------------------------------

	public function test_build_url_returns_wa_me_url_with_digits_only(): void {
		$url = ChatWidget::buildUrl( '+34 612 345 678', '' );

		$this->assertSame( 'https://wa.me/34612345678', $url );
	}

	public function test_build_url_appends_encoded_message(): void {
		$url = ChatWidget::buildUrl( '+34612345678', 'Hello world' );

		$this->assertSame( 'https://wa.me/34612345678?text=Hello%20world', $url );
	}

	public function test_build_url_returns_empty_for_blank_phone(): void {
		$url = ChatWidget::buildUrl( '', '' );

		$this->assertSame( '', $url );
	}

	public function test_build_url_returns_empty_when_only_non_digits(): void {
		$url = ChatWidget::buildUrl( '+++---', '' );

		$this->assertSame( '', $url );
	}
}
