<?php
/**
 * Plugin Name:       CartPinger for WooCommerce
 * Plugin URI:        https://cartpinger.app
 * Description:       WhatsApp commerce for WooCommerce. Send order notifications, recover abandoned carts, OTP login, and chat widget via WhatsApp Cloud API. Bring your own WhatsApp Business Account.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            Telmo Freire
 * Author URI:        https://github.com/tfreire988
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cartpinger
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0
 * WC tested up to:      9.x
 *
 * @package CartPinger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'CARTPINGER_VERSION', '0.2.0' );
define( 'CARTPINGER_PLUGIN_FILE', __FILE__ );
define( 'CARTPINGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CARTPINGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CARTPINGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register(
		function ( $class_name ) {
			$prefix = 'CartPinger\\';
			$len    = strlen( $prefix );
			if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
				return;
			}
			$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, $len ) ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( \CartPinger\Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \CartPinger\Core\Deactivator::class, 'deactivate' ) );

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
		\CartPinger\Core\Plugin::instance()->boot();
	}
);
