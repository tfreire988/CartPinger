<?php
/**
 * Plugin Name:       WhatsCom for WooCommerce
 * Plugin URI:        https://whatscom.app
 * Description:       WhatsApp commerce for WooCommerce. Send order notifications, recover abandoned carts, OTP login, and chat widget via WhatsApp Cloud API. Bring your own WhatsApp Business Account.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            Telmo Freire
 * Author URI:        https://github.com/tfreire988
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       whatscom
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0
 * WC tested up to:      9.x
 *
 * @package WhatsCom
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'WHATSCOM_VERSION', '0.1.0' );
define( 'WHATSCOM_PLUGIN_FILE', __FILE__ );
define( 'WHATSCOM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WHATSCOM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WHATSCOM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WhatsCom: Composer dependencies missing. Run composer install.', 'whatscom' ) . '</p></div>';
		}
	);
	return;
}
require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( \WhatsCom\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \WhatsCom\Core\Deactivator::class, 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		\WhatsCom\Core\Plugin::instance()->boot();
	}
);
