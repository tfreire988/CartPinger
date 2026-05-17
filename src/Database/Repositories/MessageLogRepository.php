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

	private const CACHE_GROUP = 'whatscom';

	/**
	 * Build the object-cache key for a pending-messages query.
	 *
	 * @param int $limit Row limit.
	 */
	private function pendingCacheKey( int $limit ): string {
		return 'pending_messages_' . $limit;
	}

	/**
	 * Insert a new message log entry.
	 *
	 * @param string            $recipient_phone E.164 phone number.
	 * @param string|null       $template_name   Template name if applicable.
	 * @param string            $language_code   BCP-47 language code (default: en_US).
	 * @param array<int, mixed> $components      Template variable components.
	 * @param string            $status          Initial status (default: pending).
	 * @return int|null Inserted row ID, or null on failure.
	 */
	public function insert(
		string $recipient_phone,
		?string $template_name = null,
		string $language_code = 'en_US',
		array $components = array(),
		string $status = 'pending'
	): ?int {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'whatscom_messages_log' );

		$encoded = empty( $components ) ? null : wp_json_encode( $components );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'recipient_phone' => $recipient_phone,
				'template_name'   => $template_name,
				'language_code'   => $language_code,
				'components'      => $encoded,
				'status'          => $status,
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return null;
		}

		wp_cache_delete( $this->pendingCacheKey( 50 ), self::CACHE_GROUP );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Store the Meta message ID (wamid) returned by the Cloud API.
	 *
	 * @param int    $id    Row ID.
	 * @param string $wamid Meta message ID (e.g. "wamid.abc123").
	 */
	public function updateWamid( int $id, string $wamid ): void {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'whatscom_messages_log' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'meta_message_id' => $wamid ),
			array( 'id'              => $id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_cache_delete( $this->pendingCacheKey( 50 ), self::CACHE_GROUP );
	}

	/**
	 * Update the status of a message log entry.
	 *
	 * @param int    $id     Row ID.
	 * @param string $status New status value.
	 */
	public function updateStatus( int $id, string $status ): void {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'whatscom_messages_log' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		wp_cache_delete( $this->pendingCacheKey( 50 ), self::CACHE_GROUP );
	}

	/**
	 * Retrieve pending messages up to a given limit.
	 *
	 * @param int $limit Maximum rows to return.
	 * @return array<int, object>
	 */
	public function getPending( int $limit = 50 ): array {
		$cache_key = $this->pendingCacheKey( $limit );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'whatscom_messages_log' );

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			)
		);

		$results = is_array( $results ) ? $results : array();

		wp_cache_set( $cache_key, $results, self::CACHE_GROUP );

		return $results;
	}
}
