<?php
/**
 * WhatsApp Cloud API HTTP client.
 *
 * Sends messages to the Meta Cloud API via wp_remote_post and normalises
 * all responses to the same {success, message_id, error} shape so callers
 * never need to parse raw HTTP responses.
 *
 * @package CartPinger\WhatsApp
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\WhatsApp;

/**
 * Class CloudApiClient
 */
final class CloudApiClient {

	private const API_BASE = 'https://graph.facebook.com/v19.0';
	private const TIMEOUT  = 15;

	private string $access_token;
	private string $phone_number_id;

	/**
	 * Create a new Cloud API client instance.
	 *
	 * @param string $access_token    Bearer token for the Cloud API.
	 * @param string $phone_number_id Meta Phone Number ID (numeric string).
	 */
	public function __construct( string $access_token, string $phone_number_id ) {
		$this->access_token    = $access_token;
		$this->phone_number_id = $phone_number_id;
	}

	/**
	 * Send a template message.
	 *
	 * @param string            $recipient_phone E.164 format, e.g. "+34612345678".
	 * @param string            $template_name   Approved template name.
	 * @param string            $language_code   BCP-47 code, e.g. "es" or "en_US".
	 * @param array<int, mixed> $components      Template variable components.
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	public function sendTemplate(
		string $recipient_phone,
		string $template_name,
		string $language_code = 'en_US',
		array $components = array()
	): array {
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $recipient_phone,
			'type'              => 'template',
			'template'          => array(
				'name'       => $template_name,
				'language'   => array( 'code' => $language_code ),
				'components' => $components,
			),
		);

		return $this->post( $this->messagesUrl(), $payload );
	}

	/**
	 * Send a free-form text message (only within the 24-hour customer-initiated window).
	 *
	 * @param string $recipient_phone E.164 format.
	 * @param string $text            Message body (max 4096 chars).
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	public function sendText( string $recipient_phone, string $text ): array {
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $recipient_phone,
			'type'              => 'text',
			'text'              => array(
				'preview_url' => false,
				'body'        => $text,
			),
		);

		return $this->post( $this->messagesUrl(), $payload );
	}

	/**
	 * Return the API base URL.
	 */
	public function getApiBase(): string {
		return self::API_BASE;
	}

	/**
	 * Build the /messages endpoint URL for this phone number.
	 */
	private function messagesUrl(): string {
		return sprintf( '%s/%s/messages', self::API_BASE, $this->phone_number_id );
	}

	/**
	 * Execute a POST to the Cloud API and normalise the response.
	 *
	 * @param string               $url     Fully-qualified endpoint URL.
	 * @param array<string, mixed> $payload Request body as an associative array.
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	private function post( string $url, array $payload ): array {
		$encoded = wp_json_encode( $payload );

		if ( false === $encoded ) {
			return array(
				'success'    => false,
				'message_id' => null,
				'error'      => 'Failed to encode request body.',
			);
		}

		$response = wp_remote_post(
			$url,
			array(
				'headers' => $this->buildRequestHeaders(),
				'body'    => $encoded,
				'timeout' => self::TIMEOUT,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'    => false,
				'message_id' => null,
				'error'      => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );

		if ( $status < 200 || $status >= 300 ) {
			return $this->errorResponse( $data, $status );
		}

		return $this->successResponse( $data );
	}

	/**
	 * Extract message ID from a successful Cloud API response body.
	 *
	 * @param mixed $data Decoded JSON response (may be any type).
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	private function successResponse( mixed $data ): array {
		$message_id = null;

		if ( is_array( $data ) && isset( $data['messages'] ) && is_array( $data['messages'] ) ) {
			$first = $data['messages'][0] ?? null;
			if ( is_array( $first ) && isset( $first['id'] ) && is_string( $first['id'] ) ) {
				$message_id = $first['id'];
			}
		}

		return array(
			'success'    => true,
			'message_id' => $message_id,
			'error'      => null,
		);
	}

	/**
	 * Build an error response from a non-2xx Cloud API reply.
	 *
	 * @param mixed $data   Decoded JSON response (may be any type).
	 * @param int   $status HTTP status code.
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	private function errorResponse( mixed $data, int $status ): array {
		$error = sprintf( 'HTTP %d', $status );

		if ( is_array( $data ) && isset( $data['error'] ) && is_array( $data['error'] ) ) {
			$err = $data['error'];
			if ( isset( $err['message'] ) && is_string( $err['message'] ) ) {
				$error = $err['message'];
			}
		}

		return array(
			'success'    => false,
			'message_id' => null,
			'error'      => $error,
		);
	}

	/**
	 * Build the Authorization headers for Cloud API requests.
	 *
	 * @return array<string, string>
	 */
	private function buildRequestHeaders(): array {
		return array(
			'Authorization' => 'Bearer ' . $this->access_token,
			'Content-Type'  => 'application/json',
		);
	}
}
