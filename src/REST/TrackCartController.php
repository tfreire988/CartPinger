<?php
/**
 * REST endpoint for real-time block checkout cart tracking.
 *
 * Called by the block-checkout-tracker.js script as the customer fills in
 * their phone and consent checkbox — before they click Place Order.
 *
 * @package CartPinger\REST
 */

declare(strict_types=1);

namespace CartPinger\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartPinger\Database\Repositories\CartRecoveryRepository;
use CartPinger\Support\Sanitizer;

/**
 * Class TrackCartController
 */
final class TrackCartController {

	/**
	 * Register the REST route.
	 */
	public static function register(): void {
		register_rest_route(
			'cartpinger/v1',
			'/track-cart',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle' ),
				'permission_callback' => array( self::class, 'checkPermission' ),
				'args'                => array(
					'phone'   => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'name'    => array(
						'type'              => 'string',
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'consent' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Verify the request originates from a real checkout session.
	 *
	 * Block checkout already authenticates REST writes via the wp_rest nonce
	 * (passed in the X-WP-Nonce header). We require the same nonce here so
	 * arbitrary external callers cannot flood the recoveries table.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return bool
	 */
	public static function checkPermission( \WP_REST_Request $request ): bool {
		$nonce = (string) ( $request->get_header( 'x_wp_nonce' ) ?? '' );
		if ( '' === $nonce ) {
			return false;
		}
		return false !== wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Handle POST /cartpinger/v1/track-cart.
	 *
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @return \WP_REST_Response
	 */
	public static function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$phone   = Sanitizer::phone( (string) $request->get_param( 'phone' ) );
		$name    = (string) $request->get_param( 'name' );
		$consent = (bool) $request->get_param( 'consent' );

		if ( '' === $phone ) {
			return new \WP_REST_Response( array( 'success' => false ), 400 );
		}

		$repo = new CartRecoveryRepository();

		if ( ! $consent ) {
			$repo->revokeConsent( $phone );
			return new \WP_REST_Response( array( 'success' => true ) );
		}

		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() ) {
			return new \WP_REST_Response( array( 'success' => false ) );
		}

		$contents = (string) wp_json_encode( $cart->get_cart_contents() );
		$token    = bin2hex( random_bytes( 32 ) );

		$repo->upsert( $phone, $name, $contents, $token, true );

		return new \WP_REST_Response( array( 'success' => true ) );
	}
}
