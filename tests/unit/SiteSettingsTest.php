<?php

namespace nineteenninetyfour\cookieconnect\tests\unit;

use Craft;

class SiteSettingsTest extends TestCase
{
    public function test_a_pasted_snippet_is_saved_as_its_id(): void
    {
        $problems = $this->cookiebot('default', ['values' => ['cbid' => '<script id="Cookiebot" src="https://consent.cookiebot.com/uc.js" data-cbid="1A2B3C4D-5E6F-7A8B-9C0D-1E2F3A4B5C6D" async></script>']]);

        $this->assertSame([], $problems);
        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', $this->plugin()->siteSettings->stored($this->site())['values']['cbid']);
    }

    public function test_problems_are_returned_and_the_values_kept(): void
    {
        $problems = $this->cookiebot('default', ['values' => ['cbid' => 'not an id'], 'google' => ['gtmId' => 'GTM-']]);

        $this->assertSame(['values.cbid', 'google.gtmId'], array_keys($problems));
        $this->assertSame('not an id', $this->plugin()->siteSettings->stored($this->site())['values']['cbid']);
        $this->assertSame('incomplete', $this->plugin()->output->renderer($this->site())->state());
    }

    public function test_each_site_has_its_own_platform(): void
    {
        $this->cookiebot('default');
        $this->plugin()->siteSettings->save($this->site('german'), ['provider' => 'usercentrics', 'values' => ['settingsId' => 'y05NPaJsbGnT2v']]);

        $this->assertSame('cookiebot', $this->plugin()->siteSettings->forSite($this->site())->provider);
        $this->assertSame('usercentrics', $this->plugin()->siteSettings->forSite($this->site('german'))->provider);
        $this->assertStringContainsString('data-language="de"', $this->plugin()->output->renderer($this->site('german'))->head());
    }

    public function test_config_wins_reads_env_vars_and_is_reported(): void
    {
        $this->cookiebot('default');
        putenv('TEST_GTM_ID=GTM-XYZ9876');
        $this->pluginSettings(['sites' => [
            '*' => ['google' => ['gtmId' => '$TEST_GTM_ID']],
            'german' => ['provider' => 'onetrust', 'values' => ['domainScript' => 'fff8df06-1dd2-491b-88f6-01cae248cd17']],
        ]]);

        $default = $this->plugin()->siteSettings->forSite($this->site());
        $german = $this->plugin()->siteSettings->forSite($this->site('german'));

        $this->assertSame('cookiebot', $default->provider);
        $this->assertSame('GTM-XYZ9876', $default->google->gtmId);
        $this->assertSame('onetrust', $german->provider);
        $this->assertSame(['google', 'google.gtmId'], $this->plugin()->siteSettings->overridden($this->site()));
        $this->assertContains('values.domainScript', $this->plugin()->siteSettings->overridden($this->site('german')));
        putenv('TEST_GTM_ID');
    }

    public function test_values_fixed_in_config_are_not_saved(): void
    {
        $this->pluginSettings(['sites' => ['*' => ['provider' => 'cookiebot', 'google' => ['gtmId' => 'GTM-XYZ9876']]]]);

        $this->plugin()->siteSettings->save($this->site(), ['provider' => '', 'values' => ['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'], 'google' => ['gtmId' => 'GTM-AAAA1111']]);

        $stored = $this->plugin()->siteSettings->stored($this->site());
        $this->assertArrayNotHasKey('provider', $stored);
        $this->assertArrayNotHasKey('gtmId', $stored['google']);
        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', $stored['values']['cbid']);
    }

    public function test_it_runs_only_in_the_environments_chosen(): void
    {
        $this->cookiebot('default');
        $this->pluginSettings(['environments' => [Craft::$app->env ?? 'production']]);
        $this->assertTrue($this->plugin()->siteSettings->forSite($this->site())->active);

        $this->pluginSettings(['environments' => ['staging']]);
        $this->assertFalse($this->plugin()->siteSettings->forSite($this->site())->active);
        $this->assertSame('off', $this->plugin()->output->renderer($this->site())->state());

        $this->pluginSettings(['environments' => '*']);
        $this->assertTrue($this->plugin()->siteSettings->forSite($this->site())->active);
    }

    public function test_plugin_settings_save_only_what_project_config_can_hold(): void
    {
        $settings = $this->plugin()->getSettings();
        $settings->nonce = fn () => 'abc';

        $this->assertSame(['environments', 'whenOff', 'autoInsert', 'debug'], array_keys($settings->toArray()));

        $settings->setAttributes(['environments' => 'production, staging'], false);
        $this->assertSame(['production', 'staging'], $settings->environments);
    }
}
