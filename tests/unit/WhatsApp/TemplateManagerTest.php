<?php
/**
 * Unit tests for TemplateManager.
 *
 * @package WhatsCom\Tests\Unit\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\WhatsApp;

use WhatsCom\WhatsApp\TemplateManager;
use WP_Mock\Tools\TestCase;

/**
 * Class TemplateManagerTest
 */
class TemplateManagerTest extends TestCase {

	private const WABA_ID      = '9876543210';
	private const ACCESS_TOKEN = 'EAAtest_token';

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeManager(): TemplateManager {
		return new TemplateManager( self::WABA_ID, self::ACCESS_TOKEN );
	}

	/**
	 * Build a valid Meta API response body for two templates.
	 *
	 * @return string JSON string.
	 */
	private function apiResponse(): string {
		return (string) json_encode(
			array(
				'data' => array(
					array(
						'name'     => 'order_confirmed',
						'status'   => 'APPROVED',
						'language' => 'en_US',
					),
					array(
						'name'     => 'order_completed',
						'status'   => 'APPROVED',
						'language' => 'es',
					),
				),
			)
		);
	}

	// -------------------------------------------------------------------------
	// getTemplates() — cache hit
	// -------------------------------------------------------------------------

	public function test_get_templates_returns_cached_value(): void {
		$cached = array(
			array( 'name' => 'order_confirmed', 'status' => 'APPROVED', 'language' => 'en_US' ),
		);

		\WP_Mock::userFunction( 'get_transient' )
			->with( 'whatscom_templates_cache' )
			->andReturn( $cached );

		$result = $this->makeManager()->getTemplates();

		$this->assertSame( $cached, $result );
	}

	// -------------------------------------------------------------------------
	// getTemplates() — cache miss → API call
	// -------------------------------------------------------------------------

	public function test_get_templates_fetches_from_api_on_cache_miss(): void {
		\WP_Mock::userFunction( 'get_transient' )
			->with( 'whatscom_templates_cache' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_get' )
			->andReturn( array( 'response' => array( 'code' => 200 ) ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $this->apiResponse() );

		\WP_Mock::userFunction( 'set_transient' )
			->with( 'whatscom_templates_cache', \Mockery::type( 'array' ), 3600 )
			->once()
			->andReturn( true );

		$result = $this->makeManager()->getTemplates();

		$this->assertCount( 2, $result );
		$this->assertSame( 'order_confirmed', $result[0]['name'] );
		$this->assertSame( 'APPROVED', $result[0]['status'] );
		$this->assertSame( 'en_US', $result[0]['language'] );
	}

	// -------------------------------------------------------------------------
	// syncFromApi() — error paths
	// -------------------------------------------------------------------------

	public function test_sync_returns_empty_array_when_credentials_missing(): void {
		$manager = new TemplateManager( '', '' );

		$result = $manager->syncFromApi();

		$this->assertSame( array(), $result );
	}

	public function test_sync_returns_empty_array_on_wp_error(): void {
		\WP_Mock::userFunction( 'wp_remote_get' )
			->andReturn( new \WP_Error( 'http_error', 'Connection refused' ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( true );

		$result = $this->makeManager()->syncFromApi();

		$this->assertSame( array(), $result );
	}

	public function test_sync_returns_empty_array_on_non_200_status(): void {
		\WP_Mock::userFunction( 'wp_remote_get' )
			->andReturn( array( 'response' => array( 'code' => 401 ) ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 401 );

		$result = $this->makeManager()->syncFromApi();

		$this->assertSame( array(), $result );
	}

	public function test_sync_returns_empty_array_on_invalid_json(): void {
		\WP_Mock::userFunction( 'wp_remote_get' )
			->andReturn( array( 'response' => array( 'code' => 200 ) ) );

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( 'not-json{' );

		$result = $this->makeManager()->syncFromApi();

		$this->assertSame( array(), $result );
	}

	public function test_sync_skips_incomplete_template_entries(): void {
		$body = (string) json_encode(
			array(
				'data' => array(
					array( 'name' => 'good_template', 'status' => 'APPROVED', 'language' => 'en_US' ),
					array( 'name' => 'missing_fields' ), // no status/language
					'not-an-array',
				),
			)
		);

		\WP_Mock::userFunction( 'wp_remote_get' )->andReturn( array() );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		\WP_Mock::userFunction( 'set_transient' )->andReturn( true );

		$result = $this->makeManager()->syncFromApi();

		$this->assertCount( 1, $result );
		$this->assertSame( 'good_template', $result[0]['name'] );
	}
}
