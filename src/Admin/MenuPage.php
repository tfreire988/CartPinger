<?php
/**
 * Helper for building admin menu page output.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

/**
 * Class MenuPage — utility for rendering standard admin page wrappers.
 */
final class MenuPage {

	/**
	 * Output the opening wrap div and page title.
	 *
	 * @param string $title Page title.
	 */
	public static function open( string $title ): void {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
	}

	/**
	 * Output the closing wrap div.
	 */
	public static function close(): void {
		echo '</div>';
	}
}
