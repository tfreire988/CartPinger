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
				'permission_callback' => '__return_true',
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
