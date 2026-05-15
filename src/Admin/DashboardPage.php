<?php
/**
 * Admin dashboard page.
 *
 * @package WhatsCom\Admin
 */

declare(strict_types=1);

namespace WhatsCom\Admin;

/**
 * Class DashboardPage
 */
final class DashboardPage {

	/**
	 * Render the dashboard page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'whatscom' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WhatsCom Dashboard', 'whatscom' ); ?></h1>

			<div class="notice notice-info">
				<p>
					<?php
					printf(
						/* translators: %s: version number */
						esc_html__( 'WhatsCom %s — skeleton release. Full features arrive in v1.0 (Q3 2026).', 'whatscom' ),
						esc_html( WHATSCOM_VERSION )
					);
					?>
				</p>
			</div>

			<div id="whatscom-dashboard">
				<?php
				if ( ! get_option( 'whatscom_onboarding_completed' ) ) {
					$setup_url = admin_url( 'admin.php?page=whatscom-setup' );
					printf(
						'<p><a href="%s" class="button button-primary">%s</a></p>',
						esc_url( $setup_url ),
						esc_html__( 'Complete Setup Wizard', 'whatscom' )
					);
				}
				?>
				<p class="description">
					<?php esc_html_e( 'Stats and quick actions will appear here once you complete the setup.', 'whatscom' ); ?>
				</p>
			</div>
		</div>
		<?php
	}
}
