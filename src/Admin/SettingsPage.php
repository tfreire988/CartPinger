<?php
/**
 * Admin settings page.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

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
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CartPinger Settings', 'cartpinger' ); ?></h1>

			<div class="notice notice-info">
				<p><?php esc_html_e( 'Settings UI coming in v1.0. Configuration will be available here once the Meta connection is established.', 'cartpinger' ); ?></p>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Plugin Version', 'cartpinger' ); ?></th>
					<td><code><?php echo esc_html( CARTPINGER_VERSION ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Setup Status', 'cartpinger' ); ?></th>
					<td>
						<?php if ( get_option( 'cartpinger_onboarding_completed' ) ) : ?>
							<span style="color:green;">&#10003; <?php esc_html_e( 'Complete', 'cartpinger' ); ?></span>
						<?php else : ?>
							<span style="color:orange;">&#9888; <?php esc_html_e( 'Incomplete', 'cartpinger' ); ?></span>
							&mdash;
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-setup' ) ); ?>">
								<?php esc_html_e( 'Run setup wizard', 'cartpinger' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
}
