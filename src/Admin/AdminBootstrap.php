<?php
/**
 * Admin area bootstrapper.
 *
 * @package WhatsCom\Admin
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace WhatsCom\Admin;

/**
 * Class AdminBootstrap
 */
final class AdminBootstrap {

	/**
	 * Register all admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'registerMenu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'admin_notices', array( self::class, 'maybeShowOnboardingNotice' ) );
	}

	/**
	 * Register the admin menu hierarchy.
	 */
	public static function registerMenu(): void {
		add_menu_page(
			esc_html__( 'WhatsCom', 'whatscom' ),
			esc_html__( 'WhatsCom', 'whatscom' ),
			'manage_woocommerce',
			'whatscom',
			array( DashboardPage::class, 'render' ),
			'dashicons-format-chat',
			58
		);

		add_submenu_page(
			'whatscom',
			esc_html__( 'Dashboard', 'whatscom' ),
			esc_html__( 'Dashboard', 'whatscom' ),
			'manage_woocommerce',
			'whatscom',
			array( DashboardPage::class, 'render' )
		);

		add_submenu_page(
			'whatscom',
			esc_html__( 'Setup', 'whatscom' ),
			esc_html__( 'Setup', 'whatscom' ),
			'manage_woocommerce',
			'whatscom-setup',
			array( OnboardingWizard::class, 'render' )
		);

		add_submenu_page(
			'whatscom',
			esc_html__( 'Settings', 'whatscom' ),
			esc_html__( 'Settings', 'whatscom' ),
			'manage_woocommerce',
			'whatscom-settings',
			array( SettingsPage::class, 'render' )
		);
	}

	/**
	 * Enqueue admin assets on WhatsCom screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueueAssets( string $hook ): void {
		if (
			! str_starts_with( $hook, 'toplevel_page_whatscom' ) &&
			! str_contains( $hook, '_page_whatscom-' )
		) {
			return;
		}

		$asset_file = WHATSCOM_PLUGIN_DIR . 'assets/build/admin.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = require $asset_file;

			wp_enqueue_script(
				'whatscom-admin',
				WHATSCOM_PLUGIN_URL . 'assets/build/admin.js',
				is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				is_string( $asset['version'] ) ? $asset['version'] : WHATSCOM_VERSION,
				true
			);

			wp_enqueue_style(
				'whatscom-admin',
				WHATSCOM_PLUGIN_URL . 'assets/build/admin.css',
				array(),
				is_string( $asset['version'] ) ? $asset['version'] : WHATSCOM_VERSION
			);
		}
	}

	/**
	 * Show a one-time onboarding notice outside of WhatsCom screens.
	 */
	public static function maybeShowOnboardingNotice(): void {
		if ( get_option( 'whatscom_onboarding_completed' ) ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( $current_screen && str_contains( $current_screen->id, 'whatscom' ) ) {
			return;
		}

		$url = admin_url( 'admin.php?page=whatscom-setup' );
		printf(
			'<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'WhatsCom is installed. Complete the setup to start.', 'whatscom' ),
			esc_url( $url ),
			esc_html__( 'Open setup wizard', 'whatscom' )
		);
	}
}
