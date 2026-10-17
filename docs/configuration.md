# Configuration

## The settings page

**Settings → Plugins → Cookie Connect** (saved to Project Config):

- **Environments**: where the consent platform loads, as `CRAFT_ENVIRONMENT` values, or `*` for everywhere. Default: `production`.
- **Elsewhere**: what happens to consent-gated content where it doesn't load (and on sites with no platform chosen): keep it blocked (default), run it as if accepted (to test tags), or leave it out.
- **Add to every page by itself**: on by default. Off: put `{{ consentHead() }}` straight after `<head>` in your layout, and `{{ consentBodyStart() }}` / `{{ consentBodyEnd() }}` after `<body>` and before `</body>` if you use body scripts.
- **Debug in the browser console**.

Each site's platform, Google tags and scripts are on the **Cookie Connect** page, kept in the database so they can be changed on a live site.

## config/cookie-connect.php

Copy `vendor/1994/cookie-connect-craft/src/config.php` to `config/cookie-connect.php`. Anything set there can't be changed in the control panel, and shows as fixed. Multi-environment keys work as in any Craft config file.

```php
<?php

return [
    '*' => [
        'environments' => ['production', 'staging'],
        'whenOff' => 'block',
        'autoInsert' => true,
    ],
    'dev' => [
        'whenOff' => 'run',
    ],
];
```

### Per-site settings in config

`sites` sets a site's platform, values, Google tags or scripts, by site handle, or `*` for every site. Strings starting with `$` are read from the environment.

```php
'sites' => [
    '*' => [
        'provider' => 'onetrust',
        'values' => ['domainScript' => '$ONETRUST_ID'],
        'google' => ['gtmId' => '$GTM_ID'],
    ],
    'german' => [
        'categoryMap' => ['analytics' => '2', 'marketing' => '4'],
    ],
],
```

With `ONETRUST_ID` set to the `-test` ID on staging and the production ID live, each environment uses the right one.

The keys are the same as on the page: `provider` (`cookiebot`, `usercentrics`, `onetrust`, `cookieyes`, `iubenda`, `termly`, `cookiefirst`, `cookie-script`, `custom`), `values` (the platform's fields, by the handles in [the provider files](https://github.com/1994limited/cookie-connect-core/tree/main/resources/providers)), `categoryMap`, `google` (`gtmId`, `ga4Id`, `consentMode`, `mode`, `regions`, `urlPassthrough`, `adsDataRedaction`, `gtmSetsDefaults`, `waitForUpdate`) and `scripts` (each with `name`, `categories`, `placement`, `code`, `enabled`).

## Multi-site

Each site has its own settings: choose the site in the breadcrumb menu on the Cookie Connect page. The platform's banner is shown in each site's language where the platform supports it (Cookiebot, Usercentrics, OneTrust).

## Caching

What Cookie Connect prints depends only on the site's settings, not the visitor: the visitor's choice is read in the browser. So pages cached by Blitz, a CDN or `{% cache %}` work as they are. Blitz caches pages after Cookie Connect has added itself, and saving a site's Cookie Connect settings refreshes Blitz's cache so pages pick up the change. Clear other caches (a CDN, `{% cache %}` blocks) yourself after changing settings.

## Content Security Policy

If your CSP uses nonces, give Cookie Connect a function that returns this request's nonce. Every script it prints (its own, the platform's, Google's, and the ones it holds back) gets it:

```php
'nonce' => fn () => \modules\Csp::nonce(),
```

Full-page caching and nonces don't mix (a cached page carries an old nonce). Use hashes instead: `consentCspHashes()` returns `'sha256-…'` sources for Cookie Connect's own inline scripts (the defaults, its script, Google's tags and the Control Panel's scripts), worked out even before they're added to the page, plus any inline scripts held back with `{% consent %}` earlier in the template. They only change when the settings do. Inline scripts held back after the call aren't included: give them a nonce, or move them to files.

Your platform's own domains need allowing too; see its CSP documentation.
