<?php

namespace nineteenninetyfour\cookieconnect\tests\unit;

use Codeception\Test\Unit;
use Craft;
use craft\models\Site;
use craft\web\View;
use nineteenninetyfour\cookieconnect\Plugin;
use UnitTester;

abstract class TestCase extends Unit
{
    protected UnitTester $tester;

    protected function _before(): void
    {
        parent::_before();

        // Craft's own tests run in their own environment, so run everywhere unless a test says otherwise.
        $this->pluginSettings(['environments' => '*', 'whenOff' => 'block', 'autoInsert' => true, 'debug' => false, 'sites' => [], 'nonce' => null]);
        $this->plugin()->output->forget();
    }

    protected function plugin(): Plugin
    {
        return Plugin::getInstance();
    }

    /**
     * Plugin settings for this test, without saving them.
     *
     * @param  array<string, mixed>  $values
     */
    protected function pluginSettings(array $values): void
    {
        $settings = $this->plugin()->getSettings();

        foreach ($values as $key => $value) {
            $settings->$key = $value;
        }

        $this->plugin()->output->forget();
    }

    protected function site(string $handle = 'default'): Site
    {
        $site = Craft::$app->getSites()->getSiteByHandle($handle);
        $this->assertNotNull($site);

        return $site;
    }

    /**
     * Saves Cookiebot for a site, as the control panel would.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function cookiebot(string $site = 'default', array $data = []): array
    {
        return $this->plugin()->siteSettings->save($this->site($site), array_replace_recursive([
            'provider' => 'cookiebot',
            'values' => ['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d'],
        ], $data));
    }

    protected function render(string $twig, array $variables = []): string
    {
        return Craft::$app->getView()->renderString($twig, $variables, View::TEMPLATE_MODE_SITE);
    }
}
