# Cookie settings links and the declaration

## For editors

Nothing here needs a template change.

- **A Cookie settings link**: link to `#cookie-settings` from anywhere (a navigation, a footer, a rich text field). Clicking it opens your platform's preferences. A link to another page's address ending in `#cookie-settings` (`https://example.com/#cookie-settings`, in an email, say) opens them when that page loads.
- **The cookie declaration**: type `{cookie_declaration}` on its own line in a rich text field. Your platform's cookie list appears there. Platforms without a declaration (Custom) show nothing.
- **A link in a sentence**: type `{cookie_settings}`, as in "You can change your mind at any time: {cookie_settings}." It becomes a Cookie settings link.

The tokens are swapped as the page is sent, so they work in any field. (If **Add to every page by itself** is off, use the `|consentTokens` filter on the field instead.)

## For templates

```twig
{{ consentSettingsLink() }}
{# <button type="button" class="cookie-connect-settings" data-cookie-connect-open>Cookie settings</button> #}

{{ consentSettingsLink('Change my cookie choices', { class: 'footer-link' }) }}
{{ consentSettingsLink('Cookie settings', {}, 'a') }}   {# a link to #cookie-settings #}

{{ consentDeclaration() }}

{{ entry.body|consentTokens }}
```

`|consentTokens` escapes plain strings first, like any Twig output; HTML fields (CKEditor) pass through.

Any element with `data-cookie-connect-open` opens the preferences. Where a platform opens its preferences from its own class (CookieYes, Termly, iubenda, Cookie-Script), the link gets that class too and the platform opens them.

"Cookie settings" and the placeholder text are translatable (category `cookie-connect`).
