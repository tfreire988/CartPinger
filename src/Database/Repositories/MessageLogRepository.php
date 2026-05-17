<?php
/**
 * Repository for the messages log table.
 *
 * @package CartPinger\Database\Repositories
 */

declare(strict_types=1);

namespace CartPinger\Database\Repositories;

/**
 * Class MessageLogRepository
 */
final class MessageLogRepository {

	private const CACHE_GROUP = 'cartpinger';

	/**
	 * Maps Meta status codes to the local status value and timestamp column.
	 *
	 * Unknown codes (e.g. 'queued', 'deleted') are not in the map and are
	 * silently ignored by applyDeliveryStatus().
	 *
	 * @var array<string, array{db_status: string, ts_col: string|null}>
	 */
	private const STATUS_MAP = array(
		'sent'      => array(
			'db_status' => 'sent',
			'ts_col'    => 'sent_at',
		),
		'delivered' => array(
			'db_status' => 'delivered',
			'ts_col'    => 'delivered_at',
		),
		'read'      => array(
			'db_status' => 'read',
			'ts_col'    => 'read_at',
		),
		'failed'    => array(
			'db_status' => 'failed',
			'ts_col'    => null,
		),
	);

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

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

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

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'meta_message_id' => $wamid ),
			array( 'id' => $id ),
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

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

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
	 * Apply a Meta delivery-status event to the matching messages-log row.
	 *
	 * Looks up the row by its Meta message ID (wamid), updates the status
	 * column and the appropriate timestamp column, then returns the row so
	 * callers can inspect template_name and recipient_phone for further
	 * cross-referencing (e.g. updating cartpinger_recoveries).
	 *
	 * Returns null when:
	 *  - $meta_status is not in STATUS_MAP (unknown / unhandled code)
	 *  - No row exists with the given $wamid
	 *
	 * @param string $wamid       Meta message ID (value of meta_message_id column).
	 * @param string $meta_status Raw status string from Meta (sent|delivered|read|failed).
	 * @param int    $timestamp   Unix timestamp from the status event.
	 * @return object|null        The row as stdClass with status mutated, or null.
	 */
	public function applyDeliveryStatus( string $wamid, string $meta_status, int $timestamp ): ?object {
		if ( ! isset( self::STATUS_MAP[ $meta_status ] ) ) {
			return null;
		}

		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, template_name, recipient_phone FROM `{$table}` WHERE meta_message_id = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wamid
			)
		);

		if ( ! $row instanceof \stdClass ) {
			return null;
		}

		$map  = self::STATUS_MAP[ $meta_status ];
		$data = array( 'status' => $map['db_status'] );

		if ( null !== $map['ts_col'] && $timestamp > 0 ) {
			$data[ $map['ts_col'] ] = gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			$data,
			array( 'id' => (int) $row->id )
		);

		wp_cache_delete( $this->pendingCacheKey( 50 ), self::CACHE_GROUP );

		$row->status = $map['db_status'];
		return $row;
	}

	/**
	 * Aggregate delivery KPIs for abandoned-cart-recovery messages.
	 *
	 * Counts rows where status reached 'delivered' (includes 'read') and rows
	 * where status is exactly 'read'. Single query — O(1).
	 *
	 * @return array{delivered: int, read: int}
	 */
	public function getDeliveryStats(): array {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(status IN ('delivered','read')) AS delivered, SUM(status = 'read') AS read_count FROM `{$table}` WHERE template_name = %s",
				'abandoned_cart_recovery'
			)
		);

		return array(
			'delivered' => isset( $row->delivered ) ? (int) $row->delivered : 0,
			'read'      => isset( $row->read_count ) ? (int) $row->read_count : 0,
		);
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

		$table = esc_sql( $wpdb->prefix . 'cartpinger_messages_log' );

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
