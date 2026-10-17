<?php

namespace nineteenninetyfour\cookieconnect\tests\unit;

class TwigTest extends TestCase
{
    public function test_the_consent_tag_uses_the_platforms_markup(): void
    {
        $this->cookiebot();

        $html = $this->render("{% consent 'analytics' %}<script>track({{ n }})</script>{% endconsent %}", ['n' => 1]);

        $this->assertSame('<script type="text/plain" data-cookieconsent="statistics" data-cookie-connect-native="analytics">track(1)</script>', $html);
    }

    public function test_switching_platform_needs_no_template_change(): void
    {
        $twig = "{% consent 'analytics' %}<script>track()</script>{% endconsent %}";

        $this->plugin()->siteSettings->save($this->site(), ['provider' => 'onetrust', 'values' => ['domainScript' => 'fff8df06-1dd2-491b-88f6-01cae248cd17']]);
        $this->assertStringContainsString('class="optanon-category-C0002"', $this->render($twig));

        $this->plugin()->siteSettings->save($this->site(), ['provider' => 'termly', 'values' => ['websiteUuid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d']]);
        $this->assertStringContainsString('data-categories="analytics"', $this->render($twig));
    }

    public function test_several_categories_and_a_placeholder(): void
    {
        $this->cookiebot();

        $html = $this->render("{% consent ['analytics', 'marketing'] with { placeholder: true } %}<iframe src=\"https://x.test\"></iframe>{% endconsent %}");

        $this->assertStringContainsString('<div class="cookie-connect-placeholder" data-cookie-connect-placeholder="analytics marketing"><p>This content needs analytics &amp; marketing cookies.', $html);
        $this->assertStringContainsString('data-cookieconsent="statistics,marketing"', $html);
    }

    public function test_the_function_and_filter(): void
    {
        $this->cookiebot();

        $this->assertStringContainsString('data-cookieconsent="marketing"', $this->render("{{ consent('marketing', '<script>a()</script>') }}"));
        $this->assertStringContainsString('data-cookieconsent="preferences"', $this->render("{{ html|consent('preferences') }}", ['html' => '<script>b()</script>']));
    }

    public function test_the_head_declaration_settings_link_and_tokens(): void
    {
        $this->cookiebot();

        $this->assertStringStartsWith('<!-- Cookie Connect -->', $this->render('{{ consentHead() }}'));
        $this->assertStringContainsString('id="CookieDeclaration"', $this->render('{{ consentDeclaration() }}'));
        $this->assertSame(
            '<button type="button" class="cookie-connect-settings" data-cookie-connect-open>Cookie settings</button>',
            $this->render('{{ consentSettingsLink() }}'),
        );
        $this->assertSame(
            '<a href="#cookie-settings" class="cookie-connect-settings footer" data-cookie-connect-open>Privacy</a>',
            $this->render("{{ consentSettingsLink('Privacy', { class: 'footer' }, 'a') }}"),
        );
        $this->assertStringContainsString('id="CookieDeclaration"', $this->render("{{ '<p>{cookie_declaration}</p>'|consentTokens }}"));
        $this->assertStringStartsWith("'sha256-", $this->render('{% set head = consentHead() %}{{ consentCspHashes()|first }}'));
    }

    public function test_craft_cookie_connect_variable(): void
    {
        $this->cookiebot();

        $this->assertSame('live Cookiebot', $this->render('{{ craft.cookieConnect.state }} {{ craft.cookieConnect.provider.name }}'));
    }

    public function test_with_no_platform_content_stays_blocked(): void
    {
        $this->assertSame(
            '<script type="text/plain" data-cookie-connect="marketing">a()</script>',
            $this->render("{% consent 'marketing' %}<script>a()</script>{% endconsent %}"),
        );

        $this->pluginSettings(['whenOff' => 'run']);
        $this->assertSame('<script>a()</script>', $this->render("{% consent 'marketing' %}<script>a()</script>{% endconsent %}"));
    }
}
