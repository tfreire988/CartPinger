<?php
/**
 * Plugin uninstall handler. Called from uninstall.php.
 *
 * Data is only deleted when the user has explicitly opted in by setting
 * the cartpinger_delete_data_on_uninstall option to true in plugin settings.
 *
 * @package CartPinger\Core
 */

declare(strict_types=1);

namespace CartPinger\Core;

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
		'cartpinger_phone_number_id',
		'cartpinger_waba_id',
		'cartpinger_webhook_verify_token',
		// Credentials (AES-256-GCM encrypted blobs).
		'cartpinger_access_token',
		'cartpinger_app_secret',
		// Plugin state.
		'cartpinger_version',
		'cartpinger_db_version',
		'cartpinger_activated_at',
		'cartpinger_onboarding_completed',
		'cartpinger_delete_data_on_uninstall',
		// Chat widget.
		'cartpinger_widget_enabled',
		'cartpinger_support_phone',
		'cartpinger_widget_message',
		// Pro license.
		'cartpinger_license_key',
		'cartpinger_license_status',
	);

	/**
	 * Remove all plugin data if the user opted in.
	 *
	 * Checks the cartpinger_delete_data_on_uninstall boolean option.
	 * When false (the default) this method is a no-op, preserving all data
	 * so the user can re-activate without losing configuration.
	 */
	public static function uninstall(): void {
		if ( ! get_option( 'cartpinger_delete_data_on_uninstall', false ) ) {
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
			$wpdb->prefix . 'cartpinger_settings',
			$wpdb->prefix . 'cartpinger_messages_log',
			$wpdb->prefix . 'cartpinger_recoveries',
			$wpdb->prefix . 'cartpinger_abandoned_carts',
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
		delete_transient( 'cartpinger_templates_cache' );
	}
}
