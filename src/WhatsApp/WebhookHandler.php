<?php
/**
 * Handles inbound webhooks from Meta.
 *
 * Responsible for two tasks:
 *   1. Verifying the webhook subscription challenge (GET request from Meta).
 *   2. Processing signed POST payloads — verifying the X-Hub-Signature-256
 *      HMAC before dispatching entries via do_action().
 *
 * @package CartPinger\WhatsApp
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\WhatsApp;

/**
 * Class WebhookHandler
 */
final class WebhookHandler {

	private string $verify_token;
	private string $app_secret;

	/**
	 * Create a new webhook handler.
	 *
	 * @param string $verify_token Webhook verify token stored in plugin settings.
	 * @param string $app_secret   Meta App Secret used to validate HMAC signatures.
	 */
	public function __construct( string $verify_token, string $app_secret ) {
		$this->verify_token = $verify_token;
		$this->app_secret   = $app_secret;
	}

	/**
	 * Verify the webhook subscription challenge sent by Meta on GET requests.
	 *
	 * Returns the hub.challenge string when all conditions pass, or null to
	 * signal a 403 response to the caller.
	 *
	 * @param string $mode      hub.mode query parameter (must be "subscribe").
	 * @param string $token     hub.verify_token query parameter.
	 * @param string $challenge hub.challenge query parameter.
	 * @return string|null Challenge on success, null on failure.
	 */
	public function verifySubscription( string $mode, string $token, string $challenge ): ?string {
		if ( 'subscribe' !== $mode ) {
			return null;
		}

		if ( empty( $this->verify_token ) || ! hash_equals( $this->verify_token, $token ) ) {
			return null;
		}

		return $challenge;
	}

	/**
	 * Process an inbound webhook POST payload from Meta.
	 *
	 * Silently discards the payload when the HMAC signature is invalid,
	 * the body is not valid JSON, or the object type is not
	 * "whatsapp_business_account". On success fires the
	 * cartpinger_webhook_entry action once per entry in the payload.
	 *
	 * @param string $raw_body  Raw request body (JSON string from Meta).
	 * @param string $signature X-Hub-Signature-256 header value, e.g. "sha256=abc…".
	 */
	public function process( string $raw_body, string $signature ): void {
		if ( ! $this->verifySignature( $raw_body, $signature ) ) {
			return;
		}

		$payload = json_decode( $raw_body, true );

		if ( ! is_array( $payload ) ) {
			return;
		}

		if ( ! isset( $payload['object'] ) || 'whatsapp_business_account' !== $payload['object'] ) {
			return;
		}

		$this->dispatchEntries( $payload );
	}

	/**
	 * Verify the X-Hub-Signature-256 HMAC against the raw request body.
	 *
	 * Uses hash_equals() to prevent timing-attack leaks.
	 *
	 * @param string $raw_body  Raw request body.
	 * @param string $signature Signature header, expected format "sha256=<hex>".
	 * @return bool True when the signature is valid.
	 */
	private function verifySignature( string $raw_body, string $signature ): bool {
		if ( ! str_starts_with( $signature, 'sha256=' ) ) {
			return false;
		}

		if ( empty( $this->app_secret ) ) {
			return false;
		}

		$received = substr( $signature, strlen( 'sha256=' ) );
		$expected = hash_hmac( 'sha256', $raw_body, $this->app_secret );

		return hash_equals( $expected, $received );
	}

	/**
	 * Fire the cartpinger_webhook_entry action for each entry in the payload.
	 *
	 * @param array<array-key, mixed> $payload Decoded webhook payload.
	 */
	private function dispatchEntries( array $payload ): void {
		if ( ! isset( $payload['entry'] ) || ! is_array( $payload['entry'] ) ) {
			return;
		}

		foreach ( $payload['entry'] as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			do_action( 'cartpinger_webhook_entry', $entry );
		}
	}
}
