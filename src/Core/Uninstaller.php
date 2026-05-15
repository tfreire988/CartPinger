<?php
/**
 * Plugin uninstall handler. Called from uninstall.php.
 *
 * @package WhatsCom\Core
 */

declare(strict_types=1);

namespace WhatsCom\Core;

/**
 * Class Uninstaller
 */
final class Uninstaller {

	/**
	 * Remove all plugin data if the user opted in via settings.
	 */
	public static function uninstall(): void {
		$settings = get_option( 'whatscom_settings', array() );

		if ( ! is_array( $settings ) || empty( $settings['delete_data_on_uninstall'] ) ) {
			return;
		}

		self::deleteTables();
		self::deleteOptions();
	}

	/**
	 * Drop all custom database tables.
	 */
	private static function deleteTables(): void {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'whatscom_settings',
			$wpdb->prefix . 'whatscom_messages_log',
			$wpdb->prefix . 'whatscom_abandoned_carts',
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		}
	}

	/**
	 * Delete all wp_options entries added by the plugin.
	 */
	private static function deleteOptions(): void {
		$option_keys = array(
			'whatscom_version',
			'whatscom_activated_at',
			'whatscom_settings',
			'whatscom_db_version',
			'whatscom_onboarding_completed',
		);

		foreach ( $option_keys as $key ) {
			delete_option( $key );
		}
	}
}
