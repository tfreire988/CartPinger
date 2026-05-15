<?php
/**
 * Text domain loader.
 *
 * @package WhatsCom\i18n
 */

declare(strict_types=1);

namespace WhatsCom\i18n;

/**
 * Class Loader
 */
final class Loader {

	/**
	 * Load the plugin text domain.
	 */
	public static function load(): void {
		load_plugin_textdomain(
			'whatscom',
			false,
			dirname( WHATSCOM_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
