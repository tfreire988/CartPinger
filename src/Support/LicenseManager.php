<?php
/**
 * Manages CartPinger Pro license validation via Lemon Squeezy.
 *
 * @package CartPinger\Support
 */

declare(strict_types=1);

namespace CartPinger\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseManager
 */
final class LicenseManager {

	private const OPT_KEY              = 'cartpinger_license_key';
	private const OPT_STATUS           = 'cartpinger_license_status';
	private const OPT_LIMIT_MONTH      = 'cartpinger_free_limit_month';
	private const OPT_LAST_CHECK       = 'cartpinger_license_last_check';
	private const OPT_LAST_FAIL_REASON = 'cartpinger_license_last_fail_reason';
	private const LS_API               = 'https://api.lemonsqueezy.com/v1/licenses/';
	public const FREE_MONTHLY_LIMIT    = 50;
	public const CRON_HOOK             = 'cartpinger_validate_license';

	/**
	 * Returns true when a valid Pro license is active.
	 */
	public static function isPro(): bool {
		return 'active' === get_option( self::OPT_STATUS, '' );
	}

	/**
	 * Returns true when the free monthly recovery limit has been reached.
	 *
	 * Always returns false for Pro users.
	 */
	public static function isMonthlyLimitReached(): bool {
		if ( self::isPro() ) {
			return false;
		}

		$repo = new \CartPinger\Database\Repositories\CartRecoveryRepository();
		return $repo->countMonthlySent() >= self::FREE_MONTHLY_LIMIT;
	}

	/**
	 * Record the current month as the one where the free limit was hit.
	 */
	public static function recordLimitReached(): void {
		update_option( self::OPT_LIMIT_MONTH, gmdate( 'Y-m' ), false );
	}

	/**
	 * Returns true if the limit was reached in the current calendar month.
	 */
	public static function isLimitMonthCurrent(): bool {
		return get_option( self::OPT_LIMIT_MONTH, '' ) === gmdate( 'Y-m' );
	}

	/**
	 * Activate a license key against the Lemon Squeezy API.
	 *
	 * @param string $key License key supplied by the user.
	 * @return array{success: bool, message: string}
	 */
	public static function activate( string $key ): array {
		$key = sanitize_text_field( $key );

		if ( '' === $key ) {
			return array(
				'success' => false,
				'message' => 'License key is required.',
			);
		}

		$response = wp_remote_post(
			self::LS_API . 'activate',
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
				'body'    => array(
					'license_key'   => $key,
					'instance_name' => (string) get_bloginfo( 'name' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== (int) $code || empty( $body['activated'] ) ) {
			$msg = isset( $body['error'] ) ? (string) $body['error'] : 'Activation failed.';
			return array(
				'success' => false,
				'message' => $msg,
			);
		}

		update_option( self::OPT_KEY, $key, false );
		update_option( self::OPT_STATUS, 'active', false );

		return array(
			'success' => true,
			'message' => 'License activated.',
		);
	}

	/**
	 * Deactivate the current license key.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function deactivate(): array {
		$key = (string) get_option( self::OPT_KEY, '' );

		if ( '' !== $key ) {
			wp_remote_post(
				self::LS_API . 'deactivate',
				array(
					'timeout' => 15,
					'headers' => array( 'Accept' => 'application/json' ),
					'body'    => array(
						'license_key' => $key,
					),
				)
			);
		}

		update_option( self::OPT_KEY, '', false );
		update_option( self::OPT_STATUS, '', false );

		return array(
			'success' => true,
			'message' => 'License deactivated.',
		);
	}

	/**
	 * Return the stored license key (masked for display).
	 */
	public static function getMaskedKey(): string {
		$key = (string) get_option( self::OPT_KEY, '' );

		if ( '' === $key || strlen( $key ) < 8 ) {
			return '';
		}

		return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
	}

	/**
	 * Register the daily validation cron.
	 *
	 * Hooked from Plugin::boot(). Ensures wp_schedule_event has scheduled the
	 * validation hook; the hook itself is wired here so it fires from any
	 * request that runs the WP cron (admin, REST, frontend).
	 */
	public static function register(): void {
		add_action( self::CRON_HOOK, array( self::class, 'validate' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Validate the stored license against the Lemon Squeezy API.
	 *
	 * Behavior:
	 *  - No stored key: no-op.
	 *  - LS returns `valid` AND subscription `status_formatted` is "Active",
	 *    "On Trial", or "Cancelled" with `valid` still true: keep Pro active.
	 *  - LS returns `valid` false, or the license is expired/disabled: flip
	 *    OPT_STATUS to '' so isPro() returns false. The key is NOT deleted —
	 *    if the merchant resubscribes the next validate() restores Pro.
	 *  - Network/HTTP error: do NOT downgrade. Log the timestamp so the admin
	 *    UI can show "last verified X ago".
	 *
	 * @return array{success: bool, status: string, message: string}
	 */
	public static function validate(): array {
		$key = (string) get_option( self::OPT_KEY, '' );

		if ( '' === $key ) {
			return array(
				'success' => false,
				'status'  => 'no_key',
				'message' => 'No license key stored.',
			);
		}

		update_option( self::OPT_LAST_CHECK, time(), false );

		$response = wp_remote_post(
			self::LS_API . 'validate',
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
				'body'    => array( 'license_key' => $key ),
			)
		);

		if ( is_wp_error( $response ) ) {
			update_option( self::OPT_LAST_FAIL_REASON, 'network_error: ' . $response->get_error_message(), false );
			return array(
				'success' => false,
				'status'  => 'network_error',
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) ) {
			update_option( self::OPT_LAST_FAIL_REASON, 'http_error: ' . $code, false );
			return array(
				'success' => false,
				'status'  => 'http_error',
				'message' => 'License server returned HTTP ' . $code . '.',
			);
		}

		$is_valid = ! empty( $body['valid'] );

		if ( $is_valid ) {
			update_option( self::OPT_STATUS, 'active', false );
			update_option( self::OPT_LAST_FAIL_REASON, '', false );
			return array(
				'success' => true,
				'status'  => 'active',
				'message' => 'License is active.',
			);
		}

		// Valid is false → subscription cancelled, expired, refunded, or never valid.
		update_option( self::OPT_STATUS, '', false );
		$reason = isset( $body['error'] ) ? (string) $body['error'] : 'License is no longer valid.';
		update_option( self::OPT_LAST_FAIL_REASON, $reason, false );

		return array(
			'success' => false,
			'status'  => 'invalid',
			'message' => $reason,
		);
	}

	/**
	 * Mark the Pro status as inactive without contacting Lemon Squeezy.
	 *
	 * Used by the LS webhook handler when a subscription_cancelled or
	 * subscription_payment_failed event arrives — faster than waiting for the
	 * daily cron to detect it.
	 *
	 * @param string $reason Short reason for the audit log.
	 */
	public static function forceDowngrade( string $reason ): void {
		update_option( self::OPT_STATUS, '', false );
		update_option( self::OPT_LAST_FAIL_REASON, $reason, false );
		update_option( self::OPT_LAST_CHECK, time(), false );
	}

	/**
	 * Mark the Pro status as active without contacting Lemon Squeezy.
	 *
	 * Used by the LS webhook handler when a subscription_resumed or
	 * subscription_payment_success event arrives for a previously known key.
	 */
	public static function forceUpgrade(): void {
		if ( '' === (string) get_option( self::OPT_KEY, '' ) ) {
			return;
		}
		update_option( self::OPT_STATUS, 'active', false );
		update_option( self::OPT_LAST_FAIL_REASON, '', false );
		update_option( self::OPT_LAST_CHECK, time(), false );
	}

	/**
	 * Return seconds since the last successful or attempted validate() call.
	 *
	 * Returns null when validate() has never run.
	 */
	public static function secondsSinceLastCheck(): ?int {
		$ts = (int) get_option( self::OPT_LAST_CHECK, 0 );
		if ( 0 === $ts ) {
			return null;
		}
		return max( 0, time() - $ts );
	}

	/**
	 * Return the human-readable reason the last validate() call failed, if any.
	 */
	public static function lastFailReason(): string {
		return (string) get_option( self::OPT_LAST_FAIL_REASON, '' );
	}
}
