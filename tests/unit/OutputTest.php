<?php

namespace nineteenninetyfour\cookieconnect\tests\unit;

use Craft;
use craft\web\View;

class OutputTest extends TestCase
{
    private const PAGE = '<!doctype html><html><head><title>x</title></head><body><p>{cookie_declaration}</p></body></html>';

    public function test_it_goes_into_pages_with_the_declaration_token_swapped(): void
    {
        $this->cookiebot('default', ['scripts' => [['name' => 'Hotjar', 'categories' => ['analytics'], 'placement' => 'body_end', 'code' => '<script>hj()</script>']]]);

        $html = $this->plugin()->output->inject(self::PAGE);

        $this->assertStringContainsString("<head>\n<!-- Cookie Connect -->", $html);
        $this->assertStringContainsString('consent.cookiebot.com/uc.js', $html);
        $this->assertStringContainsString('<script id="CookieDeclaration" src="https://consent.cookiebot.com/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d/cd.js"', $html);
        $this->assertStringNotContainsString('{cookie_declaration}', $html);
        $this->assertStringContainsString('data-cookieconsent="statistics" data-cookie-connect-native="analytics">hj()</script>', $html);
    }

    public function test_rendering_a_site_template_puts_it_in_by_itself(): void
    {
        $this->cookiebot('default');
        Craft::$app->getView()->setTemplatesPath(CRAFT_TEMPLATES_PATH);

        // The tests run from the console, so call what the page event calls.
        $html = $this->plugin()->output->inject(Craft::$app->getView()->renderPageTemplate('page', [], View::TEMPLATE_MODE_SITE));

        $this->assertStringContainsString('<!-- Cookie Connect -->', $html);
        $this->assertStringContainsString('data-cookieblock-src="https://www.youtube.com/embed/x" data-cookieconsent="marketing"', $html);
    }

    public function test_console_requests_are_left_alone(): void
    {
        $this->cookiebot('default');

        $this->assertSame(self::PAGE, $this->plugin()->output->injectInto(self::PAGE));
    }

    public function test_the_nonce_comes_from_config(): void
    {
        $this->cookiebot('default');
        $this->pluginSettings(['nonce' => fn () => 'n0nce']);

        $this->assertStringContainsString('<script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d" data-culture="en" type="text/javascript" async nonce="n0nce">', $this->plugin()->output->renderer()->head());
    }

    public function test_the_host_comes_from_the_site(): void
    {
        $context = $this->plugin()->output->context($this->site());

        $this->assertSame('dogear.test', $context->host);
        $this->assertSame('en', $context->lang);
    }
}
