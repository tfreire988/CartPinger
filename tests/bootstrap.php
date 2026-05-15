<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package WhatsCom\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define plugin constants for the test environment.
define( 'WHATSCOM_VERSION', '0.1.0' );
define( 'WHATSCOM_PLUGIN_FILE', dirname( __DIR__ ) . '/whatscom.php' );
define( 'WHATSCOM_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'WHATSCOM_PLUGIN_URL', 'http://localhost/wp-content/plugins/whatscom/' );
define( 'WHATSCOM_PLUGIN_BASENAME', 'whatscom/whatscom.php' );

// Bootstrap WP_Mock for unit tests.
\WP_Mock::bootstrap();
