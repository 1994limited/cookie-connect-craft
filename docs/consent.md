# Holding back scripts and embeds

Wrap anything that sets cookies in `{% consent %}` with the categories it needs. It's printed with your platform's own blocking markup, so the platform runs it once the visitor allows those categories. Switch platform and the markup changes with it; your templates don't.

```twig
{% consent 'analytics' %}
    <script src="https://plausible.io/js/script.js" defer></script>
{% endconsent %}

{% consent 'marketing' %}
    <iframe src="https://www.youtube.com/embed/abc123" width="560" height="315" allowfullscreen></iframe>
{% endconsent %}
```

The categories are **necessary**, **preferences**, **analytics** and **marketing**. Content that needs several categories waits for all of them:

```twig
{% consent ['analytics', 'marketing'] %}…{% endconsent %}
```

Scripts (inline or with a `src`), iframes and images are held back. Everything else inside the tag (text, `<div>`s, JSON-LD) is printed as it is.

## What it prints

The same template, with two platforms:

```html
<!-- Cookiebot -->
<script type="text/plain" data-cookieconsent="statistics" data-cookie-connect-native="analytics" src="https://plausible.io/js/script.js" defer></script>

<!-- OneTrust -->
<script type="text/plain" class="optanon-category-C0002" data-cookie-connect-native="analytics" src="https://plausible.io/js/script.js" defer></script>
```

Where a platform has no markup for something (CookieYes and inline scripts, Usercentrics, Custom, module scripts, or several categories on a platform that takes one), Cookie Connect holds it back itself (`type="text/plain" data-cookie-connect="analytics"`) and runs it once the platform says yes. Either way, each element is run once, in page order.

## A placeholder

![A video held back behind a placeholder, and the same page once marketing cookies are allowed](images/site-before.png)

![The video plays once marketing cookies are allowed; analytics stays off](images/site-after.png)

Show a message in place of a held-back embed, with a button that opens the cookie settings:

```twig
{% consent 'marketing' with { placeholder: true } %}
    <iframe src="https://www.youtube.com/embed/abc123"></iframe>
{% endconsent %}

{% consent 'marketing' with { placeholder: 'Allow marketing cookies to watch this video.' } %}…{% endconsent %}
```

It's hidden once the categories are allowed. Style it with `.cookie-connect-placeholder`. Held-back iframes and images take no space until they're allowed.

On its own:

```twig
{{ consentPlaceholder('marketing') }}
{{ consentPlaceholder('marketing', 'This video needs marketing cookies.', 'Change my cookie settings') }}
```

## The function and filter

For HTML in a variable or field (an embed code field, say):

```twig
{{ entry.embedCode|raw|consent('marketing') }}
{{ entry.embedCode|raw|consent('marketing', { placeholder: true }) }}
{{ entry.body|consent('marketing') }}   {# CKEditor fields are HTML already #}
{{ consent('marketing', entry.embedCode) }}
```

Like any Twig output, a plain string through the filter is escaped first, so a value a visitor could set never becomes HTML. Use `|raw` for a field you trust to hold HTML. The `consent()` function prints what it's given as HTML: pass it only HTML you trust.

## Scripts pasted into the control panel

Under **Scripts** on the Cookie Connect page, add a script with:

![A Hotjar script in the Scripts list, needing analytics](images/control-panel-scripts.png)


- a **name**, so you can find it again;
- what it **needs**: necessary, preferences, analytics or marketing;
- **where** it goes: the head, the start of the body or the end of the body;
- its **code**, including the `<script>` tags.

It's printed on every page of that site, held back like `{% consent %}` content. Turn it off with **On** without deleting it. No template changes.

## When the platform isn't loaded

Where Cookie Connect is off (an environment it doesn't run in, or no platform chosen), consent-gated content is kept blocked, run as if accepted, or left out, as chosen under **Settings → Plugins → Cookie Connect → Elsewhere**. Necessary content is always printed.
