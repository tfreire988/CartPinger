<?php
/**
 * Plugin uninstall script. Runs when the user deletes the plugin from WP admin.
 *
 * @package CartPinger
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

\CartPinger\Core\Uninstaller::uninstall();
