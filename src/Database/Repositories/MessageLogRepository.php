<?php
/**
 * Repository for the messages log table.
 *
 * @package WhatsCom\Database\Repositories
 */

declare(strict_types=1);

namespace WhatsCom\Database\Repositories;

/**
 * Class MessageLogRepository
 */
final class MessageLogRepository {

	/**
	 * Insert a new message log entry.
	 *
	 * @param string      $recipient_phone E.164 phone number.
	 * @param string|null $template_name   Template name if applicable.
	 * @param string      $status          Initial status (default: pending).
	 * @return int|null   Inserted row ID, or null on failure.
	 */
	public function insert( string $recipient_phone, ?string $template_name = null, string $status = 'pending' ): ?int {
		global $wpdb;

		$table = $wpdb->prefix . 'whatscom_messages_log';

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'recipient_phone' => $recipient_phone,
				'template_name'   => $template_name,
				'status'          => $status,
			),
			array( '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return null;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update the status of a message log entry.
	 *
	 * @param int    $id     Row ID.
	 * @param string $status New status value.
	 */
	public function updateStatus( int $id, string $status ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'whatscom_messages_log';

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Retrieve pending messages up to a given limit.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array<int, object>
	 */
	public function getPending( int $limit = 50 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'whatscom_messages_log';

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			)
		);

		return is_array( $results ) ? $results : array();
	}
}
