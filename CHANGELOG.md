# Changelog

## Unreleased

### Added
- Cookiebot, Usercentrics, OneTrust, CookieYes, iubenda, Termly, CookieFirst, Cookie-Script and Custom, chosen per site in the control panel, with IDs taken out of pasted snippets.
- Added to every front-end page by itself: Consent Mode defaults, the platform, Google Tag Manager and Google Analytics 4 in the right order.
- `{% consent %}`, `consent()`, `|consent`, `consentSettingsLink()`, `consentDeclaration()`, `consentPlaceholder()`, `consentHead()`, `consentCspHashes()`, `|consentTokens` and `craft.cookieConnect`.
- Scripts pasted into the control panel, held back until consent.
- `{cookie_declaration}` and `{cookie_settings}` in content, and links to `#cookie-settings`.
- `config/cookie-connect.php`: environments, what happens elsewhere, per-site settings from environment variables, a CSP nonce.
- SEOmatic: Google tags it adds aren't added again, and the control panel says so.
