<?php
/**
 * Plugin uninstall script. Runs when the user deletes the plugin from WP admin.
 *
 * @package CartPinger
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

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

\CartPinger\Core\Uninstaller::uninstall();
