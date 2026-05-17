<?php
/**
 * Admin dashboard page — KPI statistics view.
 *
 * Renders a bare mount point. The React StatsView component populates it by
 * calling GET /cartpinger/v1/stats via @wordpress/api-fetch.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

/**
 * Class DashboardPage
 */
final class DashboardPage {

	/**
	 * Render the dashboard page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cartpinger' ) );
		}

		// Inject REST URL and nonce so the JS bundle can call the API without
		// relying on wp-api-request (keeps the bundle dependency-free).
		wp_localize_script(
			'cartpinger-admin',
			'cartpingerAdmin',
			array(
				'apiUrl' => esc_url_raw( rest_url( 'cartpinger/v1/' ) ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
				'view'   => 'dashboard',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CartPinger', 'cartpinger' ); ?></h1>
			<div id="cartpinger-dashboard-app"></div>
		</div>
		<?php
	}
}
