<?php
/**
 * Admin area bootstrapper.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminBootstrap
 */
final class AdminBootstrap {

	/**
	 * Register all admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_init', array( OnboardingWizard::class, 'handleComplete' ) );
		add_action( 'admin_menu', array( self::class, 'registerMenu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'admin_notices', array( self::class, 'maybeShowOnboardingNotice' ) );
		add_action( 'admin_notices', array( self::class, 'maybeShowFreeLimitNotice' ) );
	}

	/**
	 * Register the admin menu hierarchy.
	 */
	public static function registerMenu(): void {
		add_menu_page(
			esc_html__( 'CartPinger', 'cartpinger' ),
			esc_html__( 'CartPinger', 'cartpinger' ),
			'manage_woocommerce',
			'cartpinger',
			array( DashboardPage::class, 'render' ),
			'dashicons-format-chat',
			58
		);

		add_submenu_page(
			'cartpinger',
			esc_html__( 'Dashboard', 'cartpinger' ),
			esc_html__( 'Dashboard', 'cartpinger' ),
			'manage_woocommerce',
			'cartpinger',
			array( DashboardPage::class, 'render' )
		);

		add_submenu_page(
			'cartpinger',
			esc_html__( 'Setup', 'cartpinger' ),
			esc_html__( 'Setup', 'cartpinger' ),
			'manage_woocommerce',
			'cartpinger-setup',
			array( OnboardingWizard::class, 'render' )
		);

		add_submenu_page(
			'cartpinger',
			esc_html__( 'Templates', 'cartpinger' ),
			esc_html__( 'Templates', 'cartpinger' ),
			'manage_woocommerce',
			'cartpinger-templates',
			array( TemplatesPage::class, 'render' )
		);

		add_submenu_page(
			'cartpinger',
			esc_html__( 'Settings', 'cartpinger' ),
			esc_html__( 'Settings', 'cartpinger' ),
			'manage_woocommerce',
			'cartpinger-settings',
			array( SettingsPage::class, 'render' )
		);
	}

	/**
	 * Enqueue admin assets on CartPinger screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueueAssets( string $hook ): void {
		if (
			! str_starts_with( $hook, 'toplevel_page_cartpinger' ) &&
			! str_contains( $hook, '_page_cartpinger-' )
		) {
			return;
		}

		$asset_file = CARTPINGER_PLUGIN_DIR . 'assets/build/admin.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;

			wp_enqueue_script(
				'cartpinger-admin',
				CARTPINGER_PLUGIN_URL . 'assets/build/admin.js',
				is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				is_string( $asset['version'] ) ? $asset['version'] : CARTPINGER_VERSION,
				true
			);

			wp_localize_script(
				'cartpinger-admin',
				'cartpingerAdmin',
				array(
					'restUrl' => rest_url( 'cartpinger/v1/' ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
				)
			);

			wp_enqueue_style(
				'cartpinger-admin',
				CARTPINGER_PLUGIN_URL . 'assets/build/admin.css',
				array(),
				is_string( $asset['version'] ) ? $asset['version'] : CARTPINGER_VERSION
			);
		}
	}

	/**
	 * Show a one-time onboarding notice outside of CartPinger screens.
	 */
	public static function maybeShowOnboardingNotice(): void {
		if ( get_option( 'cartpinger_onboarding_completed' ) ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( $current_screen && str_contains( $current_screen->id, 'cartpinger' ) ) {
			return;
		}

		$url = admin_url( 'admin.php?page=cartpinger-setup' );
		printf(
			'<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'CartPinger is installed. Complete the setup to start.', 'cartpinger' ),
			esc_url( $url ),
			esc_html__( 'Open setup wizard', 'cartpinger' )
		);
	}

	/**
	 * Show a warning notice when the free monthly recovery limit has been reached.
	 */
	public static function maybeShowFreeLimitNotice(): void {
		if ( \CartPinger\Support\LicenseManager::isPro() ) {
			return;
		}

		if ( ! \CartPinger\Support\LicenseManager::isLimitMonthCurrent() ) {
			return;
		}

		$url     = admin_url( 'admin.php?page=cartpinger-settings' );
		$message = esc_html(
			sprintf(
				/* translators: %d: monthly recovery limit */
				__( 'CartPinger: you have reached the %d free recovery limit for this month. No further messages will be sent until next month.', 'cartpinger' ),
				\CartPinger\Support\LicenseManager::FREE_MONTHLY_LIMIT
			)
		);
		printf(
			'<div class="notice notice-warning"><p>%s <a href="%s"><strong>%s</strong></a></p></div>',
			$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is produced by esc_html()
			esc_url( $url ),
			esc_html__( 'Upgrade to Pro (€14/mo or €99/year) for unlimited recoveries →', 'cartpinger' )
		);
	}
}
