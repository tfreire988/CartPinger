=== CartPinger for WooCommerce ===
Contributors: telmofreire
Tags: woocommerce, whatsapp, abandoned cart, chat, notifications
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.2
WC requires at least: 9.0
WC tested up to: 9.x
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp commerce for WooCommerce. Order notifications, abandoned cart recovery, OTP login, chat widget. Bring your own WhatsApp Business Account.

== Description ==

CartPinger integrates WhatsApp Cloud API with your WooCommerce store. Send order notifications, recover abandoned carts, enable OTP login, and add a chat widget — all using your own WhatsApp Business Account.

**This is a beta release. Full features arrive in v1.0 (Q3 2026).**

= Features (v0.1 beta) =

* Admin onboarding wizard — 5-step Meta verification flow with live credentials form
* REST API — settings, webhook, test-message, and templates endpoints
* Webhook verification and signed payload processing (X-Hub-Signature-256)
* Order status notifications — sends WhatsApp template messages on processing / completed / cancelled
* AES-256-GCM encrypted credential storage (access token + app secret never stored in plain text)
* Template manager — fetches approved templates from Meta API with 1-hour transient cache
* Async message queue backed by custom DB table, processed via WP-Cron
* WooCommerce HPOS (High-Performance Order Storage) compatible
* Block-based checkout fields support (WooCommerce 9.0+)
* Internationalization-ready (5 languages planned: ES, PT-BR, EN, FR, IT)

= Planned features (v1.0) =

* Abandoned cart recovery via WhatsApp
* Order status notifications via WhatsApp
* Pre-approved message templates in 5 languages
* WhatsApp chat widget with multi-agent routing
* OTP login via WhatsApp
* Built-in cost calculator (Meta messaging pricing)
* AI chatbot integration (Pro tier)

= Privacy =

CartPinger does not collect or transmit any data to third-party servers operated by the plugin author. Customer phone numbers are sent directly from your server to Meta (WhatsApp Cloud API) using your own access token. Please review [Meta's privacy policy](https://www.facebook.com/privacy/policy/) for details on how Meta handles this data.

= Open source =

Source code available on GitHub under GPL-2.0-or-later. Contributions welcome.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/cartpinger/`, or install via the WordPress admin Plugins screen.
2. Activate through the **Plugins** menu.
3. Navigate to **CartPinger > Setup** and complete the onboarding wizard.
4. Connect your own WhatsApp Business Account via the Meta verification flow.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

Yes, WooCommerce 9.0 or higher is required.

= Do I need a paid plan? =

No. You pay Meta directly for messages (per their official pricing). CartPinger itself is free. Pro features (planned for a future release) will be a one-time purchase — no SaaS subscription.

= Is my WhatsApp Business Account safe? =

CartPinger uses the official Meta Cloud API. Your access tokens are stored in your own WordPress database. We never see or store your credentials on any external server.

= Does this work with WooCommerce HPOS? =

Yes. CartPinger explicitly declares compatibility with WooCommerce High-Performance Order Storage (HPOS/custom order tables).

= Which WhatsApp account type is required? =

You need a **WhatsApp Business Account (WABA)** created inside Meta Business Manager, with a registered phone number. Regular personal WhatsApp accounts are not supported.

== Screenshots ==

1. CartPinger Dashboard (v0.1 skeleton)
2. Setup Wizard — Step 1 Welcome
3. Setup Wizard — Step 3 WhatsApp Business Account
4. Admin Settings page

== Changelog ==

= 0.1.0 — 2026-05-17 =
* AES-256-GCM encrypted credential storage via HKDF-derived keys.
* REST API: GET/POST /cartpinger/v1/settings (5 credential fields including WABA ID).
* REST API: GET/POST /cartpinger/v1/webhook — Meta challenge verification + signed event dispatch.
* REST API: POST /cartpinger/v1/test-message — send a test WhatsApp text message from the wizard.
* REST API: GET /cartpinger/v1/templates — list approved templates with 1-hour transient cache.
* Order notifications: template message sent on processing / completed / cancelled status change.
* Async message queue: enqueue → WP-Cron single event → Cloud API → sent/failed status update.
* Onboarding wizard step 5: live credentials form + test-connection widget + webhook URL display.
* Completion handler: nonce-verified finish-setup action marks onboarding done and redirects.
* Deactivation: clears cron events and templates transient; data preserved for re-activation.
* Uninstall: opt-in data deletion (delete_data_on_uninstall setting) removes tables, options, and transients.
* i18n: load_plugin_textdomain() wired on plugins_loaded for local translation overrides.
* Initial beta release: plugin skeleton, admin menu, HPOS compatibility declaration, DB schema.

== Upgrade Notice ==

= 0.1.0 =
First public beta release. No upgrade path needed.
