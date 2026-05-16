<?php
/**
 * WhatsApp message template manager.
 *
 * Fetches approved templates from the Meta Cloud API and caches them locally
 * in a WordPress transient. Callers receive a normalised list of template
 * name / status / language tuples.
 *
 * @package WhatsCom\WhatsApp
 */

declare(strict_types=1);

namespace WhatsCom\WhatsApp;

/**
 * Class TemplateManager
 */
final class TemplateManager {

	/** Meta Graph API base URL. */
	private const API_BASE = 'https://graph.facebook.com/v19.0';

	/** Transient key and TTL (1 hour). */
	private const CACHE_KEY = 'whatscom_templates_cache';
	private const CACHE_TTL = 3600;

	/** HTTP timeout in seconds. */
	private const TIMEOUT = 15;

	private string $waba_id;
	private string $access_token;

	/**
	 * Create a new TemplateManager.
	 *
	 * @param string $waba_id      WhatsApp Business Account ID.
	 * @param string $access_token Bearer token for the Cloud API.
	 */
	public function __construct( string $waba_id, string $access_token ) {
		$this->waba_id      = $waba_id;
		$this->access_token = $access_token;
	}

	/**
	 * Return locally cached templates, fetching from the API on a cache miss.
	 *
	 * @return array<int, array{name: string, status: string, language: string}>
	 */
	public function getTemplates(): array {
		$cached = get_transient( self::CACHE_KEY );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		return $this->syncFromApi();
	}

	/**
	 * Refresh the local cache from the Cloud API and return the result.
	 *
	 * Returns an empty array when credentials are invalid or the API is
	 * unreachable — the stale transient is not updated in that case.
	 *
	 * @return array<int, array{name: string, status: string, language: string}>
	 */
	public function syncFromApi(): array {
		if ( '' === $this->waba_id || '' === $this->access_token ) {
			return array();
		}

		$url      = self::API_BASE . '/' . $this->waba_id . '/message_templates';
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $this->access_token ),
				'timeout' => self::TIMEOUT,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$status = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return array();
		}

		$templates = $this->parseTemplates( $data['data'] );

		set_transient( self::CACHE_KEY, $templates, self::CACHE_TTL );

		return $templates;
	}

	/**
	 * Normalise raw API template entries into the {name, status, language} shape.
	 *
	 * @param array<int, mixed> $raw Raw "data" array from the API response.
	 * @return array<int, array{name: string, status: string, language: string}>
	 */
	private function parseTemplates( array $raw ): array {
		$templates = array();

		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if (
				! isset( $item['name'], $item['status'], $item['language'] ) ||
				! is_string( $item['name'] ) ||
				! is_string( $item['status'] ) ||
				! is_string( $item['language'] )
			) {
				continue;
			}

			$templates[] = array(
				'name'     => $item['name'],
				'status'   => $item['status'],
				'language' => $item['language'],
			);
		}

		return $templates;
	}
}
