<?php
/**
 * Async message queue stub.
 *
 * @package WhatsCom\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\WhatsApp;

/**
 * Class MessageQueue
 *
 * TODO v1.0: enqueue messages to wp_whatscom_messages_log, process via WP-Cron.
 */
final class MessageQueue {

	/**
	 * Add a message to the queue.
	 *
	 * @param string            $recipient_phone E.164 format.
	 * @param string            $template_name   Approved template name.
	 * @param array<int, mixed> $components      Template components.
	 */
	public function enqueue( string $recipient_phone, string $template_name, array $components = array() ): void {
		// TODO v1.0: insert row into whatscom_messages_log with status=pending.
	}

	/**
	 * Process pending messages in the queue. Called by WP-Cron.
	 */
	public function processQueue(): void {
		// TODO v1.0: fetch pending rows, call CloudApiClient, update status.
	}
}
