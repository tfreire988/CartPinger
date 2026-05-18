<?php
/**
 * Plugin-wide constants and capability slugs.
 *
 * @package CartPinger\Core
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

namespace CartPinger\Core;

/**
 * Class Constants
 */
final class Constants {

	/** Minimum WooCommerce version. */
	public const WC_MIN_VERSION = '9.0';

	/** Minimum WordPress version. */
	public const WP_MIN_VERSION = '6.5';

	/** Minimum PHP version. */
	public const PHP_MIN_VERSION = '8.2';

	/** WP capability required to access admin screens. */
	public const MANAGE_CAPABILITY = 'manage_woocommerce';

	/** Option key that marks onboarding as complete. */
	public const OPTION_ONBOARDING_COMPLETE = 'cartpinger_onboarding_completed';

	/** Option key for plugin settings. */
	public const OPTION_SETTINGS = 'cartpinger_settings';

	/** Option key for DB schema version. */
	public const OPTION_DB_VERSION = 'cartpinger_db_version';

	/** Current DB schema version. */
	public const DB_VERSION = '0.1.0';

	/** Admin page slug for main menu. */
	public const MENU_SLUG = 'cartpinger';

	/** Admin page slug for setup wizard. */
	public const MENU_SLUG_SETUP = 'cartpinger-setup';

	/** Admin page slug for settings. */
	public const MENU_SLUG_SETTINGS = 'cartpinger-settings';

	/** REST API namespace. */
	public const REST_NAMESPACE = 'cartpinger/v1';
}
