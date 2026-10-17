# Cookie Connect documentation

Cookie Connect connects your Craft site to its cookie consent platform, sets up Google Consent Mode v2, and gives your templates one way to hold back scripts until the visitor allows them.

## Setting up

1. [Installation](installation.md): requirements, installing, where it runs.
2. [Choosing your platform](platforms.md): each platform's settings, where to find its ID, and what Cookie Connect does with it.
3. [Google tags and Consent Mode](google.md): Tag Manager, Analytics 4, basic and advanced mode, SEOmatic.

## On your site

4. [Holding back scripts and embeds](consent.md): the `{% consent %}` tag, the function and filter, placeholders, and scripts pasted into the control panel.
5. [Cookie settings links and the declaration](links.md): for editors and for templates.
6. [JavaScript](javascript.md): `window.CookieConnect` and the `cookieconnect:change` event.

## Reference

7. [Configuration](configuration.md): `config/cookie-connect.php`, multi-site, caching, Content Security Policy.
8. [Troubleshooting](troubleshooting.md): common problems and fixes.
9. [The demo](demo.md): Dog-ear Books, a site to try it on.

Cookie Connect connects your site to the consent platform you choose. It doesn't give legal advice, and using it doesn't by itself make a site compliant with any law.
