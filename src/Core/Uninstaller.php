<?php
/**
 * Plugin uninstall handler. Called from uninstall.php.
 *
 * Data is only deleted when the user has explicitly opted in by setting
 * the whatscom_delete_data_on_uninstall option to true in plugin settings.
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
	 * All wp_options keys owned by this plugin.
	 *
	 * @var array<int, string>
	 */
	private const OPTION_KEYS = array(
		// Credentials (plain-text).
		'whatscom_phone_number_id',
		'whatscom_waba_id',
		'whatscom_webhook_verify_token',
		// Credentials (AES-256-GCM encrypted blobs).
		'whatscom_access_token',
		'whatscom_app_secret',
		// Plugin state.
		'whatscom_version',
		'whatscom_db_version',
		'whatscom_activated_at',
		'whatscom_onboarding_completed',
		'whatscom_delete_data_on_uninstall',
	);

	/**
	 * Remove all plugin data if the user opted in.
	 *
	 * Checks the whatscom_delete_data_on_uninstall boolean option.
	 * When false (the default) this method is a no-op, preserving all data
	 * so the user can re-activate without losing configuration.
	 */
	public static function uninstall(): void {
		if ( ! get_option( 'whatscom_delete_data_on_uninstall', false ) ) {
			return;
		}

		self::deleteTables();
		self::deleteOptions();
		self::deleteTransients();
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
		foreach ( self::OPTION_KEYS as $key ) {
			delete_option( $key );
		}
	}

	/**
	 * Delete all plugin transients.
	 */
	private static function deleteTransients(): void {
		delete_transient( 'whatscom_templates_cache' );
	}
}
