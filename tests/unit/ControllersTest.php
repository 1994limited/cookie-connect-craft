<?php

namespace nineteenninetyfour\cookieconnect\tests\unit;

use Craft;
use craft\elements\User;
use craft\web\TemplateResponseFormatter;
use nineteenninetyfour\cookieconnect\Plugin;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ControllersTest extends TestCase
{
    protected function _before(): void
    {
        parent::_before();

        Craft::$app->getRequest()->enableCsrfValidation = false;
        $this->plugin()->controllerNamespace = 'nineteenninetyfour\\cookieconnect\\controllers';
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity(null);
        unset($_SERVER['REQUEST_METHOD']);

        parent::_after();
    }

    private function actingAs(?array $permissions = null): User
    {
        $user = new User(['username' => 'ada'.bin2hex(random_bytes(2)), 'email' => bin2hex(random_bytes(3)).'@example.com', 'admin' => $permissions === null]);
        $this->assertTrue(Craft::$app->getElements()->saveElement($user));

        if ($permissions !== null) {
            Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accesscp', ...$permissions]);
        }

        Craft::$app->getUser()->setIdentity($user);

        return $user;
    }

    private function action(string $route, array $params = [], string $method = 'GET', array $body = []): Response
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        Craft::$app->getRequest()->setBodyParams($body);
        Craft::$app->getRequest()->setQueryParams($params);
        Craft::$app->getResponse()->clear();

        $response = Craft::$app->runAction($route, $params);

        if ($response->format === TemplateResponseFormatter::FORMAT) {
            (new TemplateResponseFormatter)->format($response);
        }

        return $response;
    }

    public function test_the_page_needs_the_permission(): void
    {
        $this->actingAs([]);

        $this->expectException(ForbiddenHttpException::class);
        $this->action('cookie-connect/sites/edit');
    }

    public function test_an_editor_with_the_permission_can_see_and_save_each_site(): void
    {
        $this->actingAs([Plugin::PERMISSION]);

        $page = $this->action('cookie-connect/sites/edit', ['site' => 'german']);
        $this->assertSame(200, $page->getStatusCode());

        $this->action('cookie-connect/sites/save', method: 'POST', body: [
            'siteId' => $this->site('german')->id,
            'settings' => [
                'provider' => 'cookiebot',
                'providerValues' => [
                    'cookiebot' => ['cbid' => 'data-cbid="1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d"', 'cdn' => 'eu', 'autoBlocking' => '1', 'tcf' => ''],
                    'onetrust' => ['domainScript' => 'ignored'],
                ],
                'google' => ['gtmId' => "'GTM-AB12CD3'", 'consentMode' => '1', 'mode' => 'basic'],
                'scripts' => [['name' => 'Hotjar', 'category' => 'analytics', 'placement' => 'head', 'code' => '<script>hj()</script>', 'enabled' => '1']],
            ],
        ]);

        $stored = $this->plugin()->siteSettings->stored($this->site('german'));
        $this->assertSame(['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', 'cdn' => 'eu', 'autoBlocking' => true, 'tcf' => false], $stored['values']);
        $this->assertSame('GTM-AB12CD3', $stored['google']['gtmId']);
        $this->assertSame(['analytics'], $stored['scripts'][0]['categories']);
        $this->assertSame([], $this->plugin()->siteSettings->stored($this->site()), 'The other site is untouched.');

        $page = $this->action('cookie-connect/sites/edit', ['site' => 'german']);
        $this->assertStringContainsString('Cookiebot is live on this site.', (string) $page->content);
    }

    public function test_saving_with_the_platform_fixed_in_config_keeps_its_values(): void
    {
        $this->actingAs();
        $this->pluginSettings(['sites' => ['*' => ['provider' => 'cookiebot']]]);

        // The disabled select isn't posted.
        $this->action('cookie-connect/sites/save', method: 'POST', body: [
            'siteId' => $this->site()->id,
            'settings' => ['providerValues' => ['cookiebot' => ['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d']]],
        ]);

        $this->assertSame('1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d', $this->plugin()->siteSettings->stored($this->site())['values']['cbid']);
        $this->assertSame('live', $this->plugin()->output->renderer($this->site())->state());
    }

    public function test_the_page_shows_values_set_in_config_as_fixed(): void
    {
        $this->actingAs();
        $this->pluginSettings(['sites' => ['*' => ['provider' => 'cookiebot', 'values' => ['cbid' => '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d']]]]);

        $html = (string) $this->action('cookie-connect/sites/edit')->content;

        $this->assertStringContainsString('Set in config/cookie-connect.php.', $html);
    }
}
