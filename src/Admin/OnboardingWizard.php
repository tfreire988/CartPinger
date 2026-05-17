<?php
/**
 * Admin onboarding wizard — 5-step Meta verification flow.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

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
				'title'       => __( 'Welcome to CartPinger', 'cartpinger' ),
				'description' => __( 'Connect your WooCommerce store to WhatsApp in 5 simple steps. You will need a Meta Business Account and a phone number not already registered on WhatsApp.', 'cartpinger' ),
			),
			2 => array(
				'title'       => __( 'Meta Business Verification', 'cartpinger' ),
				'description' => __( 'Verify your business on Meta Business Manager. This unlocks higher messaging limits. Visit business.facebook.com/settings to start.', 'cartpinger' ),
			),
			3 => array(
				'title'       => __( 'Create WhatsApp Business Account', 'cartpinger' ),
				'description' => __( 'Create a WhatsApp Business Account (WABA) inside Meta Business Manager, then create a System User and generate a permanent access token.', 'cartpinger' ),
			),
			4 => array(
				'title'       => __( 'Register Phone Number', 'cartpinger' ),
				'description' => __( 'Add and register a phone number in your WABA. This number will be the sender for all WhatsApp messages from your store.', 'cartpinger' ),
			),
			5 => array(
				'title'       => __( 'First Template & Test Message', 'cartpinger' ),
				'description' => __( 'Submit your first message template for Meta approval. We provide pre-approved templates in 5 languages. Send a test message to verify the connection.', 'cartpinger' ),
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
		if ( ! isset( $_GET['cartpinger_complete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if (
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( (string) $_GET['_wpnonce'] ), 'cartpinger_complete_onboarding' )
		) {
			wp_die( esc_html__( 'Security check failed.', 'cartpinger' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'cartpinger' ) );
		}

		update_option( 'cartpinger_onboarding_completed', true, false );

		wp_safe_redirect( admin_url( 'admin.php?page=cartpinger' ) );
		exit;
	}

	/**
	 * Render the wizard page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cartpinger' ) );
		}

		$current_step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_step = max( 1, min( 5, $current_step ) );
		$steps        = self::steps();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CartPinger Setup Wizard', 'cartpinger' ); ?></h1>

			<nav class="cartpinger-wizard-steps">
				<?php foreach ( $steps as $number => $step ) : ?>
					<span class="cartpinger-wizard-step <?php echo $number === $current_step ? 'active' : ( $number < $current_step ? 'done' : '' ); ?>">
						<?php echo esc_html( $number . '. ' . $step['title'] ); ?>
					</span>
				<?php endforeach; ?>
			</nav>

			<div class="cartpinger-wizard-content card" style="max-width:680px;padding:24px;margin-top:24px;">
				<h2><?php echo esc_html( $steps[ $current_step ]['title'] ); ?></h2>
				<p><?php echo esc_html( $steps[ $current_step ]['description'] ); ?></p>

				<?php if ( 5 === $current_step ) : ?>
				<div id="cartpinger-credentials-form">
					<table class="form-table" role="presentation">
						<tr>
							<th><label for="cartpinger-phone-id"><?php esc_html_e( 'Phone Number ID', 'cartpinger' ); ?></label></th>
							<td><input type="text" id="cartpinger-phone-id" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="cartpinger-access-token"><?php esc_html_e( 'Access Token', 'cartpinger' ); ?></label></th>
							<td><input type="password" id="cartpinger-access-token" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="cartpinger-verify-token"><?php esc_html_e( 'Webhook Verify Token', 'cartpinger' ); ?></label></th>
							<td><input type="text" id="cartpinger-verify-token" class="regular-text" autocomplete="off" /></td>
						</tr>
						<tr>
							<th><label for="cartpinger-app-secret"><?php esc_html_e( 'App Secret', 'cartpinger' ); ?></label></th>
							<td><input type="password" id="cartpinger-app-secret" class="regular-text" autocomplete="off" /></td>
						</tr>
					</table>

					<p>
						<button type="button" id="cartpinger-save-settings" class="button button-primary">
							<?php esc_html_e( 'Save Credentials', 'cartpinger' ); ?>
						</button>
						<span id="cartpinger-save-status" style="margin-left:8px;"></span>
					</p>

					<hr />

					<h3><?php esc_html_e( 'Test Connection', 'cartpinger' ); ?></h3>
					<p><?php esc_html_e( 'Enter a phone number (E.164 format, e.g. +34612345678) to receive a test message.', 'cartpinger' ); ?></p>
					<p>
						<input type="text" id="cartpinger-test-phone" class="regular-text" placeholder="+34612345678" />
						<button type="button" id="cartpinger-send-test" class="button">
							<?php esc_html_e( 'Send Test Message', 'cartpinger' ); ?>
						</button>
						<span id="cartpinger-test-status" style="margin-left:8px;"></span>
					</p>

					<p>
						<em class="description">
							<?php
							printf(
								/* translators: %s: webhook URL */
								esc_html__( 'Your webhook URL: %s', 'cartpinger' ),
								'<code>' . esc_html( rest_url( 'cartpinger/v1/webhook' ) ) . '</code>'
							);
							?>
						</em>
					</p>
				</div>
				<?php endif; ?>

				<p>
					<?php if ( $current_step > 1 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-setup&step=' . ( $current_step - 1 ) ) ); ?>" class="button">
							&larr; <?php esc_html_e( 'Previous', 'cartpinger' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $current_step < 5 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-setup&step=' . ( $current_step + 1 ) ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Next', 'cartpinger' ); ?> &rarr;
						</a>
					<?php else : ?>
						<?php
						$nonce = wp_create_nonce( 'cartpinger_complete_onboarding' );
						$url   = admin_url( 'admin.php?page=cartpinger&cartpinger_complete=1&_wpnonce=' . $nonce );
						?>
						<a href="<?php echo esc_url( $url ); ?>" class="button button-primary">
							<?php esc_html_e( 'Finish Setup', 'cartpinger' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		</div>
		<?php
	}
}
