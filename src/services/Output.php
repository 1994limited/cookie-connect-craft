<?php

namespace nineteenninetyfour\cookieconnect\services;

use Craft;
use craft\models\Site;
use craft\web\Response;
use craft\web\TemplateResponseFormatter;
use NineteenNinetyFour\CookieConnect\Core\Category;
use NineteenNinetyFour\CookieConnect\Core\Output\Context;
use NineteenNinetyFour\CookieConnect\Core\Output\Injector;
use NineteenNinetyFour\CookieConnect\Core\Output\Renderer;
use nineteenninetyfour\cookieconnect\Plugin;
use yii\base\Component;

/**
 * Cookie Connect's output for the current site and request: what the Twig
 * tags print, and what goes into each page by itself.
 */
class Output extends Component
{
    /** @var array<int, Renderer> */
    private array $renderers = [];

    private ?string $nonce = null;

    private bool $nonceKnown = false;

    public function renderer(?Site $site = null): Renderer
    {
        $site ??= Craft::$app->getSites()->getCurrentSite();

        return $this->renderers[(int) $site->id] ??= new Renderer(
            Plugin::getInstance()->siteSettings->forSite($site),
            $this->context($site),
        );
    }

    /**
     * Forgets the renderers made so far, after settings change.
     */
    public function forget(): void
    {
        $this->renderers = [];
    }

    public function context(Site $site): Context
    {
        $baseUrl = (string) $site->getBaseUrl();
        $host = (string) (parse_url($baseUrl, PHP_URL_HOST) ?: '');

        if ($host === '' && ! Craft::$app->getRequest()->getIsConsoleRequest()) {
            $host = (string) Craft::$app->getRequest()->getHostName();
        }

        return new Context($host, (string) $site->language, $this->nonce());
    }

    /**
     * The CSP nonce from config/cookie-connect.php's `nonce` function, once
     * per request.
     */
    public function nonce(): ?string
    {
        if (! $this->nonceKnown) {
            $this->nonceKnown = true;
            $nonce = Plugin::getInstance()->getSettings()->nonce;
            $value = is_callable($nonce) ? $nonce() : $nonce;
            $this->nonce = is_string($value) && $value !== '' ? $value : null;
        }

        return $this->nonce;
    }

    /**
     * Puts the head and body blocks into a rendered front-end page, and swaps
     * {cookie_declaration} and {cookie_settings} in its content.
     */
    public function injectInto(string $html): string
    {
        $request = Craft::$app->getRequest();

        if ($request->getIsConsoleRequest() || ! $request->getIsSiteRequest() || ! Plugin::getInstance()->getSettings()->autoInsert) {
            return $html;
        }

        $response = Craft::$app->getResponse();

        // Craft renders page templates as the "template" format, then sends them as HTML.
        if ($response instanceof Response && ! in_array($response->format, [Response::FORMAT_HTML, TemplateResponseFormatter::FORMAT], true)) {
            return $html;
        }

        if (! Injector::isPage($html, $response instanceof Response ? (string) $response->getHeaders()->get('content-type') : null)) {
            return $html;
        }

        return $this->inject($html);
    }

    /**
     * Puts Cookie Connect into a page for the current site, whatever the request.
     */
    public function inject(string $html): string
    {
        $renderer = $this->renderer();

        return Injector::inject(
            $html,
            $renderer->head($html),
            $renderer->bodyStart(),
            $renderer->bodyEnd(),
            fn (string $body) => $renderer->replaceTokens($body, $this->settingsText()),
        );
    }

    /**
     * @param  string|list<string>  $categories
     * @param  array<string, mixed>  $options  placeholder: true or a message
     */
    public function gate(string $html, string|array $categories, array $options = []): string
    {
        $renderer = $this->renderer();
        $gated = $renderer->gate($html, $categories);

        if (! empty($options['placeholder']) && $gated !== '') {
            $text = is_string($options['placeholder']) ? $options['placeholder'] : $this->placeholderText($categories);
            $gated = $renderer->placeholder($categories, $text, $this->settingsText()).$gated;
        }

        return $gated;
    }

    public function settingsText(): string
    {
        return Craft::t(Plugin::HANDLE, 'Cookie settings');
    }

    /**
     * @param  string|list<string>  $categories
     */
    public function placeholderText(string|array $categories): string
    {
        $names = array_map(fn (Category $c) => Craft::t(Plugin::HANDLE, $c->value), array_filter(Category::list($categories), fn (Category $c) => $c !== Category::Necessary));

        return Craft::t(Plugin::HANDLE, 'This content needs {categories} cookies. You can allow them in your cookie settings.', [
            'categories' => implode(' & ', $names),
        ]);
    }
}
