<?php
/**
 * Async WhatsApp message queue backed by the messages-log DB table.
 *
 * Messages are inserted with status=pending and processed by a WP-Cron
 * single event (cartpinger_process_queue) scheduled immediately after each
 * enqueue call.
 *
 * @package CartPinger\WhatsApp
 */

declare(strict_types=1);

namespace CartPinger\WhatsApp;

use CartPinger\Database\Repositories\MessageLogRepository;
use CartPinger\Support\CredentialStore;
use CartPinger\Support\Sanitizer;

/**
 * Class MessageQueue
 */
final class MessageQueue {

	/** WP-Cron action name. */
	public const CRON_HOOK = 'cartpinger_process_queue';

	private MessageLogRepository $repository;
	private CloudApiClient $client;

	/**
	 * Create a new MessageQueue.
	 *
	 * @param MessageLogRepository $repository Message log DB repository.
	 * @param CloudApiClient       $client     Cloud API HTTP client.
	 */
	public function __construct( MessageLogRepository $repository, CloudApiClient $client ) {
		$this->repository = $repository;
		$this->client     = $client;
	}

	/**
	 * Register the WP-Cron action callback on the plugin boot.
	 */
	public static function register(): void {
		add_action( self::CRON_HOOK, array( self::class, 'processBatch' ) );
	}

	/**
	 * Static WP-Cron callback.
	 *
	 * Builds a MessageQueue from stored credentials and processes pending rows.
	 * No-ops silently when the plugin is not configured.
	 */
	public static function processBatch(): void {
		$client = self::makeClient();

		if ( null === $client ) {
			return;
		}

		( new self( new MessageLogRepository(), $client ) )->processQueue();
	}

	/**
	 * Add a message to the queue.
	 *
	 * Validates the phone with Sanitizer::phone(); silently no-ops on an
	 * invalid number or a DB insertion failure. Schedules an immediate
	 * WP-Cron single event if none is already pending.
	 *
	 * @param string            $recipient_phone E.164 format.
	 * @param string            $template_name   Approved template name.
	 * @param string            $language_code   BCP-47 language code (default: en_US).
	 * @param array<int, mixed> $components      Template variable components.
	 */
	public function enqueue(
		string $recipient_phone,
		string $template_name,
		string $language_code = 'en_US',
		array $components = array()
	): void {
		$phone = Sanitizer::phone( $recipient_phone );

		if ( '' === $phone ) {
			return;
		}

		$id = $this->repository->insert( $phone, $template_name, $language_code, $components );

		if ( null === $id ) {
			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK );
		}
	}

	/**
	 * Process all pending messages in the queue.
	 *
	 * Fetches up to 50 pending rows, sends each via the Cloud API, then
	 * updates the row status to "sent" or "failed" and stores the wamid
	 * returned by Meta on success.
	 */
	public function processQueue(): void {
		$pending = $this->repository->getPending();

		foreach ( $pending as $row ) {
			if ( ! is_object( $row ) ) {
				continue;
			}

			if ( ! isset( $row->id, $row->recipient_phone, $row->template_name ) ) {
				continue;
			}

			$language_code = isset( $row->language_code ) ? (string) $row->language_code : 'en_US';
			$components    = array();

			if ( ! empty( $row->components ) ) {
				$decoded = json_decode( (string) $row->components, true );
				if ( is_array( $decoded ) ) {
					$components = $decoded;
				}
			}

			$result = $this->client->sendTemplate(
				(string) $row->recipient_phone,
				(string) $row->template_name,
				$language_code,
				$components
			);

			$this->repository->updateStatus(
				(int) $row->id,
				$result['success'] ? 'sent' : 'failed'
			);

			if ( $result['success'] && null !== $result['message_id'] ) {
				$this->repository->updateWamid( (int) $row->id, $result['message_id'] );
			}
		}
	}

	/**
	 * Build a CloudApiClient from stored credentials.
	 *
	 * @return CloudApiClient|null Null when credentials are incomplete.
	 */
	private static function makeClient(): ?CloudApiClient {
		$phone_id     = (string) get_option( 'cartpinger_phone_number_id', '' );
		$access_token = CredentialStore::load( 'cartpinger_access_token' );

		if ( '' === $phone_id || '' === $access_token ) {
			return null;
		}

		return new CloudApiClient( $access_token, $phone_id );
	}
}
