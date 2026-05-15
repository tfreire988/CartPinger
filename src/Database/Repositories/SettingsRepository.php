<?php
/**
 * Repository for custom settings table.
 *
 * @package WhatsCom\Database\Repositories
 */

declare(strict_types=1);

namespace WhatsCom\Database\Repositories;

/**
 * Class SettingsRepository
 */
final class SettingsRepository {

	/**
	 * Retrieve a setting value by key.
	 *
	 * @param string $key Option key.
	 * @return string|null
	 */
	public function get( string $key ): ?string {
		global $wpdb;

		$table = $wpdb->prefix . 'whatscom_settings';

		$value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT option_value FROM `{$table}` WHERE option_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$key
			)
		);

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

		$table = $wpdb->prefix . 'whatscom_settings';

		$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array(
				'option_key'   => $key,
				'option_value' => $value,
				'is_encrypted' => $is_encrypted ? 1 : 0,
			),
			array( '%s', '%s', '%d' )
		);
	}

	/**
	 * Delete a setting by key.
	 *
	 * @param string $key Option key.
	 */
	public function delete( string $key ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'whatscom_settings';

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table,
			array( 'option_key' => $key ),
			array( '%s' )
		);
	}
}
