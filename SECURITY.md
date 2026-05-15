# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 0.1.x   | Yes       |

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Please report security issues by emailing **security@whatscom.app**.

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We will acknowledge receipt within 48 hours and aim to release a patch within 14 days for critical issues.

## Scope

- SQL injection via plugin DB queries
- Stored/reflected XSS in admin pages
- CSRF on state-changing admin actions
- Unauthorized access to plugin settings or logs
- Exposure of WhatsApp access tokens

## Out of scope

- Vulnerabilities in WordPress core, WooCommerce, or Meta Cloud API
- Issues requiring physical access to the server
- Social engineering attacks
