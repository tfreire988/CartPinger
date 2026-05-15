<?php
/**
 * Main plugin bootstrap singleton.
 *
 * @package WhatsCom\Core
 */

declare(strict_types=1);

namespace WhatsCom\Core;

/**
 * Class Plugin
 */
final class Plugin {

	private static ?self $instance = null;

	private bool $booted = false;

	/**
	 * Get singleton instance.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {}

	/**
	 * Boot the plugin. Called on plugins_loaded.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		\WhatsCom\i18n\Loader::load();

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'noticeMissingWoo' ) );
			return;
		}

		if ( is_admin() ) {
			\WhatsCom\Admin\AdminBootstrap::register();
		}

		\WhatsCom\WooCommerce\WCBootstrap::register();

		\WhatsCom\REST\RestBootstrap::register();
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 */
	public function noticeMissingWoo(): void {
		$message = esc_html__( 'WhatsCom requires WooCommerce. Please install and activate WooCommerce 9.0+.', 'whatscom' );
		echo '<div class="notice notice-error"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
