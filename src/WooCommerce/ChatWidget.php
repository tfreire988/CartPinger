<?php
/**
 * Floating WhatsApp chat widget rendered in the store's front-end footer.
 *
 * Outputs a single anchor element with an inline SVG icon and scoped inline
 * CSS. Zero external dependencies — no icon font, no CDN, no extra HTTP
 * request. The widget is only rendered when enabled in settings AND a valid
 * support phone number is stored.
 *
 * @package CartPinger\WooCommerce
 */

declare(strict_types=1);

namespace CartPinger\WooCommerce;

use CartPinger\Support\Sanitizer;

/**
 * Class ChatWidget
 */
final class ChatWidget {

	private const OPT_ENABLED = 'cartpinger_widget_enabled';
	private const OPT_PHONE   = 'cartpinger_support_phone';
	private const OPT_MESSAGE = 'cartpinger_widget_message';

	/**
	 * Register the wp_footer hook.
	 *
	 * Priority 100 ensures the widget lands after theme markup.
	 */
	public static function register(): void {
		add_action( 'wp_footer', array( self::class, 'renderWidget' ), 100 );
	}

	/**
	 * Render the floating widget in the footer.
	 *
	 * Guards:
	 *   1. Widget must be enabled via settings.
	 *   2. A valid E.164 support phone must be stored.
	 *
	 * Both the CSS block and the HTML are output in a single call so there is
	 * no risk of orphaned styles when the method returns early.
	 */
	public static function renderWidget(): void {
		if ( ! get_option( self::OPT_ENABLED, false ) ) {
			return;
		}

		$phone = Sanitizer::phone( (string) get_option( self::OPT_PHONE, '' ) );
		if ( '' === $phone ) {
			return;
		}

		$message = (string) get_option( self::OPT_MESSAGE, '' );
		$url     = self::buildUrl( $phone, $message );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo self::buildMarkup( $url );
	}

	/**
	 * Build the wa.me deep-link URL.
	 *
	 * Strips any non-digit characters from the phone so the URL contains only
	 * the country-code + number (no plus sign). The pre-filled message is
	 * percent-encoded with rawurlencode() per WhatsApp specification.
	 *
	 * @param string $phone   E.164 or digits-only phone number.
	 * @param string $message Optional pre-filled message text.
	 * @return string         Full wa.me URL, or empty string if phone is invalid.
	 */
	public static function buildUrl( string $phone, string $message ): string {
		$digits = (string) preg_replace( '/\D/', '', $phone );

		if ( '' === $digits ) {
			return '';
		}

		$url = 'https://wa.me/' . $digits;

		if ( '' !== $message ) {
			$url .= '?text=' . rawurlencode( $message );
		}

		return $url;
	}

	/**
	 * Assemble the scoped inline CSS block and the widget HTML.
	 *
	 * All dynamic values are escaped before insertion:
	 *   - href       → esc_url()
	 *   - aria-label → esc_attr( __() )
	 * The SVG path is a static string constant and requires no escaping.
	 *
	 * @param string $url The pre-built wa.me URL.
	 * @return string     HTML string ready for echo.
	 */
	private static function buildMarkup( string $url ): string {
		$safe_url   = esc_url( $url );
		$safe_label = esc_attr( __( 'Open WhatsApp chat', 'cartpinger' ) );

		$css  = '<style>';
		$css .= '.cartpinger-chat-widget{position:fixed;bottom:24px;right:24px;z-index:9999}';
		$css .= '.cartpinger-chat-widget__link{display:flex;align-items:center;';
		$css .= 'justify-content:center;width:56px;height:56px;background:#25d366;';
		$css .= 'border-radius:50%;box-shadow:0 4px 12px rgba(0,0,0,.25);';
		$css .= 'text-decoration:none;transition:transform .2s,box-shadow .2s}';
		$css .= '.cartpinger-chat-widget__link:hover';
		$css .= '{transform:scale(1.08);box-shadow:0 6px 16px rgba(0,0,0,.30)}';
		$css .= '.cartpinger-chat-widget__icon{width:32px;height:32px;fill:#fff}';
		$css .= '</style>';

		$html  = '<div class="cartpinger-chat-widget">';
		$html .= '<a class="cartpinger-chat-widget__link"';
		$html .= ' href="' . $safe_url . '"';
		$html .= ' target="_blank" rel="noopener noreferrer"';
		$html .= ' aria-label="' . $safe_label . '">';
		$html .= self::buildSvg();
		$html .= '</a></div>';

		return $css . $html;
	}

	/**
	 * Return the WhatsApp brand SVG icon markup.
	 *
	 * Monochrome path rendered at 32×32 px. aria-hidden="true" is set because
	 * the parent anchor already carries a descriptive aria-label.
	 *
	 * @return string Inline <svg> element.
	 */
	private static function buildSvg(): string {
		// Official WhatsApp brand icon — single compound path, viewBox 0 0 24 24.
		$path  = 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148';
		$path .= '-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075';
		$path .= '-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059';
		$path .= '-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174';
		$path .= '.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612';
		$path .= '-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01';
		$path .= '-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462';
		$path .= ' 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306';
		$path .= ' 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758';
		$path .= '-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272';
		$path .= '-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l';
		$path .= '-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26';
		$path .= 'c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898';
		$path .= 'a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884';
		$path .= 'm8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157';
		$path .= ' 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882';
		$path .= ' 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893';
		$path .= 'a11.821 11.821 0 00-3.48-8.413Z';

		$svg  = '<svg class="cartpinger-chat-widget__icon"';
		$svg .= ' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">';
		$svg .= '<path d="' . $path . '"/>';
		$svg .= '</svg>';

		return $svg;
	}
}
