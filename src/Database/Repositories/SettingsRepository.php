<?php
/**
 * Repository for custom settings table.
 *
 * @package CartPinger\Database\Repositories
 */

declare(strict_types=1);

namespace CartPinger\Database\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsRepository
 */
final class SettingsRepository {

	private const CACHE_GROUP = 'cartpinger';

	/**
	 * Build the object-cache key for a given setting key.
	 *
	 * @param string $key Option key.
	 */
	private function cacheKey( string $key ): string {
		return 'setting_' . md5( $key );
	}

	/**
	 * Retrieve a setting value by key.
	 *
	 * @param string $key Option key.
	 * @return string|null
	 */
	public function get( string $key ): ?string {
		$cache_key = $this->cacheKey( $key );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return is_string( $cached ) ? $cached : null;
		}

		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_settings' );

		$value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT option_value FROM `{$table}` WHERE option_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$key
			)
		);

		if ( is_string( $value ) ) {
			wp_cache_set( $cache_key, $value, self::CACHE_GROUP );
		}

		return is_string( $value ) ? $value : null;
	}

	/**
	 * Insert or update a setting value.
	 *
	 * @param string $key          Option key.
	 * @param string $value        Option value.
	 * @param bool   $is_encrypted Whether the value is encrypted.
	 */
	public function set( string $key, string $value, bool $is_encrypted = false ): void {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_settings' );

		$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'option_key'   => $key,
				'option_value' => $value,
				'is_encrypted' => $is_encrypted ? 1 : 0,
			),
			array( '%s', '%s', '%d' )
		);

		wp_cache_delete( $this->cacheKey( $key ), self::CACHE_GROUP );
	}

	/**
	 * Delete a setting by key.
	 *
	 * @param string $key Option key.
	 */
	public function delete( string $key ): void {
		global $wpdb;

		$table = esc_sql( $wpdb->prefix . 'cartpinger_settings' );

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'option_key' => $key ),
			array( '%s' )
		);

		wp_cache_delete( $this->cacheKey( $key ), self::CACHE_GROUP );
	}
}
