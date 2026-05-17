<?php
/**
 * Repository for cartpinger_recoveries table.
 *
 * @package CartPinger\Database\Repositories
 */

declare(strict_types=1);

namespace CartPinger\Database\Repositories;

/**
 * Class CartRecoveryRepository
 */
final class CartRecoveryRepository {

	/**
	 * Insert or update a recovery row keyed by customer_phone.
	 *
	 * Always overwrites cart_contents and resets status to 'pending'.
	 *
	 * @param string $phone          E.164 phone number.
	 * @param string $name           Customer first name (may be empty).
	 * @param string $cart_contents  JSON-encoded WC cart data.
	 * @param string $token          Unique random hex token (64 chars).
	 * @param bool   $gdpr_consent   Whether the customer consented.
	 * @return int|null              Inserted/updated row ID, or null on failure.
	 */
	public function upsert(
		string $phone,
		string $name,
		string $cart_contents,
		string $token,
		bool $gdpr_consent = false
	): ?int {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$table}`
					(customer_phone, customer_name, cart_contents, recovery_token, status, gdpr_consent)
				VALUES
					(%s, %s, %s, %s, 'pending', %d)
				ON DUPLICATE KEY UPDATE
					customer_name  = VALUES(customer_name),
					cart_contents  = VALUES(cart_contents),
					recovery_token = VALUES(recovery_token),
					status         = 'pending',
					gdpr_consent   = VALUES(gdpr_consent)",
				$phone,
				$name,
				$cart_contents,
				$token,
				(int) $gdpr_consent
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $result ) {
			return null;
		}

		$inserted = $wpdb->insert_id;
		if ( $inserted > 0 ) {
			return $inserted;
		}

		// ON DUPLICATE KEY UPDATE path — fetch the existing row id.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$table}` WHERE customer_phone = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$phone
			)
		);

		return null !== $id ? (int) $id : null;
	}

	/**
	 * Find a recovery row by its token.
	 *
	 * @param string $token Recovery token.
	 * @return object|null  Row as stdClass or null when not found.
	 */
	public function findByToken( string $token ): ?object {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE recovery_token = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$token
			)
		);

		return $row instanceof \stdClass ? $row : null;
	}

	/**
	 * Return all pending rows created before a given cutoff.
	 *
	 * @param string $before MySQL datetime string (e.g. '2025-01-01 00:00:00').
	 * @return object[]
	 */
	public function getPending( string $before ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE status = 'pending' AND created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$before
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Revoke GDPR consent for a phone number.
	 *
	 * Sets status=expired and gdpr_consent=0 for every 'pending' row belonging
	 * to the given phone. Must be called immediately when the customer unchecks
	 * the WhatsApp consent box so no recovery message is ever sent.
	 *
	 * @param string $phone E.164 phone number.
	 */
	public function revokeConsent( string $phone ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'       => 'expired',
				'gdpr_consent' => 0,
			),
			array(
				'customer_phone' => $phone,
				'status'         => 'pending',
			)
		);
	}

	/**
	 * Advance a recovery row's status based on a Meta delivery receipt.
	 *
	 * Only 'delivered' and 'read' receipts are meaningful for recovery rows.
	 * All other values (e.g. 'sent', 'failed') are silently ignored so that
	 * the recovery lifecycle remains: sent → delivered → read → recovered.
	 *
	 * No-regression guarantees:
	 *   'delivered' only advances rows currently in 'sent'   (can't overwrite 'read')
	 *   'read'      advances rows in 'sent' OR 'delivered'   (handles out-of-order receipts)
	 *
	 * gdpr_consent = 1 guard ensures we never touch rows whose consent was revoked.
	 *
	 * @param string $phone       E.164 customer phone number.
	 * @param string $meta_status Raw Meta delivery status ('delivered' or 'read').
	 */
	public function markRecoveryDelivery( string $phone, string $meta_status ): void {
		if ( '' === $phone ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( 'delivered' === $meta_status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE `{$table}` SET status = 'delivered'
					WHERE customer_phone = %s AND status = 'sent' AND gdpr_consent = 1",
					$phone
				)
			);
		} elseif ( 'read' === $meta_status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE `{$table}` SET status = 'read'
					WHERE customer_phone = %s AND status IN ('sent', 'delivered') AND gdpr_consent = 1",
					$phone
				)
			);
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Aggregate KPI counts for the admin dashboard.
	 *
	 * Single query — O(1) regardless of table size.
	 *
	 * @return array{total: int, recovered: int}
	 */
	public function getStats(): array {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( "SELECT COUNT(*) AS total, SUM(status = 'recovered') AS recovered FROM `{$table}`" );

		return array(
			'total'     => isset( $row->total ) ? (int) $row->total : 0,
			'recovered' => isset( $row->recovered ) ? (int) $row->recovered : 0,
		);
	}

	/**
	 * Update the status of a recovery row.
	 *
	 * @param int    $id     Row primary key.
	 * @param string $status New status value (e.g. 'sent', 'recovered', 'expired').
	 */
	public function markStatus( int $id, string $status ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'cartpinger_recoveries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $id )
		);
	}
}
