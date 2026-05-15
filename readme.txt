=== WhatsCom for WooCommerce ===
Contributors: telmofreire
Tags: woocommerce, whatsapp, abandoned cart, chat, notifications
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 8.2
WC requires at least: 9.0
WC tested up to: 9.x
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WhatsApp commerce for WooCommerce. Order notifications, abandoned cart recovery, OTP login, chat widget. Bring your own WhatsApp Business Account.

== Description ==

WhatsCom integrates WhatsApp Cloud API with your WooCommerce store. Send order notifications, recover abandoned carts, enable OTP login, and add a chat widget — all using your own WhatsApp Business Account.

**This is a beta release. Full features arrive in v1.0 (Q3 2026).**

= Features (v0.1 beta) =

* Plugin skeleton ready for configuration
* Admin onboarding wizard for Meta Business verification
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

WhatsCom does not collect or transmit any data to third-party servers operated by the plugin author. Customer phone numbers are sent directly from your server to Meta (WhatsApp Cloud API) using your own access token. Please review [Meta's privacy policy](https://www.facebook.com/privacy/policy/) for details on how Meta handles this data.

= Open source =

Source code available on GitHub under GPL-2.0-or-later. Contributions welcome.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/whatscom/`, or install via the WordPress admin Plugins screen.
2. Activate through the **Plugins** menu.
3. Navigate to **WhatsCom > Setup** and complete the onboarding wizard.
4. Connect your own WhatsApp Business Account via the Meta verification flow.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

Yes, WooCommerce 9.0 or higher is required.

= Do I need a paid plan? =

No. You pay Meta directly for messages (per their official pricing). WhatsCom itself is free. Pro features (planned for a future release) will be a one-time purchase — no SaaS subscription.

= Is my WhatsApp Business Account safe? =

WhatsCom uses the official Meta Cloud API. Your access tokens are stored in your own WordPress database. We never see or store your credentials on any external server.

= Does this work with WooCommerce HPOS? =

Yes. WhatsCom explicitly declares compatibility with WooCommerce High-Performance Order Storage (HPOS/custom order tables).

= Which WhatsApp account type is required? =

You need a **WhatsApp Business Account (WABA)** created inside Meta Business Manager, with a registered phone number. Regular personal WhatsApp accounts are not supported.

== Screenshots ==

1. WhatsCom Dashboard (v0.1 skeleton)
2. Setup Wizard — Step 1 Welcome
3. Setup Wizard — Step 3 WhatsApp Business Account
4. Admin Settings page

== Changelog ==

= 0.1.0 — 2026-05-15 =
* Initial beta release: plugin skeleton, admin menu, HPOS compatibility declaration, DB schema, onboarding wizard scaffold.

== Upgrade Notice ==

= 0.1.0 =
First public beta release. No upgrade path needed.
