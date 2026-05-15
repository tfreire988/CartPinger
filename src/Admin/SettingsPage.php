<?php
/**
 * Admin settings page.
 *
 * @package WhatsCom\Admin
 */

declare(strict_types=1);

namespace WhatsCom\Admin;

/**
 * Class SettingsPage
 */
final class SettingsPage {

	/**
	 * Render the settings page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'whatscom' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WhatsCom Settings', 'whatscom' ); ?></h1>

			<div class="notice notice-info">
				<p><?php esc_html_e( 'Settings UI coming in v1.0. Configuration will be available here once the Meta connection is established.', 'whatscom' ); ?></p>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Plugin Version', 'whatscom' ); ?></th>
					<td><code><?php echo esc_html( WHATSCOM_VERSION ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Setup Status', 'whatscom' ); ?></th>
					<td>
						<?php if ( get_option( 'whatscom_onboarding_completed' ) ) : ?>
							<span style="color:green;">&#10003; <?php esc_html_e( 'Complete', 'whatscom' ); ?></span>
						<?php else : ?>
							<span style="color:orange;">&#9888; <?php esc_html_e( 'Incomplete', 'whatscom' ); ?></span>
							&mdash;
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=whatscom-setup' ) ); ?>">
								<?php esc_html_e( 'Run setup wizard', 'whatscom' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
