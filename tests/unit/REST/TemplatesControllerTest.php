<?php
/**
 * Unit tests for TemplatesController.
 *
 * @package CartPinger\Tests\Unit\REST
 */

declare(strict_types=1);

namespace CartPinger\Tests\Unit\REST;

use CartPinger\REST\TemplatesController;
use CartPinger\Support\Encryptor;
use WP_Mock\Tools\TestCase;

/**
 * Class TemplatesControllerTest
 */
class TemplatesControllerTest extends TestCase {

	private const WABA_ID      = '9876543210';
	private const ACCESS_TOKEN = 'EAAtest_token_xyz';
	private const SALT_AUTH    = 'auth-salt-abcdef';
	private const SALT_SECURE  = 'secure-auth-salt-ghijkl';

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function mockSalts(): void {
		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'auth' )
			->andReturn( self::SALT_AUTH );

		\WP_Mock::userFunction( 'wp_salt' )
			->with( 'secure_auth' )
			->andReturn( self::SALT_SECURE );
	}

	private function mockConfigured(): void {
		$encrypted = Encryptor::encrypt( self::ACCESS_TOKEN );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_waba_id', '' )
			->andReturn( self::WABA_ID );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_access_token', '' )
			->andReturn( $encrypted );
	}

	// -------------------------------------------------------------------------
	// handleGet()
	// -------------------------------------------------------------------------

	public function test_returns_422_when_not_configured(): void {
		$this->mockSalts();

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_waba_id', '' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'get_option' )
			->with( 'cartpinger_access_token', '' )
			->andReturn( '' );

		$response = TemplatesController::handleGet( new \WP_REST_Request() );

		$this->assertSame( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertStringContainsString( 'configured', $data['message'] );
	}

	public function test_returns_cached_templates_on_cache_hit(): void {
		$this->mockSalts();
		$this->mockConfigured();

		$cached = array(
			array( 'name' => 'order_confirmed', 'status' => 'APPROVED', 'language' => 'en_US' ),
			array( 'name' => 'order_completed', 'status' => 'APPROVED', 'language' => 'es' ),
		);

		\WP_Mock::userFunction( 'get_transient' )
			->with( 'cartpinger_templates_cache' )
			->andReturn( $cached );

		$response = TemplatesController::handleGet( new \WP_REST_Request() );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertCount( 2, $data['templates'] );
		$this->assertSame( 2, $data['count'] );
	}

	public function test_returns_empty_templates_on_api_failure(): void {
		$this->mockSalts();
		$this->mockConfigured();

		\WP_Mock::userFunction( 'get_transient' )
			->with( 'cartpinger_templates_cache' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_get' )
			->andReturn( new \WP_Error( 'http_error', 'Connection refused' ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( true );

		$response = TemplatesController::handleGet( new \WP_REST_Request() );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( array(), $data['templates'] );
		$this->assertSame( 0, $data['count'] );
	}
}
