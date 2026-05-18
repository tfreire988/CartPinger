<?php
/**
 * Admin settings page — React-powered settings form.
 *
 * Renders a bare mount point. The React SettingsView component populates it by
 * calling GET/POST /cartpinger/v1/settings via @wordpress/api-fetch.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsPage
 */
final class SettingsPage {

	/**
	 * Render the settings page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cartpinger' ) );
		}

		wp_localize_script(
			'cartpinger-admin',
			'cartpingerAdmin',
			array(
				'apiUrl' => esc_url_raw( rest_url( 'cartpinger/v1/' ) ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'view'   => 'settings',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CartPinger Settings', 'cartpinger' ); ?></h1>
			<div id="cartpinger-settings-app"></div>
		</div>
		<?php
	}
}
