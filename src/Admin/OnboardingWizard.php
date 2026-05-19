<?php
/**
 * Admin onboarding wizard — 5-step Meta verification flow.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OnboardingWizard
 */
final class OnboardingWizard {

	/**
	 * Wizard steps definition.
	 *
	 * @return array<int, array{title: string, short: string}>
	 */
	private static function steps(): array {
		return array(
			1 => array(
				'title' => __( 'Welcome', 'cartpinger' ),
				'short' => __( 'Intro', 'cartpinger' ),
			),
			2 => array(
				'title' => __( 'Meta Business', 'cartpinger' ),
				'short' => __( 'Business', 'cartpinger' ),
			),
			3 => array(
				'title' => __( 'WhatsApp Account', 'cartpinger' ),
				'short' => __( 'WABA', 'cartpinger' ),
			),
			4 => array(
				'title' => __( 'Phone Number', 'cartpinger' ),
				'short' => __( 'Phone', 'cartpinger' ),
			),
			5 => array(
				'title' => __( 'Credentials', 'cartpinger' ),
				'short' => __( 'Credentials', 'cartpinger' ),
			),
		);
	}

	/**
	 * Handle the "Finish Setup" completion request (hooked on admin_init).
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
		<div class="cp-wrap">
			<h1><?php esc_html_e( 'CartPinger Setup', 'cartpinger' ); ?></h1>
			<p class="cp-subtitle"><?php esc_html_e( 'Five quick steps to connect your store to WhatsApp.', 'cartpinger' ); ?></p>

			<nav class="cp-wizard-steps">
				<?php foreach ( $steps as $number => $step ) : ?>
					<div class="cp-wizard-step <?php echo $number === $current_step ? 'active' : ( $number < $current_step ? 'done' : '' ); ?>">
						<span class="cp-step-num"><?php echo (int) $number; ?></span>
						<span><?php echo esc_html( $step['short'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</nav>

			<div class="cp-step-content">
				<?php self::renderStep( $current_step ); ?>

				<div style="margin-top:32px;display:flex;justify-content:space-between;align-items:center;">
					<?php if ( $current_step > 1 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-setup&step=' . ( $current_step - 1 ) ) ); ?>" class="cp-btn cp-btn-secondary">
							&larr; <?php esc_html_e( 'Previous', 'cartpinger' ); ?>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>

					<?php
					$nonce = wp_create_nonce( 'cartpinger_complete_onboarding' );
					$url   = admin_url( 'admin.php?page=cartpinger&cartpinger_complete=1&_wpnonce=' . $nonce );
					?>
					<?php if ( $current_step < 5 ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-setup&step=' . ( $current_step + 1 ) ) ); ?>" class="cp-btn cp-btn-primary">
							<?php esc_html_e( 'Next', 'cartpinger' ); ?> &rarr;
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $url ); ?>" class="cp-btn cp-btn-primary">
							<?php esc_html_e( 'Finish Setup', 'cartpinger' ); ?> ✓
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the step-specific body.
	 *
	 * @param int $step Current step number (1-5).
	 */
	private static function renderStep( int $step ): void {
		switch ( $step ) {
			case 1:
				?>
				<h2><?php esc_html_e( 'Welcome to CartPinger', 'cartpinger' ); ?> 👋</h2>
				<p class="cp-step-intro">
					<?php esc_html_e( 'CartPinger connects your WooCommerce store to WhatsApp so you can recover abandoned carts, notify customers about their orders, and chat with them — all from their favorite messaging app.', 'cartpinger' ); ?>
				</p>

				<h3 style="margin-top:24px;"><?php esc_html_e( 'Before you begin, you will need:', 'cartpinger' ); ?></h3>
				<ul style="line-height:1.8;">
					<li>✅ <?php esc_html_e( 'A Facebook account', 'cartpinger' ); ?></li>
					<li>✅ <?php esc_html_e( 'A phone number not currently registered on WhatsApp (or one you can move from the WhatsApp app)', 'cartpinger' ); ?></li>
					<li>✅ <?php esc_html_e( 'About 15 minutes for the setup', 'cartpinger' ); ?></li>
				</ul>

				<div class="cp-info-box success">
					<p><strong>💡 <?php esc_html_e( 'Free tier:', 'cartpinger' ); ?></strong> <?php esc_html_e( 'Meta gives you 1,000 free service conversations per month. CartPinger Free plan includes 50 cart recoveries per month.', 'cartpinger' ); ?></p>
				</div>
				<?php
				break;

			case 2:
				?>
				<h2><?php esc_html_e( 'Step 2 — Meta Business Account', 'cartpinger' ); ?></h2>
				<p class="cp-step-intro">
					<?php esc_html_e( 'You need a Meta Business Manager account to host your WhatsApp Business Account.', 'cartpinger' ); ?>
				</p>

				<ol style="line-height:1.8;">
					<li><?php esc_html_e( 'Go to Meta Business Manager.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Create a business portfolio (or use an existing one).', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Verify your business if Meta asks (only required for higher messaging limits — not needed to get started).', 'cartpinger' ); ?></li>
				</ol>

				<p>
					<a href="https://business.facebook.com" target="_blank" rel="noopener" class="cp-btn cp-btn-primary">
						<?php esc_html_e( 'Open Meta Business Manager →', 'cartpinger' ); ?>
					</a>
				</p>
				<?php
				break;

			case 3:
				?>
				<h2><?php esc_html_e( 'Step 3 — WhatsApp Business Account (WABA)', 'cartpinger' ); ?></h2>
				<p class="cp-step-intro">
					<?php esc_html_e( 'Inside Meta for Developers, create an app and add the WhatsApp product. Meta will give you a sandbox phone number for testing.', 'cartpinger' ); ?>
				</p>

				<ol style="line-height:1.8;">
					<li><?php esc_html_e( 'Go to developers.facebook.com → My Apps → Create App.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Choose "Business" as app type.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Once created, in the Use cases section add "Connect via WhatsApp".', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Meta will create a test WhatsApp number and give you a temporary access token (24h).', 'cartpinger' ); ?></li>
				</ol>

				<p>
					<a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener" class="cp-btn cp-btn-primary">
						<?php esc_html_e( 'Open Meta for Developers →', 'cartpinger' ); ?>
					</a>
				</p>
				<?php
				break;

			case 4:
				?>
				<h2><?php esc_html_e( 'Step 4 — Get your IDs and Access Token', 'cartpinger' ); ?></h2>
				<p class="cp-step-intro">
					<?php esc_html_e( 'In your Meta app, open WhatsApp → API Setup. You will need the following values:', 'cartpinger' ); ?>
				</p>

				<ul style="line-height:1.9;">
					<li><strong><?php esc_html_e( 'Phone Number ID', 'cartpinger' ); ?></strong> — <?php esc_html_e( 'numeric ID of the test or production phone number', 'cartpinger' ); ?></li>
					<li><strong><?php esc_html_e( 'WhatsApp Business Account ID (WABA)', 'cartpinger' ); ?></strong> — <?php esc_html_e( 'numeric ID of the WABA', 'cartpinger' ); ?></li>
					<li><strong><?php esc_html_e( 'Access Token', 'cartpinger' ); ?></strong> — <?php esc_html_e( 'click "Generate access token" (24h temporary) or create a permanent System User token for production', 'cartpinger' ); ?></li>
					<li><strong><?php esc_html_e( 'App Secret', 'cartpinger' ); ?></strong> — <?php esc_html_e( 'found under App Settings → Basic → App secret', 'cartpinger' ); ?></li>
				</ul>

				<div class="cp-info-box warning">
					<p><strong>⚠️ <?php esc_html_e( 'Add yourself as test recipient:', 'cartpinger' ); ?></strong> <?php esc_html_e( 'In API Setup, add your personal WhatsApp number under "To" so Meta sends you the test message verification. Otherwise no messages will be delivered while you are in sandbox.', 'cartpinger' ); ?></p>
				</div>
				<?php
				break;

			case 5:
				?>
				<h2><?php esc_html_e( 'Step 5 — Enter credentials & test', 'cartpinger' ); ?></h2>
				<p class="cp-step-intro">
					<?php esc_html_e( 'Paste your Meta credentials below, save, and send a test message. After that, you will need to create the message templates in Meta — go to CartPinger → Templates for the exact content.', 'cartpinger' ); ?>
				</p>

				<div id="cartpinger-credentials-form" class="cp-card" style="margin-top:24px;">
					<div class="cp-field">
						<label for="cartpinger-phone-id"><?php esc_html_e( 'Phone Number ID', 'cartpinger' ); ?></label>
						<input type="text" id="cartpinger-phone-id" autocomplete="off" />
					</div>
					<div class="cp-field">
						<label for="cartpinger-waba-id"><?php esc_html_e( 'WhatsApp Business Account ID', 'cartpinger' ); ?></label>
						<input type="text" id="cartpinger-waba-id" autocomplete="off" />
					</div>
					<div class="cp-field">
						<label for="cartpinger-access-token"><?php esc_html_e( 'Access Token', 'cartpinger' ); ?></label>
						<input type="password" id="cartpinger-access-token" autocomplete="off" />
					</div>
					<div class="cp-field">
						<label for="cartpinger-app-secret"><?php esc_html_e( 'App Secret', 'cartpinger' ); ?></label>
						<input type="password" id="cartpinger-app-secret" autocomplete="off" />
					</div>
					<div class="cp-field">
						<label for="cartpinger-verify-token"><?php esc_html_e( 'Webhook Verify Token', 'cartpinger' ); ?></label>
						<input type="text" id="cartpinger-verify-token" autocomplete="off" />
						<div class="cp-help"><?php esc_html_e( 'Any random string you choose — you will paste it into Meta when configuring the webhook.', 'cartpinger' ); ?></div>
					</div>

					<p style="margin-top:16px;">
						<button type="button" id="cartpinger-save-settings" class="cp-btn cp-btn-primary">
							<?php esc_html_e( 'Save Credentials', 'cartpinger' ); ?>
						</button>
						<span id="cartpinger-save-status" class="cp-status-msg"></span>
					</p>
				</div>

				<div class="cp-card">
					<h3><?php esc_html_e( 'Test Connection', 'cartpinger' ); ?></h3>
					<p style="color:#646970;font-size:13px;"><?php esc_html_e( 'Enter a phone number in E.164 format (e.g. +34612345678) to receive a test message. The number must be verified as a test recipient in Meta.', 'cartpinger' ); ?></p>
					<div class="cp-field">
						<input type="text" id="cartpinger-test-phone" placeholder="+34612345678" />
					</div>
					<p>
						<button type="button" id="cartpinger-send-test" class="cp-btn cp-btn-secondary">
							<?php esc_html_e( 'Send Test Message', 'cartpinger' ); ?>
						</button>
						<span id="cartpinger-test-status" class="cp-status-msg"></span>
					</p>
				</div>

				<div class="cp-info-box">
					<p><strong>📋 <?php esc_html_e( 'Next step:', 'cartpinger' ); ?></strong> <?php esc_html_e( 'Create the WhatsApp message templates in Meta — they are required for cart recovery and order notifications.', 'cartpinger' ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=cartpinger-templates' ) ); ?>" class="cp-btn cp-btn-primary"><?php esc_html_e( 'Go to Templates →', 'cartpinger' ); ?></a></p>
				</div>

				<p style="margin-top:16px;color:#646970;font-size:13px;">
					<?php
					printf(
						/* translators: %s: webhook URL */
						esc_html__( 'Your webhook URL: %s', 'cartpinger' ),
						'<code>' . esc_html( rest_url( 'cartpinger/v1/webhook' ) ) . '</code>'
					);
					?>
				</p>
				<?php
				break;
		}
	}
}
