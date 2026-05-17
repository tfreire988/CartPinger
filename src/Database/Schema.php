<?php
/**
 * Database schema creation and updates.
 *
 * @package CartPinger\Database
 */

declare(strict_types=1);

namespace CartPinger\Database;

/**
 * Class Schema
 */
final class Schema {

	/**
	 * Create or upgrade all plugin database tables.
	 */
	public static function create(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$table_settings = $wpdb->prefix . 'cartpinger_settings';
		$sql_settings   = "CREATE TABLE {$table_settings} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			option_key VARCHAR(191) NOT NULL,
			option_value LONGTEXT,
			is_encrypted TINYINT(1) NOT NULL DEFAULT 0,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY option_key (option_key)
		) {$charset_collate};";
		dbDelta( $sql_settings );

		$table_messages = $wpdb->prefix . 'cartpinger_messages_log';
		$sql_messages   = "CREATE TABLE {$table_messages} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			meta_message_id VARCHAR(191) DEFAULT NULL,
			recipient_phone VARCHAR(20) NOT NULL,
			template_name VARCHAR(191) DEFAULT NULL,
			language_code VARCHAR(10) NOT NULL DEFAULT 'en_US',
			components LONGTEXT DEFAULT NULL,
			category VARCHAR(20) DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			error_message TEXT,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			sent_at DATETIME DEFAULT NULL,
			delivered_at DATETIME DEFAULT NULL,
			read_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY meta_message_id (meta_message_id),
			KEY recipient_phone (recipient_phone),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql_messages );

		$table_carts = $wpdb->prefix . 'cartpinger_abandoned_carts';
		$sql_carts   = "CREATE TABLE {$table_carts} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			cart_token VARCHAR(64) NOT NULL,
			user_id BIGINT UNSIGNED DEFAULT NULL,
			customer_email VARCHAR(191) DEFAULT NULL,
			customer_phone VARCHAR(20) DEFAULT NULL,
			cart_data LONGTEXT,
			cart_total DECIMAL(10,2) DEFAULT NULL,
			currency VARCHAR(3) DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			recovered_order_id BIGINT UNSIGNED DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			recovered_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY cart_token (cart_token),
			KEY user_id (user_id),
			KEY status (status),
			KEY customer_phone (customer_phone)
		) {$charset_collate};";
		dbDelta( $sql_carts );

		update_option( 'cartpinger_db_version', '0.1.0' );
	}
}
