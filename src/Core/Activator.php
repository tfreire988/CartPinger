<?php
/**
 * Plugin activation handler.
 *
 * @package CartPinger\Core
 */

declare(strict_types=1);

namespace CartPinger\Core;

use CartPinger\Database\MigrationRunner;

/**
 * Class Activator
 */
final class Activator {

	/**
	 * Runs on plugin activation.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( CARTPINGER_PLUGIN_BASENAME );
			wp_die( esc_html__( 'CartPinger requires PHP 8.2 or higher.', 'cartpinger' ) );
		}

		if ( version_compare( (string) get_bloginfo( 'version' ), '6.5', '<' ) ) {
			deactivate_plugins( CARTPINGER_PLUGIN_BASENAME );
			wp_die( esc_html__( 'CartPinger requires WordPress 6.5 or higher.', 'cartpinger' ) );
		}

		MigrationRunner::run();

		$defaults = array(
			'cartpinger_activated_at'         => current_time( 'mysql' ),
			'cartpinger_onboarding_completed' => false,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		flush_rewrite_rules();
	}
}
