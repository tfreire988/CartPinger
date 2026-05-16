<?php
/**
 * Unit tests for CloudApiClient.
 *
 * @package WhatsCom\Tests\Unit\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\Tests\Unit\WhatsApp;

use WhatsCom\WhatsApp\CloudApiClient;
use WP_Mock\Tools\TestCase;

/**
 * Class CloudApiClientTest
 */
class CloudApiClientTest extends TestCase {

	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function makeClient( string $token = 'test_token', string $phone_id = '1234567890' ): CloudApiClient {
		return new CloudApiClient( $token, $phone_id );
	}

	/**
	 * Mock a full successful HTTP round-trip.
	 *
	 * @param string $response_body Raw JSON string the API "returns".
	 */
	private function mockHttpSuccess( string $response_body ): void {
		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( function ( $data ) { return json_encode( $data ); } );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturn( array() );

		\WP_Mock::userFunction( 'is_wp_error' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->andReturn( 200 );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( $response_body );
	}

	/**
	 * Mock an HTTP round-trip that ends in a WP_Error (network failure).
	 *
	 * @param string $error_message Error message the WP_Error carries.
	 */
	private function mockHttpWpError( string $error_message = 'cURL error 6: Could not resolve host' ): void {
		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( function ( $data ) { return json_encode( $data ); } );

		$wp_error = new \WP_Error( 'http_request_failed', $error_message );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturn( $wp_error );

		\WP_Mock::userFunction( 'is_wp_error' )
			->andReturn( true );
	}

	/**
	 * Mock an HTTP round-trip that returns a non-2xx status.
	 *
	 * @param int    $status        HTTP status code.
	 * @param string $response_body Raw JSON string.
	 */
	private function mockHttpError( int $status, string $response_body ): void {
		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( function ( $data ) { return json_encode( $data ); } );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturn( array() );

		\WP_Mock::userFunction( 'is_wp_error' )
			->andReturn( false );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->andReturn( $status );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( $response_body );
	}

	// -------------------------------------------------------------------------
	// getApiBase()
	// -------------------------------------------------------------------------

	public function test_get_api_base_returns_graph_facebook_url(): void {
		$this->assertSame(
			'https://graph.facebook.com/v19.0',
			$this->makeClient()->getApiBase()
		);
	}

	// -------------------------------------------------------------------------
	// sendTemplate() — success paths
	// -------------------------------------------------------------------------

	public function test_send_template_returns_success_true_and_message_id(): void {
		$this->mockHttpSuccess( '{"messages":[{"id":"wamid.HBgMtest"}]}' );

		$result = $this->makeClient()->sendTemplate( '+34612345678', 'order_confirmed' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'wamid.HBgMtest', $result['message_id'] );
		$this->assertNull( $result['error'] );
	}

	public function test_send_template_returns_null_message_id_when_messages_key_missing(): void {
		$this->mockHttpSuccess( '{"meta":{"api_status":"stable"}}' );

		$result = $this->makeClient()->sendTemplate( '+34612345678', 'order_confirmed' );

		$this->assertTrue( $result['success'] );
		$this->assertNull( $result['message_id'] );
	}

	public function test_send_template_url_contains_phone_number_id(): void {
		$captured_url = '';

		\WP_Mock::userFunction( 'wp_json_encode' )
			->andReturnUsing( function ( $data ) { return json_encode( $data ); } );

		\WP_Mock::userFunction( 'wp_remote_post' )
			->andReturnUsing(
				function ( string $url ) use ( &$captured_url ) {
					$captured_url = $url;
					return array();
				}
			);

		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' )
			->andReturn( '{"messages":[{"id":"wamid.test"}]}' );

		$this->makeClient( 'token', '9876543210' )->sendTemplate( '+1234567890', 'tpl' );

		$this->assertStringContainsString( '9876543210', $captured_url );
		$this->assertStringContainsString( '/messages', $captured_url );
	}

	// -------------------------------------------------------------------------
	// sendTemplate() — error paths
	// -------------------------------------------------------------------------

	public function test_send_template_returns_error_on_wp_error(): void {
		$this->mockHttpWpError( 'cURL error 6: Could not resolve host' );

		$result = $this->makeClient()->sendTemplate( '+34612345678', 'order_confirmed' );

		$this->assertFalse( $result['success'] );
		$this->assertNull( $result['message_id'] );
		$this->assertStringContainsString( 'Could not resolve host', (string) $result['error'] );
	}

	public function test_send_template_returns_api_error_message_on_4xx(): void {
		$body = '{"error":{"message":"Invalid OAuth access token.","type":"OAuthException","code":190}}';
		$this->mockHttpError( 401, $body );

		$result = $this->makeClient()->sendTemplate( '+34612345678', 'order_confirmed' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Invalid OAuth access token.', $result['error'] );
	}

	public function test_send_template_returns_generic_http_error_when_no_api_message(): void {
		$this->mockHttpError( 500, '{"unexpected":"body"}' );

		$result = $this->makeClient()->sendTemplate( '+34612345678', 'order_confirmed' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'HTTP 500', $result['error'] );
	}

	// -------------------------------------------------------------------------
	// sendText() — success paths
	// -------------------------------------------------------------------------

	public function test_send_text_returns_success_true_and_message_id(): void {
		$this->mockHttpSuccess( '{"messages":[{"id":"wamid.textmsg"}]}' );

		$result = $this->makeClient()->sendText( '+34612345678', 'Hello from WhatsCom!' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 'wamid.textmsg', $result['message_id'] );
		$this->assertNull( $result['error'] );
	}

	// -------------------------------------------------------------------------
	// sendText() — error paths
	// -------------------------------------------------------------------------

	public function test_send_text_returns_error_on_wp_error(): void {
		$this->mockHttpWpError( 'Connection timed out' );

		$result = $this->makeClient()->sendText( '+34612345678', 'Hello!' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'timed out', (string) $result['error'] );
	}
}
