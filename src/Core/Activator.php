<?php
/**
 * Plugin activation handler.
 *
 * @package WhatsCom\Core
 */

declare(strict_types=1);

namespace WhatsCom\Core;

use WhatsCom\Database\Schema;

/**
 * Class Activator
 */
final class Activator {

	/**
	 * Runs on plugin activation.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( WHATSCOM_PLUGIN_BASENAME );
			wp_die( esc_html__( 'WhatsCom requires PHP 8.2 or higher.', 'whatscom' ) );
		}

		if ( version_compare( (string) get_bloginfo( 'version' ), '6.5', '<' ) ) {
			deactivate_plugins( WHATSCOM_PLUGIN_BASENAME );
			wp_die( esc_html__( 'WhatsCom requires WordPress 6.5 or higher.', 'whatscom' ) );
		}

		Schema::create();

		$defaults = array(
			'whatscom_version'      => WHATSCOM_VERSION,
			'whatscom_activated_at' => current_time( 'mysql' ),
			'whatscom_settings'     => array(
				'business_name'    => '',
				'business_phone'   => '',
				'enabled_features' => array(),
			),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		flush_rewrite_rules();
	}
}
