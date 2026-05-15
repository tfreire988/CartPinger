<?php
/**
 * Plugin uninstall script. Runs when the user deletes the plugin from WP admin.
 *
 * @package WhatsCom
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

\WhatsCom\Core\Uninstaller::uninstall();
