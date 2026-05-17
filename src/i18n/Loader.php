<?php
/**
 * Text domain loader.
 *
 * Loads the plugin translation files from the /languages directory.
 * WordPress.org automatically delivers community translations for hosted
 * plugins since WP 4.6, but calling load_plugin_textdomain() is still
 * required for local overrides placed in wp-content/languages/plugins/.
 *
 * @package CartPinger\i18n
 */

declare(strict_types=1);

namespace CartPinger\i18n;

/**
 * Class Loader
 */
final class Loader {

	/**
	 * Load the plugin text domain.
	 *
	 * Must be called on the plugins_loaded hook (already done in Plugin::boot()).
	 */
	public static function load(): void {
		load_plugin_textdomain(
			'cartpinger',
			false,
			dirname( CARTPINGER_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
