<?php
/**
 * WhatsApp Cloud API client stub.
 *
 * @package WhatsCom\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\WhatsApp;

/**
 * Class CloudApiClient
 *
 * TODO v1.0: implement HTTP calls to graph.facebook.com/v19.0/{phone_number_id}/messages
 */
final class CloudApiClient {

	private const API_BASE = 'https://graph.facebook.com/v19.0';

	private string $access_token;
	private string $phone_number_id;

	public function __construct( string $access_token, string $phone_number_id ) {
		$this->access_token    = $access_token;
		$this->phone_number_id = $phone_number_id;
	}

	/**
	 * Send a template message.
	 *
	 * @param string               $recipient_phone E.164 format, e.g. "+34612345678".
	 * @param string               $template_name   Approved template name.
	 * @param string               $language_code   e.g. "es", "en_US".
	 * @param array<int, mixed>    $components      Template variable components.
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	public function sendTemplate(
		string $recipient_phone,
		string $template_name,
		string $language_code = 'en_US',
		array $components = array()
	): array {
		// TODO v1.0: implement wp_remote_post() to Cloud API.
		return array(
			'success'    => false,
			'message_id' => null,
			'error'      => __( 'Cloud API client not yet implemented.', 'whatscom' ),
		);
	}

	/**
	 * Send a free-form text message (only within 24h customer-initiated window).
	 *
	 * @param string $recipient_phone E.164 format.
	 * @param string $text            Message body.
	 * @return array{success: bool, message_id: string|null, error: string|null}
	 */
	public function sendText( string $recipient_phone, string $text ): array {
		// TODO v1.0: implement.
		return array(
			'success'    => false,
			'message_id' => null,
			'error'      => __( 'Cloud API client not yet implemented.', 'whatscom' ),
		);
	}

	/**
	 * Return the API base URL (useful for testing).
	 */
	public function getApiBase(): string {
		return self::API_BASE;
	}
}
