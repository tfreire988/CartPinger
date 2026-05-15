<?php
/**
 * WhatsApp message template manager stub.
 *
 * @package WhatsCom\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\WhatsApp;

/**
 * Class TemplateManager
 *
 * TODO v1.0: list / create / delete templates via Cloud API, cache locally.
 */
final class TemplateManager {

	/**
	 * Return locally cached templates.
	 *
	 * @return array<int, array{name: string, status: string, language: string}>
	 */
	public function getTemplates(): array {
		// TODO v1.0: fetch from wp_options cache or Cloud API.
		return array();
	}

	/**
	 * Refresh the local cache from the Cloud API.
	 */
	public function syncFromApi(): void {
		// TODO v1.0: implement.
	}
}
