<?php
/**
 * Admin onboarding wizard — 5-step Meta verification flow.
 *
 * @package WhatsCom\Admin
 */

declare(strict_types=1);

namespace WhatsCom\Admin;

/**
 * Class OnboardingWizard
 */
final class OnboardingWizard {

	/**
	 * Wizard steps definition.
	 *
	 * @return array<int, array{title: string, description: string}>
	 */
	private static function steps(): array {
		return array(
			1 => array(
				'title'       => __( 'Welcome to WhatsCom', 'whatscom' ),
				'description' => __( 'Connect your WooCommerce store to WhatsApp in 5 simple steps. You will need a Meta Business Account and a phone number not already registered on WhatsApp.', 'whatscom' ),
			),
			2 => array(
				'title'       => __( 'Meta Business Verification', 'whatscom' ),
				'description' => __( 'Verify your business on Meta Business Manager. This unlocks higher messaging limits. Visit business.facebook.com/settings to start.', 'whatscom' ),
			),
			3 => array(
				'title'       => __( 'Create WhatsApp Business Account', 'whatscom' ),
				'description' => __( 'Create a WhatsApp Business Account (WABA) inside Meta Business Manager, then create a System User and generate a permanent access token.', 'whatscom' ),
			),
			4 => array(
				'title'       => __( 'Register Phone Number', 'whatscom' ),
				'description' => __( 'Add and register a phone number in your WABA. This number will be the sender for all WhatsApp messages from your store.', 'whatscom' ),
			),
			5 => array(
				'title'       => __( 'First Template & Test Message', 'whatscom' ),
				'description' => __( 'Submit your first message template for Meta approval. We provide pre-approved templates in 5 languages. Send a test message to verify the connection.', 'whatscom' ),
			),
		);
	}

	/**
	 * Handle the "Finish Setup" completion request (hooked on admin_init).
	 *
	 * Verifies the nonce, marks onboarding as complete, then redirects to
	 * the main dashboard page. No-ops when the parameter is absent.
	 */
	public static function handleComplete(): void {
		if ( ! isset( $_GET['whatscom_complete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( (string) $_GET['_wpnonce'] ), 'whatscom_complete_onboarding' )
		) {
			wp_die( esc_html__( 'Security check failed.', 'whatscom' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'whatscom' ) );
		}

		update_option( 'whatscom_onboarding_completed', true, false );

		wp_safe_redirect( admin_url( 'admin.php?page=whatscom' ) );
		exit;
	}

	/**
	 * Render the wizard page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'whatscom' ) );
		}

		$current_step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_step = max( 1, min( 5, $current_step ) );
		$steps        = self::steps();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WhatsCom Setup Wizard', 'whatscom' ); ?></h1>

			<nav class="whatscom-wizard-steps">
				<?php foreach ( $steps as $number => $step ) : ?>
					<span class="whatscom-wizard-step <?php echo $number === $current_step ? 'active' : ( $number < $current_step ? 'done' : '' ); ?>">
						<?php echo esc_html( $number . '. ' . $step['title'] ); ?>
					</span>
				<?php endforeach; ?>
			</nav>

			<div class="whatscom-wizard-content card" style="max-width:680px;padding:24px;margin-top:24px;">
				<h2><?php echo esc_html( $steps[ $current_step ]['title'] ); ?></h2>
				<p><?php echo esc_html( $steps[ $current_step ]['description'] ); ?></p>

				<?php if ( 5 === $current_step ) : ?>
				<div id="whatscom-credentials-form">
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="whatscom-phone-id"><?php esc_html_e( 'Phone Number ID', 'whatscom' ); ?></label></th>
							<td><input type="text" id="whatscom-phone-id" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="whatscom-access-token"><?php esc_html_e( 'Access Token', 'whatscom' ); ?></label></th>
							<td><input type="password" id="whatscom-access-token" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="whatscom-verify-token"><?php esc_html_e( 'Webhook Verify Token', 'whatscom' ); ?></label></th>
							<td><input type="text" id="whatscom-verify-token" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="whatscom-app-secret"><?php esc_html_e( 'App Secret', 'whatscom' ); ?></label></th>
							<td><input type="password" id="whatscom-app-secret" class="regular-text" autocomplete="off" /></td>
						</tr>
					</table>

					<p>
						<button type="button" id="whatscom-save-settings" class="button button-primary">
							<?php esc_html_e( 'Save Credentials', 'whatscom' ); ?>
						</button>
						<span id="whatscom-save-status" style="margin-left:8px;"></span>
					</p>

					<hr />

					<h3><?php esc_html_e( 'Test Connection', 'whatscom' ); ?></h3>
					<p><?php esc_html_e( 'Enter a phone number (E.164 format, e.g. +34612345678) to receive a test message.', 'whatscom' ); ?></p>
					<p>
						<input type="text" id="whatscom-test-phone" class="regular-text" placeholder="+34612345678" />
						<button type="button" id="whatscom-send-test" class="button">
							<?php esc_html_e( 'Send Test Message', 'whatscom' ); ?>
						</button>
						<span id="whatscom-test-status" style="margin-left:8px;"></span>
					</p>

					<p>
						<em class="description">
							<?php
							printf(
								/* translators: %s: webhook URL */
								esc_html__( 'Your webhook URL: %s', 'whatscom' ),
								'<code>' . esc_html( rest_url( 'whatscom/v1/webhook' ) ) . '</code>'
							);
							?>
						</em>
					</p>
				</div>
				<?php endif; ?>

				<p>
					<?php if ( $current_step > 1 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=whatscom-setup&step=' . ( $current_step - 1 ) ) ); ?>" class="button">
							&larr; <?php esc_html_e( 'Previous', 'whatscom' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $current_step < 5 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=whatscom-setup&step=' . ( $current_step + 1 ) ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Next', 'whatscom' ); ?> &rarr;
						</a>
					<?php else : ?>
						<?php
						$nonce = wp_create_nonce( 'whatscom_complete_onboarding' );
						$url   = admin_url( 'admin.php?page=whatscom&whatscom_complete=1&_wpnonce=' . $nonce );
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="button button-primary">
							<?php esc_html_e( 'Finish Setup', 'whatscom' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
	}
}
