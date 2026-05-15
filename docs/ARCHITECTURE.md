# Architecture

## Overview

WhatsCom is a WordPress plugin that integrates WooCommerce with the Meta WhatsApp Cloud API.

```
whatscom/
├── src/Core/          — Bootstrap, lifecycle (activate/deactivate/uninstall), DI container
├── src/Admin/         — Admin menu pages and onboarding wizard
├── src/WhatsApp/      — Cloud API client, template manager, webhook handler, message queue
├── src/WooCommerce/   — WooCommerce hooks (product button, order notifier, cart tracker, etc.)
├── src/Database/      — DB schema, migrations, repositories
├── src/REST/          — WordPress REST API routes
├── src/i18n/          — Text domain loader
└── src/Support/       — Logger, Sanitizer utilities
```

## Request flow (v1.0 target)

```
WooCommerce event (e.g. new order)
    → WCBootstrap hook
    → OrderNotifier::onNewOrder()
    → MessageQueue::enqueue()
    → [WP-Cron] MessageQueue::processQueue()
    → CloudApiClient::sendTemplate()
    → Meta Cloud API
    → [webhook] WebhookHandler::process()
    → MessageLogRepository::updateStatus()
```

## Data storage

| Storage            | Used for                                                  |
|--------------------|-----------------------------------------------------------|
| `wp_options`       | Plugin version, settings, onboarding flags                |
| `whatscom_settings`| Encrypted Meta credentials (access token, phone ID)      |
| `whatscom_messages_log` | Audit trail of outbound messages + delivery status   |
| `whatscom_abandoned_carts` | Cart snapshots for recovery                      |

## Security model

- Access tokens stored in `whatscom_settings` table with `is_encrypted=1`.
- Webhook payloads verified via HMAC-SHA256 against the app secret.
- All admin actions require `manage_woocommerce` capability.
- All DB queries use `$wpdb->prepare()`.
- No customer data sent to any server except Meta (via the store owner's own credentials).

## Free vs Pro tier (planned)

| Feature                   | Free | Pro |
|---------------------------|------|-----|
| Buy via WhatsApp button   | ✓    | ✓   |
| Admin order notifications | ✓    | ✓   |
| Chat widget               | ✓    | ✓   |
| COD confirmation          | ✓    | ✓   |
| Abandoned cart recovery   |      | ✓   |
| Templates manager UI      |      | ✓   |
| Multi-agent routing       |      | ✓   |
| AI chatbot                |      | ✓   |
