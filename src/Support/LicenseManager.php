<?php
/**
 * Manages CartPinger Pro license validation via Lemon Squeezy.
 *
 * @package CartPinger\Support
 */

declare(strict_types=1);

namespace CartPinger\Support;

/**
 * Class LicenseManager
 */
final class LicenseManager {

	private const OPT_KEY           = 'cartpinger_license_key';
	private const OPT_STATUS        = 'cartpinger_license_status';
	private const OPT_LIMIT_MONTH   = 'cartpinger_free_limit_month';
	private const LS_API            = 'https://api.lemonsqueezy.com/v1/licenses/';
	public const FREE_MONTHLY_LIMIT = 50;

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
}
