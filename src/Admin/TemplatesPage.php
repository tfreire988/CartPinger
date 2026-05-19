<?php
/**
 * Admin Templates page — shows the exact WhatsApp template content
 * the merchant must create in Meta Business Manager, with multi-language tabs.
 *
 * @package CartPinger\Admin
 */

declare(strict_types=1);

namespace CartPinger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TemplatesPage
 */
final class TemplatesPage {

	private const LANGUAGES = array(
		'en_US' => 'English',
		'es_ES' => 'Español',
		'pt_BR' => 'Português',
		'fr_FR' => 'Français',
		'de_DE' => 'Deutsch',
	);

	/**
	 * Return all templates with their localized body content.
	 *
	 * @return array<int, array{name: string, category: string, plan: string, description: string, bodies: array<string, string>, sample: array<int, string>}>
	 */
	private static function templates(): array {
		return array(
			array(
				'name'        => 'abandoned_cart_recovery',
				'category'    => 'MARKETING',
				'plan'        => 'Free',
				'description' => 'Sent 1 hour after a customer abandons their cart, encouraging them to complete the purchase.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, you left items in your cart. Complete your purchase here: {{2}} See you soon!",
					'es_ES' => "Hola {{1}}, has dejado productos en tu carrito. Completa tu compra aquí: {{2}} ¡Te esperamos!",
					'pt_BR' => "Olá {{1}}, você deixou itens no carrinho. Conclua sua compra aqui: {{2}} Até já!",
					'fr_FR' => "Bonjour {{1}}, des articles vous attendent dans votre panier. Terminez votre achat ici : {{2}} À bientôt !",
					'de_DE' => "Hallo {{1}}, du hast Artikel im Warenkorb gelassen. Schließe deinen Einkauf hier ab: {{2}} Bis bald!",
				),
				'sample'      => array( 'customer name', 'recovery URL' ),
			),
			array(
				'name'        => 'order_confirmed',
				'category'    => 'UTILITY',
				'plan'        => 'Free',
				'description' => 'Sent when a WooCommerce order moves to Processing.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, your order #{{2}} has been confirmed. Total: {{3}}. We will let you know when it ships. Thanks!",
					'es_ES' => "Hola {{1}}, tu pedido #{{2}} ha sido confirmado. Total: {{3}}. Te avisaremos cuando lo enviemos. ¡Gracias!",
					'pt_BR' => "Olá {{1}}, seu pedido #{{2}} foi confirmado. Total: {{3}}. Avisaremos quando for enviado. Obrigado!",
					'fr_FR' => "Bonjour {{1}}, votre commande #{{2}} a été confirmée. Total : {{3}}. Nous vous tiendrons informé de l'expédition. Merci !",
					'de_DE' => "Hallo {{1}}, deine Bestellung #{{2}} wurde bestätigt. Gesamt: {{3}}. Wir benachrichtigen dich beim Versand. Danke!",
				),
				'sample'      => array( 'customer name', 'order number', 'order total' ),
			),
			array(
				'name'        => 'order_completed',
				'category'    => 'UTILITY',
				'plan'        => 'Free',
				'description' => 'Sent when a WooCommerce order moves to Completed.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, your order #{{2}} has been completed. Thanks for shopping with us!",
					'es_ES' => "Hola {{1}}, tu pedido #{{2}} ha sido completado. ¡Gracias por comprar con nosotros!",
					'pt_BR' => "Olá {{1}}, seu pedido #{{2}} foi concluído. Obrigado por comprar conosco!",
					'fr_FR' => "Bonjour {{1}}, votre commande #{{2}} est terminée. Merci pour votre achat !",
					'de_DE' => "Hallo {{1}}, deine Bestellung #{{2}} ist abgeschlossen. Danke für deinen Einkauf!",
				),
				'sample'      => array( 'customer name', 'order number' ),
			),
			array(
				'name'        => 'order_cancelled',
				'category'    => 'UTILITY',
				'plan'        => 'Free',
				'description' => 'Sent when a WooCommerce order is cancelled.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, your order #{{2}} has been cancelled. If this was a mistake, contact us and we will help.",
					'es_ES' => "Hola {{1}}, tu pedido #{{2}} ha sido cancelado. Si fue un error, contáctanos y te ayudaremos.",
					'pt_BR' => "Olá {{1}}, seu pedido #{{2}} foi cancelado. Se foi um engano, fale conosco e ajudaremos.",
					'fr_FR' => "Bonjour {{1}}, votre commande #{{2}} a été annulée. Si c'est une erreur, contactez-nous.",
					'de_DE' => "Hallo {{1}}, deine Bestellung #{{2}} wurde storniert. Bei Versehen melde dich bei uns.",
				),
				'sample'      => array( 'customer name', 'order number' ),
			),
			array(
				'name'        => 'abandoned_cart_recovery_24h',
				'category'    => 'MARKETING',
				'plan'        => 'Pro',
				'description' => 'Pro: Sent 24h after the first cart recovery message with a discount coupon.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, still thinking about your cart? Use code {{2}} for 10% off. Complete your purchase: {{3}} Offer ends soon!",
					'es_ES' => "Hola {{1}}, ¿sigues pensando en tu carrito? Usa el código {{2}} para 10% de descuento. Completa tu compra: {{3}} ¡La oferta termina pronto!",
					'pt_BR' => "Olá {{1}}, ainda pensando no seu carrinho? Use o código {{2}} para 10% off. Conclua sua compra: {{3}} Oferta por tempo limitado!",
					'fr_FR' => "Bonjour {{1}}, vous hésitez encore ? Utilisez le code {{2}} pour 10% de réduction. Terminez ici : {{3}} Offre limitée !",
					'de_DE' => "Hallo {{1}}, noch unentschlossen? Mit Code {{2}} bekommst du 10% Rabatt. Hier abschließen: {{3}} Angebot begrenzt!",
				),
				'sample'      => array( 'customer name', 'coupon code', 'recovery URL' ),
			),
			array(
				'name'        => 'abandoned_cart_recovery_48h',
				'category'    => 'MARKETING',
				'plan'        => 'Pro',
				'description' => 'Pro: Final reminder sent 48h after the first recovery message.',
				'bodies'      => array(
					'en_US' => "Hi {{1}}, last chance — your cart is still waiting! Complete your purchase here: {{2}} Don't miss out.",
					'es_ES' => "Hola {{1}}, última oportunidad — ¡tu carrito sigue esperándote! Completa tu compra aquí: {{2}} No te lo pierdas.",
					'pt_BR' => "Olá {{1}}, última chance — seu carrinho ainda espera! Conclua aqui: {{2}} Não perca.",
					'fr_FR' => "Bonjour {{1}}, dernière chance — votre panier vous attend ! Terminez ici : {{2}} À ne pas manquer.",
					'de_DE' => "Hallo {{1}}, letzte Chance — dein Warenkorb wartet noch! Hier abschließen: {{2}} Nicht verpassen.",
				),
				'sample'      => array( 'customer name', 'recovery URL' ),
			),
		);
	}

	/**
	 * Render the Templates admin page.
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cartpinger' ) );
		}

		$templates = self::templates();
		?>
		<div class="cp-wrap">
			<h1><?php esc_html_e( 'WhatsApp Templates', 'cartpinger' ); ?></h1>
			<p class="cp-subtitle"><?php esc_html_e( 'These are the message templates CartPinger uses. You must create each one in Meta Business Manager and wait for Meta approval.', 'cartpinger' ); ?></p>

			<div class="cp-info-box">
				<p><strong><?php esc_html_e( 'How to use this page:', 'cartpinger' ); ?></strong></p>
				<ol style="margin:8px 0 0 22px;padding:0;">
					<li><?php esc_html_e( 'Open Meta Template Manager (button below).', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'For each template, click Create template in Meta.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Copy the Name and Body shown here — paste them exactly into Meta.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Choose the category and language matching the ones shown here.', 'cartpinger' ); ?></li>
					<li><?php esc_html_e( 'Submit and wait for Meta approval (a few minutes for utility, longer for marketing).', 'cartpinger' ); ?></li>
				</ol>
				<p style="margin-top:12px;">
					<a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" rel="noopener" class="cp-btn cp-btn-primary">
						<?php esc_html_e( 'Open Meta Template Manager →', 'cartpinger' ); ?>
					</a>
				</p>
			</div>

			<div class="cp-info-box warning">
				<p><strong><?php esc_html_e( 'Important:', 'cartpinger' ); ?></strong> <?php esc_html_e( 'The template Name and number of variables must match exactly. The body text is just a suggestion — feel free to adapt it to your brand. Create one template per language your customers speak.', 'cartpinger' ); ?></p>
			</div>

			<?php foreach ( $templates as $i => $tpl ) : ?>
				<div class="cp-template-card">
					<div class="cp-template-header">
						<code><?php echo esc_html( $tpl['name'] ); ?></code>
						<span class="<?php echo 'Pro' === $tpl['plan'] ? 'cp-pro-badge' : 'cp-free-badge'; ?>"><?php echo esc_html( $tpl['plan'] ); ?></span>
						<span style="color:#646970;font-size:12px;">Category: <code><?php echo esc_html( $tpl['category'] ); ?></code></span>
					</div>
					<p style="color:#50575e;margin-top:0;"><?php echo esc_html( $tpl['description'] ); ?></p>

					<div class="cp-lang-tabs">
						<strong style="margin-right:8px;font-size:13px;">Language:</strong>
						<?php foreach ( self::LANGUAGES as $code => $name ) : ?>
							<span class="cp-lang-tab <?php echo 'en_US' === $code ? 'active' : ''; ?>" data-lang="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></span>
						<?php endforeach; ?>
					</div>

					<div class="cp-field">
						<label>Body text:</label>
						<?php foreach ( $tpl['bodies'] as $lang_code => $body ) : ?>
							<div data-lang-body="<?php echo esc_attr( $lang_code ); ?>" style="<?php echo 'en_US' === $lang_code ? '' : 'display:none;'; ?>">
								<div class="cp-template-body" id="tpl-body-<?php echo (int) $i; ?>-<?php echo esc_attr( $lang_code ); ?>"><?php echo esc_html( $body ); ?></div>
								<button class="cp-copy-btn" data-target="tpl-body-<?php echo (int) $i; ?>-<?php echo esc_attr( $lang_code ); ?>">Copy</button>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="cp-template-vars">
						<strong>Variables:</strong>
						<?php foreach ( $tpl['sample'] as $k => $value ) : ?>
							<code>{{<?php echo (int) ( $k + 1 ); ?>}}</code> = <?php echo esc_html( $value ); ?><?php echo $k < count( $tpl['sample'] ) - 1 ? ' &nbsp;·&nbsp; ' : ''; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
