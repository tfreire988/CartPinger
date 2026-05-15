<?php
/**
 * Handles inbound webhooks from Meta.
 *
 * @package WhatsCom\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\WhatsApp;

/**
 * Class WebhookHandler
 *
 * TODO v1.0: verify hub.verify_token on GET, process status updates on POST.
 */
final class WebhookHandler {

	/**
	 * Verify the webhook subscription challenge from Meta.
	 *
	 * @param string $mode        hub.mode parameter.
	 * @param string $token       hub.verify_token parameter.
	 * @param string $challenge   hub.challenge parameter.
	 * @return string|null        Challenge string on success, null on failure.
	 */
	public function verifySubscription( string $mode, string $token, string $challenge ): ?string {
		if ( 'subscribe' !== $mode ) {
			return null;
		}

		$stored_token = (string) get_option( 'whatscom_webhook_verify_token', '' );
		if ( empty( $stored_token ) || ! hash_equals( $stored_token, $token ) ) {
			return null;
		}

		return $challenge;
	}

	/**
	 * Process an inbound webhook payload.
	 *
	 * @param string $raw_body Raw request body (JSON).
	 * @param string $signature X-Hub-Signature-256 header value.
	 */
	public function process( string $raw_body, string $signature ): void {
		// TODO v1.0: verify HMAC signature, parse payload, dispatch events.
	}
}
