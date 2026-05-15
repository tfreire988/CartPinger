<?php
/**
 * Migration 0001 — initial schema.
 *
 * @package WhatsCom\Database\Migrations
 */

declare(strict_types=1);

namespace WhatsCom\Database\Migrations;

/**
 * Class Migration0001Initial
 *
 * The actual SQL is delegated to Schema::create() which uses dbDelta.
 * This file documents what version 0.1.0 introduced.
 */
final class Migration0001Initial {

	public const VERSION = '0.1.0';

	/**
	 * Tables introduced in this migration:
	 * - wp_whatscom_settings
	 * - wp_whatscom_messages_log
	 * - wp_whatscom_abandoned_carts
	 */
	public function up(): void {
		\WhatsCom\Database\Schema::create();
	}
}
