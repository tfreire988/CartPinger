<?php
/**
 * Listens to the cartpinger_webhook_entry action and persists delivery-status
 * updates (sent / delivered / read / failed) to the database.
 *
 * The WebhookHandler already validates the HMAC signature and dispatches one
 * do_action per payload entry. This class only contains the business logic for
 * extracting status events and calling the relevant repositories.
 *
 * @package CartPinger\WhatsApp
 */

declare(strict_types=1);

namespace CartPinger\WhatsApp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Database\Repositories\CartRecoveryRepository;
use CartPinger\Database\Repositories\MessageLogRepository;

/**
 * Class DeliveryStatusHandler
 */
final class DeliveryStatusHandler {

	/**
	 * Register the listener on the entry-dispatch action.
	 *
	 * Called from Plugin::boot() so the listener is always active, regardless
	 * of whether the current request is REST, admin, or CLI.
	 */
	public static function register(): void {
		add_action( 'cartpinger_webhook_entry', array( self::class, 'handleEntry' ) );
	}

	/**
	 * Process a single webhook entry dispatched by WebhookHandler.
	 *
	 * Iterates over changes[] looking for field=messages entries that contain
	 * a statuses[] array. Each status event is persisted via the repositories.
	 *
	 * @param mixed $entry Entry array from the Meta payload.
	 */
	public static function handleEntry( mixed $entry ): void {
		if ( ! is_array( $entry ) ) {
			return;
		}

		if ( ! isset( $entry['changes'] ) || ! is_array( $entry['changes'] ) ) {
			return;
		}

		$message_repo  = new MessageLogRepository();
		$recovery_repo = new CartRecoveryRepository();

		foreach ( $entry['changes'] as $change ) {
			if ( ! is_array( $change ) ) {
				continue;
			}

			if ( ( $change['field'] ?? '' ) !== 'messages' ) {
				continue;
			}

			$statuses = $change['value']['statuses'] ?? array();
			if ( ! is_array( $statuses ) ) {
				continue;
			}

			foreach ( $statuses as $event ) {
				if ( ! is_array( $event ) ) {
					continue;
				}

				$wamid     = (string) ( $event['id'] ?? '' );
				$status    = (string) ( $event['status'] ?? '' );
				$timestamp = (int) ( $event['timestamp'] ?? 0 );

				if ( '' === $wamid || '' === $status ) {
					continue;
				}

				$row = $message_repo->applyDeliveryStatus( $wamid, $status, $timestamp );

				if ( null === $row ) {
					continue;
				}

				// Cross-reference recovery records for abandoned-cart messages.
				if ( 'abandoned_cart_recovery' === ( $row->template_name ?? '' ) ) {
					$recovery_repo->markRecoveryDelivery(
						(string) ( $row->recipient_phone ?? '' ),
						$status
					);
				}
			}
		}
	}
}
